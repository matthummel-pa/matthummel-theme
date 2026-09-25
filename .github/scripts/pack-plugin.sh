#!/usr/bin/env bash
# Pack the self-hosted newsletter plugin. This zip is not the theme release.
set -euo pipefail

root="$(cd "$(dirname "$0")/../.." && pwd)"
src="$root/plugins/matthummel-newsletter"
out="${1:-$root/matthummel-newsletter.zip}"
if [[ "$out" != /* ]]; then
  out="$root/$out"
fi

if [[ ! -f "$src/matthummel-newsletter.php" ]]; then
  echo "Plugin bootstrap missing" >&2
  exit 1
fi

stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT
mkdir -p "$stage/matthummel-newsletter"
tar -C "$src" \
  --exclude='tests' \
  --exclude='.DS_Store' \
  -cf - . | tar -C "$stage/matthummel-newsletter" -xf -

(cd "$stage" && zip -rq "$out" matthummel-newsletter)
echo "Wrote $out ($(du -h "$out" | awk '{print $1}'))"
