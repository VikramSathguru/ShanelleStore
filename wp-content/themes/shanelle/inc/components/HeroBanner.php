<?php
/**
 * Homepage hero banner component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Theme Customizer driven homepage hero banner with carousel-ready markup.
 */
final class HeroBanner {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/hero-banner';

	private const COMPONENT_URI = SHANELLE_URI . '/components/hero-banner';

	private const ROOT_ID = 'shanelle-hero-banner';

	private const MOD_DESKTOP_IMAGE = 'shanelle_hero_banner_desktop_image';

	private const MOD_MOBILE_IMAGE = 'shanelle_hero_banner_mobile_image';

	private const MOD_HEADLINE = 'shanelle_hero_banner_headline';

	private const MOD_SUBHEADLINE = 'shanelle_hero_banner_subheadline';

	private const MOD_PRIMARY_TEXT = 'shanelle_hero_banner_primary_text';

	private const MOD_PRIMARY_URL = 'shanelle_hero_banner_primary_url';

	private const MOD_SECONDARY_TEXT = 'shanelle_hero_banner_secondary_text';

	private const MOD_SECONDARY_URL = 'shanelle_hero_banner_secondary_url';

	private const MOD_OVERLAY_OPACITY = 'shanelle_hero_banner_overlay_opacity';

	private const MOD_OVERLAY_COLOR = 'shanelle_hero_banner_overlay_color';

	private const DESKTOP_SIZE = 'shanelle-hero';

	private const MOBILE_SIZE = 'shanelle-hero-mobile';

	/**
	 * Active render arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Prepared slide data for the active render cycle.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static array $slides = array();

	/**
	 * Boot hero banner hooks.
	 */
	public static function boot(): void {
		add_action( 'after_setup_theme', array( self::class, 'register_image_sizes' ), 20 );
		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp_head', array( self::class, 'preload_lcp_image' ), 5 );
	}

	/**
	 * Register responsive hero image sizes.
	 */
	public static function register_image_sizes(): void {
		add_image_size( self::MOBILE_SIZE, 768, 960, true );
	}

