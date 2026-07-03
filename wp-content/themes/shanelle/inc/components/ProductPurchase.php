<?php
/**
 * Product purchase panel component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Purchase controls for WooCommerce single product pages.
 */
final class ProductPurchase {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/product-purchase';

	private const COMPONENT_URI = SHANELLE_URI . '/components/product-purchase';

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
	 * Cached purchase state for the active product.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot purchase panel hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets on single product pages.
	 */
	public static function enqueue_assets(): void {
		if ( ! shanelle_is_woocommerce_active() || ! is_product() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue purchase panel assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-product-purchase',
			self::COMPONENT_URI . '/product-purchase.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-product-purchase',
			self::COMPONENT_URI . '/product-purchase.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-product-purchase', 'type', 'module' );

		wp_localize_script(
			'shanelle-product-purchase',
			'shanelleProductPurchase',
			array(
				'ajaxUrl' => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'i18n'    => array(
					'quantity'         => __( 'Quantity', 'shanelle' ),
					'decrease'         => __( 'Decrease quantity', 'shanelle' ),
					'increase'         => __( 'Increase quantity', 'shanelle' ),
					'addToCart'        => __( 'Add to bag', 'shanelle' ),
					'adding'           => __( 'Adding…', 'shanelle' ),
					'added'            => __( 'Added to bag', 'shanelle' ),
					'error'            => __( 'Could not add to bag. Try again.', 'shanelle' ),
					'selectOptions'    => __( 'Select product options', 'shanelle' ),
					'buyNowSoon'       => __( 'Buy now (coming soon)', 'shanelle' ),
					'wishlistSoon'     => __( 'Wishlist (coming soon)', 'shanelle' ),
					'shippingSoon'     => __( 'Shipping estimate (coming soon)', 'shanelle' ),
					'deliverySoon'     => __( 'Delivery estimate (coming soon)', 'shanelle' ),
					'secureCheckout'   => __( 'Secure checkout', 'shanelle' ),
					'outOfStock'       => __( 'Out of stock', 'shanelle' ),
					'onBackorder'      => __( 'Available on backorder', 'shanelle' ),
					'lowStock'         => __( 'Low stock — order soon', 'shanelle' ),
					'onlyLeft'         => __( 'Only %d left in stock', 'shanelle' ),
					'quantityUpdated'  => __( 'Quantity updated to %d', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the purchase panel.
	 *
	 * @param \WC_Product          $product Product object.
	 * @param array<string, mixed> $args    Render arguments.
	 */
	public static function render( \WC_Product $product, array $args = array() ): void {
		if ( ! $product->is_purchasable() && ! $product->is_type( 'variable' ) ) {
			return;
		}

		self::$product = $product;
		self::$args    = self::parse_args( $args );
		self::$state   = self::build_purchase_state( $product );

		if ( ! wp_style_is( 'shanelle-product-purchase', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/product-purchase.php';

		self::reset_context();
	}

	/**
	 * Render stock and availability notices.
	 */
	public static function render_notices(): void {
		$state = self::$state;
		?>
		<div class="product-purchase__notices" data-shanelle-purchase-notices>
			<?php if ( ! $state['requires_variation'] && ! $state['is_in_stock'] && ! $state['is_on_backorder'] ) : ?>
				<p class="product-purchase__notice product-purchase__notice--outofstock" role="status" data-shanelle-purchase-notice="outofstock">
					<?php echo esc_html( (string) $state['stock_label'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! $state['requires_variation'] && $state['is_on_backorder'] ) : ?>
				<p class="product-purchase__notice product-purchase__notice--backorder" role="status" data-shanelle-purchase-notice="backorder">
					<?php echo esc_html( (string) $state['stock_label'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! $state['requires_variation'] && $state['is_low_stock'] && $state['is_in_stock'] ) : ?>
				<p class="product-purchase__notice product-purchase__notice--lowstock" role="status" data-shanelle-purchase-notice="lowstock">
					<?php
					echo esc_html(
						null !== $state['stock_quantity']
							? sprintf(
								/* translators: %d: items in stock */
								__( 'Only %d left in stock', 'shanelle' ),
								(int) $state['stock_quantity']
							)
							: (string) $state['stock_label']
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( $state['requires_variation'] ) : ?>
				<p class="product-purchase__notice product-purchase__notice--outofstock" role="status" data-shanelle-purchase-notice="outofstock" hidden></p>
				<p class="product-purchase__notice product-purchase__notice--backorder" role="status" data-shanelle-purchase-notice="backorder" hidden></p>
				<p class="product-purchase__notice product-purchase__notice--lowstock" role="status" data-shanelle-purchase-notice="lowstock" hidden></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render quantity selector.
	 */
	public static function render_quantity(): void {
		$state   = self::$state;
		$disabled = ! $state['can_purchase'] ? ' disabled' : '';
		?>
		<div class="product-purchase__quantity">
			<label class="product-purchase__quantity-label text-label" for="<?php echo esc_attr( self::get_quantity_input_id() ); ?>">
				<?php esc_html_e( 'Quantity', 'shanelle' ); ?>
			</label>
			<div class="product-purchase__stepper" data-shanelle-purchase-stepper>
				<button
					type="button"
					class="product-purchase__stepper-btn btn btn--icon btn--sm"
					data-shanelle-purchase-decrement
					aria-label="<?php esc_attr_e( 'Decrease quantity', 'shanelle' ); ?>"
					aria-controls="<?php echo esc_attr( self::get_quantity_input_id() ); ?>"
					<?php echo $disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					<?php self::render_icon( 'minus' ); ?>
				</button>
				<input
					type="number"
					class="product-purchase__quantity-input"
					id="<?php echo esc_attr( self::get_quantity_input_id() ); ?>"
					name="quantity"
					value="<?php echo esc_attr( (string) $state['default_quantity'] ); ?>"
					min="<?php echo esc_attr( (string) $state['min_quantity'] ); ?>"
					<?php if ( $state['max_quantity'] > 0 ) : ?>
						max="<?php echo esc_attr( (string) $state['max_quantity'] ); ?>"
					<?php endif; ?>
					step="1"
					inputmode="numeric"
					pattern="[0-9]*"
					data-shanelle-purchase-quantity
					aria-live="polite"
					<?php echo $disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
				<button
					type="button"
					class="product-purchase__stepper-btn btn btn--icon btn--sm"
					data-shanelle-purchase-increment
					aria-label="<?php esc_attr_e( 'Increase quantity', 'shanelle' ); ?>"
					aria-controls="<?php echo esc_attr( self::get_quantity_input_id() ); ?>"
					<?php echo $disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					<?php self::render_icon( 'plus' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render primary and secondary purchase actions.
	 */
	public static function render_actions(): void {
		$state = self::$state;
		?>
		<div class="product-purchase__actions">
			<button
				type="button"
				class="btn btn--primary btn--block btn--lg product-purchase__add"
				data-shanelle-purchase-add
				data-product-id="<?php echo esc_attr( (string) $state['product_id'] ); ?>"
				<?php echo $state['requires_variation'] ? 'disabled aria-disabled="true"' : ''; ?>
				<?php echo ! $state['requires_variation'] && ! $state['can_purchase'] ? 'disabled aria-disabled="true"' : ''; ?>
			>
				<?php echo esc_html( self::get_add_to_cart_text() ); ?>
			</button>

			<div class="product-purchase__secondary-actions">
				<button
					type="button"
					class="btn btn--secondary btn--block product-purchase__buy-now"
					data-shanelle-purchase-buy-now
					disabled
					aria-disabled="true"
					aria-label="<?php esc_attr_e( 'Buy now (coming soon)', 'shanelle' ); ?>"
				>
					<?php esc_html_e( 'Buy now', 'shanelle' ); ?>
					<span class="product-purchase__soon text-caption"><?php esc_html_e( 'Coming soon', 'shanelle' ); ?></span>
				</button>

				<button
					type="button"
					class="btn btn--outline btn--icon product-purchase__wishlist"
					data-shanelle-purchase-wishlist
					disabled
					aria-disabled="true"
					aria-label="<?php esc_attr_e( 'Wishlist (coming soon)', 'shanelle' ); ?>"
				>
					<?php self::render_icon( 'heart' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render shipping and delivery estimate placeholders.
	 */
	public static function render_estimates(): void {
		?>
		<div class="product-purchase__estimates">
			<div class="product-purchase__estimate product-purchase__estimate--shipping" data-shanelle-purchase-shipping aria-disabled="true">
				<?php self::render_icon( 'shipping' ); ?>
				<div class="product-purchase__estimate-copy">
					<p class="product-purchase__estimate-title text-label"><?php esc_html_e( 'Shipping', 'shanelle' ); ?></p>
					<p class="product-purchase__estimate-note text-caption text-muted"><?php esc_html_e( 'Shipping estimate (coming soon)', 'shanelle' ); ?></p>
				</div>
			</div>
			<div class="product-purchase__estimate product-purchase__estimate--delivery" data-shanelle-purchase-delivery aria-disabled="true">
				<?php self::render_icon( 'delivery' ); ?>
				<div class="product-purchase__estimate-copy">
					<p class="product-purchase__estimate-title text-label"><?php esc_html_e( 'Delivery', 'shanelle' ); ?></p>
					<p class="product-purchase__estimate-note text-caption text-muted"><?php esc_html_e( 'Delivery estimate (coming soon)', 'shanelle' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render trust badges and secure checkout message.
	 */
	public static function render_trust(): void {
		?>
		<div class="product-purchase__trust">
			<ul class="product-purchase__trust-list" aria-label="<?php esc_attr_e( 'Shopping guarantees', 'shanelle' ); ?>">
				<li class="product-purchase__trust-item">
					<?php self::render_icon( 'lock' ); ?>
					<span><?php esc_html_e( 'Secure checkout', 'shanelle' ); ?></span>
				</li>
				<li class="product-purchase__trust-item">
					<?php self::render_icon( 'returns' ); ?>
					<span><?php esc_html_e( 'Easy returns', 'shanelle' ); ?></span>
				</li>
				<li class="product-purchase__trust-item">
					<?php self::render_icon( 'quality' ); ?>
					<span><?php esc_html_e( 'Quality guaranteed', 'shanelle' ); ?></span>
				</li>
			</ul>
			<p class="product-purchase__secure-message text-caption text-muted">
				<?php esc_html_e( 'Your payment information is processed securely. We do not store credit card details.', 'shanelle' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Return purchase panel root ID.
	 */
	public static function get_root_id(): string {
		return 'product-purchase-' . self::$args['purchase_id'];
	}

	/**
	 * Return quantity input ID.
	 */
	public static function get_quantity_input_id(): string {
		return self::get_root_id() . '-quantity';
	}

	/**
	 * Return purchase state JSON for client hydration.
	 */
	public static function get_state_json(): string {
		return wp_json_encode( self::$state ) ?: '{}';
	}

	/**
	 * Return add to cart button text.
	 */
	public static function get_add_to_cart_text(): string {
		$text = self::get_product()->single_add_to_cart_text();

		return is_string( $text ) && '' !== $text ? $text : __( 'Add to bag', 'shanelle' );
	}

	/**
	 * Output inline SVG icon markup.
	 *
	 * @param string $icon Icon slug.
	 */
	public static function render_icon( string $icon ): void {
		$icons = array(
			'minus'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 12h14"/></svg>',
			'plus'     => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
			'heart'    => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 20.5 10.55 19.1C5.4 14.36 2 11.28 2 7.5A4.5 4.5 0 0 1 6.5 3 5.5 5.5 0 0 1 12 5.09 5.5 5.5 0 0 1 17.5 3 4.5 4.5 0 0 1 22 7.5c0 3.78-3.4 6.86-8.55 11.6Z"/></svg>',
			'shipping' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 7h11v8H3z"/><path d="M14 10h3l3 3v2h-6z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>',
			'delivery' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9"/></svg>',
			'lock'     => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
			'returns'  => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 7v6h6"/><path d="M21 17a8 8 0 0 0-14-5"/></svg>',
			'quality'  => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m12 3 2.4 4.8 5.4.8-3.9 3.8.9 5.3L12 15.8 7.2 17.7l.9-5.3L4.2 8.6l5.4-.8Z"/></svg>',
		);

		if ( ! isset( $icons[ $icon ] ) ) {
			return;
		}

		echo $icons[ $icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
				'purchase_id' => wp_unique_id( 'purchase-' ),
			)
		);
	}

	/**
	 * Reset active render context.
	 */
	private static function reset_context(): void {
		self::$product = null;
		self::$args    = array();
		self::$state   = array();
	}

	/**
	 * Return active product.
	 */
	private static function get_product(): \WC_Product {
		if ( ! self::$product instanceof \WC_Product ) {
			throw new \LogicException( 'ProductPurchase render context is not set.' );
		}

		return self::$product;
	}

	/**
	 * Build purchase state from WooCommerce product data.
	 *
	 * @return array<string, mixed>
	 */
	private static function build_purchase_state( \WC_Product $product ): array {
		$stock         = self::get_stock_state( $product );
		$min_quantity  = max( 1, (int) $product->get_min_purchase_quantity() );
		$max_quantity  = (int) $product->get_max_purchase_quantity();
		$stock_quantity = $product->managing_stock() ? $product->get_stock_quantity() : null;

		if ( is_numeric( $stock_quantity ) && (int) $stock_quantity > 0 ) {
			$max_quantity = $max_quantity > 0
				? min( $max_quantity, (int) $stock_quantity )
				: (int) $stock_quantity;
		}

		$requires_variation = $product->is_type( 'variable' );
		$can_purchase       = $product->is_purchasable();

		if ( $requires_variation ) {
			$can_purchase = true;
		} elseif ( ! $stock['is_in_stock'] && ! $stock['is_on_backorder'] ) {
			$can_purchase = false;
		}

		return array(
			'product_id'          => $product->get_id(),
			'product_type'        => $product->get_type(),
			'min_quantity'        => $min_quantity,
			'max_quantity'        => max( 0, $max_quantity ),
			'default_quantity'    => $min_quantity,
			'can_purchase'        => $can_purchase,
			'requires_variation'  => $requires_variation,
			'variation_id'        => 0,
			'is_in_stock'         => $stock['is_in_stock'],
			'is_on_backorder'     => $stock['is_on_backorder'],
			'is_low_stock'        => $stock['is_low_stock'],
			'stock_status'        => $stock['status'],
			'stock_label'         => $stock['label'],
			'stock_quantity'      => is_numeric( $stock_quantity ) ? (int) $stock_quantity : null,
		);
	}

	/**
	 * Build stock state from WooCommerce APIs.
	 *
	 * @return array{
	 *   is_in_stock: bool,
	 *   is_on_backorder: bool,
	 *   is_low_stock: bool,
	 *   status: string,
	 *   label: string
	 * }
	 */
	private static function get_stock_state( \WC_Product $product ): array {
		if ( ! $product->is_in_stock() ) {
			return array(
				'is_in_stock'     => false,
				'is_on_backorder' => false,
				'is_low_stock'    => false,
				'status'          => 'outofstock',
				'label'           => __( 'Out of stock', 'shanelle' ),
			);
		}

		if ( $product->is_on_backorder() ) {
			return array(
				'is_in_stock'     => true,
				'is_on_backorder' => true,
				'is_low_stock'    => false,
				'status'          => 'onbackorder',
				'label'           => __( 'Available on backorder', 'shanelle' ),
			);
		}

		$is_low_stock = false;

		if ( $product->managing_stock() && null !== $product->get_stock_quantity() ) {
			$quantity  = (int) $product->get_stock_quantity();
			$threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );
			$is_low_stock = $quantity > 0 && $quantity <= $threshold;
		}

		return array(
			'is_in_stock'     => true,
			'is_on_backorder' => false,
			'is_low_stock'    => $is_low_stock,
			'status'          => $is_low_stock ? 'lowstock' : 'instock',
			'label'           => $is_low_stock
				? __( 'Low stock — order soon', 'shanelle' )
				: __( 'In stock', 'shanelle' ),
		);
	}
}
