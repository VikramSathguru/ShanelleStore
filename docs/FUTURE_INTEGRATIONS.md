# Future Integrations

**Status of all items below:** Not implemented yet (unless noted).  
These are architecture plans only — no code in this document.

Integration rule: keep SDKs, secrets, and vendor APIs out of theme view templates. Prefer plugins or a thin `inc/integrations/` (or must-use) layer that hooks `shanelle_*` filters and `shanelle:*` DOM events / WC hooks.

---

## Instagram Shop

| Field | Detail |
|-------|--------|
| Purpose | Sync catalog / tag products on Instagram shopping |
| Requirements | Meta Business account, Commerce Manager, product feed (CSV/API), HTTPS store |
| Dependencies | WooCommerce products with clean titles, images, prices; possibly Meta Commerce plugin or custom feed exporter |
| Expected architecture | Feed generator plugin (scheduled) → Meta catalog; theme unchanged except accurate product URLs/images |
| Difficulty | Medium |
| Risks | Attribute mapping mismatches; local inventory vs IG sync lag; policy compliance |

---

## TikTok Shop

| Field | Detail |
|-------|--------|
| Purpose | Sell via TikTok catalog / shop |
| Requirements | TikTok Shop seller account (region eligibility), product feed, webhooks for orders if OMS |
| Dependencies | WC product data quality; may need order import middleware |
| Expected architecture | Connector plugin or middleware service ↔ WC REST; theme stays presentation-only |
| Difficulty | Medium–High (region + ops) |
| Risks | Nicaragua / LATAM availability; order duplication; tax/shipping mismatches |

---

## Meta Pixel

| Field | Detail |
|-------|--------|
| Purpose | Ads attribution: ViewContent, AddToCart, Purchase |
| Requirements | Pixel ID, domain verification, preferably Conversions API |
| Dependencies | Listen to `shanelle:added_to_cart`, checkout, WC `woocommerce_thankyou` |
| Expected architecture | Small integration module enqueues pixel; maps events; server CAPI via WC order hook |
| Difficulty | Low–Medium |
| Risks | Consent/banner requirements; duplicate events if plugin + custom both fire |

---

## TikTok Pixel

| Field | Detail |
|-------|--------|
| Purpose | TikTok ads events |
| Requirements | Pixel ID / Events API token |
| Dependencies | Same event bus as Meta |
| Expected architecture | Parallel to Meta Pixel module; shared event mapper recommended |
| Difficulty | Low–Medium |
| Risks | Consent; PII hashing rules |

---

## Google Analytics (GA4)

| Field | Detail |
|-------|--------|
| Purpose | Traffic + ecommerce reporting |
| Requirements | GA4 measurement ID; Enhanced ecommerce |
| Dependencies | WC order data; optional Google Site Kit or GTM |
| Expected architecture | Prefer GTM container loaded once; fire dataLayer from theme events / thank-you |
| Difficulty | Low |
| Risks | Double counting with other tags; SPA-like drawer carts need explicit events |

---

## Stripe

| Field | Detail |
|-------|--------|
| Purpose | Card payments |
| Requirements | Stripe account, WooCommerce Stripe Gateway plugin (typical) |
| Dependencies | CheckoutPage must keep WC payment hooks intact (already does) |
| Expected architecture | Official WC Stripe plugin; theme CSS only if needed for gateway UI |
| Difficulty | Low (with official plugin) |
| Risks | SCA/3DS UX; webhook endpoints on Hostinger must be reachable |

---

## PayPal

| Field | Detail |
|-------|--------|
| Purpose | PayPal / Pay Later |
| Requirements | PayPal commerce plugin or WC PayPal Payments |
| Dependencies | WC checkout |
| Expected architecture | Plugin gateway; avoid custom PayPal SDK in theme |
| Difficulty | Low–Medium |
| Risks | Express checkout button placement conflicts with custom checkout layout |

---

## PixelPay

