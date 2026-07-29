<?php
/**
 * Header category navbar component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * SHEIN-style horizontal category navbar with desktop hover mega-menu.
 */
final class CategoryNavbar {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/category-navbar';

	private const COMPONENT_URI = SHANELLE_URI . '/components/category-navbar';

	private const ROOT_ID = 'shanelle-category-navbar';

	private const MOD_ENABLED = 'shanelle_category_navbar_enabled';

	private const MOD_MAX = 'shanelle_category_navbar_max';

	private const MOD_NEW_IN_LABEL = 'shanelle_category_navbar_new_in_label';

	private const MOD_NEW_IN_URL = 'shanelle_category_navbar_new_in_url';

	private const MOD_SALE_LABEL = 'shanelle_category_navbar_sale_label';

	private const MOD_SALE_URL = 'shanelle_category_navbar_sale_url';

	private const MOD_SHOW_MEGA = 'shanelle_category_navbar_show_dropdown';

	private const SHOP_BY_CHILD_LIMIT = 12;

	private const PICKS_LIMIT = 18;

	private const PICKS_PRODUCT_FALLBACK_LIMIT = 12;

	/**
	 * Active render state.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot category navbar hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'after_setup_theme', array( self::class, 'register_menu' ), 20 );
		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Register optional manual override menu location.
	 */
	public static function register_menu(): void {
		register_nav_menus(
			array(
				'category_navbar' => __( 'Barra de categorías', 'shanelle' ),
			)
		);
	}

