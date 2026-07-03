<?php
/**
 * Homepage category navigation component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce top-level category navigation for the homepage.
 */
final class CategoryNavigation {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/category-navigation';

	private const COMPONENT_URI = SHANELLE_URI . '/components/category-navigation';

	private const ROOT_ID = 'shanelle-category-navigation';

	private const IMAGE_SIZE = 'shanelle-category-nav';

	private const MOD_TITLE = 'shanelle_category_navigation_title';

	private const MOD_SUBTITLE = 'shanelle_category_navigation_subtitle';

	private const MOD_MAX = 'shanelle_category_navigation_max';

	private const MOD_SHOW_IMAGE = 'shanelle_category_navigation_show_image';

	private const MOD_SHOW_COUNT = 'shanelle_category_navigation_show_count';

	private const MOD_ROUND_STYLE = 'shanelle_category_navigation_round_style';

	private const MOD_LAYOUT = 'shanelle_category_navigation_layout';

	/**
	 * Active render arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Normalized category card data for the active render cycle.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static array $categories = array();

	/**
	 * Active Theme Customizer settings.
	 *
	 * @var array<string, mixed>
	 */
	private static array $settings = array();

	/**
	 * Boot category navigation hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'after_setup_theme', array( self::class, 'register_image_sizes' ), 20 );
		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Register category card image size.
	 */
	public static function register_image_sizes(): void {
		add_image_size( self::IMAGE_SIZE, 320, 320, true );
	}

