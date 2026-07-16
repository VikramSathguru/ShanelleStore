<?php
/**
 * Site header Customizer and presentation helpers.
 *
 * Markup remains in template-parts/components/site-header.php.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Header chrome settings — no commerce business logic.
 */
final class SiteHeader {

	private const MOD_SHOW_PROMO = 'shanelle_header_show_promo';

	private const MOD_PROMO_1_EMPHASIS = 'shanelle_header_promo_1_emphasis';

	private const MOD_PROMO_1_TEXT = 'shanelle_header_promo_1_text';

	private const MOD_PROMO_2_EMPHASIS = 'shanelle_header_promo_2_emphasis';

	private const MOD_PROMO_2_TEXT = 'shanelle_header_promo_2_text';

	private const MOD_CONTACT_URL = 'shanelle_header_contact_url';

	/**
	 * Boot header Customizer hooks.
	 */
	public static function boot(): void {
		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
	}

	/**
	 * Register Theme Customizer settings for the header.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'shanelle_header',
			array(
				'title'       => __( 'Encabezado', 'shanelle' ),
				'description' => __( 'Configura la barra de confianza y el enlace de atención al cliente.', 'shanelle' ),
				'priority'    => 119,
			)
		);

		$wp_customize->add_setting(
			self::MOD_SHOW_PROMO,
			array(
				'default'           => true,
				'sanitize_callback' => array( self::class, 'sanitize_checkbox' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_SHOW_PROMO,
			array(
				'label'   => __( 'Mostrar barra de confianza', 'shanelle' ),
				'section' => 'shanelle_header',
				'type'    => 'checkbox',
			)
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_PROMO_1_EMPHASIS,
			__( 'Promo 1 — énfasis', 'shanelle' ),
			__( 'Envío gratis', 'shanelle' )
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_PROMO_1_TEXT,
			__( 'Promo 1 — texto', 'shanelle' ),
			__( 'en pedidos calificados', 'shanelle' )
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_PROMO_2_EMPHASIS,
			__( 'Promo 2 — énfasis', 'shanelle' ),
			__( 'Devoluciones fáciles', 'shanelle' )
		);

		self::register_text_control(
			$wp_customize,
			self::MOD_PROMO_2_TEXT,
			__( 'Promo 2 — texto', 'shanelle' ),
			__( 'aplican T&C', 'shanelle' )
		);

		$wp_customize->add_setting(
			self::MOD_CONTACT_URL,
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_CONTACT_URL,
			array(
				'label'       => __( 'URL de atención al cliente', 'shanelle' ),
				'description' => __( 'Si está vacío, se usa la página Contacto/Contact si existe.', 'shanelle' ),
				'section'     => 'shanelle_header',
				'type'        => 'url',
			)
		);
	}

	/**
	 * Whether the promo trust strip should render.
	 */
	public static function show_promo(): bool {
		return (bool) get_theme_mod( self::MOD_SHOW_PROMO, true );
	}

	/**
	 * Return configured promo items (omits empty rows).
	 *
	 * @return array<int, array{emphasis: string, text: string}>
	 */
	public static function get_promo_items(): array {
		$items = array(
			array(
				'emphasis' => self::get_theme_mod_string( self::MOD_PROMO_1_EMPHASIS, __( 'Envío gratis', 'shanelle' ) ),
				'text'     => self::get_theme_mod_string( self::MOD_PROMO_1_TEXT, __( 'en pedidos calificados', 'shanelle' ) ),
			),
			array(
				'emphasis' => self::get_theme_mod_string( self::MOD_PROMO_2_EMPHASIS, __( 'Devoluciones fáciles', 'shanelle' ) ),
				'text'     => self::get_theme_mod_string( self::MOD_PROMO_2_TEXT, __( 'aplican T&C', 'shanelle' ) ),
			),
		);

		return array_values(
			array_filter(
				$items,
				static function ( array $item ): bool {
					return '' !== $item['emphasis'] || '' !== $item['text'];
				}
			)
		);
	}

	/**
	 * Resolve customer-service / contact URL (Customizer or WP page).
	 */
	public static function get_contact_url(): string {
		$custom = get_theme_mod( self::MOD_CONTACT_URL, '' );

		if ( is_string( $custom ) && '' !== $custom ) {
			return esc_url( $custom );
		}

		foreach ( array( 'contacto', 'contact', 'atencion-al-cliente' ) as $slug ) {
			$page = get_page_by_path( $slug );

			if ( $page instanceof \WP_Post ) {
				$url = get_permalink( $page );

				if ( is_string( $url ) && '' !== $url ) {
					return esc_url( $url );
				}
			}
		}

		return '';
	}

	/**
	 * Sanitize checkbox customizer values.
	 */
	public static function sanitize_checkbox( mixed $value ): bool {
		return (bool) $value;
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
				'section' => 'shanelle_header',
				'type'    => 'text',
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
}
