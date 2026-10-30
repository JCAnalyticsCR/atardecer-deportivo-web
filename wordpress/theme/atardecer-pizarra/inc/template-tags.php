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

/** Imagen destacada o un recuadro vacío con la etiqueta dada. */
function ad_img( $post, $size = 'ad-card', $placeholder = 'Foto' ) {
	if ( has_post_thumbnail( $post ) ) {
		echo get_the_post_thumbnail( $post, $size, array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => esc_attr( get_the_title( $post ) ) ) );
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

/** Franja "Nuestra señal": video de marca. El MP4 no se descarga hasta que assets/senal.js lo pide. */
function ad_senal() {
	if ( ! ad_opt( 'senal_on' ) ) {
		return;
	}
	$custom = (int) ad_opt( 'senal_video' );
	$video  = $custom ? wp_get_attachment_url( $custom ) : get_template_directory_uri() . '/assets/video/senal-atardecer.mp4';
	$poster = get_template_directory_uri() . '/assets/video/senal-atardecer.webp';
	?>
	<section class="senal" id="senal" aria-label="Nuestra señal">
		<div class="senal__tv" data-state="idle">
			<video class="senal__video" playsinline muted preload="none" width="848" height="478"
				poster="<?php echo esc_url( $poster ); ?>" data-src="<?php echo esc_url( $video ); ?>"
				aria-label="<?php echo esc_attr( 'Presentación de ' . get_bloginfo( 'name' ) ); ?>"></video>
			<span class="senal__hud" aria-hidden="true"><span class="senal__dot"></span>Señal AD</span>
			<button type="button" class="senal__play"><span class="senal__idle">▶ Ver presentación</span><span class="senal__again">↻ Repetir con sonido</span></button>
			<span class="senal__bar" aria-hidden="true"></span>
		</div>
		<div class="senal__copy">
			<span class="tag">Nuestra señal</span>
			<h2>Información<br><span>de alto nivel</span></h2>
			<p>Fútbol nacional, internacional y otras disciplinas, todos los días. Esta es la señal de <?php echo esc_html( get_bloginfo( 'name' ) ); ?>: sume su marca a la nuestra.</p>
			<div class="senal__actions">
				<button type="button" class="senal__sound">🔊 Verla con sonido</button>
				<a class="btn" href="<?php echo esc_url( ad_cta_url() ); ?>">Anúnciese</a>
			</div>
		</div>
	</section>
	<?php
}
