# ProductCard

## Purpose

Renders a reusable WooCommerce product card for catalog listings, grids, and merchandising surfaces. Provides a consistent SHEIN-inspired card pattern with image, badges, price, rating, and quick actions.

## Responsibilities

- Render product media with optional hover image from the gallery
- Display sale, new, and sold-out badges
- Output price markup via WooCommerce with shared `ProductPrice` modifier classes
- Show optional rating, attribute summary, and quick-add controls
- Enqueue component CSS/JS when WooCommerce is active
- Support AJAX quick add for eligible simple products

## Public API

### PHP class

`Shanelle\Components\ProductCard`

| Method | Description |
|--------|-------------|
| `boot()` | Registers asset enqueue hook |
| `render( WC_Product $product, array $args = [] )` | Renders a complete product card |

### Theme helper

```php
shanelle_product_card( WC_Product $product, array $args = [] );
```

### Render arguments

| Argument | Default | Description |
|----------|---------|-------------|
| `image_size` | `shanelle-product-card` | Registered image size slug |
| `image_sizes` | Responsive `sizes` attribute string | Image responsive sizes |
| `lazy` | `true` | Lazy-load primary image |
| `priority` | `false` | Set `fetchpriority="high"` on primary image |
| `show_rating` | `true` | Show star rating when reviews exist |
| `show_attributes` | `true` | Show visible attribute summary |
| `show_actions` | `true` | Show quick action buttons |
| `new_days` | `30` | Days a product is considered “new” |

### JavaScript exports

From `components/product-card/product-card.js`:

- `initCard( card )`
- `quickAddToCart( button )`

## Dependencies

- Design System (`shanelle-main`, buttons, badges, cards)
- WooCommerce (`WC_Product`)
- `Shanelle\WooCommerce\ProductPrice` (compact price classes, sale badge labels)
- WooCommerce AJAX add-to-cart endpoint

## Events

| Event | Source | Payload |
|-------|--------|---------|
| `shanelle:added_to_cart` | Quick add success | `{ productId, data }` |

## Extension points

- Override card render arguments per context (homepage, related products, shop grid)
- Replace badge logic via future filters if merchandising rules expand
- Hook into `shanelle:added_to_cart` for cart drawer, analytics, or PWA sync

## Filters

| Filter | Default | Description |
|--------|---------|-------------|
| `shanelle_product_card_new_days` | `30` | Days since creation to show “New” badge |
| `shanelle_product_card_attribute_limit` | `2` | Max visible attributes in summary line |

## Actions

This component does not register custom WordPress actions.

## Example usage

```php
global $product;

if ( $product instanceof WC_Product ) {
	shanelle_product_card( $product, array(
		'lazy'     => true,
		'priority' => false,
	) );
}
```

Inside a loop via ProductGrid:

```php
ProductGrid::render( $query, array(
	'pagination_mode' => 'none',
	'card_args'       => array(
		'show_actions' => true,
	),
) );
```

## Known limitations

- Quick add supports simple products with AJAX add-to-cart only; variable products link to the PDP
- Wishlist and quick view buttons are placeholders (disabled)
- Hover image uses the first gallery image only
- Does not handle grouped or external product types with custom layouts
