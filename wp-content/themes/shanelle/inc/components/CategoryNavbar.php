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
 * SHEIN-style horizontal category navbar for the site header.
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

	private const MOD_SHOW_DROPDOWN = 'shanelle_category_navbar_show_dropdown';

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
			self::MOD_SHOW_DROPDOWN,
			__( 'Mostrar menú desplegable de Categorías', 'shanelle' ),
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
					'categories'  => __( 'Categorías', 'shanelle' ),
					'scrollNext'  => __( 'Desplazar categorías hacia adelante', 'shanelle' ),
					'closePanel'  => __( 'Cerrar menú de categorías', 'shanelle' ),
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
			'categories' => self::get_categories(),
			'menu_items' => self::get_menu_items(),
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
	 * Return dropdown panel ID.
	 */
	public static function get_panel_id(): string {
		return self::ROOT_ID . '-panel';
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
			'show_dropdown' => (bool) get_theme_mod( self::MOD_SHOW_DROPDOWN, true ),
			'max'           => self::sanitize_max( get_theme_mod( self::MOD_MAX, 12 ) ),
			'new_in_label'  => self::get_label( self::MOD_NEW_IN_LABEL, __( 'Lo nuevo', 'shanelle' ) ),
			'new_in_url'    => self::get_url( self::MOD_NEW_IN_URL, add_query_arg( 'orderby', 'date', $shop_url ) ),
			'sale_label'    => self::get_label( self::MOD_SALE_LABEL, __( 'Oferta', 'shanelle' ) ),
			'sale_url'      => self::get_url( self::MOD_SALE_URL, add_query_arg( 'filter', 'onsale', $shop_url ) ),
		);
	}

	/**
	 * Return top-level WooCommerce categories for the navbar.
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

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$categories[] = array(
				'id'       => $term->term_id,
				'slug'     => $term->slug,
				'name'     => $term->name,
				'url'      => (string) $link,
				'children' => self::get_child_categories( $term->term_id ),
			);
		}

		$categories = apply_filters( 'shanelle_category_navbar_categories', $categories, $settings, $terms );

		return is_array( $categories ) ? array_values( array_filter( $categories ) ) : array();
	}

	/**
	 * Return child categories for dropdown columns.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_child_categories( int $parent_id ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent_id,
				'hide_empty' => true,
				'number'     => 8,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$children = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$children[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'url'  => (string) $link,
			);
		}

		return $children;
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

		foreach ( $items as $item ) {
			if ( ! $item instanceof \WP_Post ) {
				continue;
			}

			if ( (int) $item->menu_item_parent > 0 ) {
				continue;
			}

			$menu_items[] = array(
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

		if ( ! empty( $settings['show_dropdown'] ) && ! empty( $categories ) ) {
			self::render_dropdown_trigger();
		}

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
			self::render_link(
				(string) ( $category['url'] ?? '' ),
				(string) ( $category['name'] ?? '' )
			);
		}
	}

	/**
	 * Render the Categories dropdown trigger.
	 */
	public static function render_dropdown_trigger(): void {
		?>
		<li class="category-navbar__item category-navbar__item--dropdown">
			<button
				type="button"
				class="category-navbar__link category-navbar__link--dropdown"
				data-category-navbar-toggle
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( self::get_panel_id() ); ?>"
			>
				<?php esc_html_e( 'Categorías', 'shanelle' ); ?>
				<svg class="category-navbar__chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
			</button>
		</li>
		<?php
	}

	/**
	 * Render categories dropdown panel.
	 */
	public static function render_dropdown_panel(): void {
		$settings   = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();
		$categories = is_array( self::$state['categories'] ?? null ) ? self::$state['categories'] : self::get_categories();

		if ( empty( $settings['show_dropdown'] ) || empty( $categories ) ) {
			return;
		}
		?>
		<div
			class="category-navbar__panel"
			id="<?php echo esc_attr( self::get_panel_id() ); ?>"
			data-category-navbar-panel
			hidden
		>
			<div class="container category-navbar__panel-inner">
				<ul class="category-navbar__panel-grid" role="list">
					<?php foreach ( $categories as $category ) : ?>
						<li class="category-navbar__panel-group">
							<a class="category-navbar__panel-title" href="<?php echo esc_url( (string) ( $category['url'] ?? '#' ) ); ?>">
								<?php echo esc_html( (string) ( $category['name'] ?? '' ) ); ?>
							</a>

							<?php
							$children = is_array( $category['children'] ?? null ) ? $category['children'] : array();

							if ( ! empty( $children ) ) :
								?>
								<ul class="category-navbar__panel-sublist" role="list">
									<?php foreach ( $children as $child ) : ?>
										<li>
											<a class="category-navbar__panel-link" href="<?php echo esc_url( (string) ( $child['url'] ?? '#' ) ); ?>">
												<?php echo esc_html( (string) ( $child['name'] ?? '' ) ); ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
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
