# My Account Page

The My Account page is composed by the `MyAccountPage` controller. WooCommerce handles authentication, orders, addresses, payment methods, and account updates. The theme provides layout, navigation styling, and guest auth shells.

## Templates

| File | Role |
|------|------|
| `woocommerce/myaccount/my-account.php` | Logged-in shell override; delegates to `MyAccountPage::render()` |
| `woocommerce/myaccount/navigation.php` | Themed account navigation |
| `woocommerce/myaccount/form-login.php` | Guest login/register override |
| `woocommerce/myaccount/form-lost-password.php` | Lost password override |
| `woocommerce/myaccount/form-reset-password.php` | Reset password override |
| `woocommerce/myaccount/lost-password-confirmation.php` | Reset email confirmation override |
| `components/my-account-page/my-account-page.php` | Logged-in markup-only layout |
| `components/my-account-page/my-account-page-guest.php` | Guest auth markup shell |
| `components/my-account-page/partials/*` | Login, lost password, reset password, confirmation markup |

## Composition

```
MyAccountPage
├── Page header (endpoint title + optional welcome line)
├── WooCommerce notices
├── Account navigation (WC menu items + endpoints)
├── Account content (WC endpoint templates)
│   ├── Dashboard
│   ├── Orders / View order
│   ├── Downloads
│   ├── Addresses / Edit address
│   ├── Payment methods
│   └── Edit account
└── Guest views
    ├── Login / Register
    ├── Lost password
    ├── Reset password
    └── Reset confirmation
```

## Controller

- `inc/components/MyAccountPage.php`
- Assets: `components/my-account-page/my-account-page.css`, `my-account-page.js`

## Theme Customizer

**Appearance → Customize → My Account Page**

| Setting | Purpose |
|---------|---------|
| Show welcome line in account header | Toggle logged-in welcome copy |
| Show return to shop link | Toggle header shop link |
| Enable mobile account navigation toggle | Collapsible nav on small screens |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_my_account_page_state` | Adjust full page state payload |
| `shanelle_my_account_page_settings` | Adjust Customizer-derived settings |

WooCommerce filters such as `woocommerce_account_menu_items` and `woocommerce_my_account_my_orders_query` continue to work unchanged.

## Events

| Event | When |
|-------|------|
| `shanelle:my-account-page:ready` | Account page hydrated |

## Helper

```php
shanelle_my_account_page();
```

Normally invoked automatically by the WooCommerce `my-account.php` override.

## URL to check

Use your store My Account URL:

```
{your-site-url}/my-account/
```

Confirm the exact slug under **WooCommerce → Settings → Advanced → Page setup → My account page**.

Example (local):

```
http://localhost/wordpress/my-account/
```

Or programmatically:

```php
echo wc_get_page_permalink( 'myaccount' );
```

## How to verify

1. **Guest login**
   - Log out and open `/my-account/`.
   - Confirm themed sign-in card with login form (and register form if enabled).

2. **Login**
   - Sign in with a customer account.
   - Confirm two-column layout: navigation left, content right.

3. **Navigation**
   - Visit Dashboard, Orders, Addresses, Account details, Downloads, Payment methods (as enabled).
   - Active nav item should highlight.

4. **Orders**
   - Open **Orders** and click **View** on an order.
   - Confirm order detail renders inside themed content panel.

5. **Addresses**
   - Edit billing/shipping address and save.
   - Confirm WooCommerce success notice and persisted values.

6. **Account details**
   - Update name/email/password on **Account details**.
   - Confirm save works and notices appear once.

7. **Lost password**
   - Log out and open `/my-account/lost-password/`.
   - Submit reset request; confirm confirmation message.

8. **Mobile nav**
   - On a narrow viewport, use **Account menu** toggle to open/close navigation.

9. **Customizer**
   - Toggle welcome line, shop link, and mobile nav under **My Account Page**.

10. **Console events**
    - In DevTools: `shanelle:my-account-page:ready` on load.

## Requirements

- WooCommerce active
- My Account page assigned in WooCommerce settings
- At least one customer account for logged-in testing
