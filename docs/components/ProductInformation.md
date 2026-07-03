# ProductInformation

## Purpose

Renders expandable product information sections on the PDP using an accessible accordion. Replaces the ProductDetail information placeholder with real WooCommerce data where available and filter-driven content for future ACF or theme option integration.

## Responsibilities

- Build accordion sections: Description, Specifications, Size Guide, Care Instructions, Shipping, Returns
- Hide individual sections when no content exists
- Render Description from WooCommerce long description
- Render Specifications from visible attributes, SKU, weight, and dimensions
- Provide filter hooks for CMS-driven content (size guide, care, shipping, returns)
- Enqueue component CSS/JS on product pages or on demand
- Maintain PWA hydration attributes compatible with ProductDetail

## Public API

### PHP class

`Shanelle\Components\ProductInformation`

| Method | Description |
|--------|-------------|
| `boot()` | Registers asset enqueue hook |
| `render( WC_Product $product, array $args = [] )` | Renders accordion (nothing if all sections empty) |
| `render_sections()` | Renders visible accordion items |
| `render_section( array $section, bool $expanded )` | Renders one accordion item |
| `get_information_json()` | Hydration JSON |
| `get_root_id()` | Component root ID |
| `get_heading_id()` | Section heading ID |

### Theme helper

```php
shanelle_product_information( WC_Product $product, array $args = [] );
```

### Render arguments

| Argument | Default | Description |
|----------|---------|-------------|
| `information_id` | Auto-generated | Unique instance ID |

### Accordion sections

| Section ID | Source |
|------------|--------|
| `description` | WooCommerce long description |
| `specifications` | Visible attributes, SKU, weight, dimensions |
| `size-guide` | Filter `shanelle_product_information_size_guide` |
| `care-instructions` | Filter `shanelle_product_information_care_instructions` |
| `shipping` | Filter `shanelle_product_information_shipping` |
| `returns` | Filter `shanelle_product_information_returns` |

### JavaScript exports

From `components/product-information/product-information.js`:

- `initProductInformation( root )`
- `getInformationData( root )`
- `toggleSection( trigger )`
- `setTriggerState( trigger, expanded )`

## Dependencies

- Design System (typography, accordion patterns)
- WooCommerce product description, attributes, SKU, weight, dimensions APIs
- ProductDetail layout classes (`product-detail__section`)

## Events

| Event | Source | Payload |
|-------|--------|---------|
| `shanelle:product-information:ready` | Init complete | `{ root, data, sections }` |
| `shanelle:product-information:toggle` | Accordion toggled | `{ root, trigger, panel, sectionId, expanded }` |

## Extension points

- Provide HTML content via filters for size guide, care, shipping, and returns
- Integrate ACF fields by returning formatted HTML from filters
- Remove or reorder sections with `shanelle_product_information_sections`
- Listen for `ready` event to hydrate content in PWA

## Filters

| Filter | Default | Description |
|--------|---------|-------------|
| `shanelle_product_information_size_guide` | `''` | Size guide HTML; empty hides section |
| `shanelle_product_information_care_instructions` | `''` | Care instructions HTML |
| `shanelle_product_information_shipping` | `''` | Shipping information HTML |
| `shanelle_product_information_returns` | `''` | Returns policy HTML |
| `shanelle_product_information_sections` | Built sections | Final visible section list |
| `the_content` | — | Applied to long description |

## Actions

This component does not register custom WordPress actions.

## Example usage

On the product detail page (already wired in template):

```php
global $product;

if ( $product instanceof WC_Product ) {
	shanelle_product_information( $product );
}
```

Add size guide content from ACF:

```php
add_filter( 'shanelle_product_information_size_guide', function ( string $content, WC_Product $product ): string {
	$guide = get_field( 'size_guide', $product->get_id() );

	return is_string( $guide ) && '' !== trim( $guide )
		? wp_kses_post( $guide )
		: $content;
}, 10, 2 );
```

Reorder sections:

```php
add_filter( 'shanelle_product_information_sections', function ( array $sections, WC_Product $product ): array {
	// Move shipping before specifications, etc.
	return $sections;
}, 10, 2 );
```

## Known limitations

- Size guide, care, shipping, and returns are hidden until content is provided via filters or CMS integration
- Only the first visible section starts expanded
- Accordion allows one open panel at a time
- Does not render downloadable files or custom tabs beyond defined sections
- Specifications use visible product attributes only; hidden attributes are excluded
- No REST endpoint yet; hydration JSON is embedded in `data-information-json`
