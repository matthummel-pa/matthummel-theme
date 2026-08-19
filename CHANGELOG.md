# Changelog

All notable changes to this theme are recorded here.

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
