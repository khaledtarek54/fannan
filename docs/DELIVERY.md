# Fannan Backend — Delivery & Handover

_Last updated: 2026-07-02._

This document is the handover summary for the Fannan Laravel backend (mobile API + Filament
admin) running on Hostinger at **apps.fannan.ai** (public host `app.fannan.ai`).

---

## 1. What was done

### Stability (mobile dev "API keeps rejecting me")
Root cause was **not** an anti-spam rule. Two burst-triggered limits on shared hosting, made worse
by a heavy per-request app:
- API rate limiter was **60/min**, keyed by IP for unauthenticated calls → 429s. Raised to **120/min**
  general, with dedicated **auth (30/min)** and **payment (30/min)** limiters.
- **Laravel Telescope** was enabled in production, writing to a 86 MB table on every request →
  disabled (`TELESCOPE_ENABLED=false`) and the table cleared.
- `MAIL_MAILER` pointed at a dead `mailpit` host → set to `log` (no more hung requests).
- `APP_DEBUG=true` in production → `false`.

### Security (all items from "Backend Security Points")
Every finding — Critical, High, Medium, Low, plus **A1** (admin panel open to all users) — is fixed
and live. See `docs/SECURITY_ISSUES.md` for per-item detail. Highlights:
- **C1** removed the unauthenticated `/api/command` RCE route.
- **C2** the EasyKash GET callback no longer marks orders PAID from a crafted URL (payment state
  changes only via the HMAC-verified POST webhook).
- **H1–H5 / M2–M5** order, invoice, address, offer, and checkout endpoints now enforce ownership
  (a user can only act on their own data — 403 otherwise).
- **A1** the `/admin` panel is gated on `is_admin` (was reachable by any of the 13 users).
- **M6/M1** EasyKash pay/status require auth; **M8** HyperPay SSRF closed; rate limits added (M10/M11);
  cleanup (L1/L2/L3).

### Repo hygiene
The repo now **exactly mirrors production** (it had diverged). Secrets that were committed since the
first import (Firebase key, OAuth secret, an `.env`-bearing zip) were removed from the tree **and
scrubbed from all git history**.

---

## 2. Admin access

- **URL:** https://apps.fannan.ai/admin
- **Email:** `admin@fannan.ai`  ·  **Password:** `password`  ← **change on first login.**

This is currently the **only** admin. The other 13 users cannot reach `/admin`. The owners should
decide which real accounts, if any, get admin: `UPDATE users SET is_admin = 1 WHERE email = '…';`

---

## 3. Deploying a change

The server directory `~/domains/apps.fannan.ai/public_html` is a git checkout of this repo. To deploy:

```bash
# 1. from your machine — push to the server
git push production main

# 2. on the server — pull + install + migrate + clear caches
ssh -p 65002 u715768425@217.21.90.20 'bash ~/domains/apps.fannan.ai/public_html/deploy.sh'
```

`deploy.sh` is idempotent and uses PHP 8.4 (`/opt/alt/php84/usr/bin/php`). See `docs/DEPLOYMENT.md`
for environment notes (no `config:cache`/`route:cache`; keep dev deps installed — collision is an
auto-discovered provider).

---

## 4. Backups & rollback

- Full pre-change backup: `~/backups/code-20260702-171657.tar.gz` (+ `.env`, DB dump); copies of
  `.env` + DB dump are on the maintainer's Mac (`~/fannan-backups/`).
- Security-fix rollback (just those 15 files): `~/backups/secfix-pre-20260702-204249.tar.gz`.
- Code rollback via git: `git reset --hard <commit>` then re-run the cache-clear steps.

---

## 5. Open items for the owners / team

| Priority | Item |
|----------|------|
| **High** | **Run the mobile app end-to-end on prod** — especially the EasyKash payment flow, since `easykash/pay` + `easykash/status` now require the auth token (confirm the app sends it). |
| **High** | **Rotate** the Firebase service-account key + Google OAuth client secret (were exposed in old repo history), update `.env`. Change the admin password. |
| Medium | **M7**: public web account-deletion is rate-limited but still needs an OTP/verification step — requires wiring an SMS delivery channel. |
| Medium | Decide the real admin account(s); optionally remove the loose secret/backup files still sitting in the server app dir (not web-reachable, but should be cleaned). |
| Low | Consider moving credential JSON files out of the app directory; add a real SMTP config if transactional email is needed. |

---

## 6. Environment quick-reference

- PHP: web runs 8.4; shell default is 8.2 — always use `/opt/alt/php84/usr/bin/php` for artisan/composer.
- `.env` (prod): `APP_DEBUG=false`, `TELESCOPE_ENABLED=false`, `MAIL_MAILER=log`, `QUEUE_CONNECTION=sync`.
- DB: MySQL, per-account cap of 75 connections (shared hosting) — keep the app light per request.
