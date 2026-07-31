# Deployment

Deployment of Shanelle Store from FlyEnv local development to Hostinger production.

---

## Current environment

| Item | Local (FlyEnv) | Production (Hostinger) |
|------|----------------|------------------------|
| Role | UI development | Target live store |
| App path | Local WordPress tree under FlyEnv | Hostinger WP install / public_html |
| PHP | Must be **8.3+** (theme requirement) | Configure in Hostinger panel |
| Database | Local MySQL (see `wp-config.php`) | Hostinger MySQL |
| Debug | `WP_DEBUG` / log typically enabled locally | Must be **off** |
| Integrations | Deferred until UI freeze | Enable payment + marketing plugins after deploy smoke tests |

**Never commit or paste production/local DB passwords, salts, or API keys into documentation or public repos.**

---

## Local workflow (FlyEnv)

1. Start FlyEnv stack (PHP, Nginx/Apache, MySQL per your FlyEnv site).  
2. Open the local site URL for the WordPress vhost.  
3. Develop only in `wp-content/themes/shanelle/` (+ docs).  
4. Use Query Monitor for query/hook debugging.  
5. Test shoppers flows: home → shop → PDP → mini cart → cart → checkout (test gateway) → account.  
6. After taxonomy/structure changes, visit **Settings → Permalinks → Save** to flush rewrites.

### Theme activate

Appearance → Themes → **Shanelle**.  
On switch, Catalog seeder may queue collection seeds (`after_switch_theme`).

---

## FlyEnv configuration notes

- Point the vhost document root at the WordPress root containing `wp-config.php`.  
- Ensure PHP version ≥ 8.3.  
- Enable required extensions WooCommerce expects (mysqli, curl, etc.).  
- Local HTTPS optional but useful for cookie/`SameSite` testing.  

Exact FlyEnv UI settings are environment-specific — **not checked into this repository as a FlyEnv export file**.

---

## Production workflow (Hostinger)

1. Prepare clean WP + WooCommerce on Hostinger (or clone from staging).  
2. Set PHP 8.3+.  
3. Deploy theme files (see below).  
4. Install/activate WooCommerce + Rank Math (and only needed plugins).  
5. Import/migrate database & media.  
6. Update `wp-config.php` for Hostinger DB + keys; disable debug.  
7. Set permalinks; assign WC pages.  
8. Configure currency, shipping zones, tax, email.  
9. Smoke test and enable cache with WC exclusions.  

---

## Theme deployment methods

| Method | Steps |
|--------|-------|
| SFTP / File Manager | Upload `wp-content/themes/shanelle/` replacing prior version |
| Git on server | If Hostinger supports git deploy: pull theme or full repo excluding secrets |
| ZIP upload | Zip theme folder → WP Admin → Themes → Add New → Upload |

Retain `SHANELLE_VERSION` bumps when shipping CSS/JS changes for cache busts.

---

## Migration strategy

### Code

- Source of truth: custom theme + selected plugins.  
- Do **not** blindly copy `wp-config.php` from local to production.  

### Database migration

1. Export local DB (phpMyAdmin / WP-CLI / FlyEnv tools).  
2. Import into Hostinger DB.  
3. Search-replace URLs (`http://local…` → `https://production…`) using a tool that handles serialized PHP (WP-CLI `search-replace`, or Better Search Replace).  
4. Verify `product_collection` terms, WC pages, and theme_mods.  

### Media migration

- Copy `wp-content/uploads/` intact, **or**  
- Use a migration plugin that includes media.  
- Regenerate thumbnails if image sizes changed (`shanelle-gallery-*`, card sizes).  

### Environment variables / config

WordPress typically uses `wp-config.php` constants rather than `.env`.

| Setting | Local | Production |
|---------|-------|------------|
| `DB_*` | Local MySQL | Hostinger MySQL |
| Auth keys/salts | Local unique | New unique set |
| `WP_DEBUG` | true (dev) | false |
| `WP_DEBUG_LOG` | optional | false or private log only |
| `DISALLOW_FILE_EDIT` | optional | recommended true |
| `FORCE_SSL_ADMIN` / HTTPS | as needed | recommended |

**Not implemented yet:** dotenv-based config loader in theme.

