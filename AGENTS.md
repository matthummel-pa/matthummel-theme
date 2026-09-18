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

Production WordPress is **[matthummel.com](https://matthummel.com)** on Hostinger (theme folder
`wp-content/themes/matthummel/`). Hosting, DNS, and WPVibe safety live in
`.cursor/rules/hostinger-github-wordpress-workflow.mdc`.

Live deploys: push/merge to `main` builds a zip and publishes GitHub Release `theme-latest`.
On Hostinger (`matthummel.com`), Appearance → **Update Theme** downloads that zip over HTTPS.
There is no SiteGround FTP or SSH step. After install, purge LiteSpeed.
The same PAT (Contents: Read) is saved on that screen or as `MH_GITHUB_TOKEN`.
WP-CLI: `wp mh theme-update` (install zip), `wp mh theme-build` (rebuild on GitHub).

Theme zip products (Acreline, WalkRidge): see `docs/SHOP-DOWNLOADS.md`.

## Cursor Cloud specific instructions

The base environment already has PHP 8.3, Composer, Node 22, and WP-CLI. The update script
runs `composer install` and `npm install`. Services are not auto-started.

Vite `base` in `vite.config.js` is `/wp-content/themes/matthummel/public/build/` so it
matches the live Hostinger folder name (`wp-content/themes/matthummel`). Local WordPress
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

Preferred local URL: `http://matthummel-theme.local:8080` (add
`127.0.0.1 matthummel-theme.local` to `/etc/hosts` on the machine that
resolves the name). `http://localhost:8080` and `http://127.0.0.1:8080`
still work. Cloud Simple Browser uses the forwarded port (`127.0.0.1:8080`)
unless that hostname is in the client hosts file. PHP `wp server` is HTTP,
not Herd HTTPS on `:443`. Admin: `admin` / `password`. Acreline, when present,
is `~/wp-acreline-site` on port **8081**. Bind `wp server` to `0.0.0.0`, not
`127.0.0.1` only.

Cloud Simple Browser is isolated from VM localhost. Declaring
`.cursor/environment.json` `ports` (8080 / 8081) is what lets Cursor
forward them. After that file is on the branch, Save the Cloud environment
(or Ports panel) so the tunnel exists; this VM cannot inject it by itself.

### Local WordPress isolation (one install per product)

`~/wp-site` and port **8080** are **matthummel only**. Do not activate Acreline (or any other product theme) on this WordPress.

Each product gets its own site directory, theme symlink (folder name = that repo’s Vite `base`), and port:

| Product | Site dir | Theme folder | Port |
| --- | --- | --- | --- |
| matthummel | `~/wp-site` | `matthummel` | **8080** |
| Acreline / wp-acreline | `~/wp-acreline-site` | usually `acreline` | **8081** |
| Next new WP project | `~/wp-<slug>-site` | match Vite `base` | **8082+** (next free) |

Run both at once in separate tmux sessions (`wp server --host=0.0.0.0 --port=<port>`). Do not `pkill -f`. Local admin is `admin` / `password` unless already set. SQLite drop-in still goes in **before** `wp core install` (gotcha below). Do not mix `theme-latest` zips between sites.

User skill: `sage-docs` (Cursor user skills store).

Gotchas:

- After editing Blade templates, clear compiled views:
  `cd ~/wp-site && wp acorn view:clear`.
- `wp_mail` does not deliver here (no mail server). The contact form still redirects.
- If `~/wp-site` is missing, recreate WordPress with SQLite, symlink as `matthummel`,
  activate the theme, then `wp rewrite structure '/%postname%/'`. There is no MySQL,
  so the SQLite drop-in must be placed **before** any WP bootstrap: `wp core download`,
  `wp config create --skip-check`, then manually unzip the `sqlite-database-integration`
  plugin into `wp-content/plugins/` and copy its `db.copy` to `wp-content/db.php`
  (replacing the `{SQLITE_IMPLEMENTATION_FOLDER_PATH}` / `{SQLITE_PLUGIN}` placeholders).
  Do **not** `wp plugin install` first — WP-CLI needs the mysqli extension to bootstrap
  and errors out until `db.php` is in place. Only then run `wp core install`.
  The repo checkout may live at `/agent/repos/matthummel-theme` (not `/workspace`);
  symlink from wherever the repo actually is.
- Portfolio pages seed once via `mh_seed_portfolio_pages()` (`mh_portfolio_seeded_v2`).
  Existing posts and categories are never deleted.
- Edit visitor-facing sentences in **Pages → [page] → Page content (theme)**. Add new
  keys in `app/page-fields.php`, then `\App\field()` in the template.
- Deploy from WordPress: Appearance → Update Theme. Needs a fine-grained GitHub PAT
  (Contents read) saved on that screen or as `MH_GITHUB_TOKEN`. That **installs the
  GitHub zip** over HTTPS. Optional: Actions read/write to click “Rebuild zip on GitHub”.
  WP-CLI: `wp mh theme-update`. The Cursor Cloud `gh` token cannot create releases or
  dispatch workflows (403); use Matt’s PAT on the live site, not the agent token.
- WPVibe MCP URL is `https://mcp.wpvibe.ai/mcp` (API endpoint — a browser tab
  is usually a blank page). `.cursor/mcp.json` uses native HTTP (`url`), not
  `mcp-remote`. Cursor Cloud Agents do not support SSE or `mcp-remote`.
- Desktop Cursor: Settings → MCP should pick up `wpvibe` from the project file.
  First use opens a WPVibe email sign-in (6-digit code in the subject line).
- Cloud Agents: add the same HTTP URL once at https://cursor.com/agents
  (MCP dropdown → custom HTTP server, no client ID/secret), then complete
  OAuth. Project `.cursor/mcp.json` is not loaded in Cloud Agent VMs.
  Plugin is on the live site (`vibe-ai`). Theme **files** still ship as the
  `theme-latest` zip into `wp-content/themes/matthummel/` (not the parent `themes/` folder).
  WPVibe then connects, lists themes, and activates — it cannot replace
  `npm run build` / Composer for Sage. Theme edits on a connected site: draft →
  preview → publish.
