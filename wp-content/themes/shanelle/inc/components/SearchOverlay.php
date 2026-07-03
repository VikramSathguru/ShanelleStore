<?php
/**
 * Search overlay bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Site-wide search overlay shell and configuration.
 */
final class SearchOverlay {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/search-overlay';

	private const COMPONENT_URI = SHANELLE_URI . '/components/search-overlay';

	private const ROOT_ID = 'shanelle-search-overlay';

	private const MOD_PLACEHOLDER = 'shanelle_search_overlay_placeholder';

	private const MOD_POPULAR = 'shanelle_search_overlay_popular';

	private const MOD_EMPTY_MESSAGE = 'shanelle_search_overlay_empty_message';

	private const MOD_MIN_QUERY = 'shanelle_search_overlay_min_query';

	private const MOD_DEBOUNCE = 'shanelle_search_overlay_debounce';

	private const MOD_PRODUCT_LIMIT = 'shanelle_search_overlay_product_limit';

	private const MOD_TERM_LIMIT = 'shanelle_search_overlay_term_limit';

	private const RECENT_STORAGE_KEY = 'shanelle_recent_searches';

	/**
	 * Active overlay state for the render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot search overlay hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( self::class, 'render' ), 15 );
	}

	/**
	 * Register Theme Customizer settings.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'shanelle_search_overlay',
			array(
				'title'       => __( 'Search Overlay', 'shanelle' ),
				'description' => __( 'Configure live search overlay behavior and suggestions.', 'shanelle' ),
				'priority'    => 172,
			)
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_PLACEHOLDER,
			__( 'Search input placeholder', 'shanelle' ),
			__( 'Search dresses, tops, collections…', 'shanelle' )
		);

		self::register_textarea_control(
			$wp_customize,
			self::MOD_POPULAR,
			__( 'Popular searches', 'shanelle' ),
			"dresses\nnew arrivals\ntops\nsale"
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_EMPTY_MESSAGE,
			__( 'No results helper message', 'shanelle' ),
			__( 'Try another keyword or browse our popular searches.', 'shanelle' )
		);

		self::register_number_control(
			$wp_customize,
			self::MOD_MIN_QUERY,
			__( 'Minimum characters before live search', 'shanelle' ),
			2,
			1,
			5
		);

		self::register_number_control(
			$wp_customize,
			self::MOD_DEBOUNCE,
			__( 'Debounce delay (ms)', 'shanelle' ),
			300,
			150,
			1000
		);

		self::register_number_control(
			$wp_customize,
			self::MOD_PRODUCT_LIMIT,
			__( 'Maximum product suggestions', 'shanelle' ),
			6,
			3,
			12
		);

		self::register_number_control(
			$wp_customize,
			self::MOD_TERM_LIMIT,
			__( 'Maximum category/collection suggestions', 'shanelle' ),
			4,
			2,
			8
		);
	}

	/**
	 * Enqueue overlay assets site-wide.
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Render overlay shell in the footer.
	 */
	public static function render(): void {
		if ( is_admin() ) {
			return;
		}

		self::$state = self::build_overlay_state();

		if ( ! wp_style_is( 'shanelle-search-overlay', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/search-overlay.php';

		self::$state = array();
	}

	/**
	 * Return overlay root ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return search input ID.
	 */
	public static function get_input_id(): string {
		return self::ROOT_ID . '-input';
	}

	/**
	 * Return results panel ID.
	 */
	public static function get_results_id(): string {
		return self::ROOT_ID . '-results';
	}

	/**
	 * Return overlay title ID.
	 */
	public static function get_title_id(): string {
		return self::ROOT_ID . '-title';
	}

	/**
	 * Return overlay state JSON.
	 */
	public static function get_state_json(): string {
		return wp_json_encode( self::$state ) ?: '{}';
	}

	/**
	 * Return configured placeholder copy.
	 */
	public static function get_placeholder(): string {
		$settings = is_array( self::$state['settings'] ?? null )
			? self::$state['settings']
			: self::get_settings();

		return (string) ( $settings['placeholder'] ?? __( 'Search dresses, tops, collections…', 'shanelle' ) );
	}

	/**
	 * Return parsed popular search terms.
	 *
	 * @return array<int, string>
	 */
	public static function get_popular_searches(): array {
		$settings = is_array( self::$state['settings'] ?? null )
			? self::$state['settings']
			: self::get_settings();

		$raw = (string) ( $settings['popular_searches'] ?? '' );

		if ( '' === $raw ) {
			return array();
		}

		$terms = preg_split( '/\r\n|\r|\n|,/', $raw ) ?: array();

		$terms = array_map(
			static function ( string $term ): string {
				return sanitize_text_field( trim( $term ) );
			},
			$terms
		);

		$terms = array_values( array_filter( array_unique( $terms ) ) );

		return array_slice( $terms, 0, 8 );
	}

	/**
	 * Return Theme Customizer settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		return apply_filters(
			'shanelle_search_overlay_settings',
			array(
				'placeholder'       => self::get_theme_mod_string(
					self::MOD_PLACEHOLDER,
					__( 'Search dresses, tops, collections…', 'shanelle' )
				),
				'popular_searches'  => self::get_theme_mod_string(
					self::MOD_POPULAR,
					"dresses\nnew arrivals\ntops\nsale"
				),
				'empty_message'     => self::get_theme_mod_string(
					self::MOD_EMPTY_MESSAGE,
					__( 'Try another keyword or browse our popular searches.', 'shanelle' )
				),
				'min_query_length'  => self::get_theme_mod_int( self::MOD_MIN_QUERY, 2 ),
				'debounce_ms'       => self::get_theme_mod_int( self::MOD_DEBOUNCE, 300 ),
				'product_limit'     => self::get_theme_mod_int( self::MOD_PRODUCT_LIMIT, 6 ),
				'term_limit'        => self::get_theme_mod_int( self::MOD_TERM_LIMIT, 4 ),
				'recent_storage_key'=> self::RECENT_STORAGE_KEY,
				'recent_limit'      => 5,
			)
		);
	}

	/**
	 * Build overlay state for client hydration.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_overlay_state(): array {
		$settings = self::get_settings();

		return apply_filters(
			'shanelle_search_overlay_state',
			array(
				'settings' => $settings,
				'popular'  => self::get_popular_searches(),
				'urls'     => array(
					'results' => home_url( '/' ),
					'shop'    => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
				),
			)
		);
	}

	/**
	 * Register and enqueue overlay assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-search-results',
			SHANELLE_URI . '/components/search-results/search-results.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_style(
			'shanelle-search-overlay',
			self::COMPONENT_URI . '/search-overlay.css',
			array( 'shanelle-main', 'shanelle-search-results' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-search-overlay',
			self::COMPONENT_URI . '/search-overlay.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-search-overlay', 'type', 'module' );

		wp_localize_script(
			'shanelle-search-overlay',
			'shanelleSearchOverlay',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'restUrl'      => rest_url( SearchController::REST_NAMESPACE . '/search' ),
				'action'       => SearchController::AJAX_ACTION,
				'nonce'        => wp_create_nonce( SearchController::NONCE_ACTION ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'initialState' => self::build_overlay_state(),
				'i18n'         => array(
					'title'           => __( 'Search', 'shanelle' ),
					'close'           => __( 'Close search', 'shanelle' ),
					'submit'          => __( 'Search', 'shanelle' ),
					'clear'           => __( 'Clear search', 'shanelle' ),
					'loading'         => __( 'Searching…', 'shanelle' ),
					'resultsUpdated'  => __( 'Search suggestions updated', 'shanelle' ),
					'noResults'       => __( 'No results found', 'shanelle' ),
					'recentSearches'  => __( 'Recent searches', 'shanelle' ),
					'clearRecent'     => __( 'Clear recent searches', 'shanelle' ),
					'popularSearches' => __( 'Popular searches', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Register a text customizer control.
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
				'section' => 'shanelle_search_overlay',
				'type'    => 'text',
			)
		);
	}

	/**
	 * Register a textarea customizer control.
	 */
	private static function register_textarea_control(
		\WP_Customize_Manager $wp_customize,
		string $mod_name,
		string $label,
		string $default
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => $default,
				'sanitize_callback' => array( self::class, 'sanitize_popular_searches' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'       => $label,
				'description' => __( 'One search term per line or comma-separated.', 'shanelle' ),
				'section'     => 'shanelle_search_overlay',
				'type'        => 'textarea',
			)
		);
	}

	/**
	 * Register a number customizer control.
	 */
	private static function register_number_control(
		\WP_Customize_Manager $wp_customize,
		string $mod_name,
		string $label,
		int $default,
		int $min,
		int $max
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => $default,
				'sanitize_callback' => static function ( mixed $value ) use ( $min, $max ): int {
					return max( $min, min( $max, absint( $value ) ) );
				},
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => 'shanelle_search_overlay',
				'type'    => 'number',
				'input_attrs' => array(
					'min'  => $min,
					'max'  => $max,
					'step' => 1,
				),
			)
		);
	}

	/**
	 * Sanitize popular search textarea values.
	 */
	public static function sanitize_popular_searches( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$terms = preg_split( '/\r\n|\r|\n|,/', $value ) ?: array();

		$terms = array_map(
			static function ( string $term ): string {
				return sanitize_text_field( trim( $term ) );
			},
			$terms
		);

		$terms = array_values( array_filter( array_unique( $terms ) ) );

		return implode( "\n", array_slice( $terms, 0, 8 ) );
	}

	/**
	 * Read a sanitized string theme mod.
	 */
	private static function get_theme_mod_string( string $key, string $default = '' ): string {
		$value = get_theme_mod( $key, $default );

		return is_string( $value ) ? $value : $default;
	}

	/**
	 * Read a sanitized integer theme mod.
	 */
	private static function get_theme_mod_int( string $key, int $default ): int {
		$value = get_theme_mod( $key, $default );

		return absint( $value );
	}
}
