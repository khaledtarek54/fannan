# Fannan Backend — Security Findings (Round 2)

**Scope:** REST API + admin panel of the Fannan Laravel backend — a second, deeper pass.
**Date:** 2026-07-06 · **Baseline:** working tree at `c851d66` (branch `main`).
**Method:** A multi-domain audit (authorization/IDOR, payment integrity, secrets/config/data-exposure, auth mechanism/injection/uploads) traced every route → controller → service → repository. Every **Critical** and **High** below was then independently re-verified against source; locations are `file:line`.
**Relationship to Round 1:** This does **not** replace `docs/SECURITY_ISSUES.md`. Round 1's fixes were re-checked and **hold** (see "Round 1 re-verification" at the bottom). Everything here is **new** — issues the first pass did not cover. IDs are prefixed `R2-` to avoid collision with Round 1 IDs.
**Status legend:** ✅ Confirmed in code · ⚠️ Partially/contextually confirmed · ☐ Not yet fixed · ☑ Fixed.

## Summary

| ID | Finding | Severity | Verified | Fixed |
|----|---------|----------|----------|-------|
| R2-C1 | `POST /api/login-social` — social login trusts client-supplied `email`, no provider token check → account takeover | Critical | ✅ | ☑ |
| R2-C2 | `POST /api/easykash/pay` — charge `amount` taken from the client; callback settles order without checking amount → pay-zero bypass | Critical | ✅ | ☐ |
| R2-C3 | `POST /api/order/store` — `is_paid`/pricing columns mass-assignable → create a pre-paid order for free | Critical | ✅ | ☐ |
| R2-C4 | `Model::unguard()` disables mass-assignment protection for **all** models globally | Critical | ✅ | ☑ |
| R2-C5 | OTP is a 4-digit plaintext code, loose-compared, no expiry/lockout, hardcoded `1234` when `APP_ENV=local` → reset takeover | Critical | ✅ | ☐ |
| R2-H1 | `POST /api/bidding-order/id` — unscoped read IDOR leaks any client's PII + exact lat/long | High | ✅ | ☐ |
| R2-H2 | `fcm_token` returned to other users via `UserResource` embedded in offer listings → push hijacking | High | ✅ | ☐ |
| R2-H3 | Counterparty PII (email/phone/VAT/CR) leaked through order/offer resource embeds | High | ✅ | ☐ |
| R2-H4 | Telescope defaults **enabled** (auto-discovered) with no field scrubbing → records passwords/OTP/payment payloads in prod | High | ✅ | ☐ |
| R2-H5 | Profile-photo upload has no `mimetypes`/`max` validation, stored on public disk → stored-XSS/abuse | High | ✅ | ☐ |
| R2-H6 | Paid amount never verified against the order on either rail's confirmation (EasyKash callback + HyperPay status) | High | ✅ | ☐ |
| R2-H7 | `POST /api/easykash/pay` authenticated but no order-ownership check | High | ✅ | ☐ |
| R2-M1 | `customer_reference` is `rand(100000,999999)` — guessable + collision-prone, no unique constraint | Medium | ✅ | ☐ |
| R2-M2 | Auth rate limiters keyed by IP only — no per-account ceiling for OTP/password brute force | Medium | ✅ | ☐ |
| R2-M3 | Coupon > order cost → negative total (no clamp); same coupon reusable across concurrent checkouts | Medium | ✅ | ☐ |
| R2-M4 | `getTransactionStatus()` queries `order_id` with a `customer_reference` value — wrong column | Medium | ✅ | ☐ |
| R2-M5 | `POST /api/check-phone-exists` — unauthenticated account-enumeration oracle | Medium | ✅ | ☐ |
| R2-M6 | Passport tokens unscoped, ~1-year lived, not rotated on password reset | Medium | ✅ | ☐ |
| R2-L1 | Old **RCE route committed to git** in `routes/api.php.bak-*` (+ `RouteServiceProvider.php.bak-*`) | Low | ✅ | ☐ |
| R2-L2 | Dead code with latent IDORs: `OrderOfferController` (unrouted), `support/delete` route | Low | ✅ | ☐ |
| R2-L3 | `public/default.php` / `default.php.old.php` leftover placeholder files in web root | Low | ✅ | ☐ |
| R2-L4 | CORS wildcard origin/methods/headers (no credentials → low) | Low | ✅ | ☐ |
| R2-L5 | No `$hidden` on `UserTransaction`/`Transaction`; `SupportController::store` returns a raw model | Low | ✅ | ☐ |

