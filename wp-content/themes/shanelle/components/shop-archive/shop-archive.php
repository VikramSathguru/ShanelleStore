<?php
/**
 * Shop archive component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ShopArchive;

defined( 'ABSPATH' ) || exit;
?>
<main id="primary" class="site-main shop-archive" data-shanelle-shop-archive>
	<div class="container shop-archive__container">
		<?php ShopArchive::render_breadcrumbs(); ?>
		<?php ShopArchive::render_notices(); ?>
		<?php ShopArchive::render_header(); ?>
		<?php ShopArchive::render_toolbar(); ?>
		<?php ShopArchive::render_loading_placeholder(); ?>

		<div class="shop-archive__grid" data-shanelle-archive-grid>
			<?php ShopArchive::render_grid(); ?>
		</div>
	</div>

	<?php ShopArchive::render_filters_panel(); ?>

	<div class="shop-archive__status sr-only" aria-live="polite" aria-atomic="true" data-shanelle-archive-status></div>
</main>
