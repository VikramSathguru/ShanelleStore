<?php
/**
 * My Account navigation template override.
 *
 * @package Shanelle
 * @version 9.3.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation my-account-page__navigation" aria-label="<?php esc_attr_e( 'Account pages', 'woocommerce' ); ?>" data-shanelle-account-nav>
	<ul class="my-account-page__navigation-list">
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<li class="my-account-page__navigation-item <?php echo esc_attr( wc_get_account_menu_item_classes( $endpoint ) ); ?>">
				<a class="my-account-page__navigation-link" href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" <?php echo wc_is_current_account_menu_item( $endpoint ) ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
