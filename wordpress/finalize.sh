#!/usr/bin/env bash
# Ajustes finales de producción, después de setup.sh: plugins mínimos (todos del directorio oficial),
# formulario de contacto, nombre público del autor y endurecimiento.
#
# Uso: bash finalize.sh <ruta-de-wordpress>
set -euo pipefail

WP_PATH="${1:?ruta de WordPress}"
wp() { command wp --path="$WP_PATH" "$@"; }

# Plugins: fuera lo que trae el instalador y no se usa; dentro solo lo necesario
# (los de Hostinger que se borran aquí se reinstalan desde hPanel si algún día hacen falta)
for p in $(wp plugin list --field=name --status=active,inactive | grep -E '^(hostinger|elementor)'); do
	wp plugin deactivate "$p" || true
	wp plugin delete "$p" || true
done
wp plugin install contact-form-7 contact-form-7-honeypot two-factor updraftplus --activate
wp language plugin install --all es_CR || true

# Antispam del formulario: mensajes con enlaces se marcan como spam y no se envían
# (Contact Form 7 revisa la lista de palabras bloqueadas de WordPress)
wp option update disallowed_keys "$(printf 'http://\nhttps://\nwww.\n<a \n[url\n.ru/\n.top/\n.xyz/')"
wp plugin is-installed litespeed-cache && wp plugin activate litespeed-cache || true
wp plugin auto-updates enable --all || true   # avisa si alguno ya estaba activado

# Endurecimiento: sin editor de archivos en el panel
wp config set DISALLOW_FILE_EDIT true --raw

# Nombre público del director en las notas
wp user update "${AD_AUTHOR:-miguel.delgado}" --display_name="Miguel Delgado Segura" --first_name="Miguel" --last_name="Delgado Segura"

# Formulario de contacto: llega al correo del administrador
form_id=$(wp post list --post_type=wpcf7_contact_form --title="Contacto" --field=ID | head -1)
if [ -z "$form_id" ]; then
	form_id=$(wp eval '
		$f = WPCF7_ContactForm::get_template( array( "title" => "Contacto" ) );
		$f->set_properties( array(
			"form" => "<label>Nombre\n[text* nombre autocomplete:name]</label>\n\n<label>Correo electrónico\n[email* correo autocomplete:email]</label>\n\n<label>Asunto\n[text asunto]</label>\n\n<label>Mensaje\n[textarea* mensaje]</label>\n\n[honeypot sitio-web timecheck_enabled:true timecheck_threshold:4]\n\n[submit \"Enviar\"]",
			"locale" => "es_CR",
			"mail" => array_merge( $f->prop( "mail" ), array(
				"subject"   => "[Atardecer Deportivo] [asunto]",
				"sender"    => "Atardecer Deportivo <wordpress@atardecerdeportivocr.com>",
				"body"      => "De: [nombre] <[correo]>\nAsunto: [asunto]\n\n[mensaje]\n\n--\nEnviado desde el formulario de contacto de atardecerdeportivocr.com",
				"recipient" => get_option( "admin_email" ),
				"additional_headers" => "Reply-To: [correo]",
			) ),
		) );
		echo $f->save();
	')
fi
contact_id=$(wp post list --post_type=page --name=contacto --field=ID)
wp post update "$contact_id" --post_content="<!-- wp:paragraph --><p>¿Tiene una noticia, una consulta o quiere anunciarse con nosotros? Escríbanos y le respondemos a la brevedad.</p><!-- /wp:paragraph -->
<!-- wp:shortcode -->[contact-form-7 id=\"$form_id\" title=\"Contacto\"]<!-- /wp:shortcode -->"

wp cache flush || true
wp litespeed-purge all 2>/dev/null || true
echo "Ajustes finales listos."
