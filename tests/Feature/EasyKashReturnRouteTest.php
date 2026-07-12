<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the cleanup that removed the broken `payments/easykash/return` web route
 * (its controller App\Http\Controllers\EasyKashController@returnRedirect never existed —
 * baseline scaffolding that 500s on hit and breaks `route:list`). The real EasyKash
 * shopper-return URL is the public API callback GET, which must keep redirecting to the
 * success/failed pages based on the record's STORED state (never mutating it — see C2).
 */
class EasyKashReturnRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_removed_easykash_return_web_route_is_gone(): void
    {
        $this->get('/payments/easykash/return')->assertNotFound();
    }

    public function test_the_real_easykash_callback_get_redirects_for_an_unknown_reference(): void
    {
        // Public (unauthenticated) gateway-return endpoint; unknown reference => failed page.
        $this->get('/api/easykash/callback?customerReference=does-not-exist')
            ->assertRedirect('/payment-failed.html');
    }
}
