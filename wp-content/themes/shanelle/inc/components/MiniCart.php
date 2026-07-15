<?php
/**
 * Mini cart drawer component bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

use Shanelle\WooCommerce\ProductPrice;

defined( 'ABSPATH' ) || exit;

/**
 * Slide-over / bottom-sheet mini cart powered by WooCommerce cart APIs.
 */
final class MiniCart {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/mini-cart';

	private const COMPONENT_URI = SHANELLE_URI . '/components/mini-cart';

	private const ROOT_ID = 'shanelle-mini-cart';

	/**
	 * Cached cart state for the active render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot mini cart hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( self::class, 'render' ), 5 );
		add_filter( 'woocommerce_add_to_cart_fragments', array( self::class, 'add_fragments' ) );

		add_action( 'wc_ajax_shanelle_mini_cart_update', array( self::class, 'ajax_update_item' ) );
		add_action( 'wc_ajax_shanelle_mini_cart_get', array( self::class, 'ajax_get_cart' ) );
	}

	/**
	 * Enqueue mini cart assets site-wide when WooCommerce is active.
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() ) {
			return;
		}

		self::register_assets();
	}

	/**
	 * Register and enqueue component assets.
	 */
	private static function register_assets(): void {
		wp_enqueue_style(
			'shanelle-mini-cart',
			self::COMPONENT_URI . '/mini-cart.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-mini-cart',
			self::COMPONENT_URI . '/mini-cart.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-mini-cart', 'type', 'module' );

		wp_localize_script(
			'shanelle-mini-cart',
			'shanelleMiniCart',
			array(
				'ajaxUrl'       => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'cartUrl'       => wc_get_cart_url(),
				'checkoutUrl'   => wc_get_checkout_url(),
				'shopUrl'       => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
				'mobileQuery'   => '(max-width: 47.99rem)',
				'initialState'  => self::build_cart_state(),
				'i18n'          => array(
					'title'             => __( 'Tu bolsa', 'shanelle' ),
					'close'             => __( 'Cerrar bolsa', 'shanelle' ),
					'emptyTitle'        => __( 'Tu bolsa está vacía', 'shanelle' ),
					'emptyMessage'      => __( 'Agrega algo que te guste; lo guardaremos aquí mientras navegas.', 'shanelle' ),
					'continueShopping'  => __( 'Continuar comprando', 'shanelle' ),
					'viewCart'          => __( 'Ver bolsa', 'shanelle' ),
					'checkout'          => __( 'Pagar', 'shanelle' ),
					'subtotal'          => __( 'Subtotal', 'shanelle' ),
					'remove'            => __( 'Eliminar artículo', 'shanelle' ),
					'decrease'          => __( 'Disminuir cantidad', 'shanelle' ),
					'increase'          => __( 'Aumentar cantidad', 'shanelle' ),
					'quantity'          => __( 'Cantidad', 'shanelle' ),
					'itemCount'         => __( '%d artículo en la bolsa', 'shanelle' ),
					'itemsCount'        => __( '%d artículos en la bolsa', 'shanelle' ),
					'updated'           => __( 'Bolsa actualizada', 'shanelle' ),
					'removed'           => __( 'Artículo eliminado de la bolsa', 'shanelle' ),
					'error'             => __( 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.', 'shanelle' ),
					'loading'           => __( 'Actualizando bolsa…', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the mini cart shell in the footer.
	 */
	public static function render(): void {
		if ( is_admin() || ! function_exists( 'WC' ) ) {
			return;
		}

		self::$state = self::build_cart_state();

		if ( ! wp_style_is( 'shanelle-mini-cart', 'enqueued' ) ) {
			self::register_assets();
		}

		require self::COMPONENT_DIR . '/mini-cart.php';

		self::$state = array();
	}

	/**
	 * Append mini cart fragments for WooCommerce AJAX refresh.
	 *
	 * @param array<string, mixed> $fragments Existing fragments.
	 * @return array<string, mixed>
	 */
	public static function add_fragments( array $fragments ): array {
		self::$state = self::build_cart_state();

		ob_start();
		self::render_fragment();
		$fragments['[data-shanelle-mini-cart-fragment]'] = ob_get_clean() ?: '';

		ob_start();
		self::render_title_count();
		$fragments['[data-shanelle-mini-cart-count]'] = ob_get_clean() ?: '';

		self::$state = array();

		return $fragments;
	}

	/**
	 * AJAX: update cart item quantity.
	 */
	public static function ajax_update_item(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$cart_item_key = wc_clean( wp_unslash( (string) ( $_POST['cart_item_key'] ?? '' ) ) );
		$quantity      = wc_stock_amount( wp_unslash( $_POST['quantity'] ?? 0 ) );

		if ( '' === $cart_item_key || ! WC()->cart ) {
			wp_send_json_error(
				array(
					'message' => __( 'Artículo de bolsa no válido.', 'shanelle' ),
				),
				400
			);
		}

		$cart_item = WC()->cart->get_cart_item( $cart_item_key );

		if ( empty( $cart_item ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Artículo de bolsa no encontrado.', 'shanelle' ),
				),
				404
			);
		}

		if ( $quantity <= 0 ) {
			WC()->cart->remove_cart_item( $cart_item_key );
		} else {
			$updated = WC()->cart->set_quantity( $cart_item_key, $quantity, true );

			if ( false === $updated ) {
				wp_send_json_error(
					array(
						'message' => __( 'No se pudo actualizar la cantidad.', 'shanelle' ),
					),
					400
				);
			}
		}

		wp_send_json_success( self::build_ajax_response() );
	}

	/**
	 * AJAX: return the latest mini cart payload.
	 */
	public static function ajax_get_cart(): void {
		wp_send_json_success( self::build_ajax_response() );
	}

	/**
	 * Render replaceable cart body and footer markup.
	 */
	public static function render_fragment(): void {
		?>
		<div class="mini-cart__fragment" data-shanelle-mini-cart-fragment>
			<?php self::render_body(); ?>
			<?php self::render_footer(); ?>
		</div>
		<?php
	}

	/**
	 * Render cart title count badge.
	 */
	public static function render_title_count(): void {
		$count = (int) ( self::$state['count'] ?? 0 );
		?>
		<span class="mini-cart__count" data-shanelle-mini-cart-count aria-hidden="true">
			<?php echo esc_html( (string) $count ); ?>
		</span>
		<?php
	}

	/**
	 * Render scrollable cart body.
	 */
	public static function render_body(): void {
		$is_empty = (bool) ( self::$state['is_empty'] ?? true );
		?>
		<div class="mini-cart__body" data-shanelle-mini-cart-body>
			<?php if ( $is_empty ) : ?>
				<?php self::render_empty_state(); ?>
			<?php else : ?>
				<ul class="mini-cart__items" data-shanelle-mini-cart-items role="list">
					<?php self::render_items(); ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render cart line items.
	 */
	public static function render_items(): void {
		$items = self::$state['items'] ?? array();

		if ( ! is_array( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			self::render_item( $item );
		}
	}

	/**
	 * Render a single cart line item.
	 *
	 * @param array<string, mixed> $item Normalized cart item data.
	 */
	public static function render_item( array $item ): void {
		$key           = (string) ( $item['key'] ?? '' );
		$title         = (string) ( $item['title'] ?? '' );
		$permalink     = (string) ( $item['permalink'] ?? '' );
		$quantity      = max( 1, (int) ( $item['quantity'] ?? 1 ) );
		$min_quantity  = max( 1, (int) ( $item['min_quantity'] ?? 1 ) );
		$max_quantity  = max( 0, (int) ( $item['max_quantity'] ?? 0 ) );
		$input_id      = self::get_quantity_input_id( $key );
		$variation     = (string) ( $item['variation_summary'] ?? '' );
		$thumbnail     = (string) ( $item['thumbnail_html'] ?? '' );
		$price_html    = (string) ( $item['price_html'] ?? '' );
		$line_html     = (string) ( $item['line_subtotal_html'] ?? '' );
		?>
		<li
			class="mini-cart__item"
			data-shanelle-mini-cart-item
			data-cart-item-key="<?php echo esc_attr( $key ); ?>"
		>
			<div class="mini-cart__item-media">
				<a class="mini-cart__item-image-link" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			</div>

			<div class="mini-cart__item-details">
				<div class="mini-cart__item-header">
					<h3 class="mini-cart__item-title">
						<a class="mini-cart__item-title-link" href="<?php echo esc_url( $permalink ); ?>">
							<?php echo esc_html( $title ); ?>
						</a>
					</h3>
					<button
						type="button"
						class="mini-cart__remove btn btn--ghost btn--icon btn--sm"
						data-shanelle-mini-cart-remove
						data-cart-item-key="<?php echo esc_attr( $key ); ?>"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Eliminar %s de la bolsa', 'shanelle' ), $title ) ); ?>"
					>
						<?php self::render_icon( 'close' ); ?>
					</button>
				</div>

				<?php if ( '' !== $variation ) : ?>
					<div class="mini-cart__item-variation text-caption text-muted">
						<?php echo wp_kses_post( $variation ); ?>
					</div>
				<?php endif; ?>

				<div class="mini-cart__item-pricing">
					<?php if ( '' !== $price_html ) : ?>
						<div class="mini-cart__item-price text-price-sm">
							<?php echo wp_kses_post( $price_html ); ?>
						</div>
					<?php endif; ?>

					<?php if ( '' !== $line_html ) : ?>
						<div class="mini-cart__item-line-total text-price-sm">
							<?php echo wp_kses_post( $line_html ); ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="mini-cart__item-quantity">
					<span class="mini-cart__quantity-label text-label"><?php esc_html_e( 'Cantidad', 'shanelle' ); ?></span>
					<div class="mini-cart__stepper" data-shanelle-mini-cart-stepper>
						<button
							type="button"
							class="mini-cart__stepper-btn btn btn--icon btn--sm"
							data-shanelle-mini-cart-decrement
							data-cart-item-key="<?php echo esc_attr( $key ); ?>"
							aria-label="<?php esc_attr_e( 'Disminuir cantidad', 'shanelle' ); ?>"
							aria-controls="<?php echo esc_attr( $input_id ); ?>"
						>
							<?php self::render_icon( 'minus' ); ?>
						</button>
						<input
							type="number"
							class="mini-cart__quantity-input"
							id="<?php echo esc_attr( $input_id ); ?>"
							name="mini-cart-quantity-<?php echo esc_attr( $key ); ?>"
							value="<?php echo esc_attr( (string) $quantity ); ?>"
							min="<?php echo esc_attr( (string) $min_quantity ); ?>"
							<?php if ( $max_quantity > 0 ) : ?>
								max="<?php echo esc_attr( (string) $max_quantity ); ?>"
							<?php endif; ?>
							step="1"
							inputmode="numeric"
							pattern="[0-9]*"
							data-shanelle-mini-cart-quantity
							data-cart-item-key="<?php echo esc_attr( $key ); ?>"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Cantidad de %s', 'shanelle' ), $title ) ); ?>"
						>
						<button
							type="button"
							class="mini-cart__stepper-btn btn btn--icon btn--sm"
							data-shanelle-mini-cart-increment
							data-cart-item-key="<?php echo esc_attr( $key ); ?>"
							aria-label="<?php esc_attr_e( 'Aumentar cantidad', 'shanelle' ); ?>"
							aria-controls="<?php echo esc_attr( $input_id ); ?>"
						>
							<?php self::render_icon( 'plus' ); ?>
						</button>
					</div>
				</div>
			</div>
		</li>
		<?php
	}

	/**
	 * Render empty cart state.
	 */
	public static function render_empty_state(): void {
		$shop_url = (string) ( self::$state['urls']['shop'] ?? home_url( '/' ) );
		?>
		<div class="mini-cart__empty" data-shanelle-mini-cart-empty>
			<div class="mini-cart__empty-icon" aria-hidden="true">
				<?php self::render_icon( 'bag' ); ?>
			</div>
			<h3 class="mini-cart__empty-title"><?php esc_html_e( 'Tu bolsa está vacía', 'shanelle' ); ?></h3>
			<p class="mini-cart__empty-message text-muted">
				<?php esc_html_e( 'Agrega algo que te guste; lo guardaremos aquí mientras navegas.', 'shanelle' ); ?>
			</p>
			<a class="btn btn--primary btn--block mini-cart__empty-action" href="<?php echo esc_url( $shop_url ); ?>">
				<?php esc_html_e( 'Continuar comprando', 'shanelle' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render cart footer with subtotal and actions.
	 */
	public static function render_footer(): void {
		$is_empty     = (bool) ( self::$state['is_empty'] ?? true );
		$subtotal     = (string) ( self::$state['subtotal_html'] ?? '' );
		$cart_url     = (string) ( self::$state['urls']['cart'] ?? wc_get_cart_url() );
		$checkout_url = (string) ( self::$state['urls']['checkout'] ?? wc_get_checkout_url() );
		$shop_url     = (string) ( self::$state['urls']['shop'] ?? home_url( '/' ) );
		?>
		<div class="mini-cart__footer" data-shanelle-mini-cart-footer<?php echo $is_empty ? ' hidden' : ''; ?>>
			<div class="mini-cart__subtotal">
				<span class="mini-cart__subtotal-label text-label"><?php esc_html_e( 'Subtotal', 'shanelle' ); ?></span>
				<span class="mini-cart__subtotal-value text-price" data-shanelle-mini-cart-subtotal>
					<?php echo wp_kses_post( $subtotal ); ?>
				</span>
			</div>

			<div class="mini-cart__actions">
				<a class="btn btn--outline btn--block mini-cart__action mini-cart__action--continue" href="<?php echo esc_url( $shop_url ); ?>">
					<?php esc_html_e( 'Continuar comprando', 'shanelle' ); ?>
				</a>
				<a class="btn btn--secondary btn--block mini-cart__action mini-cart__action--cart" href="<?php echo esc_url( $cart_url ); ?>">
					<?php esc_html_e( 'Ver bolsa', 'shanelle' ); ?>
				</a>
				<a class="btn btn--primary btn--block mini-cart__action mini-cart__action--checkout" href="<?php echo esc_url( $checkout_url ); ?>">
					<?php esc_html_e( 'Pagar', 'shanelle' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Return root element ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return title element ID.
	 */
	public static function get_title_id(): string {
		return self::ROOT_ID . '-title';
	}

	/**
	 * Return cart state JSON for client hydration.
	 */
	public static function get_state_json(): string {
		return wp_json_encode( self::$state ) ?: '{}';
	}

	/**
	 * Return quantity input ID for a cart line.
	 */
	public static function get_quantity_input_id( string $cart_item_key ): string {
		return self::ROOT_ID . '-qty-' . sanitize_html_class( $cart_item_key );
	}

	/**
	 * Output inline SVG icon markup.
	 *
	 * @param string $icon Icon slug.
	 */
	public static function render_icon( string $icon ): void {
		$icons = array(
			'minus' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 12h14"/></svg>',
			'plus'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
			'close' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>',
			'bag'   => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M6 7h15l-1.5 11H7.5L6 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>',
		);

		if ( ! isset( $icons[ $icon ] ) ) {
			return;
		}

		echo $icons[ $icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build normalized cart state from WooCommerce cart object.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_cart_state(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return self::empty_cart_state();
		}

		$cart  = WC()->cart;
		$items = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$item = self::build_cart_item_data( $cart_item_key, $cart_item );

			if ( null !== $item ) {
				$items[] = $item;
			}
		}

		$count = $cart->get_cart_contents_count();

		return apply_filters(
			'shanelle_mini_cart_state',
			array(
				'count'          => $count,
				'is_empty'       => $cart->is_empty(),
				'subtotal_html'  => $cart->get_cart_subtotal(),
				'items'          => $items,
				'urls'           => array(
					'shop'     => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
					'cart'     => wc_get_cart_url(),
					'checkout' => wc_get_checkout_url(),
				),
				'title_label'    => sprintf(
					/* translators: %d: number of items in cart */
					_n( '%d artículo en la bolsa', '%d artículos en la bolsa', $count, 'shanelle' ),
					$count
				),
			),
			$cart
		);
	}

	/**
	 * Build AJAX response payload with fragments and cart state.
	 *
	 * @return array<string, mixed>
	 */
	private static function build_ajax_response(): array {
		self::$state = self::build_cart_state();

		ob_start();
		self::render_fragment();
		$fragment_html = ob_get_clean() ?: '';

		ob_start();
		self::render_title_count();
		$count_html = ob_get_clean() ?: '';

		$response = array(
			'cart'      => self::$state,
			'fragments' => array(
				'[data-shanelle-mini-cart-fragment]' => $fragment_html,
				'[data-shanelle-mini-cart-count]'    => $count_html,
			),
			'cart_hash' => WC()->cart ? WC()->cart->get_cart_hash() : '',
		);

		self::$state = array();

		return apply_filters( 'shanelle_mini_cart_ajax_response', $response );
	}

	/**
	 * Build normalized cart item data.
	 *
	 * @param string               $cart_item_key Cart item key.
	 * @param array<string, mixed> $cart_item     WooCommerce cart item.
	 * @return array<string, mixed>|null
	 */
	private static function build_cart_item_data( string $cart_item_key, array $cart_item ): ?array {
		$product = $cart_item['data'] ?? null;

		if ( ! $product instanceof \WC_Product || ! $product->exists() ) {
			return null;
		}

		$quantity     = max( 1, (int) ( $cart_item['quantity'] ?? 1 ) );
		$price_data   = ProductPrice::get_display_data( $product );
		$max_quantity = self::get_item_max_quantity( $product, $quantity );

		$item = array(
			'key'                => $cart_item_key,
			'product_id'         => $product->get_id(),
			'variation_id'       => (int) ( $cart_item['variation_id'] ?? 0 ),
			'title'              => $product->get_name(),
			'permalink'          => $product->get_permalink(),
			'thumbnail_html'     => self::get_item_thumbnail_html( $product, $cart_item ),
			'variation_summary'  => self::get_variation_summary_html( $cart_item ),
			'quantity'           => $quantity,
			'min_quantity'       => max( 1, (int) $product->get_min_purchase_quantity() ),
			'max_quantity'       => $max_quantity,
			'price_html'         => self::get_item_price_html( $price_data ),
			'line_subtotal_html' => WC()->cart->get_product_subtotal( $product, $quantity ),
		);

		return apply_filters( 'shanelle_mini_cart_item', $item, $cart_item, $product );
	}

	/**
	 * Return max purchasable quantity for a cart line product.
	 */
	private static function get_item_max_quantity( \WC_Product $product, int $current_quantity ): int {
		$max = (int) $product->get_max_purchase_quantity();

		if ( $max < 0 ) {
			$max = 0;
		}

		if ( $product->managing_stock() && is_numeric( $product->get_stock_quantity() ) ) {
			$stock_max = max( 0, (int) $product->get_stock_quantity() );
			$max       = $max > 0 ? min( $max, $stock_max ) : $stock_max;
		}

		if ( $max <= 0 ) {
			return 0;
		}

		return max( $current_quantity, $max );
	}

	/**
	 * Build compact price markup from ProductPrice data.
	 *
	 * @param array<string, mixed> $price_data Normalized price data.
	 */
	private static function get_item_price_html( array $price_data ): string {
		if ( empty( $price_data['has_price'] ) ) {
			return '';
		}

		$classes = array( 'mini-cart__price' );

		if ( ! empty( $price_data['is_on_sale'] ) ) {
			$classes[] = 'mini-cart__price--on-sale';
		}

		ob_start();
		?>
		<span class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<span class="mini-cart__price-current"><?php echo wp_kses_post( (string) $price_data['current_html'] ); ?></span>
			<?php if ( ! empty( $price_data['is_on_sale'] ) && ! empty( $price_data['regular_html'] ) ) : ?>
				<span class="mini-cart__price-regular"><?php echo wp_kses_post( (string) $price_data['regular_html'] ); ?></span>
			<?php endif; ?>
		</span>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Build cart item thumbnail markup.
	 *
	 * @param array<string, mixed> $cart_item WooCommerce cart item.
	 */
	private static function get_item_thumbnail_html( \WC_Product $product, array $cart_item ): string {
		$image_product = $product;
		$variation_id  = (int) ( $cart_item['variation_id'] ?? 0 );

		if ( $variation_id > 0 ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation instanceof \WC_Product && $variation->get_image_id() ) {
				$image_product = $variation;
			}
		}

		$image_id = $image_product->get_image_id();

		if ( $image_id <= 0 ) {
			return wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'mini-cart__item-image' ) );
		}

		return wp_get_attachment_image(
			$image_id,
			'woocommerce_thumbnail',
			false,
			array(
				'class'    => 'mini-cart__item-image',
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
	}

	/**
	 * Build variation summary markup for a cart line.
	 *
	 * @param array<string, mixed> $cart_item WooCommerce cart item.
	 */
	private static function get_variation_summary_html( array $cart_item ): string {
		$formatted = wc_get_formatted_cart_item_data( $cart_item, true );

		if ( ! is_string( $formatted ) || '' === trim( wp_strip_all_tags( $formatted ) ) ) {
			return '';
		}

		return $formatted;
	}

	/**
	 * Return empty cart state defaults.
	 *
	 * @return array<string, mixed>
	 */
	private static function empty_cart_state(): array {
		return array(
			'count'         => 0,
			'is_empty'      => true,
			'subtotal_html' => wc_price( 0 ),
			'items'         => array(),
			'urls'          => array(
				'shop'     => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
				'cart'     => wc_get_cart_url(),
				'checkout' => wc_get_checkout_url(),
			),
			'title_label'   => __( '0 artículos en la bolsa', 'shanelle' ),
		);
	}
}
