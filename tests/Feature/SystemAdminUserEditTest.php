<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAdminUserEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_edit_a_user_and_keep_their_inactive_branch_selection(): void
    {
        $active = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'is_active' => true]);
        $assigned = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Assigned inactive', 'is_active' => false]);
        $other = Branch::create(['branch_code' => 'BR003', 'branch_name' => 'Other inactive', 'is_active' => false]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => $active->id, 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $assigned->id, 'is_active' => true]);
        $admin->two_factor_secret = (new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();
        $admin->two_factor_confirmed_at = now();
        $admin->save();

        $this->actingAs($admin)->withSession(['account_security' => \App\Support\AccountSecurity::fingerprint($admin), 'mfa_user_id' => $admin->id])->get(route('admin.users.edit', $staff))
            ->assertOk()
            ->assertViewHas('branches', fn ($branches) => $branches->contains('id', $active->id)
                && $branches->contains('id', $assigned->id)
                && ! $branches->contains('id', $other->id));
    }
}
