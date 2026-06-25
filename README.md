# Matt Hummel — WordPress Theme

A bespoke **Sage 11 (Roots)** WordPress theme that powers matthummel.com — built
around a minimalist **"Paper + Geist"** design system. Server-rendered, accessible,
mobile-first, deliberately light on plugins, and packed with premium theme-options.

> Stack: Sage 11 · Blade · Tailwind CSS v4 · Vite · Acorn (Laravel-in-WordPress) · PHP 8.3

### 📖 [Support & documentation → matthummel-pa.github.io/matthummel-theme](https://matthummel-pa.github.io/matthummel-theme/)

Setup, a full settings reference, FAQ, and help all live on the GitHub Pages site
above. Questions or bugs → [open an issue](https://github.com/matthummel-pa/matthummel-theme/issues).

---

## Features

### Design & theming
- **Design system in tokens** — colors, type scale, and spacing are Tailwind v4 `@theme` variables; the whole site re-skins from one place.
- **Style Kits** — one-click presets (Editorial, Sage Classic, Warm Sand, Midnight, Mono Slate) under *Appearance → Theme Tools*.
- **Deep Customizer** — Colors, Typography (+ advanced per-element fonts/weights/responsive sizes), Layout, Header Layout (sticky/shrink/transparent), Navigation (full flexbox), Menu & Popout, Top Bar, Announcement Bar (scheduled), Dark Mode, Footer, Extras. Live CSS-variable overrides, no rebuild.
- **Import / export / reset** all settings as JSON.

### Icons & blocks
- **Blade Icons** — Simple Icons (`si-`), Heroicons (`heroicon-o-`), Lucide (`lucide-`), and a local `mh-` set. No Font Awesome.
- **Dynamic blocks** — Social Icons, Icon (any Blade icon), Post Grid (query posts/projects).
- **Starter patterns** — Hero, Pricing, Testimonials, Logo cloud, Feature grid, CTA band, Stat strip, FAQ, Callout.
- **theme.json** — spacing/border/shadow/typography/gradient/duotone editor controls.

### Content & integrations
- **Live GitHub project pages** — `app/Github.php` pulls repo metadata, stars/forks, latest release, and the README intro; **Connect with GitHub** (OAuth device flow) raises the API rate limit.
- **Plugin-free contact form** — nonce, honeypot, validation, `wp_mail`.
- **Newsletter** (`[mh_newsletter]`, Mailchimp-ready), **cookie notice**, **code injection** (head/body/footer + custom CSS).

### Performance, SEO & a11y
- **Performance engine** — disable emojis/embeds/jQuery-migrate/XML-RPC/dashicons, head cleanup, defer scripts, preconnect.
- **Self-hosted fonts** — *Appearance → Local Fonts* downloads woff2 locally and removes the Google request.
- **SEO + JSON-LD** — Open Graph, Twitter cards, Person/Organization, WebSite, Article, BreadcrumbList; `[mh_breadcrumbs]`. Auto-disables under Rank Math/Yoast.
- **Accessible & responsive** — semantic landmarks, focus states, skip link, WCAG-AA pairings, fluid `clamp()` type.

### White-label & onboarding
- Branded login screen, admin footer, **"Get started" dashboard widget**, and one-click **starter pages + menu**.

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

Activate the **Matt Hummel** theme, then open **Appearance → Theme Tools** to apply a
Style Kit. For a full no-Docker local stack (WP-CLI + SQLite + `wp server`), see
**[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)**.

## Build & deploy

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

Ship the theme folder **including** `vendor/` and `public/build/`. `node_modules/` is
never deployed. See **[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)** for the release flow.

## Project structure

```
matthummel/
├── app/                 # theme modules (registered in functions.php)
│   ├── setup.php · theme-supports.php · customizer.php · extras.php
│   ├── icons.php · social-block.php · blocks-dynamic.php · patterns-extra.php
│   ├── performance.php · fonts-local.php · seo.php · integrations.php
│   ├── header-layout.php · header-behaviors.php · nav-options.php · menu.php · menu-icons.php
│   ├── announcement.php · footer-content.php · typography.php · dark-mode.php
│   ├── settings-io.php · admin-settings.php · whitelabel.php
│   ├── projects-admin.php · github-connect.php · Github.php · contact.php
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
- **[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)** — local environment, build, deploy
- **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)** — how the theme works
- **[docs/BRAND-DESIGN-SYSTEM.md](docs/BRAND-DESIGN-SYSTEM.md)** — colors, type, spacing, components
- **[docs/THEME-SETTINGS.md](docs/THEME-SETTINGS.md)** — every setting, catalogued

## Contributing

See **[CONTRIBUTING.md](CONTRIBUTING.md)**. Branch from `main`, use Conventional Commits,
run `npm run build` + `vendor/bin/pint`, open a PR.

## License

[MIT](LICENSE) © Matt Hummel. Built on [Sage](https://roots.io/sage/) by Roots.