| Field | Detail |
|-------|--------|
| Purpose | Regional card processing (common in Central America) |
| Requirements | PixelPay merchant credentials, WC gateway plugin or custom gateway class |
| Dependencies | HTTPS, correct currency (NIO/USD as configured) |
| Expected architecture | `WC_Payment_Gateway` subclass in a dedicated plugin; CheckoutPage shows methods via WC |
| Difficulty | Medium–High (if no mature WC plugin) |
| Risks | PCI — never touch raw card data in theme; 3DS; settlement currencies |

---

## BAC Credomatic

| Field | Detail |
|-------|--------|
| Purpose | Bank/acquirer payments for Nicaragua-region merchants |
| Requirements | Merchant agreement, gateway docs, likely custom WC gateway or certified plugin |
| Dependencies | Same as PixelPay regarding PCI |
| Expected architecture | Isolated payment plugin; theme only renders WC payment box |
| Difficulty | High |
| Risks | Sparse documentation; certification; Hostinger outbound networking |

---

## Cargo Mobil (logistics)

| Field | Detail |
|-------|--------|
| Purpose | Dynamic shipping rates, labels, tracking, ETA |
| Requirements | Cargo Mobil API credentials, origin/destination rules, packing dimensions |
| Dependencies | WC Shipping zones/methods; product weight/dimensions; order hooks |
| Expected architecture | Custom `WC_Shipping_Method` + tracking field on orders; PDP estimates via filters `shanelle_product_shipping_estimate` / `shanelle_product_delivery_estimate`; account tracking UI later |
| Difficulty | High |
| Risks | API downtime; rate caching; address validation for Nicaragua; customer ETA trust |

**Theme hooks already useful:** shipping/returns info filters; PDP estimate filters; checkout shipping UI remains WC-native.

---

## Social Login (Google / Facebook / Instagram)

| Field | Detail |
|-------|--------|
| Purpose | Faster account creation / login |
| Requirements | OAuth apps, redirect URIs on production domain |
| Dependencies | My Account forms; user email uniqueness |
| Expected architecture | Established WP social login plugin or Nextend-style; theme styles buttons only |
| Difficulty | Medium |
| Risks | Email-less IG accounts; account merging; GDPR consent |

**Not implemented yet** in theme.

---

## PWA

| Field | Detail |
|-------|--------|
| Purpose | Installable storefront, offline shell, push later |
| Requirements | HTTPS, Web App Manifest, service worker, careful WC cart caching rules |
| Dependencies | Existing `shanelle/v1` REST search/grid as starting point; prefer WC Store API for cart |
| Expected architecture | Separate PWA assets (or plugin); do not cache checkout/account HTML aggressively |
| Difficulty | High |
| Risks | Stale cart; auth cookies; Hostinger HTTPS/HTTP2 |

REST stubs exist: `/wp-json/shanelle/v1/product-grid`, `/search`.

---

## Native Apps

| Field | Detail |
|-------|--------|
| Purpose | iOS/Android shopping apps |
| Requirements | Mobile team, WC REST or headless BFF, app auth |
| Dependencies | Product media quality; payment SDKs may differ from web |
| Expected architecture | App → BFF/WC REST → same database; theme remains web channel |
| Difficulty | High |
| Risks | Dual checkout rules; inventory races; feature parity |

---

## Suggested integration sequence

1. Deploy stable UI to Hostinger  
2. Payments (Stripe/PayPal and/or PixelPay/BAC)  
3. GA4 + Meta Pixel (+ TikTok Pixel)  
4. Cargo Mobil shipping method  
5. Social login  
6. Social shop feeds  
7. PWA  
8. Native apps  

---

## Related docs

- [CUSTOM_HOOKS.md](./CUSTOM_HOOKS.md)  
- [EVENTS.md](./EVENTS.md)  
- [SECURITY.md](./SECURITY.md)  
- [PROJECT_STATUS.md](./PROJECT_STATUS.md)  
