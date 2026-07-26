<?php
/**
 * Footer customer-service column markup.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\FooterCustomerService;

defined( 'ABSPATH' ) || exit;

$title_id = 'shanelle-footer-customer-service-title';
?>
<section
	class="footer-links footer-customer-service"
	data-shanelle-footer-customer-service
	aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
>
	<h2 id="<?php echo esc_attr( $title_id ); ?>" class="footer-links__title text-label">
		<?php echo esc_html( FooterCustomerService::get_title() ); ?>
	</h2>

	<nav class="footer-links__nav" aria-label="<?php echo esc_attr( FooterCustomerService::get_nav_aria_label() ); ?>">
		<?php FooterCustomerService::render_menu(); ?>
	</nav>
</section>
