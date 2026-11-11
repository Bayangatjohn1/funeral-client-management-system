<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\HistoricalCaseSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalCasesPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_roles_share_the_historical_worklist_and_preserve_branch_scope_controls(): void
    {
        config()->set('funeral.historical_cases_enabled', true);

        $branch = Branch::create([
            'branch_code' => 'BR-HISTORY',
            'branch_name' => 'Historical Branch',
            'address' => 'Test address',
            'is_active' => true,
        ]);
        $encoder = User::factory()->create([
            'name' => 'Historical Encoder',
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $submission = HistoricalCaseSubmission::create([
            'submission_code' => 'HCS-2026-001',
            'branch_id' => $branch->id,
            'encoded_by' => $encoder->id,
            'status' => HistoricalCaseSubmission::PENDING,
            'submission_data' => [
                'validated' => [
                    'client_first_name' => 'Maria',
                    'client_last_name' => 'Santos',
                    'deceased_first_name' => 'Juan',
                    'deceased_last_name' => 'Santos',
                    'wake_start_date' => '2026-10-01',
                    'interment_at' => '2026-10-04',
                ],
                'pricing' => ['total' => 50000],
                'historical_payments' => [],
            ],
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

        foreach ([[$branchAdmin, false], [$systemAdmin, true]] as [$actor, $showsBranchFilter]) {
            $index = $this->actingAs($actor)->get(route('historical-cases.index'));
            $index->assertOk()
                ->assertSee('data-worklist-consistent', false)
                ->assertSee('class="hc-worklist-shell"', false)
                ->assertSee('data-hc-search-matches', false)
                ->assertSee('data-clickable-row', false)
                ->assertSee('data-row-view-trigger', false)
                ->assertSee('data-historical-transition', false)
                ->assertSee('data-action-tooltip="View submission"', false)
                ->assertSee('HCS-2026-001')
                ->assertSee('Pending approval')
                ->assertSee('Cases per page')
                ->assertSee('Apply filters');

            if ($showsBranchFilter) {
                $index->assertSee('id="historicalBranch"', false);
            } else {
                $index->assertDontSee('id="historicalBranch"', false);
            }

            $detail = $this->actingAs($actor)->get(route('historical-cases.show', $submission));
            $detail->assertOk()
                ->assertSee('class="historical-case-detail-page"', false)
                ->assertSee('class="hc-detail-surface"', false)
                ->assertSee('Historical payment ledger')
                ->assertSee('Review decision')
                ->assertSee('Request changes')
                ->assertSee('Reject')
                ->assertSee('Approve &amp; create case', false);
        }
    }

    public function test_historical_case_feature_is_hidden_and_all_endpoints_are_blocked_when_disabled(): void
    {
        config()->set('funeral.historical_cases_enabled', false);

        $branch = Branch::create([
            'branch_code' => 'BRHISTOFF',
            'branch_name' => 'Historical Disabled Branch',
            'address' => 'Test address',
            'is_active' => true,
        ]);
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $submission = HistoricalCaseSubmission::create([
            'submission_code' => 'HCS-2026-OFF',
            'branch_id' => $branch->id,
            'encoded_by' => $staff->id,
            'status' => HistoricalCaseSubmission::PENDING,
            'submission_data' => ['validated' => []],
        ]);

        $this->actingAs($staff)->get(route('historical-intake.create'))->assertNotFound();
        $this->actingAs($staff)->post(route('historical-intake.store'))->assertNotFound();
        $this->actingAs($staff)->get(route('historical-cases.index'))->assertNotFound();
        $this->actingAs($staff)->get(route('historical-cases.show', $submission))->assertNotFound();
        $this->actingAs($staff)->get(route('historical-cases.resume', $submission))->assertNotFound();
        $this->actingAs($staff)->get(route('historical-cases.payment-proof', [$submission, 0]))->assertNotFound();
        $this->actingAs($staff)->post(route('historical-cases.decide', $submission), ['decision' => 'approve'])->assertNotFound();

        $this->actingAs($staff)->get(route('funeral-cases.index'))
            ->assertOk()
            ->assertDontSee('href="'.route('historical-cases.index').'"', false);
    }
}
