<?php
/**
 * Related products component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\Catalog\Helpers as CatalogHelpers;

defined( 'ABSPATH' ) || exit;

/**
 * Scored related product recommendations for product detail pages.
 */
final class ProductRelated {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/product-related';

	private const COMPONENT_URI = SHANELLE_URI . '/components/product-related';

	private const DEFAULT_LIMIT = 8;

	private const DEFAULT_CANDIDATE_POOL = 120;

	/**
	 * Active source product.
	 */
	private static ?\WC_Product $product = null;

	/**
	 * Active render arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Resolved recommendation product IDs.
	 *
	 * @var array<int, int>
	 */
	private static array $recommended_ids = array();

	/**
	 * Recommendation scores keyed by product ID.
	 *
	 * @var array<int, int>
	 */
	private static array $scores = array();

	/**
	 * Cached product matching contexts.
	 *
	 * @var array<int, array<string, array<int, int>>>
	 */
	private static array $context_cache = array();

	/**
	 * Boot related products hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp', array( self::class, 'disable_default_related_products' ), 20 );
	}

	/**
	 * Remove WooCommerce default related products output.
	 */
	public static function disable_default_related_products(): void {
		if ( ! is_product() || ! shanelle_is_woocommerce_active() ) {
			return;
		}

		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		remove_action( 'woocommerce_output_related_products', 'woocommerce_output_related_products' );
	}

