<?php
/**
 * Cart page composer bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Composes the WooCommerce cart page from theme components and WC cart APIs.
 */
final class CartPage {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/cart-page';

	private const COMPONENT_URI = SHANELLE_URI . '/components/cart-page';

	private const ROOT_ID = 'shanelle-cart-page';

	private const MOD_CROSS_SELLS_ENABLED = 'shanelle_cart_page_cross_sells_enabled';

	private const MOD_CROSS_SELLS_TITLE = 'shanelle_cart_page_cross_sells_title';

	private const MOD_CROSS_SELLS_LIMIT = 'shanelle_cart_page_cross_sells_limit';

	private const MOD_SHIPPING_ESTIMATOR = 'shanelle_cart_page_shipping_estimator';

	/**
	 * Active page state for the render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot cart page hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp', array( self::class, 'configure_cart_hooks' ), 20 );
		add_filter( 'woocommerce_add_to_cart_fragments', array( self::class, 'add_fragments' ) );

		add_action( 'wc_ajax_shanelle_cart_page_get', array( self::class, 'ajax_get_page' ) );
	}

	/**
	 * Adjust WooCommerce hooks on the cart page.
	 */
	public static function configure_cart_hooks(): void {
		if ( ! is_cart() ) {
			return;
		}

		remove_action( 'woocommerce_before_main_content', 'shanelle_before_main_content', 5 );
		remove_action( 'woocommerce_after_main_content', 'shanelle_after_main_content', 50 );
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10 );
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10 );
		remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );

		add_filter( 'woocommerce_show_page_title', '__return_false' );
		add_filter( 'the_title', array( self::class, 'hide_page_title' ), 10, 2 );
	}

	/**
	 * Hide the default WordPress cart page title; the component renders its own heading.
	 *
	 * @param string          $title Post title.
	 * @param int|string|null $id    Post ID.
	 */
	public static function hide_page_title( string $title, $id = null ): string {
		if ( ! is_cart() || ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}

		$cart_page_id = wc_get_page_id( 'cart' );

		if ( $cart_page_id > 0 && (int) $id === $cart_page_id ) {
			return '';
		}

		return $title;
	}

	/**
	 * Register Theme Customizer settings for the cart page.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'shanelle_cart_page',
			array(
				'title'       => __( 'Página de bolsa', 'shanelle' ),
				'description' => __( 'Configura las recomendaciones de productos de la página de bolsa.', 'shanelle' ),
				'priority'    => 170,
			)
		);

		$wp_customize->add_setting(
			self::MOD_CROSS_SELLS_ENABLED,
			array(
				'default'           => true,
				'sanitize_callback' => array( self::class, 'sanitize_checkbox' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_CROSS_SELLS_ENABLED,
			array(
				'label'   => __( 'Mostrar productos relacionados', 'shanelle' ),
				'section' => 'shanelle_cart_page',
				'type'    => 'checkbox',
			)
		);

		$wp_customize->add_setting(
			self::MOD_CROSS_SELLS_TITLE,
			array(
				'default'           => __( 'También te puede gustar', 'shanelle' ),
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_CROSS_SELLS_TITLE,
			array(
				'label'   => __( 'Título de la sección de relacionados', 'shanelle' ),
				'section' => 'shanelle_cart_page',
				'type'    => 'text',
			)
		);

		$wp_customize->add_setting(
			self::MOD_CROSS_SELLS_LIMIT,
			array(
				'default'           => 4,
				'sanitize_callback' => array( self::class, 'sanitize_cross_sells_limit' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_CROSS_SELLS_LIMIT,
			array(
				'label'       => __( 'Límite de productos relacionados', 'shanelle' ),
				'section'     => 'shanelle_cart_page',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 2,
					'max'  => 12,
					'step' => 1,
				),
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHIPPING_ESTIMATOR,
			__( 'Mostrar estimador de envío', 'shanelle' ),
			true
		);
	}

	/**
	 * Enqueue cart page assets.
	 */
	public static function enqueue_assets(): void {
		if ( ! is_cart() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-cart-page',
			self::COMPONENT_URI . '/cart-page.css',
			array( 'shanelle-main', 'shanelle-product-grid', 'shanelle-product-card' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-cart-page',
			self::COMPONENT_URI . '/cart-page.js',
			array(),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-cart-page', 'type', 'module' );

		wp_localize_script(
			'shanelle-cart-page',
			'shanelleCartPage',
			array(
				'ajaxUrl'       => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'cartUrl'       => wc_get_cart_url(),
				'checkoutUrl'   => wc_get_checkout_url(),
				'shopUrl'       => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
				'initialState'  => self::build_page_state(),
				'i18n'          => array(
					'pageTitle'      => __( 'Tu bolsa', 'shanelle' ),
					'emptyTitle'     => __( 'Tu bolsa está vacía', 'shanelle' ),
					'emptyMessage'   => __( 'Agrega algo que te guste; lo guardaremos aquí mientras navegas.', 'shanelle' ),
					'continueShopping' => __( 'Continuar comprando', 'shanelle' ),
					'product'        => __( 'Producto', 'shanelle' ),
					'price'          => __( 'Precio', 'shanelle' ),
					'quantity'       => __( 'Cantidad', 'shanelle' ),
					'subtotal'       => __( 'Subtotal', 'shanelle' ),
					'remove'         => __( 'Eliminar artículo', 'shanelle' ),
					'decrease'       => __( 'Disminuir cantidad', 'shanelle' ),
					'increase'       => __( 'Aumentar cantidad', 'shanelle' ),
					'updateCart'     => __( 'Actualizar bolsa', 'shanelle' ),
					'applyCoupon'    => __( 'Aplicar cupón', 'shanelle' ),
					'couponLabel'    => __( 'Código de cupón', 'shanelle' ),
					'couponPlaceholder' => __( 'Ingresa el código de cupón', 'shanelle' ),
					'orderSummary'   => __( 'Resumen del pedido', 'shanelle' ),
					'total'          => __( 'Total', 'shanelle' ),
					'checkout'       => __( 'Ir a pagar', 'shanelle' ),
					'shipping'       => __( 'Envío', 'shanelle' ),
					'shippingEstimate' => __( 'Calcular envío', 'shanelle' ),
					'shippingToggle' => __( 'Calcular envío', 'shanelle' ),
					'shippingToggleClose' => __( 'Cerrar estimador de envío', 'shanelle' ),
					'shippingPending' => __( 'Calcula abajo', 'shanelle' ),
					'updated'        => __( 'Bolsa actualizada', 'shanelle' ),
					'removed'        => __( 'Artículo eliminado de la bolsa', 'shanelle' ),
					'error'          => __( 'No se pudo actualizar tu bolsa. Inténtalo de nuevo.', 'shanelle' ),
					'loading'        => __( 'Actualizando bolsa…', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the cart page composition.
	 */
	public static function render(): void {
		if ( ! shanelle_is_woocommerce_active() || ! WC()->cart ) {
			return;
		}

		self::$state = self::build_page_state();

		if ( ! wp_style_is( 'shanelle-cart-page', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		if ( self::should_enqueue_shipping_assets() ) {
			wp_enqueue_script( 'wc-country-select' );
			wp_enqueue_script( 'wc-address-i18n' );
		}

		require self::COMPONENT_DIR . '/cart-page.php';

		self::$state = array();
	}

	/**
	 * Render WooCommerce notices.
	 */
	public static function render_notices(): void {
		if ( ! function_exists( 'woocommerce_output_all_notices' ) ) {
			return;
		}

		echo '<div class="cart-page__notices">';
		woocommerce_output_all_notices();
		echo '</div>';
	}

	/**
	 * Render empty cart state.
	 */
	public static function render_empty(): void {
		$shop_url = (string) ( self::$state['urls']['shop'] ?? home_url( '/' ) );
		?>
		<div class="cart-page__empty" data-shanelle-cart-page-empty>
			<div class="cart-page__empty-icon" aria-hidden="true">
				<?php self::render_icon( 'bag' ); ?>
			</div>
			<h2 class="cart-page__empty-title text-h2"><?php esc_html_e( 'Tu bolsa está vacía', 'shanelle' ); ?></h2>
			<p class="cart-page__empty-message text-body text-muted">
				<?php esc_html_e( 'Agrega algo que te guste; lo guardaremos aquí mientras navegas.', 'shanelle' ); ?>
			</p>
			<a class="btn btn--primary cart-page__empty-action" href="<?php echo esc_url( $shop_url ); ?>">
				<?php esc_html_e( 'Continuar comprando', 'shanelle' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render cart form and summary layout.
	 */
	public static function render_cart_layout(): void {
		self::render_fragment();
	}

	/**
	 * Render replaceable cart page fragment.
	 */
	public static function render_fragment(): void {
		?>
		<div class="cart-page__layout" data-shanelle-cart-page-fragment>
			<div class="cart-page__main">
				<form
					class="cart-page__form woocommerce-cart-form"
					action="<?php echo esc_url( wc_get_cart_url() ); ?>"
					method="post"
					data-shanelle-cart-page-form
				>
					<?php self::render_items_table(); ?>
					<?php self::render_coupon_form(); ?>
					<?php self::render_form_actions(); ?>
				</form>
			</div>

			<aside class="cart-page__summary" aria-labelledby="<?php echo esc_attr( self::get_summary_heading_id() ); ?>">
				<?php self::render_summary(); ?>
			</aside>
		</div>
		<?php
	}

	/**
	 * Render cart line items.
	 */
	public static function render_items_table(): void {
		$items = self::$state['items'] ?? array();
		?>
		<div class="cart-page__items" role="table" aria-label="<?php esc_attr_e( 'Artículos de la bolsa', 'shanelle' ); ?>">
			<div class="cart-page__items-head" role="row">
				<span class="cart-page__col cart-page__col--product" role="columnheader"><?php esc_html_e( 'Producto', 'shanelle' ); ?></span>
				<span class="cart-page__col cart-page__col--price" role="columnheader"><?php esc_html_e( 'Precio', 'shanelle' ); ?></span>
				<span class="cart-page__col cart-page__col--quantity" role="columnheader"><?php esc_html_e( 'Cantidad', 'shanelle' ); ?></span>
				<span class="cart-page__col cart-page__col--subtotal" role="columnheader"><?php esc_html_e( 'Subtotal', 'shanelle' ); ?></span>
				<span class="cart-page__col cart-page__col--remove" role="columnheader">
					<span class="screen-reader-text"><?php esc_html_e( 'Eliminar artículo', 'shanelle' ); ?></span>
				</span>
			</div>

			<ul class="cart-page__items-body" data-shanelle-cart-page-items role="list">
				<?php
				foreach ( $items as $item ) {
					if ( is_array( $item ) ) {
						self::render_item( $item );
					}
				}
				?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render a cart line item row.
	 *
	 * @param array<string, mixed> $item Normalized cart item data.
	 */
	public static function render_item( array $item ): void {
		$key          = (string) ( $item['key'] ?? '' );
		$title        = (string) ( $item['title'] ?? '' );
		$permalink    = (string) ( $item['permalink'] ?? '' );
		$quantity     = max( 0, (int) ( $item['quantity'] ?? 1 ) );
		$min_quantity = max( 0, (int) ( $item['min_quantity'] ?? 1 ) );
		$max_quantity = max( 0, (int) ( $item['max_quantity'] ?? 0 ) );
		$input_id     = self::get_quantity_input_id( $key );
		$variation    = (string) ( $item['variation_summary'] ?? '' );
		$thumbnail    = (string) ( $item['thumbnail_html'] ?? '' );
		$price_html   = (string) ( $item['price_html'] ?? '' );
		$line_html    = (string) ( $item['line_subtotal_html'] ?? '' );
		$remove_url   = esc_url( wc_get_cart_remove_url( $key ) );
		?>
		<li
			class="cart-page__item"
			data-shanelle-cart-page-item
			data-cart-item-key="<?php echo esc_attr( $key ); ?>"
			role="row"
		>
			<div class="cart-page__col cart-page__col--product" role="cell">
				<div class="cart-page__product">
					<a class="cart-page__product-media" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
						<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
					<div class="cart-page__product-copy">
						<h3 class="cart-page__product-title">
							<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
						</h3>
						<?php if ( '' !== $variation ) : ?>
							<div class="cart-page__product-variation text-caption text-muted">
								<?php echo wp_kses_post( $variation ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="cart-page__col cart-page__col--price text-price-sm" role="cell" data-title="<?php esc_attr_e( 'Precio', 'shanelle' ); ?>">
				<?php echo wp_kses_post( $price_html ); ?>
			</div>

			<div class="cart-page__col cart-page__col--quantity" role="cell" data-title="<?php esc_attr_e( 'Cantidad', 'shanelle' ); ?>">
				<div class="cart-page__stepper" data-shanelle-cart-page-stepper>
					<button
						type="button"
						class="cart-page__stepper-btn btn btn--icon btn--sm"
						data-shanelle-cart-page-decrement
						data-cart-item-key="<?php echo esc_attr( $key ); ?>"
						aria-label="<?php esc_attr_e( 'Disminuir cantidad', 'shanelle' ); ?>"
						aria-controls="<?php echo esc_attr( $input_id ); ?>"
					>
						<?php self::render_icon( 'minus' ); ?>
					</button>
					<input
						type="number"
						class="cart-page__quantity-input"
						id="<?php echo esc_attr( $input_id ); ?>"
						name="cart[<?php echo esc_attr( $key ); ?>][qty]"
						value="<?php echo esc_attr( (string) $quantity ); ?>"
						min="<?php echo esc_attr( (string) $min_quantity ); ?>"
						<?php if ( $max_quantity > 0 ) : ?>
							max="<?php echo esc_attr( (string) $max_quantity ); ?>"
						<?php endif; ?>
						step="1"
						inputmode="numeric"
						pattern="[0-9]*"
						data-shanelle-cart-page-quantity
						data-cart-item-key="<?php echo esc_attr( $key ); ?>"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Cantidad de %s', 'shanelle' ), $title ) ); ?>"
					>
					<button
						type="button"
						class="cart-page__stepper-btn btn btn--icon btn--sm"
						data-shanelle-cart-page-increment
						data-cart-item-key="<?php echo esc_attr( $key ); ?>"
						aria-label="<?php esc_attr_e( 'Aumentar cantidad', 'shanelle' ); ?>"
						aria-controls="<?php echo esc_attr( $input_id ); ?>"
					>
						<?php self::render_icon( 'plus' ); ?>
					</button>
				</div>
			</div>

			<div class="cart-page__col cart-page__col--subtotal text-price-sm" role="cell" data-title="<?php esc_attr_e( 'Subtotal', 'shanelle' ); ?>" data-shanelle-cart-page-line-subtotal>
				<?php echo wp_kses_post( $line_html ); ?>
			</div>

			<div class="cart-page__col cart-page__col--remove" role="cell">
				<a
					class="cart-page__remove btn btn--ghost btn--icon btn--sm"
					href="<?php echo esc_url( $remove_url ); ?>"
					data-shanelle-cart-page-remove
					data-cart-item-key="<?php echo esc_attr( $key ); ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Eliminar %s de la bolsa', 'shanelle' ), $title ) ); ?>"
				>
					<?php self::render_icon( 'close' ); ?>
				</a>
			</div>
		</li>
		<?php
	}

	/**
	 * Render coupon form controls.
	 */
	public static function render_coupon_form(): void {
		if ( empty( self::$state['coupons_enabled'] ) ) {
			return;
		}
		?>
		<div class="cart-page__coupon">
			<label class="cart-page__coupon-label text-label" for="<?php echo esc_attr( self::get_coupon_input_id() ); ?>">
				<?php esc_html_e( 'Código de cupón', 'shanelle' ); ?>
			</label>
			<div class="cart-page__coupon-row">
				<input
					type="text"
					class="cart-page__coupon-input"
					id="<?php echo esc_attr( self::get_coupon_input_id() ); ?>"
					name="coupon_code"
					value=""
					placeholder="<?php esc_attr_e( 'Ingresa el código de cupón', 'shanelle' ); ?>"
					autocomplete="off"
				>
				<button type="submit" class="btn btn--outline cart-page__coupon-submit" name="apply_coupon" value="1">
					<?php esc_html_e( 'Aplicar cupón', 'shanelle' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render form actions and security fields.
	 */
	public static function render_form_actions(): void {
		?>
		<div class="cart-page__form-actions">
			<button type="submit" class="btn btn--secondary cart-page__update" name="update_cart" value="1">
				<?php esc_html_e( 'Actualizar bolsa', 'shanelle' ); ?>
			</button>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		</div>
		<?php
	}

	/**
	 * Render order summary sidebar.
	 */
	public static function render_summary(): void {
		$totals = is_array( self::$state['totals'] ?? null ) ? self::$state['totals'] : array();
		?>
		<div class="cart-page__summary-card">
			<h2 id="<?php echo esc_attr( self::get_summary_heading_id() ); ?>" class="cart-page__summary-title text-h3">
				<?php esc_html_e( 'Resumen del pedido', 'shanelle' ); ?>
			</h2>

			<?php self::render_shipping_estimator(); ?>

			<dl class="cart-page__totals" data-shanelle-cart-page-totals>
				<?php self::render_totals_rows( $totals ); ?>
			</dl>

			<div class="cart-page__summary-actions">
				<a class="btn btn--primary btn--block btn--lg cart-page__checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
					<?php esc_html_e( 'Ir a pagar', 'shanelle' ); ?>
				</a>
				<a class="btn btn--outline btn--block cart-page__continue" href="<?php echo esc_url( (string) ( self::$state['urls']['shop'] ?? home_url( '/' ) ) ); ?>">
					<?php esc_html_e( 'Continuar comprando', 'shanelle' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render WooCommerce shipping calculator when enabled.
	 */
	public static function render_shipping_estimator(): void {
		if ( empty( self::$state['settings']['shipping_estimator'] ) ) {
			return;
		}

		if ( 'no' === get_option( 'woocommerce_enable_shipping_calc' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
			return;
		}
		?>
		<div class="cart-page__shipping" data-shanelle-cart-page-shipping>
			<?php
			woocommerce_shipping_calculator(
				apply_filters(
					'shanelle_cart_page_shipping_calculator_button_text',
					__( 'Calcular envío', 'shanelle' )
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Render totals rows from normalized totals data.
	 *
	 * @param array<int, array<string, string>> $rows Totals rows.
	 */
	public static function render_totals_rows( array $rows ): void {
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$label = (string) ( $row['label'] ?? '' );
			$value = (string) ( $row['value_html'] ?? '' );
			$class = (string) ( $row['class'] ?? '' );

			if ( '' === $label && '' === $value ) {
				continue;
			}
			?>
			<div class="cart-page__total-row<?php echo '' !== $class ? ' cart-page__total-row--' . esc_attr( $class ) : ''; ?>">
				<dt class="cart-page__total-label text-label"><?php echo wp_kses_post( $label ); ?></dt>
				<dd class="cart-page__total-value text-price-sm"><?php echo wp_kses_post( $value ); ?></dd>
			</div>
			<?php
		}
	}

	/**
	 * Render cross-sell products using ProductGrid.
	 */
	public static function render_cross_sells(): void {
		$settings = self::get_settings();

		if ( empty( $settings['cross_sells_enabled'] ) ) {
			return;
		}

		$query_args = self::build_cross_sells_query( (int) $settings['cross_sells_limit'] );

		if ( empty( $query_args ) ) {
			return;
		}
		?>
		<section
			class="cart-page__cross-sells"
			aria-labelledby="<?php echo esc_attr( self::get_cross_sells_heading_id() ); ?>"
			data-shanelle-cart-page-cross-sells
		>
			<div class="cart-page__cross-sells-inner">
				<h2 id="<?php echo esc_attr( self::get_cross_sells_heading_id() ); ?>" class="cart-page__cross-sells-title text-h2">
					<?php echo esc_html( (string) $settings['cross_sells_title'] ); ?>
				</h2>
				<?php
				ProductGrid::render(
					$query_args,
					array(
						'grid_id'         => self::ROOT_ID . '-cross-sells',
						'pagination_mode' => 'none',
						'card_args'       => array(
							'context' => 'cart-cross-sells',
						),
					)
				);
				?>
			</div>
		</section>
		<?php
	}

	/**
	 * Append cart page fragments for AJAX refresh.
	 *
	 * @param array<string, mixed> $fragments Existing fragments.
	 * @return array<string, mixed>
	 */
	public static function add_fragments( array $fragments ): array {
		if ( ! is_cart() ) {
			return $fragments;
		}

		self::$state = self::build_page_state();

		ob_start();
		self::render_fragment();
		$fragments['[data-shanelle-cart-page-fragment]'] = ob_get_clean() ?: '';

		self::$state = array();

		return $fragments;
	}

	/**
	 * AJAX: return latest cart page payload.
	 */
	public static function ajax_get_page(): void {
		wp_send_json_success( self::build_ajax_response() );
	}

	/**
	 * Return root element ID.
	 */
	public static function get_root_id(): string {
		return self::ROOT_ID;
	}

	/**
	 * Return page heading ID.
	 */
	public static function get_heading_id(): string {
		return self::ROOT_ID . '-heading';
	}

	/**
	 * Return summary heading ID.
	 */
	public static function get_summary_heading_id(): string {
		return self::ROOT_ID . '-summary-heading';
	}

	/**
	 * Return cross-sells heading ID.
	 */
	public static function get_cross_sells_heading_id(): string {
		return self::ROOT_ID . '-cross-sells-heading';
	}

	/**
	 * Return coupon input ID.
	 */
	public static function get_coupon_input_id(): string {
		return self::ROOT_ID . '-coupon';
	}

	/**
	 * Return quantity input ID for a cart line.
	 */
	public static function get_quantity_input_id( string $cart_item_key ): string {
		return self::ROOT_ID . '-qty-' . sanitize_html_class( $cart_item_key );
	}

	/**
	 * Return whether the cart is empty for the active render cycle.
	 */
	public static function is_empty(): bool {
		return ! empty( self::$state['is_empty'] );
	}

	/**
	 * Return page state JSON for client hydration.
	 */
	public static function get_state_json(): string {
		return wp_json_encode( self::$state ) ?: '{}';
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
	 * Build normalized cart page state.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_page_state(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array(
				'is_empty' => true,
				'items'    => array(),
				'totals'   => array(),
				'urls'     => array(
					'shop'     => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
					'cart'     => wc_get_cart_url(),
					'checkout' => wc_get_checkout_url(),
				),
			);
		}

		WC()->cart->calculate_totals();

		$cart_state = MiniCart::build_cart_state();

		return apply_filters(
			'shanelle_cart_page_state',
			array_merge(
				$cart_state,
				array(
					'totals'          => self::build_totals_rows(),
					'coupons_enabled' => wc_coupons_enabled(),
					'settings'        => self::get_settings(),
				)
			),
			WC()->cart
		);
	}

	/**
	 * Build totals rows using WooCommerce cart APIs.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function build_totals_rows(): array {
		if ( ! WC()->cart ) {
			return array();
		}

		$rows = array(
			array(
				'class'      => 'subtotal',
				'label'      => esc_html__( 'Subtotal', 'shanelle' ),
				'value_html' => WC()->cart->get_cart_subtotal(),
			),
		);

		foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
			ob_start();
			wc_cart_totals_coupon_label( $coupon );
			$label = ob_get_clean() ?: $code;
			ob_start();
			wc_cart_totals_coupon_html( $coupon );
			$value = ob_get_clean() ?: '';

			$rows[] = array(
				'class'      => 'discount',
				'label'      => (string) $label,
				'value_html' => (string) $value,
			);
		}

		$rows = array_merge( $rows, self::build_shipping_totals_rows() );

		foreach ( WC()->cart->get_fees() as $fee ) {
			$rows[] = array(
				'class'      => 'fee',
				'label'      => esc_html( $fee->name ),
				'value_html' => wc_cart_totals_fee_html( $fee ),
			);
		}

		if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( WC()->cart->get_tax_totals() as $tax ) {
					$rows[] = array(
						'class'      => 'tax',
						'label'      => esc_html( $tax->label ),
						'value_html' => wp_kses_post( $tax->formatted_amount ),
					);
				}
			} else {
				ob_start();
				wc_cart_totals_taxes_total_html();
				$rows[] = array(
					'class'      => 'tax',
					'label'      => esc_html( WC()->countries->tax_or_vat() ),
					'value_html' => ob_get_clean() ?: '',
				);
			}
		}

		ob_start();
		wc_cart_totals_order_total_html();
		$rows[] = array(
			'class'      => 'total',
			'label'      => esc_html__( 'Total', 'shanelle' ),
			'value_html' => ob_get_clean() ?: WC()->cart->get_total(),
		);

		return apply_filters( 'shanelle_cart_page_totals_rows', $rows, WC()->cart );
	}

	/**
	 * Build shipping total rows from WooCommerce package rates.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function build_shipping_totals_rows(): array {
		if ( ! WC()->cart || ! WC()->cart->needs_shipping() || ! WC()->cart->show_shipping() ) {
			return array();
		}

		$rows     = array();
		$packages = WC()->shipping()->get_packages();

		foreach ( $packages as $index => $package ) {
			$chosen_method = WC()->session->chosen_shipping_methods[ $index ] ?? '';

			if ( $chosen_method && isset( $package['rates'][ $chosen_method ] ) ) {
				$rate   = $package['rates'][ $chosen_method ];
				$rows[] = array(
					'class'      => 'shipping',
					'label'      => esc_html( $rate->get_label() ),
					'value_html' => wc_price( (float) $rate->cost + (float) array_sum( $rate->taxes ) ),
				);
			}
		}

		if ( ! empty( $rows ) ) {
			return $rows;
		}

		$rows[] = array(
			'class'      => 'shipping',
			'label'      => esc_html__( 'Envío', 'shanelle' ),
			'value_html' => WC()->customer->has_calculated_shipping()
				? esc_html__( 'Se calcula al pagar', 'shanelle' )
				: esc_html__( 'Calcula abajo', 'shanelle' ),
		);

		return $rows;
	}

	/**
	 * Determine whether shipping scripts should load on the cart page.
	 */
	private static function should_enqueue_shipping_assets(): bool {
		if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
			return false;
		}

		if ( empty( self::get_settings()['shipping_estimator'] ) ) {
			return false;
		}

		return 'yes' === get_option( 'woocommerce_enable_shipping_calc' );
	}

	/**
	 * Build cross-sells query args.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function build_cross_sells_query( int $limit ): ?array {
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return null;
		}

		$cross_sells = array_filter( array_map( 'absint', WC()->cart->get_cross_sells() ) );

		if ( empty( $cross_sells ) ) {
			return null;
		}

		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => max( 2, min( 12, $limit ) ),
			'post__in'       => $cross_sells,
			'orderby'        => 'post__in',
		);

		return apply_filters( 'shanelle_cart_page_cross_sell_query', ProductGrid::sanitize_query_vars( $query_args ) );
	}

	/**
	 * Build AJAX response payload.
	 *
	 * @return array<string, mixed>
	 */
	private static function build_ajax_response(): array {
		self::$state = self::build_page_state();

		ob_start();
		self::render_fragment();
		$fragment_html = ob_get_clean() ?: '';

		$response = array(
			'state'     => self::$state,
			'fragments' => array(
				'[data-shanelle-cart-page-fragment]' => $fragment_html,
			),
			'cart_hash' => WC()->cart ? WC()->cart->get_cart_hash() : '',
		);

		self::$state = array();

		return apply_filters( 'shanelle_cart_page_ajax_response', $response );
	}

	/**
	 * Read Theme Customizer settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		return apply_filters(
			'shanelle_cart_page_settings',
			array(
				'cross_sells_enabled' => self::get_theme_mod_bool( self::MOD_CROSS_SELLS_ENABLED, true ),
				'cross_sells_title'   => self::get_theme_mod_string(
					self::MOD_CROSS_SELLS_TITLE,
					__( 'También te puede gustar', 'shanelle' )
				),
				'cross_sells_limit'   => self::sanitize_cross_sells_limit(
					get_theme_mod( self::MOD_CROSS_SELLS_LIMIT, 4 )
				),
				'shipping_estimator'  => self::get_theme_mod_bool( self::MOD_SHIPPING_ESTIMATOR, true ),
			)
		);
	}

	/**
	 * Register a checkbox customizer control.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	private static function register_checkbox_control(
		\WP_Customize_Manager $wp_customize,
		string $mod_name,
		string $label,
		bool $default
	): void {
		$wp_customize->add_setting(
			$mod_name,
			array(
				'default'           => $default,
				'sanitize_callback' => array( self::class, 'sanitize_checkbox' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$mod_name,
			array(
				'label'   => $label,
				'section' => 'shanelle_cart_page',
				'type'    => 'checkbox',
			)
		);
	}

	/**
	 * Sanitize checkbox customizer values.
	 */
	public static function sanitize_checkbox( mixed $value ): bool {
		return (bool) $value;
	}

	/**
	 * Sanitize cross-sells limit customizer values.
	 */
	public static function sanitize_cross_sells_limit( mixed $value ): int {
		$limit = absint( $value );

		if ( $limit < 2 ) {
			return 2;
		}

		if ( $limit > 12 ) {
			return 12;
		}

		return $limit;
	}

	/**
	 * Read a sanitized string theme mod.
	 */
	private static function get_theme_mod_string( string $key, string $default = '' ): string {
		$value = get_theme_mod( $key, $default );

		return is_string( $value ) ? $value : $default;
	}

	/**
	 * Read a sanitized boolean theme mod.
	 */
	private static function get_theme_mod_bool( string $key, bool $default ): bool {
		$value = get_theme_mod( $key, $default );

		return (bool) $value;
	}
}
