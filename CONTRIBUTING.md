# Contributing

Thanks for your interest in improving the Matt Hummel theme.

## Workflow

1. **Branch** from `main`: `feat/…`, `fix/…`, `chore/…`, or `docs/…`.
2. **Build & check** locally:
   ```bash
   composer install
   npm install
   npm run build
   vendor/bin/pint        # PHP formatting (Laravel Pint)
   ```
3. **Commit** using [Conventional Commits](https://www.conventionalcommits.org/):
   - `feat: add stat-strip block`
   - `fix: focus ring on cards`
   - `docs: update development guide`
4. **Open a PR** into `main`, fill in the template, and link any related issue.

## Code style

- **PHP** follows WordPress + PSR-12 conventions; format with `vendor/bin/pint`. Helper functions live in `app/*.php` under the `App\` namespace. Never declare functions in `resources/views` — those files are included on every render.
- **Blade** for markup; escape output (`{{ }}`); use `{!! !!}` only for already-safe WordPress output (e.g. `the_content`, `get_the_title`).
- **CSS** lives in `resources/css/app.css` as Tailwind v4 `@theme` tokens + `@layer components`. Prefer variables (`var(--color-…)`) over hardcoded values.
- **Accessibility** is a requirement, not a nice-to-have: semantic landmarks, labelled controls, visible focus, AA contrast.

## Releases

- Tag meaningful states: `git tag -a v1.1.0 -m "…"` then `git push --tags`.
- Keep `style.css` `Version:` and `CHANGELOG.md` in sync.

## Never commit

`node_modules/`, `vendor/`, `public/build/`, `*.bak`, `.env`. These are ignored and rebuilt on install/deploy.
