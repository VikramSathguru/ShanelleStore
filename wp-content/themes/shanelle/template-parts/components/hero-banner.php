<?php
/**
 * Hero banner component.
 *
 * @package Shanelle
 *
 * @var array<string, mixed> $args
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$shop_url = shanelle_is_woocommerce_active() ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
?>
<section class="hero" aria-labelledby="hero-heading">
	<div class="hero__media">
		<div class="hero__placeholder" role="img" aria-label="<?php esc_attr_e( 'New season collection', 'shanelle' ); ?>"></div>
	</div>
	<div class="hero__content container">
		<p class="hero__eyebrow"><?php esc_html_e( 'New Season', 'shanelle' ); ?></p>
		<h1 id="hero-heading" class="hero__title"><?php esc_html_e( 'Discover your next favorite look', 'shanelle' ); ?></h1>
		<a class="btn btn--primary hero__cta" href="<?php echo esc_url( $shop_url ); ?>">
			<?php esc_html_e( 'Shop now', 'shanelle' ); ?>
		</a>
	</div>
</section>
