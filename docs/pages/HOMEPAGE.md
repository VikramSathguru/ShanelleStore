# Homepage

The homepage is composed by the `Homepage` page controller. It does not duplicate product, category, or hero logic.

## Template

- `front-page.php` — WordPress front page shell (`get_header()`, `<main>`, `shanelle_homepage()`, `get_footer()`)

## Composition order

Live markup in `components/homepage/homepage.php`:

1. `HeroBanner` via `Homepage::render_hero()`
2. Category icon grid (`Homepage::render_category_icons()`)
3. Featured collection rails (`Homepage::render_featured_collections()`)
4. For You product feed (`Homepage::render_for_you_grid()` → `ProductGrid` + `ProductCard`)

Unused helpers still exist for alternate compositions (`render_hero_promo()`, `render_category_navigation()`, `render_product_sections()`) but are **not** called by the live template.

## Controller

- `inc/components/Homepage.php`
- Template: `components/homepage/homepage.php`
- Page layout assets: `components/homepage/homepage.css`, `components/homepage/homepage.js`

## Theme Customizer

Panel: **Appearance → Customize → Shanelle Homepage**

| Section | Controls |
|---------|----------|
| Hero Banner | Managed by `HeroBanner` (image, copy, CTAs, overlay) |
| Category Navigation | Managed by `CategoryNavigation` (component exists; not in live homepage composition) |
| Product Sections | Two configurable grids (Customizer registered; not in live homepage composition) |
| For You Grid | Title, limit, sort for the main homepage feed |

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

- WooCommerce active for category icons, featured rails, and For You grid
- Published products for grids to populate
- Top-level product categories with thumbnails for best category-icon presentation
- Customizer hero image/copy configured for a branded first viewport (fallback gradient renders if media is empty)

## Extending

Add a third product section via filter (used only if `render_product_sections()` is composed):

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
