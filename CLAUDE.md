@AGENTS.md

# Claude notes

## Default host

**Staging ("hummel-dev"): https://matthummel-com-623249.hostingersite.com/**

Theme updates and new posts go to this site, always. Install the `theme-latest` zip here (Appearance → Update Theme, or `wp mh theme-update`), write and publish posts here, and check changes here. Matt publishes staging → live himself from Hostinger hPanel (Websites → Staging → Publish). Never log in to, install on, or edit `matthummel.com`. If a task names a host you cannot match to this one, stop and ask.

Rules live in `.claude/rules/` (always-on, or path-scoped with `paths:`) and skills in `.claude/skills/` (on request). That folder is the only source: edit the files directly. Rules for every project (review, WordPress, Sage, a11y, git) come from the `ai-dev-standards-playbook`, installed in `~/.claude/rules/playbook/` (edit that repo, not the copy). When an agent repeats a mistake, fix the rule here.

## Workflow

- `/pb:plan <idea>` — think it through before coding.
- `/pb:review [PR#]` — senior WordPress review. This repo's `wp-reviewer` agent overrides the playbook's and reads `.claude/rules/`.
- `/pb:fix <bug>` and `/pb:ship` — fix, or finish: checks, commit, push, PR. Checks this repo needs: `vendor/bin/pint`, `python3 .github/scripts/wp-review`, and `npm run build` when CSS/JS/Blade changed.
- Merging to `main` only publishes the `theme-latest` zip on GitHub. Install it on the staging site (Appearance → Update Theme), then purge LiteSpeed; Matt publishes staging → live from hPanel.
- `wp-review` — WordPress Handbook checks on changed lines (CI: `python3 .github/scripts/wp-review`). Checklist: `.github/review-checklist.md`.
- Local WordPress: WordPress Studio (`studio wp …`, never plain `wp` against a Studio site). Clear Blade cache with `studio wp acorn view:clear` after template edits.
