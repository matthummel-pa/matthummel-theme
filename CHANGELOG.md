# Changelog

All notable changes to this project are documented here.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-06-26

### Fixed — Dark/light mode CSS audit and accessibility pass

- **`resources/css/app.css`** — added missing `--color-paper: #fbfaf7` to `@theme`. This token was referenced in hero `color:` declarations and `.project-hero-btn--outline` but never defined, causing those properties to resolve to `initial` (browser default) — making text dark on dark hero backgrounds.
- **`resources/css/app.css`** — added dark mode rule for `.form-error`: light-pink card (`#fbeaea`, `#8a2b22`) now overrides to dark-red treatment (`#2a1515`, `#f08080`, `#4a2828`) in `html.mh-dark`.
- **`resources/css/app.css`** — added dark mode `.btn-outline` border and hover-state overrides so ghost buttons use the correct dark-mode line color rather than the light-mode `#e6e2d9`.
- **`resources/css/app.css`** — added dark mode sticky-header glass effect: `rgba(21,23,27,.95)` with `backdrop-filter: blur(12px)` ensuring the nav is readable against any hero below it.
- **`resources/css/page-templates.css`** — added `html.mh-dark .project-page-hero` and `html.mh-dark .project-single-hero` background overrides (`#0c0e11`). Both heroes were hardcoded to `#17191e` with no dark-mode rule, so they stayed at the lighter shade while every other hero correctly deepened in dark mode.
- **`resources/css/page-templates.css`** — added `html.mh-dark .blog-hero-img { background: #1c1f24 }`. The blog hero image placeholder used `var(--color-ink)` which resolves to `#f3f1ea` (cream) in dark mode — now correctly dark.
- **`resources/css/page-templates.css`** — added dark mode filter-pill overrides for `.projects-filter-btn` / `.project-filter-btn` so category chips use dark card colours in dark mode.

## [1.9.5] - 2026-06-26

### Fixed
- **`resources/views/single-projects.blade.php`** — replaced all four `@php(func())` inline patterns with `@php func(); @endphp` block form (lines 4, 137, 182, 220). The inline form compiles to `<?php(func())` without a closing `?>`, leaving subsequent PHP blocks in an open context and causing parse errors on every single project page visit.
- **`resources/views/template-projects.blade.php`** — same `@php(func())` fix for `$mhProjects->the_post()` and `wp_reset_postdata()` calls.
- **`app/setup.php`** — changed `'has_archive' => true` to `'has_archive' => false` for the `projects` CPT. With the rewrite slug matching the `projects` page slug, WordPress was serving the CPT archive template instead of the page template, making `template-projects.blade.php` unreachable.
- **`config/view.php`** — added Acorn view config override pointing compiled views to `wp-content/uploads/acorn-views`. Resolves a Windows file-permission conflict where compiled view files created by the `mhumm` PHP server process could not be overwritten by the `dad` user running the development toolchain, causing persistent "Access is denied" errors on template recompilation.
- **`resources/css/page-templates.css`** — changed `.project-page-hero` and `.project-single-hero` `background` from `var(--color-ink)` to hardcoded `#17191e`. In `html.mh-dark` mode, `--color-ink` resolves to `#f3f1ea` (cream text colour), making dark heroes render with a light cream background.

## [1.9.0] - 2026-06-26

