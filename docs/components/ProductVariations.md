# ProductVariations

## Purpose

Provides custom variation selectors (color swatches, size chips) for WooCommerce variable products. Syncs with native WooCommerce variation matching via hidden `<select>` elements—no duplicated variation resolution logic.

## Responsibilities

- Render color swatches and size/option chip selectors
- Output hidden native selects via `wc_dropdown_variation_attribute_options()`
- Sync disabled/unavailable options from WooCommerce variation script
- Display per-variation availability when a match is found
- Extend variation JSON with stock and gallery sync fields
- Provide reset control and gallery sync placeholder
- Enqueue `wc-add-to-cart-variation` and component assets

## Public API

### PHP class

`Shanelle\Components\ProductVariations`

| Method | Description |
|--------|-------------|
| `boot()` | Registers assets and variation data filter |
| `render( WC_Product $product, array $args = [] )` | Renders selectors (variable products only) |
| `render_attribute_groups()` | Renders all attribute groups |
| `render_native_selects()` | Renders hidden WooCommerce selects |
| `render_reset()` | Clear selections button |
| `render_availability()` | Selected variation stock display |
| `get_root_id()` | Component root ID |

### Theme helper

```php
shanelle_product_variations( WC_Product $product, array $args = [] );
```

### Render arguments

| Argument | Default | Description |
|----------|---------|-------------|
| `variations_id` | Auto-generated | Unique instance ID |

### JavaScript exports

From `components/product-variations/product-variations.js`:

- `initVariations( root )`
- `selectOption( root, attribute, value, label )`
- `clearSelections( root )`
- `syncSelectedState( root )`
- `syncDisabledState( root )`
- `applyAvailability( root, variation )`
- `dispatchVariationEvents( root, variation )`
- `findVariationsForm( root )`

## Dependencies

- Design System (chips, buttons)
- WooCommerce variable product APIs
- `wc-add-to-cart-variation` script (jQuery)
- Parent `form.variations_form` with `data-product_variations` (provided by ProductDetail)
- `Shanelle\WooCommerce\ProductPrice` (variation price extensions)

## Events

| Event | Source | Payload |
|-------|--------|---------|
| `shanelle:product-variations:ready` | Init complete | `{ root, form }` |
| `shanelle:product-variations:change` | Variation found or cleared | `{ root, variation, form }` |
| `shanelle:product-variations:stock-change` | Stock status updated | `{ root, variation, stockStatus, stockLabel }` |
| `shanelle:product-variations:gallery-change` | Gallery sync placeholder | `{ root, variation, imageId }` |
| `shanelle:product-variations:price-change` | Price data available | `{ root, variation, price }` |

Also listens to WooCommerce jQuery events: `found_variation`, `reset_data`, `woocommerce_update_variation_values`.

## Extension points

- Map custom attribute taxonomies to swatch or chip UI via filters
- Provide swatch hex colors through term meta or `shanelle_variation_swatch_color`
- Listen for variation events in ProductPurchase, ProductSummary, and future gallery sync
- Replace gallery sync placeholder with ProductGallery integration

## Filters

| Filter | Default | Description |
|--------|---------|-------------|
| `shanelle_variation_attribute_type` | Auto-detected | Force `color`, `size`, or `option` UI per attribute |
| `shanelle_variation_swatch_color` | Term meta `color` | Hex/CSS color for swatch terms |

### Variation JSON extensions

Added via `woocommerce_available_variation`:

- `shanelle_stock_status`
- `shanelle_stock_label`
- `shanelle_gallery_image_id`
- `shanelle_variation_name`
- `shanelle_is_on_sale`, `shanelle_current_html`, `shanelle_regular_html`, `shanelle_savings_html`

## Actions

This component does not register custom WordPress actions.

## Example usage

Must be rendered inside `form.variations_form`:

```php
global $product;

if ( $product instanceof WC_Product_Variable ) {
	shanelle_product_variations( $product );
}
```

Force size UI for a custom attribute:

```php
add_filter( 'shanelle_variation_attribute_type', function ( string $type, string $attribute_name ): string {
	if ( 'pa_fit' === $attribute_name ) {
		return 'size';
	}
	return $type;
}, 10, 2 );
```

## Known limitations

- Requires jQuery and WooCommerce variation script on the page
- Gallery sync is a placeholder; does not update ProductGallery yet
- Color swatches fall back to deterministic palette when term color meta is empty
- Simple products render nothing (early return)
- Attribute type detection is slug-heuristic; non-standard attribute names may render as generic chips
