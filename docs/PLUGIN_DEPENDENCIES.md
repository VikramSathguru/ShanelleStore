# Plugin Dependencies

Inventory of plugins present under `wp-content/plugins/` at audit time, relative to the Shanelle theme.

**mu-plugins:** none found.

---

## Plugin matrix

| Plugin directory | Purpose | Dependency level | Required for Shanelle theme? | Can be removed locally? | Future replacement / notes |
|------------------|---------|------------------|------------------------------|-------------------------|----------------------------|
| `woocommerce` | Catalog, cart, checkout, orders, customers | **Core commerce** | **Yes** | No — store breaks | Keep; always primary |
| `woocommerce-paypal-payments` | PayPal / cards via PayPal | Payment gateway | No for theme boot | Keep if PayPal is a live method | Official WC PayPal; theme styles classic checkout only |
| `woo-pagadito-payment-gateway` | Pagadito (Central America) | Payment gateway | No for theme boot | Keep if Pagadito is enabled | Regional gateway; configure in WC → Payments |
| `fygaro` | Fygaro payments | Payment gateway | No for theme boot | Keep if Fygaro is enabled | Regional gateway; configure in plugin / WC settings |
| `facebook-for-woocommerce` | Meta catalog / pixel / shops | Marketing | No for theme boot | Yes if unused | Configure in plugin UI; theme must not embed Pixel ID |
| `tiktok-for-business` | TikTok pixel / catalog | Marketing | No for theme boot | Yes if unused | Configure in plugin UI |
| `google-site-kit` | GA4 / Search Console / ads connectors | Analytics | No for theme boot | Yes if unused | Prefer plugin tags over custom theme snippets |
| `omnisend-connect` | Email / SMS marketing | Marketing | No for theme boot | Yes if unused | Footer newsletter may use provider embed (Customizer) |
| `mailpoet` | Email newsletters | Marketing | No for theme boot | Yes if unused | Optional; do not duplicate list capture in theme |
| `fluentform` | Contact / forms | Forms | No for theme boot | Keep if contact pages use shortcodes | Theme styles Fluent Forms CSS hooks only |
| `seo-by-rank-math` | SEO titles, sitemaps, schema | SEO | No for theme boot | Yes for pure UI work; keep for production SEO | Could swap for Yoast/AIOSEO |
| `advanced-custom-fields` | Custom fields UI for WP content | Optional tooling | **No** — theme catalog uses custom term meta APIs, not `get_field()` | Yes, unless editors rely on ACF field groups outside theme | May be used later for marketing pages; not wired in theme PHP |
| `wp-mail-smtp` | Transactional email transport | Soft / ops | No for theme boot | Keep for production mail delivery (Fluent Forms, WooCommerce) | Configure mailer in plugin UI; never hardcode SMTP secrets in theme |
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
- WP Mail SMTP — configure for Fluent Forms + WooCommerce mail on Hostinger.  
- Query Monitor / Health Check — local FlyEnv tooling.  
- **Payments present in this environment:** PayPal Payments, Pagadito, Fygaro — **theme does not hard-wire them**; enable/configure via WooCommerce → Payments. Stripe / PixelPay / BAC Credomatic remain planned (see [FUTURE_INTEGRATIONS.md](./FUTURE_INTEGRATIONS.md)).  
- **Marketing present in this environment:** Meta for WooCommerce, TikTok for Business, Google Site Kit, Omnisend, MailPoet — theme does not embed pixel IDs or SDKs.  
- Fluent Forms — contact/info pages may embed shortcodes in page content.  
- **Footer newsletter** — Plugin First. Theme Customizer accepts **Newsletter Embed Code** (provider HTML/JS embed, or a lone WP shortcode). Theme places sanitized markup in the footer engage column only. List capture, validation, and credentials stay with the provider (Omnisend, Mailchimp, Fluent Forms, etc.). Empty embed → no footer form markup.

---

## Theme ↔ plugin coupling

| Coupling | Detail |
|----------|--------|
| WC templates overridden | High — custom cart/checkout/account/PDP |
| WC hooks removed/replaced | High — see WooCommerce architecture |
| ACF PHP usage in theme | **None found** (`get_field` / `have_rows` not used) |
| Rank Math filters in theme | Not implemented yet |
| Payment plugins | **Installed** (PayPal, Pagadito, Fygaro); **not referenced in theme PHP** — gateway-agnostic checkout hooks/CSS only |
| Marketing / pixel plugins | **Installed**; **not referenced in theme PHP** |
| Shipping plugins | Not installed — WC core shipping zones only |
| Fluent Forms | CSS presentation hooks on contact template; body via editor shortcodes |

---

## Future plugins (planned; not present or not theme-wired)

Documented in [FUTURE_INTEGRATIONS.md](./FUTURE_INTEGRATIONS.md):

- Payment: Stripe, PixelPay, BAC Credomatic (in addition to gateways already installed)  
- Analytics: deeper CAPI / shared event mapper if plugins are insufficient  
- Auth: social login providers  
- Logistics: Cargo Mobil (custom or middleware)  
- Social commerce sync: Instagram / TikTok Shop connectors beyond installed plugins  

**Architectural rule:** Prefer plugins or `inc/integrations` modules that hook filters/events — do not embed secrets or gateway SDKs in view templates.

---

## Hostinger production recommendation

| Plugin | Production |
|--------|------------|
| WooCommerce | Required |
| Chosen payment gateway(s) | Required for live checkout (PayPal / Pagadito / Fygaro as decided) |
| Rank Math | Recommended |
| Meta / TikTok / Site Kit | Recommended when running ads |
| Fluent Forms + WP Mail SMTP | Recommended for contact + order mail |
| Omnisend or MailPoet | Optional (one list provider) |
| ACF | Only if content model needs it |
| Query Monitor | Disable / remove |
| Health Check | Optional temporarily for migrations |
| Akismet | Optional |

---

## Version pinning

Not managed by a root Composer lock for the site application. Plugin updates should be tested on FlyEnv before Hostinger. Theme header states WordPress **Requires at least 6.4**, **PHP 8.3**.
