<?php
/**
 * About page — Our Story section.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\AboutPage;

defined( 'ABSPATH' ) || exit;

$story      = AboutPage::get_section( 'story' );
$has_media  = ! empty( $story['has_media'] );
$media_side = (string) ( $story['media_side'] ?? 'left' );
$paragraphs = is_array( $story['paragraphs'] ?? null ) ? $story['paragraphs'] : array();
$eyebrow    = (string) ( $story['eyebrow'] ?? '' );
$heading    = (string) ( $story['heading'] ?? '' );
$heading_id = AboutPage::get_root_id() . '-story-heading';
$classes    = array( 'about-page__story' );

if ( $has_media ) {
	$classes[] = 'about-page__story--has-media';
	$classes[] = 'about-page__story--media-' . ( 'right' === $media_side ? 'right' : 'left' );
}
?>
<section
	class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	<?php echo '' !== $heading ? 'aria-labelledby="' . esc_attr( $heading_id ) . '"' : ''; ?>
	data-about-reveal
>
	<div class="container about-page__story-inner">
		<?php if ( $has_media ) : ?>
			<div class="about-page__story-media">
				<?php AboutPage::render_picture( $story['desktop'] ?? array(), $story['mobile'] ?? array(), false, 'about-page__image--cover' ); ?>
			</div>
		<?php endif; ?>

		<div class="about-page__story-copy">
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="about-page__eyebrow text-overline"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $heading ) : ?>
				<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="about-page__section-heading">
					<?php echo esc_html( $heading ); ?>
				</h2>
			<?php endif; ?>

			<hr class="about-page__rule" aria-hidden="true" />

			<?php if ( ! empty( $paragraphs ) ) : ?>
				<div class="about-page__prose">
					<?php foreach ( $paragraphs as $paragraph ) : ?>
						<p><?php echo esc_html( (string) $paragraph ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
