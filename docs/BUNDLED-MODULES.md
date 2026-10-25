# Bundled modules

Hummel Ops is part of the theme. It lives in `app/modules/` and loads from `app/bundled-plugins.php`.

| Module | Folder | Version | Purpose |
|---|---|---|---|
| Hummel Ops | `app/modules/hummel-ops/` | 2.5.0 | wp-admin hub: Today, Tasks, Workflows, Files, WP Releases, Integrations |

## Switching from the standalone plugin
1. Deploy a theme build that contains this folder.
2. Deactivate the **Hummel Ops** plugin, then delete it. Settings are options and tables, so they carry over unchanged.
3. Purge LiteSpeed.

If the standalone plugin is still active, its copy wins and the bundled one stays out of the way, so the order of steps 1 and 2 does not matter.

## What changed from the plugin version
- Plugin activation, deactivation and uninstall hooks do not run in a theme. The module cleans up on `switch_theme`.
- Asset URLs come from the theme folder instead of the plugin folder.
- `uninstall.php` is removed. Delete the options by hand if you drop the module for good: `hops_*`.

## Turn a module off
`add_filter( 'mh/bundled_modules', fn ( $m ) => array_diff( $m, [ 'hummel-ops' ] ) );`

## SEO
SEO is handled by Rank Math SEO. The theme's own title and description fallbacks in `app/filters.php` step aside when it is active. MH SEO was removed in 3.8.0; see `CHANGELOG.md` for the data you need to carry over.
