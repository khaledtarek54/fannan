# Fannan Admin Panel (Filament 3) — Fixes & Enhancements

> **Scope:** everything the `/admin` Filament panel needs — real bugs that make pages
> error out or "look empty / not found", plus the missing management screens and dashboard
> work. Companion to [admin-panel.md](admin-panel.md) (which documents the *current* state).
>
> **Status:** proposed / not yet implemented. Author: engineering review, 2026‑07‑06.
> Every item cites `file:line` and gives a concrete fix.

---

## 0. TL;DR

The panel is functionally there (18 resources) but several screens **error out**, several
**create/edit forms are blank or broken**, and the whole thing is thin on the parts admins
actually need day‑to‑day (a real dashboard, ratings moderation, a full wallet ledger, push
notifications, cities). That combination is why it "feels like many things are missing".

| Priority | Meaning | Count |
|----------|---------|-------|
| **P0** | Page 500s / data corruption — fix now | 4 |
| **P1** | Broken or blank create/edit forms, silent lock‑outs | 7 |
| **P2** | Consistency / dead code / UX polish | 6 |
| **ENH** | Missing screens & dashboard (net‑new value) | 9 |

---

## 1. Why things "are not found"

Three distinct causes, often confused for "the feature is missing":

1. **Computed accessors used as sortable/searchable table columns.** Clicking a column
   header or the search box runs `ORDER BY <accessor>` / `WHERE <accessor> LIKE ?` against a
   column that doesn't exist → SQL `Unknown column` → a red error screen. (§2.1, §2.2)
2. **`groupBy('user_id')` with `SELECT *`** in the Support list queries → throws under
   MySQL `ONLY_FULL_GROUP_BY` (the default on MySQL ≥ 5.7 / MariaDB, which is what Hostinger
   runs) → the list page 500s. (§2.4)
3. **Forms hidden behind `->visibleOn('view')` or left empty (`//`)** → the "New" / "Edit"
   button opens a blank form that can't save, so it looks like the feature does nothing. (§3)

None of these depend on data being present, so they reproduce even on the empty local DB.

---

## 2. P0 — Pages that error out or corrupt data

### 2.1 DirectOrder status column crashes on sort/search
`app/Filament/Resources/DirectOrderResource.php:164‑167`

`status_value` is a **model accessor** (`Order::getStatusValueAttribute()`, `Order.php:99`)
computed from Spatie model‑status + offers. `orders` has **no `status` column at all**
(confirmed in `2024_06_24_034003_create_orders_table.php`). Marking it `->sortable()->searchable()`
emits `order by "status_value"` / `where "status_value" like ?` → `Unknown column`.

```php
// FIX — a computed column can display but must not be sorted/searched at the DB level.
BadgeColumn::make('status_value')
    ->label(trans('app.status'))
    // ->searchable()   ← remove
    // ->sortable()     ← remove
    ->colors([...])
    ->formatStateUsing(fn (string $state) => ...);
```
If sort/search is genuinely wanted, implement `->searchable(query: ...)` and
`->sortable(query: ...)` closures that translate to the underlying `model_statuses` table.

### 2.2 BiddingOrder categories column crashes on sort/search
`app/Filament/Resources/BiddingOrderResource.php:117‑120`

Same class of bug: `subcategories_text` is the accessor `Order::getSubcategoriesTextAttribute()`
(`Order.php:147`) and it **returns a `Collection`**, not a string, so even the display is wrong.
Remove `->sortable()->searchable()`, and fix the accessor to return an imploded string:

```php
// Order.php
public function getSubcategoriesTextAttribute(): string
{
    return $this->categories
        ->map(fn ($item) => $item->subcategory?->name)
        ->filter()->implode(', ');
}
```

### 2.3 "Mark as complete" closes EVERY open ticket
`app/Filament/Resources/SupportResource.php:95‑97`

The row action ignores the clicked `$record` and closes the whole table:

