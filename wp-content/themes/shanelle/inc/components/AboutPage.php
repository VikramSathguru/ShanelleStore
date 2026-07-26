<?php
/**
 * About page composer bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Composes the storefront About page.
 *
 * Content is Theme Customizer driven so merchants edit copy, media, and links
 * without touching template code. Assign the About template to a WordPress page.
 *
 * Sections: Hero, Our Story, Mission & Values, Why Choose, Call to Action.
 */
final class AboutPage {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/about-page';

	private const COMPONENT_URI = SHANELLE_URI . '/components/about-page';

	private const ROOT_ID = 'shanelle-about-page';

	private const PAGE_TEMPLATE = 'page-templates/about.php';

	private const SECTION = 'shanelle_about_page';

	private const DESKTOP_SIZE = 'large';

	private const MOBILE_SIZE = 'medium_large';

	private const ICON_SIZE = 'thumbnail';

	private const VALUE_COUNT = 3;

	private const FEATURE_COUNT = 4;

	private const MOD_TITLE = 'shanelle_about_page_title';

	private const MOD_SHOW_HERO = 'shanelle_about_page_show_hero';

	private const MOD_HERO_DESKTOP_IMAGE = 'shanelle_about_page_hero_desktop_image';

	private const MOD_HERO_MOBILE_IMAGE = 'shanelle_about_page_hero_mobile_image';

	private const MOD_HERO_EYEBROW = 'shanelle_about_page_hero_eyebrow';

	private const MOD_HERO_HEADLINE = 'shanelle_about_page_hero_headline';

	private const MOD_HERO_SUBHEADLINE = 'shanelle_about_page_hero_subheadline';

	private const MOD_HERO_TAGLINE = 'shanelle_about_page_hero_tagline';

	private const MOD_HERO_CTA_TEXT = 'shanelle_about_page_hero_cta_text';

	private const MOD_HERO_CTA_URL = 'shanelle_about_page_hero_cta_url';

	private const MOD_SHOW_STORY = 'shanelle_about_page_show_story';

	private const MOD_STORY_EYEBROW = 'shanelle_about_page_story_eyebrow';

	private const MOD_STORY_HEADING = 'shanelle_about_page_story_heading';

	private const MOD_STORY_BODY = 'shanelle_about_page_story_body';

	private const MOD_STORY_DESKTOP_IMAGE = 'shanelle_about_page_story_desktop_image';

	private const MOD_STORY_MOBILE_IMAGE = 'shanelle_about_page_story_mobile_image';

	private const MOD_STORY_MEDIA_SIDE = 'shanelle_about_page_story_media_side';

	private const MOD_SHOW_MISSION = 'shanelle_about_page_show_mission';

	private const MOD_MISSION_EYEBROW = 'shanelle_about_page_mission_eyebrow';

	private const MOD_MISSION_HEADING = 'shanelle_about_page_mission_heading';

	private const MOD_MISSION_BODY = 'shanelle_about_page_mission_body';

	private const MOD_MISSION_CTA_TEXT = 'shanelle_about_page_mission_cta_text';

	private const MOD_MISSION_CTA_URL = 'shanelle_about_page_mission_cta_url';

	private const MOD_VALUE_PREFIX = 'shanelle_about_page_value_';

	private const MOD_SHOW_FEATURES = 'shanelle_about_page_show_features';

	private const MOD_FEATURES_EYEBROW = 'shanelle_about_page_features_eyebrow';

	private const MOD_FEATURES_HEADING = 'shanelle_about_page_features_heading';

	private const MOD_FEATURES_INTRO = 'shanelle_about_page_features_intro';

	private const MOD_FEATURE_PREFIX = 'shanelle_about_page_feature_';

	private const MOD_SHOW_CTA = 'shanelle_about_page_show_cta';

	private const MOD_CTA_EYEBROW = 'shanelle_about_page_cta_eyebrow';

	private const MOD_CTA_HEADING = 'shanelle_about_page_cta_heading';

	private const MOD_CTA_BODY = 'shanelle_about_page_cta_body';

	private const MOD_CTA_TEXT = 'shanelle_about_page_cta_text';

	private const MOD_CTA_URL = 'shanelle_about_page_cta_url';

