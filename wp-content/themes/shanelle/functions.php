<?php
/**
 * Shanelle theme bootstrap.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'SHANELLE_VERSION', '1.0.0' );
define( 'SHANELLE_DIR', get_template_directory() );
define( 'SHANELLE_URI', get_template_directory_uri() );

require_once SHANELLE_DIR . '/inc/setup.php';
require_once SHANELLE_DIR . '/inc/assets.php';
require_once SHANELLE_DIR . '/inc/components.php';
require_once SHANELLE_DIR . '/inc/woocommerce.php';
require_once SHANELLE_DIR . '/inc/catalog/Catalog.php';

Shanelle\Catalog\Catalog::boot();
