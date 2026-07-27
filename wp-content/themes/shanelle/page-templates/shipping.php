<?php
/**
 * Template Name: Envíos
 *
 * Shipping policy informational page. Body content is fully editable in WordPress.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

\Shanelle\Components\InfoPage::render( 'shipping' );

get_footer();
