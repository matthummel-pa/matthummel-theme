---
description: Think it through with Matt before coding — plan, risks, and options
argument-hint: "<what you want to build or fix>"
---

Matt wants: $ARGUMENTS

Act as his peer senior developer. Do not write code yet.

1. Read `AGENTS.md` and the relevant `.cursor/rules/*.mdc`, and look at the files involved.
2. Reply with:
   - **Goal** — one line, in your words.
   - **Approach** — the files you'd touch and what changes, in order. Prefer the repo's
     existing patterns; say which WordPress API or Sage feature you'd use and link its docs.
   - **Risks** — security, live-content, a11y, performance, deploy (Vite base, theme zip).
   - **Options** — if there are two sensible ways, compare them in two lines and recommend one.
   - **Size** — S/M/L and whether it should be one PR or split.
3. Ask for a go-ahead. When he says go, create a branch (`feat/…` or `fix/…`) and build it.
