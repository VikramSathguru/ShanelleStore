<?php
/**
 * Site header component.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
?>
<header class="site-header" data-header>
	<div class="container site-header__inner">
		<div class="site-header__start">
			<button
				type="button"
				class="site-header__menu-toggle btn btn--ghost btn--icon"
				data-menu-toggle
				aria-controls="site-mobile-drawer"
				aria-expanded="false"
				aria-label="<?php esc_attr_e( 'Open menu', 'shanelle' ); ?>"
			>
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			</button>

			<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php
				if ( has_custom_logo() ) {
					the_custom_logo();
				} else {
					bloginfo( 'name' );
				}
				?>
			</a>
		</div>

		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<nav class="site-header__nav" aria-label="<?php esc_attr_e( 'Primary', 'shanelle' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'site-header__menu',
						'container'      => false,
						'depth'          => 2,
						'fallback_cb'    => false,
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<div class="site-header__actions">
			<button
				type="button"
				class="site-header__action btn btn--ghost btn--icon"
				data-shanelle-search-open
				aria-label="<?php esc_attr_e( 'Search', 'shanelle' ); ?>"
			>
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
			</button>

			<?php if ( shanelle_is_woocommerce_active() ) : ?>
				<button
					type="button"
					class="site-header__action btn btn--ghost btn--icon"
					data-shanelle-mini-cart-open
					aria-label="<?php esc_attr_e( 'Open bag', 'shanelle' ); ?>"
				>
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6 7h15l-1.5 9h-12z"/><path d="M9 11V8a3 3 0 0 1 6 0v3"/></svg>
				</button>

				<a class="site-header__shop-link text-label desktop:block" href="<?php echo esc_url( is_string( $shop_url ) ? $shop_url : home_url( '/' ) ); ?>">
					<?php esc_html_e( 'Shop', 'shanelle' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="site-header__drawer" id="site-mobile-drawer" data-mobile-drawer hidden>
		<button
			type="button"
			class="site-header__drawer-overlay"
			data-drawer-overlay
			tabindex="-1"
			aria-hidden="true"
		></button>

		<div class="site-header__drawer-panel">
			<div class="site-header__drawer-head">
				<p class="site-header__drawer-title text-label"><?php esc_html_e( 'Menu', 'shanelle' ); ?></p>
				<button
					type="button"
					class="site-header__drawer-close btn btn--ghost btn--icon"
					data-drawer-close
					aria-label="<?php esc_attr_e( 'Close menu', 'shanelle' ); ?>"
				>
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
				</button>
			</div>

			<?php if ( has_nav_menu( 'mobile' ) || has_nav_menu( 'primary' ) ) : ?>
				<nav class="site-header__drawer-nav" aria-label="<?php esc_attr_e( 'Mobile', 'shanelle' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => has_nav_menu( 'mobile' ) ? 'mobile' : 'primary',
							'menu_class'     => 'site-header__drawer-menu',
							'container'      => false,
							'depth'          => 2,
							'fallback_cb'    => false,
						)
					);
					?>
				</nav>
			<?php endif; ?>

			<div class="site-header__drawer-actions">
				<button type="button" class="btn btn--outline btn--block" data-shanelle-search-open>
					<?php esc_html_e( 'Search', 'shanelle' ); ?>
				</button>

				<?php if ( shanelle_is_woocommerce_active() ) : ?>
					<a class="btn btn--primary btn--block" href="<?php echo esc_url( is_string( $shop_url ) ? $shop_url : home_url( '/' ) ); ?>">
						<?php esc_html_e( 'Shop now', 'shanelle' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</header>
