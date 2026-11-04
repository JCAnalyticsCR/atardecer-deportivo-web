<?php
/**
 * Piezas reutilizables de plantilla.
 */

defined( 'ABSPATH' ) || exit;

/** Notas recientes de una categoría (por slug), excluyendo las ya mostradas en la portada. */
function ad_query( $cat_slug, $count, &$shown = array() ) {
	$args = array(
		'posts_per_page'      => $count,
		'post__not_in'        => $shown,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $cat_slug ) {
		$args['category_name'] = $cat_slug;
	}
	$q = new WP_Query( $args );
	foreach ( $q->posts as $p ) {
		$shown[] = $p->ID;
	}
	return $q->posts;
}

function ad_date( $post = null ) {
	return esc_html( get_the_date( 'j M Y', $post ) );
}

/** Categoría principal a mostrar: la más específica (hija) si existe. */
function ad_primary_cat( $post = null ) {
	$cats = get_the_category( $post ? $post->ID : 0 );
	if ( ! $cats ) {
		return null;
	}
	usort( $cats, fn( $a, $b ) => (int) ( $b->parent > 0 ) - (int) ( $a->parent > 0 ) );
	return $cats[0];
}

function ad_cat_name( $post = null ) {
	$c = ad_primary_cat( $post );
	return $c ? esc_html( $c->name ) : '';
}

function ad_is_otras( $post ) {
	return has_category( 'otras-disciplinas', $post );
}

/** Imagen destacada o un recuadro vacío con la etiqueta dada. $eager: la foto principal de la página (carga primero). */
function ad_img( $post, $size = 'ad-card', $placeholder = 'Foto', $eager = false ) {
	if ( has_post_thumbnail( $post ) ) {
		$attrs = $eager
			? array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' )
			: array( 'loading' => 'lazy', 'decoding' => 'async' );
		echo get_the_post_thumbnail( $post, $size, $attrs + array( 'alt' => esc_attr( get_the_title( $post ) ) ) );
		return;
	}
	echo '<div class="ph">' . esc_html( $placeholder ) . '</div>';
}

function ad_section_head( $num, $line1, $line2, $more_url = '', $more_label = 'Ver todo →' ) {
	?>
	<div class="section-head">
		<span class="section-head__num" aria-hidden="true"><?php echo esc_html( $num ); ?></span>
		<h2><?php echo esc_html( $line1 ); ?><br><span><?php echo esc_html( $line2 ); ?></span></h2>
		<span class="section-head__line" aria-hidden="true"></span>
		<?php if ( $more_url ) : ?>
			<a class="section-head__more" href="<?php echo esc_url( $more_url ); ?>"><?php echo esc_html( $more_label ); ?></a>
		<?php endif; ?>
	</div>
	<?php
}

function ad_cat_url( $slug ) {
	$term = get_category_by_slug( $slug );
	return $term ? get_category_link( $term ) : '';
}

function ad_card( $post, $kicker = '' ) {
	?>
	<article class="card">
		<a class="card__img cut-bottom" href="<?php echo esc_url( get_permalink( $post ) ); ?>" tabindex="-1" aria-hidden="true"><?php ad_img( $post ); ?></a>
		<div class="card__body">
			<span class="kicker"><?php echo esc_html( $kicker ?: wp_strip_all_tags( ad_cat_name( $post ) ) ); ?> · <?php echo ad_date( $post ); ?></span>
			<a class="t20" href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
		</div>
	</article>
	<?php
}

function ad_today() {
	return esc_html( wp_date( 'l, j \d\e F \d\e Y' ) );
}

function ad_logo( $class = 'site-logo' ) {
	if ( has_custom_logo() ) {
		$id = get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image( $id, 'full', false, array( 'class' => 'custom-logo', 'alt' => esc_attr( get_bloginfo( 'name' ) ) ) );
		return;
	}
	printf( '<img class="%s" src="%s" alt="%s" width="640" height="608">', esc_attr( $class ), esc_url( get_template_directory_uri() . '/assets/logo.png' ), esc_attr( get_bloginfo( 'name' ) ) );
}

