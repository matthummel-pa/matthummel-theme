# matthummel.com — Sage Theme Build Log

A complete record of building the bespoke **Sage 11 (Roots)** WordPress theme for
matthummel.com — from the decision to convert away from Kadence, through the design
system, templates, Customizer, admin panel, header/footer system, and GitHub release.

- **Repo:** https://github.com/matthummel-pa/matthummel-theme
- **Stack:** Sage 11 · Blade · Tailwind CSS v4 · Vite · Acorn (Laravel-in-WordPress) · PHP 8.3
- **Design north star:** Webflow 2026 trend #3 — *minimalism / radical brevity*
- **Local dev:** PHP + Composer + WP-CLI + SQLite + `wp server` (no Docker, no app)

---

## 1. Why Sage

The live site ran on **Kadence + Code Snippets** (custom blocks, a `[mh_github]`
shortcode, a global CTA, dynamic GitHub project pages). To make future customization
faster and more maintainable than hand-managed Kadence blocks, we rebuilt the front
end as a **code-first Sage theme**: Blade templates, Tailwind v4 design tokens, and a
Laravel-style app container (Acorn). Goal: a bespoke, accessible, mobile-first,
low-plugin theme that doubles as portfolio + product.

## 2. Key decisions

- **Design direction:** adopt a new **"Paper + Space Grotesk"** system (light Paper
  background, bold Space Grotesk headings, Inter body, JetBrains Mono code, an
  accessible interaction-tuned color set) — a deliberate, modern redesign vs. the old
  khaki/Fraunces look.
- **Minimal plugins:** keep only Rank Math, the importer, and the SQLite drop-in;
  build everything else into the theme (contact form, GitHub engine, options, icons).
- **One source of truth for options:** all theme options are stored as **theme mods**,
  edited from **both** the Customizer and a custom **admin Theme Settings** page.
- **Watch-as-you-go workflow:** every change applied via a small PowerShell script,
  reviewed live in the browser before moving on.

## 3. Build timeline

### Phase 0 — Theme scaffold & brand
Scaffolded Sage 11, set design tokens, registered the `projects` CPT + taxonomy,
fonts, menus, the cached GitHub engine (`app/Github.php` + `[mh_github]`), and the
initial Blade templates. Created the **MH logo** (SVG mark, wordmark, lockup).

### Phase 1 — Local dev environment (no Docker)
Stood up a full local stack without an app: **PHP 8.3**, **Composer**, **WP-CLI**
(phar), the **SQLite Database Integration** drop-in (`wp-content/db.php`), and
**`wp server`** on `localhost:8080`. Built theme assets (`composer install`,
`npm run build`). Imported live content via WXR (25 posts / 12 pages).

### Phase 2 — Design system applied
Rewrote `resources/css/app.css` as Tailwind v4 `@theme` tokens: Paper palette, Space
Grotesk / Inter / JetBrains Mono, fluid `clamp()` type scale, accessible color pairings,
white hairline cards, 8px buttons. Swapped the Google Fonts enqueue.

### Phase 3 — Core templates
- **Blog index** → editorial post list (meta · ink title · excerpt · "Read more").
- **Single post / page** → centered hero + 720px reading column with full prose
  typography (headings, links, lists, blockquotes, code).
- Removed the default "Hello world!" post.

### Phase 4 — Customizer theme options
Kadence-style **Theme Options** panel: colors (live), fonts, container width, header
button, footer — emitted as CSS-variable overrides after the built stylesheet (no
rebuild to re-skin).

### Phase 5 — Full template set
**Home/landing** (`front-page`), a **plugin-free Contact form** (custom handler with
nonce + honeypot + `wp_mail`), **category/tag archives** (surfacing category
descriptions), **search**, and a styled **404**. Created Home/Blog/Contact pages,
wired the static front page + posts page, and turned on pretty permalinks.

### Phase 6 — GitHub release
Built a complete engineering repo and pushed it: **README**, **LICENSE** (MIT),
**CONTRIBUTING**, **CHANGELOG**, **CODE_OF_CONDUCT**, **SECURITY**, `/docs`
(development, architecture, design system, content architecture, SOP, brand logos),
**.github** issue/PR templates + **CI workflow**. Default branch set to `main`.

