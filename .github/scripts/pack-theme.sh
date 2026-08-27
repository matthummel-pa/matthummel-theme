#!/usr/bin/env bash
# Pack a built Sage theme (vendor + public/build, no node_modules) for WordPress install.
set -euo pipefail

root="$(cd "$(pwd)" && pwd)"
out="${1:-$root/matthummel.zip}"
if [[ "$out" != /* ]]; then
  out="$root/$out"
fi
stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT

mkdir -p "$stage/matthummel"
tar -C "$root" \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.cursor' \
  --exclude='node_modules' \
  --exclude='docs' \
  --exclude='mu-plugins' \
  --exclude='.env' \
  --exclude='.env.*' \
  --exclude='matthummel.zip' \
  -cf - . | tar -C "$stage/matthummel" -xf -

if [[ ! -f "$stage/matthummel/style.css" ]]; then
  echo "style.css missing from pack staging" >&2
  exit 1
fi
if [[ ! -f "$stage/matthummel/public/build/manifest.json" ]]; then
  echo "Vite manifest missing — run npm run build first" >&2
  exit 1
fi
if [[ ! -f "$stage/matthummel/vendor/autoload.php" ]]; then
  echo "Composer vendor missing — run composer install --no-dev first" >&2
  exit 1
fi

rm -f "$stage/matthummel/public/hot"
(cd "$stage" && zip -rq "$out" matthummel)
echo "Wrote $out ($(du -h "$out" | awk '{print $1}'))"
