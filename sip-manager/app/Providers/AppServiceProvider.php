<?php

namespace App\Providers;

use App\Services\AsteriskAmiService;
use App\Services\SipProvisioningService;
use Illuminate\Pagination\Paginator;
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
    }
}
