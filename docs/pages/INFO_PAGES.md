# Informational Pages

Reusable WordPress page templates for Contact, FAQ, Shipping, Returns, Privacy, and Terms. Each page is **fully editable in the WordPress editor**. The theme provides layout + `PageHero` only — no hardcoded body copy and no custom forms.

## Templates

| Template Name (admin) | File | Type key |
|----------------------|------|----------|
| Contacto | `page-templates/contact.php` | `contact` |
| Preguntas frecuentes | `page-templates/faq.php` | `faq` |
| Envíos | `page-templates/shipping.php` | `shipping` |
| Devoluciones | `page-templates/returns.php` | `returns` |
| Política de privacidad | `page-templates/privacy.php` | `privacy` |
| Términos y condiciones | `page-templates/terms.php` | `terms` |

## Composition

```
InfoPage
├── PageHero (FAQ, Shipping, Returns, Privacy, Terms)
│   or compact title band on Contact (no banner)
└── Body
    ├── Contact: Customizer business details + optional Maps + page content (Fluent Forms)
    └── Other types: WordPress page content only
```

| Editable in WP | Maps to |
|----------------|---------|
| Page title | Hero H1 |
| Page excerpt | Hero subtitle (optional) |
| Featured image | Hero background |
| Page body | Main content / Fluent Forms shortcode |
| Footer Customizer contact fields | Contact page details + footer contact column |

## Contact page

Business details come from **Appearance → Customize → Pie de página** (single source of truth):

| Customizer field | Contact page |
|------------------|--------------|
| Correo electrónico | Email link |
| Teléfono | Phone link |
| WhatsApp | WhatsApp link (`wa.me`) |
| Dirección | Address |
| Horario de atención | Hours (multiline) |
| Google Maps (embed) | Optional iframe (URL only; rendered when set) |

**Form:** Paste a **Fluent Forms** (or Omnisend) shortcode in the Contact page editor. The theme does not build a form.

## Controllers / assets

| Path | Role |
|------|------|
| `inc/components/InfoPage.php` | Composer, contact layout, page lookup, PDP bridge |
| `components/info-page/info-page.php` | Markup |
| `components/info-page/info-page.css` | Layout |
| `Footer::get_business_contact()` | Shared Customizer contact API |
| `PageHero` | Shared hero |

Helpers: `shanelle_info_page( $type )`, `shanelle_get_info_page_url( $type )`.

## Merchant setup

1. **Pages → Add New** for each policy/contact page.
2. Set **Page Attributes → Template** to the matching template above.
3. Add title, optional excerpt, optional featured image, and body content.
4. **Contact:**
   - Fill Footer Customizer contact fields (email, phone, WhatsApp, address, hours, optional Maps embed URL).
   - Paste Fluent Forms shortcode in the page body.
5. **Appearance → Menus** — assign pages to Footer Useful Links / Customer Service / Policies.
6. Publish.
7. Configure **WP Mail SMTP** so Fluent Forms / WooCommerce mail deliver (see below).

## WP Mail SMTP (prepare delivery)

Plugin is already under `wp-content/plugins/wp-mail-smtp`. Theme does not configure credentials (Plugin First).

1. Activate **WP Mail SMTP** in production (and local if testing forms).
2. **WP Mail SMTP → Settings**
   - From Email: store operational address (must match provider).
   - From Name: Shanelle Store (or brand).
   - Mailer: Hostinger SMTP, SendLayer, Gmail, etc. (prefer Hostinger SMTP on Hostinger).
3. Enter provider credentials via the plugin UI — **never commit API keys**.
4. Send a test email from the plugin.
5. Submit the Fluent Forms contact form and confirm notification + autoresponder (if any).
6. Confirm WooCommerce order emails still send after SMTP is live.

## Automatic connections

| Surface | Behavior |
|---------|----------|
| Header contact CTA | Customizer URL → Contact template page → slug fallbacks |
| Footer contact FAB | Customizer URL → WhatsApp (if set) → Contact page |
| Footer contact column | Same Customizer phone / email / WhatsApp / address |
| PDP Envío / Devoluciones | Teaser from Shipping / Returns pages when filters empty |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_info_page_hero_args` | Adjust PageHero args |
| `shanelle_info_page_breadcrumb` | Adjust breadcrumb trail |
| `shanelle_business_contact` | Adjust normalized contact payload |
| `shanelle_product_information_shipping` / `_returns` | Overridable; InfoPage fills empty defaults |

## Plugin First

- **No custom contact form** in the theme.
- Prefer **Fluent Forms** + **WP Mail SMTP** for submissions and delivery.
- Maps: merchant pastes Google embed URL only; theme builds the iframe.
- No ACF required.

## Notes

- Empty page body → details/map only on Contact (no invented form).
- Maps iframe is omitted when Customizer embed field is empty.
- Spanish template labels match storefront language.
- About page remains a separate Customizer-driven composer (`AboutPage`).
