<?php
/**
 * Template Name: Contacto
 *
 * Informational contact page. Add Fluent Forms / Omnisend shortcodes in the
 * page editor — the theme does not render a custom form.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

\Shanelle\Components\InfoPage::render( 'contact' );

get_footer();
