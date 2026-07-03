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
└── Cross-sells (ProductGrid + ProductCard)
```

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

AJAX quantity/remove uses the existing `shanelle_mini_cart_update` endpoint, then refreshes the cart page via `shanelle_cart_page_get`.

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
   - Open **Estimate shipping**, enter destination, submit **Update**.
   - Shipping row in order summary should refresh after page reload.

8. **Checkout**
   - Click **Proceed to checkout** → lands on checkout page with items intact.

9. **Cross-sells**
   - With cross-sells assigned on products, confirm **You may also like** grid renders via `ProductGrid`.

10. **Mini cart sync**
   - Change cart on the cart page; header mini cart count/contents should stay in sync after add/remove elsewhere.

11. **Customizer**
    - Toggle cross-sells, shipping estimator, or change title/limit under **Cart Page** settings.

12. **Console events**
    - In DevTools: `shanelle:cart-page:ready` on load, `shanelle:cart-page:updated` after AJAX changes.

## Requirements

- WooCommerce active
- Cart page assigned under **WooCommerce → Settings → Advanced → Page setup**
- Products in cart for full layout testing
