<?php
/**
 * Downloads endpoint partial.
 *
 * @package Shanelle
 *
 * @var array<int, mixed> $downloads
 * @var bool              $has_downloads
 */

declare(strict_types=1);

use Shanelle\Components\MyAccountPage;

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_downloads', $has_downloads );
?>
<section class="my-account-page__section my-account-page__section--downloads" data-shanelle-account-endpoint="downloads">
	<?php if ( $has_downloads ) : ?>
		<div class="my-account-page__loading-host" data-shanelle-account-skeleton-host>
			<?php MyAccountPage::render_loading_skeleton( 'downloads', 3 ); ?>
		</div>

		<ul class="my-account-page__download-list" data-shanelle-account-download-list hidden>
			<?php foreach ( $downloads as $download ) : ?>
				<li class="my-account-page__download-card">
					<div class="my-account-page__download-card-body">
						<h2 class="my-account-page__download-card-title text-h3"><?php echo esc_html( $download['product_name'] ); ?></h2>
						<p class="my-account-page__download-card-file text-body-sm text-muted"><?php echo esc_html( $download['file'] ); ?></p>
						<dl class="my-account-page__download-card-meta">
							<div class="my-account-page__download-card-meta-item">
								<dt class="text-label"><?php esc_html_e( 'Vence', 'shanelle' ); ?></dt>
								<dd class="text-body-sm"><?php echo esc_html( $download['access_expires'] ); ?></dd>
							</div>
							<?php if ( '' !== (string) $download['downloads_remaining'] ) : ?>
								<div class="my-account-page__download-card-meta-item">
									<dt class="text-label"><?php esc_html_e( 'Descargas restantes', 'shanelle' ); ?></dt>
									<dd class="text-body-sm"><?php echo esc_html( (string) $download['downloads_remaining'] ); ?></dd>
								</div>
							<?php endif; ?>
						</dl>
					</div>
					<a class="btn btn--primary btn--sm my-account-page__download-card-action" href="<?php echo esc_url( $download['download_url'] ); ?>">
						<?php esc_html_e( 'Download', 'woocommerce' ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<?php
		MyAccountPage::render_empty_state(
			array(
				'title'    => __( 'No hay descargas disponibles', 'shanelle' ),
				'message'  => __( 'Los productos descargables que compres aparecerán aquí.', 'shanelle' ),
				'cta_url'  => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
				'cta_text' => __( 'Browse products', 'woocommerce' ),
			)
		);
		?>
	<?php endif; ?>
</section>
<?php
do_action( 'woocommerce_after_account_downloads', $has_downloads );