```php
// CURRENT (bug): closes all open tickets platform‑wide
->action(function ($record) {
    Support::query()->where('is_complete', 0)->update(['is_complete' => 1]);
})
// FIX: close only this user's thread
->action(fn ($record) => $record->update(['is_complete' => 1]))
```
(If the row represents a *user thread* rather than one message, scope to that user:
`Support::where('user_id', $record->user_id)->update(['is_complete' => 1])` — but never the
unfiltered table.)

### 2.4 Support list pages 500 under `ONLY_FULL_GROUP_BY`
`app/Filament/Resources/SupportResource.php:150` and
`app/Filament/Resources/CompletedSupportResource.php:123`

`Support::query()->groupBy('user_id')` with an implicit `select *` is invalid SQL when
`ONLY_FULL_GROUP_BY` is on (Hostinger default). Replace the "one row per user" trick with a
subquery that groups only an aggregate:

```php
public static function getEloquentQuery(): Builder
{
    return Support::query()
        ->where('is_complete', 0)
        ->whereIn('id', function ($q) {
            $q->selectRaw('MAX(id)')->from('supports')
              ->where('is_complete', 0)
              ->groupBy('user_id');
        })
        ->with('user.activeSupport')
        ->orderByDesc('id');
}
```

---

## 3. P1 — Broken / blank create & edit forms

### 3.1 Editing an admin silently wipes their password
`app/Filament/Resources/UserResource.php:72‑75` + `UserResource/Pages/EditUser.php:28‑32`

The password field is only `->hiddenOn(['view'])`, so it shows on **edit**, and
`EditUser::handleRecordUpdate()` passes the whole `$data` (including an empty `password`) to
`$record->update()`. `User::setPasswordAttribute()` (`User.php:239`) then runs
`Hash::make('')` → the admin is locked out on the next unrelated edit. Also `->minValue(6)` is
a numeric rule (no effect on a string) and the field renders in clear text.

```php
TextInput::make('password')
    ->password()
    ->revealable()
    ->minLength(6)
    ->dehydrated(fn ($state) => filled($state))            // ← don't save when left blank
    ->required(fn (string $context) => $context === 'create')
    ->hiddenOn('view');
```
`->dehydrated(fn ($state) => filled($state))` is the key line — it removes the empty value
from the payload so an edit never overwrites the hash. (Apply the same guard anywhere a
password field is editable.)

### 3.2 Withdrawal create is broken end‑to‑end
`app/Filament/Resources/WithdrawTransactionResource.php:46‑73`

- `type` is **never set**, but `transactions.type` is a `NOT NULL` enum with no default
  (`2024_08_06_120042_create_transactions_table.php`) → insert throws.
- Even if it saved, without `type = withdraw` it wouldn't match `getEloquentQuery()` and would
  vanish from the list.
- `user_id` isn't `->required()`, `amount` has no numeric/`min` validation and no check
  against the artist's available balance (`total_income − total_withdraw`).

```php
// WithdrawTransactionResource/Pages/CreateWithdrawTransaction.php
public function handleRecordCreation(array $data): Model
{
    $data['type'] = TransactionType::WITHDRAW->value;   // ← required
    return static::getModel()::create($data);
}
```
```php
// resource form
Select::make('user_id')->label(trans('app.artist'))->required() ...
TextInput::make('amount')->numeric()->minValue(1)->required()
    ->rule(fn (callable $get) => function ($attr, $value, $fail) use ($get) {
        $u = User::find($get('user_id'));
        if ($u && $value > ($u->total_income - $u->total_withdraw)) {
            $fail(trans('app.amount_exceeds_balance'));
        }
    });
```

### 3.3 DirectOrder create form can't produce a usable order
`app/Filament/Resources/DirectOrderResource.php:87‑134`

