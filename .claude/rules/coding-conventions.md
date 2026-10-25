# Coding conventions (this repo)

Sage 11 theme (Blade, PHP 8.3, Tailwind v4, Vite). Not a Next.js or Supabase app: no App Router, RSC, Genql, shadcn, or TypeScript unless Matt asks for a separate project. Generic Sage, WordPress, security, and accessibility standards load from the playbook (`~/.claude/rules/playbook/`); this file holds only this repo's deltas. Hard no's: `agency-core.md`. Host and deploy: `hostinger-github-wordpress-workflow.md`. If a new idea fights what ships, keep the shipping pattern unless Matt asks to migrate.

Matt is a marketer who prefers working in code. Keep diffs small and avoid inventing admin UIs.

## Style
- Functional and declarative. Classes only when WordPress or Acorn requires one. Small helpers over duplication.
- Descriptive names with auxiliary verbs (`isLoading`, `hasError`, `shouldRedirect`). Directories and view filenames lowercase with dashes (`partials/work-card.blade.php`).
- PHP: named, typed functions in the `App` namespace; receive an object, return an object (RORO) for helpers with several parameters. Match Pint style in `app/`; run `vendor/bin/pint` after PHP edits.
- JS: `function` for named module functions (not `const fn = () =>`), no semicolons, named exports, `init*` functions imported from `resources/js/app.js`, early return when the DOM node is missing (`if (!root) return;`). No React, Radix, or shadcn.
- File order: public API, helpers, static content, types (JSDoc). Single-line conditionals omit braces. Guard clauses first, happy path last; no nested `else`.

## Errors
- Expected form failures are values (arrays, redirects with transients), not exceptions. Log unexpected failures and show a short human message, never a stack trace.
- Sanitize and unslash request input; nonces on every custom form or AJAX action.

## Templates and data
- Chrome in `resources/views/sections/`, reusable chunks in `partials/`, named pages in `template-*.blade.php`.
- Portfolio logic stays in `app/portfolio.php`, `app/contact.php`, `app/Github.php`. `app/page-fields.php` is frozen legacy. No extra `app/*.php` modules, theme-options screen, Kadence, or page builders unless Matt asks.
- Visitor-facing copy is block content, with no new field calls (`content.md`). Static copy and option lists go in PHP defaults or a content array at the bottom of the file, not inline mid-loop.
- GitHub data goes through `App\Github` with transients and fails soft when GitHub or DEV.to is down. Contact mail is `wp_mail` in `app/contact.php`; a local site has no mail server, so keep the redirect and validation.

## Front end
- Tailwind v4 utilities in Blade; shared look in `resources/css/portfolio.css`. Keep Inter / IBM Plex and the navy palette.
- Dark mode is `html.mh-dark` plus the header toggle; respect `prefers-color-scheme` until the visitor saves a choice.
- Images: dimensions or aspect ratio, lazy-load below the fold, WebP for new assets.

## Testing and docs
- `vendor/bin/pint --test` for PHP, `npm run build` after asset changes, `python3 .github/scripts/wp-review` before a PR.
- Visitor-facing change: check it on the local Studio site. Never delete existing posts or categories in seeds or tests.
- Update `docs/FEATURES.md` and `CHANGELOG.md` for user-visible features. Sage docs first: https://roots.io/sage/docs/ and `docs/sage/`.
