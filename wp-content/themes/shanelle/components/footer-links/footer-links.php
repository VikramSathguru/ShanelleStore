<?php
/**
 * Footer useful-links column markup.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\FooterLinks;

defined( 'ABSPATH' ) || exit;

$title_id = 'shanelle-footer-links-title';
?>
<section class="footer-links" data-shanelle-footer-links aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<h2 id="<?php echo esc_attr( $title_id ); ?>" class="footer-links__title text-label">
		<?php echo esc_html( FooterLinks::get_title() ); ?>
	</h2>

	<nav class="footer-links__nav" aria-label="<?php echo esc_attr( FooterLinks::get_nav_aria_label() ); ?>">
		<?php FooterLinks::render_menu(); ?>
	</nav>
</section>
