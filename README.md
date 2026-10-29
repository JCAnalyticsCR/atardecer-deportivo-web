# Atardecer Deportivo — rediseño web

Propuestas de diseño frontend para el nuevo sitio de Atardecer Deportivo.

## Archivos

- `Atardecer Deportivo - Pizarra.dc.html` — dirección elegida (Pizarra · táctica nocturna), escritorio y móvil.
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
