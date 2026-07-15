<?php
/**
 * Search page component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\SearchPage;
use Shanelle\Components\ShopArchive;

defined( 'ABSPATH' ) || exit;
?>
<main
	id="primary"
	class="site-main search-page shop-archive"
	data-shanelle-search-page
	data-shanelle-shop-archive
	data-search-state="<?php echo esc_attr( SearchPage::get_state_json() ); ?>"
>
	<div class="container search-page__container shop-archive__container">
		<?php SearchPage::render_search_form(); ?>

		<?php ShopArchive::render_notices(); ?>

		<?php if ( SearchPage::show_breadcrumbs() ) : ?>
			<?php ShopArchive::render_breadcrumbs(); ?>
		<?php endif; ?>

		<div class="shop-archive__layout">
			<?php ShopArchive::render_sidebar(); ?>

			<div class="shop-archive__main search-page__main">
				<?php ShopArchive::render_header(); ?>
				<?php ShopArchive::render_toolbar(); ?>
				<?php ShopArchive::render_loading_placeholder(); ?>

				<div class="search-page__grid shop-archive__grid" data-shanelle-archive-grid>
					<?php ShopArchive::render_grid(); ?>
				</div>
			</div>
		</div>
	</div>

	<?php ShopArchive::render_filters_panel(); ?>

	<div class="shop-archive__status sr-only" aria-live="polite" aria-atomic="true" data-shanelle-archive-status></div>

	<p class="screen-reader-text" data-shanelle-search-page-status role="status" aria-live="polite" aria-atomic="true"></p>
</main>
