---
paths:
  - "app/**"
  - "resources/**"
  - "vite.config.js"
  - "tailwind.config.js"
  - "theme.json"
  - ".github/workflows/**"
---

# Sage bespoke (this repo)

ACF-in-PHP registry, Customizer dark-mode default, branch hygiene and security samples: the `sage-architecture` skill (load it when Matt asks for ACF fields or a Customizer control). This file is only what is specific to this theme.

## This repository (do not ignore)

This theme is Sage 11.2.1 at repo root, live folder `matthummel`. Apply the directives below **without ripping out what already works** unless Matt asks.

- **Fields:** visitor copy is block content (`content.md`). `app/page-fields.php` is frozen legacy for unconverted pages. Do not install ACF Pro or add `app/Fields/` until Matt asks. When he does, use the `sage-architecture` skill.
- **Dark mode today:** runtime class is `html.mh-dark` (header toggle + `prefers-color-scheme`). CSS in `resources/css/portfolio.css` keys off that class. If adding a Customizer default (`sage-architecture` skill), map `system|light|dark` into that class; do not switch the whole theme to `[data-theme]` and abandon `.mh-dark`.
- **Tailwind v4:** `@tailwindcss/vite` in `vite.config.js`. Front-end tokens live in CSS `@theme` (`resources/css/app.css`). `tailwind.config.js` is only the Gutenberg / `wordpressThemeJson` map. Do not add `@config` unless Matt migrates off CSS tokens.
- **Deploy today:** `.github/workflows/deploy.yml` (`Deploy Roots Production Ecosystem`) builds on the runner and publishes GitHub Release `theme-latest`. Production is `matthummel.com` on Hostinger. Theme path is `wp-content/themes/matthummel/`. PHP 8.3, Node 22, npm (not yarn, not `web/app/themes/sage`). No SiteGround FTP or SSH.
- **Gutenberg:** posts use the block editor. Pages are moving to it one template at a time (3.7.0): a converted page is core blocks inside the theme's page blocks, PHP-rendered through Blade. Do not add block patterns, Custom HTML blocks, or Kadence.
- **PHP:** prefer functions in `App` for theme helpers. Use classes for ACF/Customizer registries when those features are introduced.
- **Branches:** this repo’s default branch is `main`. Work branches are `feat/…`, `fix/…`, `chore/…`. Do not invent a `staging` branch unless Matt wants one.

## Core architectural directives

- **Bespoke only:** zero pre-made templates, child themes, or page-builder bloat.
- **Blocks on pages:** use the theme's page blocks and core blocks; ignore block patterns. A `template-*.blade.php` page that is not converted yet keeps its fields.
- **Philosophy:** structured content lives in blocks and post meta registered in PHP (no ACF until Matt asks). Site-wide design variables belong in the WordPress Customizer when Matt asks for them.
- **2026 visual design:** layouts use modern CSS — container queries (`@container`), fluid type (`clamp()`), and design tokens as CSS custom properties wired to Tailwind v4.

## Deploy shape

Production is **[matthummel.com](https://matthummel.com)** on Hostinger. Deployments use `.github/workflows/deploy.yml` (`Deploy Roots Production Ecosystem`). The theme **is** the repo root (not `web/app/themes/sage`). CI uses **npm** and Node **22**.

CI publishes GitHub Release `theme-latest`. Install the zip on the **staging site** (Appearance -> Update Theme or `wp mh theme-update`). Matt publishes staging -> live from hPanel; agents never install on `matthummel.com` (`hostinger-github-wordpress-workflow.md`). Do not add SiteGround FTP or SSH rsync. Do not add a second production workflow. Never hardcode credentials.

After a theme swap on Hostinger, purge LiteSpeed / website cache (hPanel or Hostinger MCP `hosting_purgeLiteSpeedCacheV1` / `hosting_clearWebsiteCacheV1`) so visitors are not stuck on old Vite hashes.

Edit `.github/workflows/deploy.yml` in place.

## Tokens and CI

This theme is **Sage 11 + Vite 8 + Tailwind v4**. `bud.config.js` documents the Bud-shaped entry/`wpjson` spec; **do not run Bud**. Production compile is `npm run build` (Vite).

- **Theme.json pipeline:** Never hand-edit colors or fonts in the **generated** `public/build/assets/theme.json`. Front-end utilities come from Tailwind v4 `@theme` in `resources/css/app.css` (do not add `@config` unless Matt is migrating off CSS tokens). `tailwind.config.js` is the Gutenberg / `wordpressThemeJson` color+font map (`agencyBrand`, `agencyAccent`, `agencyMuted`). Keep `wordpressThemeJson({ tailwindConfig: './tailwind.config.js' })` aligned with `bud.config.js` `.wpjson.useTailwindColors()`. Root `theme.json` holds structural settings (`color.link`, `spacing.blockGap`, units).
- **Zero commit artifacts:** Compiled public assets stay gitignored (`/public/*` with `!/public/.gitkeep`). Production CSS/JS/`theme.json` are built in GitHub Actions (`npm ci` + `npm run build` in `.github/workflows/deploy.yml`). Do not commit `public/build/`.
- **Environment integrity:** Credentials live only in GitHub Secrets or WordPress (`MH_GITHUB_TOKEN` for Appearance → Update Theme). Never hardcode keys, hosts, or passwords in workflow YAML. Do not add SiteGround FTP/SSH secrets.
- **Atomic deploys:** Always compile production assets on the Actions runner **before** publishing the zip. Do not upload unbuilt source as the live theme. Prefer one built artifact (`theme-latest` zip) so the Hostinger swap is a complete theme, not a partial `public/` copy. Purge LiteSpeed after install so cached HTML does not 404 old Vite hashes.
