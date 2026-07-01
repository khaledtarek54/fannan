# Fannan Backend — Project Documentation

**Fannan (فنان)** is a client-to-artist marketplace for creative/event services. Clients post jobs or hire artists directly; artists accept, negotiate, deliver, and get paid. The backend is a **Laravel 10** application exposing a **REST API** for the mobile apps plus a **Filament 3** admin panel.

This `docs/` folder documents the system for maintenance and future enhancement.

## Contents

| Doc | What's inside |
|-----|---------------|
| [architecture.md](architecture.md) | Tech stack, request lifecycle, auth, middleware, service/repository layers, rate limiting |
| [domain-model.md](domain-model.md) | Eloquent models, enums, entity relationships, the order/bidding/payment workflows |
| [api-reference.md](api-reference.md) | Full endpoint catalog, controller responsibilities, FormRequest validation |
| [admin-panel.md](admin-panel.md) | Filament panel config, resources, access control |
| [integrations.md](integrations.md) | HyperPay & EasyKash payments, Firebase/FCM, Socialite, localization, Telescope |
| [SECURITY_ISSUES.md](SECURITY_ISSUES.md) | Verified security findings, severity, and remediation status |

## Tech stack

- **PHP** ^8.1 (runs on 8.4 locally; the deployed `vendor/` is built for 8.4)
- **Laravel** 10.x
- **Filament** 3.x (admin panel)
- **Auth:** Laravel **Passport** (`auth:api`) for the mobile API; Sanctum is installed but only used by the `/api/user` stub
- **DB:** MySQL (`utf8mb4`)
- **Payments:** HyperPay (primary, KSA — MADA/Visa/Apple Pay) and EasyKash (secondary, EGP)
- **Push:** Firebase Cloud Messaging via `kreait/laravel-firebase` + `laravel-notification-channels/fcm`
- **Social login:** Socialite (Google, Apple, Facebook)
- **i18n:** `mcamara/laravel-localization` + Filament translatable fields (en/ar)
- **State machines:** `spatie/laravel-model-status`
- **Debug:** Telescope (local only)

## Local development setup

The app runs locally on **Herd** (PHP) + **DBngin** (MySQL):

1. **Serve:** the project is linked in Herd as `http://fannan.test`, isolated to **PHP 8.4** (`herd isolate 8.4`). The bundled `vendor/` was built with the Composer platform pinned to 8.4, so it will not run on 8.2/8.3.
2. **Database:** start the DBngin MySQL 8.0 instance (root / no password / `127.0.0.1:3306`). The `.env` uses database `fanna`.
3. **Data:** production data was imported from a phpMyAdmin dump. Re-import with FK checks disabled (the production data contains orphaned rows):
   ```bash
   ( echo "SET FOREIGN_KEY_CHECKS=0;"; cat dump.sql ) | mysql -u root -h 127.0.0.1 fanna
   ```
4. **Admin login:** production has no admin account (only `client`/`artist` roles), and `User::canAccessPanel()` returns `true` for everyone. A local admin was created: `admin@fannan.sa` / `Fannan@2026`.

> ⚠️ Local-only credentials above. Do not use in production.

## Repository conventions

- Business logic lives in **Services** (`app/Services/`) and **Repositories** (`app/Repositories/`), bound via interfaces in `AppServiceProvider`. Controllers stay thin.
- API responses are shaped by **API Resources** (`app/Http/Resources/`).
- Request validation + authorization lives in **FormRequests** (`app/Http/Requests/`).
- Domain constants are **PHP enums** (`app/Enums/`).
