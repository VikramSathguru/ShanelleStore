<?php
/**
 * 404 template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

get_header();
?>

<main id="primary" class="site-main">
	<div class="container">
		<?php
		shanelle_component( 'empty-state', array(
			'title'   => __( 'Página no encontrada', 'shanelle' ),
			'message' => __( 'La página que buscas no existe o fue movida.', 'shanelle' ),
			'cta_url' => home_url( '/' ),
			'cta_text' => __( 'Volver al inicio', 'shanelle' ),
		) );
		?>
	</div>
</main>

<?php
get_footer();
