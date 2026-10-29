<?php
/**
 * Patrocinadores: tipo de contenido propio para que el editor los agregue con su imagen y enlace.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'patrocinador', array(
		'labels'       => array(
			'name'          => 'Patrocinadores',
			'singular_name' => 'Patrocinador',
			'add_new_item'  => 'Agregar patrocinador',
			'edit_item'     => 'Editar patrocinador',
			'featured_image'     => 'Imagen del patrocinador (vertical 2:3)',
			'set_featured_image' => 'Elegir imagen',
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-megaphone',
		'menu_position' => 21,
		'supports'     => array( 'title', 'thumbnail', 'page-attributes' ),
	) );

	register_post_meta( 'patrocinador', 'ad_sponsor_url', array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'esc_url_raw',
		'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
	) );
} );

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ad_sponsor_url', 'Enlace del patrocinador', function ( $post ) {
		wp_nonce_field( 'ad_sponsor_url', 'ad_sponsor_url_nonce' );
		printf(
			'<input type="url" name="ad_sponsor_url" value="%s" placeholder="https://" style="width:100%%">',
			esc_attr( get_post_meta( $post->ID, 'ad_sponsor_url', true ) )
		);
	}, 'patrocinador', 'side' );
} );

add_action( 'save_post_patrocinador', function ( $post_id ) {
	if ( ! isset( $_POST['ad_sponsor_url_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ad_sponsor_url_nonce'] ), 'ad_sponsor_url' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$url = isset( $_POST['ad_sponsor_url'] ) ? esc_url_raw( wp_unslash( $_POST['ad_sponsor_url'] ) ) : '';
	update_post_meta( $post_id, 'ad_sponsor_url', $url );
} );

function ad_sponsors( $limit = -1 ) {
	return get_posts( array(
		'post_type'      => 'patrocinador',
		'posts_per_page' => $limit,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	) );
}
