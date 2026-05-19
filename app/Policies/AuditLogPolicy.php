<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isMainBranchAdmin() || $user->isBranchAdmin();
    }

    public function view(User $user, AuditLog $log): bool
    {
        if ($user->isOwner() || $user->isMainBranchAdmin()) {
            return true;
        }

        // Branch admins may only view logs whose actor is themselves or staff in their branch
        if ($user->isBranchAdmin()) {
            if ($log->actor_id === $user->id) {
                return true;
            }

            return \App\Models\User::where('id', $log->actor_id)
                ->where('role', 'staff')
                ->where('branch_id', $user->branch_id)
                ->exists();
        }

        return false;
    }
}
