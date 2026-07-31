# Technical Debt

Known maintainability issues discovered during the codebase audit.  
**No fixes applied while writing this document.**

---

## Duplicate code / dual systems

| Item | Detail |
|------|--------|
| Docs drift | Homepage docs aligned to live composition (icons + rails + For You; no HeroBanner); watch for future drift |
| Duplicate checkout doc | `docs/pages/CHECKOUT.md` and `wp-content/docs/pages/CHECKOUT.md` (if present) |
| Legacy ARCHITECTURE.md | High-level tree only; superseded by `PROJECT_ARCHITECTURE.md` |

---

## Large files / complex logic

| File | Concern |
|------|---------|
| `inc/components/Homepage.php` | Very large Customizer + section builders |
| `inc/components/MyAccountPage.php` | Broad account surface |
| `inc/components/CartPage.php` | State, totals, AJAX, Customizer combined |
| `inc/components/ProductRelated.php` | Scoring engine complexity |
| `inc/components/MiniCart.php` | State + AJAX + markup helpers in one class |

---

## Temporary / incomplete UI

| Item | Evidence |
|------|----------|
| Product card wishlist / quick view | Disabled controls removed from card actions (quick-add only) |
| Header wishlist / language | Removed from header (Spanish-only storefront; no language switcher) |
| Variation gallery sync | Wired via `gallery-change` → ProductGallery (`setActiveIndex` by attachment id) |
| ProductDetail placeholder methods | Related/recently-viewed placeholders still in class while live page uses `ProductRelated` |
| Favourites | localStorage only; not synced to account |
| Shipping/delivery on PDP | Estimated copy from weekday math / filters — not live WC rates |
| Homepage Customizer drift | Inactive product-section Customizer labeled; live rails use collections or honest fallbacks |

---

## Hardcoded values

| Value | Location | Risk |
|-------|----------|------|
| Attribute role aliases still opinionated | `CatalogFilters::ATTRIBUTE_ROLE_CANDIDATES` | Unusual attribute names need `shanelle_catalog_filter_attribute_map` |
| Related default taxonomies | `pa_season`, `pa_occasion`, `pa_color-family` | May not exist in catalog |
| Shop per page 24 / columns 5 | `woocommerce.php` | Global opinion |
| Nicaragua-oriented shipping copy | `woocommerce.php` defaults | Localization assumptions |
| Promo max price default `1000` | `Homepage::PROMO_MAX_PRICE_DEFAULT` | Overridable via Customizer + `shanelle_homepage_promo_max_price` filter |

---

## Architectural risks

1. Heavy WooCommerce hook removal → upgrade fragility on major WC releases.  
2. ES module loading previously broke without `script_loader_tag` guard.  
3. Public REST + expanded future endpoints without auth design.  
4. Integrations temptation to edit composers instead of filters.  
5. No CI / PHPCS gate in repository.  
6. No theme build pipeline for CSS/JS.  

---

## Maintainability concerns

- Manual class loading will worsen as integrations land.  
- Mixing page composers with domain services in `inc/components/`.  
- Catalog module is well-separated — good pattern to copy for integrations.  
- Event documentation helps, but not all modules document extension points equally.  

---

## Suggested debt paydown order (when approved)

1. Sync homepage docs and dead composer methods.  
2. Introduce minimal asset build for production.  
3. Implement adapters under `inc/integrations/` only when plugins are insufficient.  
4. Optionally delete duplicate `wp-content/docs/` if still present.  

---

## Related docs

- [SECURITY.md](./SECURITY.md)  
- [PERFORMANCE.md](./PERFORMANCE.md)  
- [PROJECT_STATUS.md](./PROJECT_STATUS.md)  
