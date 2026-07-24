<?php
/**
 * Checkout coupon form override.
 *
 * @package Shanelle
 * @version 9.8.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( ! wc_coupons_enabled() ) {
	return;
}
?>
<div class="woocommerce-form-coupon-toggle">
	<?php
	wc_print_notice(
		apply_filters(
			'woocommerce_checkout_coupon_message',
			esc_html__( 'Have a coupon?', 'woocommerce' ) . ' <a href="#" role="button" aria-label="' . esc_attr__( 'Enter your coupon code', 'woocommerce' ) . '" aria-controls="woocommerce-checkout-form-coupon" aria-expanded="false" class="showcoupon">' . esc_html__( 'Click here to enter your code', 'woocommerce' ) . '</a>'
		),
		'notice'
	);
	?>
</div>

<form class="checkout_coupon woocommerce-form-coupon checkout-page__coupon-form" method="post" style="display:none" id="woocommerce-checkout-form-coupon">
	<p class="checkout-page__coupon-field form-row">
		<label class="checkout-page__coupon-label text-label" for="coupon_code">
			<?php esc_html_e( 'Código de cupón', 'shanelle' ); ?>
		</label>
		<span class="checkout-page__coupon-row">
			<input
				type="text"
				name="coupon_code"
				class="checkout-page__coupon-input input-text"
				placeholder="<?php esc_attr_e( 'Ingresa el código de cupón', 'shanelle' ); ?>"
				id="coupon_code"
				value=""
				autocomplete="off"
			>
			<button type="submit" class="btn btn--outline checkout-page__coupon-submit" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>">
				<?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?>
			</button>
		</span>
	</p>
</form>
