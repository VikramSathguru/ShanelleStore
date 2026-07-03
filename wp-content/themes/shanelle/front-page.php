<?php
/**
 * Front page template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

get_header();
?>

<main id="primary" class="site-main front-page">
	<?php shanelle_component( 'hero-banner' ); ?>
	<?php shanelle_component( 'category-chips' ); ?>

	<section class="section section--products" aria-labelledby="featured-products-heading">
		<div class="container">
			<?php
			shanelle_component( 'section-heading', array(
				'id'    => 'featured-products-heading',
				'title' => __( 'New Arrivals', 'shanelle' ),
				'link'  => shanelle_is_woocommerce_active() ? wc_get_page_permalink( 'shop' ) : '',
				'label' => __( 'View all', 'shanelle' ),
			) );
			?>

			<?php if ( shanelle_is_woocommerce_active() ) : ?>
				<?php
				echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
