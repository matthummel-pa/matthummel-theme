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

on SiteGround. `SITEGROUND_FTP_REMOTE_DIR` must be **that theme folder**, including the trailing slash, for example:

`/public_html/wp-content/themes/matthummel/`

If the secret points at `public_html/` or `wp-content/themes/` (the parent), Sage files never replace the live theme. After a deploy, Appearance → Themes should show **Matt Hummel 3.0.x** with description “Sage 11…”. If it still says a block/FSE theme, the FTP path is wrong.

The **first** upload of `vendor/` is slow (many small files). After that, the action only sends changed files.

Live PHP is currently **8.2.33**. Composer allows `>=8.2`. Prefer PHP 8.3 in SiteGround Site Tools when you can, so it matches local/CI.

A second in-progress deploy is cancelled (`concurrency: siteground-deploy`).

Workflow: Appearance → Update Theme (or push `main`) → Action builds + FTP → https://matthummel.com updates.

Required GitHub Actions secrets (FTP):

- `SITEGROUND_FTP_HOST`
- `SITEGROUND_FTP_USER`
- `SITEGROUND_FTP_PASSWORD`
- `SITEGROUND_FTP_REMOTE_DIR` (example: `/public_html/wp-content/themes/matthummel/`)

## Update from wp-admin

Same as Ridges & Valleys. On the live site (or any install of this theme):

1. Create a fine-grained PAT scoped to `matthummel-theme` with **Actions: Read and write** and **Contents: Read-only**.
2. Appearance → Update Theme → paste token → Save.
3. Click **Update theme from GitHub**. That calls `workflow_dispatch` on `deploy.yml`.

WP-CLI: `wp mh theme-update`. Optional constant: `MH_GITHUB_TOKEN` in wp-config.php.

The first time this updater itself is missing on the server, push `main` once so FTP can drop the new PHP files. After that, use the button.

## WPVibe (AI on the live site)

The **WPVibe** plugin (`vibe-ai`) is installed on matthummel.com. It does **not** build Sage (no Vite/Composer). Use it to connect, inspect, run WP-CLI, and activate the theme **after** files are on the server.

1. Add WPVibe as an **HTTP** MCP server (`https://mcp.wpvibe.ai/mcp`) in Cursor **and** in [Cloud Agents MCP](https://cursor.com/agents). Cloud Agents do not load project `.cursor/mcp.json` and do **not** support `mcp-remote`.
2. Sign in with email + 6-digit code.
3. In wp-admin → WPVibe, finish Connect so the badge is not “Not Connected”.
4. Ask the agent to `list_sites` → `site_info` → `run_wp_cli` (`theme list`, `theme activate matthummel`).

## Blade templates must not be public

Apache rule (also in theme `.htaccess`):

```apache
<FilesMatch ".+\.(blade\.php)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</FilesMatch>
```
