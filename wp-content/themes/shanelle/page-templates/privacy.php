<?php
/**
 * Template Name: Política de privacidad
 *
 * Privacy policy informational page. Body content is fully editable in WordPress.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

\Shanelle\Components\InfoPage::render( 'privacy' );

get_footer();
