<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\AuditLog;
use App\Models\SystemBackup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_managed_account_counts_match_the_user_directory_scope(): void
    {
        Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'address' => 'Test', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'is_active' => true, 'branch_id' => null]);
        User::factory()->create(['role' => 'owner', 'is_active' => true]);
        User::factory()->create(['role' => 'owner', 'is_active' => false]);
        User::factory()->create(['role' => 'staff', 'is_active' => true]);
        User::factory()->create(['role' => 'staff', 'is_active' => false]);
        User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'is_active' => true, 'branch_id' => null]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertOk()->assertViewHas('activeUsers', 2)->assertViewHas('inactiveUsers', 1)
            ->assertViewHas('adminCounts', ['system' => 1, 'branch' => 0]);
        $this->get('/admin/users?status=active')->assertOk()->assertViewHas('users', fn ($users) => $users->total() === 2);
    }

    public function test_critical_recovery_issue_precedes_access_issues_and_dashboard_return_link_is_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'is_active' => true, 'branch_id' => null]);
        Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Needs Coverage', 'address' => 'Test', 'is_active' => true]);
        Branch::create(['branch_code' => 'BR003', 'branch_name' => 'Closed Branch', 'address' => 'Test', 'is_active' => false]);

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertViewHas('branches', fn ($branches) => $branches->firstWhere('branch_code', 'BR003')->coverage_status === 'Inactive' && $branches->firstWhere('branch_code', 'BR002')->coverage_status === 'Needs Admin')
            ->assertViewHas('attention', fn ($alerts) => $alerts->first()['severity'] === 'critical' && $alerts->count() === 2);
        $this->get('/admin/users?return_to='.urlencode(url('/admin')))->assertOk()->assertSee('Back to Dashboard');
    }

    public function test_healthy_coverage_and_recent_verified_backup_have_no_attention_items(): void
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'address' => 'Test', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'is_active' => true, 'branch_id' => null]);
        User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'is_active' => true, 'branch_id' => $branch->id]);
        SystemBackup::create(['status' => 'verified', 'verified_at' => now()]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertViewHas('backupOverdue', false)
            ->assertDontSee('id="needs-attention"', false)
            ->assertViewHas('attention', fn ($alerts) => $alerts->isEmpty())
            ->assertViewHas('branches', fn ($branches) => $branches->first()->coverage_status === 'Covered');
    }

    public function test_branch_warnings_are_grouped_with_a_destination_to_detailed_coverage(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'is_active' => true, 'branch_id' => null]);
        foreach (['BR002', 'BR003'] as $code) {
            Branch::create(['branch_code' => $code, 'branch_name' => $code, 'address' => 'Test', 'is_active' => true]);
        }
        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('2 branches need an active Branch Admin.')
            ->assertSee('href="#sa-coverage-title"', false)
            ->assertDontSee('BR002 has no active Branch Admin account.')
            ->assertViewHas('attention', fn ($alerts) => $alerts->count() === 3);
    }

    public function test_activity_overview_exposes_only_allowlisted_change_details(): void
    {
        Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'address' => 'Test', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'is_active' => true, 'branch_id' => null]);
        AuditLog::create(['actor_id' => $admin->id, 'action' => 'user.updated', 'entity_type' => 'user', 'entity_id' => 123, 'metadata' => [
            'before' => ['role' => 'staff', 'password' => 'private-before-marker'],
            'after' => ['role' => 'admin', 'password' => 'private-after-marker'],
        ]]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('staff → admin')
            ->assertDontSee('private-before-marker')->assertDontSee('private-after-marker');
    }

    public function test_recent_activity_is_bounded_and_audit_drilldown_selects_only_the_event(): void
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'address' => 'Test', 'is_active' => true]);
        $otherBranch = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Other Branch', 'address' => 'Test', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'is_active' => true, 'branch_id' => null]);
        $event = null;
        for ($i = 0; $i < 8; $i++) {
            $event = AuditLog::create(['actor_id' => $admin->id, 'action' => 'user.updated', 'entity_type' => 'user', 'entity_id' => $i + 1, 'branch_id' => $otherBranch->id]);
        }
        $this->actingAs($admin)->get('/admin')->assertOk()->assertViewHas('activities', fn ($items) => $items->count() === 5);
        $this->get('/admin/audit-logs?audit_log_id='.$event->id)->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->id === $event->id);
        $this->get('/admin/audit-logs/export-pdf?audit_log_id='.$event->id)->assertRedirect(route('reports.exportPdf', ['report_type' => 'audit_logs', 'audit_log_id' => $event->id]));
        $branchAdmin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'is_active' => true, 'branch_id' => $branch->id]);
        $this->actingAs($branchAdmin)->get('/admin/audit-logs?audit_log_id='.$event->id)->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 0);
    }
}
