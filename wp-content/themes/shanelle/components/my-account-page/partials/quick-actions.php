<?php
/**
 * Quick actions partial.
 *
 * @package Shanelle
 *
 * @var array<int, array<string, string>> $actions
 */

declare(strict_types=1);

use Shanelle\Components\MyAccountPage;

defined( 'ABSPATH' ) || exit;
?>
<nav class="my-account-page__quick-actions" aria-label="<?php esc_attr_e( 'Quick actions', 'shanelle' ); ?>">
	<ul class="my-account-page__quick-actions-list">
		<?php foreach ( $actions as $action ) : ?>
			<li class="my-account-page__quick-actions-item">
				<a
					class="my-account-page__quick-action btn btn--outline btn--sm"
					href="<?php echo esc_url( $action['url'] ); ?>"
					<?php echo MyAccountPage::is_active_endpoint( (string) ( $action['endpoint'] ?? '' ) ) ? ' aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $action['label'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
