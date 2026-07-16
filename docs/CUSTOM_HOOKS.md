# Custom Hooks

Theme-owned actions and filters (prefix `shanelle_`).  
WooCommerce core hooks that the theme adds/removes are summarized at the end; full WC inventories are not duplicated.

---

## Actions

| Hook | Defined / fired | Used by | Purpose |
|------|-----------------|---------|---------|
| `shanelle_shop_archive_filters_after` | Fired in `ShopArchive::render_filters_content()` after CatalogFilters | Optional extensions | Append UI below CatalogFilters (must not replace filters) |

CatalogFilters is rendered directly by `ShopArchive` (desktop sidebar + mobile sheet). Widget sidebars are not used for PLP filters.

Client-side analogues are CustomEvents — see [EVENTS.md](./EVENTS.md).

---

## Filters

### Pricing & cards

| Filter | Defined in | Purpose |
|--------|------------|---------|
| `shanelle_product_price_data` | `ProductPrice.php` | Mutate normalized price payload |
| `shanelle_product_card_new_days` | `ProductCard.php` | Days window for “new” badge (default 30) |
| `shanelle_product_card_attribute_limit` | `ProductCard.php` | Max attributes in card summary |

### Catalog & collections

| Filter | Defined in | Purpose |
|--------|------------|---------|
| `shanelle_catalog_filter_groups` | `CatalogFilters.php` | Filter group definitions |
| `shanelle_catalog_filter_attribute_map` | `CatalogFilters.php` | Role → attribute taxonomy map |
| `shanelle_catalog_filter_visible_options` | `CatalogFilters.php` | Visible option count before “show more” |
| `shanelle_catalog_collections` | `catalog/Queries.php` | Collection list results |
| `shanelle_catalog_collection_groups` | `catalog/Queries.php` | Grouped collections |
| `shanelle_collection_term_meta_schema` | `catalog/Admin.php` | Term meta field schema |
| `shanelle_collection_page_grid_args` | `CollectionPage.php` | Grid args on collection archive |
| `shanelle_collections_page_state` | `CollectionsPage.php` | Page state payload |
| `shanelle_collections_page_settings` | `CollectionsPage.php` | Customizer-derived settings |
| `shanelle_shop_archive_grid_args` | `ShopArchive.php` (and filtered by Search/Collection pages) | Grid configuration for archives |

### Product grid / search

| Filter | Defined in | Purpose |
|--------|------------|---------|
| `shanelle_product_grid_query_vars` | `ProductGrid.php` | Sanitize/adjust AJAX-replayable query vars |
| `shanelle_search_response` | `SearchController.php` | Live search JSON payload |
| `shanelle_search_results_args` | `SearchResults.php` | Results render args |
| `shanelle_search_product_item` | `SearchResults.php` | Per-product suggestion item |
| `shanelle_search_product_query_args` | `SearchController.php` | Product search `WP_Query` args |
| `shanelle_search_page_grid_args` | `SearchPage.php` | Full search page grid |
| `shanelle_search_overlay_settings` | `SearchOverlay.php` | Overlay settings |
| `shanelle_search_overlay_state` | `SearchOverlay.php` | Overlay state |

### Homepage & navigation

| Filter | Defined in | Purpose |
|--------|------------|---------|
| `shanelle_homepage_promo_tiles` | `Homepage.php` | Hero promo side tiles |
| `shanelle_homepage_category_icon_items` | `Homepage.php` | Category icon grid items |
| `shanelle_homepage_featured_collections` | `Homepage.php` | Featured collection cards |
| `shanelle_homepage_sections` | `Homepage.php` | Configurable product sections |
| `shanelle_homepage_section_query_vars` | `Homepage.php` | Per-section query |
| `shanelle_hero_banner_slides` | `HeroBanner.php` | Hero slides |
| `shanelle_category_navigation_settings` | `CategoryNavigation.php` | Nav settings |
| `shanelle_category_navigation_categories` | `CategoryNavigation.php` | Category list |
| `shanelle_category_navbar_categories` | `CategoryNavbar.php` | Header navbar categories |

### PDP

