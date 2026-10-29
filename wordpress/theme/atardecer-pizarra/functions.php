<?php
/**
 * Atardecer Pizarra — configuración del tema.
 */

defined( 'ABSPATH' ) || exit;

const AD_VERSION = '1.0.0';

require_once __DIR__ . '/inc/template-tags.php';
require_once __DIR__ . '/inc/sponsors.php';
require_once __DIR__ . '/inc/customizer.php';
require_once __DIR__ . '/inc/hardening.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'atardecer', __DIR__ . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 200, 'width' => 260, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'editor-styles' );

	add_image_size( 'ad-hero', 1280, 800, true );
	add_image_size( 'ad-card', 640, 400, true );
	add_image_size( 'ad-thumb', 240, 240, true );

	register_nav_menus( array(
		'principal' => __( 'Menú principal', 'atardecer' ),
		'pie'       => __( 'Menú del pie', 'atardecer' ),
	) );
} );

add_action( 'widgets_init', function () {
	register_sidebar( array(
		'name'          => __( 'Pie · promociones', 'atardecer' ),
		'id'            => 'footer-promos',
		'description'   => __( 'Hasta dos imágenes promocionales a la derecha del pie.', 'atardecer' ),
		'before_widget' => '<div class="footer-promo %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<span class="widget-title">',
		'after_title'   => '</span>',
	) );
	register_sidebar( array(
		'name'          => __( 'Barra lateral de la nota', 'atardecer' ),
		'id'            => 'sidebar-nota',
		'before_widget' => '<section class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2>',
		'after_title'   => '</h2>',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'ad-fonts', 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Inter:wght@400;500;600;700&family=Oswald:wght@500;600;700&display=swap', array(), null );
	wp_enqueue_style( 'ad-style', get_stylesheet_uri(), array( 'ad-fonts' ), AD_VERSION );
	wp_enqueue_script( 'ad-nav', get_template_directory_uri() . '/assets/nav.js', array(), AD_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

add_filter( 'wp_resource_hints', function ( $urls, $type ) {
	if ( 'preconnect' === $type ) {
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

// Las secciones de la portada usan estas categorías; si el editor las renombra, se conservan por slug.
function ad_sections() {
	return array(
		'internacional' => 'futbol-internacional',
		'nacional'      => 'futbol-nacional',
		'otras'         => 'otras-disciplinas',
		'baul'          => 'baul-de-los-recuerdos',
		'opinion'       => 'opinion-del-director',
	);
}

add_filter( 'excerpt_length', fn() => 28 );
add_filter( 'excerpt_more', fn() => '…' );
