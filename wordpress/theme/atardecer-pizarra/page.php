<?php
/**
 * Páginas fijas (Contacto, Nosotros, políticas).
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	?>
	<header class="archive-head"><h1><?php the_title(); ?></h1></header>
	<article class="page-content entry"><?php the_content(); ?></article>
	<?php
endwhile;

get_footer();
