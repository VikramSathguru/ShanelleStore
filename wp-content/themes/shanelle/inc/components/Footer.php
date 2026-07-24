<?php
/**
 * Site footer component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Premium minimalist storefront footer with Customizer-driven content.
 */
final class Footer {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/footer';

	private const COMPONENT_URI = SHANELLE_URI . '/components/footer';

	private const ROOT_ID = 'shanelle-footer';

	private const MOD_SHOW_LOGO = 'shanelle_footer_show_logo';

	private const MOD_LOGO = 'shanelle_footer_logo';

	private const MOD_BRAND_DESCRIPTION = 'shanelle_footer_brand_description';

	private const MOD_SHOW_SOCIAL = 'shanelle_footer_show_social';

	private const MOD_SOCIAL_INSTAGRAM = 'shanelle_footer_social_instagram';

	private const MOD_SOCIAL_TIKTOK = 'shanelle_footer_social_tiktok';

	private const MOD_SOCIAL_PINTEREST = 'shanelle_footer_social_pinterest';

	private const MOD_SOCIAL_FACEBOOK = 'shanelle_footer_social_facebook';

	private const MOD_SOCIAL_YOUTUBE = 'shanelle_footer_social_youtube';

	private const MOD_SHOW_CONTACT = 'shanelle_footer_show_contact';

	private const MOD_CONTACT_TITLE = 'shanelle_footer_contact_title';

	private const MOD_CONTACT_PHONE = 'shanelle_footer_contact_phone';

	private const MOD_CONTACT_EMAIL = 'shanelle_footer_contact_email';

	private const MOD_CONTACT_ADDRESS = 'shanelle_footer_contact_address';

	private const MOD_SHOW_NEWSLETTER = 'shanelle_footer_show_newsletter';

	private const MOD_NEWSLETTER_TITLE = 'shanelle_footer_newsletter_title';

	private const MOD_NEWSLETTER_DESCRIPTION = 'shanelle_footer_newsletter_description';

	private const MOD_COPYRIGHT = 'shanelle_footer_copyright';

	private const MOD_SHOW_PAYMENT_ICONS = 'shanelle_footer_show_payment_icons';

	private const MOD_PAYMENT_ICONS = 'shanelle_footer_payment_icons';

	private const MOD_SHOW_SCROLL_TOP = 'shanelle_footer_show_scroll_top';

