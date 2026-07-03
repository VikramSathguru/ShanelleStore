# Collections Page

The Collections experience is composed by two page controllers:

- **`CollectionsPage`** — browse-all collections index (WordPress page with Collections template)
- **`CollectionPage`** — individual collection term archive (`product_collection` taxonomy)

Both reuse existing catalog data from `Shanelle\Catalog\Queries`, **`CollectionCard`** for listing cards, and **`ShopArchive` + `ProductGrid`** for collection product results.

## Templates

| File | Role |
|------|------|
| `page-templates/collections.php` | WordPress page template → `CollectionsPage::render()` |
| `taxonomy-product_collection.php` | Collection term archive → `CollectionPage::render()` |
| `components/collections-page/collections-page.php` | Collections index markup |
| `components/collection-page/collection-page.php` | Collection archive markup |
| `components/collection-card/collection-card.php` | Reusable collection card markup |

## Composition

```
CollectionsPage (index)
├── Page header (title + intro)
└── CollectionCard grid grouped by collection type

CollectionPage (term archive)
├── Collection hero (term meta hero image + description)
├── Related collection navigation (child/sibling terms)
├── WooCommerce notices (ShopArchive)
├── Optional breadcrumbs (ShopArchive)
├── Product count
├── Catalog toolbar + ordering (ShopArchive)
├── Product grid (ShopArchive → ProductGrid)
└── Filter panel (ShopArchive)
```

## Catalog query layer

- `inc/catalog/Queries.php`
- Normalizes collection term meta for front-end use
- Filters inactive collections by optional start/end dates

## Controllers

| Controller | Assets |
|------------|--------|
| `inc/components/CollectionsPage.php` | `collections-page.css`, `collections-page.js` |
| `inc/components/CollectionPage.php` | `collection-page.css`, `collection-page.js` |
| `inc/components/CollectionCard.php` | `collection-card.css` |

## Theme Customizer

**Appearance → Customize → Collections Page**

| Setting | Purpose |
|---------|---------|
| Page title | Index page heading |
| Intro copy | Index page subtitle |
| Show product counts on cards | Toggle card product counts |
| Show collection type badges | Toggle type label on cards |
| Hide inactive collections | Filter by active date range |
| Group collections by type | Section layout by seasonal/featured/campaign |

**Appearance → Customize → Collection Archives**

| Setting | Purpose |
|---------|---------|
| Show child collection navigation | Toggle related collection chips |
| Show breadcrumbs on collection archives | Toggle breadcrumb nav |
| Empty collection message | Passed to ProductGrid empty state |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_catalog_collections` | Adjust normalized collection list |
| `shanelle_catalog_collection_groups` | Adjust grouped collection sections |
| `shanelle_collections_page_state` | Adjust collections index state |
| `shanelle_collections_page_settings` | Adjust index Customizer settings |
| `shanelle_collection_page_state` | Adjust collection archive state |
| `shanelle_collection_page_settings` | Adjust archive Customizer settings |
| `shanelle_collection_page_grid_args` | Adjust archive ProductGrid config |

## Events

| Event | When |
|-------|------|
| `shanelle:collections-page:ready` | Collections index hydrated |
| `shanelle:collection-page:ready` | Collection archive hydrated |
| `shanelle:shop-archive:ready` | Shared filter panel initialized on archives |

## Helpers

```php
shanelle_collections_page();
shanelle_collection_page();
```

## URL to check

### Collections index

Create a WordPress page (e.g. slug `collections`), assign the **Collections** page template, then visit:

```
{your-site-url}/collections/
```

Example (local):

```
http://localhost/wordpress/collections/
```

### Collection archive

Seeded collection URLs use the taxonomy rewrite base `/collection/`:

```
{your-site-url}/collection/featured/new-arrivals/
{your-site-url}/collection/seasonal/spring/
{your-site-url}/collection/sale/
```

Example (local):

```
http://localhost/wordpress/collection/featured/new-arrivals/
```

Assign products to collection terms in **Products → Collections** to populate grids.

## How to verify

1. **Create index page**
   - Add a page titled “Collections”
   - Assign template **Collections**
   - Publish and open `/collections/`

2. **Index cards**
   - Confirm grouped sections (Seasonal, Featured, Campaign)
   - Cards show name, optional count/type badge, hero image when set in admin

3. **Inactive filtering**
   - Set a collection end date in the past
   - Confirm it disappears when **Hide inactive collections** is enabled

4. **Collection archive**
   - Click a collection card
   - Confirm hero, related collection chips, product grid, filters, ordering

5. **Empty collection**
   - Open a collection with no assigned products
   - Confirm ProductGrid empty state with Customizer message

6. **Admin meta**
   - Upload hero image under **Products → Collections → Edit term**
   - Confirm hero renders on archive and card thumbnail on index

7. **Customizer**
   - Adjust index/archive settings under **Collections Page** and **Collection Archives**

8. **Console events**
   - `shanelle:collections-page:ready` on index
   - `shanelle:collection-page:ready` on archive

## Requirements

- WooCommerce active
- Shanelle theme active (seeder creates default collection terms on activation)
- WordPress page with **Collections** template for the index
- Products assigned to collection terms for archive grids
