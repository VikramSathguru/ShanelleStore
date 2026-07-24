<?php
/**
 * Order card partial.
 *
 * @package Shanelle
 *
 * @var array<string, mixed> $order
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<article class="my-account-page__order-card my-account-page__order-card--<?php echo esc_attr( $order['status_class'] ); ?>">
	<div class="my-account-page__order-card-header">
		<div class="my-account-page__order-card-meta">
			<a class="my-account-page__order-card-number text-label" href="<?php echo esc_url( $order['view_url'] ); ?>">
				<?php
				printf(
					/* translators: %s: order number */
					esc_html__( 'Pedido #%s', 'shanelle' ),
					esc_html( $order['number'] )
				);
				?>
			</a>
			<?php if ( ! empty( $order['date_iso'] ) ) : ?>
				<time class="my-account-page__order-card-date text-body-sm text-muted" datetime="<?php echo esc_attr( $order['date_iso'] ); ?>">
					<?php echo esc_html( $order['date'] ); ?>
				</time>
			<?php endif; ?>
		</div>
		<span class="my-account-page__order-card-status my-account-page__status-badge my-account-page__status-badge--<?php echo esc_attr( $order['status_class'] ); ?>">
			<?php echo esc_html( $order['status_label'] ); ?>
		</span>
	</div>

	<div class="my-account-page__order-card-body">
		<p class="my-account-page__order-card-total text-body">
			<?php echo wp_kses_post( $order['total_html'] ); ?>
			<span class="my-account-page__order-card-items text-muted">· <?php echo esc_html( $order['items_label'] ); ?></span>
		</p>
	</div>

	<?php if ( ! empty( $order['actions'] ) ) : ?>
		<div class="my-account-page__order-card-actions">
			<?php foreach ( $order['actions'] as $key => $action ) : ?>
				<?php
				$aria_label = ! empty( $action['aria-label'] )
					? $action['aria-label']
					: sprintf(
						/* translators: 1: action name, 2: order number */
						__( '%1$s order number %2$s', 'woocommerce' ),
						$action['name'],
						$order['number']
					);
				?>
				<a
					class="btn btn--outline btn--sm my-account-page__order-card-action"
					href="<?php echo esc_url( $action['url'] ); ?>"
					aria-label="<?php echo esc_attr( $aria_label ); ?>"
				>
					<?php echo esc_html( $action['name'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</article>
