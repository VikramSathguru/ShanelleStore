# Security

Security audit of the Shanelle theme patterns. Host/server hardening on Hostinger is out of theme scope but listed under operational checks.

---

## Input validation

| Surface | Practice |
|---------|----------|
| AJAX product grid / search | `sanitize_text_field`, `absint`, structured query sanitizers in `ProductGrid` |
| Catalog Admin term meta | Dedicated sanitize callbacks (type, date, hero attachment, order) + capability checks |
| Catalog filters query vars | Prefixed params parsed/sanitized before touching `WP_Query` |
| REST search/grid | Sanitization callbacks on registered args |

---

## Escaping

Templates generally use:

- `esc_html`, `esc_attr`, `esc_url`
- `wp_kses_post` for rich WC price HTML / descriptions
- `esc_attr_e` / `esc_html_e` for i18n strings

ABSPATH guards and `defined( 'ABSPATH' ) || exit` are consistent.

---

## Nonce usage

| Area | Status |
|------|--------|
| Product grid AJAX | `check_ajax_referer` — **good** |
| Search suggest AJAX | `check_ajax_referer` — **good** |
| Collection term save | `wp_verify_nonce` — **good** |
| Cart forms | `woocommerce-cart` nonce fields — **good** |
| Mini cart WC AJAX update/get | **No theme nonce verification** (phpcs nonce ignore on POST) |
| Cart page WC AJAX get | **No theme nonce verification** observed |

### Risk: cart AJAX CSRF

An attacker page could potentially trigger quantity changes for a victim who has an active WooCommerce session cookie, depending on browser CSRF protections and SameSite cookie settings. **Mitigation recommendation (future work):** send and verify a WP/WC nonce on `shanelle_mini_cart_*` and `shanelle_cart_page_get`.

---

## Authentication

| Feature | Status |
|---------|--------|
| WP / WC account login | Standard WooCommerce / WP auth |
| Checkout customer | WC session + account |
| Social login | **Not implemented yet** |
| REST catalog endpoints | Public (`__return_true`) by design for read-only search/grid |

---

## Authorization

| Feature | Status |
|---------|--------|
| Collection term capabilities | Mapped to `manage_product_terms` / `edit_products` |
| Admin term meta authorize callback | Present in Catalog Admin |
| Customer can only edit own account | Relies on WC/WP core |
| Theme does not elevate privileges | No custom `map_meta_cap` abuse found |

---

## Potential vulnerabilities / issues

1. **Missing nonces on custom WC AJAX cart endpoints** (above).  
2. **Public REST** for search/grid — acceptable if query sanitization remains strict; watch for future endpoints that expose PII.  
3. **Secrets in `wp-config.php`** — local DB password and salts exist on disk; ensure not committed to public remotes; use Hostinger env-specific config; never paste secrets into docs.  
4. **`WP_DEBUG` / `WP_DEBUG_LOG` true locally** — must be disabled in production to avoid path/info leaks.  
5. **Wishlist in `localStorage`** — not authorization-sensitive, but users may believe it is server-backed.  
6. **Google Fonts third-party request** — privacy consideration for EU/LATAM compliance programs.  
7. **ACF + other plugins** — keep updated; theme is not their security boundary.

---

## XSS / stored content

Rich product descriptions output with `wp_kses_post` / `the_content` filters. Editors with `unfiltered_html` remain a WP core trust boundary.

---

## File upload

Uses WP Media Library / WC product images. No custom upload endpoints in theme.

---

## Recommended pre-production checklist

- [ ] Nonces on all custom cart AJAX  
- [ ] `WP_DEBUG` false on Hostinger  
- [ ] Unique salts/keys per environment  
- [ ] Disable file editor (`DISALLOW_FILE_EDIT`)  
- [ ] Principle of least privilege for DB user  
- [ ] Remove/disable Query Monitor on public site  
- [ ] HTTPS enforced  
- [ ] WC webhook/API keys rotated if ever exposed  

---

## Related docs

- [ROUTES.md](./ROUTES.md)  
- [DEPLOYMENT.md](./DEPLOYMENT.md)  
- [TECH_DEBT.md](./TECH_DEBT.md)  
