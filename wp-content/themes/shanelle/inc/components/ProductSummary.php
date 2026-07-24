<?php
/**
 * Product summary component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\WooCommerce\ProductPrice;

defined( 'ABSPATH' ) || exit;

/**
 * Reusable WooCommerce product summary for single product and PWA contexts.
 */
final class ProductSummary {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/product-summary';

	private const COMPONENT_URI = SHANELLE_URI . '/components/product-summary';

	/**
	 * Active product instance.
	 */
	private static ?\WC_Product $product = null;

	/**
	 * Active render arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static array $args = array();

	/**
	 * Cached price data for the active product.
	 *
	 * @var array<string, mixed>
	 */
	private static array $price_data = array();

	/**
	 * Boot summary hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_filter( 'woocommerce_available_variation', array( self::class, 'extend_variation_data' ), 10, 3 );
	}

	/**
	 * Extend variation JSON with formatted summary price fields.
	 *
	 * @param array<string, mixed> $data      Variation data.
	 * @param \WC_Product          $product   Parent product.
	 * @param \WC_Product_Variation $variation Variation product.
	 * @return array<string, mixed>
	 */
	public static function extend_variation_data( array $data, \WC_Product $product, \WC_Product_Variation $variation ): array {
		$price = ProductPrice::get_display_data( $variation );

		$data['shanelle_is_on_sale']    = (bool) $price['is_on_sale'];
		$data['shanelle_current_html']  = (string) $price['current_html'];
		$data['shanelle_regular_html']  = (string) $price['regular_html'];
		$data['shanelle_savings_html']  = (string) $price['savings_html'];

		return $data;
	}

