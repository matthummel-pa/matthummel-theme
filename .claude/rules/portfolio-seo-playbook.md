---
paths:
  - "resources/views/**"
  - "resources/blocks/**"
  - "docs/**/*.md"
---

# Portfolio SEO (this repo)

Dual-audience layout, post structure and on-page limits: the `portfolio-seo` skill. Title and meta limits: `content.md`.

## This repository (do not ignore)

This is the **matthummel** Sage 11 theme, not a generic agency case-study site. Map the playbook onto files that already ship. If it conflicts with `agency-core.md`, **shipping patterns win** until Matt asks to migrate.

- **Voice:** first-person **I / my**. Glossary: **shops**, **agencies**, **developers**, **learners**. Do not switch to “we/our” or “clients” (`master-content-writer.md`).
- **Copy location:** block content and posts, not hardcoded Blade except UI chrome (`content.md`).
- **Location:** marketing and SEO copy is **skill-first** (WordPress, plugins, web apps) — do **not** stuff city names into titles, meta, kickers, or every landing paragraph. Demo project **place** labels on Work cards may stay factual. Legal/privacy jurisdiction lines may name Pennsylvania when needed. Only reintroduce local SEO if Matt asks.
- **Work:** `/projects/` is studio **example sites**, not a case-study CPT. Do not invent fake results, testimonials, revenue lifts, or client names (`docs/FEATURES.md`).
- **Proof that ships:** GitHub repos, writing, live concept URLs, profile photo. Not AggregateRating JSON-LD unless Matt asks.
- **Titles/meta:** WordPress document title and Rank Math (or theme `document` helpers). Do not hardcode a second `<title>` stack in Blade. Format *when writing a title* as below.
- **Snippets:** blog posts and the Code page use existing highlight.js / `.snippet` windows. Do not add Markdown fences in Blade. Language class belongs on `<code>` in post HTML.
- **ACF:** do not treat “custom advanced custom fields theme development” as a reason to install ACF. Copy is block content; legacy `mh_f_*` fields are frozen (`content.md`).
- **Dual audience:** shops and developers. The home and About audience doors already name them; do not add a fifth persona (`portfolio-seo` skill has the layout).
- **CTA:** one primary **Say hello** → `/contact/`. Local SEO copy must not add a second conversion funnel.
- **Layout:** do not force a 30/40/30 case-study template onto every Work card. Use that hierarchy when writing a **post** about a build, or if Matt asks for a long project page.

## SEO keyword blueprint

Prefer phrases that match work he actually does. Lead with craft, not city.

| Service pillar | Shop intent | Technical intent |
| --- | --- | --- |
| WordPress | `hire a WordPress developer` / custom theme | Sage 11 custom theme, page fields, Vite — not ACF unless he asks |
| Web apps | `WordPress site for my shop` | custom frontend (Sage, Tailwind, Blade), plugins, other web apps |
| Power Platform | `automate workflows with Power Platform` | custom connectors / APIs when it fits; it is **not** the main offer |

Do not stuff all three pillars into every H1. One primary phrase per page. Secondary phrases belong in body copy, once or twice.
