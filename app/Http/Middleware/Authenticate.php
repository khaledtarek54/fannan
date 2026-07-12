<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // The API is stateless / token-based — never redirect an unauthenticated API request to a
        // `login` page. This app has no `login` route, so calling route('login') here threw
        // RouteNotFoundException (surfacing as a 500) for any api/* request that wasn't asking for
        // JSON. Returning null lets it render as a clean 401 (see App\Exceptions\Handler).
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        return route('login');
    }
}
