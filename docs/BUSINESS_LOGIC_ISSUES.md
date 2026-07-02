# Fannan Backend — Business-Logic Review

A trace of the core business flows (direct orders, bidding orders, payment, wallet, coupons) for
**correctness of the business model** — separate from security. Verified against code + DB.

**Root problem:** payment, completion, and artist payout are three independent mechanisms that never
converge. Payout (artist INCOME) is attached to the *rating* action; completion is a nightly cron that
only covers direct orders. So the correct end-state — **paid → completed → artist credited the net
amount collected** — is not reliably reachable.

## What works (verified correct)
- **Order creation** — sets `ARTIST_PENDING`, creates OrderCategory + per-day OrderDate rows.
- **Accept** — the artist's quoted `cost` is persisted; status → `ACCEPTED`.
- **Counter-offer** — offer chain (`counter_to`) and surfaced `COUNTER_OFFER` state are coherent.
- **Pricing** — after the recent fix, quote and charge use one `OrderPricingService` (consistent).
- **Coupon math** — fixed vs percentage calculation is correct.
- **Bid rejection** — accepting one bid correctly rejects other pending bids for that subcategory.

## Findings (by impact)

| ID | Finding | Severity | Where |
|----|---------|----------|-------|
| BL1 | Artist is credited **only when the client rates**; unrated paid orders never pay out | Critical | `RatingService.php:79-84` (sole INCOME site) |
| BL2 | Rating credits income even when the order was **never paid** (gates on date, not `is_paid`) | Critical | `RatingService.php:43,51` |
| BL3 | Income uses the **pre-coupon** cost, not the amount actually charged | High | `RatingService.php:56,77` |
| BL4 | Successful payment sets status **back to `ACCEPTED`**; no durable "paid/settled" state | High | `PaymentService.php:121` |
| BL5 | `COMPLETED` only reachable for **DIRECT** orders, via a nightly cron | High | `OrderRepository.php:145`, `Kernel.php:27` |
| BL6 | **Bidding orders never complete** (excluded from the completion query) — stuck at `ACCEPTED` | High | `OrderRepository.php:145`, `UpdateBiddingOrderStatus.php` |
| BL7 | Coupon is **consumed at the quote step** regardless of whether payment ever happens | Medium | `OrderService.php:154` |
| BL8 | `OrderStatus::NEW` is never actually set (cosmetic/confusing) | Low | `OrderRepository.php:59` |

### BL1 — Artists only paid when the client rates (Critical)
`TransactionType::INCOME` is created in exactly one place: `RatingService::store`. Payment settlement
(`PaymentService::getPaymentStatus`) marks `is_paid=true` but creates **no** income. Rating is optional
and client-initiated, so any paid-but-unrated order leaves the artist with **zero** withdrawable balance
while the platform holds the money. Payout must be driven by settlement/completion, not by a review.

### BL2 — Income credited for unpaid orders (Critical)
The direct-order rating branch gates only on `$order->is_complete` (date-based), not `is_paid`. A client
can let an order's date pass without paying, then rate → the artist is credited for money never collected.

### BL3 — Income ignores the coupon discount (High)
Income = `cost_value` − platform fee, ignoring `coupon_amount`. The client pays `total − coupon`, so on
coupon orders the artist is credited more than was collected; the books don't reconcile.

### BL4 — Paid orders revert to `ACCEPTED` (High)
`OrderService::checkout` sets `IN_PAYMENT`; the payment webhook then overwrites it back to `ACCEPTED`.
There is no durable post-payment status, so "accepted-unpaid" and "paid" are distinguishable only by the
`is_paid` flag.

### BL5 / BL6 — Completion doesn't cover bidding; bidding orders stuck (High)
`getCompletedOrders` filters `type = DIRECT`, so bidding orders (which CAN be paid) can never reach
`COMPLETED`. They sit at `ACCEPTED` forever, and their artists depend entirely on per-bid ratings for payout.

### BL7 — Coupon consumed without a sale (Medium)
`OrderService::checkout` (the quote endpoint) records `CouponUser` usage immediately, before payment. A
user who quotes but never pays has burned their one coupon use with no sale.

---

## The decision that shapes the fix

The right fix depends on **when artists should be paid** — a business/escrow-policy choice. Once decided,
BL1–BL4 are fixed together by moving income creation to that point (net of platform fee **and** coupon),
gated on `is_paid`, and BL5/BL6 by making completion cover both order types. Rating then becomes purely a
review. (Status of fixes tracked here once the model is confirmed.)
