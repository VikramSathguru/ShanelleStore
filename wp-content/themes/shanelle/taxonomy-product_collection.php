<?php
/**
 * Collection taxonomy archive template.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

\Shanelle\Components\CollectionPage::render();

get_footer();
