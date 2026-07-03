<?php
/**
 * Product summary component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ProductSummary;

defined( 'ABSPATH' ) || exit;
?>
<section
	class="product-summary"
	id="<?php echo esc_attr( ProductSummary::get_summary_id() ); ?>"
	data-shanelle-product-summary
	data-product-id="<?php echo esc_attr( (string) ProductSummary::get_render_product_id() ); ?>"
	aria-labelledby="<?php echo esc_attr( ProductSummary::get_title_id() ); ?>"
>
	<header class="product-summary__header">
		<?php ProductSummary::render_brand(); ?>
		<?php ProductSummary::render_title(); ?>
		<?php ProductSummary::render_meta(); ?>
	</header>

	<div class="product-summary__commerce">
		<?php ProductSummary::render_price(); ?>
		<?php ProductSummary::render_stock(); ?>
	</div>

	<?php ProductSummary::render_short_description(); ?>
	<?php ProductSummary::render_highlights(); ?>

	<p class="product-summary__status sr-only" aria-live="polite" aria-atomic="true" data-shanelle-summary-status></p>
</section>
