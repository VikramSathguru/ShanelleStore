# Plugin Dependencies

Inventory of plugins present under `wp-content/plugins/` at audit time, relative to the Shanelle theme.

**mu-plugins:** none found.

---

## Plugin matrix

| Plugin directory | Purpose | Dependency level | Required for Shanelle theme? | Can be removed locally? | Future replacement / notes |
|------------------|---------|------------------|------------------------------|-------------------------|----------------------------|
| `woocommerce` | Catalog, cart, checkout, orders, customers | **Core commerce** | **Yes** | No — store breaks | Keep; always primary |
| `advanced-custom-fields` | Custom fields UI for WP content | Optional tooling | **No** — theme catalog uses custom term meta APIs, not `get_field()` | Yes, unless editors rely on ACF field groups outside theme | May be used later for marketing pages; not wired in theme PHP |
| `seo-by-rank-math` | SEO titles, sitemaps, schema | SEO | No for theme boot | Yes for pure UI work; keep for production SEO | Could swap for Yoast/AIOSEO |
| `query-monitor` | Dev debugging | Development | No | Yes on production Hostinger | Do not ship enabled to public traffic |
| `health-check` | Site health / troubleshooting | Development | No | Yes on production | Ops utility |
| `akismet` | Comment spam | Optional WP default | No | Yes if unused | Keep if blog/comments enabled |

---

## Required vs optional

### Hard required

- **WooCommerce** — theme calls `WC`, `wc_*`, product types, cart, checkout, AJAX endpoints.

### Soft / environmental

- Rank Math — production SEO (not imported by theme).  
- ACF — installed but **not a theme code dependency** today.  
- Query Monitor / Health Check — local FlyEnv tooling.

---

## Theme ↔ plugin coupling

| Coupling | Detail |
|----------|--------|
| WC templates overridden | High — custom cart/checkout/account/PDP |
| WC hooks removed/replaced | High — see WooCommerce architecture |
| ACF PHP usage in theme | **None found** (`get_field` / `have_rows` not used) |
| Rank Math filters in theme | Not implemented yet |
| Payment plugins | Not installed / Not implemented yet |
| Shipping plugins | Not installed / Not implemented yet |

---

## Future plugins (planned; not present)

Documented in [FUTURE_INTEGRATIONS.md](./FUTURE_INTEGRATIONS.md):

- Payment: Stripe, PayPal, PixelPay, BAC Credomatic  
- Analytics: Meta Pixel, TikTok Pixel, GA  
- Auth: social login providers  
- Logistics: Cargo Mobil (custom or middleware)  
- Social commerce sync: Instagram / TikTok Shop connectors  

**Architectural rule:** Prefer plugins or `inc/integrations` modules that hook filters/events — do not embed secrets or gateway SDKs in view templates.

---

## Hostinger production recommendation

| Plugin | Production |
|--------|------------|
| WooCommerce | Required |
| Rank Math | Recommended |
| ACF | Only if content model needs it |
| Query Monitor | Disable / remove |
| Health Check | Optional temporarily for migrations |
| Akismet | Optional |

---

## Version pinning

Not managed by a root Composer lock for the site application. Plugin updates should be tested on FlyEnv before Hostinger. Theme header states WordPress **Requires at least 6.4**, **PHP 8.3**.
