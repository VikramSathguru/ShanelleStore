# UI Components

Inventory of reusable UI / feature components in the Shanelle theme.  
Controllers live under `wp-content/themes/shanelle/inc/components/` unless noted.

**Legend — Reusable:** Yes = designed for reuse across pages; Page = page composer; Partial = sub-view.

---

## Core commerce

### ProductCard

| Field | Detail |
|-------|--------|
| Purpose | Product tile (image, badges, price, quick actions) |
| Location | `inc/components/ProductCard.php`, `components/product-card/` |
| Dependencies | WooCommerce product, `ProductPrice`, design-system buttons |
| Used by | ProductGrid, shop loops, related, homepage grids, cross-sells |
| Reusable | Yes |
| Needs improvement | Wishlist/quick view still “coming soon” placeholders |

### ProductGrid

| Field | Detail |
|-------|--------|
| Purpose | Product listing layout, pagination / load-more, AJAX + REST |
| Location | `inc/components/ProductGrid.php`, `components/product-grid/` |
| Dependencies | ProductCard, WP_Query |
| Used by | ShopArchive, Homepage, CollectionPage, SearchPage, Cart cross-sells, ProductRelated |
| Reusable | Yes |
| Needs improvement | Dual AJAX+REST surface; ensure query sanitization stays tight |

### ProductPrice

| Field | Detail |
|-------|--------|
| Purpose | Normalized display price HTML / sale badge labels |
| Location | `inc/woocommerce/ProductPrice.php` |
| Dependencies | WooCommerce pricing APIs |
| Used by | ProductCard, ProductSummary, MiniCart, variations JSON |
| Reusable | Yes |
| Needs improvement | None critical |

### ProductGallery

| Field | Detail |
|-------|--------|
| Purpose | PDP gallery, thumbs, zoom, fullscreen modal |
| Location | `inc/components/ProductGallery.php`, `components/product-gallery/` |
| Dependencies | Product attachment IDs, custom image sizes |
| Used by | ProductDetail |
| Reusable | Yes (intended for PWA contexts) |
| Needs improvement | Variation image sync incomplete |

### ProductSummary

| Field | Detail |
|-------|--------|
| Purpose | Title, brand, rating, price, stock, short description, highlights |
| Location | `inc/components/ProductSummary.php`, `components/product-summary/` |
| Dependencies | ProductPrice, WC rating |
| Used by | ProductDetail |
| Reusable | Yes |
| Needs improvement | Brand/highlights omit when empty; supply via attributes or filters |

### ProductVariations

| Field | Detail |
|-------|--------|
| Purpose | Attribute selectors / swatches for variable products |
| Location | `inc/components/ProductVariations.php`, `components/product-variations/` |
| Dependencies | WC variable product, variation JSON |
| Used by | ProductDetail |
| Reusable | Yes |
| Needs improvement | Gallery sync “coming soon” |

### ProductPurchase

| Field | Detail |
|-------|--------|
| Purpose | Qty stepper, Add to Cart, Buy Now, favourites, shipping/delivery UI, trust badges |
| Location | `inc/components/ProductPurchase.php`, `components/product-purchase/` |
| Dependencies | WC AJAX add_to_cart, form context |
| Used by | ProductDetail |
| Reusable | Yes |
| Needs improvement | Favourites are localStorage-only; shipping dates are estimate copy not live rates |

### ProductInformation

| Field | Detail |
|-------|--------|
| Purpose | Accordion: description, specs, size/care/shipping/returns (filter-fed) |
| Location | `inc/components/ProductInformation.php`, `components/product-information/` |
| Dependencies | Product description/attributes; `shanelle_product_information_*` filters |
| Used by | ProductDetail |
| Reusable | Yes |
| Needs improvement | Size guide / care empty unless filters provide content |

### ProductDetail

| Field | Detail |
|-------|--------|
| Purpose | PDP page composer |
| Location | `inc/components/ProductDetail.php`, `components/product-detail/` |
| Dependencies | Gallery, Summary, Variations, Purchase, Information, Related, reviews partial |
| Used by | `woocommerce/single-product.php` |
| Reusable | Page |
| Needs improvement | Dead placeholder methods for related/recently-viewed remain in class |

### ProductRelated

