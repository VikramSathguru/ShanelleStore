<?php
/**
 * Section heading component.
 *
 * @package Shanelle
 *
 * @var array<string, mixed> $args
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$heading_id = $args['id'] ?? wp_unique_id( 'section-' );
$title      = $args['title'] ?? '';
$link       = $args['link'] ?? '';
$label      = $args['label'] ?? __( 'Ver todo', 'shanelle' );

if ( ! $title ) {
	return;
}
?>
<header class="section-heading">
	<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="section-heading__title"><?php echo esc_html( $title ); ?></h2>
	<?php if ( $link ) : ?>
		<a class="section-heading__link" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $label ); ?></a>
	<?php endif; ?>
</header>