`category_id`, `subcategory_id`, `number`, `cost` are all `->required()` **and**
`->visibleOn(['view'])`. On the create page they're hidden, so an admin can never set the
category/cost — the created order is incomplete. Decide the intent:
- **If admins should create orders:** make `category_id`, `subcategory_id`, `cost` visible on
  `create`/`edit` (keep `number` read‑only, auto‑generated).
- **If orders are client‑generated only:** drop the create route and mark the resource
  read‑only (`canCreate(): bool { return false; }`), keep view + relation managers.

### 3.4 ArtistGallery — empty form, create route enabled
`app/Filament/Resources/ArtistGalleryResource.php:30‑32, 80`

`form()` is `//` (blank) but the create route is live, so "New" opens a blank form that
inserts empty rows; edit is commented out. Either populate the form or make it view/delete‑only.

```php
// If it should stay creatable (table user_gallery_works → user_id, video, type):
->schema([
    Select::make('user_id')->label('Artist')
        ->relationship('user', 'name')->searchable()->required(),
    Select::make('type')->options(['image' => 'Image', 'video' => 'Video'])->required(),
    FileUpload::make('video')->label('File')->directory('artist')->required(),
])
// Otherwise: remove the 'create' page route and delete the empty form.
```

### 3.5 SubCategory — empty form
`app/Filament/Resources/SubCategoryResource.php:27‑28`

Not in the nav (`shouldRegisterNavigation = false`) and managed via the Category repeater, but
its create/edit pages still render a blank form. Either add the fields
(`category_id` Select + translatable `name`) or remove the create/edit pages entirely and keep
it list‑only.

### 3.6 Contact — create/edit forms are blank (inbound‑only data)
`app/Filament/Resources/ContactResource.php:51‑62`

All fields are `->visibleOn('view')`, so create/edit are empty. Contacts arrive from the public
site — make the resource read‑only instead of pretending to be editable:

```php
public static function canCreate(): bool { return false; }
// remove the 'create' route from getPages(); drop the Edit action (keep View + Delete).
```

### 3.7 Client list hides soft‑deleted clients → Restore action is dead
`app/Filament/Resources/ClientResource.php:112` vs `160‑163`

The table sets its own `->query(User::where(...)->where('completed_profile', true))`, which
**shadows** `getEloquentQuery()` (`User::withTrashed()->client()`). Trashed clients therefore
never appear, so the Restore action (`:135`, visible only when `deleted_at` is set) can never
fire. Remove the redundant `->query(...)` and add a proper trashed filter:

```php
// delete the ->query(...) line, then:
->filters([ Tables\Filters\TrashedFilter::make() ])
```

---

## 4. P2 — Consistency, dead code, UX polish

| # | Item | Location | Fix |
|---|------|----------|-----|
| 4.1 | `value_ar` field writes the same value to `en` **and** `ar` | `TaxResource.php:62`, `EditTax.php:19‑26` | Fine for numbers, but rename field to `value` and drop the AR‑only label to stop confusing editors. |
| 4.2 | `category_id` is a free‑text `TextInput` on a FK | `CategoryResource/RelationManagers/UserCategoriesRelationManager.php:21` | Use `Select::make('category_id')->relationship('category','name')`. |
| 4.3 | Field typo `twiteer` | `ArtistResource.php:120` | Rename to match the DB column (`twitter`/`x`); add a migration if the column itself is misspelled. |
| 4.4 | Dead commented‑out fields | `SettingResource.php` (old textareas) | Delete. |
| 4.5 | `AddressResource` & `CategoryResource` have no nav group → float at the sidebar root | `AddressResource.php`, `CategoryResource.php` | Add `getNavigationGroup()` (e.g. *Configurations* / *Users*) or hide (`$shouldRegisterNavigation`). |
| 4.6 | `heroicon-o-rectangle-stack` reused for 4 unrelated resources (Address, Category, Tax, CompletedSupport) | multiple | Give each a distinct icon so the sidebar is scannable. |

