# Matt Hummel — WordPress Theme

A bespoke **Sage 11 (Roots)** WordPress theme that powers matthummel.com — built
around a minimalist **"Paper + Geist"** design system. Server-rendered, accessible,
mobile-first, deliberately light on plugins, and packed with premium theme-options.

> Stack: Sage 11 · Blade · Tailwind CSS v4 · Vite · Acorn (Laravel-in-WordPress) · PHP 8.3

### 📖 [Support & documentation → matthummel-pa.github.io/matthummel-theme](https://matthummel-pa.github.io/matthummel-theme/)

Setup, a full settings reference, FAQ, and help live on the GitHub Pages site (and in
[SUPPORT.md](SUPPORT.md)). Questions or bugs → [open an issue](https://github.com/matthummel-pa/matthummel-theme/issues).

---

## Features

### Design system & theming
- **Tokens-first design** — colors, type scale, and spacing are Tailwind v4 `@theme` variables; the whole site re-skins from one place.
- **Style Kits** — one-click presets (Editorial, Sage Classic, Warm Sand, Midnight, Mono Slate) under *Appearance → Theme Tools*.
- **Colors** — brand/button, page background, headings, body — as live CSS-variable overrides (no rebuild).
- **Typography** — heading/body font (10 families) + base size, line-heights, letter-spacing.
- **Typography (advanced)** — separate nav/button fonts, per-element weights, nav casing, body spacing, and responsive base sizes for tablet/mobile.
- **Extras** — underline links, button + card radius, text-selection color, scroll-to-top button.
- **Dark mode** — toggle with light / dark / auto (system) default and no-flash loading.
- **`theme.json`** — unlocks spacing, border, shadow, fluid typography, gradient, and duotone controls in the block editor.

### Header, nav & footer
- **Header Layout** — full-width menu, header width/height/gap, element order (logo/nav/social/button), and the header CTA button.
- **Header behaviors** — sticky, shrink-on-scroll, and transparent overlay (off / front page / all).
- **Navigation** — full flexbox control (direction, justify, align, wrap, gap) plus per-item box/type styling.
- **Menu & off-canvas popout** — hamburger per breakpoint, solid/gradient panel, social icons.
- **Menu-item icons + mega menu** — add `mh-ic-<icon>` or `mh-mega` classes in Appearance → Menus.
- **Top utility bar** — contact text, social, and a CTA above the main nav.
- **Footer builder** — 1–4 columns mapped to block **widget areas**, palette/custom colors, social icons, tagline.
- **Announcement bar** — message + link, colors, dismissible (remembered), with optional start/end **scheduling**.

### Icons & blocks
- **Blade Icons** — Simple Icons (`si-`), Heroicons (`heroicon-o-`), Lucide (`lucide-`), and a local `mh-` set. Font Awesome removed.
- **Social Icons block** — inline SVGs; pulls from site social links by default; size, shape, brand/mono, colors, hover, alignment.
- **Icon (Blade) block** — drop in any Blade icon by name.
- **Post Grid block** — query posts/projects/pages into a responsive card grid.
- **Starter patterns** — Hero, Pricing, Testimonials, Logo cloud, Feature grid, CTA band, Stat strip, FAQ, Callout.
- **Card block style** for groups/columns.

### Content & integrations
- **Live GitHub project pages** — `app/Github.php` pulls repo metadata, stars/forks, latest release, and the README intro (cached); **Connect with GitHub** (OAuth device flow) raises the API rate limit. Per-project owner/repo/eyebrow/demo via the Project Details box.
- **Plugin-free contact form** — nonce, honeypot, validation, `wp_mail`.
- **Newsletter** — `[mh_newsletter]` (Mailchimp-ready) with configurable action/heading/button.
- **Cookie notice** — configurable, dismissible, remembered.
- **Code injection** — head / body / footer scripts + a live Custom CSS box.

### Reading experience
- **Auto table of contents**, **reading-progress bar**, **estimated read time**, and **copy buttons** on code blocks for single posts.

### Performance
- Toggle off emojis, oEmbed/wp-embed, jQuery Migrate, XML-RPC/pingbacks, dashicons (logged-out); clean `wp_head`; defer scripts; preconnect.
- **Self-hosted fonts** — *Appearance → Local Fonts* downloads woff2 locally and removes the Google request + preconnect.

### SEO & accessibility
- **SEO + JSON-LD** — Open Graph, Twitter cards, Person/Organization, WebSite, Article, BreadcrumbList; `[mh_breadcrumbs]`. Auto-disables under Rank Math/Yoast.
- **Accessible & responsive** — semantic landmarks, focus states, skip link, WCAG-AA pairings, fluid `clamp()` type, mobile-first.

### Settings, white-label & onboarding
- **Theme Options Customizer** (19 consolidated sections) + a tabbed **Appearance → Theme Settings** admin panel (General, Design, Layout, Header, Footer, Projects).
- **Import / export / reset** all settings as JSON (Theme Tools).
- **White-label** — branded login screen, admin footer text.
- **Onboarding** — "Get started" dashboard widget + one-click **starter pages + primary menu**.

## Shortcodes & blocks

| Type | Name |
|---|---|
| Blocks | `mh/social-links` · `mh/icon` · `mh/post-grid` |
| Patterns | Hero · Pricing · Testimonials · Logo cloud · Feature grid · CTA band · Stat strip · FAQ · Callout |
| Shortcodes | `[mh_newsletter]` · `[mh_breadcrumbs]` · `[mh_github]` |

## Requirements

| Tool | Version |
|---|---|
| PHP | 8.3+ |
| Composer | 2.x |
| Node | 20.19+ or 22.12+ |
| WordPress | 6.6+ |

Icon packs are Composer dependencies: `blade-ui-kit/blade-icons`, `codeat3/blade-simple-icons`, `blade-ui-kit/blade-heroicons`, `mallardduck/blade-lucide-icons`.

## Quick start (local)

```bash
composer install
npm install
npm run build      # or: npm run dev  (Vite HMR)
```

Activate **Matt Hummel**, then open **Appearance → Theme Tools** to apply a Style Kit.
For a full no-Docker local stack (WP-CLI + SQLite + `wp server`), see **[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)**.

## Build & deploy

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

Ship the theme folder **including** `vendor/` and `public/build/`. `node_modules/` is
never deployed. See **[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)** for the release flow.

## Templates (`resources/views/`)

| File | Renders |
|---|---|
| `front-page.blade.php` | Home / landing |
| `index.blade.php` | Blog index |
| `single.blade.php` | Single post (reading view: TOC, progress, read time) |
| `page.blade.php` | Static page |
| `template-contact.blade.php` | Contact page + form |
| `archive.blade.php` | Category / tag / date archive |
| `archive-projects.blade.php` | Projects grid (with filtering) |
| `single-projects.blade.php` | Single project (live GitHub data) |
| `search.blade.php` · `404.blade.php` | Search results · Not found |

## Project structure

```
matthummel/
├── app/                 # theme modules (registered in functions.php)
│   ├── setup.php · theme-supports.php · customizer.php · extras.php
│   ├── icons.php · social-block.php · blocks-dynamic.php · patterns-extra.php
│   ├── performance.php · fonts-local.php · seo.php · integrations.php
│   ├── header-layout.php · header-behaviors.php · nav-options.php · menu.php · menu-icons.php
│   ├── announcement.php · footer-content.php · typography.php · dark-mode.php · reading.php
│   ├── settings-io.php · admin-settings.php · whitelabel.php
│   ├── projects-admin.php · github-connect.php · Github.php · contact.php · blocks.php
│   └── Providers/       # Acorn service provider
├── config/blade-icons.php
├── resources/
│   ├── css/app.css      # Tailwind v4 @theme tokens + components
│   ├── js/              # block editors (plain JS, no build)
│   ├── svg/             # local mh- icon set
│   └── views/           # Blade templates
├── theme.json           # block editor settings
├── public/build/        # compiled assets (generated)
├── functions.php        # Acorn bootstrap + module registration
└── style.css            # theme header
```

## Documentation

- **[Support & settings reference](https://matthummel-pa.github.io/matthummel-theme/)** — GitHub Pages
- **[SUPPORT.md](SUPPORT.md)** — direct-link support page
- **[docs/THEME-SETTINGS.md](docs/THEME-SETTINGS.md)** — every setting, catalogued
- **[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)** · **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)** · **[docs/BRAND-DESIGN-SYSTEM.md](docs/BRAND-DESIGN-SYSTEM.md)**

## Contributing

See **[CONTRIBUTING.md](CONTRIBUTING.md)**. Branch from `main`, use Conventional Commits,
run `npm run build` + `vendor/bin/pint`, open a PR.

## License

[MIT](LICENSE) © Matt Hummel. Built on [Sage](https://roots.io/sage/) by Roots.
