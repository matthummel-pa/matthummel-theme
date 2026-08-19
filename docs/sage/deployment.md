# Deployment

Source: https://roots.io/sage/docs/deployment/

PHP on the build runner and on SiteGround must both be 8.3 (see `composer.json`).

## What Sage requires

1. `npm run build`
2. `composer install --no-dev --optimize-autoloader`
3. Upload the theme **except** `node_modules`

Optional on the server after upload: `wp acorn optimize` (caches config and Blade views).

## This repo

GitHub Actions (`.github/workflows/deploy.yml`) does steps 1–3 on every push to `main`, then FTP-uploads to:

`wp-content/themes/matthummel`

on SiteGround. The **first** upload of `vendor/` is slow (many small files). After that, the action only sends changed files.

A second in-progress deploy is cancelled (`concurrency: siteground-deploy`).

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
