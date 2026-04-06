<?php

namespace App\Providers;

use App\Models\CallContext;
use App\Services\AsteriskAmiService;
use App\Services\SipProvisioningService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AsteriskAmiService::class, function () {
            return new AsteriskAmiService();
        });

        $this->app->bind(SipProvisioningService::class, function () {
            return new SipProvisioningService();
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Force HTTPS when behind reverse proxy
        if (request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        // Bind {outbound_route} parameter to CallContext model
        Route::model('outbound_route', CallContext::class);
    }
}
