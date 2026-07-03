# Checkout Page

The checkout page is composed by the `CheckoutPage` controller. It reuses **`MiniCart::build_cart_state()`** for line items and **`CartPage::build_totals_rows()`** for totals, while WooCommerce handles billing, shipping, payment, and order processing.

## Templates

| File | Role |
|------|------|
| `woocommerce/checkout/form-checkout.php` | WooCommerce override; delegates to `CheckoutPage::render_form()` |
| `components/checkout-page/checkout-page.php` | Markup-only two-column checkout layout |

## Composition

```
CheckoutPage
├── Billing / shipping fields (WooCommerce checkout hooks)
├── Order summary (MiniCart line item state)
├── Totals (CartPage totals rows + WC calculate_totals)
├── Payment gateways (WooCommerce checkout payment)
├── Place order (WooCommerce checkout processor)
└── Secure checkout trust copy
```

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
4. Change country/shipping — order summary refreshes via WooCommerce AJAX.
5. Complete a test order with a gateway (e.g. Cash on Delivery / Stripe test mode).
6. Confirm **Edit bag** returns to the cart page with items intact.

## Requirements

- WooCommerce active
- Checkout page assigned in WooCommerce settings
- At least one payment gateway enabled
- Items in cart (empty cart redirects away from checkout)
