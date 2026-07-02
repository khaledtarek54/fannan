# Fannan Backend — Security Findings

**Scope:** REST API + admin panel of the Fannan Laravel backend.
**Method:** Each item from the client's "Backend Security Points" brief was verified against the actual source at the baseline commit (`git log` → `baseline: import ...`). Locations are given as `file:line`. Severity follows the brief, adjusted where the code warranted.
**Status legend:** ✅ Confirmed in code · ⚠️ Partially/contextually confirmed · ❌ Not present in this codebase · ☐ Not yet fixed · ☑ Fixed.

## Summary

| ID | Finding | Severity | Verified | Fixed |
|----|---------|----------|----------|-------|
| C1 | `/api/command/{command}` runs arbitrary Artisan commands, no auth | Critical | ✅ | ☑ |
| C2 | EasyKash **GET** callback marks orders PAID with no signature | Critical | ✅ | ☑ |
| H1 | `/api/invoice/download` IDOR | High | ⚠️ never implemented | ☑ built securely |
| H2 | `/api/order/accept` — artist can claim another artist's order | High | ✅ | ☑ |
| H3 | `/api/order/reject` — any user can reject any order | High | ✅ | ☑ |
| H4 | `/api/order/cancel` — any user can cancel any order | High | ✅ | ☑ |
| H5 | `/api/checkout` unauthenticated + checkout has no order ownership | High | ✅ | ☑ |
| M1 | `/api/payments/easykash/status` enumeration | Medium | ✅ | ☑ |
| M2 | `/api/order/status` IDOR | Medium | ⚠️ never implemented | ☑ built securely |
| M3 | `/api/order/offer` — counter-offer on another client's order | Medium | ✅ | ☑ |
| M4 | `/api/address/delete` IDOR | Medium | ✅ | ☑ |
| M5 | `/api/offers/accept` + `/reject` IDOR | Medium | ✅ | ☑ |
| M6 | `/api/easykash/pay` unauthenticated | Medium | ✅ | ☑ |
| M7 | `POST /delete` (web) — delete any account by phone | Medium | ✅ | ☑ |
| M8 | `resourcePath` SSRF + bearer-token leak (HyperPay status/webhook) | Medium | ✅ | ☑ |
| M9 | `/admin/login` no rate limiting (brute force) | Medium | ✅ | ☑ already by framework |
| M10 | Web forms (register/contact/delete) no rate limiting | Medium | ✅ | ☑ |
| M11 | Single shared 60/min API rate limiter | Medium | ✅ | ☑ |
| A1 | Filament admin panel open to any user (`canAccessPanel` = true) | High* | ✅ | ☑ |
| L1 | `apiResource('invoices')` public + missing controller | Low | ✅ | ☑ |
| L2 | Payment webhook has no idempotency/nonce | Low | ✅ | ☑ |
| L3 | Gallery upload — no file type/size validation | Low | ✅ | ☑ |

\* A1 is not in the client brief but was found during review; it is effectively critical for production.

### Remediation status (fixed at commit history `baseline → …`)

All confirmed findings are fixed except **M7**, which is partially mitigated (rate-limited to
6/min) — a full fix needs an OTP/ownership step on the public account-deletion form, which is a
small feature change rather than a pure patch. **M9** required no change: Filament's login page
already throttles to 5/min (`vendor/filament/filament/src/Pages/Auth/Login.php`). **H1/M2** don't
exist in this codebase.

> **Deploy note (A1):** the new `users.is_admin` column defaults to `false`, so after migrating in
> production **no account can reach `/admin`** until you flag your real admin(s):
> `UPDATE users SET is_admin = 1 WHERE email = 'you@example.com';`

---

## Critical

