<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * App-wide guard: an unauthenticated request to ANY protected `api/*` route must return a clean
 * 401 JSON — never a 500. Previously a browser-style request (Accept: text/html) to an auth:api
 * route triggered a redirect to a non-existent `login` route → RouteNotFoundException → 500.
 */
class UnauthenticatedApiReturns401Test extends TestCase
{
    use RefreshDatabase;

    public static function protectedApiRoutes(): array
    {
        return [
            'invoice download' => ['/api/invoice/download?order_id=1'],
            'addresses'        => ['/api/address'],
            'orders list'      => ['/api/orders'],
        ];
    }

    /**
     * @dataProvider protectedApiRoutes
     */
    public function test_browser_style_unauthenticated_request_returns_401_not_500(string $uri): void
    {
        // Accept: text/html is what a browser (or a token-less client) sends — this used to 500.
        $this->get($uri, ['Accept' => 'text/html'])->assertStatus(401);
    }

    public function test_json_unauthenticated_request_also_returns_401(): void
    {
        $this->getJson('/api/invoice/download?order_id=1')->assertStatus(401);
    }
}
