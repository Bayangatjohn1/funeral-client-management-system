<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['staff', 'admin', 'owner'], true);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->branchMatch($user, $payment);
    }

    public function create(User $user): bool
    {
        return $user->role === 'staff';
    }

    public function update(User $user, Payment $payment): bool
    {
        // Only staff may edit payment records. Main Branch Admin cannot edit
        // payment records regardless of branch — this also acts as a defensive
        // guard should the role check ever be relaxed in the future.
        if ($user->role !== 'staff') {
            return false;
        }

        // Main Branch Admin (staff role would not reach here, but kept explicit
        // for clarity): restrict to own branch only.
        if (method_exists($user, 'isMainBranchAdmin') && $user->isMainBranchAdmin()) {
            $ownBranchId = $user->operationalBranchId();
            return $ownBranchId !== null && (int) $payment->branch_id === (int) $ownBranchId;
        }

        return $this->branchMatch($user, $payment);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }

    private function branchMatch(User $user, Payment $payment): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        $allowed = method_exists($user, 'branchScopeIds') ? $user->branchScopeIds() : [];

        return in_array((int) $payment->branch_id, $allowed, true);
    }
}

