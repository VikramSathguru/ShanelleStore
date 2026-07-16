<?php
/**
 * Collection term archive page composer bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\Catalog\Helpers as CatalogHelpers;
use Shanelle\Catalog\Queries as CatalogQueries;

defined( 'ABSPATH' ) || exit;

/**
 * Composes collection term archives using collection hero chrome and ShopArchive catalog UI.
 */
final class CollectionPage {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/collection-page';

	private const COMPONENT_URI = SHANELLE_URI . '/components/collection-page';

	private const ROOT_ID = 'shanelle-collection-page';

	private const HERO_SIZE = 'shanelle-collection-hero';

	private const MOD_SHOW_CHILDREN = 'shanelle_collection_page_show_children';

	private const MOD_SHOW_BREADCRUMBS = 'shanelle_collection_page_show_breadcrumbs';

	private const MOD_EMPTY_MESSAGE = 'shanelle_collection_page_empty_message';

	/**
	 * Active collection term.
	 */
	private static ?\WP_Term $term = null;

	/**
	 * Active page state for the render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot collection archive page hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'after_setup_theme', array( self::class, 'register_image_sizes' ), 20 );
		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );

		add_filter( 'shanelle_shop_archive_grid_args', array( self::class, 'filter_grid_args' ) );
	}

	/**
	 * Register collection hero image size.
	 */
	public static function register_image_sizes(): void {
		add_image_size( self::HERO_SIZE, 1440, 640, true );
	}