### Added
- **`resources/views/template-projects.blade.php`** — complete redesign: dark ink hero with live GitHub stat strip (public_repos count from `Github::fetchUser()`), sticky category filter pills (All / GitHub / WordPress / Power Platform derived from taxonomy), "Live from GitHub" section using `Github::fetchRepos()` server-side with bento-style repo cards (name, desc, language dot, star count, fork count), manual CPT projects grid with featured project support, section-header layout with view-all links, CTA card at bottom. Filter pill URLs use `add_query_arg()` for clean category switching.
- **`resources/views/single-projects.blade.php`** — complete redesign: dark ink hero with category eyebrow (GitHub icon if repo linked), large project title, description (GitHub API desc → fallback to excerpt), tech stack pills (`_mh_tech_stack` meta), action buttons (Live site / GitHub / README), GitHub stats band (stars, forks, language dot, license, release tag, `owner/repo` link), featured image with rounded bottom corners, project body (post content + GitHub README intro from `Github::render()`), related projects section filtered by same taxonomy term.
- **`app/projects-admin.php`** — two new meta fields: `_mh_tech_stack` (comma-separated tech stack displayed as pills) and `_mh_featured` (checkbox, featured projects get a larger card in the grid); REST endpoint `GET /wp-json/mh/v1/github-repos?user=&count=&sort=` that proxies `Github::fetchRepos()` with transient caching via the existing engine.
- **`resources/css/page-templates.css`** — full projects CSS section: `.project-page-hero` (dark ink + dot-grid + radial glow, matching other page heroes), `.gh-live-strip` + `.gh-live-link` (GitHub stats in hero), `.projects-filter` (filter pill nav), `.github-repos-grid` + `.github-repo-card` (live repo cards with hover lift), `.section-header` + `.section-title` + `.section-view-all` (reusable section layout), `.project-card-grid` + `.project-card--featured` (2-col featured card), `.project-card-eyebrow`, `.tech-pill`, `.project-single-hero` (single post dark ink hero), `.project-single-title`, `.project-tech-pills`, `.project-hero-btn` variants (primary/outline/ghost), `.project-gh-stats` + `.project-gh-stats-inner` + `.gh-stat-item` (GitHub stats band), `.project-featured-img`, `.project-body`, `.project-shot-grid`, `.project-related-grid`; dark mode overrides for all new components.
- **`seed-projects.php`** — WP-CLI eval-file script that creates taxonomy terms (GitHub Projects, WordPress, Power Platform), creates the `Bradley Goldsmith Law` project post with `_mh_demo_url=https://bradleygoldsmithlaw.com`, `_mh_tech_stack=WordPress,Custom Theme,PHP,CSS3,JavaScript,SEO`, `_mh_featured=1`, assigns the WordPress category, flushes rewrite rules. Idempotent — safe to re-run.
- **`deploy-v190.ps1`** — full deploy script: syncs CSS, JS, Blade templates, and `app/*.php` files; runs `npm run build`; seeds projects via WP-CLI eval-file; ensures Projects page exists with correct template; flushes rewrites; clears Acorn caches; commits and pushes `v1.9.0`.

### Changed
- **`resources/views/archive-projects.blade.php`** — left as-is (CPT archive); primary projects experience is via the `template-projects.blade.php` page template.

## [1.8.0] - 2026-06-26

### Changed
- **`resources/css/page-templates.css`** — full visual redesign: stat numbers now green + clamp(2rem→2.75rem) with spring hover; `.skill-card` gets animated left-accent bar on hover + `.skill-card-icon` icon bubble; `.focus-card` top-border accent + spring lift; `.cta-card` dual radial-gradient glows + full z-index stack; `.resume-timeline` gains continuous vertical gradient line + pulsing `@keyframes dot-pulse` on current role dot; `.resource-card` now a flex column with button-style `.resource-card-link`; `.resource-channel-list` restructured with border-bottom rows; `.page-hero` adds dot-grid `::before` + top-right green radial glow `::after`; `.about-page-hero`, `.resources-page-hero`, `.resume-page-hero` all gain radial glow; dark-mode overrides for stat/skill/focus/resource/cta cards.
- **`content-home.html`** — new file replacing inline `set-page-home.php` string; skill cards gain `.skill-card-icon` divs (🌐⚙️📈); improved subtitle and CTA copy.
- **`content-about.html`** — skills and focus cards updated with icons (🌐🚀✅ / 📄⚡); focus card icon spans added; bio headings and copy refined.
- **`content-resume.html`** — skill cards gain icons (⚡☁️🌐); resume links gain emoji prefixes; timeline bullet lists expanded; certs section updated with PL-400 path.
- **`content-resources.html`** — section titles gain emoji prefixes (🎓🎬📄🔄⭐); resource card links updated to button-style; channel list copy refined; personal favourites context improved.

## [1.7.0] - 2026-06-26

### Added
- **`resources/css/page-templates.css`** — major additions: `.page-hero` (dark ink home hero with pulsing badge, terminal box, CTA actions), `.page-section` / `.page-section--cream` / `.page-section--border-top` (generic page section wrappers), `.page-section-eyebrow` / `.page-section-title` / `.page-section-lead` (section typography), `.about-page-hero` / `.resources-page-hero` / `.resume-page-hero` (dark ink heroes per page), `.about-hero-ctas` (dark hero button row), `.resources-category-chips` / `.resources-chip` (category pill chips), `.resource-section` / `.resource-section-title` / `.resource-section-desc` / `.resource-cards` / `.resource-card` / `.resource-channel-list` (resources page layout), `.resume-page-hero` with `.resume-meta` / `.resume-links` / `.resume-download-btn`, `.timeline-body ul` bullet list styling, dark-mode overrides for all new hero sections.

