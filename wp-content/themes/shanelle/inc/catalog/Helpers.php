<?php
/**
 * Catalog helper utilities and shared constants.
 *
 * @package Shanelle\Catalog
 */

declare(strict_types=1);

namespace Shanelle\Catalog;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for the catalog module.
 */
final class Helpers {

	public const TAXONOMY = 'product_collection';

	public const SEED_OPTION_KEY = 'shanelle_catalog_seeded';

	public const SEED_VERSION = '1.0.0';

	public const PENDING_SEED_OPTION_KEY = 'shanelle_catalog_pending_seed';

	public const SETTINGS_GROUP = 'shanelle_collections';

	public const META_TYPE = 'collection_type';

	public const META_START = 'collection_start';

	public const META_END = 'collection_end';

	public const META_HERO = 'collection_hero_id';

	public const META_ORDER = 'collection_display_order';

	public const TYPE_SEASONAL = 'seasonal';

	public const TYPE_FEATURED = 'featured';

	public const TYPE_CAMPAIGN = 'campaign';

	/**
	 * Determine whether WooCommerce is active.
	 */
	public static function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Return registered collection type choices.
	 *
	 * @return array<string, string>
	 */
	public static function get_collection_types(): array {
		return array(
			self::TYPE_SEASONAL  => __( 'Seasonal Collection', 'shanelle' ),
			self::TYPE_FEATURED  => __( 'Featured Collection', 'shanelle' ),
			self::TYPE_CAMPAIGN  => __( 'Campaign Collection', 'shanelle' ),
		);
	}

	/**
	 * Return the private term meta key.
	 */
	public static function meta_key( string $key ): string {
		return '_' . $key;
	}

	/**
	 * Sanitize collection type value.
	 */
	public static function sanitize_collection_type( mixed $value ): string {
		$value = sanitize_key( (string) $value );

		return array_key_exists( $value, self::get_collection_types() ) ? $value : self::TYPE_FEATURED;
	}

	/**
	 * Sanitize Y-m-d date value.
	 */
	public static function sanitize_date( mixed $value ): string {
		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		$date = \DateTimeImmutable::createFromFormat( 'Y-m-d', $value );

		if ( false === $date || $date->format( 'Y-m-d' ) !== $value ) {
			return '';
		}

		return $value;
	}

	/**
	 * Sanitize attachment ID used for hero image.
	 */
	public static function sanitize_hero_image_id( mixed $value ): int {
		$attachment_id = absint( $value );

		if ( 0 === $attachment_id ) {
			return 0;
		}

		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			return 0;
		}

		return $attachment_id;
	}

	/**
	 * Sanitize display order value.
	 */
	public static function sanitize_display_order( mixed $value ): int {
		$order = (int) $value;

		return max( 0, min( 9999, $order ) );
	}

	/**
	 * Retrieve collection term meta with fallback.
	 *
	 * @return mixed
	 */
	public static function get_term_meta( int $term_id, string $key, mixed $default = '' ) {
		$value = get_term_meta( $term_id, self::meta_key( $key ), true );

		if ( '' === $value || false === $value ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Persist collection term meta.
	 */
	public static function update_term_meta( int $term_id, string $key, mixed $value ): void {
		update_term_meta( $term_id, self::meta_key( $key ), $value );
	}
}
