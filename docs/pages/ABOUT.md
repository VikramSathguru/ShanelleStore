# About Page

Marketing "Sobre nosotros" page composed by **`AboutPage`**. Content is **100% Theme Customizer driven** (no hard-coded copy, media, or links), so merchants edit it from the WordPress admin. The global header and footer are reused as-is.

## Templates

| File | Role |
|------|------|
| `page-templates/about.php` | WordPress page template → `AboutPage::render()` |
| `components/about-page/about-page.php` | Page shell (section composition) |
| `components/about-page/partials/hero.php` | Hero — full-bleed brand intro (H1) |
| `components/about-page/partials/story.php` | Our Story — alternating image + copy |
| `components/about-page/partials/mission-values.php` | Mission & Values |
| `components/about-page/partials/why-choose.php` | Why Choose Shanelle |
| `components/about-page/partials/cta.php` | Closing call to action |
| `components/about-page/about-page.css` | Page layout (design tokens only) |
| `components/about-page/about-page.js` | Hydration, section reveal, `shanelle:about-page:ready` |

## Composition

```
AboutPage
├── Hero (full-bleed media + eyebrow, H1, subheadline, tagline, optional CTA)
├── Our Story (alternating image + body; media left/right on desktop)
├── Mission & Values (contrast band + up to 3 value cards)
├── Why Choose (up to 4 reason cards)
└── Call to Action (closing band + button)
```

Each section is optional. Empty fields or a disabled toggle omit that block; empty value/feature slots are skipped. When Hero is hidden, a screen-reader-only H1 keeps heading hierarchy intact.

## Controllers

| Controller | Assets |
|------------|--------|
| `inc/components/AboutPage.php` | `about-page.css`, `about-page.js` |

## Responsive behavior

| Breakpoint | Behavior |
|------------|----------|
| Mobile (`< 40rem`) | Single-column stacked sections |
| Tablet (`≥ 40rem`) | Story stays stacked until `48rem`; values 2-col; why-choose 2-col |
| Desktop (`≥ 48rem`) | Story becomes two-column alternating block; generous section padding |
| Large (`≥ 64rem`) | Values 3-col; why-choose 4-col; max editorial spacing |

## Theme Customizer

**Appearance → Customize → Página Sobre nosotros**

Starter copy reflects the founder story: gratitude and trust, the seller–customer relationship, empathy from also being a customer, and the values **transparencia**, **empatía**, and **paciencia**. Merchants can edit every field.

| Setting group | Purpose |
|---------------|---------|
| Título de la página | H1 fallback when hero titular is empty |
| Hero | Toggle, desktop/mobile images, antetítulo, titular (H1), subtítulo, eslogan, botón |
| Nuestra historia | Toggle, antetítulo, encabezado, texto, images, media side (left/right) |
| Misión y valores | Toggle, antetítulo, encabezado, texto, Valores 1–3 (ícono, título, descripción) |
| Por qué elegirnos | Toggle, antetítulo, encabezado, intro, Motivos 1–4 |
| Llamado a la acción | Toggle, antetítulo, encabezado, texto, botón + URL (URL empty → shop) |

Legacy mission CTA fields remain as fallback when the dedicated CTA button fields are empty.

Defaults live in `AboutPage::get_default_content()`. To write them into theme mods:

```bash
php wp-content/themes/shanelle/bin/seed-about-page.php --force
```

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_about_page_settings` | Adjust normalized Customizer settings |
| `shanelle_about_page_state` | Adjust the full render state (hero/story/mission/why_choose/cta) |

## Events

| Event | When |
|-------|------|
| `shanelle:about-page:ready` | About page hydrated |

## Helpers

```php
shanelle_about_page();
```

## Merchant setup

1. **Pages → Add New**, publish a page (e.g. slug `sobre-nosotros`).
2. In **Page Attributes → Template**, choose **Sobre nosotros**.
3. Open **Appearance → Customize → Página Sobre nosotros** and fill copy + media for each section.
4. Add the page to the primary and/or footer menus.
5. Ensure Media Library images have meaningful **alt text**.

## Accessibility

- Semantic landmarks: one `<main>`, section `aria-labelledby` where headings exist.
- Heading order: H1 (hero) → H2 (sections) → H3 (value/reason cards).
- Images use attachment alt text via `wp_get_attachment_image` / picture markup.
- Buttons use design-system `.btn` focus-visible ring (`--shadow-focus`).
- Reveal animation respects `prefers-reduced-motion`.

## Notes

- Presentation only; no WooCommerce dependency (CTA falls back to shop permalink when available, else home).
- ACF is **not** used; content lives in `theme_mods`. Filters allow moving to ACF later without template changes.
- State key `features` remains as an alias of `why_choose` for backward-compatible filters.
