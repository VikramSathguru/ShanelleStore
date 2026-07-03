<?php
/**
 * Checkout page composer bootstrap.
 *
 * @package Shanelle\Components
 */

declare(strict_types=1);

namespace Shanelle\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Composes the WooCommerce checkout page using theme components and WC checkout APIs.
 */
final class CheckoutPage {

	private const COMPONENT_DIR = SHANELLE_DIR . '/components/checkout-page';

	private const COMPONENT_URI = SHANELLE_URI . '/components/checkout-page';

	private const ROOT_ID = 'shanelle-checkout-page';

	private const MOD_SHOW_THUMBNAILS = 'shanelle_checkout_page_show_thumbnails';

	private const MOD_TRUST_MESSAGE = 'shanelle_checkout_page_trust_message';

	private const MOD_EDIT_CART_LABEL = 'shanelle_checkout_page_edit_cart_label';

	/**
	 * Active checkout instance.
	 */
	private static ?\WC_Checkout $checkout = null;

	/**
	 * Active page state for the render cycle.
	 *
	 * @var array<string, mixed>
	 */
	private static array $state = array();

	/**
	 * Boot checkout page hooks.
	 */
	public static function boot(): void {
		if ( ! shanelle_is_woocommerce_active() ) {
			return;
		}

		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp', array( self::class, 'configure_checkout_hooks' ), 20 );

		add_filter( 'woocommerce_update_order_review_fragments', array( self::class, 'add_checkout_fragments' ) );
		add_filter( 'woocommerce_order_button_html', array( self::class, 'filter_place_order_button' ) );
	}

	/**
	 * Adjust WooCommerce hooks on the checkout page.
	 */
	public static function configure_checkout_hooks(): void {
		if ( ! is_checkout() || is_order_received_page() ) {
			return;
		}

		remove_action( 'woocommerce_before_main_content', 'shanelle_before_main_content', 5 );
		remove_action( 'woocommerce_after_main_content', 'shanelle_after_main_content', 50 );
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		add_action( 'woocommerce_checkout_order_review', array( self::class, 'render_order_review' ), 10 );

		add_filter( 'woocommerce_show_page_title', '__return_false' );
		add_filter( 'the_title', array( self::class, 'hide_page_title' ), 10, 2 );
	}

	/**
	 * Hide the default WordPress checkout page title.
	 *
	 * @param string          $title Post title.
	 * @param int|string|null $id    Post ID.
	 */
	public static function hide_page_title( string $title, $id = null ): string {
		if ( ! is_checkout() || is_order_received_page() || ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}

		$checkout_page_id = wc_get_page_id( 'checkout' );

		if ( $checkout_page_id > 0 && (int) $id === $checkout_page_id ) {
			return '';
		}

		return $title;
	}

