<?php
/**
 * Homepage featured collections partial.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\Homepage;
use Shanelle\Components\ProductCard;

defined( 'ABSPATH' ) || exit;

$collections = Homepage::get_featured_collections();
?>
<section
	class="homepage__featured-collections"
	data-shanelle-homepage-section
	data-section-key="featured-collections"
	aria-label="<?php esc_attr_e( 'Colecciones destacadas', 'shanelle' ); ?>"
>
	<div class="container homepage__featured-collections-inner">
		<div class="homepage__featured-collections-grid">
			<?php foreach ( $collections as $collection ) : ?>
				<article
					class="homepage__featured-collection"
					data-collection-index="<?php echo esc_attr( (string) ( $collection['index'] ?? 0 ) ); ?>"
				>
					<header class="homepage__featured-collection-header">
						<h2 class="homepage__featured-collection-title">
							<a href="<?php echo esc_url( (string) ( $collection['url'] ?? '#' ) ); ?>">
								<?php echo esc_html( (string) ( $collection['title'] ?? '' ) ); ?>
							</a>
						</h2>
						<a class="homepage__featured-collection-more" href="<?php echo esc_url( (string) ( $collection['url'] ?? '#' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: collection title */ __( 'Ver todo en %s', 'shanelle' ), (string) ( $collection['title'] ?? '' ) ) ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
						</a>
					</header>

					<ul class="homepage__featured-collection-products" role="list">
						<?php
						$products = is_array( $collection['products'] ?? null ) ? $collection['products'] : array();

						foreach ( $products as $product_index => $product ) :
							if ( ! $product instanceof WC_Product ) {
								continue;
							}
							?>
							<li
								class="homepage__featured-collection-product"
								data-product-index="<?php echo esc_attr( (string) $product_index ); ?>"
							>
								<?php
								ProductCard::render(
									$product,
									array(
										'show_rating'     => false,
										'show_attributes' => false,
										'show_actions'    => true,
									)
								);
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