	/**
	 * Active footer state for the render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot footer hooks.
	 */
	public static function boot(): void {
		add_action( 'after_setup_theme', array( self::class, 'register_menus' ), 20 );
		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Register footer navigation menu locations.
	 */
	public static function register_menus(): void {
		$labels = array();

		foreach ( self::get_menu_locations() as $slug => $label ) {
			/* translators: %s: footer menu column title */
			$labels[ $slug ] = sprintf( __( 'Pie de página: %s', 'shanelle' ), $label );
		}

		register_nav_menus( $labels );
	}

	/**
	 * Return footer menu locations with translated column titles.
	 *
	 * @return array<string, string>
	 */
	public static function get_menu_locations(): array {
		return array(
			'footer_shop'             => __( 'Enlaces útiles', 'shanelle' ),
			'footer_customer_service' => __( 'Atención al cliente', 'shanelle' ),
			'footer_legal'            => __( 'Políticas', 'shanelle' ),
			'footer_about'            => __( 'Nosotros', 'shanelle' ),
		);
	}

	/**
	 * Register Theme Customizer settings for the footer.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'shanelle_footer',
			array(
				'title'       => __( 'Pie de página', 'shanelle' ),
				'description' => __( 'Configura la marca, contacto, menús, boletín opcional e íconos de pago del pie de página.', 'shanelle' ),
				'priority'    => 120,
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_LOGO,
			__( 'Mostrar logo', 'shanelle' ),
			true
		);

		$wp_customize->add_setting(
			self::MOD_LOGO,
			array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Media_Control(
				$wp_customize,
				self::MOD_LOGO,
				array(
					'label'       => __( 'Logo del pie de página', 'shanelle' ),
					'description' => __( 'Logo opcional del pie de página. Si está vacío, se usa el logo del sitio.', 'shanelle' ),
					'section'     => 'shanelle_footer',
					'mime_type'   => 'image',
				)
			)
		);

		$wp_customize->add_setting(
			self::MOD_BRAND_DESCRIPTION,
			array(
				'default'           => get_bloginfo( 'description', 'display' ) ?: __( 'Estilos seleccionados para cada momento.', 'shanelle' ),
				'sanitize_callback' => array( self::class, 'sanitize_textarea' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_BRAND_DESCRIPTION,
			array(
				'label'   => __( 'Descripción de la marca', 'shanelle' ),
				'section' => 'shanelle_footer',
				'type'    => 'textarea',
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_CONTACT,
			__( 'Mostrar datos de contacto', 'shanelle' ),
			true
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_CONTACT_TITLE,
			__( 'Título de contacto', 'shanelle' ),
			__( 'Datos de contacto', 'shanelle' )
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_CONTACT_PHONE,
			__( 'Teléfono', 'shanelle' ),
			''
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_CONTACT_EMAIL,
			__( 'Correo electrónico', 'shanelle' ),
			''
		);

		$wp_customize->add_setting(
			self::MOD_CONTACT_ADDRESS,
			array(
				'default'           => '',
				'sanitize_callback' => array( self::class, 'sanitize_textarea' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_CONTACT_ADDRESS,
			array(
				'label'   => __( 'Dirección', 'shanelle' ),
				'section' => 'shanelle_footer',
				'type'    => 'textarea',
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_SOCIAL,
			__( 'Mostrar redes sociales', 'shanelle' ),
			true
		);

		self::register_url_control( $wp_customize, self::MOD_SOCIAL_INSTAGRAM, __( 'URL de Instagram', 'shanelle' ) );
		self::register_url_control( $wp_customize, self::MOD_SOCIAL_TIKTOK, __( 'URL de TikTok', 'shanelle' ) );
		self::register_url_control( $wp_customize, self::MOD_SOCIAL_PINTEREST, __( 'URL de Pinterest', 'shanelle' ) );
		self::register_url_control( $wp_customize, self::MOD_SOCIAL_FACEBOOK, __( 'URL de Facebook', 'shanelle' ) );
		self::register_url_control( $wp_customize, self::MOD_SOCIAL_YOUTUBE, __( 'URL de YouTube', 'shanelle' ) );

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_NEWSLETTER,
			__( 'Mostrar bloque de boletín', 'shanelle' ),
			false
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_NEWSLETTER_TITLE,
			__( 'Título del boletín', 'shanelle' ),
			__( 'Únete a nuestra lista', 'shanelle' )
		);

		$wp_customize->add_setting(
			self::MOD_NEWSLETTER_DESCRIPTION,
			array(
				'default'           => __( 'Sé la primera en saber de novedades, ofertas exclusivas y ediciones de estilo.', 'shanelle' ),
				'sanitize_callback' => array( self::class, 'sanitize_textarea' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_NEWSLETTER_DESCRIPTION,
			array(
				'label'   => __( 'Descripción del boletín', 'shanelle' ),
				'section' => 'shanelle_footer',
				'type'    => 'textarea',
			)
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_COPYRIGHT,
			__( 'Texto de copyright', 'shanelle' ),
			'© {year} {site_name}. ' . __( 'Todos los derechos reservados.', 'shanelle' ),
			__( 'Usa los marcadores {year} y {site_name}.', 'shanelle' )
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_PAYMENT_ICONS,
			__( 'Mostrar íconos de pago', 'shanelle' ),
			true
		);

		$wp_customize->add_setting(
			self::MOD_PAYMENT_ICONS,
			array(
				'default'           => 'visa,mastercard,amex,paypal,apple_pay',
				'sanitize_callback' => array( self::class, 'sanitize_payment_icons' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_PAYMENT_ICONS,
			array(
				'label'       => __( 'Íconos de pago', 'shanelle' ),
				'description' => __( 'Slugs separados por comas: visa, mastercard, amex, paypal, apple_pay, google_pay.', 'shanelle' ),
				'section'     => 'shanelle_footer',
				'type'        => 'text',
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_SCROLL_TOP,
			__( 'Mostrar botón subir', 'shanelle' ),
			true
		);
	}

	/**
	 * Enqueue footer assets site-wide.
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-footer',
			self::COMPONENT_URI . '/footer.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-footer',
			self::COMPONENT_URI . '/footer.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-footer', 'type', 'module' );

		wp_localize_script(
			'shanelle-footer',
			'shanelleFooter',
			array(
				'initialState' => self::build_footer_state(),
				'i18n'         => array(
					'newsletterSuccess' => __( 'Gracias por suscribirte. Pronto estaremos en contacto.', 'shanelle' ),
					'newsletterInvalid' => __( 'Ingresa un correo electrónico válido.', 'shanelle' ),
					'newsletterSoon'    => __( 'El boletín estará disponible pronto.', 'shanelle' ),
					'menuExpand'        => __( 'Expandir menú', 'shanelle' ),
					'menuCollapse'      => __( 'Contraer menú', 'shanelle' ),
					'scrollTop'         => __( 'Volver arriba', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the footer composition.
	 */
	public static function render(): void {
		self::$state = self::build_footer_state();

		if ( ! wp_style_is( 'shanelle-footer', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/footer.php';

		self::$state = array();
	}

	/**
	 * Render footer logo markup.
	 */
	public static function render_logo(): void {
		if ( empty( self::$state['settings']['show_logo'] ) ) {
			return;
		}

		$home_url = home_url( '/' );
		$logo_id  = (int) ( self::$state['settings']['logo_id'] ?? 0 );

		if ( $logo_id <= 0 && has_custom_logo() ) {
			$logo_id = (int) get_theme_mod( 'custom_logo' );
		}
		?>
		<a class="footer__logo" href="<?php echo esc_url( $home_url ); ?>">
			<?php
			if ( $logo_id > 0 ) {
				echo wp_get_attachment_image(
					$logo_id,
					'medium',
					false,
					array(
						'class'    => 'footer__logo-image',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'alt'      => get_bloginfo( 'name' ),
					)
				);
			} else {
				echo esc_html( get_bloginfo( 'name' ) );
			}
			?>
		</a>
		<?php
	}

	/**
	 * Render brand description copy.
	 */
	public static function render_brand_description(): void {
		$description = (string) ( self::$state['settings']['brand_description'] ?? '' );

		if ( '' === $description ) {
			return;
		}
		?>
		<p class="footer__description text-muted"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Render social profile links.
	 */
	public static function render_social_links(): void {
		if ( empty( self::$state['settings']['show_social'] ) ) {
			return;
		}

		$links = is_array( self::$state['social'] ?? null ) ? self::$state['social'] : array();

		if ( empty( $links ) ) {
			return;
		}
		?>
		<ul class="footer__social" aria-label="<?php esc_attr_e( 'Redes sociales', 'shanelle' ); ?>">
			<?php foreach ( $links as $network => $url ) : ?>
				<li>
					<a
						class="footer__social-link"
						href="<?php echo esc_url( (string) $url ); ?>"
						target="_blank"
						rel="noopener noreferrer"
					>
						<span class="screen-reader-text"><?php echo esc_html( self::get_social_label( (string) $network ) ); ?></span>
						<?php self::render_icon( (string) $network ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Render contact details column (Customizer-driven).
	 */
	public static function render_contact_details(): void {
		if ( empty( self::$state['settings']['show_contact'] ) ) {
			return;
		}

		$title   = (string) ( self::$state['settings']['contact_title'] ?? '' );
		$phone   = (string) ( self::$state['settings']['contact_phone'] ?? '' );
		$email   = (string) ( self::$state['settings']['contact_email'] ?? '' );
		$address = (string) ( self::$state['settings']['contact_address'] ?? '' );
		$items   = array();

		if ( '' !== $phone ) {
			$items[] = array(
				'type'  => 'phone',
				'label' => $phone,
				'href'  => self::build_tel_href( $phone ),
			);
		}

		if ( '' !== $email ) {
			$items[] = array(
				'type'  => 'email',
				'label' => $email,
				'href'  => is_email( $email ) ? 'mailto:' . sanitize_email( $email ) : '',
			);
		}

		if ( '' !== $address ) {
			$items[] = array(
				'type'  => 'location',
				'label' => $address,
				'href'  => '',
			);
		}

		$has_social = ! empty( self::$state['settings']['show_social'] )
			&& ! empty( self::$state['social'] )
			&& is_array( self::$state['social'] );

		if ( empty( $items ) && ! $has_social && '' === $title ) {
			return;
		}
		?>
		<section class="footer__contact" aria-labelledby="<?php echo esc_attr( self::get_contact_title_id() ); ?>">
			<?php if ( '' !== $title ) : ?>
				<h2 id="<?php echo esc_attr( self::get_contact_title_id() ); ?>" class="footer__contact-title text-label">
					<?php echo esc_html( $title ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
				<ul class="footer__contact-list">
					<?php foreach ( $items as $item ) : ?>
						<li class="footer__contact-item">
							<span class="footer__contact-icon" aria-hidden="true">
								<?php self::render_icon( (string) $item['type'] ); ?>
							</span>
							<?php if ( '' !== (string) $item['href'] ) : ?>
								<a class="footer__contact-link" href="<?php echo esc_url( (string) $item['href'] ); ?>">
									<?php echo esc_html( (string) $item['label'] ); ?>
								</a>
							<?php else : ?>
								<span class="footer__contact-text"><?php echo nl2br( esc_html( (string) $item['label'] ), false ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php self::render_social_links(); ?>
		</section>
		<?php
	}

	/**
	 * Render scroll-to-top control when enabled.
	 */
	public static function render_scroll_top(): void {
		if ( empty( self::$state['settings']['show_scroll_top'] ) ) {
			return;
		}
		?>
		<button
			type="button"
			class="footer__scroll-top"
			data-shanelle-footer-scroll-top
			aria-label="<?php esc_attr_e( 'Volver arriba', 'shanelle' ); ?>"
			hidden
		>
			<span class="footer__scroll-top-icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
					<path d="M6 14l6-6 6 6" />
				</svg>
			</span>
		</button>
		<?php
	}

	/**
	 * Render newsletter signup notice (subscription plugin not wired yet).
	 */
	public static function render_newsletter(): void {
		if ( empty( self::$state['settings']['show_newsletter'] ) ) {
			return;
		}

		$title       = (string) ( self::$state['settings']['newsletter_title'] ?? '' );
		$description = (string) ( self::$state['settings']['newsletter_description'] ?? '' );
		?>
		<section class="footer__newsletter" aria-labelledby="<?php echo esc_attr( self::get_newsletter_title_id() ); ?>">
			<?php if ( '' !== $title ) : ?>
				<h2 id="<?php echo esc_attr( self::get_newsletter_title_id() ); ?>" class="footer__newsletter-title text-h3">
					<?php echo esc_html( $title ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( '' !== $description ) : ?>
				<p class="footer__newsletter-description text-muted"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>

			<form
				class="footer__newsletter-form is-disabled"
				data-shanelle-footer-newsletter
				data-newsletter-enabled="false"
				novalidate
			>
				<label class="footer__newsletter-label text-label" for="<?php echo esc_attr( self::get_newsletter_input_id() ); ?>">
					<?php esc_html_e( 'Correo electrónico', 'shanelle' ); ?>
				</label>
				<div class="footer__newsletter-row">
					<input
						type="email"
						class="footer__newsletter-input input"
						id="<?php echo esc_attr( self::get_newsletter_input_id() ); ?>"
						name="footer_newsletter_email"
						placeholder="<?php esc_attr_e( 'tu@ejemplo.com', 'shanelle' ); ?>"
						autocomplete="email"
						inputmode="email"
						disabled
						aria-disabled="true"
					/>
					<button type="submit" class="btn btn--primary footer__newsletter-submit" disabled aria-disabled="true">
						<?php esc_html_e( 'Próximamente', 'shanelle' ); ?>
					</button>
				</div>
				<p class="footer__newsletter-note text-caption text-muted">
					<?php esc_html_e( 'El boletín estará disponible pronto. Mientras tanto, síguenos en redes.', 'shanelle' ); ?>
				</p>
				<p class="footer__newsletter-message text-caption" data-shanelle-footer-newsletter-message hidden></p>
			</form>
		</section>
		<?php
	}

	/**
	 * Render footer menu columns.
	 */
	public static function render_menus(): void {
		$locations = self::get_menu_locations();
		$has_menu  = false;

		foreach ( array_keys( $locations ) as $location ) {
			if ( has_nav_menu( $location ) ) {
				$has_menu = true;
				break;
			}
		}

		if ( ! $has_menu ) {
			return;
		}
		?>
		<div class="footer__menus">
			<?php
			foreach ( $locations as $location => $title ) {
				self::render_menu_column( $location, $title );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render a single footer menu column.
	 */
	public static function render_menu_column( string $location, string $title ): void {
		if ( ! has_nav_menu( $location ) ) {
			return;
		}

		$panel_id = self::get_menu_panel_id( $location );
		?>
		<div class="footer__menu" data-shanelle-footer-menu>
			<button
				type="button"
				class="footer__menu-toggle text-label"
				data-shanelle-footer-menu-toggle
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $panel_id ); ?>"
			>
				<?php echo esc_html( $title ); ?>
			</button>

			<div class="footer__menu-panel" id="<?php echo esc_attr( $panel_id ); ?>" data-shanelle-footer-menu-panel hidden>
				<h3 class="footer__menu-title text-label"><?php echo esc_html( $title ); ?></h3>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => $location,
						'menu_class'     => 'footer__menu-list',
						'container'      => false,
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render copyright line.
	 */
	public static function render_copyright(): void {
		$text = self::format_copyright( (string) ( self::$state['settings']['copyright'] ?? '' ) );

		if ( '' === $text ) {
			return;
		}
		?>
		<p class="footer__copyright text-caption text-muted"><?php echo wp_kses_post( $text ); ?></p>
		<?php
	}

	/**
	 * Render payment method icons.
	 */
	public static function render_payment_icons(): void {
		if ( empty( self::$state['settings']['show_payment_icons'] ) ) {
			return;
		}

		$icons = is_array( self::$state['payment_icons'] ?? null ) ? self::$state['payment_icons'] : array();

		if ( empty( $icons ) ) {
			return;
		}
		?>
		<ul class="footer__payments" aria-label="<?php esc_attr_e( 'Métodos de pago aceptados', 'shanelle' ); ?>">
			<?php foreach ( $icons as $icon ) : ?>
				<li class="footer__payment-item">
					<span class="footer__payment-badge footer__payment-badge--<?php echo esc_attr( (string) $icon ); ?>" aria-hidden="true">
						<?php echo self::get_payment_mark_svg( (string) $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="screen-reader-text"><?php echo esc_html( self::get_payment_label( (string) $icon ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Return footer root ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return contact title ID.
	 */
	public static function get_contact_title_id(): string {
		return self::ROOT_ID . '-contact-title';
	}

	/**
	 * Return newsletter title ID.
	 */
	public static function get_newsletter_title_id(): string {
		return self::ROOT_ID . '-newsletter-title';
	}

	/**
	 * Return newsletter input ID.
	 */
	public static function get_newsletter_input_id(): string {
		return self::ROOT_ID . '-newsletter-email';
	}

	/**
	 * Return footer state JSON.
	 */
	public static function get_state_json(): string {
		return wp_json_encode( self::$state ) ?: '{}';
	}

	/**
	 * Build normalized footer state.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_footer_state(): array {
		$settings = self::get_settings();

		return apply_filters(
			'shanelle_footer_state',
			array(
				'settings'       => $settings,
				'social'         => self::get_social_links( $settings ),
				'payment_icons'  => self::parse_payment_icons( (string) ( $settings['payment_icons'] ?? '' ) ),
				'menu_locations' => array_keys( self::get_menu_locations() ),
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
			'shanelle_footer_settings',
			array(
				'show_logo'              => self::get_theme_mod_bool( self::MOD_SHOW_LOGO, true ),
				'logo_id'                => self::get_theme_mod_int( self::MOD_LOGO, 0 ),
				'brand_description'      => self::get_theme_mod_string(
					self::MOD_BRAND_DESCRIPTION,
					get_bloginfo( 'description', 'display' ) ?: __( 'Estilos seleccionados para cada momento.', 'shanelle' )
				),
				'show_contact'           => self::get_theme_mod_bool( self::MOD_SHOW_CONTACT, true ),
				'contact_title'          => self::get_theme_mod_string(
					self::MOD_CONTACT_TITLE,
					__( 'Datos de contacto', 'shanelle' )
				),
				'contact_phone'          => self::get_theme_mod_string( self::MOD_CONTACT_PHONE ),
				'contact_email'          => self::get_theme_mod_string( self::MOD_CONTACT_EMAIL ),
				'contact_address'        => self::get_theme_mod_string( self::MOD_CONTACT_ADDRESS ),
				'show_social'            => self::get_theme_mod_bool( self::MOD_SHOW_SOCIAL, true ),
				'social_instagram'       => self::get_theme_mod_url( self::MOD_SOCIAL_INSTAGRAM ),
				'social_tiktok'          => self::get_theme_mod_url( self::MOD_SOCIAL_TIKTOK ),
				'social_pinterest'       => self::get_theme_mod_url( self::MOD_SOCIAL_PINTEREST ),
				'social_facebook'        => self::get_theme_mod_url( self::MOD_SOCIAL_FACEBOOK ),
				'social_youtube'         => self::get_theme_mod_url( self::MOD_SOCIAL_YOUTUBE ),
				'show_newsletter'        => self::get_theme_mod_bool( self::MOD_SHOW_NEWSLETTER, false ),
				'newsletter_title'       => self::get_theme_mod_string(
					self::MOD_NEWSLETTER_TITLE,
					__( 'Únete a nuestra lista', 'shanelle' )
				),
				'newsletter_description' => self::get_theme_mod_string(
					self::MOD_NEWSLETTER_DESCRIPTION,
					__( 'Sé la primera en saber de novedades, ofertas exclusivas y ediciones de estilo.', 'shanelle' )
				),
				'copyright'              => self::get_theme_mod_string(
					self::MOD_COPYRIGHT,
					'© {year} {site_name}. ' . __( 'Todos los derechos reservados.', 'shanelle' )
				),
				'show_payment_icons'     => self::get_theme_mod_bool( self::MOD_SHOW_PAYMENT_ICONS, true ),
				'payment_icons'          => self::get_theme_mod_string(
					self::MOD_PAYMENT_ICONS,
					'visa,mastercard,amex,paypal,apple_pay'
				),
				'show_scroll_top'        => self::get_theme_mod_bool( self::MOD_SHOW_SCROLL_TOP, true ),
			)
		);
	}

	/**
	 * Sanitize textarea customizer values.
	 */
	public static function sanitize_textarea( mixed $value ): string {
		return sanitize_textarea_field( (string) $value );
	}

	/**
	 * Sanitize payment icon slugs.
	 */
	public static function sanitize_payment_icons( mixed $value ): string {
		$icons = self::parse_payment_icons( (string) $value );

		return implode( ',', $icons );
	}

	/**
	 * Sanitize checkbox customizer values.
	 */
	public static function sanitize_checkbox( mixed $value ): bool {
		return (bool) $value;
	}

	/**
	 * Output inline SVG icon markup.
	 *
	 * @param string $icon Icon slug.
	 */
	public static function render_icon( string $icon ): void {
		$icons = array(
			'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>',
			'tiktok'    => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M9 18V9l8-2v4l-4 1v6"/></svg>',
			'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 16.5 11 10l3-1-1 6 2 .5"/></svg>',
			'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M14 8h3V4h-3c-2.8 0-5 2.2-5 5v2H6v4h3v8h4v-8h3l1-4h-4V9c0-.6.4-1 1-1z"/></svg>',
			'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="3"/><path d="m11 10 4 2-4 2z"/></svg>',
			'phone'     => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6.5 3.5h3l1.5 4-2 1.5a12 12 0 0 0 6 6l1.5-2 4 1.5v3a2 2 0 0 1-2 2A15.5 15.5 0 0 1 4.5 5.5a2 2 0 0 1 2-2z"/></svg>',
			'email'     => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
			'location'  => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
		);

		if ( ! isset( $icons[ $icon ] ) ) {
			return;
		}

		echo $icons[ $icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Return accessible social network label.
	 */
	private static function get_social_label( string $network ): string {
		$labels = array(
			'instagram' => __( 'Instagram', 'shanelle' ),
			'tiktok'    => __( 'TikTok', 'shanelle' ),
			'pinterest' => __( 'Pinterest', 'shanelle' ),
			'facebook'  => __( 'Facebook', 'shanelle' ),
			'youtube'   => __( 'YouTube', 'shanelle' ),
		);

		return $labels[ $network ] ?? ucfirst( $network );
	}

	/**
	 * Return accessible payment method label.
	 */
	private static function get_payment_label( string $slug ): string {
		$labels = array(
			'visa'        => 'Visa',
			'mastercard'  => 'Mastercard',
			'amex'         => 'American Express',
			'paypal'      => 'PayPal',
			'apple_pay'   => 'Apple Pay',
			'google_pay'  => 'Google Pay',
		);

		return $labels[ $slug ] ?? str_replace( '_', ' ', ucwords( $slug, '_' ) );
	}

	/**
	 * Return muted SVG payment mark markup.
	 */
	private static function get_payment_mark_svg( string $slug ): string {
		$common = 'xmlns="http://www.w3.org/2000/svg" width="40" height="24" viewBox="0 0 40 24" role="img" focusable="false"';

		$marks = array(
			'visa'       => '<svg ' . $common . ' aria-hidden="true"><rect width="40" height="24" rx="4" fill="currentColor" opacity="0.08"/><text x="20" y="16" text-anchor="middle" font-size="8" font-weight="700" font-family="system-ui,sans-serif" fill="currentColor">VISA</text></svg>',
			'mastercard' => '<svg ' . $common . ' aria-hidden="true"><rect width="40" height="24" rx="4" fill="currentColor" opacity="0.08"/><circle cx="16" cy="12" r="6" fill="currentColor" opacity="0.35"/><circle cx="24" cy="12" r="6" fill="currentColor" opacity="0.55"/></svg>',
			'amex'       => '<svg ' . $common . ' aria-hidden="true"><rect width="40" height="24" rx="4" fill="currentColor" opacity="0.08"/><text x="20" y="16" text-anchor="middle" font-size="7" font-weight="700" font-family="system-ui,sans-serif" fill="currentColor">AMEX</text></svg>',
			'paypal'     => '<svg ' . $common . ' aria-hidden="true"><rect width="40" height="24" rx="4" fill="currentColor" opacity="0.08"/><text x="20" y="16" text-anchor="middle" font-size="7" font-weight="700" font-family="system-ui,sans-serif" fill="currentColor">PayPal</text></svg>',
			'apple_pay'  => '<svg ' . $common . ' aria-hidden="true"><rect width="40" height="24" rx="4" fill="currentColor" opacity="0.08"/><text x="20" y="16" text-anchor="middle" font-size="6.5" font-weight="700" font-family="system-ui,sans-serif" fill="currentColor">Apple Pay</text></svg>',
			'google_pay' => '<svg ' . $common . ' aria-hidden="true"><rect width="40" height="24" rx="4" fill="currentColor" opacity="0.08"/><text x="20" y="16" text-anchor="middle" font-size="6.5" font-weight="700" font-family="system-ui,sans-serif" fill="currentColor">G Pay</text></svg>',
		);

		return $marks[ $slug ] ?? '<svg ' . $common . ' aria-hidden="true"><rect width="40" height="24" rx="4" fill="currentColor" opacity="0.08"/></svg>';
	}

	/**
	 * Parse payment icon slugs from comma-separated string.
	 *
	 * @return array<int, string>
	 */
	private static function parse_payment_icons( string $value ): array {
		$allowed = array( 'visa', 'mastercard', 'amex', 'paypal', 'apple_pay', 'google_pay' );
		$parts   = preg_split( '/\s*,\s*/', strtolower( $value ) ) ?: array();
		$icons   = array();

		foreach ( $parts as $part ) {
			$slug = sanitize_key( (string) $part );

			if ( in_array( $slug, $allowed, true ) ) {
				$icons[] = $slug;
			}
		}

		return array_values( array_unique( $icons ) );
	}

	/**
	 * Build social link map from settings.
	 *
	 * @param array<string, mixed> $settings Footer settings.
	 * @return array<string, string>
	 */
	private static function get_social_links( array $settings ): array {
		$map = array(
			'instagram' => (string) ( $settings['social_instagram'] ?? '' ),
			'tiktok'    => (string) ( $settings['social_tiktok'] ?? '' ),
			'pinterest' => (string) ( $settings['social_pinterest'] ?? '' ),
			'facebook'  => (string) ( $settings['social_facebook'] ?? '' ),
			'youtube'   => (string) ( $settings['social_youtube'] ?? '' ),
		);

		return array_filter(
			$map,
			static function ( string $url ): bool {
				return '' !== $url;
			}
		);
	}

	/**
	 * Build a tel: href from a display phone string.
	 */
	private static function build_tel_href( string $phone ): string {
		$digits = preg_replace( '/[^\d+]/', '', $phone );

		if ( ! is_string( $digits ) || '' === $digits ) {
			return '';
		}

		return 'tel:' . $digits;
	}

	/**
	 * Replace copyright placeholders.
	 */
	private static function format_copyright( string $text ): string {
		$replacements = array(
			'{year}'      => gmdate( 'Y' ),
			'{site_name}' => get_bloginfo( 'name' ),
		);

		return strtr( $text, $replacements );
	}

	/**
	 * Return menu panel ID for a location.
	 */
	private static function get_menu_panel_id( string $location ): string {
		return self::ROOT_ID . '-menu-' . sanitize_html_class( $location );
	}

	/**
	 * Register a checkbox customizer control.
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
				'section' => 'shanelle_footer',
				'type'    => 'checkbox',
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
		string $default,
		string $description = ''
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
				'label'       => $label,
				'description' => $description,
				'section'     => 'shanelle_footer',
				'type'        => 'text',
			)
		);
	}

	/**
	 * Register a URL customizer control.
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
				'section' => 'shanelle_footer',
				'type'    => 'url',
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

	/**
	 * Read a sanitized integer theme mod.
	 */
	private static function get_theme_mod_int( string $key, int $default ): int {
		$value = get_theme_mod( $key, $default );

		return absint( $value );
	}

	/**
	 * Read a sanitized URL theme mod.
	 */
	private static function get_theme_mod_url( string $key ): string {
		$value = get_theme_mod( $key, '' );

		return is_string( $value ) ? esc_url( $value ) : '';
	}
}
