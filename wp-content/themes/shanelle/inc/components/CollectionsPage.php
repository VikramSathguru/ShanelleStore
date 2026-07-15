<?php
/**
 * Collections index page composer bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\Catalog\Queries as CatalogQueries;

defined( 'ABSPATH' ) || exit;

/**
 * Composes the storefront collections index page.
 */
final class CollectionsPage {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/collections-page';

	private const COMPONENT_URI = SHANELLE_URI . '/components/collections-page';

	private const ROOT_ID = 'shanelle-collections-page';

	private const PAGE_TEMPLATE = 'page-templates/collections.php';

	private const MOD_TITLE = 'shanelle_collections_page_title';

	private const MOD_INTRO = 'shanelle_collections_page_intro';

	private const MOD_SHOW_COUNTS = 'shanelle_collections_page_show_counts';

	private const MOD_SHOW_TYPE_BADGES = 'shanelle_collections_page_show_type_badges';

	private const MOD_ACTIVE_ONLY = 'shanelle_collections_page_active_only';

	private const MOD_GROUP_BY_TYPE = 'shanelle_collections_page_group_by_type';

	/**
	 * Active page state for the render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot collections index page hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp', array( self::class, 'configure_page_hooks' ), 20 );
	}

	/**
	 * Adjust hooks on the collections index page.
	 */
	public static function configure_page_hooks(): void {
		if ( ! self::is_collections_page() ) {
			return;
		}

		remove_action( 'woocommerce_before_main_content', 'shanelle_before_main_content', 5 );
		remove_action( 'woocommerce_after_main_content', 'shanelle_after_main_content', 50 );

		add_filter( 'woocommerce_show_page_title', '__return_false' );
		add_filter( 'the_title', array( self::class, 'hide_page_title' ), 10, 2 );
	}

	/**
	 * Hide the default WordPress page title.
	 *
	 * @param string          $title Post title.
	 * @param int|string|null $id    Post ID.
	 */
	public static function hide_page_title( string $title, $id = null ): string {
		if ( ! self::is_collections_page() || ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}

		return '';
	}

