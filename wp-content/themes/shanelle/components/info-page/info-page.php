<?php
/**
 * Informational page component template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\InfoPage;

defined( 'ABSPATH' ) || exit;
?>
<main
	id="primary"
	class="<?php echo esc_attr( implode( ' ', InfoPage::get_root_classes() ) ); ?>"
	data-shanelle-info-page
	data-info-page-type="<?php echo esc_attr( InfoPage::get_type() ); ?>"
	aria-labelledby="<?php echo esc_attr( InfoPage::get_heading_id() ); ?>"
>
	<?php InfoPage::render_hero(); ?>
	<?php InfoPage::render_body(); ?>
</main>
