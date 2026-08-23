#!/usr/bin/env bash
#
# Migrate WordPress database content between the local Cursor Cloud dev site
# and the live SiteGround site (matthummel.com).
#
# Both sides must speak WP-CLI over the connection actually available:
#   - local:  direct (this VM)
#   - live:   SSH (SiteGround shared hosting has no public MySQL port; WP-CLI
#             over SSH is the supported path)
#
# Usage:
#   wp-db-migrate.sh pull                 # live -> local (safe default)
#   wp-db-migrate.sh push --confirm       # local -> live (dangerous, opt-in)
#   wp-db-migrate.sh push --confirm --include-users   # also overwrite live users
#
# Required environment variables (same names as .github/workflows/deploy.yml):
#   SERVER_SSH_PRIVATE_KEY   (or SITEGROUND_SSH_PRIVATE_KEY / STR_SSH_PRIVATE_KEY)
#   SERVER_USER              (or SITEGROUND_USER)
#   SERVER_IP                (or SITEGROUND_HOST)
#   SERVER_SSH_PORT          (or SITEGROUND_PORT)              default: 22
#   LIVE_WP_PATH             absolute path to WP root on the server
#                            (contains wp-config.php — NOT the theme folder)
#
# Optional:
#   LOCAL_WP_PATH            default: $HOME/wp-site
#   LIVE_URL                 default: https://matthummel.com
#   LOCAL_URL                default: http://localhost:8080
#   BACKUP_DIR               default: <repo>/.deploy-keys/db-backups (gitignored)
#
# The script always backs up whichever database it is about to overwrite,
# before touching it. Backups are timestamped SQL dumps kept in BACKUP_DIR.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"

LOCAL_WP_PATH="${LOCAL_WP_PATH:-$HOME/wp-site}"
LIVE_URL="${LIVE_URL:-https://matthummel.com}"
LOCAL_URL="${LOCAL_URL:-http://localhost:8080}"
BACKUP_DIR="${BACKUP_DIR:-$REPO_ROOT/.deploy-keys/db-backups}"

SSH_KEY_MATERIAL="${SERVER_SSH_PRIVATE_KEY:-${SITEGROUND_SSH_PRIVATE_KEY:-${STR_SSH_PRIVATE_KEY:-}}}"
SSH_USER="${SERVER_USER:-${SITEGROUND_USER:-}}"
SSH_HOST="${SERVER_IP:-${SITEGROUND_HOST:-}}"
SSH_PORT="${SERVER_SSH_PORT:-${SITEGROUND_PORT:-22}}"
REMOTE_WP_PATH="${LIVE_WP_PATH:-}"

TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$BACKUP_DIR"

log() { echo "[wp-db-migrate] $*" >&2; }
die() { echo "[wp-db-migrate] ERROR: $*" >&2; exit 1; }

usage() {
  grep -E '^#( |$)' "$0" | sed -E 's/^# ?//' | sed -n '2,30p'
  exit 1
}

[[ $# -ge 1 ]] || usage
DIRECTION="$1"; shift || true
CONFIRM=false
INCLUDE_USERS=false
DRY_RUN=false

for arg in "$@"; do
  case "$arg" in
    --confirm) CONFIRM=true ;;
    --include-users) INCLUDE_USERS=true ;;
    --dry-run) DRY_RUN=true ;;
    *) die "Unknown flag: $arg" ;;
  esac
done

case "$DIRECTION" in
  pull|push) ;;
  *) usage ;;
esac

command -v wp >/dev/null 2>&1 || die "wp-cli not found locally"
[[ -d "$LOCAL_WP_PATH" ]] || die "LOCAL_WP_PATH ($LOCAL_WP_PATH) does not exist"

setup_ssh() {
  [[ -n "$SSH_KEY_MATERIAL" ]] || die "No SSH key set (SERVER_SSH_PRIVATE_KEY / SITEGROUND_SSH_PRIVATE_KEY / STR_SSH_PRIVATE_KEY)"
  [[ -n "$SSH_USER" ]] || die "No SSH user set (SERVER_USER / SITEGROUND_USER)"
  [[ -n "$SSH_HOST" ]] || die "No SSH host set (SERVER_IP / SITEGROUND_HOST)"
  [[ -n "$REMOTE_WP_PATH" ]] || die "LIVE_WP_PATH is required (absolute WP root on the server, containing wp-config.php)"

  SSH_KEY_FILE="$(mktemp -d)/deploy_key"
  printf '%s\n' "$SSH_KEY_MATERIAL" > "$SSH_KEY_FILE"
  chmod 600 "$SSH_KEY_FILE"
  trap 'rm -f "$SSH_KEY_FILE"; rmdir "$(dirname "$SSH_KEY_FILE")" 2>/dev/null || true' EXIT

  SSH_CMD=(ssh -i "$SSH_KEY_FILE" -p "$SSH_PORT" -o StrictHostKeyChecking=yes "${SSH_USER}@${SSH_HOST}")
  SCP_TO_REMOTE=(scp -i "$SSH_KEY_FILE" -P "$SSH_PORT" -o StrictHostKeyChecking=yes)
}

