# Homepage

The homepage is composed by the `Homepage` page controller. It does not duplicate product, category, or hero logic.

## Template

- `front-page.php` — WordPress front page shell (`get_header()`, `<main>`, `shanelle_homepage()`, `get_footer()`)

## Composition order

Live markup in `components/homepage/homepage.php`:

1. **Full-bleed `HeroBanner`** via `Homepage::render_hero()` (Customizer image/copy/CTAs; brand fallback when media empty)
2. **Category icon grid** (`Homepage::render_category_icons()`) — top-level `product_cat` terms with thumbnails
3. **Featured rails** (`Homepage::render_featured_collections()`) — up to 3 rails × 4 products
4. **For You feed** (`Homepage::render_for_you_grid()` → `ProductGrid` + `ProductCard`, load more; default 12 products)
5. **Empty catalog state** when no published products

### Design decision: full-bleed hero

Shanelle keeps a **full-bleed HeroBanner** for brand-first first viewport. The older SHEIN-style side-tile `hero-promo` layout is **not** composed. Helpers remain deprecated for experiments only (`render_hero_promo()`, `shanelle_homepage_promo_tiles` filter).

### Featured rails honesty

- Prefer up to 3 active `product_collection` terms (featured → campaign → seasonal, then display order).
- Each rail links to the collection archive and loads products in that term.
- If no collections exist, fallback rails use honest shop sorts: **Novedades**, **Populares**, **Mejor valorados**.

### Category discovery

- **Live homepage:** circular category icon grid (automatic from WooCommerce categories).
- **Not live:** `CategoryNavigation` component / Customizer section labeled inactive. Call `shanelle_category_navigation()` only if composing manually.

Unused helpers still exist for alternate compositions (`render_hero_promo()`, `render_category_navigation()`, `render_product_sections()` / `build_sections()`) but are **not** called by the live template.

## Controller

- `inc/components/Homepage.php`
- Template: `components/homepage/homepage.php`
- Page layout assets: `components/homepage/homepage.css`, `components/homepage/homepage.js` (front page only; lightweight `shanelle:homepage:ready` API)

## Theme Customizer

Panel: **Appearance → Customize → Inicio Shanelle**

| Section | Controls |
|---------|----------|
| Banner principal | `HeroBanner` (image, copy, CTAs, overlay) |
| Navegación de categorías (inactiva) | `CategoryNavigation` settings — **not rendered** on live homepage |
| Para ti | Title, initial product count (default 12), sort |
| Product Sections (inactive) | Informational only — optional grids not shown on live homepage |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_homepage_featured_collections` | Override featured / fallback rails |
| `shanelle_homepage_category_icon_items` | Override category icon items |
| `shanelle_homepage_sections` | Optional unused product-section configs for future composition |
| `shanelle_homepage_section_query_vars` | Adjust query vars when `render_product_sections()` is composed |
| `shanelle_homepage_promo_tiles` | Promo tiles for deprecated `render_hero_promo()` only |

## Events

| Event | When |
|-------|------|
| `shanelle:homepage:ready` | Homepage hydrated; exposes scroll/API helpers for PWA use |

Child component events (`shanelle:hero-banner:ready`, `shanelle:product-grid:loaded`) still fire independently.

## Helper

```php
shanelle_homepage();
```

## Requirements

- WooCommerce active for category icons, featured rails, and For You grid
- Published products for grids to populate
- Top-level product categories (thumbnails recommended) for category icons
- Optional: `product_collection` terms for real featured rails
- Customizer hero image/copy for a branded first viewport (site name + tagline fallback when media empty)

## Extending

Compose optional product sections only if you call `Homepage::render_product_sections()` (not in live template). Filter:

```php
add_filter( 'shanelle_homepage_sections', function ( array $sections ) {
	$sections[] = array(
		'key'           => 'editors-picks',
		'enabled'       => true,
		'title'         => __( 'Selección del editor', 'shanelle' ),
		'subtitle'      => '',
		'link_label'    => __( 'Ver todo', 'shanelle' ),
		'link_url'      => wc_get_page_permalink( 'shop' ),
		'orderby'       => 'menu_order',
		'order'         => 'ASC',
		'limit'         => 8,
		'collection_id' => 0,
		'anchor_id'     => 'shanelle-homepage-editors-picks',
		'heading_id'    => 'shanelle-homepage-editors-picks-heading',
		'grid_id'       => 'shanelle-homepage-editors-picks-grid',
		'empty_message' => __( 'No se encontraron productos.', 'shanelle' ),
	);

	return $sections;
} );
```
