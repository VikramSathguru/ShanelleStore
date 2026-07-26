<?php
/**
 * Footer brand column markup.
 *
 * @package Shanelle
 */

declare(strict_types=1);

use Shanelle\Components\FooterBrand;

defined( 'ABSPATH' ) || exit;
?>
<section class="footer-brand" data-shanelle-footer-brand aria-label="<?php esc_attr_e( 'Marca', 'shanelle' ); ?>">
	<?php FooterBrand::render_logo(); ?>
	<?php FooterBrand::render_description(); ?>
	<?php FooterBrand::render_social(); ?>
</section>
