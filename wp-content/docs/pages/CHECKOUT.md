# Checkout Page

The checkout page is composed by the `CheckoutPage` controller. It reuses **`MiniCart::build_cart_state()`** for line items and **`CartPage::build_totals_rows()`** (via `CheckoutPage::build_checkout_totals_rows()`) for totals, while WooCommerce handles billing, shipping, payment, coupons, and order processing.

## Templates

| File | Role |
|------|------|
| `woocommerce/checkout/form-checkout.php` | WooCommerce override; fires pre/post hooks, delegates to `CheckoutPage::render_form()` |
| `woocommerce/checkout/form-coupon.php` | Themed coupon form markup (separate form; required by WooCommerce AJAX) |
| `components/checkout-page/checkout-page.php` | Markup-only two-column checkout layout |
| `components/checkout-page/partials/shipping-methods.php` | Shipping method radio list markup using WC package rates |

## Composition

```
CheckoutPage
├── Customer notices (woocommerce_output_all_notices)
├── Returning customer login (woocommerce_checkout_login_form)
├── Billing / shipping / order notes (WooCommerce checkout hooks)
├── Guest checkout + account creation (WooCommerce billing/account fields)
├── Coupon form (woocommerce_checkout_coupon_form — outside main checkout form)
├── Order summary (MiniCart line item state)
├── Shipping methods (WC shipping packages + rates)
├── Totals (CartPage totals rows, shipping row omitted when methods shown)
├── Payment gateways (woocommerce_checkout_payment)
├── Place order (WooCommerce checkout processor)
└── Trust indicators + secure checkout message
```

## Controller

- `inc/components/CheckoutPage.php`
- Assets: `components/checkout-page/checkout-page.css`, `checkout-page.js`

## Theme Customizer

**Appearance → Customize → Checkout Page**

| Setting | Purpose |
|---------|---------|
| Show product thumbnails in order summary | Toggle line-item images |
| Show secure checkout message | Toggle trust indicators below summary |
| Edit bag link label | Link back to cart page |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_checkout_page_state` | Adjust full checkout page state |
| `shanelle_checkout_page_settings` | Adjust Customizer-derived settings |
| `shanelle_checkout_page_totals_rows` | Adjust checkout totals rows |
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

```
{your-site-url}/checkout/
```

Confirm the slug under **WooCommerce → Settings → Advanced → Page setup → Checkout page**.

## Integration steps

1. Ensure WooCommerce is active and a checkout page is assigned.
2. Enable at least one shipping zone/method if products require shipping.
3. Enable at least one payment gateway (e.g. Cash on Delivery for testing).
4. Optional: enable coupons under **WooCommerce → Settings → General**.
5. Optional: enable guest checkout and/or account creation under **WooCommerce → Settings → Accounts & Privacy**.
6. Optional: enable order notes under **WooCommerce → Settings → General → Order notes**.
7. Customize presentation under **Appearance → Customize → Checkout Page**.

## Testing checklist

### Layout and navigation

- [ ] Add products to cart and open `/checkout/`.
- [ ] Confirm premium two-column layout on desktop (details left, summary right).
- [ ] Confirm mobile-first stacking: billing → coupon → order summary.
- [ ] Confirm sticky order summary on desktop (disabled with `prefers-reduced-motion`).
- [ ] Confirm **Edit bag** returns to cart with items intact.

### Customer details

- [ ] Billing fields render and validate (required fields show inline errors).
- [ ] Ship-to-different-address toggle reveals shipping fields.
- [ ] Order notes field appears when enabled in WooCommerce settings.
- [ ] Guest checkout works without an account.
- [ ] Account creation checkbox and fields appear when registration is enabled.

### Login and coupon

- [ ] Returning customer login toggle opens login form (when enabled).
- [ ] Coupon toggle reveals coupon form; applying a valid coupon updates totals.
- [ ] Invalid coupon shows WooCommerce notice.

### Shipping and totals

- [ ] Shipping methods appear after address entry and refresh via AJAX.
- [ ] Changing shipping method updates totals without page reload.
- [ ] Order summary shows subtotal, discounts, fees, taxes (if applicable), and total.
- [ ] Line item thumbnails respect Customizer setting.

### Payment and order

- [ ] Payment methods render in themed cards.
- [ ] Place order button uses design system styling.
- [ ] Validation errors announce via live region and focus first invalid field.
- [ ] Successful test order reaches thank-you page.

### Trust and accessibility

- [ ] Trust indicators and secure checkout message display when enabled.
- [ ] Form fields have visible focus states.
- [ ] Shipping methods use a radiogroup; payment methods are keyboard accessible.

## Suggested commit message

```
Complete checkout page with shipping methods, coupon layout, and validation UX.

Wire WooCommerce shipping rate selection, themed coupon/login panels, trust indicators, and inline error handling without duplicating checkout business logic.
```
