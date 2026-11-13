#!/usr/bin/env bash
# Fails if the homepage, REST API or the theme stylesheet are not healthy.
set -euo pipefail
url="${1:?site url required}"; slug="${2:?theme slug required}"
ua="deploy-health-check"
sleep 5

code() { curl -s -o /dev/null -A "$ua" -w '%{http_code}' --max-time 20 "$1?nocache=$(date +%s)"; }

home=$(code "$url/");            echo "Home: $home";  [ "$home" = "200" ]
rest=$(code "$url/wp-json/");    echo "REST: $rest";  [ "$rest" = "200" ]

html="$(curl -s -A "$ua" --max-time 20 "$url/?nocache=$(date +%s)")"
grep -qi 'critical error' <<<"$html" && { echo "WordPress critical error on homepage" >&2; exit 1; }
css="$(grep -oE "/wp-content/themes/$slug/public/build/assets/app-[A-Za-z0-9_-]+\\.css" <<<"$html" | head -1)"
[ -n "$css" ] || { echo "Theme stylesheet not found in homepage HTML" >&2; exit 1; }
cs=$(code "$url$css"); echo "CSS:  $cs"; [ "$cs" = "200" ]
echo "Healthy."
