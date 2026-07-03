/**
 * The Template for displaying product archives, including the main shop page.
 *
 * This template composes the ShopArchive component.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header();

\Shanelle\Components\ShopArchive::render();

get_footer();
