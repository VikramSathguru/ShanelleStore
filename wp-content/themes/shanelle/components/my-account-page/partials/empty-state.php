<?php
/**
 * Account empty state partial.
 *
 * @package Shanelle
 *
 * @var string $title
 * @var string $message
 * @var string $cta_url
 * @var string $cta_text
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="my-account-page__empty-state">
	<h2 class="my-account-page__empty-state-title text-h3"><?php echo esc_html( $title ); ?></h2>
	<p class="my-account-page__empty-state-message text-body text-muted"><?php echo esc_html( $message ); ?></p>
	<?php if ( ! empty( $cta_url ) && ! empty( $cta_text ) ) : ?>
		<a class="btn btn--primary my-account-page__empty-state-action" href="<?php echo esc_url( $cta_url ); ?>">
			<?php echo esc_html( $cta_text ); ?>
		</a>
	<?php endif; ?>
</div>
