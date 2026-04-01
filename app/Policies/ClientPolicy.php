<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['staff', 'admin', 'owner'], true);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->branchMatch($user, $client);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['staff', 'admin'], true);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->branchMatch($user, $client) && in_array($user->role, ['staff', 'admin'], true);
    }

    public function delete(User $user, Client $client): bool
    {
        return false;
    }

    private function branchMatch(User $user, Client $client): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        $allowed = method_exists($user, 'branchScopeIds') ? $user->branchScopeIds() : [];

        return in_array((int) $client->branch_id, $allowed, true);
    }
}