**Fix priority:** R2-C1, R2-C2, R2-C3, R2-C4 first (single-request account takeover / payment bypass), then R2-C5 and the High PII leaks (R2-H1..H3) and Telescope (R2-H4).

---

## Critical

### R2-C1 — Social login is a full authentication bypass ✅
**Location:** `app/Repository/UserRepository.php:72-93` (`socialLogin`), `app/Http/Requests/Users/SocialLoginRequest.php:24-26`, routed at `routes/api.php` (`/login-social`, `/social/login`).
`SocialLoginRequest` validates **only** `email => required|exists:users,email`. There is no OAuth/OpenID ID-token, access token, or signature — a grep of the app for provider-token verification (`verifyIdToken`/socialite/firebase) finds nothing live. `socialLogin()` looks the user up by the client-supplied email and immediately returns `$user->createToken('authToken')->accessToken`. The only gates are `is_verified` and not-soft-deleted.
**Impact:** `POST /api/login-social {"email":"victim@example.com"}` returns a valid Passport bearer token for the victim — no password, no OTP. Every verified account (and any admin) is takeover-able. `/api/check-phone-exists` and distinct error messages (R2-M5) make target discovery trivial.
**Fix:** Verify the provider's ID token server-side (Socialite / Firebase Admin `verifyIdToken`) and use the *verified* email/subject from the provider — never a client-supplied email — before issuing a token.
**Fixed (`security/round2-criticals`):** ☑ New `FirebaseAuthService::verifiedEmail()` verifies the Firebase ID token via the kreait Admin SDK (`verifyIdToken`, `checkIfRevoked=true`, requires `email_verified`) and returns the token's verified email. `SocialLoginRequest` now requires `id_token` (no trusted `email`); `UserRepository::socialLogin()` resolves the account from the verified email, stays **login-only** (rejects unknown emails, no auto-register). Covered by `tests/Feature/SocialLoginTest.php`. **Ops note:** requires `FIREBASE_CREDENTIALS` (service-account JSON) in prod, and the mobile client must send the Firebase ID token as `id_token`.

