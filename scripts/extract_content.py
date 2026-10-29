"""Extrae todo el contenido público del WordPress viejo vía REST y lo escanea en busca de inyecciones.

Uso: python scripts/extract_content.py <carpeta_salida>
Salida: posts.json, pages.json, categories.json, tags.json, users.json y scan-report.json
"""
import html, json, re, sys, time, urllib.parse, urllib.request
from collections import Counter
from pathlib import Path

SITE = "https://atardecerdeportivocr.com"
UA = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130 Safari/537.36"}
OUT = Path(sys.argv[1])

# Dominios que esperamos ver en notas legítimas (embeds y fuentes)
ALLOWED = re.compile(r"(^|\.)(atardecerdeportivocr\.com|youtube\.com|youtu\.be|twitter\.com|x\.com|t\.co|instagram\.com|facebook\.com|fb\.watch|tiktok\.com|wp\.com|gravatar\.com|w\.org|wordpress\.org|google\.com|goo\.gl|bit\.ly)$")
SPAM_WORDS = re.compile(r"\b(nude|naked|porn|xxx|sex|escort|viagra|cialis|semaglutide|ozempic|casino|slot|gacor|togel|judi|payday|loan|crypto|bitcoin|replica|essay)\b", re.I)
HIDDEN = re.compile(r"display\s*:\s*none|visibility\s*:\s*hidden|font-size\s*:\s*0|left\s*:\s*-\d{3,}px|height\s*:\s*0", re.I)


def get_all(endpoint, fields):
    items, page = [], 1
    while True:
        url = f"{SITE}/wp-json/wp/v2/{endpoint}?per_page=100&page={page}&_fields={fields}"
        req = urllib.request.Request(url, headers=UA)
        try:
            with urllib.request.urlopen(req, timeout=90) as r:
                batch = json.loads(r.read())
                total = int(r.headers.get("X-WP-TotalPages") or 1)
        except urllib.error.HTTPError as e:
            if e.code == 400:  # página fuera de rango
                break
            raise
        items += batch
        print(f"{endpoint}: página {page}/{total}", flush=True)
        if page >= total:
            break
        page += 1
        time.sleep(0.3)
    return items


def scan(kind, item):
    content = item.get("content", {}).get("rendered", "") + item.get("excerpt", {}).get("rendered", "")
    title = html.unescape(item.get("title", {}).get("rendered", ""))
    findings = []
    for tag in re.findall(r"<(script|iframe|object|embed|form|meta)\b[^>]*>", content, re.I):
        findings.append(("tag", tag.lower()))
    for href, text in re.findall(r"<a\b[^>]*href=[\"']([^\"']+)[\"'][^>]*>(.*?)</a>", content, re.I | re.S):
        host = urllib.parse.urlparse(href).hostname or ""
        if host and not ALLOWED.search(host):
            findings.append(("link_externo", f"{host} :: {re.sub('<[^>]+>', '', text)[:60]}"))
        if SPAM_WORDS.search(text):
            findings.append(("texto_spam", f"{host} :: {text[:60]}"))
    if HIDDEN.search(content):
        findings.append(("css_oculto", HIDDEN.search(content).group(0)))
    if SPAM_WORDS.search(title):
        findings.append(("titulo_spam", title[:80]))
    return [{"kind": kind, "id": item["id"], "slug": item.get("slug"), "date": item.get("date"),
             "modified": item.get("modified"), "title": title[:100], "type": t, "detail": d} for t, d in findings]


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    common = "id,date,modified,slug,status,link,title,content,excerpt,author,featured_media"
    data = {
        "posts": get_all("posts", common + ",categories,tags,format,sticky"),
        "pages": get_all("pages", common + ",parent,menu_order"),
        "categories": get_all("categories", "id,name,slug,description,parent,count"),
        "tags": get_all("tags", "id,name,slug,count"),
        "users": get_all("users", "id,name,slug"),
    }
    for name, items in data.items():
        (OUT / f"{name}.json").write_text(json.dumps(items, ensure_ascii=False), encoding="utf-8")

    findings = [f for kind in ("posts", "pages") for it in data[kind] for f in scan(kind, it)]
    (OUT / "scan-report.json").write_text(json.dumps(findings, ensure_ascii=False, indent=1), encoding="utf-8")

    print({k: len(v) for k, v in data.items()})
    print("hallazgos por tipo:", Counter(f["type"] for f in findings))
    print("items afectados:", len({(f["kind"], f["id"]) for f in findings}))
    print("dominios externos top:", Counter(f["detail"].split(" :: ")[0] for f in findings if f["type"] == "link_externo").most_common(25))


if __name__ == "__main__":
    main()
