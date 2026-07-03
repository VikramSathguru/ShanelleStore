<?php
/**
 * Product detail page component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ProductDetail;

defined( 'ABSPATH' ) || exit;

global $product;
?>
<main id="primary" class="site-main product-detail-main">
	<div class="container product-detail-main__inner">
		<article
			<?php wc_product_class( 'product-detail', $product ); ?>
			id="<?php echo esc_attr( ProductDetail::get_root_id() ); ?>"
			data-shanelle-product-detail
			data-detail-json="<?php echo esc_attr( ProductDetail::get_detail_json() ); ?>"
		>
			<?php ProductDetail::render_breadcrumbs(); ?>
			<?php ProductDetail::render_hero(); ?>
			<div class="product-detail__below">
				<?php shanelle_product_information( $product ); ?>
				<?php ProductDetail::render_reviews_section(); ?>
				<?php shanelle_product_related( $product ); ?>
				<?php ProductDetail::render_recently_viewed_section(); ?>
			</div>
		</article>
	</div>
</main>
