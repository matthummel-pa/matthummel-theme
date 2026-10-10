# Bundled modules

Hummel Ops and MH SEO are part of the theme. They live in `app/modules/` and load from `app/bundled-plugins.php`.

| Module | Folder | Version | Purpose |
|---|---|---|---|
| Hummel Ops | `app/modules/hummel-ops/` | 2.2.0 | wp-admin hub: Today, Tasks, Workflows, Files, WP Releases, Integrations |
| MH SEO | `app/modules/mh-seo/` | 1.1.1 | Titles, descriptions, schema, sitemaps, redirects, writing score |

## Switching from the standalone plugins
1. Deploy a theme build that contains these folders.
2. Deactivate the **Hummel Ops** and **MH SEO** plugins, then delete them. Settings are options and tables, so they carry over unchanged.
3. Purge LiteSpeed. MH SEO creates its redirect tables and flushes rewrite rules once on the first load.

If a standalone plugin is still active, its copy wins and the bundled one stays out of the way, so the order of steps 1 and 2 does not matter.

## What changed from the plugin versions
- Plugin activation, deactivation and uninstall hooks do not run in a theme. MH SEO sets itself up once per version on `init`; both modules clean up on `switch_theme`.
- Asset URLs come from the theme folder instead of the plugin folder.
- MH SEO's self-updater is removed. Updates arrive with the theme.
- `uninstall.php` files are removed. Delete the options by hand if you drop a module for good: `hops_*` and `mh_seo_*`.

## Turn a module off
`add_filter( 'mh/bundled_modules', fn ( $m ) => array_diff( $m, [ 'mh-seo' ] ) );`
