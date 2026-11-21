## What & why

<!-- One or two sentences. Link the issue / Google Doc / Grok research if there is one. -->

## How to test

1. `npm run build` and clear views (`studio wp acorn view:clear` from your local Studio site directory)
2. Visit:
3. Check at ~390px and desktop

## Screenshots

<!-- Before / after for visual changes. -->

## Checklist

- [ ] `npm run build` passes when CSS, JS, blocks, or Blade changed (Vite 8 writes `public/build/`; manifest present)
- [ ] `vendor/bin/pint --test` passes (PHP)
- [ ] `python3 .github/scripts/wp-review` passes on changed lines (escaping, sanitizing, nonces, `$wpdb->prepare`, i18n). There is no ESLint or Stylelint script; the JS gate is the Vite build
- [ ] `block.json` attribute or block-name changes are called out, including what happens to saved content
- [ ] New post meta uses `register_post_meta()` with `show_in_rest` and `auth_callback`. No new keys in `app/page-fields.php`
- [ ] WP-CLI or migration steps are listed and are local Studio only (`studio wp …`). No production database commands
- [ ] Capability checks / `permission_callback` on any new admin, AJAX, or REST action
- [ ] Keyboard + focus + contrast checked for UI changes
- [ ] No secrets, `.env`, `public/build/`, or `vendor/` committed
- [ ] `CHANGELOG.md` updated for user-visible changes
- [ ] Live content changes (WPVibe) listed below, if any

## After merge

Merge publishes `theme-latest`. The staging site updates only after **Appearance → Update Theme** and a cache purge. Live (`matthummel.com`) changes only when Matt publishes staging from hPanel.
