<?php
/**
 * About page — Call to Action section.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\AboutPage;

defined( 'ABSPATH' ) || exit;

$cta        = AboutPage::get_section( 'cta' );
$paragraphs = is_array( $cta['paragraphs'] ?? null ) ? $cta['paragraphs'] : array();
$button     = is_array( $cta['button'] ?? null ) ? $cta['button'] : array();
$eyebrow    = (string) ( $cta['eyebrow'] ?? '' );
$heading    = (string) ( $cta['heading'] ?? '' );
$heading_id = AboutPage::get_root_id() . '-cta-heading';
?>
<section
	class="about-page__cta"
	<?php echo '' !== $heading ? 'aria-labelledby="' . esc_attr( $heading_id ) . '"' : ''; ?>
	data-about-reveal
>
	<div class="container about-page__cta-inner">
		<div class="about-page__cta-copy">
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

		<?php if ( ! empty( $button['visible'] ) ) : ?>
			<div class="about-page__cta-actions">
				<a class="btn btn--primary btn--lg" href="<?php echo esc_url( (string) $button['url'] ); ?>">
					<?php echo esc_html( (string) $button['text'] ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</section>
