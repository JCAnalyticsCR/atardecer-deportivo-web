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
	header_remove( 'X-Powered-By' ); // no anunciar la versión de PHP
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000' ); // el navegador recuerda usar siempre https
	}
} );

// Mensaje de error de acceso genérico (no revelar si el usuario existe).
add_filter( 'login_errors', fn() => 'Usuario o contraseña incorrectos, o demasiados intentos: espere 15 minutos.' );

// Freno a la fuerza bruta: 5 intentos fallidos desde una IP la dejan 15 minutos sin poder entrar.
// (Hostinger entrega la IP real del visitante en REMOTE_ADDR aunque pase por su CDN.)
const AD_LOGIN_MAX  = 5;
const AD_LOGIN_LOCK = 15 * MINUTE_IN_SECONDS;
function ad_login_key() {
	return 'ad_login_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
}
add_filter( 'authenticate', function ( $user ) {
	if ( (int) get_transient( ad_login_key() ) >= AD_LOGIN_MAX ) {
		return new WP_Error( 'ad_locked', 'Demasiados intentos.' );
	}
	return $user;
}, 30 );
add_action( 'wp_login_failed', function () {
	$k = ad_login_key();
	set_transient( $k, (int) get_transient( $k ) + 1, AD_LOGIN_LOCK );
} );
add_action( 'wp_login', fn() => delete_transient( ad_login_key() ) );

// Contraseñas de aplicación: puerta de entrada por API que este sitio no usa.
add_filter( 'wp_is_application_passwords_available', '__return_false' );
