<?php
/**
 * Mobile bottom navigation partial.
 *
 * @package Shanelle
 *
 * @var array<int, array<string, string>> $items
 */

declare(strict_types=1);

use Shanelle\Components\MyAccountPage;

defined( 'ABSPATH' ) || exit;
?>
<nav
	class="my-account-page__bottom-nav"
	aria-label="<?php esc_attr_e( 'Navegación rápida de cuenta', 'shanelle' ); ?>"
	data-shanelle-account-bottom-nav
	style="--bottom-nav-count: <?php echo esc_attr( (string) count( $items ) ); ?>;"
>
	<ul class="my-account-page__bottom-nav-list">
		<?php foreach ( $items as $item ) : ?>
			<?php $is_active = MyAccountPage::is_active_endpoint( (string) $item['endpoint'] ); ?>
			<li class="my-account-page__bottom-nav-item<?php echo $is_active ? ' is-active' : ''; ?>">
				<a
					class="my-account-page__bottom-nav-link"
					href="<?php echo esc_url( $item['url'] ); ?>"
					<?php echo $is_active ? ' aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $item['label'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
