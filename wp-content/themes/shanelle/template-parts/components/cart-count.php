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
$label = sprintf(
	/* translators: %d: cart item count */
	_n( '%d artículo en la bolsa', '%d artículos en la bolsa', $count, 'shanelle' ),
	$count
);
?>
<span
	class="header-cart__count<?php echo $count > 0 ? ' is-active' : ''; ?>"
	data-cart-count
	aria-label="<?php echo esc_attr( $label ); ?>"
>
	<?php echo esc_html( (string) $count ); ?>
</span>
