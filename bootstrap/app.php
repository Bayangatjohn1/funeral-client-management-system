<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'owner' => \App\Http\Middleware\OwnerMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'main_admin' => \App\Http\Middleware\MainBranchAdminMiddleware::class,
            'staff' => \App\Http\Middleware\StaffMiddleware::class,
            'staff_or_admin' => \App\Http\Middleware\StaffOrAdminMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'no_cache' => \App\Http\Middleware\NoCache::class,
            'branch.scope' => \App\Http\Middleware\BranchScopeMiddleware::class,
            'log.request' => \App\Http\Middleware\LogRequestTime::class,
        ]);
    })
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
        \App\Providers\AuthServiceProvider::class,
    ])


    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
