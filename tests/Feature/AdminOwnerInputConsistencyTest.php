<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOwnerInputConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_dashboard_has_no_obsolete_operational_date_controls(): void
    {
        $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createUser('admin');
        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertViewIs('dashboards.system-admin')
            ->assertDontSee('name="date_filter"', false)
            ->assertSee('System Admin Dashboard');
    }

    public function test_unsupported_system_admin_filters_do_not_change_administrative_scope(): void
    {
        $one = $this->createBranch('BR001', 'Main Branch');
        $two = $this->createBranch('BR002', 'Branch Two');
        $admin = $this->createUser('admin');
        foreach (['this_week', 'INVALID_OPTION'] as $filter) {
            $this->actingAs($admin)->get('/admin?date_filter='.$filter.'&branch_id='.$two->id)
                ->assertOk()->assertViewIs('dashboards.system-admin')
                ->assertViewHas('branches', fn ($items) => $items->pluck('id')->all() === [$one->id, $two->id]);
        }
        $branchAdmin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch',
            'branch_id' => $two->id, 'is_active' => true]);
        $this->actingAs($branchAdmin)->get('/admin?date_filter=INVALID_OPTION&branch_id='.$one->id)->assertForbidden();
    }

    public function test_owner_dashboard_filter_validation_is_consistent(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->get('/owner?range=LAST_30_DAYS&branch_id=' . $branch->id)
            ->assertOk();

        $this->actingAs($owner)
            ->get('/owner?range=INVALID_RANGE')
            ->assertSessionHasErrors('range');
    }

    public function test_owner_analytics_filter_validation_is_consistent(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->get('/owner/branch-analytics?range=THIS_MONTH&branch_id=' . $branch->id)
            ->assertOk();

        $this->actingAs($owner)
            ->get('/owner/branch-analytics?range=LAST_30_DAYS')
            ->assertSessionHasErrors('range');
    }

    public function test_owner_reporting_filters_validate_consistently_across_pages(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');

        $validQuery = http_build_query([
            'branch_id' => $branch->id,
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
            'transport_option' => 'HEARSE',
            'embalming_status' => 'PENDING',
            'interment_from' => now()->subDays(3)->toDateString(),
            'interment_to' => now()->addDay()->toDateString(),
            'wake_days_min' => 1,
            'wake_days_max' => 7,
            'q' => 'CASE-123',
        ]);

        $this->actingAs($owner)
            ->get('/owner/sales-per-branch?' . $validQuery)
            ->assertRedirect(route('reports.index', ['report_type' => 'owner_branch_analytics'], absolute: false));

        $this->actingAs($owner)
            ->get('/owner/case-history?' . $validQuery)
            ->assertOk();

        $this->actingAs($owner)
            ->get('/reports/preview?report_type=owner_branch_analytics&date_from=2026-04-20&date_to=2026-04-01')
            ->assertSessionHasErrors('date_to');

        $this->actingAs($owner)
            ->get('/owner/case-history?q=DROP_TABLE!')
            ->assertSessionHasErrors('q');
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'admin_scope' => $role === 'admin' ? 'main' : null,
            'is_active' => true,
        ]);
    }

    private function createBranch(string $code, string $name): Branch
    {
        return Branch::create([
            'branch_code' => $code,
            'branch_name' => $name,
            'address' => 'Test Address',
            'is_active' => true,
        ]);
    }
}
