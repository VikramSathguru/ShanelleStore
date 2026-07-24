<?php
/**
 * My Account view order template override.
 *
 * @package Shanelle
 * @version 10.6.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

\Shanelle\Components\MyAccountPage::render_view_order( $order_id ?? 0 );