### Changed
- **Home page (ID 17)** — rebuilt from scratch: dark ink `.page-hero` with pulsing availability badge, large heading, terminal box (`$ whoami → matt hummel · full-stack developer`), stat strip (15+ / 5+ / 50+ / 100%), 3-column `.skills-grid` (Front-End / Back-End & Platforms / SEO & Growth), latest posts block, dark `.cta-card` (Open to select side projects).
- **About page (ID 25)** — rebuilt from scratch: dark ink `.about-page-hero` with heading + intro + LinkedIn/contact CTAs, 4-stat cream strip, 3-col `.skills-grid` (Front-End / Back-End / Performance & Accessibility), 2-col `.focus-grid` (WordPress / Power Platform), `.about-bio` journey narrative (3 sections), dark `.cta-card`.
- **Résumé page (ID 15)** — rebuilt from scratch: dark ink `.resume-page-hero` with name / meta / links / download button, full `.resume-timeline` (3 roles with bullet lists), 3-col `.skills-grid` with `.skills-tag-wrap` tag pills, `.resume-timeline` certs section, dark `.cta-card`.
- **Resources page (ID 21)** — rebuilt from scratch: dark ink `.resources-page-hero` with category chips, resource sections for MS Learn / YouTube channels / Blogs / Power Automate / Personal Favourites — all using `.resource-cards` / `.resource-card` layout, `.resource-channel-list` for personal picks, dark `.cta-card`.
- **`resources/views/front-page.blade.php`** — fixed Blade `@php(post_class())` bug; replaced with `implode(get_post_class())` + `@php the_content(); @endphp`.
- **`resources/views/template-about.blade.php`** — same Blade fix.
- **`resources/views/template-resume.blade.php`** — same Blade fix.
- **`resources/views/template-resources.blade.php`** — same Blade fix.

## [1.6.0] - 2026-06-26

### Added
- **`resources/css/page-templates.css`** — full redesign; added new CSS blocks for `.site-header` (sticky frosted-glass header with brand mark, primary nav, social icons, dark toggle, "Hire Me" CTA), `.blog-page-header` (dark ink full-bleed hero), `.blog-hero-card` (2-column hero card with image + body), `.blog-card-grid` / `.blog-grid-card` (responsive 2–3 column grid), `.blog-post-tag`, `.blog-post-meta`, `.blog-read-more`, `.blog-pagination`, `.blog-empty`, `.mh-progress` (reading progress bar), `.post-hero` (dark ink post hero), `.post-featured-img`, `.post-layout` / `.post-main`, `.mh-toc` (table of contents), `.post-author-bio`, `.post-prev-next`, `.mh-copy` (code copy button).
- **`resources/js/reading-progress.js`** — standalone module: scroll-driven reading progress bar, JS-generated table of contents from H2/H3 headings (min 3 headings), and copy buttons on code blocks.
- **`resources/js/app.js`** — wires up dark mode toggle (localStorage persist), popout mobile menu (open/close/overlay/keyboard), and imports `reading-progress.js`.

### Changed
- **`resources/views/sections/header.blade.php`** — complete rewrite from old `.banner` layout to new `.site-header` / `.site-header-inner` with green MH logo mark, primary nav, social icons list, dark-mode toggle button, "Hire Me" CTA, and hamburger-triggered popout menu.
- **`resources/views/template-blog.blade.php`** — complete rewrite; replaced old `.blog-grid` / `.blog-card` layout with new dark page header + featured hero card (first post) + responsive grid of remaining posts. Removed `@php(post_class())` inline directive (broke Blade parser); uses plain class strings instead.
- **`resources/views/archive.blade.php`** — same new hero + grid layout as `template-blog.blade.php` for category/tag/date archive URLs.
- **`resources/views/partials/content-single.blade.php`** — complete rewrite; replaced old `.post-single-header` layout with new `.post-hero` (dark ink), `.post-featured-img`, `.post-layout`, `.mh-toc`, `.post-prose`, `.post-author-bio`, `.post-prev-next`. Removed `@php(post_class())` directive; uses plain `class=""` strings. Removed `$pagination()` callable (unavailable in partials context); uses direct WordPress functions.

