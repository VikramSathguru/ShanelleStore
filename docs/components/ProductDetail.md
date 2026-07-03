# ProductDetail

## Purpose

Orchestrates the single product page (PDP) by composing existing product components into a responsive layout. Does not duplicate component business logic—it only handles page structure, WooCommerce form wrappers, and below-the-fold placeholders.

## Responsibilities

- Compose ProductGallery, ProductSummary, ProductVariations, and ProductPurchase
- Provide desktop two-column and mobile stacked layout via CSS Grid
- Open/close WooCommerce cart or variation forms with required hidden fields
- Disable default WooCommerce single product template output
- Render breadcrumbs via `woocommerce_breadcrumb()`
- Manage below-the-fold sections (information, reviews, related, recently viewed)
- Fire WooCommerce before/after single product actions
- Enqueue product detail layout CSS/JS

## Public API

### PHP class

`Shanelle\Components\ProductDetail`

| Method | Description |
|--------|-------------|
| `boot()` | Registers assets and single-product hook cleanup |
| `render( array $args = [] )` | Renders full PDP (uses global `$product`) |
| `render_breadcrumbs()` | WooCommerce breadcrumbs |
| `render_hero()` | Gallery + commerce grid |
| `render_gallery()` | Delegates to ProductGallery |
| `render_commerce_stack()` | Summary, variations, purchase |
| `render_form_open()` / `render_form_close()` | WooCommerce form wrapper |
| `render_reviews_section()` | Reviews placeholder |
| `render_recently_viewed_section()` | Recently viewed placeholder |
| `get_detail_json()` | PWA hydration JSON |
| `get_root_id()` | Page root ID |

### Theme helper

```php
shanelle_product_detail( array $args = [] );
```

### WooCommerce template

`woocommerce/single-product.php` calls `ProductDetail::render()` inside the WordPress loop.

### Render arguments

| Argument | Default | Description |
|----------|---------|-------------|
| `detail_id` | Auto-generated | Unique PDP instance ID |

### JavaScript exports

From `components/product-detail/product-detail.js`:

- `initProductDetail( detail )`
- `getDetailData( detail )`
- `getHydrationTargets( detail )`
- `toggleAccordionPanel( trigger )` (legacy placeholder accordion)

## Dependencies

- ProductGallery
- ProductSummary
- ProductVariations
- ProductPurchase
- ProductInformation (wired in template)
- ProductRelated (wired in template)
- Design System layout utilities
- WooCommerce single product context

## Events

| Event | Source | Payload |
|-------|--------|---------|
| `shanelle:product-detail:ready` | Init complete | `{ detail, data, sections, hydrationTargets }` |
| `shanelle:product-detail:accordion-toggle` | Legacy placeholder accordion | `{ trigger, panel, expanded }` |

Child components dispatch their own events independently.

## Extension points

- Replace placeholder sections (reviews, recently viewed) with future components
- Listen for `shanelle:product-detail:ready` to hydrate PWA sections
- Hook `woocommerce_before_single_product` / `woocommerce_after_single_product` for plugins
- Customize below-the-fold composition in `components/product-detail/product-detail.php`

## Filters

This component does not register custom filters.

## Actions

### Dispatched

| Action | Description |
|--------|-------------|
| `woocommerce_before_single_product` | Fired before layout render |
| `woocommerce_after_single_product` | Fired after layout render |

### Removed (on single product pages)

Default WooCommerce summary, gallery, tabs, upsells, and related product output hooks are removed to prevent duplication.

## Example usage

The PDP is rendered automatically via the WooCommerce template:

```php
// woocommerce/single-product.php
while ( have_posts() ) {
	the_post();
	\Shanelle\Components\ProductDetail::render();
}
```

Manual render in a custom template:

```php
global $product;

if ( $product instanceof WC_Product ) {
	shanelle_product_detail();
}
```

## Known limitations

- Reviews and recently viewed sections are placeholders
- Legacy methods `render_information_section()` and `render_related_section()` still exist in the PHP class but are no longer used by the active template
- Variable product form uses `get_available_variations()` which can be expensive on large catalogs
- Does not implement sticky mobile add-to-bar or structured product schema beyond WooCommerce defaults
- Product detail accordion JS targets legacy placeholder markup only (superseded by ProductInformation)
