# CategoryNavbar

Desktop hover mega-menu for the header category strip. Presentation only — catalog data comes from WooCommerce `product_cat`.

## Purpose

- Render the sticky horizontal category navbar under the site header
- On desktop (≥64rem), open a full-width mega-menu on hover/focus of a category link
- Mirror UX patterns of image-first fashion mega-menus (sidebar + circle grids) with Shanelle branding

## Layout

```
[ Lo nuevo | Oferta | Category A | Category B | … ]
└ hover Category A
   ┌─────────────┬──────────────────────────────────────────┐
   │ Sidebar     │ Comprar por categoría │ Elegidos para ti │
   │ (all cats)  │ circle grid (mapped)  │ circle grid      │
   ├─────────────┴──────────────────────────────────────────┤
   │ CTA explanation cards (4 items, one markup, foreach)   │
   └────────────────────────────────────────────────────────┘
```

## Files

| Path | Role |
|------|------|
| `inc/components/CategoryNavbar.php` | Controller, Customizer, data builders, mapped render helpers |
| `components/category-navbar/category-navbar.php` | Shell markup |
| `components/category-navbar/category-navbar.css` | Strip + mega layout |
| `components/category-navbar/category-navbar.js` | Scroll controls + hover/focus mega interactions |

## Data mapping (no per-item components)

Repeated UI is rendered from arrays with `index` keys:

| Section | Method | Render helper |
|---------|--------|---------------|
| Shop by category circles | `build_shop_by_items()` | `render_circle_item()` — “Ver todo” uses category thumbnail when set |
| Picks for you circles | `build_picks_items()` | `render_circle_item()` — grandchildren → children → **products in category** |
| CTA explanation cards | `get_cta_cards()` | `render_cta_card()` |

Do **not** add separate small PHP components per circle or per CTA card.

## Behavior

- **Desktop:** pointer enter / keyboard focus on `[data-category-navbar-trigger]` opens the mega panel; sidebar buttons switch panes; Escape / backdrop click closes
- **Mobile / tablet (&lt;64rem):** mega panel CSS-hidden; strip links navigate normally
- **Manual menu:** if `category_navbar` location has a menu, strip uses those links and mega-menu is not rendered
- **Flat categories:** when a top-level category has products but no child terms, **Elegidos para ti** falls back to up to 12 product circles (image + name → PDP). **Ver todo** uses the category thumbnail when assigned.

## Customizer

Section **Barra de categorías**: enable strip, enable mega-menu hover, max categories, Lo nuevo / Oferta labels + URLs.

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_category_navbar_categories` | Full category pane payloads |
| `shanelle_category_navbar_shop_by_items` | Per-term shop-by circle items |
| `shanelle_category_navbar_picks_items` | Per-term picks circle items |
| `shanelle_category_navbar_cta_cards` | Footer CTA cards |

## Event

`shanelle:category-navbar:ready` — detail `{ root, i18n }`
