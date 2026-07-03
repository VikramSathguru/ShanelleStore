<?php
/**
 * Site header component.
 *
 * @package Shanelle
 *
 * @var array<string, mixed> $args
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$shop_url = shanelle_is_woocommerce_active() ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$cart_url = shanelle_is_woocommerce_active() ? wc_get_cart_url() : '#';
?>
<header class="site-header" data-header>
	<div class="site-header__promo" role="region" aria-label="<?php esc_attr_e( 'Promotional message', 'shanelle' ); ?>">
		<p class="site-header__promo-text"><?php esc_html_e( 'Free shipping on orders over $49', 'shanelle' ); ?></p>
	</div>

	<div class="site-header__bar">
		<div class="container site-header__inner">
			<button
				class="site-header__menu-toggle"
				type="button"
				data-menu-toggle
				aria-expanded="false"
				aria-controls="mobile-drawer"
			>
				<span class="site-header__menu-icon" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Open menu', 'shanelle' ); ?></span>
			</button>

			<div class="site-header__brand">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php bloginfo( 'name' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<form class="site-header__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="header-search"><?php esc_html_e( 'Search products', 'shanelle' ); ?></label>
				<input
					id="header-search"
					class="site-header__search-input"
					type="search"
					name="s"
					placeholder="<?php esc_attr_e( 'Search styles…', 'shanelle' ); ?>"
					value="<?php echo esc_attr( get_search_query() ); ?>"
				>
				<input type="hidden" name="post_type" value="product">
				<button class="site-header__search-submit" type="submit" aria-label="<?php esc_attr_e( 'Submit search', 'shanelle' ); ?>">
					<span aria-hidden="true">⌕</span>
				</button>
			</form>

			<nav class="site-header__actions" aria-label="<?php esc_attr_e( 'Account and cart', 'shanelle' ); ?>">
				<a class="site-header__action" href="<?php echo esc_url( $shop_url ); ?>">
					<?php esc_html_e( 'Shop', 'shanelle' ); ?>
				</a>
				<?php if ( shanelle_is_woocommerce_active() ) : ?>
					<a class="site-header__action site-header__action--cart header-cart" href="<?php echo esc_url( $cart_url ); ?>">
						<?php esc_html_e( 'Bag', 'shanelle' ); ?>
						<?php shanelle_component( 'cart-count' ); ?>
					</a>
				<?php endif; ?>
			</nav>
		</div>
	</div>

	<?php if ( has_nav_menu( 'primary' ) ) : ?>
		<nav class="site-header__nav" aria-label="<?php esc_attr_e( 'Primary', 'shanelle' ); ?>">
			<div class="container">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'menu_class'     => 'nav-primary',
					'container'      => false,
					'depth'          => 2,
					'fallback_cb'    => false,
				) );
				?>
			</div>
		</nav>
	<?php endif; ?>

	<div id="mobile-drawer" class="mobile-drawer" data-mobile-drawer hidden>
		<div class="mobile-drawer__overlay" data-drawer-overlay></div>
		<div class="mobile-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Navigation menu', 'shanelle' ); ?>">
			<div class="mobile-drawer__header">
				<span class="mobile-drawer__title"><?php esc_html_e( 'Menu', 'shanelle' ); ?></span>
				<button class="mobile-drawer__close" type="button" data-drawer-close aria-label="<?php esc_attr_e( 'Close menu', 'shanelle' ); ?>">×</button>
			</div>
			<?php
			wp_nav_menu( array(
				'theme_location' => has_nav_menu( 'mobile' ) ? 'mobile' : 'primary',
				'menu_class'     => 'mobile-drawer__menu',
				'container'      => false,
				'fallback_cb'    => false,
			) );
			?>
		</div>
	</div>
</header>
