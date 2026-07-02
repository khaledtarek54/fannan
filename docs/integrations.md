# Integrations

## Payments

The app integrates **two** gateways. HyperPay is the primary (KSA) path used by `PaymentController`; EasyKash is a secondary (EGP) path used by the API `EasyKashController`.

### HyperPay (primary — KSA)

- **Service:** `app/Services/HyperPayService.php`
- **Config:** `config/hyperpay.php` — test/live endpoints & entity IDs switch on `APP_ENV`. Env keys: `HYPERPAY_TEST_URL`, `HYPERPAY_TEST_ENTITY_ID`, `HYPERPAY_TEST_MADA_ENTITY_ID`, `HYPERPAY_TEST_APPLE_PAY_ENTITY_ID`, `HYPERPAY_TEST_ACCESS_TOKEN`, and the `HYPERPAY_LIVE_*` equivalents.
- **Controller:** `PaymentController` (`checkout`, `checkPaymentStatus`, `webhook`).
- **Flow:**
  1. `checkout` → `POST {baseUrl}/v1/checkouts` with entityId, amount, currency (SAR), merchantTransactionId, `shopperResultUrl`; returns a checkout id + widget.
  2. `checkPaymentStatus` → `GET {baseUrl}{resourcePath}?entityId=…` and pattern-matches HyperPay result codes.
  3. `webhook` marks the transaction/order paid.
- **Payment methods:** MADA, Visa/card, Apple Pay (separate entity IDs).
- ⚠️ **Security:** `resourcePath` is taken from client input and used to build the outbound status URL without validation (SSRF risk). See [SECURITY_ISSUES.md](SECURITY_ISSUES.md).

### EasyKash (secondary — EGP)

- **Service:** `app/Services/EasyKashService.php`; record service `app/Services/UserTransactionService.php`.
- **Config:** `config/services.php` (`EASYKASH_API_KEY`, `EASYKASH_HMAC_SECRET`, `EASYKASH_REDIRECT_URL`).
- **Controller:** `app/Http/Controllers/API/EasyKashController.php` (`createPayment`, `callback`).
- **Flow:**
  1. `createPayment` → `POST https://back.easykash.net/api/directpayv1/pay` with amount, currency (EGP), paymentOptions, a random 6-digit `customerReference`; stores a `UserTransaction` (pending).
  2. `callback` handles two paths:
     - **POST/webhook:** verifies an **HMAC-SHA512** signature (`verifyCallbackSignature`, `hash_equals`) over ProductCode, Amount, ProductType, PaymentMethod, status, easykashRef, customerReference → then `updateFromCallback`. ✅
     - **GET redirect:** updates the record from **query params** (`status`, `customerReference`, `easykashRef`) via `updateFromRedirect` — **no signature**. ⚠️ See [SECURITY_ISSUES.md](SECURITY_ISSUES.md).
- **Status lookup:** `GET /api/payments/easykash/status?customerReference=…` (public closure, `routes/api.php:30`) — no auth, enables enumeration.

## Firebase / FCM (push)

- **Packages:** `kreait/laravel-firebase`, `laravel-notification-channels/fcm`.
- **Config:** `config/firebase.php` (`FIREBASE_PROJECT`, `FIREBASE_CREDENTIALS`, `FIREBASE_DATABASE_URL`, `FIREBASE_STORAGE_DEFAULT_BUCKET`, …).
- **Base channel:** `app/Notifications/PushNotification.php` (implements `ShouldQueue`; Android/iOS configs).
- **Events (8 notifications):** AcceptOrder, BiddingOfferStatus, CancelOrder, CompleteOrder, CounterOffer, NewBiddingOffer, NewMessage, NewOrder.
- User FCM token routed via `User::routeNotificationForFcm()`.

## Social login (Socialite)

- **Package:** `laravel/socialite`. Providers: Google, Apple, Facebook (`config/services.php`).
- Env keys: `GOOGLE_CLIENT_ID/SECRET`, `APPLE_CLIENT_ID/SECRET`, `FACEBOOK_CLIENT_ID/SECRET`.
- Entry points: `POST /api/login-social`, `POST /api/social/login`. (Redirect URLs contain placeholder values in config — verify before relying on the web OAuth flow.)

## Localization

- **Package:** `mcamara/laravel-localization` (`config/laravellocalization.php`; supported en/es/ar, default en).
- **Runtime:** the custom `Localization` middleware overrides URL-based detection — it reads the `lang` **header** (default `ar`) and sets the locale directly. Filament uses translatable fields for en/ar.

## PDF (invoices)

- **Package:** `barryvdh/laravel-dompdf`. Used by `InvoiceController::download` (`GET /api/invoice/download`) to render `resources/views/invoices/order.blade.php` into a downloadable PDF. Participant-only, IBAN omitted.

## Observability

- **Telescope** (`laravel/telescope`) — enabled in **local** only (`AppServiceProvider` ~100-102), path `/telescope`, DB driver, slow-query threshold 100ms.

## Model status

- **Package:** `spatie/laravel-model-status` (`config/model-status.php`) — status history for Order, OrderOffer, BiddingOrderArtist, Ad.

## External service env keys (reference)

Grouped: **DB/mail/cache/queue/redis** (standard Laravel), **AWS S3**, **Pusher** (broadcast, optional), plus service integrations: **HyperPay** (`HYPERPAY_*`), **EasyKash** (`EASYKASH_*`), **Firebase** (`FIREBASE_*`), **Socialite** (`GOOGLE_/APPLE_/FACEBOOK_*`), **Google Maps** (`GOOGLE_MAP_API_KEY`). Keep all of these out of version control (`.env` is git-ignored).
