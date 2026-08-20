# Feature log

What the 3.x Sage theme does, and where it lives.

## Public site

| Feature | Notes | Code |
| --- | --- | --- |
| Theme updater | Appearance → Update Theme installs the GitHub `theme-latest` zip over HTTPS (FTP optional) | `app/theme-updater.php` |
| Profile photo | Header (small), Home/About (larger), posts; Customizer override | `mh_profile_photo_url()`, `partials/profile-photo.blade.php` |
| Home | Bold blue/gray landing; full-stack role (WordPress, plugins, other web apps); GitHub stats and repos; 2×2 audience doors | `resources/views/partials/home.blade.php`, `App\Github`, `partials/audience.blade.php` |
| About / Now | Split story, audience grid, numbered now list | `template-about.blade.php`, `template-now.blade.php` |
| Work | Ridges & Valleys concepts + screenshots + category filter; editable repeater | `template-projects.blade.php`, `mh_work_page_items()`, `resources/images/work/` |
| Services | Numbered offers, process, FAQ | `template-services.blade.php` |
| Code | Featured + live GitHub cards: View code, Live demo, stack icons | `template-code.blade.php`, `App\Github` |
| Writing | Blog index in a 3/2/1 card grid; search, topics, read time, covers | `index.blade.php`, `archive.blade.php` |
| Contact | Split form + square elsewhere cards; what to send / what happens next; POST `mh_contact` | `template-contact.blade.php`, `app/contact.php` |
| Pages | Named templates + **Page content (theme)** fields; Gutenberg off | `app/bespoke.php`, `app/page-fields.php` |
| Dark mode | `html.mh-dark`, `localStorage mh-theme` | `resources/js/app.js` |
| Mobile menu | Slide-over `#mh-popout` | `sections/header.blade.php` |

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
