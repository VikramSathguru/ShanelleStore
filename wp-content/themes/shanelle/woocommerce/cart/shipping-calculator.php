<?php
/**
 * Shipping calculator template override.
 *
 * @package Shanelle
 * @version 9.7.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_shipping_calculator' );

$button_text = ! empty( $button_text ) ? $button_text : __( 'Calcular envío', 'shanelle' );
?>

<form class="woocommerce-shipping-calculator cart-page__shipping-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

	<button
		type="button"
		class="cart-page__shipping-toggle btn btn--ghost btn--sm shipping-calculator-button"
		aria-expanded="false"
		aria-controls="shipping-calculator-form"
		data-shanelle-cart-page-shipping-toggle
	>
		<?php echo esc_html( (string) $button_text ); ?>
	</button>

	<section class="shipping-calculator-form cart-page__shipping-panel" id="shipping-calculator-form" hidden>

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_country', true ) ) : ?>
			<p class="form-row form-row-wide cart-page__shipping-field" id="calc_shipping_country_field">
				<label class="text-label" for="calc_shipping_country"><?php esc_html_e( 'Country / region', 'woocommerce' ); ?></label>
				<select name="calc_shipping_country" id="calc_shipping_country" class="country_to_state country_select cart-page__shipping-input" rel="calc_shipping_state">
					<option value="default"><?php esc_html_e( 'Select a country / region&hellip;', 'woocommerce' ); ?></option>
					<?php
					foreach ( WC()->countries->get_shipping_countries() as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '"' . selected( WC()->customer->get_shipping_country(), esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
					}
					?>
				</select>
			</p>
		<?php endif; ?>

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_state', true ) ) : ?>
			<p class="form-row form-row-wide cart-page__shipping-field" id="calc_shipping_state_field">
				<?php
				$current_cc = WC()->customer->get_shipping_country();
				$current_r  = WC()->customer->get_shipping_state();
				$states     = WC()->countries->get_states( $current_cc );

				if ( is_array( $states ) && empty( $states ) ) {
					?>
					<input type="hidden" name="calc_shipping_state" id="calc_shipping_state" />
					<?php
				} elseif ( is_array( $states ) ) {
					?>
					<label class="text-label" for="calc_shipping_state"><?php esc_html_e( 'State / County', 'woocommerce' ); ?></label>
					<select name="calc_shipping_state" class="state_select cart-page__shipping-input" id="calc_shipping_state">
						<option value=""><?php esc_html_e( 'Select an option&hellip;', 'woocommerce' ); ?></option>
						<?php
						foreach ( $states as $ckey => $cvalue ) {
							echo '<option value="' . esc_attr( $ckey ) . '" ' . selected( $current_r, $ckey, false ) . '>' . esc_html( $cvalue ) . '</option>';
						}
						?>
					</select>
					<?php
				} else {
					?>
					<label class="text-label" for="calc_shipping_state"><?php esc_html_e( 'State / County', 'woocommerce' ); ?></label>
					<input type="text" class="input-text cart-page__shipping-input" value="<?php echo esc_attr( $current_r ); ?>" name="calc_shipping_state" id="calc_shipping_state" />
					<?php
				}
				?>
			</p>
		<?php endif; ?>

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_city', true ) ) : ?>
			<p class="form-row form-row-wide cart-page__shipping-field" id="calc_shipping_city_field">
				<label class="text-label" for="calc_shipping_city"><?php esc_html_e( 'City:', 'woocommerce' ); ?></label>
				<input type="text" class="input-text cart-page__shipping-input" value="<?php echo esc_attr( WC()->customer->get_shipping_city() ); ?>" name="calc_shipping_city" id="calc_shipping_city" />
			</p>
		<?php endif; ?>

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_postcode', true ) ) : ?>
			<p class="form-row form-row-wide cart-page__shipping-field" id="calc_shipping_postcode_field">
				<label class="text-label" for="calc_shipping_postcode"><?php esc_html_e( 'Postcode / ZIP:', 'woocommerce' ); ?></label>
				<input type="text" class="input-text cart-page__shipping-input" value="<?php echo esc_attr( WC()->customer->get_shipping_postcode() ); ?>" name="calc_shipping_postcode" id="calc_shipping_postcode" />
			</p>
		<?php endif; ?>

		<p class="cart-page__shipping-actions">
			<button type="submit" name="calc_shipping" value="1" class="btn btn--outline cart-page__shipping-submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>">
				<?php esc_html_e( 'Update', 'woocommerce' ); ?>
			</button>
		</p>

		<?php wp_nonce_field( 'woocommerce-shipping-calculator', 'woocommerce-shipping-calculator-nonce' ); ?>
	</section>
</form>

<?php do_action( 'woocommerce_after_shipping_calculator' ); ?>
