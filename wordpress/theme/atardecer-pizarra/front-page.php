<?php
/**
 * Portada: se arma sola con las últimas notas de cada categoría.
 */

defined( 'ABSPATH' ) || exit;
get_header();

$shown = array();
$s     = ad_sections();

// Portada: la nota fijada (sticky) más reciente o, si no hay, la última publicada.
$sticky = get_option( 'sticky_posts' );
$hero   = $sticky ? get_posts( array( 'post__in' => $sticky, 'posts_per_page' => 1, 'ignore_sticky_posts' => true ) ) : array();
$hero   = $hero ? $hero[0] : ( ad_query( '', 1 )[0] ?? null );
if ( $hero ) {
	$shown[] = $hero->ID;
}
$side = ad_query( '', 4, $shown );
?>

<?php if ( $hero ) : ?>
<section class="hero" aria-label="Principales">
	<div class="hero__main">
		<?php ad_img( $hero, 'ad-hero', '' ); ?>
		<div class="hero__box">
			<span class="tag"><?php echo ad_cat_name( $hero ); ?></span>
			<h1 class="hero__title"><a href="<?php echo esc_url( get_permalink( $hero ) ); ?>"><?php echo esc_html( get_the_title( $hero ) ); ?></a></h1>
			<span class="meta"><?php echo ad_date( $hero ); ?> · <?php echo esc_html( get_the_author_meta( 'display_name', $hero->post_author ) ); ?></span>
		</div>
	</div>
	<div class="hero__side">
		<?php foreach ( $side as $i => $a ) : ?>
			<article class="side-item">
				<span class="num-outline" aria-hidden="true">0<?php echo (int) $i + 1; ?></span>
				<div class="stack">
					<span class="kicker <?php echo ad_is_otras( $a ) ? '' : 'kicker--lima'; ?>"><?php echo ad_cat_name( $a ); ?></span>
					<a class="t20" href="<?php echo esc_url( get_permalink( $a ) ); ?>"><?php echo esc_html( get_the_title( $a ) ); ?></a>
					<span class="date"><?php echo ad_date( $a ); ?></span>
				</div>
				<a class="thumb cut-12" href="<?php echo esc_url( get_permalink( $a ) ); ?>" tabindex="-1" aria-hidden="true"><?php ad_img( $a, 'ad-thumb', '' ); ?></a>
			</article>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<section class="section">
	<?php ad_section_head( '01', 'Fútbol', 'Internacional', ad_cat_url( $s['internacional'] ) ); ?>
	<div class="grid-4">
		<?php foreach ( ad_query( $s['internacional'], 4, $shown ) as $a ) { ad_card( $a, 'Fútbol' ); } ?>
	</div>
</section>

<section class="section--dark">
	<?php ad_section_head( '02', 'Fútbol', 'Nacional', ad_cat_url( $s['nacional'] ) ); ?>
	<?php $nac = ad_query( $s['nacional'], 4, $shown ); ?>
	<?php if ( $nac ) : ?>
		<div class="grid-4"><?php foreach ( $nac as $a ) { ad_card( $a, 'Fútbol' ); } ?></div>
	<?php else : ?>
		<p class="empty">Todavía no hay notas en Fútbol Nacional.</p>
	<?php endif; ?>
</section>

<section class="section split">
	<div>
		<?php ad_section_head( '03', 'Otras', 'Disciplinas', ad_cat_url( $s['otras'] ) ); ?>
		<?php foreach ( ad_query( $s['otras'], 4, $shown ) as $a ) : ?>
			<article class="row">
				<div class="stack">
					<span class="kicker">Otras Disciplinas · <?php echo ad_date( $a ); ?></span>
					<a class="t22" href="<?php echo esc_url( get_permalink( $a ) ); ?>"><?php echo esc_html( get_the_title( $a ) ); ?></a>
				</div>
				<a class="row__img cut-12" href="<?php echo esc_url( get_permalink( $a ) ); ?>" tabindex="-1" aria-hidden="true"><?php ad_img( $a, 'ad-card' ); ?></a>
			</article>
		<?php endforeach; ?>
	</div>
	<?php $op = ad_query( $s['opinion'], 1, $shown ); ?>
	<aside class="opinion">
		<span class="opinion__label">Opinión del Director</span>
		<?php if ( $op ) : ?>
			<h3><a href="<?php echo esc_url( get_permalink( $op[0] ) ); ?>"><?php echo esc_html( get_the_title( $op[0] ) ); ?></a></h3>
			<p><?php echo esc_html( get_the_excerpt( $op[0] ) ); ?></p>
		<?php else : ?>
			<h3>Próximamente</h3>
			<p>La columna del director aparecerá aquí en cuanto se publique una nota en «Opinión del Director».</p>
		<?php endif; ?>
		<div class="opinion__author">
			<?php
			$photo = (int) ad_opt( 'director_photo' );
			echo $photo ? wp_get_attachment_image( $photo, 'ad-thumb' ) : '<span class="opinion__avatar" aria-hidden="true"></span>';
			?>
			<div>
				<div class="opinion__name"><?php echo esc_html( ad_opt( 'director_name' ) ); ?></div>
				<div class="opinion__role"><?php echo esc_html( ad_opt( 'director_role' ) ); ?></div>
			</div>
		</div>
	</aside>
</section>

<?php $baul = ad_query( $s['baul'], 3, $shown ); ?>
<?php if ( $baul ) : ?>
<section class="baul">
	<?php ad_section_head( '04', 'Baúl de los', 'Recuerdos', ad_cat_url( $s['baul'] ), 'Archivo' ); ?>
	<div class="grid-3">
		<?php foreach ( $baul as $a ) : ?>
			<article class="baul__item">
				<a class="baul__img cut-bottom" href="<?php echo esc_url( get_permalink( $a ) ); ?>" tabindex="-1" aria-hidden="true"><?php ad_img( $a, 'ad-card', 'Foto de archivo' ); ?></a>
				<a class="t22" href="<?php echo esc_url( get_permalink( $a ) ); ?>"><?php echo esc_html( get_the_title( $a ) ); ?></a>
			</article>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php ad_senal(); ?>

<section class="sponsors">
	<?php $sp = ad_sponsors(); ?>
	<?php if ( $sp ) : ?>
		<?php ad_section_head( '05', 'Nuestros', 'patrocinadores' ); ?>
		<div class="sponsors__grid">
			<?php foreach ( $sp as $p ) : ?>
				<?php
				$url = get_post_meta( $p->ID, 'ad_sponsor_url', true );
				$tag = $url ? 'a' : 'div';
				?>
				<<?php echo $tag; ?> class="sponsor"<?php echo $url ? ' href="' . esc_url( $url ) . '" rel="noopener sponsored" target="_blank"' : ''; ?>>
					<div class="sponsor__img"><?php ad_img( $p, 'large', get_the_title( $p ) ); ?></div>
					<span class="sponsor__name"><?php echo esc_html( get_the_title( $p ) ); ?></span>
					<?php if ( $url ) : ?>
						<span class="btn"><?php echo str_contains( $url, 'wa.me' ) ? 'WhatsApp' : 'Ver más'; ?></span>
					<?php endif; ?>
				</<?php echo $tag; ?>>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<div class="cta">
		<span class="cta__text">¿Quiere que su marca llegue a <span>miles de aficionados</span>? Anúnciese con nosotros</span>
		<a class="btn" href="<?php echo esc_url( ad_cta_url() ); ?>">Anúnciese</a>
	</div>
</section>

<?php
get_footer();
