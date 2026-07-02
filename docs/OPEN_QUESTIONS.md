# Fannan — Open Questions & Pending Decisions

Decisions and coordination items that need your (or the client's) input. Each has context and a
recommendation so it can be answered quickly later. Resolved ones are kept at the bottom for the record.

## Needs a decision

### Q1 — Coupon discounts: who absorbs them? (from BL3)
When a client uses a coupon, does the discount come out of **the platform's cut** or **the artist's
earnings**?
- **Current behavior:** the *platform absorbs* the coupon — the artist is paid their full `cost − fee`
  regardless of the client's discount.
- **Trade-off:** if a coupon is larger than the platform's margin (tax + VAT + fee) on that order, the
  platform pays out more than it collected.
- **Recommendation:** keep "platform absorbs" (simple, artist-friendly) **and** cap coupon size below the
  platform margin. If you'd rather the coupon reduce artist earnings, it's a one-line change in
  `OrderService::creditArtist()`.

### Q2 — VAT: is charging it correct? (from B4)
Quote and charge are now unified and **include VAT** (currently 14% in settings; KSA VAT is normally
15% and mandatory).
- **Question:** is VAT meant to be added on top (current), or is it already included inside the order
  `cost`? This changes what customers pay.
- **Recommendation:** confirm with the client's accountant. If `cost` already includes VAT, remove the VAT
  term in `OrderPricingService` (one place, updates both quote and charge).

### Q3 — Account deletion: add OTP? (from M7)
The public "delete my account" form currently deletes by phone number and is only **rate-limited** (interim
mitigation).
- **Question:** implement a full OTP/verification step (reuses the existing SMS-code system) before
  deletion? Recommended for real protection, but it's a small feature (view + flow) rather than a patch.

### Q4 — Fix the mistyped money/key columns? (from B9)
`order_payment_transactions.amount`, `user_transactions.user_id`, and `users.email_verified_at` are stored
as `VARCHAR`.
- **Question:** run the schema-type migrations (to `decimal` / `bigint` / `timestamp`)? This needs the
  `doctrine/dbal` package added and a **supervised deploy** (existing data must convert cleanly). Works fine
  functionally today, so it's not urgent.

### Q5 — Clean up the `NEW` order status? (from BL8)
`OrderStatus::NEW` is never actually set on any order (new orders are `ARTIST_PENDING`); `NEW` is only a
display label. Cosmetic — clean up the enum/labels or leave as-is?

## Needs coordination (not a code decision)

### C1 — Mobile app must send the verification code on password reset (from B3)
`/api/password/update` now **requires** a `verification_code`. The app already collects it at the "enter
code" step — it just needs to include it in the reset request. **Coordinate before deploying** this change.

### C2 — Confirm the chat-list response shape (from B7)
`GET /api/chat` now returns the latest message per conversation partner (it was an empty stub). Confirm the
JSON shape matches what the mobile app expects for the conversation-list screen.

### C3 — Flag real admins after deploy (from A1)
The security fix adds a `users.is_admin` column defaulting to `false`. After migrating in production,
**no one can reach `/admin`** until you flag the real admin account(s):
`UPDATE users SET is_admin = 1 WHERE email = '...';`

## Resolved (for the record)
- **Artist payout timing** — decided: pay on **order completion** (escrow model). Implemented.
- **Quote vs charge divergence** — unified into one `OrderPricingService`.