	/**
	 * Enqueue related products assets.
	 */
	public static function enqueue_assets(): void {
		if ( ! shanelle_is_woocommerce_active() || ! is_product() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue component assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-product-related',
			self::COMPONENT_URI . '/product-related.css',
			array( 'shanelle-main', 'shanelle-product-grid' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-product-related',
			self::COMPONENT_URI . '/product-related.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-product-related', 'type', 'module' );
	}

	/**
	 * Render related products for a product.
	 *
	 * @param \WC_Product          $product Source product.
	 * @param array<string, mixed> $args    Render arguments.
	 */
	public static function render( \WC_Product $product, array $args = array() ): void {
		self::$product = $product;
		self::$args    = self::parse_args( $args );

		$limit = self::get_limit( $product );
		self::$recommended_ids = self::get_recommended_product_ids( $product, $limit );

		if ( empty( self::$recommended_ids ) ) {
			self::reset_context();
			return;
		}

		if ( ! wp_style_is( 'shanelle-product-related', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/product-related.php';

		self::reset_context();
	}

	/**
	 * Render the related products grid via ProductGrid.
	 */
	public static function render_grid(): void {
		$query = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'post__in'       => self::$recommended_ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count( self::$recommended_ids ),
				'no_found_rows'  => true,
			)
		);

		ProductGrid::render(
			$query,
			array(
				'grid_id'         => 'related-' . self::get_product()->get_id(),
				'pagination_mode' => 'none',
				'card_args'       => (array) self::$args['card_args'],
			)
		);
	}

	/**
	 * Return component root ID.
	 */
	public static function get_root_id(): string {
		return 'product-related-' . self::get_product()->get_id();
	}

	/**
	 * Return heading element ID.
	 */
	public static function get_heading_id(): string {
		return self::get_root_id() . '-heading';
	}

	/**
	 * Return source product ID.
	 */
	public static function get_source_product_id(): int {
		return self::get_product()->get_id();
	}

	/**
	 * Return recommendation payload JSON for client hydration.
	 */
	public static function get_recommendation_json(): string {
		$items = array();

		foreach ( self::$recommended_ids as $product_id ) {
			$items[] = array(
				'id'    => $product_id,
				'score' => self::$scores[ $product_id ] ?? 0,
			);
		}

		$data = array(
			'sourceProductId' => self::get_product()->get_id(),
			'limit'           => self::get_limit( self::get_product() ),
			'fallback'        => self::get_fallback_strategy( self::get_product() ),
			'engine'          => 'rules',
			'items'           => $items,
		);

		return wp_json_encode( $data ) ?: '{}';
	}

	/**
	 * Return scored recommendation IDs for a product.
	 *
	 * @return array<int, int>
	 */
	public static function get_recommended_product_ids( \WC_Product $product, ?int $limit = null ): array {
		$limit   = null === $limit ? self::get_limit( $product ) : max( 1, $limit );
		$weights = self::get_scoring_weights( $product );
		$context = self::get_product_context( $product->get_id(), $product );
		$scores  = array();

		foreach ( self::get_candidate_ids( $product ) as $candidate_id ) {
			if ( $candidate_id === $product->get_id() ) {
				continue;
			}

			$candidate_context = self::get_product_context( $candidate_id );
			$scores[ $candidate_id ] = self::calculate_score( $context, $candidate_context, $weights );
		}

		/**
		 * Filter calculated recommendation scores before ranking.
		 *
		 * AI recommendation engines can replace or adjust scores here.
		 *
		 * @param array<int, int>       $scores  Product ID => score.
		 * @param \WC_Product             $product Source product.
		 * @param array<string, mixed>  $context Source product matching context.
		 */
		$scores = apply_filters( 'shanelle_related_products_scores', $scores, $product, $context );

		$scores = self::sort_scored_products( $scores );
		$ids    = array_slice( array_keys( $scores ), 0, $limit );

		if ( count( $ids ) < $limit ) {
			$ids = self::apply_fallback_strategy( $product, $ids, $limit );
		}

		/**
		 * Filter final related product IDs.
		 *
		 * @param array<int, int>      $ids     Recommended product IDs.
		 * @param \WC_Product          $product Source product.
		 * @param array<string, mixed> $context Source product matching context.
		 */
		$ids = apply_filters( 'shanelle_related_products_product_ids', $ids, $product, $context );
		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
		$ids = array_values( array_filter( $ids, static fn( int $id ): bool => $id > 0 && $id !== $product->get_id() ) );

		self::$scores = array_intersect_key( $scores, array_flip( $ids ) );

		return array_slice( $ids, 0, $limit );
	}

	/**
	 * Parse render arguments.
	 *
	 * @param array<string, mixed> $args Input args.
	 * @return array<string, mixed>
	 */
	private static function parse_args( array $args ): array {
		return wp_parse_args(
			$args,
			array(
				'card_args' => array(),
			)
		);
	}

	/**
	 * Reset active render context.
	 */
	private static function reset_context(): void {
		self::$product         = null;
		self::$args            = array();
		self::$recommended_ids = array();
		self::$scores          = array();
	}

	/**
	 * Return active product.
	 */
	private static function get_product(): \WC_Product {
		if ( ! self::$product instanceof \WC_Product ) {
			throw new \LogicException( 'ProductRelated render context is not set.' );
		}

		return self::$product;
	}

	/**
	 * Return recommendation limit.
	 */
	private static function get_limit( \WC_Product $product ): int {
		/**
		 * Filter related products limit.
		 *
		 * @param int         $limit   Product count.
		 * @param \WC_Product $product Source product.
		 */
		$limit = (int) apply_filters( 'shanelle_related_products_limit', self::DEFAULT_LIMIT, $product );

		return max( 1, min( 24, $limit ) );
	}

	/**
	 * Return fallback strategy slug.
	 */
	private static function get_fallback_strategy( \WC_Product $product ): string {
		/**
		 * Filter related products fallback strategy.
		 *
		 * Supported: best_selling.
		 *
		 * @param string      $strategy Fallback strategy slug.
		 * @param \WC_Product $product  Source product.
		 */
		$strategy = (string) apply_filters( 'shanelle_related_products_fallback_strategy', 'best_selling', $product );

		return '' !== $strategy ? $strategy : 'best_selling';
	}

	/**
	 * Return scoring weights keyed by matching dimension.
	 *
	 * @return array<string, int>
	 */
	private static function get_scoring_weights( \WC_Product $product ): array {
		$defaults = array(
			'collection'   => 1000,
			'category'     => 800,
			'season'       => 600,
			'occasion'     => 400,
			'color_family' => 200,
		);

		/**
		 * Filter recommendation scoring weights.
		 *
		 * @param array<string, int> $weights Dimension weights.
		 * @param \WC_Product        $product Source product.
		 */
		$weights = apply_filters( 'shanelle_related_products_scoring_weights', $defaults, $product );

		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $weights[ $key ] ) ) {
				$weights[ $key ] = $value;
			}

