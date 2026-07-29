<?php
/**
 * CLI: seed About page Customizer theme mods from brand defaults.
 *
 * Usage:
 *   php bin/seed-about-page.php
 *   php bin/seed-about-page.php --force
 *
 * @package Shanelle
 */

declare(strict_types=1);

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "CLI only.\n" );
	exit( 1 );
}

require dirname( __DIR__, 4 ) . '/wp-load.php';

$force = in_array( '--force', $argv, true );

\Shanelle\Components\AboutPage::seed_theme_mods( $force );

$page = get_page_by_path( 'sobre-nosotros' );
if ( $page instanceof WP_Post ) {
	wp_update_post(
		array(
			'ID'         => $page->ID,
			'post_title' => 'Sobre nosotros',
		)
	);
}

$locations = get_nav_menu_locations();
$menu_id   = isset( $locations['footer_useful_links'] ) ? (int) $locations['footer_useful_links'] : 0;
if ( $menu_id > 0 && $page instanceof WP_Post ) {
	$items = wp_get_nav_menu_items( $menu_id );
	if ( is_array( $items ) ) {
		foreach ( $items as $item ) {
			if ( (int) $item->object_id === (int) $page->ID && 'About Us' === $item->title ) {
				wp_update_nav_menu_item(
					$menu_id,
					(int) $item->ID,
					array(
						'menu-item-title'     => 'Sobre nosotros',
						'menu-item-object'    => 'page',
						'menu-item-object-id' => (int) $page->ID,
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-position'  => (int) $item->menu_order,
						'menu-item-parent-id' => (int) $item->menu_item_parent,
					)
				);
			}
		}
	}
}

echo $force ? "About page theme mods seeded (forced).\n" : "About page theme mods seeded.\n";
echo 'Hero: ' . get_theme_mod( 'shanelle_about_page_hero_headline' ) . "\n";
echo 'Values: ' . get_theme_mod( 'shanelle_about_page_value_1_title' ) . ' / '
	. get_theme_mod( 'shanelle_about_page_value_2_title' ) . ' / '
	. get_theme_mod( 'shanelle_about_page_value_3_title' ) . "\n";
