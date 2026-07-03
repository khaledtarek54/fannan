# Deployment Guide

The Fannan backend (`apps.fannan.ai`) runs on **Hostinger shared hosting** and deploys with
**plain git + a single script** — no CI, no containers. Deploying is one command.

---

## TL;DR — how to deploy

From your machine, after committing:

```bash
git push production main
```

That's it. The server auto-deploys itself and streams the deploy log back to your terminal.
(You'll be asked for the SSH password unless you've added your SSH key — see setup below.)

Also mirror to the private GitHub backup:

```bash
git push origin main
```

---

## How it works (auto-deploy)

```
your machine  --git push-->  server bare repo (~/repos/fannan.git)
                                     |
                              post-receive hook
                                     |
                                 deploy.sh   (in public_html)
                          pull → composer* → migrate → clear caches
```

- The server's **`~/domains/apps.fannan.ai/public_html`** is a git checkout of the bare repo
  **`~/repos/fannan.git`**.
- The `production` remote points at that bare repo. Pushing to it fires a **`post-receive` hook**
  (`~/repos/fannan.git/hooks/post-receive`) that runs **`deploy.sh`**.
- `deploy.sh` (`public_html/deploy.sh`): `git pull` → `composer install` *only if `composer.lock`
  changed* → `php artisan migrate --force` → clear config/cache/view → ensure storage symlink.
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
| Push succeeds but code didn't change | You pushed to `origin` (GitHub) not `production`. Deploy target is `production`. |
| 500 on every request after a deploy | Dev deps got removed (collision). Re-run `composer install` **without** `--no-dev` (see above). |
| `composer` aborts on `ext-sodium` | Use `--ignore-platform-reqs` (the 8.4 **CLI** lacks sodium; the web SAPI has it). `deploy.sh` handles this. |
| artisan "requires PHP >= 8.4" | You used the default `php` (8.2). Use `/opt/alt/php84/usr/bin/php`. |

---

## Why not GitHub Actions?

We set it up and it can't work here: GitHub's runners can't reach the server (Hostinger firewalls
their datacenter IPs — connection times out). The repo is on GitHub purely as an off-site backup;
**deploys go through the `production` remote**, not GitHub.
