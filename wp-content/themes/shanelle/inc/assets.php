<?php
/**
 * Enqueue styles and scripts.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue front-end assets.
 */
function shanelle_enqueue_assets(): void {
	wp_enqueue_style(
		'shanelle-main',
		SHANELLE_URI . '/assets/css/main.css',
		array(),
		SHANELLE_VERSION
	);

	wp_enqueue_script(
		'shanelle-main',
		SHANELLE_URI . '/assets/js/main.js',
		array(),
		SHANELLE_VERSION,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_script_add_data( 'shanelle-main', 'type', 'module' );
}
add_action( 'wp_enqueue_scripts', 'shanelle_enqueue_assets' );

/**
 * Remove block library CSS on front-end when not needed.
 */
function shanelle_dequeue_unused_assets(): void {
	if ( ! is_admin() && ! is_customize_preview() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-blocks-style' );
	}
}
add_action( 'wp_enqueue_scripts', 'shanelle_dequeue_unused_assets', 100 );