### C1 — Arbitrary Artisan command execution (RCE) ✅
**Location:** `routes/api.php:227-229`
```php
Route::get('/command/{command}', function ($command) {
    Artisan::call($command);
});
```
**Impact:** Unauthenticated. Anyone can hit `GET /api/command/migrate:fresh` to wipe the database, `config:clear`, `cache:clear`, etc. Full remote control of application state. This is the single most dangerous line in the app.
**Fix:** Delete the route entirely. (Ad-hoc command execution belongs behind an authenticated, authorized, allow-listed admin tool — not a public GET.)

### C2 — EasyKash GET callback marks any order PAID without signature ✅
**Location:** `app/Http/Controllers/API/EasyKashController.php:64-81`, `app/Services/UserTransactionService.php:60-84`
The POST/webhook branch verifies an HMAC-SHA512 signature (good), but the **GET redirect** branch trusts `status`, `customerReference`, `easykashRef` straight from the query string and calls `updateFromRedirect()`, which on `status=PAID` sets `is_paid=true` on **both** the `UserTransaction` and its `Order`.
**Impact:** `customer_reference` is `rand(100000, 999999)` (`UserTransactionService.php:14`) — only 900k values, and they are leaked by M1. An attacker crafts `GET /api/easykash/callback?status=PAID&customerReference=<ref>` and marks any order as paid without paying. Direct financial loss.
**Fix:** The GET redirect must **not** mutate payment state. It should only redirect the user; payment state changes belong solely to the signature-verified POST webhook. Remove the `updateFromRedirect` call from the GET branch (or reduce it to a read-only status lookup).

---

## High

### H1 — `/api/invoice/download` IDOR ⚠️ WAS NEVER IMPLEMENTED → ☑ BUILT SECURELY
This feature **did not exist at all** in the delivered codebase — there was no `InvoiceController` and no `/invoice/download` route (only a broken `apiResource('invoices', InvoiceController::class)` pointing at a missing class, since removed in L1). So there was no vulnerable code to patch. **At the client's request it was then built from scratch, securely:** `app/Http/Controllers/InvoiceController.php` + `GET /api/invoice/download` — returns a **downloadable PDF** (via `barryvdh/laravel-dompdf`), restricted to the order's own **client or artist** (403 otherwise, no order_id enumeration) and deliberately **omitting IBAN/bank details** so it cannot leak the other party's private data. Guarded by `tests/Feature/InvoiceDownloadTest.php`.

### H2 — `/api/order/accept`: artist can accept another artist's order ✅
**Location:** `app/Http/Controllers/API/OrderController.php:51-58` → `app/Services/OrderService.php:79-93`
`AcceptOrderRequest` only checks `role == artist`. `acceptOrder()` loads the order by the request's `order_id` and accepts it with no check that `order.artist_id == auth()->id()`.
**Impact:** Artist A accepts/claims orders assigned to Artist B, hijacking business.
**Fix:** In `acceptOrder`, assert the order belongs to the authenticated artist (`abort_unless($order->artist_id === auth()->id(), 403)`), and that it is in an acceptable status.

### H3 — `/api/order/reject`: any authenticated user can reject any order ✅
**Location:** `app/Http/Controllers/API/OrderController.php:69-76`, `app/Http/Requests/Order/RejectOrderRequest.php` (`authorize()` returns `true`), `OrderService::updateStatus()`
No ownership or role check at all. Any valid token can `POST /api/order/reject {order_id}` for **any** order.
**Fix:** Verify the caller is the order's client or assigned artist before allowing rejection; validate the status transition.

### H4 — `/api/order/cancel`: any authenticated user can cancel any order ✅
**Location:** `app/Http/Controllers/API/OrderController.php:87-94`, `OrderIdRequest` (`authorize()` returns `true`), `OrderService::cancel()`
Same class of bug as H3.
**Fix:** Same as H3 — enforce participant ownership + valid status transition.

