<?php
/**
 * Homepage page template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\Homepage;

defined( 'ABSPATH' ) || exit;
?>
<div
	class="homepage"
	id="<?php echo esc_attr( Homepage::get_root_id() ); ?>"
	data-shanelle-homepage
	data-homepage-sections="<?php echo esc_attr( Homepage::get_sections_json() ); ?>"
>
	<?php Homepage::render_hero(); ?>
	<?php Homepage::render_category_icons(); ?>
	<?php Homepage::render_featured_collections(); ?>
	<?php Homepage::render_for_you_grid(); ?>
</div>
