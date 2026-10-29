<?php
/**
 * Nota individual.
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	$cat = ad_primary_cat();
	?>
	<article <?php post_class( 'article' ); ?>>
		<header class="article__head">
			<?php if ( $cat ) : ?>
				<a class="tag" href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
			<?php endif; ?>
			<h1 class="article__title"><?php the_title(); ?></h1>
			<span class="article__meta"><?php echo ad_date(); ?> · <?php the_author(); ?></span>
		</header>
		<div>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="article__img"><?php the_post_thumbnail( 'large', array( 'fetchpriority' => 'high' ) ); ?></figure>
			<?php endif; ?>
			<div class="entry"><?php the_content(); ?></div>
			<?php
			$tags = get_the_tags();
			if ( $tags ) :
				?>
				<div class="article__tags">
					<?php foreach ( $tags as $t ) : ?>
						<a href="<?php echo esc_url( get_tag_link( $t ) ); ?>">#<?php echo esc_html( $t->name ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<aside class="sidebar">
			<?php if ( is_active_sidebar( 'sidebar-nota' ) ) : ?>
				<?php dynamic_sidebar( 'sidebar-nota' ); ?>
			<?php else : ?>
				<h2>Lo último</h2>
				<?php
				$latest = get_posts( array( 'posts_per_page' => 5, 'post__not_in' => array( get_the_ID() ), 'no_found_rows' => true ) );
				foreach ( $latest as $i => $a ) :
					?>
					<article class="side-item" style="grid-template-columns:40px 1fr">
						<span class="num-outline" style="font-size:32px" aria-hidden="true">0<?php echo (int) $i + 1; ?></span>
						<div class="stack">
							<span class="kicker"><?php echo ad_cat_name( $a ); ?></span>
							<a class="t20" href="<?php echo esc_url( get_permalink( $a ) ); ?>"><?php echo esc_html( get_the_title( $a ) ); ?></a>
						</div>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</aside>
	</article>

	<?php
	if ( $cat ) :
		$related = get_posts( array( 'posts_per_page' => 4, 'cat' => $cat->term_id, 'post__not_in' => array( get_the_ID() ), 'no_found_rows' => true ) );
		if ( $related ) :
			?>
			<section class="related">
				<?php ad_section_head( '+', 'Más de', $cat->name, get_category_link( $cat ) ); ?>
				<div class="grid-4"><?php foreach ( $related as $a ) { ad_card( $a ); } ?></div>
			</section>
			<?php
		endif;
	endif;
endwhile;

get_footer();
