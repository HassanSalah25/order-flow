<?php

namespace App\Providers;

use App\Payments\PaymentGatewayManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PaymentGatewayManager::class,
            fn ($app) => new PaymentGatewayManager($app, $app['config']->get('payments.gateways', [])),
        );
    }

    public function boot(): void
    {
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
