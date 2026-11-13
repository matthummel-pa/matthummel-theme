# Install the theme in WordPress

Develop on local WordPress Studio. A merge to `main` deploys the theme over SSH to the live site, **[matthummel.com](https://matthummel.com)**. Do not install the theme on a Hostinger staging copy, and do not publish staging → live from hPanel.

Do the steps below on the live site after that deploy, or on the local Studio site while you are still editing.

## 0. PHP 8.3+ on Hostinger (required)

Sage fatals on 8.2. In Hostinger hPanel, set **matthummel.com** to **PHP 8.3** or newer. Then purge LiteSpeed cache.

Theme folder is `wp-content/themes/matthummel/`.

## 1. Folder name

The theme directory must be `matthummel`:

```text
wp-content/themes/matthummel/
  style.css
  functions.php
  vendor/
  public/build/manifest.json
  …
```

Vite looks for assets at `/wp-content/themes/matthummel/public/build/`. A different folder name 404s CSS/JS.

## 2. Built files must be present

The deploy Action builds these. If you copied git only, run on a machine with Node 22 and PHP 8.3:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Then upload everything **except** `node_modules`.

If you see `Vite manifest not found`, the build step did not run.

## 3. Activate

**wp-admin:** Appearance → Themes → **Matt Hummel** → Activate.

**WP-CLI:**

```bash
wp theme activate matthummel
wp acorn view:clear
wp rewrite flush
```

Optional after deploy: `wp acorn optimize`.

## 4. Pages and menu

On first activation, `mh_seed_portfolio_pages()` creates Home, About, Work (`/projects/`), Services, Code, Contact, Now, and Journal (`/blog/` as the posts page), plus a Primary menu. It does **not** delete posts or categories.

If pages already exist from an earlier seed (`mh_portfolio_seeded_v2`), they are left as-is.

To use a different headshot: Appearance → Customize → Profile photo. Leave it empty to use your GitHub profile photo (then the bundled `resources/images/matt-hummel.jpg` if GitHub is unreachable).

## 5. Edit page copy

Each portfolio page has a **Page content (theme)** box. Leave a field blank to keep the built-in default. Repeaters restore the built-in list if you remove every row.

Do not hardcode new sentences in Blade — add a row in `app/page-fields.php` and read it with `\App\field('key', __('Default', 'sage'))`.

## 6. Fallback

Keep a default theme (Twenty Twenty-Five) installed so WordPress has a fallback.

## 7. Later deploys (SSH to the live site)

Theme updates go to **[matthummel.com](https://matthummel.com)** when `main` changes. GitHub Actions builds the theme, copies it over SSH, swaps the theme folder, checks the homepage, and rolls back if that check fails. The GitHub `production` environment holds `SITE_URL` (`https://matthummel.com`), `THEME_SLUG` (`matthummel`), and the SSH secrets. Do not install a zip on a staging copy.

The server script clears Acorn and LiteSpeed after the swap. If HTML is still stale, clear cache in hPanel for the live site.

### SSH access

The GitHub Action is the deploy. It uses the SSH key stored in the `production` environment, not a key in this repo. Add that public key on the live site (hPanel → the matthummel.com website → Advanced → SSH Access). Keep the host, port, and username in GitHub secrets.

WordPress on this account lives at `~/websites/<site-id>/public_html`. That path is `HOSTINGER_WP_PATH`. The login shell is `ksh`, and WP-CLI is at `/usr/local/bin/wp`.

`wp mh theme-update` (`app/theme-updater.php`) still installs a GitHub zip when you run it by hand. The normal path is the folder swap in `server-deploy.sh`, which does not need `MH_GITHUB_TOKEN`.

## Must-use plugins (optional)

Companion MU plugins live in the repo under `mu-plugins/`. The theme zip includes them; `functions.php` loads the Rank Math REST meta file when it is **not** already installed at `wp-content/mu-plugins/`.

**Rank Math REST Meta** — exposes `rank_math_focus_keyword`, `rank_math_title`, and `rank_math_description` on posts in the REST API for users who can `edit_posts`.

Optional real must-use install (runs earlier than the theme; same file):

```bash
# Local Cloud
cp /workspace/mu-plugins/rank-math-rest-meta.php ~/wp-site/wp-content/mu-plugins/

# Live (Hostinger File Manager)
# Copy to: public_html/wp-content/mu-plugins/rank-math-rest-meta.php
```

## Local (Linux, SQLite)

```bash
ln -sfn /workspace ~/wp-site/wp-content/themes/matthummel
cp /workspace/mu-plugins/rank-math-rest-meta.php ~/wp-site/wp-content/mu-plugins/
cd ~/wp-site && wp theme activate matthummel
cd ~/wp-site && wp acorn view:clear
cd ~/wp-site && wp server --host=0.0.0.0 --port=8080
```

Admin: `admin` / `password`.

## Newsletter

The newsletter is Hostinger Reach, installed from hPanel as the Hostinger Reach plugin. Nothing in the theme stores addresses or sends letters. Put the Reach form block (choose **Use classic Reach Form** so the theme can style it) in Appearance → Widgets → **Footer: Get updates** and in the body of the Get updates page. Sending, confirmation, unsubscribe, and the audience all live in Reach.

Sites that ran the old self-hosted list (theme 3.6.20 to 3.7.14) still have the tables `wp_mh_newsletter`, `wp_mhn_subscribers`, `wp_mhn_events`, and `wp_mhn_archive`. Export those addresses into Reach before you deactivate and delete the old plugin; its uninstall drops its tables.
