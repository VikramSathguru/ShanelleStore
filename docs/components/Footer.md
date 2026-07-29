# Footer

Site footer composition for Shanelle Store. Presentation only — no cart, checkout, or plugin business logic.

## Placement

| Entry | Role |
|-------|------|
| `footer.php` | Theme chrome → `shanelle_footer()` |
| `inc/components/Footer.php` | Footer composer (Customizer, remaining menus, contact, floats) |
| `inc/components/FooterBrand.php` | Brand column (logo, description) |
| `inc/components/FooterLinks.php` | Useful Links column (`wp_nav_menu`) |
| `inc/components/FooterCustomerService.php` | Customer Service column (`wp_nav_menu`) |
| `inc/components/FooterPolicies.php` | Policies / legal links in bottom bar (`wp_nav_menu`) |
| `components/footer/footer.php` | Assembles footer columns + engage cluster + bottom legal |
| `components/footer-brand/` | Brand column markup + CSS |
| `components/footer-links/` | Useful Links markup + CSS |
| `components/footer-customer-service/` | Customer Service markup + CSS |
| `components/footer-policies/` | Policies legal links markup + CSS |
| `components/footer/footer.js` | Accordion + scroll-to-top (respects `prefers-reduced-motion`); no newsletter submit handling |

Legacy partial `template-parts/components/site-footer.php` is **not** the live storefront footer.

## Ownership

| Owns | Does not own |
|------|----------------|
| Footer UI / UX / Customizer copy | MiniCart drawer behavior |
| `FooterBrand` — logo + description; social in engage cluster | WhatsApp Business APIs / plugins |
| `FooterLinks` — WordPress useful-links menu | Hard-coded page URLs |
| `FooterCustomerService` — customer-service menu | |
| `FooterPolicies` — policies / legal menu (bottom bar) | |
| Newsletter presentation (title, description, embed slot) | List signup / ESP APIs / credentials (Omnisend, Mailchimp, etc.) |
| Floating scroll-top + optional contact FAB | Payment gateway logic (icons are decorative) |

## Layout (desktop ≥64rem)

Compact CSS Grid on `.footer__inner`:

```
FooterBrand | FooterLinks | FooterCustomerService | Engage
Contact (under Brand)
```

**Engage** (`.footer__engage`) clusters:

1. Compact newsletter (Customizer title + description + provider embed — no card chrome)
2. Social icons (`FooterBrand::render_social()`)

- Brand and Engage tracks are slightly wider; Useful / CS share equal `1fr` tracks.
- Contact is placed under Brand with a tight row gap.
- Section headings share the same bottom margin and line-height baseline.
- Newsletter is borderless/padding-free; embed + social stack in one column.

**FooterLinks** / **FooterCustomerService** stack:

1. Section title (`h2`)
2. 16px (`--space-4`)
3. `<nav>` + vertical `<ul>` (item gap `--space-3-5`)

If no menu is assigned, that column is omitted entirely (`fallback_cb` → `false`).

**Bottom bar** (`.footer__bottom`):

```
.footer__legal                          | payments
  copyright
  FooterPolicies (pipe-separated wrap)
```

- Copyright on its own line.
- Policies render as a compact wrapping row of underlined links separated by `|` (no column heading).
- Payment icons sit to the right on tablet/desktop.

Mobile: single-column stack. Tablet (≥48rem): two-row grid (`Brand | Useful | CS` / `Contact | Engage | Engage`). Desktop (≥64rem): single-row four-track grid with contact under brand.

## Menu locations

| Location slug | Admin label | Component |
|---------------|-------------|-----------|
| `footer_useful_links` | **Footer Useful Links** | `FooterLinks` |
| `footer_shop` | *(legacy)* | Still honored by `FooterLinks` if new location empty |
| `footer_customer_service` | **Footer Customer Service** | `FooterCustomerService` |
| `footer_policies` | **Footer Policies** | `FooterPolicies` (bottom bar) |
| `footer_legal` | *(legacy)* | Still honored by `FooterPolicies` if new location empty |
| `footer_about` | Pie de página: Nosotros | `Footer::render_menus()` |

