<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#contenido">Saltar al contenido</a>
<div class="site">
<header class="site-header">
	<div class="topbar">
		<span class="topbar__date"><?php echo ad_today(); ?></span>
		<div class="topbar__links">
			<?php if ( ad_opt( 'facebook' ) ) : ?>
				<a href="<?php echo esc_url( ad_opt( 'facebook' ) ); ?>" rel="noopener" target="_blank">Facebook</a>
			<?php endif; ?>
			<a class="btn" href="<?php echo esc_url( ad_cta_url() ); ?>">Anúnciese</a>
		</div>
	</div>
	<div class="mainbar">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — inicio"><?php ad_logo(); ?></a>
		<nav class="main-nav" id="menu-principal" aria-label="Menú principal">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'principal',
				'container'      => false,
				'depth'          => 1,
				'fallback_cb'    => 'ad_default_menu',
			) );
			?>
		</nav>
		<div class="header-tools">
			<button class="icon-btn" type="button" data-toggle="buscar" aria-controls="buscar" aria-expanded="false" aria-label="Buscar">⌕</button>
			<button class="icon-btn menu-toggle" type="button" data-toggle="menu-principal" aria-controls="menu-principal" aria-expanded="false" aria-label="Menú">☰</button>
		</div>
	</div>
	<div class="search-panel" id="buscar"><?php get_search_form(); ?></div>
	<?php
	$ticker = get_posts( array( 'posts_per_page' => 6, 'no_found_rows' => true, 'ignore_sticky_posts' => true ) );
	if ( $ticker ) :
		?>
		<div class="ticker">
			<span class="ticker__label">Última hora</span>
			<div class="ticker__track">
				<div class="ticker__items">
					<?php foreach ( array( false, true ) as $dup ) : ?>
						<?php foreach ( $ticker as $t ) : ?>
							<a href="<?php echo esc_url( get_permalink( $t ) ); ?>"<?php echo $dup ? ' aria-hidden="true" tabindex="-1"' : ''; ?>><span>//</span><?php echo esc_html( get_the_title( $t ) ); ?></a>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</header>
<main id="contenido">
