<?php
/**
 * Site footer component.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<footer class="site-footer">
	<div class="container site-footer__inner">
		<div class="site-footer__brand">
			<a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php bloginfo( 'name' ); ?>
			</a>
			<p class="site-footer__tagline"><?php bloginfo( 'description' ); ?></p>
		</div>

		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Pie de página', 'shanelle' ); ?>">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer',
					'menu_class'     => 'site-footer__menu',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => false,
				) );
				?>
			</nav>
		<?php endif; ?>
	</div>

	<div class="site-footer__bottom">
		<div class="container">
			<p class="site-footer__copy">
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			</p>
		</div>
	</div>
</footer>
