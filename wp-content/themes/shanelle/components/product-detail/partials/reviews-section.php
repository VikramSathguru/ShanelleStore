<?php
/**
 * Product detail reviews section partial.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\ProductDetail;

defined( 'ABSPATH' ) || exit;

$section_id   = ProductDetail::get_section_id( 'reviews' );
$heading_id   = ProductDetail::get_section_heading_id( 'reviews' );
$review_count = (int) ( $summary['count'] ?? 0 );
$has_reviews  = ! empty( $summary['has_reviews'] );
$can_review   = ! empty( $can_review );
$has_more     = ! empty( $has_more );
?>
<section
	class="product-detail__section product-detail__section--reviews product-reviews"
	id="<?php echo esc_attr( $section_id ); ?>"
	data-shanelle-detail-section="reviews"
	aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"
>
	<header class="product-reviews__header">
		<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="product-reviews__title product-detail__section-title">
			<?php
			if ( $review_count > 0 ) {
				printf(
					/* translators: %d: review count */
					esc_html__( 'Reseñas de clientes (%d+)', 'shanelle' ),
					$review_count
				);
			} else {
				esc_html_e( 'Reseñas de clientes', 'shanelle' );
			}
			?>
		</h2>

		<?php if ( $can_review ) : ?>
			<a class="product-reviews__view-all" href="#review_form">
				<?php esc_html_e( 'Escribir reseña', 'shanelle' ); ?>
			</a>
		<?php elseif ( $has_more ) : ?>
			<a class="product-reviews__view-all" href="#reviews">
				<?php esc_html_e( 'Ver reseñas', 'shanelle' ); ?>
			</a>
		<?php endif; ?>
	</header>

	<?php if ( $has_reviews ) : ?>
		<div class="product-reviews__summary<?php echo empty( $fit_rows ) ? ' product-reviews__summary--score-only' : ''; ?>">
			<div class="product-reviews__score">
				<p class="product-reviews__average">
					<span class="product-reviews__average-value"><?php echo esc_html( (string) ( $summary['display'] ?? '0.00' ) ); ?></span>
					<span class="product-reviews__average-max">/5</span>
				</p>

				<?php if ( ! empty( $summary['stars'] ) ) : ?>
					<div class="product-reviews__stars">
						<?php echo wp_kses_post( (string) $summary['stars'] ); ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $fit_rows ) ) : ?>
				<div class="product-reviews__fit">
					<p class="product-reviews__fit-title"><?php esc_html_e( 'Ajuste general', 'shanelle' ); ?></p>
					<ul class="product-reviews__fit-list" role="list">
						<?php foreach ( $fit_rows as $row ) : ?>
							<?php
							$percent = max( 0, min( 100, (float) ( $row['percent'] ?? 0 ) ) );
							?>
							<li class="product-reviews__fit-item" data-fit-index="<?php echo esc_attr( (string) ( $row['index'] ?? 0 ) ); ?>">
								<span class="product-reviews__fit-label"><?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?></span>
								<span class="product-reviews__fit-bar" aria-hidden="true">
									<span
										class="product-reviews__fit-fill"
										style="<?php echo esc_attr( '--fit-percent: ' . $percent . '%;' ); ?>"
									></span>
								</span>
								<span class="product-reviews__fit-percent"><?php echo esc_html( (string) (int) round( $percent ) ); ?>%</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $tags ) ) : ?>
		<ul class="product-reviews__tags" role="list">
			<?php foreach ( $tags as $tag ) : ?>
				<li class="product-reviews__tag-item" data-tag-index="<?php echo esc_attr( (string) ( $tag['index'] ?? 0 ) ); ?>">
					<button type="button" class="product-reviews__tag" disabled aria-disabled="true">
						<?php echo esc_html( (string) ( $tag['label'] ?? '' ) ); ?>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( ! empty( $reviews ) ) : ?>
		<ul class="product-reviews__list" id="reviews" role="list">
			<?php foreach ( $reviews as $review_index => $review ) : ?>
				<?php
				if ( ! $review instanceof WP_Comment ) {
					continue;
				}

				$rating = (int) get_comment_meta( $review->comment_ID, 'rating', true );
				?>
				<li
					class="product-reviews__item"
					data-review-index="<?php echo esc_attr( (string) $review_index ); ?>"
				>
					<div class="product-reviews__item-head">
						<div class="product-reviews__item-meta">
							<?php if ( $rating > 0 ) : ?>
								<div class="product-reviews__item-stars">
									<?php echo wp_kses_post( wc_get_rating_html( $rating ) ); ?>
								</div>
							<?php endif; ?>
							<p class="product-reviews__item-author"><?php echo esc_html( (string) $review->comment_author ); ?></p>
							<time class="product-reviews__item-date" datetime="<?php echo esc_attr( get_comment_date( 'c', $review ) ); ?>">
								<?php echo esc_html( get_comment_date( '', $review ) ); ?>
							</time>
						</div>
					</div>

					<div class="product-reviews__item-body">
						<?php echo wp_kses_post( wpautop( (string) $review->comment_content ) ); ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<div class="product-reviews__empty">
			<p class="product-reviews__empty-text text-body-sm text-muted">
				<?php esc_html_e( 'Sé la primera en reseñar este producto.', 'shanelle' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $can_review && isset( $product ) && $product instanceof WC_Product ) : ?>
		<?php ProductDetail::render_review_form( $product ); ?>
	<?php endif; ?>
</section>
