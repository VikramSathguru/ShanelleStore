<?php
/**
 * WooCommerce integration via hooks.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Declare WooCommerce theme support.
 */
function shanelle_woocommerce_setup(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 400,
		'single_image_width'    => 600,
		'product_grid'            => array(
			'default_rows'    => 4,
			'min_rows'        => 1,
			'max_rows'        => 8,
			'default_columns' => 4,
			'min_columns'     => 2,
			'max_columns'     => 6,
		),
	) );

	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'shanelle_woocommerce_setup' );

/**
 * Bail early when WooCommerce is inactive.
 *
 * @return bool
 */
function shanelle_is_woocommerce_active(): bool {
	return class_exists( 'WooCommerce' );
}

/**
 * Register WooCommerce front-end hooks.
 */
function shanelle_register_woocommerce_hooks(): void {
	if ( ! shanelle_is_woocommerce_active() ) {
		return;
	}

	add_filter( 'loop_shop_per_page', 'shanelle_products_per_page' );
	add_filter( 'loop_shop_columns', 'shanelle_loop_columns' );

	add_action( 'woocommerce_before_main_content', 'shanelle_before_main_content', 5 );
	add_action( 'woocommerce_after_main_content', 'shanelle_after_main_content', 50 );

	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

	remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

	add_action( 'woocommerce_before_shop_loop_item', 'shanelle_product_card_start', 5 );
	add_filter( 'woocommerce_add_to_cart_fragments', 'shanelle_cart_count_fragment' );
}
add_action( 'woocommerce_init', 'shanelle_register_woocommerce_hooks' );

/**
 * Adjust products per page.
 *
 * @return int
 */
function shanelle_products_per_page(): int {
	return 24;
}

/**
 * Adjust shop loop columns.
 *
 * @return int
 */
function shanelle_loop_columns(): int {
	return 4;
}

/**
 * Wrap shop content with theme layout classes.
 */
function shanelle_before_main_content(): void {
	echo '<main id="primary" class="site-main shop-main">';
	echo '<div class="container shop-main__inner">';
}

/**
 * Close shop content wrapper.
 */
function shanelle_after_main_content(): void {
	echo '</div></main>';
}

/**
 * Render custom product card in loops.
 */
function shanelle_product_card_start(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	shanelle_component( 'product-card', array( 'product' => $product ) );
}

/**
 * Update cart count fragment for AJAX add-to-cart.
 *
 * @param array<string, mixed> $fragments Cart fragments.
 * @return array<string, mixed>
 */
function shanelle_cart_count_fragment( array $fragments ): array {
	ob_start();
	shanelle_component( 'cart-count' );
	$fragments['.header-cart__count'] = ob_get_clean();

	return $fragments;
}
