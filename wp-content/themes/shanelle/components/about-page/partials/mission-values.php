<?php
/**
 * About page — Mission & Values section.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\AboutPage;

defined( 'ABSPATH' ) || exit;

$mission    = AboutPage::get_section( 'mission' );
$paragraphs = is_array( $mission['paragraphs'] ?? null ) ? $mission['paragraphs'] : array();
$values     = is_array( $mission['values'] ?? null ) ? $mission['values'] : array();
$eyebrow    = (string) ( $mission['eyebrow'] ?? '' );
$heading    = (string) ( $mission['heading'] ?? '' );
$heading_id = AboutPage::get_root_id() . '-mission-heading';
?>
<section
	class="about-page__mission"
	<?php echo '' !== $heading ? 'aria-labelledby="' . esc_attr( $heading_id ) . '"' : ''; ?>
	data-about-reveal
>
	<div class="container about-page__mission-inner">
		<header class="about-page__mission-header">
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
				<div class="about-page__prose about-page__prose--inverse">
					<?php foreach ( $paragraphs as $paragraph ) : ?>
						<p><?php echo esc_html( (string) $paragraph ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</header>

		<?php if ( ! empty( $values ) ) : ?>
			<ul class="about-page__values-grid" role="list">
				<?php foreach ( $values as $value ) : ?>
					<li class="about-page__value">
						<?php if ( ! empty( $value['image']['id'] ) ) : ?>
							<span class="about-page__value-icon" aria-hidden="true">
								<?php AboutPage::render_image( (int) $value['image']['id'], 'thumbnail' ); ?>
							</span>
						<?php endif; ?>

						<?php if ( '' !== (string) ( $value['title'] ?? '' ) ) : ?>
							<h3 class="about-page__value-title"><?php echo esc_html( (string) $value['title'] ); ?></h3>
						<?php endif; ?>

						<?php if ( '' !== (string) ( $value['text'] ?? '' ) ) : ?>
							<p class="about-page__value-text"><?php echo esc_html( (string) $value['text'] ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
