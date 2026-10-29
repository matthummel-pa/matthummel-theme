---
paths:
  - "app/**"
  - "resources/**"
  - "vite.config.js"
  - "composer.json"
  - "package.json"
---

# Sage (Roots), this repo

Generic Sage conventions load from the playbook (`sage/sage.md`). Local copies of the docs: `docs/sage/`.

## Templates

WordPress template hierarchy still applies. Blade files live in `resources/views/`.

| File | Role |
| --- | --- |
| `layouts/app.blade.php` | Base wrap (header, `#main`, footer). Itself wrapped by root `index.php`. |
| `index.blade.php` | Blog/home-of-posts, categories, authors, generic archives |
| `page.blade.php` | Default single page |
| `single.blade.php` | Single post |
| `search.blade.php` | Search results |
| `404.blade.php` | Not found |
| `front-page.blade.php` | Static front page |
| `template-*.blade.php` | Named page templates (Home, About, Work, …) |

Sage 11 keeps chrome in `sections/` (`header`, `footer`, `sidebar`), not `partials/header`. Put reusable chunks in `partials/`.

Examples from the [theme templates docs](https://roots.io/sage/docs/theme-templates/):

- Author archives → copy `index.blade.php` to `author.blade.php`
- CPT `gallery` archive → `archive-gallery.blade.php`
- Page titled About → `page-about.blade.php` (we prefer explicit `template-about.blade.php` page templates instead)

Do not add Kadence, theme-options admin, or extra `app/*.php` modules unless Matt asks. Portfolio helpers stay in `app/portfolio.php`, `app/contact.php`, `app/Github.php`.

## Local installs

One Cloud/local WordPress per product. This theme: `~/wp-site`, folder `matthummel`, port **8080**. Acreline: `~/wp-acreline-site`, port **8081**. Next project: `~/wp-<slug>-site` and **8082+**. Never activate another product theme on the matthummel WP. See `AGENTS.md` (Local WordPress isolation) and user skill `sage-docs`.

## Assets

- Edit `vite.config.js` `base` if the WordPress theme folder name changes. Live folder is `matthummel`.
- `npm run build` writes `public/build/` (gitignored). The site fatals without a Vite manifest.
- `npm run dev` is local HMR only; production uses the built manifest.

## Deploy

Push/merge to `main` runs GitHub Actions (`Deploy Roots Production Ecosystem`): Composer + `npm run build`, GitHub Release `theme-latest`. Install the zip on the **staging site** with Appearance -> Update Theme; Matt publishes staging -> live from hPanel (agents never install on `matthummel.com`). Live folder is `wp-content/themes/matthummel`. See `.github/workflows/deploy.yml` and `hostinger-github-wordpress-workflow.md`.
