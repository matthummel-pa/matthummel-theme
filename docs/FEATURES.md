# Feature log

What the 3.x Sage theme does, and where it lives.

## Public site

| Feature | Notes | Code |
| --- | --- | --- |
| Marketplace files | `screenshot.png`, `readme.txt`, `CREDITS.md` for Theme Check / Appearance. **Do not** upload this theme to WordPress.org or ThemeForest — see `docs/MARKETPLACE.md` | `docs/MARKETPLACE.md` |
| Vite assets | Hashed files in `public/build/`; deploys keep old hashes so cached HTML does not 404 CSS | `.github/scripts/preserve-vite-assets.py`, `app/cache-headers.php` |
| Profile photo | Customizer upload → GitHub avatar → bundled headshot → Gravatar | `mh_profile_photo_url()`, `partials/profile-photo.blade.php` |
| Home | Two-column hero; recruiter glance (employers + Power Platform + adjacent-work sentence → `/hire/`); section anchors; skills ticker; audience cards; Hire me primary CTA | `resources/views/partials/home.blade.php`, `partials/recruiter-glance.blade.php`, `App\Github` |
| Marketing pages | Split hero: copy left, window-card panel right (stats/snapshot per page) via `partials/hero-panel.blade.php` | `template-*.blade.php`, `partials/page-hero.blade.php` |
| SEO | Per-template `mh_seo_landing_defaults()` titles/descriptions; page fields for overrides; Woo shop titles | `app/filters.php`, `app/page-fields.php` |
| Shared CTA | Sitewide closing band above the footer on marketing + utility pages: mesh/grid atmosphere, high-contrast type, primary + ghost action, trust note, light scroll reveal | `partials/cta-band.blade.php`, `.cta-band` in `portfolio.css` |
| Typography | Fluid Inter display + IBM Plex body, optical letter-spacing, pretty wrapping, comfortable long-form measure | `resources/css/portfolio.css`, `app.css` @theme |
| Now | Dated list of current focus items | `template-now.blade.php` |
| Work | Featured project, search, type counts, Grid/List, share/copy links; context + audience + how-to + FAQ sections (field-driven); **Concept** badge; **View concept** primary, **Buy theme** ghost; **Projects CPT** | `template-projects.blade.php`, `mh_work_page_fit/how/faq()`, `partials/work-card.blade.php`, `resources/js/work-tools.js` |
| Uses | Stack reference with Page content fields; affiliate disclosure; external link screen-reader labels | `template-uses.blade.php`, `app/page-fields.php` |
| Resources | Catalog with Page content fields; disclosed affiliate links | `template-resources.blade.php`, `mh_resources_catalog()`, `app/page-fields.php` |
| Services | Principles section (6 cards + icons), numbered offers, process, FAQ | `template-services.blade.php` |
| Code | Open-source GitHub showcase (profile, followers + stargazers thank-you, earned badges, 90-day contrib grid + tips, activity feed, featured/recent repos), practice cards, skills panel, docs cards, hire CTA | `template-code.blade.php`, `App\Github`, `partials/repo-card.blade.php` |
| Hire | Conversion page with LinkedIn profile panel, resume timeline, skills, process, handoff | `template-hire.blade.php`, `App\LinkedIn`, `partials/resume-timeline.blade.php` |
| Journal | Featured latest post, hero search, newest/oldest sort, Grid/List, topics, years, tags, most discussed, numbered pagination, RSS; unique Read more links; source posts in `docs/posts/` as Gutenberg block markup; single-post hero shows featured image beside title/meta; **Tool Blocks** (`matthummel/tool-grid` + `tool-card` with icon/mark/labels) plus `ship-pipe` / `ship-step` | `index.blade.php`, `archive.blade.php`, `partials/content-single.blade.php`, `resources/js/blocks/`, `app/blocks.php`, `resources/css/journal-blocks.css`, `resources/css/editor.css`, `docs/posts/` |
| Single post | Reading progress bar, hero/bottom share (Bluesky, LinkedIn, Facebook, Reddit, copy link), “What changed” collapsible separator (closed by default), inline TOC, desktop sidebar, tags, author bio, post-end CTA (WordPress/full-stack or Power Platform), prev/next, related posts | `single.blade.php`, `partials/content-single.blade.php`, `partials/post-sidebar.blade.php`, `app/social-share.php`, `mh_enhance_what_changed()` |
| Contact | Split form + square elsewhere cards; what to send / what happens next; POST `mh_contact` → n8n CRM webhook (`wp_mail` fallback) | `template-contact.blade.php`, `app/contact.php` |
| Search titles / meta | Document title and meta description from the theme (skill-first WordPress / full-stack wording, no city stuffing); Rank Math / Yoast values win on project pages when set; optional Page content overrides | `app/filters.php`, `seo_title` / `seo_desc` |
| Light mode | Light-only design; `color-scheme: light`; no dark mode toggle | `resources/css/portfolio.css`, `app.css` |
| Site header | Sticky on all viewports; primary nav + availability + Say hello; current page underline | `sections/header.blade.php` |
| Mobile menu | Slide-over dialog (`#mh-popout`): Home + primary links, scroll lock, focus trap, Escape close, Menu label | `sections/header.blade.php`, `resources/js/app.js` |
| Project brief | `/start/` stepped discovery form for agencies/shops; CTA on Home + Services process; POST `mh_discovery` → n8n CRM webhook (`wp_mail` fallback) | `template-start.blade.php`, `partials/discovery-cta.blade.php`, `app/contact.php` |
| Comments | ASCII markdown, preview, reply notices; `wptexturize` off so punctuation stays typed | `app/comments.php`, `partials/comments.blade.php` |
| Code snippets | VS Code Dark+ windows, highlight.js, copy button on post `pre` and `.snippet` | `resources/js/code-blocks.js`, `resources/css/code-blocks.css` |
| Block editor off on pages | Gutenberg disabled on pages; posts keep the block editor; core patterns stripped | `app/bespoke.php` |
| SVG icons | `mh_svg_icon()` — inline SVG with `currentColor` for brand icons | `app/icons.php` |
| WooCommerce | Optional. Theme support + gallery; Blade shop/product templates with heroes, crumbs, empty states; Cart / Checkout / My account classic shortcodes; SEO titles/meta; a11y focus/notices/tables; seed when active (`mh_woocommerce_pages_seeded_v1`); projects sync to virtual products (`mh_woocommerce_project_products_seeded_v1`); header cart when ready | `app/woocommerce.php`, `app/shop.php`, `app/filters.php`, `resources/views/woocommerce/`, `partials/woocommerce-crumb.blade.php`, `template-woocommerce.blade.php`, `portfolio.css`, `generoi/sage-woocommerce` |

