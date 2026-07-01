# Fannan Backend — Second-Round Review (Bugs & Logic)

A deeper review beyond the client's security brief, covering **correctness bugs, money math,
additional vulnerabilities, and production-readiness**. Every item was verified against the actual
code **and** the imported production database. Status: ☑ fixed · ⚠️ partial/flagged · ☐ documented.

## Confirmed findings

| ID | Finding | Severity | Where | Status |
|----|---------|----------|-------|--------|
| B1 | Withdrawal balance always allows (wrong key `'is-completed'`) → over-withdraw | Critical | `User.php:166` | ☑ |
| B2 | Rating endpoint: no ownership, no dedup, credits artist wallet each call, offer branch skips completion, fee fallback uses VAT | Critical | `RatingService.php:31-66` | ☑ |
| B3 | Password reset never checks the verification code → account takeover by phone | High | `UserRepository.php:126`, `AuthController.php:70` | ☑ |
| B4 | Checkout quote reads non-existent `settings.text_en` → quoted tax always 0; quote ≠ charge | High | `OrderService.php:144` | ⚠️ |
| B5 | VAT operator-precedence bug (`* … ?? 0`) — the default guard is illusory | Medium | `OrderService.php:167` | ☑ |
| B6 | Notification `markAsRead`/unread-count use ungrouped OR → wrong rows/counts | Medium | `NotificationRepository.php:27-50` | ☑ |
| B7 | `ChatController::chats()` is an empty stub wired to a live route | Medium | `ChatController.php:19` | ☐ documented |
| B8 | Debug routes exposed: `/test-mail` (500/leak) and `/firebase-test` (dumps all Firebase UIDs) | High | `routes/web.php` | ☑ |
| B9 | Money/keys stored as VARCHAR: `order_payment_transactions.amount`, `user_transactions.user_id`, `users.email_verified_at` | Medium | migrations | ☐ documented |
| B10 | `SettingRepository::getAll()` typo `firts()` (dead code, latent fatal) | Low | `SettingRepository.php:14` | ☑ |
| B11 | Dead code: `OrderOfferController` (no route), commented `/debug` route, VAT read via enum object | Low | various | ☑ |

## Details

### B1 — Withdrawal balance check is always bypassable (Critical)
`User::getTotalWithdrawAttribute()` filters the collection on `'is-completed'` (hyphen) but the column
is `is_completed`, so it **always returns 0**. `TransactionService::storeNewRequest()` computes
`net = total_income − total_withdraw` (= income − 0), so an artist can request withdrawals that sum to
far more than they earned, and stack multiple pending requests. **Fix:** correct the key and count all
withdrawal requests (pending + completed) as committed against the balance.

### B2 — Rating endpoint enables wallet inflation & IDOR (Critical)
`RatingService::store()` is the only path that creates `INCOME` transactions (artist balance). It (a)
never checks the caller owns the order/offer, (b) has no duplicate-rating guard, (c) credits the artist
on **every** call, (d) skips the completion check on the bidding-offer branch, and (e) falls back to the
**VAT** setting as the platform-fee %. A client can rate the same order repeatedly to pump any artist's
withdrawable balance. **Fix:** ownership check + one-rating-per-model dedup + completion required on both
branches + correct fee source. (Recommended follow-up: credit income at payment settlement, not on rating.)

### B3 — Password reset = account takeover (High)
`POST /password/update` maps to `UserRepository::updatePassword($phone, $password)`, which sets the new
password with **no verification-code check** and even returns a valid access token. Anyone can take over
any account with only a phone number. **Fix:** require and verify a matching `verification_code`, then
invalidate it. *Note: the mobile app must send the code it already collected at the "enter code" step.*

### B4 — Checkout tax bug + quote/charge divergence (High, partially fixed)
The `settings` table has columns `id, type, value` (no `text_en`). `OrderService::checkout()` reads
`?->text_en`, so the **quoted tax is always 0**, while `PaymentService::checkout()` reads `?->value`
(tax 10%). The two paths also differ on VAT (quote adds 14% VAT; the charge adds none). **Fixed:** the
quote now reads `->value` and the VAT precedence bug is corrected. **Still needs a business/legal
decision (flagged):** should the *charge* include VAT (KSA VAT is normally mandatory)? Unifying quote and
charge changes what customers pay, so it is left for confirmation — one shared pricing method should be
used by both once the rule is confirmed.

### B6 — Notification query precedence (Medium)
`->where('user_id',me)->orWhere('to_user_id',me)->where('is_read',false)` parses as
`user_id=me OR (to_user_id=me AND is_read=false)`, so `markAsRead` touches all of `user_id=me` and the
unread count is inflated. **Fix:** group the OR: `(user_id=me OR to_user_id=me) AND is_read=false`.

### B8 — Exposed debug routes (High)
`/firebase-test` (public) calls Firebase `listUsers()` and returns **every user UID** plus exception
messages; `/test-mail` (public) renders an email for a hardcoded `User::find(8)`. **Fix:** removed both.

### B9 — Money & keys as VARCHAR (Medium)
Verified in the DB: `order_payment_transactions.amount` = `varchar(255)`, `user_transactions.user_id` =
`varchar(255)`, `users.email_verified_at` = `varchar(255)`. **Recommended (deferred, not auto-applied):**
migrations to `decimal(12,2)` / `unsignedBigInteger` + index / `timestamp`, plus model casts. Left as a
documented recommendation because `->change()` needs the `doctrine/dbal` package (not installed) and the
existing production values must be validated/converted first — a change best done in a supervised deploy,
not a blind patch. The columns are functionally usable today (PHP coerces the strings).

## Verified NON-issues (false positives — not changed)

Investigated and **disproved** against code/DB, to keep the fix list honest:

- **`transactions.type` ENUM "malformed"** — the live column is a correct `enum('income','withdraw')`; income/withdraw store fine. ❌ not a bug.
- **`users.iban` stored as integer** — the live column is `text`; IBANs can be stored. ❌ not a bug.
- **Mass-assignment role/wallet escalation** — `ClientRepository::complete()` uses an explicit whitelist (not `$request->all()`), so `role`/`wallet`/`is_verified` can't be set by users. ❌ not exploitable.
- **Coupon null-deref crash** — the `ValidCoupon` rule rejects unknown codes before the service runs. ❌ not reachable.
- **Telescope gate open** — the gate is restricted to specific admin emails; `/telescope` isn't routed outside `local`. ✅ already safe (just set `TELESCOPE_ENABLED=false` in prod as defense-in-depth).
