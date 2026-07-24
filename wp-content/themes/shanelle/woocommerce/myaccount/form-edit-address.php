<?php
/**
 * My Account edit address form template override.
 *
 * @package Shanelle
 * @version 9.3.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Shanelle\Components\MyAccountPage;

if ( empty( $load_address ) ) {
	MyAccountPage::render_addresses();
	return;
}

MyAccountPage::render_edit_address_form( (string) $load_address, is_array( $address ) ? $address : array() );
