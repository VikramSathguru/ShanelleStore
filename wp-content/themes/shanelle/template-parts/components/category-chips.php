<?php
/**
 * Horizontal category chips navigation.
 *
 * @package Shanelle
 *
 * @var array<string, mixed> $args
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( ! has_nav_menu( 'categories' ) ) {
	return;
}
?>
<nav class="category-chips" aria-label="<?php esc_attr_e( 'Explorar categorías', 'shanelle' ); ?>">
	<div class="container category-chips__scroll">
		<?php
		wp_nav_menu( array(
			'theme_location' => 'categories',
			'menu_class'     => 'category-chips__list',
			'container'      => false,
			'depth'          => 1,
			'fallback_cb'    => false,
		) );
		?>
	</div>
</nav>
