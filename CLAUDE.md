@AGENTS.md

# Claude notes

## Default host

**Live: https://matthummel.com/**

Matt's standing instruction (22 Nov 2026): stop using staging, update live by default. Theme updates and new posts go to `matthummel.com`. Install the `theme-latest` zip there (Appearance → Update Theme, or `wp mh theme-update`) after the PR is merged and its checks pass, purge LiteSpeed, then check the changed pages. Before a theme install, check that a recent backup exists. Staging ("hummel-dev", matthummel-com-623249.hostingersite.com) stays in Hostinger but is not used unless Matt asks. Never edit theme or plugin files on the server, and never log in with a password you were not given; if a task names a host you cannot match to this one, stop and ask.

Rules live in `.claude/rules/` (always-on, or path-scoped with `paths:`) and skills in `.claude/skills/` (on request). That folder is the only source: edit the files directly. Rules for every project (review, WordPress, Sage, a11y, git) come from the `ai-dev-standards-playbook`, installed in `~/.claude/rules/playbook/` (edit that repo, not the copy). When an agent repeats a mistake, fix the rule here.

## Workflow

- `/pb:plan <idea>` — think it through before coding.
- `/pb:review [PR#]` — senior WordPress review. This repo's `wp-reviewer` agent overrides the playbook's and reads `.claude/rules/`.
- `/pb:fix <bug>` and `/pb:ship` — fix, or finish: checks, commit, push, PR. Checks this repo needs: `vendor/bin/pint`, `composer analyse`, `python3 .github/scripts/wp-review`, and `npm run build` when CSS/JS/Blade changed.
- Merging to `main` only publishes the `theme-latest` zip on GitHub. Install it on `matthummel.com` (Appearance → Update Theme), then purge LiteSpeed and check the changed pages.
- `wp-review` — WordPress Handbook checks on changed lines (CI: `python3 .github/scripts/wp-review`). Checklist: `.github/review-checklist.md`.
- Local WordPress: WordPress Studio (`studio wp …`, never plain `wp` against a Studio site). Clear Blade cache with `studio wp acorn view:clear` after template edits.
