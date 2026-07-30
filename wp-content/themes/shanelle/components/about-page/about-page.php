<?php
/**
 * About page component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\AboutPage;

defined( 'ABSPATH' ) || exit;
?>
<main
	id="primary"
	class="site-main about-page"
	data-shanelle-about-page
	data-about-state="<?php echo esc_attr( AboutPage::get_state_json() ); ?>"
	aria-labelledby="<?php echo esc_attr( AboutPage::get_heading_id() ); ?>"
>
	<h1 id="<?php echo esc_attr( AboutPage::get_heading_id() ); ?>" class="sr-only">
		<?php echo esc_html( AboutPage::get_page_title() ); ?>
	</h1>

	<?php AboutPage::render_story(); ?>
	<?php AboutPage::render_mission_values(); ?>
	<?php AboutPage::render_why_choose(); ?>
	<?php AboutPage::render_cta(); ?>
</main>
