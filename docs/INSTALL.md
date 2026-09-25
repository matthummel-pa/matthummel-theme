# Install the theme in WordPress

Do this **after** files are on the server (Update Theme in wp-admin, a `main` push, or a manual upload).

## 0. PHP 8.3+ on Hostinger (required)

Sage fatals on 8.2. In Hostinger hPanel, set `matthummel.com` to **PHP 8.3** or newer. Then purge LiteSpeed cache.

Production is **[matthummel.com](https://matthummel.com)**. Theme folder is `wp-content/themes/matthummel/`.

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

## 7. Later deploys

Appearance → **Update Theme** → Update theme from GitHub. Token setup is one-time (see `docs/sage/deployment.md`). Then purge LiteSpeed.

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

## Local Cursor Cloud

```bash
ln -sfn /workspace ~/wp-site/wp-content/themes/matthummel
cp /workspace/mu-plugins/rank-math-rest-meta.php ~/wp-site/wp-content/mu-plugins/
cd ~/wp-site && wp theme activate matthummel
cd ~/wp-site && wp acorn view:clear
cd ~/wp-site && wp server --host=0.0.0.0 --port=8080
```

Admin: `admin` / `password`.

## Newsletter plugin

The self-hosted newsletter is not part of the theme zip. A push to `main` publishes GitHub Release `matthummel-newsletter-latest` (asset `matthummel-newsletter.zip`), next to `theme-latest`. You can also pack it locally:

```bash
bash .github/scripts/pack-plugin.sh
```

In wp-admin, upload `matthummel-newsletter.zip` via Plugins → Add New → Upload. Activate it. That creates the Get updates and Email preferences pages if they are missing, and copies any existing footer signups onto the list. Activation does not send email. Running it again does not add a second copy of those addresses.

Leave automatic sending off until the From address is a mailbox on your domain and SPF, DKIM, and DMARC are in place. Sending uses `wp_mail`, so an SMTP plugin on the site is used when one is installed.
