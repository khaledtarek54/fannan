# API Reference

Base path: `/api` (`routes/api.php`). Auth: Laravel **Passport**, `auth:api`. Locale via `lang` header (default `ar`).

Response envelope (via `BaseController`): `{ success, data, message, errors }` — though many controllers return ad-hoc `{ status, ... }` JSON directly.

## Authentication & account

| Method | URI | Controller | Auth | Notes |
|--------|-----|------------|------|-------|
| POST | `/register` | `AuthController@register` | public | name, phone(+prefix), role, password; issues verification code |
| POST | `/login` | `AuthController@login` | public | phone + password → Passport token |
| POST | `/login-social`, `/social/login` | `AuthController@socialLogin` | public | email-based social auth |
| POST | `/verification/check` | `AuthController@checkCode` | public | verify code |
| POST | `/send/code` | `AuthController@sendCodeAgain` | public | resend code |
| POST | `/password/update` | `AuthController@updatePassword` | public | reset password by phone |
| POST | `/update/fcm_token` | `AuthController@updateToken` | `auth:api` | update FCM token |
| GET | `/delete-account` | `ClientController@deleteAccount` | `auth:api` | soft-delete self |

## Profile

| Method | URI | Controller | Auth |
|--------|-----|------------|------|
| POST | `/client/complete/profile`, `/client/update` | `ClientController@completeProfile` | `auth:api` |
| GET | `/client/profile` | `ClientController@profile` | `auth:api` |
| GET | `/artist` , POST `/artist/id` | `ArtistController@index` / `getArtistById` | public |
| POST | `/artist/categories/update` | `ArtistController@updateCategories` | `auth:api` (artist) |
| GET | `/artist/profile` | `ArtistController@profile` | `auth:api` |
| GET | `/update-lang` | `Controller@updateLang` | `auth:api` |

## Orders (direct) — `CompleteProfileMiddleware`

