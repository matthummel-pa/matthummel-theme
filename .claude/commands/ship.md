---
description: Finish the current change — checks, commit, push, and open a PR
---

Ship the current work as a pull request:

1. `git status` — make sure no secrets, `.env`, `vendor/`, `node_modules/`, or `public/build/` are staged.
2. If on `main`, create a branch named for the change (`feat/…`, `fix/…`, `chore/…`).
3. Run checks and fix what you can: `vendor/bin/pint`, `python3 .github/scripts/wp-review`,
   and `npm run build` if CSS/JS/Blade changed. Report anything left.
4. Update `CHANGELOG.md` (and `docs/FEATURES.md` if the repo has it) for user-visible changes.
5. Commit with a conventional message (`feat:`, `fix:`, `chore:`, `docs:`).
6. `git push -u origin HEAD` and `gh pr create --fill` using `.github/pull_request_template.md`;
   tick only the checklist items you actually verified.
7. Remind Matt: merging publishes `theme-latest`, but the live site changes only after
   Appearance → Update Theme (then purge the host cache).
