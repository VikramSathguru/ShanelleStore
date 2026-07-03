<?php
/**
 * Default collection seeder.
 *
 * @package Shanelle\Catalog
 */

declare(strict_types=1);

namespace Shanelle\Catalog;

defined( 'ABSPATH' ) || exit;

/**
 * Seeds default collection terms on first theme activation.
 */
final class Seeder {

	/**
	 * Default collection tree.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function get_seed_map(): array {
		return array(
			'Seasonal' => array(
				'type'         => Helpers::TYPE_SEASONAL,
				'display_order'=> 10,
				'children'     => array(
					'Spring' => array(
						'type'          => Helpers::TYPE_SEASONAL,
						'display_order' => 10,
					),
					'Summer' => array(
						'type'          => Helpers::TYPE_SEASONAL,
						'display_order' => 20,
					),
					'Autumn' => array(
						'type'          => Helpers::TYPE_SEASONAL,
						'display_order' => 30,
					),
					'Winter' => array(
						'type'          => Helpers::TYPE_SEASONAL,
						'display_order' => 40,
					),
				),
			),
			'Featured' => array(
				'type'          => Helpers::TYPE_FEATURED,
				'display_order' => 20,
				'children'      => array(
					'New Arrivals' => array(
						'type'          => Helpers::TYPE_FEATURED,
						'display_order' => 10,
					),
					'Best Sellers' => array(
						'type'          => Helpers::TYPE_FEATURED,
						'display_order' => 20,
					),
					'Trending'     => array(
						'type'          => Helpers::TYPE_FEATURED,
						'display_order' => 30,
					),
					"Editor's Picks" => array(
						'type'          => Helpers::TYPE_FEATURED,
						'display_order' => 40,
					),
				),
			),
			'Sale'     => array(
				'type'          => Helpers::TYPE_CAMPAIGN,
				'display_order' => 30,
			),
		);
	}

	/**
	 * Queue seeding after theme activation.
	 */
	public static function queue_seed(): void {
		if ( get_option( Helpers::SEED_OPTION_KEY ) ) {
			return;
		}

		update_option( Helpers::PENDING_SEED_OPTION_KEY, '1', false );
	}

	/**
	 * Run pending seed once taxonomy exists.
	 */
	public static function maybe_run_pending_seed(): void {
		if ( get_option( Helpers::SEED_OPTION_KEY ) ) {
			return;
		}

		if ( ! get_option( Helpers::PENDING_SEED_OPTION_KEY ) ) {
			return;
		}

		if ( ! taxonomy_exists( Helpers::TAXONOMY ) ) {
			return;
		}

		self::seed();

		delete_option( Helpers::PENDING_SEED_OPTION_KEY );
		update_option( Helpers::SEED_OPTION_KEY, Helpers::SEED_VERSION, false );
	}

	/**
	 * Insert default collection terms and meta.
	 */
	public static function seed(): void {
		foreach ( self::get_seed_map() as $parent_name => $parent_config ) {
			$parent_id = self::insert_term( $parent_name, 0 );

			if ( 0 === $parent_id ) {
				continue;
			}

			self::apply_term_meta( $parent_id, $parent_config );

			if ( empty( $parent_config['children'] ) || ! is_array( $parent_config['children'] ) ) {
				continue;
			}

			foreach ( $parent_config['children'] as $child_name => $child_config ) {
				$child_id = self::insert_term( $child_name, $parent_id );

				if ( 0 === $child_id ) {
					continue;
				}

				self::apply_term_meta( $child_id, $child_config );
			}
		}
	}

	/**
	 * Insert or resolve a collection term.
	 */
	private static function insert_term( string $name, int $parent_id ): int {
		$existing = term_exists( $name, Helpers::TAXONOMY, $parent_id );

		if ( is_array( $existing ) && isset( $existing['term_id'] ) ) {
			return (int) $existing['term_id'];
		}

		if ( is_int( $existing ) ) {
			return $existing;
		}

		$result = wp_insert_term(
			$name,
			Helpers::TAXONOMY,
			array(
				'parent' => $parent_id,
				'slug'   => sanitize_title( $name ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return 0;
		}

		return (int) $result['term_id'];
	}

	/**
	 * Apply default meta to a seeded term.
	 *
	 * @param array<string, mixed> $config Term configuration.
	 */
	private static function apply_term_meta( int $term_id, array $config ): void {
		$type = isset( $config['type'] ) ? Helpers::sanitize_collection_type( $config['type'] ) : Helpers::TYPE_FEATURED;
		$order = isset( $config['display_order'] ) ? Helpers::sanitize_display_order( $config['display_order'] ) : 0;

		Helpers::update_term_meta( $term_id, Helpers::META_TYPE, $type );
		Helpers::update_term_meta( $term_id, Helpers::META_ORDER, $order );
		Helpers::update_term_meta( $term_id, Helpers::META_START, '' );
		Helpers::update_term_meta( $term_id, Helpers::META_END, '' );
		Helpers::update_term_meta( $term_id, Helpers::META_HERO, 0 );
	}
}
