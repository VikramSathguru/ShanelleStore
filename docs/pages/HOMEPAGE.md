# Homepage

The homepage is composed by the `Homepage` page controller. It does not duplicate product, category, or hero logic.

## Template

- `front-page.php` — WordPress front page shell (`get_header()`, `<main>`, `shanelle_homepage()`, `get_footer()`)

## Composition order

1. `HeroBanner`
2. `CategoryNavigation`
3. Homepage product sections (`ProductGrid` + `ProductCard`)

## Controller

- `inc/components/Homepage.php`
- Template: `components/homepage/homepage.php`
- Page layout assets: `components/homepage/homepage.css`, `components/homepage/homepage.js`

## Theme Customizer

Panel: **Appearance → Customize → Shanelle Homepage**

| Section | Controls |
|---------|----------|
| Hero Banner | Managed by `HeroBanner` |
| Category Navigation | Managed by `CategoryNavigation` |
| Product Sections | Two configurable grids (enable, title, subtitle, sort, limit, collection filter, view-all link) |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_homepage_sections` | Reorder, add, or remove homepage product sections |
| `shanelle_homepage_section_query_vars` | Adjust the `WP_Query` args for a section before `ProductGrid` renders |

## Events

| Event | When |
|-------|------|
| `shanelle:homepage:ready` | Homepage hydrated; exposes scroll/API helpers for PWA use |

Child component events (`shanelle:hero-banner:ready`, `shanelle:category-navigation:ready`, `shanelle:product-grid:loaded`) still fire independently.

## Helper

```php
shanelle_homepage();
```

## Requirements

- WooCommerce active for product sections
- Published products for grids to populate
- Top-level product categories for category navigation
- Customizer content configured for hero and section headings

## Extending

Add a third product section via filter:

```php
add_filter( 'shanelle_homepage_sections', function ( array $sections ) {
	$sections[] = array(
		'key'           => 'editors-picks',
		'enabled'       => true,
		'title'         => __( 'Editor\'s Picks', 'shanelle' ),
		'subtitle'      => '',
		'link_label'    => __( 'View all', 'shanelle' ),
		'link_url'      => wc_get_page_permalink( 'shop' ),
		'orderby'       => 'menu_order',
		'order'         => 'ASC',
		'limit'         => 8,
		'collection_id' => 0,
		'anchor_id'     => 'shanelle-homepage-editors-picks',
		'heading_id'    => 'shanelle-homepage-editors-picks-heading',
		'grid_id'       => 'shanelle-homepage-editors-picks-grid',
		'empty_message' => __( 'No products found.', 'shanelle' ),
	);

	return $sections;
} );
```
