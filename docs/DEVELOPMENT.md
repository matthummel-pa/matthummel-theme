# Development

A no-Docker local WordPress stack: **PHP + Composer + WP-CLI + SQLite + `wp server`**.

## Prerequisites

- **PHP 8.3+** with extensions: `openssl`, `mbstring`, `curl`, `fileinfo`, `pdo_sqlite`, `sqlite3`
- **Composer** 2.x
- **WP-CLI** (the `wp-cli.phar`) — invoke as `php wp-cli.phar …` if it isn't on `PATH`
- **Node** 20.19+ or 22.12+

## One-time setup

```bash
# 1. WordPress core (in your site folder)
wp core download

# 2. SQLite drop-in (no MySQL needed)
#    - install the "sqlite-database-integration" plugin into wp-content/plugins
#    - copy its db.copy to wp-content/db.php, filling the two path placeholders
wp config create --dbname=wp --dbuser=root --dbpass="" --skip-check

# 3. Install WordPress
wp core install --url="http://localhost:8080" --title="Matt Hummel (Dev)" \
  --admin_user=admin --admin_password=secret --admin_email=dev@example.test --skip-email

# 4. Theme deps + build, then activate
cd wp-content/themes/matthummel
composer install
npm install
npm run build
wp theme activate matthummel
```

## Run

```bash
# from the WordPress root
wp server --host=localhost --port=8080
```

Open http://localhost:8080. Use `npm run dev` (Vite) in the theme for HMR while developing styles/JS.

## Notes

- Set **pretty permalinks** so `/blog/`, `/contact/`, `/projects/` resolve:
  `wp rewrite structure '/%postname%/' && wp rewrite flush`
- After editing Blade templates, clear the compiled view cache: `wp acorn view:clear`
- `wp_mail` won't deliver on a local SQLite stack (no mail server); the contact form's logic still runs and works on production.

## Release / deploy

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

Upload the theme folder **including** `vendor/` and `public/build/` (or run the two build commands on the host). Never deploy `node_modules/`. Tag the release (`git tag -a vX.Y.Z`) and keep `style.css` `Version:` + `CHANGELOG.md` in sync.
