<?php
/**
 * Product card component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ProductCard;

defined( 'ABSPATH' ) || exit;

$card_title_id = 'product-card-title-' . wp_unique_id();
$is_catalog    = ProductCard::is_catalog_variant();
?>
<article
	class="product-card card card--product<?php echo ProductCard::get_render_in_stock() ? '' : ' is-sold-out'; ?><?php echo $is_catalog ? ' product-card--catalog' : ''; ?>"
	data-shanelle-product-card
	data-product-id="<?php echo esc_attr( (string) ProductCard::get_render_product_id() ); ?>"
	aria-labelledby="<?php echo esc_attr( $card_title_id ); ?>"
>
	<div class="product-card__media card__media">
		<?php ProductCard::render_badges(); ?>
		<?php ProductCard::render_sale_overlay(); ?>
		<?php ProductCard::render_image(); ?>
		<?php ProductCard::render_actions(); ?>
	</div>

	<div class="product-card__body card__body">
		<?php ProductCard::render_attributes(); ?>

		<h3 id="<?php echo esc_attr( $card_title_id ); ?>" class="product-card__title card__title text-clamp-2">
			<a href="<?php echo esc_url( ProductCard::get_render_permalink() ); ?>">
				<?php echo esc_html( ProductCard::get_render_name() ); ?>
			</a>
		</h3>

		<?php if ( $is_catalog ) : ?>
			<?php ProductCard::render_footer(); ?>
		<?php else : ?>
			<?php ProductCard::render_rating(); ?>
			<?php ProductCard::render_price(); ?>
		<?php endif; ?>
	</div>

	<div class="product-card__live sr-only" aria-live="polite" aria-atomic="true" data-shanelle-card-live></div>
</article>
