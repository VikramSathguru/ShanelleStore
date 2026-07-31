<?php
/**
 * Thin integration adapter bootstrap.
 *
 * Adapters live under this directory (or a dedicated plugin). They may hook
 * `shanelle_*` filters, WC actions, and `shanelle:*` DOM events — never embed
 * payment/pixel SDKs or secrets in view templates.
 *
 * @package Shanelle\Integrations
 */

declare(strict_types=1);

namespace Shanelle\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Loads optional integration adapters when present and eligible.
 */
final class Integrations {

	/**
	 * Relative adapter class basenames under this directory (without .php).
	 *
	 * Add new adapters here once implemented. Prefer plugins when they already
	 * cover the capability (see docs/PLUGIN_FIRST_ARCHITECTURE.md).
	 *
	 * @var array<int, string>
	 */
	private const ADAPTERS = array(
		// 'MetaPixel',
		// 'TikTokPixel',
		// 'CargoMobil',
	);

	/**
	 * Boot the integrations layer.
	 */
	public static function boot(): void {
		foreach ( self::ADAPTERS as $basename ) {
			self::boot_adapter( $basename );
		}

		/**
		 * Fires after Shanelle integration adapters have been considered.
		 *
		 * External plugins may attach here instead of editing theme composers.
		 */
		do_action( 'shanelle_integrations_boot' );
	}

	/**
	 * Load and boot a single adapter class if it opts in.
	 */
	private static function boot_adapter( string $basename ): void {
		$basename = preg_replace( '/[^A-Za-z0-9_]/', '', $basename ) ?? '';

		if ( '' === $basename ) {
			return;
		}

		$file = __DIR__ . '/' . $basename . '.php';

		if ( ! is_readable( $file ) ) {
			return;
		}

		require_once $file;

		$class = __NAMESPACE__ . '\\' . $basename;

		if ( ! class_exists( $class ) || ! is_callable( array( $class, 'should_boot' ) ) || ! is_callable( array( $class, 'boot' ) ) ) {
			return;
		}

		if ( ! (bool) $class::should_boot() ) {
			return;
		}

		$class::boot();
	}
}
