"""Baja imágenes del WordPress viejo de Atardecer Deportivo a assets/wp/ (espeja la ruta de wp-content/uploads)."""
import html, json, re, sys, time, urllib.request
from pathlib import Path

ROOT = Path(sys.argv[1])
SITE = "https://atardecerdeportivocr.com"
UP = SITE + "/wp-content/uploads/"
OUT = ROOT / "assets" / "wp"
DATA = ROOT / "assets" / "data"
UA = {"User-Agent": "Mozilla/5.0 (AtardecerDeportivo redesign asset sync)"}
# categoría -> cuántas notas recientes traer
CATS = {"futbol": 24, "otras-disciplinas": 16, "baul-de-los-recuerdos": 10, "atardecer-deportivo": 10, "general": 10, "opinion-del-director": 10}


def get(url):
    req = urllib.request.Request(url, headers=UA)
    with urllib.request.urlopen(req, timeout=60) as r:
        return r.read(), dict(r.headers)


def get_json(url):
    body, headers = get(url)
    return json.loads(body), headers


def download(src):
    if not src.startswith(UP):
        return None
    rel = src[len(UP):].split("?")[0]
    dest = OUT / rel
    if not dest.exists():
        body, _ = get(src)
        dest.parent.mkdir(parents=True, exist_ok=True)
        dest.write_bytes(body)
        time.sleep(0.15)
    return "assets/wp/" + rel


def best_size(media):
    sizes = (media.get("media_details") or {}).get("sizes") or {}
    for k in ("large", "medium_large", "full"):
        if k in sizes:
            return sizes[k]["source_url"]
    return media.get("source_url")


def main():
    DATA.mkdir(parents=True, exist_ok=True)
    cats, _ = get_json(SITE + "/wp-json/wp/v2/categories?per_page=50&_fields=id,name,slug")
    by_slug = {c["slug"]: c for c in cats}

    # 1) Imágenes que el diseño ya referencia
    referenced = set()
    for f in ROOT.glob("*.dc.html"):
        txt = f.read_text(encoding="utf-8")
        referenced |= {UP + m for m in re.findall(r"U \+ '([^']+)'", txt)}
        referenced |= set(re.findall(re.escape(UP) + r"[^\"' )]+\.(?:jpe?g|png|webp|avif|gif)", txt))
        block = re.search(r"const sponsors = \[(.*?)\]", txt, re.S)
        if block:
            referenced |= {UP + m for m in re.findall(r"'([^']+)'", block.group(1))}
    failed = []
    for src in sorted(referenced):
        try:
            download(src)
        except Exception as e:  # noqa: BLE001
            failed.append((src, str(e)))

    # 2) Notas recientes por sección con su imagen destacada
    posts = []
    for slug, n in CATS.items():
        c = by_slug.get(slug)
        if not c:
            continue
        items, _ = get_json(f"{SITE}/wp-json/wp/v2/posts?categories={c['id']}&per_page={n}&_embed=wp:featuredmedia")
        for p in items:
            media = ((p.get("_embedded") or {}).get("wp:featuredmedia") or [{}])[0]
            src = best_size(media) if media.get("source_url") else None
            local = None
            if src:
                try:
                    local = download(src)
                except Exception as e:  # noqa: BLE001
                    failed.append((src, str(e)))
            posts.append({
                "id": p["id"], "section": slug, "sectionName": html.unescape(c["name"]),
                "date": p["date"], "title": html.unescape(re.sub("<[^>]+>", "", p["title"]["rendered"])),
                "link": p["link"], "img": local, "imgRemote": src,
            })
    (DATA / "posts.json").write_text(json.dumps(posts, ensure_ascii=False, indent=1), encoding="utf-8")

    # 3) Catálogo completo de la mediateca (solo URLs, para ir montando más después)
    catalog, page = [], 1
    while True:
        items, headers = get_json(f"{SITE}/wp-json/wp/v2/media?per_page=100&page={page}&_fields=id,date,source_url,mime_type,alt_text,post")
        catalog += [{"id": m["id"], "date": m["date"], "url": m["source_url"], "mime": m["mime_type"], "alt": m.get("alt_text", ""), "post": m.get("post")} for m in items]
        if page >= int(headers.get("X-WP-TotalPages") or headers.get("x-wp-totalpages") or 1):
            break
        page += 1
        time.sleep(0.2)
    (DATA / "media-catalog.json").write_text(json.dumps(catalog, ensure_ascii=False, indent=1), encoding="utf-8")

    files = list(OUT.rglob("*.*"))
    print(f"referenciadas={len(referenced)} notas={len(posts)} con_img={sum(1 for p in posts if p['img'])} "
          f"archivos={len(files)} MB={sum(f.stat().st_size for f in files)/1e6:.1f} catalogo={len(catalog)}")
    for src, err in failed:
        print("FALLO", src, err)


if __name__ == "__main__":
    main()
