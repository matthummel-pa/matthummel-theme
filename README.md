# Matt Hummel — WordPress theme

Sage 11 theme for **[matthummel.com](https://matthummel.com)** — portfolio, services, and journal for a WordPress developer in Gettysburg, PA.

No page builders. Visitor copy lives in wp-admin (**Page content (theme)**), not hardcoded in Blade. Front end is Blade + Tailwind + a few small JS modules.

| | |
| --- | --- |
| **Live site** | [matthummel.com](https://matthummel.com) |
| **Stack** | Sage 11.2.1 · PHP 8.3 · Acorn 6 · Blade · Tailwind v4 · Vite 8 |
| **License** | [MIT](LICENSE.md) |

---

## Who it’s for

| Audience | What they get |
| --- | --- |
| Shops and local businesses | Concept sites, plain-language WordPress options, a clear way to start a project |
| Agencies and developers | Stack, open-source code, overflow / hire paths |
| Recruiters | Experience, GitHub, availability |

---

## What’s in the theme

- Named Blade page templates (Home, About, Services, Hire, Work, Code, Contact, Now, Uses, …)
- Projects CPT with on-site concept pages at `/concept/{slug}/`
- Plugin-free contact + project brief forms (nonce, honeypot)
- GitHub API showcase (profile, contribs, activity, repos) via transients
- Journal with share intents, comments, and DEV.to / Bluesky helpers
- Appearance → **Update Theme** installs the `theme-latest` GitHub Release zip

Deep feature list: [`docs/FEATURES.md`](docs/FEATURES.md).

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

Full local / Cloud setup: [`AGENTS.md`](AGENTS.md).

---

## Deploy

Push or merge to `main` runs GitHub Actions:

1. Composer + `npm run build`
2. Publish Release **`theme-latest`**
3. Optional FTP / SSH rsync to SiteGround

Live theme path: `wp-content/themes/matthummel/`.

On the server: **Appearance → Update Theme** (fine-grained PAT, Contents: Read) or `wp mh theme-update`.

Details: [`docs/INSTALL.md`](docs/INSTALL.md) · [`docs/sage/deployment.md`](docs/sage/deployment.md).

---

## Repo layout

```
app/                 PHP (fields, contact, GitHub, updater, …)
resources/views/     Blade templates, sections, partials
resources/css/       Tailwind @theme + portfolio styles
resources/js/        Small front-end modules
public/build/        Vite output (CI only — never commit)
.github/workflows/   Deploy pipeline
docs/                Feature log, install, theme deep-dive
```

Architecture, page map, SEO, a11y, and security notes: [`docs/THEME.md`](docs/THEME.md).

---

## Docs

| Doc | Contents |
| --- | --- |
| [docs/THEME.md](docs/THEME.md) | Full theme documentation (architecture, SEO, a11y, security) |
| [docs/FEATURES.md](docs/FEATURES.md) | Feature log and WP-CLI reference |
| [docs/INSTALL.md](docs/INSTALL.md) | Activate after deploy |
| [docs/ERRORS.md](docs/ERRORS.md) | Error / debug cheatsheet |
| [CHANGELOG.md](CHANGELOG.md) | Version history |
| [AGENTS.md](AGENTS.md) | Cursor Cloud environment |

Sage: [roots.io/sage/docs](https://roots.io/sage/docs/) · local copies in [`docs/sage/`](docs/sage/).

---

## License

MIT. Built on [Sage](https://roots.io/sage/) (MIT).
