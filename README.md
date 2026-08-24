# Matt Hummel theme

Sage 11 WordPress theme for [matthummel.com](https://matthummel.com). Built with [Roots Sage](https://roots.io/sage/) 11.2.1, Blade templates, Tailwind v4, and Vite 8.

---

## Architecture

| File / folder | Role |
| --- | --- |
| `app/portfolio.php` | Social links, Ridges & Valleys work list, GitHub highlights, DEV.to feed, one-time page seed |
| `app/page-fields.php` | `Page content (theme)` field registry — all visitor-facing copy lives here |
| `app/contact.php` | Plugin-free contact form (`mh_contact` action, nonce, honeypot) |
| `app/Github.php` | Cached GitHub API helper (profile, repos, activity, contributions) |
| `app/filters.php` | Document title / meta description overrides (Gettysburg SEO format) |
| `app/bespoke.php` | Disables Gutenberg on pages, strips block patterns, skips core stylesheets |
| `app/icons.php` | `mh_svg_icon()` — inline SVG with `currentColor` for brand icons |
| `app/comments.php` | ASCII markdown in comments, write/preview, `wptexturize` off |
| `app/cache-headers.php` | `no-cache` on HTML so SiteGround's proxy doesn't serve stale Vite hashes |
| `app/theme-updater.php` | Appearance → Update Theme + `wp mh theme-update` / `wp mh theme-build` CLI commands |
| `app/db-migrate.php` | `wp mh db-pull` / `wp mh db-push` — DB sync to/from SiteGround over SSH |
| `resources/css/portfolio.css` | All theme styles: Inter headings, IBM Plex body, navy/blue/gray palette, light mode only |
| `resources/js/app.js` | Minimal JS: mobile menu, search, writing tools, work tools, code blocks |
| `resources/views/template-*.blade.php` | Named Blade page templates (Home, About, Work, Services, Code, Journal, Contact, Now) |
| `resources/views/partials/` | Reusable Blade partials (hero, cards, sidebar, comments, etc.) |
| `.github/workflows/deploy.yml` | CI: build → zip → publish GitHub Release `theme-latest` → optional FTP |
| `.github/scripts/preserve-vite-assets.py` | Keeps previous Vite hashes in the release so cached HTML never 404s |
| `.github/scripts/db-pull.sh` | Shell-only DB pull (no WP bootstrap required) |

Existing WordPress **posts and categories are never deleted**. Gutenberg block patterns (core and remote) are turned off on all pages; posts keep the block editor.

---

## Requirements

| Tool | Version |
| --- | --- |
| PHP | 8.3 |
| Node | 22 |
| Composer | 2 |
| WordPress | 6.6+ |

---

## Local development

```bash
composer install
npm install
npm run build
```

WordPress must use the folder name `matthummel` to match the Vite `base` in `vite.config.js`:

```bash
ln -sfn /path/to/this/repo wp-content/themes/matthummel
wp theme activate matthummel
wp acorn view:clear
```

Start the dev server for HMR:

```bash
npm run dev
```

Check PHP code style:

```bash
vendor/bin/pint --test   # check only
vendor/bin/pint          # fix
```

Cursor Cloud WordPress notes: [`AGENTS.md`](AGENTS.md).

---

## WP-CLI commands

| Command | What it does |
| --- | --- |
| `wp mh theme-update` | Download and install the `theme-latest` GitHub Release zip over HTTPS |
| `wp mh theme-build` | Trigger a new CI build (dispatch `deploy.yml`) |
| `wp mh db-pull` | Export prod DB via SSH → import locally → search-replace URLs |
| `wp mh db-push` | Export local DB → upload to prod via SSH → import + search-replace (`--yes` required) |

SSH credentials for `db-pull` / `db-push` resolve in order: WP-CLI `--ssh-*` flags → `MH_SSH_*` constants in `wp-config.php` → `SITEGROUND_*` / `SERVER_*` environment variables.

---

## Deploy

Push to `main` triggers `.github/workflows/deploy.yml`:

1. Runs `composer install --no-dev` + `npm run build`
2. Zips the theme as `matthummel.zip` (folder `matthummel/` at the zip root)
3. Publishes it to GitHub Release **`theme-latest`**
4. Optionally uploads to SiteGround over FTP (best-effort, non-blocking)

**Live site install:** Appearance → Update Theme → paste a fine-grained PAT (Contents: Read on `matthummel-theme`) → Install latest zip from GitHub.

See [`docs/sage/deployment.md`](docs/sage/deployment.md) for full deploy and token setup instructions.

---

## Editing page copy

All visitor-facing text is edited in **wp-admin → Pages → [page] → Page content (theme)**. Field defaults are in `app/page-fields.php`. Leave a field blank to use the built-in default.

Do not hardcode marketing copy in Blade — add a key in `app/page-fields.php` and read it with:

```php
\App\field('key', __('Default text', 'sage'))
```

---

## Debugging and error logs

### WordPress debug log

Enable in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);   // writes to wp-content/debug.log
define('WP_DEBUG_DISPLAY', false);
```

Tail locally:

```bash
tail -f ~/wp-site/wp-content/debug.log
```

### Vite manifest errors

`RuntimeException: Vite manifest not found` means `public/build/manifest.json` is missing. Run `npm run build` or check the GitHub Actions log for a failed build step.

### GitHub API failures

`App\Github` fails soft — if the API is down or rate-limited, transient data is returned or the section renders empty. Check the transient in wp-admin → Tools → Site Health → Info, or clear it with:

```bash
wp transient delete mh_github_profile
wp transient delete mh_github_repos
```

### Acorn view cache

After editing any Blade template, clear compiled views:

```bash
cd ~/wp-site && wp acorn view:clear
```

A stale view cache is the most common reason a template change doesn't appear on the site.

### Contact form

`wp_mail` does not deliver in the Cursor Cloud environment (no mail server). The form still validates, nonces, and redirects — it just doesn't send email locally. On the live site, verify via a standard SMTP plugin if mail is not arriving.

### SiteGround deploy

The `deploy.yml` workflow runs a secrets check step before uploading. If FTP credentials are missing or wrong, the step prints which secrets are absent. View the run log in GitHub → Actions → Deploy Roots Production Ecosystem.

See [`docs/ERRORS.md`](docs/ERRORS.md) for a full error reference.

---

## Documentation

| Doc | What's in it |
| --- | --- |
| [CHANGELOG.md](CHANGELOG.md) | Version history |
| [docs/ERRORS.md](docs/ERRORS.md) | Error reference and debug cheatsheet |
| [docs/FEATURES.md](docs/FEATURES.md) | Feature log and dev tools reference |
| [docs/INSTALL.md](docs/INSTALL.md) | WordPress install after a deploy |
| [docs/sage/deployment.md](docs/sage/deployment.md) | SiteGround deploy and token setup |
| [docs/sage/theme-templates.md](docs/sage/theme-templates.md) | Blade template hierarchy |
| [Sage docs](https://roots.io/sage/docs/) | Official Roots reference |

---

## License

MIT. Sage is MIT ([Roots](https://roots.io/sage/)).
