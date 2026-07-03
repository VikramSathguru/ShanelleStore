<?php
/**
 * Template Name: Collections
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

\Shanelle\Components\CollectionsPage::render();

get_footer();