### Merchant setup — Useful Links

1. **Appearance → Menus → Edit Menus**
2. Create or select a menu (Home, Shop, About, Contact, etc.)
3. Assign it to **Footer Useful Links**
4. Save — the footer column appears automatically

### Merchant setup — Customer Service

1. **Appearance → Menus → Edit Menus**
2. Create or select a menu (shipping, returns, FAQ, contact, etc.)
3. Assign it to **Footer Customer Service**
4. Save — the column appears automatically

### Merchant setup — Policies

1. **Appearance → Menus → Edit Menus**
2. Create or select a menu (privacy, terms, returns, etc.)
3. Assign it to **Footer Policies**
4. Save — links appear in the **bottom bar** under copyright (pipe-separated), not as a main column

## Customizer

**Appearance → Customize → Pie de página**

Notable controls:

- Logo, brand description (default Shanelle brand copy)
- Contact phone / email / WhatsApp / address / business hours
- Optional Google Maps embed URL (Contact page only; sanitized `maps/embed` hosts)
- Social URLs (used by `FooterBrand`; empty → `#`)
- Scroll-to-top toggle
- Optional contact FAB: toggle + URL + accessible label (falls back to WhatsApp then Contact page)
- Copyright, payment icon slugs
- Newsletter block (off by default):
  - Show toggle
  - Title + description (theme copy)
  - **Newsletter Embed Code** — paste provider embed markup (Omnisend JS embed, Mailchimp embed, iframe, or a lone WP shortcode). Theme places sanitized markup in the engage column. Empty embed → newsletter form area hidden (no fake form). Signup submission stays with the provider.

## Newsletter (Plugin First)

| Layer | Responsibility |
|-------|----------------|
| Theme | Visibility, title, description, embed placement, layout CSS |
| Provider / plugin | Form markup, scripts, validation, list API, credentials |

**Merchant setup**

1. Create a signup form in the list provider (Omnisend, Mailchimp, etc.) or a WP form plugin.
2. Copy the provider’s **embed code** (HTML/JS) — or a single form shortcode if the plugin exposes one.
3. **Appearance → Customize → Pie de página** → enable **Mostrar bloque de boletín**.
4. Paste into **Newsletter Embed Code**.
5. Optionally edit title / description.
6. Publish — footer engage column shows provider output; theme JS does not intercept submit.

## Floating controls

| Control | Position | z-index |
|---------|----------|---------|
| Contact FAB | bottom-left | `--z-fixed` (below MiniCart `--z-drawer`); URL falls back to WhatsApp then Contact info page when Customizer URL empty |
| Scroll to top | bottom-right | `--z-fixed` |

## Filters / events

| Hook / event | Purpose |
|--------------|---------|
| `shanelle_footer_settings` | Adjust Customizer-derived settings |
| `shanelle_footer_state` | Adjust render state |
| `shanelle_footer_brand_args` | Adjust FooterBrand render args |
| `shanelle:footer:ready` | Client hydration |

## Manual testing checklist

### Desktop (≥64rem)

- [ ] Useful Links column shows title + vertical list when menu assigned
- [ ] Customer Service column shows when `footer_customer_service` assigned
- [ ] No Policies column in the main grid
- [ ] Policies appear under copyright in the bottom bar when `footer_policies` assigned
- [ ] Policy links wrap with `|` separators
- [ ] Columns hidden when no menu assigned (no fallback pages)
- [ ] Hover/focus: color transition on legal links
- [ ] Keyboard tab through links
- [ ] Useful Links `nav` has `aria-label="Footer Useful Links"`
- [ ] Customer Service `nav` has `aria-label="Footer Customer Service"`
- [ ] Policies `nav` has `aria-label="Footer Policies"`

### Mobile (&lt;48rem)

- [ ] Useful Links centered below Brand
- [ ] Bottom legal block centered under copyright
- [ ] Vertical useful/CS lists preserved
- [ ] Tap targets usable

### Regression

- [ ] Cart / checkout / PDP / mini-cart unaffected
- [ ] Other footer menu columns still work when assigned
