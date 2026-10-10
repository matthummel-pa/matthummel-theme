@AGENTS.md

# Claude notes

Cursor and Claude share one set of rules. `.cursor/rules/*.mdc` is the source of truth; `sync-rules` mirrors it into `.claude/rules/` (always-on and path-scoped, loaded automatically) and `.claude/skills/` (on request). Rules for every project (review, WordPress, Sage, a11y, git) live in `~/.claude/rules/` from wp-dev-kit. When an agent repeats a mistake, edit the `.mdc`, then run `sync-rules`.

## Workflow

- `/plan <idea>` — think it through before coding.
- `/review [PR#]` — senior WordPress review (uses the `wp-reviewer` subagent).
- `/ship` — checks, commit, push, PR.
- `sync-rules` after editing `.cursor/rules/*.mdc`; `sync-rules --check` fails when `.claude/` is stale.
- `wp-review` — WordPress Handbook checks on changed lines (CI: `python3 .github/scripts/wp-review`). Checklist: `.github/review-checklist.md`.
- Local WordPress: WordPress Studio (`studio wp …`, never plain `wp` against a Studio site). Clear Blade cache with `studio wp acorn view:clear` after template edits.
