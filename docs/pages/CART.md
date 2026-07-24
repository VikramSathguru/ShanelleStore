# Cart Page

The cart page is composed by the `CartPage` page controller. It reuses `MiniCart::build_cart_state()` for line items and WooCommerce cart APIs for totals, coupons, and checkout.

## Templates

| File | Role |
|------|------|
| `woocommerce/cart/cart.php` | WooCommerce override; delegates to `CartPage::render()` |
| `woocommerce/cart/cart-empty.php` | Empty cart override; same composer |
| `components/cart-page/cart-page.php` | Markup-only composition template |

## Composition

```
CartPage
├── Line items (state from MiniCart::build_cart_state())
├── Coupon form (WC POST handler)
├── Update bag form (WC POST handler)
├── Shipping estimator (WooCommerce shipping calculator)
├── Order summary (WC cart totals APIs)
├── Proceed to checkout / Continue shopping
├── Cross-sells (ProductGrid + ProductCard)
└── Mobile sticky checkout (total + Ir a pagar → WC checkout URL)
```

Mobile line cells use `data-title` labels (Precio / Cantidad / Subtotal) via CSS; desktop keeps the table header row.
## Controller

- `inc/components/CartPage.php`
- Assets: `components/cart-page/cart-page.css`, `cart-page.js`

## Theme Customizer

**Appearance → Customize → Cart Page**

| Setting | Purpose |
|---------|---------|
| Show cross-sell products | Toggle recommendations block |
| Cross-sells section title | Section heading |
| Cross-sells product limit | 2–12 products |
| Show shipping estimator | Toggle WooCommerce shipping calculator in order summary |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_cart_page_state` | Adjust full page state payload |
| `shanelle_cart_page_totals_rows` | Modify order summary rows |
| `shanelle_cart_page_cross_sell_query` | Adjust cross-sell `WP_Query` args |
| `shanelle_cart_page_settings` | Adjust Customizer-derived settings |
| `shanelle_cart_page_ajax_response` | Adjust AJAX refresh payload |
| `shanelle_cart_page_shipping_calculator_button_text` | Shipping estimator toggle label |

Line items also pass through existing `shanelle_mini_cart_state` and `shanelle_mini_cart_item` filters via `MiniCart::build_cart_state()`.

## Events

| Event | When |
|-------|------|
| `shanelle:cart-page:ready` | Cart page hydrated |
| `shanelle:cart-page:updated` | AJAX fragment refresh completed |

AJAX quantity/remove uses a **single** `shanelle_mini_cart_update` request with `include_cart_page=1`. The response returns cart page state, MiniCart fragments, and header `.header-cart__count` together (no sequential `shanelle_cart_page_get`).

`shanelle_cart_page_get` remains available for full-page refresh and also returns MiniCart + header fragments.

## Shipping calculator

WooCommerce owns the estimator end to end; the theme only styles it.

| Concern | Owner |
|---------|-------|
| Panel toggle, country/state sync, select2 | WooCommerce `wc-cart` (`cart.js`) + `wc-country-select` / `wc-address-i18n` |
| Address submit + rate calculation | `WC_Form_Handler::calculate_shipping()` (nonce `woocommerce-shipping-calculator`) |
| Summary/total re-render after calculation | Theme: `updated_wc_div` / `updated_shipping_method` → `shanelle_cart_page_get` |
| Field markup + styling | `woocommerce/cart/shipping-calculator.php`, `cart-page.css` |

Notes:

- The override keeps WooCommerce's `shipping-calculator-button` / `shipping-calculator-form` hooks and `style="display:none;"` so `cart.js` controls visibility and `aria-expanded`. The theme no longer ships a competing toggle (a JS-less fallback runs only when `wc-cart` is absent).
- State fields carry `data-input-classes` so theme classes survive WooCommerce's country/state field swap.
- `CartPage::enqueue_assets()` enqueues `wc-cart` when the estimator renders; WooCommerce still enqueues it on cart pages by default.
- Because the theme renders no `.cart_totals` element, WooCommerce cannot refresh totals itself — the theme re-renders its own fragment (also updating the MiniCart, header count, and mobile sticky total).

## Helper

```php
shanelle_cart_page();
```

Normally invoked automatically by the WooCommerce template overrides.

## How to check (verification)

1. **Page loads**
   - Visit `/cart/` (or your WooCommerce cart page slug).
   - Confirm themed layout: heading, line items or empty state, order summary.

2. **Add products**
   - Add simple and variable products from the shop or PDP.
   - Open cart; verify thumbnail, title, variation summary, price, quantity, line subtotal.

3. **Update quantity (AJAX)**
   - Use +/- stepper or change the quantity input.
   - Totals and line subtotals should refresh without a full page reload.

4. **Remove item (AJAX)**
   - Click remove on a line item.
   - Item disappears and totals update. Empty cart reloads to empty state.

5. **Update bag (form POST)**
   - Change quantities and click **Update bag**.
   - Page reloads with WooCommerce notices and persisted quantities.

6. **Coupon**
   - Apply a valid WooCommerce coupon code.
   - Discount row appears in order summary.

7. **Shipping estimator**
   - Enable shipping under **WooCommerce → Settings → Shipping** and turn on **Enable the shipping calculator on the cart page**.
   - Click **Calcular envío** → panel slides open once, `aria-expanded` becomes `true`.
   - Pick a country → state field swaps to select/text/hidden and keeps theme styling.
   - Submit **Update** → shipping row, total, and mobile sticky total refresh without a full page reload.

8. **Checkout**
   - Click **Ir a pagar** (summary or mobile sticky) → lands on WooCommerce checkout with items intact.

9. **Mobile sticky checkout**
   - Viewport &lt; 48rem with items in cart: fixed bottom bar shows **Total** + **Ir a pagar**.
   - Change qty via AJAX → sticky total updates; empty cart → sticky gone (page reloads to empty).
   - Desktop ≥ 48rem: sticky hidden; sidebar summary remains sticky.

10. **Mobile line labels**
    - On mobile, each line shows labeled **Precio**, **Cantidad**, and **Subtotal**.
    - Desktop keeps column headers; no inline cell labels.

11. **Cross-sells**
   - With cross-sells assigned on products, confirm **You may also like** grid renders via `ProductGrid`.

12. **Mini cart sync**
   - Change qty/remove on the cart page; open the header bag drawer — lines, subtotal, and header badge must match without a full reload.
   - From the drawer while still on `/cart/`, change qty; cart page totals/lines must update from the same response.

13. **Header count**
   - Qty change or remove on cart page or MiniCart updates `.header-cart__count` immediately (including `is-active` when count hits 0).

14. **Customizer**
    - Toggle cross-sells, shipping estimator, or change title/limit under **Cart Page** settings.

15. **Console events**
    - In DevTools: `shanelle:cart-page:ready` on load, `shanelle:cart-page:updated` after AJAX changes.
    - Network: one `shanelle_mini_cart_update` per stepper/remove (no follow-up `shanelle_cart_page_get`).

## Requirements

- WooCommerce active
- Cart page assigned under **WooCommerce → Settings → Advanced → Page setup**
- Products in cart for full layout testing
