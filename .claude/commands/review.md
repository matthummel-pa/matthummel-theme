---
description: Senior WordPress code review of the current branch (or a PR number)
argument-hint: "[PR number]"
---

Review $ARGUMENTS as a peer senior WordPress developer.

- If a PR number was given: `gh pr view $ARGUMENTS` and `gh pr diff $ARGUMENTS`.
- Otherwise review the current branch against `origin/main`.

Use the **wp-reviewer** subagent. Then summarize its findings for Matt in plain language,
and ask whether to fix the blockers now. If the review was of a PR and Matt says yes,
post it with `gh pr review` (comment, not approve, unless he says approve).
