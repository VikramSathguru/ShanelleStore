# Checkout Page

The checkout page is composed by the `CheckoutPage` controller. It reuses **`MiniCart::build_cart_state()`** for line items and **`CartPage::build_totals_rows()`** for totals, while WooCommerce handles billing, shipping, payment, and order processing.

## Ownership

| Concern | Owner |
|---------|--------|
| Checkout validation | WooCommerce |
| Order creation | WooCommerce checkout processor |
| Payment processing | WooCommerce payment gateways (plugins) |
| Shipping calculation / method rates | WooCommerce shipping |
| Customer field definitions & persistence | WooCommerce |
| Layout, order-review presentation, styling | Shanelle theme (`CheckoutPage`) |

The theme must not replace WooCommerce checkout processing, validation, gateways, or payment SDKs.

## Classic shortcode checkout (required)

Shanelle checkout presentation depends on the **classic** WooCommerce checkout (shortcode / block theme equivalent that loads `woocommerce/checkout/form-checkout.php`).

| Requirement | Detail |
|-------------|--------|
| Checkout page content | Must use classic checkout (`[woocommerce_checkout]` or the standard WC checkout page that loads theme template overrides) |
| Do not use | WooCommerce **Checkout block** as the page content — Blocks bypass `form-checkout.php` and `CheckoutPage` |
| Admin path | **WooCommerce → Settings → Advanced → Page setup → Checkout page** → page that renders classic checkout |

If the checkout page is switched to the Checkout block, Shanelle’s themed layout, order-review fragments, and CSS will not apply.

## Templates

| File | Role |
|------|------|
| `woocommerce/checkout/form-checkout.php` | WooCommerce override; delegates to `CheckoutPage::render_form()` |
| `woocommerce/checkout/form-coupon.php` | Styled coupon form (sibling of checkout form; not nested) |
| `components/checkout-page/checkout-page.php` | Markup-only two-column checkout layout |

## Composition

```
CheckoutPage
├── Express payment slot (`woocommerce_checkout_before_customer_details`)
├── Billing / shipping fields (WooCommerce checkout hooks)
├── Order summary (MiniCart line item state)
├── Totals (CartPage totals rows + WC calculate_totals)
├── Payment gateways (WooCommerce checkout payment)
├── Place order (WooCommerce checkout processor)
├── Coupon (WC coupon form — sibling of checkout form, after summary)
├── Mobile sticky place-order CTA (clicks WC #place_order)
└── Secure checkout trust copy
```

### Express payment preparation

Theme-owned presentation slot at the top of the checkout main column. It fires the standard WooCommerce hook that Stripe, PayPal, and WC pay buttons use — **no gateway SDKs or vendor logic in the theme**.

| Item | Detail |
|------|--------|
| Markup | `.checkout-page__express` / `[data-shanelle-checkout-express]` |
| Hook | `woocommerce_checkout_before_customer_details` (inside the slot) |
| Empty state | Slot is `hidden` + `--empty` when plugins output nothing |
| Ownership | Gateway **plugins** own wallets, SCA/3DS, and charge flows |
| Theme role | Wrapper spacing + CSS hardening only |

Do **not** install payment SDKs in the theme. Prefer official WooCommerce gateway plugins; relocate hooks only in an adapter plugin if a vendor default placement is wrong.

**Future gateway test checklist (when a plugin is installed):**

1. Express / Apple Pay / Google Pay / Link / PayPal buttons appear in the express slot (main column, above billing).  
2. Buttons remain usable after `updated_checkout` (address/shipping change).  
3. Classic `#place_order` and mobile sticky CTA still work for non-express methods.  
4. Sticky CTA does **not** attempt to drive express wallets (bound only to `#place_order`).  
5. SCA/3DS or PayPal popups are not clipped by checkout CSS; sticky `z-index` does not block modals.  
6. Checkout page remains **classic** shortcode checkout (not the Checkout block).

### Coupon placement

The WooCommerce coupon form stays **outside** the checkout `<form>` (nested forms are invalid). Layout order is:

1. Customer fields  
2. Order summary / payment / place order  
3. Coupon  

