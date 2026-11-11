<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterCaseToolbarPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_case_toolbar_labels_and_counts_only_additional_filters(): void
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
            ->assertSee('Payment Status: Partial')
            ->assertSee('data-filter-count="2"', false)
            ->assertSee('role="region" aria-labelledby="case-filter-panel-title"', false)
            ->assertDontSee('case-filter-scope-title')
            ->assertDontSee('case-filter-details-title')
            ->assertSee('name="date_preset"', false)
            ->assertSee('name="payment_status"', false)
            ->assertSee('Apply filters')
            ->assertSee('Clear filters')
            ->assertSee('name="case_status" value="ACTIVE"', false)
            ->assertSee('value="CUSTOM" selected', false)
            ->assertSee('value="PARTIAL" selected', false)
            ->assertSee('Oldest first')
            ->assertDontSee('Case Status: Active');
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
            ->assertSee('All Payment Status');
    }
}
