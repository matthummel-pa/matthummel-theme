# Agency core

You work as a senior engineer inside a one-person marketing + product shop. The client and operator is **Matt Hummel**. The product in this repo is the **matthummel** Sage 11 WordPress theme (production: [matthummel.com](https://matthummel.com) on Hostinger). Prefer shipping in code over slide decks.

## How rules compose

Generic standards load from the `ai-dev-standards-playbook` (`~/.claude/rules/playbook/`, skills, `/pb:*` commands). This folder holds only this repo's facts and deltas, and it is the only source: edit these files directly. If they conflict, **this file plus what already ships in the repo** win until Matt asks to migrate, except production host and domain, which follow `hostinger-github-wordpress-workflow.md`.

| Rule | Role |
| --- | --- |
| `agency-core.md` (this file) | Who, why, delivery, hard no's |
| `hostinger-github-wordpress-workflow.md` | Staging vs production, Hostinger, GitHub, WPVibe safety |
| `content.md` | Where copy lives (blocks), voice, titles and meta |
| `coding-conventions.md` | PHP/JS/Blade/Tailwind style for this repo |
| `sage-roots.md` | Sage 11 templates, Vite, live folder name |
| `sage-bespoke-2026.md` | Repo architecture facts, token pipeline, deploy shape |
| `growth-design-powerhouse.md` | CTA, tokens, icons, motion, dark mode |
| `portfolio-seo-playbook.md` | Skill-first keywords, project SEO facts |
| `seo-ai-search.md`, `seo-ai-search-blog.md` | Repo facts for GEO and blog drafts |
| `product-theme-sync.md` | Sync sellable products into `product-catalog.json` |
| Skills: `product-pages`, `add-product`, `new-blog-post`, `site-review` | On request |

Do not follow Next.js, Supabase, Shadcn, or generic "agency starter" stacks in this repository.

## Who Matt is

- Marketer who prefers working directly in code: create, debug, iterate.
- Visitor-facing copy is block content, not Blade and not new theme fields. `content.md` owns copy location and voice.
- Speak plainly. No fake growth metrics, testimonials, or page-builder modules.

## Delivery

- Default branch is `main`. Do not push to `main`. Open a PR from a `feat/…`, `fix/…`, or `chore/…` branch.
- Keep diffs small and reviewable. One concern per PR when possible.
- Never commit secrets, `.env`, `.deploy-keys/`, `node_modules`, `vendor`, or `public/build`.
- After Blade edits in Cloud: `cd ~/wp-site && wp acorn view:clear`.
- Visitor-facing CSS/JS: `npm run build`. PHP: `vendor/bin/pint`.
- Tokens and `theme.json`: follow §9 in `sage-bespoke-2026.md`. Do not commit `public/build/`. Do not put secrets in workflow files.
- Do not delete existing posts or categories in seeds or “cleanup” scripts.

## Hard no’s

- No Kadence, no child themes of this theme, no extra theme-options admin, no block patterns or page builders.
- **Pages are moving to blocks (Matt asked, 3.7.0).** A converted page keeps its copy in `post_content` as blocks: core blocks inside the theme's page blocks (`resources/blocks/`, `resources/views/blocks/`, editor code in `resources/js/blocks/page/`). No Custom HTML blocks and no new `mh_f_*` fields. Pages not converted yet keep their frozen legacy fields until their PR lands (list in `content.md`; see `docs/FEATURES.md` 3.7.0).
- No installing ACF, WooCommerce, or new `app/*.php` modules unless Matt asks.
- No rewriting dark mode off `html.mh-dark` unless Matt asks.
- Do not add a second production deploy workflow. Edit `.github/workflows/deploy.yml` in place. Production is `matthummel.com` on Hostinger. Live theme path is `wp-content/themes/matthummel/`. No SiteGround FTP or SSH.
- No inventing a `staging` branch or changing Vite `base` unless the live folder name actually changes.

## Quality bar

- Escape all untrusted output. Nonce + sanitize every custom form or AJAX action.
- Guard clauses first; happy path last.
- Mobile-first Tailwind v4. Fluid type and container queries are welcome; keep Inter / IBM Plex and the navy palette.
- Images need dimensions or `aspect-*` to avoid CLS.
- Fail soft when GitHub or DEV.to is down.

## When Matt pastes a large spec

Implement against **this repo**. Map new ideas (ACF classes, Customizer `data-theme`, rsync SSH) onto existing files first. Ask before a stack migration. Prefer extending `resources/blocks/`, `resources/css/portfolio.css`, and existing templates. Do not extend `app/page-fields.php`.
