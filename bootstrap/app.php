<?php

use Illuminate\Foundation\Application;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require base_path('routes/branch-sync.php');
        },
    )
    ->withCommands([
        \App\Console\Commands\CompletePastInterments::class,
        \App\Console\Commands\CreateSystemBackup::class,
        \App\Console\Commands\ReviewRecordRetention::class,
        \App\Console\Commands\BranchSync::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        if (config('branch_sync.mode') === 'branch') {
            $schedule->command('branch:sync run')->everyMinute()->withoutOverlapping(15);
        }
        $schedule->command('cases:complete-past-interments')->everyMinute();
        $backup = $schedule->command('system:backup --type=scheduled')->withoutOverlapping(15);
        if (config('branch_sync.mode') === 'branch') {
            $backup->hourly();
        } else {
            $backup->dailyAt(config('backup.daily_time'));
        }
        $schedule->call(fn () => app(\App\Services\SystemBackupService::class)->deleteExpired())->name('system-backup-retention')->dailyAt('02:30')->withoutOverlapping();
        $schedule->command('retention:review')->dailyAt('03:00')->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->web(append: [\App\Http\Middleware\BranchDeploymentMode::class, \App\Http\Middleware\EnsureIdleSession::class, \App\Http\Middleware\EnsureAccountSecurity::class]);

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
        $exceptions->dontFlash(['token', 'code', 'current_password', 'password', 'password_confirmation']);
    })->create();