remote_wp() {
  "${SSH_CMD[@]}" "wp --path='$REMOTE_WP_PATH' $*"
}

user_table_excludes() {
  # WordPress core user tables; excluded by default when pushing local -> live
  # so a throwaway local admin account never overwrites real production logins.
  echo "wp_users,wp_usermeta"
}

backup_local() {
  local file="$BACKUP_DIR/local-before-${DIRECTION}-${TIMESTAMP}.sql"
  log "Backing up local DB -> $file"
  (cd "$LOCAL_WP_PATH" && wp db export "$file")
}

backup_remote() {
  local remote_file="/tmp/live-backup-${TIMESTAMP}.sql"
  local local_file="$BACKUP_DIR/live-before-${DIRECTION}-${TIMESTAMP}.sql"
  log "Backing up live DB on server -> $remote_file"
  remote_wp "db export '$remote_file'"
  log "Downloading live backup -> $local_file"
  scp -i "$SSH_KEY_FILE" -P "$SSH_PORT" -o StrictHostKeyChecking=yes \
    "${SSH_USER}@${SSH_HOST}:${remote_file}" "$local_file"
  "${SSH_CMD[@]}" "rm -f '$remote_file'"
}

do_pull() {
  # live -> local. Safe default direction: only ever overwrites the local dev DB.
  setup_ssh
  backup_local
  local remote_file="/tmp/live-export-${TIMESTAMP}.sql"
  local local_file="$BACKUP_DIR/live-export-${TIMESTAMP}.sql"

  log "Exporting live DB on server -> $remote_file"
  remote_wp "db export '$remote_file'"
  log "Downloading -> $local_file"
  scp -i "$SSH_KEY_FILE" -P "$SSH_PORT" -o StrictHostKeyChecking=yes \
    "${SSH_USER}@${SSH_HOST}:${remote_file}" "$local_file"
  "${SSH_CMD[@]}" "rm -f '$remote_file'"

  log "Importing into local DB"
  (cd "$LOCAL_WP_PATH" && wp db import "$local_file")

  log "Rewriting URLs: $LIVE_URL -> $LOCAL_URL"
  local dry_flag=()
  $DRY_RUN && dry_flag=(--dry-run)
  (cd "$LOCAL_WP_PATH" && wp search-replace "$LIVE_URL" "$LOCAL_URL" \
    --all-tables --skip-columns=guid --report-changed-only "${dry_flag[@]}")

  if $DRY_RUN; then
    log "Dry run only — local DB left unchanged after import (re-run without --dry-run to apply search-replace)."
    return
  fi

  log "Resetting local admin password to the documented dev default"
  (cd "$LOCAL_WP_PATH" && wp user update admin --user_pass=password 2>&1) || \
    log "No 'admin' user found after import — check AGENTS.md for the current local admin login."

  (cd "$LOCAL_WP_PATH" && wp acorn view:clear) || true
  (cd "$LOCAL_WP_PATH" && wp cache flush) || true
  log "Pull complete. Local dev now has a copy of live content."
}

do_push() {
  # local -> live. Dangerous: mutates production. Requires --confirm.
  $CONFIRM || die "Refusing to push to production without --confirm. Read the skill's SKILL.md first."
  setup_ssh
  backup_remote
  local local_file="$BACKUP_DIR/local-export-${TIMESTAMP}.sql"
  local remote_file="/tmp/local-import-${TIMESTAMP}.sql"

  log "Exporting local DB -> $local_file"
  local export_flags=()
  if ! $INCLUDE_USERS; then
    export_flags=(--exclude_tables="$(user_table_excludes)")
    log "Excluding user tables from export ($(user_table_excludes)) — pass --include-users to override."
  fi
  (cd "$LOCAL_WP_PATH" && wp db export "$local_file" "${export_flags[@]}")

  log "Uploading -> $remote_file"
  scp -i "$SSH_KEY_FILE" -P "$SSH_PORT" -o StrictHostKeyChecking=yes \
    "$local_file" "${SSH_USER}@${SSH_HOST}:${remote_file}"

  if $DRY_RUN; then
    log "Dry run only — uploaded backup taken, but skipping remote import/search-replace."
    "${SSH_CMD[@]}" "rm -f '$remote_file'"
    return
  fi

  log "Importing into live DB"
  remote_wp "db import '$remote_file'"
  "${SSH_CMD[@]}" "rm -f '$remote_file'"

  log "Rewriting URLs: $LOCAL_URL -> $LIVE_URL"
  remote_wp "search-replace '$LOCAL_URL' '$LIVE_URL' --all-tables --skip-columns=guid --report-changed-only"

  remote_wp "cache flush" || true
  log "Push complete. A pre-push live backup is at $BACKUP_DIR/live-before-push-${TIMESTAMP}.sql"
}

case "$DIRECTION" in
  pull) do_pull ;;
  push) do_push ;;
esac
