<?php
/**
 * Importa el contenido saneado al WordPress nuevo.
 * Uso: wp eval-file import-content.php   (AD_IMPORT_DIR apunta a la carpeta import/; por defecto, la vecina)
 * Espera import/content.json, import/uploads/AAAA/MM/archivo y seed/sponsors.json (ver scripts/sanitize_content.py y fetch_uploads.py).
 * Es idempotente: si una nota ya existe (por slug) la salta.
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/image.php';

const AD_OLD_UPLOADS = 'https://atardecerdeportivocr.com/wp-content/uploads/';
define( 'AD_IMPORT', rtrim( getenv( 'AD_IMPORT_DIR' ) ?: __DIR__ . '/import', '/' ) . '/' );
const AD_SRC = AD_IMPORT . 'uploads/';

$data    = json_decode( file_get_contents( AD_IMPORT . 'content.json' ), true );
$uploads = wp_get_upload_dir();
$att_by_rel = array();

// Lo que el contenido puede traer: lo de una nota normal más embeds de redes.
$allowed           = wp_kses_allowed_html( 'post' );
$allowed['iframe'] = array( 'src' => true, 'width' => true, 'height' => true, 'frameborder' => true, 'allow' => true, 'allowfullscreen' => true, 'title' => true, 'loading' => true, 'referrerpolicy' => true );
$embed_hosts       = '/(^|\.)(youtube\.com|youtube-nocookie\.com|twitter\.com|x\.com|instagram\.com|facebook\.com|tiktok\.com)$/i';

kses_remove_filters(); // ya saneamos con nuestra lista; sin esto WordPress quitaría los embeds legítimos

function ad_clean_html( $html, $allowed, $embed_hosts ) {
	$html = wp_kses( $html, $allowed );
	return preg_replace_callback( '#<iframe\b[^>]*src="([^"]+)"[^>]*>.*?</iframe>#is', function ( $m ) use ( $embed_hosts ) {
		$host = wp_parse_url( $m[1], PHP_URL_HOST ) ?: '';
		return preg_match( $embed_hosts, $host ) ? $m[0] : '';
	}, $html );
}

/** Registra como adjunto una imagen ya copiada en uploads, sin duplicar. */
function ad_attachment( $old_url, &$att_by_rel, $uploads, $parent = 0 ) {
	if ( ! $old_url || ! str_starts_with( $old_url, AD_OLD_UPLOADS ) ) {
		return 0;
	}
	$rel = substr( $old_url, strlen( AD_OLD_UPLOADS ) );
	if ( isset( $att_by_rel[ $rel ] ) ) {
		return $att_by_rel[ $rel ];
	}
	$dest = $uploads['basedir'] . '/' . $rel;
	if ( ! file_exists( $dest ) ) {
		if ( ! file_exists( AD_SRC . $rel ) ) {
			return 0;
		}
		wp_mkdir_p( dirname( $dest ) );
		copy( AD_SRC . $rel, $dest );
	}
	$type = wp_check_filetype( $dest );
	if ( ! $type['type'] || ! str_starts_with( $type['type'], 'image/' ) ) {
		return 0;
	}
	$id = wp_insert_attachment( array(
		'post_mime_type' => $type['type'],
		'post_title'     => sanitize_text_field( pathinfo( $rel, PATHINFO_FILENAME ) ),
		'post_status'    => 'inherit',
	), $dest, $parent );
	if ( ! is_wp_error( $id ) ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
		$att_by_rel[ $rel ] = $id;
		return $id;
	}
	return 0;
}

/** Copia a uploads todas las imágenes que aparecen dentro del contenido y reescribe sus URLs. */
function ad_localize_inline( $html, $uploads ) {
	return preg_replace_callback( '#' . preg_quote( AD_OLD_UPLOADS, '#' ) . '([^"\'\s)>,]+)#', function ( $m ) use ( $uploads ) {
		$rel  = $m[1];
		$dest = $uploads['basedir'] . '/' . $rel;
		if ( ! file_exists( $dest ) && file_exists( AD_SRC . $rel ) ) {
			wp_mkdir_p( dirname( $dest ) );
			copy( AD_SRC . $rel, $dest );
		}
		return $uploads['baseurl'] . '/' . $rel;
	}, $html );
}

