#!/usr/bin/env bash
# Configura un WordPress recién instalado y vacío: activa el tema, importa el contenido saneado y arma menús.
# Sirve igual en el hosting (por SSH), en Local o en Playground; solo necesita WP-CLI.
#
# Uso: bash setup.sh <ruta-de-wordpress> <url-del-sitio>
#   ej: bash setup.sh ~/domains/atardecerdeportivocr.com/public_html https://atardecerdeportivocr.com
set -euo pipefail

WP_PATH="${1:?ruta de WordPress}"
SITE_URL="${2:?URL del sitio}"
HERE="$(cd "$(dirname "$0")" && pwd)"
export AD_IMPORT_DIR="$HERE/import"

wp() { command wp --path="$WP_PATH" "$@"; }

wp core is-installed || { echo "Primero instalá WordPress en $WP_PATH (wp core install)."; exit 1; }

# Todo al día antes de empezar: WordPress, traducciones y PHP soportado
php -r 'exit(version_compare(PHP_VERSION, "8.4", ">=") ? 0 : 1);' || echo "AVISO: PHP $(php -r 'echo PHP_VERSION;') — en hPanel → Avanzado → Configuración PHP elegí la versión más nueva (8.5)."
wp core update
wp core update-db

# Tema propio
rm -rf "$WP_PATH/wp-content/themes/atardecer-pizarra"
cp -r "$HERE/theme/atardecer-pizarra" "$WP_PATH/wp-content/themes/"
wp theme activate atardecer-pizarra
for t in $(wp theme list --status=inactive --field=name); do wp theme delete "$t"; done
wp plugin delete akismet hello 2>/dev/null || true

# Ajustes
wp language core install es_CR --activate || wp language core install es_ES --activate
wp option update blogname "Atardecer Deportivo"
wp option update blogdescription "Información de alto nivel"
wp option update timezone_string "America/Costa_Rica"
wp option update date_format "j \d\e F \d\e Y"
wp option update posts_per_page 12
wp option update default_comment_status closed
wp option update users_can_register 0

# Actualizaciones automáticas: núcleo (incluidas versiones mayores), plugins y temas de terceros
wp config set WP_AUTO_UPDATE_CORE true --raw
wp plugin auto-updates enable --all 2>/dev/null || true
wp rewrite structure '/%postname%/' --hard

# Contenido
wp eval-file "$HERE/import-content.php"

# Menús
if ! wp menu list --format=ids | grep -q .; then
	wp menu create "Principal"
	wp menu item add-custom principal "Inicio" "$SITE_URL/"
	for slug in futbol-nacional futbol-internacional otras-disciplinas baul-de-los-recuerdos opinion-del-director; do
		wp menu item add-term principal category "$(wp term get category "$slug" --by=slug --field=term_id)"
	done
	wp menu item add-post principal "$(wp post list --post_type=page --name=contacto --field=ID)"
	wp menu location assign principal principal
	wp menu create "Pie"
	for slug in contacto politica-de-privacidad terminos-y-condiciones; do
		wp menu item add-post pie "$(wp post list --post_type=page --name="$slug" --field=ID)"
	done
	wp menu location assign pie pie
fi

# Imágenes del pie
if ! wp widget list footer-promos --format=ids | grep -q .; then
	for f in WhatsApp-Image-2025-05-15-at-09.49.15_b50260cf-1024x1024 WhatsApp-Image-2025-09-30-at-16.59.38-2-895x1024; do
		att=$(wp post list --post_type=attachment --title="$f" --field=ID | head -1)
		[ -n "$att" ] && wp widget add media_image footer-promos --attachment_id="$att" --size=large
	done
fi

wp language core update
wp rewrite flush
echo "Listo: $SITE_URL"
