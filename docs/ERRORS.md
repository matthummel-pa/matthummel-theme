# Error reference

A quick-lookup cheatsheet for the errors and warnings you're most likely to hit when developing, deploying, or running this theme locally.

---

## WordPress debug log

### Enable

Add to `wp-config.php` (before `require_once ABSPATH . 'wp-settings.php'`):

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);   // logs to wp-content/debug.log
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

### Read

```bash
# Cursor Cloud / local
tail -f ~/wp-site/wp-content/debug.log

# WP-CLI
wp eval 'echo file_get_contents(WP_CONTENT_DIR . "/debug.log");' | tail -50

# SiteGround (SSH)
tail -f ~/public_html/wp-content/debug.log
```

---

## Vite / asset errors

| Symptom | Cause | Fix |
| --- | --- | --- |
| `RuntimeException: Vite manifest not found` | `public/build/manifest.json` is missing | Run `npm run build`; check GitHub Actions for a failed build step |
| CSS/JS returning 404 in production | SiteGround HTML cache serving an old Vite hash | Push a new deploy; `preserve-vite-assets.py` keeps old hash files so cached pages keep working |
| `npm run build` fails with `Cannot find module` | `node_modules` missing or stale | Run `npm ci` then `npm run build` |
| Styles load in dev but not production | `npm run dev` manifest used in production | Stop the dev server; run `npm run build` and deploy |

---

## Blade / Acorn view errors

| Symptom | Cause | Fix |
| --- | --- | --- |
| Template change not visible | Stale compiled view cache | `cd ~/wp-site && wp acorn view:clear` |
| `Class "App\..." not found` | Composer autoload out of date | `composer dump-autoload` |
| `View [partials.xxx] not found` | Partial missing or typo in file name | Check `resources/views/partials/` — file names must use dashes, not underscores |
| `502 Bad Gateway` on first page load after deploy | Acorn config cache stale | `wp acorn config:clear && wp acorn view:clear` |

---

## GitHub API errors

`App\Github` fails soft — the site renders empty sections rather than a white screen when the API is unavailable.

| Symptom | Cause | Fix |
| --- | --- | --- |
| GitHub sections blank or missing | API rate-limited (unauthenticated: 60 req/h) | Set `MH_GITHUB_TOKEN` in `wp-config.php` or Appearance → Customize → GitHub |
| GitHub sections blank after token change | Old transient still cached | `wp transient delete mh_github_profile` then reload |
| Stale repo data | Transient still valid | `wp transient delete mh_github_repos && wp transient delete mh_github_activity` |
| `401 Unauthorized` in debug log | Token expired or wrong scope | Re-generate PAT with `read:user` and `public_repo` scopes |

Clear all GitHub transients at once:

```bash
wp eval 'foreach(["mh_github_profile","mh_github_repos","mh_github_events","mh_github_activity","mh_github_contribs","mh_devto_feed"] as $k) wp_cache_delete($k) || delete_transient($k); echo "done\n";'
```

---

## Contact form errors

| Symptom | Cause | Fix |
| --- | --- | --- |
| Form submits but no email arrives | No mail server (local or Cursor Cloud) | Expected; add an SMTP plugin on the live site |
| `403 Forbidden` on submit | Nonce expired (>12 h old page) | User refreshes the page — nonce is regenerated |
| Redirect loop on submit | `wp_safe_redirect` destination blocked | Check `allowed_redirect_hosts` filter in `app/contact.php` |
| Honeypot field filled (spam) | Bot filled `mh_website` hidden field | Form silently discards and redirects — this is correct behavior |

---

## Theme updater errors

| Symptom | Cause | Fix |
| --- | --- | --- |
| `wp mh theme-update` fails with `401` | `MH_GITHUB_TOKEN` missing or wrong | Set a fine-grained PAT (Contents: Read on `matthummel-theme`) in Appearance → Update Theme or `wp-config.php` |
| `wp mh theme-update` fails with `403` | PAT lacks permission to `matthummel-theme` | Re-issue PAT with Contents: Read on the `matthummel-pa/matthummel-theme` repo |
| Update downloads but theme breaks | Deploy zip is corrupt or incomplete | Check the `theme-latest` release on GitHub; re-run the workflow from Actions if the zip is malformed |
| `wp mh theme-build` returns `403` | `MH_GITHUB_TOKEN` needs Actions: Write | Not strictly needed — use `theme-update` to install an already-built release |