so the coupon does not interrupt the path to payment.

### Mobile sticky place-order

On viewports below `64rem`, a fixed bar shows the current order total and a button that **clicks** WooCommerce’s existing `#place_order` submit control (no duplicate processing).

| Behavior | Detail |
|----------|--------|
| Show | When `#place_order` exists and is mostly off-screen |
| Hide | When `#place_order` is missing, substantially visible, or desktop (`≥64rem`) |
| Safe area | `env(safe-area-inset-bottom)` padding |
| Total sync | Reads `.checkout-page__total-row--total` after `updated_checkout` |
| Express wallets | **Out of scope for sticky** — Apple Pay / PayPal express / etc. stay in gateway UI; sticky never proxies them |

### Error / notice focus

On WooCommerce `checkout_error`, theme JS scrolls to the first invalid field or notice group and focuses it. Validation messages remain WooCommerce-owned.
### Order review AJAX compatibility

Theme order review keeps Shanelle markup and adds WooCommerce’s review fragment class so `checkout.js` can show the `update_order_review` loading overlay:

| Piece | Detail |
|-------|--------|
| Root class | `woocommerce-checkout-review-order-table` on `[data-shanelle-checkout-order-review]` |
| Fragment keys | `.woocommerce-checkout-review-order-table` (overrides WC default table HTML) and `[data-shanelle-checkout-order-review]` |
| Payment fragment | Unchanged — still `.woocommerce-checkout-payment` from WC |

## Controller

- `inc/components/CheckoutPage.php`
- Assets: `components/checkout-page/checkout-page.css`, `checkout-page.js`

## Theme Customizer

**Appearance → Customize → Checkout Page**

| Setting | Purpose |
|---------|---------|
| Show product thumbnails in order summary | Toggle line-item images |
| Show secure checkout message | Toggle trust copy below summary |
| Edit bag link label | Link back to cart page |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_checkout_page_state` | Adjust full checkout page state |
| `shanelle_checkout_page_settings` | Adjust Customizer-derived settings |
| `shanelle_checkout_page_fragments` | Adjust AJAX order-review fragments |

Line items inherit `shanelle_mini_cart_state` / `shanelle_mini_cart_item` via `MiniCart::build_cart_state()`. Totals inherit `shanelle_cart_page_totals_rows` via `CartPage::build_totals_rows()`.

## Events

| Event | When |
|-------|------|
| `shanelle:checkout-page:ready` | Checkout page hydrated |
| `shanelle:checkout-page:updated` | WooCommerce `updated_checkout` AJAX refresh |

## Helper

```php
shanelle_checkout_page( WC()->checkout() );
```

Normally invoked by the WooCommerce `form-checkout.php` override.

## URL to check

Use your store checkout URL:

```
{your-site-url}/checkout/
```

In WordPress admin, confirm the exact slug under **WooCommerce → Settings → Advanced → Page setup → Checkout page**.

Example (local):

```
http://localhost/wordpress/checkout/
```

Or programmatically:

```php
echo wc_get_checkout_url();
```

## How to verify

1. Add products to the cart.
2. Open **`/checkout/`** (or your checkout page slug).
3. Confirm themed two-column layout: billing/shipping left, order summary + payment right.
4. Confirm checkout page uses **classic** shortcode checkout (not the Checkout block).
5. On mobile: confirm sticky total + place-order bar; tapping it submits via WC `#place_order`.
6. On mobile: confirm coupon appears **after** the order summary (not between fields and payment).
7. Change country/shipping — order summary shows WC loading overlay and refreshes via AJAX.
8. Submit with an empty required field — page scrolls/focuses the invalid field or WC notice.
9. Complete a test order with a gateway (e.g. Cash on Delivery / Stripe test mode).
10. Confirm **Edit bag** returns to the cart page with items intact.

## Requirements

- WooCommerce active
- Checkout page assigned in WooCommerce settings
- Classic `[woocommerce_checkout]` (or equivalent classic template), **not** the Checkout block
- At least one payment gateway enabled
- Items in cart (empty cart redirects away from checkout)
