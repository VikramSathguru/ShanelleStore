<?php
/**
 * Addresses endpoint partial.
 *
 * @package Shanelle
 *
 * @var array<int, mixed> $address_cards
 * @var string            $description
 */

declare(strict_types=1);

use Shanelle\Components\MyAccountPage;

defined( 'ABSPATH' ) || exit;
?>
<section class="my-account-page__section my-account-page__section--addresses" data-shanelle-account-endpoint="edit-address">
	<p class="my-account-page__section-lead text-body text-muted">
		<?php echo wp_kses_post( $description ); ?>
	</p>

	<div class="my-account-page__address-grid">
		<?php foreach ( $address_cards as $address ) : ?>
			<?php MyAccountPage::render_address_card( $address ); ?>
		<?php endforeach; ?>
	</div>
</section>
