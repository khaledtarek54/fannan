<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // [SECURITY][R2-M6] Give issued tokens a finite lifetime — they were effectively
        // non-expiring (no expiry was ever configured). Kept long (1 year) because the mobile
        // client has no refresh-token flow; the sharper control is revoking a user's tokens on
        // password reset (see UserRepository::updatePassword).
        Passport::personalAccessTokensExpireIn(now()->addYear());
        Passport::tokensExpireIn(now()->addYear());
        Passport::refreshTokensExpireIn(now()->addYears(2));
    }
}
