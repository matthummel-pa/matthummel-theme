# Changelog

All notable changes to this theme are recorded here.

## 3.0.11 — 2026-08-20

### Changed

- Pages are custom-field layouts only: Gutenberg editor and patterns off for `page`; leftover block HTML cleared on theme-templated pages (privacy policy left alone)
- Unique Blade layouts for About, Services, Work, Code, Now, Contact, Writing, 404, and Search — no `the_content()` on pages
- Hero copy stays on the left; profile photo is 300px on the right; header logo is text only
- Hero blue gradient blobs drift slowly around the banner (off when `prefers-reduced-motion`)
- Primary + footer menus; home “four doors” for developers, learners, shops, and agencies
- Contact form asks who you are; 44px tap targets; new-window text on external links
- Positioning copy leads with full-stack work (WordPress, plugins, other web apps); Power Platform stays in the mix but is not the day job
- Cards and grids use ink borders, blue offset shadows, ExtraBold titles, and a navy featured tile
- Headings use Inter (Helvetica-style, extra bold); body uses IBM Plex Sans next to IBM Plex Mono
- Page gutters match the navbar and footer (80rem, 1.5rem)
- Work and post cards are square-cornered; post cards add cover, topic, read time, and a full-card hit target
- Body copy is larger with more line and paragraph space, in the style of a modern blog
- WCAG 2.2 / Section 508: button and text contrast, 44px targets, mobile menu focus trap, social icons with names
- Single posts: wider left column, right sidebar (summary, TOC, search, popular, topics, RSS), auto heading ids
- Skill chips and social links use icons; navbar is roomier
- Work cards use screenshots from the Ridges & Valleys concept pages (image links to the concept)
- Work screenshots are cropped to one 16:9 size so the grid stays even
- Writing post cards use a 3/2/1 column grid from desktop down to phone
- Code repo cards (featured and live) are square, with View code, Live demo, and GitHub language/topic icons
- Home hero is taller (about a viewport) with more section padding and wider grid gaps sitewide

## 3.0.10 — 2026-08-19

### Changed

- FTP deploy prefers the live FSE `matthummel` folder (and SiteGround `www/<domain>/public_html`) over a Sage dump under a generic `public_html/`

## 3.0.9 — 2026-08-19

### Changed

- FTP locator uses plain IPv4 with retries; if login still fails, the deploy falls back to `SITEGROUND_FTP_REMOTE_DIR`

## 3.0.8 — 2026-08-19

### Changed

- Home landing restyled in the spirit of a personal advocate site: oversized name, two CTAs, tool chips, card grids, navy bands
- Graphic mix of slate and `#2563EB` (hero mesh, stack chips, rounded cards) without copying another portfolio
- Wider layout, pill buttons, navy footer; contrast, skip link, and `prefers-reduced-motion` kept
- Home, About, and footer pull live GitHub profile data (name, bio, location, website, hireable, repo/follower counts, account year) and mix featured repos with recent public work

## 3.0.7 — 2026-08-19

### Changed

- SiteGround deploy locates the live `wp-content/themes/matthummel` folder over FTP instead of trusting a possibly wrong `SITEGROUND_FTP_REMOTE_DIR`
- If live PHP is still 8.2, Sage uploads to `matthummel-sage` (so the FSE site stays up) and the live FSE `theme.json` palette is updated to Sage blue-gray (`#2563EB` / slate)
- If live PHP is 8.3+, the FSE folder is renamed to `matthummel-fse-backup` and Sage is uploaded into `matthummel`

## 3.0.6 — 2026-08-19

### Changed

- Deployment docs: FTP remote dir must be the `matthummel` theme folder; live PHP is 8.2.33 and must be raised to 8.3 before Sage can activate
- Cloud Agent notes for adding WPVibe as an HTTP MCP on cursor.com/agents (plugin is installed but Not Connected)

## 3.0.5 — 2026-08-19

### Added

- Appearance → **Update Theme** (same as Ridges & Valleys): one click dispatches `.github/workflows/deploy.yml`, which builds Sage and FTP-uploads to SiteGround
- GitHub token field on that page and in Appearance → Customize → GitHub (`mh_gh_token`); optional `MH_GITHUB_TOKEN` in wp-config.php
- WP-CLI: `wp mh theme-update`

## 3.0.4 — 2026-08-19

### Changed

- SiteGround deploy cancels overlapping runs (`concurrency`)
- Keep the proven FTP-Deploy-Action (first full upload succeeded; later runs are incremental). Parallel lftp was rejected by SiteGround with 530.

## 3.0.3 — 2026-08-19

### Added

- Ridges-style **Page content (theme)** fields on Home, About, Now, Services, Contact, Code, Work, and Writing
- Repeaters for example sites, featured repos, snippets, and About place cards
- Footer sentence edited from the Home fields

### Changed

- Page copy is no longer hardcoded in Blade; templates read `\App\field()` with the same defaults
- Sample posts rewritten as real snippet notes (skip link + shortcode)
- Now page dated August 2026

## 3.0.2 — 2026-08-19

### Added

- Profile photo next to the site name in the header (circular crop)
- Larger photo on Home and About, beside the greeting
- Same photo on post bylines and the author bio
- Appearance → Customize → Profile photo to replace the bundled headshot

## 3.0.1 — 2026-08-19

Welcoming copy for visitors, new developers, and business owners. Sharing first; paid help is optional.

### Changed

- Home leads with hello, writing, snippets, and example sites
- Services, About, Contact, and post footers drop consultant pitch
- Extra beginner snippets on `/code/` (shortcode, CSS variables, Blade)

## 3.0.0 — 2026-08-19

Replace the Pressroot-era module theme with stock Sage 11.2.1 and a thin portfolio layer.

### Added

- Sage 11.2.1 (Blade, Tailwind v4, Vite, Acorn)
- Portfolio pages: Home, About, Work, Services, Code, Writing, Contact, Now
- Plugin-free contact form (`mh_contact`, nonce, honeypot)
- Cached GitHub user/repo helper and DEV.to feed
- Ridges & Valleys studio project list (filters on Work)
- One-time page/menu seed (`mh_portfolio_seeded_v2`); never deletes posts or categories
- Dark mode toggle (localStorage `mh-theme`)
- Skip link, 3px focus, `prefers-reduced-motion`
- GitHub Actions CI (build + Pint) and FTP deploy to SiteGround on `main`
- Cursor Sage docs assets (`.cursor/docs.json`, `docs/sage/`)
- Theme `.htaccess` to deny public `*.blade.php`

### Changed

- Visual system: Plus Jakarta Sans, cool gray canvas, `#2563EB` blue, ~65ch measure
- Header/footer socials as text links (no icon packs)
- Vite `base` set to `/wp-content/themes/matthummel/public/build/`
- Contact success redirect always returns to `/contact/`

### Removed

- Pressroot/Kadence-era PHP modules (theme options, pattern library, custom Gutenberg blocks)
- Bud, yarn.lock, Blade Icons, CPT project templates
- Consultant landing tropes: numbered 01–04 blocks, uppercase eyebrows, FAQ/process modules, tag pills, striped bands
- Core and remote WordPress block patterns

## 2.0.0

Prior Pressroot-based releases (see git history on older tags). Superseded by 3.0.0.