### Phase 7 — Logo + layout system
Put the **MH logo** in the header. Added a **layout engine**: per content type
(Pages / Posts / Archives / Projects-only) choose a width preset (Default / Full /
Narrow / Boxed), an exact **custom width** (standard sizes), and an optional
**sidebar** — applied via body classes + a precise width override.

### Phase 8 — Admin Theme Settings page
A premium-style, tabbed **Theme Settings** admin page (General / Design / Layout /
Header / Footer / Projects) with dashicon UX icons, a color picker, and the MH logo —
reading/writing the same theme mods as the Customizer.

### Phase 9 — Header system
- **Top utility bar** above the nav (contact left, socials + CTA right, ~half height),
  background/text from the global palette.
- **Off-canvas popout menu**: a Menu icon (toggle per breakpoint — desktop / tablet /
  mobile) opening a slide-in panel with **solid or gradient** background, plus
  **Font Awesome brand icons** (LinkedIn, GitHub, Dev.to, X, Bluesky, YouTube,
  Instagram, Facebook, Mastodon, Email, RSS), each with a custom URL. Accessible JS
  (ESC, focus, `aria-expanded`).

### Phase 10 — Projects admin
A **Project Details** meta box (GitHub owner / repo, eyebrow label, demo URL) feeding
the project template, plus **Repo** and **Label** columns on the Projects list.

### Phase 11 — Footer + content controls
**Footer & Header** Customizer section (footer background/text from palette, footer
social icons, sticky-header toggle) and a **CTA & Intros** section (edit the global
project CTA + the Projects-archive and Contact intros live).

### Phase 12 — (next) Mockups + branding guide
Design draft mockups for each page/layout + header/footer and a visual branding guide,
committed to `/docs`.

## 4. Architecture

- `functions.php` boots Acorn and registers modules:
  `setup, filters, customizer, contact, theme-options, admin-settings, menu,
  projects-admin, footer-content`.
- **Tokens** live in `resources/css/app.css` (`@theme` + `@layer components`).
- **Customizer + admin** both write **theme mods**; appearance overrides are emitted
  via the `mh_head_end` hook (fires after `@vite`, so overrides win with no rebuild).
- **GitHub engine** (`app/Github.php`) caches API data in a 6h transient; exposed as
  `[mh_github]`.
- **Contact** handler runs on `init` (nonce + honeypot + `wp_mail`).

## 5. Module / file map

| File | Role |
|---|---|
| `app/setup.php` | CPT, fonts, menus, sidebars |
| `app/Github.php` + `app/filters.php` | live GitHub data + `[mh_github]` |
| `app/customizer.php` | colors, fonts, header CTA, footer text |
| `app/theme-options.php` | layout engine + top bar + palette helpers |
| `app/admin-settings.php` | tabbed admin Theme Settings page |
| `app/menu.php` | off-canvas popout + social icon system |
| `app/projects-admin.php` | Project Details meta box + columns |
| `app/footer-content.php` | footer builder + CTA/intro controls |
| `app/contact.php` | plugin-free contact form handler |
| `resources/css/app.css` | design tokens + all components |
| `resources/views/*` | Blade templates (front-page, index, single, page, archive, search, 404, projects, contact) |

## 6. Options reference

**Customizer → Theme Options:** Colors · Typography · Layout (per type: preset +
custom width + sidebar) · Top Bar · Menu & Popout · Footer & Header · CTA & Intros.
**Admin → Theme Settings:** General · Design · Layout · Header · Footer · Projects.

## 7. Local dev quick reference

```bash
# from the WordPress root
php "C:\tools\wp-cli\wp-cli.phar" server --host=localhost --port=8080
# in the theme, after edits
npm run build
php "C:\tools\wp-cli\wp-cli.phar" acorn view:clear
```

## 8. Roadmap

- Design mockups + visual branding guide → `/docs` (in progress).
- Re-import the **Projects** content (keepary, tocflow) so `/projects/` fills in.
- Apply **category descriptions** + final **Rank Math** wiring.
- **Social automation** (Bluesky / Dev.to / Reddit) generated with the Claude API —
  review-first, keys stored securely (never in chat).
- Deploy the theme to production matthummel.com.
