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
        return $this->branchMatch($user, $case);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['staff', 'admin'], true);
    }

    public function update(User $user, FuneralCase $case): bool
    {
        return $this->branchMatch($user, $case) && in_array($user->role, ['staff', 'admin'], true);
    }

    public function delete(User $user, FuneralCase $case): bool
    {
        return false;
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
