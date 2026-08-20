# Feature log

What the 3.x Sage theme does, and where it lives.

## Public site

| Feature | Notes | Code |
| --- | --- | --- |
| Vite assets | Hashed files in `public/build/`; deploys keep old hashes so cached HTML does not 404 CSS | `.github/scripts/preserve-vite-assets.py`, `app/cache-headers.php` |
| Profile photo | Header (small), Home/About (larger), posts; Customizer override | `mh_profile_photo_url()`, `partials/profile-photo.blade.php` |
| Home | Bold blue/gray landing; full-stack role (WordPress, plugins, other web apps); GitHub stats and repos; 2×2 audience doors | `resources/views/partials/home.blade.php`, `App\Github`, `partials/audience.blade.php` |
| Page / post heroes | Same navy gradient + blobs as Home, still (no wander animation) | `partials/page-hero.blade.php`, `partials/hero-graphic.blade.php` |
| About / Now | Split story, audience grid, numbered now list | `template-about.blade.php`, `template-now.blade.php` |
| Work | Featured concept, search, type counts, Grid/List, share/copy deep links, Use this concept → contact prefill | `template-projects.blade.php`, `partials/work-card.blade.php`, `resources/js/work-tools.js` |
| Services | Numbered offers, process, FAQ | `template-services.blade.php` |
| Code | Featured + live GitHub cards: View code, Live demo, stack icons | `template-code.blade.php`, `App\Github` |
| Writing | Featured latest post, search/RSS/copy, Grid/List, topic counts, DEV.to cards, RSS subscribe strip; unique Read more links | `index.blade.php`, `archive.blade.php`, `partials/write-*.blade.php`, `partials/read-more.blade.php`, `resources/js/writing-tools.js` |
| Contact | Split form + square elsewhere cards; what to send / what happens next; POST `mh_contact` | `template-contact.blade.php`, `app/contact.php` |
| Pages | Named templates + **Page content (theme)** fields; Gutenberg off | `app/bespoke.php`, `app/page-fields.php` |
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

## Stack

Sage 11.2.1 · PHP 8.3 · Tailwind v4 · Vite 8 · Acorn 6 · WordPress 6.6+ · Inter + IBM Plex Sans
