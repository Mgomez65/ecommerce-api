<?php

namespace App\Providers;

use App\Models\User;
use App\Services\MercadoPago\MercadoPagoClient;
use App\Services\MercadoPago\WebhookSignatureValidator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MercadoPagoClient::class, fn () => new MercadoPagoClient(
            config('services.mercadopago.access_token')
        ));

        $this->app->singleton(WebhookSignatureValidator::class, fn () => new WebhookSignatureValidator(
            config('services.mercadopago.webhook_secret')
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // General API traffic: generous, per-user when authenticated so one
        // client can't starve everyone else sharing an IP.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Credential/account endpoints (login, register, password reset):
        // tight limit by IP to slow down brute-force/credential-stuffing.
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Mercado Pago webhook: generous but bounded, so a misbehaving or
        // malicious sender can't force unlimited outbound calls to MP's API.
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // The default reset link points at Laravel's web 'password.reset'
        // route, which doesn't exist in this API-first app — point it at
        // the frontend page instead.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return rtrim(config('app.url'), '/')
                . '/resetear-password?token=' . $token
                . '&email=' . urlencode($user->email);
        });

        // Not tied to an Eloquent model, so a Gate rather than a Policy.
        Gate::define('viewDashboard', function (User $user) {
            return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR], true);
        });
    }
}
