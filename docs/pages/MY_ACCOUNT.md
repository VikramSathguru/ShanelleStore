# My Account Page

The My Account page is composed by the `MyAccountPage` controller. WooCommerce handles authentication, order persistence, address validation, payment token storage, and account updates. The theme provides layout, navigation, endpoint presentation, and guest auth shells.

## Templates

| File | Role |
|------|------|
| `woocommerce/myaccount/my-account.php` | Logged-in shell override; delegates to `MyAccountPage::render()` |
| `woocommerce/myaccount/navigation.php` | Themed sidebar account navigation |
| `woocommerce/myaccount/dashboard.php` | Dashboard endpoint override |
| `woocommerce/myaccount/orders.php` | Orders list endpoint override |
| `woocommerce/myaccount/view-order.php` | Order detail endpoint override |
| `woocommerce/myaccount/downloads.php` | Downloads endpoint override |
| `woocommerce/myaccount/my-address.php` | Addresses overview override |
| `woocommerce/myaccount/form-edit-address.php` | Address list + edit form override |
| `woocommerce/myaccount/form-edit-account.php` | Account details form override |
| `woocommerce/myaccount/payment-methods.php` | Payment methods endpoint override |
| `woocommerce/myaccount/form-login.php` | Guest login/register override |
| `woocommerce/myaccount/form-lost-password.php` | Lost password override |
| `woocommerce/myaccount/form-reset-password.php` | Reset password override |
| `woocommerce/myaccount/lost-password-confirmation.php` | Reset email confirmation override |
| `components/my-account-page/my-account-page.php` | Logged-in markup-only layout |
| `components/my-account-page/my-account-page-guest.php` | Guest auth markup shell |
| `components/my-account-page/partials/*` | Endpoint and UI partials (markup only) |

## Composition

```
MyAccountPage
├── Account welcome header
├── Quick actions (dashboard + Customizer)
├── WooCommerce notices
├── Sidebar navigation (desktop + collapsible mobile toggle)
├── Account content (themed endpoint partials)
│   ├── Dashboard (recent order cards + quick actions)
│   ├── Orders (order cards + pagination)
│   ├── View order (status header + WC order details hook)
│   ├── Downloads (download cards)
│   ├── Addresses (address cards)
│   ├── Payment methods (saved method cards)
│   └── Edit account / Edit address (WC core forms in themed shell)
├── Mobile bottom navigation
└── Guest views
    ├── Login / Register
    ├── Lost password
    ├── Reset password
    └── Reset confirmation
```

## Controller

- `inc/components/MyAccountPage.php`
- Assets: `components/my-account-page/my-account-page.css`, `my-account-page.js`

Business logic (queries, normalization, empty states, WC core form delegation) lives in the controller. Partials contain markup only. CSS handles page layout and scoped endpoint styling. JavaScript handles mobile nav toggle, skeleton reveal, and hydration events.

## Theme Customizer

**Appearance → Customize → My Account Page**

| Setting | Purpose |
|---------|---------|
| Show welcome line in account header | Toggle logged-in welcome copy |
| Show return to shop link | Toggle header shop link |
| Enable mobile account navigation toggle | Collapsible sidebar nav on small screens |
| Enable mobile bottom account navigation | Fixed bottom nav on small screens |
| Show dashboard quick actions | Toggle quick action links on dashboard |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_my_account_page_state` | Adjust full page state payload |
| `shanelle_my_account_page_settings` | Adjust Customizer-derived settings |
| `shanelle_my_account_page_quick_actions` | Adjust dashboard quick action links |
| `shanelle_my_account_page_mobile_bottom_nav_items` | Adjust mobile bottom nav items |

WooCommerce filters such as `woocommerce_account_menu_items` and `woocommerce_my_account_my_orders_query` continue to work unchanged.

## Events

| Event | When |
|-------|------|
| `shanelle:my-account-page:ready` | Account page hydrated; skeleton placeholders removed |

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

2. **Dashboard**
   - Sign in and open `/my-account/`.
   - Confirm welcome header, quick actions, and recent order cards (or empty state).

3. **Navigation**
   - Visit Dashboard, Orders, Addresses, Account details, Downloads, Payment methods (as enabled), and Log out.
   - Active nav item should highlight in sidebar and mobile bottom nav.

4. **Orders**
   - Open **Orders** and confirm order cards with status badges.
   - Click **View** and confirm order detail header plus WooCommerce order table.

5. **Addresses**
   - Confirm billing/shipping address cards.
   - Edit an address, save, and confirm WooCommerce success notice.

6. **Account details**
   - Update name/email/password on **Account details** using the WooCommerce form.
   - Confirm save works and notices appear once.

7. **Downloads & payment methods**
   - Confirm empty states when none exist.
   - Confirm card layout when data exists.

8. **Mobile**
   - On a narrow viewport, use **Account menu** toggle for sidebar nav.
   - Confirm fixed bottom navigation remains visible and highlights the active endpoint.

9. **Loading placeholders**
   - On dashboard/orders/downloads, confirm skeleton cards appear briefly then content reveals on `shanelle:my-account-page:ready`.

10. **Customizer**
    - Toggle welcome line, shop link, sidebar toggle, bottom nav, and quick actions.

11. **Console events**
    - In DevTools: `shanelle:my-account-page:ready` on load.

## Requirements

- WooCommerce active
- My Account page assigned in WooCommerce settings
- At least one customer account for logged-in testing