| Field | Detail |
|-------|--------|
| Purpose | Scored “customers also viewed” recommendations |
| Location | `inc/components/ProductRelated.php`, `components/product-related/` |
| Dependencies | ProductGrid, catalog helpers, attribute taxonomies |
| Used by | ProductDetail |
| Reusable | Yes |
| Needs improvement | Candidate pool can be heavy; taxonomy assumptions |

---

## Cart & checkout

### MiniCart

| Field | Detail |
|-------|--------|
| Purpose | Slide-over bag; fragments; qty update AJAX |
| Location | `inc/components/MiniCart.php`, `components/mini-cart/` |
| Dependencies | WC cart session, ProductPrice |
| Used by | Global footer; state reused by CartPage & CheckoutPage |
| Reusable | Yes |
| Needs improvement | WC AJAX handlers lack theme nonces (see SECURITY.md) |

### CartPage

| Field | Detail |
|-------|--------|
| Purpose | Full cart page composer |
| Location | `inc/components/CartPage.php`, `components/cart-page/` |
| Dependencies | MiniCart state, ProductGrid, WC coupon/shipping |
| Used by | `woocommerce/cart/*` |
| Reusable | Page |
| Needs improvement | Large controller file |

### CheckoutPage

| Field | Detail |
|-------|--------|
| Purpose | Themed checkout layout + order review |
| Location | `inc/components/CheckoutPage.php`, `components/checkout-page/` |
| Dependencies | WC checkout, MiniCart/CartPage totals helpers |
| Used by | `woocommerce/checkout/form-checkout.php` |
| Reusable | Page |
| Needs improvement | Payment UX depends on gateway plugins (none custom yet) |

### MyAccountPage

| Field | Detail |
|-------|--------|
| Purpose | Account shell + endpoint theming |
| Location | `inc/components/MyAccountPage.php`, `components/my-account-page/` (+ many partials) |
| Dependencies | WC account endpoints |
| Used by | `woocommerce/myaccount/*` |
| Reusable | Page |
| Needs improvement | Large surface area; social login not present |

---

## Catalog & discovery

### ShopArchive

| Field | Detail |
|-------|--------|
| Purpose | Shop / category / shared PLP chrome |
| Location | `inc/components/ShopArchive.php`, `components/shop-archive/` |
| Dependencies | CatalogFilters, ProductGrid |
| Used by | `woocommerce/archive-product.php`, CollectionPage, SearchPage |
| Reusable | Page |
| Needs improvement | — |

### CatalogFilters

| Field | Detail |
|-------|--------|
| Purpose | Sidebar/sheet filter UI + product query mutation + active chips |
| Location | `inc/components/CatalogFilters.php`, `components/catalog-filters/` |
| Dependencies | Attribute taxonomies, `woocommerce_product_query`, search `pre_get_posts` |
| Used by | ShopArchive (desktop + mobile; sole PLP filter source) |
| Reusable | Yes |
| Needs improvement | — |

### CollectionCard

| Field | Detail |
|-------|--------|
| Purpose | Collection term card |
| Location | `inc/components/CollectionCard.php`, `components/collection-card/` |
| Dependencies | `product_collection` term meta |
| Used by | CollectionsPage, Homepage featured collections |
| Reusable | Yes |
| Needs improvement | — |

### CollectionsPage

| Field | Detail |
|-------|--------|
| Purpose | Collections index |
| Location | `inc/components/CollectionsPage.php`, `components/collections-page/` |
| Dependencies | Catalog Queries, CollectionCard |
| Used by | `page-templates/collections.php` |
| Reusable | Page |
| Needs improvement | — |

### CollectionPage

| Field | Detail |
|-------|--------|
| Purpose | Single collection archive composer |
| Location | `inc/components/CollectionPage.php`, `components/collection-page/` |
| Dependencies | ProductGrid, Catalog taxonomy |
| Used by | Collection taxonomy templates |
| Reusable | Page |
| Needs improvement | — |

### SearchOverlay

| Field | Detail |
|-------|--------|
| Purpose | Global live search overlay |
| Location | `inc/components/SearchOverlay.php`, `components/search-overlay/` |
| Dependencies | SearchController, SearchResults |
| Used by | `wp_footer` sitewide |
| Reusable | Yes |
| Needs improvement | Always loaded; consider conditional enqueue if needed |

### SearchController

| Field | Detail |
|-------|--------|
| Purpose | AJAX/REST search orchestration (not a visual component) |
| Location | `inc/components/SearchController.php` |
| Dependencies | WC product query, categories, collections |
| Used by | SearchOverlay |
| Reusable | Yes |
| Needs improvement | — |

