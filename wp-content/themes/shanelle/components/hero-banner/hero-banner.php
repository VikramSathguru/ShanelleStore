<?php
/**
 * Hero banner component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\HeroBanner;

defined( 'ABSPATH' ) || exit;
?>
<section
	class="hero-banner"
	id="<?php echo esc_attr( HeroBanner::get_root_id() ); ?>"
	data-shanelle-hero-banner
	data-hero-slides="<?php echo esc_attr( HeroBanner::get_slides_json() ); ?>"
	aria-labelledby="<?php echo esc_attr( HeroBanner::get_aria_labelledby() ); ?>"
>
	<span class="screen-reader-text" id="<?php echo esc_attr( HeroBanner::get_root_id() ); ?>-label">
		<?php esc_html_e( 'Banner principal de inicio', 'shanelle' ); ?>
	</span>

	<div class="hero-banner__viewport" data-shanelle-hero-viewport>
		<div
			class="hero-banner__slides"
			data-shanelle-hero-slides
			role="group"
			aria-roledescription="<?php esc_attr_e( 'carrusel', 'shanelle' ); ?>"
		>
			<?php HeroBanner::render_slides(); ?>
		</div>
	</div>
</section>
