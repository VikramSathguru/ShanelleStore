<?php
/**
 * Product grid component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Layout and query layer for product listings.
 */
final class ProductGrid {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/product-grid';

	private const COMPONENT_URI = SHANELLE_URI . '/components/product-grid';

	private const AJAX_ACTION = 'shanelle_load_product_grid';

	private const REST_NAMESPACE = 'shanelle/v1';

	/**
	 * Active query instance.
	 */
	private static ?\WP_Query $query = null;

	/**
	 * Active render configuration.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Sanitized query variables used for AJAX replay.
	 *
	 * @var array<string, mixed>
	 */
	private static array $query_vars = array();

	/**
	 * Boot grid hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( self::class, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( self::class, 'handle_ajax' ) );
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
	}

	/**
	 * Enqueue product grid assets.
	 */
	public static function enqueue_assets(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-product-grid',
			self::COMPONENT_URI . '/product-grid.css',
			array( 'shanelle-main', 'shanelle-product-card' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-product-grid',
			self::COMPONENT_URI . '/product-grid.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-product-grid', 'type', 'module' );

		wp_localize_script(
			'shanelle-product-grid',
			'shanelleProductGrid',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'restUrl'  => rest_url( self::REST_NAMESPACE . '/product-grid' ),
				'nonce'    => wp_create_nonce( self::AJAX_ACTION ),
				'restNonce'=> wp_create_nonce( 'wp_rest' ),
				'i18n'     => array(
					'loadMore'   => __( 'Load more', 'shanelle' ),
					'loading'    => __( 'Loading products…', 'shanelle' ),
					'empty'      => __( 'No products found.', 'shanelle' ),
					'error'      => __( 'Unable to load products. Please try again.', 'shanelle' ),
					'pagination' => __( 'Product pagination', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Register REST route for future PWA hydration.
	 */
	public static function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/product-grid',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_rest' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'page'       => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'query'      => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'append'     => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);
	}

	/**
	 * Render a product grid from any query source.
	 *
	 * @param \WP_Query|array<string, mixed>|null $query Query instance or arguments.
	 * @param array<string, mixed>                $args  Grid configuration.
	 */
	public static function render( $query = null, array $args = array() ): void {
		self::$query      = self::resolve_query( $query );
		self::$args       = self::parse_args( $args );
		self::$query_vars = self::extract_query_vars( self::$query );

		if ( ! self::$query->have_posts() ) {
			self::render_empty();
			wp_reset_postdata();
			return;
		}

		require self::COMPONENT_DIR . '/product-grid.php';

		wp_reset_postdata();
	}

	/**
	 * Render grid item loop using ProductCard.
	 */
	public static function render_items(): void {
		if ( ! self::$query instanceof \WP_Query ) {
			return;
		}

		while ( self::$query->have_posts() ) {
			self::$query->the_post();

			$product = wc_get_product( get_the_ID() );

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			echo '<li class="product-grid__item">';
			ProductCard::render( $product, self::$args['card_args'] );
			echo '</li>';
		}
	}

	/**
	 * Render numbered pagination.
	 */
	public static function render_pagination(): void {
		if ( ! self::$query instanceof \WP_Query || self::$query->max_num_pages <= 1 ) {
			return;
		}

		if ( 'pagination' !== self::$args['pagination_mode'] ) {
			return;
		}

		$links = paginate_links(
			array(
				'base'      => self::$args['pagination_base'],
				'format'    => self::$args['pagination_format'],
				'current'   => max( 1, (int) self::$query->get( 'paged' ) ),
				'total'     => (int) self::$query->max_num_pages,
				'type'      => 'list',
				'prev_text' => __( 'Previous', 'shanelle' ),
				'next_text' => __( 'Next', 'shanelle' ),
			)
		);

		if ( ! $links ) {
			return;
		}
		?>
		<nav class="product-grid__pagination" aria-label="<?php esc_attr_e( 'Product pagination', 'shanelle' ); ?>">
			<?php echo wp_kses_post( $links ); ?>
		</nav>
		<?php
	}

	/**
	 * Render load-more control.
	 */
	public static function render_load_more(): void {
		if ( ! self::$query instanceof \WP_Query ) {
			return;
		}

		$modes = array( 'load_more', 'infinite' );

		if ( ! in_array( self::$args['pagination_mode'], $modes, true ) ) {
			return;
		}

		if ( ! self::$args['infinite_scroll'] && 'infinite' === self::$args['pagination_mode'] ) {
			return;
		}

		$current = max( 1, (int) self::$query->get( 'paged' ) );
		$has_more = $current < (int) self::$query->max_num_pages;

		if ( ! $has_more ) {
			return;
		}
		?>
		<div class="product-grid__load-more-wrap">
			<button
				type="button"
				class="btn btn--outline product-grid__load-more"
				data-shanelle-load-more
				<?php echo 'infinite' === self::$args['pagination_mode'] ? 'hidden' : ''; ?>
			>
				<?php echo esc_html( (string) self::$args['load_more_label'] ); ?>
			</button>
			<div
				class="product-grid__sentinel"
				data-shanelle-infinite-sentinel
				aria-hidden="true"
				<?php echo ( 'infinite' === self::$args['pagination_mode'] && self::$args['infinite_scroll'] ) ? '' : 'hidden'; ?>
			></div>
		</div>
		<?php
	}

	/**
	 * Render empty state.
	 */
	public static function render_empty(): void {
		$message = (string) self::$args['empty_message'];
		?>
		<div class="product-grid product-grid--empty" data-shanelle-product-grid data-grid-state="empty">
			<div class="product-grid__empty">
				<h2 class="product-grid__empty-title text-h4"><?php esc_html_e( 'No products found', 'shanelle' ); ?></h2>
				<p class="product-grid__empty-text text-body-sm text-muted"><?php echo esc_html( $message ); ?></p>
				<a class="btn btn--primary product-grid__empty-action" href="<?php echo esc_url( shanelle_is_woocommerce_active() ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">
					<?php esc_html_e( 'Browse all products', 'shanelle' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render loading skeleton placeholders.
	 *
	 * @param int $count Number of skeleton cards.
	 */
	public static function render_skeleton( int $count = 8 ): void {
		$count = max( 1, min( 12, $count ) );
		?>
		<ul class="product-grid__items product-grid__items--skeleton" aria-hidden="true">
			<?php for ( $i = 0; $i < $count; $i++ ) : ?>
				<li class="product-grid__item product-grid__skeleton">
					<div class="product-grid__skeleton-media skeleton"></div>
					<div class="product-grid__skeleton-line skeleton"></div>
					<div class="product-grid__skeleton-line product-grid__skeleton-line--short skeleton"></div>
				</li>
			<?php endfor; ?>
		</ul>
		<?php
	}

	/**
	 * Return grid data attributes for client hydration.
	 */
	public static function get_grid_attributes(): string {
		$current = self::$query ? max( 1, (int) self::$query->get( 'paged' ) ) : 1;
		$max     = self::$query ? (int) self::$query->max_num_pages : 1;

		$attrs = array(
			'data-shanelle-product-grid'   => '',
			'data-grid-id'                 => self::$args['grid_id'],
			'data-grid-page'               => (string) $current,
			'data-grid-max-pages'          => (string) $max,
			'data-grid-mode'               => self::$args['pagination_mode'],
			'data-grid-infinite'           => self::$args['infinite_scroll'] ? 'true' : 'false',
			'data-grid-query'              => wp_json_encode( self::$query_vars ),
			'data-grid-card-args'          => wp_json_encode( self::$args['card_args'] ),
		);

		$output = '';

		foreach ( $attrs as $key => $value ) {
			if ( '' === $value ) {
				$output .= ' ' . esc_attr( $key );
			} else {
				$output .= ' ' . esc_attr( $key ) . '="' . esc_attr( (string) $value ) . '"';
			}
		}

		return $output;
	}

	/**
	 * Handle admin-ajax grid loading.
	 */
	public static function handle_ajax(): void {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		$response = self::build_ajax_response();

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array( 'message' => $response->get_error_message() ),
				400
			);
		}

		wp_send_json_success( $response );
	}

	/**
	 * Handle REST grid loading.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function handle_rest( \WP_REST_Request $request ) {
		$query_json = $request->get_param( 'query' );
		$query_vars = json_decode( (string) $query_json, true );

		if ( ! is_array( $query_vars ) ) {
			return new \WP_Error( 'invalid_query', __( 'Invalid product query.', 'shanelle' ), array( 'status' => 400 ) );
		}

		$_POST['query_vars'] = wp_json_encode( self::sanitize_query_vars( $query_vars ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST['page']       = (string) $request->get_param( 'page' );

		$response = self::build_ajax_response();

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Build AJAX/REST response payload.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function build_ajax_response() {
		$page = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$query_raw = isset( $_POST['query_vars'] ) ? wp_unslash( $_POST['query_vars'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$query_vars = json_decode( (string) $query_raw, true );

		if ( ! is_array( $query_vars ) ) {
			return new \WP_Error( 'invalid_query', __( 'Invalid product query.', 'shanelle' ) );
		}

		$query_vars         = self::sanitize_query_vars( $query_vars );
		$query_vars['paged'] = max( 1, $page );

		$card_args_raw = isset( $_POST['card_args'] ) ? wp_unslash( $_POST['card_args'] ) : '{}'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$card_args     = json_decode( (string) $card_args_raw, true );
		$card_args     = is_array( $card_args ) ? $card_args : array();

		self::$query      = new \WP_Query( $query_vars );
		self::$args       = self::parse_args( array( 'card_args' => $card_args ) );
		self::$query_vars = $query_vars;

		ob_start();
		self::render_items();
		$items_html = ob_get_clean();

		wp_reset_postdata();

		return array(
			'html'      => $items_html,
			'page'      => $page,
			'max_pages' => (int) self::$query->max_num_pages,
			'has_more'  => $page < (int) self::$query->max_num_pages,
		);
	}

	/**
	 * Resolve query input into WP_Query.
	 *
	 * @param \WP_Query|array<string, mixed>|null $query Query source.
	 */
	private static function resolve_query( $query ): \WP_Query {
		if ( $query instanceof \WP_Query ) {
			return $query;
		}

		if ( is_array( $query ) ) {
			return new \WP_Query( self::sanitize_query_vars( $query ) );
		}

		global $wp_query;

		if ( $wp_query instanceof \WP_Query ) {
			return $wp_query;
		}

		return new \WP_Query(
			self::sanitize_query_vars(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => 12,
				)
			)
		);
	}

	/**
	 * Parse grid render arguments.
	 *
	 * @param array<string, mixed> $args Input args.
	 * @return array<string, mixed>
	 */
	private static function parse_args( array $args ): array {
		$defaults = array(
			'grid_id'            => 'grid-' . wp_unique_id(),
			'pagination_mode'    => 'pagination',
			'infinite_scroll'    => false,
			'load_more_label'    => __( 'Load more', 'shanelle' ),
			'empty_message'      => __( 'Try adjusting your filters or browse our full collection.', 'shanelle' ),
			'card_args'          => array(),
			'pagination_base'    => '',
			'pagination_format'  => '',
		);

		$parsed = wp_parse_args( $args, $defaults );

		if ( ! in_array( $parsed['pagination_mode'], array( 'pagination', 'load_more', 'infinite', 'none' ), true ) ) {
			$parsed['pagination_mode'] = 'pagination';
		}

		return $parsed;
	}

	/**
	 * Extract replay-safe query vars from WP_Query.
	 *
	 * @return array<string, mixed>
	 */
	private static function extract_query_vars( \WP_Query $query ): array {
		return self::sanitize_query_vars( $query->query_vars );
	}

	/**
	 * Sanitize product query vars for replay over AJAX/REST.
	 *
	 * @param array<string, mixed> $query_vars Raw query vars.
	 * @return array<string, mixed>
	 */
	public static function sanitize_query_vars( array $query_vars ): array {
		$allowed_orderby = array(
			'date',
			'title',
			'modified',
			'menu_order',
			'popularity',
			'rating',
			'price',
			'price-desc',
			'rand',
		);

		$clean = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => isset( $query_vars['posts_per_page'] ) ? absint( $query_vars['posts_per_page'] ) : 12,
			'paged'          => isset( $query_vars['paged'] ) ? absint( $query_vars['paged'] ) : 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $query_vars['s'] ) ) {
			$clean['s'] = sanitize_text_field( (string) $query_vars['s'] );
		}

