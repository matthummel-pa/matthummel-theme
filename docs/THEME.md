# matthummel.com — Theme documentation

Deep dive for the Sage theme that powers [matthummel.com](https://matthummel.com). For the short GitHub landing page, see the [root README](../README.md).

Built on [Sage 11](https://roots.io/sage/) with Blade templates, Tailwind v4, Vite 8, and Acorn 6.

---

## Table of contents

1. [Project overview](#project-overview)
2. [How this was planned](#how-this-was-planned)
3. [Tech stack](#tech-stack)
4. [Site architecture](#site-architecture)
5. [File structure](#file-structure)
6. [Local development](#local-development)
7. [WP-CLI commands](#wp-cli-commands)
8. [Deploy](#deploy)
9. [SEO strategy](#seo-strategy)
10. [Accessibility](#accessibility)
11. [Security notes](#security-notes)
12. [Analytics and tracking](#analytics-and-tracking)
13. [Documentation](#documentation)
14. [License](#license)

---

## Project overview

A personal portfolio and professional service site for Matt Hummel — WordPress developer, Gettysburg PA. The site serves three audiences simultaneously:

| Audience | What they come for |
|---|---|
| **Shops and local businesses** | See concept sites, understand what WordPress can look like for their business type, start a project |
| **Agencies and developers** | Evaluate technical skills, review open-source code, discuss overflow work |
| **Recruiters and hiring managers** | Review experience, stack, and availability for full-time or contract roles |

The site is intentionally simple: no page builders, no JavaScript frameworks on the front end beyond small utility modules, no third-party analytics UI. Content is edited in `wp-admin` via custom field boxes — not hardcoded in templates.

---

## How this was planned

This site was built entirely with [Cursor AI](https://cursor.com) as the planning and development environment.

### Planning process

1. **Architecture decisions** — Cursor AI was used to reason through template hierarchy, the custom field approach (`app/page-fields.php`), the GitHub API integration, and the deployment pipeline. Every architectural decision was reviewed before implementation.

2. **Page-by-page design** — Each page was designed through a conversation: what the visitor intent is, what content sections are needed, and how they should be laid out. Screenshots were reviewed after each iteration.

3. **Iterative audit loop** — After building, visual audits were run using Cursor's AI vision tools to catch alignment issues, contrast failures, and content quality gaps. Lighthouse accessibility scores were checked and violations fixed.

4. **Code review** — A full security and quality audit was run using an AI subagent against all `app/` PHP and `resources/js/` JS files. Findings were fixed in the same session.

### What "planned with Cursor AI" means in practice

- AI generated the first version of each template and function
- Every line was reviewed before it shipped
- The AI did not have write access to the live site at any point
- All deploys went through CI (GitHub Actions) which runs Pint and Vite before publishing
- The commit history reflects real, reviewable changes — not generated output dumped in one shot

---

## Tech stack

| Layer | Tool | Version |
|---|---|---|
| Theme framework | [Sage](https://roots.io/sage/) | 11.2.1 |
| PHP | PHP | 8.3 |
| Templates | Blade (via Acorn) | Acorn 6 |
| CSS | Tailwind v4 | v4 |
| Build | Vite | 8 |
| WordPress | WordPress | 6.6+ |
| Dependency manager | Composer | 2 |
| Code style | Laravel Pint | — |
| CI/CD | GitHub Actions | — |
| Hosting | SiteGround | — |
| Local DB | SQLite (dev only) | — |
| Editor | Cursor AI | — |

### Fonts

| Use | Family |
|---|---|
| Headings / display | Inter |
| Body text | IBM Plex Sans |
| Code / mono | IBM Plex Mono |

### Design tokens

All tokens are CSS custom properties defined in `resources/css/app.css` using `@theme`. Key values:

```css
--color-text:      #111827  /* gray-900 */
--color-accent:    #2563eb  /* blue-600 */
--color-border:    #e5e7eb  /* gray-200 */
--color-text-muted: #6b7280 /* gray-500 — WCAG AA compliant */
--page-max:        1200px
--page-gutter:     clamp(1.25rem, 4vw, 2rem)
```

---

## Site architecture

### Page inventory

| URL | Template | Purpose |
|---|---|---|
| `/` | `template-home.blade.php` | Home / landing |
| `/about/` | `template-about.blade.php` | Background, story, availability |
| `/services/` | `template-services.blade.php` | Service offerings, process, FAQ |
| `/hire/` | `template-hire.blade.php` | Focused hire-me conversion page |
| `/projects/` | `template-projects.blade.php` | Studio concept site portfolio |
| `/code/` | `template-code.blade.php` | GitHub showcase, resume, skills |
| `/blog/` | `index.blade.php` | Journal / blog listing |
| `/contact/` | `template-contact.blade.php` | Contact form |
| `/now/` | `template-now.blade.php` | Now page (nownownow.com pattern) |
| `/uses/` | `template-uses.blade.php` | Tech stack and tools reference |
| `/changelog/` | `template-changelog.blade.php` | Public site update log |
| `/thank-you/` | `template-thankyou.blade.php` | Post-form conversion page |
| `/accessibility/` | `template-accessibility.blade.php` | WCAG/508 conformance statement |
| `/privacy-policy/` | `template-privacy.blade.php` | Privacy policy (GDPR) |
| `/terms-of-use/` | `template-terms.blade.php` | Terms of use |
| `/[post-slug]/` | `single.blade.php` | Individual blog posts |
| `/[category]/` | `archive.blade.php` | Category archives |
| `/?s=query` | `search.blade.php` | Search results |

### Data flow

```
wp-admin (Page content box)
    │
    ▼
app/page-fields.php  →  \App\field('key', 'Default text')
    │                          │
    ▼                          ▼
post meta (mh_f_*)    Blade template renders output
```

All visitor-facing copy lives in `wp-admin` under **Pages → [page] → Page content (theme)**. Blade templates read it with `\App\field()`. Leaving a field blank uses the built-in default.

### External API integrations

```
matthummel.com
    │
    ├── GitHub API (public)
    │       └── app/Github.php
    │               ├── fetchUser()      → profile, repos, followers
    │               ├── fetchActivity()  → public events feed
    │               ├── fetchContribs()  → contribution calendar
    │               └── fetch()          → per-repo metadata
    │       All cached in WP transients (6h default, configurable)
    │
    └── DEV.to RSS
            └── app/portfolio.php → mh_devto_feed()
                    Cached 3 hours in WP transients
```

---

## File structure

```
matthummel/
├── app/
│   ├── bespoke.php          # Block editor off on pages, pattern removal, one-time data migrations
│   ├── cache-headers.php    # no-cache on HTML so CDN doesn't serve stale Vite hashes
│   ├── comments.php         # ASCII markdown comments, preview, reply notifications
│   ├── contact.php          # Plugin-free contact form (nonce, honeypot, transient draft)
│   ├── db-migrate.php       # WP-CLI: wp mh db-pull / db-push via SSH
│   ├── filters.php          # Document title and meta description filters (SEO)
│   ├── Github.php           # GitHub API class with transient caching
│   ├── icons.php            # mh_svg_icon() — inline SVG with currentColor
│   ├── page-fields.php      # Page content (theme) field registry + save/render helpers
│   ├── portfolio.php        # Social links, work list, GitHub highlights, DEV.to, page seed
│   ├── setup.php            # Theme setup, asset enqueuing
│   ├── theme-updater.php    # Appearance → Update Theme + WP-CLI commands
│   ├── Providers/
│   │   └── ThemeServiceProvider.php
│   └── View/
│       └── Composers/
│           ├── App.php
│           ├── Comments.php
│           └── Post.php
│
├── resources/
│   ├── css/
│   │   ├── app.css          # Tailwind @theme tokens, global styles
│   │   ├── portfolio.css    # All component and page styles (~6 000 lines)
│   │   ├── graphic.css      # Decorative / illustration styles
│   │   └── code-blocks.css  # VS Code Dark+ syntax highlighting
│   ├── images/
│   │   ├── matt-hummel.jpg  # Profile photo
│   │   └── work/            # Concept site screenshots (16 images)
│   ├── js/
│   │   ├── app.js           # Menu, progress bar, TOC spy, comments, share buttons
│   │   ├── code-blocks.js   # highlight.js, copy button, VS Code window chrome
│   │   ├── writing-tools.js # Journal grid/list toggle, RSS copy
│   │   └── work-tools.js    # Work grid/list toggle, filter, share/copy actions
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php       # Base layout (skip link, header, main, footer)
│       ├── sections/
│       │   ├── header.blade.php    # Site header + mobile slide-over menu
│       │   └── footer.blade.php    # Footer nav + bottom bar (copyright, legal, built-with)
│       ├── partials/
│       │   ├── home.blade.php          # All home page sections (hero, skills, work, journal, CTA)
│       │   ├── content.blade.php       # Blog post card (listing)
│       │   ├── content-single.blade.php # Single post layout (hero, body, sidebar, CTA)
│       │   ├── post-sidebar.blade.php   # Post sidebar (TOC, author, popular, hire CTA)
│       │   ├── work-card.blade.php      # Project concept card
│       │   ├── profile-photo.blade.php  # Profile photo helper
│       │   └── …                        # Other partials
│       ├── template-*.blade.php     # Named page templates (15 total)
│       ├── single.blade.php         # Single post entry point
│       ├── index.blade.php          # Journal / blog listing
│       ├── archive.blade.php        # Category / tag archives
│       └── search.blade.php         # Search results
│
├── .github/
│   ├── workflows/
│   │   └── deploy.yml           # CI: build → zip → GitHub Release → optional FTP
│   └── scripts/
│       ├── preserve-vite-assets.py  # Keep old Vite hashes so cached HTML never 404s
│       ├── db-pull.sh               # Shell-only DB pull (no WP bootstrap needed)
│       └── import-live-posts.py     # Import matthummel.com posts into local WP
│
├── docs/
│   ├── ERRORS.md            # Error reference and debug cheatsheet
│   ├── FEATURES.md          # Feature log and dev tools reference
│   ├── INSTALL.md           # WordPress install after deploy
│   └── sage/                # Sage template and deploy docs
│
├── public/                  # Gitignored — built by CI
│   └── build/
│       ├── manifest.json
│       └── assets/          # Hashed CSS, JS, images
│
├── style.css                # WordPress theme header (name, version, description)
├── functions.php            # Boots Acorn, requires app/ files
├── vite.config.js           # Vite + Tailwind v4 config
├── composer.json            # PHP dependencies (Acorn, Pint, Sage)
└── package.json             # Node dependencies (Vite, Tailwind, highlight.js)
```

---

## Local development

### Prerequisites

- PHP 8.3
- Node 22
- Composer 2
- WP-CLI
- WordPress with SQLite (see `AGENTS.md` for full setup)

### First-time setup

```bash
# 1. Install dependencies
composer install
npm install
npm run build

# 2. Link theme into WordPress
ln -sfn /path/to/this/repo ~/wp-site/wp-content/themes/matthummel

# 3. Activate and seed pages
cd ~/wp-site
wp theme activate matthummel
wp acorn view:clear
wp rewrite structure '/%postname%/'

# 4. Start dev server
wp server --host=0.0.0.0 --port=8080
```

Site: `http://localhost:8080` · Admin: `http://localhost:8080/wp-admin` (user: `admin`, password: `password`)

### Day-to-day commands

| Command | What it does |
|---|---|
| `npm run build` | Build assets to `public/build/` (required for site to load) |
| `npm run dev` | HMR dev server (requires WordPress to be running) |
| `vendor/bin/pint --test` | Check PHP code style |
| `vendor/bin/pint` | Fix PHP code style |
| `wp acorn view:clear` | Clear compiled Blade views (run after any template edit) |

### Import live posts (optional)

```bash
python3 .github/scripts/import-live-posts.py
```

Scrapes all published posts from matthummel.com and imports them locally with categories, tags, and content.

---

## WP-CLI commands

All commands are registered in `app/theme-updater.php` and `app/db-migrate.php`.

| Command | Description |
|---|---|
| `wp mh theme-update` | Download and install `theme-latest` GitHub Release zip |
| `wp mh theme-build` | Trigger a new CI build (dispatch `deploy.yml`) |
| `wp mh db-pull` | Export production DB via SSH → import locally → search-replace URLs |
| `wp mh db-push --yes` | Export local DB → send to production → import + search-replace |

### `db-pull` / `db-push` credential resolution

Credentials are resolved in this order (first match wins):

1. WP-CLI `--ssh-host`, `--ssh-port`, `--ssh-user`, `--ssh-path` flags
2. `MH_SSH_HOST`, `MH_SSH_PORT`, `MH_SSH_USER`, `MH_SSH_WP_PATH` constants in `wp-config.php`
3. `SERVER_IP` / `SITEGROUND_HOST`, `SERVER_SSH_PORT` / `SITEGROUND_PORT`, `SERVER_USER` / `SITEGROUND_USER`, `SERVER_DESTINATION_PATH` environment variables

---

## Deploy

### Automatic (recommended)

Every push to `main` triggers `.github/workflows/deploy.yml`:

```
push to main
    │
    ▼
GitHub Actions runner
    ├── composer install --no-dev --optimize-autoloader
    ├── npm ci && npm run build
    ├── preserve-vite-assets.py (keep old hashes)
    ├── zip theme as matthummel.zip
    ├── publish GitHub Release `theme-latest`
    └── optional: FTP to SiteGround (best-effort, non-blocking)
```

### Install on the live site

```
Appearance → Update Theme → Install latest zip from GitHub
```

Requires a fine-grained GitHub PAT with **Contents: Read** on `matthummel-pa/matthummel-theme`. Paste it once in the Update Theme screen or add `define('MH_GITHUB_TOKEN', 'ghp_...')` to `wp-config.php`.

WP-CLI: `wp mh theme-update`

### What gets deployed

- `app/` — PHP theme logic
- `resources/views/` — Blade templates
- `resources/images/` — Static images
- `public/build/` — Compiled and hashed CSS/JS (built by CI, never committed)
- `vendor/` — PHP dependencies (built by CI, never committed)
- `style.css`, `functions.php`, `*.json` — Config files

**Never committed:** `public/build/`, `vendor/`, `node_modules/`, `.env`, `.deploy-keys/`

---

## SEO strategy

### Target keywords

| Page | Primary keyword | Secondary |
|---|---|---|
| Home | WordPress web design in Gettysburg | WordPress developer for hire |
| Services | WordPress developer for hire in Gettysburg | custom WordPress sites |
| Hire | Hire a WordPress developer in Gettysburg | agency overflow WordPress |
| Work | WordPress sites for Gettysburg businesses | concept site examples |
| About | WordPress developer Gettysburg PA | — |
| Code | WordPress developer GitHub | open source PHP |
| Journal | WordPress development notes | PHP tutorials |

### Implementation

- Document titles: `[Topic] in Gettysburg | Matt Hummel` format
- Meta descriptions: under 155 chars, benefit-led, first-person, include CTA
- Gettysburg: appears naturally on Home, Services, Hire, Work, About — removed from Code and Journal where it adds no value
- Headings: single `<h1>` per page, sequential `<h2>` → `<h3>` hierarchy, never skip levels
- FAQ JSON-LD: `FAQPage` schema on Services page for rich results
- Blog post JSON-LD: `BlogPosting` schema via `content-single.blade.php`

### Filtering

SEO title and description defaults live in `app/filters.php` in the `mh_seo_landing_defaults()` map. Custom overrides can be set per-page via the `seo_title` and `seo_desc` fields in `Page content (theme)`.

---

## Accessibility

This site targets **WCAG 2.1 Level AA** and the 2017 refresh of **Section 508**.

### Implementation highlights

- Skip navigation link as first focusable element on every page
- Landmark elements: `<header>`, `<main>`, `<nav>`, `<footer>`, `<aside>`, `<section>`, `<article>`
- All interactive elements reachable via keyboard; mobile menu has a full focus trap
- `inert` attribute on closed mobile menu (prevents focus entering hidden container)
- Visible focus ring (`2px solid var(--color-focus)`) — uses `:focus-visible` so mouse users see clean UI
- Colour contrast: `--color-text-muted` is `#6b7280` (4.63:1 on white — passes AA)
- All post body links have `text-decoration: underline` (not colour alone)
- Images: `alt=""` on decorative, descriptive `alt` on meaningful
- Forms: every field has `<label>`, `aria-required`, `aria-describedby` for hints and errors
- `prefers-reduced-motion` respected for all transitions
- `aria-current="page"` on active nav item (WordPress adds this automatically)

See `/accessibility/` for the full conformance statement.

---

## Security notes

All `$_POST` / `$_GET` access is wrapped in `wp_unslash()` + `sanitize_*()` before use. Admin actions use `check_admin_referer()`. Front-end form uses `wp_verify_nonce()` + honeypot field.

### Key points

- Contact form: nonce (`mh_contact_nonce`), honeypot (`mh_hp`), `sanitize_text_field()` / `sanitize_email()` / `sanitize_textarea_field()` on all inputs, `wp_mail()` for sending
- Theme updater: `update_themes` capability check before any action; POST blocks use `if/elseif` to prevent double-execution
- Template output: all dynamic values use `{{ }}` (Blade auto-escapes) or explicit `esc_html()` / `esc_attr()` / `esc_url()`; `{!! !!}` only for trusted, already-escaped HTML (WP core functions, `json_encode()` with `JSON_HEX_TAG`)
- Search input: `esc_attr(get_search_query())` in the form value attribute
- JSON-LD: `json_encode()` uses `JSON_HEX_TAG` to prevent `</script>` escaping the block
- `.htaccess`: direct access to `*.blade.php` files is denied

---

## Analytics and tracking

The following tools are active or planned. See `/privacy-policy/` for the full cookie table and opt-out instructions.

| Tool | Status | Purpose |
|---|---|---|
| Google Analytics 4 | Active | Page views, session data, audience |
| Google Tag Manager | Active | Manages all tracking scripts |
| HubSpot | Active | Visitor tracking, CRM, contact capture |
| Microsoft / Bing UET | Active | Bing search performance, ad conversions |
| Meta (Facebook) Pixel | Planned | Ad reach and conversion measurement |

The `/thank-you/` page (post-form redirect) is the primary conversion goal across all tools.

---

## Documentation

| Doc | Contents |
|---|---|
| [../README.md](../README.md) | GitHub landing README |
| [../CHANGELOG.md](../CHANGELOG.md) | Version history from 3.0.0 |
| [ERRORS.md](ERRORS.md) | Error reference and debug cheatsheet |
| [FEATURES.md](FEATURES.md) | Feature log and dev tools reference |
| [INSTALL.md](INSTALL.md) | WordPress install after deploy |
| [sage/deployment.md](sage/deployment.md) | SiteGround deploy and token setup |
| [../AGENTS.md](../AGENTS.md) | Cursor Cloud environment and commands |

---

## License

MIT. Sage is MIT ([Roots](https://roots.io/sage/)).
