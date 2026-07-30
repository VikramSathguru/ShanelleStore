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

	private const MOD_PROMO_SPEED = 'shanelle_header_promo_speed';

	private const MOD_CONTACT_URL = 'shanelle_header_contact_url';

	/**
	 * Maximum configurable promotion slots in the Customizer.
	 */
	public const PROMO_SLOT_COUNT = 6;

	/**
	 * Default marquee speed in pixels per second.
	 */
	public const PROMO_SPEED_DEFAULT = 40;

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
				'description' => __( 'Configura el banner de promociones (estilo cinta rotativa) y el enlace de atención al cliente.', 'shanelle' ),
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
				'label'       => __( 'Mostrar banner de promociones', 'shanelle' ),
				'description' => __( 'Si las promociones no caben en una línea, se desplazan en bucle (como una cinta de estadio).', 'shanelle' ),
				'section'     => 'shanelle_header',
				'type'        => 'checkbox',
			)
		);

		$wp_customize->add_setting(
			self::MOD_PROMO_SPEED,
			array(
				'default'           => self::PROMO_SPEED_DEFAULT,
				'sanitize_callback' => array( self::class, 'sanitize_speed' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_PROMO_SPEED,
			array(
				'label'       => __( 'Velocidad del desplazamiento (px/s)', 'shanelle' ),
				'description' => __( 'Solo aplica cuando el contenido no cabe en pantalla. Rango: 20–80.', 'shanelle' ),
				'section'     => 'shanelle_header',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 20,
					'max'  => 80,
					'step' => 5,
				),
			)
		);

		$defaults = self::get_default_promo_slots();

		for ( $index = 1; $index <= self::PROMO_SLOT_COUNT; $index++ ) {
			/* translators: %d: promotion slot number. */
			$prefix  = sprintf( __( 'Promoción %d', 'shanelle' ), $index );
			$default = $defaults[ $index ] ?? array(
				'emphasis' => '',
				'text'     => '',
				'url'      => '',
			);

			self::register_text_control(
				$wp_customize,
				self::promo_mod( $index, 'emphasis' ),
				$prefix . ' — ' . __( 'énfasis', 'shanelle' ),
				(string) $default['emphasis']
			);

			self::register_text_control(
				$wp_customize,
				self::promo_mod( $index, 'text' ),
				$prefix . ' — ' . __( 'texto', 'shanelle' ),
				(string) $default['text']
			);

			self::register_url_control(
				$wp_customize,
				self::promo_mod( $index, 'url' ),
				$prefix . ' — ' . __( 'enlace (opcional)', 'shanelle' )
			);
		}

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
	 * Whether the promo banner should render.
	 */
	public static function show_promo(): bool {
		return (bool) get_theme_mod( self::MOD_SHOW_PROMO, true );
	}

	/**
	 * Marquee speed in pixels per second.
	 */
	public static function get_promo_speed(): int {
		return self::sanitize_speed( get_theme_mod( self::MOD_PROMO_SPEED, self::PROMO_SPEED_DEFAULT ) );
	}

	/**
	 * Return configured promo items (omits empty rows).
	 *
	 * @return array<int, array{emphasis: string, text: string, url: string}>
	 */
	public static function get_promo_items(): array {
		$defaults = self::get_default_promo_slots();
		$items    = array();

		for ( $index = 1; $index <= self::PROMO_SLOT_COUNT; $index++ ) {
			$default = $defaults[ $index ] ?? array(
				'emphasis' => '',
				'text'     => '',
				'url'      => '',
			);

			$emphasis = self::get_theme_mod_string( self::promo_mod( $index, 'emphasis' ), (string) $default['emphasis'] );
			$text     = self::get_theme_mod_string( self::promo_mod( $index, 'text' ), (string) $default['text'] );
			$url      = self::get_theme_mod_url( self::promo_mod( $index, 'url' ) );

			// Legacy fallback for slots 1–2 when new mods were never saved.
			if ( 1 === $index && '' === $emphasis && '' === $text ) {
				$emphasis = self::get_theme_mod_string( 'shanelle_header_promo_1_emphasis', (string) $default['emphasis'] );
				$text     = self::get_theme_mod_string( 'shanelle_header_promo_1_text', (string) $default['text'] );
			}

			if ( 2 === $index && '' === $emphasis && '' === $text ) {
				$emphasis = self::get_theme_mod_string( 'shanelle_header_promo_2_emphasis', (string) $default['emphasis'] );
				$text     = self::get_theme_mod_string( 'shanelle_header_promo_2_text', (string) $default['text'] );
			}

			if ( '' === $emphasis && '' === $text ) {
				continue;
			}

			$items[] = array(
				'emphasis' => $emphasis,
				'text'     => $text,
				'url'      => $url,
			);
		}

		/**
		 * Filter header promotion banner items.
		 *
		 * @param array<int, array{emphasis: string, text: string, url: string}> $items Promo rows.
		 */
		$filtered = apply_filters( 'shanelle_header_promo_items', $items );

		if ( ! is_array( $filtered ) ) {
			return $items;
		}

		$normalized = array();

		foreach ( $filtered as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$emphasis = isset( $item['emphasis'] ) ? sanitize_text_field( (string) $item['emphasis'] ) : '';
			$text     = isset( $item['text'] ) ? sanitize_text_field( (string) $item['text'] ) : '';
			$url      = isset( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '';

			if ( '' === $emphasis && '' === $text ) {
				continue;
			}

			$normalized[] = array(
				'emphasis' => $emphasis,
				'text'     => $text,
				'url'      => $url,
			);
		}

		return $normalized;
	}

	/**
	 * Default starter promotions (Spanish).
	 *
	 * @return array<int, array{emphasis: string, text: string, url: string}>
	 */
	public static function get_default_promo_slots(): array {
		return array(
			1 => array(
				'emphasis' => __( 'Envío gratis', 'shanelle' ),
				'text'     => __( 'en pedidos calificados', 'shanelle' ),
				'url'      => '',
			),
			2 => array(
				'emphasis' => __( 'Devoluciones fáciles', 'shanelle' ),
				'text'     => __( 'dentro de 30 días', 'shanelle' ),
				'url'      => '',
			),
			3 => array(
				'emphasis' => __( 'Novedades semanales', 'shanelle' ),
				'text'     => __( 'descubre lo último en moda', 'shanelle' ),
				'url'      => '',
			),
			4 => array(
				'emphasis' => __( 'Atención personalizada', 'shanelle' ),
				'text'     => __( 'estamos para ayudarte', 'shanelle' ),
				'url'      => '',
			),
			5 => array(
				'emphasis' => '',
				'text'     => '',
				'url'      => '',
			),
			6 => array(
				'emphasis' => '',
				'text'     => '',
				'url'      => '',
			),
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

		if ( class_exists( InfoPage::class ) ) {
			$info_url = InfoPage::get_page_url( 'contact' );

			if ( '' !== $info_url ) {
				return esc_url( $info_url );
			}
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
	 * Sanitize marquee speed.
	 */
	public static function sanitize_speed( mixed $value ): int {
		$speed = absint( $value );

		if ( $speed < 20 ) {
			return 20;
		}

		if ( $speed > 80 ) {
			return 80;
		}

		return $speed > 0 ? $speed : self::PROMO_SPEED_DEFAULT;
	}

	/**
	 * Build a promo mod key for a slot and field.
	 */
	private static function promo_mod( int $index, string $field ): string {
		return 'shanelle_header_promo_' . $index . '_' . $field;
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
				'section' => 'shanelle_header',
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
	 * Read a sanitized URL theme mod.
	 */
	private static function get_theme_mod_url( string $key ): string {
		$value = get_theme_mod( $key, '' );

		return is_string( $value ) ? esc_url_raw( $value ) : '';
	}
}
