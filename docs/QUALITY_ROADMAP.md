# Fannan Backend — Roadmap to (As-Close-As-Possible) Bug-Free

"Bug-free" is asymptotic — you never *prove* zero bugs, you build a system that keeps finding and
preventing them faster than they appear. This plan does three things in order of ROI:
**1) lock in what's fixed (regression tests), 2) let tools find the next bugs automatically,
3) sweep the untested corners.** It's sized for a bootstrapped startup — each phase is independently
valuable, so you can stop at any point with a real improvement banked.

Effort key: **S** ≈ ≤1 day · **M** ≈ 2–4 days · **L** ≈ 1–2 weeks. Do phases top-to-bottom; the
"Minimal path" at the end is what to do if budget is very tight.

---

## Where we are now

- ✅ Security brief remediated (see `SECURITY_ISSUES.md`) — RCE, payment bypass, IDOR, admin gate, SSRF, rate limits.
- ✅ Second-round bug review (see `CODE_REVIEW_FINDINGS.md`) — withdrawal, rating, password reset, tax, notifications, debug routes.
- ✅ Full documentation, Git history with reviewable commits.
- ✅ Dependency security: safe within-major update (advisories **39 → 4**); composer platform pinned to Hostinger **PHP 8.4**. The remaining 4 need a Laravel 10 → 12 upgrade (see OPEN_QUESTIONS Q6).
- ⚠️ **No automated tests exist yet** — this is the single biggest gap. Every fix so far is only protected by manual checks.
- ☐ Open items: VAT quote/charge policy (needs sign-off), mobile app must send `verification_code`, chat-list endpoint (B7), varchar money columns (B9).

---

## Phase 0 — Close the known loop  ·  **S**  ·  🟡 MOSTLY DONE
Finish the already-identified items so the "known bug" list is empty before we go hunting for unknowns.
- ✅ **VAT unified** — quote (`OrderService`) and charge (`PaymentService`) now share `OrderPricingService`; they can't diverge. VAT is charged (matches quote + KSA rules); one-line toggle if not wanted.
- ✅ **Chat-list endpoint** (B7) implemented (latest message per partner). *Confirm the response shape matches the mobile app.*
- ☐ Coordinate the **mobile app** to send `verification_code` on password reset (B3 — app-side change).
- ☐ Plan the **schema type fixes** (B9) for a supervised deploy (add `doctrine/dbal`, convert `amount`/`user_id`/`email_verified_at`).
- ☐ One-time **data integrity pass**: fix the orphaned `order_categories` rows and add the missing FK constraints/indexes.

## Phase 1 — Safety net: automated tests  ·  **L**  ·  *highest ROI*  ·  🟡 STARTED
PHPUnit + Pest and Faker are already in `composer.json`; there's just no test suite. This is what makes
the app *stay* fixed.
- ✅ **Done:** harness set up (`fanna_testing` MySQL DB, `TestCase`/`CreatesApplication`, Passport client in `setUp`, factories) and **35 passing feature tests** covering the security + business fixes (admin gate, EasyKash callback, order/address/rating ownership, password reset, withdrawal balance, pricing, settlement/escrow, coupons, chat, account deletion, invoice, order status). See `tests/Feature/`.
- ☐ **Remaining:** broaden coverage — bidding flow, coupon/tax math (quote == charge), address/gallery/support IDOR happy+sad paths, notifications, and the rating credit/dedup path (needs an `OrderDate` factory + `is_complete`).
- Set up the test harness: a dedicated test database (in-memory SQLite or a `fanna_testing` MySQL), `RefreshDatabase`, model factories for `User`, `Order`, `OrderOffer`, `BiddingOrderArtist`, `Address`, `Transaction`, `Setting`.
- **Regression tests for every fix already made** (guards so they can never come back):
  - Auth: register/login/social, and password reset **rejects** without a valid code.
  - Orders: accept/reject/cancel/offer/checkout return **403** for non-participants; happy path works.
  - Payments: checkout ownership; EasyKash GET callback does **not** mark paid; HMAC POST does; `resourcePath` allow-list.
  - Wallet: withdrawal **rejected** above balance; rating credits **once** and only by the owner after completion.
  - Admin: `canAccessPanel` true only for `is_admin`.
  - Money math: quote == charge for representative carts (tax + VAT + coupon).
- Target: cover the **money and auth flows first**, then the rest of the API. Aim for meaningful coverage of `app/Services` (where the logic lives), not a coverage-percentage vanity number.

## Phase 2 — Let tools find bugs: static analysis + formatting  ·  **M**
The bugs we found by hand (reading a non-existent column, `*` vs `??` precedence, null derefs) are exactly
what static analysis flags automatically.
- Add **Larastan/PHPStan** (start at level 4–5, raise over time). Fix the real errors it surfaces; baseline the noise.
- Wire up **Laravel Pint** (already in `composer.json`) for consistent formatting.
- Add **type hints** to service/repository method params/returns (the analyzer is currently blind to a lot because of missing types).

## Phase 3 — Sweep the untested corners  ·  **M**
The reviews so far concentrated on security + money. Cover the rest with the same rigor.
- **Filament admin resources** (18 of them) — form validation, bulk actions, authorization per resource, N+1 queries in tables.
- **API Resources** (`app/Http/Resources`) — response-shape consistency and null handling the mobile app depends on.
- **Query performance** — N+1 hunts on list endpoints (home, orders, artists), add eager-loading + indexes on hot foreign keys.
- **Notifications/FCM** — the 8 push notifications actually deliver; failures don't break the request.

## Phase 4 — Runtime observability  ·  **S–M**
You can't fix what you can't see. This catches the bugs that only appear with real users/data.
- Add **error tracking** (Sentry / Laravel Flare / Bugsnag) so production exceptions arrive with stack + context.
- Clean up logging: downgrade the noisy `Log::info(...exception...)` calls and stop logging payloads/PII.
- Set `TELESCOPE_ENABLED=false` and `APP_DEBUG=false` in production; add a `/health` check.

## Phase 5 — Payment & integration validation  ·  **M**
The money paths are the highest-risk and can't be fully unit-tested — they need real sandbox runs.
- End-to-end tests in **HyperPay** and **EasyKash** sandboxes: success, failure, retry, and **replay** (idempotency) cases.
- Confirm callback/return URLs and signatures against the providers' current docs.

## Phase 6 — Prevent regressions: CI + process  ·  **S**
Make quality automatic so it doesn't depend on remembering.
- **GitHub Actions**: run Pint + PHPStan + the test suite on every push/PR; block merges on failure.
- A **staging** environment mirroring production; deploy there first.
- Lightweight PR review checklist (ownership check? test added? migration safe?).

---

## Minimal path (if budget is very tight)
Do these three and you get ~80% of the benefit:
1. **Phase 0** — close the known items (small, finishes the current work).
2. **Phase 1, money + auth tests only** — regression-proof the dangerous flows we just fixed.
3. **Phase 4 error tracking** — so anything we missed surfaces immediately in production instead of silently.

Everything else (full test coverage, static analysis, CI, admin sweep) is high-value but can follow as the
startup grows.

## Definition of "done enough"
- Critical flows (auth, orders, payments, wallet) covered by passing tests.
- PHPStan green at the chosen level; Pint clean; CI enforcing both.
- Production errors visible in a dashboard, not lost in logs.
- No open items on `SECURITY_ISSUES.md` / `CODE_REVIEW_FINDINGS.md`.
