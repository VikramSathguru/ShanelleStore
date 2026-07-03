<?php
/**
 * Product purchase panel component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ProductPurchase;

defined( 'ABSPATH' ) || exit;
?>
<section
	class="product-purchase"
	id="<?php echo esc_attr( ProductPurchase::get_root_id() ); ?>"
	data-shanelle-product-purchase
	data-purchase-state="<?php echo esc_attr( ProductPurchase::get_state_json() ); ?>"
	aria-label="<?php esc_attr_e( 'Purchase options', 'shanelle' ); ?>"
>
	<?php ProductPurchase::render_notices(); ?>
	<?php ProductPurchase::render_quantity(); ?>
	<?php ProductPurchase::render_actions(); ?>
	<?php ProductPurchase::render_estimates(); ?>
	<?php ProductPurchase::render_trust(); ?>

	<p class="product-purchase__status sr-only" aria-live="polite" aria-atomic="true" data-shanelle-purchase-status></p>
</section>
