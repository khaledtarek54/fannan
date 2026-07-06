---
name: deploy
description: How the Fannan backend reaches production (Hostinger) and how to avoid deploying by accident. Invoke before pushing, releasing, or when asked to deploy. Explains the two git remotes — origin (GitHub backup, no deploy) vs production (live auto-deploy).
---

# Deploying Fannan

Full guide: `docs/DEPLOYMENT.md`. The critical facts:

## Two remotes — know the difference

- **`git push origin main`** → GitHub backup only. **No deploy.** Safe.
- **`git push production main`** → **deploys live to `apps.fannan.ai`.** Only do this to intentionally release.

## What a production push does (post-receive hook → `deploy.sh`)

`git pull` → `composer install` *only if `composer.lock` changed* → `php artisan migrate --force` →
clear config/route/view cache → ensure storage symlink.

- **`migrate --force` runs automatically** — review pending migrations before pushing to production.
- `.env`, `vendor/`, `storage/`, and uploaded media are **gitignored**; a deploy never touches them.

## Guardrails

- Keep **Telescope disabled in production** (`TELESCOPE_ENABLED=false`) — it caused the prod slowdowns.
- The host is CloudLinux shared hosting with a tight concurrent-connection cap (~75) and per-minute throttles; avoid anything that holds many connections (synchronous mail, long-running requests, unbounded loops).
- Default to `git push origin main` after committing; push to `production` **only** when a live release is intended, and confirm first if you're an agent acting on someone's behalf.
