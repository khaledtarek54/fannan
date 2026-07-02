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
LOCK_BEFORE="$(md5sum composer.lock 2>/dev/null | awk '{print $1}')"
git pull --ff-only origin main
LOCK_AFTER="$(md5sum composer.lock 2>/dev/null | awk '{print $1}')"

# Only run composer when dependencies actually changed (keeps code-only deploys fast).
# --ignore-platform-reqs: the 8.4 CLI is missing ext-sodium (the web SAPI has it), so a plain
#   install would abort. We do NOT pass --no-dev — this app boots with dev deps installed
#   (nunomaduro/collision is an auto-discovered provider); --no-dev would need collision moved to
#   extra.laravel.dont-discover first, else every request 500s.
COMPOSER_BIN="$(command -v composer || true)"
if [ -n "$COMPOSER_BIN" ] && { [ "$LOCK_BEFORE" != "$LOCK_AFTER" ] || [ ! -f vendor/autoload.php ]; }; then
  echo "==> composer.lock changed — installing dependencies"
  COMPOSER_MEMORY_LIMIT=-1 "$PHP" "$COMPOSER_BIN" install --ignore-platform-reqs --optimize-autoloader --no-interaction --prefer-dist
else
  echo "==> Dependencies unchanged — skipping composer install"
fi

echo "==> Running migrations"
"$PHP" artisan migrate --force

echo "==> Clearing caches (config/cache/view)"
"$PHP" artisan config:clear
"$PHP" artisan cache:clear
"$PHP" artisan view:clear

[ -L public/storage ] || "$PHP" artisan storage:link 2>/dev/null || true

echo "==> After:  $(git rev-parse --short HEAD)"
echo "==> Deploy done ✅"
