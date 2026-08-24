#!/usr/bin/env bash
# db-pull.sh — Pull the SiteGround production database to a local WordPress install.
#
# Usage:
#   bash .github/scripts/db-pull.sh [LOCAL_WP_ROOT]
#
# Credentials (env vars — same names as deploy.yml GitHub secrets):
#   SSH_HOST      or SITEGROUND_HOST   or SERVER_IP
#   SSH_PORT      or SITEGROUND_PORT   or SERVER_SSH_PORT  (default: 22)
#   SSH_USER      or SITEGROUND_USER   or SERVER_USER
#   SSH_PATH      or SERVER_DESTINATION_PATH               (theme path or WP root)
#   REMOTE_URL                                             (default: https://matthummel.com)
#   LOCAL_URL                                              (default: http://localhost:8080)
#
# The local WordPress root is the first positional arg (default: ~/wp-site).
# SSH key must be loaded (ssh-agent or ~/.ssh/id_rsa).

set -euo pipefail

# ── Resolve credentials ────────────────────────────────────────────────────────
resolve() { echo "${1:-${2:-${3:-}}}"; }

HOST=$(resolve "${SSH_HOST:-}" "${SITEGROUND_HOST:-}" "${SERVER_IP:-}")
PORT=$(resolve "${SSH_PORT:-}" "${SITEGROUND_PORT:-}" "${SERVER_SSH_PORT:-22}")
USER=$(resolve "${SSH_USER:-}" "${SITEGROUND_USER:-}" "${SERVER_USER:-}")
REMOTE_PATH=$(resolve "${SSH_PATH:-}" "${SERVER_DESTINATION_PATH:-}" "")
REMOTE_URL="${REMOTE_URL:-https://matthummel.com}"
LOCAL_URL="${LOCAL_URL:-http://localhost:8080}"
LOCAL_WP="${1:-$HOME/wp-site}"

# ── Validate ──────────────────────────────────────────────────────────────────
if [[ -z "$HOST" || -z "$USER" ]]; then
  echo "❌  SSH_HOST and SSH_USER (or SITEGROUND_HOST / SERVER_IP + SITEGROUND_USER / SERVER_USER) are required."
  echo "    Export them before running, e.g.:"
  echo "      export SITEGROUND_HOST=sg-server.example.com"
  echo "      export SITEGROUND_USER=sguser"
  echo "      export SERVER_DESTINATION_PATH=/home/customer/www/example.com/public_html/wp-content/themes/matthummel"
  exit 1
fi

if [[ -z "$REMOTE_PATH" ]]; then
  echo "❌  SSH_PATH / SERVER_DESTINATION_PATH is required (WP root or theme path)."
  exit 1
fi

# Strip trailing theme folder so we land at the WP root.
WP_ROOT="${REMOTE_PATH%/wp-content/themes/matthummel}"
WP_ROOT="${WP_ROOT%/}"

STAMP=$(date '+%Y%m%d-%H%M%S')
REMOTE_TMP="/tmp/mh-db-pull-${STAMP}.sql"
LOCAL_TMP="/tmp/mh-db-pull-${STAMP}.sql"
SSH_CMD="ssh -p ${PORT} -o StrictHostKeyChecking=accept-new ${USER}@${HOST}"

# ── Step 1: Export remote DB ───────────────────────────────────────────────────
echo "→ Exporting production database on ${HOST}…"
$SSH_CMD "cd $(printf '%q' "$WP_ROOT") && wp db export --add-drop-table $(printf '%q' "$REMOTE_TMP") --quiet"
echo "  Remote export complete."

# ── Step 2: Download ──────────────────────────────────────────────────────────
echo "→ Downloading dump via scp…"
scp -P "${PORT}" -o StrictHostKeyChecking=accept-new \
  "${USER}@${HOST}:${REMOTE_TMP}" "${LOCAL_TMP}"
echo "  Saved to ${LOCAL_TMP}"

# ── Step 3: Clean up remote temp ──────────────────────────────────────────────
$SSH_CMD "rm -f $(printf '%q' "$REMOTE_TMP")" || true

# ── Step 4: Import locally ────────────────────────────────────────────────────
echo "→ Importing into local database at ${LOCAL_WP}…"
(cd "$LOCAL_WP" && wp db import "${LOCAL_TMP}" --quiet)
rm -f "${LOCAL_TMP}"

# ── Step 5: Search-replace URLs ───────────────────────────────────────────────
if [[ "${REMOTE_URL%/}" != "${LOCAL_URL%/}" ]]; then
  echo "→ Search-replacing ${REMOTE_URL} → ${LOCAL_URL}…"
  (cd "$LOCAL_WP" && wp search-replace "${REMOTE_URL}" "${LOCAL_URL}" \
    --skip-columns=guid --report-changed-only)
fi

echo ""
echo "✅  db-pull complete. Local database now mirrors production."
