<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterCaseToolbarPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_case_toolbar_exposes_case_status_as_an_additional_filter(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-TOOLBAR',
            'branch_name' => 'Toolbar Branch',
            'address' => 'Test Address',
            'is_active' => true,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.cases.index', [
            'q' => 'FC0008',
            'case_status' => 'ACTIVE',
            'date_preset' => 'CUSTOM',
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-19',
            'payment_status' => 'PARTIAL',
            'sort' => 'oldest',
        ]));

        $response->assertOk()
            ->assertSee('placeholder="e.g., FC0008, Juan Luna..."', false)
            ->assertSee('Date: Oct 1–19, 2026')
            ->assertSee('Case Status: Active')
            ->assertSee('Payment Status: Partial')
            ->assertSee('data-filter-count="3"', false)
            ->assertSee('role="region" aria-labelledby="case-filter-panel-title"', false)
            ->assertDontSee('case-filter-scope-title')
            ->assertDontSee('case-filter-details-title')
            ->assertSee('name="date_preset"', false)
            ->assertSee('name="payment_status"', false)
            ->assertSee('Apply filters')
            ->assertSee('Clear filters')
            ->assertSee('name="case_status"', false)
            ->assertSee('value="ACTIVE" selected', false)
            ->assertSee('value="CUSTOM" selected', false)
            ->assertSee('value="PARTIAL" selected', false)
            ->assertSee('Oldest first');
    }

    public function test_case_records_title_is_role_appropriate_for_admin_scope(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-TITLE',
            'branch_name' => 'Title Branch',
            'address' => 'Test Address',
            'is_active' => true,
        ]);
        $branchAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $systemAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'system',
            'is_active' => true,
        ]);

        $this->actingAs($branchAdmin)->get(route('admin.cases.index'))
            ->assertOk()
            ->assertSee('You are viewing Case Records.')
            ->assertDontSee('Master Case Records');

        $this->actingAs($systemAdmin)->get(route('admin.cases.index'))
            ->assertOk()
            ->assertSee('You are viewing Master Case Records.');
    }

    public function test_staff_and_owner_case_worklists_render_the_case_status_filter(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-STATUS',
            'branch_name' => 'Status Branch',
            'address' => 'Test Address',
            'is_active' => true,
        ]);
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $owner = User::factory()->create([
            'role' => 'owner',
            'branch_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($staff)->get(route('funeral-cases.index', ['case_status' => 'ACTIVE']))
            ->assertOk()
            ->assertSee('name="case_status"', false)
            ->assertSee('value="ACTIVE" selected', false)
            ->assertSee('Case Status: Active');

        $this->actingAs($owner)->get(route('owner.history', ['case_status' => 'COMPLETED']))
            ->assertOk()
            ->assertSee('name="case_status"', false)
            ->assertSee('value="COMPLETED" selected', false)
            ->assertSee('Case Status: Completed');
    }

    public function test_system_admin_branch_selection_is_visible_in_the_filter_control(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-SELECTED',
            'branch_name' => 'Selected Branch',
            'address' => 'Test Address',
            'is_active' => true,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'system',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.cases.index', [
            'branch_id' => $branch->id,
        ]));

        $response->assertOk()
            ->assertSee('Branch: BR-SELECTED - Selected Branch')
            ->assertSee('value="'.$branch->id.'" selected', false)
            ->assertSee('data-filter-count="1"', false)
            ->assertSee('Clear filters');
    }

    public function test_master_case_filter_panel_hides_clear_all_when_no_additional_filters_are_active(): void
    {
        Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'branch_type' => 'main', 'is_active' => true]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'system',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.cases.index'));

        $response->assertOk()
            ->assertSee('data-filter-count="0"', false)
            ->assertDontSee('<span>Clear filters</span>', false)
            ->assertSee('All Dates')
            ->assertSee('All Case Status')
            ->assertSee('All Payment Status');
    }
}
