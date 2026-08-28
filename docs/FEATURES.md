# Feature log

What the 3.x Sage theme does, and where it lives.

## Public site

| Feature | Notes | Code |
| --- | --- | --- |
| Vite assets | Hashed files in `public/build/`; deploys keep old hashes so cached HTML does not 404 CSS | `.github/scripts/preserve-vite-assets.py`, `app/cache-headers.php` |
| Profile photo | Customizer upload → GitHub avatar → bundled headshot → Gravatar | `mh_profile_photo_url()`, `partials/profile-photo.blade.php` |
| Home | Minimalist landing; section anchors + jump-nav pills; live GitHub API panel; skills ticker; 2×2 audience cards; one primary Say hello CTA | `resources/views/partials/home.blade.php`, `App\Github` |
| Shared CTA | Sitewide closing band above the footer on marketing + utility pages: mesh/grid atmosphere, high-contrast type, primary + ghost action, trust note, light scroll reveal | `partials/cta-band.blade.php`, `.cta-band` in `portfolio.css` |
| Typography | Fluid Inter display + IBM Plex body, optical letter-spacing, pretty wrapping, comfortable long-form measure | `resources/css/portfolio.css`, `app.css` @theme |
| Now | Dated list of current focus items | `template-now.blade.php` |
| Work | Featured concept, search, type counts, Grid/List, share/copy deep links, Use this concept → contact prefill; **Projects CPT** with per-project “Show on site” from wp-admin | `template-projects.blade.php`, `partials/work-card.blade.php`, `resources/js/work-tools.js`, `mh_project_*()` in `app/portfolio.php` |
| Services | Principles section (6 cards + icons), numbered offers, process, FAQ | `template-services.blade.php` |
| Code | Open-source GitHub showcase (profile, 90-day contrib grid + tips, activity feed, featured/recent repos), practice cards, skills panel, docs cards, hire CTA | `template-code.blade.php`, `App\Github`, `partials/repo-card.blade.php` |
| Hire | Conversion page with LinkedIn profile panel, resume timeline, skills, process, handoff | `template-hire.blade.php`, `App\LinkedIn`, `partials/resume-timeline.blade.php` |
| Journal | Featured latest post, hero search, newest/oldest sort, Grid/List, topics, years, tags, most discussed, numbered pagination, RSS; unique Read more links | `index.blade.php`, `archive.blade.php`, `partials/write-*.blade.php`, `partials/read-more.blade.php`, `resources/js/writing-tools.js` |
| Single post | Reading progress bar, hero/bottom share (Bluesky, LinkedIn, Facebook, Reddit, copy link), “What changed” collapsible separator (closed by default), inline TOC, desktop sidebar, tags, author bio, post-end CTA (WordPress/full-stack or Power Platform), prev/next, related posts | `single.blade.php`, `partials/content-single.blade.php`, `partials/post-sidebar.blade.php`, `app/social-share.php`, `mh_enhance_what_changed()` |
| Contact | Split form + square elsewhere cards; what to send / what happens next; POST `mh_contact` | `template-contact.blade.php`, `app/contact.php` |
| Search titles / meta | Document title and meta description from the theme (Gettysburg format); optional Page content overrides | `app/filters.php`, `seo_title` / `seo_desc` |
| Light mode | Light-only design; `color-scheme: light`; no dark mode toggle | `resources/css/portfolio.css`, `app.css` |
| Site header | Sticky on all viewports; primary nav + availability + Say hello; current page underline | `sections/header.blade.php` |
| Mobile menu | Slide-over dialog (`#mh-popout`): Home + primary links, scroll lock, focus trap, Escape close, Menu label | `sections/header.blade.php`, `resources/js/app.js` |
| Project brief | `/start/` stepped discovery form for agencies/shops; CTA on Home + Services process | `template-start.blade.php`, `partials/discovery-cta.blade.php`, `app/contact.php` |
| Comments | ASCII markdown, preview, reply notices; `wptexturize` off so punctuation stays typed | `app/comments.php`, `partials/comments.blade.php` |
| Code snippets | VS Code Dark+ windows, highlight.js, copy button on post `pre` and `.snippet` | `resources/js/code-blocks.js`, `resources/css/code-blocks.css` |
| Block editor off on pages | Gutenberg disabled on pages; posts keep the block editor; core patterns stripped | `app/bespoke.php` |
| SVG icons | `mh_svg_icon()` — inline SVG with `currentColor` for brand icons | `app/icons.php` |

## Content helpers

