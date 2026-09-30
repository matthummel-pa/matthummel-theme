@AGENTS.md

# Claude notes

Cursor and Claude share one set of rules. `AGENTS.md` (above) and `.cursor/rules/*.mdc` are the source of truth — edit those, not this file, when an agent repeats a mistake.

## Always-on rules

@.cursor/rules/agency-core.mdc
@.cursor/rules/coding-conventions.mdc
@.cursor/rules/growth-design-powerhouse.mdc
@.cursor/rules/hostinger-github-wordpress-workflow.mdc
@.cursor/rules/master-content-writer.mdc
@.cursor/rules/portfolio-seo-playbook.mdc
@.cursor/rules/sage-bespoke-2026.mdc
@.cursor/rules/sage-roots.mdc
@.cursor/rules/wp-review.mdc

## Read before editing matching files

| Rule | Applies to | What it covers |
| --- | --- | --- |
| `.cursor/rules/product-theme-sync.mdc` | `on request` | Playbook for syncing matthummel.com product pages from product theme/plugin repos (Acreline, TOCflow, future packs). |
| `.cursor/rules/seo-ai-search-blog.mdc` | `**/*.{md,mdx,html}` | Draft blog posts for GEO / Google AI Overviews using seo-ai-search standards (BLUF, E-E-A-T, FAQ, Article JSON-LD) |
| `.cursor/rules/seo-ai-search.mdc` | `**/*.{html,jsx,tsx,astro,md,mdx,blade.php,php}` | Enforce Google's latest SEO standards for AI Search (AI Overviews, SGE, and LLM indexing) |
| `.cursor/rules/woocommerce-product.mdc` | `on request` | Standards for WooCommerce product pages, catalog JSON, and product SEO on matthummel.com |

## Workflow

- `/plan <idea>` — think it through before coding.
- `/review [PR#]` — senior WordPress review (uses the `wp-reviewer` subagent).
- `/ship` — checks, commit, push, PR.
- `python3 .github/scripts/wp-review` — WordPress Handbook checks on changed lines. Checklist: `.github/review-checklist.md`.
- Local WordPress: WordPress Studio (`studio wp …`, never plain `wp` against a Studio site). Clear Blade cache with `studio wp acorn view:clear` after template edits.
