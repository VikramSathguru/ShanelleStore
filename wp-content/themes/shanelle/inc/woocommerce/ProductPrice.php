<?php
/**
 * Shared WooCommerce product price formatting.
 *
 * @package Shanelle\WooCommerce
 */

declare(strict_types=1);

namespace Shanelle\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes product price data for cards, summaries, and badges.
 */
final class ProductPrice {

	/**
	 * Build normalized price data for a product.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_display_data( \WC_Product $product ): array {
		$data = array(
			'has_price'        => false,
			'is_on_sale'       => false,
			'is_range'         => false,
			'current_html'     => '',
			'regular_html'     => '',
			'savings_html'     => '',
			'savings_raw'      => 0.0,
			'savings_percent'  => 0,
			'badge_label'      => '',
			'compact_html'     => '',
		);

		if ( ! $product->is_purchasable() && '' === $product->get_price() && ! $product->is_type( 'variable' ) ) {
			return $data;
		}

		$data['has_price']    = true;
		$data['is_on_sale']   = $product->is_on_sale();
		$data['compact_html'] = $product->get_price_html();

		if ( $product->is_type( 'variable' ) ) {
			$variable = self::get_variable_price_data( $product );
			$data     = array_merge( $data, $variable );

			return apply_filters( 'shanelle_product_price_data', $data, $product );
		}

		$regular = (float) $product->get_regular_price();
		$current = (float) $product->get_price();

		if ( $regular <= 0 && $current <= 0 ) {
			$data['has_price'] = false;

			return apply_filters( 'shanelle_product_price_data', $data, $product );
		}

		$display_regular = $regular > 0
			? (float) wc_get_price_to_display( $product, array( 'price' => $regular ) )
			: 0.0;
		$display_current = $current > 0
			? (float) wc_get_price_to_display( $product, array( 'price' => $current ) )
			: $display_regular;

		$data['current_html'] = wc_price( $display_current );
		$data['regular_html'] = $display_regular > $display_current
			? wc_price( $display_regular )
			: '';

		if ( $data['is_on_sale'] && $display_regular > $display_current ) {
			$data['savings_raw']     = $display_regular - $display_current;
			$data['savings_html']    = self::format_savings( $data['savings_raw'] );
			$data['savings_percent'] = self::calculate_percent( $display_regular, $display_current );
			$data['badge_label']     = self::format_percent_badge( $data['savings_percent'] );
		}

		return apply_filters( 'shanelle_product_price_data', $data, $product );
	}

	/**
	 * Return sale badge label with optional percentage.
	 */
	public static function get_sale_badge_label( \WC_Product $product ): string {
		if ( ! $product->is_on_sale() ) {
			return '';
		}

		$data = self::get_display_data( $product );

		if ( '' !== $data['badge_label'] ) {
			return $data['badge_label'];
		}

		return __( 'Oferta', 'shanelle' );
	}

	/**
	 * Return modifier classes for compact price wrappers.
	 *
	 * @return array<int, string>
	 */
	public static function get_compact_classes( \WC_Product $product, string $base_class = 'product-card__price' ): array {
		$classes = array( $base_class, 'text-price-sm' );

		if ( $product->is_on_sale() ) {
			$classes[] = $base_class . '--on-sale';
		}

		if ( ! $product->is_in_stock() ) {
			$classes[] = $base_class . '--sold-out';
		}

		return $classes;
	}

	/**
	 * Build variable product price data from min variation values.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_variable_price_data( \WC_Product $product ): array {
		$min_price         = (float) $product->get_variation_price( 'min', true );
		$max_price         = (float) $product->get_variation_price( 'max', true );
		$min_regular       = (float) $product->get_variation_regular_price( 'min', true );
		$max_regular       = (float) $product->get_variation_regular_price( 'max', true );
		$is_range          = $min_price !== $max_price || $min_regular !== $max_regular;
		$display_current   = $min_price;
		$display_regular   = $min_regular;
		$is_on_sale        = $min_regular > $min_price && $min_price > 0;

		$data = array(
			'is_range'        => $is_range,
			'current_html'    => $is_range
				? sprintf(
					/* translators: %s: minimum price */
					__( 'Desde %s', 'shanelle' ),
					wc_price( $display_current )
				)
				: wc_price( $display_current ),
			'regular_html'    => '',
			'savings_html'    => '',
			'savings_raw'     => 0.0,
			'savings_percent' => 0,
			'badge_label'     => '',
		);

		if ( $is_on_sale && $display_regular > $display_current ) {
			$data['regular_html'] = $is_range
				? sprintf(
					/* translators: %s: minimum regular price */
					__( 'Desde %s', 'shanelle' ),
					wc_price( $display_regular )
				)
				: wc_price( $display_regular );
			$data['savings_raw']     = $display_regular - $display_current;
			$data['savings_html']    = self::format_savings( $data['savings_raw'] );
			$data['savings_percent'] = self::calculate_percent( $display_regular, $display_current );
			$data['badge_label']     = self::format_percent_badge( $data['savings_percent'] );
		}

		return $data;
	}

	/**
	 * Calculate discount percentage.
	 */
	private static function calculate_percent( float $regular, float $sale ): int {
		if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
			return 0;
		}

		return (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
	}

	/**
	 * Format savings amount for display.
	 */
	private static function format_savings( float $amount ): string {
		if ( $amount <= 0 ) {
			return '';
		}

		return sprintf(
			/* translators: %s: formatted savings amount */
			__( 'Ahorra %s', 'shanelle' ),
			wc_price( $amount )
		);
	}

	/**
	 * Format percentage badge label.
	 */
	private static function format_percent_badge( int $percent ): string {
		if ( $percent <= 0 ) {
			return __( 'Oferta', 'shanelle' );
		}

		/* translators: %d: discount percentage */
		return sprintf( __( '-%d%%', 'shanelle' ), $percent );
	}
}
