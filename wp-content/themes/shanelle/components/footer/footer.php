<?php
/**
 * Footer component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\Footer;

defined( 'ABSPATH' ) || exit;
?>
<footer
	class="footer"
	id="<?php echo esc_attr( Footer::get_root_id() ); ?>"
	data-shanelle-footer
	data-footer-state="<?php echo esc_attr( Footer::get_state_json() ); ?>"
>
	<div class="footer__main">
		<div class="container footer__inner">
			<?php \Shanelle\Components\FooterBrand::render(); ?>
			<?php \Shanelle\Components\FooterLinks::render(); ?>
			<?php \Shanelle\Components\FooterCustomerService::render(); ?>

			<?php Footer::render_menus(); ?>
			<?php Footer::render_contact_details(); ?>

			<aside class="footer__engage" data-shanelle-footer-engage aria-label="<?php esc_attr_e( 'Boletín y redes', 'shanelle' ); ?>">
				<?php Footer::render_newsletter(); ?>
				<?php \Shanelle\Components\FooterBrand::render_social(); ?>
			</aside>
		</div>
	</div>

	<div class="footer__bottom">
		<div class="container footer__bottom-inner">
			<div class="footer__legal">
				<?php Footer::render_copyright(); ?>
				<?php \Shanelle\Components\FooterPolicies::render(); ?>
			</div>
			<?php Footer::render_payment_icons(); ?>
		</div>
	</div>

	<?php Footer::render_contact_fab(); ?>
	<?php Footer::render_scroll_top(); ?>

	<p class="screen-reader-text" data-shanelle-footer-status role="status" aria-live="polite" aria-atomic="true"></p>
</footer>
