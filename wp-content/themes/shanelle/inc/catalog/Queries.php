<?php
/**
 * Catalog collection query helpers.
 *
 * @package Shanelle\Catalog
 */

declare(strict_types=1);

namespace Shanelle\Catalog;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end collection query and normalization helpers.
 */
final class Queries {

	/**
	 * Determine whether a collection term is within its active date range.
	 */
	public static function is_collection_active( int $term_id ): bool {
		if ( $term_id <= 0 ) {
			return false;
		}

		$today = current_time( 'Y-m-d' );
		$start = (string) Helpers::get_term_meta( $term_id, Helpers::META_START, '' );
		$end   = (string) Helpers::get_term_meta( $term_id, Helpers::META_END, '' );

		if ( '' !== $start && $today < $start ) {
			return false;
		}

		if ( '' !== $end && $today > $end ) {
			return false;
		}

		return true;
	}

	/**
	 * Return normalized collection cards for storefront listings.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_collections( array $args = array() ): array {
		if ( ! taxonomy_exists( Helpers::TAXONOMY ) ) {
			return array();
		}

		$defaults = array(
			'hide_empty'   => true,
			'active_only'  => true,
			'leaf_only'    => true,
			'parent'       => null,
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'taxonomy'   => Helpers::TAXONOMY,
			'hide_empty' => (bool) $args['hide_empty'],
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		if ( null !== $args['parent'] ) {
			$query_args['parent'] = (int) $args['parent'];
		}

		$terms = get_terms( $query_args );

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		$collections = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			if ( ! empty( $args['leaf_only'] ) && self::term_has_children( $term->term_id ) ) {
				continue;
			}

			if ( ! empty( $args['active_only'] ) && ! self::is_collection_active( $term->term_id ) ) {
				continue;
			}

			$collections[] = self::normalize_term( $term );
		}

		usort(
			$collections,
			static function ( array $left, array $right ): int {
				$order = ( $left['display_order'] ?? 0 ) <=> ( $right['display_order'] ?? 0 );

				if ( 0 !== $order ) {
					return $order;
				}

				return strcasecmp( (string) ( $left['name'] ?? '' ), (string) ( $right['name'] ?? '' ) );
			}
		);

		return apply_filters( 'shanelle_catalog_collections', $collections, $args );
	}

	/**
	 * Return a normalized collection by term ID.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get_collection( int $term_id ): ?array {
		if ( $term_id <= 0 || ! taxonomy_exists( Helpers::TAXONOMY ) ) {
			return null;
		}

		$term = get_term( $term_id, Helpers::TAXONOMY );

		if ( ! $term instanceof \WP_Term || is_wp_error( $term ) ) {
			return null;
		}

		return self::normalize_term( $term );
	}

	/**
	 * Return child collections for a parent term.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_child_collections( int $parent_id, bool $active_only = true ): array {
		return self::get_collections(
			array(
				'parent'      => $parent_id,
				'leaf_only'   => false,
				'active_only' => $active_only,
				'hide_empty'  => false,
			)
		);
	}

	/**
	 * Group collections by collection type for index sections.
	 *
	 * @param array<int, array<string, mixed>> $collections Normalized collections.
	 * @return array<int, array<string, mixed>>
	 */
	public static function group_collections_by_type( array $collections ): array {
		$types  = Helpers::get_collection_types();
		$groups = array();

		foreach ( $collections as $collection ) {
			if ( ! is_array( $collection ) ) {
				continue;
			}

			$type = (string) ( $collection['type'] ?? Helpers::TYPE_FEATURED );

			if ( ! isset( $groups[ $type ] ) ) {
				$groups[ $type ] = array(
					'type'  => $type,
					'label' => (string) ( $types[ $type ] ?? $type ),
					'items' => array(),
				);
			}

			$groups[ $type ]['items'][] = $collection;
		}

		$ordered = array();

		foreach ( array_keys( $types ) as $type ) {
			if ( isset( $groups[ $type ] ) ) {
				$ordered[] = $groups[ $type ];
			}
		}

		return apply_filters( 'shanelle_catalog_collection_groups', $ordered, $collections );
	}

	/**
	 * Normalize a collection term for component rendering.
	 *
	 * @return array<string, mixed>
	 */
	public static function normalize_term( \WP_Term $term ): array {
		$type     = Helpers::sanitize_collection_type(
			Helpers::get_term_meta( $term->term_id, Helpers::META_TYPE, Helpers::TYPE_FEATURED )
		);
		$types    = Helpers::get_collection_types();
		$hero_id  = (int) Helpers::get_term_meta( $term->term_id, Helpers::META_HERO, 0 );
		$link     = get_term_link( $term );
		$link     = is_wp_error( $link ) ? '' : (string) $link;

		return array(
			'id'            => (int) $term->term_id,
			'name'          => $term->name,
			'slug'          => $term->slug,
			'url'           => $link,
			'description'   => term_description( $term->term_id, Helpers::TAXONOMY ),
			'type'          => $type,
			'type_label'    => (string) ( $types[ $type ] ?? $type ),
			'hero_id'       => $hero_id,
			'product_count' => (int) $term->count,
			'display_order' => (int) Helpers::get_term_meta( $term->term_id, Helpers::META_ORDER, 0 ),
			'is_active'     => self::is_collection_active( $term->term_id ),
			'parent_id'     => (int) $term->parent,
		);
	}

	/**
	 * Determine whether a term has child collections.
	 */
	private static function term_has_children( int $term_id ): bool {
		$children = get_terms(
			array(
				'taxonomy'   => Helpers::TAXONOMY,
				'parent'     => $term_id,
				'hide_empty' => false,
				'fields'     => 'ids',
				'number'     => 1,
			)
		);

		return ! is_wp_error( $children ) && ! empty( $children );
	}
}
