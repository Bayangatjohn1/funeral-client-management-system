<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaffMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (! $user) {
            abort(403, 'Unauthorized');
        }

        $isIntakeStore = $request->routeIs('intake.store', 'intake.main.store', 'intake.other.store') && method_exists($user, 'isAdmin') && $user->isAdmin();
        $isOtherBranchReport = $request->routeIs('intake.other.create', 'funeral-cases.other-reports') && method_exists($user, 'isMainBranchAdmin') && $user->isMainBranchAdmin();
        $isBranchAdminReminder = $request->routeIs('staff.reminders.index') && method_exists($user, 'isBranchAdmin') && $user->isBranchAdmin();
        $isOwnerPaymentRedirect = $request->routeIs('payments.index') && method_exists($user, 'isOwner') && $user->isOwner();

        if ($user->role !== 'staff' && ! $isIntakeStore && ! $isOtherBranchReport && ! $isBranchAdminReminder && ! $isOwnerPaymentRedirect) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
