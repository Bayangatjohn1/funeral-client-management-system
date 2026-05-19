<?php

namespace App\Policies;

use App\Models\FuneralCase;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FuneralCasePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['staff', 'admin', 'owner'], true);
    }

    public function view(User $user, FuneralCase $case): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        if ($user->isMainBranchAdmin()) {
            return true;
        }

        if ($user->role === 'staff' && ($case->entry_source ?? 'MAIN') === 'OTHER_BRANCH') {
            return false;
        }

        if ($user->isBranchAdmin() || $user->role === 'staff') {
            return (int) $user->branch_id === (int) $case->branch_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === 'staff';
    }

    public function update(User $user, FuneralCase $case): bool
    {
        if (!in_array($user->role, ['staff', 'admin'], true)) {
            return false;
        }

        // Main Branch Admin may only edit cases that belong to their own (main) branch.
        if ($user->isMainBranchAdmin()) {
            $ownBranchId = $user->operationalBranchId();
            return $ownBranchId !== null && (int) $case->branch_id === (int) $ownBranchId;
        }

        return $this->branchMatch($user, $case);
    }

    public function delete(User $user, FuneralCase $case): bool
    {
        if (!in_array($user->role, ['staff', 'admin'], true)) {
            return false;
        }

        // Main Branch Admin may only delete cases that belong to their own (main) branch.
        if ($user->isMainBranchAdmin()) {
            $ownBranchId = $user->operationalBranchId();
            return $ownBranchId !== null && (int) $case->branch_id === (int) $ownBranchId;
        }

        return $this->branchMatch($user, $case);
    }

    private function branchMatch(User $user, FuneralCase $case): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        $allowed = method_exists($user, 'branchScopeIds') ? $user->branchScopeIds() : [];

        return in_array((int) $case->branch_id, $allowed, true);
    }
}
