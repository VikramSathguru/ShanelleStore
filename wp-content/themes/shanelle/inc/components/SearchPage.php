<?php
/**
 * Search page composer bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Composes the product search results page using ShopArchive catalog chrome and ProductGrid.
 */
final class SearchPage {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/search-page';

	private const COMPONENT_URI = SHANELLE_URI . '/components/search-page';

	private const ROOT_ID = 'shanelle-search-page';

	private const MOD_PLACEHOLDER = 'shanelle_search_page_placeholder';

	private const MOD_SHOW_BREADCRUMBS = 'shanelle_search_page_show_breadcrumbs';

	private const MOD_EMPTY_MESSAGE = 'shanelle_search_page_empty_message';

	/**
	 * Active page state for the render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot search page hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'pre_get_posts', array( self::class, 'restrict_search_to_products' ) );
		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );

		add_filter( 'shanelle_shop_archive_grid_args', array( self::class, 'filter_grid_args' ) );
	}

	/**
	 * Limit front-end search requests to WooCommerce products.
	 *
	 * @param \WP_Query $query Query instance.
	 */
	public static function restrict_search_to_products( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		$query->set( 'post_type', 'product' );
	}

	/**
	 * Register Theme Customizer settings for the search page.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'shanelle_search_page',
			array(
				'title'       => __( 'Página de búsqueda', 'shanelle' ),
				'description' => __( 'Configura la presentación de los resultados de búsqueda de productos.', 'shanelle' ),
				'priority'    => 173,
			)
		);

		$wp_customize->add_setting(
			self::MOD_PLACEHOLDER,
			array(
				'default'           => __( 'Buscar estilos, marcas o categorías', 'shanelle' ),
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_PLACEHOLDER,
			array(
				'label'   => __( 'Texto del campo de búsqueda', 'shanelle' ),
				'section' => 'shanelle_search_page',
				'type'    => 'text',
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_BREADCRUMBS,
			__( 'Mostrar migas de pan en los resultados de búsqueda', 'shanelle' ),
			false
		);

		$wp_customize->add_setting(
			self::MOD_EMPTY_MESSAGE,
			array(
				'default'           => __( 'Prueba con otra palabra o explora nuestras novedades.', 'shanelle' ),
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_EMPTY_MESSAGE,
			array(
				'label'   => __( 'Mensaje de ayuda sin resultados', 'shanelle' ),
				'section' => 'shanelle_search_page',
				'type'    => 'text',
			)
		);
	}

	/**
	 * Enqueue search page assets.
	 */
	public static function enqueue_assets(): void {
		if ( ! is_search() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-search-page',
			self::COMPONENT_URI . '/search-page.css',
			array( 'shanelle-main', 'shanelle-shop-archive', 'shanelle-product-grid' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-search-page',
			self::COMPONENT_URI . '/search-page.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-search-page', 'type', 'module' );

		wp_localize_script(
			'shanelle-search-page',
			'shanelleSearchPage',
			array(
				'initialState' => self::build_page_state(),
				'i18n'         => array(
					'pageTitle'    => __( 'Buscar', 'shanelle' ),
					'searchLabel'  => __( 'Buscar productos', 'shanelle' ),
					'searchSubmit' => __( 'Buscar', 'shanelle' ),
					'clearSearch'  => __( 'Borrar búsqueda', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the search results page composition.
	 */
	public static function render(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		self::$state = self::build_page_state();

		if ( ! wp_style_is( 'shanelle-search-page', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/search-page.php';

		self::$state = array();
	}

	/**
	 * Render the product search form.
	 */
	public static function render_search_form(): void {
		$query       = self::get_search_query();
		$placeholder = self::get_placeholder();
		$action      = home_url( '/' );
		?>
		<form
			class="search-page__form"
			role="search"
			method="get"
			action="<?php echo esc_url( $action ); ?>"
			data-shanelle-search-form
		>
			<label class="screen-reader-text" for="<?php echo esc_attr( self::get_search_input_id() ); ?>">
				<?php esc_html_e( 'Buscar productos', 'shanelle' ); ?>
			</label>

			<div class="search-page__form-field">
				<input
					type="search"
					id="<?php echo esc_attr( self::get_search_input_id() ); ?>"
					class="input input--search search-page__input"
					name="s"
					value="<?php echo esc_attr( $query ); ?>"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					autocomplete="off"
					required
				/>
				<input type="hidden" name="post_type" value="product" />
				<button type="submit" class="btn btn--primary search-page__submit">
					<?php esc_html_e( 'Buscar', 'shanelle' ); ?>
				</button>
			</div>
		</form>
		<?php
	}

	/**
	 * Customize ProductGrid args on search requests.
	 *
	 * @param array<string, mixed> $grid_args Grid arguments.
	 * @return array<string, mixed>
	 */
	public static function filter_grid_args( array $grid_args ): array {
		if ( ! is_search() ) {
			return $grid_args;
		}

		$grid_args['grid_id'] = 'search-page-grid';

		$empty_message = self::get_empty_message();

		if ( '' !== $empty_message ) {
			$grid_args['empty_message'] = $empty_message;
		}

		return apply_filters( 'shanelle_search_page_grid_args', $grid_args );
	}

	/**
	 * Return root element ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return search input ID.
	 */
	public static function get_search_input_id(): string {
		return self::ROOT_ID . '-input';
	}

	/**
	 * Return page state JSON for client hydration.
	 */
	public static function get_state_json(): string {
		return wp_json_encode( self::$state ) ?: '{}';
	}

	/**
	 * Return the active search query.
	 */
	public static function get_search_query(): string {
		return get_search_query();
	}

	/**
	 * Return configured search placeholder copy.
	 */
	public static function get_placeholder(): string {
		$settings = is_array( self::$state['settings'] ?? null )
			? self::$state['settings']
			: self::get_settings();

		return (string) ( $settings['placeholder'] ?? __( 'Buscar estilos, marcas o categorías', 'shanelle' ) );
	}

	/**
	 * Return configured empty-results helper copy.
	 */
	public static function get_empty_message(): string {
		$settings = is_array( self::$state['settings'] ?? null )
			? self::$state['settings']
			: self::get_settings();

		return (string) ( $settings['empty_message'] ?? '' );
	}

	/**
	 * Whether breadcrumbs should render on search results.
	 */
	public static function show_breadcrumbs(): bool {
		$settings = is_array( self::$state['settings'] ?? null )
			? self::$state['settings']
			: self::get_settings();

		return ! empty( $settings['show_breadcrumbs'] );
	}

	/**
	 * Build normalized search page state.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_page_state(): array {
		return apply_filters(
			'shanelle_search_page_state',
			array(
				'query'   => self::get_search_query(),
				'title'   => ShopArchive::get_archive_title(),
				'count'   => ShopArchive::get_product_count(),
				'settings'=> self::get_settings(),
				'urls'    => array(
					'shop'   => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
					'search' => home_url( '/' ),
				),
			)
		);
	}

	/**
	 * Read Theme Customizer settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		return apply_filters(
			'shanelle_search_page_settings',
			array(
				'placeholder'      => self::get_theme_mod_string(
					self::MOD_PLACEHOLDER,
					__( 'Buscar estilos, marcas o categorías', 'shanelle' )
				),
				'show_breadcrumbs' => self::get_theme_mod_bool( self::MOD_SHOW_BREADCRUMBS, false ),
				'empty_message'    => self::get_theme_mod_string(
					self::MOD_EMPTY_MESSAGE,
					__( 'Prueba con otra palabra o explora nuestras novedades.', 'shanelle' )
				),
			)
		);
	}

	/**
	 * Sanitize checkbox customizer values.
	 */
	public static function sanitize_checkbox( mixed $value ): bool {
		return (bool) $value;
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
				'section' => 'shanelle_search_page',
				'type'    => 'checkbox',
			)
		);
	}

	/**
	 * Read a sanitized string theme mod.
	 */
	private static function get_theme_mod_string( string $key, string $default = '' ): string {
		$value = get_theme_mod( $key, $default );

		return is_string( $value ) ? $value : $default;
	}

	/**
	 * Read a sanitized boolean theme mod.
	 */
	private static function get_theme_mod_bool( string $key, bool $default ): bool {
		$value = get_theme_mod( $key, $default );

		return (bool) $value;
	}
}
