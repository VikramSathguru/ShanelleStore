<?php
/**
 * Cart page template override.
 *
 * @package Shanelle
 * @version 10.8.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );

\Shanelle\Components\CartPage::render();

do_action( 'woocommerce_after_cart' );