### SearchResults

| Field | Detail |
|-------|--------|
| Purpose | Suggestion results markup |
| Location | `inc/components/SearchResults.php`, `components/search-results/` |
| Dependencies | Search payload |
| Used by | SearchController |
| Reusable | Yes |
| Needs improvement | — |

### SearchPage

| Field | Detail |
|-------|--------|
| Purpose | Full product search results page |
| Location | `inc/components/SearchPage.php`, `components/search-page/` |
| Dependencies | Shop-style grid args filters |
| Used by | `search.php` |
| Reusable | Page |
| Needs improvement | — |

---

## Homepage & marketing

### Homepage

| Field | Detail |
|-------|--------|
| Purpose | Homepage composer |
| Location | `inc/components/Homepage.php`, `components/homepage/` + partials |
| Dependencies | HeroBanner, Catalog collections, ProductGrid, Customizer |
| Used by | `front-page.php` |
| Reusable | Page |
| Needs improvement | Soften remaining P2 polish (load-more styling, “Ver todo” text) |

### HeroBanner

| Field | Detail |
|-------|--------|
| Purpose | Full-bleed homepage hero / LCP preload |
| Location | `inc/components/HeroBanner.php`, `components/hero-banner/` |
| Dependencies | Customizer |
| Used by | Live homepage via `Homepage::render_hero()` → `shanelle_hero_banner()` |
| Reusable | Yes |
| Needs improvement | — |

### CategoryNavigation

| Field | Detail |
|-------|--------|
| Purpose | Optional richer category block (not on live homepage) |
| Location | `inc/components/CategoryNavigation.php`, `components/category-navigation/` |
| Dependencies | product_cat, Customizer |
| Used by | Manual `shanelle_category_navigation()` only; live homepage uses category icon grid |
| Reusable | Yes |
| Needs improvement | Customizer section labeled inactive to avoid merchant confusion |

### CategoryNavbar

| Field | Detail |
|-------|--------|
| Purpose | Sticky/header category strip |
| Location | `inc/components/CategoryNavbar.php`, `components/category-navbar/` |
| Dependencies | Menu / product categories |
| Used by | Site header |
| Reusable | Yes |
| Needs improvement | — |

---

## Layout shell

### Footer

| Field | Detail |
|-------|--------|
| Purpose | Site footer composition (brand, menus, contact, optional newsletter) |
| Location | `inc/components/Footer.php`, `components/footer/` |
| Dependencies | Menus, Customizer (contact, social, payments, scroll-to-top; newsletter off by default) |
| Used by | `footer.php` |
| Reusable | Yes |
| Needs improvement | — |

### Site header (template-part)

| Field | Detail |
|-------|--------|
| Purpose | Global header chrome |
| Location | `template-parts/components/site-header.php` + `assets/css/components/site-header.css` + `inc/components/SiteHeader.php` |
| Dependencies | SiteHeader Customizer, SearchOverlay, cart count, CategoryNavbar |
| Used by | `header.php` |
| Reusable | Yes |
| Needs improvement | Markup not yet migrated into `components/header/` package |

### SiteHeader

| Field | Detail |
|-------|--------|
| Purpose | Header Customizer (promo strip, contact URL) + presentation helpers |
| Location | `inc/components/SiteHeader.php` |
| Dependencies | Theme Customizer, WP pages for contact fallback |
| Used by | `site-header.php` |
| Reusable | Yes |
| Needs improvement | — |

---

## Catalog module (admin + data, not storefront UI)

| Unit | Path | Purpose |
|------|------|---------|
| Catalog | `inc/catalog/Catalog.php` | Boot |
| Collections | `inc/catalog/Collections.php` | Taxonomy register |
| Admin | `inc/catalog/Admin.php` | Term meta UI |
| Queries | `inc/catalog/Queries.php` | Collection queries |
| Seeder | `inc/catalog/Seeder.php` | Theme-switch seed |
| Helpers | `inc/catalog/Helpers.php` | Constants/sanitize |

---

## Design-system primitives (CSS)

Not PHP components, but reusable across UI:

- Buttons, forms, badges, chips, cards, modals — `assets/css/components/`
- Tokens — `assets/css/base/variables.css`

---

## Supplementary deep-dives

Component write-ups already exist under `docs/components/` and `docs/pages/` — see [COMPONENTS.md](./COMPONENTS.md).
