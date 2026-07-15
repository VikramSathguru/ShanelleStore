<?php
/**
 * Cart page component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\CartPage;

defined( 'ABSPATH' ) || exit;
?>
<div
	class="cart-page<?php echo CartPage::is_empty() ? ' cart-page--empty' : ''; ?>"
	id="<?php echo esc_attr( CartPage::get_root_id() ); ?>"
	data-shanelle-cart-page
	data-cart-state="<?php echo esc_attr( CartPage::get_state_json() ); ?>"
>
	<header class="cart-page__header">
		<h1 id="<?php echo esc_attr( CartPage::get_heading_id() ); ?>" class="cart-page__title text-h1">
			<?php esc_html_e( 'Tu bolsa', 'shanelle' ); ?>
		</h1>
	</header>

	<?php CartPage::render_notices(); ?>

	<?php if ( CartPage::is_empty() ) : ?>
		<?php CartPage::render_empty(); ?>
	<?php else : ?>
		<?php CartPage::render_cart_layout(); ?>
		<?php CartPage::render_cross_sells(); ?>
	<?php endif; ?>

	<p class="screen-reader-text" data-shanelle-cart-page-status role="status" aria-live="polite" aria-atomic="true"></p>
</div>
