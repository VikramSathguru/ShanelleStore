<?php
/**
 * Footer customer-service column component.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Third footer column: WordPress-managed customer service menu.
 *
 * Presentation only. Menu items come exclusively from {@see wp_nav_menu()}.
 */
final class FooterCustomerService {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/footer-customer-service';

	private const COMPONENT_URI = SHANELLE_URI . '/components/footer-customer-service';

	/**
	 * Theme menu location slug.
	 */
	public const MENU_LOCATION = 'footer_customer_service';

	/**
	 * Boot menu registration and assets.
	 */
	public static function boot(): void {
		add_action( 'after_setup_theme', array( self::class, 'register_menu' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Register the Footer Customer Service menu location.
	 */
	public static function register_menu(): void {
		register_nav_menus(
			array(
				self::MENU_LOCATION => __( 'Footer Customer Service', 'shanelle' ),
			)
		);
	}

	/**
	 * Enqueue customer-service column styles (shares visual system with FooterLinks).
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-footer-customer-service',
			self::COMPONENT_URI . '/footer-customer-service.css',
			array( 'shanelle-main', 'shanelle-footer-links' ),
			SHANELLE_VERSION
		);
	}

	/**
	 * Render the customer service column when a menu is assigned.
	 */
	public static function render(): void {
		if ( ! self::has_menu() ) {
			return;
		}

		if ( ! wp_style_is( 'shanelle-footer-customer-service', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/footer-customer-service.php';
	}

	/**
	 * Whether a customer-service menu is assigned.
	 */
	public static function has_menu(): bool {
		return has_nav_menu( self::MENU_LOCATION );
	}

	/**
	 * Return the visible section title (Spanish LATAM storefront).
	 */
	public static function get_title(): string {
		return __( 'Atención al cliente', 'shanelle' );
	}

	/**
	 * Return nav aria-label required for the customer-service landmark.
	 */
	public static function get_nav_aria_label(): string {
		return 'Footer Customer Service';
	}

	/**
	 * Output the WordPress menu list (no fallback pages).
	 */
	public static function render_menu(): void {
		if ( ! self::has_menu() ) {
			return;
		}

		wp_nav_menu(
			array(
				'theme_location' => self::MENU_LOCATION,
				'container'      => false,
				'menu_class'     => 'footer-links__list footer-customer-service__list',
				'menu_id'        => 'shanelle-footer-customer-service',
				'depth'          => 1,
				'fallback_cb'    => false,
				'item_spacing'   => 'discard',
			)
		);
	}
}
