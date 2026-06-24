# Local dev — modern, app-free (Node + wp-now)

No Local app, no Docker, no MAMP. This uses **wp-now** (by the WordPress Playground
team): it runs a real WordPress on **WASM PHP** straight from Node, with WP-CLI built
in. Sage's **Vite** dev server gives you live hot-reload while you edit.

## Requirements

- **Node 20.19+ / 22.12+** (you have it)
- **Composer** — needed once to install Sage's framework (Acorn). It's a CLI, not an
  app. On Windows: https://getcomposer.org/Composer-Setup.exe (or `winget install Composer`).

## One-time setup

From inside the theme folder (`...\DevProjects\matthummel`):

```bash
composer install            # installs Acorn (Sage's Laravel framework) -> vendor/
npm install                 # installs Vite, Tailwind, wp-now, etc.
npm run build               # compiles CSS/JS -> public/build/
```

## Run it (live local)

```bash
npm run wp                  # boots WordPress via wp-now with this theme active
```

wp-now prints a URL like `http://localhost:8881` — open it. Admin is at `/wp-admin`
(user `admin`, pass `password`). The theme is already active and permalinks are pretty.

For **live hot-reload** while you style, open a second terminal and run:

```bash
npm run dev                 # Vite dev server — edits to Blade/CSS refresh instantly
```

## Load the two example projects

In `wp-admin` → **Tools → Import → WordPress**, upload
`matthummel-example-projects.wxr` (included). It creates the `keepary` and `tocflow`
projects; the theme pulls their stats + README live from GitHub.

Or with WP-CLI (wp-now ships it):

```bash
npx @wp-now/wp-now wp import matthummel-example-projects.wxr --authors=create
```

## Day-to-day

- Edit templates in `resources/views/*.blade.php`, styles/tokens in
  `resources/css/app.css`. With `npm run dev` running, changes hot-reload.
- `npm run build` before committing/deploying for production assets.

## Honest caveat (Sage on WASM PHP)

Sage is heavy — it boots Laravel (Acorn). wp-now's WASM PHP usually runs it, but if you
hit a white screen or an Acorn/extension error on first load, that's the WASM runtime
missing something Laravel wants. Reliable fallbacks, in order of effort:

1. **wp-env** (`npm i -g @wordpress/env`, needs Docker Desktop) — full native PHP.
2. A real **PHP 8.3 + MySQL** locally (XAMPP-free: `php -S` + SQLite plugin, or your host's SSH).

If you'd rather avoid Composer/Acorn entirely, I can also convert this to a **classic
(non-Acorn) theme** — same khaki/Fraunces design, same live GitHub data, plain PHP
templates + the same Vite/Tailwind build — which runs under wp-now with zero Composer.
Just ask.

## Deploying to matthummel.com later

Build, then upload the theme **with `vendor/` and `public/build/`** (or build on the
host). Activate, then Settings → Permalinks → Save.