### H5 — Unauthenticated `/api/checkout` + no ownership on checkout ✅
**Location:** `routes/api.php:244` (`POST /checkout` is **outside** the `auth:api` group), duplicate of `routes/api.php:214` (`/payment/checkout` inside auth). `CheckoutOrderRequest::authorize()` does `auth()->user()->role == ...` (`CheckoutOrderRequest.php:16`).
**Impact:** (a) `POST /api/checkout` is reachable with no token; because `authorize()` dereferences `auth()->user()` it will error/behave unpredictably for anonymous callers and bypasses the intended middleware chain — usable to probe/abuse the payment flow (e.g. card testing). (b) Even authenticated, `PaymentService::checkout()` uses `order_id` from the request without verifying the order belongs to the caller, so Client A can initiate payment for Client B's order.
**Fix:** Delete the public `POST /checkout` and `GET /webhook` duplicates (`routes/api.php:244-245`); keep the authenticated `payment/*` group. Add an ownership check in the checkout path.

---

## Medium

### M1 — EasyKash status enumeration ✅
**Location:** `routes/api.php:30-42` (public closure). Returns a transaction's status/ref/payload for any `customerReference`, unauthenticated. Combined with C2's small reference space, this both **discloses** payment data and **enables** the C2 attack.
**Fix:** Require authentication and scope to the caller's own transactions, or remove the endpoint.

### M2 — `/api/order/status` IDOR ⚠️ WAS NEVER IMPLEMENTED → ☑ BUILT SECURELY
This endpoint **did not exist at all** in the delivered codebase (order routes were index/artist/store/accept/offer/reject/cancel/checkout). The *existing* order-read endpoints were already safe — `GET /api/order` and `/api/order/artist` are scoped by role in `BaseRepository::index` (`checkAuthClient`/`checkAuthArtist`: a client only sees their `client_id` rows, an artist only their `artist_id`), so there was no "view arbitrary orders" leak. **At the client's request the status lookup was then built, securely:** `POST /api/order/status` (`OrderController::orderStatus` → `OrderService::getOrderStatus`) returns status/price/parties **only to a participant** of that order (403 otherwise). Guarded by `tests/Feature/OrderStatusTest.php`.

### M3 — `/api/order/offer`: counter-offer on another client's order ✅
**Location:** `OrderController@offer` → `OrderService::counterOffer()` (`OrderService.php:99-111`). Role-checked as client, but no check the order belongs to the caller.
**Fix:** Assert `order.client_id === auth()->id()` before applying the counter-offer.

### M4 — `/api/address/delete` IDOR ✅
**Location:** `AddressController@destroy` → `AddressService::destroy()` (`AddressService.php:57-60`) — deletes by id with no user scoping.
**Fix:** Only delete addresses where `user_id === auth()->id()` (scope the query or assert ownership).

### M5 — `/api/offers/accept` + `/api/offers/reject` IDOR ✅
**Location:** `BiddingOrderArtistController@accept/reject` → `BiddingOrderArtistService::updateStatus()` (`BiddingOrderArtistService.php:23-50`). Finds the offer by id and changes status with no check the caller owns the parent bidding order.
**Fix:** Verify the offer's order belongs to the authenticated client before accept/reject.

### M6 — `/api/easykash/pay` unauthenticated ✅
**Location:** `routes/api.php:219-225` (outside the auth group). Anyone can create payment transactions/links against any `order_id`.
**Fix:** Move inside `auth:api` and verify the order belongs to the caller.

### M7 — `POST /delete` deletes any account by phone ✅
**Location:** `routes/web.php:23` → `UserController::deleteUserAccount()` (`UserController.php:28-37`). `DeleteUserAccountRequest` only validates that the phone exists; there is no ownership/OTP check.
**Fix (applied):** the deletion form now **requires a verification code** matching the account's stored code (`UserService::deleteAccount`), a `POST /delete-account/send-code` step to (re)generate it, and rate limiting. An account can no longer be deleted with a phone number alone. **Note:** SMS/OTP *delivery* is not wired in this codebase (there is no OTP notification class) — the client must configure a delivery channel; the security check on the code holds regardless of delivery. Guarded by `tests/Feature/AccountDeletionTest.php`.

