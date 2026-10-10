## What & why

<!-- One or two sentences. Link the issue / Google Doc / Grok research if there is one. -->

## How to test

1. `npm run build` and clear views (`studio wp acorn view:clear`)
2. Visit:
3. Check at ~390px and desktop

## Screenshots

<!-- Before / after for visual changes. -->

## Checklist

- [ ] `wp-review` passes (escaping, sanitizing, nonces, `$wpdb->prepare`, i18n on changed lines)
- [ ] `vendor/bin/pint --test` passes
- [ ] `npm run build` passes (if CSS/JS/Blade changed)
- [ ] Capability checks / `permission_callback` on any new admin, AJAX, or REST action
- [ ] Keyboard + focus + contrast checked for UI changes
- [ ] No secrets, `.env`, `public/build/`, or `vendor/` committed
- [ ] `CHANGELOG.md` updated for user-visible changes
- [ ] Live content changes (WPVibe) listed below, if any

## After merge

Merge publishes `theme-latest`. Live site updates only after **Appearance → Update Theme** and a cache purge.
