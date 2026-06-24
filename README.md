# Matt Hummel — WordPress Theme

A bespoke **Sage 11 (Roots)** WordPress theme for [matthummel.com](https://matthummel.com), built around a minimalist **"Paper + Space Grotesk"** design system (inspired by the 2026 *radical brevity* trend). Server-rendered, accessible, mobile-first, and deliberately light on plugins.

> Stack: Sage 11 · Blade · Tailwind CSS v4 · Vite · Acorn (Laravel-in-WordPress) · PHP 8.3

---

## Features

- **Design system in tokens** — colors, type scale, and spacing live as Tailwind v4 `@theme` variables, so the whole site re-skins from one file.
- **Kadence-style Customizer** — a *Theme Options* panel (colors, fonts, layout width, header button, footer) that writes live CSS-variable overrides. No rebuild needed to re-theme.
- **Live GitHub project pages** — `app/Github.php` pulls repo metadata, stars/forks, latest release, and the README intro (cached 6h) and renders it via the `[mh_github]` shortcode.
- **Plugin-free contact form** — custom template + handler (`app/contact.php`) with nonce, honeypot, validation, and `wp_mail`.
- **Full template set** — home/landing, blog index, single post, page, category/tag archive, search, 404, projects archive, single project.
- **Accessible & responsive** — semantic landmarks, visible focus states, skip link, WCAG-AA color pairings, fluid `clamp()` type, mobile-first layouts.
- **SEO-ready** — clean markup and structure suited to Rank Math (titles, meta, schema, breadcrumbs).

## Requirements

| Tool | Version |
|---|---|
| PHP | 8.3+ |
| Composer | 2.x |
| Node | 20.19+ or 22.12+ |
| WordPress | 6.6+ |

## Quick start (local)

```bash
composer install
npm install
npm run build      # or: npm run dev  (Vite HMR)
```

Then activate the **Matt Hummel** theme in WordPress. For a full no-Docker local stack (WP-CLI + SQLite + `wp server`), see **[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)**.

## Build & deploy

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

Ship the theme folder **including** `vendor/` and `public/build/` (or build on the host). `node_modules/` is never deployed. See **[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)** for the release flow.

## Project structure

```
matthummel/
├── app/
│   ├── setup.php        # theme setup: CPT (projects), fonts, menus, sidebars
│   ├── filters.php      # [mh_github] shortcode + filters
│   ├── Github.php       # cached GitHub API fetch + render
│   ├── customizer.php   # Theme Options panel + CSS-variable injection
│   ├── contact.php      # plugin-free contact form handler
│   └── Providers/       # Acorn service provider
├── resources/
│   ├── css/app.css      # Tailwind v4 @theme tokens + components (design system)
│   ├── js/app.js
│   └── views/           # Blade templates (see below)
├── public/build/        # compiled assets (generated)
├── functions.php        # Acorn bootstrap + file registration
└── style.css            # theme header
```

### Templates (`resources/views/`)

| File | Renders |
|---|---|
| `front-page.blade.php` | Home / landing |
| `index.blade.php` | Blog index |
| `single.blade.php` + `partials/content-single` | Single post (reading view) |
| `page.blade.php` + `partials/content-page` | Static page |
| `template-contact.blade.php` | Contact page + form |
| `archive.blade.php` | Category / tag / date archive |
| `archive-projects.blade.php` | Projects grid |
| `single-projects.blade.php` | Single project (live GitHub data) |
| `search.blade.php` | Search results |
| `404.blade.php` | Not found |

## Documentation

- **[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)** — local environment, build, deploy
- **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)** — how the theme works
- **[docs/BRAND-DESIGN-SYSTEM.md](docs/BRAND-DESIGN-SYSTEM.md)** — colors, type, spacing, components
- **[docs/CONTENT-ARCHITECTURE.md](docs/CONTENT-ARCHITECTURE.md)** — post types, taxonomy, Rank Math SEO
- **[docs/EDITORIAL-AND-DEV-SOP.md](docs/EDITORIAL-AND-DEV-SOP.md)** — editorial + development SOPs

## Contributing

See **[CONTRIBUTING.md](CONTRIBUTING.md)**. Short version: branch from `main`, use Conventional Commits, run `npm run build` + `vendor/bin/pint`, open a PR.

## License

[MIT](LICENSE) © Matt Hummel. Built on [Sage](https://roots.io/sage/) by Roots.
