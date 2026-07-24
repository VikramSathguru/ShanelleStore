# Documentation System Roadmap

Recommendations for improving `/docs` so humans and AI sessions need less rediscovery.  
**This file is guidance only** — it does not change product code.

---

## Current strengths

- Core architecture set exists (project, theme, WooCommerce, data flow, plugin-first).  
- Component and page deep-dives under `components/` and `pages/`.  
- Explicit **Not implemented yet** language in core docs.  
- `.cursorrules` encodes Plugin First; README is the entry point.  

---

## Gaps and recommendations

### 1. Collapse or label legacy docs

| Issue | Recommendation |
|-------|----------------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) overlaps PROJECT_ARCHITECTURE | Add banner: “Superseded by PROJECT_ARCHITECTURE.md” or merge and delete |
| `wp-content/docs/pages/CHECKOUT.md` duplicates `docs/pages/CHECKOUT.md` | Delete the `wp-content/docs` copy; keep a single tree under `/docs` |
| Homepage page doc drift | Reconcile [pages/HOMEPAGE.md](./pages/HOMEPAGE.md) with `components/homepage/homepage.php` |

### 2. Add a single “AI context pack”

Create a short `docs/AI_CONTEXT.md` (1–2 pages) with:

- Theme path  
- Composer list  
- Non-negotiable Plugin First rules  
- Links only — no duplicated essays  

Purpose: attachable “minimum context” for new Cursor chats.

### 3. Standardize doc ownership

| Change type | Must update |
|-------------|-------------|
| New component | UI_COMPONENTS + optional `components/X.md` |
| New `shanelle_*` hook | CUSTOM_HOOKS |
| New AJAX/REST route | ROUTES + SECURITY if auth changes |
| New plugin installed | PLUGIN_DEPENDENCIES |
| New integration adapter | FUTURE_INTEGRATIONS + PLUGIN_FIRST if policy shifts |
| Status milestone | PROJECT_STATUS |

### 4. Folder layout (future target)

```text
docs/
  README.md                 # entry (done)
  AI_CONTEXT.md             # recommended
  architecture/             # optional regroup of PROJECT_*, THEME_*, WOO_*, DATA_FLOW, PLUGIN_FIRST
  operations/               # DEPLOYMENT, PERFORMANCE, SECURITY, TECH_DEBT, PROJECT_STATUS
  reference/                # HOOKS, ROUTES, DATABASE, EVENTS, PLUGIN_DEPENDENCIES
  pages/                    # keep
  components/               # keep
  design-system/            # keep
```

Do **not** mass-move files until links and `.cursorrules` paths are updated in one pass.

### 5. Keep integration stubs out of theme docs until code exists

When `inc/integrations/` is created, add `docs/INTEGRATIONS.md` with one subsection per adapter. Until then, FUTURE_INTEGRATIONS remains the plan doc.

### 6. Event + pixel mapping sheet

Add a future `docs/ANALYTICS_EVENTS.md` that maps:

`shanelle:added_to_cart` / thank-you / view_item → Meta / TikTok / GA4  

Prevents each integration from inventing a parallel event model.

### 7. CI / editorial checks (optional)

- Fail PRs that change `inc/components/*.php` composers without mentioning a docs path.  
- Link checker for `/docs/*.md`.  
- Ban secrets via simple grep in docs (password, sk_live, etc.).

### 8. Language / locale note

Add a short `docs/I18N.md` when WPML/Polylang/TranslatePress is chosen — compatibility is already listed in Plugin First; a dedicated doc will reduce theme string drift.

---

## Priority order

1. Fix homepage doc drift + remove duplicate `wp-content/docs` checkout file  
2. Add `AI_CONTEXT.md`  
3. Banner on ARCHITECTURE.md  
4. Analytics events sheet when first pixel lands  
5. Optional folder regroup  

---

## Related

- [README.md](./README.md) — entry point  
- [PLUGIN_FIRST_ARCHITECTURE.md](./PLUGIN_FIRST_ARCHITECTURE.md)  
- [PROJECT_STATUS.md](./PROJECT_STATUS.md)  
