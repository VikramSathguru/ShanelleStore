<?php
/**
 * Loading skeleton partial.
 *
 * @package Shanelle
 *
 * @var string $context
 * @var int    $count
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$modifier = sanitize_html_class( $context );
?>
<div class="my-account-page__skeleton my-account-page__skeleton--<?php echo esc_attr( $modifier ); ?>" data-shanelle-account-skeleton aria-hidden="true">
	<?php for ( $i = 0; $i < $count; $i++ ) : ?>
		<div class="my-account-page__skeleton-card">
			<div class="my-account-page__skeleton-line my-account-page__skeleton-line--title skeleton"></div>
			<div class="my-account-page__skeleton-line skeleton"></div>
			<div class="my-account-page__skeleton-line my-account-page__skeleton-line--short skeleton"></div>
		</div>
	<?php endfor; ?>
</div>
