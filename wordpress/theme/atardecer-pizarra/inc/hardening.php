<?php
/**
 * Endurecimiento básico que el sitio viejo no tenía (complementa, no reemplaza, un plugin de seguridad).
 */

defined( 'ABSPATH' ) || exit;

// XML-RPC: vector clásico de fuerza bruta; el editor no lo necesita.
add_filter( 'xmlrpc_enabled', '__return_false' );

// No anunciar la versión de WordPress.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// No listar usuarios por la API pública ni por ?author=N (el sitio viejo exponía sus cuentas).
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );
add_action( 'template_redirect', function () {
	if ( ! is_admin() && isset( $_GET['author'] ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );
add_filter( 'wp_sitemaps_add_provider', fn( $provider, $name ) => 'users' === $name ? false : $provider, 10, 2 );

// Encabezados de seguridad.
add_action( 'send_headers', function () {
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
} );

// Mensaje de error de acceso genérico (no revelar si el usuario existe).
add_filter( 'login_errors', fn() => 'Usuario o contraseña incorrectos.' );
