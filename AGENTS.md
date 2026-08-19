# AGENTS.md

This repository is the **Matt Hummel** WordPress theme — a Sage 11 (Roots) theme built on
Blade + Tailwind v4 + Vite + Acorn (Laravel-in-WordPress), PHP 8.3. The repo root *is* the
theme folder (it is meant to live at `wp-content/themes/matthummel-theme`).

For human-facing docs see `README.md`, `docs/DEVELOPMENT.md`, and `CONTRIBUTING.md`.

## Cursor Cloud specific instructions

The base environment already has PHP 8.3 (+ `mbstring curl xml sqlite3 gd zip intl bcmath`),
Composer, Node 22, and WP-CLI installed. The update script runs `composer install` and
`npm install` on startup. Nothing else is auto-run — services must be started manually.

### Standard commands (see `package.json`, `composer.json`, `.github/workflows/ci.yml`)

- Build assets: `npm run build` (outputs to `public/build/`, which is gitignored). Assets
  must be built at least once before the theme renders styled; a snapshot preserves a prior
  build, but rebuild after changing `resources/css` or `resources/js`.
- Dev/HMR: `npm run dev` (Vite dev server on port 3000).
- PHP lint: `vendor/bin/pint --test` (check) / `vendor/bin/pint` (fix). Note: the committed
  code currently has many Pint style deviations; CI runs this with `continue-on-error`, so a
  non-zero exit from `--test` is expected and not a regression you introduced.
- There is no automated PHP/JS test suite; CI only builds and lints.

### Running the site (local WordPress stack)

A ready-to-run WordPress site lives **outside the repo** at `~/wp-site` (WordPress core +
SQLite drop-in, no MySQL). The repo is symlinked in as the active theme:
`~/wp-site/wp-content/themes/matthummel-theme -> /workspace`. This site is part of the base
snapshot; the update script does not recreate it.

Start the server (foreground / long-running — use a tmux session or a `terminals` entry):

```bash
cd ~/wp-site && wp server --host=0.0.0.0 --port=8080
```

Then the site is at `http://localhost:8080` and admin at `http://localhost:8080/wp-admin`
(user `admin`, password `password`).

Gotchas:

- After editing Blade templates (`resources/views/*.blade.php`), clear the compiled view
  cache: `cd ~/wp-site && wp acorn view:clear`. Stale compiled views are a common cause of
  changes "not showing up".
- `wp_mail` does not deliver on this stack (no mail server); the contact/newsletter logic
  still runs but no email is sent.
- If `~/wp-site` is ever missing (e.g. snapshot not preserved), recreate it with:
  `wp core download`, install the `sqlite-database-integration` plugin and copy its
  `db.copy` to `wp-content/db.php` (fill the two path placeholders), `wp config create
  --dbname=wp --dbuser=root --skip-check`, `wp core install --url=http://localhost:8080 ...`,
  symlink `/workspace` to `wp-content/themes/matthummel-theme`, then
  `wp theme activate matthummel-theme` and `wp rewrite structure '/%postname%/'`.
- Load the two demo GitHub project posts (optional) from `matthummel-example-projects.wxr`
  via **Tools → Import** or `wp import matthummel-example-projects.wxr --authors=create`.
