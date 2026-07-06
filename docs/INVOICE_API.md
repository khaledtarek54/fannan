# Invoice API

How the Fannan backend generates and serves order invoices, and how to test the invoice
locally. The invoice is a server-rendered PDF built from an `Order` and its relations.

- Controller: [`app/Http/Controllers/InvoiceController.php`](../app/Http/Controllers/InvoiceController.php)
- Template: [`resources/views/invoices/template.blade.php`](../resources/views/invoices/template.blade.php)
- Totals: [`app/Services/OrderPricingService.php`](../app/Services/OrderPricingService.php) (single source of truth)
- Renderer: DomPDF (`dompdf/dompdf`)

---

## 1. Endpoints

All endpoints live under `/api` and require a **Passport/Sanctum bearer token** (`auth:api`).

### 1.1 Download invoice PDF

```
GET  /api/invoice/download?order_id={id}
POST /api/invoice/download        { "order_id": {id} }
```

Streams a PDF (`Content-Type: application/pdf`) named `INV-<ref>-<order_id>.pdf`.

| Field      | In         | Type | Required | Notes                          |
|------------|------------|------|----------|--------------------------------|
| `order_id` | query/body | int  | yes      | Must exist in `orders`         |

**Authorization:** only the order's **client** or **assigned artist** may download it.
Anyone else gets `403` (prevents PII/tax-ID enumeration by iterating `order_id` — security
issue H1).

**Responses**

| Status | Meaning                                             |
|--------|-----------------------------------------------------|
| `200`  | PDF stream                                           |
| `401`  | Not authenticated                                   |
| `403`  | Authenticated but not a participant of the order    |
| `404`  | `order_id` does not exist (validation)              |
| `422`  | `order_id` missing                                  |
| `500`  | Render failure (logged; generic message returned)   |

**Example**

```bash
curl -L -H "Authorization: Bearer <token>" \
  "https://apps.fannan.ai/api/invoice/download?order_id=272" -o invoice.pdf
```

### 1.2 Supporting endpoints (used by the orders/invoice screens)

```
GET  /api/orders?per_page=15&page=1        # paginated orders for the authed client
GET  /api/order/status?order_id={id}       # lifecycle + payment status for one order
```

`/api/order/status` is also participant-guarded (`403` for non-participants — security issue M2)
and returns:

```json
{
  "success": true,
  "data": {
    "status": "COMPLETED",
    "payment_status": "PAID",
    "artist_name": "Alberto",
    "total_price": 0
  }
}
```

---

## 2. What goes on the invoice (data contract)

`InvoiceController::prepareInvoiceData(Order $order)` builds this structure and passes it to the
Blade template. This is the contract the backend fills:

```jsonc
{
  "invoice_number": "INV-7436-272",      // INV-{4-digit hash of order id}-{order id}
  "invoice_date":   "Mon, 7/6/2026",     // render date, format: D, n/j/Y
  "payment_status": "PAID",              // PAID when order.is_paid, else PENDING
  "event_name":     "Test Event",        // order.name (falls back to "Order #{id}")

  "billed_by": {                         // fixed Fannan tax identity (issuer)
    "company": "Fannan LLC",
    "cr":      "10530 0000 271325",
    "tax":     "4220216263694642",
    "email":   "info@fannan.ai"
  },

  "billed_to": {                         // from order.client (User)
    "client_id": 203,
    "name":      "Khaled",
    "email":     "khaled-hossam@outlook.com",
    "phone":     "1020700343"
  },

  "items": [                             // one row per performing artist (see §3)
    {
      "artist_name": "Alberto",          // artist.name
      "artist_id":   201,                // artist.id
      "address":     "Giza Al Hosary Mosque, Block 21, 6th Of October", // order.address.name
      "description": "Test Description",  // order.description
      "start":       "Tue, 9/1/2026 15:00:00",   // order_dates.start_date + start_time
      "end":         "Wed, 9/16/2026 15:00:00"    // order_dates.end_date + end_time
    }
  ],

  "totals": {                            // computed by OrderPricingService (see §4)
    "subtotal":   "0",                   // order cost (before tax/discount)
    "discount":   "0",                   // order.coupon_amount
    "tax":        "0",                   // cost * tax%
    "vat_fees":   "0",                   // (cost + tax - discount) * vat%
    "total_paid": "0",                   // subtotal + tax - discount + vat
    "currency":   "EGP"
  },

  "company": { /* footer + bottom-bar branding: hq, phone, hotline, website, terms_url */ }
}
```

