<?php
/**
 * Product purchase panel component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\WooCommerce\ProductPrice;

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
				'ajaxUrl'     => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'checkoutUrl' => wc_get_checkout_url(),
				'i18n'        => array(
					'quantity'        => __( 'Cantidad', 'shanelle' ),
					'decrease'          => __( 'Disminuir cantidad', 'shanelle' ),
					'increase'          => __( 'Aumentar cantidad', 'shanelle' ),
					'addToCart'         => __( 'Agregar a la bolsa', 'shanelle' ),
					'adding'            => __( 'Agregando…', 'shanelle' ),
					'added'             => __( 'Agregado a la bolsa', 'shanelle' ),
					'error'             => __( 'No se pudo agregar a la bolsa. Intenta de nuevo.', 'shanelle' ),
					'selectOptions'     => __( 'Selecciona las opciones del producto', 'shanelle' ),
					'buyNow'            => __( 'Comprar ahora', 'shanelle' ),
					'buying'            => __( 'Procesando…', 'shanelle' ),
					'addToWishlist'     => __( 'Agregar a favoritos', 'shanelle' ),
					'removeFromWishlist'=> __( 'Quitar de favoritos', 'shanelle' ),
					'addedToWishlist'   => __( 'Agregado a favoritos', 'shanelle' ),
					'removedFromWishlist'=> __( 'Eliminado de favoritos', 'shanelle' ),
					'secureCheckout'    => __( 'Pago seguro', 'shanelle' ),
					'outOfStock'        => __( 'Agotado', 'shanelle' ),
					'onBackorder'       => __( 'Disponible bajo pedido', 'shanelle' ),
					'lowStock'          => __( 'Poco stock — pide pronto', 'shanelle' ),
					'onlyLeft'          => __( 'Solo quedan %d en stock', 'shanelle' ),
					'quantityUpdated'   => __( 'Cantidad actualizada a %d', 'shanelle' ),
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
	 * Render mobile sticky add-to-cart bar (shown when main ATC scrolls out of view).
	 */
	public static function render_sticky_bar(): void {
		$state = self::$state;
		$price = ProductPrice::get_display_data( self::get_product() );
		?>
		<div
			class="product-purchase__sticky"
			data-shanelle-purchase-sticky
			hidden
		>
			<div class="product-purchase__sticky-inner">
				<?php if ( ! empty( $price['has_price'] ) && '' !== (string) $price['current_html'] ) : ?>
					<p class="product-purchase__sticky-price" data-shanelle-purchase-sticky-price>
						<?php echo wp_kses_post( (string) $price['current_html'] ); ?>
					</p>
				<?php endif; ?>
				<button
					type="button"
					class="btn btn--primary btn--lg product-purchase__sticky-add"
					data-shanelle-purchase-sticky-add
					data-product-id="<?php echo esc_attr( (string) $state['product_id'] ); ?>"
					<?php echo $state['requires_variation'] ? 'disabled aria-disabled="true"' : ''; ?>
					<?php echo ! $state['requires_variation'] && ! $state['can_purchase'] ? 'disabled aria-disabled="true"' : ''; ?>
				>
					<?php echo esc_html( self::get_add_to_cart_text() ); ?>
				</button>
			</div>
		</div>
		<?php
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
								__( 'Solo quedan %d en stock', 'shanelle' ),
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
				<?php esc_html_e( 'Cantidad', 'shanelle' ); ?>
			</label>
			<div class="product-purchase__stepper" data-shanelle-purchase-stepper>
				<button
					type="button"
					class="product-purchase__stepper-btn btn btn--icon btn--sm"
					data-shanelle-purchase-decrement
					aria-label="<?php esc_attr_e( 'Disminuir cantidad', 'shanelle' ); ?>"
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
					aria-label="<?php esc_attr_e( 'Aumentar cantidad', 'shanelle' ); ?>"
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
					<?php echo $state['requires_variation'] ? 'disabled aria-disabled="true"' : ''; ?>
					<?php echo ! $state['requires_variation'] && ! $state['can_purchase'] ? 'disabled aria-disabled="true"' : ''; ?>
				>
					<?php esc_html_e( 'Comprar ahora', 'shanelle' ); ?>
				</button>

				<button
					type="button"
					class="btn btn--outline btn--icon product-purchase__wishlist"
					data-shanelle-purchase-wishlist
					data-product-id="<?php echo esc_attr( (string) $state['product_id'] ); ?>"
					aria-pressed="false"
					aria-label="<?php esc_attr_e( 'Agregar a favoritos', 'shanelle' ); ?>"
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
		$shipping = self::get_shipping_estimate();
		$delivery = self::get_delivery_estimate();
		?>
		<div class="product-purchase__estimates">
			<button
				type="button"
				class="product-purchase__estimate product-purchase__estimate--shipping"
				data-shanelle-purchase-shipping
				aria-label="<?php echo esc_attr( (string) $shipping['aria'] ); ?>"
			>
				<span class="product-purchase__estimate-icon" aria-hidden="true"><?php self::render_icon( 'shipping' ); ?></span>
				<span class="product-purchase__estimate-copy">
					<span class="product-purchase__estimate-title text-label"><?php echo esc_html( (string) $shipping['title'] ); ?></span>
					<span class="product-purchase__estimate-value"><?php echo esc_html( (string) $shipping['value'] ); ?></span>
					<span class="product-purchase__estimate-note text-caption text-muted"><?php echo esc_html( (string) $shipping['note'] ); ?></span>
				</span>
			</button>
			<button
				type="button"
				class="product-purchase__estimate product-purchase__estimate--delivery"
				data-shanelle-purchase-delivery
				aria-label="<?php echo esc_attr( (string) $delivery['aria'] ); ?>"
			>
				<span class="product-purchase__estimate-icon" aria-hidden="true"><?php self::render_icon( 'delivery' ); ?></span>
				<span class="product-purchase__estimate-copy">
					<span class="product-purchase__estimate-title text-label"><?php echo esc_html( (string) $delivery['title'] ); ?></span>
					<span class="product-purchase__estimate-value"><?php echo esc_html( (string) $delivery['value'] ); ?></span>
					<span class="product-purchase__estimate-note text-caption text-muted"><?php echo esc_html( (string) $delivery['note'] ); ?></span>
				</span>
			</button>
		</div>
		<?php
	}

	/**
	 * Render trust badges and secure checkout message.
	 */
	public static function render_trust(): void {
		?>
		<div class="product-purchase__trust">
			<ul class="product-purchase__trust-list" aria-label="<?php esc_attr_e( 'Garantías de compra', 'shanelle' ); ?>">
				<li class="product-purchase__trust-item">
					<?php self::render_icon( 'lock' ); ?>
					<span><?php esc_html_e( 'Pago seguro', 'shanelle' ); ?></span>
				</li>
				<li class="product-purchase__trust-item">
					<?php self::render_icon( 'returns' ); ?>
					<span><?php esc_html_e( 'Devoluciones fáciles', 'shanelle' ); ?></span>
				</li>
				<li class="product-purchase__trust-item">
					<?php self::render_icon( 'quality' ); ?>
					<span><?php esc_html_e( 'Calidad garantizada', 'shanelle' ); ?></span>
				</li>
			</ul>
			<p class="product-purchase__secure-message text-caption text-muted">
				<?php esc_html_e( 'Tu información de pago se procesa de forma segura. No almacenamos los datos de tarjetas de crédito.', 'shanelle' ); ?>
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
		return __( 'Agregar a la bolsa', 'shanelle' );
	}

	/**
	 * Return shipping estimate copy.
	 *
	 * @return array{title: string, value: string, note: string, aria: string}
	 */
	public static function get_shipping_estimate(): array {
		$ship_date = wp_date( 'M j', strtotime( '+2 weekdays' ) );

		$data = array(
			'title' => __( 'Envío', 'shanelle' ),
			'value' => sprintf(
				/* translators: %s: estimated ship date */
				__( 'Sale el %s', 'shanelle' ),
				$ship_date
			),
			'note'  => __( 'Estimación · Envío estándar en Nicaragua', 'shanelle' ),
			'aria'  => sprintf(
				/* translators: %s: estimated ship date */
				__( 'Estimación de envío: sale el %s. Ver detalles de envío.', 'shanelle' ),
				$ship_date
			),
		);

		/**
		 * Filter product page shipping estimate copy.
		 *
		 * @param array<string, string> $data Shipping estimate data.
		 * @param \WC_Product           $product Product object.
		 */
		return apply_filters( 'shanelle_product_shipping_estimate', $data, self::get_product() );
	}

	/**
	 * Return delivery estimate copy.
	 *
	 * @return array{title: string, value: string, note: string, aria: string}
	 */
	public static function get_delivery_estimate(): array {
		$delivery_date = wp_date( 'M j', strtotime( '+5 weekdays' ) );

		$data = array(
			'title' => __( 'Entrega', 'shanelle' ),
			'value' => sprintf(
				/* translators: %s: estimated delivery date */
				__( 'Llega el %s', 'shanelle' ),
				$delivery_date
			),
			'note'  => __( 'Estimación · Express disponible al pagar', 'shanelle' ),
			'aria'  => sprintf(
				/* translators: %s: estimated delivery date */
				__( 'Estimación de entrega: llega el %s. Ver detalles de envío.', 'shanelle' ),
				$delivery_date
			),
		);

		/**
		 * Filter product page delivery estimate copy.
		 *
		 * @param array<string, string> $data Delivery estimate data.
		 * @param \WC_Product           $product Product object.
		 */
		return apply_filters( 'shanelle_product_delivery_estimate', $data, self::get_product() );
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
				'label'           => __( 'Agotado', 'shanelle' ),
			);
		}

		if ( $product->is_on_backorder() ) {
			return array(
				'is_in_stock'     => true,
				'is_on_backorder' => true,
				'is_low_stock'    => false,
				'status'          => 'onbackorder',
				'label'           => __( 'Disponible bajo pedido', 'shanelle' ),
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
				? __( 'Poco stock — pide pronto', 'shanelle' )
				: __( 'En stock', 'shanelle' ),
		);
	}
}
