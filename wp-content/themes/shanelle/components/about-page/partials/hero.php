<?php
/**
 * About page — Hero section.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\AboutPage;

defined( 'ABSPATH' ) || exit;

$hero       = AboutPage::get_section( 'hero' );
$has_media  = ! empty( $hero['has_media'] );
$cta        = is_array( $hero['cta'] ?? null ) ? $hero['cta'] : array();
$headline   = (string) ( $hero['headline'] ?? '' );
$eyebrow    = (string) ( $hero['eyebrow'] ?? '' );
$subhead    = (string) ( $hero['subheadline'] ?? '' );
$tagline    = (string) ( $hero['tagline'] ?? '' );
$section_id = AboutPage::get_heading_id();
?>
<section
	class="about-page__hero<?php echo $has_media ? ' about-page__hero--has-media' : ' about-page__hero--plain'; ?>"
	aria-labelledby="<?php echo esc_attr( $section_id ); ?>"
	data-about-reveal
>
	<div class="about-page__hero-frame">
		<?php if ( $has_media ) : ?>
			<div class="about-page__hero-media" aria-hidden="false">
				<?php AboutPage::render_picture( $hero['desktop'] ?? array(), $hero['mobile'] ?? array(), true, 'about-page__image--cover' ); ?>
				<span class="about-page__hero-veil" aria-hidden="true"></span>
			</div>
		<?php endif; ?>

		<div class="container about-page__hero-inner">
			<div class="about-page__hero-copy">
				<?php if ( '' !== $eyebrow ) : ?>
					<p class="about-page__eyebrow text-overline text-brand"><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $headline ) : ?>
					<h1 id="<?php echo esc_attr( $section_id ); ?>" class="about-page__hero-headline text-display">
						<?php echo esc_html( $headline ); ?>
					</h1>
				<?php else : ?>
					<h1 id="<?php echo esc_attr( $section_id ); ?>" class="sr-only">
						<?php echo esc_html( AboutPage::get_page_title() ); ?>
					</h1>
				<?php endif; ?>

				<?php if ( '' !== $subhead ) : ?>
					<p class="about-page__hero-subheadline text-body text-secondary"><?php echo esc_html( $subhead ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $tagline ) : ?>
					<p class="about-page__hero-tagline text-h5 text-brand"><?php echo esc_html( $tagline ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $cta['visible'] ) ) : ?>
					<div class="about-page__hero-actions">
						<a class="btn btn--primary btn--lg" href="<?php echo esc_url( (string) $cta['url'] ); ?>">
							<?php echo esc_html( (string) $cta['text'] ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
