<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Convert an authentication exception into a response.
     *
     * [FIX] The mobile API is stateless / token-based (Passport). An unauthenticated request to ANY
     * `api/*` route must ALWAYS get a clean 401 JSON — never a redirect to a `login` route. This app
     * has no `login` route, so the default redirect threw RouteNotFoundException ("Route [login] not
     * defined") and surfaced as a 500 whenever a protected endpoint was hit without
     * `Accept: application/json` (e.g. opened in a browser). Non-API (Filament admin) auth is left to
     * the framework/Filament, which manages its own login redirect.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => $exception->getMessage() ?: 'Unauthenticated.'], 401);
        }

        return parent::unauthenticated($request, $exception);
    }
}