	/**
	 * Register Theme Customizer settings for the collections page.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'shanelle_collections_page',
			array(
				'title'       => __( 'Página de colecciones', 'shanelle' ),
				'description' => __( 'Configura la página índice de colecciones. Asigna la plantilla de Colecciones a una página de WordPress.', 'shanelle' ),
				'priority'    => 174,
			)
		);

		$wp_customize->add_setting(
			self::MOD_TITLE,
			array(
				'default'           => __( 'Colecciones', 'shanelle' ),
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_TITLE,
			array(
				'label'   => __( 'Título de la página', 'shanelle' ),
				'section' => 'shanelle_collections_page',
				'type'    => 'text',
			)
		);

		$wp_customize->add_setting(
			self::MOD_INTRO,
			array(
				'default'           => __( 'Explora ediciones curadas por temporada, destacados y campañas.', 'shanelle' ),
				'sanitize_callback' => 'sanitize_textarea_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_INTRO,
			array(
				'label'   => __( 'Texto introductorio', 'shanelle' ),
				'section' => 'shanelle_collections_page',
				'type'    => 'textarea',
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_COUNTS,
			__( 'Mostrar cantidad de productos en las tarjetas', 'shanelle' ),
			true
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_TYPE_BADGES,
			__( 'Mostrar insignias del tipo de colección', 'shanelle' ),
			false
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_ACTIVE_ONLY,
			__( 'Ocultar colecciones inactivas', 'shanelle' ),
			true
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_GROUP_BY_TYPE,
			__( 'Agrupar colecciones por tipo', 'shanelle' ),
			true
		);
	}

	/**
	 * Enqueue collections page assets.
	 */
	public static function enqueue_assets(): void {
		if ( ! self::is_collections_page() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-collections-page',
			self::COMPONENT_URI . '/collections-page.css',
			array( 'shanelle-main', 'shanelle-collection-card' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-collections-page',
			self::COMPONENT_URI . '/collections-page.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-collections-page', 'type', 'module' );

		wp_localize_script(
			'shanelle-collections-page',
			'shanelleCollectionsPage',
			array(
				'initialState' => self::build_page_state(),
				'i18n'         => array(
					'pageTitle' => __( 'Colecciones', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the collections index page composition.
	 */
	public static function render(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		self::$state = self::build_page_state();

		if ( ! wp_style_is( 'shanelle-collections-page', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/collections-page.php';

		self::$state = array();
	}

	/**
	 * Render page header copy.
	 */
	public static function render_header(): void {
		?>
		<header class="collections-page__header">
			<h1 id="<?php echo esc_attr( self::get_heading_id() ); ?>" class="collections-page__title text-h1">
				<?php echo esc_html( self::get_page_title() ); ?>
			</h1>

			<?php if ( '' !== self::get_intro_copy() ) : ?>
				<p class="collections-page__intro text-body text-muted">
					<?php echo esc_html( self::get_intro_copy() ); ?>
				</p>
			<?php endif; ?>
		</header>
		<?php
	}

	/**
	 * Render grouped or flat collection listings.
	 */
	public static function render_listings(): void {
		$groups = is_array( self::$state['groups'] ?? null ) ? self::$state['groups'] : array();

		if ( empty( $groups ) ) {
			self::render_empty();
			return;
		}

		$card_args = self::get_card_args();

		if ( ! empty( self::$state['settings']['group_by_type'] ) ) {
			foreach ( $groups as $group ) {
				if ( ! is_array( $group ) || empty( $group['items'] ) ) {
					continue;
				}

				self::render_group( $group, $card_args );
			}

			return;
		}

		$items = is_array( self::$state['collections'] ?? null ) ? self::$state['collections'] : array();
		self::render_grid( $items, '', $card_args );
	}

	/**
	 * Render a grouped collection section.
	 *
	 * @param array<string, mixed> $group    Group payload.
	 * @param array<string, mixed> $card_args Card render arguments.
	 */
	public static function render_group( array $group, array $card_args ): void {
		$label = (string) ( $group['label'] ?? '' );
		$items = is_array( $group['items'] ?? null ) ? $group['items'] : array();

		if ( empty( $items ) ) {
			return;
		}
		?>
		<section class="collections-page__group" aria-label="<?php echo esc_attr( $label ); ?>">
			<?php if ( '' !== $label ) : ?>
				<h2 class="collections-page__group-title text-h3">
					<?php echo esc_html( $label ); ?>
				</h2>
			<?php endif; ?>

			<?php self::render_grid( $items, (string) ( $group['type'] ?? '' ), $card_args ); ?>
		</section>
		<?php
	}

	/**
	 * Render a collection card grid.
	 *
	 * @param array<int, array<string, mixed>> $items     Collection cards.
	 * @param string                           $group_key Optional group key.
	 * @param array<string, mixed>             $card_args Card render arguments.
	 */
	public static function render_grid( array $items, string $group_key, array $card_args ): void {
		?>
		<div
			class="collections-page__grid"
			data-shanelle-collections-grid
			<?php echo '' !== $group_key ? 'data-collection-group="' . esc_attr( $group_key ) . '"' : ''; ?>
		>
			<?php
			foreach ( $items as $collection ) {
				if ( is_array( $collection ) ) {
					CollectionCard::render( $collection, $card_args );
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render empty state when no collections are available.
	 */
	public static function render_empty(): void {
		shanelle_component(
			'empty-state',
			array(
				'title'    => __( 'No hay colecciones disponibles', 'shanelle' ),
				'message'  => __( 'Vuelve pronto para nuevas ediciones curadas.', 'shanelle' ),
				'cta_url'  => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
				'cta_text' => __( 'Ver toda la tienda', 'shanelle' ),
			)
		);
	}

	/**
	 * Determine whether the current request is the collections index page.
	 */
	public static function is_collections_page(): bool {
		return is_page_template( self::PAGE_TEMPLATE );
	}

	/**
	 * Return root element ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return page heading ID.
	 */
	public static function get_heading_id(): string {
		return self::ROOT_ID . '-heading';
	}

	/**
	 * Return page state JSON for client hydration.
	 */
	public static function get_state_json(): string {
		return wp_json_encode( self::$state ) ?: '{}';
	}

	/**
	 * Return configured page title.
	 */
	public static function get_page_title(): string {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();

		return (string) ( $settings['title'] ?? __( 'Colecciones', 'shanelle' ) );
	}

	/**
	 * Return configured intro copy.
	 */
	public static function get_intro_copy(): string {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();

		return (string) ( $settings['intro'] ?? '' );
	}

	/**
	 * Return card render arguments.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_card_args(): array {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();

		return array(
			'show_count' => ! empty( $settings['show_counts'] ),
			'show_type'  => ! empty( $settings['show_type_badges'] ),
		);
	}

	/**
	 * Build normalized collections page state.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_page_state(): array {
		$settings    = self::get_settings();
		$collections = CatalogQueries::get_collections(
			array(
				'active_only' => ! empty( $settings['active_only'] ),
			)
		);
		$groups      = ! empty( $settings['group_by_type'] )
			? CatalogQueries::group_collections_by_type( $collections )
			: array();

		return apply_filters(
			'shanelle_collections_page_state',
			array(
				'collections' => $collections,
				'groups'      => $groups,
				'settings'    => $settings,
				'urls'        => array(
					'shop' => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
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
			'shanelle_collections_page_settings',
			array(
				'title'          => self::get_theme_mod_string( self::MOD_TITLE, __( 'Colecciones', 'shanelle' ) ),
				'intro'          => self::get_theme_mod_string(
					self::MOD_INTRO,
					__( 'Explora ediciones curadas por temporada, destacados y campañas.', 'shanelle' )
				),
				'show_counts'    => self::get_theme_mod_bool( self::MOD_SHOW_COUNTS, true ),
				'show_type_badges' => self::get_theme_mod_bool( self::MOD_SHOW_TYPE_BADGES, false ),
				'active_only'    => self::get_theme_mod_bool( self::MOD_ACTIVE_ONLY, true ),
				'group_by_type'  => self::get_theme_mod_bool( self::MOD_GROUP_BY_TYPE, true ),
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
				'section' => 'shanelle_collections_page',
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
