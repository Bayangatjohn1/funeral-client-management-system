<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemRegressionGuardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_main_staff_cannot_view_other_branch_case_details_directly(): void
    {
        $otherBranch = $this->createBranch('BR002', 'Other Branch');
        $package = $this->createPackage();
        $client = $this->createClient($otherBranch, 'Other Client');
        $deceased = $this->createDeceased($otherBranch, $client, 'Other Deceased');
        $case = $this->createCase($otherBranch, $client, $deceased, $package, [
            'entry_source' => 'OTHER_BRANCH',
            'case_status' => 'COMPLETED',
            'payment_status' => 'PAID',
            'total_paid' => 20000,
            'balance_amount' => 0,
            'reporter_name' => 'Reporter One',
        ]);

        $otherStaff = $this->createUser('staff', $otherBranch, false);

        $this->actingAs($otherStaff)
            ->get("/funeral-cases/{$case->id}")
            ->assertForbidden();
    }

    public function test_owner_analytics_charts_exclude_unverified_cases(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');
        $package = $this->createPackage();

        $verifiedClient = $this->createClient($branch, 'Verified Client');
        $verifiedDeceased = $this->createDeceased($branch, $verifiedClient, 'Verified Deceased');
        $pendingClient = $this->createClient($branch, 'Pending Client', '09170000001', 'Pending Address');
        $pendingDeceased = $this->createDeceased($branch, $pendingClient, 'Pending Deceased', [
            'address' => 'Pending Address',
        ]);

        $this->createCase($branch, $verifiedClient, $verifiedDeceased, $package, [
            'case_code' => 'FC1001',
            'total_amount' => 10000,
            'total_paid' => 10000,
            'balance_amount' => 0,
            'payment_status' => 'PAID',
            'verification_status' => 'VERIFIED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createCase($branch, $pendingClient, $pendingDeceased, $package, [
            'case_code' => 'FC1002',
            'total_amount' => 20000,
            'total_paid' => 20000,
            'balance_amount' => 0,
            'payment_status' => 'PAID',
            'verification_status' => 'PENDING',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get('/owner/branch-analytics?range=THIS_MONTH&branch_id=' . $branch->id)
            ->assertOk()
            ->assertViewHas('chart', function (array $chart) {
                return round((float) array_sum($chart['line']['data']), 2) === 10000.00
                    && round((float) array_sum($chart['bar']['revenue']), 2) === 10000.00;
            });
    }

    public function test_owner_dashboard_sales_include_partial_and_unpaid_gross_amounts(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');
        $package = $this->createPackage();
        $paidClient = $this->createClient($branch, 'Owner Gross Paid Client');
        $paidDeceased = $this->createDeceased($branch, $paidClient, 'Owner Gross Paid Deceased');
        $partialClient = $this->createClient($branch, 'Owner Gross Partial Client');
        $partialDeceased = $this->createDeceased($branch, $partialClient, 'Owner Gross Partial Deceased');
        $unpaidClient = $this->createClient($branch, 'Owner Gross Unpaid Client');
        $unpaidDeceased = $this->createDeceased($branch, $unpaidClient, 'Owner Gross Unpaid Deceased');

        $this->createCase($branch, $paidClient, $paidDeceased, $package, [
            'case_code' => 'GROSS-PAID',
            'total_amount' => 10000,
            'total_paid' => 10000,
            'balance_amount' => 0,
            'payment_status' => 'PAID',
            'verification_status' => 'VERIFIED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createCase($branch, $partialClient, $partialDeceased, $package, [
            'case_code' => 'GROSS-PARTIAL',
            'total_amount' => 15000,
            'total_paid' => 5000,
            'balance_amount' => 10000,
            'payment_status' => 'PARTIAL',
            'verification_status' => 'VERIFIED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createCase($branch, $unpaidClient, $unpaidDeceased, $package, [
            'case_code' => 'GROSS-UNPAID',
            'total_amount' => 20000,
            'total_paid' => 0,
            'balance_amount' => 20000,
            'payment_status' => 'UNPAID',
            'verification_status' => 'VERIFIED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get('/owner?range=THIS_MONTH&branch_id=' . $branch->id)
            ->assertOk()
            ->assertViewHas('totalSales', 45000.0)
            ->assertViewHas('totalCollected', 15000.0)
            ->assertViewHas('totalOutstanding', 30000.0);
    }

    public function test_owner_overview_packages_trend_and_collection_links_include_partial_cases(): void
    {
        $branch = $this->createBranch('BR021', 'Review Branch');
        $owner = $this->createUser('owner');
        $package = $this->createPackage();
        $client = $this->createClient($branch, 'Review Family');
        $deceased = $this->createDeceased($branch, $client, 'Review Deceased');
        $case = $this->createCase($branch, $client, $deceased, $package, [
            'case_code' => 'REVIEW-PARTIAL', 'verification_status' => 'VERIFIED',
            'total_amount' => 10000, 'total_paid' => 2000, 'balance_amount' => 8000,
            'payment_status' => 'PARTIAL', 'created_at' => now(),
            'interment_at' => now()->subDay(),
        ]);
        $this->actingAs($owner)->get('/owner?range=THIS_MONTH')->assertOk()
            ->assertViewHas('topPackages', fn ($rows) => $rows->sum('total_cases') === 1)
            ->assertViewHas('revenueTrend', fn ($rows) => $rows->sum('value') === 10000.0)
            ->assertViewHas('attentionCount', 1)->assertViewHas('attentionBalance', 8000.0);
        foreach (['WITH_PAYMENTS', 'WITH_BALANCE', 'AFTER_INTERMENT'] as $state) {
            $this->actingAs($owner)->get('/owner/case-history?collection_state='.$state)
                ->assertOk()->assertViewHas('cases', fn ($rows) => $rows->contains('id', $case->id));
        }
        $this->actingAs($owner)->get('/owner?range=THIS_YEAR&branch_id='.$branch->id)
            ->assertOk()->assertViewHas('branchCards', fn ($rows) => $rows->count() === 1)
            ->assertViewHas('revenueTrend', fn ($rows) => $rows->sum('value') === 10000.0);
        $this->actingAs($owner)->get('/owner/case-history?collection_state=INVALID')->assertSessionHasErrors('collection_state');
    }

    public function test_owner_executive_metrics_use_receipt_dates_and_all_date_balances(): void
    {
        $branch = $this->createBranch('BR031', 'Executive Branch');
        $owner = $this->createUser('owner');
        $package = $this->createPackage();
        $client = $this->createClient($branch, 'Executive Client');
        $deceased = $this->createDeceased($branch, $client, 'Executive Deceased');
        $oldCase = $this->createCase($branch, $client, $deceased, $package, [
            'case_code' => 'EXEC-OLD', 'verification_status' => 'VERIFIED',
            'created_at' => now()->subMonths(2), 'total_amount' => 10000,
            'total_paid' => 2000, 'balance_amount' => 8000, 'payment_status' => 'PARTIAL',
            'interment_at' => now()->subMonth(),
        ]);
        $oldCase->forceFill(['created_at' => now()->subMonths(2)])->save();
        foreach (['POSTED' => 2000, 'VOIDED' => 9000] as $status => $amount) {
            Payment::create(['funeral_case_id'=>$oldCase->id,'branch_id'=>$branch->id,
                'method'=>'CASH','payment_mode'=>'cash','amount'=>$amount,'paid_at'=>now(),
                'paid_date'=>now()->toDateString(),'status'=>$status]);
        }
        $pendingClient = $this->createClient($branch, 'Pending Executive Client');
        $pendingDeceased = $this->createDeceased($branch, $pendingClient, 'Pending Executive Deceased');
        $pending = $this->createCase($branch, $pendingClient, $pendingDeceased, $package, [
            'case_code' => 'EXEC-PENDING', 'verification_status' => 'PENDING',
        ]);
        Payment::create(['funeral_case_id'=>$pending->id,'branch_id'=>$branch->id,
            'method'=>'CASH','amount'=>6000,'paid_at'=>now(),'paid_date'=>now()->toDateString(),'status'=>'POSTED']);
        $response = $this->actingAs($owner)->get('/owner?range=THIS_MONTH&branch_id='.$branch->id)->assertOk();
        $o = $response->viewData('overview');
        $this->assertSame(0, $o['current']['cases']);
        $this->assertSame(2000.0, $o['current']['collections']);
        $this->assertSame(8000.0, $o['current']['outstanding']);
        $this->assertSame(1, $o['riskCount']);
        $this->assertSame(2000.0, (float) collect($o['points'])->sum('collections'));
        $this->assertNull($o['comparison']['collections']);
        $this->assertNull($o['branchRows']->first()['rate']);
        $this->assertSame(0.0, $o['branchRows']->first()['cohortCollected']);
        $this->actingAs($owner)->get('/owner/dashboard/details?kind=collections&range=THIS_MONTH&branch_id='.$branch->id)
            ->assertOk()->assertJsonPath('count', 1);
        $drilldown = $this->actingAs($owner)->get('/owner/dashboard/details?kind=collections&range=CUSTOM&date_from='.now()->toDateString().'&date_to='.now()->toDateString().'&branch_id='.$branch->id.'&dashboard_range=THIS_MONTH&dashboard_from='.now()->startOfMonth()->toDateString().'&dashboard_to='.now()->toDateString())
            ->assertOk()->assertJsonPath('count', 1);
        $this->assertStringContainsString('range%3DTHIS_MONTH', $drilldown->json('html'));
        $this->assertStringContainsString('2,000.00', $drilldown->json('html'));
        $collectionSummary = $this->actingAs($owner)->get('/owner/dashboard/details?summary=1&kind=collections&range=CUSTOM&date_from='.now()->toDateString().'&date_to='.now()->toDateString().'&branch_id='.$branch->id)->assertOk();
        preg_match('/data-records-url="([^"]+)"/', $collectionSummary->json('html'), $destinationMatch);
        $destinationUrl = html_entity_decode($destinationMatch[1], ENT_QUOTES);
        $this->assertSame('/reports', parse_url($destinationUrl, PHP_URL_PATH));
        parse_str(parse_url($destinationUrl, PHP_URL_QUERY), $destinationFilters);
        $this->assertSame('sales', $destinationFilters['report_type']);
        $this->assertSame((string) $branch->id, $destinationFilters['branch_id']);
        $this->assertSame(now()->toDateString(), $destinationFilters['date_from']);
        $this->assertSame(now()->toDateString(), $destinationFilters['date_to']);
        $this->actingAs($owner)->get($destinationUrl)->assertOk();
        $this->actingAs($owner)->get('/owner/dashboard/details?kind=risks&range=TODAY&branch_id='.$branch->id)
            ->assertOk()->assertJsonPath('count', 1);
        $summary = $this->actingAs($owner)->get('/owner/dashboard/details?summary=1&kind=risks&range=TODAY&branch_id='.$branch->id.'&dashboard_range=THIS_MONTH')->assertOk();
        $this->assertStringContainsString('Cases past interment', $summary->json('html'));
        $this->assertStringContainsString('AFTER_INTERMENT', $summary->json('html'));
        $this->assertStringNotContainsString('<table', $summary->json('html'));
        foreach (['cases', 'service', 'outstanding', 'risks'] as $kind) {
            $drawer = $this->actingAs($owner)->get('/owner/dashboard/details?summary=1&kind='.$kind.'&range=THIS_MONTH&branch_id='.$branch->id)->assertOk();
            preg_match('/data-records-url="([^"]+)"/', $drawer->json('html'), $reportMatch);
            $reportUrl = html_entity_decode($reportMatch[1], ENT_QUOTES);
            $this->assertSame('/reports', parse_url($reportUrl, PHP_URL_PATH));
            parse_str(parse_url($reportUrl, PHP_URL_QUERY), $reportFilters);
            $this->assertSame('master_cases', $reportFilters['report_type']);
            $this->assertSame('recording', $reportFilters['date_basis']);
            $report = $this->getJson('/reports/preview?'.http_build_query($reportFilters))->assertOk();
            if (in_array($kind, ['outstanding', 'risks'])) {
                $this->assertArrayNotHasKey('date_from', $reportFilters);
                $this->assertCount(1, $report->json('rows'));
                $this->assertSame(8000.0, (float) collect($report->json('rows'))->sum('balance'));
            } else {
                $this->assertCount(0, $report->json('rows'));
            }
        }
        $oldCase->forceFill(['interment_at'=>null])->save();
        $deceased->forceFill(['interment_at'=>now()->subMonth()])->save();
        $history = $this->actingAs($owner)->get('/owner/case-history?collection_state=AFTER_INTERMENT&branch_id='.$branch->id)->assertOk();
        $this->assertSame(1, $history->viewData('cases')->total());
        $receipts = $this->actingAs($owner)->get('/owner/collections?range=THIS_MONTH&branch_id='.$branch->id)->assertOk();
        $this->assertSame(1, $receipts->viewData('records')->total());
        $this->assertSame(2000.0, $receipts->viewData('scopeAmount'));
        $staff = $this->createUser('staff', $branch);
        $this->actingAs($staff)->get('/owner/dashboard/details?kind=collections')->assertForbidden();
        $this->actingAs($staff)->get('/owner/collections')->assertForbidden();
        for ($i = 0; $i < 20; $i++) {
            Payment::create(['funeral_case_id'=>$oldCase->id,'branch_id'=>$branch->id,
                'method'=>'CASH','amount'=>1,'paid_at'=>now(),'paid_date'=>now()->toDateString(),'status'=>'POSTED']);
        }
        $this->actingAs($owner)->get('/owner/dashboard/details?kind=collections&range=THIS_MONTH&branch_id='.$branch->id.'&page=2')
            ->assertOk()->assertJsonPath('count', 21)->assertSee('Page 2 of 2');
        $receiptPage = $this->actingAs($owner)->get('/owner/collections?range=THIS_MONTH&branch_id='.$branch->id.'&page=2')->assertOk();
        $this->assertSame(21, $receiptPage->viewData('records')->total());
        $this->assertCount(1, $receiptPage->viewData('records')->items());
        $this->actingAs($owner)->get('/owner/dashboard/details?kind=collections&range=THIS_MONTH&branch_id='.$branch->id.'&q=no-such-case')
            ->assertOk()->assertJsonPath('count', 0);
        $cohortDeceased = $this->createDeceased($branch, $client, 'Cohort Deceased');
        $cohortCase = $this->createCase($branch, $client, $cohortDeceased, $package, [
            'case_code'=>'EXEC-COHORT', 'verification_status'=>'VERIFIED', 'total_amount'=>10000,
            'total_paid'=>3000, 'balance_amount'=>7000, 'created_at'=>now(),
        ]);
        foreach (['POSTED'=>3000, 'VOIDED'=>5000] as $status=>$amount) {
            Payment::create(['funeral_case_id'=>$cohortCase->id,'branch_id'=>$branch->id,
                'method'=>'CASH','amount'=>$amount,'paid_at'=>now()->subMonth(),'status'=>$status]);
        }
        $cohortRow = $this->actingAs($owner)->get('/owner?range=THIS_MONTH&branch_id='.$branch->id)
            ->assertOk()->viewData('overview')['branchRows']->first();
        $this->assertSame(10000.0, $cohortRow['service']);
        $this->assertSame(3000.0, $cohortRow['cohortCollected']);
        $this->assertSame(7000.0, $cohortRow['cohortRemaining']);
        $this->assertSame(30.0, $cohortRow['rate']);

    }

    public function test_intake_blocks_duplicate_active_case_when_identity_matches_existing_record(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Original Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Existing Deceased', [
            'born' => '1965-01-01',
            'died' => now()->subDay()->toDateString(),
            'date_of_death' => now()->subDay()->toDateString(),
        ]);

        $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC2001',
            'case_status' => 'ACTIVE',
            'payment_status' => 'UNPAID',
            'total_paid' => 0,
            'balance_amount' => 20000,
        ]);

        $response = $this->actingAs($staff)->from('/intake/main')->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'client_name' => 'New Client',
            'client_contact_number' => '09179990000',
            'client_address' => 'Different Intake Address',
            'deceased_name' => 'Existing Deceased',
            'deceased_address' => 'Client Address',
            'born' => '1965-01-01',
            'died' => now()->subDay()->toDateString(),
        ]));

        $response->assertRedirect('/intake/main');
        $response->assertSessionHasErrors('deceased_name');
        $this->assertDatabaseCount('funeral_cases', 1);
        $this->assertDatabaseCount('deceased', 1);
    }

    public function test_admin_cannot_deactivate_main_branch(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->patch("/admin/branches/{$mainBranch->id}/toggle-status")
            ->assertRedirect(route('admin.branches.index', absolute: false))
            ->assertSessionHasErrors('is_active');

        $mainBranch->refresh();
        $this->assertTrue($mainBranch->is_active);
    }

    public function test_branch_disable_explains_unfinished_and_unpaid_cases_and_blocks_both_paths(): void
    {
        $branch = $this->createBranch('BR002', 'Other Branch');
        $admin = $this->createUser('admin');
        $package = $this->createPackage();
        $client = $this->createClient($branch, 'Branch Client');
        $deceased = $this->createDeceased($branch, $client, 'Branch Deceased');
        $this->createCase($branch, $client, $deceased, $package);

        $this->actingAs($admin)
            ->getJson("/admin/branches/{$branch->id}/disable-check")
            ->assertOk()
            ->assertJsonPath('can_disable', false)
            ->assertJsonPath('unfinished_cases', 1)
            ->assertJsonPath('completed_with_balance', 0);

        $this->actingAs($admin)
            ->patch("/admin/branches/{$branch->id}/toggle-status")
            ->assertSessionHasErrors('is_active');

        $this->actingAs($admin)
            ->put("/admin/branches/{$branch->id}", [
                'branch_name' => $branch->branch_name,
                'address' => $branch->address,
                'is_active' => 0,
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($branch->fresh()->is_active);
    }

    public function test_completed_case_with_balance_blocks_disable_but_settled_case_does_not(): void
    {
        $branch = $this->createBranch('BR002', 'Other Branch');
        $admin = $this->createUser('admin');
        $package = $this->createPackage();
        $client = $this->createClient($branch, 'Branch Client');
        $deceased = $this->createDeceased($branch, $client, 'Branch Deceased');
        $case = $this->createCase($branch, $client, $deceased, $package, ['case_status' => 'COMPLETED']);
        $staff = $this->createUser('staff', $branch);

        $this->actingAs($admin)
            ->getJson("/admin/branches/{$branch->id}/disable-check")
            ->assertJsonPath('completed_with_balance', 1);

        $case->update(['total_paid' => 20000, 'balance_amount' => 0, 'payment_status' => 'PAID']);

        $this->actingAs($admin)
            ->getJson("/admin/branches/{$branch->id}/disable-check")
            ->assertJsonPath('can_disable', true);

        $this->actingAs($admin)
            ->patch("/admin/branches/{$branch->id}/toggle-status")
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'BR002 · Other Branch is now inactive.');

        $this->assertFalse($branch->fresh()->is_active);
        $this->assertTrue($staff->fresh()->is_active);
        $this->actingAs($staff)->get('/staff')->assertRedirect('/login');
    }

    public function test_other_branch_intake_redirects_back_to_other_branch_reports(): void
    {
        $this->travelTo(now()->setTime(10, 0));

        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Other Branch');
        $mainAdmin = $this->createUser('admin', $mainBranch);
        $package = $this->createPackage();

        $response = $this->actingAs($mainAdmin)->post('/intake/other', $this->baseIntakePayload($otherBranch, $package, [
            'branch_id' => $otherBranch->id,
            'client_name' => 'External Client',
            'client_contact_number' => '09175550000',
            'client_address' => 'Other Branch Address',
            'deceased_name' => 'External Deceased',
            'deceased_address' => 'Other Branch Address',
            'born' => now()->subYears(50)->toDateString(),
            'died' => now()->subDay()->toDateString(),
            'reported_at' => now()->format('Y-m-d H:i:s'),
            'reporter_name' => 'Branch Reporter',
            'reporter_contact' => '09176660000',
            'mark_as_paid' => 1,
            'payment_type' => 'FULL',
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => 20000,
        ]));

        $response->assertRedirect(route('intake.saved', absolute: false));
        $this->assertDatabaseHas('funeral_cases', [
            'branch_id' => $otherBranch->id,
            'entry_source' => 'OTHER_BRANCH',
            'case_status' => 'COMPLETED',
            'payment_status' => 'PAID',
        ]);
    }

    public function test_other_branch_reports_search_accepts_name_punctuation(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Other Branch');
        $mainAdmin = $this->createUser('admin', $mainBranch);
        $package = $this->createPackage();
        $client = $this->createClient($otherBranch, "Maria P. O'Neil");
        $deceased = $this->createDeceased($otherBranch, $client, 'Other Search Deceased');

        $this->createCase($otherBranch, $client, $deceased, $package, [
            'case_code' => 'FC2010',
            'entry_source' => 'OTHER_BRANCH',
            'case_status' => 'COMPLETED',
            'payment_status' => 'PAID',
            'total_paid' => 20000,
            'balance_amount' => 0,
            'reporter_name' => "Anne P. O'Neil",
        ]);

        $this->actingAs($mainAdmin)
            ->get('/other-branch-reports?q=' . urlencode("O'Neil"))
            ->assertOk()
            ->assertSee('FC2010');
    }

    public function test_active_cases_are_paginated_for_staff(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        foreach (range(1, 25) as $index) {
            $client = $this->createClient($mainBranch, "Client {$index}", '0917' . str_pad((string) $index, 7, '0', STR_PAD_LEFT), "Address {$index}");
            $deceased = $this->createDeceased($mainBranch, $client, "Deceased {$index}", [
                'address' => "Address {$index}",
            ]);

            $this->createCase($mainBranch, $client, $deceased, $package, [
                'case_code' => 'FCA' . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'case_status' => 'ACTIVE',
            ]);
        }

        $this->actingAs($staff)
            ->get('/funeral-cases')
            ->assertOk()
            ->assertViewHas('cases', function ($cases) {
                return method_exists($cases, 'perPage')
                    && $cases->perPage() === 25
                    && $cases->total() === 25;
            });
    }

    public function test_payment_history_search_accepts_name_punctuation(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, "Maria P. O'Neil");
        $deceased = $this->createDeceased($mainBranch, $client, 'Payment Search Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'payment_status' => 'PARTIAL',
            'total_paid' => 5000,
            'balance_amount' => 15000,
        ]);

        Payment::create([
            'receipt_number' => 'RCPT-2026-010001',
            'funeral_case_id' => $case->id,
            'branch_id' => $mainBranch->id,
            'method' => 'CASH',
            'amount' => 5000,
            'balance_after_payment' => 15000,
            'payment_status_after_payment' => 'PARTIAL',
            'paid_date' => now()->toDateString(),
            'paid_at' => now(),
            'recorded_by' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->get('/payments/history?q=' . urlencode("O'Neil"))
            ->assertOk()
            ->assertSee("Maria P. O'Neil");
    }

    public function test_branch_admin_payment_monitoring_forces_assigned_branch_scope(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $branchTwo = $this->createBranch('BR002', 'Branch Two');
        $package = $this->createPackage();

        $mainClient = $this->createClient($mainBranch, 'Main Payment Client');
        $mainDeceased = $this->createDeceased($mainBranch, $mainClient, 'Main Payment Deceased');
        $mainCase = $this->createCase($mainBranch, $mainClient, $mainDeceased, $package, [
            'case_code' => 'PAY-MAIN-001',
            'payment_status' => 'PAID',
            'total_paid' => 20000,
            'balance_amount' => 0,
        ]);

        $branchClient = $this->createClient($branchTwo, 'Branch Two Payment Client');
        $branchDeceased = $this->createDeceased($branchTwo, $branchClient, 'Branch Two Payment Deceased');
        $branchCase = $this->createCase($branchTwo, $branchClient, $branchDeceased, $package, [
            'case_code' => 'PAY-BR002-001',
            'payment_status' => 'PARTIAL',
            'total_paid' => 5000,
            'balance_amount' => 15000,
        ]);

        Payment::create([
            'payment_record_no' => 'PAY-2026-000101',
            'accounting_reference_no' => 'ACCT-MAIN-001',
            'funeral_case_id' => $mainCase->id,
            'branch_id' => $mainBranch->id,
            'method' => 'CASH',
            'payment_method' => 'cash',
            'amount' => 20000,
            'balance_after_payment' => 0,
            'payment_status_after_payment' => 'PAID',
            'paid_date' => now()->toDateString(),
            'paid_at' => now(),
        ]);

        Payment::create([
            'payment_record_no' => 'PAY-2026-000102',
            'accounting_reference_no' => 'ACCT-BR002-001',
            'funeral_case_id' => $branchCase->id,
            'branch_id' => $branchTwo->id,
            'method' => 'CASH',
            'payment_method' => 'cash',
            'amount' => 5000,
            'balance_after_payment' => 15000,
            'payment_status_after_payment' => 'PARTIAL',
            'paid_date' => now()->toDateString(),
            'paid_at' => now(),
        ]);

        $branchAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $branchTwo->id,
            'is_active' => true,
        ]);

        $this->actingAs($branchAdmin)
            ->get('/payments/history?branch_id=' . $mainBranch->id)
            ->assertOk()
            ->assertViewHas('branches', fn ($items) => $items->pluck('id')->all() === [$branchTwo->id])
            ->assertSee('PAY-BR002-001')
            ->assertDontSee('PAY-MAIN-001')
            ->assertDontSee('All Branches')
            ->assertDontSee('Add Payment');
    }

    public function test_main_admin_payment_monitoring_can_filter_all_branches(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $branchTwo = $this->createBranch('BR002', 'Branch Two');
        $admin = $this->createUser('admin', $mainBranch);
        $package = $this->createPackage();

        $mainClient = $this->createClient($mainBranch, 'Main Admin Client');
        $mainDeceased = $this->createDeceased($mainBranch, $mainClient, 'Main Admin Deceased');
        $mainCase = $this->createCase($mainBranch, $mainClient, $mainDeceased, $package, [
            'case_code' => 'ADMIN-MAIN-001',
            'payment_status' => 'PAID',
            'total_paid' => 20000,
            'balance_amount' => 0,
        ]);

        $branchClient = $this->createClient($branchTwo, 'Branch Admin Client');
        $branchDeceased = $this->createDeceased($branchTwo, $branchClient, 'Branch Admin Deceased');
        $branchCase = $this->createCase($branchTwo, $branchClient, $branchDeceased, $package, [
            'case_code' => 'ADMIN-BR002-001',
            'payment_status' => 'PAID',
            'total_paid' => 20000,
            'balance_amount' => 0,
        ]);

        foreach ([[$mainCase, $mainBranch, 'PAY-2026-000201'], [$branchCase, $branchTwo, 'PAY-2026-000202']] as [$case, $branch, $recordNo]) {
            Payment::create([
                'payment_record_no' => $recordNo,
                'accounting_reference_no' => $recordNo . '-ACCT',
                'funeral_case_id' => $case->id,
                'branch_id' => $branch->id,
                'method' => 'CASH',
                'payment_method' => 'cash',
                'amount' => 20000,
                'balance_after_payment' => 0,
                'payment_status_after_payment' => 'PAID',
                'paid_date' => now()->toDateString(),
                'paid_at' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->get('/admin/payments')
            ->assertOk()
            ->assertSee('All Branches')
            ->assertSee('ADMIN-MAIN-001')
            ->assertSee('ADMIN-BR002-001')
            ->assertDontSee('Add Payment');

        $this->actingAs($admin)
            ->get('/admin/payments?branch_id=' . $branchTwo->id)
            ->assertOk()
            ->assertSee('ADMIN-BR002-001')
            ->assertViewHas('paymentCases', function ($cases) {
                return $cases->pluck('case_code')->contains('ADMIN-BR002-001')
                    && ! $cases->pluck('case_code')->contains('ADMIN-MAIN-001');
            });
    }

    public function test_transaction_history_groups_multiple_payments_under_one_case(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Grouped Payment Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Grouped Payment Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC0003',
            'total_amount' => 250000,
            'total_paid' => 250000,
            'balance_amount' => 0,
            'payment_status' => 'PAID',
        ]);

        foreach ([50000, 20000, 10000, 170000] as $index => $amount) {
            $isFinal = $index === 3;
            Payment::create([
                'payment_record_no' => 'PAY-2026-00030' . ($index + 1),
                'accounting_reference_no' => 'ACCT-FC0003-' . ($index + 1),
                'funeral_case_id' => $case->id,
                'branch_id' => $mainBranch->id,
                'method' => 'CASH',
                'payment_method' => 'cash',
                'amount' => $amount,
                'balance_after_payment' => $isFinal ? 0 : 250000 - array_sum(array_slice([50000, 20000, 10000, 170000], 0, $index + 1)),
                'payment_status_after_payment' => $isFinal ? 'PAID' : 'PARTIAL',
                'paid_date' => now()->subDays(3 - $index)->toDateString(),
                'paid_at' => now()->subDays(3 - $index),
                'received_by' => 'Accounting Staff',
                'recorded_by' => $staff->id,
                'encoded_by' => $staff->id,
            ]);
        }

        $this->actingAs($staff)
            ->get('/payments/history?tab=transactions')
            ->assertOk()
            ->assertViewHas('transactionCases', function ($cases) {
                return $cases->total() === 1
                    && $cases->first()?->case_code === 'FC0003'
                    && $cases->first()?->payments->count() === 4;
            })
            ->assertSee('View Full Transactions')
            ->assertSee('PAY-2026-000301')
            ->assertSee('PAY-2026-000304');

        $this->actingAs($staff)
            ->get('/payments/history?tab=transactions&payment_status=PAID')
            ->assertOk()
            ->assertViewHas('transactionCases', fn ($cases) => $cases->pluck('case_code')->contains('FC0003'));

        $this->actingAs($staff)
            ->get('/payments/history?tab=transactions&payment_status=PARTIAL')
            ->assertOk()
            ->assertViewHas('transactionCases', fn ($cases) => ! $cases->pluck('case_code')->contains('FC0003'));
    }

    public function test_admin_master_cases_search_accepts_name_punctuation(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createUser('admin');
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, "Maria P. O'Neil");
        $deceased = $this->createDeceased($mainBranch, $client, 'Admin Search Deceased');

        $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FCA2001',
        ]);

        $this->actingAs($admin)
            ->get('/admin/cases?q=' . urlencode("O'Neil"))
            ->assertOk()
            ->assertSee('FCA2001');
    }

    public function test_owner_case_history_search_accepts_name_punctuation(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, "Maria P. O'Neil");
        $deceased = $this->createDeceased($mainBranch, $client, 'Owner Search Deceased');

        $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FCO3001',
            'verification_status' => 'VERIFIED',
        ]);

        $this->actingAs($owner)
            ->get('/owner/case-history?q=' . urlencode("O'Neil"))
            ->assertOk()
            ->assertSee('FCO3001');
    }

    public function test_owner_records_worklist_filters_sort_and_preserve_return_context(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');
        $package = $this->createPackage();
        $client = $this->createClient($branch, 'Worklist Client');
        $deceased = $this->createDeceased($branch, $client, 'Worklist Deceased');
        $this->createCase($branch, $client, $deceased, $package, ['case_code' => 'OLD001', 'verification_status' => 'VERIFIED', 'case_status' => 'ACTIVE', 'created_at' => now()->subDays(2)]);
        $this->createCase($branch, $client, $this->createDeceased($branch, $client, 'New Worklist Deceased'), $package, ['case_code' => 'NEW001', 'verification_status' => 'VERIFIED', 'case_status' => 'ACTIVE', 'created_at' => now()]);
        $this->createCase($branch, $client, $this->createDeceased($branch, $client, 'Hidden Worklist Deceased'), $package, ['case_code' => 'HIDDEN001', 'verification_status' => 'PENDING']);
        $this->actingAs($owner)->get('/owner/case-history?case_status=ACTIVE&sort=oldest&per_page=10&dashboard_range=THIS_MONTH')
            ->assertOk()->assertSeeInOrder(['OLD001', 'NEW001'])->assertDontSee('HIDDEN001')
            ->assertViewHas('cases', fn($cases) => $cases->perPage() === 10 && $cases->total() === 2)
            ->assertSee('View Full Information for OLD001')->assertSee('dashboard_range=THIS_MONTH', false);
        $this->actingAs($owner)->get('/owner/case-history?per_page=999')->assertSessionHasErrors('per_page');
    }

    public function test_other_branch_intake_page_shows_completed_and_full_payment_rules_up_front(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $this->createBranch('BR002', 'Other Branch');
        $mainAdmin = $this->createUser('admin', $mainBranch);

        $this->actingAs($mainAdmin)
            ->get('/intake/other')
            ->assertOk()
            ->assertSee('External Branch Report')
            ->assertSee('Encoded Date')
            ->assertSee('Other-Branch Intake Rules')
            ->assertSee('submitted within today only from 00:00')
            ->assertSee('Payment Confirmation')
            ->assertSee('Other-branch completed reports must already be fully paid before they can be encoded.')
            ->assertSee('This report will be saved as a completed, fully paid branch report')
            ->assertDontSee('Record unpaid, partial, or full payment and let the system calculate the remaining balance.');
    }

    public function test_main_branch_intake_supports_unpaid_case_creation(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        $response = $this->actingAs($staff)->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'client_name' => 'Walk In Client',
            'client_contact_number' => '09170001000',
            'client_address' => 'Main Branch Address',
            'deceased_name' => 'Unpaid Deceased',
            'deceased_address' => 'Main Branch Address',
            'died' => now()->subDay()->toDateString(),
        ]));

        $response->assertRedirect(route('funeral-cases.index', ['record_scope' => 'main'], absolute: false));

        $case = FuneralCase::query()->latest('id')->first();
        $this->assertNotNull($case);
        $this->assertSame('UNPAID', $case->payment_status);
        $this->assertSame(0.0, (float) $case->total_paid);
        $this->assertEquals((float) $case->total_amount, (float) $case->balance_amount);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_main_branch_intake_supports_partial_payment_case_creation(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        $response = $this->actingAs($staff)->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'client_name' => 'Partial Client',
            'submission_id' => '167266b7-3a41-4ca1-8616-3b88d933668d',
            'client_contact_number' => '09170002000',
            'client_address' => 'Main Branch Address',
            'deceased_name' => 'Partial Deceased',
            'deceased_address' => 'Main Branch Address',
            'died' => now()->subDay()->toDateString(),
            'mark_as_paid' => 1,
            'payment_type' => 'PARTIAL',
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => 5000,
        ]));

        $response->assertRedirect(route('intake.saved', absolute: false));

        $case = FuneralCase::query()->latest('id')->first();
        $this->assertNotNull($case);
        $this->assertSame('PARTIAL', $case->payment_status);
        $this->assertEquals(5000.0, (float) $case->total_paid);
        $this->assertEquals(round((float) $case->total_amount - 5000.0, 2), (float) $case->balance_amount);

        $payment = Payment::query()->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(5000.0, (float) $payment->amount);
        $this->assertSame('167266b7-3a41-4ca1-8616-3b88d933668d', $payment->submission_id);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $payment->submission_hash);
        $this->assertSame($case->id, $payment->funeral_case_id);
        $this->assertNotNull($payment->receipt_number);
        $this->assertSame('PARTIAL', $payment->payment_status_after_payment);
        $this->assertEquals(round((float) $case->total_amount - 5000.0, 2), (float) $payment->balance_after_payment);
    }

    public function test_committed_initial_payment_can_be_recovered_after_losing_the_success_response(): void
    {
        $branch = $this->createBranch('BR001', 'Main');
        $staff = $this->createUser('staff', $branch);
        $package = $this->createPackage();
        $payload = $this->baseIntakePayload($branch, $package, [
            'submission_id' => (string) \Illuminate\Support\Str::uuid(), 'mark_as_paid' => 1,
            'payment_type' => 'PARTIAL', 'paid_at' => now()->format('Y-m-d H:i:s'), 'amount_paid' => 5000,
        ]);
        // Commit, then discard the response and all success-page session state.
        $this->actingAs($staff)->post('/intake/main', $payload)->assertSessionHasNoErrors();
        $case = FuneralCase::firstOrFail();
        $this->flushSession();
        $this->actingAs($staff)->post('/intake/main', $payload)
            ->assertRedirect(route('funeral-cases.show', $case, absolute: false))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Recovered existing case '.$case->case_code)
                && str_contains($message, 'did not apply changes or create another case or payment'));
        $this->assertDatabaseCount('funeral_cases', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->get(route('funeral-cases.show', $case))->assertOk()->assertSee($case->case_code)->assertSee('Transactions');
        $this->post('/intake/main', array_replace($payload, ['amount_paid' => 6000]))->assertSessionHasErrors('submission_id');
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(5000.0, (float) $case->fresh()->total_paid);
    }

    public function test_main_branch_intake_persists_extended_spec_fields(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        $response = $this->actingAs($staff)->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'client_name' => 'Spec Client',
            'client_relationship' => 'Spouse',
            'client_email' => 'spec@example.com',
            'client_valid_id_number' => 'ID-9000',
            'deceased_name' => 'Spec Deceased',
            'civil_status' => 'MARRIED',
            'senior_citizen_status' => 1,
            'senior_citizen_id_number' => 'SC-9000',
            'wake_location' => 'Town Chapel',
            'additional_services' => 'Flower arrangement',
            'additional_service_amount' => 3000,
            'mark_as_paid' => 1,
            'payment_type' => 'FULL',
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => 19000,
        ]));

        $response->assertRedirect(route('intake.saved', absolute: false));

        $this->assertDatabaseHas('clients', [
            'full_name' => 'Spec Client',
            'relationship_to_deceased' => 'Spouse',
            'email' => 'spec@example.com',
            'valid_id_number' => 'ID-9000',
        ]);

        $this->assertDatabaseHas('deceased', [
            'full_name' => 'Spec Deceased',
            'civil_status' => 'MARRIED',
            'senior_citizen_status' => 1,
            'senior_citizen_id_number' => 'SC-9000',
        ]);

        $this->assertDatabaseHas('funeral_cases', [
            'wake_location' => 'Town Chapel',
            'additional_services' => 'Flower arrangement',
            'additional_service_amount' => 3000,
            'discount_type' => 'SENIOR',
            'discount_amount' => 4000,
            'initial_payment_type' => 'FULL',
            'payment_status' => 'PAID',
            'total_amount' => 19000,
        ]);
    }

    public function test_main_branch_intake_accepts_common_name_punctuation(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        $response = $this->actingAs($staff)->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'client_name' => "Maria P. O'Neil-Santos",
            'deceased_name' => "Jose D. Cruz-Santos",
        ]));

        $response->assertRedirect(route('funeral-cases.index', ['record_scope' => 'main'], absolute: false));

        $this->assertDatabaseHas('clients', [
            'full_name' => "Maria P. O'Neil-Santos",
        ]);
        $this->assertDatabaseHas('deceased', [
            'full_name' => "Jose D. Cruz-Santos",
        ]);
    }

    public function test_main_branch_intake_auto_applies_pwd_discount_to_package_price_only(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        $response = $this->actingAs($staff)->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'senior_citizen_status' => 0,
            'senior_citizen_id_number' => null,
            'pwd_status' => 1,
            'pwd_id_number' => 'PWD-9000',
            'additional_service_amount' => 5000,
            'mark_as_paid' => 1,
            'payment_type' => 'FULL',
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => 21000,
        ]));

        $response->assertRedirect(route('intake.saved', absolute: false));

        $this->assertDatabaseHas('funeral_cases', [
            'discount_type' => 'PWD',
            'discount_amount' => 4000,
            'additional_service_amount' => 5000,
            'total_amount' => 21000,
        ]);
    }

    public function test_case_details_review_payments_without_a_record_payment_entry_point(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Payment Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Payment Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC3001',
            'total_amount' => 20000,
            'total_paid' => 5000,
            'balance_amount' => 15000,
            'payment_status' => 'PARTIAL',
            'case_status' => 'ACTIVE',
        ]);

        $this->actingAs($staff)
            ->get("/funeral-cases/{$case->id}")
            ->assertOk()
            ->assertDontSee('Record Payment')
            ->assertSee('Payment Summary')
            ->assertSee('Transactions');
        $this->get(route('payments.history', ['case_id' => $case->id, 'record_payment' => 1]))
            ->assertOk()->assertViewHas('preselectCase', fn ($item) => $item->id === $case->id)
            ->assertViewHas('autoOpenPayment', true);

        $this->actingAs($staff)
            ->post('/payments/pay', [
                'funeral_case_id' => $case->id,
                'paid_at' => now()->format('Y-m-d H:i:s'),
                'amount_paid' => 10000,
                'payment_method' => 'cash',
                'return_to_case' => 1,
            ])
            ->assertRedirect(route('funeral-cases.show', $case, absolute: false));

        $case->refresh();
        $this->assertSame('PARTIAL', $case->payment_status);
        $this->assertEquals(15000.0, (float) $case->total_paid);
        $this->assertEquals(5000.0, (float) $case->balance_amount);

        $payment = Payment::query()->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertSame($case->id, $payment->funeral_case_id);
        $this->assertNotNull($payment->payment_record_no);
        $this->assertNotNull($payment->receipt_number);
        $this->assertNull($payment->accounting_reference_no);
        $this->assertNull($payment->receipt_or_no);
        $this->assertNull($payment->received_by);
        $this->assertSame('PARTIAL', $payment->payment_status_after_payment);
        $this->assertEquals(5000.0, (float) $payment->balance_after_payment);

        $this->assertDatabaseHas('payments', [
            'funeral_case_id' => $case->id,
            'amount' => 10000,
        ]);
    }

    public function test_payment_submission_retries_are_idempotent_but_distinct_equal_payments_are_allowed(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $branch, true);
        $client = $this->createClient($branch, 'Retry Client');
        $case = $this->createCase($branch, $client, $this->createDeceased($branch, $client, 'Retry Deceased'), $this->createPackage(), [
            'total_amount' => 1000, 'total_paid' => 0, 'balance_amount' => 1000,
            'payment_status' => 'UNPAID', 'case_status' => 'ACTIVE',
        ]);
        $request = ['submission_id' => (string) \Illuminate\Support\Str::uuid(), 'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'), 'amount_paid' => 500, 'payment_method' => 'cash'];
        $this->actingAs($staff)->post('/payments/pay', $request)->assertSessionMissing('errors');
        $this->post('/payments/pay', $request)->assertSessionMissing('errors');
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(500.0, (float) $case->fresh()->balance_amount);
        $this->post('/payments/pay', array_replace($request, ['amount_paid' => 501]))->assertSessionHasErrors('submission_id');
        $request['submission_id'] = (string) \Illuminate\Support\Str::uuid();
        $this->post('/payments/pay', $request)->assertSessionMissing('errors');
        $this->post('/payments/pay', $request)->assertSessionMissing('errors');
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame('PAID', $case->fresh()->payment_status);
        $this->assertSame(1000.0, (float) $case->fresh()->total_paid);
        $this->assertSame(0.0, (float) $case->fresh()->balance_amount);
        $request['submission_id'] = (string) \Illuminate\Support\Str::uuid();
        $this->post('/payments/pay', $request)->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_legacy_cross_branch_grants_never_expand_operational_access(): void
    {
        $branches = [];
        foreach (['BR001', 'BR002', 'BR003'] as $code) $branches[] = $this->createBranch($code, $code);
        $package = $this->createPackage();
        foreach ($branches as $i => $branch) {
            $other = $branches[($i + 1) % 3];
            $staff = $this->createUser('staff', $branch, true);
            $client = $this->createClient($other, 'Foreign Client '.$i);
            $foreign = $this->createCase($other, $client, $this->createDeceased($other, $client, 'Foreign '.$i), $package);
            $grantId = \Illuminate\Support\Facades\DB::table('temporary_cross_branch_permissions')->insertGetId([
                'user_id' => $staff->id, 'allowed_branch_id' => $other->id, 'is_active' => true, 'is_used' => false,
                'granted_at' => now(), 'expires_at' => now()->addDay(),
            ]);
            $this->assertNull($staff->activeTemporaryPermission());
            $this->assertFalse($staff->canEncodeAnyBranch());
            $this->assertSame([$branch->id], $staff->branchScopeIds());
            $this->assertSame(0, \App\Models\TemporaryCrossBranchPermission::active()->count());
            $this->actingAs($staff)->get('/funeral-cases/'.$foreign->id)->assertForbidden();
            $this->get('/intake/other')->assertForbidden();
            $this->post('/intake/other', ['branch_id' => $other->id])->assertForbidden();
            $this->post('/payments/pay', ['funeral_case_id' => $foreign->id, 'branch_id' => $branch->id,
                'amount_paid' => 100, 'paid_at' => today()->toDateString(), 'payment_method' => 'cash',
                'submission_id' => (string) \Illuminate\Support\Str::uuid()])->assertSessionHasErrors();
            $this->assertDatabaseHas('temporary_cross_branch_permissions', ['id' => $grantId, 'is_active' => true]);
            $admin = $this->createUser('admin', $branch);
            $admin->update(['admin_scope' => 'branch']);
            $this->actingAs($admin)->get('/payments/history?branch_id='.$other->id)
                ->assertOk()->assertViewHas('branches', fn ($items) => $items->pluck('id')->all() === [$branch->id]);
        }
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_new_temporary_cross_branch_grants_are_disabled_without_removing_history(): void
    {
        $branch = $this->createBranch('BR001', 'Main');
        $staff = $this->createUser('staff', $branch);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        \App\Models\TemporaryCrossBranchPermission::create(['user_id' => $staff->id, 'allowed_branch_id' => $branch->id,
            'is_active' => true, 'is_used' => false]);
    }

    public function test_cashless_bank_transfer_payment_saves_normalized_details(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Cashless Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Cashless Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC3101',
            'total_amount' => 20000,
            'total_paid' => 0,
            'balance_amount' => 20000,
            'payment_status' => 'UNPAID',
            'case_status' => 'ACTIVE',
        ]);

        $this->actingAs($staff)->post('/payments/pay', [
            'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => 5000,
            'payment_method' => 'cashless',
            'cashless_type' => 'bank_transfer',
            'bank_name' => 'BPI',
            'reference_number' => 'TXN-001',
            'receipt_or_no' => 'OR-CASHLESS-001',
            'received_by' => 'Accounting Staff',
        ])->assertRedirect('/payments');

        $this->assertDatabaseHas('payments', [
            'funeral_case_id' => $case->id,
            'payment_method' => 'cashless',
            'cashless_type' => 'bank_transfer',
            'bank_name' => 'BPI',
            'reference_number' => 'TXN-001',
            'transaction_reference_no' => 'TXN-001',
            'receipt_or_no' => 'OR-CASHLESS-001',
            'accounting_reference_no' => 'OR-CASHLESS-001',
            'payment_mode' => 'bank_transfer',
        ]);
    }

    public function test_cashless_wallet_payment_requires_reference_and_sets_provider(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Wallet Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Wallet Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC3102',
            'total_amount' => 20000,
            'total_paid' => 0,
            'balance_amount' => 20000,
            'payment_status' => 'UNPAID',
            'case_status' => 'ACTIVE',
        ]);

        $this->actingAs($staff)->from('/payments')->post('/payments/pay', [
            'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => 2500,
            'payment_method' => 'cashless',
            'cashless_type' => 'gcash',
            'accounting_reference_no' => 'OR-GCASH-001',
            'received_by' => 'Accounting Staff',
        ])->assertRedirect('/payments')
            ->assertSessionHasErrors('reference_number');

        $this->actingAs($staff)->post('/payments/pay', [
            'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => 2500,
            'payment_method' => 'cashless',
            'cashless_type' => 'gcash',
            'reference_number' => '1234567890123',
            'accounting_reference_no' => 'OR-GCASH-002',
            'received_by' => 'Accounting Staff',
        ])->assertRedirect('/payments');

        $this->assertDatabaseHas('payments', [
            'funeral_case_id' => $case->id,
            'payment_method' => 'cashless',
            'cashless_type' => 'gcash',
            'wallet_provider' => 'GCash',
            'reference_number' => '1234567890123',
        ]);
    }

    public function test_cashless_bank_transfer_other_bank_requires_bank_name(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Other Bank Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Other Bank Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC3103',
            'total_amount' => 20000,
            'total_paid' => 0,
            'balance_amount' => 20000,
            'payment_status' => 'UNPAID',
            'case_status' => 'ACTIVE',
        ]);

        $this->actingAs($staff)->from('/payments')->post('/payments/pay', [
            'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => 5000,
            'payment_method' => 'cashless',
            'cashless_type' => 'bank_transfer',
            'bank_name' => 'Other Bank',
            'reference_number' => 'TXN-OTHER-001',
            'accounting_reference_no' => 'OR-OTHERBANK-001',
            'received_by' => 'Accounting Staff',
        ])->assertRedirect('/payments')
            ->assertSessionHasErrors('other_bank_name');
    }

    public function test_card_and_other_cashless_validation_require_tracking_fields(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Card Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Card Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC3104',
            'total_amount' => 20000,
            'total_paid' => 0,
            'balance_amount' => 20000,
            'payment_status' => 'UNPAID',
            'case_status' => 'ACTIVE',
        ]);

        $basePayload = [
            'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => 1000,
            'payment_method' => 'cashless',
            'accounting_reference_no' => 'OR-CARD-001',
            'received_by' => 'Accounting Staff',
        ];

        $this->actingAs($staff)->from('/payments')->post('/payments/pay', array_merge($basePayload, [
            'cashless_type' => 'card',
        ]))->assertRedirect('/payments')
            ->assertSessionHasErrors('approval_code');

        $this->actingAs($staff)->from('/payments')->post('/payments/pay', array_merge($basePayload, [
            'cashless_type' => 'other',
            'reference_number' => 'PWN-001',
        ]))->assertRedirect('/payments')
            ->assertSessionHasErrors('payment_channel');
    }

    public function test_payment_backend_rejects_strict_invalid_payment_inputs(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Strict Validation Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Strict Validation Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC3105',
            'total_amount' => 20000,
            'total_paid' => 0,
            'balance_amount' => 20000,
            'payment_status' => 'UNPAID',
            'case_status' => 'ACTIVE',
            'created_at' => now()->subDay(),
        ]);

        $basePayload = [
            'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => '1000.00',
            'payment_method' => 'cash',
            'accounting_reference_no' => 'OR-STRICT-001',
            'received_by' => 'Accounting Staff',
        ];

        foreach ([
            ['amount_paid' => 'abc', 'error' => 'amount_paid'],
            ['amount_paid' => '-100', 'error' => 'amount_paid'],
            ['amount_paid' => '0', 'error' => 'amount_paid'],
            ['amount_paid' => '1000.999', 'error' => 'amount_paid'],
            ['amount_paid' => '25000', 'error' => 'amount_paid'],
            ['paid_at' => now()->addDay()->format('Y-m-d H:i:s'), 'error' => 'paid_at'],
            ['payment_method' => 'BANK_TRANSFER', 'error' => 'payment_method'],
            ['reference_number' => str_repeat('A', 61), 'error' => 'reference_number'],
            ['payment_method' => 'cashless', 'cashless_type' => 'gcash', 'reference_number' => '!@#@', 'error' => 'reference_number'],
        ] as $casePayload) {
            $payload = $basePayload;
            $error = $casePayload['error'];
            unset($casePayload['error']);

            $this->actingAs($staff)
                ->from('/payments')
                ->post('/payments/pay', array_merge($payload, $casePayload))
                ->assertRedirect('/payments')
                ->assertSessionHasErrors($error);
        }

        $this->actingAs($staff)->from('/payments')->post('/payments/pay', array_merge($basePayload, [
            'payment_method' => 'cashless',
            'cashless_type' => 'gcash',
            'reference_number' => 'GCASH-1234',
            'mobile_number' => '0917ABC4567',
        ]))->assertRedirect('/payments')
            ->assertSessionHasErrors('mobile_number');

        foreach (['<script>alert(1)</script>', '1232', 'juan123', 'Juan@Dela'] as $invalidAccountName) {
            $this->actingAs($staff)->from('/payments')->post('/payments/pay', array_merge($basePayload, [
                'payment_method' => 'cashless',
                'cashless_type' => 'bank_transfer',
                'bank_name' => 'BPI',
                'reference_number' => 'TXN-1234',
                'account_name' => $invalidAccountName,
            ]))->assertRedirect('/payments')
                ->assertSessionHasErrors('account_name');
        }
    }

    public function test_valid_card_and_mobile_payment_inputs_are_saved_normalized(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Valid Strict Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Valid Strict Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC3106',
            'total_amount' => 20000,
            'total_paid' => 0,
            'balance_amount' => 20000,
            'payment_status' => 'UNPAID',
            'case_status' => 'ACTIVE',
        ]);

        $this->actingAs($staff)->post('/payments/pay', [
            'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => '1000.50',
            'payment_method' => 'cashless',
            'cashless_type' => 'gcash',
            'reference_number' => 'GCASH/ABC-123',
            'account_name' => 'Juan Dela Cruz',
            'mobile_number' => '+639171234567',
            'accounting_reference_no' => 'OR-VALID-001',
            'received_by' => 'Accounting Staff',
        ])->assertRedirect('/payments');

        $this->assertDatabaseHas('payments', [
            'funeral_case_id' => $case->id,
            'mobile_number' => '09171234567',
            'account_name' => 'Juan Dela Cruz',
            'reference_number' => 'GCASH/ABC-123',
            'amount' => 1000.50,
        ]);

        $this->actingAs($staff)->post('/payments/pay', [
            'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => '500.00',
            'payment_method' => 'cashless',
            'cashless_type' => 'card',
            'approval_code' => 'AUTH-001',
            'card_type' => 'credit',
            'terminal_provider' => 'Terminal 1',
            'accounting_reference_no' => 'OR-VALID-002',
            'received_by' => 'Accounting Staff',
        ])->assertRedirect('/payments');

        $this->assertDatabaseHas('payments', [
            'funeral_case_id' => $case->id,
            'cashless_type' => 'card',
            'approval_code' => 'AUTH-001',
            'card_type' => 'credit',
            'terminal_provider' => 'Terminal 1',
        ]);
    }

    public function test_receipt_number_can_be_added_later_and_cannot_duplicate_within_branch(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Receipt Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Receipt Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC3107',
            'total_amount' => 20000,
            'total_paid' => 0,
            'balance_amount' => 20000,
            'payment_status' => 'UNPAID',
            'case_status' => 'ACTIVE',
        ]);

        $basePayload = [
            'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => '1000.00',
            'payment_method' => 'cash',
        ];

        $this->actingAs($staff)->post('/payments/pay', array_merge($basePayload, [
            'receipt_or_no' => 'OR-2026-1001',
        ]))->assertRedirect('/payments');

        $this->actingAs($staff)->from('/payments/history')->post('/payments/pay', array_merge($basePayload, [
            'receipt_or_no' => 'OR-2026-1001',
        ]))->assertRedirect('/payments/history')
            ->assertSessionHasErrors('receipt_or_no');

        $this->actingAs($staff)->post('/payments/pay', $basePayload)->assertRedirect('/payments');
        $paymentWithoutReceipt = Payment::query()->latest('id')->firstOrFail();

        $this->actingAs($staff)
            ->from('/payments/history')
            ->patch(route('payments.receipt.update', $paymentWithoutReceipt, absolute: false), [
                'receipt_or_no' => 'OR-2026-1001',
            ])
            ->assertRedirect('/payments/history')
            ->assertSessionHasErrors('receipt_or_no');

        $this->actingAs($staff)
            ->patch(route('payments.receipt.update', $paymentWithoutReceipt, absolute: false), [
                'receipt_or_no' => 'OR-2026-1002',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'id' => $paymentWithoutReceipt->id,
            'receipt_or_no' => 'OR-2026-1002',
            'accounting_reference_no' => 'OR-2026-1002',
        ]);
    }

    public function test_other_branch_case_details_do_not_offer_add_payment_action(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Other Branch');
        $mainAdmin = $this->createUser('admin', $mainBranch);
        $package = $this->createPackage();
        $client = $this->createClient($otherBranch, 'Other Payment Client');
        $deceased = $this->createDeceased($otherBranch, $client, 'Other Payment Deceased');
        $case = $this->createCase($otherBranch, $client, $deceased, $package, [
            'entry_source' => 'OTHER_BRANCH',
            'case_status' => 'COMPLETED',
            'payment_status' => 'PAID',
            'total_paid' => 20000,
            'balance_amount' => 0,
            'reporter_name' => 'Reporter One',
        ]);

        $this->actingAs($mainAdmin)
            ->get("/funeral-cases/{$case->id}")
            ->assertOk()
            ->assertDontSee('Add Payment')
            ->assertSee('locked for payment updates');
    }

    public function test_case_with_payments_cannot_be_deleted(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Delete Guard Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Delete Guard Deceased');
        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC3010',
            'total_paid' => 5000,
            'balance_amount' => 15000,
            'payment_status' => 'PARTIAL',
        ]);

        Payment::create([
            'receipt_number' => 'RCPT-2026-999999',
            'funeral_case_id' => $case->id,
            'branch_id' => $mainBranch->id,
            'method' => 'CASH',
            'amount' => 5000,
            'balance_after_payment' => 15000,
            'payment_status_after_payment' => 'PARTIAL',
            'paid_date' => now()->toDateString(),
            'paid_at' => now(),
            'recorded_by' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->from('/funeral-cases')
            ->delete("/funeral-cases/{$case->id}")
            ->assertRedirect('/funeral-cases')
            ->assertSessionHasErrors('case');

        $this->assertDatabaseHas('funeral_cases', [
            'id' => $case->id,
        ]);
    }

    private function createUser(string $role, ?Branch $branch = null, bool $canEncodeAnyBranch = false): User
    {
        return User::factory()->create([
            'role' => $role,
            'admin_scope' => $role === 'admin' ? 'main' : null,
            'is_active' => true,
            'branch_id' => $branch?->id,
            'can_encode_any_branch' => $canEncodeAnyBranch,
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

    private function createPackage(): Package
    {
        return Package::create([
            'name' => 'Standard Package',
            'coffin_type' => 'Standard',
            'price' => 20000,
            'is_active' => true,
        ]);
    }

    private function createClient(Branch $branch, string $name, string $contact = '09170000000', string $address = 'Client Address'): Client
    {
        return Client::create([
            'branch_id' => $branch->id,
            'full_name' => $name,
            'relationship_to_deceased' => 'Other',
            'contact_number' => $contact,
            'valid_id_type' => 'Legacy Record',
            'valid_id_number' => 'LEGACY-' . strtoupper((string) \Illuminate\Support\Str::ulid()),
            'address' => $address,
        ]);
    }

    private function createDeceased(Branch $branch, Client $client, string $name, array $overrides = []): Deceased
    {
        return Deceased::create(array_merge([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => $name,
            'address' => 'Client Address',
            'born' => now()->subYears(60)->toDateString(),
            'died' => now()->subDays(2)->toDateString(),
            'date_of_death' => now()->subDays(2)->toDateString(),
            'age' => 60,
            'interment' => now()->addDay()->toDateString(),
            'interment_at' => now()->addDay(),
            'place_of_cemetery' => 'Public Cemetery',
            'embalming_required' => true,
            'embalming_status' => 'PENDING',
        ], $overrides));
    }

    private function createCase(Branch $branch, Client $client, Deceased $deceased, Package $package, array $overrides = []): FuneralCase
    {
        return FuneralCase::create(array_merge([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_code' => 'FC9999',
            'service_requested_at' => now()->toDateString(),
            'service_package' => $package->name,
            'coffin_type' => $package->coffin_type,
            'wake_location' => 'Family Residence',
            'funeral_service_at' => now()->addDay()->toDateString(),
            'subtotal_amount' => 20000,
            'discount_type' => 'NONE',
            'discount_value_type' => 'AMOUNT',
            'discount_value' => 0,
            'discount_amount' => 0,
            'total_amount' => 20000,
            'total_paid' => 0,
            'balance_amount' => 20000,
            'payment_status' => 'UNPAID',
            'paid_at' => null,
            'case_status' => 'ACTIVE',
            'reported_branch_id' => $branch->id,
            'reported_at' => now(),
            'encoded_by' => null,
            'entry_source' => 'MAIN',
            'verification_status' => 'VERIFIED',
            'verified_by' => null,
            'verified_at' => now(),
            'verification_note' => null,
        ], $overrides));
    }

    private function baseIntakePayload(Branch $branch, Package $package, array $overrides = []): array
    {
        $deathDate = now()->subDay()->toDateString();
        $funeralDate = now()->addDay()->toDateString();
        $intermentAt = now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i:s');

        return array_merge([
            'service_requested_at' => now()->toDateString(),
            'branch_id' => $branch->id,
            'client_name' => 'Client Intake',
            'client_relationship' => 'Daughter',
            'client_contact_number' => '09170000000',
            'client_email' => 'family@example.com',
            'client_valid_id_type' => 'National ID',
            'client_valid_id_number' => 'ID-1000',
            'client_address' => 'Client Address',
            'deceased_name' => 'Deceased Intake',
            'deceased_address' => 'Client Address',
            'born' => now()->subYears(65)->toDateString(),
            'died' => $deathDate,
            'civil_status' => 'WIDOWED',
            'wake_location' => 'Family Residence',
            'wake_start_date' => now()->toDateString(),
            'wake_start_time' => '08:00',
            'wake_end_date' => $funeralDate,
            'wake_end_time' => '07:00',
            'funeral_service_at' => $funeralDate,
            'funeral_service_time' => '08:00',
            'interment_at' => $intermentAt,
            'interment_time' => '09:00',
            'place_of_cemetery' => 'Public Cemetery',
            'case_status' => 'ACTIVE',
            'transport_option' => 'HEARSE',
            'coffin_length_cm' => 175,
            'coffin_size' => 'LARGE',
            'embalming_required' => 1,
            'embalming_status' => 'PENDING',
            'package_id' => $package->id,
            'additional_service_amount' => 0,
            'senior_citizen_status' => 0,
            'senior_citizen_id_number' => null,
            'pwd_status' => 0,
            'pwd_id_number' => null,
            'payment_method' => !empty($overrides['mark_as_paid']) ? 'cash' : 'none',
        ], $overrides);
    }
}
