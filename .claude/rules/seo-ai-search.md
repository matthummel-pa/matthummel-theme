---
paths:
  - "**/*.{html,jsx,tsx,astro,md,mdx,blade.php,php}"
---

# AI search (GEO), this repo

Generic GEO standards load from the playbook (`seo/seo-ai-search.md`). This file is only what is specific to this theme.

## This repository (do not ignore)

This is the **matthummel** Sage 11 theme. Apply GEO standards to block copy, posts, and Blade markup without fighting what already ships.

- **Copy location:** block content and posts, not hardcoded Blade except UI chrome (`content.md`).
- **JSON-LD:** add schema only when Matt asks for a specific type (`Article`, `FAQPage`, `Organization`, `HowTo`). Do not spray fake `AggregateRating` or invent Product schemas (`portfolio-seo-playbook.md`, `docs/FEATURES.md`).
- **Canonical / titles:** WordPress document title, Rank Math, and theme `document` helpers own `<title>` and canonicals. Do not hardcode a second title or canonical stack in Blade.
- **SSR:** Sage Blade is already server-rendered. Prefer that over locking indexable copy behind tabs or JS-only UI.
- If this file conflicts with `agency-core.md` or shipping patterns, **shipping patterns win** until Matt asks to migrate.
