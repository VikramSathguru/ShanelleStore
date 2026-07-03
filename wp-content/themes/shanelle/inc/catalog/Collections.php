<?php
/**
 * Product collection taxonomy registration.
 *
 * @package Shanelle\Catalog
 */

declare(strict_types=1);

namespace Shanelle\Catalog;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the product_collection taxonomy for WooCommerce products.
 */
final class Collections {

	/**
	 * Hook taxonomy registration.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_taxonomy' ), 10 );
	}

	/**
	 * Register hierarchical product_collection taxonomy.
	 */
	public static function register_taxonomy(): void {
		$labels = array(
			'name'                       => _x( 'Collections', 'taxonomy general name', 'shanelle' ),
			'singular_name'              => _x( 'Collection', 'taxonomy singular name', 'shanelle' ),
			'search_items'               => __( 'Search Collections', 'shanelle' ),
			'popular_items'              => __( 'Popular Collections', 'shanelle' ),
			'all_items'                  => __( 'All Collections', 'shanelle' ),
			'parent_item'                => __( 'Parent Collection', 'shanelle' ),
			'parent_item_colon'          => __( 'Parent Collection:', 'shanelle' ),
			'edit_item'                  => __( 'Edit Collection', 'shanelle' ),
			'view_item'                  => __( 'View Collection', 'shanelle' ),
			'update_item'                => __( 'Update Collection', 'shanelle' ),
			'add_new_item'               => __( 'Add New Collection', 'shanelle' ),
			'new_item_name'              => __( 'New Collection Name', 'shanelle' ),
			'separate_items_with_commas' => __( 'Separate collections with commas', 'shanelle' ),
			'add_or_remove_items'        => __( 'Add or remove collections', 'shanelle' ),
			'choose_from_most_used'      => __( 'Choose from the most used collections', 'shanelle' ),
			'not_found'                  => __( 'No collections found.', 'shanelle' ),
			'no_terms'                   => __( 'No collections', 'shanelle' ),
			'menu_name'                  => __( 'Collections', 'shanelle' ),
			'back_to_items'              => __( '&larr; Back to Collections', 'shanelle' ),
		);

		$args = array(
			'labels'            => $labels,
			'description'       => __( 'Seasonal, featured, and campaign product collections.', 'shanelle' ),
			'public'            => true,
			'publicly_queryable'=> true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'         => 'collection',
				'with_front'   => false,
				'hierarchical' => true,
			),
			'query_var'         => true,
			'capabilities'      => array(
				'manage_terms' => 'manage_product_terms',
				'edit_terms'   => 'manage_product_terms',
				'delete_terms' => 'manage_product_terms',
				'assign_terms' => 'edit_products',
			),
		);

		register_taxonomy( Helpers::TAXONOMY, array( 'product' ), $args );
	}
}