### Fixed
- **Nav menu labels** — renamed menu items: Home, Resources, Contact, About (via `fix-nav.ps1`). Removed Privacy Policy and Accessibility Statement items.
- **Site title** — was displaying "portfolio"; corrected to "Matt Hummel" in WordPress options.
- **Blade `@php(func('arg'))` bug** — `@php(post_class('some-class'))` with quoted string argument causes Blade's regex parser to treat everything after the second parenthesis as literal HTML. Fixed by extracting PHP computations into standalone `@php ... @endphp` blocks before the HTML element.

## [1.5.0] - 2026-06-26

### Changed
- **`front-page.blade.php`** — rewritten to render `the_content()` from the static front page instead of hardcoded Customizer theme mods. Blocks now drive the home page layout, consistent with About/Résumé/Resources templates.
- **Home page (ID 17)** — updated with real matthummel.com copy: "Hi, I'm Matt Hummel · WordPress & Power Platform Developer in Gettysburg, PA", bento stat strip (10+ yrs web, 5+ yrs PP, 50+ projects), three-column "What I do" section (Front-End / Back-End & Platforms / SEO & Growth), Latest Posts block, ink CTA band.
- **About page (ID 25)** — updated with real copy: "Hey, I'm Matt Hummel / Senior Power Platform Consultant at Saliense Consulting", terminal box showing real role/company/location, stat strip (5+ PP / 10+ web / 50+ projects), Power Apps + Automate + SharePoint three-column section, My Journey narrative, full tech stack grid, Outside of Tech, ink CTA.
- **Résumé page (ID 15)** — updated with real resume: dark header with name/title/links/download, three experience roles (Saliense Consulting 2022–present, Higher Ed 2015–2022, Earlier Roles 2012–2015) with bullet lists, three-column skills grid (Power Platform / M365 / Web Dev), certs section, ink CTA.
- **Resources page (ID 21)** — updated with real matthummel.com resources: ink hero, MS Learn section with recommended modules, YouTube channels (Shane Young, Reza Dorrani, April Dunnam, Matthew Devaney), blog recommendations, Power Automate resources, personal favourites, ink CTA.

### Fixed
- **`show_on_front` option** — set to `page` with `page_on_front = 17` so the home page renders the static front page instead of the blog roll.
- **Page templates** — confirmed `_wp_page_template` for pages 25/15/21 use filename-only keys matching Sage's registration (e.g. `template-about.blade.php`), not full paths.

## [1.4.0] - 2026-06-26

### Added
- **Page content populated** — Home (ID 17), About (ID 25), Résumé (ID 15), and Resources (ID 21) pages now have full block content matching the 2025-26 design mockups.
- **Page templates set** — About, Résumé, and Resources pages have their `_wp_page_template` meta set to the correct Blade templates via WP-CLI `eval-file`.
- Home page: dark ink hero with `.badge-available` pulsing dot, `.display-xl` heading, `.code-accent` line, stat strip on cream band, asymmetric `.proj-bento` featured project grid, ink CTA band.
- About page: ink hero with terminal box showing whoami output, stat strip, skills grid, experience timeline, ink CTA.
- Résumé page: ink header with download CV button, full experience timeline, 3-column skills grid, ink CTA with outline/filled button pair.
- Resources page: ink hero, two full resource group sections (WordPress, Power Platform, Dev tools, Design, Accessibility, Performance), ink CTA.

## [1.3.0] - 2026-06-26

### Added
- **`design-language.css`** — full design system CSS for 2025-26 developer site trends: `.display-xl`/`.display-lg` oversized headings, `.eyebrow` labels, `.lead` paragraphs, `.badge-available` animated green availability dot, `.terminal-box` with `term-*` line classes, `.bento-grid` variants (4/3/2/2-1/3-2 columns), `.bento-card` with dark/tint modifiers, `.proj-featured`/`.proj-secondary`/`.proj-stack` project bento layout, `.blog-featured-card`, `.read-progress` bar, `.skills-pill-list`, `.filter-tabs`, `.resume-header`, `.code-accent` inline monospace. Reduced-motion and forced-colours support.
- **12 block patterns** — complete redesign of all patterns using the new design language: dark ink heroes with availability badge and terminal box, asymmetric bento stat grids, skills/timeline/resource sections with eyebrow labels, featured project bento layout, full About page and Résumé page compositions, two-column split layout.
- Imported `design-language.css` into `app.css`.

### Changed
- All existing patterns rewritten to use `.display-xl`, `.eyebrow`, `.lead`, `.badge-available`, and `.terminal-box` classes so pages look consistent with the mockup designs.

