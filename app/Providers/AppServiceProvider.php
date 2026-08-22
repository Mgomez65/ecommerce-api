<?php

namespace App\Providers;

use App\Services\MercadoPago\MercadoPagoClient;
use App\Services\MercadoPago\WebhookSignatureValidator;
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
        //
    }
}
