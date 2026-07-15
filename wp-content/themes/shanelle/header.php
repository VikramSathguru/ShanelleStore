<?php
/**
 * Header template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Saltar al contenido', 'shanelle' ); ?></a>

	<?php shanelle_component( 'site-header' ); ?>
