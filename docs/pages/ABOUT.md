# About Page

Marketing "Sobre nosotros" page composed by **`AboutPage`**. Content is **100% Theme Customizer driven** (no hard-coded copy, media, or links), so merchants edit it from the WordPress admin. The global header and footer are reused as-is.

## Templates

| File | Role |
|------|------|
| `page-templates/about.php` | WordPress page template → `AboutPage::render()` |
| `components/about-page/about-page.php` | Page markup (title band, hero, mission, features) |
| `components/about-page/about-page.css` | Page layout (design tokens only) |
| `components/about-page/about-page.js` | Hydration + `shanelle:about-page:ready` event |

## Composition

```
AboutPage
├── Title band (page title)
├── Hero (responsive image + eyebrow, headline, subheadline, tagline)
├── Mission (contrast band: heading + paragraphs + CTA)
└── Features grid (up to 4 slots: icon image + title + text)
```

Each section is optional. Empty fields or a disabled toggle omit that block; empty feature slots are skipped.

## Controllers

| Controller | Assets |
|------------|--------|
| `inc/components/AboutPage.php` | `about-page.css`, `about-page.js` |

## Theme Customizer

**Appearance → Customize → Página Sobre nosotros**

| Setting | Purpose |
|---------|---------|
| Título de la página | Title band heading |
| Mostrar sección hero | Toggle hero |
| Imagen hero (escritorio / móvil) | Hero media (media library) |
| Hero: antetítulo / titular / subtítulo / eslogan | Hero copy |
| Mostrar sección de misión | Toggle mission |
| Misión: encabezado / texto | Mission heading + body (one paragraph per line) |
| Misión: texto del botón / URL | Mission CTA (URL empty → shop permalink) |
| Mostrar sección de características | Toggle features |
| Características: encabezado / texto introductorio | Features header |
| Característica 1–4: ícono/imagen, título, descripción | Feature slots |

## Filters

| Filter | Purpose |
|--------|---------|
| `shanelle_about_page_settings` | Adjust normalized Customizer settings |
| `shanelle_about_page_state` | Adjust the full render state (hero/mission/features) |

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
3. Open **Appearance → Customize → Página Sobre nosotros** and fill copy + media.
4. Add the page to the primary and/or footer menus.

## Notes

- Presentation only; no WooCommerce dependency (CTA falls back to shop permalink when available, else home).
- ACF is **not** used; content lives in `theme_mods`. Filters allow moving to ACF later without template changes.
