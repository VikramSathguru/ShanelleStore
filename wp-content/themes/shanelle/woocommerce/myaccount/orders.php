<?php
/**
 * My Account orders template override.
 *
 * @package Shanelle
 * @version 9.5.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

\Shanelle\Components\MyAccountPage::render_orders( $current_page ?? 1 );
