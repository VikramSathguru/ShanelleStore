<?php
/**
 * Collections index page component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\CollectionsPage;

defined( 'ABSPATH' ) || exit;
?>
<main
	id="primary"
	class="site-main collections-page"
	data-shanelle-collections-page
	data-collections-state="<?php echo esc_attr( CollectionsPage::get_state_json() ); ?>"
>
	<div class="container collections-page__container">
		<?php CollectionsPage::render_header(); ?>
		<?php CollectionsPage::render_listings(); ?>
	</div>

	<p class="screen-reader-text" data-shanelle-collections-page-status role="status" aria-live="polite" aria-atomic="true"></p>
</main>
