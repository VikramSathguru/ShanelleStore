<?php
/**
 * Site header component.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Shanelle\Components\SearchOverlay;
use Shanelle\Components\SiteHeader;

$shop_url           = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$account_url        = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$cart_url           = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$account_href       = is_string( $account_url ) ? $account_url : wp_login_url();
$contact_url        = SiteHeader::get_contact_url();
$show_promo         = SiteHeader::show_promo();
$promo_items        = $show_promo ? SiteHeader::get_promo_items() : array();

if ( shanelle_is_woocommerce_active() && ! is_user_logged_in() ) {
	$account_href = trailingslashit( $account_href ) . '#customer_login';
}

$search_action      = home_url( '/' );
$search_placeholder = class_exists( SearchOverlay::class )
	? SearchOverlay::get_placeholder()
	: __( 'Buscar vestidos, tops, colecciones…', 'shanelle' );
$logo_fallback_uri  = SHANELLE_URI . '/assets/images/logo.png';
$has_drawer_menu    = has_nav_menu( 'mobile' ) || has_nav_menu( 'primary' ) || has_nav_menu( 'categories' );
?>
<header class="site-header" data-header>
	<?php if ( ! empty( $promo_items ) ) : ?>
		<?php
		$promo_speed = SiteHeader::get_promo_speed();
		$promo_label = __( 'Promociones', 'shanelle' );
		?>
		<div
			class="site-header__promo is-static"
			data-shanelle-promo-banner
			data-promo-speed="<?php echo esc_attr( (string) $promo_speed ); ?>"
			role="region"
			aria-label="<?php echo esc_attr( $promo_label ); ?>"
		>
			<div class="container-fluid site-header__promo-inner">
				<div class="site-header__promo-viewport" data-shanelle-promo-viewport>
					<div class="site-header__promo-track" data-shanelle-promo-track>
						<?php foreach ( $promo_items as $index => $item ) : ?>
							<?php
							$emphasis = (string) ( $item['emphasis'] ?? '' );
							$text     = (string) ( $item['text'] ?? '' );
							$url      = (string) ( $item['url'] ?? '' );
							?>
							<?php if ( $index > 0 ) : ?>
								<span class="site-header__promo-sep" aria-hidden="true"></span>
							<?php endif; ?>
							<p class="site-header__promo-item">
								<?php if ( '' !== $url ) : ?>
									<a class="site-header__promo-link" href="<?php echo esc_url( $url ); ?>">
										<?php if ( '' !== $emphasis ) : ?>
											<strong><?php echo esc_html( $emphasis ); ?></strong>
										<?php endif; ?>
										<?php if ( '' !== $text ) : ?>
											<span><?php echo esc_html( $text ); ?></span>
										<?php endif; ?>
									</a>
								<?php else : ?>
									<span class="site-header__promo-copy">
										<?php if ( '' !== $emphasis ) : ?>
											<strong><?php echo esc_html( $emphasis ); ?></strong>
										<?php endif; ?>
										<?php if ( '' !== $text ) : ?>
											<span><?php echo esc_html( $text ); ?></span>
										<?php endif; ?>
									</span>
								<?php endif; ?>
							</p>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="site-header__chrome" data-header-chrome>
	<div class="site-header__main">
		<div class="container-fluid site-header__main-inner">
			<div class="site-header__start">
				<button
					type="button"
					class="site-header__menu-toggle site-header__icon-btn"
					data-menu-toggle
					aria-controls="site-mobile-drawer"
					aria-expanded="false"
					aria-label="<?php esc_attr_e( 'Abrir menú', 'shanelle' ); ?>"
				>
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
				</button>

				<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php
					if ( has_custom_logo() ) {
						$logo_id = (int) get_theme_mod( 'custom_logo' );

						if ( $logo_id > 0 ) {
							echo wp_get_attachment_image(
								$logo_id,
								'full',
								false,
								array(
									'class'    => 'custom-logo',
									'decoding' => 'async',
								)
							); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
					} else {
						?>
						<img
							src="<?php echo esc_url( $logo_fallback_uri ); ?>"
							alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"
							width="48"
							height="48"
							decoding="async"
						/>
						<?php
					}
					?>
				</a>
			</div>

			<div class="site-header__search">
				<form
					class="site-header__search-form"
					role="search"
					method="get"
					action="<?php echo esc_url( $search_action ); ?>"
				>
					<label class="screen-reader-text" for="site-header-search">
						<?php esc_html_e( 'Buscar productos', 'shanelle' ); ?>
					</label>

					<input
						type="search"
						id="site-header-search"
						class="site-header__search-input"
						name="s"
						value="<?php echo esc_attr( get_search_query() ); ?>"
						placeholder="<?php echo esc_attr( $search_placeholder ); ?>"
						autocomplete="off"
						autocapitalize="off"
						spellcheck="false"
						enterkeyhint="search"
						data-shanelle-header-search
					/>

					<?php if ( shanelle_is_woocommerce_active() ) : ?>
						<input type="hidden" name="post_type" value="product" />
					<?php endif; ?>

					<button type="submit" class="site-header__search-submit" aria-label="<?php esc_attr_e( 'Buscar', 'shanelle' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
					</button>
				</form>

				<button
					type="button"
					class="site-header__search-toggle site-header__icon-btn"
					data-shanelle-search-open
					aria-label="<?php esc_attr_e( 'Buscar', 'shanelle' ); ?>"
				>
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
				</button>
			</div>

			<div class="site-header__actions">
				<a
					class="site-header__action site-header__icon-btn"
					href="<?php echo esc_url( $account_href ); ?>"
					aria-label="<?php esc_attr_e( 'Mi cuenta', 'shanelle' ); ?>"
				>
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-4 6-6 8-6s6.5 2 8 6"/></svg>
				</a>

				<?php if ( shanelle_is_woocommerce_active() ) : ?>
					<a
						class="site-header__action site-header__icon-btn site-header__action--cart"
						href="<?php echo esc_url( is_string( $cart_url ) ? $cart_url : home_url( '/cart/' ) ); ?>"
						data-shanelle-mini-cart-open
						aria-label="<?php esc_attr_e( 'Abrir bolsa', 'shanelle' ); ?>"
					>
						<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6 7h15l-1.5 9h-12z"/><path d="M9 11V8a3 3 0 0 1 6 0v3"/></svg>
						<?php shanelle_component( 'cart-count' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php if ( shanelle_is_woocommerce_active() ) : ?>
		<?php \Shanelle\Components\CategoryNavbar::render(); ?>
	<?php endif; ?>
	</div><!-- .site-header__chrome -->

	<div
		class="site-header__drawer"
		id="site-mobile-drawer"
		data-mobile-drawer
		role="dialog"
		aria-modal="true"
		aria-labelledby="site-mobile-drawer-title"
		hidden
	>
		<button
			type="button"
			class="site-header__drawer-overlay"
			data-drawer-overlay
			tabindex="-1"
			aria-hidden="true"
		></button>

		<div class="site-header__drawer-panel" data-drawer-panel>
			<div class="site-header__drawer-head">
				<p class="site-header__drawer-title text-label" id="site-mobile-drawer-title"><?php esc_html_e( 'Menú', 'shanelle' ); ?></p>
				<button
					type="button"
					class="site-header__drawer-close site-header__icon-btn"
					data-drawer-close
					aria-label="<?php esc_attr_e( 'Cerrar menú', 'shanelle' ); ?>"
				>
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
				</button>
			</div>

			<?php if ( $has_drawer_menu ) : ?>
				<nav class="site-header__drawer-nav" aria-label="<?php esc_attr_e( 'Navegación móvil', 'shanelle' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => has_nav_menu( 'mobile' )
								? 'mobile'
								: ( has_nav_menu( 'primary' ) ? 'primary' : 'categories' ),
							'menu_class'     => 'site-header__drawer-menu',
							'container'      => false,
							'depth'          => 2,
							'fallback_cb'    => false,
						)
					);
					?>
				</nav>
			<?php else : ?>
				<nav class="site-header__drawer-nav" aria-label="<?php esc_attr_e( 'Navegación móvil', 'shanelle' ); ?>">
					<ul class="site-header__drawer-menu" role="list">
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'shanelle' ); ?></a></li>
						<?php if ( shanelle_is_woocommerce_active() ) : ?>
							<li><a href="<?php echo esc_url( is_string( $shop_url ) ? $shop_url : home_url( '/' ) ); ?>"><?php esc_html_e( 'Tienda', 'shanelle' ); ?></a></li>
						<?php endif; ?>
						<li><a href="<?php echo esc_url( $account_href ); ?>"><?php esc_html_e( 'Mi cuenta', 'shanelle' ); ?></a></li>
						<?php if ( '' !== $contact_url ) : ?>
							<li><a href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contacto', 'shanelle' ); ?></a></li>
						<?php endif; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<div class="site-header__drawer-actions">
				<?php if ( '' !== $contact_url ) : ?>
					<a class="btn btn--outline btn--block" href="<?php echo esc_url( $contact_url ); ?>">
						<?php esc_html_e( 'Atención al cliente', 'shanelle' ); ?>
					</a>
				<?php endif; ?>

				<button type="button" class="btn btn--outline btn--block" data-shanelle-search-open>
					<?php esc_html_e( 'Buscar', 'shanelle' ); ?>
				</button>

				<?php if ( shanelle_is_woocommerce_active() ) : ?>
					<a class="btn btn--primary btn--block" href="<?php echo esc_url( is_string( $shop_url ) ? $shop_url : home_url( '/' ) ); ?>">
						<?php esc_html_e( 'Comprar ahora', 'shanelle' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</header>
