---
name: wp-db-migrate
description: Sync WordPress database content (posts, pages, Page content (theme) field values, comments) between the local Cursor Cloud dev site and the live SiteGround site at matthummel.com. Use when Matt asks to sync, mirror, migrate, pull, or push content between local and live WordPress — this is separate from deploying theme code, which .github/workflows/deploy.yml already handles.
---

# WordPress DB migration (local ↔ SiteGround)

Theme **code** already syncs via `git push` to `main` (`.github/workflows/deploy.yml`).
This skill covers the other half: the **database** — posts, pages, `Page content
(theme)` field values (`app/page-fields.php`), comments, menus, and theme mods.
Local (`~/wp-site`) and live (SiteGround) are separate MySQL databases with no
automatic sync; this skill's script (`scripts/wp-db-migrate.sh`) does it safely
on demand, over SSH, using WP-CLI on both ends.

Prerequisite: local WordPress must be on real MySQL, not the old SQLite
drop-in. See `AGENTS.md` → "Running the site" for the current setup. Content
migration cannot work against SQLite because `wp db export`/`import` need a
real SQL dump both sides can exchange.

## Two directions, very different risk levels

| Direction | Command | Risk | Default behavior |
| --- | --- | --- | --- |
| Live → local (`pull`) | `wp-db-migrate.sh pull` | Low — only overwrites the disposable local dev DB | Runs immediately, no confirmation flag needed |
| Local → live (`push`) | `wp-db-migrate.sh push --confirm` | High — overwrites production content | Refuses to run without `--confirm`; excludes `wp_users`/`wp_usermeta` unless `--include-users` is also passed |

**Always prefer `pull`.** The common workflow is: pull live content down to
test against, make theme/template changes locally, then ship those as
**code** through the normal git deploy — not by pushing the local database
back up. Only use `push` when Matt explicitly asks to move specific database
content (not code) from local up to production, and confirm with him first
which tables/content he means before running it.

## Before running either command

1. Confirm local WordPress is on MySQL (`wp db check` inside `~/wp-site` should
   list real InnoDB tables, not error about a missing driver).
2. Confirm the required environment variables are set (same secret names the
   deploy workflow already uses — see `scripts/wp-db-migrate.sh` header for the
   full list): `SERVER_SSH_PRIVATE_KEY` (or `SITEGROUND_SSH_PRIVATE_KEY` /
   `STR_SSH_PRIVATE_KEY`), `SERVER_USER`, `SERVER_IP`, `SERVER_SSH_PORT`
   (defaults to 22), and `LIVE_WP_PATH` — the **WordPress root** on the server
   (contains `wp-config.php`), which is a different path than the theme
   folder used for FTP deploys.
3. If any of those are missing, stop and tell Matt which secret to add in the
   Cursor Dashboard (Cloud Agents → Secrets) or export locally before running
   the script manually — do not invent or guess credentials.
4. Run with `--dry-run` first when unsure. For `pull`, this imports into local
   and previews the URL search-replace without applying it. For `push`, this
   takes the safety backups and uploads the export, but skips the remote
   import — nothing on live changes.

## Running it

```bash
# Preview what a pull would change (no local edits committed)
bash .cursor/skills/wp-db-migrate/scripts/wp-db-migrate.sh pull --dry-run

# Actually pull live content down to local (safe, default direction)
bash .cursor/skills/wp-db-migrate/scripts/wp-db-migrate.sh pull

# Push local DB content up to live — explicit opt-in, user tables excluded
bash .cursor/skills/wp-db-migrate/scripts/wp-db-migrate.sh push --confirm

# Push including the real WordPress users table (rarely what you want)
bash .cursor/skills/wp-db-migrate/scripts/wp-db-migrate.sh push --confirm --include-users
```

The script always takes a timestamped `wp db export` backup of whichever side
it is about to overwrite, before touching it, saved under
`.deploy-keys/db-backups/` (gitignored — never commit these; they may contain
real content, hashed passwords, or session data).

After a `pull`, the script resets the local `admin` user's password back to
the documented dev default (`password` — see `AGENTS.md`) so Matt can still
log in locally with the credentials the local instance is documented to use,
even though the imported users table came from production.

## Verifying the result

After a `pull`:

```bash
cd ~/wp-site && wp post list --post_type=page --fields=ID,post_title,post_status
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/
```

After a `push`, check the live site directly (`https://matthummel.com`) and
confirm the specific content Matt asked to move actually shows up there.

## Failure modes to watch for

- **`wp: command not found` over SSH**: some SiteGround plans don't expose
  WP-CLI by default. If `remote_wp` calls fail, tell Matt to enable WP-CLI in
  Site Tools → Devs, or fall back to a manual export/import via Site Tools'
  phpMyAdmin — do not attempt to open a direct MySQL TCP connection to
  SiteGround; shared hosting does not allow remote MySQL access.
- **`search-replace` mismatches**: if the live site ever moves off
  `https://matthummel.com` or local moves off `http://localhost:8080`, set
  `LIVE_URL`/`LOCAL_URL` env vars before running — do not hardcode a different
  URL inline.
- **Serialized data corruption**: always use `wp search-replace`, never a raw
  SQL `UPDATE`/`sed` on the dump — WordPress stores serialized PHP arrays in
  several core tables, and a naive string replace on those breaks them.
