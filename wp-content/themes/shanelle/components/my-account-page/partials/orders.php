<?php
/**
 * Orders endpoint partial.
 *
 * @package Shanelle
 *
 * @var array<int, mixed> $orders
 * @var bool              $has_orders
 * @var int               $current_page
 * @var int               $max_num_pages
 * @var string            $wp_button_class
 */

declare(strict_types=1);

use Shanelle\Components\MyAccountPage;

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders );
?>
<section class="my-account-page__section my-account-page__section--orders" data-shanelle-account-endpoint="orders">
	<?php if ( $has_orders ) : ?>
		<div class="my-account-page__loading-host" data-shanelle-account-skeleton-host>
			<?php MyAccountPage::render_loading_skeleton( 'orders', 4 ); ?>
		</div>

		<div class="my-account-page__order-list" data-shanelle-account-order-list hidden>
			<?php foreach ( $orders as $order ) : ?>
				<?php MyAccountPage::render_order_card( $order ); ?>
			<?php endforeach; ?>
		</div>

		<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

		<?php if ( 1 < $max_num_pages ) : ?>
			<nav class="my-account-page__pagination woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination" aria-label="<?php esc_attr_e( 'Orders pagination', 'shanelle' ); ?>">
				<?php if ( 1 !== $current_page ) : ?>
					<a class="btn btn--outline btn--sm woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">
						<?php esc_html_e( 'Previous', 'woocommerce' ); ?>
					</a>
				<?php endif; ?>

				<?php if ( $max_num_pages !== $current_page ) : ?>
					<a class="btn btn--outline btn--sm woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">
						<?php esc_html_e( 'Next', 'woocommerce' ); ?>
					</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<?php
		MyAccountPage::render_empty_state(
			array(
				'title'    => __( 'No orders yet', 'shanelle' ),
				'message'  => __( 'You have not placed any orders yet. Explore our latest styles and find something you love.', 'shanelle' ),
				'cta_url'  => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
				'cta_text' => __( 'Browse products', 'woocommerce' ),
			)
		);
		?>
	<?php endif; ?>
</section>
<?php
do_action( 'woocommerce_after_account_orders', $has_orders );
