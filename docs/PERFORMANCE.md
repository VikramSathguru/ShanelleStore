# Performance

Assessment of the Shanelle theme as implemented on FlyEnv. Measurements should be re-run on Hostinger with production cache.

---

## Assets

| Concern | Current state | Risk |
|---------|---------------|------|
| Global CSS | `main.css` uses multiple `@import`s | Extra CSS requests / parse cost |
| Feature CSS | Many enqueued handles | Acceptable when page-gated; MiniCart/Search are sitewide |
| JS strategy | `defer` + ES modules | Good; module graph per page |
| No bundler | No Vite/Webpack in theme | No tree-shaking / minify pipeline |
| Versioning | `SHANELLE_VERSION` constant | Cache-bust on version bump |

---

## CSS

**Good**

- Design tokens keep consistency without huge utility explosions.  
- Component CSS scoped by BEM-like class prefixes.  

**Risks**

- Native `@import` in `main.css` is slower than a concatenated build.  
- Duplicate/overlapping rules possible between design-system and feature files.  

**Opportunities**

- Production concat/minify of `assets/css`.  
- Critical CSS for header + LCP.  
- Purge unused utilities if a build step is introduced.

---

## JavaScript

**Good**

- Modules initialize only their `[data-shanelle-*]` roots.  
- Heavy PDP scripts generally enqueued on product pages.  

**Risks**

- MiniCart + SearchOverlay JS/CSS load on essentially all storefront pages.  
- Multiple independent modules → many HTTP requests without HTTP/2 prioritization discipline.  

**Opportunities**

- Conditional enqueue when drawer/search unused (rare for fashion UX).  
- Bundle shared helpers if a build tool is added.  
- Prefer WC Store API later for PWA instead of growing admin-ajax.

---

## Images

**Good**

- Custom sizes: product card, gallery main/thumb, hero, collection sizes.  
- Lazy loading patterns on thumbs / responsive helper `shanelle_responsive_image()`.  
- HeroBanner can preload LCP (`wp_head`).  

**Risks**

- Large homepage “For You” grids (Customizer default up to 50).  
- Related products candidate pool default 120.  
- Unoptimized uploads still hurt — editorial process matters.  

**Opportunities**

- WebP/AVIF via Hostinger / optimization plugin.  
- Enforce max upload dimensions.  
- Responsive `srcset` already used in gallery — extend elsewhere.

---

## Fonts

- Google Fonts loaded remotely (`shanelle-fonts`).  
- **Risks:** extra DNS/TLS, GDPR/privacy considerations, FOIT/FOUT.  
- **Opportunities:** self-host on Hostinger, `font-display: swap` (verify in Google CSS URL — `display=swap` is present).

---

## Caching opportunities

| Layer | Local FlyEnv | Hostinger prod recommendation |
|-------|--------------|-------------------------------|
| Page cache | Usually off under WP_DEBUG | Enable (LiteSpeed/Hostinger cache) with WC exclusions for cart/checkout/account |
| Object cache | Not configured in theme | Redis/Memcached if available |
| Opcode | PHP OPcache | Enable in production |
| CDN | Not implemented yet | Optional for media/static |
| Browser cache | Via host headers | Set long cache for hashed assets |

Theme should keep cart/checkout cookies compatible with any full-page cache plugin (standard WC rule).

---

## Lazy loading

| Feature | Status |
|---------|--------|
| Image `loading="lazy"` | Used in helpers / cards / thumbs |
| Gallery thumb IntersectionObserver | Implemented |
| JS defer | Implemented |
| Below-fold homepage sections | Relies on product grid; no intersection “section hydrator” beyond grid AJAX |

**Not implemented yet:** Infinite scroll for entire homepage Feed beyond product-grid load-more patterns.

---

## Query optimization

| Pattern | Notes |
|---------|-------|
| Shop filters | Mutate main `woocommerce_product_query` — good |
| Homepage sections | Multiple queries possible (icons + collections + For You + Customizer sections) |
| Related scoring | Loads candidate pool then scores in PHP — can be expensive |
| Search suggest | Limits product/term counts via Customizer — keep limits low |
| Seed / term meta | Fine for admin |

**Opportunities:** transient cache for homepage featured collections; reduce related candidate pool; ensure product meta lookup tables healthy (WC).

---

## Performance risks (priority)

1. Large homepage product counts.  
2. Related products uncached scoring.  
3. CSS `@import` + many stylesheets on every PDP.  
4. Sitewide MiniCart/Search assets.  
5. Remote fonts.  
6. Debug mode on — `WP_DEBUG` true in local `wp-config.php` must be **false** on Hostinger.

---

## Related docs

- [DEPLOYMENT.md](./DEPLOYMENT.md)  
- [TECH_DEBT.md](./TECH_DEBT.md)  
