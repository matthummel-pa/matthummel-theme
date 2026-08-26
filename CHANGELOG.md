# Changelog

All notable changes to this theme are recorded here.

## 3.1.10 — 2026-08-26

### Changed

- Code page contribution heat map: last 90 days, full-width grid with smaller day circles, entrance/glow animation, hover/focus tips with public activity for that day

## 3.1.9 — 2026-08-26

### Changed

- Code page contribution heat map: last 30 days only, newest week first, circular day cells; activity feed filtered to the last 30 days
- Code page hero links are page sections only (Open source, Skills, Docs) plus Hire me → `/hire/`
- Resume moved from Code to Hire; Hire page adds LinkedIn profile panel (OpenID userinfo when token set, soft OG/fallback), share-on-LinkedIn, open-to-work badge, skills, and clearer fit/process CTAs
- Seed utility pages on init: Changelog, Privacy, Terms, Accessibility, Uses, Thank you (plus Hire me)

### Added

- `App\LinkedIn` helper + Appearance → Customize → LinkedIn (access token, headline, about, open-to-work)

## 3.1.8 — 2026-08-25

### Changed

- Code page open-source GitHub section: SEO-focused headings/meta, section jump links, profile showcase with mesh graphic and live API badge, contribution calendar with month labels, typed activity feed beside the heat map, numbered featured repo cards with language color dots

## 3.1.7 — 2026-08-25

### Fixed

- Single post pages (including DEV.to imports): more left/right padding on mobile so content clears the screen edge; code blocks scroll instead of overflowing

## 3.1.6 — 2026-08-25

### Added

- Journal sidebar: thank-you card for DEV.to followers, with avatars from the DEV.to API (Customizer / `MH_DEVTO_TOKEN`) or an optional curated list in Page content
- `DEV.to` journal category plus `wp mh devto-import` / `wp mh devto-sync` to pull articles in as regular posts
- Hourly auto-import of new DEV.to posts (Customizer → DEV.to → Auto-import new posts)
- Export a journal post to DEV.to: editor sidebar converts Gutenberg → Markdown, rewrites for DEV.to (rule-based + optional OpenAI), then creates a draft or publishes (`wp mh devto-export`)

### Changed

- DEV.to profile and RSS feed use `matthummeldev` (`https://dev.to/matthummeldev`)

## 3.1.5 — 2026-08-25

### Changed

- Availability badges (header, home, about, now, contact, hire, code, uses, post sidebar, footer) follow GitHub `hireable` and the profile status emoji/message (☕ Available)
- Profile photo prefers the GitHub avatar when Customizer has no upload (bundled JPG is the next fallback)

## 3.1.4 — 2026-08-25

### Added

- `/start/` project discovery brief — four-step form for agencies and shops (you → project → goals → send)
- Shared Quick start CTA on Home and Services process sections linking to the brief
- Thank-you page copy when arriving from a brief (`?from=start`)

### Changed

- Privacy policy: contact card covers the project brief fields
- `wp mh db-pull` / `db-push`: auto-detect Cloud Agent identity (`~/.ssh/id_ed25519_sg`), `--ssh-identity`, `LIVE_WP_PATH` path fallback, and passphrase unlock via `SERVER_SSH_PRIVATE_KEY_PASSPHRASE`

## 3.1.3 — 2026-08-25

### Changed

- Navbar second pass: sticky header on mobile (was static under 900px), scroll lock when the menu is open
- Mobile menu: Home link, visible “Menu” label, dialog labelled by title, Escape only closes when open (no stray focus steal)
- Availability control meets 44×44 touch target; toggle aria-label switches Open/Close
- Current page title in the popout works on archives, search, and the posts index

## 3.1.2 — 2026-08-24

### Changed

- Single post redesigned for SEO, readability, and modern UX: cleaner article layout, better heading hierarchy, reading time and topic pills in the meta bar
- Post sidebar: streamlined sections, improved spacing, sticky scroll on desktop
- `content-single.blade.php` and `post-sidebar.blade.php` rebuilt with consistent CSS tokens and Tailwind utilities

## 3.1.1 — 2026-08-24

### Changed

- **Complete visual redesign**: minimalist design system across all pages — clean typography, consistent spacing, ink-border cards, flat layouts without layered gradients
- Home: section anchors (`#about`, `#skills`, `#process`, `#work`, `#fit`, `#principles`), jump-nav pills below hero, back-to-top link
- Home: live GitHub API panel in OSS section; Netlify, Supabase, and AI/workflow/marketing tools added to skills ticker
- About: stats bar, story block, audience cards, open-for-work signals, approach grid, journal preview, CTA band
- Services: principles section with 6 cards + icons, AI-assisted builds with human review noted in copy
- Work: SEO and UX improvements, consistent card layout
- Journal: SEO-optimised redesign with cleaner landing structure
- Code: GitHub profile showcase redesign, mobile padding fixed
- Contact / Now: UX/SEO overhaul — shorter copy, cleaner form, footer and header improvements
- `resources/css/portfolio.css`: full rewrite (~6,000 lines revised), all component styles use consistent CSS custom properties

## 3.1.0 — 2026-08-24

### Changed

- **Light mode only**: removed dark mode toggle button from header, removed `localStorage` dark-mode detection script, stripped all `html.mh-dark {}` overrides from `portfolio.css` (~16 KB removed)
- `app.css`: added `color-scheme: light` declaration
- `resources/js/app.js`: removed `preferredTheme()`, `applyTheme()`, `syncThemeToggle()`, `initDarkMode()`, and blob animation — JS bundle shrinks ~2 KB

## 3.0.23 — 2026-08-24

### Added

