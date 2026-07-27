<?php
/**
 * Page hero component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\PageHero;

defined( 'ABSPATH' ) || exit;

$args         = PageHero::get_args();
$title        = (string) ( $args['title'] ?? '' );
$subtitle     = (string) ( $args['subtitle'] ?? '' );
$heading_tag  = PageHero::get_heading_tag();
$heading_id   = PageHero::get_heading_id();
$has_subtitle = '' !== $subtitle;
?>
<section
	id="<?php echo esc_attr( PageHero::get_root_id() ); ?>"
	class="<?php echo esc_attr( implode( ' ', PageHero::get_root_classes() ) ); ?>"
	data-shanelle-page-hero
	aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"
>
	<?php PageHero::render_background(); ?>

	<div class="container page-hero__inner">
		<?php PageHero::render_breadcrumb(); ?>

		<div class="page-hero__copy">
			<<?php echo tag_escape( $heading_tag ); ?>
				id="<?php echo esc_attr( $heading_id ); ?>"
				class="page-hero__title text-h1"
			>
				<?php echo esc_html( $title ); ?>
			</<?php echo tag_escape( $heading_tag ); ?>>

			<?php if ( $has_subtitle ) : ?>
				<p class="page-hero__subtitle text-body text-secondary">
					<?php echo esc_html( $subtitle ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</section>