---

## Deployment checklist

### Pre-deploy

- [ ] UI freeze agreed  
- [ ] PHP 8.3+ on Hostinger  
- [ ] WooCommerce compatible version tested locally  
- [ ] Theme syntax smoke (`php -l` on changed files if desired)  
- [ ] No secrets in git  
- [x] Cart AJAX nonces (`shanelle_cart_ajax`) — see [SECURITY.md](./SECURITY.md)  

### Hostinger hardening (required before public traffic)

- [ ] `WP_DEBUG` / `WP_DEBUG_DISPLAY` / `WP_DEBUG_LOG` **false** (or log only to a non-public path)  
- [ ] Unique auth keys/salts (do not reuse local)  
- [ ] `DISALLOW_FILE_EDIT` true  
- [ ] HTTPS enforced (site URL + admin)  
- [ ] Query Monitor **disabled/removed**  
- [ ] Health Check disabled when not troubleshooting  
- [ ] Site language **Español** + language pack installed (WC/core strings)  

### Deploy

- [ ] Backup Hostinger files + DB  
- [ ] Deploy theme  
- [ ] Migrate DB + uploads (if needed)  
- [ ] Update `wp-config.php`  
- [ ] Activate theme + WooCommerce  
- [ ] Flush permalinks (**Ajustes → Enlaces permanentes → Guardar**)  
- [ ] Confirm WC carts/checkout/account pages  
- [ ] Confirm checkout page uses classic `[woocommerce_checkout]` (not the Checkout block)  

### Payments E2E (ops — plugins present, theme stays gateway-agnostic)

Installed gateways in this project (see [PLUGIN_DEPENDENCIES.md](./PLUGIN_DEPENDENCIES.md)): PayPal Payments, Pagadito, Fygaro.

- [ ] Choose **one primary** live method for day one; leave others off until certified  
- [ ] WooCommerce → Payments: enable gateway, sandbox/live credentials, webhooks reachable on Hostinger  
- [ ] Place **sandbox/test** order: home → shop → PDP → ATC → mini-cart qty → cart → checkout → pay → thank-you  
- [ ] Confirm order email + customer account order history  
- [ ] Confirm gateway shows correctly in Shanelle classic checkout (express slot + `#payment`)  
- [ ] Align footer payment icons (Customizer → Footer → Íconos de pago) with **enabled** methods only  
- [ ] Switch to live credentials only after sandbox pass; re-test one small live order  

### Shipping & mail

- [ ] WC shipping zones/rates for Nicaragua (or target market)  
- [ ] WP Mail SMTP configured; test order + Fluent Forms contact mail  
- [ ] PDP/shipping policy pages still accurate (estimate copy ≠ live rates)  

### Marketing / SEO (after payments smoke)

- [ ] Rank Math sitemap + titles  
- [ ] Meta / TikTok / Site Kit: enable only what you will use; verify no double Purchase events  
- [ ] Omnisend or MailPoet (pick one list provider) + footer newsletter embed if used  

### Post-deploy smoke

- [ ] Mobile: shop, PDP sticky ATC, mini-cart, checkout  
- [ ] HTTPS + redirects  
- [ ] Cache configured (exclude cart/checkout/account/my-account)  
- [ ] Monitoring / uptime  

---

## Rollback strategy

1. Keep prior theme ZIP (`shanelle-YYYYMMDD.zip`) on Hostinger.  
2. DB dump before each migration.  
3. Rollback steps: restore theme folder → restore DB if schema/content broken → flush cache/permalinks.  
4. Prefer reverting theme only when DB untouched.  

---

## Caching & Hostinger specifics

- Use Hostinger/LiteSpeed cache carefully with WooCommerce.  
- Never full-page-cache personalized cart fragments without ESI/exclusions.  
- Object cache optional.  
- Cron: ensure WP-Cron or real cron for WC scheduled actions.  

---

## Related docs

- [SECURITY.md](./SECURITY.md)  
- [PERFORMANCE.md](./PERFORMANCE.md)  
- [PLUGIN_DEPENDENCIES.md](./PLUGIN_DEPENDENCIES.md)  
- [PROJECT_STATUS.md](./PROJECT_STATUS.md)  
