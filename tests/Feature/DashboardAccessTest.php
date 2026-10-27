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
        $response->assertSee('System Admin Dashboard');
        $response->assertDontSee('<header class="topbar">', false);
        $response->assertSee('Good day,');
        $response->assertSee('Backup &amp; Recovery', false);
    }

    public function test_branch_dashboard_renders_case_summary_drawer(): void
    {
        $branch = $this->createBranch('BR009', 'Summary Branch');
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'is_active' => true, 'branch_id' => $branch->id]);
        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('Summary Branch')->assertSee('Currently in Wake')
            ->assertSee('Scheduled Today')->assertSee('Upcoming Events')
            ->assertSee('Cases With Balance')->assertSee('id="branchCaseSummary"', false);
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

        // System Admin intentionally uses the administration dashboard, not branch operations.
        $this->actingAs($mainAdmin)->get('/admin')->assertOk()
            ->assertViewIs('dashboards.system-admin')
            ->assertSee('System Admin Dashboard')
            ->assertSee('Backup &amp; Recovery', false)
            ->assertViewHas('branches', fn ($items) => $items->contains('id', $mainBranch->id) && $items->contains('id', $branch->id));

        $this->actingAs($branchAdmin)->get('/admin')->assertOk()
            ->assertViewIs('dashboards.admin')
            ->assertViewHas('selectedBranchId', $branch->id)
            ->assertSee('North Branch')->assertSee('Daily Operations')
            ->assertSee('Payments Received')->assertSee('Remaining Balance')
            ->assertSee('Payment Progress')->assertSee('Active Cases')
            ->assertSee('Currently in Wake')->assertSee('Scheduled Today')
            ->assertSee('Upcoming Events')->assertSee('Cases With Balance')
            ->assertSee(route('admin.reminders.index', ['branch_id' => $branch->id]))
            ->assertDontSee('Record New Case')->assertDontSee('Record Payment')
            ->assertDontSee('System Admin Dashboard');

    }

    public function test_owner_dashboard_renders_for_owner(): void
    {
        $owner = $this->createUser('owner');

        $response = $this->actingAs($owner)->get('/owner');

        $response->assertOk();
        $response->assertSee('Good day,');
        $response->assertSee('Owner Overview');
        $response->assertSee('eb-greeting--flat', false);
        $response->assertSee('Reports &amp; Analytics', false);
        $response->assertDontSee('Recent Cases');
        $response->assertDontSee('Reminders &amp; Alerts', false);
        $response->assertDontSee('bi-bell', false);
    }

    public function test_system_admin_uses_administration_dashboard_and_branch_filter_cannot_change_role(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'North Branch');
        $mainAdmin = $this->createUser('admin', $mainBranch);
        foreach (['/admin', '/admin?branch_id='.$otherBranch->id] as $url) {
            $this->actingAs($mainAdmin)->get($url)->assertOk()
                ->assertViewIs('dashboards.system-admin')
                ->assertViewHas('branches', fn ($items) => $items->count() === 2)
                ->assertDontSee('Record Payment');
        }
        $branchAdmin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch',
            'branch_id' => $otherBranch->id, 'is_active' => true]);
        $this->actingAs($branchAdmin)->get('/admin')->assertOk()
            ->assertViewIs('dashboards.admin')->assertViewHas('selectedBranchId', $otherBranch->id);
        $this->get('/admin?branch_id='.$mainBranch->id)->assertForbidden();
    }

    public function test_staff_dashboard_renders_for_staff(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch);

        $response = $this->actingAs($staff)->get('/staff');

        $response->assertOk()->assertViewIs('dashboards.staff')
            ->assertSee('Daily Operations')->assertSee('Ongoing Cases')
            ->assertSee('Recent Daily Activity')->assertSee('Schedules')
            ->assertSee('Outstanding Balances')->assertSee('Needs Attention')
            ->assertSee('BR001 - Main Branch')
            ->assertViewHas('dashboardBranch', fn ($item) => $item->id === $mainBranch->id)
            ->assertSee('data-activity-tab="cases"', false)
            ->assertSee('data-activity-tab="payments"', false)
            ->assertSee(route('intake.main.create'))
            ->assertSee(route('funeral-cases.index'))
            ->assertSee(route('payments.history'))
            ->assertSee(route('staff.reminders.index', ['tab' => 'today']))
            ->assertSee(route('staff.reminders.index', ['tab' => 'upcoming']))
            ->assertDontSee('<header class="topbar">', false)
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

    public function test_staff_funeral_case_create_route_uses_current_intake_flow(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch);

        $this->actingAs($staff)
            ->get(route('funeral-cases.create'))
            ->assertRedirect(route('intake.main.create'));
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