		if ( ! empty( $query_vars['orderby'] ) ) {
			$orderby = sanitize_key( (string) $query_vars['orderby'] );
			$clean['orderby'] = in_array( $orderby, $allowed_orderby, true ) ? $orderby : 'date';
		}

		if ( ! empty( $query_vars['order'] ) ) {
			$order = strtoupper( sanitize_text_field( (string) $query_vars['order'] ) );
			$clean['order'] = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
		}

		if ( ! empty( $query_vars['tax_query'] ) && is_array( $query_vars['tax_query'] ) ) {
			$clean['tax_query'] = self::sanitize_tax_query( $query_vars['tax_query'] );
		}

		if ( ! empty( $query_vars['meta_query'] ) && is_array( $query_vars['meta_query'] ) ) {
			$clean['meta_query'] = self::sanitize_meta_query( $query_vars['meta_query'] );
		}

		if ( ! wp_doing_ajax() && ! defined( 'REST_REQUEST' ) ) {
			$clean = self::apply_wc_catalog_ordering( $clean );
		}

		$clean = self::map_wc_orderby_args( $clean );

		return apply_filters( 'shanelle_product_grid_query_vars', $clean, $query_vars );
	}

	/**
	 * Map WooCommerce catalog orderby values to query args.
	 *
	 * @param array<string, mixed> $query_vars Query vars.
	 * @return array<string, mixed>
	 */
	private static function map_wc_orderby_args( array $query_vars ): array {
		if ( ! shanelle_is_woocommerce_active() ) {
			return $query_vars;
		}

		if ( empty( $query_vars['orderby'] ) || ! in_array( (string) $query_vars['orderby'], array( 'popularity', 'rating', 'price', 'price-desc' ), true ) ) {
			return $query_vars;
		}

		$ordering = WC()->query->get_catalog_ordering_args( (string) $query_vars['orderby'] );

		if ( ! empty( $ordering['orderby'] ) ) {
			$query_vars['orderby'] = $ordering['orderby'];
		}

		if ( ! empty( $ordering['order'] ) ) {
			$query_vars['order'] = $ordering['order'];
		}

		if ( ! empty( $ordering['meta_key'] ) ) {
			$query_vars['meta_key'] = $ordering['meta_key'];
		}

		return $query_vars;
	}

	/**
	 * Apply WooCommerce catalog ordering from request context.
	 *
	 * @param array<string, mixed> $query_vars Sanitized query vars.
	 * @return array<string, mixed>
	 */
	private static function apply_wc_catalog_ordering( array $query_vars ): array {
		if ( ! shanelle_is_woocommerce_active() ) {
			return $query_vars;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['orderby'] ) ) {
			return $query_vars;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby  = wc_clean( wp_unslash( $_GET['orderby'] ) );
		$ordering = WC()->query->get_catalog_ordering_args( $orderby );

		$query_vars['orderby'] = $orderby;

		return self::map_wc_orderby_args( $query_vars );
	}

	/**
	 * Sanitize taxonomy query clauses.
	 *
	 * @param array<int, mixed> $tax_query Raw tax query.
	 * @return array<int, mixed>
	 */
	private static function sanitize_tax_query( array $tax_query ): array {
		$clean = array();

		foreach ( $tax_query as $clause ) {
			if ( ! is_array( $clause ) || empty( $clause['taxonomy'] ) ) {
				continue;
			}

			$clean[] = array(
				'taxonomy' => sanitize_key( (string) $clause['taxonomy'] ),
				'field'    => isset( $clause['field'] ) ? sanitize_key( (string) $clause['field'] ) : 'slug',
				'terms'    => array_map( 'sanitize_title', (array) ( $clause['terms'] ?? array() ) ),
				'operator' => isset( $clause['operator'] ) ? sanitize_text_field( (string) $clause['operator'] ) : 'IN',
			);
		}

		return $clean;
	}

	/**
	 * Sanitize meta query clauses.
	 *
	 * @param array<int, mixed> $meta_query Raw meta query.
	 * @return array<int, mixed>
	 */
	private static function sanitize_meta_query( array $meta_query ): array {
		$clean = array();

		foreach ( $meta_query as $clause ) {
			if ( ! is_array( $clause ) || empty( $clause['key'] ) ) {
				continue;
			}

			$clean[] = array(
				'key'     => sanitize_key( (string) $clause['key'] ),
				'value'   => sanitize_text_field( (string) ( $clause['value'] ?? '' ) ),
				'compare' => isset( $clause['compare'] ) ? sanitize_text_field( (string) $clause['compare'] ) : '=',
				'type'    => isset( $clause['type'] ) ? sanitize_text_field( (string) $clause['type'] ) : 'CHAR',
			);
		}

		return $clean;
	}
}
