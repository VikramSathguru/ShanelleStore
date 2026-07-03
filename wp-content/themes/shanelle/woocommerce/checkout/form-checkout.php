<?php
/**
 * Checkout form template override.
 *
 * @package Shanelle
 * @version 9.4.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout instanceof WC_Checkout ) {
	return;
}

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html(
		apply_filters(
			'woocommerce_checkout_must_be_logged_in_message',
			__( 'You must be logged in to checkout.', 'woocommerce' )
		)
	);
	return;
}

\Shanelle\Components\CheckoutPage::render_form( $checkout );

do_action( 'woocommerce_after_checkout_form', $checkout );
