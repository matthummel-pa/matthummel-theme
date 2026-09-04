# Marketplace readiness

Audit of **this** theme (`matthummel`) against [WordPress.org theme requirements](https://make.wordpress.org/themes/handbook/review/required/) (updated June 2026) and [ThemeForest WordPress requirements](https://help.author.envato.com/hc/en-us/articles/360000481263-WordPress-Theme-Requirements-Part-1-General).

**Verdict: do not submit this repository to either directory.** Submit **Acreline** (`matthummel-pa/wp-acreline`). This zip is the live matthummel.com site.

---

## What to submit where

| Channel | Upload | Do not upload |
| --- | --- | --- |
| **Own site / WooCommerce** | This theme (already live) plus product zips you sell | Nothing from this repo as a “generic theme” |
| **ThemeForest** | Acreline outer pack from `bin/build-marketplace-pack.sh` | `matthummel.zip` / `theme-latest` |
| **WordPress.org** | Acreline **theme zip only**, later, as a lite listing — see Acreline `docs/marketplace/wordpress-org.md` | This theme, Acreline’s outer pack, or any plugin zip inside a theme |

Acreline already has `readme.txt`, `screenshot.png` (1200×900), GPLv2, `CREDITS.md`, child theme, companion plugin, and `Documentation/` for Envato.

---

## Why WordPress.org would close this ticket

Themes with **three or more distinct issues** are closed as not-approved. This theme has more than that on day one.

| Requirement | This theme |
| --- | --- |
| No custom post types | Projects CPT (`project`) |
| No shortcodes / plugin territory | Contact + `/start/` forms, WooCommerce shortcodes, theme updater, GitHub/DEV.to/Bluesky/OpenAI, n8n webhook |
| No remote data without consent | Google Fonts (allowed exception), GitHub API, CRM webhook, optional AI |
| No auto-inserting pages | `mh_seed_portfolio_pages()` / WooCommerce page seed |
| `readme.txt` + `screenshot.png` 1200×900 | Added in 3.1.31 for Appearance → Themes and Theme Check hygiene — **not** a green light to upload |
| Text domain = folder slug | Folder `matthummel`, text domain `sage` (Sage default; do not rename on the live site) |
| License | MIT (GPL-compatible). Directory strongly prefers **GPLv2 or later** stated |
| Classic hooks | `title-tag` yes; `automatic-feed-links` added in 3.1.31 |
| No `.sh` / hidden / zip in the theme | Deploy scripts live under `.github/scripts/` (keep them out of a directory zip) |
| Sage / Acorn / Blade / Vite | Reviewers expect PHP templates and CSS in `style.css`. `vendor/` + compiled `public/build` is a common rejection |
| Gutenberg | Pages disable the block editor on purpose |

Plugin-territory examples from the handbook that this theme implements: contact forms, SEO title/meta, social share buttons, resource caching (transients for GitHub), analytics-related helpers.

If you sell **any** GPL theme elsewhere, wordpress.org also requires the **sales site** to say products are 100% GPL-compatible in an easy-to-find place. Put that on Acreline’s sales page and, when you sell, on matthummel.com — not by uploading this theme.

---

## Why ThemeForest would reject this zip

Envato wants a **buyer product**: unique slug, public docs, unit-test content, Envato Theme Check **REQUIRED** items fixed, no third-party data without opt-in.

This theme is branded Matt Hummel, seeds personal pages, talks to your CRM, and is not a reusable office/shop theme. Slug `matthummel` also risks colliding with a future wordpress.org listing (buyers “updating” to the wrong theme).

Acreline is the Envato item: Customizer identity, **21 Core Gutenberg blocks** for marketing pages, listings/agents/bookings (metaboxes), child theme, Core plugin, seller pack. Listing paste lives in Acreline `docs/marketplace/themeforest-listing.md` (may lag README — prefer shipping product truth when syncing matthummel.com; see `.cursor/rules/product-theme-sync.mdc`).

---

## What 3.1.31 adds here (safe for the live site)

These files help Appearance → Themes and match the file list Theme Check looks for. They do **not** make this theme directory-legal.

- `screenshot.png` — 1200×900 (4:3) homepage still
- `readme.txt` — wordpress.org parser format; states this is not a directory listing
- `CREDITS.md` — fonts, Sage, highlight.js
- `style.css` — `Tested up to: 7.1`, `Domain Path`, tags (no `accessibility-ready`; that tag triggers extra review)
- `add_theme_support('automatic-feed-links')`
- `LICENSE.md` — Matt Hummel copyright; Sage MIT retained

Do not change the live text domain from `sage` to `matthummel` unless you plan a gettext sweep and accept churn on translations.

---

## Acreline checklist (the real submission)

Work in **wp-acreline**, not this repo.

1. Run Envato Theme Check on a stock WordPress install. Fix every **REQUIRED**. Document Sage/`vendor` warnings.
2. Import [theme unit test](https://github.com/WPTT/theme-unit-test) data: sticky posts, long titles, comments, search empty state, tags + categories.
3. Recapture `screenshot.png` at 1200×900 after homepage changes. No “SALE” chrome.
4. Confirm the ThemeForest pack has no Unsplash binaries, no `.git`, no plugin zip **inside** the theme folder.
5. Live preview: concept banner on for honesty; one screenshot with chrome off.
6. Sales page: **100% GPL-compatible**. Footer credit optional + `rel="nofollow"`.
7. WordPress.org later: theme zip only, CPTs in Acreline Core (separate plugin). Do not claim Envato “Gutenberg optimized” unless the listing and product match; marketing pages already use Core blocks — keep ThemeForest attributes honest.
8. Price/docs: Acreline `docs/marketplace/SELLING.md`.
9. After Acreline ships a release, sync matthummel.com via `resources/data/product-catalog.json` + `mh_product_catalog_vN` (playbook: `.cursor/rules/product-theme-sync.mdc`).

---

## If you ever want a wordpress.org theme from this stack

Build a **lite** presentation theme (no CPT, no forms, no remote CRM, no updater). Keep Sage only if you accept a likely rejection; a classic PHP or block theme has a real shot. Do not strip this live site to pass Theme Check.
