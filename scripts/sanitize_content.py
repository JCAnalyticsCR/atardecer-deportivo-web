"""Sanea el volcado del WordPress viejo y deja un paquete listo para importar al sitio nuevo.

Uso: python scripts/sanitize_content.py migracion/raw wordpress/import
- Quita <script>, <form>, <object>, <embed>, <meta>, <style> y atributos on*.
- Solo conserva <iframe> de YouTube, X/Twitter, Instagram, Facebook y TikTok.
- Enlaces a medios deportivos/redes: se conservan. Enlaces a cualquier otro dominio: se quita el enlace
  y, si el texto es spam o la nota fue editada en una oleada de la inyección, también el texto.
- Clasifica fútbol en Nacional / Internacional por palabras clave.
Salida: content.json (posts, pages, categories) e images.txt (URLs de imágenes a traer).
"""
import html, json, re, sys, urllib.parse
from collections import Counter
from pathlib import Path

RAW, OUT = Path(sys.argv[1]), Path(sys.argv[2])
SITE_HOST = "atardecerdeportivocr.com"
UPLOADS = f"https://{SITE_HOST}/wp-content/uploads/"

KEEP_LINK = re.compile(
    r"(^|\.)(atardecerdeportivocr\.com|tudn\.com|futbolcentroamerica\.com|mediotiempo\.com|espn\.com(\.mx)?|espndeportes\.espn\.com|"
    r"tvazteca\.com|aztecadeportes\.com|azteca7\.com|univision\.com|televisa\.com|fifa\.com|concacaf\.com|uefa\.com|ligamx\.net|"
    r"fcbarcelona\.es|realmadrid\.com|nba\.com|mlb\.com|nfl\.com|basketball-reference\.com|tycsports\.com|ole\.com\.ar|marca\.com|as\.com|"
    r"nytimes\.com|miamiherald\.com|laopinion\.com|independentespanol\.com|metroworldnews\.com|metroecuador\.com\.ec|telediario\.mx|"
    r"mdzol\.com|msn\.com|adn40\.mx|nacion\.com|crhoy\.com|teletica\.com|repretel\.com|fedefutbol\.com|"
    r"youtube\.com|youtu\.be|twitter\.com|x\.com|instagram\.com|facebook\.com|tiktok\.com)$", re.I)
EMBED_HOST = re.compile(r"(^|\.)(youtube\.com|youtube-nocookie\.com|twitter\.com|x\.com|instagram\.com|facebook\.com|tiktok\.com)$", re.I)
SPAM = re.compile(r"\b(nude|naked|porn|xxx|sex|fuck|escort|onlyfans|feet|casino|bet|slot|gacor|togel|viagra|cialis|semaglutide|ozempic|"
                  r"payday|loan|crypto|выплаты)\b", re.I)
# Oleadas de la inyección detectadas en el sitio viejo
LATE_EDIT_FROM = "2026-01"

NACIONAL = re.compile(r"\b(saprissa|alajuelense|liga deportiva alajuelense|herediano|cartagin[eé]s|puntarenas|san carlos|p[eé]rez zeled[oó]n|"
                      r"guanacasteca|sporting f\.?c|liberia|santos de gu[aá]pil(es)?|municipal grecia|liga prom[eé]rica|primera divisi[oó]n de costa rica|"
                      r"la sele\b|selecci[oó]n (nacional )?de costa rica|fedefutbol|unafut|ticos?\b|costarricense)", re.I)

DROP_TAGS = re.compile(r"<(script|style|form|object|embed|meta|link|noscript)\b.*?(</\1\s*>|/?>)", re.I | re.S)
ON_ATTR = re.compile(r"\s+on[a-z]+\s*=\s*(\"[^\"]*\"|'[^']*'|[^\s>]+)", re.I)
HIDDEN_STYLE = re.compile(r"\s+style\s*=\s*([\"'])[^\"']*(display\s*:\s*none|visibility\s*:\s*hidden|font-size\s*:\s*0|left\s*:\s*-\d{3,}px)[^\"']*\1", re.I)
ANCHOR = re.compile(r"<a\b([^>]*)>(.*?)</a>", re.I | re.S)
IFRAME = re.compile(r"<iframe\b[^>]*\bsrc=[\"']([^\"']+)[\"'][^>]*>.*?</iframe>", re.I | re.S)
IMG_SRC = re.compile(r"https://atardecerdeportivocr\.com/wp-content/uploads/[^\"'\s)>,]+\.(?:jpe?g|png|webp|avif|gif)", re.I)


def host(url):
    return (urllib.parse.urlparse(html.unescape(url)).hostname or "").lower()