| Filter | Defined in | Purpose |
|--------|------------|---------|
| `shanelle_product_information_sections` | `ProductInformation.php` | Accordion section list |
| `shanelle_product_information_size_guide` | via `build_filtered_section` | Size guide HTML |
| `shanelle_product_information_care_instructions` | via `build_filtered_section` | Care HTML |
| `shanelle_product_information_shipping` | via filter + default in `woocommerce.php` | Shipping HTML |
| `shanelle_product_information_returns` | via filter + default in `woocommerce.php` | Returns HTML |
| `shanelle_product_review_filter_tags` | `ProductDetail.php` | Optional review chip labels (empty by default; omit UI when empty) |
| `shanelle_product_review_fit_breakdown` | `ProductDetail.php` | Optional fit bar rows (empty by default; omit UI when empty) |
| `shanelle_product_summary_brand` | `ProductSummary.php` | Brand label string (omit UI when empty) |
| `shanelle_product_summary_highlights` | `ProductSummary.php` | Highlight bullet strings (omit UI when empty) |
| `shanelle_product_shipping_estimate` | `ProductPurchase.php` | Shipping estimate copy |
| `shanelle_product_delivery_estimate` | `ProductPurchase.php` | Delivery estimate copy |
| `shanelle_variation_swatch_color` | `ProductVariations.php` | Swatch hex/color |
| `shanelle_related_products_*` | `ProductRelated.php` | Scores, IDs, limits, weights, taxonomies, fallbacks, candidate query |
| `shanelle_customers_also_viewed_title` | `ProductRelated.php` | Section title string filter path |

### Mini cart / cart / checkout / account

| Filter | Defined in | Purpose |
|--------|------------|---------|
| `shanelle_mini_cart_state` | `MiniCart.php` | Full drawer state |
| `shanelle_mini_cart_item` | `MiniCart.php` | Per line item |
| `shanelle_mini_cart_ajax_response` | `MiniCart.php` | AJAX JSON |
| `shanelle_cart_page_state` | `CartPage.php` | Cart page state |
| `shanelle_cart_page_settings` | `CartPage.php` | Customizer settings |
| `shanelle_cart_page_totals_rows` | `CartPage.php` | Order summary rows |
| `shanelle_cart_page_cross_sell_query` | `CartPage.php` | Cross-sell query |
| `shanelle_cart_page_ajax_response` | `CartPage.php` | AJAX JSON |
| `shanelle_cart_page_shipping_calculator_button_text` | `CartPage.php` | Calc toggle label |
| `shanelle_checkout_page_state` | `CheckoutPage.php` | Checkout state |
| `shanelle_checkout_page_settings` | `CheckoutPage.php` | Settings |
| `shanelle_checkout_page_fragments` | `CheckoutPage.php` | Updated checkout fragments |
| `shanelle_checkout_page_totals_rows` | `CheckoutPage.php` | Totals rows |
| `shanelle_my_account_page_mobile_bottom_nav_items` | `MyAccountPage.php` | Mobile nav items |

---

## Theme callbacks on WooCommerce / WP hooks

### Actions added (examples)

| Hook | Callback | File |
|------|----------|------|
| `after_setup_theme` | `shanelle_setup`, image sizes, menus | `setup.php`, components |
| `woocommerce_init` | `shanelle_register_woocommerce_hooks` | `woocommerce.php` |
| `woocommerce_before_main_content` | `shanelle_before_main_content` | `woocommerce.php` (often removed on specialized pages) |
| `woocommerce_before_shop_loop_item` | `shanelle_product_card_start` | `woocommerce.php` |
| `wp_footer` | MiniCart / SearchOverlay render | respective classes |
| `wp_ajax(_nopriv)_shanelle_*` | ProductGrid / SearchController | see ROUTES |
| `wc_ajax_shanelle_*` | MiniCart / CartPage | see ROUTES |
| `rest_api_init` | REST routes | ProductGrid, SearchController |

### Filters added on WC options / catalogs (examples)

| Hook | Purpose |
|------|---------|
| `loop_shop_per_page` / `loop_shop_columns` | 24 / 4 |
| `woocommerce_add_to_cart_fragments` | Cart count + mini cart |
| `woocommerce_catalog_orderby` | Friendly sort labels |
| `option_woocommerce_enable_myaccount_registration` | Force registration on |
| `woocommerce_available_variation` | Extra Shanelle fields |
| `woocommerce_update_order_review_fragments` | Checkout fragments |
| `woocommerce_order_button_html` | Place order button markup |

---

## How to extend safely

1. Prefer `add_filter( 'shanelle_…' )` from a small plugin or `inc/integrations` file.  
2. Do not edit component markup for integration-specific copy when a filter exists.  
3. For analytics, prefer listening to DOM events in [EVENTS.md](./EVENTS.md) rather than forking purchase JS.

---

## Related docs

- [ROUTES.md](./ROUTES.md)  
- [EVENTS.md](./EVENTS.md)  
- [FUTURE_INTEGRATIONS.md](./FUTURE_INTEGRATIONS.md)  
