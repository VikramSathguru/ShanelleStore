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
	add_filter( 'woocommerce_catalog_orderby', 'shanelle_catalog_orderby_labels' );
	add_filter( 'option_woocommerce_enable_myaccount_registration', 'shanelle_enable_myaccount_registration' );
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

	\Shanelle\Components\ProductCard::render( $product );
}

/**
 * Ensure customer registration is available on My Account.
 *
 * @param mixed $value Stored option value.
 * @return string
 */
function shanelle_enable_myaccount_registration( $value ): string {
	unset( $value );

	return 'yes';
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

/**
 * Customize catalog sort labels for shop archive UX.
 *
 * @param array<string, string> $options Orderby options.
 * @return array<string, string>
 */
function shanelle_catalog_orderby_labels( array $options ): array {
	if ( isset( $options['menu_order'] ) ) {
		$options['menu_order'] = __( 'Recomendados', 'shanelle' );
	}

	if ( isset( $options['popularity'] ) ) {
		$options['popularity'] = __( 'Más populares', 'shanelle' );
	}

	if ( isset( $options['date'] ) ) {
		$options['date'] = __( 'Novedades', 'shanelle' );
	}

	return $options;
}

/**
 * Default shipping information for product detail accordion.
 *
 * @param string      $content Default content.
 * @param WC_Product  $product Product object.
 * @return string
 */
function shanelle_default_product_shipping_information( string $content, WC_Product $product ): string {
	unset( $product );

	if ( '' !== trim( $content ) ) {
		return $content;
	}

	ob_start();
	?>
	<p><?php esc_html_e( 'Enviamos a todo Nicaragua con cuidado. Los pedidos se empacan en 1–2 días hábiles.', 'shanelle' ); ?></p>
		<ul>
		<li><?php esc_html_e( 'Entrega estándar: 3–5 días hábiles', 'shanelle' ); ?></li>
		<li><?php esc_html_e( 'Entrega express disponible al pagar', 'shanelle' ); ?></li>
		<li><?php esc_html_e( 'Envío gratis en pedidos calificados', 'shanelle' ); ?></li>
	</ul>
	<?php
	return (string) ob_get_clean();
}
add_filter( 'shanelle_product_information_shipping', 'shanelle_default_product_shipping_information', 10, 2 );

/**
 * Default returns information for product detail accordion.
 *
 * @param string      $content Default content.
 * @param WC_Product  $product Product object.
 * @return string
 */
function shanelle_default_product_returns_information( string $content, WC_Product $product ): string {
	unset( $product );

	if ( '' !== trim( $content ) ) {
		return $content;
	}

	ob_start();
	?>
	<p><?php esc_html_e( 'Queremos que ames cada prenda. Si algo no es lo ideal, puedes devolver artículos sin usar dentro de 30 días.', 'shanelle' ); ?></p>
	<ul>
		<li><?php esc_html_e( 'Los artículos deben estar sin usar y con etiquetas', 'shanelle' ); ?></li>
		<li><?php esc_html_e( 'Reembolsos procesados en 5–7 días hábiles', 'shanelle' ); ?></li>
		<li><?php esc_html_e( 'Cambios fáciles de talla o color cuando estén disponibles', 'shanelle' ); ?></li>
	</ul>
	<?php
	return (string) ob_get_clean();
}
add_filter( 'shanelle_product_information_returns', 'shanelle_default_product_returns_information', 10, 2 );

/**
 * Default size guide copy for product detail accordion.
 *
 * @param string     $content Default content.
 * @param WC_Product $product Product object.
 * @return string
 */
function shanelle_default_product_size_guide( string $content, WC_Product $product ): string {
	unset( $product );

	if ( '' !== trim( $content ) ) {
		return $content;
	}

	ob_start();
	?>
	<p><?php esc_html_e( 'Usa esta guía como referencia. Las medidas pueden variar ligeramente según el diseño y el material.', 'shanelle' ); ?></p>
	<ul>
		<li><?php esc_html_e( 'Compara con una prenda similar que ya te quede bien', 'shanelle' ); ?></li>
		<li><?php esc_html_e( 'Si estás entre dos tallas, elige la mayor para un ajuste más cómodo', 'shanelle' ); ?></li>
		<li><?php esc_html_e( 'Revisa la descripción del producto para notas de ajuste (ajustado, regular, oversized)', 'shanelle' ); ?></li>
	</ul>
	<?php
	return (string) ob_get_clean();
}
add_filter( 'shanelle_product_information_size_guide', 'shanelle_default_product_size_guide', 10, 2 );

/**
 * Default care instructions for product detail accordion.
 *
 * @param string     $content Default content.
 * @param WC_Product $product Product object.
 * @return string
 */
function shanelle_default_product_care_instructions( string $content, WC_Product $product ): string {
	unset( $product );

	if ( '' !== trim( $content ) ) {
		return $content;
	}

	ob_start();
	?>
	<p><?php esc_html_e( 'Cuida tus prendas para que duren más tiempo hermosas.', 'shanelle' ); ?></p>
	<ul>
		<li><?php esc_html_e( 'Lavar a mano o en ciclo delicado con agua fría', 'shanelle' ); ?></li>
		<li><?php esc_html_e( 'No usar blanqueador', 'shanelle' ); ?></li>
		<li><?php esc_html_e( 'Secar a la sombra; planchar a temperatura baja si es necesario', 'shanelle' ); ?></li>
	</ul>
	<?php
	return (string) ob_get_clean();
}
add_filter( 'shanelle_product_information_care_instructions', 'shanelle_default_product_care_instructions', 10, 2 );
