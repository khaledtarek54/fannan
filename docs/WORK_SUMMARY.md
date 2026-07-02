# Fannan Backend — Work Summary

What was done on the Fannan backend, **split by where each issue came from**:
- **Part A — reported by the client** (the "Backend Security Points" PDF).
- **Part B — found by us** during our own review (security, code bugs, and business logic).
- **Part C — other deliverables** (recovery, documentation, tests, infrastructure).

Status key: ✅ fixed · ⚠️ partial · ☐ documented/deferred · ❌ not present · ➖ already safe.

---

## Part A — Issues the client reported (the PDF)

The client's document listed 21 points. Every real one is fixed; **2 did not exist** in the code, and
**1 was already handled** by the framework — verified against the actual source.

### Critical
| # | Issue | Status |
|---|-------|--------|
| C1 | `/api/command/{command}` ran arbitrary Artisan commands with no auth (RCE) | ✅ route removed |
| C2 | EasyKash GET callback marked orders PAID with no signature | ✅ GET no longer mutates state |

### High
| # | Issue | Status |
|---|-------|--------|
| H1 | `/api/invoice/download` IDOR | ❌ endpoint/controller doesn't exist in this codebase |
| H2 | `/api/order/accept` — artist could claim another artist's order | ✅ ownership check |
| H3 | `/api/order/reject` — any user could reject any order | ✅ ownership check |
| H4 | `/api/order/cancel` — any user could cancel any order | ✅ ownership check |
| H5 | `/api/checkout` unauthenticated + no order ownership | ✅ dup route removed + ownership |

### Medium
| # | Issue | Status |
|---|-------|--------|
| M1 | `/api/payments/easykash/status` enumeration | ✅ auth + scoped to caller |
| M2 | `/api/order/status` IDOR | ❌ no such route in this codebase |
| M3 | `/api/order/offer` — counter-offer on another client's order | ✅ ownership check |
| M4 | `/api/address/delete` IDOR | ✅ ownership check |
| M5 | `/api/offers/accept` + `/reject` IDOR | ✅ ownership check |
| M6 | `/api/easykash/pay` unauthenticated | ✅ auth + ownership |
| M7 | `POST /delete` — delete any account by phone | ✅ now requires a verification code (OTP) |
| M8 | `resourcePath` SSRF + bearer-token leak | ✅ strict allow-list on the path |
| M9 | `/admin/login` no rate limiting | ➖ already throttled by Filament (5/min) |
| M10 | Public web forms no rate limiting | ✅ throttle added |
| M11 | Single shared 60/min API limiter | ✅ dedicated auth/payment limiters |

### Low
| # | Issue | Status |
|---|-------|--------|
| L1 | `apiResource('invoices')` public + missing controller | ✅ removed |
| L2 | Payment webhook had no idempotency | ✅ idempotency guards |
| L3 | Gallery upload accepted any file type/size | ✅ mimetype + size validation |

---

## Part B — Issues we found ourselves

These were **not** in the client's document. We found them during our security, code, and business-logic
reviews. Full detail in `SECURITY_ISSUES.md`, `CODE_REVIEW_FINDINGS.md`, and `BUSINESS_LOGIC_ISSUES.md`.

### B.1 — Extra security issue
| # | Issue | Status |
|---|-------|--------|
| A1 | **The admin panel was open to every logged-in user** (`canAccessPanel` returned `true`) | ✅ gated behind a new `is_admin` column |

### B.2 — Code / correctness bugs
| # | Issue | Status |
|---|-------|--------|
| B1 | Withdrawal balance check always passed (mistyped key → "total withdrawn" was always 0) | ✅ fixed |
| B2 | Rating endpoint let clients inflate any artist's wallet (no ownership/dedup) | ✅ fixed (superseded by the escrow model) |
| B3 | Password reset needed no verification code → account takeover by phone | ✅ code now required |
| B4 | Checkout quoted tax was always 0 (read a non-existent column); quote ≠ charge | ✅ unified pricing |
| B6 | Notification "mark as read" / unread-count query over-matched | ✅ fixed |
| B7 | Chat-list endpoint (`GET /api/chat`) was an empty stub | ✅ implemented |
| B8 | Debug routes exposed: `/firebase-test` (dumped all Firebase user IDs) and `/test-mail` | ✅ removed |
| B9 | Money/key columns stored as `VARCHAR` | ☐ documented (see Q4) |
| B10 | `SettingRepository::getAll()` had a typo that would fatal if called | ✅ fixed |
| B11 | Dead code (`OrderOfferController`, commented `/debug` route) | ✅ removed |

**Also ruled OUT (false alarms):** we investigated and *disproved* four scary-looking claims — the
`transactions` enum, the `iban` column type, a mass-assignment "privilege escalation," and a coupon crash —
so they were **not** changed. (Kept the fix list honest.)

### B.3 — Business-logic / money-model bugs (the biggest finding)
The core marketplace payout was broken — artists were only paid when a client left a *rating*.
| # | Issue | Status |
|---|-------|--------|
| BL1 | Artist paid **only if the client rated**; unrated paid orders never paid out | ✅ pay on completion |
| BL2 | Rating credited income even for **unpaid** orders | ✅ settlement gated on `is_paid` |
| BL3 | Income ignored the coupon discount | ➖ policy set: platform absorbs coupon (see Q1) |
| BL4 | Paid orders reverted to `ACCEPTED`; no durable "paid" state | ➖ de-risked (completion no longer depends on it) |
| BL5/BL6 | **Bidding orders could never complete** and never paid out | ✅ completion now covers both types |
| BL7 | Coupon was consumed at the quote step even if the client never paid | ✅ consumed only on completion |
| BL8 | `NEW` status never actually set | ☐ cosmetic (see Q5) |

---

## Part C — Other deliverables

- **Recovery & repo:** extracted the app from the messy Hostinger download, imported the 53 MB production
  database, and put it under Git with a clean history (small, reviewable commits per fix).
- **Documentation:** a full `docs/` set — architecture, domain model, API reference, admin panel,
  integrations — plus the findings docs referenced above.
- **Automated test suite:** built from scratch (there were none) — **28 passing feature tests** that
  guard every security and business fix (payout, ownership, pricing, password reset, coupons, chat, …).
- **Pricing:** unified the quote and the charge into one `OrderPricingService` so they can't diverge.
- **Chat list:** implemented the previously-empty conversations endpoint.
- **Environment fixes:** repaired a corrupted Mockery package from the download, stopped `bootstrap/cache`
  from leaking dev providers into deploys, and generated local Passport keys for the test suite.

See [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md) for the decisions still pending.
