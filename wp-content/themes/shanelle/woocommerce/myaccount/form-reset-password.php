<?php
/**
 * Reset password form template override.
 *
 * @package Shanelle
 * @version 9.2.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

\Shanelle\Components\MyAccountPage::render_guest(
	'reset-password',
	array(
		'key'   => $key ?? '',
		'login' => $login ?? '',
	)
);
