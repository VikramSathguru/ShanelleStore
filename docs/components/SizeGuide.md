# SizeGuide

## Purpose

Product-page size guide modal opened from the size options row. Presentation-only; chart data is filterable for CMS or plugin feeds.

## Responsibilities

- Render **Guía de tallas** trigger on the same row as size chips
- Open a centered modal matching the brand size-guide reference layout
- Support garment type switch, IN/CM toggle, fit-type indicator, product/body charts
- Convert stored CM measurements to inches in the browser
- Highlight hovered cell’s full row and column (crosshair) for easier reading
- Remain disabled when `shanelle_size_guide_data` sets `enabled` to false

## Plugin First

Mature size-chart plugins exist, but they do not deliver this modal UX (fit scale, dual charts, type tabs, unit toggle) without heavy restyling. Theme owns presentation; merchants can replace chart rows via `shanelle_size_guide_data` (e.g. ACF/plugin).

## Public API

`Shanelle\Components\SizeGuide`

| Method | Description |
|--------|-------------|
| `boot()` | Enqueues assets on product pages when enabled |
| `is_enabled( WC_Product $product )` | Whether trigger/modal should render |
| `get_guide_data( WC_Product $product )` | Normalized guide payload |
| `render_trigger( WC_Product $product )` | Size-row button |
| `render_modal( WC_Product $product )` | Modal markup |

Helper: `shanelle_size_guide( WC_Product $product )`.

## Location

- Controller: `inc/components/SizeGuide.php`
- Markup / CSS / JS: `components/size-guide/`

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_size_guide_data` | Full guide payload (types, charts, fit, disclaimer) |
| `shanelle_size_guide_fit` | Fit marker: `skinny` \| `regular` \| `oversized` |

Product meta `_shanelle_size_guide_fit` overrides fit when set.

## Integration

Rendered by `ProductVariations` for `size` / `talla` attribute groups. Trigger sits in `.product-variations__size-row` beside size chips.

## Known limitations

- Default charts are women’s fashion reference tables (not per-SKU measurements)
- No admin UI for editing charts (use filter or future CMS field)
- Simple products without a size attribute do not show the trigger
