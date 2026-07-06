# Fannan backend — agent guide

Fannan (فنان) is a client-to-artist marketplace. This repo is a **Laravel 10** app: a
**Passport-authenticated REST API** for the iOS/Android apps, plus a **Filament 3** admin panel.
MySQL (utf8mb4). Deployed to `apps.fannan.ai` on Hostinger shared hosting.

## Start here — canonical docs live in [`docs/`](docs/)

Read the relevant one before non-trivial work:

- [architecture.md](docs/architecture.md) — stack, request lifecycle, auth, middleware, services/repos, rate limiting
- [domain-model.md](docs/domain-model.md) — models, enums, order/bidding/payment workflows
- [api-reference.md](docs/api-reference.md) — endpoint catalog + FormRequest validation
- [admin-panel.md](docs/admin-panel.md) — Filament resources & access control
- [integrations.md](docs/integrations.md) — HyperPay/EasyKash payments, Firebase/FCM, Socialite
- [SECURITY_ISSUES.md](docs/SECURITY_ISSUES.md) — verified findings + status (read before touching auth/payments/ownership)
- [DEPLOYMENT.md](docs/DEPLOYMENT.md) — how releases reach production

## Conventions (match existing code)

- Controllers stay **thin**; business logic lives in **Services** (`app/Services/`) + **Repositories** (`app/Repositories/`), bound via interfaces in `AppServiceProvider`.
- API responses are shaped by **API Resources** (`app/Http/Resources/`) — never return a raw model (PII/IBAN leak risk).
- Validation **and** authorization live in **FormRequests** (`app/Http/Requests/`).
- Domain constants are **enums** (`app/Enums/`).
- Mobile API auth is Passport `auth:api` (Sanctum only backs the `/api/user` stub).

## Skills — invoke the matching one

- `/feature-test` — write & run a PHPUnit feature test. **Required after every change/feature** (happy + authorization path).
- `/secure-endpoint` — before adding/modifying any API endpoint, controller, or payment/auth flow.
- `/local-setup` — Herd + DBngin dev environment, running the app and the test suite.
- `/deploy` — how releases reach production (and how not to deploy by accident).

## Non-negotiables

- **Deploy = `git push production main`** (auto-deploys live). `git push origin main` is GitHub backup only — no deploy. Never push to `production` unless you intend a live release. See `/deploy`.
- **Authorization/ownership is the recurring bug class here.** Scope every query to the authenticated user; never trust a client-supplied amount, price, `is_paid`, `role`, or balance. See `/secure-endpoint`.
- **Telescope stays disabled in production** (`TELESCOPE_ENABLED=false`) — it caused the prod slowdowns.
- The committed `vendor/` is pinned to **PHP 8.4** — use Herd's 8.4, not 8.2/8.3, and not Homebrew PHP.
- After any code change, **write/update a feature test and run `php artisan test`** before calling it done.
