# Search

Product search is split into three controllers plus the existing full-results page.

## Architecture

```
SearchController   → AJAX + REST query orchestration (WooCommerce product search APIs)
SearchResults      → Markup composer for suggestions (uses ProductPrice, not ProductCard)
SearchOverlay      → Site-wide modal overlay shell + assets
SearchPage         → Full results page via ShopArchive + ProductGrid
```

## Files

| File | Role |
|------|------|
| `inc/components/SearchController.php` | Live search AJAX/REST, WC product/term queries, query highlighting |
| `inc/components/SearchResults.php` | Suggestion row markup, idle/popular/recent shells, skeleton, empty state |
| `inc/components/SearchOverlay.php` | Overlay boot, Customizer, site-wide assets, footer render |
| `components/search-overlay/search-overlay.php` | Overlay markup (combobox input + results panel) |
| `components/search-overlay/search-overlay.css` | Mobile full-screen / desktop centered modal layout |
| `components/search-overlay/search-overlay.js` | Open/close, debounced AJAX, keyboard nav, recent searches |
| `components/search-results/search-results.php` | Results panel sections (products, categories, collections) |
| `components/search-results/search-results.css` | Suggestion row layout, skeleton, chips |
| `template-parts/components/site-header.php` | Header search trigger (`data-shanelle-search-open`) |
| `search.php` | Full search results route → `SearchPage` |

## Entry points

- **Header search icon** → opens overlay (`data-shanelle-search-open`)
- **Live typing** → `admin-ajax.php?action=shanelle_search_suggest`
- **PWA / REST** → `GET /wp-json/shanelle/v1/search?query=dress`
- **Full results** → `/?s=dress&post_type=product`

## Customizer

**Appearance → Customize → Search Overlay**

| Setting | Purpose |
|---------|---------|
| Search input placeholder | Overlay input copy |
| Popular searches | Idle-state suggestion chips (one per line) |
| No results helper message | Empty state helper copy |
| Minimum characters | Live search threshold |
| Debounce delay (ms) | Input debounce for AJAX |
| Maximum product suggestions | Product row limit |
| Maximum category/collection suggestions | Term row limit |

**Appearance → Customize → Search Page** — full results page settings (unchanged).

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_search_product_query_args` | Adjust `wc_get_products()` live search args |
| `shanelle_search_category_query_args` | Adjust category term query |
| `shanelle_search_collection_query_args` | Adjust collection term query |
| `shanelle_search_response` | Adjust AJAX/REST payload |
| `shanelle_search_results_args` | Adjust results render args |
| `shanelle_search_product_item` | Adjust normalized product suggestion data |
| `shanelle_search_overlay_state` | Adjust overlay client state |
| `shanelle_search_overlay_settings` | Adjust overlay Customizer settings |

## Events

| Event | When |
|-------|------|
| `shanelle:search-overlay:ready` | Overlay hydrated |
| `shanelle:search-overlay:opened` | Overlay opened |
| `shanelle:search-overlay:closed` | Overlay closed |
| `shanelle:search-page:ready` | Full results page hydrated |

## Helpers

```php
shanelle_search_overlay();
shanelle_search_results( array $args );
shanelle_search_page();
```

## Testing checklist

### Overlay shell

- [ ] Click header search icon — overlay opens, input focused
- [ ] Mobile: overlay is full-screen
- [ ] Desktop: overlay is centered modal with backdrop
- [ ] Escape closes overlay and restores focus
- [ ] Click backdrop / outside dialog closes overlay
- [ ] Body scroll locked while open

### Live search

- [ ] Typing fewer than minimum characters shows idle suggestions
- [ ] Typing valid query shows loading skeleton, then results
- [ ] Debounce prevents excessive requests
- [ ] Product rows show image, highlighted title, price, sale badge, category/collection meta, quick link
- [ ] Category and collection suggestions appear when matched
- [ ] No-results state renders helper message + view-all link
- [ ] View all link navigates to `/?s=query&post_type=product`

### Keyboard and accessibility

- [ ] Arrow down/up moves active suggestion
- [ ] Enter on active suggestion navigates to item URL
- [ ] Enter in input (no selection) navigates to full results
- [ ] Combobox exposes `aria-expanded`, `aria-controls`, live region announcements
- [ ] Focus trap keeps Tab inside overlay

### Recent and popular searches

- [ ] Popular searches from Customizer appear as chips on idle state
- [ ] Selecting a chip fills input and fetches suggestions
- [ ] Successful searches persist to localStorage recent list
- [ ] Clear recent removes stored searches

### Full results page

- [ ] `/?s=dress&post_type=product` renders SearchPage grid
- [ ] Search remains product-only (no posts/pages)

### PWA / REST

- [ ] `GET /wp-json/shanelle/v1/search?query=dress` returns JSON payload with `html`, `items`, and `resultsUrl`

## Suggested commit message

```
Add premium live search overlay with AJAX suggestions and full results integration.

Introduce SearchController, SearchResults, and SearchOverlay with keyboard navigation, recent/popular searches, ProductPrice-powered suggestion rows, header trigger, and REST support for PWA hydration.
```
