<?php
/**
 * Collection archive page component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\CollectionPage;
use Shanelle\Components\ShopArchive;

defined( 'ABSPATH' ) || exit;
?>
<main
	id="primary"
	class="site-main collection-page shop-archive"
	data-shanelle-collection-page
	data-shanelle-shop-archive
	data-collection-state="<?php echo esc_attr( CollectionPage::get_state_json() ); ?>"
>
	<?php CollectionPage::render_hero(); ?>

	<div class="container collection-page__container shop-archive__container">
		<?php CollectionPage::render_related_collections(); ?>
		<?php ShopArchive::render_notices(); ?>

		<?php if ( CollectionPage::show_breadcrumbs() ) : ?>
			<?php ShopArchive::render_breadcrumbs(); ?>
		<?php endif; ?>

		<div class="shop-archive__layout">
			<?php ShopArchive::render_sidebar(); ?>

			<div class="shop-archive__main collection-page__main">
				<?php CollectionPage::render_product_count(); ?>
				<?php ShopArchive::render_toolbar(); ?>
				<?php ShopArchive::render_active_filters(); ?>
				<?php ShopArchive::render_loading_placeholder(); ?>

				<div class="collection-page__grid shop-archive__grid" data-shanelle-archive-grid>
					<?php ShopArchive::render_grid(); ?>
				</div>
			</div>
		</div>
	</div>

	<?php ShopArchive::render_filters_panel(); ?>

	<div class="shop-archive__status sr-only" aria-live="polite" aria-atomic="true" data-shanelle-archive-status></div>

	<p class="screen-reader-text" data-shanelle-collection-page-status role="status" aria-live="polite" aria-atomic="true"></p>
</main>
