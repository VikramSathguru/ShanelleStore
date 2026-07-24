# Data Flow

End-to-end flows for how store data moves from WooCommerce through the Shanelle theme into the browser.

---

## Conceptual chain

```text
Product (WC)
  ↓
WooCommerce APIs / queries
  ↓
Theme templates & composers
  ↓
Component markup + localized JS
  ↓
Browser (render + ES modules + AJAX/REST)
```

```mermaid
graph TD
  Browser --> WordPress
  WordPress --> WooCommerce
  WooCommerce --> Theme
  Theme --> Browser
```

---

## Request flow (typical page)

1. HTTP request hits WordPress permalink / WC page endpoint.  
2. WP loads query (`WP_Query` / product query).  
3. Template hierarchy selects theme file (e.g. `single-product.php`).  
4. Template calls composer (`ProductDetail::render()`).  
5. Composer may strip conflicting WC hooks, enqueue assets, require markup.  
6. Markup reads `WC_Product` / cart / taxonomies.  
7. `wp_footer` prints MiniCart + SearchOverlay.  
8. Browser downloads CSS/JS modules and hydrates `[data-shanelle-*]` roots.

---

## Rendering flow (PDP example)

```mermaid
sequenceDiagram
  participant B as Browser
  participant WP as WordPress
  participant T as ProductDetail
  participant G as ProductGallery
  participant P as ProductPurchase
  participant WC as WooCommerce

  B->>WP: GET /product/slug/
  WP->>T: single-product.php
  T->>WC: global $product
  T->>G: render gallery
  T->>P: render purchase panel
  T-->>B: HTML
  B->>P: click Add to Cart
  P->>WC: POST WC AJAX add_to_cart
  WC-->>B: fragments JSON
  B->>B: update MiniCart / cart count
```

---

## Product listing flow

1. Main query or composer-built `WP_Query` args.  
2. `CatalogFilters` merges tax/meta clauses with a valid top-level `relation` — via `woocommerce_product_query` on shop/tax archives and `pre_get_posts` on product search.  
3. `ProductGrid::render()` loops posts → `wc_get_product` → `ProductCard::render()`.  
4. PLP load-more via `admin-ajax.php?action=shanelle_load_product_grid` (also REST `shanelle/v1/product-grid` for PWA).

---

## Cart flow

```mermaid
flowchart LR
  PDP[PDP / card ATC] --> Sess[WC cart session]
  Sess --> Frag[add_to_cart fragments]
  Frag --> MC[MiniCart DOM]
  Frag --> Count[Header cart count]
  MC --> Update[wc-ajax shanelle_mini_cart_update]
  Update --> Sess
  Update --> Bundle[MiniCart + header + optional CartPage fragments]
  Bundle --> CartPage[Cart page UI]
  CartPage --> CO[Checkout]
```

| Step | Mechanism |
|------|-----------|
| Add | WC `add_to_cart` AJAX (ProductPurchase / ProductCard) or form POST |
| Fragment refresh | `woocommerce_add_to_cart_fragments` (header count + MiniCart) |
| Qty change in drawer | `/?wc-ajax=shanelle_mini_cart_update` → MiniCart + `.header-cart__count` |
| Qty/remove on cart page | Same update endpoint with `include_cart_page=1` (one round-trip) |
| Full cart page refresh | `shanelle_cart_page_get` (includes MiniCart + header fragments) |
| Shipping estimate | WooCommerce `cart.js` posts the calculator → `WC_Form_Handler::calculate_shipping()`; theme re-renders on `updated_wc_div` |
| Persist | WooCommerce session / cookies |

---

## Checkout flow

1. User opens WC **classic** checkout page → `form-checkout.php` → `CheckoutPage::render_form()`.  
   (Checkout **block** is not supported — it bypasses theme template overrides.)  
2. Billing/shipping fields render via WC checkout fields.  
3. Order review HTML supplied by theme on `woocommerce_checkout_order_review` (root also carries `woocommerce-checkout-review-order-table` for WC AJAX loading).  
4. Payment methods from registered WC gateways.  
5. `updated_checkout` AJAX refreshes fragments (`CheckoutPage::add_checkout_fragments` overrides `.woocommerce-checkout-review-order-table`).  
6. Place order posts to WC checkout processor → order created.  
7. Redirect to order-received (WC thank-you — not a custom Shanelle composer in this audit).

WooCommerce owns validation, order creation, payment, and shipping calculation. The theme owns presentation only.

**Not implemented yet:** Custom multi-step checkout; gateway-specific theme code.

---

## Search flow

| Mode | Path |
|------|------|
| Overlay typing | JS → `admin-ajax` `shanelle_search_suggest` or REST `/wp-json/shanelle/v1/search` |
| Response | JSON with HTML from `SearchResults` + structured matches |
| Full page | WP search → `search.php` → `SearchPage` restricted to products |

---

## Collection flow

1. Terms in taxonomy `product_collection` with private term meta.  
2. Index via CollectionsPage; archive via CollectionPage / taxonomy templates.  
3. Products assigned in admin like categories.  
4. Homepage featured collections read via `Shanelle\Catalog\Queries`.

---

## Price / variation flow

1. `ProductPrice::get_display_data( $product )` normalizes HTML.  
2. Variable selection updates client state via variation JSON + CustomEvents (`shanelle:product-variations:*`).  
3. Purchase panel listens and enables/disables ATC.

---

## Related docs

- [WOOCOMMERCE_ARCHITECTURE.md](./WOOCOMMERCE_ARCHITECTURE.md)  
- [ROUTES.md](./ROUTES.md)  
- [EVENTS.md](./EVENTS.md)  
