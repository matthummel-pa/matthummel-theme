# Theme templates

Source: https://roots.io/sage/docs/theme-templates/

Sage keeps the [WordPress template hierarchy](https://developer.wordpress.org/themes/classic-themes/basics/template-hierarchy/). Files live in `resources/views/`.

## Hierarchy files in this theme

- `404.blade.php` — error 404
- `index.blade.php` — blog page, categories, authors, generic archives
- `page.blade.php` — default single page
- `search.blade.php` — search results
- `single.blade.php` — single post
- `front-page.blade.php` — static front page
- `template-custom.blade.php` — example custom page template
- `template-home.blade.php`, `template-about.blade.php`, `template-projects.blade.php`, `template-services.blade.php`, `template-code.blade.php`, `template-contact.blade.php`, `template-now.blade.php`, `template-blog.blade.php` — portfolio page templates

Root `index.php` renders the matched Blade view. `layouts/app.blade.php` wraps every template with skip link, header, `<main id="main">`, and footer.

## Chrome vs partials (Sage 11)

Official docs mention `partials/header.blade.php`. Sage 11.2.1 (this repo) uses:

- `sections/header.blade.php`
- `sections/footer.blade.php`
- `sections/sidebar.blade.php`

Keep post/page chunks in `partials/` (`content.blade.php`, `content-page.blade.php`, `content-single.blade.php`, `page-header.blade.php`, …).

## Extending

Copy a close template and rename it to match the hierarchy:

- Author archive → `author.blade.php` (from `index.blade.php`)
- Latest-posts home (no static front page) → `home.blade.php`
- CPT `gallery` archive → `archive-gallery.blade.php`
- Page slug `about` → `page-about.blade.php` (we already use a named page template instead)

Do not invent a second layout system. Extend `layouts.app` and `@section('content')`.