- WP-CLI `wp mh db-pull` — export prod DB via SSH, import locally, search-replace URLs
- WP-CLI `wp mh db-push` — export local DB, upload to prod via SSH, import + search-replace (requires `--yes`)
- `.github/scripts/db-pull.sh` — shell-only DB pull when no WP bootstrap is available
- CI: SiteGround connection secrets validation step in `deploy.yml` — reports which FTP / SSH secrets are set or missing before the upload begins

## 3.0.22 — 2026-08-23

### Changed

- Writing is now **Journal** in the nav, page title, Home, and SEO (URL stays `/blog/`)
- Journal hero uses a short title and a plain, professional lede
- Journal landing: hero search, newest/oldest sort, year and tag browse, most-discussed list, numbered pagination, two-column tools rail
- One-time rename of known Writing defaults (`mh_journal_rename_v1`)

## 3.0.21 — 2026-08-23

### Changed

- Code page is a professional “What I do” GitHub showcase: profile stats, contribution grid, featured and recent repos, activity feed
- Resume (Saliense + independent web work) sits under GitHub; skill chips use brand-colored icons
- Documentation links for WordPress, Sage, Tailwind, Vite, PHP, MDN, React, and Power Platform
- One-time swap of known informal Code defaults (`mh_code_showcase_v1`)
- Resume block: timeline, current-role card, period/type tags, LinkedIn link
- Resume roles: Ridges & Valleys (current studio), Saliense as previous, independent web work unchanged
- Ridges & Valleys entry: studio just started; still open to agencies and full-time roles
- Code page: Gettysburg is home; shops and agencies in any location are in scope
- Resume employers from LinkedIn: Saliense, All Native Group, Knowledge Capital Associates (USMC), Germanna (Public Information and Marketing, Jul 2011–Oct 2020)
- Code page no longer shows the snippets section (Home still uses the helpers)

## 3.0.20 — 2026-08-21

### Changed

- Local SEO playbook on live landings: document titles (`… in Gettysburg | Matt Hummel`), meta descriptions, 3-sentence ledes, Gettysburg twice in Home/Services/Work/Contact body copy
- Agency cards and overflow FAQ: shop / relationship, not “client”
- Optional **Search preview** fields on pages; one-time swap of known old ledes (`mh_seo_playbook_v1`)

## 3.0.19 — 2026-08-21

### Changed

- Home hero: one filled CTA (Say hello → `/contact/`), ghost See example sites → `/projects/`; GitHub sits in the quick links
- Page-content defaults: shops (not clients) in services/work copy, shorter ledes, “sentences are” on the contact hint
- Nav `aria-label`s: Primary navigation / Mobile navigation

## 3.0.18 — 2026-08-20

### Fixed

- Live CSS 404s from SiteGround HTML cache: keep previous Vite `app-*.css` / `app-*.js` hashes on deploy, and send `no-cache` on HTML so the proxy is not stuck on old filenames

## 3.0.17 — 2026-08-20

### Changed

- More space under headings: page heroes, section titles, cards, post copy, and sidebar labels

## 3.0.16 — 2026-08-20

### Changed

- Writing hub leaves section-sized space under the hero and above the footer (same rhythm as single posts)
- Contact form block uses the same space under the hero and above the footer

## 3.0.15 — 2026-08-20

### Changed

- Work hub: featured concept, search, type counts, Grid/List, unique View/Use/Share/Copy actions
- Share copies a deep link (`/projects/#slug`); Use this concept prefills the contact form
- Bottom CTA for visitors who want a site in the same shape; home example titles link to that hash

## 3.0.14 — 2026-08-20

### Changed

- Writing hub: featured latest post, search/RSS toolbar, Grid/List view, topic counts, `/` to search
- RSS subscribe strip (copy feed URL) on Writing and topic archives
- DEV.to items render as cards; post cards mark snippets with a Code badge
- Search results reuse the same writing tools and card layout
- Post “Read more” is a single unique link (`Read more: [title]`), stretched across the card; excerpts no longer add a generic Continued link

## 3.0.13 — 2026-08-20

### Changed

- Small screens pin Dark and the menu button to the right of the header
- Theme toggle is a moon/sun icon (still 44px, with a name for screen readers)
- Mobile sheet: kicker, current-item bar, hover/focus slide, compact socials with separators
- Single-post featured image sits in the article column above the copy, lined up with the sidebar
- Home hero on small screens keeps the same left/right gutter as the header and sections (no clipped inset)
- Header is in document flow on small screens (not sticky) so it scrolls away with the page
- Page and post heroes use the home navy gradient and blobs, without the drifting animation
- Single posts leave more space between the hero and the article column
- Comments use Write/Preview, ASCII markdown (**bold**, _italic_, `code`), reply mail, and no smart punctuation
- Summary and On this page start closed and open with a toggle
- Post sidebar boxes are square-cornered
- Post and snippet code uses VS Code-style windows with Dark+ highlighting and Copy

## 3.0.12 — 2026-08-20

### Changed

- Dark mode meets WCAG contrast: lighter links and eyebrows, distinct muted text, visible borders, `color-scheme: dark`
- Outline buttons, skill pills, contact fields, skip link, and “elsewhere” cards keep readable text on dark surfaces
- Focus rings use `--color-focus`; form errors and autofill stay visible in dark mode
- Theme follows `prefers-color-scheme` until you pick Dark/Light (saved in `localStorage`)
- Paper grain in dark mode is quieter so body copy stays readable

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
- About header photo sits to the right of the copy at 300×300
- Home / About / Services “who this is for” is a 2×2 grid with icons, numbered doors, and a link on each card
- Theme updates install a GitHub Release zip over HTTPS (Appearance → Update Theme); SiteGround FTP is optional and must not block the build

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