	/**
	 * Active page state for the render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot About page hooks.
	 */
	public static function boot(): void {
		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp', array( self::class, 'configure_page_hooks' ), 20 );
	}

	/**
	 * Adjust hooks on the About page.
	 */
	public static function configure_page_hooks(): void {
		if ( ! self::is_about_page() ) {
			return;
		}

		add_filter( 'the_title', array( self::class, 'hide_page_title' ), 10, 2 );
	}

	/**
	 * Hide the default WordPress page title in the main query.
	 *
	 * @param string          $title Post title.
	 * @param int|string|null $id    Post ID.
	 */
	public static function hide_page_title( string $title, $id = null ): string {
		if ( ! self::is_about_page() || ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}

		return '';
	}

	/**
	 * Register Theme Customizer settings for the About page.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			self::SECTION,
			array(
				'title'       => __( 'Página Sobre nosotros', 'shanelle' ),
				'description' => __( 'Configura la página Sobre nosotros. Asigna la plantilla "Sobre nosotros" a una página de WordPress. Deja los campos vacíos para ocultar contenido opcional.', 'shanelle' ),
				'priority'    => 176,
			)
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_TITLE,
			__( 'Título de la página (H1 de respaldo)', 'shanelle' ),
			__( 'Sobre nosotros', 'shanelle' )
		);

		// Hero.
		self::register_checkbox_control( $wp_customize, self::MOD_SHOW_HERO, __( 'Mostrar sección hero', 'shanelle' ), true );

		self::register_image_control(
			$wp_customize,
			self::MOD_HERO_DESKTOP_IMAGE,
			__( 'Hero: imagen (escritorio)', 'shanelle' ),
			__( 'Tamaño recomendado: 1600 x 900 px.', 'shanelle' )
		);

		self::register_image_control(
			$wp_customize,
			self::MOD_HERO_MOBILE_IMAGE,
			__( 'Hero: imagen (móvil)', 'shanelle' ),
			__( 'Tamaño recomendado: 768 x 960 px. Si está vacío, se usa la imagen de escritorio.', 'shanelle' )
		);

		self::register_text_control( $wp_customize, self::MOD_HERO_EYEBROW, __( 'Hero: antetítulo', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_HERO_HEADLINE, __( 'Hero: titular (H1)', 'shanelle' ) );
		self::register_textarea_control( $wp_customize, self::MOD_HERO_SUBHEADLINE, __( 'Hero: subtítulo', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_HERO_TAGLINE, __( 'Hero: eslogan', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_HERO_CTA_TEXT, __( 'Hero: texto del botón', 'shanelle' ) );
		self::register_url_control( $wp_customize, self::MOD_HERO_CTA_URL, __( 'Hero: URL del botón (vacío = tienda)', 'shanelle' ) );

		// Our Story.
		self::register_checkbox_control( $wp_customize, self::MOD_SHOW_STORY, __( 'Mostrar sección Nuestra historia', 'shanelle' ), true );
		self::register_text_control( $wp_customize, self::MOD_STORY_EYEBROW, __( 'Historia: antetítulo', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_STORY_HEADING, __( 'Historia: encabezado', 'shanelle' ) );
		self::register_textarea_control( $wp_customize, self::MOD_STORY_BODY, __( 'Historia: texto', 'shanelle' ) );

		self::register_image_control(
			$wp_customize,
			self::MOD_STORY_DESKTOP_IMAGE,
			__( 'Historia: imagen (escritorio)', 'shanelle' ),
			__( 'Tamaño recomendado: 1200 x 1500 px.', 'shanelle' )
		);

		self::register_image_control(
			$wp_customize,
			self::MOD_STORY_MOBILE_IMAGE,
			__( 'Historia: imagen (móvil)', 'shanelle' ),
			__( 'Si está vacío, se usa la imagen de escritorio.', 'shanelle' )
		);

		self::register_select_control(
			$wp_customize,
			self::MOD_STORY_MEDIA_SIDE,
			__( 'Historia: posición de la imagen (escritorio)', 'shanelle' ),
			array(
				'left'  => __( 'Izquierda', 'shanelle' ),
				'right' => __( 'Derecha', 'shanelle' ),
			),
			'left'
		);

		// Mission & Values.
		self::register_checkbox_control( $wp_customize, self::MOD_SHOW_MISSION, __( 'Mostrar sección Misión y valores', 'shanelle' ), true );
		self::register_text_control( $wp_customize, self::MOD_MISSION_EYEBROW, __( 'Misión: antetítulo', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_MISSION_HEADING, __( 'Misión: encabezado', 'shanelle' ) );
		self::register_textarea_control( $wp_customize, self::MOD_MISSION_BODY, __( 'Misión: texto', 'shanelle' ) );

		for ( $index = 1; $index <= self::VALUE_COUNT; $index++ ) {
			/* translators: %d: value slot number. */
			$prefix = sprintf( __( 'Valor %d', 'shanelle' ), $index );

