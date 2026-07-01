# Domain Model

Fannan is a **client-to-artist marketplace** for creative/event services with two order workflows: **direct** (hire a specific artist and negotiate) and **bidding** (post a job, collect bids, pick winners).

## Core entities & relationships

```
User (role: client | artist)
 ├─ clientOrders  ──┐
 ├─ artistOrders  ──┤
 │                  ▼
 │               Order (type: direct | bidding)
 │                ├─ OrderCategory[]   (subcategory + budget)
 │                ├─ OrderDate[]       (scheduling)
 │                ├─ OrderOffer[]      (direct: artist counter-offers)
 │                ├─ BiddingOrderArtist[] (bidding: artist bids)
 │                ├─ Address           (service location)
 │                ├─ Rating  (morph)   (client → artist review)
 │                └─ Transaction (morph) / UserTransaction (EasyKash) / OrderPaymentTransaction (HyperPay)
 │
 ├─ UserCategory[]  (artist's offerings: category + subcategory + price range)
 ├─ ArtistGallery[] (portfolio, table user_gallery_works)
 ├─ Address[]       (saved locations)
 ├─ Rating[]        (as artist_id — received reviews)
 ├─ Transaction[]   (wallet ledger: income / withdraw)
 ├─ Chat[]          (from/to messaging, self-referential replies)
 └─ Notification[], Support[], Ad[] (morph)
```

## Models (`app/Models/`)

| Model | Table | Purpose | Key relationships |
|-------|-------|---------|-------------------|
| **User** | `users` | Account (client or artist); wallet, profile, IBAN, social handles | clientOrders/artistOrders (Order), userCategories, works (ArtistGallery), ratings, transactions, categories (BelongsToMany), city |
| **Order** | `orders` | Service request, direct or bidding | client/artist (User), categories (OrderCategory), dates, offers, biddingOrderArtists, address, rating (morph), transaction (morph) |
| **OrderOffer** | `order_offers` | Artist counter-offer on a **direct** order (`counter_to` chains) | artist, order, subcategory; HasStatuses |
| **OrderCategory** | `order_categories` | Subcategory + optional budget on an order | order, subcategory |
| **BiddingOrderArtist** | `bidding_order_artists` | Artist bid on a **bidding** order (`is_accepted`) | order, artist, subcategory; rating (morph); HasStatuses |
| **Address** | `addresses` | Client service location | user, city |
| **Rating** | `ratings` | Star rating + notes (client → artist) | client, artist, model (morph: Order or BiddingOrderArtist) |
| **Transaction** | `transactions` | Wallet ledger (income/withdraw) | user, model (morph) |
| **UserTransaction** | `user_transactions` | EasyKash checkout record (customer_reference, status, callback payload) | order |
| **OrderPaymentTransaction** | `order_payment_transactions` | HyperPay processor record (checkout_id, resourcePath) | order |
| **Coupon** / **CouponUser** | `coupons` / `coupon_user` | Discount codes (fixed/percentage) + usage tracking | user |
| **Chat** | `chats` | Direct messages (text/file) with reply threading | fromUser, toUser, reply (self) |
| **Category** / **SubCategory** | `categories` / `sub_categories` | Service taxonomy (translatable en/ar) | subCategory, users (BelongsToMany) |
| **UserCategory** | `user_categories` | Artist offering: category + subcategory + price range | user, category, subcategory, priceRange |
| **ArtistGallery** | `user_gallery_works` | Artist portfolio media (`is_pin`, `type`) | user |
| **OrderDate** / **UserDate** | `order_dates` / `user_dates` | Order scheduling / artist availability windows | order / user |
| **Ad** | `ads` | Promotional banners (morph to User/Category); HasStatuses | adable (morph) |
| **Support** | `supports` | Help tickets (general or order-related) | user, replyUser, model (morph) |
| **Notification** | `notifications` | In-app notifications (translatable key title/body) | user, toUser, model (morph) |
| **Setting** | `settings` | Platform config (fees, tax, policies); translatable value | — (keyed by SettingKey) |
| **City** | `cities` | Saudi cities (translatable) | — |
| **PriceRange** | `price_ranges` | Price brackets (`from`/`to`) | — |
| **Contact** | `contacts` | Contact-form submissions | — |

Models using **SoftDeletes** can be restored; models using **HasStatuses** (Order, OrderOffer, BiddingOrderArtist, Ad) track a status history via `spatie/laravel-model-status`.

## Enums (`app/Enums/`)

| Enum | Cases |
|------|-------|
| `UserRole` | `artist`, `client` |
| `OrderType` | `direct`, `bidding` |
| `OrderStatus` | `new`, `artist_pending`, `counter_offer`, `accepted`, `in_payment`, `completed`, `rejected`, `pending`, `canceled` |
| `TransactionType` | `income`, `withdraw` |
| `CouponType` | `fixed`, `percentage` |
| `SettingKey` | `platform_fees`, `tax`, `vat`, `terms_and_conditions`, `privacy_policy`, `about_us`, `help_and_support`, `call_center_call`, `artist_acknowledgement` |
| `SupportType` | `general`, `direct_order`, `bidding_order` |
| `MessageType` | `text`, `file` |
| `FileType` | `image`, `video` |
| `AdStatus` | `active`, `inactive` |
| `ModelName` | polymorphic type identifiers |

## Business workflows

### Direct order
1. Client creates an order targeting a specific **artist** (`type=direct`) → status `artist_pending`.
2. Artist **accepts** with a quoted `cost` (`OrderController@accept`), or negotiation happens via **OrderOffer** counter-offers (`@offer`).
3. Client **checkout** → payment (HyperPay/EasyKash) → order `is_paid`, status progresses to `completed`.
4. Client **rates** the artist after completion.

### Bidding order
1. Client posts a job with talents/subcategories & optional budgets (`type=bidding`).
2. Multiple artists submit **bids** (`BiddingOrderArtist`, via `bidding-order/send-offer`).
3. Client **accepts/rejects** offers (`offers/accept`, `offers/reject`) — accepted bid → `is_accepted=1`.
4. Payment and rating proceed per accepted bid.

### Wallet & payouts
- Artist earnings recorded as `Transaction` (type `income`), net of `platform_fees`.
- Artists request **withdrawals** (`transactions/request`, type `withdraw`); admins process them via the Filament `WithdrawTransactionResource`.

## Roles & admin

- A user is a **client** or **artist** via `users.role` (`UserRole`).
- There is **no admin role**. The Filament panel currently authorizes any authenticated user (`User::canAccessPanel()` returns `true`). Admin actions (support replies, withdrawal processing) reference `reply_user_id` but there is no formal admin/permission model. This is a known gap — see [SECURITY_ISSUES.md](SECURITY_ISSUES.md).
