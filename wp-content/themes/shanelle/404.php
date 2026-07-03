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
			'title'   => __( 'Page not found', 'shanelle' ),
			'message' => __( 'The page you are looking for does not exist or has moved.', 'shanelle' ),
			'cta_url' => home_url( '/' ),
			'cta_text' => __( 'Back to home', 'shanelle' ),
		) );
		?>
	</div>
</main>

<?php
get_footer();
