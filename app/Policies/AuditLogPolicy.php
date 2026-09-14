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

        if ($user->isBranchAdmin()) {
            return $this->logBelongsToAssignedBranch($user, $log);
        }

        return false;
    }

    private function logBelongsToAssignedBranch(User $user, AuditLog $log): bool
    {
        if (! $user->branch_id) {
            return false;
        }

        $assignedBranchId = (int) $user->branch_id;

        return (int) $log->branch_id === $assignedBranchId
            || (int) $log->target_branch_id === $assignedBranchId;
    }
}
