<?php
/**
 * Footer policies legal links markup (bottom bar).
 *
 * Compact pipe-separated legal links beside copyright — not a main-column stack.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\FooterPolicies;

defined( 'ABSPATH' ) || exit;
?>
<nav
	class="footer-policies"
	data-shanelle-footer-policies
	aria-label="<?php echo esc_attr( FooterPolicies::get_nav_aria_label() ); ?>"
>
	<?php FooterPolicies::render_menu(); ?>
</nav>
