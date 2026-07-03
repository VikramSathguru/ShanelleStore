<?php
/**
 * Collection taxonomy archive template override.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

\Shanelle\Components\CollectionPage::render();

get_footer();
