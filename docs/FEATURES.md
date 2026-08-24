# Feature log

What the 3.x Sage theme does, and where it lives.

## Public site

| Feature | Notes | Code |
| --- | --- | --- |
| Vite assets | Hashed files in `public/build/`; deploys keep old hashes so cached HTML does not 404 CSS | `.github/scripts/preserve-vite-assets.py`, `app/cache-headers.php` |
| Profile photo | Header (small), Home/About (larger), posts; Customizer override | `mh_profile_photo_url()`, `partials/profile-photo.blade.php` |
| Home | Bold blue/gray landing; one primary Say hello CTA; GitHub in quick links; stats and repos; 2×2 audience doors | `resources/views/partials/home.blade.php`, `App\Github`, `partials/audience.blade.php` |
| Page / post heroes | Same navy gradient + blobs as Home, still (no wander animation) | `partials/page-hero.blade.php`, `partials/hero-graphic.blade.php` |
| About / Now | Split story, audience grid, numbered now list | `template-about.blade.php`, `template-now.blade.php` |
| Work | Featured concept, search, type counts, Grid/List, share/copy deep links, Use this concept → contact prefill | `template-projects.blade.php`, `partials/work-card.blade.php`, `resources/js/work-tools.js` |
| Services | Numbered offers, process, FAQ | `template-services.blade.php` |
| Code | GitHub profile, contribution grid, featured/recent repos, activity, resume, skill chips, docs | `template-code.blade.php`, `App\Github` |
| Journal | Featured latest post, hero search, newest/oldest sort, Grid/List, topics, years, tags, most discussed, numbered pagination, RSS; unique Read more links | `index.blade.php`, `archive.blade.php`, `partials/write-*.blade.php`, `partials/read-more.blade.php`, `resources/js/writing-tools.js` |
| Contact | Split form + square elsewhere cards; what to send / what happens next; POST `mh_contact` | `template-contact.blade.php`, `app/contact.php` |
| Search titles / meta | Document title and meta description from the theme (Gettysburg format); optional Page content overrides | `app/filters.php`, `seo_title` / `seo_desc` |
| Dark mode | `html.mh-dark`, icon toggle, `prefers-color-scheme` until saved | `layouts/app.blade.php`, `resources/js/app.js`, `html.mh-dark` in `portfolio.css` |
| Mobile menu | Slide-over `#mh-popout` with hover motion and compact socials | `sections/header.blade.php` |
| Comments | ASCII markdown, preview, reply notices; `wptexturize` off so punctuation stays typed | `app/comments.php`, `partials/comments.blade.php` |
| Code snippets | VS Code Dark+ windows, highlight.js, copy button on post `pre` and `.snippet` | `resources/js/code-blocks.js`, `resources/css/code-blocks.css` |

## Content helpers

| Feature | Behavior |
| --- | --- |
| Page seed | Creates the standard pages and Primary menu once (`mh_portfolio_seeded_v2`) |
| Social defaults | GitHub, LinkedIn, DEV.to, Bluesky, Reddit, RSS |
| DEV.to | RSS cached 3 hours |
| GitHub | Transients; optional `mh_gh_token` / `MH_GITHUB_TOKEN` |

## Intentionally not included

- Page builders, Kadence, theme-options admin
- Gutenberg pattern library on pages (posts still use the block editor)
- Fake testimonials or “3x revenue” style landing modules
- Custom post type for projects (Work is a page + PHP list)

## Editor’s notes (3.0.20 SEO playbook)

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

SSH credentials for `db-pull` / `db-push` resolve in this order:
1. `--ssh-*` WP-CLI flags
2. `MH_SSH_HOST`, `MH_SSH_PORT`, `MH_SSH_USER`, `MH_SSH_WP_PATH` constants in wp-config.php
3. `SITEGROUND_HOST` / `SERVER_IP`, `SITEGROUND_PORT` / `SERVER_SSH_PORT`, `SITEGROUND_USER` / `SERVER_USER`, `SERVER_DESTINATION_PATH` env vars (mirrors deploy.yml secrets)

## Stack

Sage 11.2.1 · PHP 8.3 · Tailwind v4 · Vite 8 · Acorn 6 · WordPress 6.6+ · Inter + IBM Plex Sans
