<?php
/**
 * Template Name: Contenido informativo
 *
 * Generic editorial informational page (size guide, tracking, payments, etc.).
 * Body content is fully editable in WordPress.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

\Shanelle\Components\InfoPage::render( 'editorial' );

get_footer();
