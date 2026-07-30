# Shop / Product Listing Pages

Shared PLP chrome for shop, category, tag, collection, and product search results.

## Templates

| Surface | Entry | Composer |
|---------|-------|----------|
| Shop | `woocommerce/archive-product.php` | `ShopArchive::render()` |
| Category / tag | same archive template | `ShopArchive` |
| Collection | `taxonomy-product_collection.php` | `CollectionPage` → ShopArchive chrome |
| Search results | `search.php` | `SearchPage` → ShopArchive chrome |

## Composition

1. Breadcrumbs / notices (surface-specific)
2. Desktop `CatalogFilters` sidebar (`submit_mode=instant`)
3. Screen-reader archive H1 only (visible title / product-count header removed)
4. Sticky mobile toolbar — Filtros + polished Ordenar pill control
5. Active filter chips + clear all (every `shanelle_filter_*` URL param is clearable)
6. `ProductGrid` (`pagination_mode=load_more`, label “Ver más”; catalog cards include media favourite heart)
7. Mobile filter sheet — same `CatalogFilters` (`submit_mode=apply`)
8. Empty state — clear-filters CTA when refined; archive-aware links otherwise (not forced to shop)

## Layout (image-first PLP)

- Full-width stage via `container-fluid` (not the capped `.container`).
- Filter column width: `--shop-archive-filter-width` (`15.4rem`, +10% vs prior). Sticky sidebar scrolls internally when options exceed the viewport.
- From `80rem`, product columns use `repeat(auto-fill, var(--shop-archive-filter-width))` so each card matches the filter panel width.
- Below that: 3 columns (`64rem`), 2 columns (`40rem`), 2 on small mobile.
- Color filter options render as a **two-column** swatch list.
- Catalog cards omit the favourite control; titles use a larger heading-scale name.

## Filters

- Source of truth: `CatalogFilters` only (not `shop-sidebar` widgets).
- Query params: `shanelle_filter_*`.
- Applied via `woocommerce_product_query` (archives) and `pre_get_posts` (search).
- Attribute taxonomies resolved by role aliases (`shanelle_catalog_filter_attribute_map`).
- Category filter: top-level on shop/search; children of current term on category archives (hidden when no children).

## Helpers

```php
shanelle_shop_archive(); // via ShopArchive::render / archive template
```

## Related

- [COLLECTIONS.md](./COLLECTIONS.md)
- [SEARCH.md](./SEARCH.md)
- [WOOCOMMERCE_ARCHITECTURE.md](../WOOCOMMERCE_ARCHITECTURE.md)
