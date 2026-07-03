<?php
/**
 * Product grid component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ProductGrid;

defined( 'ABSPATH' ) || exit;
?>
<section class="product-grid" <?php echo ProductGrid::get_grid_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<ul class="product-grid__items" data-shanelle-grid-items>
		<?php ProductGrid::render_items(); ?>
	</ul>

	<div class="product-grid__skeleton-host" data-shanelle-grid-skeleton hidden>
		<?php ProductGrid::render_skeleton( 8 ); ?>
	</div>

	<div class="product-grid__error" data-shanelle-grid-error hidden role="alert">
		<p class="product-grid__error-text text-body-sm"><?php esc_html_e( 'Unable to load products. Please try again.', 'shanelle' ); ?></p>
		<button type="button" class="btn btn--outline product-grid__error-retry" data-shanelle-grid-retry>
			<?php esc_html_e( 'Retry', 'shanelle' ); ?>
		</button>
	</div>

	<?php ProductGrid::render_load_more(); ?>
	<?php ProductGrid::render_pagination(); ?>

	<div class="product-grid__status sr-only" aria-live="polite" aria-atomic="true" data-shanelle-grid-status></div>
</section>
