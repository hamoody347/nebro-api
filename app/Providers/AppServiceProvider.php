<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\LoggingService;
use App\Services\PaymentGatewayManager;
use App\Services\SSOManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Telescope is local-only and must NOT be auto-discovered.
        // Added to composer.json extra.laravel.dont-discover, registered here conditionally.
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }

        $this->app->singleton(SSOManager::class);
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->singleton(LoggingService::class);
    }

    public function boot(): void
    {
        //
    }
}
