<?php
/**
 * Search results markup composer.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\Catalog\Helpers;
use Shanelle\WooCommerce\ProductPrice;

defined( 'ABSPATH' ) || exit;

/**
 * Renders search suggestion and live result markup using WooCommerce data.
 */
final class SearchResults {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/search-results';

	/**
	 * Active render arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Render search results panel markup.
	 *
	 * @param array<string, mixed> $args Render arguments.
	 */
	public static function render( array $args ): void {
		self::$args = apply_filters( 'shanelle_search_results_args', $args );

		require self::COMPONENT_DIR . '/search-results.php';

		self::$args = array();
	}

	/**
	 * Render idle suggestions (popular searches; recent filled client-side).
	 */
	public static function render_idle(): void {
		self::$args = array(
			'mode' => 'idle',
		);

		require self::COMPONENT_DIR . '/search-results.php';

		self::$args = array();
	}

	/**
	 * Render loading skeleton markup.
	 */
	public static function render_skeleton(): void {
		?>
		<div class="search-results search-results--loading" data-shanelle-search-skeleton aria-hidden="true">
			<ul class="search-results__list" role="presentation">
				<?php for ( $index = 0; $index < 4; $index++ ) : ?>
					<li class="search-results__skeleton-item" aria-hidden="true">
						<span class="search-results__skeleton-media"></span>
						<span class="search-results__skeleton-copy">
							<span class="search-results__skeleton-line search-results__skeleton-line--title"></span>
							<span class="search-results__skeleton-line search-results__skeleton-line--meta"></span>
						</span>
					</li>
				<?php endfor; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render empty state when no matches are found.
	 */
	public static function render_empty(): void {
		$query    = (string) ( self::$args['query'] ?? '' );
		$settings = SearchOverlay::get_settings();
		?>
		<div class="search-results search-results--empty" data-shanelle-search-empty>
			<p class="search-results__empty-title text-h3"><?php esc_html_e( 'No se encontraron resultados', 'shanelle' ); ?></p>
			<p class="search-results__empty-message text-muted">
				<?php
				echo esc_html(
					(string) ( $settings['empty_message'] ?? __( 'Prueba con otra palabra o explora nuestras búsquedas populares.', 'shanelle' ) )
				);
				?>
			</p>
			<?php if ( '' !== $query ) : ?>
				<a class="btn btn--outline search-results__view-all" href="<?php echo esc_url( SearchController::get_results_url( $query ) ); ?>">
					<?php
					/* translators: %s: search query */
					printf( esc_html__( 'Ver todos los resultados de "%s"', 'shanelle' ), esc_html( $query ) );
					?>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render product suggestion rows.
	 */
	public static function render_products(): void {
		$query    = (string) ( self::$args['query'] ?? '' );
		$products = is_array( self::$args['products'] ?? null ) ? self::$args['products'] : array();

		if ( empty( $products ) ) {
			return;
		}
		?>
		<section class="search-results__section" aria-label="<?php esc_attr_e( 'Sugerencias de productos', 'shanelle' ); ?>">
			<h3 class="search-results__section-title text-label"><?php esc_html_e( 'Productos', 'shanelle' ); ?></h3>
			<ul class="search-results__list" role="listbox" aria-label="<?php esc_attr_e( 'Sugerencias de productos', 'shanelle' ); ?>">
				<?php
				foreach ( $products as $product ) {
					if ( $product instanceof \WC_Product ) {
						self::render_product_item( $product, $query );
					}
				}
				?>
			</ul>
		</section>
		<?php
	}

	/**
	 * Render a single product suggestion row.
	 */
	public static function render_product_item( \WC_Product $product, string $query ): void {
		$data = self::build_product_item( $product, $query );
		?>
		<li
			class="search-results__item search-results__item--product"
			role="option"
			data-shanelle-search-option
			data-search-url="<?php echo esc_url( (string) $data['url'] ); ?>"
		>
			<a class="search-results__link" href="<?php echo esc_url( (string) $data['url'] ); ?>">
				<span class="search-results__media">
					<?php echo $data['thumbnail_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>

				<span class="search-results__copy">
					<span class="search-results__title text-label">
						<?php echo wp_kses_post( (string) $data['title_html'] ); ?>
					</span>

					<?php if ( '' !== (string) $data['meta'] ) : ?>
						<span class="search-results__meta text-caption text-muted"><?php echo esc_html( (string) $data['meta'] ); ?></span>
					<?php endif; ?>

					<?php if ( ! empty( $data['price_html'] ) ) : ?>
						<span class="<?php echo esc_attr( implode( ' ', (array) $data['price_classes'] ) ); ?>">
							<?php echo wp_kses_post( (string) $data['price_html'] ); ?>
						</span>
					<?php endif; ?>
				</span>

				<?php if ( '' !== (string) $data['sale_badge'] ) : ?>
					<span class="badge badge--sale search-results__badge"><?php echo esc_html( (string) $data['sale_badge'] ); ?></span>
				<?php endif; ?>

				<span class="search-results__quick text-caption"><?php esc_html_e( 'Ver', 'shanelle' ); ?></span>
			</a>
		</li>
		<?php
	}

	/**
	 * Render category suggestions.
	 */
	public static function render_categories(): void {
		$query      = (string) ( self::$args['query'] ?? '' );
		$categories = is_array( self::$args['categories'] ?? null ) ? self::$args['categories'] : array();

		if ( empty( $categories ) ) {
			return;
		}
		?>
		<section class="search-results__section" aria-label="<?php esc_attr_e( 'Sugerencias de categorías', 'shanelle' ); ?>">
			<h3 class="search-results__section-title text-label"><?php esc_html_e( 'Categorías', 'shanelle' ); ?></h3>
			<ul class="search-results__list search-results__list--terms" role="listbox" aria-label="<?php esc_attr_e( 'Sugerencias de categorías', 'shanelle' ); ?>">
				<?php
				foreach ( $categories as $term ) {
					if ( $term instanceof \WP_Term ) {
						self::render_term_item( $term, $query, 'category' );
					}
				}
				?>
			</ul>
		</section>
		<?php
	}

	/**
	 * Render collection suggestions.
	 */
	public static function render_collections(): void {
		$query       = (string) ( self::$args['query'] ?? '' );
		$collections = is_array( self::$args['collections'] ?? null ) ? self::$args['collections'] : array();

		if ( empty( $collections ) ) {
			return;
		}
		?>
		<section class="search-results__section" aria-label="<?php esc_attr_e( 'Sugerencias de colecciones', 'shanelle' ); ?>">
			<h3 class="search-results__section-title text-label"><?php esc_html_e( 'Colecciones', 'shanelle' ); ?></h3>
			<ul class="search-results__list search-results__list--terms" role="listbox" aria-label="<?php esc_attr_e( 'Sugerencias de colecciones', 'shanelle' ); ?>">
				<?php
				foreach ( $collections as $term ) {
					if ( $term instanceof \WP_Term ) {
						self::render_term_item( $term, $query, 'collection' );
					}
				}
				?>
			</ul>
		</section>
		<?php
	}

	/**
	 * Render a taxonomy suggestion row.
	 */
	public static function render_term_item( \WP_Term $term, string $query, string $type ): void {
		$link = get_term_link( $term );

		if ( is_wp_error( $link ) ) {
			return;
		}

		$label = 'collection' === $type
			? __( 'Colección', 'shanelle' )
			: __( 'Categoría', 'shanelle' );
		?>
		<li
			class="search-results__item search-results__item--term search-results__item--<?php echo esc_attr( $type ); ?>"
			role="option"
			data-shanelle-search-option
			data-search-url="<?php echo esc_url( $link ); ?>"
		>
			<a class="search-results__link search-results__link--term" href="<?php echo esc_url( $link ); ?>">
				<span class="search-results__term-label text-caption text-muted"><?php echo esc_html( $label ); ?></span>
				<span class="search-results__title text-label"><?php echo wp_kses_post( SearchController::highlight_query( $term->name, $query ) ); ?></span>
				<span class="search-results__quick text-caption"><?php esc_html_e( 'Explorar', 'shanelle' ); ?></span>
			</a>
		</li>
		<?php
	}

	/**
	 * Render popular search chips for idle state.
	 */
	public static function render_popular_searches(): void {
		$popular = SearchOverlay::get_popular_searches();

		if ( empty( $popular ) ) {
			return;
		}
		?>
		<section class="search-results__section search-results__section--popular" aria-label="<?php esc_attr_e( 'Búsquedas populares', 'shanelle' ); ?>">
			<h3 class="search-results__section-title text-label"><?php esc_html_e( 'Búsquedas populares', 'shanelle' ); ?></h3>
			<ul class="search-results__chips" role="list">
				<?php foreach ( $popular as $term ) : ?>
					<li>
						<button
							type="button"
							class="chip search-results__chip"
							data-shanelle-search-suggestion
							data-search-query="<?php echo esc_attr( $term ); ?>"
						>
							<?php echo esc_html( $term ); ?>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}

	/**
	 * Render recent searches container (filled client-side).
	 */
	public static function render_recent_searches_shell(): void {
		?>
		<section class="search-results__section search-results__section--recent" data-shanelle-search-recent hidden aria-label="<?php esc_attr_e( 'Búsquedas recientes', 'shanelle' ); ?>">
			<div class="search-results__section-head">
				<h3 class="search-results__section-title text-label"><?php esc_html_e( 'Búsquedas recientes', 'shanelle' ); ?></h3>
				<button type="button" class="search-results__clear-recent text-caption" data-shanelle-search-clear-recent hidden>
					<?php esc_html_e( 'Borrar', 'shanelle' ); ?>
				</button>
			</div>
			<ul class="search-results__chips" data-shanelle-search-recent-list role="list"></ul>
		</section>
		<?php
	}

	/**
	 * Render view-all footer link.
	 */
	public static function render_view_all(): void {
		$query = (string) ( self::$args['query'] ?? '' );

		if ( '' === $query ) {
			return;
		}
		?>
		<div class="search-results__footer">
			<a class="btn btn--outline btn--block search-results__view-all" href="<?php echo esc_url( SearchController::get_results_url( $query ) ); ?>">
				<?php
				/* translators: %s: search query */
				printf( esc_html__( 'Ver todos los resultados de "%s"', 'shanelle' ), esc_html( $query ) );
				?>
			</a>
		</div>
		<?php
	}

	/**
	 * Build normalized product item data.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_product_item( \WC_Product $product, string $query ): array {
		$price_data     = ProductPrice::get_display_data( $product );
		$price_classes  = ProductPrice::get_compact_classes( $product, 'search-results__price' );
		$category_label = self::get_primary_term_name( $product->get_id(), 'product_cat' );
		$collection     = self::get_primary_term_name( $product->get_id(), Helpers::TAXONOMY );
		$meta_parts     = array_filter( array( $category_label, $collection ) );

		return apply_filters(
			'shanelle_search_product_item',
			array(
				'id'              => $product->get_id(),
				'url'             => $product->get_permalink(),
				'title_html'      => SearchController::highlight_query( $product->get_name(), $query ),
				'meta'            => implode( ' · ', $meta_parts ),
				'price_html'      => (string) ( $price_data['compact_html'] ?? '' ),
				'price_classes'   => $price_classes,
				'sale_badge'      => ProductPrice::get_sale_badge_label( $product ),
				'thumbnail_html'  => self::get_product_thumbnail( $product ),
			),
			$product,
			$query
		);
	}

	/**
	 * Build PWA-friendly items payload.
	 *
	 * @param array<string, mixed> $args Result arguments.
	 * @return array<string, mixed>
	 */
	public static function build_items_payload( array $args ): array {
		$query       = (string) ( $args['query'] ?? '' );
		$products    = is_array( $args['products'] ?? null ) ? $args['products'] : array();
		$categories  = is_array( $args['categories'] ?? null ) ? $args['categories'] : array();
		$collections = is_array( $args['collections'] ?? null ) ? $args['collections'] : array();
		$items       = array(
			'products'    => array(),
			'categories'  => array(),
			'collections' => array(),
		);

		foreach ( $products as $product ) {
			if ( $product instanceof \WC_Product ) {
				$items['products'][] = self::build_product_item( $product, $query );
			}
		}

		foreach ( $categories as $term ) {
			if ( $term instanceof \WP_Term ) {
				$items['categories'][] = self::build_term_item( $term, $query, 'category' );
			}
		}

		foreach ( $collections as $term ) {
			if ( $term instanceof \WP_Term ) {
				$items['collections'][] = self::build_term_item( $term, $query, 'collection' );
			}
		}

		return $items;
	}

	/**
	 * Whether the active render has any matches.
	 */
	public static function has_results(): bool {
		foreach ( array( 'products', 'categories', 'collections' ) as $key ) {
			if ( ! empty( self::$args[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return active query string.
	 */
	public static function get_query(): string {
		return (string) ( self::$args['query'] ?? '' );
	}

	/**
	 * Whether idle suggestions are being rendered.
	 */
	public static function is_idle_mode(): bool {
		return 'idle' === ( self::$args['mode'] ?? '' );
	}

	/**
	 * Build normalized term item data.
	 *
	 * @return array<string, string>
	 */
	private static function build_term_item( \WP_Term $term, string $query, string $type ): array {
		$link = get_term_link( $term );

		return array(
			'id'         => (string) $term->term_id,
			'type'       => $type,
			'name'       => $term->name,
			'name_html'  => SearchController::highlight_query( $term->name, $query ),
			'url'        => is_wp_error( $link ) ? '' : (string) $link,
		);
	}

	/**
	 * Return primary taxonomy label for a product.
	 */
	private static function get_primary_term_name( int $product_id, string $taxonomy ): string {
		$terms = wc_get_product_terms( $product_id, $taxonomy, array( 'number' => 1 ) );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$term = $terms[0];

		return $term instanceof \WP_Term ? $term->name : '';
	}

	/**
	 * Return responsive product thumbnail markup.
	 */
	private static function get_product_thumbnail( \WC_Product $product ): string {
		$image_id = (int) $product->get_image_id();

		if ( $image_id <= 0 ) {
			return wc_placeholder_img(
				'shanelle-product-card',
				array(
					'class'   => 'search-results__image',
					'loading' => 'lazy',
					'alt'     => '',
				)
			);
		}

		return wp_get_attachment_image(
			$image_id,
			'shanelle-product-card',
			false,
			array(
				'class'         => 'search-results__image',
				'loading'       => 'lazy',
				'alt'           => $product->get_name(),
				'decoding'      => 'async',
				'fetchpriority' => 'low',
			)
		) ?: '';
	}
}
