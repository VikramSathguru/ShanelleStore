<?php
/**
 * Product information component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ProductInformation;

defined( 'ABSPATH' ) || exit;
?>
<section
	class="product-information product-detail__section product-detail__section--information"
	id="<?php echo esc_attr( ProductInformation::get_root_id() ); ?>"
	data-shanelle-product-information
	data-shanelle-detail-section="information"
	data-shanelle-detail-hydrate
	data-information-json="<?php echo esc_attr( ProductInformation::get_information_json() ); ?>"
	aria-labelledby="<?php echo esc_attr( ProductInformation::get_heading_id() ); ?>"
>
	<h2 id="<?php echo esc_attr( ProductInformation::get_heading_id() ); ?>" class="product-information__title product-detail__section-title">
		<?php esc_html_e( 'Información del producto', 'shanelle' ); ?>
	</h2>

	<div class="product-information__accordion" data-shanelle-information-accordion>
		<?php ProductInformation::render_sections(); ?>
	</div>
</section>
