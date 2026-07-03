<?php
/**
 * Address card partial.
 *
 * @package Shanelle
 *
 * @var array<string, mixed> $address
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<article class="my-account-page__address-card<?php echo ! empty( $address['is_empty'] ) ? ' my-account-page__address-card--empty' : ''; ?>">
	<header class="my-account-page__address-card-header">
		<h2 class="my-account-page__address-card-title text-h3"><?php echo esc_html( $address['title'] ); ?></h2>
		<a class="my-account-page__address-card-edit text-label" href="<?php echo esc_url( $address['edit_url'] ); ?>">
			<?php echo esc_html( $address['edit_label'] ); ?>
		</a>
	</header>

	<div class="my-account-page__address-card-body">
		<?php if ( ! empty( $address['is_empty'] ) ) : ?>
			<p class="my-account-page__address-card-empty text-body text-muted">
				<?php esc_html_e( 'You have not set up this type of address yet.', 'woocommerce' ); ?>
			</p>
		<?php else : ?>
			<address class="my-account-page__address-card-content">
				<?php echo wp_kses_post( $address['formatted'] ); ?>
			</address>
		<?php endif; ?>
	</div>
</article>
