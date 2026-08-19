# Matt Hummel theme

Sage 11 WordPress theme for [matthummel.com](https://matthummel.com).

Built from [Roots Sage](https://roots.io/sage/) 11.2.1. Portfolio templates, contact form,
and GitHub/DEV.to helpers live in `app/` and `resources/views/template-*.blade.php`.

## Develop

```bash
composer install
npm install
npm run build
```

Theme directory name in WordPress should be `matthummel` so it matches `vite.config.js`
`base`. See `AGENTS.md` for the Cursor Cloud WordPress stack.