// ---- Categorías
foreach ( $data['categories'] as $c ) {
	if ( ! term_exists( $c['slug'], 'category' ) ) {
		$parent = $c['parent'] ? (int) get_category_by_slug( $c['parent'] )->term_id : 0;
		wp_insert_term( $c['name'], 'category', array( 'slug' => $c['slug'], 'parent' => $parent, 'description' => $c['description'] ) );
	}
}
$cat_ids = array();
foreach ( get_categories( array( 'hide_empty' => false ) ) as $t ) {
	$cat_ids[ $t->slug ] = $t->term_id;
}

// ---- Autor: todas las notas quedan a nombre del director (las cuentas viejas no se migran).
$author = get_user_by( 'login', 'miguel.delgado' );
if ( ! $author ) {
	$uid = wp_insert_user( array(
		'user_login'   => 'miguel.delgado',
		'user_pass'    => wp_generate_password( 32, true, true ),
		'display_name' => 'Miguel Delgado Segura',
		'first_name'   => 'Miguel',
		'last_name'    => 'Delgado Segura',
		'role'         => 'editor',
	) );
	$author = get_user_by( 'id', $uid );
}

// ---- Notas (de la más vieja a la más nueva, para que los IDs sigan el orden cronológico)
$posts = array_reverse( $data['posts'] );
$done  = 0;
$skip  = 0;
foreach ( $posts as $p ) {
	if ( get_page_by_path( $p['slug'], OBJECT, 'post' ) ) {
		++$skip;
		continue;
	}
	$content = ad_localize_inline( ad_clean_html( $p['content'], $allowed, $embed_hosts ), $uploads );
	$id      = wp_insert_post( array(
		'post_title'    => wp_strip_all_tags( $p['title'] ),
		'post_name'     => $p['slug'],
		'post_content'  => $content,
		'post_excerpt'  => '',
		'post_status'   => 'publish',
		'post_date'     => str_replace( 'T', ' ', $p['date'] ),
		'post_author'   => $author->ID,
		'post_category' => array_values( array_filter( array_map( fn( $s ) => $cat_ids[ $s ] ?? 0, $p['categories'] ) ) ),
	), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $p['slug'] . ': ' . $id->get_error_message() );
		continue;
	}
	$att = ad_attachment( $p['featured'], $att_by_rel, $uploads, $id );
	if ( $att ) {
		set_post_thumbnail( $id, $att );
	}
	if ( $p['sticky'] ) {
		stick_post( $id );
	}
	if ( ++$done % 100 === 0 ) {
		WP_CLI::log( "notas: $done" );
	}
}
WP_CLI::log( "notas importadas: $done, ya existían: $skip" );

// ---- Páginas
foreach ( $data['pages'] as $pg ) {
	if ( get_page_by_path( $pg['slug'] ) ) {
		continue;
	}
	wp_insert_post( array(
		'post_type'    => 'page',
		'post_title'   => $pg['title'],
		'post_name'    => $pg['slug'],
		'post_content' => ad_localize_inline( ad_clean_html( $pg['content'], $allowed, $embed_hosts ), $uploads ),
		'post_status'  => 'publish',
		'post_author'  => $author->ID,
	) );
}

// ---- Patrocinadores (imagen, nombre y enlace; el editor los cambia en Patrocinadores → Editar)
if ( ! get_posts( array( 'post_type' => 'patrocinador', 'posts_per_page' => 1 ) ) ) {
	foreach ( json_decode( file_get_contents( __DIR__ . '/seed/sponsors.json' ), true ) as $i => $sp ) {
		$sid = wp_insert_post( array( 'post_type' => 'patrocinador', 'post_title' => $sp['name'], 'post_status' => 'publish', 'menu_order' => $i ) );
		if ( $sp['url'] ) {
			update_post_meta( $sid, 'ad_sponsor_url', esc_url_raw( $sp['url'] ) );
		}
		$att = ad_attachment( AD_OLD_UPLOADS . $sp['img'], $att_by_rel, $uploads, $sid );
		if ( $att ) {
			set_post_thumbnail( $sid, $att );
		}
	}
}
$promos = array();
foreach ( array( '2025/05/WhatsApp-Image-2025-05-15-at-09.49.15_b50260cf-1024x1024.jpg', '2025/09/WhatsApp-Image-2025-09-30-at-16.59.38-2-895x1024.jpeg' ) as $rel ) {
	$promos[] = ad_attachment( AD_OLD_UPLOADS . $rel, $att_by_rel, $uploads );
}
WP_CLI::log( 'promos: ' . implode( ',', $promos ) );
WP_CLI::success( 'Importación terminada.' );