			self::register_image_control(
				$wp_customize,
				self::value_mod( $index, 'image' ),
				$prefix . ': ' . __( 'ícono/imagen', 'shanelle' ),
				__( 'Imagen cuadrada recomendada (ej. 160 x 160 px).', 'shanelle' )
			);

			self::register_text_control( $wp_customize, self::value_mod( $index, 'title' ), $prefix . ': ' . __( 'título', 'shanelle' ) );
			self::register_textarea_control( $wp_customize, self::value_mod( $index, 'text' ), $prefix . ': ' . __( 'descripción', 'shanelle' ) );
		}

		// Legacy mission CTA fields (fall back for closing CTA when new CTA fields are empty).
		self::register_text_control( $wp_customize, self::MOD_MISSION_CTA_TEXT, __( 'Misión: texto del botón (legado)', 'shanelle' ) );
		self::register_url_control( $wp_customize, self::MOD_MISSION_CTA_URL, __( 'Misión: URL del botón legado (vacío = tienda)', 'shanelle' ) );

		// Why Choose.
		self::register_checkbox_control( $wp_customize, self::MOD_SHOW_FEATURES, __( 'Mostrar sección Por qué elegirnos', 'shanelle' ), true );
		self::register_text_control( $wp_customize, self::MOD_FEATURES_EYEBROW, __( 'Por qué: antetítulo', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_FEATURES_HEADING, __( 'Por qué: encabezado', 'shanelle' ) );
		self::register_textarea_control( $wp_customize, self::MOD_FEATURES_INTRO, __( 'Por qué: texto introductorio', 'shanelle' ) );

		for ( $index = 1; $index <= self::FEATURE_COUNT; $index++ ) {
			/* translators: %d: feature slot number. */
			$prefix = sprintf( __( 'Motivo %d', 'shanelle' ), $index );

			self::register_image_control(
				$wp_customize,
				self::feature_mod( $index, 'image' ),
				$prefix . ': ' . __( 'ícono/imagen', 'shanelle' ),
				__( 'Imagen cuadrada recomendada (ej. 160 x 160 px).', 'shanelle' )
			);

			self::register_text_control( $wp_customize, self::feature_mod( $index, 'title' ), $prefix . ': ' . __( 'título', 'shanelle' ) );
			self::register_textarea_control( $wp_customize, self::feature_mod( $index, 'text' ), $prefix . ': ' . __( 'descripción', 'shanelle' ) );
		}

		// Call to Action.
		self::register_checkbox_control( $wp_customize, self::MOD_SHOW_CTA, __( 'Mostrar sección de llamado a la acción', 'shanelle' ), true );
		self::register_text_control( $wp_customize, self::MOD_CTA_EYEBROW, __( 'CTA: antetítulo', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_CTA_HEADING, __( 'CTA: encabezado', 'shanelle' ) );
		self::register_textarea_control( $wp_customize, self::MOD_CTA_BODY, __( 'CTA: texto', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_CTA_TEXT, __( 'CTA: texto del botón', 'shanelle' ) );
		self::register_url_control( $wp_customize, self::MOD_CTA_URL, __( 'CTA: URL del botón (vacío = tienda)', 'shanelle' ) );
	}

	/**
	 * Enqueue About page assets.
	 */
	public static function enqueue_assets(): void {
		if ( ! self::is_about_page() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue About page assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-about-page',
			self::COMPONENT_URI . '/about-page.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-about-page',
			self::COMPONENT_URI . '/about-page.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-about-page', 'type', 'module' );
	}

	/**
	 * Render the About page composition.
	 */
	public static function render(): void {
		self::$state = self::build_page_state();

		if ( ! wp_style_is( 'shanelle-about-page', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/about-page.php';

		self::$state = array();
	}

	/**
	 * Render the hero section.
	 */
	public static function render_hero(): void {
		$hero = self::get_section( 'hero' );

		if ( empty( $hero['visible'] ) ) {
			return;
		}

		require self::COMPONENT_DIR . '/partials/hero.php';
	}

	/**
	 * Render the Our Story section.
	 */
	public static function render_story(): void {
		$story = self::get_section( 'story' );

		if ( empty( $story['visible'] ) ) {
			return;
		}

		require self::COMPONENT_DIR . '/partials/story.php';
	}

	/**
	 * Render the Mission & Values section.
	 */
	public static function render_mission_values(): void {
		$mission = self::get_section( 'mission' );

		if ( empty( $mission['visible'] ) ) {
			return;
		}

		require self::COMPONENT_DIR . '/partials/mission-values.php';
	}

	/**
	 * Backward-compatible alias for mission rendering.
	 */
	public static function render_mission(): void {
		self::render_mission_values();
	}

	/**
	 * Render the Why Choose section.
	 */
	public static function render_why_choose(): void {
		$why = self::get_section( 'why_choose' );

		if ( empty( $why['visible'] ) || empty( $why['items'] ) ) {
			return;
		}

		require self::COMPONENT_DIR . '/partials/why-choose.php';
	}

	/**
	 * Backward-compatible alias for features rendering.
	 */
	public static function render_features(): void {
		self::render_why_choose();
	}

	/**
	 * Render the closing Call to Action section.
	 */
	public static function render_cta(): void {
		$cta = self::get_section( 'cta' );

		if ( empty( $cta['visible'] ) ) {
			return;
		}

		require self::COMPONENT_DIR . '/partials/cta.php';
	}

	/**
	 * Return a normalized section from the active render state.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_section( string $key ): array {
		return is_array( self::$state[ $key ] ?? null ) ? self::$state[ $key ] : array();
	}

	/**
	 * Render a responsive <picture> from desktop/mobile image data.
	 *
	 * @param array<string, mixed> $desktop Desktop image data.
	 * @param array<string, mixed> $mobile  Mobile image data.
	 * @param bool                 $eager   Whether to eager load (LCP).
	 * @param string               $class   Extra image class suffix.
	 */
	public static function render_picture( array $desktop, array $mobile, bool $eager = false, string $class = '' ): void {
		$desktop_src = (string) ( $desktop['src'] ?? '' );
		$mobile_src  = (string) ( $mobile['src'] ?? '' );
		$image_class = 'about-page__image' . ( '' !== $class ? ' ' . $class : '' );

		if ( '' === $desktop_src && '' === $mobile_src ) {
			return;
		}
		?>
		<picture class="about-page__picture">
			<?php if ( '' !== $desktop_src && ! empty( $desktop['srcset'] ) ) : ?>
				<source
					media="(min-width: 48rem)"
					srcset="<?php echo esc_attr( (string) $desktop['srcset'] ); ?>"
					sizes="<?php echo esc_attr( (string) ( $desktop['sizes'] ?? '100vw' ) ); ?>"
				>
			<?php endif; ?>

			<img
				class="<?php echo esc_attr( $image_class ); ?>"
				src="<?php echo esc_url( '' !== $mobile_src ? $mobile_src : $desktop_src ); ?>"
				<?php if ( ! empty( $mobile['srcset'] ) || ! empty( $desktop['srcset'] ) ) : ?>
					srcset="<?php echo esc_attr( (string) ( $mobile['srcset'] ?: $desktop['srcset'] ) ); ?>"
				<?php endif; ?>
				sizes="<?php echo esc_attr( (string) ( $mobile['sizes'] ?? $desktop['sizes'] ?? '100vw' ) ); ?>"
				alt="<?php echo esc_attr( (string) ( $mobile['alt'] ?: $desktop['alt'] ?? '' ) ); ?>"
				<?php if ( ! empty( $desktop['width'] ) ) : ?>
					width="<?php echo esc_attr( (string) $desktop['width'] ); ?>"
					height="<?php echo esc_attr( (string) $desktop['height'] ); ?>"
				<?php endif; ?>
				loading="<?php echo esc_attr( $eager ? 'eager' : 'lazy' ); ?>"
				decoding="async"
				<?php echo $eager ? ' fetchpriority="high"' : ''; ?>
			>
		</picture>
		<?php
	}

	/**
	 * Render a simple responsive attachment image.
	 */
	public static function render_image( int $attachment_id, string $size ): void {
		if ( $attachment_id <= 0 ) {
			return;
		}

		echo wp_get_attachment_image(
			$attachment_id,
			$size,
			false,
			array(
				'class'    => 'about-page__img',
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
	}

	/**
	 * Determine whether the current request is the About page.
	 */
	public static function is_about_page(): bool {
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
	 * Return the configured page title.
	 */
	public static function get_page_title(): string {
		$settings = is_array( self::$state['settings'] ?? null ) ? self::$state['settings'] : self::get_settings();

		return (string) ( $settings['title'] ?? '' );
	}

	/**
	 * Build normalized About page state.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_page_state(): array {
		$settings = self::get_settings();

		$hero_desktop = self::get_image_data( (int) $settings['hero_desktop_image_id'], self::DESKTOP_SIZE );
		$hero_mobile  = self::get_image_data( (int) $settings['hero_mobile_image_id'], self::MOBILE_SIZE );

		if ( $hero_mobile['id'] <= 0 && $hero_desktop['id'] > 0 ) {
			$hero_mobile = self::get_image_data( (int) $settings['hero_desktop_image_id'], self::MOBILE_SIZE );
		}

		$hero_headline = (string) $settings['hero_headline'];
		$page_title    = (string) $settings['title'];
		$h1            = '' !== $hero_headline ? $hero_headline : $page_title;

		$hero = array(
			'visible'     => ! empty( $settings['show_hero'] ),
			'has_media'   => $hero_desktop['id'] > 0 || $hero_mobile['id'] > 0,
			'desktop'     => $hero_desktop,
			'mobile'      => $hero_mobile,
			'eyebrow'     => (string) $settings['hero_eyebrow'],
			'headline'    => $h1,
			'subheadline' => (string) $settings['hero_subheadline'],
			'tagline'     => (string) $settings['hero_tagline'],
			'cta'         => self::get_cta_data( (string) $settings['hero_cta_text'], (string) $settings['hero_cta_url'] ),
		);

		$story_desktop = self::get_image_data( (int) $settings['story_desktop_image_id'], self::DESKTOP_SIZE );
		$story_mobile  = self::get_image_data( (int) $settings['story_mobile_image_id'], self::MOBILE_SIZE );

		if ( $story_mobile['id'] <= 0 && $story_desktop['id'] > 0 ) {
			$story_mobile = self::get_image_data( (int) $settings['story_desktop_image_id'], self::MOBILE_SIZE );
		}

		$story_paragraphs = self::split_paragraphs( (string) $settings['story_body'] );
		$story_has_copy   = '' !== (string) $settings['story_eyebrow']
			|| '' !== (string) $settings['story_heading']
			|| ! empty( $story_paragraphs );
		$story_has_media  = $story_desktop['id'] > 0 || $story_mobile['id'] > 0;

		$story = array(
			'visible'    => ! empty( $settings['show_story'] ) && ( $story_has_copy || $story_has_media ),
			'has_media'  => $story_has_media,
			'desktop'    => $story_desktop,
			'mobile'     => $story_mobile,
			'media_side' => (string) $settings['story_media_side'],
			'eyebrow'    => (string) $settings['story_eyebrow'],
			'heading'    => (string) $settings['story_heading'],
			'paragraphs' => $story_paragraphs,
		);

		$values             = self::get_card_items( $settings, 'value', self::VALUE_COUNT );
		$mission_paragraphs = self::split_paragraphs( (string) $settings['mission_body'] );
		$mission_has_copy   = '' !== (string) $settings['mission_eyebrow']
			|| '' !== (string) $settings['mission_heading']
			|| ! empty( $mission_paragraphs );

		$mission = array(
			'visible'    => ! empty( $settings['show_mission'] ) && ( $mission_has_copy || ! empty( $values ) ),
			'eyebrow'    => (string) $settings['mission_eyebrow'],
			'heading'    => (string) $settings['mission_heading'],
			'paragraphs' => $mission_paragraphs,
			'values'     => $values,
		);

		$why_items = self::get_card_items( $settings, 'feature', self::FEATURE_COUNT );

		$why_choose = array(
			'visible' => ! empty( $settings['show_features'] ) && ! empty( $why_items ),
			'eyebrow' => (string) $settings['features_eyebrow'],
			'heading' => (string) $settings['features_heading'],
			'intro'   => (string) $settings['features_intro'],
			'items'   => $why_items,
		);

		$cta_text = (string) $settings['cta_text'];
		$cta_url  = (string) $settings['cta_url'];

		if ( '' === trim( $cta_text ) ) {
			$cta_text = (string) $settings['mission_cta_text'];
		}

		if ( '' === trim( $cta_url ) ) {
			$cta_url = (string) $settings['mission_cta_url'];
		}

		$cta_button     = self::get_cta_data( $cta_text, $cta_url );
		$cta_paragraphs = self::split_paragraphs( (string) $settings['cta_body'] );
		$cta_has_copy   = '' !== (string) $settings['cta_eyebrow']
			|| '' !== (string) $settings['cta_heading']
			|| ! empty( $cta_paragraphs )
			|| ! empty( $cta_button['visible'] );

		$cta = array(
			'visible'    => ! empty( $settings['show_cta'] ) && $cta_has_copy,
			'eyebrow'    => (string) $settings['cta_eyebrow'],
			'heading'    => (string) $settings['cta_heading'],
			'paragraphs' => $cta_paragraphs,
			'button'     => $cta_button,
		);

		return apply_filters(
			'shanelle_about_page_state',
			array(
				'hero'       => $hero,
				'story'      => $story,
				'mission'    => $mission,
				'why_choose' => $why_choose,
				'features'   => $why_choose,
				'cta'        => $cta,
				'settings'   => $settings,
				'urls'       => array(
					'shop' => self::get_shop_url(),
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
		$settings = array(
			'title'                  => self::get_theme_mod_string( self::MOD_TITLE, __( 'Sobre nosotros', 'shanelle' ) ),
			'show_hero'              => self::get_theme_mod_bool( self::MOD_SHOW_HERO, true ),
			'hero_desktop_image_id'  => self::get_theme_mod_int( self::MOD_HERO_DESKTOP_IMAGE ),
			'hero_mobile_image_id'   => self::get_theme_mod_int( self::MOD_HERO_MOBILE_IMAGE ),
			'hero_eyebrow'           => self::get_theme_mod_string( self::MOD_HERO_EYEBROW ),
			'hero_headline'          => self::get_theme_mod_string( self::MOD_HERO_HEADLINE ),
			'hero_subheadline'       => self::get_theme_mod_string( self::MOD_HERO_SUBHEADLINE ),
			'hero_tagline'           => self::get_theme_mod_string( self::MOD_HERO_TAGLINE ),
			'hero_cta_text'          => self::get_theme_mod_string( self::MOD_HERO_CTA_TEXT ),
			'hero_cta_url'           => self::get_theme_mod_url( self::MOD_HERO_CTA_URL ),
			'show_story'             => self::get_theme_mod_bool( self::MOD_SHOW_STORY, true ),
			'story_eyebrow'          => self::get_theme_mod_string( self::MOD_STORY_EYEBROW ),
			'story_heading'          => self::get_theme_mod_string( self::MOD_STORY_HEADING ),
			'story_body'             => self::get_theme_mod_string( self::MOD_STORY_BODY ),
			'story_desktop_image_id' => self::get_theme_mod_int( self::MOD_STORY_DESKTOP_IMAGE ),
			'story_mobile_image_id'  => self::get_theme_mod_int( self::MOD_STORY_MOBILE_IMAGE ),
			'story_media_side'       => self::get_theme_mod_choice( self::MOD_STORY_MEDIA_SIDE, array( 'left', 'right' ), 'left' ),
			'show_mission'           => self::get_theme_mod_bool( self::MOD_SHOW_MISSION, true ),
			'mission_eyebrow'        => self::get_theme_mod_string( self::MOD_MISSION_EYEBROW ),
			'mission_heading'        => self::get_theme_mod_string( self::MOD_MISSION_HEADING ),
			'mission_body'           => self::get_theme_mod_string( self::MOD_MISSION_BODY ),
			'mission_cta_text'       => self::get_theme_mod_string( self::MOD_MISSION_CTA_TEXT ),
			'mission_cta_url'        => self::get_theme_mod_url( self::MOD_MISSION_CTA_URL ),
			'show_features'          => self::get_theme_mod_bool( self::MOD_SHOW_FEATURES, true ),
			'features_eyebrow'       => self::get_theme_mod_string( self::MOD_FEATURES_EYEBROW ),
			'features_heading'       => self::get_theme_mod_string( self::MOD_FEATURES_HEADING ),
			'features_intro'         => self::get_theme_mod_string( self::MOD_FEATURES_INTRO ),
			'show_cta'               => self::get_theme_mod_bool( self::MOD_SHOW_CTA, true ),
			'cta_eyebrow'            => self::get_theme_mod_string( self::MOD_CTA_EYEBROW ),
			'cta_heading'            => self::get_theme_mod_string( self::MOD_CTA_HEADING ),
			'cta_body'               => self::get_theme_mod_string( self::MOD_CTA_BODY ),
			'cta_text'               => self::get_theme_mod_string( self::MOD_CTA_TEXT ),
			'cta_url'                => self::get_theme_mod_url( self::MOD_CTA_URL ),
		);

		for ( $index = 1; $index <= self::VALUE_COUNT; $index++ ) {
			$settings[ 'value_' . $index . '_image_id' ] = self::get_theme_mod_int( self::value_mod( $index, 'image' ) );
			$settings[ 'value_' . $index . '_title' ]    = self::get_theme_mod_string( self::value_mod( $index, 'title' ) );
			$settings[ 'value_' . $index . '_text' ]     = self::get_theme_mod_string( self::value_mod( $index, 'text' ) );
		}

		for ( $index = 1; $index <= self::FEATURE_COUNT; $index++ ) {
			$settings[ 'feature_' . $index . '_image_id' ] = self::get_theme_mod_int( self::feature_mod( $index, 'image' ) );
			$settings[ 'feature_' . $index . '_title' ]    = self::get_theme_mod_string( self::feature_mod( $index, 'title' ) );
			$settings[ 'feature_' . $index . '_text' ]     = self::get_theme_mod_string( self::feature_mod( $index, 'text' ) );
		}

		return apply_filters( 'shanelle_about_page_settings', $settings );
	}

	/**
	 * Build populated card items for values or features.
	 *
	 * @param array<string, mixed> $settings Normalized settings.
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_card_items( array $settings, string $prefix, int $count ): array {
		$items = array();

		for ( $index = 1; $index <= $count; $index++ ) {
			$image_id = (int) ( $settings[ $prefix . '_' . $index . '_image_id' ] ?? 0 );
			$title    = (string) ( $settings[ $prefix . '_' . $index . '_title' ] ?? '' );
			$text     = (string) ( $settings[ $prefix . '_' . $index . '_text' ] ?? '' );

			if ( $image_id <= 0 && '' === $title && '' === $text ) {
				continue;
			}

			$items[] = array(
				'image' => self::get_image_data( $image_id, self::ICON_SIZE ),
				'title' => $title,
				'text'  => $text,
			);
		}

		return $items;
	}

	/**
	 * Split a textarea value into trimmed, non-empty paragraphs.
	 *
	 * @return array<int, string>
	 */
	private static function split_paragraphs( string $value ): array {
		$value = trim( $value );

		if ( '' === $value ) {
			return array();
		}

		$lines = preg_split( '/\R+/', $value ) ?: array();

		return array_values(
			array_filter(
				array_map( 'trim', $lines ),
				static fn( string $line ): bool => '' !== $line
			)
		);
	}

	/**
	 * Build CTA data. Falls back to the shop URL when no URL is set.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_cta_data( string $text, string $url ): array {
		$text = trim( $text );
		$url  = trim( $url );

		if ( '' === $url ) {
			$url = self::get_shop_url();
		}

		return array(
			'text'    => $text,
			'url'     => $url,
			'visible' => '' !== $text && '' !== $url,
		);
	}

	/**
	 * Return the shop permalink, falling back to home.
	 */
	private static function get_shop_url(): string {
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop = wc_get_page_permalink( 'shop' );

			if ( is_string( $shop ) && '' !== $shop ) {
				return $shop;
			}
		}

		return home_url( '/' );
	}

	/**
	 * Build normalized image data for responsive rendering.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_image_data( int $attachment_id, string $size ): array {
		$data = array(
			'id'     => 0,
			'src'    => '',
			'srcset' => '',
			'sizes'  => '100vw',
			'alt'    => '',
			'width'  => 0,
			'height' => 0,
		);

		if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
			return $data;
		}

		$meta = wp_get_attachment_metadata( $attachment_id );
		$alt  = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		$data['id']     = $attachment_id;
		$data['src']    = (string) ( wp_get_attachment_image_url( $attachment_id, $size ) ?: '' );
		$data['srcset'] = (string) ( wp_get_attachment_image_srcset( $attachment_id, $size ) ?: '' );
		$data['alt']    = is_string( $alt ) ? $alt : '';
		$data['width']  = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
		$data['height'] = isset( $meta['height'] ) ? (int) $meta['height'] : 0;

		return $data;
	}

	/**
	 * Sanitize attachment ID customizer values.
	 */
	public static function sanitize_image_id( mixed $value ): int {
		$attachment_id = absint( $value );

		if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
			return 0;
		}

		return $attachment_id;
	}

	/**
	 * Sanitize checkbox customizer values.
	 */
	public static function sanitize_checkbox( mixed $value ): bool {
		return (bool) $value;
	}

	/**
	 * Sanitize story media side choice.
	 */
	public static function sanitize_media_side( mixed $value ): string {
		$value = is_string( $value ) ? $value : '';

		return in_array( $value, array( 'left', 'right' ), true ) ? $value : 'left';
	}

	/**
	 * Build a value mod key for a slot and field.
	 */
	private static function value_mod( int $index, string $field ): string {
		return self::MOD_VALUE_PREFIX . $index . '_' . $field;
	}

	/**
	 * Build a feature mod key for a slot and field.
	 */
	private static function feature_mod( int $index, string $field ): string {
		return self::MOD_FEATURE_PREFIX . $index . '_' . $field;
	}

	/**
	 * Register an image customizer control.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	private static function register_image_control(
		\WP_Customize_Manager $wp_customize,
		string $mod_name,
		string $label,
		string $description = ''
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => 0,
				'sanitize_callback' => array( self::class, 'sanitize_image_id' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Media_Control(
				$wp_customize,
				$mod_name,
				array(
					'label'       => $label,
					'description' => $description,
					'section'     => self::SECTION,
					'mime_type'   => 'image',
				)
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
				'section' => self::SECTION,
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
		string $default = ''
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
				'section' => self::SECTION,
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
		string $default = ''
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
				'section' => self::SECTION,
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
		string $label
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => self::SECTION,
				'type'    => 'url',
			)
		);
	}

	/**
	 * Register a select customizer control.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 * @param array<string, string> $choices     Select choices.
	 */
	private static function register_select_control(
		\WP_Customize_Manager $wp_customize,
		string $mod_name,
		string $label,
		array $choices,
		string $default
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => $default,
				'sanitize_callback' => array( self::class, 'sanitize_media_side' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => self::SECTION,
				'type'    => 'select',
				'choices' => $choices,
			)
		);
	}

	/**
	 * Read a sanitized integer theme mod.
	 */
	private static function get_theme_mod_int( string $key, int $default = 0 ): int {
		return absint( get_theme_mod( $key, $default ) );
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
		return (bool) get_theme_mod( $key, $default );
	}

	/**
	 * Read a sanitized URL theme mod.
	 */
	private static function get_theme_mod_url( string $key ): string {
		return esc_url_raw( (string) get_theme_mod( $key, '' ) );
	}

	/**
	 * Read a choice theme mod constrained to allowed values.
	 *
	 * @param array<int, string> $allowed Allowed values.
	 */
	private static function get_theme_mod_choice( string $key, array $allowed, string $default ): string {
		$value = self::get_theme_mod_string( $key, $default );

		return in_array( $value, $allowed, true ) ? $value : $default;
	}
}
