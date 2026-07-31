<?php
/**
 * Catalog module bootstrap.
 *
 * @package Shanelle\Catalog
 */

declare(strict_types=1);

namespace Shanelle\Catalog;

defined( 'ABSPATH' ) || exit;

/**
 * Initializes catalog registration, admin UI, and seeding.
 */
final class Catalog {

	/**
	 * Boot the catalog module.
	 */
	public static function boot(): void {
		add_action( 'after_setup_theme', array( self::class, 'init' ), 20 );
		add_action( 'init', array( Seeder::class, 'maybe_run_pending_seed' ), 20 );
		add_action( 'after_switch_theme', array( Seeder::class, 'queue_seed' ) );
	}

	/**
	 * Initialize catalog services when WooCommerce is available.
	 */
	public static function init(): void {
		if ( ! Helpers::is_woocommerce_active() ) {
			return;
		}

		Collections::register();
		Admin::register();
	}
}
