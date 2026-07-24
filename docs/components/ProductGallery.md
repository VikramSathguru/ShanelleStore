# ProductGallery

## Purpose

Renders a reusable WooCommerce product image gallery for the single product page and future PWA contexts. Replaces the default WooCommerce gallery with a custom main stage, thumbnail strip, navigation, and fullscreen shell.

## Responsibilities

- Build normalized gallery data from featured image and gallery IDs
- Render main image with responsive `srcset` and lazy-loaded thumbnails
- Provide prev/next navigation and thumbnail selection
- Expose fullscreen modal placeholder and zoom placeholder
- Register gallery-specific image sizes
- Enqueue gallery CSS/JS on product pages or on demand

## Public API

### PHP class

`Shanelle\Components\ProductGallery`

| Method | Description |
|--------|-------------|
| `boot()` | Registers image sizes and asset hooks |
| `render( WC_Product $product, array $args = [] )` | Renders full gallery |
| `render_main_image()` | Renders main stage |
| `render_thumbnails()` | Renders thumbnail strip |
| `render_navigation()` | Renders prev/next controls |
| `render_actions()` | Renders zoom/fullscreen actions |
| `render_modal()` | Renders fullscreen modal shell |
| `get_gallery_json()` | Returns gallery JSON for hydration |
| `get_image_count()` | Returns image count |
| `get_panel_id()` | Returns ARIA panel ID |

### Theme helper

```php
shanelle_product_gallery( WC_Product $product, array $args = [] );
```

### Render arguments

| Argument | Default | Description |
|----------|---------|-------------|
| `gallery_id` | Auto-generated | Unique gallery instance ID |

### Registered image sizes

- `shanelle-gallery-main` — 800×1066 (hard crop)
- `shanelle-gallery-thumb` — 120×160 (hard crop)

### JavaScript exports

From `components/product-gallery/product-gallery.js`:

- `initGallery( gallery )`
- `setActiveIndex( gallery, index )`
- `step( gallery, delta )`

## Dependencies

- Design System
- WooCommerce product images and placeholder API
- No external slider libraries

## Events

| Event | Source | Payload |
|-------|--------|---------|
| `shanelle:product-gallery:ready` | Init complete | `{ gallery }` |
| `shanelle:product-gallery:change` | Active image changed | `{ gallery, index, item }` |

## Extension points

- Listen for `shanelle:product-gallery:change` to sync variation images (see ProductVariations gallery placeholder)
- Call exported JS methods from a PWA shell
- Replace zoom placeholder with a future zoom implementation

## Filters

This component does not register custom filters. Gallery data is built from WooCommerce attachment APIs.

## Actions

| Action | Description |
|--------|-------------|
| `after_setup_theme` (priority 11) | Registers gallery image sizes |

## Example usage

```php
global $product;

if ( $product instanceof WC_Product ) {
	shanelle_product_gallery( $product );
}
```

Client-side re-init after dynamic injection:

```javascript
import { initGallery } from '/wp-content/themes/shanelle/components/product-gallery/product-gallery.js';

document.querySelectorAll( '[data-shanelle-product-gallery]' ).forEach( initGallery );
```

## Known limitations

- Image zoom is a placeholder (button disabled); no magnifier implementation yet
- Fullscreen modal is a functional shell without deep gallery integration
- Thumbnail lazy-load observer is prepared but thumbs use native `loading="lazy"`
- Variable product image switching is not wired; ProductVariations dispatches a separate gallery-change event for future use
- One image hides thumbnails and disables navigation; no images show WooCommerce placeholder
