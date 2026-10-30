# Atardecer Deportivo — rediseño web

Propuestas de diseño frontend para el nuevo sitio de Atardecer Deportivo.

## Archivos

- `index.html` — la portada publicada: Pizarra ya armada como HTML normal (sin `support.js`), liviana en móvil.
  Se genera con `scripts/hornear_portada.py` (instrucciones dentro); no se edita a mano.
- `Atardecer Deportivo - Pizarra.dc.html` — fuente editable de la dirección elegida (Pizarra · táctica nocturna).
- `assets/video/` — video de entrada (24 fps constantes) y su póster.
- `Atardecer Deportivo - 3 Ideas.dc.html` — las tres direcciones visuales exploradas.
- `support.js` — runtime que renderiza los `.dc.html` (generado, no editar).
- `assets/wp/` — imágenes traídas del sitio actual (misma ruta que `wp-content/uploads/`).
- `assets/data/posts.json` — notas recientes por sección con su imagen local.
- `assets/data/media-catalog.json` — catálogo completo de la mediateca del sitio actual (solo URLs).
- `scripts/pull_images.py` — vuelve a sincronizar imágenes desde el sitio actual.

## Ver el diseño

```bash
python -m http.server 5173
```

Luego abrir <http://localhost:5173/>.

## Traer más imágenes

Agregar la ruta (`U + 'AAAA/MM/archivo.ext'`) en el diseño y correr:

```bash
python scripts/pull_images.py .
```
