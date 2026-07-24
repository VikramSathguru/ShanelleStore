<?php
/**
 * My Account page template override.
 *
 * @package Shanelle
 * @version 3.5.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

\Shanelle\Components\MyAccountPage::render(
	array(
		'current_user' => $current_user ?? null,
		'order_count'  => $order_count ?? 15,
	)
);
