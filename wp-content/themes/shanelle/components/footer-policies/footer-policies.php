<?php
/**
 * Footer policies column markup.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\FooterPolicies;

defined( 'ABSPATH' ) || exit;

$title_id = 'shanelle-footer-policies-title';
?>
<section
	class="footer-links footer-policies"
	data-shanelle-footer-policies
	aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
>
	<h2 id="<?php echo esc_attr( $title_id ); ?>" class="footer-links__title text-label">
		<?php echo esc_html( FooterPolicies::get_title() ); ?>
	</h2>

	<nav class="footer-links__nav" aria-label="<?php echo esc_attr( FooterPolicies::get_nav_aria_label() ); ?>">
		<?php FooterPolicies::render_menu(); ?>
	</nav>
</section>
