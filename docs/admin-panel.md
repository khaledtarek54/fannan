# Admin Panel (Filament 3)

Provider: `app/Providers/Filament/AdminPanelProvider.php`.

## Panel configuration

| Property | Value |
|----------|-------|
| Panel ID | `admin` |
| Path | `/admin` |
| Auth guard | `web` (default) |
| Login | default Filament login page (`->login()`) |
| Auth middleware | `Filament\Http\Middleware\Authenticate` |
| Plugins | `FilamentTranslatableFieldsPlugin` (locales: en, ar) |
| Branding | custom "Co Headline" font; purple/pink/red/indigo palette; custom favicon |
| Auto-discovery | Resources in `app/Filament/Resources` (18), Widgets in `app/Filament/Widgets` |

## Access control ✅ (fixed — A1)

`app/Models/User.php` → `canAccessPanel()`:

```php
public function canAccessPanel(Panel $panel): bool
{
    return (bool) $this->is_admin; // only explicit admins
}
```

This used to `return true` — **every** authenticated client/artist could enter `/admin` (finding A1). It is now gated on a dedicated `users.is_admin` column (added by migration; intentionally **not** mass-assignable). The column defaults to `false`, so after deploying you must flag the real admin(s):
`UPDATE users SET is_admin = 1 WHERE email = '...';`
See [SECURITY_ISSUES.md](SECURITY_ISSUES.md) A1.

## Resources (18) by navigation group

**Users**
- `UserResource` — admin/staff-style user records; soft-delete restore action.
- `ClientResource` — CRUD of `client`-role users (query-filtered).
- `ArtistResource` — CRUD of `artist`-role users; per-record + bulk `platform_fees` editing.
- `ArtistGalleryResource` — read-only artist portfolio.

**Orders**
- `DirectOrderResource` — `Order` where `type=direct`; relation managers: Categories, Dates, Offers, Supports.
- `BiddingOrderResource` — `Order` where `type=bidding`; relation manager: BiddingOrderArtists.

**Supports**
- `SupportResource` — active tickets; reply + close-with-reason.
- `CompletedSupportResource` — closed tickets (read-only, bulk archive).
- `ContactResource` — contact-form submissions (read-only).

**Promotions**
- `AdResource` — CRUD of ads with status badges.
- `CouponResource` — CRUD of coupons (fixed/percentage).

**Transactions**
- `WithdrawTransactionResource` — artist withdrawal requests (read-only + status).

**Configurations**
- `SettingResource` — platform settings (rich text / textarea).
- `PriceRangeResource` — price tiers.
- `TaxResource` — tax config (read-only, `canCreate=false`).

**Hidden (no nav)**
- `CategoryResource` — categories with inline subcategory repeater; translatable (en/ar); relation manager: UserCategories.
- `SubCategoryResource` — managed via Category (`shouldRegisterNavigation=false`).
- `AddressResource` — address records.

## Widgets

- `UserResource/Widgets/UserWidget` — dashboard stat card: active clients and active artists (`completed_profile=1`, by role). Registered on the default dashboard. No other custom pages/dashboards exist.

## Localization

Translatable admin fields (via `FilamentTranslatableFieldsPlugin`, en/ar): Category name, SubCategory name (inline + resource). UI strings in `lang/{en,ar}/app.php` and `lang/{en,ar}/front.php`.
