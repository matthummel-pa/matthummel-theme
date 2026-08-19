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

## Sage docs

- https://roots.io/sage/docs/
- https://roots.io/sage/docs/theme-templates/
- Local notes: [`docs/sage/`](docs/sage/)

## Live deploys

1. Change the theme in Cursor.
2. Commit and push. Merge to `main`.
3. GitHub Action [Deploy to SiteGround](../../actions/workflows/deploy.yml) runs `composer install --no-dev`, `npm run build`, and FTP-uploads to `wp-content/themes/matthummel`.

Needs repo secrets `SITEGROUND_FTP_HOST`, `SITEGROUND_FTP_USER`, `SITEGROUND_FTP_PASSWORD`, and `SITEGROUND_FTP_REMOTE_DIR`. Details: [`docs/sage/deployment.md`](docs/sage/deployment.md).
