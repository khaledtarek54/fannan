# Fannan Backend — Recommended Improvements & Roadmap

_A proposal of optional next steps, identified during the recent stabilization and security work.
None of these block the current delivery — they are opportunities to make the platform more reliable,
secure, and capable as it grows._

Each item notes **why it matters** and a rough **effort** (S / M / L). Suggested phasing is at the end.

---

## 1. Infrastructure & Reliability — *highest impact*

| # | Improvement | Why it matters | Effort |
|---|-------------|----------------|--------|
| 1.1 | **Move off shared hosting** to a VPS or managed host | The current Hostinger plan caps the account at **75 simultaneous DB connections** and a limited number of concurrent requests. This was the actual root cause of the "the API keeps rejecting the developer" problem — under bursts the server refuses connections. A dedicated environment removes that ceiling and unlocks everything below. | L |
| 1.2 | **Background job queue** | Right now emails, notifications, and push messages are sent **inline during the request** (`QUEUE_CONNECTION=sync`), so every action waits on them. Moving to a real queue + worker makes the app noticeably faster and more resilient. | M |
| 1.3 | **Redis for cache, sessions & rate-limiting** | These are currently file-based, which is slow under load on shared disks. Redis is much faster and scales. | S–M |
| 1.4 | **Real transactional email** | Mail is currently written to a log file, **not actually delivered** (the previous config pointed at a dev-only mail tool). Wire a real SMTP/provider so password resets, receipts, etc. reach users. | S |
| 1.5 | **Staging environment** | A copy of the app to test changes before they hit production — prevents surprises for real users. | M |
| 1.6 | **Log rotation** | Logs currently grow without limit (we found a 12 MB log). Rotate daily and cap size. | S |

## 2. Monitoring & Backups

| # | Improvement | Why it matters | Effort |
|---|-------------|----------------|--------|
| 2.1 | **Error tracking** (e.g. Sentry) | There is currently **no visibility into production errors** — problems are only found when a user complains. Error tracking alerts you the moment something breaks. | S |
| 2.2 | **Automated scheduled backups** (DB + uploaded media) | Backups are currently manual. Automate daily off-server backups so nothing is ever lost. | S–M |
| 2.3 | **Uptime & health monitoring** | Get alerted if the API goes down, before customers notice. | S |

## 3. Security & Compliance — *ongoing hardening*

_(The reported security issues are already fixed; these are the next layer.)_

| # | Improvement | Why it matters | Effort |
|---|-------------|----------------|--------|
| 3.1 | **Finish OTP delivery for account deletion** | The security check is in place (a code is required), but the **code isn't actually sent** yet — an SMS channel needs wiring. | S–M |
| 3.2 | **Two-factor authentication for the admin panel** | Protects the most sensitive access (full read/write over users, orders, payments). | S |
| 3.3 | **Audit logging** | Record who changed what on orders, payments, and users — essential for disputes and trust. | M |
| 3.4 | **Rotate & relocate credentials** | Move the Firebase and payment/OAuth key files out of the web app directory and rotate them. | S |
| 3.5 | **Automated dependency scanning** | Now that the code is in a private Git repo, enable automatic alerts for vulnerable libraries. | S |
| 3.6 | **Security headers & token-expiry review** | Standard web hardening (HSTS/CSP) and a review of how long API sessions stay valid. | S |

## 4. Code Quality & Maintainability

| # | Improvement | Why it matters | Effort |
|---|-------------|----------------|--------|
| 4.1 | **Phone-number normalization** | Login currently requires the phone in one exact stored format; other formats (e.g. with the country code) are rejected. Normalizing input prevents avoidable "can't log in" issues. | S |
| 4.2 | **Automated tests + CI** | Grow the test suite around payments and orders, and run it automatically on every change so regressions are caught before release. (CI can run tests here even though it can't deploy to this host.) | M |
| 4.3 | **API versioning** (`/v1`) | Lets the backend evolve without breaking older app versions still in users' hands. | M |
| 4.4 | **Clean up legacy/dead code & data-model quirks** | Remove commented-out and duplicate endpoints; fix small model inconsistencies (one of which caused a login bug we already patched). | S–M |
| 4.5 | **Keep an up-to-date API reference** | Ensures the mobile team always has accurate endpoint docs. | S |

## 5. Product & Feature Enhancements

| # | Enhancement | Value | Effort |
|---|-------------|-------|--------|
| 5.1 | **Real-time chat** (WebSockets/Pusher — the config is already present) | The chat is currently polling-based; real-time makes it feel instant and reduces load. | M |
| 5.2 | **Payments: refunds, retries, and a single clear flow** | Two gateways exist (EasyKash + HyperPay); unify the experience, add refunds and automatic retry on failure. | M–L |
| 5.3 | **Push-notification reliability & tracking** | Ensure notifications are delivered and know when they aren't. | M |
| 5.4 | **Admin analytics dashboard** | Orders, revenue, active artists/clients at a glance in the admin panel. | M |
| 5.5 | **Artist discovery / search improvements** | Better filtering and ranking to help clients find artists. | M |
| 5.6 | **Coupons, ratings & notification-preferences polish** | Incremental improvements to existing systems (we found a minor coupon-consumption bug already fixed). | S–M |
| 5.7 | **Full Arabic/English coverage** | The framework supports both; ensure every message is translated. | S |

---

## Suggested phasing

- **Phase 1 — Stabilize & operate (foundation):** hosting move (1.1), queue (1.2), real email (1.4),
  error tracking (2.1), automated backups (2.2), finish OTP delivery (3.1).
- **Phase 2 — Harden & professionalize:** Redis (1.3), staging (1.5), 2FA (3.2), audit log (3.3),
  dependency scanning (3.5), tests + CI (4.2), phone normalization (4.1).
- **Phase 3 — Grow the product:** real-time chat (5.1), payments enhancements (5.2), admin
  analytics (5.4), search (5.5), API versioning (4.3).

> Effort key: **S** ≈ up to a couple of days · **M** ≈ up to a week or two · **L** ≈ multi-week.
> These are rough; a fixed quote can be prepared per phase.
