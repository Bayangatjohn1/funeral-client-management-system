<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Policies\ClientPolicy;
use App\Policies\DeceasedPolicy;
use App\Policies\FuneralCasePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\AuditLogPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        FuneralCase::class => FuneralCasePolicy::class,
        Payment::class => PaymentPolicy::class,
        Client::class => ClientPolicy::class,
        Deceased::class => DeceasedPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Owners are read-only but can view everything. We do not blanket-allow mutations.
        Gate::after(function ($user, $ability, $result) {
            if ($result === false && $user?->role === 'owner' && str_starts_with($ability, 'view')) {
                return true;
            }

            return $result;
        });
    }
}

