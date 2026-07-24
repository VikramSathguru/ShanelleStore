<?php
/**
 * Homepage category icon grid partial.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\Homepage;

defined( 'ABSPATH' ) || exit;

$items = Homepage::get_category_icon_items();
?>
<section
	class="homepage__category-icons"
	data-shanelle-homepage-section
	data-section-key="category-icons"
	aria-label="<?php esc_attr_e( 'Explorar categorías', 'shanelle' ); ?>"
>
	<div class="container homepage__category-icons-inner">
		<ul class="homepage__category-icons-grid" role="list">
			<?php foreach ( $items as $item ) : ?>
				<li
					class="homepage__category-icon-item"
					data-category-index="<?php echo esc_attr( (string) ( $item['index'] ?? 0 ) ); ?>"
				>
					<a class="homepage__category-icon-link" href="<?php echo esc_url( (string) ( $item['url'] ?? '#' ) ); ?>">
						<span class="homepage__category-icon-media">
							<?php if ( ! empty( $item['image_html'] ) ) : ?>
								<?php echo $item['image_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php else : ?>
								<span class="homepage__category-icon-placeholder" aria-hidden="true">
									<?php echo esc_html( (string) ( $item['initial'] ?? '•' ) ); ?>
								</span>
							<?php endif; ?>
						</span>
						<span class="homepage__category-icon-label"><?php echo esc_html( (string) ( $item['name'] ?? '' ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
