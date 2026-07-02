# Architecture

## High-level shape

```
Mobile apps (iOS/Android)
        │  REST/JSON  (Bearer token, Passport)
        ▼
   routes/api.php ──► API Controllers ──► Services / Repositories ──► Eloquent Models ──► MySQL
        │                                     │
        │                                     ├─► HyperPayService / EasyKashService  (payments)
        │                                     ├─► NotificationService ──► FCM (Firebase)
        │                                     └─► API Resources (response shaping)
        │
   routes/web.php ──► Web controllers (privacy/terms/contact/delete-account, artist web register)
        │
   /admin  ──► Filament 3 admin panel (web guard)
```

## Layers

- **Controllers** (`app/Http/Controllers/`, API ones under `API/`) — thin; validate via FormRequest, delegate to a Service/Repository, return an API Resource.
- **Services** (`app/Services/`) — business logic (orders, bidding, payments, notifications, artists, chat, etc.). Order pricing is centralised in `OrderPricingService` (so the quote == the charge); artist payout happens **on order completion** via `OrderService::settleOrder` (escrow).
- **Repositories** (`app/Repositories/`) — data access for users/auth/categories; bound to interfaces in `app/Providers/AppServiceProvider.php` (~lines 105-127).
- **Models** (`app/Models/`) — Eloquent entities; several use `SoftDeletes` and the Spatie `HasStatuses` trait.
- **Resources** (`app/Http/Resources/`) — transform models into the JSON contract the apps expect.
- **Enums** (`app/Enums/`) — domain constants (roles, order statuses, types, etc.).

## Request lifecycle & middleware

Global middleware (`app/Http/Kernel.php`): TrustProxies, HandleCors, PreventRequestsDuringMaintenance, ValidatePostSize, TrimStrings, ConvertEmptyStringsToNull.

**API group** (`api`):
1. `ThrottleRequests:api` — rate limiting (see below)
2. `SubstituteBindings`
3. `Localization` (custom) — reads the `lang` header (default `ar`) and sets the app locale.

**Web group** (`web`): cookies, session, CSRF, bindings — used by the Filament panel and the marketing/legal web routes.

### Custom middleware (`app/Http/Middleware/`)

| Alias | Class | Purpose |
|-------|-------|---------|
| `DeleteAccount` | `DeleteAccountMiddleware` | Rejects (401) requests from soft-deleted users (`deleted_at` set). |
| `CompleteProfileMiddleware` | `CompleteProfileMiddleware` | Rejects (400) client/artist users whose profile isn't verified/complete. |
| `Localization` | `Localization` | Header-based locale (`lang` header, default `ar`). |

## Authentication

- **API:** Laravel **Passport**, guard `auth:api` (`config/auth.php`). Tokens are issued by `AuthController` on `login` / `register` / `socialLogin`.
- Most authenticated API routes sit inside a `['auth:api', 'DeleteAccount']` group (`routes/api.php:90`); order/bidding/payment sub-groups add `CompleteProfileMiddleware`.
- **Admin:** Filament uses the default `web` guard with session auth.
- **Roles:** the `users.role` column + `UserRole` enum (`client` | `artist`). There is **no admin role** — admin access is currently ungated (see [admin-panel.md](admin-panel.md) and [SECURITY_ISSUES.md](SECURITY_ISSUES.md)).

### Authorization pattern

Most authorization is enforced in **FormRequest `authorize()`** methods via role checks (e.g. `role == CLIENT`). Many requests return `true` unconditionally and rely on the controller/service. Note: role checks answer *"is this a client?"* — they generally **do not** answer *"does this client own this record?"*, which is the root of several IDOR findings in [SECURITY_ISSUES.md](SECURITY_ISSUES.md).

## Rate limiting

`app/Providers/RouteServiceProvider.php` (~27-29):

```php
RateLimiter::for('api', fn (Request $r) =>
    Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
```

A **single** 60/min limiter covers **all** API routes — login, payments, and browsing share one pool keyed by user id (or IP when unauthenticated). There are no per-endpoint limits.

## Configuration

Environment keys are grouped in `.env`: app, DB, mail, cache/session/queue, Redis, AWS S3, Pusher, plus service keys referenced from `config/services.php` and `config/hyperpay.php` (HyperPay, EasyKash, Firebase, Socialite, Google Maps). See [integrations.md](integrations.md).

## Notable providers

- `AppServiceProvider` — binds repository/service interfaces; registers Telescope in `local` only.
- `RouteServiceProvider` — route loading + the `api` rate limiter; `HOME = '/home'`.
- `Filament/AdminPanelProvider` — the `/admin` panel.
- `EventServiceProvider` — `Registered → SendEmailVerificationNotification`.

## Background work

There are **no** Jobs/Events/Listeners of the app's own (`app/Jobs`, `app/Events`, `app/Listeners` are empty). Push notifications (`app/Notifications/`, 8 classes) implement `ShouldQueue` and are delivered via the FCM channel, so they depend on `QUEUE_CONNECTION`.
