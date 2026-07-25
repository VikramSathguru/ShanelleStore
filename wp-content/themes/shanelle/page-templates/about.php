<?php
/**
 * Template Name: Sobre nosotros
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

\Shanelle\Components\AboutPage::render();

get_footer();
