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
        if (in_array(config('branch_sync.mode'), ['central', 'branch'], true)) {
            // Offline deployments must not follow a development server's public/hot marker.
            \Illuminate\Support\Facades\Vite::useHotFile(storage_path('framework/branch-sync-production.hot'));
        }
    }
}
