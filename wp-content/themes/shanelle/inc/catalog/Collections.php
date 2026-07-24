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
			'name'                       => _x( 'Colecciones', 'taxonomy general name', 'shanelle' ),
			'singular_name'              => _x( 'Colección', 'taxonomy singular name', 'shanelle' ),
			'search_items'               => __( 'Buscar colecciones', 'shanelle' ),
			'popular_items'              => __( 'Colecciones populares', 'shanelle' ),
			'all_items'                  => __( 'Todas las colecciones', 'shanelle' ),
			'parent_item'                => __( 'Colección superior', 'shanelle' ),
			'parent_item_colon'          => __( 'Colección superior:', 'shanelle' ),
			'edit_item'                  => __( 'Editar colección', 'shanelle' ),
			'view_item'                  => __( 'Ver colección', 'shanelle' ),
			'update_item'                => __( 'Actualizar colección', 'shanelle' ),
			'add_new_item'               => __( 'Agregar nueva colección', 'shanelle' ),
			'new_item_name'              => __( 'Nombre de la nueva colección', 'shanelle' ),
			'separate_items_with_commas' => __( 'Separa las colecciones con comas', 'shanelle' ),
			'add_or_remove_items'        => __( 'Agregar o quitar colecciones', 'shanelle' ),
			'choose_from_most_used'      => __( 'Elegir entre las colecciones más usadas', 'shanelle' ),
			'not_found'                  => __( 'No se encontraron colecciones.', 'shanelle' ),
			'no_terms'                   => __( 'Sin colecciones', 'shanelle' ),
			'menu_name'                  => __( 'Colecciones', 'shanelle' ),
			'back_to_items'              => __( '&larr; Volver a Colecciones', 'shanelle' ),
		);

		$args = array(
			'labels'            => $labels,
			'description'       => __( 'Colecciones de productos de temporada, destacadas y de campaña.', 'shanelle' ),
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
