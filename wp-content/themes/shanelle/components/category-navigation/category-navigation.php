<?php
/**
 * Category navigation component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\CategoryNavigation;

defined( 'ABSPATH' ) || exit;
?>
<section
	class="<?php echo esc_attr( implode( ' ', CategoryNavigation::get_section_classes() ) ); ?> section"
	id="<?php echo esc_attr( CategoryNavigation::get_root_id() ); ?>"
	data-shanelle-category-navigation
	data-category-json="<?php echo esc_attr( CategoryNavigation::get_categories_json() ); ?>"
	data-settings-json="<?php echo esc_attr( CategoryNavigation::get_settings_json() ); ?>"
	aria-labelledby="<?php echo esc_attr( CategoryNavigation::get_aria_labelledby() ); ?>"
>
	<span class="screen-reader-text" id="<?php echo esc_attr( CategoryNavigation::get_root_id() ); ?>-label">
		<?php esc_html_e( 'Browse product categories', 'shanelle' ); ?>
	</span>

	<div class="container category-navigation__inner">
		<?php CategoryNavigation::render_header(); ?>

		<div class="category-navigation__viewport" data-shanelle-category-viewport>
			<ul class="category-navigation__list" data-shanelle-category-list role="list">
				<?php CategoryNavigation::render_categories(); ?>
			</ul>
		</div>
	</div>
</section>
