# ProductSummary

## Purpose

Renders the primary product summary block on the single product page: title, meta, price, stock, short description, and placeholder highlights. Designed for reuse on the PDP and future PWA product views.

## Responsibilities

- Render product title, brand placeholder, SKU, and rating
- Output structured price block (current, regular, savings) via `ProductPrice`
- Display stock status (in stock, low stock, backorder, out of stock)
- Render WooCommerce short description
- Extend variation JSON with formatted price fields for client sync
- Enqueue summary CSS/JS on product pages or on demand

## Public API

### PHP class

`Shanelle\Components\ProductSummary`

| Method | Description |
|--------|-------------|
| `boot()` | Registers assets and variation data filter |
| `render( WC_Product $product, array $args = [] )` | Renders full summary |
| `render_brand()` | Brand placeholder |
| `render_title()` | Product `<h1>` |
| `render_meta()` | SKU and rating |
| `render_price()` | Structured price block |
| `render_stock()` | Stock availability |
| `render_short_description()` | Short description |
| `render_highlights()` | Highlights placeholder list |
| `get_price_json()` | Price hydration array |
| `get_summary_id()` | Root region ID |

### Theme helper

```php
shanelle_product_summary( WC_Product $product, array $args = [] );
```

### Render arguments

| Argument | Default | Description |
|----------|---------|-------------|
| `summary_id` | Auto-generated | Unique summary instance ID |

### JavaScript exports

From `components/product-summary/product-summary.js`:

- `initSummary( summary )`
- `applyPriceState( summary, price )`
- `updateFromVariation( summary, variation )`

## Dependencies

- Design System (typography, badges)
- WooCommerce product APIs
- `Shanelle\WooCommerce\ProductPrice`
- WooCommerce variation script (jQuery `found_variation` / `reset_data`) for variable products

## Events

| Event | Source | Payload |
|-------|--------|---------|
| `shanelle:product-summary:ready` | Init complete | `{ summary }` |
| `shanelle:product-summary:price-change` | Variation price updated | `{ summary, price, variation }` |

## Extension points

- Replace brand placeholder with taxonomy or ACF-driven brand later
- Populate highlights list from custom fields
- Listen for price-change events in analytics or PWA state
- Filter `ProductPrice` data for consistent price formatting

## Filters

| Filter | Description |
|--------|-------------|
| `shanelle_product_price_data` | Modify normalized price data (via `ProductPrice`) |
| `woocommerce_short_description` | WordPress/WooCommerce short description filter |

### Variation JSON extensions

Added via `woocommerce_available_variation`:

- `shanelle_is_on_sale`
- `shanelle_current_html`
- `shanelle_regular_html`
- `shanelle_savings_html`

## Actions

This component does not register custom WordPress actions.

## Example usage

```php
global $product;

if ( $product instanceof WC_Product ) {
	shanelle_product_summary( $product );
}
```

Inside the product detail commerce column (within cart form):

```php
ProductSummary::render( $product );
ProductVariations::render( $product ); // variable only
ProductPurchase::render( $product );
```

## Known limitations

- Brand and product highlights are placeholders
- Variable products show price range initially; per-variation price requires variation selection
- Rating links to `#reviews` before a reviews component exists
- Price sync depends on jQuery variation events when WooCommerce variation script is present
- Does not render add-to-cart controls (delegated to ProductPurchase)
