<?php
/**
 * About page — Why Choose section.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\AboutPage;

defined( 'ABSPATH' ) || exit;

$why        = AboutPage::get_section( 'why_choose' );
$items      = is_array( $why['items'] ?? null ) ? $why['items'] : array();
$eyebrow    = (string) ( $why['eyebrow'] ?? '' );
$heading    = (string) ( $why['heading'] ?? '' );
$intro      = (string) ( $why['intro'] ?? '' );
$heading_id = AboutPage::get_root_id() . '-why-heading';
?>
<section
	class="about-page__why"
	<?php echo '' !== $heading ? 'aria-labelledby="' . esc_attr( $heading_id ) . '"' : ''; ?>
	data-about-reveal
>
	<div class="container about-page__why-inner">
		<?php if ( '' !== $eyebrow || '' !== $heading || '' !== $intro ) : ?>
			<header class="about-page__why-header">
				<?php if ( '' !== $eyebrow ) : ?>
					<p class="about-page__eyebrow text-overline text-brand"><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $heading ) : ?>
					<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="about-page__section-heading text-h2">
						<?php echo esc_html( $heading ); ?>
					</h2>
				<?php endif; ?>

				<?php if ( '' !== $intro ) : ?>
					<p class="about-page__why-intro text-body text-secondary"><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<ul class="about-page__why-grid" role="list">
			<?php foreach ( $items as $index => $item ) : ?>
				<li class="about-page__why-item<?php echo 0 === ( (int) $index % 2 ) ? ' about-page__why-item--accent' : ''; ?>">
					<?php if ( ! empty( $item['image']['id'] ) ) : ?>
						<span class="about-page__why-icon" aria-hidden="true">
							<?php AboutPage::render_image( (int) $item['image']['id'], 'thumbnail' ); ?>
						</span>
					<?php endif; ?>

					<div class="about-page__why-copy">
						<?php if ( '' !== (string) ( $item['title'] ?? '' ) ) : ?>
							<h3 class="about-page__why-title text-h5"><?php echo esc_html( (string) $item['title'] ); ?></h3>
						<?php endif; ?>

						<?php if ( '' !== (string) ( $item['text'] ?? '' ) ) : ?>
							<p class="about-page__why-text text-body-sm text-secondary"><?php echo esc_html( (string) $item['text'] ); ?></p>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
