"""Descarga las imágenes listadas en images.txt y verifica que cada archivo sea de verdad una imagen.

Uso: python scripts/fetch_uploads.py wordpress/import
Rechaza cualquier archivo cuyo contenido no empiece con la firma de JPEG/PNG/GIF/WebP/AVIF
(así no entra un .php disfrazado de .jpg desde el servidor de origen).
"""
import concurrent.futures as cf, sys, urllib.parse, urllib.request
from pathlib import Path

BASE = Path(sys.argv[1])
UP = "https://atardecerdeportivocr.com/wp-content/uploads/"
UA = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130 Safari/537.36"}


def is_image(b):
    return (b[:3] == b"\xff\xd8\xff" or b[:8] == b"\x89PNG\r\n\x1a\n" or b[:6] in (b"GIF87a", b"GIF89a")
            or (b[:4] == b"RIFF" and b[8:12] == b"WEBP") or b[4:12] in (b"ftypavif", b"ftypavis", b"ftypheic", b"ftypmif1"))


def fetch(url):
    dest = BASE / "uploads" / url[len(UP):]
    if dest.exists():
        return "ya", url
    try:
        with urllib.request.urlopen(urllib.request.Request(urllib.parse.quote(url, safe=":/%"), headers=UA), timeout=60) as r:
            body = r.read()
    except Exception as e:  # noqa: BLE001
        return f"error {e}", url
    if not is_image(body[:16]) or b"<?php" in body:
        return "rechazado", url
    dest.parent.mkdir(parents=True, exist_ok=True)
    dest.write_bytes(body)
    return "ok", url


def main():
    urls = [u for u in (BASE / "images.txt").read_text(encoding="utf-8").split() if u.startswith(UP)]
    results = {"ok": 0, "ya": 0, "rechazado": [], "error": []}
    with cf.ThreadPoolExecutor(max_workers=4) as pool:
        for i, (status, url) in enumerate(pool.map(fetch, urls), 1):
            key = status.split()[0]
            if key in ("ok", "ya"):
                results[key] += 1
            else:
                results[key].append(f"{status} {url}")
            if i % 200 == 0:
                print(f"{i}/{len(urls)}", flush=True)
    size = sum(f.stat().st_size for f in (BASE / "uploads").rglob("*") if f.is_file())
    print(f"ok={results['ok']} ya={results['ya']} rechazados={len(results['rechazado'])} errores={len(results['error'])} MB={size/1e6:.0f}")
    for line in results["rechazado"] + results["error"][:30]:
        print(line)


if __name__ == "__main__":
    main()
