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
			<div class="footer__brand">
				<?php Footer::render_logo(); ?>
				<?php Footer::render_brand_description(); ?>
				<?php Footer::render_social_links(); ?>
			</div>

			<?php Footer::render_newsletter(); ?>
			<?php Footer::render_menus(); ?>
		</div>
	</div>

	<div class="footer__bottom">
		<div class="container footer__bottom-inner">
			<?php Footer::render_copyright(); ?>
			<?php Footer::render_payment_icons(); ?>
		</div>
	</div>

	<p class="screen-reader-text" data-shanelle-footer-status role="status" aria-live="polite" aria-atomic="true"></p>
</footer>