			$weights[ $key ] = (int) $weights[ $key ];
		}

		return $weights;
	}

	/**
	 * Build matching context for a product.
	 *
	 * @return array<string, array<int, int>>
	 */
	private static function get_product_context( int $product_id, ?\WC_Product $product = null ): array {
		if ( isset( self::$context_cache[ $product_id ] ) ) {
			return self::$context_cache[ $product_id ];
		}

		$product = $product instanceof \WC_Product ? $product : wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			self::$context_cache[ $product_id ] = array(
				'collection'   => array(),
				'category'     => array(),
				'season'       => array(),
				'occasion'     => array(),
				'color_family' => array(),
			);

			return self::$context_cache[ $product_id ];
		}

		self::$context_cache[ $product_id ] = array(
			'collection'   => self::get_term_ids( $product_id, CatalogHelpers::TAXONOMY ),
			'category'     => self::get_term_ids( $product_id, 'product_cat' ),
			'season'       => self::get_attribute_term_ids( $product, self::get_season_taxonomy() ),
			'occasion'     => self::get_attribute_term_ids( $product, self::get_occasion_taxonomy() ),
			'color_family' => self::get_attribute_term_ids( $product, self::get_color_family_taxonomy() ),
		);

		return self::$context_cache[ $product_id ];
	}

	/**
	 * Calculate recommendation score for a candidate product.
	 *
	 * @param array<string, array<int, int>> $source_context    Source product context.
	 * @param array<string, array<int, int>> $candidate_context Candidate product context.
	 * @param array<string, int>             $weights           Dimension weights.
	 */
	private static function calculate_score( array $source_context, array $candidate_context, array $weights ): int {
		$score = 0;

		foreach ( $weights as $dimension => $weight ) {
			if ( $weight <= 0 ) {
				continue;
			}

			$source_terms     = $source_context[ $dimension ] ?? array();
			$candidate_terms  = $candidate_context[ $dimension ] ?? array();

			if ( empty( $source_terms ) || empty( $candidate_terms ) ) {
				continue;
			}

			if ( array_intersect( $source_terms, $candidate_terms ) ) {
				$score += $weight;
			}
		}

		return $score;
	}

	/**
	 * Sort scored products by score then sales volume.
	 *
	 * @param array<int, int> $scores Product scores.
	 * @return array<int, int>
	 */
	private static function sort_scored_products( array $scores ): array {
		uksort(
			$scores,
			static function ( int $left_id, int $right_id ) use ( $scores ): int {
				$left_score  = $scores[ $left_id ] ?? 0;
				$right_score = $scores[ $right_id ] ?? 0;

				if ( $left_score !== $right_score ) {
					return $right_score <=> $left_score;
				}

				$left_sales  = (int) get_post_meta( $left_id, 'total_sales', true );
				$right_sales = (int) get_post_meta( $right_id, 'total_sales', true );

				if ( $left_sales !== $right_sales ) {
					return $right_sales <=> $left_sales;
				}

				return $right_id <=> $left_id;
			}
		);

		return $scores;
	}

	/**
	 * Return candidate product IDs for scoring.
	 *
	 * @return array<int, int>
	 */
	private static function get_candidate_ids( \WC_Product $product ): array {
		$args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => self::DEFAULT_CANDIDATE_POOL,
			'post__not_in'           => array( $product->get_id() ),
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => true,
			'orderby'                => 'date',
			'order'                  => 'DESC',
		);

		$tax_query = array( 'relation' => 'OR' );
		$context   = self::get_product_context( $product->get_id(), $product );

		if ( ! empty( $context['collection'] ) ) {
			$tax_query[] = array(
				'taxonomy' => CatalogHelpers::TAXONOMY,
				'field'    => 'term_id',
				'terms'    => $context['collection'],
			);
		}

		if ( ! empty( $context['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $context['category'],
			);
		}

		foreach ( array( 'season', 'occasion', 'color_family' ) as $dimension ) {
			$taxonomy = self::get_dimension_taxonomy( $dimension );

			if ( '' === $taxonomy || empty( $context[ $dimension ] ) ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $context[ $dimension ],
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		/**
		 * Filter candidate pool query arguments used before scoring.
		 *
		 * @param array<string, mixed> $args    WP_Query args.
		 * @param \WC_Product          $product Source product.
		 */
		$args = apply_filters( 'shanelle_related_products_candidate_query_args', $args, $product );

		$query = new \WP_Query( $args );
		$ids   = array_map( 'absint', $query->posts );

		if ( count( $ids ) >= self::DEFAULT_CANDIDATE_POOL ) {
			return $ids;
		}

		$fallback_args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => self::DEFAULT_CANDIDATE_POOL,
			'post__not_in'           => array_merge( array( $product->get_id() ), $ids ),
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => true,
			'orderby'                => 'meta_value_num',
			'meta_key'               => 'total_sales',
			'order'                  => 'DESC',
		);

		$fallback_query = new \WP_Query( $fallback_args );

		return array_values( array_unique( array_merge( $ids, array_map( 'absint', $fallback_query->posts ) ) ) );
	}

	/**
	 * Fill remaining slots using the configured fallback strategy.
	 *
	 * @param array<int, int> $selected Already selected product IDs.
	 * @return array<int, int>
	 */
	private static function apply_fallback_strategy( \WC_Product $product, array $selected, int $limit ): array {
		$strategy = self::get_fallback_strategy( $product );

		if ( 'best_selling' !== $strategy ) {
			/**
			 * Allow custom fallback strategies to append product IDs.
			 *
			 * @param array<int, int> $selected Current selection.
			 * @param \WC_Product     $product  Source product.
			 * @param int             $limit    Desired limit.
			 */
			return (array) apply_filters( 'shanelle_related_products_fallback_ids', $selected, $product, $limit );
		}

		$needed  = $limit - count( $selected );
		$exclude = array_merge( array( $product->get_id() ), $selected );

		$query = new \WP_Query(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => $needed,
				'post__not_in'           => $exclude,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'orderby'                => 'meta_value_num',
				'meta_key'               => 'total_sales',
				'order'                  => 'DESC',
			)
		);

		foreach ( array_map( 'absint', $query->posts ) as $product_id ) {
			$selected[]           = $product_id;
			self::$scores[ $product_id ] = self::$scores[ $product_id ] ?? 0;
		}

		return array_slice( array_values( array_unique( $selected ) ), 0, $limit );
	}

	/**
	 * Return taxonomy used for a scoring dimension.
	 */
	private static function get_dimension_taxonomy( string $dimension ): string {
		return match ( $dimension ) {
			'season'       => self::get_season_taxonomy(),
			'occasion'     => self::get_occasion_taxonomy(),
			'color_family' => self::get_color_family_taxonomy(),
			default        => '',
		};
	}

	/**
	 * Return season attribute taxonomy.
	 */
	private static function get_season_taxonomy(): string {
		/**
		 * Filter season attribute taxonomy used for related product scoring.
		 *
		 * @param string $taxonomy Default taxonomy slug.
		 */
		$taxonomy = (string) apply_filters( 'shanelle_related_products_season_taxonomy', 'pa_season' );

		return taxonomy_exists( $taxonomy ) ? $taxonomy : '';
	}

	/**
	 * Return occasion attribute taxonomy.
	 */
	private static function get_occasion_taxonomy(): string {
		/**
		 * Filter occasion attribute taxonomy used for related product scoring.
		 *
		 * @param string $taxonomy Default taxonomy slug.
		 */
		$taxonomy = (string) apply_filters( 'shanelle_related_products_occasion_taxonomy', 'pa_occasion' );

		return taxonomy_exists( $taxonomy ) ? $taxonomy : '';
	}

	/**
	 * Return color family attribute taxonomy.
	 */
	private static function get_color_family_taxonomy(): string {
		/**
		 * Filter color family attribute taxonomy used for related product scoring.
		 *
		 * @param string $taxonomy Default taxonomy slug.
		 */
		$taxonomy = (string) apply_filters( 'shanelle_related_products_color_family_taxonomy', 'pa_color-family' );

		return taxonomy_exists( $taxonomy ) ? $taxonomy : '';
	}

	/**
	 * Return taxonomy term IDs for a product.
	 *
	 * @return array<int, int>
	 */
	private static function get_term_ids( int $product_id, string $taxonomy ): array {
		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = wc_get_product_terms( $product_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'absint', (array) $terms ) ) );
	}

	/**
	 * Return attribute term IDs for a taxonomy-backed product attribute.
	 *
	 * @return array<int, int>
	 */
	private static function get_attribute_term_ids( \WC_Product $product, string $taxonomy ): array {
		if ( '' === $taxonomy ) {
			return array();
		}

		return self::get_term_ids( $product->get_id(), $taxonomy );
	}
}
