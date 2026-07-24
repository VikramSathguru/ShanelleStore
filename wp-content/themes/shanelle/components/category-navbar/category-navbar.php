<?php
/**
 * Header category navbar template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\CategoryNavbar;

defined( 'ABSPATH' ) || exit;
?>
<div
	class="category-navbar"
	id="<?php echo esc_attr( CategoryNavbar::get_root_id() ); ?>"
	data-category-navbar
>
	<div class="container category-navbar__inner">
		<button
			type="button"
			class="category-navbar__scroll category-navbar__scroll--prev"
			data-category-navbar-prev
			aria-label="<?php esc_attr_e( 'Desplazar categorías hacia atrás', 'shanelle' ); ?>"
			hidden
		>
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
		</button>

		<nav class="category-navbar__nav" aria-label="<?php esc_attr_e( 'Categorías de la tienda', 'shanelle' ); ?>">
			<ul class="category-navbar__list scrollbar-hide" data-category-navbar-list role="list">
				<?php CategoryNavbar::render_links(); ?>
			</ul>
		</nav>

		<button
			type="button"
			class="category-navbar__scroll category-navbar__scroll--next"
			data-category-navbar-next
			aria-label="<?php esc_attr_e( 'Desplazar categorías hacia adelante', 'shanelle' ); ?>"
			hidden
		>
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
		</button>
	</div>

	<?php CategoryNavbar::render_dropdown_panel(); ?>
</div>
