#!/usr/bin/env bash
# Claude Code PostToolUse hook: run PHPCS on the PHP file that was just edited.
# Exit 2 + stderr sends the problems back to Claude so it can fix them.
# -n = errors only; warnings (e.g. DirectDatabaseQuery) are left to wp-review and CI.
set -uo pipefail
export PATH="/opt/homebrew/bin:$HOME/.composer/vendor/bin:$HOME/.config/composer/vendor/bin:$PATH"

input="$(cat)"
file="$(printf '%s' "$input" | jq -r '.tool_input.file_path // empty')"
[ -z "$file" ] && exit 0

case "$file" in
  *.blade.php) exit 0 ;;          # Blade isn't parseable by PHPCS
  *.php) ;;
  *) exit 0 ;;
esac
case "$file" in */vendor/*|*/node_modules/*) exit 0 ;; esac
[ -f "$file" ] || exit 0

command -v phpcs >/dev/null 2>&1 || { echo "phpcs not found on PATH; skipping lint" >&2; exit 0; }

cd "${CLAUDE_PROJECT_DIR:-$(pwd)}" || exit 0
# Uses ./phpcs.xml.dist automatically when present.
if ! out="$(phpcs -q -n --no-colors --report=full "$file" 2>&1)"; then
  printf 'PHPCS found problems in %s. Fix them (or justify any phpcs:ignore):\n%s\n' "$file" "$out" >&2
  exit 2
fi
exit 0
