# Deployment

Source: https://roots.io/sage/docs/deployment/

PHP on the build runner and on SiteGround must both be 8.3 (see `composer.json`).

## What Sage requires

1. `npm run build`
2. `composer install --no-dev --optimize-autoloader`
3. Put `vendor/` and `public/build/` on the server (never upload `node_modules`)

Optional on the server after install: `wp acorn view:clear`.

## How this repo ships (no FTP required)

GitHub Actions (`.github/workflows/deploy.yml`) on every push to `main`:

1. Builds Sage (Composer + Vite)
2. Zips the theme as `matthummel.zip` (folder `matthummel/` at the zip root)
3. Publishes it to the GitHub Release **`theme-latest`**
4. *Optionally* still tries SiteGround FTP. FTP is best-effort and must not block the zip.

### Install on the live site (HTTPS)

On matthummel.com wp-admin:

1. Fine-grained PAT scoped to `matthummel-theme` with **Contents: Read**
2. Appearance → Update Theme → paste token → Save
3. **Install latest zip from GitHub**

WordPress downloads the zip and overwrites `wp-content/themes/matthummel/`. Database, posts, and uploads stay put.

WP-CLI: `wp mh theme-update` (install zip). Rebuild only: `wp mh theme-build`.

Optional constant: `MH_GITHUB_TOKEN` in wp-config.php.

### SiteGround File Manager (also no FTP)

If the updater PHP is not on the server yet:

1. Open the [theme-latest release](https://github.com/matthummel-pa/matthummel-theme/releases/tag/theme-latest)
2. Download `matthummel.zip`
3. Site Tools → File Manager → `wp-content/themes/`
4. Upload and extract so `matthummel/style.css` sits in that folder (replace the old theme files)

### FTP (legacy, optional)

Still in the workflow with `continue-on-error`. Secrets:

- `SITEGROUND_FTP_HOST`
- `SITEGROUND_FTP_USER`
- `SITEGROUND_FTP_PASSWORD`
- `SITEGROUND_FTP_REMOTE_DIR` (hint only)

PHP **8.3+** is required. Sage 11 / Acorn 6 fatals on 8.2.

A second in-progress deploy is cancelled (`concurrency: siteground-deploy`).