### R2-C2 — EasyKash charge amount is client-controlled → pay-zero / pay-less bypass ✅
**Location:** `app/Http/Requests/CreatePaymentRequest.php:26-32`, `app/Http/Controllers/API/EasyKashController.php:28-57`, `app/Services/UserTransactionService.php:12-25` and `:44-56`.
The EasyKash amount comes straight from the request body (`"amount" => "required|numeric"` — no minimum, never compared to `Order->total_cost`), is stored verbatim by `UserTransactionService::create()`, and sent to EasyKash. On callback, `updateFromCallback()` sets `is_paid=true` on the transaction **and the order** whenever `status === "PAID"`, storing the paid `Amount` but **never validating it against the order total**.
**Impact:** `POST /api/easykash/pay {order_id:<mine>, amount:1, …}` creates a 1-EGP (or 0) pay-link; the resulting *legitimately-signed* PAID callback fully settles the order. The HMAC check does not help — EasyKash signs the tiny amount it actually processed. Direct financial loss. (The HyperPay *charge* is server-priced and safe; the gap on that rail is only the confirmation step — see R2-H6.)
**Fix:** Compute the charge server-side from the order (as HyperPay's `PaymentService::checkout` already does), and in `updateFromCallback` require `(float)$data->Amount >= (float)$order->total_cost` before flipping `is_paid`.

### R2-C3 — Mass-assignable `is_paid` on order creation → free orders ✅
**Location:** `app/Services/OrderService.php:67-70` → `app/Services/Concerns/OrderRepository.php:51-59` → `Order::create()`. `Order` `$fillable` includes `is_paid`, `cost`, `updated_budget`, `coupon_amount` (`app/Models/Order.php:25-27`). `StoreOrderRequest` (`app/Http/Requests/Order/StoreOrderRequest.php`) does not strip unknown keys.
`store()` passes `$request->all()` into `orderRepository->create($payload)`, which forces only `number` and `client_id` before `Order::create($payload)`. Any other request key with a matching fillable column is written.
**Impact:** `POST /api/order/store` with the required valid fields plus `"is_paid": true` creates an order already marked paid (no payment). `cost`/`coupon_amount`/`updated_budget` can likewise be tampered to distort pricing. Reachable by any client. (Compounded by R2-C4, but exploitable even without it because these columns are in `$fillable`.)
**Fix:** Remove `is_paid` and pricing columns from `Order::$fillable`; build the create payload from an explicit server-side whitelist.

### R2-C4 — `Model::unguard()` disables mass-assignment protection globally ✅
**Location:** `app/Providers/AppServiceProvider.php:80` — `Model::unguard();` runs unconditionally in `boot()`.
Every model's `$fillable`/`$guarded` becomes inert app-wide. This is the framework-level backstop for R2-C3 and every `$request->all()` sink (`SupportService::create`, `AddressService::create`, `OrderService::store/checkout`, etc.). It also **voids the reasoning behind the Round 1 A1 fix** — the "`is_admin` is intentionally not fillable" comment in `User.php` is meaningless while unguard is active.
**Impact:** Any current or future `Model::create($request->all())` / `->update($request->all())` accepts arbitrary columns for that model (`Order.is_paid`, `UserTransaction.is_paid/status`, `Setting.value`, `User.is_admin/role/wallet`, …). Today, direct `is_admin` self-escalation is *not* reachable — the profile-update path (`ClientRepository::complete`, `app/Repository/ClientRepository.php:25-46`) uses an explicit whitelist — but nothing prevents it if any sink changes.
**Fix:** Delete `Model::unguard()`; define precise `$fillable` per model (omitting `is_admin`, `role`, `wallet`, `platform_fees`, `is_paid`, `status`, `is_verified`, `verification_code`).
**Fixed (`security/round2-criticals`):** ☑ `Model::unguard()` removed from `AppServiceProvider::boot()`. All 26 models already declare `$fillable`, and `BaseRepository::create/update` already filter `$request->all()` to `$fillable`, so the untrusted API sinks were already constrained — removing unguard restores per-model `$fillable` for the *direct* create/update calls. Blast-radius reconciled: added `city_id`/`iban` to `User::$fillable` (written by profile-completion + admin forms); converted 3 Filament restore actions from `update(['deleted_at'=>null])` to `->restore()`. `is_admin` stays out of `$fillable` (A1 now truly holds); `role`/`wallet`/`platform_fees`/`is_verified`/`verification_code` remain fillable but are unreachable from any untrusted mass-assignment sink (register/profile/delete paths use explicit whitelists or direct assignment). `Order.is_paid`/pricing stripped separately in R2-C3 (group 2). Covered by `tests/Feature/MassAssignmentGuardTest.php`.

### R2-C5 — Weak / backdoored OTP → password-reset account takeover ✅
**Location:** generator `app/Http/Controllers/Controller.php:20-26`; consumed at `app/Repository/UserRepository.php:111-124` (`checkVerificationCode`, loose `==`), `:126-138` (`updatePassword`), `app/Services/UserService.php` (account deletion).
`createVerificationCode()` returns a hardcoded **`1234`** when `config('app.env') === "local"`, else a 4-digit `rand(0,9999)` (10k values), stored plaintext in `users.verification_code`, never expiring, only overwritten on register/resend. Password reset is gated solely on this code; there is no per-account attempt lockout — only 30/min-per-IP (R2-M2).
**Impact:** 10,000 codes ÷ 30/min ≈ 5.5 h single-IP (minutes distributed) to brute any account's code → `updatePassword` returns a token = takeover. If a prod node ever runs `APP_ENV=local`, every OTP is `1234`.
**Fix:** 6-digit `random_int`, stored hashed with a short TTL, per-account attempt lockout + rotation on failure; remove the static `1234` branch.

---

## High

### R2-H1 — `POST /api/bidding-order/id` unscoped read IDOR (PII + geolocation) ✅
**Location:** `routes/api.php` (`bidding-order/id`) → `app/Http/Controllers/API/BiddingOrderController.php:30-37` → `app/Services/BiddingOrderService.php:45-48` (`findById`, unscoped) → `app/Services/Concerns/BaseRepository.php` (`findOrFail`). Authorization: `app/Http/Requests/Order/OrderIdRequest.php:12-14` returns `true`, validates only `exists:orders,id`.
**Impact:** Any authenticated user enumerates `order_id = 1,2,3…` and receives the order owner's **name, email, phone, DOB, gender, city, social handles** (`app/Http/Resources/Client/ClientResource.php:19-38`) plus the order's **exact latitude/longitude** and address (`app/Http/Resources/BiddingOrderResource.php`). The resource author scoped `bidding_offers` to the caller but not the parent record or client PII.
**Fix:** In `show()`, load the order's client and `abort_unless` the caller is the owner; or return a redacted resource (no client PII / no precise coordinates) to non-owner artists.

### R2-H2 — `fcm_token` leaked to other users ✅
**Location:** `app/Http/Resources/UserResource.php:25` emits `fcm_token`; embedded at `app/Http/Resources/BiddingOrderOfferResource.php:25` (`'artist' => new UserResource($artist)`), returned by the authenticated offers listing. Also self-exposed via `ArtistController::profile()`.
**Impact:** A user viewing bids receives every bidding artist's Firebase push token → push-notification spoofing/hijacking against those users.
**Fix:** Remove `fcm_token` from `UserResource` (and any resource served cross-user); add it to `User::$hidden`.

### R2-H3 — Counterparty PII harvesting via resource embeds ✅
**Location:** `app/Http/Resources/ArtistResource.php:23,24,30,31` (email/phone/VAT/CR) embedded in `app/Http/Resources/Order/OrderResource.php:41`; `app/Http/Resources/Client/ClientResource.php:22,23` (email/phone) embedded in `Order/ArtistOrderResource.php` and `Order/BiddingOrderResource.php`.
**Impact:** Any client viewing an order gets the assigned artist's contact + tax identifiers; any artist viewing incoming orders gets the client's email/phone. Systematic PII/tax-ID harvesting by enumerating orders. (Bonus defect: `ClientResource.php:29-30` sets `vat_number`/`cr_number` to `$this->name` — a copy-paste bug.)
**Fix:** Split "self" vs "counterparty" resource shapes; expose contact details only to the participant who needs them for a confirmed engagement, not in list/browse responses.

### R2-H4 — Telescope defaults ON, records secrets in prod ✅
**Location:** `config/telescope.php:19` — `'enabled' => env('TELESCOPE_ENABLED', true)`. The vendor `Laravel\Telescope\TelescopeServiceProvider` is package-**auto-discovered** (`composer.json extra.laravel.dont-discover` is empty; confirmed in `bootstrap/cache/packages.php`), so it boots in every environment gated only by `config('telescope.enabled')` — the `local`-only registration in `AppServiceProvider.php:100-103` does **not** prevent recording. `app/Providers/TelescopeServiceProvider.php:31` hides only `_token`; passwords, `verification_code`, `fcm_token`, and payment payloads are not scrubbed. Dashboard gate hardcodes `admin@admin.com` / `admin@gslksa.com`.
**Impact:** If the production `.env` omits `TELESCOPE_ENABLED=false`, Telescope records login passwords, OTP codes, and payment payloads in cleartext in `telescope_entries`. (`.env.example` sets it to `false`, so exposure is conditional on the deployed env being correct — a fragile default.)
**Fix:** `'enabled' => env('TELESCOPE_ENABLED', false)`; expand `hideRequestParameters` to `password`, `password_confirmation`, `verification_code`, `fcm_token`, `token`; prune stored data.

### R2-H5 — Unrestricted profile-photo upload ✅
**Location:** `app/Repository/ClientRepository.php:22-23` — `Storage::disk('public')->put('users/', $request->profile_photo)` with **no** validation; `ClientCompleteProfileRequest` has no `profile_photo` rule. Public disk is web-served via the storage symlink (`config/filesystems.php`).
**Impact:** Any authenticated user uploads arbitrary content (e.g. `.svg`/`.html` for stored XSS, or oversized files for storage exhaustion); the original extension is kept. The Round 1 L3 fix (which correctly hardened the *gallery* upload at `StoreArtistGalleryRequest.php:29-35`) was never applied to this path.
**Fix:** Add `file|mimetypes:image/jpeg,image/png,image/webp|max:…` to the profile-photo rule; store with a server-generated name.

### R2-H6 — Paid amount not verified on payment confirmation ✅
**Location:** EasyKash: `app/Services/UserTransactionService.php:44-56` (see R2-C2). HyperPay: `app/Services/PaymentService.php:107-129` → `app/Services/HyperPayService.php:62-153` — the status confirmation reads only the result **code**; it never compares HyperPay's returned amount / `merchantTransactionId` to the stored order before setting `is_paid=true`, and has no idempotency guard.
**Impact:** Confirmation trusts a success code without binding it to the expected amount + order — the weakest link on both rails, and the mechanism that turns R2-C2 into a settled order.
**Fix:** Before marking paid, assert returned `amount == stored amount` and `merchantTransactionId == "Transaction".$order_id`; short-circuit if already complete (idempotency).

### R2-H7 — `POST /api/easykash/pay` lacks order-ownership check ✅
**Location:** `app/Http/Controllers/API/EasyKashController.php:28-57`, `app/Services/UserTransactionService.php:12-25`. Round 1 M6 added `auth:api` + `throttle:payment` (good) but never verified `order_id` belongs to `auth()->id()`.
**Impact:** An authenticated user creates EasyKash pay-links against another user's `order_id`. Lower impact alone, but combined with R2-C2 it lets an attacker drive another user's order to "paid" for a trivial amount.
**Fix:** `abort_unless($order->client_id === auth()->id(), 403)` before creating the transaction.

---

## Medium

### R2-M1 — `customer_reference` is guessable and collision-prone ✅
`app/Services/UserTransactionService.php:14` — `rand(100000, 999999)` (900k values, not `random_int`, no unique DB constraint). `updateFromCallback` resolves by `where('customer_reference', …)->first()`, so a collision can settle the wrong transaction/order. Round 1's C2 fix removed the *state mutation* from the GET path but left this root weakness.
**Fix:** `Str::uuid()` (or `random_int` + a unique index on `customer_reference`).

### R2-M2 — Auth rate limiters keyed by IP only ✅
`app/Providers/RouteServiceProvider.php:34-36` — `auth` limiter is `Limit::perMinute(30)->by($request->ip())`, with no per-phone/per-account dimension. One attacker rotating IPs (or a botnet) can brute a single account's OTP/password without hitting a per-account ceiling. The named limiters *do* exist and cover login/OTP (Round 1 M11 structure holds) — the keying is the gap. 30/min is also generous given R2-C5's 10k-code space.
**Fix:** Add a per-account key (`->by($request->input('phone').'|'.$request->ip())`) and lower the OTP ceiling; add account lockout on repeated failures.

### R2-M3 — Coupon over-discount and cross-order reuse ✅
`app/Services/Concerns/OrderRepository.php:127-138` (`calculateCouponAmount` for FIXED returns `$coupon->amount` uncapped) + `app/Services/OrderPricingService.php:25-41` (`total = subtotal + vat`, no `max(0, …)`) → a fixed coupon larger than the order yields a **negative total**. Separately, single-use is only recorded on completion (`consumeOrderCoupon`, `OrderService.php:264-276`), so the same coupon can be applied to many concurrent checkouts before one completes; no global redemption cap; `ValidCoupon` checks only the date window.
**Fix:** Clamp discount to the order cost and total to `>= 0`; reserve/lock the coupon at checkout; add active flag + global max-redemptions.

### R2-M4 — `getTransactionStatus()` queries the wrong column ✅
`app/Services/UserTransactionService.php:70-73` — `UserTransaction::where('order_id', $customerReference)->latest()->first()` despite the parameter being a `customer_reference`. Not an IDOR (the caller still enforces ownership at `EasyKashController.php:~218`), but the status lookup is semantically broken and returns the newest transaction for the order rather than the one asked about.
**Fix:** `where('customer_reference', $customerReference)`.

### R2-M5 — Account-enumeration oracle ✅
`app/Http/Controllers/API/AuthController.php:82-86` — `POST /api/check-phone-exists` returns `{"exists": true/false}` unauthenticated. Combined with distinct login/social error messages (`lang/en/auth.php`) it enables cheap enumeration that feeds R2-C1.
**Fix:** Remove the oracle or gate it; return uniform messages on the auth paths.

### R2-M6 — Passport tokens unscoped / long-lived / not rotated ✅
`createToken('authToken')->accessToken` (register/login/social/reset) issues unscoped personal-access tokens; no `tokensExpireIn`/`personalAccessTokensExpireIn` is configured (Passport default ~1 year), and tokens are not revoked on password reset — so an attacker's token from R2-C1/C5 persists.
**Fix:** Set token TTLs, scope tokens, and revoke existing tokens on password reset.

---

## Low / hygiene

### R2-L1 — Old RCE route committed to git ✅
`routes/api.php.bak-20260702-162004` (tracked) still contains `Artisan::call($command)`; `app/Providers/RouteServiceProvider.php.bak-20260702-162004` is likewise committed. Laravel does not load `.bak` files, so the route is **not active**, but committing the vulnerable code is a re-introduction risk and poor hygiene.
**Fix:** `git rm` both `.bak` files; keep backups out of the repo.

### R2-L2 — Dead code with latent IDORs ✅
`app/Http/Controllers/API/OrderOfferController.php` (accept/reject → `OrderOfferService::updateStatus`) changes offer status with no ownership check but is **unrouted** (the live path is the guarded `BiddingOrderArtistController`). `routes/api.php` also wires `support/delete` → `SupportController@destroy`, which does not exist (500 on hit).
**Fix:** Delete `OrderOfferController`/`OrderOfferService` and the dangling `support/delete` route so they can't be wired up later.

### R2-L3 — Leftover placeholder files in web root ✅
`public/default.php` and `public/default.php.old.php` are Hostinger's static placeholder page (harmless HTML despite the `.php` name), plus `public/payment-success.html` / `payment-failed.html`.
**Fix:** Remove the `default.php*` leftovers from `public/`.

### R2-L4 — CORS wildcard ✅
`config/cors.php` — `allowed_origins/methods/headers` are all `['*']`. `supports_credentials => false`, and it applies to `api/*` (bearer-token, no cookies), so browsers won't send credentials — low risk. Tightening origins is defense-in-depth.

### R2-L5 — Missing `$hidden` on transaction models ✅
Only `User` defines `$hidden` (`password`, `remember_token`). `UserTransaction` (`callback_payload`, `easykash_ref`, `email`, `mobile`) and `Transaction` define none — a leak only where a raw model is serialized directly, e.g. `SupportController::store` returns the raw `$support` model (`app/Http/Controllers/API/SupportController.php:31`).
**Fix:** Add `$hidden` to `UserTransaction`/`Transaction`; return resources, not raw models, from `SupportController::store`.

---

## Round 1 re-verification (all hold)

Independently re-checked and **confirmed still correct**: C1 RCE route removed; **H2/H3/H4/H5, M3, M4, M5** order/address IDORs (ownership enforced in `OrderService`/`AddressService`/`BiddingOrderArtistService`); **A1** Filament gate (`User::canAccessPanel()` → `is_admin`) — *but see R2-C4, which undermines the mass-assignment assumption behind it*; **C2** EasyKash GET callback no longer mutates state; **M8** HyperPay `resourcePath` SSRF regex is not bypassable and applied on both paths; HMAC uses timing-safe `hash_equals`; HyperPay *charge* is server-priced; L3 *gallery* upload validation present. No hardcoded secrets, no committed `.env`/credential files, none in git history, no debug dumps, password hashes/OTP never returned in responses.

## Suggested remediation order

1. **R2-C1, R2-C4** — single-request account takeover + global mass-assignment (fix both together; C4 is a one-line removal + tightening `$fillable`).
2. **R2-C2, R2-C3, R2-H6, R2-H7** — payment-integrity sweep (bind amount to server-side order on both rails; strip `is_paid` from create).
3. **R2-C5, R2-M2, R2-M6** — OTP hardening + per-account throttling + token TTL/rotation.
4. **R2-H1, R2-H2, R2-H3, R2-H4** — stop leaking PII / push tokens / Telescope data.
5. **R2-H5, R2-M1, R2-M3..M5, R2-L1..L5** — upload validation, reference strength, coupon clamp, cleanup.
