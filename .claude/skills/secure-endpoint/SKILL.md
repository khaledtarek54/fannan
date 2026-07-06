---
name: secure-endpoint
description: Guardrails for adding or modifying any Fannan API endpoint, controller, auth flow, or payment path. Invoke before writing endpoint/controller/FormRequest/service code, or when reviewing auth, ownership, payments, or mass-assignment. Encodes this project's recurring security invariants — authorization scoping, server-side amounts, mass-assignment discipline.
---

# Writing a secure endpoint (Fannan)

Authorization/ownership bugs are the recurring, highest-severity bug class in this codebase (see
`docs/SECURITY_ISSUES.md`). Treat every new or changed endpoint as security-sensitive.

## Structure (match the codebase)

- Route in `routes/api.php` behind `auth:api` (Passport). Auth endpoints use `throttle:auth` (30/min/IP), payment endpoints `throttle:payment` (30/min), everything else `api` (120/min). Don't remove throttles.
- Keep the controller **thin** — delegate to a Service (`app/Services/`); persistence via a Repository.
- Put validation **and** authorization in a **FormRequest** (`app/Http/Requests/`), not inline.
- Return an **API Resource** (`app/Http/Resources/`), never a raw model — avoid leaking IBAN/phone/PII.

## Invariants — do not violate

1. **Scope every query to the authenticated user.** Resolve child records through the owner relationship (e.g. `$request->user()->orders()->findOrFail($id)`), not by a raw client-supplied id. A non-participant must get `403`, never someone else's data. (This is the IDOR class behind several findings.)
2. **Never trust client-supplied money or state.** Amounts, prices, totals, `is_paid`, `status`, `role`, and wallet balances come from the server-side record — never from the request body. Bind payment amounts to the order on the server.
3. **Mass-assignment discipline.** No `$model->update($request->all())` on models with sensitive columns. Whitelist fields; never let `is_paid`/`role`/`balance` be filled from input. Do not reintroduce `Model::unguard()`.
4. **Verify third-party tokens.** Social login must verify the Firebase/provider token server-side before trusting an identity. EasyKash payment callbacks must verify the HMAC signature.
5. **OTP / verification codes** need a TTL and attempt lockout; no hardcoded backdoor codes.

## Definition of done

Add or adjust a feature test covering **both** the allowed path (200/201) and the denied path
(403/422) — use `/feature-test` — and get `php artisan test` green before finishing.
