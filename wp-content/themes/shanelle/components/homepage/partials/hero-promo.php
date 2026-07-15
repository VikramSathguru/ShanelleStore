<?php
/**
 * Homepage hero promo grid partial.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\Homepage;

defined( 'ABSPATH' ) || exit;

$left_tiles  = Homepage::get_promo_tiles( 'left' );
$right_tiles = Homepage::get_promo_tiles( 'right' );
?>
<section class="homepage__hero-promo" data-shanelle-homepage-hero>
	<div class="container homepage__hero-promo-inner">
		<aside class="homepage__hero-promo-side homepage__hero-promo-side--left" aria-label="<?php esc_attr_e( 'Promociones destacadas', 'shanelle' ); ?>">
			<ul class="homepage__hero-promo-list" role="list">
				<?php foreach ( $left_tiles as $tile ) : ?>
					<li class="homepage__hero-promo-item" data-promo-index="<?php echo esc_attr( (string) ( $tile['index'] ?? 0 ) ); ?>">
						<a class="homepage__hero-promo-link" href="<?php echo esc_url( (string) ( $tile['url'] ?? '#' ) ); ?>">
							<span class="homepage__hero-promo-label"><?php echo esc_html( (string) ( $tile['label'] ?? '' ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</aside>

		<div class="homepage__hero-promo-main">
			<?php shanelle_hero_banner(); ?>
		</div>

		<aside class="homepage__hero-promo-side homepage__hero-promo-side--right" aria-label="<?php esc_attr_e( 'Promociones en tendencia', 'shanelle' ); ?>">
			<ul class="homepage__hero-promo-list" role="list">
				<?php foreach ( $right_tiles as $tile ) : ?>
					<li class="homepage__hero-promo-item" data-promo-index="<?php echo esc_attr( (string) ( $tile['index'] ?? 0 ) ); ?>">
						<a class="homepage__hero-promo-link" href="<?php echo esc_url( (string) ( $tile['url'] ?? '#' ) ); ?>">
							<span class="homepage__hero-promo-label"><?php echo esc_html( (string) ( $tile['label'] ?? '' ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</aside>
	</div>
</section>
