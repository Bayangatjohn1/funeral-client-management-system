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
        return in_array($user->role, ['staff', 'admin'], true);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->branchMatch($user, $payment) && in_array($user->role, ['staff', 'admin'], true);
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