### Field → source mapping

| Invoice field        | Source                                                            |
|----------------------|-------------------------------------------------------------------|
| Invoice ID           | Derived from `order.id` (stable) — swap for a real sequence later  |
| Invoice Date         | Server `now()` at render time                                     |
| Payment Status       | `order.is_paid` → `PAID` / `PENDING`                              |
| Event name           | `order.name`                                                     |
| Billed To            | `order.client` → `id`, `name`, `email`, `phone`                  |
| Artist row(s)        | `order.artist` (direct) or accepted bidding artists (§3)         |
| Address              | `order.address.name` (fallback `order.address.description`)      |
| Start / End          | `order.dates` first row → `start/end_date` + `start/end_time`    |
| Totals               | `OrderPricingService::breakdown(cost, discount)` (§4)            |
| Billed By / footer   | Hardcoded Fannan identity in the controller (§5)                 |

---

## 3. Artist rows (direct vs. bidding orders)

- **Direct order** (`type = direct`): one row — `order.artist`.
- **Bidding order** (`type = bidding`): one row per **accepted** artist
  (`order.acceptedBiddingOrderArtists`). All rows share the order's address, description and dates.

If no artist can be resolved, the row falls back to `N/A` values (the invoice still renders).

---

## 4. Totals (single source of truth)

Totals are **not** computed in the controller — they come from
[`OrderPricingService::breakdown()`](../app/Services/OrderPricingService.php) so the invoice can
never disagree with what the customer was quoted at checkout or actually charged.

```
tax        = cost * tax%
subtotal   = cost + tax - discount
vat_fees   = subtotal * vat%
total_paid = subtotal + vat_fees
```

- `tax%` and `vat%` come from the `settings` table (`type = 'tax'` / `type = 'vat'`, read via
  `->value`). Missing settings default to `0%`.
- `cost` is `order.total_cost` (last offer for direct orders, or sum of accepted bidding artists).
- `discount` is `order.coupon_amount`.
- Whole amounts render without decimals (`0`, `1500`); fractional amounts render with two (`1500.50`).

To change tax/VAT rates, update the `settings` rows — the invoice, the checkout quote and the
charge all update together.

---

## 5. Branding / issuer details

The **Billed By** block, the footer, and the dark bottom bar (CR, TAX, hotline, HQ address, terms
URL) are currently **hardcoded** in the controller (`billedBy()` and `companyDetails()`), matching
the Fannan invoice design. To make them editable, move these into the `settings` table and read
them the same way rates are read. Logos are embedded from `public/images/logo-white.png` (pink
header mark) and `public/images/logo-gold.png` (watermark) as base64 — DomPDF cannot reliably load
image paths, so bytes are inlined.

---

## 6. Testing the invoice locally

### Option A — instant visual preview (no data needed)

A **local-only** route renders the invoice in the browser. On an empty DB it uses built-in sample
data that mirrors the design (Khaled / Alberto / Test Event):

```
http://fannan.test/invoice/preview          # newest order, or sample data — HTML (fast to iterate)
http://fannan.test/invoice/preview?pdf=1     # the same, as the real PDF
http://fannan.test/invoice/preview/272       # a specific order id — HTML
http://fannan.test/invoice/preview/272?pdf=1 # a specific order id — PDF
```

The route is registered only when `APP_ENV=local` (see [`routes/web.php`](../routes/web.php)); the
controller enforces the same guard.

### Option B — seed a real order and test the real endpoint

```bash
php artisan db:seed --class=Database\\Seeders\\InvoiceDemoSeeder
```

Creates a fully-populated demo order (client **Khaled**, artist **Alberto**, address, dates, tax/VAT
settings) and prints the order id, client id, and preview URLs. Idempotent — safe to re-run. Then:

- Preview: open the printed `/invoice/preview/{id}` URL, or
- Hit the real authed endpoint with the demo client's token:
  `GET /api/invoice/download?order_id={id}`.

### Automated test

[`tests/Feature/InvoiceDownloadTest.php`](../tests/Feature/InvoiceDownloadTest.php) asserts a
participant can download a real PDF and a non-participant gets `403`:

```bash
php artisan test --filter=InvoiceDownloadTest
```
