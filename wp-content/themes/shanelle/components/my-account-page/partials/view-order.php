<?php
/**
 * View order endpoint partial.
 *
 * @package Shanelle
 *
 * @var array<string, mixed>   $order
 * @var int                    $order_id
 * @var array<int, WP_Comment> $notes
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<section class="my-account-page__section my-account-page__section--view-order" data-shanelle-account-endpoint="view-order">
	<header class="my-account-page__order-detail-header">
		<a class="my-account-page__back-link text-label" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">
			<?php esc_html_e( 'Volver a pedidos', 'shanelle' ); ?>
		</a>

		<div class="my-account-page__order-detail-summary">
			<h2 class="my-account-page__order-detail-title text-h3">
				<?php
				printf(
					/* translators: %s: order number */
					esc_html__( 'Pedido #%s', 'shanelle' ),
					esc_html( $order['number'] )
				);
				?>
			</h2>
			<span class="my-account-page__status-badge my-account-page__status-badge--<?php echo esc_attr( $order['status_class'] ); ?>">
				<?php echo esc_html( $order['status_label'] ); ?>
			</span>
		</div>

		<p class="my-account-page__order-detail-meta text-body text-muted">
			<?php if ( ! empty( $order['date_iso'] ) ) : ?>
				<time datetime="<?php echo esc_attr( $order['date_iso'] ); ?>"><?php echo esc_html( $order['date'] ); ?></time>
				<span aria-hidden="true"> · </span>
				<span><?php echo esc_html( $order['status_label'] ); ?></span>
			<?php endif; ?>
		</p>
	</header>

	<?php if ( ! empty( $notes ) ) : ?>
		<div class="my-account-page__order-updates">
			<h3 class="my-account-page__subsection-title text-h3"><?php esc_html_e( 'Order updates', 'woocommerce' ); ?></h3>
			<ol class="my-account-page__order-updates-list woocommerce-OrderUpdates commentlist notes">
				<?php foreach ( $notes as $note ) : ?>
					<li class="my-account-page__order-update woocommerce-OrderUpdate comment note">
						<p class="my-account-page__order-update-meta text-body-sm text-muted">
							<?php echo esc_html( date_i18n( esc_html__( 'l jS \o\f F Y, h:ia', 'woocommerce' ), strtotime( $note->comment_date ) ) ); ?>
						</p>
						<div class="my-account-page__order-update-text text-body">
							<?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	<?php endif; ?>

	<div class="my-account-page__order-detail-content">
		<?php do_action( 'woocommerce_view_order', $order_id ); ?>
	</div>
</section>
