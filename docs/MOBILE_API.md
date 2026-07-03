# Fannan — Mobile API Reference

A practical reference for the mobile developer integrating the Fannan app. Fannan is an art/talent
marketplace: **clients** book **artists** via **direct orders** and **bidding orders**.

- **Base URL:** `https://apps.fannan.ai/api` (this is `APP_URL` + `/api`; use your own env for staging/local)
- **Auth:** Laravel Passport — **Bearer token** (`Authorization: Bearer <token>`)
- **Framework:** Laravel 10 REST/JSON

> **Read [Conventions](#conventions) and [Known issues & gotchas](#known-issues--gotchas) first.** This
> API has a few sharp edges (two different response envelopes, OTP codes that aren't actually delivered,
> one broken route). Knowing them up front will save you days.

---

## Table of contents

1. [Conventions](#conventions) — auth, headers, response envelopes, errors, rate limits, localization, OTP
2. [Auth](#auth)
3. [Profile & account](#profile--account)
4. [Public artist](#public-artist)
5. [Orders](#orders)
6. [Invoice, order status & coupons](#invoice-order-status--coupons)
7. [Bidding orders & offers](#bidding-orders--offers)
8. [Ratings](#ratings)
9. [Payments](#payments)
10. [Chat](#chat)
11. [Notifications](#notifications)
12. [Wallet & transactions](#wallet--transactions)
13. [Addresses](#addresses)
14. [Support](#support)
15. [Gallery](#gallery)
16. [Home & catalog](#home--catalog)
17. [Known issues & gotchas](#known-issues--gotchas)
18. [Recommendations for the mobile team](#recommendations-for-the-mobile-team)

---

## Conventions

### Authentication & tokens

- Register or log in → you receive a Passport access token in **`data.token`**.
- Send it on every authenticated request: `Authorization: Bearer <token>`.
- Missing/invalid token on a protected route → **401**.

### Standard headers

| Header | Value | Notes |
|---|---|---|
| `Authorization` | `Bearer <token>` | Required on `auth:api` routes |
| `Accept` | `application/json` | Forces JSON error bodies (recommended globally) |
| `Content-Type` | `application/json` | Or `multipart/form-data` for file uploads |
| `lang` | `ar` or `en` | Controls the response language. **Default is `ar`.** |

### ⚠️ Two response envelopes (important)

This API is **not** uniform. There are two shapes — check per endpoint.

**1. The `sendResponse` / `sendError` envelope** (used by auth, profile, notifications, catalog):

```jsonc
// success
{ "success": true,  "data": <payload>, "message": "done", "errors": null }
// error
{ "success": false, "data": null,      "message": "validation_failed", "errors": <errors> }
```

> Quirk: `sendError` defaults to **HTTP 200** unless a code is passed. Don't rely solely on the HTTP
> status for these — also check the `success` boolean.

**2. Ad-hoc envelopes** (used by orders, bidding, chat, address, gallery, wallet, home): hand-built
JSON with a top-level `status` boolean and a custom payload key (`orders`, `chats`, `addresses`,
`works`, `data`, …). **Each endpoint below states its exact shape.** Write a small response adapter that
tolerates both.

### Error status codes

| Code | Meaning in this API |
|---|---|
| **401** | No/invalid token, **or** the account is soft-deleted (`DeleteAccount` middleware) |
| **400** | Business rule failed, or profile incomplete (`CompleteProfileMiddleware` — user not `is_verified`) |
| **403** | Ownership/role check failed (wrong role, or not the owner of the resource) |
| **404** | Resource id not found |
| **422** | Laravel validation failure → `{ "message": "...", "errors": { "field": ["..."] } }` |
| **429** | Rate-limited (see below) |

### Rate limits

| Limiter | Limit | Applies to |
|---|---|---|
| `api` (global) | **120 / min** per user (or IP) | all `/api` routes |
| `auth` | **30 / min** per IP | register, login, social, verification, send-code, password/update, check-phone |
| `payment` | **30 / min** per user/IP | `POST /easykash/pay` |

On 429, back off and retry (respect `Retry-After`).

### Localization

Send a `lang: en` (or `ar`) header. Server-side status labels (`status_text`) and messages are localized
from it. Default is `ar`.

### OTP / verification code

- The code is a **4-digit** number stored on `users.verification_code`.
- In the **local** environment it is hard-coded to **`1234`**; in production it's random `0000–9999`.
- **🚨 The backend generates and checks the code but does NOT currently send it** (there's no SMS/OTP
  gateway wired — the send call is commented out). See [Known issues](#known-issues--gotchas) #2. Until an
  SMS gateway is configured, OTP-gated flows (verify, password reset, account delete) can't complete for
  real users. Coordinate with the backend team.

### Common middleware (what can block a request before the controller runs)

- **`auth:api`** → 401 if not authenticated.
- **`DeleteAccount`** → 401 `{message, status:false}` if the account is soft-deleted.
- **`CompleteProfileMiddleware`** → 400 `{message, status:false}` if the user's `is_verified` is false
  (i.e. profile not completed). Applies to orders, bidding, offers, rating, payment groups.

---

## Auth

All under `throttle:auth` (30/min per IP). `Content-Type: application/json`. No token needed except
`update/fcm_token`.

### `POST /register`
Create an account and get a token immediately.

| Field | Type | Req | Notes |
|---|---|---|---|
| `name` | string | ✔ | min 3, max 100 |
| `phone_prefix` | string | ✔ | e.g. `+966` |
| `phone` | string | ✔ | unique |
| `role` | string | ✔ | `client` or `artist` |
| `password` | string | ✔ | hashed server-side |
| `fcm_token` | string | ✖ | push token |

**Response** (`sendResponse`, message `created`): `data = { user: <UserResource>, status: true, token: "<passport token>" }`.
New users start `is_verified = false`, `completed_profile = false`.

### `POST /login`
| Field | Type | Req |
|---|---|---|
| `phone` | string | ✔ |
| `password` | string | ✔ |
| `fcm_token` | string | ✖ |

**Response:** `sendResponse` with `data = { user, token, status:true }`, message `done`.
**Errors (HTTP 400):** `block_account` (soft-deleted), `password_wrong`.

### `POST /login-social` **and** `POST /social/login`
Same handler. **Only `email` is checked — no password.**

| Field | Type | Req |
|---|---|---|
| `email` | string | ✔ (must exist) |

**Response:** `data = { user, token, status:true }`. **Errors (400):** `not_verified`, `block_account`.
> Security note: presence of a verified, non-deleted account with that email yields a token. Make sure your
> social sign-in only calls this after a verified provider (Google/Apple) handshake.

### `POST /verification/check`
Confirm the OTP → sets `is_verified = true`.

| Field | Type | Req |
|---|---|---|
| `phone` | string | ✔ |
| `verification_code` | string/int | ✔ (`1234` in local) |

**Response:** `sendResponse` with `data = <UserResource>`. **Wrong code → 400 `wrong_code`.**

### `POST /send/code`
Re-issue the OTP. `{ phone }`. Returns the user. **(Does not actually send the SMS — see gotcha #2.)**

### `POST /password/update` — ⚠️ now requires the code
| Field | Type | Req | Notes |
|---|---|---|---|
| `phone` | string | ✔ | |
| `password` | string | ✔ | min 6 (the new password) |
| `verification_code` | string/int | ✔ | **must equal the user's stored OTP** |

**Response:** `data = { user, token }`. **A missing/wrong `verification_code` → HTTP 403 `wrong_code`.**
This closed an account-takeover hole — the app **must** send `verification_code` here now.

### `POST /check-phone-exists`
`{ phone }` → `sendResponse` `data = { exists: true|false }`.

### `POST /update/fcm_token` (auth required)
`{ fcm_token }` → updates the token on the authenticated user. Response `data: []`.

---

## Profile & account

All require `auth:api` + `DeleteAccount`.

### `POST /client/complete/profile` and `POST /client/update`
Same handler. `Content-Type: multipart/form-data` (optional `profile_photo` file).

| Field | Type | Req | Notes |
|---|---|---|---|
| `name` | string | ✔ | |
| `phone` | string | ✔ | unique except self |
| `email` | string | ✔ | unique except self |
| `dob` | date | ✔ | Arabic digits are auto-converted |
| `gender` | string | ✔ | `male`/`female` |
| `city_id` | int | ✔ | |
| `city` | string | ✔ | |
| `latitude` / `longitude` | numeric | ✔ | |
| `phone_prefix` | string | ✖ | |
| `vat_number` / `cr_number` | string | ✖ | digits, 1–16 |
| `facebook`/`instagram`/`youtube`/`snapchat`/`whatsapp` | string | ✖ | |
| `start_date` / `end_date` | array | ✔ *for artists* | availability blocks (paired by index) |
| `profile_photo` | file | ✖ | image |

**Effect:** sets `completed_profile = true`, `is_verified = true`; (artists) rebuilds availability dates;
sends a welcome email. **Response:** `sendResponse` `data = { user: <UserResource>, status:true }`.

### `GET /client/profile`
`sendResponse` `data = <ClientResource>`.
> Bug to be aware of: `vat_number` and `cr_number` in `ClientResource` currently echo the user's **name**,
> not the real numbers (gotcha #4).

### `GET /delete-account`
Soft-deletes the account and frees the phone number (appends `-deleted-<ts>`). Response `data: true`.
Afterward the token still authenticates but `DeleteAccount` middleware rejects further calls with 401.

### `GET /artist/all-nearby`
Bare shape (not `sendResponse`):
```json
{ "status": true, "artists": [
  { "id": 7, "name": "DJ Nova", "profile_image": "https://.../x.jpg", "category": ["Music"],
    "address": "Jeddah", "latitude": 21.48, "longitude": 39.19, "distance_km": 12.34 } ] }
```
Only `role=artist` + `completed_profile=true`. `distance_km` = Haversine from the caller's lat/lng (`0` if either side lacks coords).

### `POST /artist/delete-account`
Soft-deletes the authenticated **artist**. Response `data: true`.

### `POST /artist/categories/update` (role: artist → else 403)
```json
{ "categories": [ { "category_id": 2, "subcategory_id": 9, "range_id": 3 } ] }
```
Replaces the artist's categories. Response `data: true`.

### `GET /artist/profile`
`sendResponse` `data = <UserResource>` for the authenticated artist.

---

## Public artist

No auth.

### `GET /artist`
```json
{ "status": true, "artists": [
  { "id":7, "name":"DJ Nova", "profile":"https://.../x.jpg", "from_range":500, "to_range":1500,
    "created_at":"2026/06/01 09:15:30 AM", "average_rate":4.5, "rates":12, "categories":["Music"] } ] }
```
Only `completed_profile=true` artists.

### `POST /artist/id`
`{ user_id }` (must exist) →
```json
{ "status": true, "artist": {
  "id":7, "name":"DJ Nova", "profile":"https://.../x.jpg", "from_range":500, "to_range":1500,
  "categories":["Music"], "completed_events":8, "average_rate":4.5, "rates":12,
  "works":[ /* gallery items */ ], "videos_count":2, "images_count":5, "completed_profile":true,
  "facebook":null, "instagram":null, "youtube":null, "snapchat":null, "whatsapp":null } }
```

---

## Orders

Group middleware: `auth:api` + `DeleteAccount` + `CompleteProfileMiddleware`.

**Order lifecycle statuses** (`data.status`): `artist_pending`, `new`, `accepted`, `completed`,
`rejected`, `in_payment`, `counter_offer`, `pending`, `canceled`.

**Typical flow:** create → `artist_pending` (shown to the artist as `new`) → client counter-offer surfaces
as `counter_offer` → artist accepts → `accepted` → client checks out → `in_payment` → after payment and
once the booking dates pass, a scheduled job settles + completes it → `completed`.

### `GET /order`
Returns the authenticated **client's** orders. Shape: `{ "orders": { "data": [ <OrderResource> ] }, "status": true }`.

`OrderResource` (key fields):
```json
{
  "id":42, "client_id":7, "artist_id":15, "number":"ORD-000042", "type":"direct",
  "artist_name":"Layla Q.", "city":"Riyadh", "latitude":"24.71", "longitude":"46.67",
  "address_name":"Home", "address_description":"Villa 3", "description":"Wedding henna",
  "average_rate":4.5, "rates":12, "status":"accepted", "status_text":"Accepted", "status_reason":null,
  "cost":500, "is_complete":false, "is_paid":false, "has_rating":null,
  "artist":{ /* ArtistResource */ }, "categories":[ /* OrderCategoryResource */ ],
  "dates":[ {"start_date":"2026-08-01","end_date":"2026-08-01","start_time":"18:00","end_time":"22:00"} ],
  "offers":{ "id":5, "order_id":42, "cost":500, "counter_to":450 }, "bidding_offers":[],
  "days_count":1, "hours_count":4, "create_at":"2026/Jul/01 14:30:05 PM",
  "image":"https://apps.fannan.ai/images/image.png"
}
```
- `status` shows `counter_offer` when `artist_pending` **and** a client offer exists.
- `is_complete` is **time-based** (last booking date passed) — distinct from the lifecycle status.
- `offers` = the **last** offer only. `image` is always a static placeholder.

### `GET /order/artist`
Same envelope, items are `ArtistOrderResource` (adds `client`, omits `average_rate`/`rates`/`artist`/`has_rating`;
`bidding_offers` filtered to this artist). For an artist, `artist_pending` renders as **"New"**.

### `POST /order/store` (role: client → else 403)
```json
{
  "artist_id": 15,
  "subcategories": [9],
  "dates": [ { "start_date":"2026-08-01","end_date":"2026-08-01","start_time":"18:00","end_time":"22:00" } ],
  "address_id": 5,
  "description": "Wedding henna session"
}
```
Creates the order (`artist_pending`), notifies the artist. Response `{ "order": <OrderResource>, "status": true }`.
> Note: an artist double-booking check exists but is **commented out** — overlapping dates aren't rejected (gotcha #10).

### `POST /order/accept` (role: artist; **only the assigned artist** → else 403)
`{ order_id, cost }` (`cost` ≥ 0). Sets status `accepted`, notifies client. Response `{ message, status:true }`.

### `POST /order/offer` — client counter-offer (role: client; **only the order's client** → else 403)
`{ order_id, cost }`. Creates an `OrderOffer`, status becomes `counter_offer`, notifies artist.
Response returns the raw Order model: `{ "data": { ...order... }, "status": true }`.

### `POST /order/reject` (participant only → else 403)
`{ order_id, reason? }` (`reason` min 3 if given). Sets status `rejected`.

### `POST /order/cancel` (participant only → else 403)
`{ order_id }`. Sets status `canceled`, notifies the artist. Response `{ message, status:true }`.

### `POST /order/checkout` — the price quote (role: client; **order's client only** → else 403)
`{ order_id, code? }` (`code` = coupon, validated by `ValidCoupon`).

```json
{ "data": { "cost":500, "tax":25, "vat":26.25, "discount":50, "total_cost":501.25, "applied_coupon":true },
  "status": true }
```

**How the total is computed** (`OrderPricingService`, rates from the `settings` table):
1. `tax = cost × taxRate%`
2. `subtotal = cost + tax − discount`
3. `vat = subtotal × vatRate%` (VAT on the post-tax, post-discount subtotal)
4. `total_cost = subtotal + vat`

**This quote is the single source of truth — the amount you show here is exactly what the customer is
charged** (the payment step uses the same service; VAT is intentionally included per KSA rules).
Sets order → `in_payment`. **The coupon is only *applied* here (stored on the order); it is *consumed*
only when the order completes** — a quote that never gets paid does not burn the coupon.

---

## Invoice, order status & coupons

These run under `auth:api` + `DeleteAccount` (no `CompleteProfileMiddleware`).

### `GET | POST /invoice/download` (participant only → else 403)
`order_id` as query or body. **Returns a streamed PDF** (`Content-Type: application/pdf`), not JSON.
> The PDF template hardcodes its own tax (15%) / VAT (0%) / currency (EGP) for line items — this is
> template math, independent of the live checkout pricing (gotcha #9).

### `GET /orders` — paginated order list (client's orders)
Query: `per_page` (default 15), `page` (default 1).
```json
{ "success": true,
  "data": [ { "id":42, "status":"PAID", "artist_name":"Layla Q.", "total_price":450 } ],
  "pagination": { "current_page":1, "per_page":15, "total":3, "last_page":1, "from":1, "to":3 } }
```
Here `status` is the **payment** status (`PAID`/`PENDING`/`FAILED`/…). `total_price = cost − coupon_amount`.

### `GET | POST /order/status` (participant only → else 403)
`order_id` as query or body.
```json
{ "success": true, "data": {
  "status": "accepted",          // ORDER LIFECYCLE status
  "payment_status": "PENDING",   // PAID / PENDING / FAILED / CANCELLED / UNKNOWN
  "artist_name": "Layla Q.",
  "total_price": 450 } }
```
> Use `data.status` for the order state and `data.payment_status` for payment. (This endpoint's shape was
> recently changed to return the lifecycle status; it now accepts GET **and** POST.)

### `POST /coupon/check-coupon`
`{ code }` (validated by `ValidCoupon`: must exist and be inside its date window).
- Valid & unused → `{ "code": { "id":3, "type":"percentage", "amount":10, "code":"WELCOME10", ... }, "status": true }`
- Already used by this user → **422** `{ "code": "<already-used msg>", "status": true }`

`type` is `fixed` (flat) or `percentage`. This only checks validity — it doesn't apply or consume the coupon.

---

## Bidding orders & offers

Group middleware: `auth:api` + `DeleteAccount` + `CompleteProfileMiddleware`.

**Flow:** client posts a bidding order → artists discover it (`/bidding-order/available`) → artists bid
(`/bidding-order/send-offer`) → client lists bids (`/offers`) → client accepts one (`/offers/accept`) →
accepted artist fulfills → client rates.

### `GET /bidding-order`
Role-scoped: a **client** sees their own orders (`client_id = me`); an **artist** sees direct orders assigned
to them (`artist_id = me`) — **not** bidding orders they bid on. Paginated `BiddingOrderResource` under `orders`:
```json
{ "orders": { "data": [ {
  "id":42,"client_id":7,"client":{/*ClientResource*/},"name":"Wedding band","number":"B1042","type":"bidding",
  "city":"Riyadh","description":"4-piece band, 3h","status":"pending","status_text":"Pending","cost":"1500",
  "categories":[/*OrderCategoryResource*/],"dates":[...],"offers":null,
  "bidding_offers":[/* only MY bids on this order */],"days_count":3,"hours_count":9,"is_paid":0,
  "create_at":"2026/Jul/02 14:30:11 PM","image":"https://.../images/image.png"
} ], "meta": {...} }, "status": true }
```

### `POST /bidding-order/id`
`{ order_id }` → single `BiddingOrderResource`. **No ownership check** — any authenticated user can fetch any order by id (gotcha #6).

### `POST /bidding-order/store` (role: client → else 403)
```json
{
  "name": "Wedding band needed",
  "dates": [ { "start_date":"2026-08-01","end_date":"2026-08-01","start_time":"18:00","end_time":"21:00" } ],
  "address_id": 5,
  "description": "Need a 4-piece band",
  "talents": [ { "subcategory_id": 3, "has_budget": 1, "budget": 1500 } ]
}
```
Creates a `bidding` order (`number` = `B…`, status `pending`), expands dates to per-day rows, one category per talent.
Response `{ "order": <BiddingOrderResource>, "status": true }`.

### `POST /bidding-order/send-offer` — artist places a bid (role: artist → else 403)
`{ order_id, subcategory_id, cost }` (`cost` ≥ 0). Creates a `BiddingOrderArtist` (`is_accepted:0`, `pending`),
notifies the client.
- Rejected (**400**) with `already_has_offer` if the artist already has a pending bid for that (order, subcategory).
- Rejected (**400**) with `order_subcategory_accepted` if some bid for that (order, subcategory) is already accepted.
> Quirk: the failure body still says `"status": true` (gotcha #8) — branch on the `message`, not `status`.

### `GET /bidding-order/available` — artist discovery feed
Optional `city_id` (single `"5"` or JSON `"[5,8]"`). Returns open bidding orders the artist hasn't bid on and
that aren't accepted yet, **paginated 10/page**, under `biddings`.

### `GET /offers`
An "offer" = one artist's bid. Role-scoped: an **artist** sees their own bids; a **client** sees **all** offers
unless they pass `filter[order_id]=<their order>` (gotcha #5 — always pass the filter as a client). Paginated
`BiddingOrderOfferResource` under `offers`:
```json
{ "offers": { "data": [ {
  "id":91,"cost":"1200","status":"pending","status_text":"Pending","artist_id":15,"artist":{/*UserResource*/},
  "average_rate":4.5,"rates":12,"is_accepted":0,
  "subcategory":{"id":3,"name":"Guitarist","category_id":1,"category_name":"Music"},
  "subcategory_name":"Guitarist","category_name":"Music","offer_rate":null,"created_at":"2026-Jul-02 02:40 PM"
} ], "meta": {...} }, "status": true }
```

### `POST /offers/accept` (role: client; **owning order's client only** → else 403)
`{ offer_id }`. Sets that offer `accepted`, **auto-rejects competing pending bids** for the same (order,
subcategory), notifies the winner. Response `{ "message":"Success", "status":true }`.

### `POST /offers/reject` (role: client; owner only → else 403)
`{ offer_id }`. Sets the offer `rejected`, notifies the artist. Response `{ "message":"Done", "status":true }`.

---

## Ratings

### `POST /rating/store` (role: client → else 403)
Review an artist. **Review-only — it does NOT credit any wallet** (artists are paid on order completion, not
on rating).

| Field | Type | Req | Notes |
|---|---|---|---|
| `stars` | int | ✔ | 1–5 |
| `notes` | string | ✖ | 1–255 |
| `offer_id` | int | ✖* | rate an accepted **bidding offer** |
| `order_id` | int | ✖* | rate a **direct order** |

\* Send **exactly one** of `offer_id` / `order_id` (server derives `model_type`/`model_id`).

**Rules:** owner only (403 otherwise); the bid must be **accepted** / the order must be **complete**; **one
rating per target per client** (duplicate → `400 { status:false, message:"cannot_rate_order" }`).
Success → `{ "status": true, "message": "Done" }`.

---

## Payments

Two gateways exist. **HyperPay (SAR, COPYandPAY) is the primary path for the mobile app.** EasyKash (EGP)
is a secondary gateway.

### Payment lifecycle (HyperPay) — read this

1. **Initiate** — `POST /payment/checkout` with `order_id` + `payment_method` (+ optional coupon `code`).
   Only the order's client may call it; the amount is computed server-side (== the `/order/checkout` quote).
2. **Receive** — `{ status:true, id:"<checkoutId>", link:"<hosted widget URL>", message:"Success" }`.
3. **Render** — open `link` in a webview; the user completes card / mada / Apple Pay. HyperPay redirects the
   browser to the return URL (`GET /api/webhook`).
4. **Confirm** — **do not trust the redirect.** Confirm the final state by polling `POST /payment/status`
   (check `status_string == "Paid"`) and/or reading `GET /order/status` (`payment_status == "PAID"`). The
   order's `is_paid` is only ever flipped by the server-verified webhook path — **never** by a redirect.

> **Security model:** the unsigned browser return/redirect never marks anything paid. Only a verified path
> does (HyperPay server-side confirmation via `/payment-webhook`, or EasyKash's HMAC-signed POST callback).
> The app must confirm "paid" via a status call, not by landing on a success page.

### `POST /payment/checkout` (role: client; order's client only → else 403)
| Field | Type | Req | Notes |
|---|---|---|---|
| `order_id` | int | ✔ | must be the caller's order |
| `payment_method` | string | ✔ | `mada`, `visa`, or `apple_pay` |
| `code` | string | ✖ | coupon |

**Response (200):** `{ "status":true, "id":"<checkoutId>", "link":"https://.../payment_links/xxx.html", "message":"Success" }`.
`id` is the `checkoutId` you pass to `/payment/status`; `link` is the hosted HyperPay widget page.
**Idempotent:** if already paid, returns `{ status:false, message:"paid_done" }`.

### `POST /payment/status` — poll result (read-only, does not flip state)
| Field | Type | Req | Notes |
|---|---|---|---|
| `resourcePath` | string | ✔ | HyperPay path; strictly validated `^/v1/checkouts/[A-Za-z0-9._-]+/payment$` |
| `id` | string | ✖ | checkoutId (echoed back) |
| `payment_method` | string | ✖ | default `card` |

Paid → `{ status:true, status_string:"Paid", message:"Payment success", checkoutId }`; otherwise `status:false`
+ `status_string:"Not paid"` (400).

### `POST /payment-webhook`
Same fields as `/payment/status`. On a success result it looks up the transaction by `checkoutId` and flips
`order.is_paid = true`, status → `accepted`. **This is the only HyperPay path that marks an order paid.** Has
idempotency guards.

### `GET /webhook` (public) — HyperPay shopper return URL
Same handler as the webhook; `resourcePath` is strictly allow-listed (SSRF hardening). This is where the
webview lands after payment.

### `POST /easykash/pay` (auth + `throttle:payment` 30/min)
| Field | Type | Req |
|---|---|---|
| `order_id` | int | ✔ (must exist; 400 if already paid) |
| `amount` | numeric | ✔ |
| `name` | string | ✔ |
| `email` | string | ✔ |
| `mobile` | string | ✔ |
Returns the EasyKash DirectPay link JSON (EGP). Writes a pending `UserTransaction` with a random
`customer_reference`.

### `POST /easykash/status` (auth; **own transaction only** → else 403)
`{ customer_reference }` **or** `{ checkout_id }` → the stored status (`is_paid`, `amount`, `easykashRef`, …).
404 if not found. Read-only.

### `GET | POST /easykash/callback` (public)
- **GET** (browser return): **never mutates state** — redirects to `/payment-success.html` or
  `/payment-failed.html` based on the stored `is_paid`.
- **POST** (gateway webhook): HMAC-SHA512-signed; verifies the signature, then updates payment state on
  `status=PAID`. Invalid signature → 401. Idempotent (skips if already paid).

---

## Chat

`ChatResource` (used by both list and thread) — each row carries **both** sides; decide "me vs them" by
comparing `from_user_id`/`to_user_id` to your user id:

| field | notes |
|---|---|
| `id`, `from_user_id`, `from_user_name`, `from_user_profile` | sender (profile is a full URL / fallback logo) |
| `to_user_id`, `to_user_name`, `to_user_profile` | recipient |
| `type` | `text` or `file` |
| `message` | text, or a **Storage URL** when `type=file` |
| `is_read`, `reply_to`, `reply` | `reply` is the replied-to row (or null) |

### `GET /chat` — conversation list (latest message per partner)
`{ "chats": [ <ChatResource> ], "status": true }` (newest-first, no pagination).

### `POST /chat/details` — full thread with one partner
`{ to_user_id }` → `{ "chat": [ <ChatResource> ], "status": true }`.

### `POST /chat/store` — send a message
`{ to_user_id, type, message, reply_to? }`. For `type=file`, upload a file (stored, returned as a URL);
for `type=text`, send text. `from_user_id` is forced to you; recipient is notified. Response `{ status:true, message:"done" }`.

---

## Notifications

Uses the `sendResponse` envelope. A notification matches you if `user_id = me` OR `to_user_id = me`.

### `GET /notifications`
`data = [ <NotificationResource> ]`:
```json
{ "id":44, "user_id":3, "type":"order", "title":"New offer", "body":"You received a new offer",
  "model_type":"order", "model_id":88, "is_read":0, "order": { /* OrderResource | BiddingOrderResource | null */ } }
```
`order` is populated only for order-typed notifications. **This endpoint does not mark anything read.**

### `POST /notifications/mark-read`
No body. Bulk-marks **all** your unread notifications read → `data: true`.

### `GET /notifications/unread-count`
`data = { "unread_count": 5 }`.

---

## Wallet & transactions

**Balance model:** `total_income` (sum of `income` rows) − `total_withdraw` (sum of **all** `withdraw` rows,
pending + completed) = **available balance** (`net_amount`).

### `GET /transactions`
```json
{ "data": {
  "transactions": [ { "id":12,"user_id":3,"type":"income","amount":"150.00","model_type":"order","model_id":88,
                      "is_completed":1,"created_at":"...","updated_at":"...","deleted_at":null } ],
  "total_income": 900, "total_withdraw": 300, "net_amount": 600 }, "status": true }
```
`transactions` is the current page (default 25). Supports `filter[type]`, `sort`, `perPage`, `page`.

### `POST /transactions/request` — artist withdrawal request (role: artist → else 403)
`{ amount }` (≥ 0). If `amount > net_amount` → rejected. Success → `{ message:"done", status:true }`.
> Quirk: the insufficient-balance response is HTTP **400** but the body still says `"status": true` (gotcha #8).

---

## Addresses

### `GET /address`
`{ "addresses": [ { "id":5,"user_id":3,"city_id":2,"city_name":"Riyadh","name":"Home","description":"Villa 12",
  "latitude":"24.71","longitude":"46.67","created_at":"2026-06-30 10:00 AM" } ], "status": true }`.

### `POST /address/store` (role: client → else 403)
`{ city_id, name, latitude, longitude, description? }`. **The server reverse-geocodes lat/lng via Google to
resolve the city and stores that `city_id`** (the sent `city_id` is effectively overridden). If geocoding
fails, no address is created: `{ status:true, message:"address_error_try_again" }`. Success → `{ address:{...}, status:true }`.

### `POST /address/delete` (role: client; **owner only** → else 403)
`{ address_id }`. Deletes only if it belongs to you. Response `{ message:"done", status:true }`.

### `POST /address/reverse-geocode` (public) — 🚫 **BROKEN**
The route is registered but **the controller method doesn't exist** — calling it errors (gotcha #7). Don't use
it; rely on `/address/store`'s internal geocoding.

---

## Support

Under `auth:api` + `DeleteAccount`.

### `GET /support`
`{ "data": [ <SupportResource> ], "status": true }` — fields: `id, user_id, reply_user_id, reply_user_name,
name, phone, email, description, model_type, model_id, created_at`.

### `POST /support/create`
| Field | Type | Req | Notes |
|---|---|---|---|
| `type` | string | ✔ | `general`, `direct_order`, `bidding_order` |
| `description` | string | ✔ | |
| `order_id` | int | ✔ if order type | linked order |
| `name`/`phone`/`email` | string | ✔ if `general` | |
Response `{ data:{...}, status:true }`.

### `POST /support/delete`
`{ support_id }`. Deletes by id — **no owner check** (gotcha #6).

---

## Gallery

Artist portfolio. Under `auth:api` + `DeleteAccount`; create/update/delete require **role: artist** (else 403).

`GalleryResource`: `{ id, type: "image"|"video", is_pin, video }` (the media path/URL is in `video`, even for images).

### `GET /gallery`
`{ "status": true, "works": [ { "id":1,"type":"image","is_pin":1,"video":"gallery/abc.jpg" } ] }`.

### `POST /gallery/create` — `multipart/form-data`
| Field | Type | Req | Notes |
|---|---|---|---|
| `video` | file or string | ✔ | the media |
| `type` | string | ✔ | `image` / `video` |
| `is_pin` | int | ✔ | `1` / `0` |

**Upload limits when `video` is a file:** mimetypes `image/jpeg`, `image/png`, `image/webp`, `video/mp4`,
`video/quicktime`; **max 50 MB**. Response `{ status:true, message:"done" }`.

### `POST /gallery/update` — `multipart/form-data`
Same fields + `gallery_id` (must belong to you — validated). Deletes the old file, stores the new one.

### `POST /gallery/delete`
`{ gallery_id }` (owner-scoped — another artist's id fails validation with 422). Deletes the file + record.

---

## Home & catalog

### `GET /home` (public)
`{ "unread_notifications": 0, "ads": [ <AdResource> ], "top": [ <HomeArtistResource> ], "latest": [ ... ] }`.

### `GET /artist/home` (auth)
`{ "unread_notifications": 3, "ads": [...], "orders": [ <ArtistOrderHomeResource> ], "biddings": [ <BiddingOrderResource> ] }`.

### `GET /categories` (public)
`sendResponse` envelope; `data` = paginated `CategoryResource` (`{ id, name, photo }`), 10/page.

### `GET /settings` (public)
`sendResponse`; `data = { whatsapp, privacy_policy, terms_and_conditions, about_us }`.

### `GET /price-ranges` (public)
`sendResponse`; `data = [ { id, from, to } ]`.

### `GET /artist-acknowledgement` (public)
`sendResponse`; `data` = a plain string (or null).

### `GET /cities` (public)
**Raw** shape (no envelope): `{ "cities": { "1": "Riyadh", "2": "Jeddah" } }` (id → name map).

### `GET /update-lang` (auth)
Reads the **`lang` HTTP header** and saves it on the user. Response `{ status:true, message:"done" }`.

---

## Known issues & gotchas

These are real behaviors in the current code — build around them, and raise the ones that matter with the
backend team.

1. **Two response envelopes.** Some endpoints return `{success,data,message,errors}`, others return ad-hoc
   `{status:true, <key>:...}`. Write one response adapter that handles both. (`sendError` can even return
   HTTP 200 — check the boolean, not just the status.)
2. **OTP is generated but not delivered.** No SMS/OTP gateway is wired, so verify / password-reset /
   account-delete codes never reach the user in production. In **local** the code is always `1234`. Until the
   gateway exists, those flows can't complete end-to-end. **(Backend coordination item.)**
3. **`verification_code` is now required** on `POST /password/update` (403 without) and on the public web
   account-deletion form. Make sure the app sends it.
4. **`ClientResource.vat_number` / `cr_number` echo the user's name**, not the real numbers — don't display
   them as VAT/CR until fixed.
5. **`GET /offers` isn't ownership-scoped for clients** — a client gets *all* offers unless you pass
   `filter[order_id]=<their order id>`. Always send the filter.
6. **No ownership check on `POST /bidding-order/id` or `POST /support/delete`** — any authenticated user can
   fetch any order by id / delete any support ticket by id. Don't expose those ids across users.
7. **`POST /address/reverse-geocode` is broken** (no controller method) — don't call it.
8. **Some failures return `"status": true`** with an HTTP 400 (withdrawal over balance, duplicate bid). Branch
   on the HTTP status + `message`, not just `status`.
9. **The invoice PDF uses its own hardcoded tax (15%) / VAT (0%) / EGP**, which can differ from the live
   checkout total. Treat the PDF as a document, not a source of the amount charged.
10. **Order double-booking isn't prevented** — the availability check in `store` is commented out.
11. **Social login checks only the email** (no password). Gate it behind a real provider handshake.

---

## Recommendations for the mobile team

**Networking foundation**
- Build a single API client that always sends `Authorization: Bearer`, `Accept: application/json`, and a
  `lang` header, and that centralizes error mapping: **401** → re-auth / "account disabled"; **400** →
  "complete your profile" or a business message; **403** → not-allowed; **422** → field errors; **429** →
  back off.
- Add one **response adapter** for the two envelopes so screens don't each special-case `data` vs `status`.
- Passport tokens can expire — handle a 401 by sending the user back through login (there's no refresh-token
  endpoint exposed).

**Flows**
- **Payments: never mark an order paid from the redirect.** After the webview returns, confirm via
  `POST /payment/status` (or `GET /order/status` → `payment_status == "PAID"`). Only the server webhook flips
  `is_paid`.
- **Order state vs payment state:** use `order/status` → `data.status` for the lifecycle and `data.payment_status`
  for money; don't conflate them.
- **Bidding as a client:** always pass `filter[order_id]` to `GET /offers`.
- **OTP:** treat codes as *not delivered* today. For local testing use `1234`. Don't ship the password-reset
  or delete flows to production until the SMS gateway is live.
- **Idempotency:** disable the pay/checkout button after tap; the backend guards against double-pay, but avoid
  duplicate widgets.

**Uploads**
- Gallery + chat files: enforce the mimetype allow-list (`jpeg/png/webp/mp4/quicktime`) and 50 MB cap
  client-side before upload for a better UX.

**Nice-to-haves worth asking the backend for**
- A **Scribe** (or OpenAPI) setup so these docs auto-generate from the routes and stay in sync — I can
  scaffold it if you want (`composer require knuckleswtf/scribe`, add response annotations, `php artisan
  scribe:generate` → static HTML + Postman collection). It needs per-endpoint annotations to be accurate here,
  because of the mixed envelopes.
- Fixing the gotchas above (the `ClientResource` VAT/CR bug, the `/offers` client scoping, the broken
  reverse-geocode route) would remove the most error-prone surprises.

---

*Generated from the current `main` branch source (routes, controllers, form-requests, resources). If an
endpoint's behavior looks off, the code is the source of truth — check the matching controller/service.*
