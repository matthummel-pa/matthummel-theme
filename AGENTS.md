# AGENTS.md

This repository is the **Matt Hummel** WordPress theme — Sage 11.2.1 (Roots) with Blade,
Tailwind v4, Vite, and Acorn. The repo root *is* the theme folder.

A thin portfolio layer lives in `app/portfolio.php`, `app/contact.php`, `app/Github.php`,
`app/page-fields.php`, `resources/css/portfolio.css`, and the `template-*.blade.php` views.
Page copy is block content edited in the block editor, not hardcoded in Blade. Rules:
`.claude/rules/content.md`. Pages are moving to blocks one template at a time (3.7.0): page
blocks live in `resources/blocks/` (block.json), `resources/views/blocks/` (Blade render), and
`resources/js/blocks/page/` (editor). A converted page has no theme fields. Pages not yet
converted keep their frozen **Page content (theme)** fields; add no new keys.

Sage docs:

- https://roots.io/sage/docs/
- https://roots.io/sage/docs/theme-templates/
- Local notes: `docs/sage/`
- Feature log: `docs/FEATURES.md`
- Install after deploy: `docs/INSTALL.md`
- Changelog: `CHANGELOG.md`

Production WordPress is **[matthummel.com](https://matthummel.com)** on Hostinger (theme folder
`wp-content/themes/matthummel/`). Hosting, DNS, and WPVibe safety live in
`.claude/rules/hostinger-github-wordpress-workflow.md`.

Staging first: **[matthummel-com-623249.hostingersite.com](https://matthummel-com-623249.hostingersite.com/)**
("hummel-dev") is Hostinger's staging copy of the site. Every design or dev change is installed
and checked there. Agents never deploy to, install on, or edit `matthummel.com`; Matt publishes
staging → live from hPanel.

Deploys: push/merge to `main` builds a zip and publishes GitHub Release `theme-latest`.
On the **staging** site, Appearance → **Update Theme** downloads that zip over HTTPS
(or `wp mh theme-update` over SSH — `docs/INSTALL.md` §7). After install, purge LiteSpeed.
The same PAT (Contents: Read) is saved on that screen or as `MH_GITHUB_TOKEN`.
WP-CLI: `wp mh theme-update` (install zip), `wp mh theme-build` (rebuild on GitHub).

Theme zip products (Acreline, WalkRidge): see `docs/SHOP-DOWNLOADS.md`.

## Local development and deploy notes

Vite `base` in `vite.config.js` is `/wp-content/themes/matthummel/public/build/` so it matches the live Hostinger folder name (`wp-content/themes/matthummel`). A local WordPress must use that same theme directory name (`matthummel`), not `matthummel-theme`. Local setup recipes (WordPress Studio, or Linux with SQLite): `docs/INSTALL.md`.

### Standard commands

- Build assets: `npm run build` (writes `public/build/`, gitignored). Build at least once
  before expecting styled pages.
- Dev/HMR: `npm run dev`.
- PHP style: `vendor/bin/pint --test` (check) / `vendor/bin/pint` (fix).

### Local WordPress isolation (one install per product)

`~/wp-site` and port **8080** are **matthummel only**. Do not activate Acreline (or any other product theme) on this WordPress.

Each product gets its own site directory, theme symlink (folder name = that repo’s Vite `base`), and port:

| Product | Site dir | Theme folder | Port |
| --- | --- | --- | --- |
| matthummel | `~/wp-site` | `matthummel` | **8080** |
| Acreline / wp-acreline | `~/wp-acreline-site` | usually `acreline` | **8081** |
| Next new WP project | `~/wp-<slug>-site` | match Vite `base` | **8082+** (next free) |

Run each product on its own site and port. Do not `pkill -f`. Do not mix `theme-latest` zips between sites.

Gotchas:

- After editing Blade templates, clear compiled views:
  `studio wp acorn view:clear`.
- `wp_mail` does not deliver locally (no mail server). The contact form still redirects.
- Portfolio pages seed once via `mh_seed_portfolio_pages()` (`mh_portfolio_seeded_v2`).
  Existing posts and categories are never deleted.
- Edit visitor-facing copy in the block editor (**Pages -> [page]**), on staging first. Do not
  add keys to `app/page-fields.php`; unconverted pages keep their existing fields until their
  conversion PR (`.claude/rules/content.md`).
- Deploy from WordPress (**staging site only**): Appearance → Update Theme. Needs a
  fine-grained GitHub PAT (Contents read) saved on that screen or as `MH_GITHUB_TOKEN`.
  That **installs the GitHub zip** over HTTPS. Optional: Actions read/write to click
  “Rebuild zip on GitHub”. WP-CLI: `wp mh theme-update`. The agent's `gh` token
  cannot create releases or dispatch workflows (403); use Matt’s PAT on the staging
  site, not the agent token. Live (`matthummel.com`) only changes when Matt publishes
  staging from hPanel.
- WPVibe MCP URL is `https://mcp.wpvibe.ai/mcp` (API endpoint — a browser tab is usually
  a blank page). Use native HTTP (`url`), not `mcp-remote`. First use opens a WPVibe email
  sign-in (6-digit code in the subject line).
- The plugin is on the live site (`vibe-ai`). Theme **files** still ship as the
  `theme-latest` zip into `wp-content/themes/matthummel/` (not the parent `themes/` folder).
  WPVibe then connects, lists themes, and activates — it cannot replace
  `npm run build` / Composer for Sage. Theme edits on a connected site: draft →
  preview → publish.