	/**
	 * Enqueue summary assets on single product pages.
	 */
	public static function enqueue_assets(): void {
		if ( ! shanelle_is_woocommerce_active() || ! is_product() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue summary assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-product-summary',
			self::COMPONENT_URI . '/product-summary.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-product-summary',
			self::COMPONENT_URI . '/product-summary.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-product-summary', 'type', 'module' );

		wp_localize_script(
			'shanelle-product-summary',
			'shanelleProductSummary',
			array(
				'i18n' => array(
					'inStock'       => __( 'En stock', 'shanelle' ),
					'outOfStock'    => __( 'Agotado', 'shanelle' ),
					'onBackorder'   => __( 'Disponible bajo pedido', 'shanelle' ),
					'lowStock'      => __( 'Poco stock', 'shanelle' ),
					'priceUpdated'  => __( 'Precio actualizado', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the product summary.
	 *
	 * @param \WC_Product          $product Product object.
	 * @param array<string, mixed> $args    Render arguments.
	 */
	public static function render( \WC_Product $product, array $args = array() ): void {
		self::$product     = $product;
		self::$args        = self::parse_args( $args );
		self::$price_data  = ProductPrice::get_display_data( $product );

		if ( ! wp_style_is( 'shanelle-product-summary', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/product-summary.php';

		self::$product    = null;
		self::$args       = array();
		self::$price_data = array();
	}

	/**
	 * Render brand when a real value exists.
	 */
	public static function render_brand(): void {
		$brand = self::get_brand_label();

		if ( '' === $brand ) {
			return;
		}
		?>
		<p class="product-summary__brand text-label text-muted" data-shanelle-summary-brand>
			<span class="product-summary__brand-label"><?php esc_html_e( 'Marca', 'shanelle' ); ?></span>
			<span class="product-summary__brand-value"><?php echo esc_html( $brand ); ?></span>
		</p>
		<?php
	}

	/**
	 * Render product title.
	 */
	public static function render_title(): void {
		$product = self::get_product();
		?>
		<h1 id="<?php echo esc_attr( self::get_title_id() ); ?>" class="product-summary__title">
			<?php echo esc_html( $product->get_name() ); ?>
		</h1>
		<?php
	}

	/**
	 * Render SKU and rating meta row.
	 */
	public static function render_meta(): void {
		$product = self::get_product();
		$sku     = self::get_sku_display( $product );
		$rating  = self::get_rating_data( $product );
		$has_sku = '' !== $sku;
		$has_rating = $rating['has_rating'];

		if ( ! $has_sku && ! $has_rating ) {
			return;
		}
		?>
		<dl class="product-summary__meta">
			<?php if ( $has_sku ) : ?>
				<div class="product-summary__meta-item">
					<dt class="product-summary__meta-label"><?php esc_html_e( 'SKU', 'shanelle' ); ?></dt>
					<dd class="product-summary__meta-value" data-shanelle-summary-sku><?php echo esc_html( $sku ); ?></dd>
				</div>
			<?php endif; ?>

			<?php if ( $has_rating ) : ?>
				<div class="product-summary__meta-item product-summary__meta-item--rating">
					<dt class="sr-only"><?php esc_html_e( 'Valoración de clientes', 'shanelle' ); ?></dt>
					<dd class="product-summary__rating">
						<?php echo wp_kses_post( $rating['html'] ); ?>
						<a class="product-summary__review-link text-caption" href="#reviews">
							<?php echo esc_html( $rating['review_text'] ); ?>
						</a>
					</dd>
				</div>
			<?php endif; ?>
		</dl>
		<?php
	}

	/**
	 * Render detailed price block.
	 */
	public static function render_price(): void {
		$data    = self::$price_data;
		$product = self::get_product();

		if ( ! $data['has_price'] ) {
			return;
		}

		$classes = array( 'product-summary__price' );

		if ( $data['is_on_sale'] ) {
			$classes[] = 'product-summary__price--on-sale';
		}

		if ( $data['is_range'] ) {
			$classes[] = 'product-summary__price--range';
		}

		$show_regular = $data['is_on_sale'] && '' !== $data['regular_html'];
		$show_savings = $data['is_on_sale'] && '' !== $data['savings_html'];
		$is_variable  = $product->is_type( 'variable' );
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-shanelle-summary-price
			data-price-json="<?php echo esc_attr( wp_json_encode( self::get_price_json() ) ?: '{}' ); ?>"
		>
			<p class="product-summary__price-current text-price">
				<span class="sr-only"><?php esc_html_e( 'Precio actual', 'shanelle' ); ?></span>
				<span class="product-summary__price-value" data-shanelle-summary-price-current>
					<?php echo wp_kses_post( $data['current_html'] ); ?>
				</span>
			</p>

			<?php if ( $is_variable || $show_regular ) : ?>
				<p class="product-summary__price-regular<?php echo $show_regular ? '' : ' sr-only'; ?>">
					<span class="sr-only"><?php esc_html_e( 'Precio original', 'shanelle' ); ?></span>
					<span class="product-summary__price-value product-summary__price-value--regular" data-shanelle-summary-price-regular>
						<?php echo wp_kses_post( $data['regular_html'] ); ?>
					</span>
				</p>
			<?php endif; ?>

			<?php if ( $is_variable || $show_savings ) : ?>
				<p class="product-summary__price-savings<?php echo $show_savings ? '' : ' sr-only'; ?>">
					<span class="badge badge--sale badge--pill" data-shanelle-summary-price-savings>
						<?php echo esc_html( $data['savings_html'] ); ?>
					</span>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render stock availability.
	 */
	public static function render_stock(): void {
		$product = self::get_product();
		$stock   = self::get_stock_data( $product );

		if ( ! $stock['show'] ) {
			return;
		}
		?>
		<p
			class="product-summary__stock product-summary__stock--<?php echo esc_attr( $stock['status'] ); ?>"
			data-shanelle-summary-stock
			data-stock-status="<?php echo esc_attr( $stock['status'] ); ?>"
		>
			<span class="product-summary__stock-indicator" aria-hidden="true"></span>
			<span class="product-summary__stock-label"><?php echo esc_html( $stock['label'] ); ?></span>
		</p>
		<?php
	}

	/**
	 * Render short description.
	 */
	public static function render_short_description(): void {
		$product     = self::get_product();
		$description = apply_filters( 'woocommerce_short_description', $product->get_short_description() );

		if ( ! is_string( $description ) || '' === trim( wp_strip_all_tags( $description ) ) ) {
			return;
		}
		?>
		<div class="product-summary__description">
			<?php echo wp_kses_post( $description ); ?>
		</div>
		<?php
	}

	/**
	 * Render product highlights when real items exist.
	 */
	public static function render_highlights(): void {
		$highlights = self::get_highlights();

		if ( empty( $highlights ) ) {
			return;
		}
		?>
		<section class="product-summary__highlights" aria-labelledby="<?php echo esc_attr( self::get_highlights_id() ); ?>" data-shanelle-summary-highlights>
			<h2 id="<?php echo esc_attr( self::get_highlights_id() ); ?>" class="product-summary__highlights-title text-label">
				<?php esc_html_e( 'Destacados', 'shanelle' ); ?>
			</h2>
			<ul class="product-summary__highlights-list">
				<?php foreach ( $highlights as $highlight ) : ?>
					<li class="product-summary__highlights-item">
						<?php echo esc_html( $highlight ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}

	/**
	 * Resolve brand label from product attributes/taxonomies.
	 */
	public static function get_brand_label(): string {
		$product = self::get_product();
		$brand   = '';

		foreach ( array( 'pa_brand', 'brand', 'pa_marca' ) as $attribute ) {
			$value = $product->get_attribute( $attribute );

			if ( is_string( $value ) && '' !== trim( $value ) ) {
				$brand = trim( $value );
				break;
			}
		}

		if ( '' === $brand && taxonomy_exists( 'product_brand' ) ) {
			$terms = get_the_terms( $product->get_id(), 'product_brand' );

			if ( is_array( $terms ) && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$names = array_map(
					static function ( $term ): string {
						return $term instanceof \WP_Term ? $term->name : '';
					},
					$terms
				);
				$names = array_values( array_filter( $names ) );

				if ( ! empty( $names ) ) {
					$brand = implode( ', ', $names );
				}
			}
		}

		$brand = apply_filters( 'shanelle_product_summary_brand', $brand, $product );

		return is_string( $brand ) ? trim( $brand ) : '';
	}

	/**
	 * Resolve highlight bullets for the summary.
	 *
	 * @return array<int, string>
	 */
	public static function get_highlights(): array {
		$product    = self::get_product();
		$highlights = apply_filters( 'shanelle_product_summary_highlights', array(), $product );

		if ( ! is_array( $highlights ) ) {
			return array();
		}

		$cleaned = array();

		foreach ( $highlights as $highlight ) {
			if ( ! is_string( $highlight ) ) {
				continue;
			}

			$highlight = trim( wp_strip_all_tags( $highlight ) );

			if ( '' !== $highlight ) {
				$cleaned[] = $highlight;
			}
		}

		return array_values( array_unique( $cleaned ) );
	}

	/**
	 * Return summary region ID.
	 */
	public static function get_summary_id(): string {
		return 'product-summary-' . self::$args['summary_id'];
	}

	/**
	 * Return title element ID.
	 */
	public static function get_title_id(): string {
		return self::get_summary_id() . '-title';
	}

	/**
	 * Return highlights heading ID.
	 */
	public static function get_highlights_id(): string {
		return self::get_summary_id() . '-highlights';
	}

	/**
	 * Return active product ID.
	 */
	public static function get_render_product_id(): int {
		return self::get_product()->get_id();
	}

	/**
	 * Return price JSON for client-side variation updates.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_price_json(): array {
		$data = self::$price_data;

		return array(
			'hasPrice'       => (bool) $data['has_price'],
			'isOnSale'       => (bool) $data['is_on_sale'],
			'isRange'        => (bool) $data['is_range'],
			'currentHtml'    => (string) $data['current_html'],
			'regularHtml'    => (string) $data['regular_html'],
			'savingsHtml'    => (string) $data['savings_html'],
			'savingsPercent' => (int) $data['savings_percent'],
		);
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
				'summary_id' => wp_unique_id( 'summary-' ),
			)
		);
	}

	/**
	 * Return active product or throw logic guard.
	 */
	private static function get_product(): \WC_Product {
		if ( ! self::$product instanceof \WC_Product ) {
			throw new \LogicException( 'ProductSummary render context is not set.' );
		}

		return self::$product;
	}

	/**
	 * Return SKU display value.
	 */
	private static function get_sku_display( \WC_Product $product ): string {
		if ( ! wc_product_sku_enabled() ) {
			return '';
		}

		$sku = $product->get_sku();

		return is_string( $sku ) && '' !== $sku ? $sku : '';
	}

	/**
	 * Build rating display data.
	 *
	 * @return array{has_rating: bool, html: string, review_text: string}
	 */
	private static function get_rating_data( \WC_Product $product ): array {
		$count = (int) $product->get_rating_count();

		if ( $count <= 0 ) {
			return array(
				'has_rating'  => false,
				'html'        => '',
				'review_text' => '',
			);
		}

		$average = (float) $product->get_average_rating();
		$html    = wc_get_rating_html( $average, $count );

		return array(
			'has_rating'  => is_string( $html ) && '' !== $html,
			'html'        => is_string( $html ) ? $html : '',
			'review_text' => sprintf(
				/* translators: %d: number of reviews */
				_n( '%d reseña', '%d reseñas', $count, 'shanelle' ),
				$count
			),
		);
	}

	/**
	 * Build stock display data.
	 *
	 * @return array{show: bool, status: string, label: string}
	 */
	private static function get_stock_data( \WC_Product $product ): array {
		$availability = $product->get_availability();
		$class        = isset( $availability['class'] ) ? sanitize_html_class( (string) $availability['class'] ) : '';
		$label        = isset( $availability['availability'] )
			? wp_strip_all_tags( (string) $availability['availability'] )
			: '';

		if ( ! $product->is_in_stock() ) {
			return array(
				'show'   => true,
				'status' => 'outofstock',
				'label'  => '' !== $label ? $label : __( 'Agotado', 'shanelle' ),
			);
		}

		if ( $product->is_on_backorder() ) {
			return array(
				'show'   => true,
				'status' => 'onbackorder',
				'label'  => '' !== $label ? $label : __( 'Disponible bajo pedido', 'shanelle' ),
			);
		}

		if ( $product->managing_stock() && $product->get_stock_quantity() !== null ) {
			$quantity = (int) $product->get_stock_quantity();
			$threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );

			if ( $quantity > 0 && $quantity <= $threshold ) {
				return array(
					'show'   => true,
					'status' => 'lowstock',
					'label'  => '' !== $label ? $label : __( 'Poco stock', 'shanelle' ),
				);
			}
		}

		return array(
			'show'   => true,
			'status' => 'instock',
			'label'  => '' !== $label ? $label : __( 'En stock', 'shanelle' ),
		);
	}
}
