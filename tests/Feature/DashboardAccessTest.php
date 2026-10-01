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
            ->assertSee('Good day,')
            ->assertSee('Managing Main Branch and all branch operations')
            ->assertSee('Payment Summary')
            ->assertSee('Payment Progress')
            ->assertSee('Active Cases')
            ->assertSee('Ongoing Operations')
            ->assertDontSee('Open Cases')
            ->assertSee('date_preset=month', false)
            ->assertDontSee('Active Services')
            ->assertSee('Operations Overview')
            ->assertSee('Currently in Wake')
            ->assertSee('Today’s Scheduled Events')
            ->assertSee('Upcoming Events')
            ->assertSee('Cases With Balance')
            ->assertSee('All Branches')
            ->assertSee('This Month')
            ->assertSee('Active &amp; Completed Cases', false)
            ->assertDontSee('name="date_filter"', false)
            ->assertDontSee('>Reset<', false)
            ->assertSee('payment_status=WITH_BALANCE', false)
            ->assertSee('case_scope=active_completed', false)
            ->assertDontSee('payment_status=UNPAID', false)
            ->assertSee('View all reminders')
            ->assertDontSee('<div class="topbar-notification-menu__chips">', false)
            ->assertDontSee('>Due<', false)
            ->assertDontSee('Mark all as read')
            ->assertSee(route('admin.reminders.index'))
            ->assertDontSee('View services')
            ->assertDontSee('Service Status Summary')
            ->assertDontSee('Add User')
            ->assertDontSee('New Branch')
            ->assertDontSee('Network Branches')
            ->assertDontSee('Active Terminals')
            ->assertDontSee('Service Catalogs')
            ->assertDontSee('System Audit Log')
            ->assertDontSee('Collections by Branch')
            ->assertDontSee('Cases by Branch');

        $this->actingAs($mainAdmin)
            ->get('/admin?branch_id=')
            ->assertOk()
            ->assertSee('Payment Summary')
            ->assertSee('Operations Overview')
            ->assertSee('All Branches');

        $this->actingAs($mainAdmin)
            ->get('/admin?branch_id=' . $branch->id)
            ->assertOk()
            ->assertSee(route('admin.reminders.index', ['branch_id' => $branch->id]));

        $this->actingAs($branchAdmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Good day,')
            ->assertSee('Managing branch operations - BR002 - North Branch')
            ->assertSee('Currently in Wake')
            ->assertSee('Scheduled Today')
            ->assertSee('Upcoming Events')
            ->assertSee('Payment Summary')
            ->assertDontSee('Quick Actions')
            ->assertSee('Payment Monitoring')
            ->assertDontSee('<div class="topbar-notification-menu__chips">', false)
            ->assertSee(route('admin.reminders.index', ['branch_id' => $branch->id]))
            ->assertDontSee('Record New Case')
            ->assertDontSee('Record Payment')
            ->assertDontSee('Open Service Records')
            ->assertDontSee('Network Branches')
            ->assertDontSee('Active Terminals')
            ->assertDontSee('Service Catalogs')
            ->assertDontSee('System Audit Log')
            ->assertDontSee('Recent Branch Activity')
            ->assertDontSee('Collections by Branch')
            ->assertDontSee('Cases by Branch')
            ->assertDontSee('Open Master Records');
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

    public function test_main_admin_defaults_to_all_branches_and_keeps_branch_drill_down(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'North Branch');
        $mainAdmin = $this->createUser('admin', $mainBranch);

        $response = $this->actingAs($mainAdmin)->get('/admin');

        $response
            ->assertOk()
            ->assertViewHas('selectedBranchId', null)
            ->assertSee('<option value="" selected', false)
            ->assertSee('All Branches')
            ->assertSee('Payment Summary')
            ->assertSee('Operations Overview')
            ->assertDontSee('name="date_filter"', false);

        $this->actingAs($mainAdmin)
            ->get('/admin?branch_id='.$otherBranch->id)
            ->assertOk()
            ->assertViewHas('selectedBranchId', $otherBranch->id)
            ->assertSee('BR002 - North Branch');
    }

    public function test_staff_dashboard_renders_for_staff(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch);

        $response = $this->actingAs($staff)->get('/staff');

        $response->assertOk()
            ->assertSee('Staff Dashboard')
            ->assertSee('Good day,')
            ->assertSee('Your Daily Workspace')
            ->assertSee('staff-workspace-label', false)
            ->assertDontSee('<header class="topbar">', false)
            ->assertSee('staff-header-card', false)
            ->assertSee('<div class="topbar-notification-wrap"', false)
            ->assertSee('data-theme-toggle', false)
            ->assertSee('Cases Today')
            ->assertSee('Package Selected')
            ->assertSee('Payments Today')
            ->assertSee('Go to Case Records')
            ->assertSee('Go to Payment Monitoring')
            ->assertSee('Go to Reminders')
            ->assertSee(route('staff.reminders.index', ['tab' => 'today']))
            ->assertSee(route('staff.reminders.index', ['tab' => 'upcoming']))
            ->assertDontSee('due_window=today', false)
            ->assertSee('Cases with remaining balances')
            ->assertSee("Today's Services and Interments", false)
            ->assertSee('Upcoming Interments')
            ->assertSee('Payment - Date &amp; Time', false)
            ->assertDontSee('<th class="text-right">Amount</th>', false)
            ->assertDontSee('<th class="text-center">Amount</th>', false)
            ->assertDontSee('<th class="text-center">Balance</th>', false)
            ->assertDontSee('data-month-card', false);
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
