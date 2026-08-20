# AGENTS.md

This repository is the **Matt Hummel** WordPress theme — Sage 11.2.1 (Roots) with Blade,
Tailwind v4, Vite, and Acorn. The repo root *is* the theme folder.

A thin portfolio layer lives in `app/portfolio.php`, `app/contact.php`, `app/Github.php`,
`app/page-fields.php`, `resources/css/portfolio.css`, and the `template-*.blade.php` views.
Page copy is edited in wp-admin (**Page content (theme)**), not hardcoded in Blade.

Sage docs (also listed in `.cursor/docs.json`):

- https://roots.io/sage/docs/
- https://roots.io/sage/docs/theme-templates/
- Local notes: `docs/sage/`
- Feature log: `docs/FEATURES.md`
- Install after deploy: `docs/INSTALL.md`
- Changelog: `CHANGELOG.md`

Live deploys: push/merge to `main` builds a zip and publishes GitHub Release `theme-latest`.
On the live site, Appearance → **Update Theme** downloads that zip over HTTPS (no FTP).
FTP remains an optional, best-effort step in `.github/workflows/deploy.yml`.
The same PAT (Contents: Read) is saved on that screen or as `MH_GITHUB_TOKEN`.
WP-CLI: `wp mh theme-update` (install zip), `wp mh theme-build` (rebuild on GitHub).

## Cursor Cloud specific instructions

The base environment already has PHP 8.3, Composer, Node 22, and WP-CLI. The update script
runs `composer install` and `npm install`. Services are not auto-started.

Vite `base` in `vite.config.js` is `/wp-content/themes/matthummel/public/build/` so it
matches the live SiteGround folder name (`wp-content/themes/matthummel`). Local WordPress
must use that same directory name (symlink `/workspace` to
`~/wp-site/wp-content/themes/matthummel`), not `matthummel-theme`.

### Standard commands

- Build assets: `npm run build` (writes `public/build/`, gitignored). Build at least once
  before expecting styled pages.
- Dev/HMR: `npm run dev`.
- PHP style: `vendor/bin/pint --test` (check) / `vendor/bin/pint` (fix).

### Running the site

WordPress lives **outside the repo** at `~/wp-site` (SQLite, no MySQL). Symlink:

```bash
ln -sfn /workspace ~/wp-site/wp-content/themes/matthummel
cd ~/wp-site && wp theme activate matthummel
cd ~/wp-site && wp server --host=0.0.0.0 --port=8080
```

Site: `http://localhost:8080`. Admin: `http://localhost:8080/wp-admin`
(user `admin`, password `password`).

Gotchas:

- After editing Blade templates, clear compiled views:
  `cd ~/wp-site && wp acorn view:clear`.
- `wp_mail` does not deliver here (no mail server). The contact form still redirects.
- If `~/wp-site` is missing, recreate WordPress with SQLite, symlink as `matthummel`,
  activate the theme, then `wp rewrite structure '/%postname%/'`.
- Portfolio pages seed once via `mh_seed_portfolio_pages()` (`mh_portfolio_seeded_v2`).
  Existing posts and categories are never deleted.
- Edit visitor-facing sentences in **Pages → [page] → Page content (theme)**. Add new
  keys in `app/page-fields.php`, then `\App\field()` in the template.
- Deploy from WordPress: Appearance → Update Theme. Needs a fine-grained GitHub PAT
  (Contents read) saved on that screen or as `MH_GITHUB_TOKEN`. That **installs the
  GitHub zip** over HTTPS. Optional: Actions read/write to click “Rebuild zip on GitHub”.
  WP-CLI: `wp mh theme-update`. The Cursor Cloud `gh` token cannot create releases or
  dispatch workflows (403); use Matt’s PAT on the live site, not the agent token.
- WPVibe desktop Cursor: `.cursor/mcp.json` uses `npx mcp-remote` against
  `https://mcp.wpvibe.ai/mcp`. Do not open that URL in a browser — it is an API
  endpoint (often a blank white page). Connect it in Cursor Settings → MCP; a
  browser window should open for email sign-in.
- WPVibe Cloud Agents: only MCP servers added at https://cursor.com/agents are
  loaded (HTTP URL `https://mcp.wpvibe.ai/mcp`, then OAuth). Project
  `.cursor/mcp.json` is not loaded here; `mcp-remote` is not supported on Cloud
  Agents. Plugin is on the live site (`vibe-ai`). Theme **files** still ship via
  FTP into `wp-content/themes/matthummel/` (not the parent `themes/` folder).
  WPVibe then connects, lists themes, and activates — it cannot replace
  `npm run build` / Composer for Sage. Theme edits on a connected site: draft →
  preview → publish.
