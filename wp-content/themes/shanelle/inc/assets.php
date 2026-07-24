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
		'shanelle-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'shanelle-main',
		SHANELLE_URI . '/assets/css/main.css',
		array( 'shanelle-fonts' ),
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
 * Ensure Shanelle scripts load as ES modules.
 *
 * WordPress 6.3+ supports wp_script_add_data( ..., 'type', 'module' ), but some
 * environments still omit the attribute. This filter guarantees module loading.
 *
 * @param string $tag    The script tag HTML.
 * @param string $handle The script handle.
 * @param string $src    The script source URL.
 * @return string
 */
function shanelle_script_loader_tag( string $tag, string $handle, string $src ): string {
	unset( $src );

	if ( ! str_starts_with( $handle, 'shanelle-' ) ) {
		return $tag;
	}

	if ( preg_match( '/type=(["\'])module\1/i', $tag ) ) {
		return $tag;
	}

	$tag = preg_replace( '/\stype=(["\'])[^"\']*\1/i', '', $tag );

	return str_replace( '<script ', '<script type="module" ', $tag );
}
add_filter( 'script_loader_tag', 'shanelle_script_loader_tag', 20, 3 );

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
