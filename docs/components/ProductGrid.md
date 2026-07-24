# ProductGrid

## Purpose

Provides a layout and query layer for product listings. Delegates item rendering to `ProductCard` and supports pagination, load-more, infinite scroll, AJAX, and REST hydration for future PWA use.

## Responsibilities

- Accept `WP_Query`, query array, or global query
- Render responsive product grid markup
- Delegate each item to `ProductCard::render()`
- Provide pagination, load-more, and infinite scroll modes
- Expose AJAX and REST endpoints for client-side loading
- Sanitize replay-safe query variables
- Render empty and skeleton states

## Public API

### PHP class

`Shanelle\Components\ProductGrid`

| Method | Description |
|--------|-------------|
| `boot()` | Registers assets, AJAX, and REST routes |
| `render( $query = null, array $args = [] )` | Renders full grid |
| `render_items()` | Renders loop items only |
| `render_pagination()` | Renders numbered pagination |
| `render_load_more()` | Renders load-more / infinite sentinel |
| `render_empty()` | Renders empty state |
| `render_skeleton( int $count = 8 )` | Renders loading skeleton |
| `get_grid_attributes()` | Returns data attributes for client hydration |
| `sanitize_query_vars( array $query_vars )` | Sanitizes query vars for AJAX/REST replay |

### Theme helper

```php
shanelle_product_grid( $query = null, array $args = [] );
```

### Render arguments

| Argument | Default | Description |
|----------|---------|-------------|
| `grid_id` | Auto-generated | Client hydration ID |
| `pagination_mode` | `pagination` | `pagination`, `load_more`, `infinite`, or `none` |
| `infinite_scroll` | `false` | Enable infinite scroll sentinel |
| `load_more_label` | Translated string | Load-more button label |
| `empty_message` | Translated string | Empty state message |
| `card_args` | `[]` | Passed to `ProductCard::render()` |
| `pagination_base` | `''` | Pagination base URL |
| `pagination_format` | `''` | Pagination format string |

### AJAX action

- `shanelle_load_product_grid`

### REST route

- `GET /wp-json/shanelle/v1/product-grid`

### JavaScript exports

From `components/product-grid/product-grid.js`:

- Grid load/infinite scroll handlers (internal)
- Dispatches `shanelle:product-grid:loaded`

## Dependencies

- Design System
- `ProductCard`
- WooCommerce catalog ordering APIs
- WordPress `WP_Query`

## Events

| Event | Source | Payload |
|-------|--------|---------|
| `shanelle:product-grid:loaded` | AJAX/REST load success | `{ page, products, query }` (grid context) |

## Extension points

- Pass custom `WP_Query` or query arrays for curated collections
- Set `pagination_mode` to `none` for related products and embedded grids
- Use REST/AJAX endpoints for PWA product listing hydration
- Forward `card_args` to customize card presentation per grid

## Filters

| Filter | Description |
|--------|-------------|
| `shanelle_product_grid_query_vars` | Modify sanitized query vars before execution |

## Actions

This component does not register custom WordPress actions.

## Example usage

```php
$query = new WP_Query( array(
	'post_type'      => 'product',
	'posts_per_page' => 12,
	'tax_query'      => array(
		array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array( 'dresses' ),
		),
	),
) );

shanelle_product_grid( $query, array(
	'pagination_mode' => 'load_more',
	'card_args'       => array(
		'show_rating' => false,
	),
) );
```

Related products (no pagination):

```php
ProductGrid::render( $related_query, array(
	'pagination_mode' => 'none',
	'grid_id'         => 'related-' . $product_id,
) );
```

## Known limitations

- AJAX/REST replay supports a constrained set of query vars (sanitized in `sanitize_query_vars()`)
- Infinite scroll is disabled by default
- Does not implement filtering UI; pairs with `ShopArchive` for catalog filters
- WooCommerce catalog ordering from `$_GET['orderby']` applies on initial server render only
