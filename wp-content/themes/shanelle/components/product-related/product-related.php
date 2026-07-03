<?php
/**
 * Related products component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ProductRelated;

defined( 'ABSPATH' ) || exit;
?>
<section
	class="product-related product-detail__section product-detail__section--related"
	id="<?php echo esc_attr( ProductRelated::get_root_id() ); ?>"
	data-shanelle-product-related
	data-shanelle-detail-section="related"
	data-shanelle-detail-hydrate
	data-source-product-id="<?php echo esc_attr( (string) ProductRelated::get_source_product_id() ); ?>"
	data-recommendation-json="<?php echo esc_attr( ProductRelated::get_recommendation_json() ); ?>"
	aria-labelledby="<?php echo esc_attr( ProductRelated::get_heading_id() ); ?>"
>
	<h2 id="<?php echo esc_attr( ProductRelated::get_heading_id() ); ?>" class="product-related__title product-detail__section-title">
		<?php esc_html_e( 'Related Products', 'shanelle' ); ?>
	</h2>

	<div class="product-related__grid">
		<?php ProductRelated::render_grid(); ?>
	</div>
</section>
