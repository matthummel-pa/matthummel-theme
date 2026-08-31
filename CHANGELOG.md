# Changelog

All notable changes to this theme are recorded here.

## 3.1.37 — 2026-08-31

### Changed

- **How I work** (Home principles + About approach): more upfront technical detail — Sage 11, Blade, Tailwind, Vite, PHP 8.3, GitHub Actions, focused plugins, readable App helpers — in a professional, welcoming tone
- One-time reset (`mh_how_i_work_v1`) clears saved About approach fields so the new defaults show on existing installs

## 3.1.36 — 2026-08-31

### Changed

- Sitewide SEO and marketing copy is skill-first (WordPress, plugins, web apps) instead of Gettysburg keyword stuffing — field defaults, document titles/meta, Blade chrome, contact notes, and CTA trust lines
- One-time reset (`mh_seo_global_copy_v1`) clears saved page fields that still contain Gettysburg / Adams County so new defaults show on existing installs
- Portfolio SEO playbook updated to match (no required city density on landings)

## 3.1.35 — 2026-08-31

### Fixed

- Live critical error: WooCommerce product seed threw uncaught `Invalid or duplicated SKU` on `woocommerce_init` (e.g. `theme-tocflow`). Sync now adopts the existing SKU owner and never fatals the front end

## 3.1.34 — 2026-08-31

### Fixed

- Seed known-good Acorn `packages.php` / `services.php` over FTP after deploy when the live cache cannot recreate manifests (site stayed 500 after Heroicons clear)

## 3.1.33 — 2026-08-31

### Fixed

- Critical error / white screen when `wp-content/cache/acorn` still listed removed Composer providers (e.g. Blade Heroicons). `functions.php` drops stale `packages.php` / `services.php` before Acorn boots; deploy FTP clears those files; theme updater does the same after install

## 3.1.32 — 2026-08-31

### Added

- Live projects sync to WooCommerce products (virtual, sold individually, default **$149**). Work cards and project pages get **Buy theme** (add to cart) and **Get help** (contact form with project prefill)
- Header cart icon when WooCommerce is active; empty cart and checkout return links go to `/projects/`

### Changed

- Public business name is **Matt Hummel** (this site). Visitor copy, resume, Now, About, and Work no longer point shops to Ridges & Valleys as a second studio brand
- Featured GitHub repo on Code/Home is `matthummel-theme` (this theme), not the old studio repo slug

## 3.1.31 — 2026-08-31

### Added

- Marketplace hygiene for Appearance → Themes and Theme Check file lists: `screenshot.png` (1200×900), `readme.txt`, `CREDITS.md`, `Tested up to` / tags in `style.css`
- `docs/MARKETPLACE.md` — why this theme is not a WordPress.org or ThemeForest upload; submit Acreline instead
- `automatic-feed-links` theme support (classic theme requirement)

### Changed

- `LICENSE.md` copyright includes Matt Hummel (Sage MIT notice kept)

## 3.1.30 — 2026-08-31

### Changed

- Project singles live at `/projects/{slug}/` (was `/concept/{slug}/`). Legacy `/concept/` URLs 301 to the new path
- Visitor-facing copy says **project**, not concept (Work cards, home, services, project pages, field defaults)
- Project hero: screenshot sits to the right of the title and summary
- Project pages add buyer documentation: how it’s built, who it’s for, what ships, spec sidebar, and a short FAQ

## 3.1.29 — 2026-08-31

### Added

- WooCommerce: Sage Blade wrappers for Shop (`archive-product`) and single product, plus a **WooCommerce** page template for Cart, Checkout, and My account (classic shortcodes — not WooCommerce 9 block pages)
- WooCommerce: idempotent seed creates/assigns Shop, Cart, Checkout, and My account when the plugin is active; Coming soon is turned off so guests see theme templates
- WooCommerce: `generoi/sage-woocommerce` so WooCommerce templates can render as Blade inside the Sage layout

## 3.1.28 — 2026-08-31

### Fixed

- Project / concept screenshots: **Featured image** now wins over the seeded Screenshot file meta, so setting or regenerating a featured image updates Work cards, home, and `/concept/` pages

## 3.1.27 — 2026-08-31

### Changed

- Contact (`/contact/`) and project brief (`/start/`) submissions POST JSON to the n8n CRM webhook (`https://matthummel.app.n8n.cloud/webhook/crm-contact`). `wp_mail` remains a fallback if the webhook fails.

## 3.1.26 — 2026-08-29

### Changed

- Concept pages use Rank Math (or Yoast) title and meta description when those fields are set, instead of always overriding them with the constructed `Title — Place | Brand` string

## 3.1.25 — 2026-08-28

### Added

- Projects admin: **Generate featured image** (same DALL·E flow as journal posts) — sets the featured image and fills the Work card screenshot URL from concept title/category/place/blurb

### Changed

- Projects CPT supports featured images (`thumbnail`)

## 3.1.24 — 2026-08-28

### Added

- Projects admin: full **concept page custom fields** (eyebrow, summary, problem/approach/result, deliverables, metrics, live demo) — edit anytime after import
- Projects list: **On site** toggle column, sortable **Category** / **Place** / **Date**, filter dropdowns to group by category, place, or on-site status
- Bulk action **Import concept fields (fill empty)** from bundled JSON; one-time fill for empty fields (`mh_concept_fields_admin_v1`)

### Changed

- Projects list and Work grid default to **newest first**

## 3.1.23 — 2026-08-28

### Added

- On-site **concept pages** for Projects at `/concept/{slug}/` (matthummel theme), with narrative seed from studio concepts
- Work / home / services cards link to on-site concept pages; optional **Live demo** opens the clickable demo URL when set

### Changed