## Editor’s notes (3.1.52 AI comparison UX)

- Cards use a 1px grey border and a letter chip (`data-mark`). Do not add a left accent stripe. Blue stays on chips, pills, and links.
- Pipeline (`.mh-ship-pipe`) is numbered cards, not a left timeline bar.
- After merge, Matt must paste `docs/posts/what-actually-gets-faster-with-ai.html` into the live WP post (or publish it — `https://matthummel.com/what-actually-gets-faster-with-ai/` 404s as of this change). Rank Math title: `What AI Speeds Up in WordPress | Matt Hummel`.

## Editor’s notes (3.1.51 AI comparison post)

- New journal post lives in `docs/posts/`, not Blade. Local import: `.github/scripts/import-docs-posts.py`. Production still needs a wp-admin paste (this repo cannot publish matthummel.com).
- Infographic is HTML+CSS in the post body. Classes: `mh-tool-grid`, `mh-tool-card`, `mh-ship-pipe`, `mh-ship-note`. FAQ reuses `.faq-list`.
- Tools named are only ones already on Uses / Home / contact: Cursor, ChatGPT, Claude, Gemini, VS Code, n8n, MCP, GitHub Actions, Vite. No fake time savings.

## Editor’s notes (3.1.50 adjacent work)

- One sentence from `mh_adjacent_range_copy()`. Glance, About intro, Hire, and the home FAQ use it. Do not paraphrase into a skill cloud.
- FAQ question is **Do you only do WordPress?** — tighten that item; do not add a second FAQ.
- One-shot `mh_adjacent_work_copy_v1` is exact-string only (old About services intro).

## Editor’s notes (3.1.49 contrast and layout)

- Tokens: muted/body text is gray-600/800, not gray-500. One blue accent family.
- Glance keeps the hire story; About/Now/footer use the same Power Platform sentence and concept-gallery wording. Do not add a public demo or email.
- Home extra CTAs (about avail card, fit/principles mini-CTAs, FAQ avail card) stay off — glance + header + closing band cover hire.
- One-shot `mh_hireability_visual_v1` is exact-string only.

## Editor’s notes (3.1.48 recruiter hireability)

