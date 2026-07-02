#!/usr/bin/env bash
#
# Simple deploy for the Fannan backend (apps.fannan.ai on Hostinger).
# Run this ON THE SERVER, from anywhere:
#
#     bash ~/domains/apps.fannan.ai/public_html/deploy.sh
#
# It pulls the latest code from origin/main, installs deps, migrates, and
# clears caches. Safe to run repeatedly.
#
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/domains/apps.fannan.ai/public_html}"
# Shell default php on this host is 8.2, but the app requires 8.4. Use the 8.4 CLI.
PHP="${PHP:-/opt/alt/php84/usr/bin/php}"

cd "$APP_DIR"
[ -f artisan ] || { echo "!! no artisan found in $APP_DIR — wrong directory?"; exit 1; }

echo "==> Deploy start"
echo "==> Before: $(git rev-parse --short HEAD 2>/dev/null || echo 'not a git repo yet')"

echo "==> Pulling origin/main"
git pull --ff-only origin main

echo "==> Composer install"
COMPOSER_BIN="$(command -v composer || true)"
if [ -n "$COMPOSER_BIN" ]; then
  # --ignore-platform-reqs: the 8.4 CLI is missing ext-sodium (the web SAPI has it), so a plain
  #   install would abort. NOTE: we do NOT pass --no-dev — this app currently boots with dev deps
  #   installed (nunomaduro/collision is an auto-discovered provider); a --no-dev install would need
  #   collision moved to extra.laravel.dont-discover first, else every request 500s. Keep as-is.
  COMPOSER_MEMORY_LIMIT=-1 "$PHP" "$COMPOSER_BIN" install --ignore-platform-reqs --optimize-autoloader --no-interaction --prefer-dist
else
  echo "   (composer not on PATH — skipping; run it manually if dependencies changed)"
fi

echo "==> Running migrations"
"$PHP" artisan migrate --force

echo "==> Clearing caches (config/cache/view)"
"$PHP" artisan config:clear
"$PHP" artisan cache:clear
"$PHP" artisan view:clear

echo "==> Ensuring storage symlink"
"$PHP" artisan storage:link 2>/dev/null || true

echo "==> After:  $(git rev-parse --short HEAD)"
echo "==> Deploy done ✅"