---

## DB migration errors (`wp mh db-pull` / `wp mh db-push`)

| Symptom | Cause | Fix |
| --- | --- | --- |
| `SSH connection refused` | Wrong host / port, or SiteGround firewall | Verify `MH_SSH_HOST`, `MH_SSH_PORT` in `wp-config.php`; SiteGround SSH port is usually non-22 |
| `Permission denied (publickey)` | SSH key not authorized, wrong key, or **passphrase-protected key without passphrase** | Site Tools → Devs → SSH Manager → add public key. For Cloud Agents: use an unencrypted deploy key as `SERVER_SSH_PRIVATE_KEY`, or set `SERVER_SSH_PRIVATE_KEY_PASSPHRASE`. Pass `--ssh-identity=/path/to/key` if the key is not a default `~/.ssh/id_*` name. |
| `command not found: wp` on remote | WP-CLI not on `$PATH` on SiteGround | Use the full path `~/bin/wp` or set `MH_SSH_WP_PATH` |
| `db-push` refused without `--yes` | Safety guard | Run `wp mh db-push --yes` to confirm overwriting production |
| Search-replace misses URLs | Non-standard domain or http/https mismatch | Pass `--remote-url` / `--local-url` explicitly |
| `incorrect passphrase` / ssh-add fails | Wrong or missing passphrase secret | Update `SERVER_SSH_PRIVATE_KEY_PASSPHRASE`, or replace the key with an unencrypted one |

---

## CI / GitHub Actions errors

| Symptom | Cause | Fix |
| --- | --- | --- |
| Build fails: `PHP Fatal error` | PHP version mismatch | Ensure Actions runner uses PHP 8.3; check `setup-php` step in `deploy.yml` |
| FTP upload times out | SiteGround FTP down or wrong credentials | FTP is best-effort (`continue-on-error: true`); the zip release still publishes |
| `SITEGROUND_FTP_HOST — MISSING` in secrets check | Secret not set in repo | Settings → Secrets → Actions; add `SITEGROUND_FTP_HOST` |
| Workflow cancelled before finishing | Concurrent deploy triggered cancellation | This is by design (`concurrency: siteground-deploy`); the latest push wins |
| Release not updated after merge to `main` | Workflow was skipped or failed | Check Actions tab; re-run from GitHub UI if needed |

---

## SiteGround-specific

| Symptom | Cause | Fix |
| --- | --- | --- |
| Sage fatal on live site | Live PHP still 8.2 | Site Tools → Devs → PHP Manager → set to 8.3 or 8.4 |
| Old styles served after deploy | SuperCacher serving stale HTML | Site Tools → Speed → Caching → Flush cache; or wait for `no-cache` headers to expire |
| `Vite manifest not found` on live site | `public/build/` not in the deploy zip | Check that `npm run build` ran in CI; `public/build/` must ship in the zip |

---

## SQLite (Cursor Cloud local only)

| Symptom | Cause | Fix |
| --- | --- | --- |
| `WordPress could not establish a database connection` | `wp-content/db.php` missing | Copy `sqlite-database-integration` plugin's `db.copy` to `wp-content/db.php` (replace placeholders) before `wp core install` |
| WP-CLI errors with `mysqli` extension missing | Bootstrapped before `db.php` in place | Put `db.php` first, then run `wp core install` |
| `wp server` crashes after a while | SQLite file locked by another process | Kill the other `php` process by PID, then restart |

---

## Quick debug checklist

1. `cd ~/wp-site && wp acorn view:clear` — stale Blade cache
2. `wp transient delete mh_github_profile` — stale GitHub data
3. `npm run build` — missing or outdated `public/build/`
4. `composer dump-autoload` — missing PHP class
5. `tail -f ~/wp-site/wp-content/debug.log` — PHP errors
6. Check GitHub Actions tab for a failed deploy step
