# Feature log

What the 3.x Sage theme does, and where it lives.

## Public site

| Feature | Notes | Code |
| --- | --- | --- |
| Theme updater | Appearance → Update Theme dispatches GitHub Actions FTP deploy | `app/theme-updater.php` |
| Profile photo | Header (small), Home/About (larger), posts; Customizer override | `mh_profile_photo_url()`, `partials/profile-photo.blade.php` |
| Home | Hello, writing, snippets, example sites, optional help | `resources/views/partials/home.blade.php` |
| About / Now | Bio, two shops, current focus | `template-about.blade.php`, `template-now.blade.php` |
| Work | Ridges & Valleys concepts + category filter; editable repeater | `template-projects.blade.php`, `mh_work_page_items()` |
| Services | WordPress, Power Platform, full-stack, fixes | `template-services.blade.php` |
| Code | Featured repos, live GitHub, snippets | `template-code.blade.php`, `App\Github` |
| Writing | Blog index; categories unchanged | `index.blade.php`, `archive.blade.php` |
| Contact | POST `mh_contact`, nonce, honeypot | `template-contact.blade.php`, `app/contact.php` |
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
- Gutenberg pattern library
- Fake testimonials or “3x revenue” style landing modules
- Custom post type for projects (Work is a page + PHP list)

## Stack

Sage 11.2.1 · PHP 8.3 · Tailwind v4 · Vite 8 · Acorn 6 · WordPress 6.6+
