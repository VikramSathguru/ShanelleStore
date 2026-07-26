<?php
/**
 * Footer useful-links column component.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Second footer column: WordPress-managed useful links menu.
 *
 * Presentation only. Menu items come exclusively from {@see wp_nav_menu()}.
 */
final class FooterLinks {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/footer-links';

	private const COMPONENT_URI = SHANELLE_URI . '/components/footer-links';

	/**
	 * Theme menu location slug.
	 */
	public const MENU_LOCATION = 'footer_useful_links';

	/**
	 * Legacy location previously used for this column (BC).
	 */
	private const LEGACY_MENU_LOCATION = 'footer_shop';

	/**
	 * Boot menu registration and assets.
	 */
	public static function boot(): void {
		add_action( 'after_setup_theme', array( self::class, 'register_menu' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Register the Footer Useful Links menu location.
	 */
	public static function register_menu(): void {
		register_nav_menus(
			array(
				self::MENU_LOCATION => __( 'Footer Useful Links', 'shanelle' ),
			)
		);
	}

	/**
	 * Enqueue footer links styles site-wide.
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-footer-links',
			self::COMPONENT_URI . '/footer-links.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);
	}

	/**
	 * Render the useful links column when a menu is assigned.
	 */
	public static function render(): void {
		$location = self::resolve_menu_location();

		if ( null === $location ) {
			return;
		}

		if ( ! wp_style_is( 'shanelle-footer-links', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		$title_id = 'shanelle-footer-links-title';

		require self::COMPONENT_DIR . '/footer-links.php';
	}

	/**
	 * Whether a useful-links menu is assigned.
	 */
	public static function has_menu(): bool {
		return null !== self::resolve_menu_location();
	}

	/**
	 * Return the active theme location slug, or null when none assigned.
	 *
	 * Prefers {@see MENU_LOCATION}; falls back to legacy `footer_shop` if still assigned.
	 */
	public static function resolve_menu_location(): ?string {
		if ( has_nav_menu( self::MENU_LOCATION ) ) {
			return self::MENU_LOCATION;
		}

		if ( has_nav_menu( self::LEGACY_MENU_LOCATION ) ) {
			return self::LEGACY_MENU_LOCATION;
		}

		return null;
	}

	/**
	 * Return the visible section title (Spanish LATAM storefront).
	 */
	public static function get_title(): string {
		return __( 'Enlaces útiles', 'shanelle' );
	}

	/**
	 * Return nav aria-label required for the useful-links landmark.
	 */
	public static function get_nav_aria_label(): string {
		return 'Footer Useful Links';
	}

	/**
	 * Output the WordPress menu list (no fallback pages).
	 */
	public static function render_menu(): void {
		$location = self::resolve_menu_location();

		if ( null === $location ) {
			return;
		}

		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'footer-links__list',
				'menu_id'        => 'shanelle-footer-useful-links',
				'depth'          => 1,
				'fallback_cb'    => false,
				'item_spacing'   => 'discard',
			)
		);
	}
}