| Method | URI | Controller | FormRequest (authorize) |
|--------|-----|------------|-------------------------|
| GET | `/order` | `OrderController@index` | — (client's orders) |
| GET | `/order/artist` | `OrderController@artistOrders` | — |
| POST | `/order/store` | `OrderController@store` | `StoreOrderRequest` (role=client) |
| POST | `/order/accept` | `OrderController@accept` | `AcceptOrderRequest` (role=artist) |
| POST | `/order/offer` | `OrderController@offer` | `CounterOfferRequest` (role=client) |
| POST | `/order/reject` | `OrderController@reject` | `RejectOrderRequest` (**authorize=true**) |
| POST | `/order/cancel` | `OrderController@cancel` | `OrderIdRequest` (**authorize=true**) |
| POST | `/order/checkout` | `OrderController@checkout` | `CheckoutRequest` (role=client) |
| POST | `/order/status` | `OrderController@orderStatus` | `OrderIdRequest` — **participant-only** (M2, added) |

## Bidding — `CompleteProfileMiddleware`

| Method | URI | Controller | FormRequest |
|--------|-----|------------|-------------|
| GET | `/bidding-order` | `BiddingOrderController@index` | — |
| POST | `/bidding-order/id` | `BiddingOrderController@show` | `OrderIdRequest` |
| POST | `/bidding-order/store` | `BiddingOrderController@store` | `BiddingOrderRequest` (role=client) |
| POST | `/bidding-order/send-offer` | `BiddingOrderController@offer` | `BiddingOfferRequest` (role=artist) |
| GET | `/bidding-order/available` | `BiddingOrderController@available` | — |
| GET | `/offers` | `BiddingOrderArtistController@index` | — |
| POST | `/offers/accept` | `BiddingOrderArtistController@accept` | `OfferIdRequest` |
| POST | `/offers/reject` | `BiddingOrderArtistController@reject` | `OfferIdRequest` |

## Payments

| Method | URI | Controller | Auth | Notes |
|--------|-----|------------|------|-------|
| POST | `/payment/checkout` | `PaymentController@checkout` | `auth:api` + CompleteProfile + `throttle:payment` | HyperPay; order-owner enforced. Amount from shared `OrderPricingService` |
| POST | `/payment/status` | `PaymentController@checkPaymentStatus` | `auth:api` + CompleteProfile | `resourcePath` allow-list validated (M8 fixed) |
| POST | `/payment-webhook` | `PaymentController@webhook` | inside auth group | HyperPay callback (idempotent) |
| GET | `/webhook` | `PaymentController@webhook` | public | HyperPay shopper return URL |
| POST | `/easykash/pay` | `EasyKashController@createPayment` | `auth:api` + `throttle:payment` (fixed) | order-owner only |
| GET/POST | `/easykash/callback` | `EasyKashController@callback` | public | POST HMAC-verified; **GET redirect is read-only** (C2 fixed) |
| GET | `/payments/easykash/status` | closure | `auth:api`, **scoped to caller** (M1 fixed) | own transactions only |

> The duplicate public `POST /checkout` and the arbitrary-command `GET /command/{command}` routes were **removed** (H5, C1).

## Other resources (all `auth:api`, most inside the main group)

| Area | Endpoints |
|------|-----------|
| Gallery (artist) | `GET /gallery`, `POST /gallery/create|update|delete` |
| Support | `GET /support`, `POST /support/create|delete` |
| Coupons | `POST /coupon/check-coupon` |
| Ratings | `POST /rating/store` (role=client) |
| Chat | `GET /chat`, `POST /chat/details|store` |
| Transactions | `GET /transactions`, `POST /transactions/request` (role=artist) |
| Notifications | `GET /notifications` |
| Address | `GET /address`, `POST /address/store|delete` (role=client) |
| Home | `GET /home` (public), `GET /artist/home` (auth) |
| Reference | `GET /categories`, `GET /settings`, `GET /price-ranges`, `GET /cities`, `GET /artist-acknowledgement` (public) |
| Invoices | `GET /invoice/download?order_id=` — **participant-only**, returns a PDF, no IBAN (H1, newly built) |

## Web routes (`routes/web.php`)

`/` → `/admin`; legal pages (`/privacy-policy`, `/terms`, `/about`, `/contact` + `POST /contact-store`); `/delete-account` view + `POST /delete` (**now requires a verification code** — M7) + `POST /delete-account/send-code`; `/artist-register` + `POST /artist-store`. Public forms are rate-limited (`throttle:6,1`). The debug routes (`/test-mail`, `/firebase-test`) and the broken `/payments/easykash/return` route were **removed**.

## FormRequest validation summary

FormRequests under `app/Http/Requests/` carry both validation **and** authorization. Common patterns:

- **Role-gated** (`authorize()` checks `user()->role`): `StoreOrderRequest`, `AcceptOrderRequest`, `CounterOfferRequest`, `CheckoutRequest`/`CheckoutOrderRequest`, `BiddingOrderRequest`, `BiddingOfferRequest`, `RatingRequest`, `StoreAddressRequest`, `DeleteAddressRequest`, `ArtistCategoryRequest`, gallery/transaction requests.
- **`authorize()` returns `true`** (no ownership/role check): `LoginRequest`, `StoreUserRequest`, `SocialLoginRequest`, `RejectOrderRequest`, `OrderIdRequest`, `OfferIdRequest`, `ChatRequest`, `StoreChatRequest`, `StoreSupportRequest`, `CreatePaymentRequest`, `StoreContactRequest`, `DeleteUserAccountRequest`.

> **Note:** role checks in FormRequests confirm *what kind* of user is calling, not *whether they own the target record*. Ownership is now enforced in the **service layer** (`OrderService`, `AddressService`, `BiddingOrderArtistService`, `RatingService`, `PaymentService`) — the IDOR findings in [SECURITY_ISSUES.md](SECURITY_ISSUES.md) (H2-H5, M3-M5) are fixed there.