	/**
	 * Register Theme Customizer settings for the checkout page.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public static function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'shanelle_checkout_page',
			array(
				'title'       => __( 'Checkout Page', 'shanelle' ),
				'description' => __( 'Configure checkout page presentation.', 'shanelle' ),
				'priority'    => 171,
			)
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_SHOW_THUMBNAILS,
			__( 'Show product thumbnails in order summary', 'shanelle' ),
			true
		);

		self::register_checkbox_control(
			$wp_customize,
			self::MOD_TRUST_MESSAGE,
			__( 'Show secure checkout message', 'shanelle' ),
			true
		);

		$wp_customize->add_setting(
			self::MOD_EDIT_CART_LABEL,
			array(
				'default'           => __( 'Edit bag', 'shanelle' ),
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			self::MOD_EDIT_CART_LABEL,
			array(
				'label'   => __( 'Edit bag link label', 'shanelle' ),
				'section' => 'shanelle_checkout_page',
				'type'    => 'text',
			)
		);
	}

	/**
	 * Enqueue checkout page assets.
	 */
	public static function enqueue_assets(): void {
		if ( ! is_checkout() || is_order_received_page() ) {
			return;
		}

		wp_enqueue_style(
			'shanelle-checkout-page',
			self::COMPONENT_URI . '/checkout-page.css',
			array( 'shanelle-main' ),
			SHANELLE_VERSION
		);

		wp_enqueue_script(
			'shanelle-checkout-page',
			self::COMPONENT_URI . '/checkout-page.js',
			array( 'wc-checkout' ),
			SHANELLE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_script_add_data( 'shanelle-checkout-page', 'type', 'module' );

		wp_localize_script(
			'shanelle-checkout-page',
			'shanelleCheckoutPage',
			array(
				'cartUrl'      => wc_get_cart_url(),
				'initialState' => self::build_page_state(),
				'i18n'         => array(
					'pageTitle'       => __( 'Checkout', 'shanelle' ),
					'orderSummary'    => __( 'Order summary', 'shanelle' ),
					'billingDetails'  => __( 'Billing details', 'shanelle' ),
					'shippingDetails' => __( 'Shipping details', 'shanelle' ),
					'payment'         => __( 'Payment', 'shanelle' ),
					'secureCheckout'  => __( 'Secure checkout', 'shanelle' ),
					'updated'         => __( 'Order summary updated', 'shanelle' ),
					'validationError' => __( 'Please correct the errors below before placing your order.', 'shanelle' ),
					'shippingMethods' => __( 'Shipping method', 'shanelle' ),
				),
			)
		);
	}

	/**
	 * Render the checkout form composition.
	 *
	 * @param \WC_Checkout $checkout Checkout instance.
	 */
	public static function render_form( \WC_Checkout $checkout ): void {
		self::$checkout = $checkout;
		self::$state    = self::build_page_state();

		if ( ! wp_style_is( 'shanelle-checkout-page', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		$checkout = self::$checkout;
		require self::COMPONENT_DIR . '/checkout-page.php';

		self::$checkout = null;
		self::$state    = array();
	}

	/**
	 * Render returning customer login prompt and form.
	 */
	public static function render_login(): void {
		if ( is_user_logged_in() || ! function_exists( 'woocommerce_checkout_login_form' ) ) {
			return;
		}
		?>
		<div class="checkout-page__login">
			<?php woocommerce_checkout_login_form(); ?>
		</div>
		<?php
	}

	/**
	 * Render checkout coupon form outside the main checkout form.
	 */
	public static function render_coupon(): void {
		if ( ! function_exists( 'woocommerce_checkout_coupon_form' ) || ! wc_coupons_enabled() ) {
			return;
		}
		?>
		<div class="checkout-page__coupon" data-shanelle-checkout-coupon>
			<?php woocommerce_checkout_coupon_form(); ?>
		</div>
		<?php
	}

	/**
	 * Render WooCommerce notices.
	 */
	public static function render_notices(): void {
		if ( ! function_exists( 'woocommerce_output_all_notices' ) ) {
			return;
		}

		echo '<div class="checkout-page__notices">';
		woocommerce_output_all_notices();
		echo '</div>';
	}

	/**
	 * Render customer billing and shipping fields.
	 */
	public static function render_customer_details(): void {
		if ( ! self::$checkout instanceof \WC_Checkout || ! self::$checkout->get_checkout_fields() ) {
			return;
		}
		?>
		<div class="checkout-page__customer" id="customer_details">
			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<div class="checkout-page__billing">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
			</div>

			<div class="checkout-page__shipping">
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
		</div>
		<?php
	}

	/**
	 * Render themed order review line items and totals.
	 */
	public static function render_order_review(): void {
		self::$state = self::$state ?: self::build_page_state();
		?>
		<div class="checkout-page__review" data-shanelle-checkout-order-review>
			<?php self::render_line_items(); ?>
			<?php self::render_shipping_methods(); ?>
			<?php self::render_totals(); ?>
		</div>
		<?php
	}

	/**
	 * Render checkout order line items.
	 */
	public static function render_line_items(): void {
		$items        = self::$state['items'] ?? array();
		$show_images  = ! empty( self::$state['settings']['show_thumbnails'] );
		?>
		<ul class="checkout-page__items" data-shanelle-checkout-items role="list">
			<?php
			foreach ( $items as $item ) {
				if ( is_array( $item ) ) {
					self::render_line_item( $item, $show_images );
				}
			}
			?>
		</ul>
		<?php
	}

	/**
	 * Render a checkout order line item.
	 *
	 * @param array<string, mixed> $item        Normalized cart item data.
	 * @param bool                 $show_images Whether to show thumbnails.
	 */
	public static function render_line_item( array $item, bool $show_images ): void {
		$title      = (string) ( $item['title'] ?? '' );
		$permalink  = (string) ( $item['permalink'] ?? '' );
		$quantity   = max( 1, (int) ( $item['quantity'] ?? 1 ) );
		$variation  = (string) ( $item['variation_summary'] ?? '' );
		$thumbnail  = (string) ( $item['thumbnail_html'] ?? '' );
		$line_html  = (string) ( $item['line_subtotal_html'] ?? '' );
		?>
		<li class="checkout-page__item" data-shanelle-checkout-item>
			<?php if ( $show_images ) : ?>
				<a class="checkout-page__item-media" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			<?php endif; ?>

			<div class="checkout-page__item-copy">
				<p class="checkout-page__item-title text-label">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
					<span class="checkout-page__item-qty text-caption text-muted">&times; <?php echo esc_html( (string) $quantity ); ?></span>
				</p>

				<?php if ( '' !== $variation ) : ?>
					<div class="checkout-page__item-variation text-caption text-muted">
						<?php echo wp_kses_post( $variation ); ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="checkout-page__item-total text-price-sm">
				<?php echo wp_kses_post( $line_html ); ?>
			</div>
		</li>
		<?php
	}

	/**
	 * Render shipping method selection using WooCommerce package rates.
	 */
	public static function render_shipping_methods(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() || ! WC()->cart->show_shipping() ) {
			return;
		}

		$packages = WC()->shipping()->get_packages();

		if ( empty( $packages ) ) {
			return;
		}
		?>
		<section class="checkout-page__shipping" data-shanelle-checkout-shipping aria-label="<?php esc_attr_e( 'Shipping method', 'shanelle' ); ?>">
			<h3 class="checkout-page__shipping-title text-label"><?php esc_html_e( 'Shipping method', 'shanelle' ); ?></h3>
			<?php
			foreach ( $packages as $index => $package ) {
				$product_names = array();

				if ( count( $packages ) > 1 ) {
					foreach ( $package['contents'] as $item_id => $values ) {
						$product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
					}

					$product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
				}

				wc_get_template(
					'shipping-methods.php',
					array(
						'package'                  => $package,
						'available_methods'        => $package['rates'],
						'show_package_details'     => count( $packages ) > 1,
						'package_details'          => implode( ', ', $product_names ),
						'package_name'             => $package['package_name'],
						'index'                    => $index,
						'chosen_method'            => WC()->session->chosen_shipping_methods[ $index ] ?? '',
						'formatted_destination'    => WC()->countries->get_formatted_address( $package['destination'], ', ' ),
						'has_calculated_shipping'  => WC()->customer->has_calculated_shipping(),
					),
					'',
					self::COMPONENT_DIR . '/partials/'
				);
			}
			?>
		</section>
		<?php
	}

	/**
	 * Render checkout totals rows.
	 */
	public static function render_totals(): void {
		$rows = is_array( self::$state['totals'] ?? null ) ? self::$state['totals'] : array();
		?>
		<dl class="checkout-page__totals" data-shanelle-checkout-totals>
			<?php self::render_totals_rows( $rows ); ?>
		</dl>
		<?php
	}

	/**
	 * Render totals rows markup.
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
			<div class="checkout-page__total-row<?php echo '' !== $class ? ' checkout-page__total-row--' . esc_attr( $class ) : ''; ?>">
				<dt class="checkout-page__total-label text-label"><?php echo wp_kses_post( $label ); ?></dt>
				<dd class="checkout-page__total-value text-price-sm"><?php echo wp_kses_post( $value ); ?></dd>
			</div>
			<?php
		}
	}

	/**
	 * Render secure checkout trust indicators.
	 */
	public static function render_trust(): void {
		if ( empty( self::$state['settings']['trust_message'] ) ) {
			return;
		}
		?>
		<div class="checkout-page__trust">
			<ul class="checkout-page__trust-list" aria-label="<?php esc_attr_e( 'Shopping guarantees', 'shanelle' ); ?>">
				<li class="checkout-page__trust-item">
					<?php self::render_icon( 'lock' ); ?>
					<span><?php esc_html_e( 'Secure checkout', 'shanelle' ); ?></span>
				</li>
				<li class="checkout-page__trust-item">
					<?php self::render_icon( 'returns' ); ?>
					<span><?php esc_html_e( 'Easy returns', 'shanelle' ); ?></span>
				</li>
				<li class="checkout-page__trust-item">
					<?php self::render_icon( 'quality' ); ?>
					<span><?php esc_html_e( 'Quality guaranteed', 'shanelle' ); ?></span>
				</li>
			</ul>
			<p class="checkout-page__trust-message text-caption text-muted">
				<?php esc_html_e( 'Your payment information is processed securely. We do not store credit card details.', 'shanelle' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Replace default place order button markup with design system classes.
	 *
	 * @param string $button_html Default button HTML.
	 */
	public static function filter_place_order_button( string $button_html ): string {
		if ( ! is_checkout() || is_order_received_page() ) {
			return $button_html;
		}

		$text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) );

		return sprintf(
			'<button type="submit" class="btn btn--primary btn--block btn--lg checkout-page__place-order" name="woocommerce_checkout_place_order" id="place_order" value="%1$s" data-value="%1$s">%2$s</button>',
			esc_attr( (string) $text ),
			esc_html( (string) $text )
		);
	}

	/**
	 * Append checkout order review fragments for AJAX refresh.
	 *
	 * @param array<string, mixed> $fragments Existing fragments.
	 * @return array<string, mixed>
	 */
	public static function add_checkout_fragments( array $fragments ): array {
		if ( ! is_checkout() || is_order_received_page() ) {
			return $fragments;
		}

		self::$state = self::build_page_state();

		ob_start();
		self::render_order_review();
		$fragments['[data-shanelle-checkout-order-review]'] = ob_get_clean() ?: '';

		self::$state = array();

		return apply_filters( 'shanelle_checkout_page_fragments', $fragments );
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
	 * Return page state JSON for client hydration.
	 */
	public static function get_state_json(): string {
		return wp_json_encode( self::$state ) ?: '{}';
	}

	/**
	 * Return edit bag link label.
	 */
	public static function get_edit_cart_label(): string {
		$settings = is_array( self::$state['settings'] ?? null )
			? self::$state['settings']
			: self::get_settings();

		return (string) ( $settings['edit_cart_label'] ?? __( 'Edit bag', 'shanelle' ) );
	}

	/**
	 * Build normalized checkout page state.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_page_state(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array(
				'is_empty' => true,
				'items'    => array(),
				'totals'   => array(),
			);
		}

		WC()->cart->calculate_totals();

		$cart_state = MiniCart::build_cart_state();

		return apply_filters(
			'shanelle_checkout_page_state',
			array_merge(
				$cart_state,
				array(
					'totals'   => self::build_checkout_totals_rows(),
					'settings' => self::get_settings(),
					'urls'     => array(
						'cart'     => wc_get_cart_url(),
						'checkout' => wc_get_checkout_url(),
						'shop'     => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
					),
				)
			),
			WC()->cart
		);
	}

	/**
	 * Build checkout totals rows, omitting shipping when method selection is rendered separately.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function build_checkout_totals_rows(): array {
		if ( ! WC()->cart ) {
			return array();
		}

		$rows = CartPage::build_totals_rows();

		if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
			$rows = array_values(
				array_filter(
					$rows,
					static function ( $row ): bool {
						return is_array( $row ) && 'shipping' !== ( $row['class'] ?? '' );
					}
				)
			);
		}

		return apply_filters( 'shanelle_checkout_page_totals_rows', $rows, WC()->cart );
	}

	/**
	 * Output inline SVG icon markup.
	 *
	 * @param string $icon Icon slug.
	 */
	public static function render_icon( string $icon ): void {
		$icons = array(
			'lock'    => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
			'returns' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 7v6h6"/><path d="M21 17a8 8 0 0 0-14-5"/></svg>',
			'quality' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m12 3 2.4 4.8 5.4.8-3.9 3.8.9 5.3L12 15.8 7.2 17.7l.9-5.3L4.2 8.6l5.4-.8Z"/></svg>',
		);

		if ( ! isset( $icons[ $icon ] ) ) {
			return;
		}

		echo $icons[ $icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Read Theme Customizer settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		return apply_filters(
			'shanelle_checkout_page_settings',
			array(
				'show_thumbnails' => self::get_theme_mod_bool( self::MOD_SHOW_THUMBNAILS, true ),
				'trust_message'   => self::get_theme_mod_bool( self::MOD_TRUST_MESSAGE, true ),
				'edit_cart_label' => self::get_theme_mod_string(
					self::MOD_EDIT_CART_LABEL,
					__( 'Edit bag', 'shanelle' )
				),
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
				'section' => 'shanelle_checkout_page',
				'type'    => 'checkbox',
			)
		);
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
