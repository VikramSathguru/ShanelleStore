<?php
/**
 * PSR-4 style autoloader for Shanelle theme classes.
 *
 * No Composer dependency — Hostinger-friendly.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $class ): void {
		$prefixes = array(
			'Shanelle\\Components\\'   => SHANELLE_DIR . '/inc/components/',
			'Shanelle\\Catalog\\'      => SHANELLE_DIR . '/inc/catalog/',
			'Shanelle\\Integrations\\' => SHANELLE_DIR . '/inc/integrations/',
			'Shanelle\\WooCommerce\\'  => SHANELLE_DIR . '/inc/woocommerce/',
			'Shanelle\\Setup\\'        => SHANELLE_DIR . '/inc/setup/',
		);

		foreach ( $prefixes as $prefix => $base_dir ) {
			$prefix_length = strlen( $prefix );

			if ( strncmp( $prefix, $class, $prefix_length ) !== 0 ) {
				continue;
			}

			$relative = str_replace( '\\', '/', substr( $class, $prefix_length ) );
			$file     = $base_dir . $relative . '.php';

			if ( is_readable( $file ) ) {
				require_once $file;
			}

			return;
		}
	}
);
