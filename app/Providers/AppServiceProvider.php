<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Use our custom dark-theme pagination
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.custom');

        // Force HTTPS scheme on Ngrok / Proxies to prevent Mixed Content CSS block
        if (request()->server('HTTP_X_FORWARDED_PROTO') === 'https' 
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || str_contains(request()->getHost(), 'ngrok')
            || str_contains(request()->header('User-Agent', ''), 'ngrok')
        ) {
            URL::forceScheme('https');
        }
    }
}