	/**
	 * Register Theme Customizer settings for the hero banner.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_panel(
			'shanelle_homepage',
			array(
				'title'       => __( 'Inicio Shanelle', 'shanelle' ),
				'description' => __( 'Configura las secciones de la página de inicio.', 'shanelle' ),
				'priority'    => 160,
			)
		);

		$wp_customize->add_section(
			'shanelle_hero_banner',
			array(
				'title'       => __( 'Banner principal', 'shanelle' ),
				'description' => __( 'Primera sección de la página de inicio. Deja los campos vacíos para ocultar contenido opcional.', 'shanelle' ),
				'panel'       => 'shanelle_homepage',
				'priority'    => 10,
			)
		);

		self::register_image_control(
			$wp_customize,
			self::MOD_DESKTOP_IMAGE,
			__( 'Imagen de escritorio', 'shanelle' ),
			__( 'Tamaño recomendado: 1440 x 720 px.', 'shanelle' )
		);

		self::register_image_control(
			$wp_customize,
			self::MOD_MOBILE_IMAGE,
			__( 'Imagen móvil', 'shanelle' ),
			__( 'Tamaño recomendado: 768 x 960 px. Si está vacío, se usa la imagen de escritorio.', 'shanelle' )
		);

		self::register_text_control( $wp_customize, self::MOD_HEADLINE, __( 'Titular', 'shanelle' ) );
		self::register_textarea_control( $wp_customize, self::MOD_SUBHEADLINE, __( 'Subtítulo', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_PRIMARY_TEXT, __( 'Texto del botón principal', 'shanelle' ) );
		self::register_url_control( $wp_customize, self::MOD_PRIMARY_URL, __( 'URL del botón principal', 'shanelle' ) );
		self::register_text_control( $wp_customize, self::MOD_SECONDARY_TEXT, __( 'Texto del botón secundario', 'shanelle' ) );
		self::register_url_control( $wp_customize, self::MOD_SECONDARY_URL, __( 'URL del botón secundario', 'shanelle' ) );

		$wp_customize->add_setting(
			self::MOD_OVERLAY_OPACITY,
			array(
				'default'           => 35,
				'sanitize_callback' => array( self::class, 'sanitize_overlay_opacity' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_OVERLAY_OPACITY,
			array(
				'label'       => __( 'Opacidad del overlay (%)', 'shanelle' ),
				'description' => __( '0 oculta el overlay. 100 cubre por completo la imagen.', 'shanelle' ),
				'section'     => 'shanelle_hero_banner',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 100,
					'step' => 1,
				),
			)
		);

		$wp_customize->add_setting(
			self::MOD_OVERLAY_COLOR,
			array(
				'default'           => '#171717',
				'sanitize_callback' => array( self::class, 'sanitize_overlay_color' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				self::MOD_OVERLAY_COLOR,
				array(
					'label'   => __( 'Color del overlay', 'shanelle' ),
					'section' => 'shanelle_hero_banner',
				)
			)
		);
	}

	/**
	 * Enqueue hero assets on the front page.
	 */
	public static function enqueue_assets(): void {
		if ( ! is_front_page() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue hero banner assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-hero-banner',
			self::COMPONENT_URI . '/hero-banner.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-hero-banner',
			self::COMPONENT_URI . '/hero-banner.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-hero-banner', 'type', 'module' );

		wp_localize_script(
			'shanelle-hero-banner',
			'shanelleHeroBanner',
			array(
				'slideCount' => count( self::build_slides() ),
				'i18n'       => array(
					'carouselLabel' => __( 'Carrusel del banner principal', 'shanelle' ),
					'slideLabel'    => __( 'Diapositiva %1$d de %2$d', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Preload the LCP hero image on the front page.
	 */
	public static function preload_lcp_image(): void {
		if ( ! is_front_page() ) {
			return;
		}

		$slides = self::build_slides();

		if ( empty( $slides[0]['lcp_image']['href'] ) ) {
			return;
		}

		$lcp = $slides[0]['lcp_image'];
		$href = (string) $lcp['href'];

		if ( '' === $href ) {
			return;
		}

		printf(
			'<link rel="preload" as="image" href="%1$s"%2$s%3$s />' . "\n",
			esc_url( $href ),
			! empty( $lcp['imagesrcset'] )
				? ' imagesrcset="' . esc_attr( (string) $lcp['imagesrcset'] ) . '"'
				: '',
			! empty( $lcp['imagesizes'] )
				? ' imagesizes="' . esc_attr( (string) $lcp['imagesizes'] ) . '"'
				: ''
		);
	}

	/**
	 * Render the hero banner component.
	 *
	 * @param array<string, mixed> $args Optional render arguments.
	 */
	public static function render( array $args = array() ): void {
		self::$args   = self::parse_args( $args );
		self::$slides = self::build_slides();

		if ( empty( self::$slides ) ) {
			self::reset_context();
			return;
		}

		if ( ! wp_style_is( 'shanelle-hero-banner', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/hero-banner.php';

		self::reset_context();
	}

	/**
	 * Render all slides.
	 */
	public static function render_slides(): void {
		foreach ( self::$slides as $index => $slide ) {
			self::render_slide( $slide, (int) $index );
		}
	}

	/**
	 * Render a single hero slide.
	 *
	 * @param array<string, mixed> $slide Slide data.
	 * @param int                  $index Slide index.
	 */
	public static function render_slide( array $slide, int $index ): void {
		$is_active     = 0 === $index;
		$heading_id    = self::get_heading_id( $index );
		$overlay_style = (string) ( $slide['overlay_style'] ?? '' );
		$has_media     = ! empty( $slide['has_media'] );
		?>
		<article
			class="hero-banner__slide<?php echo $is_active ? ' is-active' : ''; ?>"
			data-shanelle-hero-slide
			data-slide-index="<?php echo esc_attr( (string) $index ); ?>"
			aria-hidden="<?php echo $is_active ? 'false' : 'true'; ?>"
			<?php echo $is_active ? '' : ' hidden'; ?>
		>
			<div class="hero-banner__media<?php echo $has_media ? '' : ' hero-banner__media--fallback'; ?>">
				<?php if ( $has_media ) : ?>
					<?php self::render_slide_media( $slide, $index ); ?>
				<?php else : ?>
					<div class="hero-banner__fallback" aria-hidden="true"></div>
				<?php endif; ?>

				<div
					class="hero-banner__overlay"
					style="<?php echo esc_attr( $overlay_style ); ?>"
					aria-hidden="true"
				></div>
			</div>

			<div class="hero-banner__content container">
				<?php self::render_slide_content( $slide, $heading_id ); ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Render responsive hero media for a slide.
	 *
	 * @param array<string, mixed> $slide Slide data.
	 * @param int                  $index Slide index.
	 */
	public static function render_slide_media( array $slide, int $index ): void {
		$desktop = is_array( $slide['desktop'] ?? null ) ? $slide['desktop'] : array();
		$mobile  = is_array( $slide['mobile'] ?? null ) ? $slide['mobile'] : array();
		$lcp     = 0 === $index;
		?>
		<picture class="hero-banner__picture">
			<?php if ( ! empty( $desktop['srcset'] ) ) : ?>
				<source
					media="(min-width: 48rem)"
					srcset="<?php echo esc_attr( (string) $desktop['srcset'] ); ?>"
					sizes="<?php echo esc_attr( (string) $desktop['sizes'] ); ?>"
				>
			<?php endif; ?>

			<img
				class="hero-banner__image"
				src="<?php echo esc_url( (string) ( $mobile['src'] ?: $desktop['src'] ) ); ?>"
				<?php if ( ! empty( $mobile['srcset'] ) || ! empty( $desktop['srcset'] ) ) : ?>
					srcset="<?php echo esc_attr( (string) ( $mobile['srcset'] ?: $desktop['srcset'] ) ); ?>"
				<?php endif; ?>
				sizes="<?php echo esc_attr( (string) ( $mobile['sizes'] ?: $desktop['sizes'] ) ); ?>"
				alt="<?php echo esc_attr( (string) ( $mobile['alt'] ?: $desktop['alt'] ) ); ?>"
				width="<?php echo esc_attr( (string) ( $mobile['width'] ?: $desktop['width'] ) ); ?>"
				height="<?php echo esc_attr( (string) ( $mobile['height'] ?: $desktop['height'] ) ); ?>"
				loading="<?php echo esc_attr( $lcp ? 'eager' : 'lazy' ); ?>"
				decoding="async"
				<?php if ( $lcp ) : ?>
					fetchpriority="high"
				<?php endif; ?>
			>
		</picture>
		<?php
	}

	/**
	 * Render slide copy and actions.
	 *
	 * @param array<string, mixed> $slide      Slide data.
	 * @param string               $heading_id Accessible heading ID.
	 */
	public static function render_slide_content( array $slide, string $heading_id ): void {
		$headline    = (string) ( $slide['headline'] ?? '' );
		$subheadline = (string) ( $slide['subheadline'] ?? '' );
		$has_actions = ! empty( $slide['primary']['visible'] ) || ! empty( $slide['secondary']['visible'] );
		?>
		<div class="hero-banner__copy">
			<?php if ( '' !== $headline ) : ?>
				<h1 id="<?php echo esc_attr( $heading_id ); ?>" class="hero-banner__headline text-display">
					<?php echo esc_html( $headline ); ?>
				</h1>
			<?php endif; ?>

			<?php if ( '' !== $subheadline ) : ?>
				<p class="hero-banner__subheadline text-body">
					<?php echo esc_html( $subheadline ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $has_actions ) : ?>
				<?php self::render_slide_actions( $slide ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render primary and secondary CTA links.
	 *
	 * @param array<string, mixed> $slide Slide data.
	 */
	public static function render_slide_actions( array $slide ): void {
		$primary   = is_array( $slide['primary'] ?? null ) ? $slide['primary'] : array();
		$secondary = is_array( $slide['secondary'] ?? null ) ? $slide['secondary'] : array();
		?>
		<div class="hero-banner__actions">
			<?php if ( ! empty( $primary['visible'] ) ) : ?>
				<a
					class="btn btn--primary btn--lg hero-banner__action hero-banner__action--primary"
					href="<?php echo esc_url( (string) $primary['url'] ); ?>"
				>
					<?php echo esc_html( (string) $primary['text'] ); ?>
				</a>
			<?php endif; ?>

			<?php if ( ! empty( $secondary['visible'] ) ) : ?>
				<a
					class="btn btn--secondary btn--lg hero-banner__action hero-banner__action--secondary"
					href="<?php echo esc_url( (string) $secondary['url'] ); ?>"
				>
					<?php echo esc_html( (string) $secondary['text'] ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Return root element ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return heading ID for a slide.
	 */
	public static function get_heading_id( int $index = 0 ): string {
		return 0 === $index ? self::ROOT_ID . '-heading' : self::ROOT_ID . '-heading-' . $index;
	}

	/**
	 * Return slides JSON for client hydration.
	 */
	public static function get_slides_json(): string {
		return wp_json_encode( self::$slides ) ?: '[]';
	}

	/**
	 * Return aria-labelledby target for the hero region.
	 */
	public static function get_aria_labelledby(): string {
		foreach ( self::$slides as $index => $slide ) {
			if ( ! empty( $slide['headline'] ) ) {
				return self::get_heading_id( (int) $index );
			}
		}

		return self::get_root_id() . '-label';
	}

	/**
	 * Build slide data from Theme Customizer settings.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function build_slides(): array {
		$settings = self::get_settings();
		$desktop  = self::get_image_data( (int) $settings['desktop_image_id'], self::DESKTOP_SIZE );
		$mobile   = self::get_image_data( (int) $settings['mobile_image_id'], self::MOBILE_SIZE );

		if ( $mobile['id'] <= 0 && $desktop['id'] > 0 ) {
			$mobile = self::get_image_data( (int) $settings['desktop_image_id'], self::MOBILE_SIZE );
		}

		$has_media   = $desktop['id'] > 0 || $mobile['id'] > 0;
		$headline    = (string) $settings['headline'];
		$subheadline = (string) $settings['subheadline'];

		// Presentation fallback when the merchant has not configured hero media/copy yet.
		if ( ! $has_media && '' === $headline ) {
			$headline = (string) get_bloginfo( 'name', 'display' );
		}

		if ( ! $has_media && '' === $subheadline ) {
			$description = (string) get_bloginfo( 'description', 'display' );
			$subheadline = '' !== $description
				? $description
				: __( 'Estilos seleccionados para cada momento.', 'shanelle' );
		}

		$slide = array(
			'index'         => 0,
			'headline'      => $headline,
			'subheadline'   => $subheadline,
			'desktop'       => $desktop,
			'mobile'        => $mobile,
			'has_media'     => $has_media,
			'overlay_style' => self::get_overlay_style(
				(string) $settings['overlay_color'],
				(int) $settings['overlay_opacity']
			),
			'primary'       => self::get_button_data(
				(string) $settings['primary_text'],
				(string) $settings['primary_url']
			),
			'secondary'     => self::get_button_data(
				(string) $settings['secondary_text'],
				(string) $settings['secondary_url']
			),
			'lcp_image'     => self::get_lcp_preload_data( $desktop, $mobile ),
		);

		$slides = apply_filters( 'shanelle_hero_banner_slides', array( $slide ), $settings );

		return is_array( $slides ) ? array_values( $slides ) : array();
	}

	/**
	 * Read Theme Customizer settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		return apply_filters(
			'shanelle_hero_banner_settings',
			array(
				'desktop_image_id' => self::get_theme_mod_int( self::MOD_DESKTOP_IMAGE ),
				'mobile_image_id'  => self::get_theme_mod_int( self::MOD_MOBILE_IMAGE ),
				'headline'         => self::get_theme_mod_string( self::MOD_HEADLINE ),
				'subheadline'      => self::get_theme_mod_string( self::MOD_SUBHEADLINE ),
				'primary_text'     => self::get_theme_mod_string( self::MOD_PRIMARY_TEXT ),
				'primary_url'      => self::get_theme_mod_url( self::MOD_PRIMARY_URL ),
				'secondary_text'   => self::get_theme_mod_string( self::MOD_SECONDARY_TEXT ),
				'secondary_url'    => self::get_theme_mod_url( self::MOD_SECONDARY_URL ),
				'overlay_opacity'  => self::get_theme_mod_int( self::MOD_OVERLAY_OPACITY, 35 ),
				'overlay_color'    => self::get_theme_mod_string( self::MOD_OVERLAY_COLOR, '#171717' ),
			)
		);
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
	 * Sanitize overlay opacity customizer values.
	 */
	public static function sanitize_overlay_opacity( mixed $value ): int {
		$opacity = absint( $value );

		if ( $opacity > 100 ) {
			return 100;
		}

		return $opacity;
	}

	/**
	 * Sanitize overlay color customizer values.
	 */
	public static function sanitize_overlay_color( mixed $value ): string {
		$color = sanitize_hex_color( (string) $value );

		return is_string( $color ) && '' !== $color ? $color : '#171717';
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
		string $description
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
					'section'     => 'shanelle_hero_banner',
					'mime_type'   => 'image',
				)
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
				'section' => 'shanelle_hero_banner',
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
				'section' => 'shanelle_hero_banner',
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
				'section' => 'shanelle_hero_banner',
				'type'    => 'url',
			)
		);
	}

	/**
	 * Build normalized image data for responsive rendering.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_image_data( int $attachment_id, string $size ): array {
		$data = array(
			'id'      => 0,
			'src'     => '',
			'srcset'  => '',
			'sizes'   => '100vw',
			'alt'     => '',
			'width'   => 0,
			'height'  => 0,
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
	 * Build CTA data for a button pair.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_button_data( string $text, string $url ): array {
		$text = trim( $text );
		$url  = trim( $url );

		return array(
			'text'    => $text,
			'url'     => $url,
			'visible' => '' !== $text && '' !== $url,
		);
	}

	/**
	 * Build inline overlay style declarations.
	 */
	private static function get_overlay_style( string $color, int $opacity ): string {
		$color   = self::sanitize_overlay_color( $color );
		$opacity = self::sanitize_overlay_opacity( $opacity );
		$rgb     = self::hex_to_rgb( $color );

		return sprintf(
			'--hero-banner-overlay-color: rgb(%1$d %2$d %3$d); --hero-banner-overlay-opacity: %4$s;',
			$rgb['r'],
			$rgb['g'],
			$rgb['b'],
			number_format( $opacity / 100, 2, '.', '' )
		);
	}

	/**
	 * Build preload metadata for the LCP image.
	 *
	 * @param array<string, mixed> $desktop Desktop image data.
	 * @param array<string, mixed> $mobile  Mobile image data.
	 * @return array<string, string>
	 */
	private static function get_lcp_preload_data( array $desktop, array $mobile ): array {
		$href      = (string) ( $mobile['src'] ?: $desktop['src'] );
		$imagesrcset = (string) ( $mobile['srcset'] ?: $desktop['srcset'] );
		$imagesizes  = (string) ( $mobile['sizes'] ?: $desktop['sizes'] );

		return array(
			'href'        => $href,
			'imagesrcset' => $imagesrcset,
			'imagesizes'  => $imagesizes,
		);
	}

	/**
	 * Convert a hex color to RGB channels.
	 *
	 * @return array{r:int,g:int,b:int}
	 */
	private static function hex_to_rgb( string $hex ): array {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return array(
			'r' => (int) hexdec( substr( $hex, 0, 2 ) ),
			'g' => (int) hexdec( substr( $hex, 2, 2 ) ),
			'b' => (int) hexdec( substr( $hex, 4, 2 ) ),
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
	 * Read a sanitized URL theme mod.
	 */
	private static function get_theme_mod_url( string $key ): string {
		return esc_url_raw( (string) get_theme_mod( $key, '' ) );
	}

	/**
	 * Parse render arguments.
	 *
	 * @param array<string, mixed> $args Input args.
	 * @return array<string, mixed>
	 */
	private static function parse_args( array $args ): array {
		return wp_parse_args(
			$args,
			array(
				'carousel_enabled' => false,
			)
		);
	}

	/**
	 * Reset active render context.
	 */
	private static function reset_context(): void {
		self::$args   = array();
		self::$slides = array();
	}
}
