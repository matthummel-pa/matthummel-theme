# Matt Hummel theme

Sage 11 WordPress theme for [matthummel.com](https://matthummel.com).

## What this is

A stock [Roots Sage](https://roots.io/sage/) 11.2.1 install plus a thin portfolio layer:

| Piece | Role |
| --- | --- |
| Blade page templates | Home, About, Work, Services, Code, Writing, Contact, Now |
| `app/portfolio.php` | Social links, Ridges & Valleys work list, GitHub highlights, DEV.to feed, one-time page seed |
| `app/contact.php` | Plugin-free contact form |
| `app/Github.php` | Cached GitHub API helper |
| `resources/css/portfolio.css` | Blue/gray, reader-width type (rounded Nunito) |

Existing WordPress **posts and categories are never deleted**. Gutenberg block patterns (core and remote) are turned off.

## Requirements

- PHP 8.3
- Node 22
- Composer 2
- WordPress 6.6+

## Local develop

```bash
composer install
npm install
npm run build
```

Point WordPress at this folder **named** `matthummel` so it matches `vite.config.js` `base`:

```bash
ln -sfn /path/to/matthummel-theme wp-content/themes/matthummel
wp theme activate matthummel
```

- Dev CSS/JS: `npm run dev`
- PHP style: `vendor/bin/pint --test`

Cursor Cloud WordPress notes: [`AGENTS.md`](AGENTS.md).

## Documentation

| Doc | What’s in it |
| --- | --- |
| [CHANGELOG.md](CHANGELOG.md) | Version history |
| [docs/FEATURES.md](docs/FEATURES.md) | Feature log |
| [docs/INSTALL.md](docs/INSTALL.md) | WordPress install after a deploy |
| [docs/sage/](docs/sage/) | Sage templates, Vite, SiteGround deploy |
| [Sage docs](https://roots.io/sage/docs/) | Official Roots reference |
| [Theme templates](https://roots.io/sage/docs/theme-templates/) | WordPress template hierarchy in Sage |


## License

MIT. Sage is MIT ([Roots](https://roots.io/sage/)).
