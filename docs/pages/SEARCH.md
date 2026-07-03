# Search Page

The search page is composed by the `SearchPage` controller. It reuses **`ShopArchive`** catalog chrome (toolbar, filters, ordering) and **`ProductGrid`** for results. WooCommerce product queries and pagination are handled by WordPress and WooCommerce APIs.

## Templates

| File | Role |
|------|------|
| `search.php` | WordPress search template; delegates to `SearchPage::render()` |
| `components/search-page/search-page.php` | Markup-only search page layout |

## Composition

```
SearchPage
├── Product search form (query + post_type=product)
├── WooCommerce notices (ShopArchive)
├── Optional breadcrumbs (ShopArchive)
├── Results header + count (ShopArchive)
├── Catalog toolbar + ordering (ShopArchive)
├── Product grid (ShopArchive → ProductGrid)
└── Filter panel (ShopArchive)
```

## Controller

- `inc/components/SearchPage.php`
- Assets: `components/search-page/search-page.css`, `search-page.js`
- Also loads existing `shop-archive` and `product-grid` assets via catalog context

## Theme Customizer

**Appearance → Customize → Search Page**

| Setting | Purpose |
|---------|---------|
| Search input placeholder | Placeholder text for the search field |
| Show breadcrumbs on search results | Toggle breadcrumb navigation |
| Empty results helper message | Passed to `ProductGrid` empty state |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_search_page_state` | Adjust full page state payload |
| `shanelle_search_page_settings` | Adjust Customizer-derived settings |
| `shanelle_search_page_grid_args` | Adjust search `ProductGrid` configuration |

Grid query vars inherit `shanelle_product_grid_query_vars` via `ProductGrid`. Catalog toolbar and filters inherit `shanelle_shop_archive_*` hooks unchanged.

## Hooks

| Hook | Purpose |
|------|---------|
| `pre_get_posts` → `SearchPage::restrict_search_to_products` | Limits front-end search to `product` post type |

## Events

| Event | When |
|-------|------|
| `shanelle:search-page:ready` | Search page hydrated |
| `shanelle:shop-archive:ready` | Filter panel initialized (shared catalog JS) |

## Helper

```php
shanelle_search_page();
```

Normally invoked automatically by `search.php`.

## URL to check

Product search uses the WordPress search endpoint with a product post type:

```
{your-site-url}/?s=dress&post_type=product
```

Because `SearchPage` restricts main search queries to products, this also works:

```
{your-site-url}/?s=dress
```

Example (local):

```
http://localhost/wordpress/?s=dress&post_type=product
```

Or programmatically:

```php
echo esc_url( add_query_arg( array( 's' => 'dress', 'post_type' => 'product' ), home_url( '/' ) ) );
```

## How to verify

1. **Search page loads**
   - Visit `/?s=dress` (or any product keyword).
   - Confirm themed layout: search form, results heading, product grid.

2. **Product-only results**
   - Search a term that matches blog posts but not products.
   - Confirm only WooCommerce products appear (or empty state).

3. **Refine search**
   - Submit a new term from the on-page search form.
   - Results and heading should update for the new query.

4. **Ordering**
   - Change catalog ordering dropdown.
   - Results should reorder and paginate correctly.

5. **Filters**
   - Open the filter panel and apply widgets/hooks if configured.
   - Panel open/close and loading state should work via shared shop archive JS.

6. **Empty results**
   - Search for a nonsense term.
   - Confirm `ProductGrid` empty state with Customizer helper message.

7. **Pagination**
   - Search a broad term with many products.
   - Confirm pagination links preserve `s` and `post_type`.

8. **Customizer**
   - Change placeholder, breadcrumbs toggle, and empty message under **Search Page**.

9. **Console events**
   - In DevTools: `shanelle:search-page:ready` and `shanelle:shop-archive:ready` on load.

## Requirements

- WooCommerce active
- Published products indexed for search
- Permalinks configured (recommended)
