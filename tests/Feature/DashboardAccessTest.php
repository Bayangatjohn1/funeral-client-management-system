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
        $response->assertSee('Admin Dashboard');
        $response->assertDontSee('<header class="topbar">', false);
        $response->assertSee('admin-dashboard-greeting', false);
        $response->assertSee('<div class="topbar-notification-wrap"', false);
    }

    public function test_admin_dashboard_content_is_scoped_by_admin_type(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $branch = $this->createBranch('BR002', 'North Branch');
        $mainAdmin = $this->createUser('admin', $mainBranch);
        $branchAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'is_active' => true,
            'branch_id' => $branch->id,
            'can_encode_any_branch' => false,
        ]);

        $this->actingAs($mainAdmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Managing Main Branch and all branch operations')
            ->assertSee('Network Branches')
            ->assertSee('System Audit Log')
            ->assertSee('Service Amount by Branch')
            ->assertSee('Case Volume Distribution');

        $this->actingAs($branchAdmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Managing branch operations - BR002 - North Branch')
            ->assertSee("Today's Schedules", false)
            ->assertSee('Recent Branch Activity')
            ->assertSee('Open Case Records')
            ->assertSee('Payment Monitoring')
            ->assertDontSee('Record New Case')
            ->assertDontSee('Record Payment')
            ->assertDontSee('Network Branches')
            ->assertDontSee('System Audit Log')
            ->assertDontSee('Service Amount by Branch')
            ->assertDontSee('Case Volume Distribution')
            ->assertDontSee('Open Master Records');
    }

    public function test_owner_dashboard_renders_for_owner(): void
    {
        $owner = $this->createUser('owner');

        $response = $this->actingAs($owner)->get('/owner');

        $response->assertOk();
        $response->assertSee('Owner Overview');
        $response->assertSee('Reports & Analytics', false);
        $response->assertDontSee('Recent Cases');
        $response->assertDontSee('Reminders &amp; Alerts', false);
        $response->assertDontSee('bi-bell', false);
    }

    public function test_staff_dashboard_renders_for_staff(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch);

        $response = $this->actingAs($staff)->get('/staff');

        $response->assertOk()
            ->assertSee('Staff Dashboard')
            ->assertDontSee('<header class="topbar">', false)
            ->assertSee('staff-header-card', false)
            ->assertSee('<div class="topbar-notification-wrap"', false)
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

        $this->actingAs($admin)->get('/staff')->assertForbidden();
        $this->actingAs($owner)->get('/staff')->assertForbidden();
    }

    public function test_admins_cannot_access_staff_case_and_payment_recording_workflows(): void
    {
        $mainAdmin = $this->createUser('admin');
        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'is_active' => true,
            'branch_id' => $branch->id,
            'can_encode_any_branch' => false,
        ]);

        foreach ([$mainAdmin, $branchAdmin] as $admin) {
            $this->actingAs($admin)->get('/staff')->assertForbidden();
            $this->actingAs($admin)->get(route('intake.main.create'))->assertForbidden();
            $this->actingAs($admin)->get(route('funeral-cases.create'))->assertForbidden();
            $this->actingAs($admin)->get(route('payments.index'))->assertForbidden();
            $this->assertFalse($admin->can('create', \App\Models\FuneralCase::class));
            $this->assertFalse($admin->can('create', \App\Models\Payment::class));
        }
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
