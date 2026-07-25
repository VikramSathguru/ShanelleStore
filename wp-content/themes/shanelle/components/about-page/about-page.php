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
	<?php AboutPage::render_title_band(); ?>
	<?php AboutPage::render_hero(); ?>
	<?php AboutPage::render_mission(); ?>
	<?php AboutPage::render_features(); ?>
</main>
