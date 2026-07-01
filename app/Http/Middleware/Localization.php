<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check header request and determine localization
        $local = ($request->hasHeader("lang")) ? $request->header("lang") : "ar";
        // set laravel localization
        app()->setLocale($local);
        LaravelLocalization::setLocale($local);
        // continue request
        return $next($request);
    }
}
