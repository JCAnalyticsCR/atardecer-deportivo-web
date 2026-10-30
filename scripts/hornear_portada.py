"""Genera index.html: la portada Pizarra ya armada, sin el motor de Claude Design (support.js).

El .dc.html se arma en el navegador con support.js; en un teléfono eso compite con el video de entrada y se
siente lento. Este script toma el DOM ya renderizado y lo guarda como HTML normal, con las fotos de abajo en
carga diferida.

Uso (con el servidor del diseño corriendo: python -m http.server 5173):
  chrome --headless=new --virtual-time-budget=8000 --dump-dom \
      "http://localhost:5173/Atardecer%20Deportivo%20-%20Pizarra.dc.html" > dom.html
  python scripts/hornear_portada.py dom.html "Atardecer Deportivo - Pizarra.dc.html" index.html
"""
import re
import sys
from pathlib import Path

dom_path, src_path, out_path = sys.argv[1:4]
dom = Path(dom_path).read_text(encoding="utf-8")
src = Path(src_path).read_text(encoding="utf-8")

# Portada ya armada: el contenido de #dc-root (termina donde empiezan los scripts del body)
start = dom.index('<div id="dc-root">') + len('<div id="dc-root">')
end = dom.index("<script", start)
page = dom[start:end].rstrip()
assert page.endswith("</div>"), "no se encontró el cierre de #dc-root"
page = page[: -len("</div>")].strip()
assert "{{" not in page, "quedaron marcas de plantilla sin resolver"

# Piezas del archivo fuente
helmet = re.search(r"<helmet>(.*?)</helmet>", src, re.S).group(1).strip()
intro_css = re.search(r"<style>\n(/\* Portada de entrada.*?)</style>\n</head>", src, re.S).group(1)
intro = src[src.index("<body>\n") + len("<body>\n"): src.index("<x-dc>")].strip()
tail = src[src.rindex("<script>"): src.rindex("</body>")].strip()

# Fotos: solo el logo y la nota principal cargan de entrada; el resto, al acercarse
imgs = 0


def lazy(m):
    global imgs
    imgs += 1
    tag = m.group(0)
    if "loading=" in tag:
        return tag
    extra = ' fetchpriority="high"' if imgs == 2 else ("" if imgs <= 2 else ' loading="lazy"')
    return tag[:-1] + extra + ' decoding="async">'


page = re.sub(r"<img\b[^>]*>", lazy, page)

html = f"""<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Atardecer Deportivo — Información de alto nivel</title>
<meta name="description" content="Fútbol nacional, internacional y otras disciplinas, todos los días.">
<link rel="preload" as="image" href="assets/video/senal-atardecer.webp">
{helmet}
<style>
{intro_css.strip()}
</style>
</head>
<body>
{intro}
<div id="dc-root">
{page}
</div>
{tail}
</body>
</html>
"""
Path(out_path).write_text(html, encoding="utf-8", newline="\n")
print(f"{out_path}: {len(html) // 1024} KB, {imgs} imágenes ({max(imgs - 2, 0)} diferidas)")
