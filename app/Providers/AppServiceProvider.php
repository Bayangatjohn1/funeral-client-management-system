<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('components.pagination.table');
        Paginator::defaultSimpleView('components.pagination.table');
        $loopbackHosts = ['localhost', '127.0.0.1', '::1', '[::1]'];
        $hotHost = is_file(public_path('hot'))
            ? parse_url(trim(file_get_contents(public_path('hot'))), PHP_URL_HOST) : null;
        $lanUsingLocalVite = ! in_array(request()->getHost(), $loopbackHosts, true)
            && in_array($hotHost, $loopbackHosts, true);
        if (in_array(config('branch_sync.mode'), ['central', 'branch'], true) || $lanUsingLocalVite) {
            // LAN clients need same-origin built assets when Vite only serves loopback.
            \Illuminate\Support\Facades\Vite::useHotFile(storage_path('framework/branch-sync-production.hot'));
        }
    }
}
