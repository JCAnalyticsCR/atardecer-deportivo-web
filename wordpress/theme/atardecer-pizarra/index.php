<?php
/**
 * Listados: categorías, etiquetas, búsqueda, autor y fallback general.
 */

defined( 'ABSPATH' ) || exit;
get_header();

if ( is_search() ) {
	$title = 'Resultados para <span>' . esc_html( get_search_query() ) . '</span>';
} elseif ( is_category() || is_tag() ) {
	$title = esc_html( single_term_title( '', false ) );
} elseif ( is_author() ) {
	$title = 'Notas de <span>' . esc_html( get_the_author() ) . '</span>';
} elseif ( is_404() ) {
	$title = 'No encontramos <span>esa página</span>';
} else {
	$title = 'Últimas <span>noticias</span>';
}
?>
<header class="archive-head">
	<h1><?php echo wp_kses( $title, array( 'span' => array() ) ); ?></h1>
	<?php if ( is_category() && category_description() ) : ?>
		<p><?php echo wp_kses_post( category_description() ); ?></p>
	<?php endif; ?>
	<?php if ( is_404() || ( is_search() && ! have_posts() ) ) : ?>
		<div style="margin-top:20px;max-width:560px"><?php get_search_form(); ?></div>
	<?php endif; ?>
</header>

<section class="archive-list">
	<?php if ( have_posts() ) : ?>
		<div class="grid-4">
			<?php
			while ( have_posts() ) {
				the_post();
				ad_card( get_post() );
			}
			?>
		</div>
		<?php
		the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '←', 'next_text' => '→', 'screen_reader_text' => 'Paginación' ) );
		?>
	<?php elseif ( ! is_404() ) : ?>
		<p>No hay notas para mostrar.</p>
	<?php endif; ?>
</section>
<?php
get_footer();
