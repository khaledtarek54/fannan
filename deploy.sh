#!/usr/bin/env bash
#
# Deploy for the Fannan backend (apps.fannan.ai on Hostinger).
# Run this ON THE SERVER, from anywhere:
#
#     bash ~/domains/apps.fannan.ai/public_html/deploy.sh
#
# It pulls the latest main STRAIGHT FROM GITHUB (not the local bare repo), installs
# deps if they changed, migrates, and clears caches. Safe to run repeatedly.
#
# Why pull from GitHub instead of pushing to the server? This is CloudLinux shared
# hosting whose per-account connection throttling makes laptop->server uploads
# (git push / scp) hang under load. A GitHub pull runs server->GitHub, which never
# hangs. So the reliable release flow is two steps, both upload-free:
#
#     1) git push origin main          # from a dev machine (fast, reliable)
#     2) bash ~/domains/apps.fannan.ai/public_html/deploy.sh   # on the server
#
# `git reset --hard` (not `pull --ff-only`) makes the rollout resilient even if the
# deployed line ever diverges from main. GitHub is the source of truth for deploys —
# always `git push origin main` BEFORE running this.
#
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/domains/apps.fannan.ai/public_html}"
BARE_DIR="${BARE_DIR:-$HOME/repos/fannan.git}"
# Shell default php on this host is 8.2, but the app requires 8.4. Use the 8.4 CLI.
PHP="${PHP:-/opt/alt/php84/usr/bin/php}"
GIT_REMOTE="${GIT_REMOTE:-https://github.com/khaledtarek54/fannan.git}"
BRANCH="${BRANCH:-main}"

cd "$APP_DIR"
[ -f artisan ] || { echo "!! no artisan found in $APP_DIR — wrong directory?"; exit 1; }

echo "==> Deploy start"
echo "==> Before: $(git rev-parse --short HEAD 2>/dev/null || echo 'not a git repo yet')"

echo "==> Fetching $BRANCH from GitHub ($GIT_REMOTE)"
LOCK_BEFORE="$(md5sum composer.lock 2>/dev/null | awk '{print $1}')"
git fetch "$GIT_REMOTE" "$BRANCH"
git reset --hard FETCH_HEAD
LOCK_AFTER="$(md5sum composer.lock 2>/dev/null | awk '{print $1}')"

# Keep the local bare repo (the `production` push remote) in step with GitHub, so a
# `git push production main` fallback stays a fast-forward. Never fatal if it can't.
git -C "$BARE_DIR" fetch "$GIT_REMOTE" "$BRANCH:$BRANCH" 2>/dev/null || true

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

echo "==> Clearing caches (config/route/cache/view)"
"$PHP" artisan config:clear
"$PHP" artisan route:clear
"$PHP" artisan cache:clear
"$PHP" artisan view:clear

[ -L public/storage ] || "$PHP" artisan storage:link 2>/dev/null || true

echo "==> After:  $(git rev-parse --short HEAD)"
echo "==> Deploy done ✅"
