# Matt Hummel — WordPress theme

Sage 11 theme for **[matthummel.com](https://matthummel.com)** — portfolio, services, and journal for a WordPress / full-stack developer in Gettysburg, PA.

No page builders. Visitor copy lives in wp-admin (**Page content (theme)**), not hardcoded in Blade. Front end is Blade + Tailwind + a few small JS modules. PHP does the heavy lifting; Vite ships the assets.

| | |
| --- | --- |
| **Live site** | [matthummel.com](https://matthummel.com) |
| **Version** | `3.1.31` · [CHANGELOG](CHANGELOG.md) · [public changelog](https://matthummel.com/changelog/) |
| **Stack** | Sage 11.2.1 · PHP 8.3 · Acorn 6 · Blade · Tailwind v4 · Vite 8 · WordPress 6.6+ |
| **Release** | [`theme-latest`](https://github.com/matthummel-pa/matthummel-theme/releases/tag/theme-latest) (CI zip for Appearance → Update Theme) |
| **License** | [MIT](LICENSE.md) |

---

## Activity feed

Recent ship log — useful if you’re cloning, reviewing a PR, or picking up where CI left off. Full history: [`CHANGELOG.md`](CHANGELOG.md) · live list: [matthummel.com/changelog](https://matthummel.com/changelog/).

```text
● 2026-08-31  3.1.31   Marketplace files + audit: screenshot, readme.txt, CREDITS; submit Acreline
● 2026-08-31  3.1.30   Project singles at /projects/{slug}/; hero screenshot; buyer docs
● 2026-08-31  3.1.29   WooCommerce Blade shop/product templates and classic Cart/Checkout pages
● 2026-08-31  3.1.27   Contact and /start/ forms POST to the n8n CRM webhook
● 2026-08-29  3.1.26   Concept pages honor Rank Math / Yoast title and meta description
● 2026-08-28  3.1.25   Projects admin: Generate featured image (DALL·E → Media → Work card)
● 2026-08-28  3.1.24   Concept custom fields + On site toggles, filters, newest-first Work grid
● 2026-08-28  3.1.23   On-site concept pages at /concept/{slug}/ (non-live stay 404)
● 2026-08-28  3.1.22   Projects CPT: Ridges & Valleys concepts, Show on site / Hide from site
● 2026-08-27  3.1.21   Post editor: Generate featured image (shared OpenAI key with DEV.to)
● 2026-08-27  3.1.20   Journal CTA + author bio: WordPress platforms + full-stack web apps
● 2026-08-27  3.1.18   Social share (Bluesky / LinkedIn / Facebook / Reddit) + DEV.to helpers
```

| Watch this | Why |
| --- | --- |
| [CHANGELOG.md](CHANGELOG.md) | Semver release notes (source of truth in git) |
| [docs/ERRORS.md](docs/ERRORS.md) | Debug log, Vite/manifest fatals, SQLite, deploy gotchas |
| [docs/FEATURES.md](docs/FEATURES.md) | What ships where (templates, CPT, WP-CLI map) |
| [Actions](https://github.com/matthummel-pa/matthummel-theme/actions) | Build → zip → `theme-latest` |
| [Commits on `main`](https://github.com/matthummel-pa/matthummel-theme/commits/main) | PR-sized diffs |

Stuck on a white screen or missing CSS? Start in the [error cheatsheet](docs/ERRORS.md) — enable `WP_DEBUG_LOG`, then `tail -f ~/wp-site/wp-content/debug.log`.

---

## Who it’s for

| Audience | What they get |
| --- | --- |
| Shops and local businesses | Example projects, plain-language WordPress options, a clear way to start a project |
| Agencies and developers | Stack, open-source code, overflow / hire paths |
| Recruiters | Experience, GitHub, availability |

---

## What’s in the theme

- Named Blade page templates (Home, About, Services, Hire, Work, Code, Contact, Now, Uses, …)
- Projects CPT with on-site project pages at `/projects/{slug}/`
- Plugin-free contact + project brief forms (nonce, honeypot)
- GitHub API showcase (profile, contribs, activity, repos) via transients
- Journal with share intents, comments, and DEV.to / Bluesky helpers
- Appearance → **Update Theme** installs the `theme-latest` GitHub Release zip

Deep feature list: [`docs/FEATURES.md`](docs/FEATURES.md). Architecture deep-dive: [`docs/THEME.md`](docs/THEME.md).

---

## Full-stack toolbox

Day-to-day links a full-stack WordPress / Sage developer actually opens.

### Changelogs & ship history

| Resource | Use it when |
| --- | --- |
| [CHANGELOG.md](CHANGELOG.md) | You need exact version notes before updating live |
| [matthummel.com/changelog](https://matthummel.com/changelog/) | Public-facing “what changed” |
| [`theme-latest` release](https://github.com/matthummel-pa/matthummel-theme/releases/tag/theme-latest) | Grab the built zip CI publishes |
| [GitHub Actions](https://github.com/matthummel-pa/matthummel-theme/actions) | CI failed, or you want the last green build |

### Errors & debugging

| Resource | Use it when |
| --- | --- |
| [docs/ERRORS.md](docs/ERRORS.md) | Manifest missing, Blade cache stale, PHP version, SQLite bootstrap |
| [WordPress debugging](https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/) | Official `WP_DEBUG` / `WP_DEBUG_LOG` reference |
| `~/wp-site/wp-content/debug.log` | Local runtime errors (Cloud / SQLite setup) |
| SiteGround `~/public_html/wp-content/debug.log` | Production PHP fatals over SSH |

### Framework & build docs

| Layer | Official | In this repo |
| --- | --- | --- |
| **Sage 11** | [roots.io/sage/docs](https://roots.io/sage/docs/) | [`docs/sage/`](docs/sage/) |
| Theme templates | [Theme templates](https://roots.io/sage/docs/theme-templates/) | [`docs/sage/theme-templates.md`](docs/sage/theme-templates.md) |
| Compiling assets | [Compiling assets](https://roots.io/sage/docs/compiling-assets/) | `npm run build` / `npm run dev` |
| Deployment | [Sage deployment](https://roots.io/sage/docs/deployment/) | [`docs/sage/deployment.md`](docs/sage/deployment.md) · [`docs/INSTALL.md`](docs/INSTALL.md) |
| **Acorn** | [roots.io/acorn](https://roots.io/acorn/) | `wp acorn view:clear` |
| **Tailwind v4** | [tailwindcss.com](https://tailwindcss.com/docs) | `resources/css/app.css` `@theme` |
| **Vite** | [vite.dev](https://vite.dev/guide/) | `vite.config.js` (`base` = `/wp-content/themes/matthummel/public/build/`) |
| **WordPress** | [developer.wordpress.org](https://developer.wordpress.org/) | Template hierarchy still applies |
| **Blade** | [Laravel Blade](https://laravel.com/docs/blade) | `resources/views/` |
| Roots community | [discourse.roots.io](https://discourse.roots.io/) | Ask Sage / Acorn questions |

### Live site surfaces (dev-facing)

| URL | Why open it |
| --- | --- |
| [matthummel.com/code](https://matthummel.com/code/) | Live GitHub panel this theme powers |
| [matthummel.com/uses](https://matthummel.com/uses/) | Stack and tools list |
| [matthummel.com/changelog](https://matthummel.com/changelog/) | Public release notes |
| [matthummel.com/accessibility](https://matthummel.com/accessibility/) | WCAG / 508 statement |

---

## Quick start

```bash
composer install && npm install && npm run build

# Symlink into WordPress (folder name must be matthummel)
ln -sfn /path/to/this/repo ~/wp-site/wp-content/themes/matthummel

cd ~/wp-site
wp theme activate matthummel
wp acorn view:clear
wp rewrite structure '/%postname%/'
wp server --host=0.0.0.0 --port=8080
```

Site: `http://localhost:8080` · Admin: `admin` / `password`

| Command | Purpose |
| --- | --- |
| `npm run build` | Compile assets to `public/build/` (required; gitignored) |
| `npm run dev` | Vite HMR |
| `vendor/bin/pint` | Fix PHP style |
| `wp acorn view:clear` | Clear Blade cache after template edits |
| `wp mh theme-update` | Install `theme-latest` zip on a WordPress install |
| `wp mh theme-build` | Dispatch CI to rebuild the release zip |
| `wp mh db-pull` | Pull production DB over SSH → local (see FEATURES) |

Full local / Cloud setup: [`AGENTS.md`](AGENTS.md). Hit a snag: [`docs/ERRORS.md`](docs/ERRORS.md).

---

## Deploy

Push or merge to `main` runs GitHub Actions:

1. Composer + `npm run build`
2. Publish Release **`theme-latest`**
3. Optional FTP / SSH rsync to SiteGround

Live theme path: `wp-content/themes/matthummel/`.

On the server: **Appearance → Update Theme** (fine-grained PAT, Contents: Read) or `wp mh theme-update`.

Details: [`docs/INSTALL.md`](docs/INSTALL.md) · [`docs/sage/deployment.md`](docs/sage/deployment.md) · [workflow](.github/workflows/deploy.yml).

---

## Repo layout

```
app/                 PHP (fields, contact, GitHub, updater, …)
resources/views/     Blade templates, sections, partials
resources/css/       Tailwind @theme + portfolio styles
resources/js/        Small front-end modules
public/build/        Vite output (CI only — never commit)
.github/workflows/   Deploy pipeline
docs/                Feature log, errors, install, theme deep-dive, Sage notes
```

Architecture, page map, SEO, a11y, and security notes: [`docs/THEME.md`](docs/THEME.md).

---

## Docs map

| Doc | Contents |
| --- | --- |
| [docs/MARKETPLACE.md](docs/MARKETPLACE.md) | ThemeForest / WordPress.org: do not upload this theme; submit Acreline |
| [docs/THEME.md](docs/THEME.md) | Full theme documentation (architecture, SEO, a11y, security) |
| [docs/FEATURES.md](docs/FEATURES.md) | Feature log and WP-CLI reference |
| [docs/INSTALL.md](docs/INSTALL.md) | Activate after deploy |
| [docs/ERRORS.md](docs/ERRORS.md) | Error / debug cheatsheet |
| [docs/sage/](docs/sage/) | Local Sage template + deploy notes |
| [CHANGELOG.md](CHANGELOG.md) | Version history |
| [AGENTS.md](AGENTS.md) | Cursor Cloud environment |

---

## License

MIT. Built on [Sage](https://roots.io/sage/) (MIT).
