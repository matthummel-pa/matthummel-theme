# Deployment

Source: https://roots.io/sage/docs/deployment/

PHP on the build runner and on SiteGround must both be 8.3 (see `composer.json`).

## What Sage requires

1. `npm run build`
2. `composer install --no-dev --optimize-autoloader`
3. Upload the theme **except** `node_modules`

Optional on the server after upload: `wp acorn optimize` (caches config and Blade views).

## This repo

GitHub Actions (`.github/workflows/deploy.yml`) does steps 1–3 on every push to `main`, then **parallel FTP** (`lftp mirror --parallel=12`) to:

`wp-content/themes/matthummel`

on SiteGround. The first upload is still the slow one (thousands of files in `vendor/`). Later deploys only send changed files and should take a few minutes.

Faster still (not wired up): SiteGround **SSH + rsync**. That needs extra secrets (`SITEGROUND_SSH_HOST`, key). FTP is what we have today.

Workflow: edit in Cursor → commit → merge/push `main` → Action deploys → https://matthummel.com updates.

Required GitHub Actions secrets:

- `SITEGROUND_FTP_HOST`
- `SITEGROUND_FTP_USER`
- `SITEGROUND_FTP_PASSWORD`
- `SITEGROUND_FTP_REMOTE_DIR` (example: `/public_html/wp-content/themes/matthummel/`)

## Blade templates must not be public

Apache rule (also in theme `.htaccess`):

```apache
<FilesMatch ".+\.(blade\.php)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</FilesMatch>
```
