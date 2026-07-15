# Database

Shanelle uses the standard WordPress + WooCommerce schema. The theme does **not** register custom database tables.

Local table prefix (FlyEnv `wp-config.php`): `wp_`  
Database name (local): configured in `wp-config.php` — **do not commit credentials into docs or git**.

---

## Custom tables

**Not implemented yet.** No `dbDelta` / custom `$wpdb` tables in the Shanelle theme.

---

## WordPress core tables (standard)

Used heavily by the theme via WP APIs:

| Table | Usage |
|-------|--------|
| `wp_posts` | Products (`product`), pages, attachments, variations (`product_variation`) |
| `wp_postmeta` | WC product meta, attachment meta |
| `wp_terms` / `wp_term_taxonomy` / `wp_term_relationships` | Categories, tags, attributes, `product_collection` |
| `wp_termmeta` | Collection term meta (theme), color hex for filter swatches |
| `wp_options` | Theme mods, WC settings, catalog seed flags |
| `wp_users` / `wp_usermeta` | Customers / account |
| `wp_comments` / `wp_commentmeta` | Product reviews (`type=review`) |

---

## WooCommerce tables (standard)

Present when WooCommerce is installed (HPOS may add `wp_wc_orders*` depending on store settings):

| Area | Tables (examples) |
|------|-------------------|
| Orders (legacy) | `wp_posts` type `shop_order`, `wp_postmeta` |
| Orders (HPOS) | `wp_wc_orders`, `wp_wc_order_addresses`, `wp_wc_order_operational_data`, … |
| Order items | `wp_woocommerce_order_items`, `wp_woocommerce_order_itemmeta` |
| Lookup | `wp_wc_product_meta_lookup`, `wp_wc_order_product_lookup`, … |
| Tax / shipping | `wp_woocommerce_tax_rates`, `wp_woocommerce_shipping_zones`, … |
| Sessions | `wp_woocommerce_sessions` |
| Attributes | `wp_woocommerce_attribute_taxonomies` |

Theme code uses WC APIs rather than querying these tables directly.

---

## Custom metadata (theme)

### Collection term meta (`wp_termmeta`)

Registered/used by `Shanelle\Catalog\Helpers` — keys stored with leading underscore:

| Meta key | Constant | Purpose |
|----------|----------|---------|
| `_collection_type` | `META_TYPE` | `seasonal` \| `featured` \| `campaign` |
| `_collection_start` | `META_START` | Optional start date `Y-m-d` |
| `_collection_end` | `META_END` | Optional end date |
| `_collection_hero_id` | `META_HERO` | Attachment ID |
| `_collection_display_order` | `META_ORDER` | Sort int 0–9999 |

### Color filter term meta

| Meta key | Purpose |
|----------|---------|
| `shanelle_color_hex` | Optional hex for `pa_color` terms (`CatalogFilters`) |

### Theme options (`wp_options`)

| Option key | Purpose |
|------------|---------|
| `shanelle_catalog_seeded` | Seed version string after theme switch seed |
| `shanelle_catalog_pending_seed` | Pending seed flag |

Plus numerous **theme_mod** entries from Customizer (homepage, cart, checkout, search overlay, footer, etc.) stored in the theme mods option for the `shanelle` theme.

---

## Product metadata

Theme consumes standard WooCommerce product meta via `WC_Product` (price, stock, SKU, gallery IDs, attributes).  

**Not implemented yet:** Shanelle-specific product post meta schema beyond WC defaults (no theme `_shanelle_*` product meta layer found).

---

## Order metadata

Orders use WooCommerce standard meta / HPOS fields.  

**Not implemented yet:** Cargo Mobil tracking IDs, courier payloads, or custom order meta writers in the theme.

---

## User metadata

Account pages use WC customer APIs (`WC_Customer`) and core user fields.  

**Not implemented yet:** Social login provider IDs stored by theme; favourites/wishlist user meta (PDP favourites use **browser localStorage** key `shanelle_wishlist`).

---

## Taxonomies in schema

| Taxonomy | Registered by |
|----------|---------------|
| `product_cat`, `product_tag`, `product_shipping_class`, `pa_*` | WooCommerce |
| `product_collection` | Shanelle Catalog (`inc/catalog/Collections.php`) |

---

## Related docs

- [WOOCOMMERCE_ARCHITECTURE.md](./WOOCOMMERCE_ARCHITECTURE.md)  
- [DEPLOYMENT.md](./DEPLOYMENT.md)  
