<?php
/**
 * Search controller — AJAX, REST, and WooCommerce product query orchestration.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\Catalog\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Handles live search requests and normalized search payloads.
 */
final class SearchController {

	public const AJAX_ACTION = 'shanelle_search_suggest';

	public const REST_NAMESPACE = 'shanelle/v1';

	public const NONCE_ACTION = 'shanelle_search';

	private const DEFAULT_PRODUCT_LIMIT = 6;

	private const DEFAULT_TERM_LIMIT = 4;

	private const MIN_QUERY_LENGTH = 2;

	/**
	 * Boot search controller hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( self::class, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( self::class, 'handle_ajax' ) );
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
	}

	/**
	 * Register REST route for PWA and headless hydration.
	 */
	public static function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_rest' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'query' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Handle admin-ajax live search requests.
	 */
	public static function handle_ajax(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$query = sanitize_text_field( wp_unslash( (string) ( $_REQUEST['query'] ?? '' ) ) );

		wp_send_json_success( self::build_response( $query ) );
	}

	/**
	 * Handle REST live search requests.
	 *
	 * @param \WP_REST_Request $request REST request.
	 */
	public static function handle_rest( \WP_REST_Request $request ): \WP_REST_Response {
		$query = sanitize_text_field( (string) $request->get_param( 'query' ) );

		return rest_ensure_response( self::build_response( $query ) );
	}

	/**
	 * Build normalized search response payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_response( string $query ): array {
		$query = trim( $query );
		$state = SearchOverlay::get_settings();

		if ( mb_strlen( $query ) < (int) ( $state['min_query_length'] ?? self::MIN_QUERY_LENGTH ) ) {
			return self::build_idle_payload( $query );
		}

		$products    = self::query_products( $query, (int) ( $state['product_limit'] ?? self::DEFAULT_PRODUCT_LIMIT ) );
		$categories  = self::query_categories( $query, (int) ( $state['term_limit'] ?? self::DEFAULT_TERM_LIMIT ) );
		$collections = self::query_collections( $query, (int) ( $state['term_limit'] ?? self::DEFAULT_TERM_LIMIT ) );

		$results_args = array(
			'query'       => $query,
			'products'    => $products,
			'categories'  => $categories,
			'collections' => $collections,
		);

		ob_start();
		SearchResults::render( $results_args );
		$html = ob_get_clean() ?: '';

		$payload = array(
			'status'      => self::has_matches( $products, $categories, $collections ) ? 'results' : 'empty',
			'query'       => $query,
			'html'        => $html,
			'resultsUrl'  => self::get_results_url( $query ),
			'counts'      => array(
				'products'    => count( $products ),
				'categories'  => count( $categories ),
				'collections' => count( $collections ),
			),
			'items'       => SearchResults::build_items_payload( $results_args ),
		);

		return apply_filters( 'shanelle_search_response', $payload, $query );
	}

	/**
	 * Return full search results page URL.
	 */
	public static function get_results_url( string $query ): string {
		return add_query_arg(
			array(
				's'          => $query,
				'post_type'  => 'product',
			),
			home_url( '/' )
		);
	}

	/**
	 * Highlight matched query text in escaped copy.
	 */
	public static function highlight_query( string $text, string $query ): string {
		$text  = esc_html( $text );
		$query = trim( $query );

		if ( '' === $query || '' === $text ) {
			return $text;
		}

		$pattern = '/' . preg_quote( $query, '/' ) . '/iu';

		return preg_replace( $pattern, '<mark class="search-results__highlight">$0</mark>', $text ) ?: $text;
	}

	/**
	 * Return minimum query length for live search.
	 */
	public static function get_min_query_length(): int {
		$settings = SearchOverlay::get_settings();

		return max( 1, (int) ( $settings['min_query_length'] ?? self::MIN_QUERY_LENGTH ) );
	}

	/**
	 * Query WooCommerce products by search term.
	 *
	 * @return array<int, \WC_Product>
	 */
	public static function query_products( string $query, int $limit = self::DEFAULT_PRODUCT_LIMIT ): array {
		$args = apply_filters(
			'shanelle_search_product_query_args',
			array(
				'limit'   => max( 1, $limit ),
				'status'  => 'publish',
				'orderby' => 'relevance',
				'order'   => 'DESC',
				'return'  => 'objects',
				's'       => $query,
			),
			$query
		);

		$products = wc_get_products( $args );

		return array_values(
			array_filter(
				$products,
				static function ( $product ): bool {
					return $product instanceof \WC_Product;
				}
			)
		);
	}

	/**
	 * Query matching product categories.
	 *
	 * @return array<int, \WP_Term>
	 */
	public static function query_categories( string $query, int $limit = self::DEFAULT_TERM_LIMIT ): array {
		$terms = get_terms(
			apply_filters(
				'shanelle_search_category_query_args',
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'number'     => max( 1, $limit ),
					'search'     => $query,
					'orderby'    => 'count',
					'order'      => 'DESC',
				),
				$query
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$terms,
				static function ( $term ): bool {
					return $term instanceof \WP_Term;
				}
			)
		);
	}

	/**
	 * Query matching product collections.
	 *
	 * @return array<int, \WP_Term>
	 */
	public static function query_collections( string $query, int $limit = self::DEFAULT_TERM_LIMIT ): array {
		$terms = get_terms(
			apply_filters(
				'shanelle_search_collection_query_args',
				array(
					'taxonomy'   => Helpers::TAXONOMY,
					'hide_empty' => true,
					'number'     => max( 1, $limit ),
					'search'     => $query,
					'orderby'    => 'count',
					'order'      => 'DESC',
				),
				$query
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$terms,
				static function ( $term ): bool {
					return $term instanceof \WP_Term;
				}
			)
		);
	}

	/**
	 * Build idle payload for short or empty queries.
	 *
	 * @return array<string, mixed>
	 */
	private static function build_idle_payload( string $query ): array {
		ob_start();
		SearchResults::render_idle();
		$html = ob_get_clean() ?: '';

		return array(
			'status'     => 'idle',
			'query'      => $query,
			'html'       => $html,
			'resultsUrl' => self::get_results_url( $query ),
			'counts'     => array(
				'products'    => 0,
				'categories'  => 0,
				'collections' => 0,
			),
			'items'      => array(),
		);
	}

	/**
	 * @param array<int, \WC_Product> $products Product results.
	 * @param array<int, \WP_Term>    $categories Category results.
	 * @param array<int, \WP_Term>    $collections Collection results.
	 */
	private static function has_matches( array $products, array $categories, array $collections ): bool {
		return ! empty( $products ) || ! empty( $categories ) || ! empty( $collections );
	}
}
