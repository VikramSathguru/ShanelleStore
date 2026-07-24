<?php
/**
 * Lost password confirmation template override.
 *
 * @package Shanelle
 * @version 3.9.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

\Shanelle\Components\MyAccountPage::render_guest( 'lost-password-confirmation' );
