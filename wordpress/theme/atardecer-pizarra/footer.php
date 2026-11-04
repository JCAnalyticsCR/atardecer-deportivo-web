<?php defined( 'ABSPATH' ) || exit; ?>
</main>
<footer class="site-footer">
	<div class="footer-brand">
		<?php ad_logo(); ?>
		<span class="footer-tagline"><?php echo esc_html( ad_opt( 'tagline' ) ); ?></span>
		<nav class="footer-links" aria-label="Enlaces del pie">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'pie',
				'container'      => false,
				'depth'          => 1,
				'fallback_cb'    => function () {
					echo '<ul>';
					foreach ( array( 'contacto' => 'Contacto', 'politica-de-privacidad' => 'Política de privacidad', 'terminos-y-condiciones' => 'Términos y condiciones' ) as $slug => $label ) {
						$p = get_page_by_path( $slug );
						if ( $p ) {
							printf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink( $p ) ), esc_html( $label ) );
						}
					}
					echo '</ul>';
				},
			) );
			?>
		</nav>
		<div class="footer-social">
			<?php foreach ( array( 'facebook' => 'Facebook', 'x' => 'X', 'instagram' => 'Instagram' ) as $key => $label ) : ?>
				<?php if ( ad_opt( $key ) ) : ?>
					<a href="<?php echo esc_url( ad_opt( $key ) ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $label ); ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>

	<nav class="footer-col" aria-labelledby="pie-secciones">
		<h2 class="footer-h" id="pie-secciones">Secciones</h2>
		<?php ad_footer_sections(); ?>
	</nav>

	<nav class="footer-col" aria-labelledby="pie-archivo">
		<h2 class="footer-h" id="pie-archivo">Archivo</h2>
		<?php ad_footer_archive(); ?>
	</nav>

	<?php if ( is_active_sidebar( 'footer-promos' ) ) : ?>
		<div class="footer-col">
			<h2 class="footer-h">Promociones</h2>
			<div class="footer-promos"><?php dynamic_sidebar( 'footer-promos' ); ?></div>
		</div>
	<?php endif; ?>

	<div class="footer-bottom">
		<span class="footer-copy">© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?> · Todos los derechos reservados</span>
		<a class="jc-credit" href="https://jcanalytic.com" target="_blank" rel="noopener">
			<span class="jc-credit__label">Hecho por</span>
			<img class="jc-credit__mark" src="<?php echo esc_url( get_template_directory_uri() . '/assets/jc-mark.webp' ); ?>" alt="" width="77" height="96" loading="lazy" decoding="async">
			<span class="jc-credit__name"><b>JC Analytics</b><small>jcanalytic.com</small></span>
			<span class="jc-credit__arrow" aria-hidden="true">↗</span>
		</a>
	</div>
</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
