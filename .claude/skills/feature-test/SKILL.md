---
name: feature-test
description: Write and run Laravel feature tests for the Fannan API. Invoke after implementing OR modifying any change/feature (new or changed endpoint, controller, model behavior, policy, security fix) to add/update the matching feature test and run it before calling the work done. Note — this project uses PHPUnit (class-based), not the Pest framework; Pest is NOT installed.
---

# Feature testing (Fannan)

**Standing rule:** After each change or feature, write or update the feature test that covers it, then run it. A change is not "done" until a test exercises the new behaviour (happy path + the authorization/failure path) and passes.

## Framework reality check

This project uses **PHPUnit 10** with Laravel's testing helpers — *not* the Pest framework (it isn't in `composer.json`). Tests are **class-based**, one class per file in `tests/Feature/`, extending `Tests\TestCase`. Match that style; do not introduce Pest's `it()`/`test()` function syntax.

## Conventions (copy the existing tests)

- File: `tests/Feature/<Thing>Test.php`, namespace `Tests\Feature`, class extends `Tests\TestCase`.
- Always `use Illuminate\Foundation\Testing\RefreshDatabase;` — the DB is wiped per test.
- Build state with factories, never raw inserts: `User::factory()->client()->create()`, `->artist()`, `->admin()`, `->unverified()`; `Order::factory()->create()` (creates its own client + artist).
- Authenticate against the API guard: `$this->actingAs($user, 'api')`. Passport's personal-access client is recreated for you in `Tests\TestCase::setUp()`.
- Hit endpoints with `getJson` / `postJson` (JSON assertions) or `get` for streamed downloads.
- Name methods `test_<behaviour_in_words>()` with a `: void` return type and a docblock linking to the issue/security note it guards, when relevant.
- Cover **both** the allowed path and the denied path. Ownership/authorization is the recurring bug class here — assert `403` for a non-participant, `200`/`201` for the legitimate actor.
- Streamed responses (PDF/file download): assert on `$response->streamedContent()`, not `getContent()`.

## Template

```php
<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_do_the_thing(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($order->client, 'api')
            ->postJson('/api/thing', ['order_id' => $order->id])
            ->assertStatus(200);
    }

    public function test_a_stranger_cannot_do_the_thing(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->client()->create(), 'api')
            ->postJson('/api/thing', ['order_id' => $order->id])
            ->assertStatus(403);
    }
}
```

## Running

```bash
php artisan test --filter=ThingTest   # the one you just wrote (fast feedback loop)
php artisan test                       # full suite before wrapping up
```

Tests run against a **MySQL database named `testing`** (`phpunit.xml` sets `DB_DATABASE=testing`, connection stays `mysql`). Create that empty DB in DBngin once if it doesn't exist. `phpunit.xml` also injects dummy `EASYKASH_*` env so payment services can construct.

## Checklist per change/feature

1. Implement the change.
2. Add or update `tests/Feature/<Thing>Test.php` — happy path **and** the failure/authorization path.
3. `php artisan test --filter=<Thing>Test` → green.
4. `php artisan test` → whole suite still green.
5. Only then report the work as done.
