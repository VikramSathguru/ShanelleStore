<?php
/**
 * Dashboard endpoint partial.
 *
 * @package Shanelle
 *
 * @var WP_User                $user
 * @var array<int, mixed>      $quick_actions
 * @var array<int, mixed>      $recent_orders
 * @var string                 $orders_url
 */

declare(strict_types=1);

use Shanelle\Components\MyAccountPage;

defined( 'ABSPATH' ) || exit;
?>
<section class="my-account-page__section my-account-page__section--dashboard" data-shanelle-account-endpoint="dashboard">
	<div class="my-account-page__section-intro">
		<p class="my-account-page__section-lead text-body text-muted">
			<?php
			printf(
				/* translators: %s: user display name */
				esc_html__( 'Hola %s, administra tus pedidos, direcciones y configuración de cuenta desde aquí.', 'shanelle' ),
				esc_html( $user->display_name )
			);
			?>
		</p>
	</div>

	<?php if ( ! empty( $quick_actions ) ) : ?>
		<?php MyAccountPage::render_quick_actions_block( $quick_actions ); ?>
	<?php endif; ?>

	<div class="my-account-page__subsection">
		<div class="my-account-page__subsection-header">
			<h2 class="my-account-page__subsection-title text-h3"><?php esc_html_e( 'Pedidos recientes', 'shanelle' ); ?></h2>
			<a class="my-account-page__subsection-link text-label" href="<?php echo esc_url( $orders_url ); ?>">
				<?php esc_html_e( 'Ver todos', 'shanelle' ); ?>
			</a>
		</div>

		<div class="my-account-page__loading-host" data-shanelle-account-skeleton-host>
			<?php MyAccountPage::render_loading_skeleton( 'orders', 3 ); ?>
		</div>

		<div class="my-account-page__order-list" data-shanelle-account-order-list hidden>
			<?php if ( ! empty( $recent_orders ) ) : ?>
				<?php foreach ( $recent_orders as $order ) : ?>
					<?php MyAccountPage::render_order_card( $order ); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<?php
				MyAccountPage::render_empty_state(
					array(
						'title'    => __( 'Aún no hay pedidos', 'shanelle' ),
						'message'  => __( 'Cuando realices un pedido, aparecerá aquí.', 'shanelle' ),
						'cta_url'  => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
						'cta_text' => __( 'Empezar a comprar', 'shanelle' ),
					)
				);
				?>
			<?php endif; ?>
		</div>
	</div>

	<?php do_action( 'woocommerce_account_dashboard' ); ?>
</section>
