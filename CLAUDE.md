@AGENTS.md

# Claude notes

## Default host

**Live site: https://matthummel.com**

Develop on local WordPress Studio. Merging to `main` deploys this theme over SSH to the live site (`.github/workflows/deploy.yml`): build, swap the theme folder, health check, rollback. Do not install a zip on a Hostinger staging copy, and do not publish staging → live from hPanel. Secrets stay in the GitHub `production` environment. If a task names a host you cannot match to matthummel.com, stop and ask.

Rules live in `.claude/rules/` (always-on, or path-scoped with `paths:`) and skills in `.claude/skills/` (on request). That folder is the only source: edit the files directly. Rules for every project (review, WordPress, Sage, a11y, git) come from the `ai-dev-standards-playbook`, installed in `~/.claude/rules/playbook/` (edit that repo, not the copy). When an agent repeats a mistake, fix the rule here.

## Workflow

- `/pb:plan <idea>` — think it through before coding.
- `/pb:review [PR#]` — senior WordPress review. This repo's `wp-reviewer` agent overrides the playbook's and reads `.claude/rules/`.
- `/pb:fix <bug>` and `/pb:ship` — fix, or finish: checks, commit, push, PR. Checks this repo needs: `vendor/bin/pint`, `python3 .github/scripts/wp-review`, and `npm run build` when CSS/JS/Blade changed.
- Merging to `main` deploys over SSH to https://matthummel.com. Do not ship by installing `theme-latest` on a staging copy.
- `wp-review` — WordPress Handbook checks on changed lines (CI: `python3 .github/scripts/wp-review`). Checklist: `.github/review-checklist.md`.
- Local WordPress: WordPress Studio (`studio wp …`, never plain `wp` against a Studio site). Clear Blade cache with `studio wp acorn view:clear` after template edits.