/** Menú por defecto si el editor todavía no armó uno en Apariencia → Menús. */
function ad_default_menu() {
	$items = array( array( home_url( '/' ), 'Inicio' ) );
	foreach ( array( 'futbol-nacional', 'futbol-internacional', 'otras-disciplinas', 'baul-de-los-recuerdos', 'opinion-del-director' ) as $slug ) {
		$t = get_category_by_slug( $slug );
		if ( $t ) {
			$items[] = array( get_category_link( $t ), $t->name );
		}
	}
	$contact = get_page_by_path( 'contacto' );
	if ( $contact ) {
		$items[] = array( get_permalink( $contact ), 'Contacto' );
	}
	echo '<ul>';
	foreach ( $items as [ $url, $label ] ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}

/**
 * Video de entrada: pantalla completa sobre el sitio la primera vez que alguien entra en la visita.
 * El script va en línea, justo después del marcado, para decidir antes del primer pintado si se muestra.
 */
function ad_intro() {
	if ( ! ad_opt( 'intro_on' ) || is_customize_preview() ) {
		return;
	}
	$custom = (int) ad_opt( 'intro_video' );
	$video  = $custom ? wp_get_attachment_url( $custom ) : get_template_directory_uri() . '/assets/video/senal-atardecer.mp4';
	$poster = get_template_directory_uri() . '/assets/video/senal-atardecer.webp';
	$name   = get_bloginfo( 'name' );
	?>
	<div id="ad-intro" class="ad-intro" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( 'Presentación de ' . $name ); ?>">
		<span class="ad-intro__kicker"><span class="ad-intro__dot"></span>Señal AD · <?php echo esc_html( get_bloginfo( 'description' ) ); ?></span>
		<div class="ad-intro__frame">
			<video class="ad-intro__video" data-src="<?php echo esc_url( $video ); ?>" poster="<?php echo esc_url( $poster ); ?>" muted playsinline preload="auto" width="848" height="478" aria-label="<?php echo esc_attr( 'Video de presentación de ' . $name ); ?>"></video>
			<span class="ad-intro__bar" aria-hidden="true"></span>
		</div>
		<span class="ad-intro__hint" hidden>🔊 Tocá la pantalla para escuchar el sonido</span>
		<button type="button" class="ad-intro__enter">Entrar al sitio →</button>
		<button type="button" class="ad-intro__close" aria-label="Cerrar y entrar al sitio">✕</button>
	</div>
	<?php
	wp_print_inline_script_tag( file_get_contents( __DIR__ . '/../assets/intro.js' ) );
}

/** Iniciales de un nombre ("Miguel Delgado Segura" → "MD") para cuando no hay foto. */
function ad_initials( $name ) {
	$parts = preg_split( '/\s+/', trim( (string) $name ) );
	return mb_strtoupper( mb_substr( $parts[0] ?? '', 0, 1 ) . mb_substr( $parts[1] ?? '', 0, 1 ) );
}

/** Secciones del pie: las categorías con notas, sin la general "Solamente Fútbol" (repite a todas). */
function ad_footer_sections() {
	$cats = get_categories( array( 'hide_empty' => true, 'exclude' => array_filter( array( (int) get_cat_ID( 'Solamente Fútbol' ) ) ), 'orderby' => 'count', 'order' => 'DESC' ) );
	if ( ! $cats ) {
		return;
	}
	echo '<ul class="footer-sections">';
	foreach ( $cats as $c ) {
		printf(
			'<li><a href="%s"><span>%s</span><b>%s</b></a></li>',
			esc_url( get_category_link( $c ) ),
			esc_html( $c->name ),
			esc_html( number_format_i18n( $c->count ) )
		);
	}
	echo '</ul>';
}

/**
 * Archivo del pie como calendario: una fila por año y una casilla por mes; los meses sin notas se ven apagados.
 * La consulta se guarda medio día y se renueva cuando se publica o se borra una nota.
 */
function ad_footer_archive() {
	$rows = get_transient( 'ad_footer_archive' );
	if ( false === $rows ) {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT YEAR(post_date) AS y, MONTH(post_date) AS m, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' GROUP BY y, m ORDER BY y DESC, m ASC", ARRAY_A );
		set_transient( 'ad_footer_archive', $rows, 12 * HOUR_IN_SECONDS );
	}
	$years = array();
	foreach ( (array) $rows as $r ) {
		$years[ (int) $r['y'] ][ (int) $r['m'] ] = (int) $r['n'];
	}
	if ( ! $years ) {
		return;
	}
	$months = array( 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic' );
	echo '<div class="footer-archive">';
	foreach ( $years as $y => $counts ) {
		printf( '<div class="footer-archive__year"><a class="footer-archive__y" href="%s">%d</a><ol>', esc_url( get_year_link( $y ) ), (int) $y );
		foreach ( $months as $i => $label ) {
			$n = $counts[ $i + 1 ] ?? 0;
			if ( $n ) {
				printf( '<li><a href="%s" title="%s">%s</a></li>', esc_url( get_month_link( $y, $i + 1 ) ), esc_attr( sprintf( '%s %d · %s notas', wp_date( 'F', mktime( 0, 0, 0, $i + 1, 1, $y ) ), $y, number_format_i18n( $n ) ) ), esc_html( $label ) );
			} else {
				printf( '<li><span aria-hidden="true">%s</span></li>', esc_html( $label ) );
			}
		}
		echo '</ol></div>';
	}
	echo '</div>';
}
add_action( 'save_post_post', fn() => delete_transient( 'ad_footer_archive' ) );
add_action( 'deleted_post', fn() => delete_transient( 'ad_footer_archive' ) );
