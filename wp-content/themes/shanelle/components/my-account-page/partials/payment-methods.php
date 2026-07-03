<?php
/**
 * Payment methods endpoint partial.
 *
 * @package Shanelle
 *
 * @var array<int, mixed> $methods
 * @var bool              $has_methods
 * @var string            $add_url
 * @var bool              $show_add_link
 */

declare(strict_types=1);

use Shanelle\Components\MyAccountPage;

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_payment_methods', $has_methods );
?>
<section class="my-account-page__section my-account-page__section--payment-methods" data-shanelle-account-endpoint="payment-methods">
	<?php if ( $has_methods ) : ?>
		<ul class="my-account-page__payment-list">
			<?php foreach ( $methods as $method ) : ?>
				<li class="my-account-page__payment-card<?php echo ! empty( $method['is_default'] ) ? ' my-account-page__payment-card--default' : ''; ?>">
					<div class="my-account-page__payment-card-body">
						<p class="my-account-page__payment-card-label text-body"><?php echo esc_html( $method['label'] ); ?></p>
						<?php if ( ! empty( $method['expires'] ) ) : ?>
							<p class="my-account-page__payment-card-expires text-body-sm text-muted">
								<?php
								printf(
									/* translators: %s: expiry date */
									esc_html__( 'Expires %s', 'shanelle' ),
									esc_html( $method['expires'] )
								);
								?>
							</p>
						<?php endif; ?>
						<?php if ( ! empty( $method['is_default'] ) ) : ?>
							<span class="my-account-page__payment-card-badge text-label"><?php esc_html_e( 'Default', 'shanelle' ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $method['actions'] ) ) : ?>
						<div class="my-account-page__payment-card-actions">
							<?php foreach ( $method['actions'] as $key => $action ) : ?>
								<a class="btn btn--outline btn--sm" href="<?php echo esc_url( $action['url'] ); ?>">
									<?php echo esc_html( $action['name'] ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<?php
		MyAccountPage::render_empty_state(
			array(
				'title'    => __( 'No saved payment methods', 'shanelle' ),
				'message'  => __( 'Save a payment method at checkout for faster future purchases.', 'shanelle' ),
				'cta_url'  => $show_add_link ? $add_url : ( wc_get_page_permalink( 'shop' ) ?: home_url( '/' ) ),
				'cta_text' => $show_add_link ? __( 'Add payment method', 'woocommerce' ) : __( 'Browse products', 'woocommerce' ),
			)
		);
		?>
	<?php endif; ?>

	<?php if ( $show_add_link && $has_methods ) : ?>
		<p class="my-account-page__section-actions">
			<a class="btn btn--outline btn--sm" href="<?php echo esc_url( $add_url ); ?>">
				<?php esc_html_e( 'Add payment method', 'woocommerce' ); ?>
			</a>
		</p>
	<?php endif; ?>
</section>
<?php
do_action( 'woocommerce_after_account_payment_methods', $has_methods );
