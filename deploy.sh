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

# Composer strategy: a full `install` is expensive, so we only run it when composer.lock changes.
# BUT the autoloader MUST be regenerated on every deploy — adding a file to composer.json
# `autoload.files` (e.g. app/helpers.php) or a new psr-4 path changes composer.json but NOT the
# lock, so an install-only-on-lock-change deploy shipped code that referenced a helper the compiled
# autoloader never registered → "Call to undefined function currency_code()" → every invoice
# download 500'd in prod (2026-07-12). So: lock changed → full install (which rebuilds the
# autoloader); otherwise → a cheap `dump-autoload` (~1s, no network) so autoload can never go stale.
# --ignore-platform-reqs: the 8.4 CLI is missing ext-sodium (the web SAPI has it), so composer would
#   otherwise abort. We do NOT pass --no-dev — this app boots with dev deps installed
#   (nunomaduro/collision is an auto-discovered provider); --no-dev would need collision moved to
#   extra.laravel.dont-discover first, else every request 500s.
COMPOSER_BIN="$(command -v composer || true)"
if [ -z "$COMPOSER_BIN" ]; then
  echo "!! composer not found — SKIPPING autoload regeneration (autoloader may be stale)"
elif [ "$LOCK_BEFORE" != "$LOCK_AFTER" ] || [ ! -f vendor/autoload.php ]; then
  echo "==> composer.lock changed — installing dependencies"
  COMPOSER_MEMORY_LIMIT=-1 "$PHP" "$COMPOSER_BIN" install --ignore-platform-reqs --optimize-autoloader --no-interaction --prefer-dist
else
  # Deps unchanged: regenerate ONLY the autoloader. --no-scripts skips composer's post-autoload-dump
  # hooks (artisan package:discover / filament:upgrade) — they boot the framework (and re-publish
  # Filament assets) and could fail on the ext-sodium-less 8.4 CLI; this branch just needs a fresh
  # autoload map, which composer writes BEFORE those hooks run. Non-fatal so a composer hiccup can
  # never strand prod between `reset --hard` and migrate/cache-clear — a stale autoloader is the
  # lesser evil, and cache:clear/route:clear below re-derive discovery anyway.
  echo "==> Dependencies unchanged — regenerating autoloader (composer dump-autoload, no scripts)"
  if ! COMPOSER_MEMORY_LIMIT=-1 "$PHP" "$COMPOSER_BIN" dump-autoload --optimize --ignore-platform-reqs --no-scripts --no-interaction; then
    echo "!! dump-autoload failed — continuing so the deploy still migrates & clears caches (autoloader may be stale)"
  fi
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
