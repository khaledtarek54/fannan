<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompleteProfileMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()) {
            $user = auth()->user();
            if (($user->role == UserRole::CLIENT->value || $user->role == UserRole::ARTIST->value) && !$user->is_verified)
                return \response()->json([
                    'message' => trans('app.complete_profile_information'),
                    'status' => false,
                ], 400);
        }

        return $next($request);
    }
}
