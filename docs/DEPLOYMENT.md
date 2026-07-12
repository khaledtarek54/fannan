# Deployment Guide

The Fannan backend (`apps.fannan.ai`) runs on **Hostinger shared hosting** and deploys with
**plain git + a single script** — no CI, no containers. Deploying is one command.

---

## TL;DR — how to deploy

Two **upload-free** steps from your machine, after committing:

```bash
git push origin main                                        # 1. publish to GitHub (deploy source of truth)
ssh -p 65002 u715768425@217.21.90.20 \
    'bash ~/domains/apps.fannan.ai/public_html/deploy.sh'   # 2. server pulls from GitHub & rolls out
```

Step 2 streams the deploy log back to your terminal. That's it.

> **Why not just `git push production main`?** It still works and fires the same `deploy.sh`, but
> the push itself is a **laptop→server upload**, and this shared host's per-account connection
> throttling makes uploads (`git push` / `scp`) **hang under load**. The two-step flow above only
> ever runs **server→GitHub** pulls, which never hang. So GitHub is the deploy source of truth —
> **always `git push origin main` before deploying.**

---

## How it works (deploy = server pulls from GitHub)

```
your machine  --git push origin main-->  GitHub (khaledtarek54/fannan)
                                                |
   you run deploy.sh on the server (ssh, or via `git push production main` hook)
                                                |
                                          deploy.sh  (in public_html)
                     fetch GitHub → reset --hard → composer* → migrate → clear caches
```

- The server's **`~/domains/apps.fannan.ai/public_html`** is a git checkout; **`deploy.sh` fetches
  `main` straight from GitHub** (`https://github.com/khaledtarek54/fannan.git`) and `git reset
  --hard`s to it — divergence-proof, and no laptop upload required.
- **`git push production main` still works** as an optional trigger: it pushes to the bare repo
  **`~/repos/fannan.git`**, whose **`post-receive` hook** runs the same `deploy.sh` (which then pulls
  from GitHub). deploy.sh also keeps the bare repo in step so that push stays a fast-forward.
- `deploy.sh` (`public_html/deploy.sh`): `git fetch GitHub` + `reset --hard` → `composer install`
  *only if `composer.lock` changed* → `php artisan migrate --force` → clear config/route/cache/view →
  ensure storage symlink.
- **`.env`, `vendor/`, `storage/`, and uploaded media are gitignored**, so a deploy never touches
  them. Migrations run automatically; no new migrations = no-op.

---

## First-time setup for a new developer

1. **Get SSH access** to the server from the maintainer (IP, port, username, password).
   > ⚠️ Hostinger restricts SSH **by IP** — if your connection times out, ask the maintainer to
   > whitelist your IP in hPanel (Advanced → SSH Access). This is also why cloud CI (GitHub Actions,
   > etc.) **cannot** deploy — Hostinger blocks datacenter IPs. Deploy from your own machine.

2. **Clone the repo** (from GitHub `khaledtarek54/fannan`, private) or from the maintainer.

3. **Add the `production` remote** (this is the deploy target):
   ```bash
   git remote add production ssh://u715768425@217.21.90.20:65002/home/u715768425/repos/fannan.git
   ```

4. **(Optional, recommended) passwordless push** — add your public key to the server so you don't
   type the password each deploy:
   ```bash
   ssh-copy-id -p 65002 u715768425@217.21.90.20
   ```

You can now `git push production main` to deploy.

---

## Environment notes (important gotchas)

- **PHP:** the shell default is 8.2, but the app requires **8.4**. Always use
  `/opt/alt/php84/usr/bin/php` for any manual artisan/composer command. `deploy.sh` already does.
- **No `config:cache` / `route:cache`** — `routes/api.php` uses closures (route caching would error)
  and we keep `.env` read live.
- **Keep dev dependencies installed** — the app boots with `nunomaduro/collision` (an auto-discovered
  provider). Do **not** run `composer install --no-dev` unless you first add collision to
  `extra.laravel.dont-discover`, or every request 500s.
- `.env` on prod: `APP_DEBUG=false`, `TELESCOPE_ENABLED=false`, `MAIL_MAILER=log`.

---

## Manual deploy (if you're already SSH'd into the server)

```bash
bash ~/domains/apps.fannan.ai/public_html/deploy.sh
```

Safe to run repeatedly.

---

## Rollback

- **Code:** on the server, `cd public_html && git reset --hard <previous-commit>` then
  `/opt/alt/php84/usr/bin/php artisan config:clear && ... cache:clear && ... view:clear`.
  Find the commit with `git log --oneline`.
- **Full snapshot / DB:** backups live on the server in `~/backups/` (code+media tar, `.env`, DB dump).
  Restore a `.sql.gz` dump for the database.

---

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `ssh: connect ... Connection timed out` | Your IP isn't whitelisted for SSH (Hostinger), or you're on a cloud/CI IP. Deploy from a whitelisted machine. |
| `git push production main` hangs at `password:` / after auth, ref never updates | Host's connection throttling stalling the upload. Don't fight it — use the two-step flow: `git push origin main` then run `deploy.sh` on the server over SSH. |
| Ran `deploy.sh` but code didn't change | `deploy.sh` pulls from **GitHub** — you forgot `git push origin main` first (GitHub is the deploy source of truth). |
| 500 on every request after a deploy | Dev deps got removed (collision). Re-run `composer install` **without** `--no-dev` (see above). |
| `composer` aborts on `ext-sodium` | Use `--ignore-platform-reqs` (the 8.4 **CLI** lacks sodium; the web SAPI has it). `deploy.sh` handles this. |
| artisan "requires PHP >= 8.4" | You used the default `php` (8.2). Use `/opt/alt/php84/usr/bin/php`. |

---

## Why not GitHub Actions?

We set it up and it can't work here: GitHub's runners can't reach the server (Hostinger firewalls
their datacenter IPs — connection times out), so CI can't SSH in to trigger a deploy. But the
**server itself** can reach GitHub, which is exactly what `deploy.sh` relies on: it pulls `main`
from GitHub. GitHub is the deploy **source of truth**; the release step is just triggering
`deploy.sh` on the server (over SSH, or via the `git push production main` hook).