- Projects CPT is publicly queryable with rewrite base `concept`; non-live projects stay 404 for visitors

## 3.1.22 — 2026-08-28

### Added

- **Projects** custom post type: Ridges & Valleys studio concepts import once on theme load (`mh_projects_cpt_seeded_v1`)
- wp-admin **Projects** screen: **Show on site** / **Hide from site** row actions, bulk actions, and edit-screen checkbox; only live projects appear on `/projects/` and the home example grid

### Changed

- Ridges & Valleys copy reframed as Gettysburg concept demos (studio brand grows with real projects)

## 3.1.21 — 2026-08-27

### Added

- Post editor **Generate featured image**: DALL·E 3 image from title/excerpt (optional prompt override), sideloads to Media, sets as featured image; uses the same OpenAI key as Customize → DEV.to

## 3.1.20 — 2026-08-27

### Changed

- Journal post-end CTA: WordPress/full-stack heading and copy (Gettysburg SEO), Power Platform detection via title as well as category, shell-style dark panel with accessible **Say hello** / **Hire me** buttons
- Post sidebar author bio: WordPress platforms and full-stack web apps

## 3.1.19 — 2026-08-27

### Changed

- Home principles section: white shell container, fuller section atmosphere, and heading updated for WordPress + full-stack positioning
- Principles footer CTA: replaced low-visibility text link with a solid accessible **Say hello** button (44px target, focus ring, mobile full-width)
- Home process + fit sections: shared white shell containers, softer section atmosphere, and Fit footer upgraded to an accessible **Write a note** button

### Fixed

- In-page jumps to `#principles`, `#fit`, and other home anchors clear the sticky header (`scroll-margin-top`)

## 3.1.18 — 2026-08-27

### Added

- Journal post pages: share buttons for Bluesky, LinkedIn, Facebook, and Reddit (plus copy link)
- Post editor **Social share & drafts**: generators for Bluesky, Facebook, Reddit, LinkedIn, and a DEV.to ranking checklist; Open share buttons; auto-share to Bluesky / publish to DEV.to when credentials are set
- Bluesky Customizer settings (handle, app password, auto-share on publish) and `wp mh bluesky-share`

### Fixed

- “What changed” toggle also wraps the common Gutenberg pattern: a **What changed:** paragraph followed by a list (not only blockquotes)

## 3.1.17 — 2026-08-27

### Added

- Journal prose: “What changed” blockquotes become a closed-by-default toggle separator (class `what-changed`, or blockquote text starting with “What changed”)

### Changed

- Single post hero: title, meta, and share align to the same left column as the article body (shared wide grid as `.post-shell`)

## 3.1.16 — 2026-08-27

### Added

- MU plugin: `mu-plugins/rank-math-rest-meta.php` registers Rank Math focus keyword, title, and description for the REST API (`edit_posts`)
- Theme loads the bundled Rank Math REST meta file when it is not installed site-wide under `wp-content/mu-plugins/`

## 3.1.15 — 2026-08-27

### Added

- Skills: n8n icon (brand mark + pink chip color) on Code defaults and Home Workflow shelf
- Skills: Gemini icon (Google Gemini mark + purple chip color) on Home AI & Tooling shelf and skills marquee


### Fixed

- Single journal posts: fatal error from mistyped `\App\mh_post_has_affiliate_links()` namespace in `content-single.blade.php`

## 3.1.14 — 2026-08-26

### Changed

- About “How I got here”: plainer story copy — dropped odd wording like “flashiest”; one-time meta swap for saved field values

## 3.1.13 — 2026-08-26

### Changed

- Sitewide typography refresh: optical tracking, fluid display/lead scale, roomier sections, softer radii — still Inter / IBM Plex
- Closing CTA band added to Work, Journal, Start, Thank you, Changelog, and Accessibility
- Content presence: Work proof strip, Uses/Now card shells, shared `.content-shell`, softer legal/hire card hovers
- Content chrome: legal prose shells, post CTA atmosphere, long-form type tokens aligned with marketing pages

## 3.1.12 — 2026-08-26

### Changed

- Portfolio presence: redesigned sitewide CTA band (mesh, grid, glow) with clearer hierarchy and secondary actions; footer and page-header atmosphere; light scroll reveals (respects reduced motion); friendlier SEO meta and footer blurb
- Portfolio presence v2: higher-contrast CTA copy, trust note on closing CTAs, unified home/site CTA system, card washes + title accents, reading comfort, stronger focus/skip-link a11y — standout without sacrificing readability

## 3.1.11 — 2026-08-26

### Changed

- About page redesign: field-driven service / approach / availability cards, mesh section shells, stronger Gettysburg SEO title/meta, mobile-friendly stats and cards
- About hero rewrite: benefit-led blurb (WordPress / Sage / PHP / Gettysburg), fact chips, dual CTAs, section jump chips, squared photo with caption
- About above the fold: name kicker, shorter lede, larger photo, proof strip in-hero, jump links below; chip/social clutter removed from first viewport
- About hero lede: casual, friendly voice (what I build + who it helps) without Sage/PHP jargon
- About jump bar: shell-aligned “On this page” nav; friendlier How I got here + section intros; softer service card copy

## 3.1.10 — 2026-08-26

### Changed

- Code page contribution heat map: last 90 days, full-width grid with clearer day circles, entrance/glow animation, hover/focus tips with public activity for that day
- Code page open-source section redesign: grouped practice shelves (title/detail cards, jump chips), featured/recent repo shelves (category chips, push badges, open-repo affordance), grouped skills shelves (jump chips, tinted groups, tile cards), documentation shelf (grouped tinted shelves, scroll jump chips, clearer card affordance), field-driven CTA; tighter title/meta

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
