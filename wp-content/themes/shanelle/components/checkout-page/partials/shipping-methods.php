<?php
/**
 * Checkout shipping method selection partial.
 *
 * Uses WooCommerce shipping package data; markup only.
 *
 * @package Shanelle
 * @version 8.8.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$formatted_destination   = isset( $formatted_destination ) ? $formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
$has_calculated_shipping = ! empty( $has_calculated_shipping );
$show_package_details    = ! empty( $show_package_details );
$package_details         = isset( $package_details ) ? (string) $package_details : '';
$package_name            = isset( $package_name ) ? (string) $package_name : '';
$index                   = isset( $index ) ? (int) $index : 0;
$chosen_method           = isset( $chosen_method ) ? (string) $chosen_method : '';
$available_methods       = isset( $available_methods ) && is_array( $available_methods ) ? $available_methods : array();
?>
<div class="checkout-page__shipping-package">
	<?php if ( '' !== $package_name && count( WC()->shipping()->get_packages() ) > 1 ) : ?>
		<p class="checkout-page__shipping-package-name text-label"><?php echo esc_html( $package_name ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $available_methods ) ) : ?>
		<ul id="shipping_method" class="checkout-page__shipping-methods woocommerce-shipping-methods" role="radiogroup" aria-label="<?php echo esc_attr( $package_name ?: __( 'Opciones de envío', 'shanelle' ) ); ?>">
			<?php foreach ( $available_methods as $method ) : ?>
				<li class="checkout-page__shipping-method">
					<?php
					if ( 1 < count( $available_methods ) ) {
						printf(
							'<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />',
							$index,
							esc_attr( sanitize_title( $method->id ) ),
							esc_attr( $method->id ),
							checked( $method->id, $chosen_method, false )
						);
					} else {
						printf(
							'<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />',
							$index,
							esc_attr( sanitize_title( $method->id ) ),
							esc_attr( $method->id )
						);
					}

					printf(
						'<label for="shipping_method_%1$s_%2$s">%3$s</label>',
						$index,
						esc_attr( sanitize_title( $method->id ) ),
						wc_cart_totals_shipping_method_label( $method )
					);

					do_action( 'woocommerce_after_shipping_rate', $method, $index );
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php elseif ( ! $has_calculated_shipping || ! $formatted_destination ) : ?>
		<p class="checkout-page__shipping-message text-caption text-muted">
			<?php echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', __( 'Enter your address to view shipping options.', 'woocommerce' ) ) ); ?>
		</p>
	<?php else : ?>
		<p class="checkout-page__shipping-message text-caption text-muted">
			<?php echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' ) ) ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $show_package_details && '' !== $package_details ) : ?>
		<p class="checkout-page__shipping-contents text-caption text-muted">
			<?php echo esc_html( $package_details ); ?>
		</p>
	<?php endif; ?>
</div>