	/**
	 * Register Theme Customizer settings.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'shanelle_category_navbar',
			array(
				'title'       => __( 'Barra de categorías', 'shanelle' ),
				'description' => __( 'Configura la barra de navegación de categorías del encabezado.', 'shanelle' ),
				'priority'    => 121,
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_ENABLED,
			__( 'Mostrar barra de categorías', 'shanelle' ),
			true
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_MEGA,
			__( 'Mostrar mega menú al pasar el cursor', 'shanelle' ),
			true
		);

		$wp_customize->add_setting(
			self::MOD_MAX,
			array(
				'default'           => 12,
				'sanitize_callback' => array( self::class, 'sanitize_max' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_MAX,
			array(
				'label'       => __( 'Máximo de enlaces de categoría', 'shanelle' ),
				'description' => __( 'Limita las categorías de WooCommerce mostradas en la barra de desplazamiento.', 'shanelle' ),
				'section'     => 'shanelle_category_navbar',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 24,
					'step' => 1,
				),
			)
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_NEW_IN_LABEL,
			__( 'Etiqueta de Lo nuevo', 'shanelle' ),
			__( 'Lo nuevo', 'shanelle' )
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_NEW_IN_URL,
			__( 'URL de Lo nuevo', 'shanelle' ),
			''
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_SALE_LABEL,
			__( 'Etiqueta de Oferta', 'shanelle' ),
			__( 'Oferta', 'shanelle' )
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_SALE_URL,
			__( 'URL de Oferta', 'shanelle' ),
			''
		);
	}

	/**
	 * Enqueue component assets.
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() || ! self::is_enabled() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-category-navbar',
			self::COMPONENT_URI . '/category-navbar.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-category-navbar',
			self::COMPONENT_URI . '/category-navbar.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-category-navbar', 'type', 'module' );

		wp_localize_script(
			'shanelle-category-navbar',
			'shanelleCategoryNavbar',
			array(
				'i18n' => array(
					'categories' => __( 'Categorías', 'shanelle' ),
					'scrollNext' => __( 'Desplazar categorías hacia adelante', 'shanelle' ),
					'closePanel' => __( 'Cerrar mega menú', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the category navbar.
	 */
	public static function render(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		self::$state = array(
			'settings'   => self::get_settings(),
			'categories'  => self::get_categories(),
			'menu_items'  => self::get_menu_items(),
			'cta_cards'   => self::get_cta_cards(),
		);

		if ( ! wp_style_is( 'shanelle-category-navbar', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/category-navbar.php';

		self::$state = array();
	}

	/**
	 * Return root element ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return mega-menu panel ID.
	 */
	public static function get_panel_id(): string {
		return self::ROOT_ID . '-mega';
	}

	/**
	 * Whether the navbar is enabled.
	 */
	public static function is_enabled(): bool {
		return (bool) get_theme_mod( self::MOD_ENABLED, true );
	}

	/**
	 * Return active settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
		$shop_url = is_string( $shop_url ) ? $shop_url : home_url( '/' );

		return array(
			'show_mega'    => (bool) get_theme_mod( self::MOD_SHOW_MEGA, true ),
			'max'          => self::sanitize_max( get_theme_mod( self::MOD_MAX, 12 ) ),
			'new_in_label' => self::get_label( self::MOD_NEW_IN_LABEL, __( 'Lo nuevo', 'shanelle' ) ),
			'new_in_url'   => self::get_url( self::MOD_NEW_IN_URL, add_query_arg( 'orderby', 'date', $shop_url ) ),
			'sale_label'   => self::get_label( self::MOD_SALE_LABEL, __( 'Oferta', 'shanelle' ) ),
			'sale_url'     => self::get_url( self::MOD_SALE_URL, add_query_arg( 'filter', 'onsale', $shop_url ) ),
		);
	}

	/**
	 * Return top-level WooCommerce categories for the navbar + mega-menu.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_categories(): array {
		$settings = is_array( self::$state['settings'] ?? null )
			? self::$state['settings']
			: self::get_settings();

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
				'number'     => (int) $settings['max'],
				'meta_key'   => 'order',
				'orderby'    => 'meta_value_num',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$categories = array();

		foreach ( $terms as $index => $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$url = (string) $link;

			$categories[] = array(
				'index'   => (int) $index,
				'id'      => $term->term_id,
				'slug'    => $term->slug,
				'name'    => $term->name,
				'url'     => $url,
				'shop_by' => self::build_shop_by_items( $term, $url ),
				'picks'   => self::build_picks_items( $term ),
			);
		}

		$categories = apply_filters( 'shanelle_category_navbar_categories', $categories, $settings, $terms );

		return is_array( $categories ) ? array_values( array_filter( $categories ) ) : array();
	}

	/**
	 * Return CTA explanation cards for the mega-menu footer strip.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_cta_cards(): array {
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
		$shop_url = is_string( $shop_url ) ? $shop_url : home_url( '/' );

		$cards = array(
			array(
				'index' => 0,
				'icon'  => 'truck',
				'title' => __( 'Envío confiable', 'shanelle' ),
				'text'  => __( 'Recibe tu pedido con seguimiento claro.', 'shanelle' ),
				'url'   => self::resolve_info_url( array( 'envio', 'shipping' ), $shop_url ),
			),
			array(
				'index' => 1,
				'icon'  => 'refresh',
				'title' => __( 'Cambios fáciles', 'shanelle' ),
				'text'  => __( 'Proceso simple si algo no te queda.', 'shanelle' ),
				'url'   => self::resolve_info_url( array( 'devoluciones', 'returns' ), $shop_url ),
			),
			array(
				'index' => 2,
				'icon'  => 'shield',
				'title' => __( 'Pago seguro', 'shanelle' ),
				'text'  => __( 'Checkout protegido con WooCommerce.', 'shanelle' ),
				'url'   => $shop_url,
			),
			array(
				'index' => 3,
				'icon'  => 'heart',
				'title' => __( 'Atención cercana', 'shanelle' ),
				'text'  => __( 'Estamos para ayudarte en cada compra.', 'shanelle' ),
				'url'   => self::resolve_info_url( array( 'contacto', 'contact' ), $shop_url ),
			),
		);

		$cards = apply_filters( 'shanelle_category_navbar_cta_cards', $cards );

		return is_array( $cards ) ? array_values( $cards ) : array();
	}

	/**
	 * Return optional manual menu items when a menu is assigned.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_menu_items(): array {
		if ( ! has_nav_menu( 'category_navbar' ) ) {
			return array();
		}

		$locations = get_nav_menu_locations();
		$menu_id   = (int) ( $locations['category_navbar'] ?? 0 );

		if ( $menu_id <= 0 ) {
			return array();
		}

		$items = wp_get_nav_menu_items( $menu_id );

		if ( ! is_array( $items ) || empty( $items ) ) {
			return array();
		}

		$menu_items = array();

		foreach ( $items as $index => $item ) {
			if ( ! $item instanceof \WP_Post ) {
				continue;
			}

			if ( (int) $item->menu_item_parent > 0 ) {
				continue;
			}

			$menu_items[] = array(
				'index' => (int) $index,
				'id'    => (int) $item->ID,
				'title' => (string) $item->title,
				'url'   => (string) $item->url,
			);
		}

		return $menu_items;
	}

	/**
	 * Render scrollable navbar links.
	 */
	public static function render_links(): void {
		$settings   = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();
		$categories = is_array( self::$state['categories'] ?? null ) ? self::$state['categories'] : self::get_categories();
		$menu_items = is_array( self::$state['menu_items'] ?? null ) ? self::$state['menu_items'] : self::get_menu_items();
		$show_mega  = ! empty( $settings['show_mega'] ) && ! empty( $categories );

		self::render_pinned_link(
			(string) $settings['new_in_url'],
			(string) $settings['new_in_label']
		);

		self::render_pinned_link(
			(string) $settings['sale_url'],
			(string) $settings['sale_label']
		);

		if ( ! empty( $menu_items ) ) {
			foreach ( $menu_items as $item ) {
				self::render_link(
					(string) ( $item['url'] ?? '' ),
					(string) ( $item['title'] ?? '' )
				);
			}
			return;
		}

		foreach ( $categories as $category ) {
			if ( $show_mega ) {
				self::render_category_trigger( $category );
				continue;
			}

			self::render_link(
				(string) ( $category['url'] ?? '' ),
				(string) ( $category['name'] ?? '' )
			);
		}
	}

	/**
	 * Render the hover mega-menu panel.
	 */
	public static function render_mega_menu(): void {
		$settings   = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();
		$categories = is_array( self::$state['categories'] ?? null ) ? self::$state['categories'] : self::get_categories();
		$menu_items = is_array( self::$state['menu_items'] ?? null ) ? self::$state['menu_items'] : self::get_menu_items();
		$cta_cards  = is_array( self::$state['cta_cards'] ?? null ) ? self::$state['cta_cards'] : self::get_cta_cards();

		// Manual menu location replaces the strip; mega-menu stays WooCommerce-category driven.
		if ( empty( $settings['show_mega'] ) || empty( $categories ) || ! empty( $menu_items ) ) {
			return;
		}
		?>
		<div
			class="category-navbar__mega"
			id="<?php echo esc_attr( self::get_panel_id() ); ?>"
			data-category-navbar-mega
			hidden
		>
			<div class="category-navbar__mega-backdrop" data-category-navbar-backdrop tabindex="-1" aria-hidden="true"></div>

			<div class="category-navbar__mega-panel" role="region" aria-label="<?php esc_attr_e( 'Explorar categorías', 'shanelle' ); ?>">
				<div class="container-fluid category-navbar__mega-inner">
					<div class="category-navbar__mega-layout">
						<aside class="category-navbar__mega-sidebar" aria-label="<?php esc_attr_e( 'Categorías', 'shanelle' ); ?>">
							<ul class="category-navbar__mega-sidebar-list" role="list">
								<?php foreach ( $categories as $category ) : ?>
									<?php
									$category_id = (int) ( $category['id'] ?? 0 );
									$index       = (int) ( $category['index'] ?? 0 );
									?>
									<li class="category-navbar__mega-sidebar-item" data-index="<?php echo esc_attr( (string) $index ); ?>">
										<button
											type="button"
											class="category-navbar__mega-sidebar-btn"
											data-category-navbar-sidebar
											data-category-id="<?php echo esc_attr( (string) $category_id ); ?>"
											aria-pressed="false"
										>
											<span class="category-navbar__mega-sidebar-label"><?php echo esc_html( (string) ( $category['name'] ?? '' ) ); ?></span>
											<svg class="category-navbar__mega-sidebar-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
										</button>
									</li>
								<?php endforeach; ?>
							</ul>
						</aside>

						<div class="category-navbar__mega-content">
							<?php foreach ( $categories as $category ) : ?>
								<?php
								$category_id = (int) ( $category['id'] ?? 0 );
								$shop_by     = is_array( $category['shop_by'] ?? null ) ? $category['shop_by'] : array();
								$picks       = is_array( $category['picks'] ?? null ) ? $category['picks'] : array();
								?>
								<div
									class="category-navbar__mega-pane"
									data-category-navbar-pane
									data-category-id="<?php echo esc_attr( (string) $category_id ); ?>"
									hidden
								>
									<div class="category-navbar__mega-columns">
										<section class="category-navbar__mega-section category-navbar__mega-section--shop" aria-labelledby="<?php echo esc_attr( 'mega-shop-' . $category_id ); ?>">
											<h3 class="category-navbar__mega-heading" id="<?php echo esc_attr( 'mega-shop-' . $category_id ); ?>">
												<svg class="category-navbar__mega-heading-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
												<?php esc_html_e( 'Comprar por categoría', 'shanelle' ); ?>
											</h3>

											<ul class="category-navbar__circle-grid category-navbar__circle-grid--shop" role="list">
												<?php foreach ( $shop_by as $item ) : ?>
													<?php self::render_circle_item( $item ); ?>
												<?php endforeach; ?>
											</ul>
										</section>

										<section class="category-navbar__mega-section category-navbar__mega-section--picks" aria-labelledby="<?php echo esc_attr( 'mega-picks-' . $category_id ); ?>">
											<h3 class="category-navbar__mega-heading" id="<?php echo esc_attr( 'mega-picks-' . $category_id ); ?>">
												<?php esc_html_e( 'Elegidos para ti', 'shanelle' ); ?>
											</h3>

											<?php if ( ! empty( $picks ) ) : ?>
												<ul class="category-navbar__circle-grid category-navbar__circle-grid--picks" role="list">
													<?php foreach ( $picks as $item ) : ?>
														<?php self::render_circle_item( $item ); ?>
													<?php endforeach; ?>
												</ul>
											<?php else : ?>
												<p class="category-navbar__mega-empty">
													<a href="<?php echo esc_url( (string) ( $category['url'] ?? '#' ) ); ?>">
														<?php
														printf(
															/* translators: %s: category name */
															esc_html__( 'Ver todo en %s', 'shanelle' ),
															esc_html( (string) ( $category['name'] ?? '' ) )
														);
														?>
													</a>
												</p>
											<?php endif; ?>
										</section>
									</div>
								</div>
							<?php endforeach; ?>

							<?php if ( ! empty( $cta_cards ) ) : ?>
								<section class="category-navbar__mega-cta" aria-label="<?php esc_attr_e( 'Beneficios de compra', 'shanelle' ); ?>">
									<ul class="category-navbar__cta-grid" role="list">
										<?php foreach ( $cta_cards as $card ) : ?>
											<?php self::render_cta_card( $card ); ?>
										<?php endforeach; ?>
									</ul>
								</section>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one circular shop/picks item (shared markup for mapped arrays).
	 *
	 * @param array<string, mixed> $item Circle item.
	 */
	public static function render_circle_item( array $item ): void {
		$label = trim( (string) ( $item['label'] ?? '' ) );
		$url   = trim( (string) ( $item['url'] ?? '' ) );
		$index = (int) ( $item['index'] ?? 0 );

		if ( '' === $label || '' === $url ) {
			return;
		}

		$icon       = (string) ( $item['icon'] ?? '' );
		$image_html = (string) ( $item['image_html'] ?? '' );
		$initial    = (string) ( $item['initial'] ?? self::get_initial( $label ) );
		?>
		<li class="category-navbar__circle-item" data-index="<?php echo esc_attr( (string) $index ); ?>">
			<a class="category-navbar__circle-link" href="<?php echo esc_url( $url ); ?>">
				<span class="category-navbar__circle-media">
					<?php if ( '' !== $icon ) : ?>
						<span class="category-navbar__circle-icon category-navbar__circle-icon--<?php echo esc_attr( $icon ); ?>" aria-hidden="true">
							<?php self::render_circle_icon( $icon ); ?>
						</span>
					<?php elseif ( '' !== $image_html ) : ?>
						<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php else : ?>
						<span class="category-navbar__circle-placeholder" aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
					<?php endif; ?>
				</span>
				<span class="category-navbar__circle-label"><?php echo esc_html( $label ); ?></span>
			</a>
		</li>
		<?php
	}

	/**
	 * Render one CTA explanation card (shared markup for mapped array).
	 *
	 * @param array<string, mixed> $card CTA card.
	 */
	public static function render_cta_card( array $card ): void {
		$title = trim( (string) ( $card['title'] ?? '' ) );
		$text  = trim( (string) ( $card['text'] ?? '' ) );
		$url   = trim( (string) ( $card['url'] ?? '' ) );
		$index = (int) ( $card['index'] ?? 0 );
		$icon  = (string) ( $card['icon'] ?? 'spark' );

		if ( '' === $title ) {
			return;
		}

		$tag   = '' !== $url ? 'a' : 'div';
		$attrs = '' !== $url ? ' href="' . esc_url( $url ) . '"' : '';
		?>
		<li class="category-navbar__cta-item" data-index="<?php echo esc_attr( (string) $index ); ?>">
			<<?php echo esc_attr( $tag ); ?> class="category-navbar__cta-card"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<span class="category-navbar__cta-icon" aria-hidden="true">
					<?php self::render_cta_icon( $icon ); ?>
				</span>
				<span class="category-navbar__cta-copy">
					<span class="category-navbar__cta-title"><?php echo esc_html( $title ); ?></span>
					<?php if ( '' !== $text ) : ?>
						<span class="category-navbar__cta-text"><?php echo esc_html( $text ); ?></span>
					<?php endif; ?>
				</span>
			</<?php echo esc_attr( $tag ); ?>>
		</li>
		<?php
	}

	/**
	 * Build "shop by category" circle items for a parent term.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_shop_by_items( \WP_Term $term, string $url ): array {
		$items      = array();
		$index      = 0;
		$term_image = self::get_term_image_html( $term->term_id );

		$view_all = array(
			'index' => $index++,
			'label' => __( 'Ver todo', 'shanelle' ),
			'url'   => $url,
		);

		if ( '' !== $term_image ) {
			$view_all['image_html'] = $term_image;
			$view_all['initial']    = self::get_initial( $term->name );
		} else {
			$view_all['icon'] = 'grid';
		}

		$items[] = $view_all;
		$items[] = array(
			'index' => $index++,
			'label' => __( 'Lo nuevo', 'shanelle' ),
			'url'   => add_query_arg( 'orderby', 'date', $url ),
			'icon'  => 'spark',
		);
		$items[] = array(
			'index' => $index++,
			'label' => __( 'Mejor valorados', 'shanelle' ),
			'url'   => add_query_arg( 'orderby', 'rating', $url ),
			'icon'  => 'star',
		);

		$children = self::get_child_terms( $term->term_id, self::SHOP_BY_CHILD_LIMIT );

		foreach ( $children as $child ) {
			$items[] = self::term_to_circle_item( $child, $index++ );
		}

		$items = apply_filters( 'shanelle_category_navbar_shop_by_items', $items, $term );

		return is_array( $items ) ? array_values( $items ) : array();
	}

	/**
	 * Build "picks for you" circle items.
	 *
	 * Prefer grandchild categories, then children. When the category is flat
	 * (products assigned directly, no subcategories), fall back to product
	 * thumbnails from that category so the mega-menu is never empty.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_picks_items( \WP_Term $term ): array {
		$children = self::get_child_terms( $term->term_id, 8 );
		$picks    = array();
		$index    = 0;

		foreach ( $children as $child ) {
			$grandchildren = self::get_child_terms( $child->term_id, 6 );

			foreach ( $grandchildren as $grandchild ) {
				$picks[] = self::term_to_circle_item( $grandchild, $index++ );

				if ( count( $picks ) >= self::PICKS_LIMIT ) {
					break 2;
				}
			}
		}

		if ( empty( $picks ) ) {
			foreach ( $children as $child ) {
				$picks[] = self::term_to_circle_item( $child, $index++ );

				if ( count( $picks ) >= self::PICKS_LIMIT ) {
					break;
				}
			}
		}

		if ( empty( $picks ) ) {
			$picks = self::build_product_circle_items( $term, self::PICKS_PRODUCT_FALLBACK_LIMIT );
		}

		$picks = apply_filters( 'shanelle_category_navbar_picks_items', $picks, $term );

		return is_array( $picks ) ? array_values( $picks ) : array();
	}

	/**
	 * Build circle items from published products in a category.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_product_circle_items( \WP_Term $term, int $limit ): array {
		if ( ! function_exists( 'wc_get_products' ) || $limit < 1 ) {
			return array();
		}

		$products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => $limit,
				'category' => array( $term->slug ),
				'orderby'  => 'date',
				'order'    => 'DESC',
				'return'   => 'objects',
			)
		);

		if ( ! is_array( $products ) || empty( $products ) ) {
			return array();
		}

		$items = array();

		foreach ( $products as $index => $product ) {
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$permalink = $product->get_permalink();
			$name      = $product->get_name();

			if ( '' === $permalink || '' === $name ) {
				continue;
			}

			$items[] = array(
				'index'      => (int) $index,
				'id'         => $product->get_id(),
				'label'      => $name,
				'url'        => $permalink,
				'image_html' => self::get_product_image_html( $product ),
				'initial'    => self::get_initial( $name ),
			);
		}

		return $items;
	}

	/**
	 * Convert a term into a circle-grid item.
	 *
	 * @return array<string, mixed>
	 */
	private static function term_to_circle_item( \WP_Term $term, int $index ): array {
		$link = get_term_link( $term );
		$url  = is_wp_error( $link ) ? '#' : (string) $link;

		return array(
			'index'      => $index,
			'id'         => $term->term_id,
			'label'      => $term->name,
			'url'        => $url,
			'image_html' => self::get_term_image_html( $term->term_id ),
			'initial'    => self::get_initial( $term->name ),
		);
	}

	/**
	 * Fetch child product categories.
	 *
	 * @return array<int, \WP_Term>
	 */
	private static function get_child_terms( int $parent_id, int $limit ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent_id,
				'hide_empty' => true,
				'number'     => $limit,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$terms,
				static function ( $term ): bool {
					return $term instanceof \WP_Term;
				}
			)
		);
	}

	/**
	 * Return category thumbnail HTML.
	 */
	private static function get_term_image_html( int $term_id ): string {
		$thumbnail_id = (int) get_term_meta( $term_id, 'thumbnail_id', true );

		if ( $thumbnail_id <= 0 || ! wp_attachment_is_image( $thumbnail_id ) ) {
			return '';
		}

		$html = wp_get_attachment_image(
			$thumbnail_id,
			'thumbnail',
			false,
			array(
				'class'    => 'category-navbar__circle-image',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => '',
			)
		);

		return is_string( $html ) ? $html : '';
	}

	/**
	 * Return product thumbnail HTML for circle items.
	 */
	private static function get_product_image_html( \WC_Product $product ): string {
		$image_id = (int) $product->get_image_id();

		if ( $image_id <= 0 || ! wp_attachment_is_image( $image_id ) ) {
			return '';
		}

		$html = wp_get_attachment_image(
			$image_id,
			'thumbnail',
			false,
			array(
				'class'    => 'category-navbar__circle-image',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => '',
			)
		);

		return is_string( $html ) ? $html : '';
	}

	/**
	 * Resolve an informational page URL by candidate slugs.
	 *
	 * @param array<int, string> $slugs Candidate page slugs.
	 */
	private static function resolve_info_url( array $slugs, string $fallback ): string {
		foreach ( $slugs as $slug ) {
			$page = get_page_by_path( $slug );

			if ( $page instanceof \WP_Post ) {
				$url = get_permalink( $page );

				if ( is_string( $url ) && '' !== $url ) {
					return $url;
				}
			}
		}

		return $fallback;
	}

	/**
	 * Render a category trigger that opens the mega-menu on hover/focus.
	 *
	 * @param array<string, mixed> $category Category payload.
	 */
	private static function render_category_trigger( array $category ): void {
		$url  = trim( (string) ( $category['url'] ?? '' ) );
		$name = trim( (string) ( $category['name'] ?? '' ) );
		$id   = (int) ( $category['id'] ?? 0 );

		if ( '' === $url || '' === $name || $id <= 0 ) {
			return;
		}
		?>
		<li class="category-navbar__item category-navbar__item--mega">
			<a
				class="category-navbar__link"
				href="<?php echo esc_url( $url ); ?>"
				data-category-navbar-trigger
				data-category-id="<?php echo esc_attr( (string) $id ); ?>"
				aria-haspopup="true"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( self::get_panel_id() ); ?>"
			>
				<?php echo esc_html( $name ); ?>
			</a>
		</li>
		<?php
	}

	/**
	 * Render a pinned navbar link.
	 */
	private static function render_pinned_link( string $url, string $label ): void {
		if ( '' === trim( $label ) || '' === trim( $url ) ) {
			return;
		}

		self::render_link( $url, $label );
	}

	/**
	 * Render a single navbar link item.
	 */
	private static function render_link( string $url, string $label ): void {
		if ( '' === trim( $label ) || '' === trim( $url ) ) {
			return;
		}
		?>
		<li class="category-navbar__item">
			<a class="category-navbar__link" href="<?php echo esc_url( $url ); ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		</li>
		<?php
	}

	/**
	 * Render an inline SVG for circle icon types.
	 */
	private static function render_circle_icon( string $icon ): void {
		switch ( $icon ) {
			case 'spark':
				echo '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/></svg>';
				return;
			case 'star':
				echo '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="m12 3 2.7 5.5 6 .9-4.4 4.2 1 6L12 16.9 6.7 19.6l1-6L3.3 9.4l6-.9L12 3Z"/></svg>';
				return;
			case 'grid':
			default:
				echo '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>';
		}
	}

	/**
	 * Render an inline SVG for CTA card icons.
	 */
	private static function render_cta_icon( string $icon ): void {
		switch ( $icon ) {
			case 'truck':
				echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7V10Z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>';
				return;
			case 'refresh':
				echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.6-6.2"/><path d="M21 3v6h-6"/></svg>';
				return;
			case 'shield':
				echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 7.8 8 9 4.6-1.2 8-4 8-9V6l-8-3Z"/></svg>';
				return;
			case 'heart':
				echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M19.5 12.5 12 20l-7.5-7.5a4.5 4.5 0 1 1 7.5-5.2 4.5 4.5 0 1 1 7.5 5.2Z"/></svg>';
				return;
			default:
				self::render_circle_icon( 'spark' );
		}
	}

	/**
	 * Return a single display initial.
	 */
	private static function get_initial( string $label ): string {
		$label = trim( $label );

		if ( '' === $label ) {
			return '•';
		}

		if ( function_exists( 'mb_substr' ) ) {
			return mb_strtoupper( mb_substr( $label, 0, 1 ) );
		}

		return strtoupper( substr( $label, 0, 1 ) );
	}

	/**
	 * Sanitize max categories value.
	 */
	public static function sanitize_max( mixed $value ): int {
		$max = absint( $value );

		if ( $max < 1 ) {
			return 1;
		}

		if ( $max > 24 ) {
			return 24;
		}

		return $max;
	}

	/**
	 * Return a sanitized label with fallback.
	 */
	private static function get_label( string $mod, string $fallback ): string {
		$value = sanitize_text_field( (string) get_theme_mod( $mod, $fallback ) );

		return '' !== $value ? $value : $fallback;
	}

	/**
	 * Return a sanitized URL with fallback.
	 */
	private static function get_url( string $mod, string $fallback ): string {
		$value = esc_url_raw( (string) get_theme_mod( $mod, '' ) );

		return '' !== $value ? $value : $fallback;
	}

	/**
	 * Register a checkbox customizer control.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	private static function register_checkbox_control(
		\WP_Customize_Manager $wp_customize,
		string $mod_name,
		string $label,
		bool $default
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => $default,
				'sanitize_callback' => array( self::class, 'sanitize_checkbox' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => 'shanelle_category_navbar',
				'type'    => 'checkbox',
			)
		);
	}

	/**
	 * Register a text customizer control.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	private static function register_text_control(
		\WP_Customize_Manager $wp_customize,
		string $mod_name,
		string $label,
		string $default
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => $default,
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => 'shanelle_category_navbar',
				'type'    => 'text',
			)
		);
	}

	/**
	 * Sanitize checkbox customizer values.
	 */
	public static function sanitize_checkbox( mixed $value ): bool {
		return (bool) $value;
	}
}
