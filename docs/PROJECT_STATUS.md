# Project Status

Snapshot of Shanelle Store based on the current codebase (UI development phase).  
Update this file as milestones complete.

---

## Completed

| Area | Evidence |
|------|----------|
| Custom theme bootstrap | `functions.php`, `inc/*` |
| Design tokens + shared UI CSS | `assets/css/` |
| Component architecture | `inc/components` + `components/` |
| Homepage composer (current markup) | Category icons, featured rails (4/product), For You (default 12); HeroBanner not composed |
| Shop archive + catalog filters | `ShopArchive`, `CatalogFilters` (query merge, search filters, chips, load more) |
| Collections taxonomy + admin + pages | `inc/catalog/*`, collections templates |
| PDP composers | Gallery, summary, variations, purchase, information, reviews, related |
| Mini cart | Footer drawer + fragments |
| Cart page | Themed WC overrides |
| Checkout page | Themed WC overrides |
| My Account theming | Extensive `myaccount` overrides |
| Search overlay + search page | SearchController / Overlay / Page |
| Product grid AJAX + REST stubs | `shanelle/v1` |
| Header / footer / category navbar | Present — desktop hover mega-menu (sidebar, shop-by, picks, CTA strip) |
| Homepage hero removed | Live front page starts with category icons; `HeroBanner` retained but inactive |
| Wave B header polish | Stadium-style multi-promo banner (Customizer, up to 6 slots, marquee on overflow); wishlist/language stubs removed; drawer a11y + fallbacks |
| Spanish-only theme UI | Customer-facing `shanelle` strings use LATAM Spanish source copy |
| Wave C catalog polish | Sale color tokens, archive title scale, shared empty-state CSS |
| Wave D PDP polish | Sticky mobile ATC, honest shipping estimates, info defaults, review form; luxury spacing/chrome pass (unboxed purchase/reviews, editorial price, quieter gallery); SizeGuide modal on size row |
| Wave E homepage/footer honesty | Real collections rails, Customizer sync, newsletter via provider embed code, SVG payment marks |
| Wave F consistency polish | Unified grid density, navbar prev, empty catalog band, focus/error tokens |
| Storefront heroes removed | About / InfoPage / Collection full-bleed heroes retired; compact titles only; homepage HeroBanner remains inactive |
| Informational / policy pages | `InfoPage` templates + Spanish starter content seeded into WP (shipping, returns, privacy, terms, cookies, FAQ, CS pages) |
| Internal docs (events, pages, components) | `docs/` supplementary files |
| ES module script tag guard | `inc/assets.php` |
| Cart AJAX nonce hardening | MiniCart + CartPage WC-AJAX verify `shanelle_cart_ajax`; JS sends localized nonce |
| Homepage promo max price | Customizer + WC currency label + `shanelle_filter_max_price` shop link (no hard-coded `C$`) |
| Footer payment marks | Defaults include PayPal + Pagadito + Fygaro (align Customizer list to enabled gateways) |
| Self-hosted fonts | DM Sans + Cormorant Garamond woff2 via `assets/css/fonts.css` (no Google Fonts CDN) |
| Integrations skeleton | `inc/integrations/Integrations.php` + [INTEGRATIONS.md](./INTEGRATIONS.md); no vendor adapters yet |
| Theme class autoload | `inc/autoload.php` PSR-4-style loader; `functions.php` slimmed to procedural requires + boot list |
| Legacy template-parts cleanup | Removed unused product-card / hero-banner / site-footer / category-chips / section-heading partials |

---

## Language (storefront)

| Rule | Detail |
|------|--------|
| Theme UI language | Latin American Spanish only (source strings in theme PHP/JS) |
| No language switcher | Header language control removed |
| WooCommerce / WordPress core | Set **Ajustes → Generales → Idioma del sitio** to **Español** and install the language pack so checkout fields, emails, and WC templates translate |
| Catalog content | Product titles, menus, and pages remain whatever is entered in admin (not auto-translated) |

---

## In progress

| Area | Notes |
|------|-------|
| Visual polish of fashion UI | Active development on FlyEnv |
| Homepage composition alignment | Live composition documented; inactive Customizer product sections labeled; featured rails taxonomy-aware |
| Favourites / wishlist UX | Card + PDP localStorage favourites; header wishlist removed; server-side wishlist not started |
| Documentation set | This audit documentation supersedes older high-level sketches |

---

## Not started

| Area | Notes |
|------|-------|
| Payment gateway **merchant config** (live credentials, webhooks, certification) | Plugins installed (PayPal Payments, Pagadito, Fygaro); theme stays gateway-agnostic — ops must enable and test |
| Stripe / PixelPay / BAC Credomatic | Not installed yet |
| Cargo Mobil logistics | Not implemented yet |
| Theme-owned Meta / TikTok / GA pixel modules | Prefer installed plugins; `inc/integrations` skeleton ready — adapters not started |
| Instagram / TikTok Shop sync beyond plugin connectors | Not implemented yet |
| Social login | Not implemented yet |
| PWA service worker / manifest | Not implemented yet |
| Native mobile apps | Not implemented yet |
| Theme Composer autoload / CI | Autoload shipped (`inc/autoload.php`); Composer/CI not started |
| Asset build pipeline | Not implemented yet |
| Server-side wishlist | Not implemented yet |
| Recently viewed products (live) | Placeholder remnants only |

---

## Blocked

| Item | Blocker |
|------|---------|
| Production integrations | Explicitly deferred until UI finalize + Hostinger deploy |
| Accurate live shipping ETAs | Needs logistics API partnership / credentials |
| Regional payment certification | Needs merchant accounts |

Nothing in the theme codebase appears blocked by a hard technical dependency today beyond WooCommerce itself.

---

## Future work

Prioritized after UI approval:

1. Deploy to Hostinger ([DEPLOYMENT.md](./DEPLOYMENT.md))  
2. Configure and E2E-test payment gateways (plugins already present)  
3. Analytics / ads plugin configuration (avoid double-firing)  
4. Shipping integration (Cargo Mobil or WC zones)  
5. Social login + social commerce  
6. PWA → apps  
7. Autoload + asset build pipeline → **autoload done** (`inc/autoload.php`); asset build still not started  
8. Migrate header markup into `components/header/`  

See [FUTURE_INTEGRATIONS.md](./FUTURE_INTEGRATIONS.md).

---

## Status legend for planning

| Label | Meaning |
|-------|---------|
| Completed | Implemented and wired in theme |
| In Progress | Partially implemented or actively changing |
| Not Started | Absent from codebase |
| Blocked | Waiting on external decision or credential |
| Future Work | Planned after current phase |

---

## Related docs

- [PROJECT_ARCHITECTURE.md](./PROJECT_ARCHITECTURE.md)  
- [TECH_DEBT.md](./TECH_DEBT.md)  
- [FUTURE_INTEGRATIONS.md](./FUTURE_INTEGRATIONS.md)  
