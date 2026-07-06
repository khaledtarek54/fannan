---
name: local-setup
description: How to run the Fannan Laravel app and its test suite locally — Herd (PHP 8.4) + DBngin (MySQL). Invoke when setting up the environment, running the app, running/debugging tests, or hitting DB, Passport-key, or PHP-version issues.
---

# Local setup (Fannan)

Local stack is **Herd** (PHP) + **DBngin** (MySQL). Do **not** use Homebrew PHP/MySQL — the committed
`vendor/` is pinned to PHP 8.4 and won't run on 8.2/8.3. Full reference: `docs/README.md`.

## Serve

- The project is linked in Herd at `http://fannan.test`, isolated to PHP 8.4 (`herd isolate 8.4`).

## Database (DBngin MySQL 8 — `root` / no password / `127.0.0.1:3306`)

- App DB: `fanna` (set in `.env`).
- Test DB: **`testing`** — this is what `phpunit.xml` actually points at (`DB_DATABASE=testing`). Create it once if missing.
  - ⚠️ `docs/README.md` mentions `fanna_testing`; that's stale. The authoritative value is whatever `phpunit.xml` sets — currently `testing`.

## Before tests run

- Passport keys must exist locally (gitignored): `php artisan passport:keys`.
- `Tests\TestCase::setUp()` recreates the Passport personal-access client each run, so token-issuing endpoints (login/register/password-reset) work under `RefreshDatabase`.

## Commands

```bash
php artisan test                    # full feature suite (uses the `testing` DB)
php artisan test --filter=XxxTest   # one file, fast feedback loop
php artisan migrate                 # against `fanna`
```

Admin panel: a local-only admin account is documented in `docs/README.md` (`User::canAccessPanel()`
currently returns true for everyone — a known finding, don't rely on it in prod).
