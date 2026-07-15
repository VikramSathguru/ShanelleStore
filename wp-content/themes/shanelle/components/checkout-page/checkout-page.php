<?php
/**
 * Checkout page component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\CheckoutPage;

defined( 'ABSPATH' ) || exit;

$checkout = $checkout ?? null;

if ( ! $checkout instanceof WC_Checkout ) {
	return;
}
?>
<div
	class="checkout-page"
	id="<?php echo esc_attr( CheckoutPage::get_root_id() ); ?>"
	data-shanelle-checkout-page
	data-checkout-state="<?php echo esc_attr( CheckoutPage::get_state_json() ); ?>"
>
	<header class="checkout-page__header">
		<h1 id="<?php echo esc_attr( CheckoutPage::get_heading_id() ); ?>" class="checkout-page__title text-h1">
			<?php esc_html_e( 'Pagar', 'shanelle' ); ?>
		</h1>
	</header>

	<?php CheckoutPage::render_notices(); ?>

	<?php CheckoutPage::render_login(); ?>

	<div class="checkout-page__layout">
		<form
			name="checkout"
			method="post"
			class="checkout-page__form checkout woocommerce-checkout"
			action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
			enctype="multipart/form-data"
			aria-label="<?php esc_attr_e( 'Pagar', 'shanelle' ); ?>"
		>
			<div class="checkout-page__main">
				<?php CheckoutPage::render_customer_details(); ?>
			</div>

			<aside class="checkout-page__summary" aria-labelledby="<?php echo esc_attr( CheckoutPage::get_summary_heading_id() ); ?>">
				<div class="checkout-page__summary-header">
					<h2 id="<?php echo esc_attr( CheckoutPage::get_summary_heading_id() ); ?>" class="checkout-page__summary-title text-h3">
						<?php esc_html_e( 'Resumen del pedido', 'shanelle' ); ?>
					</h2>
					<a class="checkout-page__edit-cart text-label" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
						<?php echo esc_html( CheckoutPage::get_edit_cart_label() ); ?>
					</a>
				</div>

				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

				<div id="order_review" class="checkout-page__order-review woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

				<?php CheckoutPage::render_trust(); ?>
			</aside>
		</form>

		<?php CheckoutPage::render_coupon(); ?>
	</div>

	<p class="screen-reader-text" data-shanelle-checkout-page-status role="status" aria-live="polite" aria-atomic="true"></p>
</div>
