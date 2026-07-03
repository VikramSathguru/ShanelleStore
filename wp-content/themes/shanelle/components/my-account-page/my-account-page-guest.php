<?php
/**
 * My Account guest views component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\MyAccountPage;

defined( 'ABSPATH' ) || exit;
?>
<div
	class="my-account-page my-account-page--guest my-account-page--<?php echo esc_attr( MyAccountPage::get_guest_view() ); ?>"
	id="<?php echo esc_attr( MyAccountPage::get_root_id() ); ?>"
	data-shanelle-my-account-page
	data-account-state="<?php echo esc_attr( MyAccountPage::get_state_json() ); ?>"
>
	<header class="my-account-page__header">
		<div class="my-account-page__header-copy">
			<h1 id="<?php echo esc_attr( MyAccountPage::get_heading_id() ); ?>" class="my-account-page__title text-h1">
				<?php echo esc_html( MyAccountPage::get_page_title() ); ?>
			</h1>
		</div>

		<?php if ( MyAccountPage::show_shop_link() ) : ?>
			<a class="my-account-page__shop-link text-label" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ?: home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Continue shopping', 'shanelle' ); ?>
			</a>
		<?php endif; ?>
	</header>

	<?php MyAccountPage::render_notices(); ?>

	<div class="my-account-page__guest-card">
		<?php MyAccountPage::render_guest_content(); ?>
	</div>

	<p class="screen-reader-text" data-shanelle-my-account-page-status role="status" aria-live="polite" aria-atomic="true"></p>
</div>