---

## 5. ENH — Missing management screens (net‑new)

The mobile app produces data that has **no admin surface** today. Each is a small resource.

| # | Screen | Backing model / table | Why it's needed |
|---|--------|-----------------------|-----------------|
| 5.1 | **Ratings / Reviews** | `Rating` (`ratings`) | Read + moderate/delete abusive reviews; view per‑artist average. No admin view exists. |
| 5.2 | **Wallet / Transactions ledger** | `Transaction` (all types) | Today only *withdrawals* are shown. Admins can't see income entries, per‑artist balance, or the full money trail. Add a full ledger with `type` filter + per‑user balance column. |
| 5.3 | **Push Notifications (broadcast)** | `Notification` + existing `NotificationService` / `Notifications/PushNotification.php` | A custom Filament **Page** with a form (audience: all / clients / artists / one user, title, body) that dispatches FCM. High‑value marketing/ops tool; the plumbing already exists. |
| 5.4 | **Cities** | `City` (translatable) | Cities drive addresses, orders and artist coverage but can only be edited via DB. Simple translatable CRUD. |
| 5.5 | **Coupon usage** | `CouponUser` (`coupon_users`) | See who redeemed which coupon and when; a relation manager on `CouponResource` is enough. |
| 5.6 | **Unified Orders view** | `Order` | Direct + Bidding are split; a combined read‑only list with a `type` filter + payment status is what ops usually want. |
| 5.7 | **Chats (read‑only)** | `Chat` (`chats`) | Support/abuse investigations. Read‑only viewer, no editing. |
| 5.8 | **Order payment status column** | `Order.is_paid` + `OrderPaymentTransaction` | Surface paid/unpaid and the EasyKash transaction on the order table & view. |
| 5.9 | **Invoices** | `Order` (rendered PDF — no table) | Admins currently have **no way to see or download any invoice** — see §5A. |

Scaffold pattern (repeat per model):
```bash
php artisan make:filament-resource Rating --generate      # then trim columns/forms
php artisan make:filament-page BroadcastNotification       # for 5.3
```

---

## 5A. ENH — Invoices (no admin surface today)

**How invoices work (important):** there is **no `invoices` table**. An invoice is a PDF
rendered on demand from an `Order` + its relations by
[`InvoiceController`](../app/Http/Controllers/InvoiceController.php); the reference is
deterministic (`INV-{hash}-{orderId}`, `InvoiceController.php:330`) and the money totals come
from `OrderPricingService`. So "add invoices to admin" means **let admins view/download the
invoice for any order** (plus an invoice-oriented list) — not build a new CRUD table.

**Why admins can't see them now:** the only download route is
`GET/POST /api/invoice/download`, and it is **participant-guarded** — it `abort_unless` the
caller is the order's own client or artist (`InvoiceController.php:44‑46`, the H1 IDOR fix).
An admin is neither, so they get `403`. The render helpers (`prepareInvoiceData`, `renderPdf`,
`renderHtml`) are all `private`, so nothing else can reuse them either.

### Recommended approach

**Step 1 — extract the render logic into a reusable `InvoiceService`** so the API and Filament
share one code path and the strict API guard stays untouched:

```php
// app/Services/InvoiceService.php  (move prepareInvoiceData + renderPdf + helpers here)
class InvoiceService
{
    public const RELATIONS = ['client','artist','address','address.city',
        'categories.subcategory','dates','userTransaction'];

    public function pdf(Order $order): string;    // PDF bytes
    public function data(Order $order): array;     // prepared invoice data
    public function number(Order $order): string;  // INV-xxxx-id
}
```
`InvoiceController::download()` then becomes: validate → load → **keep the client/artist
`abort_unless`** → `app(InvoiceService::class)->pdf($order)`. No behaviour change on the API.

