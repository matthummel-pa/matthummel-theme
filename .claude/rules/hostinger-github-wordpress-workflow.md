# Hostinger and WPVibe (this repo)

Local first, then SSH to the live site. Generic standards load from the playbook.

## This repository (do not ignore)

This is the **matthummel** Sage 11 theme. Map these workflow rules onto files and hosts that already ship. If they conflict with `agency-core.md` or what already ships, **this file wins on production host and domain**; shipping theme patterns still win on Sage, Blade, Vite, and “do not rip out what works.”

- **Local site is where design and development happen.** WordPress Studio, theme folder `matthummel`. Check the change there before a merge.
- **Live site:** `https://matthummel.com` on Hostinger. Merging to `main` deploys the theme over SSH (`.github/workflows/deploy.yml`). Do not install a zip on a Hostinger staging copy, and do not publish staging → live from hPanel. Theme folder is `wp-content/themes/matthummel/`. Not `hummelwp.com`.
- **Source of truth for custom code:** this GitHub repo. Do not treat live theme-file edits as canonical.
- **WordPress connection:** WPVibe (`vibe-ai` plugin + `https://mcp.wpvibe.ai/mcp`) for posts, pages, settings, plugins, and site inspection.
- **Hostinger connection:** Hostinger MCP (`hosting_`, `domains_`, `DNS_`, `billing_`) for hosting, DNS, PHP, databases, and account infrastructure — not `curl` against the Hostinger API, SSH, or `dig` as the first read.
- **This theme is already custom.** Do not add a child theme of `matthummel`. Do not edit WordPress core or third-party plugin files on the server.
- **Git branches:** `main` is the released theme; work on `feat/…`, `fix/…`, `chore/…`. Do not invent a git `staging` branch — staging is a Hostinger *site*, not a branch.
- **Deploy today:** `.github/workflows/deploy.yml` builds the theme and swaps it over SSH on the live site. The GitHub Release `theme-latest` is the rollback copy of that deploy. Do not add a second deploy workflow.
- **Copy vs code:** visitor copy is block content edited in the block editor (`content.md`). Keep content PRs/edits separate from theme/plugin/infrastructure PRs.

## Environment separation

Use local and the live site:

- **Local:** WordPress Studio (`studio wp …`; see `CLAUDE.md`), theme folder `matthummel`. Product themes (Acreline, Cobble & Candle, and the rest) get a **separate** local site. Never activate them on this install.
- **Production:** `matthummel.com`. A merge to `main` deploys the theme over SSH. `hummelwp.com` is a separate Hostinger install (do not treat it as the default). Do not invent a git `staging` branch.

Rules:

- Do not debug by experimenting directly on production when a local or staging option exists.
- Do not copy production secrets into local files.
- Use `.env.example` with placeholder values, and keep real `.env` files ignored by Git.
- Before any migration, deployment, plugin update, database change, or bulk content operation, create or verify a recent backup.

## Hostinger workflow

- Use Hostinger MCP tools for hosting, domain, DNS, SSL, backups, PHP, databases, deployment, and account infrastructure.
- Use the Hostinger connection primarily to inspect state and perform narrowly scoped infrastructure actions.
- Never change DNS, nameservers, domains, email records, PHP versions, databases, SSL, or production files unless the target and intended result are explicit.
- Before changing DNS, record the current relevant records and explain the expected effect.
- Before changing PHP or server configuration, check WordPress and plugin compatibility (this theme targets PHP 8.3+; Hostinger may report a newer patch).
- After infrastructure changes, verify the site URL, HTTPS, WordPress admin, homepage, and critical forms (`/contact/`).
- Do not delete domains, sites, databases, backups, or subscriptions without a separate explicit confirmation.
- Confirm destructive Hostinger actions (deploys, DNS overwrites, plugin/theme/PHP changes, purchases) and wait for a yes.
- If Hostinger reports an active service incident, do not apply speculative workarounds; verify whether the issue is platform-wide first.
- Access logs, PHP error logs, and Node.js env vars are not in the Hostinger API — those stay in hPanel.

## WPVibe-specific safety

- WPVibe here is the **`vibe-ai` plugin** plus the MCP at `https://mcp.wpvibe.ai/mcp`. It is not this theme and not a page-builder store.
- Inspect its files, settings, dependencies, and current version before changing it.
- Do not overwrite WPVibe plugin code or settings until a backup and rollback method are confirmed.
- Do not edit `vibe-ai` plugin files on the server. Use its documented hooks, settings, or a small custom plugin in this repo only if Matt asks.
- Theme work through WPVibe is draft-first. Preview, then `publish_draft_theme` only when Matt says publish, go live, or ship it.
- Application Passwords must stay enabled on `matthummel.com` or the MCP cannot reconnect. Do not turn them off in Hostinger Tools or Wordfence without a replacement auth path.
- Test WPVibe-driven content or permission changes with a non-administrator account when front-end behavior is affected.
- Verify mobile layout, forms, links, media, and logged-out behavior after changes.

## Deployment rules

- Deploy from a reviewed Git commit, not from untracked local files.
- Confirm the destination before deployment: local, or the live SSH deploy that runs on merge to `main`. Do not publish a Hostinger staging copy from hPanel.
- Never deploy secrets or development configuration.
- Prefer atomic or easily reversible deployments (built `theme-latest` zip over partial `public/` copies).
- Do not run database migrations automatically against production without approval and a backup.
- When code and database changes must ship together, document the order and rollback plan.
- After deployment, check HTTP status, HTTPS, homepage, changed functionality, login, forms, and (in hPanel) error logs.
- If a deployment partially fails, stop and report the exact state instead of repeatedly retrying destructive steps.
- Never `--delete` over rsync. Cached HTML may still reference old Vite hashes.

## Also
- If the target site or environment is unclear, stop and ask which one is intended.
- Do not modify WordPress core or third-party plugin files. Never delete users, pages, posts, media, plugins, themes, or settings in bulk without listing the exact items first.
- No raw SQL for destructive operations unless the exact query, affected rows, backup, and rollback plan are known. Prefer WPVibe abilities, REST, or WP-CLI.
- Generic Git, security, testing, and change-sequence standards load from the playbook (`core.md`, `wordpress/`); this file keeps only Hostinger, staging, WPVibe, and deploy safety.

## Communication format for deploys and infrastructure

Use this only for deploy, DNS, plugin, database, or hosting tasks. For code questions and small edits, answer first and keep it short.

For each such task, respond with:

- **Target:** environment and site;
- **Plan:** files, settings, or WordPress objects affected;
- **Risk:** possible downtime, data loss, or compatibility concerns;
- **Backup:** status and location;
- **Change:** exact implementation summary;
- **Tests:** commands and browser checks performed;
- **Diff:** files and important lines changed;
- **Deployment:** what is ready and what still requires approval;
- **Rollback:** the precise reversal path.

If any required detail is missing, do not guess. Inspect safely or ask one focused question.
