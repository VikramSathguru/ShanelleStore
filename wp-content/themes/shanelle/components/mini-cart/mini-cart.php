<?php
/**
 * Mini cart component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\MiniCart;

defined( 'ABSPATH' ) || exit;
?>
<div
	class="mini-cart"
	id="<?php echo esc_attr( MiniCart::get_root_id() ); ?>"
	data-shanelle-mini-cart
	data-cart-state="<?php echo esc_attr( MiniCart::get_state_json() ); ?>"
	hidden
>
	<button
		type="button"
		class="mini-cart__overlay"
		data-shanelle-mini-cart-overlay
		tabindex="-1"
		aria-hidden="true"
	></button>

	<aside
		class="mini-cart__panel"
		data-shanelle-mini-cart-panel
		role="dialog"
		aria-modal="true"
		aria-labelledby="<?php echo esc_attr( MiniCart::get_title_id() ); ?>"
		aria-hidden="true"
		tabindex="-1"
	>
		<header class="mini-cart__header">
			<h2 class="mini-cart__title" id="<?php echo esc_attr( MiniCart::get_title_id() ); ?>">
				<span class="mini-cart__title-text"><?php esc_html_e( 'Your bag', 'shanelle' ); ?></span>
				<?php MiniCart::render_title_count(); ?>
			</h2>
			<button
				type="button"
				class="mini-cart__close btn btn--ghost btn--icon"
				data-shanelle-mini-cart-close
				aria-label="<?php esc_attr_e( 'Close bag', 'shanelle' ); ?>"
			>
				<?php MiniCart::render_icon( 'close' ); ?>
			</button>
		</header>

		<?php MiniCart::render_fragment(); ?>

		<p class="screen-reader-text" data-shanelle-mini-cart-status role="status" aria-live="polite" aria-atomic="true"></p>
	</aside>
</div>
