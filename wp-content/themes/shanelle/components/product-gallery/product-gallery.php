<?php
/**
 * Product gallery component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ProductGallery;

defined( 'ABSPATH' ) || exit;
?>
<div
	class="product-gallery"
	data-shanelle-product-gallery
	data-gallery-items="<?php echo esc_attr( ProductGallery::get_gallery_json() ); ?>"
	data-gallery-count="<?php echo esc_attr( (string) ProductGallery::get_image_count() ); ?>"
	role="region"
	aria-label="<?php esc_attr_e( 'Galería del producto', 'shanelle' ); ?>"
>
	<div class="product-gallery__layout">
		<?php ProductGallery::render_thumbnails(); ?>

		<div
			id="<?php echo esc_attr( ProductGallery::get_panel_id() ); ?>"
			class="product-gallery__panel"
			role="tabpanel"
			tabindex="0"
			data-shanelle-gallery-panel
		>
			<?php ProductGallery::render_main_image(); ?>
		</div>
	</div>

	<?php ProductGallery::render_modal(); ?>

	<p class="product-gallery__status sr-only" aria-live="polite" aria-atomic="true" data-shanelle-gallery-status></p>
</div>
