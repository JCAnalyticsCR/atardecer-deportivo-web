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

// Freno a la fuerza bruta: 10 intentos fallidos desde una IP en 10 minutos la dejan 10 minutos sin poder entrar.
// La ventana es fija: probar de nuevo mientras está bloqueado NO alarga el bloqueo ni suma intentos.
// (Hostinger entrega la IP real del visitante en REMOTE_ADDR aunque pase por su CDN.)
const AD_LOGIN_MAX  = 10;
const AD_LOGIN_LOCK = 10 * MINUTE_IN_SECONDS;
function ad_login_key() {
	return 'ad_login_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
}
/** Estado de la IP actual: [ intentos, momento en que vence la ventana ]. */
function ad_login_state() {
	$s = get_transient( ad_login_key() );
	return is_array( $s ) && $s[1] > time() ? $s : array( 0, 0 );
}
function ad_login_locked() {
	[ $n, $until ] = ad_login_state();
	return $n >= AD_LOGIN_MAX ? max( 1, (int) ceil( ( $until - time() ) / MINUTE_IN_SECONDS ) ) : 0;
}
add_filter( 'authenticate', function ( $user ) {
	$min = ad_login_locked();
	if ( $min ) {
		$GLOBALS['ad_login_blocked'] = true; // un intento estando bloqueado no cuenta
		return new WP_Error( 'ad_locked', sprintf( 'Demasiados intentos fallidos desde esta conexión. Espere %d minuto(s) y vuelva a intentar, o use «¿Has olvidado tu contraseña?».', $min ) );
	}
	return $user;
}, 30 );
add_action( 'wp_login_failed', function ( $username ) {
	if ( ! empty( $GLOBALS['ad_login_blocked'] ) ) {
		return;
	}
	[ $n, $until ] = ad_login_state();
	$until = $until ?: time() + AD_LOGIN_LOCK;
	set_transient( ad_login_key(), array( $n + 1, $until ), $until - time() );
	// Registro corto de intentos fallidos para poder diagnosticar (últimos 50; no se carga en cada página).
	$log   = (array) get_option( 'ad_login_log', array() );
	$log[] = array( gmdate( 'Y-m-d H:i:s' ), (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ), sanitize_user( (string) $username ) );
	update_option( 'ad_login_log', array_slice( $log, -50 ), false );
} );
add_action( 'wp_login', fn() => delete_transient( ad_login_key() ) );

// Mensaje de error genérico (no revelar si el usuario existe), salvo el aviso de bloqueo, que dice cuánto esperar.
add_filter( 'login_errors', function ( $msg ) {
	$min = ad_login_locked();
	if ( $min ) {
		return sprintf( 'Demasiados intentos fallidos desde esta conexión. Espere %d minuto(s) y vuelva a intentar, o use «¿Has olvidado tu contraseña?».', $min );
	}
	return 'Usuario o contraseña incorrectos. Puede entrar con el usuario o con el correo.';
} );

// Contraseñas de aplicación: puerta de entrada por API que este sitio no usa.
add_filter( 'wp_is_application_passwords_available', '__return_false' );
