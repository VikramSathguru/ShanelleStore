<?php
/**
 * The Template for displaying all single products.
 *
 * Composes the ProductDetail layout from existing product components.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) {
	the_post();
	\Shanelle\Components\ProductDetail::render();
}

get_footer();
