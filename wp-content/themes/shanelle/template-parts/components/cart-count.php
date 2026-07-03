<?php
/**
 * Cart count badge for header.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( ! shanelle_is_woocommerce_active() ) {
	return;
}

$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
?>
<span class="header-cart__count" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: cart item count */ _n( '%d item in bag', '%d items in bag', $count, 'shanelle' ), $count ) ); ?>">
	<?php echo esc_html( (string) $count ); ?>
</span>
