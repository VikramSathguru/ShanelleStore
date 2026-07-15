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
| Homepage composer (current markup) | Category icons, featured collections, For You grid |
| Shop archive + catalog filters | `ShopArchive`, `CatalogFilters` |
| Collections taxonomy + admin + pages | `inc/catalog/*`, collections templates |
| PDP composers | Gallery, summary, variations, purchase, information, reviews, related |
| Mini cart | Footer drawer + fragments |
| Cart page | Themed WC overrides |
| Checkout page | Themed WC overrides |
| My Account theming | Extensive `myaccount` overrides |
| Search overlay + search page | SearchController / Overlay / Page |
| Product grid AJAX + REST stubs | `shanelle/v1` |
| Header / footer / category navbar | Present |
| Homepage hero composed | `Homepage::render_hero()` first on front page |
| Wave B header polish | Promo bar visible; wishlist/language stubs removed; drawer a11y + fallbacks |
| Spanish-only theme UI | Customer-facing `shanelle` strings use LATAM Spanish source copy |
| Wave C catalog polish | Sale color tokens, archive title scale, shared empty-state CSS |
| Wave D PDP polish | Sticky mobile ATC, honest shipping estimates, info defaults, review form |
| Internal docs (events, pages, components) | `docs/` supplementary files |
| ES module script tag guard | `inc/assets.php` |

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
| Homepage composition alignment | Hero is live; Category Navigation / product-section Customizer still unused by template |
| Favourites / wishlist UX | PDP localStorage only; header wishlist removed |
| Documentation set | This audit documentation supersedes older high-level sketches |

---

## Not started

| Area | Notes |
|------|-------|
| Payment gateways (Stripe, PayPal, PixelPay, BAC) | Not implemented yet |
| Cargo Mobil logistics | Not implemented yet |
| Meta / TikTok / GA pixels | Not implemented yet |
| Instagram / TikTok Shop sync | Not implemented yet |
| Social login | Not implemented yet |
| PWA service worker / manifest | Not implemented yet |
| Native mobile apps | Not implemented yet |
| Theme Composer autoload / CI | Not implemented yet |
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

1. Security harden cart AJAX nonces  
2. Deploy to Hostinger ([DEPLOYMENT.md](./DEPLOYMENT.md))  
3. Payments  
4. Analytics pixels  
5. Shipping integration  
6. Social login + social commerce  
7. PWA → apps  

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
