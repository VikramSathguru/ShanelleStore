<?php
/**
 * Search results template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

if ( shanelle_is_woocommerce_active() ) {
	\Shanelle\Components\SearchPage::render();
} else {
	?>
	<main id="primary" class="site-main">
		<div class="container">
			<?php
			shanelle_component(
				'empty-state',
				array(
					'title'   => __( 'Búsqueda no disponible', 'shanelle' ),
					'message' => __( 'Se requiere WooCommerce para la búsqueda de productos.', 'shanelle' ),
				)
			);
			?>
		</div>
	</main>
	<?php
}

get_footer();
