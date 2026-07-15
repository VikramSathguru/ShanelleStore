<?php
/**
 * Theme setup and feature support.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports, menus, and image sizes.
 */
function shanelle_setup(): void {
	load_theme_textdomain( 'shanelle', SHANELLE_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 96,
		'width'       => 96,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	register_nav_menus( array(
		'primary'   => __( 'Navegación principal', 'shanelle' ),
		'mobile'    => __( 'Navegación del menú móvil', 'shanelle' ),
		'footer'    => __( 'Navegación del pie de página', 'shanelle' ),
		'categories' => __( 'Chips de categorías', 'shanelle' ),
	) );

	add_image_size( 'shanelle-product-card', 400, 533, true );
	add_image_size( 'shanelle-product-card-2x', 800, 1066, true );
	add_image_size( 'shanelle-hero', 1440, 720, true );
}
add_action( 'after_setup_theme', 'shanelle_setup' );

/**
 * Set content width for embedded media.
 */
function shanelle_content_width(): void {
	$GLOBALS['content_width'] = 1280;
}
add_action( 'after_setup_theme', 'shanelle_content_width', 0 );

/**
 * Register widget areas.
 */
function shanelle_widgets_init(): void {
	register_sidebar( array(
		'name'          => __( 'Barra lateral de la tienda', 'shanelle' ),
		'id'            => 'shop-sidebar',
		'description'   => __( 'Filtros y widgets en las páginas de la tienda.', 'shanelle' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget__title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'shanelle_widgets_init' );
