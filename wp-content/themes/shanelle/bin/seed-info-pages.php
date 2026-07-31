<?php
/**
 * CLI: seed informational / policy page content into WordPress.
 *
 * Usage:
 *   php bin/seed-info-pages.php
 *   php bin/seed-info-pages.php --force
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

$results = Shanelle\Setup\InfoPageSeeder::seed( $force );

foreach ( $results as $key => $post_id ) {
	$title = $post_id > 0 ? get_the_title( $post_id ) : '(failed)';
	$url   = $post_id > 0 ? (string) get_permalink( $post_id ) : '';
	echo sprintf( "%s → #%d %s %s\n", $key, $post_id, $title, $url );
}

echo $force ? "Seed complete (forced overwrite).\n" : "Seed complete.\n";
