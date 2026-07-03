<?php
/**
 * Front page template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

get_header();
?>

<main id="primary" class="site-main front-page">
	<?php shanelle_homepage(); ?>
</main>

<?php
get_footer();
