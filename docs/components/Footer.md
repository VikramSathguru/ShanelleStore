# Footer

Site footer composition for Shanelle Store. Presentation only — no cart, checkout, or plugin business logic.

## Placement

| Entry | Role |
|-------|------|
| `footer.php` | Theme chrome → `shanelle_footer()` |
| `inc/components/Footer.php` | Footer composer (Customizer, remaining menus, contact, floats) |
| `inc/components/FooterBrand.php` | Brand column (logo, description, social) |
| `inc/components/FooterLinks.php` | Useful Links column (`wp_nav_menu`) |
| `inc/components/FooterCustomerService.php` | Customer Service column (`wp_nav_menu`) |
| `inc/components/FooterPolicies.php` | Policies column (`wp_nav_menu`) |
| `components/footer/footer.php` | Assembles footer columns only |
| `components/footer-brand/` | Brand column markup + CSS |
| `components/footer-links/` | Useful Links markup + CSS |
| `components/footer-customer-service/` | Customer Service markup + CSS |
| `components/footer-policies/` | Policies markup + CSS |
| `components/footer/footer.js` | Accordion + scroll-to-top (respects `prefers-reduced-motion`) |

Legacy partial `template-parts/components/site-footer.php` is **not** the live storefront footer.

## Ownership

| Owns | Does not own |
|------|----------------|
| Footer UI / UX / Customizer copy | MiniCart drawer behavior |
| `FooterBrand` — first column identity | WhatsApp Business APIs / plugins |
| `FooterLinks` — WordPress useful-links menu | Hard-coded page URLs |
| `FooterCustomerService` — customer-service menu | |
| `FooterPolicies` — policies / legal menu | |
| Floating scroll-top + optional contact FAB | Payment gateway logic (icons are decorative) |

## Layout (desktop ≥64rem)

Premium CSS Grid on `.footer__inner`:

```
FooterBrand | FooterLinks | FooterCustomerService | FooterPolicies | Newsletter
Contact (under Brand)
```

- Brand and Newsletter tracks are slightly wider; Useful / CS / Policies share equal `1fr` tracks.
- Contact is placed under Brand with a tight row gap (social → contact).
- Section headings share the same bottom margin and line-height baseline.
- Newsletter card `max-width` is ~24.5rem (~12% narrower than the previous 28rem card).

**FooterLinks** / **FooterCustomerService** / **FooterPolicies** stack:

1. Section title (`h2`)
2. 16px (`--space-4`)
3. `<nav>` + vertical `<ul>` (item gap `--space-3-5`)

If no menu is assigned, that column is omitted entirely (`fallback_cb` → `false`).

Mobile: single-column stack. Tablet (≥48rem): two-row grid. Desktop (≥64rem): single-row five-track grid with contact under brand.

## Menu locations

| Location slug | Admin label | Component |
|---------------|-------------|-----------|
| `footer_useful_links` | **Footer Useful Links** | `FooterLinks` |
| `footer_shop` | *(legacy)* | Still honored by `FooterLinks` if new location empty |
| `footer_customer_service` | **Footer Customer Service** | `FooterCustomerService` |
| `footer_policies` | **Footer Policies** | `FooterPolicies` |
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
4. Save — the column appears automatically

## Customizer

**Appearance → Customize → Pie de página**

Notable controls:

- Logo, brand description (default Shanelle brand copy)
- Contact phone / email / address
- Social URLs (used by `FooterBrand`; empty → `#`)
- Scroll-to-top toggle
- Optional contact FAB: toggle + URL + accessible label
- Copyright, payment icon slugs
- Newsletter block (off by default until a list plugin is wired)

## Floating controls

| Control | Position | z-index |
|---------|----------|---------|
| Contact FAB | bottom-left | `--z-fixed` (below MiniCart `--z-drawer`) |
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
- [ ] Policies column shows when `footer_policies` assigned
- [ ] Columns hidden when no menu assigned (no fallback pages)
- [ ] Hover/focus: color transition + underline accent
- [ ] Keyboard tab through links
- [ ] Useful Links `nav` has `aria-label="Footer Useful Links"`
- [ ] Customer Service `nav` has `aria-label="Footer Customer Service"`
- [ ] Policies `nav` has `aria-label="Footer Policies"`

### Mobile (&lt;48rem)

- [ ] Useful Links centered below Brand
- [ ] Vertical list preserved
- [ ] Tap targets usable

### Regression

- [ ] Cart / checkout / PDP / mini-cart unaffected
- [ ] Other footer menu columns still work when assigned
