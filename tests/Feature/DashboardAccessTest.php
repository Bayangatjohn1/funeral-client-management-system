<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_for_admin(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Administration Overview');
    }

    public function test_owner_dashboard_renders_for_owner(): void
    {
        $owner = $this->createUser('owner');

        $response = $this->actingAs($owner)->get('/owner');

        $response->assertOk();
        $response->assertSee('Executive Board');
    }

    public function test_staff_dashboard_renders_for_staff(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch);

        $response = $this->actingAs($staff)->get('/staff');

        $response->assertOk()
            ->assertSee('Staff Dashboard')
            ->assertSee('data-theme-toggle', false);
    }

    public function test_dashboard_redirects_users_to_their_role_homepages(): void
    {
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin');
        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'is_active' => true,
            'branch_id' => $branch->id,
            'can_encode_any_branch' => false,
        ]);
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertRedirect(route('owner.dashboard', absolute: false));

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect('/admin');

        $this->actingAs($branchAdmin)
            ->get('/dashboard')
            ->assertRedirect('/admin');

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertRedirect('/staff');
    }

    public function test_dashboard_routes_are_forbidden_for_wrong_roles(): void
    {
        $admin = $this->createUser('admin');
        $owner = $this->createUser('owner');
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch);

        $this->actingAs($staff)->get('/admin')->assertForbidden();
        $this->actingAs($owner)->get('/admin')->assertForbidden();

        $this->actingAs($staff)->get('/owner')->assertForbidden();
        $this->actingAs($admin)->get('/owner')->assertForbidden();

        $this->actingAs($admin)->get('/staff')->assertOk();
        $this->actingAs($owner)->get('/staff')->assertForbidden();
    }

    private function createUser(string $role, ?Branch $branch = null): User
    {
        if ($role === 'admin' && !$branch) {
            $branch = $this->createBranch('BR001', 'Main Branch');
        }

        return User::factory()->create([
            'role' => $role,
            'admin_scope' => $role === 'admin' ? 'main' : null,
            'is_active' => true,
            'branch_id' => $branch?->id,
            'can_encode_any_branch' => false,
        ]);
    }

    private function createBranch(string $code, string $name): Branch
    {
        return Branch::firstOrCreate(
            ['branch_code' => $code],
            [
                'branch_name' => $name,
                'address' => 'Test Address',
                'is_active' => true,
            ]
        );
    }
}
