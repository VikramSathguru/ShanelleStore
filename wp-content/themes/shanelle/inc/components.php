<?php
/**
 * Reusable component helpers.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Render a reusable template component.
 *
 * @param string               $name Component slug (template-parts/components/{name}.php).
 * @param array<string, mixed> $args Arguments passed to the template.
 */
function shanelle_component( string $name, array $args = array() ): void {
	get_template_part( 'template-parts/components/' . $name, null, $args );
}

/**
 * Render the product card component.
 *
 * @param \WC_Product          $product Product instance.
 * @param array<string, mixed> $args    Optional render arguments.
 */
function shanelle_product_card( WC_Product $product, array $args = array() ): void {
	\Shanelle\Components\ProductCard::render( $product, $args );
}

/**
 * Render the product grid component.
 *
 * @param \WP_Query|array<string, mixed>|null $query Query source.
 * @param array<string, mixed>                $args  Grid configuration.
 */
function shanelle_product_grid( $query = null, array $args = array() ): void {
	\Shanelle\Components\ProductGrid::render( $query, $args );
}

/**
 * Render the shop archive component.
 */
function shanelle_shop_archive(): void {
	\Shanelle\Components\ShopArchive::render();
}

/**
 * Render the product gallery component.
 *
 * @param \WC_Product          $product Product instance.
 * @param array<string, mixed> $args    Optional render arguments.
 */
function shanelle_product_gallery( WC_Product $product, array $args = array() ): void {
	\Shanelle\Components\ProductGallery::render( $product, $args );
}

/**
 * Render the product summary component.
 *
 * @param \WC_Product          $product Product instance.
 * @param array<string, mixed> $args    Optional render arguments.
 */
function shanelle_product_summary( WC_Product $product, array $args = array() ): void {
	\Shanelle\Components\ProductSummary::render( $product, $args );
}

/**
 * Render the product variation selector component.
 *
 * @param \WC_Product          $product Product instance.
 * @param array<string, mixed> $args    Optional render arguments.
 */
function shanelle_product_variations( WC_Product $product, array $args = array() ): void {
	\Shanelle\Components\ProductVariations::render( $product, $args );
}

/**
 * Render the product purchase panel component.
 *
 * @param \WC_Product          $product Product instance.
 * @param array<string, mixed> $args    Optional render arguments.
 */
function shanelle_product_purchase( WC_Product $product, array $args = array() ): void {
	\Shanelle\Components\ProductPurchase::render( $product, $args );
}

/**
 * Render the product detail page composition.
 *
 * @param array<string, mixed> $args Optional render arguments.
 */
function shanelle_product_detail( array $args = array() ): void {
	\Shanelle\Components\ProductDetail::render( $args );
}

/**
 * Render the product information accordion component.
 *
 * @param \WC_Product          $product Product instance.
 * @param array<string, mixed> $args    Optional render arguments.
 */
function shanelle_product_information( WC_Product $product, array $args = array() ): void {
	\Shanelle\Components\ProductInformation::render( $product, $args );
}

/**
 * Render the related products component.
 *
 * @param \WC_Product          $product Product instance.
 * @param array<string, mixed> $args    Optional render arguments.
 */
function shanelle_product_related( WC_Product $product, array $args = array() ): void {
	\Shanelle\Components\ProductRelated::render( $product, $args );
}

/**
 * Render the mini cart drawer component.
 */
function shanelle_mini_cart(): void {
	\Shanelle\Components\MiniCart::render();
}

/**
 * Render the homepage hero banner component.
 *
 * @param array<string, mixed> $args Optional render arguments.
 */
function shanelle_hero_banner( array $args = array() ): void {
	\Shanelle\Components\HeroBanner::render( $args );
}

/**
 * Render the homepage category navigation component.
 *
 * @param array<string, mixed> $args Optional render arguments.
 */
function shanelle_category_navigation( array $args = array() ): void {
	\Shanelle\Components\CategoryNavigation::render( $args );
}

/**
 * Render the homepage page composition.
 */
function shanelle_homepage(): void {
	\Shanelle\Components\Homepage::render();
}

/**
 * Render a responsive image with lazy loading.
 *
 * @param int                  $attachment_id Attachment ID.
 * @param string               $size          Image size slug.
 * @param array<string, mixed> $attr          Additional attributes.
 */
function shanelle_responsive_image( int $attachment_id, string $size = 'full', array $attr = array() ): void {
	if ( $attachment_id <= 0 ) {
		return;
	}

	$defaults = array(
		'loading'       => 'lazy',
		'decoding'      => 'async',
		'fetchpriority' => 'auto',
	);

	$attr = wp_parse_args( $attr, $defaults );

	echo wp_get_attachment_image( $attachment_id, $size, false, $attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get formatted product sale badge text.
 *
 * @param mixed $product Product object.
 * @return string Empty string when not on sale.
 */
function shanelle_get_sale_badge( $product ): string {
	if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
		return '';
	}

	return \Shanelle\WooCommerce\ProductPrice::get_sale_badge_label( $product );
}
