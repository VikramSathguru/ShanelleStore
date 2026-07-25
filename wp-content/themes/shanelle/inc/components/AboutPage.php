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

	private const FEATURE_COUNT = 4;

	private const MOD_TITLE = 'shanelle_about_page_title';

	private const MOD_SHOW_HERO = 'shanelle_about_page_show_hero';

	private const MOD_HERO_DESKTOP_IMAGE = 'shanelle_about_page_hero_desktop_image';

	private const MOD_HERO_MOBILE_IMAGE = 'shanelle_about_page_hero_mobile_image';

	private const MOD_HERO_EYEBROW = 'shanelle_about_page_hero_eyebrow';

	private const MOD_HERO_HEADLINE = 'shanelle_about_page_hero_headline';

	private const MOD_HERO_SUBHEADLINE = 'shanelle_about_page_hero_subheadline';

	private const MOD_HERO_TAGLINE = 'shanelle_about_page_hero_tagline';

	private const MOD_SHOW_MISSION = 'shanelle_about_page_show_mission';

	private const MOD_MISSION_HEADING = 'shanelle_about_page_mission_heading';

	private const MOD_MISSION_BODY = 'shanelle_about_page_mission_body';

	private const MOD_MISSION_CTA_TEXT = 'shanelle_about_page_mission_cta_text';

	private const MOD_MISSION_CTA_URL = 'shanelle_about_page_mission_cta_url';

	private const MOD_SHOW_FEATURES = 'shanelle_about_page_show_features';

	private const MOD_FEATURES_HEADING = 'shanelle_about_page_features_heading';

	private const MOD_FEATURES_INTRO = 'shanelle_about_page_features_intro';

	private const MOD_FEATURE_PREFIX = 'shanelle_about_page_feature_';

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
			__( 'Título de la página', 'shanelle' ),
			__( 'Sobre nosotros', 'shanelle' )
		);

		// Hero.
		self::register_checkbox_control( $wp_customize, self::MOD_SHOW_HERO, __( 'Mostrar sección hero', 'shanelle' ), true );

		self::register_image_control(
			$wp_customize,
			self::MOD_HERO_DESKTOP_IMAGE,
			__( 'Imagen hero (escritorio)', 'shanelle' ),
			__( 'Tamaño recomendado: 1440 x 720 px.', 'shanelle' )
		);

		self::register_image_control(
			$wp_customize,
			self::MOD_HERO_MOBILE_IMAGE,
			__( 'Imagen hero (móvil)', 'shanelle' ),
			__( 'Tamaño recomendado: 768 x 960 px. Si está vacío, se usa la imagen de escritorio.', 'shanelle' )
		);

		self::register_text_control( $wp_customize, self::MOD_HERO_EYEBROW, __( 'Hero: antetítulo', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_HERO_HEADLINE, __( 'Hero: titular', 'shanelle' ) );
		self::register_textarea_control( $wp_customize, self::MOD_HERO_SUBHEADLINE, __( 'Hero: subtítulo', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_HERO_TAGLINE, __( 'Hero: eslogan', 'shanelle' ) );

		// Mission.
		self::register_checkbox_control( $wp_customize, self::MOD_SHOW_MISSION, __( 'Mostrar sección de misión', 'shanelle' ), true );
		self::register_text_control( $wp_customize, self::MOD_MISSION_HEADING, __( 'Misión: encabezado', 'shanelle' ) );
		self::register_textarea_control( $wp_customize, self::MOD_MISSION_BODY, __( 'Misión: texto', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_MISSION_CTA_TEXT, __( 'Misión: texto del botón', 'shanelle' ) );
		self::register_url_control( $wp_customize, self::MOD_MISSION_CTA_URL, __( 'Misión: URL del botón (vacío = tienda)', 'shanelle' ) );

		// Features.
		self::register_checkbox_control( $wp_customize, self::MOD_SHOW_FEATURES, __( 'Mostrar sección de características', 'shanelle' ), true );
		self::register_text_control( $wp_customize, self::MOD_FEATURES_HEADING, __( 'Características: encabezado', 'shanelle' ) );
		self::register_textarea_control( $wp_customize, self::MOD_FEATURES_INTRO, __( 'Características: texto introductorio', 'shanelle' ) );

		for ( $index = 1; $index <= self::FEATURE_COUNT; $index++ ) {
			/* translators: %d: feature slot number. */
			$prefix = sprintf( __( 'Característica %d', 'shanelle' ), $index );

			self::register_image_control(
				$wp_customize,
				self::feature_mod( $index, 'image' ),
				$prefix . ': ' . __( 'ícono/imagen', 'shanelle' ),
				__( 'Imagen cuadrada recomendada (ej. 160 x 160 px).', 'shanelle' )
			);

			self::register_text_control( $wp_customize, self::feature_mod( $index, 'title' ), $prefix . ': ' . __( 'título', 'shanelle' ) );
			self::register_textarea_control( $wp_customize, self::feature_mod( $index, 'text' ), $prefix . ': ' . __( 'descripción', 'shanelle' ) );
		}
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
	 * Render the page title band.
	 */
	public static function render_title_band(): void {
		$title = self::get_page_title();

		if ( '' === $title ) {
			return;
		}
		?>
		<section class="about-page__title-band">
			<div class="container">
				<h1 id="<?php echo esc_attr( self::get_heading_id() ); ?>" class="about-page__title text-h2 text-center">
					<?php echo esc_html( $title ); ?>
				</h1>
			</div>
		</section>
		<?php
	}

	/**
	 * Render the hero section.
	 */
	public static function render_hero(): void {
		$hero = is_array( self::$state['hero'] ?? null ) ? self::$state['hero'] : array();

		if ( empty( $hero['visible'] ) ) {
			return;
		}

		$has_media = ! empty( $hero['has_media'] );
		?>
		<section class="about-page__hero<?php echo $has_media ? '' : ' about-page__hero--plain'; ?>">
			<div class="container about-page__hero-inner">
				<?php if ( $has_media ) : ?>
					<div class="about-page__hero-media">
						<?php self::render_picture( $hero['desktop'] ?? array(), $hero['mobile'] ?? array(), true ); ?>
					</div>
				<?php endif; ?>

				<div class="about-page__hero-copy">
					<?php if ( '' !== (string) ( $hero['eyebrow'] ?? '' ) ) : ?>
						<p class="about-page__eyebrow text-overline text-brand"><?php echo esc_html( (string) $hero['eyebrow'] ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== (string) ( $hero['headline'] ?? '' ) ) : ?>
						<p class="about-page__hero-headline text-display"><?php echo esc_html( (string) $hero['headline'] ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== (string) ( $hero['subheadline'] ?? '' ) ) : ?>
						<p class="about-page__hero-subheadline text-body text-secondary"><?php echo esc_html( (string) $hero['subheadline'] ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== (string) ( $hero['tagline'] ?? '' ) ) : ?>
						<p class="about-page__hero-tagline text-h5 text-brand"><?php echo esc_html( (string) $hero['tagline'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Render the mission section.
	 */
	public static function render_mission(): void {
		$mission = is_array( self::$state['mission'] ?? null ) ? self::$state['mission'] : array();

		if ( empty( $mission['visible'] ) ) {
			return;
		}

		$paragraphs = is_array( $mission['paragraphs'] ?? null ) ? $mission['paragraphs'] : array();
		$cta        = is_array( $mission['cta'] ?? null ) ? $mission['cta'] : array();
		?>
		<section class="about-page__mission">
			<div class="container about-page__mission-inner">
				<?php if ( '' !== (string) ( $mission['heading'] ?? '' ) ) : ?>
					<h2 class="about-page__mission-heading text-h3 text-inverse text-center">
						<?php echo esc_html( (string) $mission['heading'] ); ?>
					</h2>
				<?php endif; ?>

				<?php if ( ! empty( $paragraphs ) ) : ?>
					<div class="about-page__mission-body">
						<?php foreach ( $paragraphs as $paragraph ) : ?>
							<p class="text-body text-inverse text-center"><?php echo esc_html( (string) $paragraph ); ?></p>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $cta['visible'] ) ) : ?>
					<div class="about-page__mission-actions">
						<a class="btn btn--secondary btn--lg" href="<?php echo esc_url( (string) $cta['url'] ); ?>">
							<?php echo esc_html( (string) $cta['text'] ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Render the features section.
	 */
	public static function render_features(): void {
		$features = is_array( self::$state['features'] ?? null ) ? self::$state['features'] : array();
		$items    = is_array( $features['items'] ?? null ) ? $features['items'] : array();

		if ( empty( $features['visible'] ) || empty( $items ) ) {
			return;
		}
		?>
		<section class="about-page__features">
			<div class="container about-page__features-inner">
				<?php if ( '' !== (string) ( $features['heading'] ?? '' ) || '' !== (string) ( $features['intro'] ?? '' ) ) : ?>
					<header class="about-page__features-header">
						<?php if ( '' !== (string) ( $features['heading'] ?? '' ) ) : ?>
							<h2 class="about-page__features-heading text-h3 text-brand text-center">
								<?php echo esc_html( (string) $features['heading'] ); ?>
							</h2>
						<?php endif; ?>

						<?php if ( '' !== (string) ( $features['intro'] ?? '' ) ) : ?>
							<p class="about-page__features-intro text-body text-secondary text-center">
								<?php echo esc_html( (string) $features['intro'] ); ?>
							</p>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<ul class="about-page__features-grid" role="list">
					<?php foreach ( $items as $item ) : ?>
						<li class="about-page__feature">
							<?php if ( ! empty( $item['image']['id'] ) ) : ?>
								<span class="about-page__feature-icon" aria-hidden="true">
									<?php self::render_image( (int) $item['image']['id'], self::ICON_SIZE ); ?>
								</span>
							<?php endif; ?>

							<?php if ( '' !== (string) ( $item['title'] ?? '' ) ) : ?>
								<h3 class="about-page__feature-title text-h5"><?php echo esc_html( (string) $item['title'] ); ?></h3>
							<?php endif; ?>

							<?php if ( '' !== (string) ( $item['text'] ?? '' ) ) : ?>
								<p class="about-page__feature-text text-body-sm text-secondary"><?php echo esc_html( (string) $item['text'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	}

	/**
	 * Render a responsive <picture> from desktop/mobile image data.
	 *
	 * @param array<string, mixed> $desktop Desktop image data.
	 * @param array<string, mixed> $mobile  Mobile image data.
	 * @param bool                 $eager   Whether to eager load (LCP).
	 */
	private static function render_picture( array $desktop, array $mobile, bool $eager = false ): void {
		$desktop_src = (string) ( $desktop['src'] ?? '' );
		$mobile_src  = (string) ( $mobile['src'] ?? '' );

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
				class="about-page__image"
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
	private static function render_image( int $attachment_id, string $size ): void {
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

		$desktop = self::get_image_data( (int) $settings['hero_desktop_image_id'], self::DESKTOP_SIZE );
		$mobile  = self::get_image_data( (int) $settings['hero_mobile_image_id'], self::MOBILE_SIZE );

		if ( $mobile['id'] <= 0 && $desktop['id'] > 0 ) {
			$mobile = self::get_image_data( (int) $settings['hero_desktop_image_id'], self::MOBILE_SIZE );
		}

		$has_media = $desktop['id'] > 0 || $mobile['id'] > 0;

		$hero = array(
			'visible'     => ! empty( $settings['show_hero'] ),
			'has_media'   => $has_media,
			'desktop'     => $desktop,
			'mobile'      => $mobile,
			'eyebrow'     => (string) $settings['hero_eyebrow'],
			'headline'    => (string) $settings['hero_headline'],
			'subheadline' => (string) $settings['hero_subheadline'],
			'tagline'     => (string) $settings['hero_tagline'],
		);

		$mission = array(
			'visible'    => ! empty( $settings['show_mission'] ),
			'heading'    => (string) $settings['mission_heading'],
			'paragraphs' => self::split_paragraphs( (string) $settings['mission_body'] ),
			'cta'        => self::get_cta_data( (string) $settings['mission_cta_text'], (string) $settings['mission_cta_url'] ),
		);

		$features = array(
			'visible' => ! empty( $settings['show_features'] ),
			'heading' => (string) $settings['features_heading'],
			'intro'   => (string) $settings['features_intro'],
			'items'   => self::get_feature_items( $settings ),
		);

		return apply_filters(
			'shanelle_about_page_state',
			array(
				'hero'     => $hero,
				'mission'  => $mission,
				'features' => $features,
				'settings' => $settings,
				'urls'     => array(
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
			'title'                 => self::get_theme_mod_string( self::MOD_TITLE, __( 'Sobre nosotros', 'shanelle' ) ),
			'show_hero'             => self::get_theme_mod_bool( self::MOD_SHOW_HERO, true ),
			'hero_desktop_image_id' => self::get_theme_mod_int( self::MOD_HERO_DESKTOP_IMAGE ),
			'hero_mobile_image_id'  => self::get_theme_mod_int( self::MOD_HERO_MOBILE_IMAGE ),
			'hero_eyebrow'          => self::get_theme_mod_string( self::MOD_HERO_EYEBROW ),
			'hero_headline'         => self::get_theme_mod_string( self::MOD_HERO_HEADLINE ),
			'hero_subheadline'      => self::get_theme_mod_string( self::MOD_HERO_SUBHEADLINE ),
			'hero_tagline'          => self::get_theme_mod_string( self::MOD_HERO_TAGLINE ),
			'show_mission'          => self::get_theme_mod_bool( self::MOD_SHOW_MISSION, true ),
			'mission_heading'       => self::get_theme_mod_string( self::MOD_MISSION_HEADING ),
			'mission_body'          => self::get_theme_mod_string( self::MOD_MISSION_BODY ),
			'mission_cta_text'      => self::get_theme_mod_string( self::MOD_MISSION_CTA_TEXT ),
			'mission_cta_url'       => self::get_theme_mod_url( self::MOD_MISSION_CTA_URL ),
			'show_features'         => self::get_theme_mod_bool( self::MOD_SHOW_FEATURES, true ),
			'features_heading'      => self::get_theme_mod_string( self::MOD_FEATURES_HEADING ),
			'features_intro'        => self::get_theme_mod_string( self::MOD_FEATURES_INTRO ),
		);

		for ( $index = 1; $index <= self::FEATURE_COUNT; $index++ ) {
			$settings[ 'feature_' . $index . '_image_id' ] = self::get_theme_mod_int( self::feature_mod( $index, 'image' ) );
			$settings[ 'feature_' . $index . '_title' ]    = self::get_theme_mod_string( self::feature_mod( $index, 'title' ) );
			$settings[ 'feature_' . $index . '_text' ]     = self::get_theme_mod_string( self::feature_mod( $index, 'text' ) );
		}

		return apply_filters( 'shanelle_about_page_settings', $settings );
	}

	/**
	 * Build feature items from settings, keeping only populated slots.
	 *
	 * @param array<string, mixed> $settings Normalized settings.
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_feature_items( array $settings ): array {
		$items = array();

		for ( $index = 1; $index <= self::FEATURE_COUNT; $index++ ) {
			$image_id = (int) ( $settings[ 'feature_' . $index . '_image_id' ] ?? 0 );
			$title    = (string) ( $settings[ 'feature_' . $index . '_title' ] ?? '' );
			$text     = (string) ( $settings[ 'feature_' . $index . '_text' ] ?? '' );

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
}
