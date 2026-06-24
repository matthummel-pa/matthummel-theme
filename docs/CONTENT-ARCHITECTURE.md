# Content Architecture & SEO (Rank Math)

Clean silos so content is easy to find, easy to expand, and ranks well — with **one**
SEO plugin (Rank Math) and no page builders.

---

## 1. Content types

| Type | What | Built by |
|---|---|---|
| **Post** | Blog articles (the three tracks below) | core, theme templates |
| **Project** (CPT) | Case studies / portfolio | theme (registered in `app/setup.php`) |

**Project Type** taxonomy (on Projects): `GitHub Project` · `Client Work` · `Experiment`.
GitHub projects pull live repo data via the theme's `[mh_github]` engine — no extra plugin.

---

## 2. Blog categories (topic pillars = SEO silos)

Keep categories few and meaningful. **One primary category per post.**

- **Web Development** — front-end, performance, accessibility, UX, the open web.
- **WordPress Development** — Gutenberg, blocks, Sage/Roots, themes, plugins, WP-CLI.
- **Power Platform Development** — Power Apps, Power Automate, Power BI, Dataverse.
- *(optional)* **Notes** — short links/opinions that don't fit a pillar.

> Slugs: `/web-development/`, `/wordpress-development/`, `/power-platform/`.

---

## 3. Tags (granular, cross-cutting discovery)

3–6 per post, lowercase-hyphenated, **reuse** existing (no near-duplicates).

**Web Development:** `html` `css` `javascript` `performance` `accessibility` `seo` `ux` `responsive-design` `animations` `core-web-vitals`

**WordPress Development:** `gutenberg` `block-development` `sage` `roots` `acorn` `tailwind` `wp-cli` `theme-development` `plugin-development` `rank-math` `block-patterns`

**Power Platform:** `power-apps` `power-automate` `power-bi` `dataverse` `connectors` `canvas-apps` `model-driven-apps` `copilot-studio` `power-fx`

**Format (cross-cutting):** `tutorial` `guide` `case-study` `tips` `opinion` `quick-win`

---

## 4. Rank Math SEO conventions (per post)

- **Focus keyword:** one per post. Put it in the **title, URL slug, first 100 words, one H2, and meta description.**
- **SEO title:** ≤ 60 chars, CTR-first, include a number when natural ("7 Gutenberg…").
- **Meta description:** ~150–155 chars, keyword + the payoff.
- **Slug:** short, keyword-focused, drop stop words (`/gutenberg-block-basics/`).
- **Headings:** one H1 (the title), logical H2/H3 — Rank Math + a11y both reward this.
- **Internal links:** 3+ with descriptive anchors; link new posts to pillar/older posts.
- **Featured image:** 1200×630, descriptive `alt`, compressed.
- **Schema:** Article (Rank Math default); set author + published/updated dates.
- **Technical (set once):** sitemap on, breadcrumbs on, titles/meta templates per type, noindex thin/utility pages.
- **Target a green Rank Math score (≥ 80)** before publishing — but don't keyword-stuff; readability first (trend #3).

---

## 5. Mobile & performance (ranking factors too)

- Mobile-first layouts (see design system); test 320–1440px.
- Lazy-load images, set width/height, serve `srcset`; prefer AVIF/WebP.
- Minimal CSS/JS (theme-built blocks, no builder bloat) = faster Core Web Vitals = better ranking.
- Descriptive, stable URLs; 301 any changed slugs (Rank Math → Redirections).

---

## 2a. Category descriptions (set on each category + reuse as Rank Math meta)

- **Web Development** — Front-end craft for the open web: performance, accessibility, CSS, and the UX details that make sites feel fast and effortless.
- **WordPress Development** — Modern, code-first WordPress: Gutenberg blocks, Sage/Roots themes, WP-CLI, and building lean without page-builder bloat.
- **Power Platform Development** — Low-code that scales: Power Apps, Power Automate, Dataverse, and Power BI patterns from real Microsoft 365 builds.
- **Notes** — Short takes, useful links, and quick wins that don't need a full article.

> Set via WP-CLI: `wp term update category <id> --description="…"`. Keep ~120–160 chars so they double as the archive intro + SEO meta.
