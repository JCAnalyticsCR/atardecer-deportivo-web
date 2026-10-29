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
		<span class="footer-copy">© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
	</div>
	<?php dynamic_sidebar( 'footer-promos' ); ?>
</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
