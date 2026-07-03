<?php
/**
 * Collection card component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\CollectionCard;

defined( 'ABSPATH' ) || exit;

$collection = CollectionCard::get_collection();

if ( empty( $collection['url'] ) ) {
	return;
}
?>
<article class="<?php echo esc_attr( implode( ' ', CollectionCard::get_card_classes() ) ); ?>" data-shanelle-collection-card>
	<a class="collection-card__link" href="<?php echo esc_url( (string) $collection['url'] ); ?>">
		<?php CollectionCard::render_media(); ?>

		<div class="collection-card__copy">
			<?php if ( CollectionCard::show_type_badge() && ! empty( $collection['type_label'] ) ) : ?>
				<p class="collection-card__type text-caption text-muted">
					<?php echo esc_html( (string) $collection['type_label'] ); ?>
				</p>
			<?php endif; ?>

			<h2 class="collection-card__title text-h4">
				<?php echo esc_html( (string) ( $collection['name'] ?? '' ) ); ?>
			</h2>

			<?php if ( CollectionCard::show_product_count() ) : ?>
				<p class="collection-card__count text-caption text-muted">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of products */
							_n( '%d product', '%d products', (int) ( $collection['product_count'] ?? 0 ), 'shanelle' ),
							(int) ( $collection['product_count'] ?? 0 )
						)
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</a>
</article>
