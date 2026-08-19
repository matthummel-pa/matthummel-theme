# AGENTS.md

This repository is the **Matt Hummel** WordPress theme — Sage 11.2.1 (Roots) with Blade,
Tailwind v4, Vite, and Acorn. The repo root *is* the theme folder.

A thin portfolio layer lives in `app/portfolio.php`, `app/contact.php`, `app/Github.php`,
`resources/css/portfolio.css`, and the `template-*.blade.php` views. Stock Sage files
(`app/setup.php`, `app/filters.php`) stay close to upstream.

Sage docs (also listed in `.cursor/docs.json`):

- https://roots.io/sage/docs/
- https://roots.io/sage/docs/theme-templates/
- Local notes: `docs/sage/`
- Feature log: `docs/FEATURES.md`
- Install after deploy: `docs/INSTALL.md`
- Changelog: `CHANGELOG.md`

Live deploys: merge/push `main` → `.github/workflows/deploy.yml` builds assets and FTP-uploads to SiteGround `wp-content/themes/matthummel`. Do not upload `node_modules`. PHP 8.3 must match production.

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
