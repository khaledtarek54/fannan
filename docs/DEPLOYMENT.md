# Deployment

The Fannan backend (`apps.fannan.ai`) runs on Hostinger shared hosting and is
deployed with plain **git + a single script**. No CI, no containers.

## One-time setup (already done)

- The server directory `~/domains/apps.fannan.ai/public_html` is a git checkout
  of this repo's `main` branch (remote `origin` = the private GitHub repo).
- `.env`, `vendor/`, `storage/`, and uploaded media are **not** in git, so a
  deploy never touches them.

## Deploying a change

1. Commit and push from your machine:
   ```bash
   git add -A && git commit -m "..." && git push
   ```
2. Run the deploy script on the server (over SSH):
   ```bash
   ssh -p 65002 u715768425@217.21.90.20 'bash ~/domains/apps.fannan.ai/public_html/deploy.sh'
   ```
   `deploy.sh` pulls `origin/main`, runs `composer install --no-dev`, applies
   migrations, and clears caches. It is safe to re-run.

## Environment notes

- **PHP:** the shell default is 8.2, but the app needs 8.4. Always run artisan/composer
  with `/opt/alt/php84/usr/bin/php` (the deploy script already does).
- **Caches:** we intentionally do **not** run `config:cache` (keeps `.env` edits live)
  or `route:cache` (`routes/api.php` uses closures, which can't be cached).
- **Telescope** must stay disabled in production (`TELESCOPE_ENABLED=false`).
- **Mail** is `MAIL_MAILER=log` until real SMTP credentials are set.

## Backups & rollback

- Full backups live on the server in `~/backups/` (code+media tar, `.env`, DB dump),
  and copies of `.env` + the DB dump are on the maintainer's Mac.
- **Roll back code:** `git reset --hard <previous-commit>` in the app dir, then re-run
  the cache-clear steps. Find the previous commit with `git log --oneline`.
- **Roll back the database:** restore the matching `db-<timestamp>.sql.gz` dump.

## Rate limits (per `RouteServiceProvider`)

- `api` (general): 120/min · `auth` (login/OTP): 30/min per IP · `payment`: 30/min.
- Raise the numbers there if a client legitimately needs more throughput.
