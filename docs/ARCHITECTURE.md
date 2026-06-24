# Architecture

How the theme is put together.

## Foundation

[Sage 11](https://roots.io/sage/) on top of **Acorn** (a Laravel application container inside WordPress). Markup is **Blade**; styles/scripts are bundled by **Vite**; CSS is **Tailwind CSS v4**.

- `functions.php` boots Acorn and registers theme files via `collect(['setup', 'filters', 'customizer', 'contact'])`.
- `@vite([...])` in `resources/views/layouts/app.blade.php` injects the built CSS/JS.

## Design tokens

`resources/css/app.css` defines the design system as Tailwind v4 `@theme` variables
(`--color-*`, `--font-*`, radius) plus `@layer components` for buttons, cards, the post
list, reading prose, the contact form, etc. Components reference `var(--color-…)`, so the
whole site re-skins from these tokens.

## Customizer (Theme Options)

`app/customizer.php` registers a **Theme Options** panel (colors, fonts, layout width,
header button, footer). Chosen values are emitted as a `:root { … }` override in a
`<style id="mh-customizer">` block via the `mh_head_end` action — which fires **after**
`@vite` in the layout, so the overrides win without a rebuild. Non-default fonts are
enqueued from Google Fonts on demand. Header/footer values flow through existing
`matthummel/*` filters.

## Live GitHub engine

`app/Github.php` calls the GitHub REST API server-side (`wp_remote_get`, User-Agent set)
for repo metadata, stars/forks, latest release, and the rendered README intro; results are
cached in a 6-hour transient. `app/filters.php` exposes it as `[mh_github owner="…" repo="…" show="desc,stats,intro"]`, used on single project pages.

## Contact form

`app/contact.php` handles submissions on the `init` hook: verifies a nonce, checks a
honeypot, sanitizes + validates fields, sends via `wp_mail`, and redirects back with a
`?contact=success|error` flag the template reads. No third-party form plugin.

## Template hierarchy

Standard WordPress hierarchy, in Blade:

| Request | Template |
|---|---|
| Front page | `front-page.blade.php` |
| Posts page / archives | `index.blade.php` / `archive.blade.php` |
| Single post | `single.blade.php` → `partials/content-single` |
| Page | `page.blade.php` → `partials/content-page` |
| Page using "Contact" template | `template-contact.blade.php` |
| Projects archive / single | `archive-projects` / `single-projects` |
| Search / 404 | `search.blade.php` / `404.blade.php` |

Shared chrome lives in `sections/header.blade.php`, `sections/footer.blade.php`, and `layouts/app.blade.php`.
