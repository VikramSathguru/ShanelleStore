<?php
/**
 * Footer policies legal links component.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Footer policies: WordPress-managed legal / policies menu in the bottom bar.
 *
 * Presentation only. Menu items come exclusively from {@see wp_nav_menu()}.
 * Renders as a compact, pipe-separated wrapping list under copyright — not a column.
 */
final class FooterPolicies {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/footer-policies';

	private const COMPONENT_URI = SHANELLE_URI . '/components/footer-policies';

	/**
	 * Theme menu location slug.
	 */
	public const MENU_LOCATION = 'footer_policies';

	/**
	 * Legacy location previously used for this column (BC).
	 */
	private const LEGACY_MENU_LOCATION = 'footer_legal';

	/**
	 * Boot menu registration and assets.
	 */
	public static function boot(): void {
		add_action( 'after_setup_theme', array( self::class, 'register_menu' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Register the Footer Policies menu location.
	 */
	public static function register_menu(): void {
		register_nav_menus(
			array(
				self::MENU_LOCATION => __( 'Footer Policies', 'shanelle' ),
			)
		);
	}

	/**
	 * Enqueue policies legal-links styles.
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-footer-policies',
			self::COMPONENT_URI . '/footer-policies.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);
	}

	/**
	 * Render the policies legal links when a menu is assigned.
	 */
	public static function render(): void {
		$location = self::resolve_menu_location();

		if ( null === $location ) {
			return;
		}

		if ( ! wp_style_is( 'shanelle-footer-policies', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		require self::COMPONENT_DIR . '/footer-policies.php';
	}

	/**
	 * Whether a policies menu is assigned.
	 */
	public static function has_menu(): bool {
		return null !== self::resolve_menu_location();
	}

	/**
	 * Return the active theme location slug, or null when none assigned.
	 *
	 * Prefers {@see MENU_LOCATION}; falls back to legacy `footer_legal` if still assigned.
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
	 * Return a human title for admin / docs (not rendered in the bottom bar).
	 */
	public static function get_title(): string {
		return __( 'Políticas', 'shanelle' );
	}

	/**
	 * Return nav aria-label required for the policies landmark.
	 */
	public static function get_nav_aria_label(): string {
		return 'Footer Policies';
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
				'menu_class'     => 'footer-policies__list',
				'menu_id'        => 'shanelle-footer-policies',
				'depth'          => 1,
				'fallback_cb'    => false,
				'item_spacing'   => 'discard',
			)
		);
	}
}
