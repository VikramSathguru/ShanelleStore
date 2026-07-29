# PageHero

Reusable informational page hero for Shanelle Store content pages (Contact, FAQ, Shipping, Privacy, Terms, Returns, Cookies, editorial info, and similar). Presentation only — callers pass copy and media.

**Not** the homepage `HeroBanner` (carousel / Customizer-driven LCP surface).

## Placement

| File | Role |
|------|------|
| `inc/components/PageHero.php` | Controller — normalize args, enqueue, render helpers |
| `components/page-hero/page-hero.php` | Markup |
| `components/page-hero/page-hero.css` | Layout (design tokens + typography utilities) |

Helper: `shanelle_page_hero( $args )`.

Consumed by **`InfoPage`** for Contact, FAQ, Shipping, Returns, Privacy, and Terms. See [pages/INFO_PAGES.md](../pages/INFO_PAGES.md).

## Arguments

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `title` | string | Yes | Page heading (H1 by default) |
| `subtitle` | string | No | Supporting line under the title |
| `breadcrumb` | array | No | Trail items: `[['label' => '…', 'url' => '…'], …]` |
| `background_image` | int\|string | No | Attachment ID (preferred) or image URL |
| `background_image_alt` | string | No | Alt override (attachment alt used when empty) |
| `heading_level` | int | No | `1` (default) or `2` |
| `class` | string | No | Extra root CSS classes |
| `id` | string | No | Root element id (default `shanelle-page-hero`) |

Empty `title` skips render. Empty optional fields omit that UI.

## Usage

```php
shanelle_page_hero(
	array(
		'title'    => __( 'Contacto', 'shanelle' ),
		'subtitle' => __( 'Estamos para ayudarte con pedidos y estilo.', 'shanelle' ),
		'breadcrumb' => array(
			array(
				'label' => __( 'Inicio', 'shanelle' ),
				'url'   => home_url( '/' ),
			),
			array(
				'label' => __( 'Contacto', 'shanelle' ),
			),
		),
		'background_image' => 123, // media library attachment ID
	)
);
```

## Accessibility

- Semantic `<section aria-labelledby="…">`
- Single page heading (`h1` default) with stable id
- Breadcrumb `<nav>` + ordered list; current page uses `aria-current="page"`
- Decorative background without alt is `aria-hidden="true"`; meaningful alt keeps media in the a11y tree
- Focus styles on breadcrumb links via design-system focus tokens

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_page_hero_args` | Adjust normalized args before render |

## Notes

- Styles enqueue on first `render()` so commerce routes stay lean.
- Reuses existing utilities (`.container`, `.text-h1`, `.text-body`, `.text-secondary`) — no duplicated type scale.
- Breadcrumb markup follows the same trail pattern as shop archives for visual consistency.
