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

## 7. Later deploys (staging first)

Theme updates go to the **staging site** — `https://matthummel-com-524423.hostingersite.com/` ("hummel-dev"), Hostinger's staging copy of matthummel.com. Check the change there. Live (`matthummel.com`) only changes when Matt publishes staging → live in hPanel (Websites → Staging → Publish). Nobody installs the theme on live directly.

On staging: Appearance → **Update Theme** → Update theme from GitHub. Token setup is one-time (see `docs/sage/deployment.md`). Then purge LiteSpeed.

### From the terminal over SSH

Same install, one line, no wp-admin clicks. The build still happens on GitHub (merge to `main` → `theme-latest` release); SSH only runs the install step, and only on staging.

One-time setup, in `~/.ssh/config` on your machine. Values come from hPanel → Websites → the **staging** site → Advanced → **SSH Access**; add your public key on that same page. Keep the real host, port, and username out of this repo — it is public.

```text
Host matthummel-staging
  HostName <IP or host from hPanel>
  Port <port from hPanel>
  User <u… username from hPanel>
  IdentityFile ~/.ssh/id_ed25519
  IdentitiesOnly yes
  ServerAliveInterval 60
```

Deploy to staging after the `Deploy Roots Production Ecosystem` run is green:

```bash
ssh matthummel-staging 'cd ~/websites/*/public_html && wp mh theme-update && wp litespeed-purge all'
```

- WordPress on Hostinger shared hosting lives at `~/websites/<site-id>/public_html`; the glob resolves it so the id is not written down here.
- `wp mh theme-update` is the theme's own command (`app/theme-updater.php`). It needs the GitHub token: `MH_GITHUB_TOKEN` in `wp-config.php`, or the token saved on the Update Theme screen. "No GitHub token is set" means neither is there.
- `wp litespeed-purge all` is the LiteSpeed Cache plugin's command, so cached HTML stops pointing at old Vite hashes.
- The login shell on Hostinger shared hosting is `ksh`, and WP-CLI is at `/usr/local/bin/wp`. Nothing to install.
- Test the key without a password prompt: `ssh -o BatchMode=yes matthummel-staging 'echo ok'`.

Do not keep a live-site alias in regular use. The Hostinger VPS (`srv…hstgr.cloud`) is a separate, empty server; it does not host this site either.

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

## Newsletter

The newsletter is Hostinger Reach, installed from hPanel as the Hostinger Reach plugin. Nothing in the theme stores addresses or sends letters. Put the Reach form block (choose **Use classic Reach Form** so the theme can style it) in Appearance → Widgets → **Footer: Get updates** and in the body of the Get updates page. Sending, confirmation, unsubscribe, and the audience all live in Reach.

Sites that ran the old self-hosted list (theme 3.6.20 to 3.7.14) still have the tables `wp_mh_newsletter`, `wp_mhn_subscribers`, `wp_mhn_events`, and `wp_mhn_archive`. Export those addresses into Reach before you deactivate and delete the old plugin; its uninstall drops its tables.
