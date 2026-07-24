<?php
/**
 * Product card component for shop loops.
 *
 * @package Shanelle
 *
 * @var array<string, mixed> $args
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$product = $args['product'] ?? null;

if ( ! $product instanceof WC_Product ) {
	return;
}

$permalink = $product->get_permalink();
$badge     = shanelle_get_sale_badge( $product );
?>
<article class="product-card">
	<a class="product-card__media" href="<?php echo esc_url( $permalink ); ?>">
		<?php if ( $badge ) : ?>
			<span class="product-card__badge"><?php echo esc_html( $badge ); ?></span>
		<?php endif; ?>

		<?php
		echo $product->get_image( 'shanelle-product-card', array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'class'    => 'product-card__image',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => '(max-width: 767px) 50vw, (max-width: 1023px) 33vw, 25vw',
		) );
		?>
	</a>

	<div class="product-card__body">
		<h3 class="product-card__title">
			<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
		</h3>

		<div class="product-card__price">
			<?php echo wp_kses_post( $product->get_price_html() ); ?>
		</div>
	</div>
</article>
