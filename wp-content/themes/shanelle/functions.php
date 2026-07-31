<?php
/**
 * Shanelle theme bootstrap.
 *
 * @package Shanelle
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'SHANELLE_VERSION', '1.0.19' );
define( 'SHANELLE_DIR', get_template_directory() );
define( 'SHANELLE_URI', get_template_directory_uri() );

require_once SHANELLE_DIR . '/inc/autoload.php';
require_once SHANELLE_DIR . '/inc/setup.php';
require_once SHANELLE_DIR . '/inc/assets.php';
require_once SHANELLE_DIR . '/inc/components.php';
require_once SHANELLE_DIR . '/inc/woocommerce.php';

Shanelle\Catalog\Catalog::boot();
Shanelle\Components\ProductCard::boot();
Shanelle\Components\ProductGrid::boot();
Shanelle\Components\ShopArchive::boot();
Shanelle\Components\CatalogFilters::boot();
Shanelle\Components\ProductGallery::boot();
Shanelle\Components\ProductSummary::boot();
Shanelle\Components\ProductVariations::boot();
Shanelle\Components\SizeGuide::boot();
Shanelle\Components\ProductPurchase::boot();
Shanelle\Components\ProductDetail::boot();
Shanelle\Components\ProductInformation::boot();
Shanelle\Components\ProductRelated::boot();
Shanelle\Components\MiniCart::boot();
Shanelle\Components\HeroBanner::boot();
Shanelle\Components\CategoryNavigation::boot();
Shanelle\Components\Homepage::boot();
Shanelle\Components\CartPage::boot();
Shanelle\Components\CheckoutPage::boot();
Shanelle\Components\MyAccountPage::boot();
Shanelle\Components\SearchPage::boot();
Shanelle\Components\SearchController::boot();
Shanelle\Components\SearchOverlay::boot();
Shanelle\Components\CollectionCard::boot();
Shanelle\Components\CollectionsPage::boot();
Shanelle\Components\CollectionPage::boot();
Shanelle\Components\CategoryNavbar::boot();
Shanelle\Components\AboutPage::boot();
Shanelle\Components\PageHero::boot();
Shanelle\Components\InfoPage::boot();
Shanelle\Components\SiteHeader::boot();
Shanelle\Components\FooterBrand::boot();
Shanelle\Components\FooterLinks::boot();
Shanelle\Components\FooterCustomerService::boot();
Shanelle\Components\FooterPolicies::boot();
Shanelle\Components\Footer::boot();
Shanelle\Integrations\Integrations::boot();