**Step 2 — add a "Download invoice" action to the order resources**
([DirectOrderResource](../app/Filament/Resources/DirectOrderResource.php),
[BiddingOrderResource](../app/Filament/Resources/BiddingOrderResource.php)) — a row action plus
a header action on the View page. The Filament panel already gates on `is_admin`
(`User::canAccessPanel`), so admins download any order's invoice **without** loosening the API's
participant guard:

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

Tables\Actions\Action::make('invoice')
    ->label(trans('app.download_invoice'))
    ->icon('heroicon-o-document-arrow-down')
    ->action(function (Order $record): StreamedResponse {
        $svc = app(\App\Services\InvoiceService::class);
        $order = $record->loadMissing(\App\Services\InvoiceService::RELATIONS);
        return response()->streamDownload(
            fn () => print($svc->pdf($order)),
            $svc->number($order) . '.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    });
```

**Step 3 (optional) — a dedicated read-only "Invoices" resource.** Scope `Order` to
invoiceable orders (`->where('is_paid', 1)`, or completed status) and show what finance needs:

- **Columns:** invoice number, client, artist(s), event date, **subtotal / discount / VAT /
  total** (from `OrderPricingService::breakdown()`), payment status, issued date.
  ⚠️ invoice number and the money totals are **computed** — display only; do **not** mark them
  `->sortable()/->searchable()` (same trap as §2.1). Sort/filter on real columns (`created_at`,
  `is_paid`).
- **Actions:** the same Download-invoice action; optionally "open PDF in a new tab".
- **Filters:** date range, paid/unpaid, by client/artist.

This list also cleanly absorbs §5.8 (payment status) — add an `is_paid` badge column here.

> **Reconcile the currency first:** `prepareInvoiceData` hardcodes `EGP` and a Cairo company
> address, while the order resources label costs `SAR`. Finance shouldn't rely on these totals
> until that mismatch is resolved.

---

## 6. ENH — Dashboard & widgets

Today the dashboard shows **one** widget with two counters, and it loads the **entire** users
table into PHP to count them:

```php
// UserResource/Widgets/UserWidget.php:13  — pulls every user into memory
$users = User::query()->where('completed_profile', 1)->get();
```

**Fixes + additions:**
1. Make the existing widget count in SQL:
   ```php
   Stat::make(__('app.active_clients'), User::client()->count()),
   Stat::make(__('app.active_artists'), User::artist()->count()),
   ```
2. Add a **business KPIs** `StatsOverviewWidget`: total GMV (sum of completed order cost),
   this‑month revenue (platform fees), orders today, pending withdrawals count, open support
   tickets. Use `->chart([...])` sparklines and `->description()` deltas.
3. Add an **Orders‑by‑status** chart widget (`ChartWidget`, doughnut) and a **revenue over
   time** line chart (last 12 months).
4. Add a **Latest orders** and **Pending withdrawals** table widget
   (`->tableQuery()` limited to 5) so ops see the queue on login.
5. Register them on a small custom Dashboard page and give each `->columnSpan()` /
   `getColumns()` so the layout isn't a single card on an empty screen.

---

## 7. ENH — Performance

| Issue | Location | Fix |
|-------|----------|-----|
| Select options load whole tables into memory on every form render (`User::client()->get()->pluck(...)`, `City::all()->pluck(...)`, `Category::all()...`) | DirectOrder/Bidding/Withdraw/Artist/Client resources | Use `->relationship('client','name')->searchable()` or `->getSearchResultsUsing()` / `->preload()` so it queries on demand. |
| Widget counts hydrate full collections | `UserWidget.php:13` | `->count()` in SQL (see §6.1). |
| Navigation badges run an extra query per resource on every page load | `getNavigationBadge()` in DirectOrder/Bidding/Artist/Client | Fine at small scale; cache with `Cache::remember(...)` if the sidebar feels slow. |
| Missing eager‑loading on some tables (`address.city.name`) | DirectOrderResource table | Add `->modifyQueryUsing(fn ($q) => $q->with('address.city','client','artist'))`. |

---

## 8. ENH — Access control (RBAC)

Panel access is a single boolean (`User::canAccessPanel()` → `is_admin`). **Every** admin can
do everything — delete users, edit platform fees, mark withdrawals paid. There are no roles.

- Add role‑based permissions (e.g. `bezhanSalleh/filament-shield`) with at least
  *Super‑admin* vs *Support/Ops* roles, so finance actions (withdrawals, fees) and destructive
  actions (delete/restore users) are gated.
- Until then, at minimum wrap the sensitive actions in `->visible(fn () => auth()->user()->...)`.
- Ties into the security review — see [SECURITY_ISSUES.md](SECURITY_ISSUES.md) /
  [SECURITY_ISSUES_ROUND2.md](SECURITY_ISSUES_ROUND2.md).

---

## 9. ENH — Localization polish

Nav groups already resolve (`app.orders → "Events"`, etc. in `lang/{en,ar}/app.php`). Remaining
gaps are **hardcoded English strings** in resources that should use `trans()`:
`'Artist'`, `'Phone'`, `'E‑Mail'`, `'City'`, `'Birthdate'`, `'Type'`, `'File'`, `'View'`,
`'Edit Fees'`, `'New Value'`, `'Mark as Completed'` (ArtistResource, ClientResource,
ArtistGalleryResource, WithdrawTransactionResource). Move these into `app.php` (en + ar).

---

## 10. Suggested delivery order

**Sprint 1 — stop the bleeding (P0 + the two worst P1):** §2.1–2.4, §3.1 (password wipe),
§3.2 (withdrawals). ~0.5–1 day. These are the "errors / broken" items users hit first.

**Sprint 2 — repair forms & lists (rest of P1):** §3.3–3.7. ~1 day.

**Sprint 3 — dashboard + top missing screens:** §6, §5A steps 1–2 (InvoiceService +
Download-invoice action — quick, high-value), then §5.1 Ratings, §5.2 Transactions ledger,
§5.4 Cities. ~2–3 days.

**Sprint 4 — value adds:** §5.3 push broadcast, §5.5–5.8, §7 performance, §8 RBAC, §9 i18n,
§4 polish. ~3–4 days.

---

## 11. Checklist

- [ ] §2.1 DirectOrder `status_value` — drop sortable/searchable
- [ ] §2.2 BiddingOrder `subcategories_text` — drop sortable/searchable + fix accessor
- [ ] §2.3 Support `mark_as_complete` — scope to `$record`
- [ ] §2.4 Support/CompletedSupport — replace `groupBy` with `whereIn(MAX(id))` subquery
- [ ] §3.1 UserResource password — `->dehydrated(filled)`, `->password()`, `->minLength(6)`
- [ ] §3.2 Withdrawal create — set `type`, require `user_id`, validate amount vs balance
- [ ] §3.3 DirectOrder create form — expose or disable creation
- [ ] §3.4 ArtistGallery — populate form or make view/delete‑only
- [ ] §3.5 SubCategory — populate form or remove create/edit pages
- [ ] §3.6 Contact — make read‑only (`canCreate=false`)
- [ ] §3.7 Client — remove shadow `->query()`, add `TrashedFilter`
- [ ] §4 polish (icons, nav groups, FK selects, dead code, typo)
- [ ] §5 new resources (Ratings, Transactions, Push, Cities, Coupon usage, Orders, Chats, payment status)
- [ ] §5A Invoices — extract `InvoiceService`, add Download-invoice action, optional Invoices list (+ fix EGP/SAR currency mismatch)
- [ ] §6 dashboard widgets (KPIs, charts, queues) + fix `UserWidget` counts
- [ ] §7 performance (relationship selects, eager loads)
- [ ] §8 RBAC (Shield / gated actions)
- [ ] §9 localize hardcoded strings
</content>
</invoke>