	/**
	 * Register Theme Customizer settings.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		if ( ! $wp_customize->get_panel( 'shanelle_homepage' ) ) {
			$wp_customize->add_panel(
				'shanelle_homepage',
				array(
					'title'       => __( 'Shanelle Homepage', 'shanelle' ),
					'description' => __( 'Configure homepage sections.', 'shanelle' ),
					'priority'    => 160,
				)
			);
		}

		$wp_customize->add_section(
			'shanelle_category_navigation',
			array(
				'title'       => __( 'Category Navigation', 'shanelle' ),
				'description' => __( 'Second homepage section. Displays top-level WooCommerce categories.', 'shanelle' ),
				'panel'       => 'shanelle_homepage',
				'priority'    => 20,
			)
		);

		self::register_text_control( $wp_customize, self::MOD_TITLE, __( 'Section title', 'shanelle' ) );
		self::register_textarea_control( $wp_customize, self::MOD_SUBTITLE, __( 'Section subtitle', 'shanelle' ) );

		$wp_customize->add_setting(
			self::MOD_MAX,
			array(
				'default'           => 8,
				'sanitize_callback' => array( self::class, 'sanitize_max_categories' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_MAX,
			array(
				'label'       => __( 'Maximum categories', 'shanelle' ),
				'description' => __( 'Limit the number of top-level categories shown.', 'shanelle' ),
				'section'     => 'shanelle_category_navigation',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 24,
					'step' => 1,
				),
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_IMAGE,
			__( 'Show category image', 'shanelle' ),
			true
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_COUNT,
			__( 'Show product count', 'shanelle' ),
			true
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_ROUND_STYLE,
			__( 'Round icon style', 'shanelle' ),
			true
		);

		$wp_customize->add_setting(
			self::MOD_LAYOUT,
			array(
				'default'           => 'responsive',
				'sanitize_callback' => array( self::class, 'sanitize_layout' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_LAYOUT,
			array(
				'label'   => __( 'Layout', 'shanelle' ),
				'section' => 'shanelle_category_navigation',
				'type'    => 'select',
				'choices' => array(
					'responsive' => __( 'Grid on desktop, horizontal scroll on mobile', 'shanelle' ),
					'grid'       => __( 'Grid at all screen sizes', 'shanelle' ),
					'scroll'     => __( 'Horizontal scroll at all screen sizes', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Enqueue assets on the front page.
	 */
	public static function enqueue_assets(): void {
		if ( ! is_front_page() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue component assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-category-navigation',
			self::COMPONENT_URI . '/category-navigation.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-category-navigation',
			self::COMPONENT_URI . '/category-navigation.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-category-navigation', 'type', 'module' );

		wp_localize_script(
			'shanelle-category-navigation',
			'shanelleCategoryNavigation',
			array(
				'layout' => (string) self::get_settings()['layout'],
				'i18n'   => array(
					'navLabel'      => __( 'Browse product categories', 'shanelle' ),
					'productCount'  => __( '%d products', 'shanelle' ),
					'scrollPrev'    => __( 'Scroll categories backward', 'shanelle' ),
					'scrollNext'    => __( 'Scroll categories forward', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the category navigation section.
	 *
	 * @param array<string, mixed> $args Optional render arguments.
	 */
	public static function render( array $args = array() ): void {
		self::$args       = self::parse_args( $args );
		self::$settings   = self::get_settings();
		self::$categories = self::get_categories();

		if ( empty( self::$categories ) ) {
			self::reset_context();
			return;
		}

		if ( ! wp_style_is( 'shanelle-category-navigation', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/category-navigation.php';

		self::reset_context();
	}

	/**
	 * Render section heading.
	 */
	public static function render_header(): void {
		$title    = (string) ( self::$settings['title'] ?? '' );
		$subtitle = (string) ( self::$settings['subtitle'] ?? '' );

		if ( '' === $title && '' === $subtitle ) {
			return;
		}
		?>
		<header class="category-navigation__header">
			<?php if ( '' !== $title ) : ?>
				<h2 id="<?php echo esc_attr( self::get_heading_id() ); ?>" class="category-navigation__title text-h2">
					<?php echo esc_html( $title ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( '' !== $subtitle ) : ?>
				<p class="category-navigation__subtitle text-body text-muted">
					<?php echo esc_html( $subtitle ); ?>
				</p>
			<?php endif; ?>
		</header>
		<?php
	}

	/**
	 * Render category cards.
	 */
	public static function render_categories(): void {
		foreach ( self::$categories as $category ) {
			if ( ! is_array( $category ) ) {
				continue;
			}

			self::render_category_card( $category );
		}
	}

	/**
	 * Render a single category card.
	 *
	 * @param array<string, mixed> $category Normalized category data.
	 */
	public static function render_category_card( array $category ): void {
		$name      = (string) ( $category['name'] ?? '' );
		$url       = (string) ( $category['url'] ?? '' );
		$count     = (int) ( $category['count'] ?? 0 );
		$show_image = ! empty( self::$settings['show_image'] );
		$show_count = ! empty( self::$settings['show_count'] );
		$has_image  = ! empty( $category['has_image'] );
		?>
		<li class="category-navigation__item" data-shanelle-category-item data-category-id="<?php echo esc_attr( (string) ( $category['id'] ?? 0 ) ); ?>">
			<a class="category-navigation__card" href="<?php echo esc_url( $url ); ?>">
				<?php if ( $show_image ) : ?>
					<span class="category-navigation__media<?php echo $has_image ? '' : ' category-navigation__media--placeholder'; ?>">
						<?php if ( $has_image ) : ?>
							<?php echo $category['image_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<span class="category-navigation__placeholder" aria-hidden="true">
								<?php self::render_placeholder_icon( $name ); ?>
							</span>
						<?php endif; ?>
					</span>
				<?php endif; ?>

				<span class="category-navigation__copy">
					<span class="category-navigation__name text-label"><?php echo esc_html( $name ); ?></span>
					<?php if ( $show_count ) : ?>
						<span class="category-navigation__count text-caption text-muted">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: number of products */
									_n( '%d product', '%d products', $count, 'shanelle' ),
									$count
								)
							);
							?>
						</span>
					<?php endif; ?>
				</span>
			</a>
		</li>
		<?php
	}

	/**
	 * Return root element ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return heading element ID.
	 */
	public static function get_heading_id(): string {
		return self::ROOT_ID . '-heading';
	}

	/**
	 * Return section CSS classes.
	 *
	 * @return array<int, string>
	 */
	public static function get_section_classes(): array {
		$classes = array( 'category-navigation' );

		if ( ! empty( self::$settings['round_style'] ) ) {
			$classes[] = 'category-navigation--round';
		}

		$layout = (string) ( self::$settings['layout'] ?? 'responsive' );

		if ( in_array( $layout, array( 'responsive', 'grid', 'scroll' ), true ) ) {
			$classes[] = 'category-navigation--layout-' . $layout;
		}

		return $classes;
	}

	/**
	 * Return categories JSON for client hydration.
	 */
	public static function get_categories_json(): string {
		return wp_json_encode( self::$categories ) ?: '[]';
	}

	/**
	 * Return settings JSON for client hydration.
	 */
	public static function get_settings_json(): string {
		return wp_json_encode( self::$settings ) ?: '{}';
	}

	/**
	 * Return aria-labelledby target.
	 */
	public static function get_aria_labelledby(): string {
		$title = (string) ( self::$settings['title'] ?? '' );

		return '' !== $title ? self::get_heading_id() : self::get_root_id() . '-label';
	}

	/**
	 * Output branded placeholder icon markup.
	 */
	public static function render_placeholder_icon( string $name ): void {
		$initial = self::get_category_initial( $name );
		?>
		<span class="category-navigation__placeholder-mark"><?php echo esc_html( $initial ); ?></span>
		<?php
	}

	/**
	 * Sanitize maximum categories customizer value.
	 */
	public static function sanitize_max_categories( mixed $value ): int {
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
	 * Sanitize layout customizer value.
	 */
	public static function sanitize_layout( mixed $value ): string {
		$layout = sanitize_key( (string) $value );

		return in_array( $layout, array( 'responsive', 'grid', 'scroll' ), true ) ? $layout : 'responsive';
	}

	/**
	 * Sanitize checkbox customizer values.
	 */
	public static function sanitize_checkbox( mixed $value ): bool {
		return (bool) $value;
	}

	/**
	 * Read Theme Customizer settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		return apply_filters(
			'shanelle_category_navigation_settings',
			array(
				'title'       => self::get_theme_mod_string( self::MOD_TITLE ),
				'subtitle'    => self::get_theme_mod_string( self::MOD_SUBTITLE ),
				'max'         => self::sanitize_max_categories( get_theme_mod( self::MOD_MAX, 8 ) ),
				'show_image'  => self::get_theme_mod_bool( self::MOD_SHOW_IMAGE, true ),
				'show_count'  => self::get_theme_mod_bool( self::MOD_SHOW_COUNT, true ),
				'round_style' => self::get_theme_mod_bool( self::MOD_ROUND_STYLE, true ),
				'layout'      => self::sanitize_layout( get_theme_mod( self::MOD_LAYOUT, 'responsive' ) ),
			)
		);
	}

	/**
	 * Build normalized category cards from WooCommerce terms.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_categories(): array {
		$settings = self::$settings ?: self::get_settings();
		$terms    = get_terms(
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

			$categories[] = self::build_category_data( $term, $settings );
		}

		$categories = apply_filters( 'shanelle_category_navigation_categories', $categories, $settings, $terms );

		return is_array( $categories ) ? array_values( array_filter( $categories ) ) : array();
	}

	/**
	 * Build normalized data for a category term.
	 *
	 * @param array<string, mixed> $settings Active settings.
	 * @return array<string, mixed>
	 */
	private static function build_category_data( \WP_Term $term, array $settings ): array {
		$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		$link         = get_term_link( $term );
		$url          = is_wp_error( $link ) ? '' : (string) $link;
		$image_html   = '';
		$has_image    = false;

		if ( ! empty( $settings['show_image'] ) && $thumbnail_id > 0 && wp_attachment_is_image( $thumbnail_id ) ) {
			$image_html = wp_get_attachment_image(
				$thumbnail_id,
				self::IMAGE_SIZE,
				false,
				array(
					'class'    => 'category-navigation__image',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'alt'      => '',
				)
			);
			$has_image  = is_string( $image_html ) && '' !== $image_html;
		}

		return array(
			'id'         => $term->term_id,
			'slug'       => $term->slug,
			'name'       => $term->name,
			'url'        => $url,
			'count'      => (int) $term->count,
			'has_image'  => $has_image,
			'image_html' => $image_html,
			'menu_order' => (int) get_term_meta( $term->term_id, 'order', true ),
		);
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
	 * Register a text customizer control.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	private static function register_text_control(
		\WP_Customize_Manager $wp_customize,
		string $mod_name,
		string $label
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => 'shanelle_category_navigation',
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
		string $label
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => 'shanelle_category_navigation',
				'type'    => 'textarea',
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
				'section' => 'shanelle_category_navigation',
				'type'    => 'checkbox',
			)
		);
	}

	/**
	 * Read a sanitized string theme mod.
	 */
	private static function get_theme_mod_string( string $key ): string {
		$value = get_theme_mod( $key, '' );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Read a sanitized boolean theme mod.
	 */
	private static function get_theme_mod_bool( string $key, bool $default ): bool {
		$value = get_theme_mod( $key, $default );

		return (bool) $value;
	}

	/**
	 * Parse render arguments.
	 *
	 * @param array<string, mixed> $args Input args.
	 * @return array<string, mixed>
	 */
	private static function parse_args( array $args ): array {
		return wp_parse_args( $args, array() );
	}

	/**
	 * Reset active render context.
	 */
	private static function reset_context(): void {
		self::$args       = array();
		self::$categories = array();
		self::$settings   = array();
	}
}
