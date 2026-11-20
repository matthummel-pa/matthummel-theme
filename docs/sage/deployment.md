# Deployment

Source: https://roots.io/sage/docs/deployment/

PHP on the build runner must be 8.3 (see `composer.json`). Production Hostinger PHP should be 8.3+.

## What Sage requires

1. `npm run build`
2. `composer install --no-dev --optimize-autoloader`
3. Put `vendor/` and `public/build/` on the server (never upload `node_modules`)

Optional on the server after install: `wp acorn view:clear`.

## How this repo ships

GitHub Actions (`.github/workflows/deploy.yml`) on a push to `main` that changes the theme:

1. Builds Sage (Composer + Vite) and runs Pint
2. Packs the theme (folder `matthummel/` at the zip root, `vendor/` and `public/build/` included)
3. Copies the zip over SSH and swaps `wp-content/themes/matthummel/` on the live site
4. Checks the homepage, REST API, and theme CSS, and swaps the previous folder back if that fails
5. Publishes the same zip to the GitHub Release **`theme-latest`** as the rollback copy

The live site is **[matthummel.com](https://matthummel.com)**. Database, posts, and uploads stay put. PHP **8.3+** is required. Sage 11 / Acorn 6 fatals on 8.2.

A deploy in progress is not cancelled (`concurrency: production-deploy`).

SSH host, port, user, key, and the WordPress path are GitHub `production` environment secrets. Repo variables are `SITE_URL` and `THEME_SLUG`. Do not put those values in the repo, and do not install the zip through hPanel or a staging copy.
