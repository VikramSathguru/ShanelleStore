<?php
/**
 * Product variation selector component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ProductVariations;

defined( 'ABSPATH' ) || exit;
?>
<div
	class="product-variations"
	id="<?php echo esc_attr( ProductVariations::get_root_id() ); ?>"
	data-shanelle-product-variations
	data-product-id="<?php echo esc_attr( (string) ProductVariations::get_render_product_id() ); ?>"
	role="group"
	aria-label="<?php esc_attr_e( 'Product options', 'shanelle' ); ?>"
>
	<?php ProductVariations::render_attribute_groups(); ?>

	<?php ProductVariations::render_availability(); ?>
	<?php ProductVariations::render_gallery_sync_placeholder(); ?>
	<?php ProductVariations::render_reset(); ?>

	<div class="product-variations__native sr-only" aria-hidden="true" data-shanelle-variation-native>
		<?php ProductVariations::render_native_selects(); ?>
	</div>

	<p class="product-variations__status sr-only" aria-live="polite" aria-atomic="true" data-shanelle-variation-status></p>
</div>
