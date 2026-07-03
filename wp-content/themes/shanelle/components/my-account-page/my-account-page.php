<?php
/**
 * My Account page component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\MyAccountPage;

defined( 'ABSPATH' ) || exit;

$welcome_line = MyAccountPage::get_welcome_line();
?>
<div
	class="my-account-page"
	id="<?php echo esc_attr( MyAccountPage::get_root_id() ); ?>"
	data-shanelle-my-account-page
	data-account-state="<?php echo esc_attr( MyAccountPage::get_state_json() ); ?>"
>
	<header class="my-account-page__header">
		<div class="my-account-page__header-copy">
			<h1 id="<?php echo esc_attr( MyAccountPage::get_heading_id() ); ?>" class="my-account-page__title text-h1">
				<?php echo esc_html( MyAccountPage::get_page_title() ); ?>
			</h1>

			<?php if ( '' !== $welcome_line ) : ?>
				<p class="my-account-page__welcome text-body text-muted">
					<?php echo esc_html( $welcome_line ); ?>
				</p>
			<?php endif; ?>
		</div>

		<?php if ( MyAccountPage::show_shop_link() ) : ?>
			<a class="my-account-page__shop-link text-label" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ?: home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Continue shopping', 'shanelle' ); ?>
			</a>
		<?php endif; ?>
	</header>

	<?php MyAccountPage::render_notices(); ?>

	<div class="my-account-page__layout">
		<aside class="my-account-page__nav" aria-label="<?php esc_attr_e( 'Account navigation', 'shanelle' ); ?>">
			<?php MyAccountPage::render_navigation(); ?>
		</aside>

		<div class="my-account-page__content">
			<?php MyAccountPage::render_content(); ?>
		</div>
	</div>

	<p class="screen-reader-text" data-shanelle-my-account-page-status role="status" aria-live="polite" aria-atomic="true"></p>
</div>
