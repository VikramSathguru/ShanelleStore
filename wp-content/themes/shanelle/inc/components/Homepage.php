<?php
/**
 * Homepage page composer bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\Catalog\Helpers as CatalogHelpers;

defined( 'ABSPATH' ) || exit;

/**
 * Composes the storefront homepage from existing theme components.
 */
final class Homepage {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/homepage';

	private const COMPONENT_URI = SHANELLE_URI . '/components/homepage';

	private const ROOT_ID = 'shanelle-homepage';

	private const SECTION_COUNT = 2;

	private const CATEGORY_ICON_COUNT = 16;

	private const FEATURED_COLLECTION_COUNT = 3;

	private const FOR_YOU_DEFAULT_LIMIT = 50;

	private const MOD_FOR_YOU_TITLE = 'shanelle_homepage_for_you_title';

	private const MOD_FOR_YOU_LIMIT = 'shanelle_homepage_for_you_limit';

	private const MOD_FOR_YOU_ORDERBY = 'shanelle_homepage_for_you_orderby';

	/**
	 * Product section configuration for the active render cycle.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static array $sections = array();

	/**
	 * Boot homepage hooks.
	 */
	public static function boot(): void {
		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Register Theme Customizer settings for homepage product sections.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		if ( ! $wp_customize->get_panel( 'shanelle_homepage' ) ) {
			$wp_customize->add_panel(
				'shanelle_homepage',
				array(
					'title'       => __( 'Inicio Shanelle', 'shanelle' ),
					'description' => __( 'Configura las secciones de la página de inicio.', 'shanelle' ),
					'priority'    => 160,
				)
			);
		}

		$wp_customize->add_section(
			'shanelle_homepage_products',
			array(
				'title'       => __( 'Secciones de productos', 'shanelle' ),
				'description' => __( 'Configura las cuadrículas de productos de inicio. El banner principal y la navegación de categorías se gestionan en sus propias secciones.', 'shanelle' ),
				'panel'       => 'shanelle_homepage',
				'priority'    => 30,
			)
		);

		for ( $index = 1; $index <= self::SECTION_COUNT; $index++ ) {
			self::register_product_section_controls( $wp_customize, $index );
		}

		$wp_customize->add_section(
			'shanelle_homepage_for_you',
			array(
				'title'       => __( 'Cuadrícula Para ti', 'shanelle' ),
				'description' => __( 'Configura el feed principal de productos de la página de inicio.', 'shanelle' ),
				'panel'       => 'shanelle_homepage',
				'priority'    => 40,
			)
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_FOR_YOU_TITLE,
			__( 'Título de la sección', 'shanelle' ),
			__( 'Para ti', 'shanelle' ),
			'shanelle_homepage_for_you'
		);

		$wp_customize->add_setting(
			self::MOD_FOR_YOU_LIMIT,
			array(
				'default'           => self::FOR_YOU_DEFAULT_LIMIT,
				'sanitize_callback' => array( self::class, 'sanitize_for_you_limit' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_FOR_YOU_LIMIT,
			array(
				'label'       => __( 'Cantidad inicial de productos', 'shanelle' ),
				'description' => __( 'Productos mostrados antes de hacer clic en Ver más.', 'shanelle' ),
				'section'     => 'shanelle_homepage_for_you',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 10,
					'max'  => 100,
					'step' => 5,
				),
			)
		);

		$wp_customize->add_setting(
			self::MOD_FOR_YOU_ORDERBY,
			array(
				'default'           => 'popularity',
				'sanitize_callback' => array( self::class, 'sanitize_orderby' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_FOR_YOU_ORDERBY,
			array(
				'label'   => __( 'Orden de productos', 'shanelle' ),
				'section' => 'shanelle_homepage_for_you',
				'type'    => 'select',
				'choices' => self::get_orderby_choices(),
			)
		);
	}

	/**
	 * Enqueue homepage assets on the front page.
	 */
	public static function enqueue_assets(): void {
		if ( ! is_front_page() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-homepage',
			self::COMPONENT_URI . '/homepage.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-homepage',
			self::COMPONENT_URI . '/homepage.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-homepage', 'type', 'module' );

		wp_localize_script(
			'shanelle-homepage',
			'shanelleHomepage',
			array(
				'sections' => self::build_sections(),
				'i18n'     => array(
					'pageLabel' => __( 'Inicio', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the homepage composition.
	 */
	public static function render(): void {
		if ( ! is_front_page() ) {
			return;
		}

		self::$sections = self::build_sections();

		if ( ! wp_style_is( 'shanelle-homepage', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/homepage.php';

		self::$sections = array();
	}

	/**
	 * Render the hero promo grid with side tiles and center carousel.
	 */
	public static function render_hero_promo(): void {
		require self::COMPONENT_DIR . '/partials/hero-promo.php';
	}

	/**
	 * Render the circular category icon grid.
	 */
	public static function render_category_icons(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		$items = self::get_category_icon_items();

		if ( empty( $items ) ) {
			return;
		}

		require self::COMPONENT_DIR . '/partials/category-icons.php';
	}

	/**
	 * Render featured collection columns.
	 */
	public static function render_featured_collections(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		$collections = self::get_featured_collections();

		if ( empty( $collections ) ) {
			return;
		}

		require self::COMPONENT_DIR . '/partials/featured-collections.php';
	}

	/**
	 * Render the main For You product feed.
	 */
	public static function render_for_you_grid(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		$config = self::get_for_you_config();
		$query  = self::build_for_you_query_vars( $config );

		if ( empty( $query ) ) {
			return;
		}

		require self::COMPONENT_DIR . '/partials/for-you-grid.php';
	}

	/**
	 * Return promo tiles for a hero sidebar.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_promo_tiles( string $side ): array {
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
		$shop_url = is_string( $shop_url ) ? $shop_url : home_url( '/' );

		$left = array(
			array(
				'index' => 0,
				'label' => __( 'Oferta flash', 'shanelle' ),
				'url'   => add_query_arg( 'filter', 'onsale', $shop_url ),
			),
			array(
				'index' => 1,
				'label' => __( 'Lo nuevo', 'shanelle' ),
				'url'   => add_query_arg( 'orderby', 'date', $shop_url ),
			),
			array(
				'index' => 2,
				'label' => __( 'Tallas plus', 'shanelle' ),
				'url'   => $shop_url,
			),
		);

		$right = array(
			array(
				'index' => 0,
				'label' => __( 'Más vendidos', 'shanelle' ),
				'url'   => add_query_arg( 'orderby', 'popularity', $shop_url ),
			),
			array(
				'index' => 1,
				'label' => __( 'Menos de $25', 'shanelle' ),
				'url'   => $shop_url,
			),
			array(
				'index' => 2,
				'label' => __( 'Tendencias', 'shanelle' ),
				'url'   => add_query_arg( 'orderby', 'rating', $shop_url ),
			),
		);

		$tiles = 'right' === $side ? $right : $left;

		$tiles = apply_filters( 'shanelle_homepage_promo_tiles', $tiles, $side );

		return is_array( $tiles ) ? array_values( $tiles ) : array();
	}

	/**
	 * Return category icon items for the homepage grid.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_category_icon_items(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
				'number'     => self::CATEGORY_ICON_COUNT,
				'meta_key'   => 'order',
				'orderby'    => 'meta_value_num',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$items = array();

		foreach ( $terms as $index => $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
			$image_html   = '';

			if ( $thumbnail_id > 0 && wp_attachment_is_image( $thumbnail_id ) ) {
				$image_html = wp_get_attachment_image(
					$thumbnail_id,
					'thumbnail',
					false,
					array(
						'class'    => 'homepage__category-icon-image',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'alt'      => '',
					)
				);
			}

			$items[] = array(
				'index'      => (int) $index,
				'id'         => $term->term_id,
				'name'       => $term->name,
				'url'        => (string) $link,
				'image_html' => is_string( $image_html ) ? $image_html : '',
				'initial'    => self::get_category_initial( $term->name ),
			);
		}

		return apply_filters( 'shanelle_homepage_category_icon_items', $items, $terms );
	}

	/**
	 * Return featured homepage collection columns.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_featured_collections(): array {
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
		$shop_url = is_string( $shop_url ) ? $shop_url : home_url( '/' );

		$defaults = array(
			array(
				'index'   => 0,
				'title'   => __( 'Casual chic', 'shanelle' ),
				'url'     => $shop_url,
				'orderby' => 'date',
			),
			array(
				'index'   => 1,
				'title'   => __( 'Tendencias top', 'shanelle' ),
				'url'     => $shop_url,
				'orderby' => 'popularity',
			),
			array(
				'index'   => 2,
				'title'   => __( 'Estilo floral', 'shanelle' ),
				'url'     => $shop_url,
				'orderby' => 'rating',
			),
		);

		$collections = array();

		foreach ( $defaults as $config ) {
			$products = self::query_collection_products(
				(string) ( $config['orderby'] ?? 'date' ),
				2,
				(int) ( $config['index'] ?? 0 )
			);

			if ( empty( $products ) ) {
				continue;
			}

			$collections[] = array_merge(
				$config,
				array(
					'products' => $products,
				)
			);
		}

		return apply_filters( 'shanelle_homepage_featured_collections', $collections );
	}

	/**
	 * Return For You section configuration.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_for_you_config(): array {
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

		return array(
			'title'           => self::get_theme_mod_string( self::MOD_FOR_YOU_TITLE, __( 'Para ti', 'shanelle' ) ),
			'limit'           => self::sanitize_for_you_limit( get_theme_mod( self::MOD_FOR_YOU_LIMIT, self::FOR_YOU_DEFAULT_LIMIT ) ),
			'orderby'         => self::sanitize_orderby( get_theme_mod( self::MOD_FOR_YOU_ORDERBY, 'popularity' ) ),
			'order'           => 'DESC',
			'load_more_label' => __( 'Ver más', 'shanelle' ),
			'shop_url'        => is_string( $shop_url ) ? $shop_url : home_url( '/' ),
			'anchor_id'       => self::ROOT_ID . '-for-you',
			'heading_id'      => self::ROOT_ID . '-for-you-heading',
			'grid_id'         => self::ROOT_ID . '-for-you-grid',
			'empty_message'   => __( 'Los productos aparecerán aquí cuando publiques tu catálogo.', 'shanelle' ),
		);
	}

	/**
	 * Build query vars for the For You product feed.
	 *
	 * @param array<string, mixed> $config Section configuration.
	 * @return array<string, mixed>
	 */
	public static function build_for_you_query_vars( array $config ): array {
		$query_vars = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => max( 10, (int) ( $config['limit'] ?? self::FOR_YOU_DEFAULT_LIMIT ) ),
			'paged'          => 1,
			'orderby'        => sanitize_key( (string) ( $config['orderby'] ?? 'popularity' ) ),
			'order'          => 'DESC',
		);

		return ProductGrid::sanitize_query_vars( $query_vars );
	}

	/**
	 * Query products for a featured collection column.
	 *
	 * @return array<int, \WC_Product>
	 */
	private static function query_collection_products( string $orderby, int $limit, int $seed ): array {
		$query_vars = ProductGrid::sanitize_query_vars(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, $limit ),
				'paged'          => 1,
				'orderby'        => $orderby,
				'order'          => 'DESC',
				'offset'         => $seed * $limit,
			)
		);

		$query = new \WP_Query( $query_vars );
		$items = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( $product instanceof \WC_Product ) {
					$items[] = $product;
				}
			}
		}

		wp_reset_postdata();

		return $items;
	}

	/**
	 * Extract a display initial from a category name.
	 */
	private static function get_category_initial( string $name ): string {
		$name = trim( wp_strip_all_tags( $name ) );

		if ( '' === $name ) {
			return '•';
		}

		if ( function_exists( 'mb_substr' ) ) {
			return mb_strtoupper( mb_substr( $name, 0, 1 ) );
		}

		return strtoupper( substr( $name, 0, 1 ) );
	}

	/**
	 * Sanitize For You product limit.
	 */
	public static function sanitize_for_you_limit( mixed $value ): int {
		$limit = absint( $value );

		if ( $limit < 10 ) {
			return 10;
		}

		if ( $limit > 100 ) {
			return 100;
		}

		return $limit;
	}

	/**
	 * Render the hero banner section.
	 */
	public static function render_hero(): void {
		shanelle_hero_banner();
	}

	/**
	 * Render the category navigation section.
	 */
	public static function render_category_navigation(): void {
		shanelle_category_navigation();
	}

	/**
	 * Render configured homepage product sections.
	 */
	public static function render_product_sections(): void {
		foreach ( self::$sections as $section ) {
			if ( ! is_array( $section ) || empty( $section['enabled'] ) ) {
				continue;
			}

			self::render_product_section( $section );
		}
	}

	/**
	 * Render a single homepage product section.
	 *
	 * @param array<string, mixed> $section Section configuration.
	 */
	public static function render_product_section( array $section ): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		$query_args = self::build_section_query_vars( $section );

		if ( empty( $query_args ) ) {
			return;
		}
		$title = (string) ( $section['title'] ?? '' );
		?>
		<section
			class="homepage__section homepage__section--products"
			id="<?php echo esc_attr( (string) $section['anchor_id'] ); ?>"
			data-shanelle-homepage-section
			data-section-key="<?php echo esc_attr( (string) $section['key'] ); ?>"
			<?php if ( '' !== $title ) : ?>
				aria-labelledby="<?php echo esc_attr( (string) $section['heading_id'] ); ?>"
			<?php else : ?>
				aria-label="<?php esc_attr_e( 'Productos', 'shanelle' ); ?>"
			<?php endif; ?>
		>
			<div class="container homepage__section-inner">
				<?php self::render_section_header( $section ); ?>

				<div class="homepage__grid">
					<?php
					ProductGrid::render(
						$query_args,
						array(
							'grid_id'           => (string) $section['grid_id'],
							'pagination_mode'   => 'none',
							'empty_message'     => (string) $section['empty_message'],
							'card_args'         => array(
								'context' => 'homepage',
							),
						)
					);
					?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Render a homepage section heading.
	 *
	 * @param array<string, mixed> $section Section configuration.
	 */
	public static function render_section_header( array $section ): void {
		$title    = (string) ( $section['title'] ?? '' );
		$subtitle = (string) ( $section['subtitle'] ?? '' );
		$link     = (string) ( $section['link_url'] ?? '' );
		$label    = (string) ( $section['link_label'] ?? '' );

		if ( '' === $title && '' === $subtitle && ( '' === $link || '' === $label ) ) {
			return;
		}
		?>
		<header class="homepage__section-header">
			<div class="homepage__section-copy">
				<?php if ( '' !== $title ) : ?>
					<h2 id="<?php echo esc_attr( (string) $section['heading_id'] ); ?>" class="homepage__section-title text-h2">
						<?php echo esc_html( $title ); ?>
					</h2>
				<?php endif; ?>

				<?php if ( '' !== $subtitle ) : ?>
					<p class="homepage__section-subtitle text-body text-muted">
						<?php echo esc_html( $subtitle ); ?>
					</p>
				<?php endif; ?>
			</div>

			<?php if ( '' !== $link && '' !== $label ) : ?>
				<a class="homepage__section-link text-label" href="<?php echo esc_url( $link ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endif; ?>
		</header>
		<?php
	}

	/**
	 * Return homepage root ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return sections JSON for client hydration.
	 */
	public static function get_sections_json(): string {
		return wp_json_encode( self::$sections ) ?: '[]';
	}

	/**
	 * Build homepage product section configuration.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function build_sections(): array {
		$sections = array();

		for ( $index = 1; $index <= self::SECTION_COUNT; $index++ ) {
			$sections[] = self::build_section_config( $index );
		}

		$sections = apply_filters( 'shanelle_homepage_sections', $sections );

		return is_array( $sections ) ? array_values( $sections ) : array();
	}

	/**
	 * Build query vars for a homepage product section.
	 *
	 * @param array<string, mixed> $section Section configuration.
	 * @return array<string, mixed>
	 */
	public static function build_section_query_vars( array $section ): array {
		if ( ! shanelle_is_woocommerce_active() ) {
			return array();
		}

		$query_vars = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, (int) ( $section['limit'] ?? 8 ) ),
			'paged'          => 1,
			'orderby'        => sanitize_key( (string) ( $section['orderby'] ?? 'date' ) ),
			'order'          => strtoupper( (string) ( $section['order'] ?? 'DESC' ) ),
		);

		$collection_id = (int) ( $section['collection_id'] ?? 0 );

		if ( $collection_id > 0 ) {
			$query_vars['tax_query'] = array(
				array(
					'taxonomy' => CatalogHelpers::TAXONOMY,
					'field'    => 'term_id',
					'terms'    => array( $collection_id ),
				),
			);
		}

		$query_vars = ProductGrid::sanitize_query_vars( $query_vars );

		return apply_filters( 'shanelle_homepage_section_query_vars', $query_vars, $section );
	}

	/**
	 * Build a section config array from Theme Customizer values.
	 */
	private static function build_section_config( int $index ): array {
		$key           = 'section-' . $index;
		$default_shop  = shanelle_is_woocommerce_active() ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
		$defaults      = self::get_section_defaults( $index );
		$enabled       = self::get_theme_mod_bool( self::mod_key( $index, 'enabled' ), (bool) $defaults['enabled'] );
		$title         = self::get_theme_mod_string( self::mod_key( $index, 'title' ), (string) $defaults['title'] );
		$subtitle      = self::get_theme_mod_string( self::mod_key( $index, 'subtitle' ), (string) $defaults['subtitle'] );
		$link_label    = self::get_theme_mod_string( self::mod_key( $index, 'link_label' ), (string) $defaults['link_label'] );
		$link_url      = esc_url_raw( (string) get_theme_mod( self::mod_key( $index, 'link_url' ), (string) $defaults['link_url'] ) );
		$orderby       = self::sanitize_orderby( get_theme_mod( self::mod_key( $index, 'orderby' ), $defaults['orderby'] ) );
		$order         = self::sanitize_order( get_theme_mod( self::mod_key( $index, 'order' ), $defaults['order'] ) );
		$limit         = self::sanitize_limit( get_theme_mod( self::mod_key( $index, 'limit' ), $defaults['limit'] ) );
		$collection_id = absint( get_theme_mod( self::mod_key( $index, 'collection_id' ), 0 ) );

		if ( '' === $link_url ) {
			$link_url = (string) $default_shop;
		}

		return array(
			'key'           => $key,
			'index'         => $index,
			'enabled'       => $enabled,
			'title'         => $title,
			'subtitle'      => $subtitle,
			'link_label'    => $link_label,
			'link_url'      => $link_url,
			'orderby'       => $orderby,
			'order'         => $order,
			'limit'         => $limit,
			'collection_id' => $collection_id,
			'anchor_id'     => self::ROOT_ID . '-' . $key,
			'heading_id'    => self::ROOT_ID . '-' . $key . '-heading',
			'grid_id'       => self::ROOT_ID . '-' . $key . '-grid',
			'empty_message' => (string) $defaults['empty_message'],
		);
	}

	/**
	 * Return default configuration for a homepage section.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_section_defaults( int $index ): array {
		$shop_url = shanelle_is_woocommerce_active() ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

		if ( 1 === $index ) {
			return array(
				'enabled'       => true,
				'title'         => __( 'Novedades', 'shanelle' ),
				'subtitle'      => __( 'Estilos frescos agregados cada día.', 'shanelle' ),
				'link_label'    => __( 'Ver todo', 'shanelle' ),
				'link_url'      => $shop_url,
				'orderby'       => 'date',
				'order'         => 'DESC',
				'limit'         => 8,
				'empty_message' => __( 'Las novedades aparecerán aquí cuando se publiquen productos.', 'shanelle' ),
			);
		}

		return array(
			'enabled'       => true,
			'title'         => __( 'Tendencias ahora', 'shanelle' ),
			'subtitle'      => __( 'Las piezas favoritas de esta semana.', 'shanelle' ),
			'link_label'    => __( 'Comprar tendencias', 'shanelle' ),
			'link_url'      => $shop_url,
			'orderby'       => 'popularity',
			'order'         => 'DESC',
			'limit'         => 8,
			'empty_message' => __( 'Los productos en tendencia aparecerán aquí cuando las clientas empiecen a comprar.', 'shanelle' ),
		);
	}

	/**
	 * Register customizer controls for a product section.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	private static function register_product_section_controls( \WP_Customize_Manager $wp_customize, int $index ): void {
		$defaults = self::get_section_defaults( $index );
		$prefix   = sprintf( __( 'Sección de productos %d', 'shanelle' ), $index );

		self::register_checkbox_control(
			$wp_customize,
			self::mod_key( $index, 'enabled' ),
			sprintf( '%s — %s', $prefix, __( 'Activada', 'shanelle' ) ),
			(bool) $defaults['enabled']
		);

		self::register_text_control(
			$wp_customize,
			self::mod_key( $index, 'title' ),
			sprintf( '%s — %s', $prefix, __( 'Título', 'shanelle' ) ),
			(string) $defaults['title']
		);

		self::register_textarea_control(
			$wp_customize,
			self::mod_key( $index, 'subtitle' ),
			sprintf( '%s — %s', $prefix, __( 'Subtítulo', 'shanelle' ) ),
			(string) $defaults['subtitle']
		);

		self::register_text_control(
			$wp_customize,
			self::mod_key( $index, 'link_label' ),
			sprintf( '%s — %s', $prefix, __( 'Etiqueta de Ver todo', 'shanelle' ) ),
			(string) $defaults['link_label']
		);

		self::register_url_control(
			$wp_customize,
			self::mod_key( $index, 'link_url' ),
			sprintf( '%s — %s', $prefix, __( 'URL de Ver todo', 'shanelle' ) ),
			(string) $defaults['link_url']
		);

		$wp_customize->add_setting(
			self::mod_key( $index, 'orderby' ),
			array(
				'default'           => (string) $defaults['orderby'],
				'sanitize_callback' => array( self::class, 'sanitize_orderby' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::mod_key( $index, 'orderby' ),
			array(
				'label'   => sprintf( '%s — %s', $prefix, __( 'Ordenar productos por', 'shanelle' ) ),
				'section' => 'shanelle_homepage_products',
				'type'    => 'select',
				'choices' => self::get_orderby_choices(),
			)
		);

		$wp_customize->add_setting(
			self::mod_key( $index, 'order' ),
			array(
				'default'           => (string) $defaults['order'],
				'sanitize_callback' => array( self::class, 'sanitize_order' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::mod_key( $index, 'order' ),
			array(
				'label'   => sprintf( '%s — %s', $prefix, __( 'Dirección del orden', 'shanelle' ) ),
				'section' => 'shanelle_homepage_products',
				'type'    => 'select',
				'choices' => array(
					'DESC' => __( 'Descendente', 'shanelle' ),
					'ASC'  => __( 'Ascendente', 'shanelle' ),
				),
			)
		);

		$wp_customize->add_setting(
			self::mod_key( $index, 'limit' ),
			array(
				'default'           => (int) $defaults['limit'],
				'sanitize_callback' => array( self::class, 'sanitize_limit' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::mod_key( $index, 'limit' ),
			array(
				'label'       => sprintf( '%s — %s', $prefix, __( 'Límite de productos', 'shanelle' ) ),
				'section'     => 'shanelle_homepage_products',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 4,
					'max'  => 24,
					'step' => 1,
				),
			)
		);

		if ( taxonomy_exists( CatalogHelpers::TAXONOMY ) ) {
			$wp_customize->add_setting(
				self::mod_key( $index, 'collection_id' ),
				array(
					'default'           => 0,
					'sanitize_callback' => array( self::class, 'sanitize_collection_id' ),
					'transport'         => 'refresh',
				)
			);

			$wp_customize->add_control(
				self::mod_key( $index, 'collection_id' ),
				array(
					'label'   => sprintf( '%s — %s', $prefix, __( 'Filtro de colección', 'shanelle' ) ),
					'section' => 'shanelle_homepage_products',
					'type'    => 'select',
					'choices' => self::get_collection_choices(),
				)
			);
		}
	}

	/**
	 * Return orderby choices for customizer controls.
	 *
	 * @return array<string, string>
	 */
	private static function get_orderby_choices(): array {
		return array(
			'date'       => __( 'Más recientes', 'shanelle' ),
			'popularity' => __( 'Popularidad', 'shanelle' ),
			'rating'     => __( 'Calificación promedio', 'shanelle' ),
			'price'      => __( 'Precio: de menor a mayor', 'shanelle' ),
			'price-desc' => __( 'Precio: de mayor a menor', 'shanelle' ),
			'menu_order' => __( 'Orden manual', 'shanelle' ),
			'rand'       => __( 'Aleatorio', 'shanelle' ),
		);
	}

	/**
	 * Return product collection choices for customizer controls.
	 *
	 * @return array<int, string>
	 */
	private static function get_collection_choices(): array {
		$choices = array(
			0 => __( 'Todos los productos', 'shanelle' ),
		);

		$terms = get_terms(
			array(
				'taxonomy'   => CatalogHelpers::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $choices;
		}

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$choices[ (int) $term->term_id ] = $term->name;
		}

		return $choices;
	}

	/**
	 * Sanitize orderby customizer values.
	 */
	public static function sanitize_orderby( mixed $value ): string {
		$orderby = sanitize_key( (string) $value );

		return array_key_exists( $orderby, self::get_orderby_choices() ) ? $orderby : 'date';
	}

	/**
	 * Sanitize order customizer values.
	 */
	public static function sanitize_order( mixed $value ): string {
		$order = strtoupper( sanitize_text_field( (string) $value ) );

		return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
	}

	/**
	 * Sanitize product limit customizer values.
	 */
	public static function sanitize_limit( mixed $value ): int {
		$limit = absint( $value );

		if ( $limit < 4 ) {
			return 4;
		}

		if ( $limit > 24 ) {
			return 24;
		}

		return $limit;
	}

	/**
	 * Sanitize collection term ID customizer values.
	 */
	public static function sanitize_collection_id( mixed $value ): int {
		$term_id = absint( $value );

		if ( $term_id <= 0 ) {
			return 0;
		}

		$term = get_term( $term_id, CatalogHelpers::TAXONOMY );

		return ( $term instanceof \WP_Term && ! is_wp_error( $term ) ) ? $term_id : 0;
	}

	/**
	 * Sanitize checkbox customizer values.
	 */
	public static function sanitize_checkbox( mixed $value ): bool {
		return (bool) $value;
	}

	/**
	 * Build a theme mod key for a section field.
	 */
	private static function mod_key( int $index, string $field ): string {
		return 'shanelle_homepage_' . $index . '_' . $field;
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
		string $default,
		string $section = 'shanelle_homepage_products'
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
				'section' => $section,
				'type'    => 'text',
			)
		);
	}

	/**
	 * Register a textarea customizer control.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
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
				'sanitize_callback' => 'sanitize_textarea_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => 'shanelle_homepage_products',
				'type'    => 'textarea',
			)
		);
	}

	/**
	 * Register a URL customizer control.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	private static function register_url_control(
		\WP_Customize_Manager $wp_customize,
		string $mod_name,
		string $label,
		string $default
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => $default,
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => 'shanelle_homepage_products',
				'type'    => 'url',
			)
		);
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
				'section' => 'shanelle_homepage_products',
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
