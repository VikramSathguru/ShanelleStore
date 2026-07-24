<?php
/**
 * Endpoint content shell opening partial.
 *
 * @package Shanelle
 *
 * @var string $modifier
 * @var string $title
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$section_class = 'my-account-page__section my-account-page__section--' . sanitize_html_class( $modifier );
?>
<section class="<?php echo esc_attr( $section_class ); ?>" data-shanelle-account-endpoint="<?php echo esc_attr( $modifier ); ?>">
	<?php if ( ! empty( $title ) ) : ?>
		<h2 class="my-account-page__subsection-title text-h3"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>
	<div class="my-account-page__section-body">