| Feature | Behavior |
| --- | --- |
| Page seed | Creates the standard pages and Primary menu once (`mh_portfolio_seeded_v2`) |
| Projects CPT | Ridges & Valleys concepts imported once (`mh_projects_cpt_seeded_v1`); toggle **Show on site** from **Projects** in wp-admin (list row action, bulk action, or edit screen) |
| Social defaults | GitHub, LinkedIn, DEV.to, Bluesky, Reddit, RSS |
| DEV.to | RSS cached 3 hours; Journal sidebar thanks followers (API key or curated list); `DEV.to` category; hourly auto-import; export journal → Markdown / DEV.to draft (`wp mh devto-export`) |
| Social share | Post editor drafts (Bluesky / Facebook / Reddit / LinkedIn / DEV.to tips); auto-post Bluesky + DEV.to; frontend share intents | `app/social-share.php`, `app/bluesky-share.php`, Customizer → Bluesky |
| Featured image AI | Post editor **Generate featured image** (DALL·E 3 → Media Library → set thumbnail); same OpenAI key as DEV.to | `app/featured-image.php` |
| Bluesky | Auto-share journal posts on publish (AI or pasted summary + link); `wp mh bluesky-share` | `app/bluesky-share.php` |
| GitHub | Transients; optional `mh_gh_token` / `MH_GITHUB_TOKEN`; `hireable` + GraphQL status emoji/message drive availability badges |
| LinkedIn | Hire page profile card; optional `mh_li_token` / `MH_LINKEDIN_TOKEN` for OpenID `/v2/userinfo`; soft OG scrape + field/GitHub fallbacks; share URL helper |

## Intentionally not included

- Page builders, Kadence, theme-options admin
- Gutenberg pattern library on pages (posts still use the block editor)
- Fake testimonials or “3x revenue” style landing modules

## Editor’s notes (3.1.x Projects CPT)

- Cut the home hero to three sentences and put Gettysburg in the lede (kicker already had it).
- Services / Work / Contact ledes: city twice across the page, still first person.
- Dropped “client” on agency cards and overflow FAQ; kept the agency relationship meaning.
- Did not copy the live About title’s “15+ years” claim into the theme.

## Editor’s notes (3.0.19 copy)

- Split services lede so the Power Platform aside is one sentence, not two.
- Swapped “clients” for “shops” on services fit copy and the Work band (glossary).
- Subject–verb on the contact hint: “sentences are.”
- Combined the two fragment closers on About into one sentence.
- Hero CTAs: filled button is the contact action; GitHub is a text link, not a third button.

## Dev tools

| Command | What it does | Code |
| --- | --- | --- |
| `wp mh theme-update` | Download and install the `theme-latest` GitHub Release zip over HTTPS | `app/theme-updater.php` |
| `wp mh theme-build` | Trigger a new CI build (dispatch `deploy.yml`) | `app/theme-updater.php` |
| `wp mh db-pull` | Export prod DB via SSH → import locally → search-replace URLs | `app/db-migrate.php` |
| `wp mh db-push` | Export local DB → upload to prod via SSH → import + search-replace (requires `--yes`) | `app/db-migrate.php` |
| `bash .github/scripts/db-pull.sh` | Shell-only db-pull (no WP bootstrap required) | `.github/scripts/db-pull.sh` |
| `vendor/bin/pint --test` | Check PHP code style (Laravel Pint) | `pint.json` |
| `npm run build` | Build Vite assets into `public/build/` (gitignored) | `vite.config.js` |
| `wp acorn view:clear` | Clear compiled Blade views (run after any template edit) | — |

SSH credentials for `db-pull` / `db-push` resolve in this order:
1. `--ssh-*` WP-CLI flags (`--ssh-host`, `--ssh-user`, `--ssh-path`, `--ssh-identity`, …)
2. `MH_SSH_HOST`, `MH_SSH_PORT`, `MH_SSH_USER`, `MH_SSH_WP_PATH`, `MH_SSH_IDENTITY_FILE`, `MH_SSH_KEY_PASSPHRASE` constants in wp-config.php
3. Env vars: `SITEGROUND_HOST` / `SERVER_IP`, `SITEGROUND_PORT` / `SERVER_SSH_PORT`, `SITEGROUND_USER` / `SERVER_USER`, `SERVER_DESTINATION_PATH` / `LIVE_WP_PATH`, `SERVER_SSH_IDENTITY_FILE`, `SERVER_SSH_PRIVATE_KEY_PASSPHRASE`

Passphrase-protected keys need `SERVER_SSH_PRIVATE_KEY_PASSPHRASE` (or an unencrypted deploy key). Cloud Agents often write the key to `~/.ssh/id_ed25519_sg` — that path is auto-detected.

## Stack

Sage 11.2.1 · PHP 8.3 · Tailwind v4 · Vite 8 · Acorn 6 · WordPress 6.6+ · Inter + IBM Plex Sans
