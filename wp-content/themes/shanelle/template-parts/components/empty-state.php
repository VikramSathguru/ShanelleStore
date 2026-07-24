<?php
/**
 * Empty state component.
 *
 * @package Shanelle
 *
 * @var array<string, mixed> $args
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$title    = $args['title'] ?? '';
$message  = $args['message'] ?? '';
$cta_url  = $args['cta_url'] ?? '';
$cta_text = $args['cta_text'] ?? '';
?>
<section class="empty-state">
	<?php if ( $title ) : ?>
		<h1 class="empty-state__title"><?php echo esc_html( $title ); ?></h1>
	<?php endif; ?>

	<?php if ( $message ) : ?>
		<p class="empty-state__message"><?php echo esc_html( $message ); ?></p>
	<?php endif; ?>

	<?php if ( $cta_url && $cta_text ) : ?>
		<a class="btn btn--primary empty-state__cta" href="<?php echo esc_url( $cta_url ); ?>">
			<?php echo esc_html( $cta_text ); ?>
		</a>
	<?php endif; ?>
</section>