## [1.2.0] - 2026-06-26

### Added
- **`mh/section` block** — page-builder wrapper with background colour/image + overlay, padding top/bottom (none/sm/md/lg/xl), container width (narrow/contained/wide/full), text colour override, and optional horizontal rule above. InnerBlocks inside, server-side rendered outer wrapper so CSS variables always apply. Live preview in editor.
- **`matthummel` block category** — all `mh/*` blocks now appear under their own "Matthummel" group in the block inserter, registered via `block_categories_all` filter.
- **Block Patterns** — 11 pre-built patterns in the `matthummel` category: hero, stats, skills, focus, timeline, CTA band, resource groups, project cards, about page (full), résumé page (full), two-column text+image. Insert via Inserter → Patterns → Matthummel.
- **Pattern Library admin page** (Appearance → Pattern Library) — shows all registered matthummel patterns with descriptions, keywords, copy-name buttons, plus tips for using Synced Patterns (reusable blocks).
- **`blocks.css`** — dedicated accessibility-first stylesheet for all `mh/*` blocks. Covers section padding/width utilities, all block component styles, visible focus rings (`:focus-visible`), reduced-motion, forced-colours/high-contrast, and print safety. Imported via `app.css`.
- **Simplified page templates** — About, Résumé, and Resources templates now render `the_content()` only; blocks drive the layout via patterns.
- **`functions.php`** — added `blocks-bespoke`, `block-section`, `block-patterns`, and `pattern-library` to the Sage file loader array.

### Changed
- All 6 bespoke block JS editors updated: `category: 'widgets'` → `category: 'matthummel'` so they appear in the correct inserter group.

## [1.1.0] - 2026-06-25

### Added
- **Hero builder** â€” editable eyebrow / H1 / sub-paragraph, 1-3 columns (content + side image + 2nd image), content position (horizontal & vertical), content max-width + spacing with tablet/mobile overrides, and advanced flexbox controls (direction, justify, align, wrap, gap).
- **Hero media** â€” side image / illustration, plus a background cover image with overlay percentage and min-height.
- **In-Customizer image finder** â€” search Openverse (no key), Unsplash and Pexels (optional free keys), or generate a free AI image (Pollinations). Picks are imported into the Media Library (self-hosted) and set as the hero image.
- **Site-wide on-scroll animations** â€” IntersectionObserver reveal (fade-up/in, zoom, pop, blur, slide) with effect + speed, plus a hero entrance animation. Honours prefers-reduced-motion and never leaves content hidden.
- **Responsive controls** â€” per-device hide toggles (navigation/top-bar social, top-bar & navbar buttons, Menu label, logo shrink) and per-breakpoint inner widths (top bar / navbar / message bar); keep the top bar on one line at tablet.
- **Social Icons** Customizer section consolidating icon appearance and the social account URLs.
- Announcement bar hide-on-mobile option.

### Changed
- **Customizer reorganized** â€” split the overloaded Navigation section into Navigation / Social Icons / Responsive, moved header element alignment into Header Layout and popout controls into Menu & Popout, ordered all sections logically, and added section descriptions.
- Top bar, navbar, and message bar are driven by Theme Options again (removed the widget-area approach).
- Hero content position now moves all copy (flex column), not just the buttons.
- Dark mode: the navbar now uses a dedicated dark surface with light text.

### Fixed
- Top-bar social icons no longer render oversized / stacked (grouped-selector bug).
- Removed a duplicate "Sticky header" control (kept the one in Header Layout).

### Removed
- Experimental Canva integration (kept open-source image search + free AI generation).

## [1.0.0] - 2026-06-24

### Added
- Sage 11 theme scaffold (Blade, Tailwind v4, Vite, Acorn).
- "Paper + Space Grotesk" design system as Tailwind `@theme` tokens.
- Kadence-style **Theme Options** Customizer panel (colors, fonts, layout, header, footer) with live CSS-variable overrides.
- Live GitHub project engine (`app/Github.php`) + `[mh_github]` shortcode.
- Plugin-free contact form template + handler (`app/contact.php`).
- Templates: home/landing, blog index, single post, page, archive, search, 404, projects archive, single project.
- Documentation set: development, architecture, design system, content architecture, editorial + dev SOPs.
- CI workflow (build verification).

[1.1.0]: https://github.com/matthummel-pa/matthummel-theme/releases/tag/v1.1.0
[1.0.0]: https://github.com/matthummel-pa/matthummel-theme/releases/tag/v1.0.0