def clean(content, late_edit, stats):
    content = DROP_TAGS.sub(lambda m: stats.update(["tag_quitado"]) or "", content)
    content = ON_ATTR.sub(lambda m: stats.update(["atributo_on"]) or "", content)
    content = IFRAME.sub(lambda m: m.group(0) if EMBED_HOST.search(host(m.group(1))) else (stats.update(["iframe_quitado"]) or ""), content)

    def fix_anchor(m):
        attrs, text = m.group(1), m.group(2)
        href = re.search(r"href\s*=\s*[\"']([^\"']*)[\"']", attrs, re.I)
        h = host(href.group(1)) if href else ""
        plain = re.sub(r"<[^>]+>", "", text)
        if not h or KEEP_LINK.search(h):
            if HIDDEN_STYLE.search(attrs) or SPAM.search(plain):
                stats.update(["enlace_spam_quitado"])
                return ""
            return m.group(0)
        if SPAM.search(plain) or late_edit or HIDDEN_STYLE.search(attrs):
            stats.update(["enlace_spam_quitado"])
            return ""
        stats.update(["enlace_desvinculado"])
        return text

    content = ANCHOR.sub(fix_anchor, content)
    content = HIDDEN_STYLE.sub(lambda m: stats.update(["css_oculto"]) or "", content)
    # párrafos que quedaron vacíos tras quitar spam
    return re.sub(r"<p>\s*(&nbsp;|\s)*</p>", "", content)


def text(rendered):
    return html.unescape(re.sub(r"<[^>]+>", "", rendered or "")).strip()


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    load = lambda n: json.loads((RAW / f"{n}.json").read_text(encoding="utf-8"))
    posts, pages, cats, users = load("posts"), load("pages"), load("categories"), load("users")
    media = json.loads(Path("assets/data/media-catalog.json").read_text(encoding="utf-8"))
    media_url = {m["id"]: m["url"] for m in media}

    spam_cats = {c["id"] for c in cats if SPAM.search(c["name"]) or re.search(r"semaglutide", c["slug"], re.I)}
    keep_cats = [c for c in cats if c["id"] not in spam_cats]
    cat_slug = {c["id"]: c["slug"] for c in keep_cats}
    author_name = {u["id"]: u["name"] for u in users}

    stats, images, out_posts = Counter(), set(), []
    for p in posts:
        late = p["date"][:7] < LATE_EDIT_FROM and p["modified"][:7] >= LATE_EDIT_FROM
        body = clean(p["content"]["rendered"], late, stats)
        title = text(p["title"]["rendered"])
        slugs = [cat_slug[c] for c in p["categories"] if c in cat_slug]
        if "futbol" in slugs:
            slugs.append("futbol-nacional" if NACIONAL.search(title + " " + text(body)[:600]) else "futbol-internacional")
        feat = media_url.get(p.get("featured_media"))
        images |= set(IMG_SRC.findall(body))
        if feat and feat.startswith(UPLOADS):
            images.add(feat)
        out_posts.append({
            "old_id": p["id"], "slug": p["slug"], "date": p["date"], "title": title, "content": body,
            "excerpt": text(p["excerpt"]["rendered"]), "categories": slugs, "sticky": p.get("sticky", False),
            "author": author_name.get(p["author"], "Miguel Delgado"), "featured": feat if feat and feat.startswith(UPLOADS) else None,
            "late_edit": late,
        })

    out_pages = []
    for pg in pages:
        if pg["slug"] in ("inicio", "blog", "top-categories"):
            continue  # el tema nuevo genera la portada y los archivos
        body = clean(pg["content"]["rendered"], True, stats)
        images |= set(IMG_SRC.findall(body))
        out_pages.append({"old_id": pg["id"], "slug": pg["slug"], "date": pg["date"], "title": text(pg["title"]["rendered"]), "content": body})

    new_cats = [{"slug": c["slug"], "name": text(c["name"]).title() if c["name"].isupper() else text(c["name"]),
                 "description": c.get("description", ""), "parent": None}
                for c in keep_cats if c["slug"] != "general"]
    new_cats += [{"slug": "futbol-nacional", "name": "Fútbol Nacional", "description": "", "parent": "futbol"},
                 {"slug": "futbol-internacional", "name": "Fútbol Internacional", "description": "", "parent": "futbol"}]

    (OUT / "content.json").write_text(json.dumps({"posts": out_posts, "pages": out_pages, "categories": new_cats},
                                                 ensure_ascii=False), encoding="utf-8")
    (OUT / "images.txt").write_text("\n".join(sorted(images)), encoding="utf-8")
    sys.stdout.reconfigure(encoding="utf-8")
    print("posts", len(out_posts), "páginas", len(out_pages), "categorías", [c["slug"] for c in new_cats])
    print("categorías spam descartadas:", [c["name"] for c in cats if c["id"] in spam_cats])
    print("limpieza:", dict(stats))
    print("fútbol nacional:", sum("futbol-nacional" in p["categories"] for p in out_posts),
          "internacional:", sum("futbol-internacional" in p["categories"] for p in out_posts))
    print("imágenes a traer:", len(images))


if __name__ == "__main__":
    main()