### M8 — SSRF + bearer-token leak via `resourcePath` ✅
**Location:** `HyperPayService::getPaymentStatus()` (`HyperPayService.php:62-71`):
```php
$url = $this->baseUrl . $request->resourcePath . '?entityId=' . $this->entityId;
$response = Http::withHeaders(['Authorization' => 'Bearer ' . $this->accessToken])->get($url);
```
`resourcePath` is unvalidated user input concatenated onto the base URL, and the **HyperPay access token** is attached to the outbound request. A crafted `resourcePath` (path traversal / `@host` / userinfo tricks) can redirect the request — and thus leak the bearer token — to an attacker-controlled host. Reachable via `POST /api/payment/status` and, worse, `GET /api/webhook` (`routes/api.php:245`, unauthenticated).
**Fix:** Validate `resourcePath` against an allow-list/format (must match HyperPay's `^/v1/checkouts/[\w-]+/payment$`), or store & look up the resourcePath server-side from the checkout record instead of trusting the client.

### M9 — `/admin/login` has no rate limiting ✅
**Location:** Filament login route; no throttle applied. The `api` limiter does not cover web/Filament routes.
**Status:** Already mitigated — Filament's Login page throttles to 5 attempts/min out of the box (`rateLimit(5)` in `vendor/filament/filament/src/Pages/Auth/Login.php`). No change required for this version.

### M10 — Public web forms have no rate limiting ✅
**Location:** `routes/web.php` — `artist-store` (25), `contact-store` (21), `delete` (23). No throttle → spam/abuse.
**Fix:** Apply `throttle:` middleware to these POST routes.

### M11 — One shared 60/min API limiter ✅
**Location:** `app/Providers/RouteServiceProvider.php:27-29`. Login, payments, and browsing share a single 60/min bucket keyed by user/IP, so ordinary browsing can lock a user out of login/payment, and login/payment lack their own stricter limits.
**Fix:** Add named limiters (e.g. tighter `login`, `payment`) and apply per route group.

---

## Additional (found in review)

### A1 — Admin panel open to every authenticated user ✅ (High)
**Location:** `app/Models/User.php:70-75` — `canAccessPanel()` returns `true`; no admin role exists.
**Impact:** Any client/artist (or anyone who registers) can log in at `/admin` and use every Filament resource — full read/write over users, orders, payments, settings.
**Fix:** Introduce an admin concept (an `is_admin` flag or an admin role/guard) and gate `canAccessPanel()` on it. Seed the legitimate admin account(s).

---

## Low

### L1 — `apiResource('invoices')` public & missing controller ✅
**Location:** `routes/api.php:70`. Registers CRUD routes with no auth, pointing at a non-existent `InvoiceController` (500 on hit). If someone later implements it, the endpoints are publicly writable.
**Fix:** Remove the route (or implement the controller behind `auth:api` + ownership) — don't leave a public resource wired to a missing class.

### L2 — No webhook idempotency/nonce ✅
**Location:** EasyKash/HyperPay webhook handlers. A valid callback can be replayed; combined with M8 this enables replay attacks.
**Fix:** Persist a processed-callback marker (e.g. `easykash_ref`/checkout id) and ignore duplicates.

### L3 — Gallery upload accepts any file type/size ✅
**Location:** `app/Http/Requests/Gallery/StoreArtistGalleryRequest.php` — `video` is `required` with no `file`/`mimetypes`/`max` rules.
**Fix:** Validate uploads with `file|mimetypes:...|max:...` and store outside the web root / on a dedicated disk.

---

## Suggested remediation order

1. **C1, C2** — immediate (RCE + payment bypass).
2. **A1, H2-H5** — before any further production exposure (admin lockdown + order/payment ownership).
3. **M1, M3-M8** — IDOR/enumeration/SSRF sweep.
4. **M9-M11, L1-L3** — rate limiting, cleanup, hardening.
