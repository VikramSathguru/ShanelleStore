# Routes

How URLs, rewrite rules, AJAX, and REST endpoints map onto Shanelle.

---

## WordPress / WooCommerce pages

Exact slugs are configured in **WooCommerce → Settings → Advanced → Page setup** and WordPress **Settings → Reading**. Typical defaults:

| Page | Typical path | Theme entry |
|------|--------------|-------------|
| Front page | `/` | `front-page.php` → Homepage |
| Shop | `/shop/` | `woocommerce/archive-product.php` |
| Product | `/product/{slug}/` | `woocommerce/single-product.php` |
| Cart | `/cart/` | `woocommerce/cart/cart.php` |
| Checkout | `/checkout/` | `woocommerce/checkout/form-checkout.php` |
| My Account | `/my-account/` (+ endpoints) | `woocommerce/myaccount/*` |
| Search results | `/?s=` or pretty search | `search.php` → SearchPage |
| Collections index | Page using template `page-templates/collections.php` | CollectionsPage |
| Collection archive | `/collection/{slug}/` | `product_collection` taxonomy templates |
| Category | `/product-category/{slug}/` | Shop archive path |

My Account endpoints (WC): `orders`, `view-order`, `downloads`, `edit-account`, `edit-address`, `payment-methods`, `customer-logout`, etc. — themed via overrides.

---

## Rewrite rules

| Rule source | Pattern | Notes |
|-------------|---------|-------|
| Taxonomy `product_collection` | `collection` slug, hierarchical | Registered in `inc/catalog/Collections.php` |
| WooCommerce product / cat | WC defaults | Theme does not custom-register product rewrites |
| Theme `add_rewrite_rule` | — | **Not implemented yet** (no custom rewrite API usage found) |

Flush permalinks after theme switch / taxonomy registration (Catalog seeder queues on `after_switch_theme`).

---

## Admin AJAX (`admin-ajax.php`)

| Action | Auth | Handler | Nonce |
|--------|------|---------|-------|
| `shanelle_load_product_grid` | `wp_ajax_` + `nopriv_` | `ProductGrid::handle_ajax` | `check_ajax_referer( 'shanelle_load_product_grid', 'nonce' )` |
| `shanelle_search_suggest` | `wp_ajax_` + `nopriv_` | `SearchController::handle_ajax` | `check_ajax_referer( 'shanelle_search', 'nonce' )` |

Localized URLs via `admin_url( 'admin-ajax.php' )` on product grid / search scripts.

---

## WooCommerce AJAX (`?wc-ajax=`)

Endpoint base from `\WC_AJAX::get_endpoint( '%%endpoint%%' )`.

| Endpoint | Handler | Purpose |
|----------|---------|---------|
| `add_to_cart` | WooCommerce core | Used by ProductPurchase / cards |
| `shanelle_mini_cart_update` | `MiniCart::ajax_update_item` | Update/remove line qty |
| `shanelle_mini_cart_get` | `MiniCart::ajax_get_cart` | Refresh drawer payload |
| `shanelle_cart_page_get` | `CartPage::ajax_get_page` | Refresh cart page fragments |
| `update_order_review` | WooCommerce core | Checkout updates (theme adds fragments) |

**Note:** Mini cart / cart page custom WC AJAX handlers do not perform theme-level nonce checks today — see [SECURITY.md](./SECURITY.md).

---

## REST API

Namespace: `shanelle/v1`  
Base: `/wp-json/shanelle/v1/...`

| Route | Method | Permission | Handler |
|-------|--------|------------|---------|
| `/product-grid` | GET | `__return_true` (public) | `ProductGrid::handle_rest` |
| `/search` | GET | `__return_true` (public) | `SearchController::handle_rest` |

Query args are sanitized (`sanitize_text_field`, `absint`, etc.). Intended for future PWA hydration.

**Not implemented yet:** Authenticated customer REST for wishlist, orders App API, or Store API wrappers in the theme.

WooCommerce core REST (`/wp-json/wc/v3/…`) remains available when API keys exist — not customized by Shanelle theme code.

---

## Client storage (not HTTP routes)

| Key | Location | Purpose |
|-----|----------|---------|
| `shanelle_wishlist` | `localStorage` | PDP favourites (ProductPurchase) |
| `shanelle_recent_searches` | (SearchOverlay constant) | Recent search UX |

---

## Related docs

- [DATA_FLOW.md](./DATA_FLOW.md)  
- [CUSTOM_HOOKS.md](./CUSTOM_HOOKS.md)  
- [SECURITY.md](./SECURITY.md)  
