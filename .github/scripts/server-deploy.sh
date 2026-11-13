#!/usr/bin/env bash
# Runs ON the server (SSH). Usage: server-deploy.sh <wp_path> <sha> deploy|rollback <theme_slug>
# deploy:   unzip ~/theme.zip next to the live theme, keep old Vite assets,
#           swap folders (two renames), clear Acorn + LiteSpeed caches.
# rollback: swap the previous theme folder back in.
set -euo pipefail

wp_path="$1"; sha="$2"; mode="${3:-deploy}"; slug="${4:?theme slug required}"
themes="$wp_path/wp-content/themes"
live="$themes/$slug"
prev="$themes/.$slug-prev"
next="$themes/.$slug-next"

clear_caches() {
  cd "$wp_path"
  wp acorn optimize:clear >/dev/null 2>&1 || wp acorn view:clear || true
  wp litespeed-purge all >/dev/null 2>&1 || wp cache flush || true
}

if [ "$mode" = "rollback" ]; then
  [ -d "$prev" ] || { echo "No previous theme to roll back to." >&2; exit 1; }
  rm -rf "$next"
  mv "$live" "$next" && mv "$prev" "$live" && mv "$next" "$prev"
  clear_caches
  echo "Rolled back. Live: $(cat "$live/.deployed-sha" 2>/dev/null || echo unknown)"
  exit 0
fi

stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT
unzip -q ~/theme.zip -d "$stage"
[ -f "$stage/$slug/style.css" ] || { echo "Zip has no $slug/style.css" >&2; exit 1; }

rm -rf "$next"
mv "$stage/$slug" "$next"
echo "$sha" > "$next/.deployed-sha"

# Keep last build's hashed assets so cached HTML never loses its CSS/JS.
if [ -d "$live/public/build/assets" ]; then
  cp -n "$live/public/build/assets/"* "$next/public/build/assets/" 2>/dev/null || true
  find "$next/public/build/assets" -type f -mtime +14 -delete 2>/dev/null || true
fi

# Swap: live → prev, next → live. The window between renames is milliseconds.
rm -rf "$prev"
mv "$live" "$prev"
mv "$next" "$live"

clear_caches
rm -f ~/theme.zip
echo "Deployed $sha"
