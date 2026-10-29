<?php
defined( 'ABSPATH' ) || exit;
$ad_sid = 's-' . wp_unique_id();
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $ad_sid ); ?>">Buscar</label>
	<input type="search" id="<?php echo esc_attr( $ad_sid ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Buscar noticias…">
	<button type="submit">Buscar</button>
</form>
