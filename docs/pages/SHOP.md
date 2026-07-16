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
3. Title / count (or collection hero + count)
4. Sticky mobile toolbar — Filtros + Ordenar
5. Active filter chips + clear all
6. `ProductGrid` (`pagination_mode=load_more`, label “Ver más”)
7. Mobile filter sheet — same `CatalogFilters` (`submit_mode=apply`)

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
