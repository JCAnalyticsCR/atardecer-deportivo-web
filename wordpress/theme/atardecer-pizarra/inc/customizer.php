<?php
/**
 * Ajustes editables en Apariencia → Personalizar → Atardecer Deportivo.
 */

defined( 'ABSPATH' ) || exit;

function ad_opt( $key ) {
	$defaults = array(
		'tagline'        => 'Deportes todo el día',
		'director_name'  => 'Miguel Delgado Segura',
		'director_role'  => 'Periodista – Director',
		'director_photo' => 0,
		'facebook'       => '',
		'x'              => '',
		'instagram'      => '',
		'cta_url'        => '',
	);
	return get_theme_mod( 'ad_' . $key, $defaults[ $key ] ?? '' );
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$wp->add_section( 'ad_sitio', array( 'title' => 'Atardecer Deportivo', 'priority' => 30 ) );

	$fields = array(
		'tagline'       => array( 'Frase del pie', 'text', 'sanitize_text_field' ),
		'director_name' => array( 'Nombre del director', 'text', 'sanitize_text_field' ),
		'director_role' => array( 'Cargo del director', 'text', 'sanitize_text_field' ),
		'facebook'      => array( 'Facebook (URL)', 'url', 'esc_url_raw' ),
		'x'             => array( 'X / Twitter (URL)', 'url', 'esc_url_raw' ),
		'instagram'     => array( 'Instagram (URL)', 'url', 'esc_url_raw' ),
		'cta_url'       => array( 'Botón "Anúnciese" (URL, vacío = página Contacto)', 'url', 'esc_url_raw' ),
	);
	foreach ( $fields as $key => [ $label, $type, $sanitize ] ) {
		$wp->add_setting( 'ad_' . $key, array( 'default' => ad_opt( $key ), 'sanitize_callback' => $sanitize ) );
		$wp->add_control( 'ad_' . $key, array( 'label' => $label, 'section' => 'ad_sitio', 'type' => $type ) );
	}

	$wp->add_setting( 'ad_director_photo', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$wp->add_control( new WP_Customize_Media_Control( $wp, 'ad_director_photo', array(
		'label'     => 'Foto del director',
		'section'   => 'ad_sitio',
		'mime_type' => 'image',
	) ) );
} );

function ad_cta_url() {
	$url = ad_opt( 'cta_url' );
	if ( $url ) {
		return $url;
	}
	$contact = get_page_by_path( 'contacto' );
	return $contact ? get_permalink( $contact ) : home_url( '/' );
}