	/**
	 * Register Theme Customizer settings for collection archives.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'shanelle_collection_page',
			array(
				'title'       => __( 'Archivos de colección', 'shanelle' ),
				'description' => __( 'Configura las páginas de archivo de cada colección.', 'shanelle' ),
				'priority'    => 175,
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_CHILDREN,
			__( 'Mostrar navegación de colecciones hijas', 'shanelle' ),
			true
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_BREADCRUMBS,
			__( 'Mostrar ruta de navegación en archivos de colección', 'shanelle' ),
			true
		);

		$wp_customize->add_setting(
			self::MOD_EMPTY_MESSAGE,
			array(
				'default'           => __( 'Esta colección está vacía por ahora. Explora otras ediciones abajo.', 'shanelle' ),
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_EMPTY_MESSAGE,
			array(
				'label'   => __( 'Mensaje de colección vacía', 'shanelle' ),
				'section' => 'shanelle_collection_page',
				'type'    => 'text',
			)
		);
	}

	/**
	 * Enqueue collection archive assets.
	 */
	public static function enqueue_assets(): void {
		if ( ! self::is_collection_archive() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-collection-page',
			self::COMPONENT_URI . '/collection-page.css',
			array( 'shanelle-main', 'shanelle-shop-archive', 'shanelle-product-grid' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-collection-page',
			self::COMPONENT_URI . '/collection-page.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-collection-page', 'type', 'module' );

		wp_localize_script(
			'shanelle-collection-page',
			'shanelleCollectionPage',
			array(
				'initialState' => self::build_page_state(),
				'i18n'         => array(
					'pageTitle' => __( 'Colección', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the collection archive page composition.
	 */
	public static function render(): void {
		if ( ! shanelle_is_woocommerce_active() || ! self::is_collection_archive() ) {
			return;
		}

		$term = get_queried_object();

		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		self::$term  = $term;
		self::$state = self::build_page_state();

		if ( ! wp_style_is( 'shanelle-collection-page', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/collection-page.php';

		self::$term  = null;
		self::$state  = array();
	}

	/**
	 * Render collection hero banner.
	 */
	public static function render_hero(): void {
		$collection  = is_array( self::$state['collection'] ?? null ) ? self::$state['collection'] : array();
		$hero_id     = (int) ( $collection['hero_id'] ?? 0 );
		$name        = (string) ( $collection['name'] ?? '' );
		$type_label  = (string) ( $collection['type_label'] ?? '' );
		$description = (string) ( $collection['description'] ?? '' );
		?>
		<section class="collection-page__hero" aria-labelledby="<?php echo esc_attr( self::get_heading_id() ); ?>">
			<div class="collection-page__hero-media">
				<?php if ( $hero_id > 0 ) : ?>
					<?php
					shanelle_responsive_image(
						$hero_id,
						self::HERO_SIZE,
						array(
							'class'         => 'collection-page__hero-image',
							'alt'           => $name,
							'loading'       => 'eager',
							'fetchpriority' => 'high',
							'decoding'      => 'async',
						)
					);
					?>
				<?php else : ?>
					<div class="collection-page__hero-placeholder" aria-hidden="true"></div>
				<?php endif; ?>

				<div class="collection-page__hero-copy">
					<div class="container collection-page__hero-copy-inner">
						<?php if ( '' !== $type_label ) : ?>
							<p class="collection-page__hero-type text-caption">
								<?php echo esc_html( $type_label ); ?>
							</p>
						<?php endif; ?>

						<h1 id="<?php echo esc_attr( self::get_heading_id() ); ?>" class="collection-page__hero-title text-h1">
							<?php echo esc_html( $name ); ?>
						</h1>

						<?php if ( '' !== wp_strip_all_tags( $description ) ) : ?>
							<div class="collection-page__hero-description text-body">
								<?php echo wp_kses_post( $description ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Render child or sibling collection navigation links.
	 */
	public static function render_related_collections(): void {
		if ( empty( self::$state['settings']['show_children'] ) ) {
			return;
		}

		$items = is_array( self::$state['related_collections'] ?? null ) ? self::$state['related_collections'] : array();

		if ( empty( $items ) ) {
			return;
		}

		$current_id = (int) ( self::$state['collection']['id'] ?? 0 );
		?>
		<nav class="collection-page__related" aria-label="<?php esc_attr_e( 'Colecciones relacionadas', 'shanelle' ); ?>">
			<ul class="collection-page__related-list" role="list">
				<?php foreach ( $items as $item ) : ?>
					<?php
					if ( ! is_array( $item ) || empty( $item['url'] ) ) {
						continue;
					}

					$is_current = (int) ( $item['id'] ?? 0 ) === $current_id;
					?>
					<li class="collection-page__related-item">
						<a
							class="collection-page__related-link<?php echo $is_current ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( (string) $item['url'] ); ?>"
							<?php echo $is_current ? 'aria-current="page"' : ''; ?>
						>
							<?php echo esc_html( (string) ( $item['name'] ?? '' ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}

	/**
	 * Render product count for the active collection archive.
	 */
	public static function render_product_count(): void {
		$count = ShopArchive::get_product_count();
		?>
		<p class="collection-page__count text-caption">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of products */
					_n( '%d producto', '%d productos', $count, 'shanelle' ),
					$count
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Customize ProductGrid args on collection archives.
	 *
	 * @param array<string, mixed> $grid_args Grid arguments.
	 * @return array<string, mixed>
	 */
	public static function filter_grid_args( array $grid_args ): array {
		if ( ! self::is_collection_archive() ) {
			return $grid_args;
		}

		$grid_args['grid_id'] = 'collection-page-grid';

		$empty_message = self::get_empty_message();

		if ( '' !== $empty_message ) {
			$grid_args['empty_message'] = $empty_message;
		}

		return apply_filters( 'shanelle_collection_page_grid_args', $grid_args );
	}

	/**
	 * Determine whether the current request is a collection archive.
	 */
	public static function is_collection_archive(): bool {
		return is_tax( CatalogHelpers::TAXONOMY );
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
	 * Whether breadcrumbs should render.
	 */
	public static function show_breadcrumbs(): bool {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();

		return ! empty( $settings['show_breadcrumbs'] );
	}

	/**
	 * Return configured empty message.
	 */
	public static function get_empty_message(): string {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();

		return (string) ( $settings['empty_message'] ?? '' );
	}

	/**
	 * Build normalized collection archive state.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_page_state(): array {
		$term       = self::$term instanceof \WP_Term ? self::$term : get_queried_object();
		$collection = ( $term instanceof \WP_Term ) ? CatalogQueries::get_collection( (int) $term->term_id ) : null;

		return apply_filters(
			'shanelle_collection_page_state',
			array(
				'collection'          => $collection,
				'related_collections' => self::resolve_related_collections( $term instanceof \WP_Term ? $term : null ),
				'product_count'       => ShopArchive::get_product_count(),
				'settings'            => self::get_settings(),
				'urls'                => array(
					'collections' => self::get_collections_index_url(),
					'shop'        => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
				),
			),
			$term instanceof \WP_Term ? $term : null
		);
	}

	/**
	 * Resolve child or sibling collections for archive navigation.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function resolve_related_collections( ?\WP_Term $term ): array {
		if ( ! $term instanceof \WP_Term ) {
			return array();
		}

		$settings = self::get_settings();

		if ( empty( $settings['show_children'] ) ) {
			return array();
		}

		$children = CatalogQueries::get_child_collections( (int) $term->term_id, true );

		if ( ! empty( $children ) ) {
			return $children;
		}

		if ( $term->parent > 0 ) {
			return CatalogQueries::get_child_collections( (int) $term->parent, true );
		}

		return array();
	}

	/**
	 * Return collections index page URL when available.
	 */
	private static function get_collections_index_url(): string {
		$pages = get_pages(
			array(
				'meta_key'   => '_wp_page_template',
				'meta_value' => 'page-templates/collections.php',
				'number'     => 1,
			)
		);

		if ( ! empty( $pages[0] ) && $pages[0] instanceof \WP_Post ) {
			return (string) get_permalink( $pages[0] );
		}

		return wc_get_page_permalink( 'shop' ) ?: home_url( '/' );
	}

	/**
	 * Read Theme Customizer settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		return apply_filters(
			'shanelle_collection_page_settings',
			array(
				'show_children'    => self::get_theme_mod_bool( self::MOD_SHOW_CHILDREN, true ),
				'show_breadcrumbs' => self::get_theme_mod_bool( self::MOD_SHOW_BREADCRUMBS, true ),
				'empty_message'    => self::get_theme_mod_string(
					self::MOD_EMPTY_MESSAGE,
					__( 'Esta colección está vacía por ahora. Explora otras ediciones abajo.', 'shanelle' )
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
				'section' => 'shanelle_collection_page',
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
