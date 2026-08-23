# Install the theme in WordPress

Do this **after** files are on the server (Update Theme in wp-admin, a `main` push, or a manual upload).

## 0. PHP 8.3 on SiteGround (required)

Sage fatals on 8.2. In SiteGround **Site Tools → Devs → PHP Manager**, set `matthummel.com` to **8.3** (or 8.4). Then flush SuperCacher.

The live site is still the June **block** theme. GitHub has been uploading Sage into a `public_html/…/matthummel` folder that is **not** what nginx serves. After a deploy, in **Site Tools → Site → File Manager** search `style.css` under themes: the copy dated **June 2026** (~2 KB, no “Sage 11” in the header) is the live theme. That is the folder WordPress uses.

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

To use a different headshot: Appearance → Customize → Profile photo. Leave it empty to keep `resources/images/matt-hummel.jpg`.

## 5. Edit page copy

Each portfolio page has a **Page content (theme)** box (same pattern as Ridges & Valleys). Leave a field blank to keep the built-in default. Repeaters restore the built-in list if you remove every row.

Do not hardcode new sentences in Blade — add a row in `app/page-fields.php` and read it with `\App\field('key', __('Default', 'sage'))`.

## 6. Fallback

Keep a default theme (Twenty Twenty-Five) installed so WordPress has a fallback.

## 7. Later deploys

Appearance → **Update Theme** → Update theme from GitHub. Token setup is one-time (see `docs/sage/deployment.md`).

## Local Cursor Cloud

```bash
ln -sfn /workspace ~/wp-site/wp-content/themes/matthummel
cd ~/wp-site && wp theme activate matthummel
cd ~/wp-site && wp acorn view:clear
cd ~/wp-site && wp server --host=0.0.0.0 --port=8080
```

Admin: `admin` / `password`.
