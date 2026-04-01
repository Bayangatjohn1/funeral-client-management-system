<?php

namespace App\Policies;

use App\Models\Deceased;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeceasedPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['staff', 'admin', 'owner'], true);
    }

    public function view(User $user, Deceased $deceased): bool
    {
        return $this->branchMatch($user, $deceased);
    }

    public function update(User $user, Deceased $deceased): bool
    {
        return $this->branchMatch($user, $deceased) && in_array($user->role, ['staff', 'admin'], true);
    }

    public function delete(User $user, Deceased $deceased): bool
    {
        return false;
    }

    private function branchMatch(User $user, Deceased $deceased): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        $allowed = method_exists($user, 'branchScopeIds') ? $user->branchScopeIds() : [];

        return in_array((int) $deceased->branch_id, $allowed, true);
    }
}