- Glance sits under the ticker. Named employers and Power Platform copy come from `/hire/` — do not invent extra companies, a demo, a public email, or a resume PDF.
- GitHub featured list is theme-hardcoded: pressroot, matthummel-theme, tocflow, ridgesandvalleys, keepary. Pin those on github.com too. tocflow is not claimed as a WordPress.org listing.
- Work cards stay **Concept** even when a theme pack is for sale. Checkout is a side path.
- Homepage about strip should not repeat glance facts (years, availability types, Power Platform).
- One-shot `mh_hireability_recruiter_v1` only writes empty or exact prior meta.

## Editor’s notes (3.1.47 home hero SEO)

- H1 keeps the name and adds the primary phrase (`WordPress developer`).
- Role line carries stack + audience (shops / agencies).
- Lede: ownership benefit, stack proof, open-for-work close — three short sentences.
- Empty fields restore via `mh_home_hero_default()` (Blade + `mh_seo_landing_defaults`). Do not pass older H1/role/lede strings into `field()`.
- One-shot `mh_home_hero_seo_copy_v1` only writes empty or exact prior home meta.

## Editor’s notes (3.1.41 home hero)

Above-the-fold is copy left + illustration right. Stats (repos, followers, Remote, Full stack) sit in the illustration only — not under the CTAs. Availability is a status pill on the card. Keep the left column to kicker, name, role, lede, and two actions.

## Editor’s notes (3.1.40 home hero)

- First viewport: name, role, short lede, Hire me + Browse work (+ quiet GitHub). Stats live in the right-column viz panel, not under the CTAs.

## Editor’s notes (3.1.39 hireable + affiliate)

- Portfolio stays primary (Work / Hire / Code / Journal). Shop is Themes only in footer. Resources holds free starters + disclosed affiliates.
- Compensated links: visible Affiliate badge + page disclosure + `rel="sponsored noopener"`.

## Content helpers

| Feature | Behavior |
| --- | --- |
| Page seed | Creates the standard pages and Primary menu once (`mh_portfolio_seeded_v2`) |
| Projects CPT | Studio projects imported once (`mh_projects_cpt_seeded_v1`); narrative/custom fields seed (`mh_concept_pages_seeded_v1`, `mh_concept_fields_admin_v1`); editable project fields in wp-admin; list columns for On site (toggle), Category, Place with filters + newest-first sort; public singles at `/projects/{slug}/` (legacy `/concept/` 301s); **Generate featured image** on the edit screen (featured image wins over Screenshot file meta for Work/project cards); project pages include architecture, handoff, spec, and buyer FAQ; themes for sale sync to WooCommerce products |
| Social defaults | GitHub, LinkedIn, DEV.to, Bluesky, Reddit, RSS |
| DEV.to | RSS cached 3 hours; Journal sidebar thanks followers (API key or curated list); `DEV.to` category; hourly auto-import; export journal → Markdown / DEV.to draft (`wp mh devto-export`) |
| Social share | Post editor drafts (Bluesky / Facebook / Reddit / LinkedIn / DEV.to tips); auto-post Bluesky + DEV.to; frontend share intents | `app/social-share.php`, `app/bluesky-share.php`, Customizer → Bluesky |
| Featured image AI | Post + **Projects** editor **Generate featured image** (DALL·E 3 → Media Library → set thumbnail; Projects also fill Work card screenshot URL); same OpenAI key as DEV.to | `app/featured-image.php` |
| Bluesky | Auto-share journal posts on publish (AI or pasted summary + link); `wp mh bluesky-share` | `app/bluesky-share.php` |
| GitHub | Transients; optional `mh_gh_token` / `MH_GITHUB_TOKEN`; `hireable` + GraphQL status emoji/message drive availability badges |
| LinkedIn | Hire page profile card; optional `mh_li_token` / `MH_LINKEDIN_TOKEN` for OpenID `/v2/userinfo`; soft OG scrape + field/GitHub fallbacks; share URL helper |

## Intentionally not included

- Page builders, Kadence, theme-options admin
- Gutenberg pattern library on pages (posts still use the block editor)
- Fake testimonials or “3x revenue” style landing modules

## Editor’s notes (3.1.36 SEO)

- Marketing and SEO copy is skill-first (WordPress, plugins, web apps). City names are not required on landings.
- Demo Work card places may still name a town; titles/meta/kickers should not stuff location.
- One-time meta reset: `mh_seo_global_copy_v1` clears saved `mh_f_*` fields that still contain Gettysburg / Adams County.

## Editor’s notes (3.1.x Projects CPT)

- Home hero stays short; kicker leads with craft, not a city.
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
