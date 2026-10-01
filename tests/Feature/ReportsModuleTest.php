<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\Payment;
use App\Models\ServiceDetail;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        }
    }

    public function test_staff_cannot_access_reports_routes(): void
    {
        $staff = $this->user('staff', $this->branch('BR001', 'Main Branch'));

        $this->actingAs($staff)->get('/reports')->assertForbidden();
        $this->actingAs($staff)->get('/reports/analytics')->assertForbidden();
        $this->actingAs($staff)->getJson('/reports/preview?report_type=sales')->assertForbidden();
        $this->actingAs($staff)->get('/reports/print?report_type=sales')->assertForbidden();
    }

    public function test_admin_and_owner_default_to_analytics_from_reports_entry(): void
    {
        $this->actingAs($this->user('admin'))
            ->get('/reports')
            ->assertRedirect(route('reports.analytics', absolute: false));

        $this->actingAs($this->user('owner'))
            ->get('/reports')
            ->assertRedirect(route('owner.analytics', absolute: false));
    }

    public function test_admin_and_owner_can_access_reports_index_with_report_type(): void
    {
        $this->actingAs($this->user('admin'))
            ->get('/reports?report_type=owner_branch_analytics')
            ->assertOk();

        $this->actingAs($this->user('owner'))
            ->get('/reports?report_type=owner_branch_analytics')
            ->assertOk();
    }

    public function test_admin_can_access_shared_analytics_dashboard(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get('/reports')
            ->assertRedirect(route('reports.analytics', absolute: false));

        $this->actingAs($admin)
            ->get('/reports/analytics')
            ->assertOk()
            ->assertSee('Revenue Trend')
            ->assertSee('Reports');
    }

    public function test_branch_admin_analytics_dashboard_is_forced_to_assigned_branch(): void
    {
        $assigned = $this->branch('BR002', 'Second Branch');
        $other = $this->branch('BR001', 'Main Branch');
        $branchAdmin = $this->branchAdmin($assigned);

        $this->case($assigned, ['case_code' => 'ASSIGNED-001']);
        $this->case($other, ['case_code' => 'OTHER-001']);

        $this->actingAs($branchAdmin)
            ->get('/reports/analytics?branch_id=' . $other->id)
            ->assertOk()
            ->assertSee('BR002')
            ->assertDontSee('BR001');
    }

    public function test_branch_admin_report_user_filter_only_lists_assigned_branch_users(): void
    {
        $assigned = $this->branch('BR002', 'Second Branch');
        $other = $this->branch('BR003', 'Other Branch');
        $branchAdmin = $this->branchAdmin($assigned);
        $assignedStaff = $this->user('staff', $assigned);
        $otherStaff = $this->user('staff', $other);

        $this->actingAs($branchAdmin)
            ->get('/reports?report_type=master_cases')
            ->assertOk()
            ->assertSee($branchAdmin->name)
            ->assertSee($assignedStaff->name)
            ->assertDontSee($otherStaff->name);
    }

    public function test_payment_monitoring_with_balance_includes_unpaid_and_partial_cases_without_requiring_a_payment(): void
    {
        $admin = $this->user('admin');
        $branch = $admin->branch;

        $this->case($branch, ['case_code' => 'FOLLOW-UP-UNPAID', 'payment_status' => 'UNPAID']);
        $this->case($branch, [
            'case_code' => 'FOLLOW-UP-PARTIAL',
            'payment_status' => 'PARTIAL',
            'total_paid' => 4000,
            'balance_amount' => 6000,
        ]);
        $this->case($branch, [
            'case_code' => 'FOLLOW-UP-COMPLETED',
            'case_status' => 'COMPLETED',
            'payment_status' => 'PARTIAL',
            'total_paid' => 4000,
            'balance_amount' => 6000,
        ]);
        $this->case($branch, [
            'case_code' => 'DRAFT-NOT-FOLLOW-UP',
            'case_status' => 'DRAFT',
            'payment_status' => 'UNPAID',
            'balance_amount' => 10000,
        ]);
        $this->case($branch, [
            'case_code' => 'NO-FOLLOW-UP-PAID',
            'payment_status' => 'PAID',
            'total_paid' => 10000,
            'balance_amount' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payment-monitoring', ['payment_status' => 'WITH_BALANCE']))
            ->assertOk()
            ->assertSee('FOLLOW-UP-UNPAID')
            ->assertSee('FOLLOW-UP-PARTIAL')
            ->assertSee('FOLLOW-UP-COMPLETED')
            ->assertDontSee('DRAFT-NOT-FOLLOW-UP')
            ->assertDontSee('NO-FOLLOW-UP-PAID')
            ->assertSee('<option value="WITH_BALANCE" selected>With Balance</option>', false)
            ->assertSee('Cases With Balance');
    }

    public function test_payment_monitoring_all_statuses_includes_cases_without_payments(): void
    {
        $admin = $this->user('admin');
        $branch = $admin->branch;

        $this->case($branch, ['case_code' => 'ALL-STATUS-UNPAID', 'payment_status' => 'UNPAID']);
        $this->case($branch, [
            'case_code' => 'ALL-STATUS-PAID',
            'payment_status' => 'PAID',
            'total_paid' => 10000,
            'balance_amount' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payment-monitoring'))
            ->assertOk()
            ->assertSee('ALL-STATUS-UNPAID')
            ->assertSee('ALL-STATUS-PAID')
            ->assertSee('Cases Shown');
    }

    public function test_payment_monitoring_with_payments_filter_excludes_cases_without_payments(): void
    {
        $admin = $this->user('admin');
        $branch = $admin->branch;
        $withPayment = $this->case($branch, [
            'case_code' => 'HAS-PAYMENT-CASE',
            'payment_status' => 'PARTIAL',
            'total_paid' => 500,
            'balance_amount' => 9500,
        ]);
        $this->case($branch, ['case_code' => 'NO-PAYMENT-CASE', 'payment_status' => 'UNPAID']);

        Payment::create([
            'funeral_case_id' => $withPayment->id,
            'branch_id' => $branch->id,
            'payment_record_no' => 'PAY-2026-000010',
            'payment_method' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'paid_at' => now(),
            'status' => 'VALID',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payment-monitoring', ['payment_status' => 'HAS_PAYMENT']))
            ->assertOk()
            ->assertSee('HAS-PAYMENT-CASE')
            ->assertDontSee('NO-PAYMENT-CASE')
            ->assertSee('<option value="HAS_PAYMENT" selected>With Payments</option>', false);
    }

    public function test_payment_monitoring_date_preset_shows_and_applies_the_selected_period(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.payment-monitoring', ['date_preset' => 'month']))
            ->assertOk()
            ->assertViewHas('paidFrom', now()->startOfMonth()->toDateString())
            ->assertViewHas('paidTo', now()->endOfMonth()->toDateString())
            ->assertSee('<option value="month" selected>This Month</option>', false);
    }

    public function test_payment_monitoring_totals_follow_payment_method_and_date_filters(): void
    {
        $admin = $this->user('admin');
        $branch = $admin->branch;
        $case = $this->case($branch, [
            'case_code' => 'FILTERED-TOTALS',
            'payment_status' => 'PARTIAL',
            'total_paid' => 1000,
            'balance_amount' => 9000,
        ]);

        Payment::create([
            'funeral_case_id' => $case->id,
            'branch_id' => $branch->id,
            'payment_record_no' => 'PAY-2026-000001',
            'payment_method' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 600,
            'paid_at' => now()->subMonth(),
            'status' => 'VALID',
        ]);
        Payment::create([
            'funeral_case_id' => $case->id,
            'branch_id' => $branch->id,
            'payment_record_no' => 'PAY-2026-000002',
            'payment_method' => 'cashless',
            'payment_mode' => 'bank_transfer',
            'amount' => 400,
            'paid_at' => now(),
            'status' => 'VALID',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payment-monitoring', [
                'payment_method' => 'cashless',
                'paid_from' => now()->toDateString(),
                'paid_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('paymentRecordsCount', 1)
            ->assertViewHas('totalCollected', fn ($total) => (float) $total === 400.0);
    }

    public function test_summary_case_keeps_complete_valid_history_when_period_is_filtered(): void
    {
        $admin = $this->user('admin');
        $case = $this->case($admin->branch, [
            'case_code' => 'COMPLETE-HISTORY',
            'payment_status' => 'PARTIAL',
            'total_paid' => 1000,
            'balance_amount' => 9000,
        ]);

        foreach ([
            ['PAY-OLD-HISTORY', 600, now()->subMonth()],
            ['PAY-CURRENT-HISTORY', 400, now()],
        ] as [$recordNo, $amount, $paidAt]) {
            Payment::create([
                'funeral_case_id' => $case->id,
                'branch_id' => $admin->branch_id,
                'payment_record_no' => $recordNo,
                'payment_method' => 'cash',
                'payment_mode' => 'cash',
                'amount' => $amount,
                'paid_at' => $paidAt,
                'status' => 'VALID',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.payment-monitoring', [
                'paid_from' => now()->toDateString(),
                'paid_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('paymentRecordsCount', 1)
            ->assertViewHas('paymentCases', fn ($cases) => $cases->first()->payments->count() === 2)
            ->assertSee('PAY-OLD-HISTORY')
            ->assertSee('PAY-CURRENT-HISTORY');
    }

    public function test_payment_history_print_is_complete_and_respects_branch_scope(): void
    {
        $branch = $this->branch('BR001', 'Main Branch');
        $otherBranch = $this->branch('BR002', 'Other Branch');
        $branchAdmin = $this->branchAdmin($branch);
        $ownCase = $this->case($branch, ['case_code' => 'PRINT-OWN-HISTORY']);
        $otherCase = $this->case($otherBranch, ['case_code' => 'PRINT-OTHER-HISTORY']);

        Payment::create([
            'funeral_case_id' => $ownCase->id,
            'branch_id' => $branch->id,
            'payment_record_no' => 'PAY-PRINT-VALID',
            'payment_method' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'paid_at' => now(),
            'status' => 'VALID',
        ]);
        Payment::create([
            'funeral_case_id' => $ownCase->id,
            'branch_id' => $branch->id,
            'payment_record_no' => 'PAY-PRINT-VOID',
            'payment_method' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 100,
            'paid_at' => now(),
            'status' => 'VOID',
        ]);

        $this->actingAs($branchAdmin)
            ->get(route('payments.history.print', $ownCase))
            ->assertOk()
            ->assertSee('PAY-PRINT-VALID')
            ->assertSee('Previous Balance')
            ->assertDontSee('PAY-PRINT-VOID');

        $this->get(route('payments.history.print', $otherCase))->assertForbidden();
    }

    public function test_payment_monitoring_print_preview_uses_current_scope_and_clean_report_content(): void
    {
        $admin = $this->user('admin');
        $case = $this->case($admin->branch, [
            'case_code' => 'MONITORING-PRINT-CASE',
            'payment_status' => 'PARTIAL',
            'total_paid' => 2500,
            'balance_amount' => 7500,
        ]);
        Payment::create([
            'funeral_case_id' => $case->id,
            'branch_id' => $admin->branch_id,
            'payment_record_no' => 'PAY-MONITORING-PRINT',
            'payment_method' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 2500,
            'paid_at' => now(),
            'status' => 'VALID',
        ]);

        $this->actingAs($admin)
            ->get(route('payments.monitoring.print', ['payment_status' => 'WITH_BALANCE']))
            ->assertOk()
            ->assertSee('Payment Monitoring')
            ->assertSee('MONITORING-PRINT-CASE')
            ->assertSee('Amount Availed')
            ->assertSee('Last Payment Date')
            ->assertSee('Total Amount Availed')
            ->assertSee('Branch Collectibles')
            ->assertDontSee('Search client');
    }

    public function test_case_record_lists_paginate_and_search_all_matching_records(): void
    {
        $branch = $this->branch('BR001', 'Main Branch');
        $admin = $this->user('admin', $branch);
        $staff = $this->user('staff', $branch);
        for ($index = 1; $index <= 26; $index++) {
            $this->case($branch, ['case_code' => sprintf('PAGING-%03d', $index)]);
        }

        foreach ([[$admin, 'admin.cases.index'], [$staff, 'funeral-cases.index']] as [$user, $route]) {
            $this->actingAs($user)->get(route($route))
                ->assertOk()
                ->assertSee('Rows per page')
                ->assertViewHas('cases', fn ($cases) => $cases->perPage() === 25 && $cases->count() === 25 && $cases->total() === 26);

            $this->get(route($route, ['page' => 2]))->assertOk()
                ->assertViewHas('cases', fn ($cases) => $cases->count() === 1);

            foreach ([10, 25, 50, 100] as $size) {
                $this->get(route($route, ['per_page' => $size, 'q' => 'PAGING']))->assertOk()
                    ->assertViewHas('cases', fn ($cases) => $cases->perPage() === $size
                        && $cases->count() === min($size, 26)
                        && $cases->hasPages() === ($size < 26)
                        && str_contains($cases->url(2), 'q=PAGING')
                        && str_contains($cases->url(2), 'per_page=' . $size));
            }

            $this->get(route($route, ['q' => 'PAGING-026', 'per_page' => 10]))->assertOk()
                ->assertViewHas('cases', fn ($cases) => $cases->total() === 1 && $cases->first()->case_code === 'PAGING-026');

            $this->getJson(route($route, ['per_page' => 999]))->assertUnprocessable()
                ->assertJsonValidationErrors('per_page');
        }
    }

    public function test_record_filters_combine_balance_package_and_interment_range(): void
    {
        $branch = $this->branch('BR001', 'Main Branch');
        $match = $this->case($branch, ['case_code' => 'FILTER-MATCH', 'interment_at' => '2026-09-20 09:00:00', 'payment_status' => 'PARTIAL']);
        $paid = $this->case($branch, ['case_code' => 'FILTER-PAID', 'interment_at' => '2026-09-20 09:00:00', 'payment_status' => 'PAID']);
        $outside = $this->case($branch, ['case_code' => 'FILTER-OUTSIDE', 'interment_at' => '2026-10-01 09:00:00']);
        foreach ([$match, $paid, $outside] as $case) {
            ServiceDetail::create(['funeral_case_id' => $case->id, 'internment_date' => $case->interment_at->toDateString()]);
        }
        foreach ([['admin', 'admin.cases.index'], ['staff', 'funeral-cases.index']] as [$role, $route]) {
            $this->actingAs($this->user($role, $branch))
                ->get(route($route, [
                    'payment_status' => 'WITH_BALANCE', 'package_id' => $match->package_id,
                    'interment_from' => '2026-09-01', 'interment_to' => '2026-09-30', 'per_page' => 50,
                ]))
                ->assertOk()
                ->assertViewHas('cases', fn ($cases) => $cases->total() === 1 && $cases->first()->id === $match->id && $cases->perPage() === 50);
        }
    }

    public function test_master_case_with_balance_filter_includes_unpaid_and_partial_only(): void
    {
        $admin = $this->user('admin');
        $branch = $admin->branch;

        $this->case($branch, ['case_code' => 'BALANCE-UNPAID', 'payment_status' => 'UNPAID']);
        $this->case($branch, ['case_code' => 'BALANCE-PARTIAL', 'payment_status' => 'PARTIAL']);
        $this->case($branch, ['case_code' => 'BALANCE-PAID', 'payment_status' => 'PAID']);

        $this->actingAs($admin)
            ->get(route('admin.cases.index', ['payment_status' => 'WITH_BALANCE']))
            ->assertOk()
            ->assertSee('BALANCE-UNPAID')
            ->assertSee('BALANCE-PARTIAL')
            ->assertDontSee('BALANCE-PAID')
            ->assertSee('<option value="WITH_BALANCE" selected>With Balance</option>', false);
    }

    public function test_old_owner_sales_route_redirects_to_central_reports_module(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->get('/owner/sales-per-branch')
            ->assertRedirect(route('reports.index', ['report_type' => 'owner_branch_analytics'], absolute: false));
    }

    public function test_staff_cannot_access_old_owner_sales_route_or_csv_export(): void
    {
        $staff = $this->user('staff', $this->branch('BR001', 'Main Branch'));

        $this->actingAs($staff)->get('/owner/sales-per-branch')->assertForbidden();
        $this->actingAs($staff)->get('/reports/export-csv?report_type=owner_branch_analytics')->assertForbidden();
    }

    public function test_collection_report_is_transaction_based_and_filters_payment_method(): void
    {
        $admin = $this->user('admin');
        $branch = $admin->branch;

        $case = $this->case($branch, ['case_code' => 'COLLECT-001']);
        foreach ([['PAY-CASH', 'cash', 600], ['PAY-BANK', 'bank_transfer', 400]] as [$record, $method, $amount]) {
            Payment::create(['funeral_case_id' => $case->id, 'branch_id' => $branch->id, 'payment_record_no' => $record, 'payment_method' => $method, 'payment_mode' => $method, 'amount' => $amount, 'paid_at' => now(), 'status' => 'VALID']);
        }

        $response = $this->actingAs($admin)->getJson('/reports/preview?report_type=sales&payment_method=cash');

        $response->assertOk()
            ->assertJsonPath('summary.total_records', 1)
            ->assertJsonPath('summary.amount_collected', 600)
            ->assertJsonPath('rows.0.case_code', 'COLLECT-001');
    }

    public function test_collection_report_lists_each_valid_payment_in_the_payment_period(): void
    {
        $admin = $this->user('admin');
        $case = $this->case($admin->branch, ['case_code' => 'MULTI-COLLECTION']);
        foreach ([['PAY-SEP-1', 20000, 'VALID'], ['PAY-SEP-2', 10000, 'VALID'], ['PAY-VOID', 5000, 'VOID']] as [$record, $amount, $status]) {
            Payment::create(['funeral_case_id' => $case->id, 'branch_id' => $admin->branch_id, 'payment_record_no' => $record, 'payment_method' => 'cash', 'payment_mode' => 'cash', 'amount' => $amount, 'paid_at' => '2026-09-20 10:00:00', 'status' => $status]);
        }

        $this->actingAs($admin)
            ->getJson('/reports/preview?report_type=sales&date_from=2026-09-01&date_to=2026-09-30')
            ->assertOk()
            ->assertJsonCount(2, 'rows')
            ->assertJsonPath('summary.amount_collected', 30000);
    }

    public function test_case_monitoring_period_uses_service_request_date(): void
    {
        $admin = $this->user('admin');
        $this->case($admin->branch, ['case_code' => 'REQUEST-IN-RANGE', 'service_requested_at' => '2026-09-15']);
        $this->case($admin->branch, ['case_code' => 'REQUEST-OUTSIDE', 'service_requested_at' => '2026-08-31']);

        $this->actingAs($admin)
            ->getJson('/reports/preview?report_type=master_cases&date_from=2026-09-01&date_to=2026-09-30')
            ->assertOk()
            ->assertJsonCount(1, 'rows')
            ->assertJsonPath('rows.0.case_code', 'REQUEST-IN-RANGE');
    }

    public function test_reports_selector_uses_final_report_names_and_period_options(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get('/reports?report_type=sales')
            ->assertOk()
            ->assertSee('Collection Report')
            ->assertSee('Case Monitoring Report')
            ->assertSee('This Week')
            ->assertSee('This Quarter')
            ->assertSee('First Half')
            ->assertSee('Second Half');
    }

    public function test_report_summary_cards_use_clear_scope_labels_and_are_not_click_filters(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get('/reports?report_type=master_cases')
            ->assertOk()
            ->assertSee('Cases in Period')
            ->assertSee('Current Collectibles')
            ->assertSee('All Payments for These Cases')
            ->assertSee('Payments Received in Period')
            ->assertSee('Current Balance')
            ->assertSee('Valid payments received within the selected payment period.')
            ->assertDontSee('@click="selectMetric(card.key)"', false)
            ->assertDontSee('View details');
    }

    public function test_preview_filters_by_branch_id(): void
    {
        $admin = $this->user('admin');
        $main = $admin->branch;
        $other = $this->branch('BR002', 'Second Branch');

        $mainCase = $this->case($main, ['case_code' => 'MAIN-001']);
        $otherCase = $this->case($other, ['case_code' => 'OTHER-001']);
        Payment::create(['funeral_case_id' => $mainCase->id, 'branch_id' => $main->id, 'payment_record_no' => 'PAY-MAIN', 'payment_method' => 'cash', 'payment_mode' => 'cash', 'amount' => 100, 'paid_at' => now(), 'status' => 'VALID']);
        Payment::create(['funeral_case_id' => $otherCase->id, 'branch_id' => $other->id, 'payment_record_no' => 'PAY-OTHER', 'payment_method' => 'cash', 'payment_mode' => 'cash', 'amount' => 100, 'paid_at' => now(), 'status' => 'VALID']);

        $response = $this->actingAs($admin)->getJson('/reports/preview?report_type=sales&branch_id=' . $other->id);

        $response->assertOk()
            ->assertJsonPath('summary.total_records', 1)
            ->assertJsonPath('rows.0.branch', 'BR002 - Second Branch');
    }

    public function test_branch_admin_reports_are_forced_to_assigned_branch(): void
    {
        $assigned = $this->branch('BR002', 'Second Branch');
        $other = $this->branch('BR001', 'Main Branch');
        $branchAdmin = $this->branchAdmin($assigned);

        $assignedCase = $this->case($assigned, ['case_code' => 'ASSIGNED-001']);
        $otherCase = $this->case($other, ['case_code' => 'OTHER-001']);
        Payment::create(['funeral_case_id' => $assignedCase->id, 'branch_id' => $assigned->id, 'payment_record_no' => 'PAY-ASSIGNED', 'payment_method' => 'cash', 'payment_mode' => 'cash', 'amount' => 100, 'paid_at' => now(), 'status' => 'VALID']);
        Payment::create(['funeral_case_id' => $otherCase->id, 'branch_id' => $other->id, 'payment_record_no' => 'PAY-OTHER', 'payment_method' => 'cash', 'payment_mode' => 'cash', 'amount' => 100, 'paid_at' => now(), 'status' => 'VALID']);

        $response = $this->actingAs($branchAdmin)
            ->getJson('/reports/preview?report_type=sales&branch_id=' . $other->id);

        $response->assertOk()
            ->assertJsonPath('summary.total_records', 1)
            ->assertJsonPath('rows.0.branch', 'BR002 - Second Branch')
            ->assertJsonPath('filters.branch_id', 'BR002 - Second Branch');
    }

    public function test_branch_admin_print_and_csv_are_forced_to_assigned_branch(): void
    {
        $assigned = $this->branch('BR002', 'Second Branch');
        $other = $this->branch('BR001', 'Main Branch');
        $branchAdmin = $this->branchAdmin($assigned);

        $assignedCase = $this->case($assigned, ['case_code' => 'ASSIGNED-001']);
        $otherCase = $this->case($other, ['case_code' => 'OTHER-001']);
        Payment::create(['funeral_case_id' => $assignedCase->id, 'branch_id' => $assigned->id, 'payment_record_no' => 'PAY-ASSIGNED-PRINT', 'payment_method' => 'cash', 'payment_mode' => 'cash', 'amount' => 100, 'paid_at' => now(), 'status' => 'VALID']);
        Payment::create(['funeral_case_id' => $otherCase->id, 'branch_id' => $other->id, 'payment_record_no' => 'PAY-OTHER-PRINT', 'payment_method' => 'cash', 'payment_mode' => 'cash', 'amount' => 100, 'paid_at' => now(), 'status' => 'VALID']);

        $print = $this->actingAs($branchAdmin)
            ->get('/reports/print?report_type=sales&branch_id=' . $other->id);

        $print->assertOk()
            ->assertSee('BR002 - Second Branch')
            ->assertDontSee('BR001 - Main Branch');

        $csv = $this->actingAs($branchAdmin)
            ->get('/reports/export-csv?report_type=sales&branch_id=' . $other->id);

        $csv->assertOk();
        $content = $csv->streamedContent();

        $this->assertStringContainsString('BR002 - Second Branch', $content);
        $this->assertStringNotContainsString('BR001 - Main Branch', $content);
    }

    public function test_branch_admin_owner_analytics_is_forced_to_assigned_branch(): void
    {
        $assigned = $this->branch('BR002', 'Second Branch');
        $other = $this->branch('BR001', 'Main Branch');
        $branchAdmin = $this->branchAdmin($assigned);

        $this->case($assigned, ['case_code' => 'ASSIGNED-001']);
        $this->case($other, ['case_code' => 'OTHER-001']);

        $response = $this->actingAs($branchAdmin)
            ->getJson('/reports/preview?report_type=owner_branch_analytics');

        $response->assertOk()
            ->assertJsonPath('summary.total_cases', 1)
            ->assertJsonCount(1, 'rows')
            ->assertJsonPath('rows.0.branch', 'BR002 - Second Branch');
    }

    public function test_branch_admin_audit_logs_are_forced_to_assigned_branch(): void
    {
        $assigned = $this->branch('BR002', 'Second Branch');
        $other = $this->branch('BR001', 'Main Branch');
        $branchAdmin = $this->branchAdmin($assigned);

        AuditLog::create([
            'actor_id' => $branchAdmin->id,
            'actor_role' => 'admin',
            'action' => 'assigned.action',
            'action_label' => 'Assigned Action',
            'action_type' => 'create',
            'entity_type' => 'funeral_case',
            'entity_id' => 1,
            'branch_id' => $assigned->id,
            'status' => 'success',
        ]);
        AuditLog::create([
            'actor_id' => $branchAdmin->id,
            'actor_role' => 'admin',
            'action' => 'other.action',
            'action_label' => 'Other Action',
            'action_type' => 'create',
            'entity_type' => 'funeral_case',
            'entity_id' => 2,
            'branch_id' => $other->id,
            'status' => 'success',
        ]);
        AuditLog::create([
            'actor_id' => $branchAdmin->id,
            'actor_role' => 'admin',
            'action' => 'target.assigned',
            'action_label' => 'Target Assigned Action',
            'action_type' => 'permission',
            'entity_type' => 'user',
            'entity_id' => 3,
            'branch_id' => $other->id,
            'target_branch_id' => $assigned->id,
            'status' => 'success',
        ]);

        $response = $this->actingAs($branchAdmin)
            ->getJson('/reports/preview?report_type=audit_logs&branch_id=' . $other->id);

        $response->assertOk()
            ->assertJsonPath('summary.total_records', 2)
            ->assertJsonFragment(['action' => 'Assigned Action'])
            ->assertJsonFragment(['action' => 'Target Assigned Action'])
            ->assertJsonMissing(['action' => 'Other Action']);
    }

    public function test_audit_logs_page_has_pdf_export_entry_point(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get('/admin/audit-logs')
            ->assertOk()
            ->assertSee('PDF')
            ->assertSee(route('admin.audit-logs.exportPdf', absolute: false));
    }

    public function test_audit_logs_pdf_export_reuses_report_filters(): void
    {
        $branch = $this->branch('BR002', 'Second Branch');
        $admin = $this->user('admin', $branch);

        AuditLog::create([
            'actor_id' => $admin->id,
            'actor_role' => 'admin',
            'action' => 'case.created',
            'action_label' => 'Created Action',
            'action_type' => 'create',
            'entity_type' => 'funeral_case',
            'entity_id' => 1,
            'branch_id' => $branch->id,
            'status' => 'success',
        ]);
        $deletedLog = AuditLog::create([
            'actor_id' => $admin->id,
            'actor_role' => 'admin',
            'action' => 'case.deleted',
            'action_label' => 'Deleted Action',
            'action_type' => 'delete',
            'entity_type' => 'funeral_case',
            'entity_id' => 2,
            'branch_id' => $branch->id,
            'status' => 'success',
        ]);

        $this->actingAs($admin)
            ->get('/reports/export-pdf?report_type=audit_logs&action_type=delete')
            ->assertOk()
            ->assertSee('Log ID')
            ->assertSee((string) $deletedLog->id)
            ->assertSee($admin->name)
            ->assertSee('Deleted Action')
            ->assertDontSee('Created Action');
    }

    public function test_invalid_date_range_returns_validation_error(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->getJson('/reports/preview?report_type=sales&date_from=2026-04-20&date_to=2026-04-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_to']);
    }

    public function test_master_case_report_uses_service_detail_interment_date_fallback(): void
    {
        $admin = $this->user('admin');
        $case = $this->case($admin->branch, [
            'case_code' => 'FALLBACK-001',
            'interment_at' => null,
        ]);

        ServiceDetail::create([
            'funeral_case_id' => $case->id,
            'internment_date' => '2026-07-21',
            'case_status' => 'ongoing',
        ]);

        $this->actingAs($admin)
            ->getJson('/reports/preview?report_type=master_cases')
            ->assertOk()
            ->assertJsonPath('rows.0.case_code', 'FALLBACK-001')
            ->assertJsonPath('rows.0.interment_date', '2026-07-21');
    }

    public function test_owner_branch_analytics_returns_grouped_branch_rows(): void
    {
        $owner = $this->user('owner');
        $main = $this->branch('BR001', 'Main Branch');
        $other = $this->branch('BR002', 'Second Branch');

        $this->case($main, ['case_code' => 'MAIN-001', 'payment_status' => 'PAID', 'total_amount' => 10000, 'total_paid' => 10000, 'balance_amount' => 0]);
        $this->case($other, ['case_code' => 'OTHER-001', 'payment_status' => 'PARTIAL', 'total_amount' => 15000, 'total_paid' => 5000, 'balance_amount' => 10000]);
        $this->case($main, [
            'case_code' => 'PENDING-001',
            'payment_status' => 'PAID',
            'total_amount' => 99999,
            'total_paid' => 99999,
            'balance_amount' => 0,
            'verification_status' => 'PENDING',
        ]);

        $response = $this->actingAs($owner)->getJson('/reports/preview?report_type=owner_branch_analytics');

        $response->assertOk()
            ->assertJsonPath('summary.total_cases', 2)
            ->assertJsonPath('summary.gross_amount', 25000)
            ->assertJsonPath('summary.collected_amount', 15000)
            ->assertJsonPath('summary.remaining_balance', 10000)
            ->assertJsonStructure([
                'charts' => [
                    'bar' => ['labels', 'revenue', 'volume', 'colors'],
                    'donut' => ['labels', 'values', 'colors'],
                    'period' => ['labels', 'cases', 'service_amount', 'collected_amount', 'outstanding_balance'],
                    'line' => ['labels', 'data'],
                ],
            ])
            ->assertJsonCount(2, 'rows');
    }

    public function test_owner_can_export_branch_analytics_csv(): void
    {
        $owner = $this->user('owner');
        $branch = $this->branch('BR001', 'Main Branch');

        $this->case($branch, ['case_code' => 'CSV-001', 'payment_status' => 'PAID', 'total_amount' => 10000, 'total_paid' => 10000, 'balance_amount' => 0]);

        $response = $this->actingAs($owner)
            ->get('/reports/export-csv?report_type=owner_branch_analytics');

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim($csv))));

        $this->assertSame([
            'Branch',
            'Cases in Period',
            'Total Amount Availed',
            'All Payments',
            'Current Collectibles',
            'Paid Cases',
            'Partially Paid Cases',
            'Unpaid Cases',
        ], str_getcsv($lines[0]));
        $this->assertSame('BR001 - Main Branch', str_getcsv($lines[1])[0]);
    }

    private function user(string $role, ?Branch $branch = null): User
    {
        if ($role === 'admin' && ! $branch) {
            $branch = $this->branch('BR001', 'Main Branch');
        }

        return User::factory()->create([
            'role' => $role,
            'admin_scope' => $role === 'admin' ? 'main' : null,
            'branch_id' => $branch?->id,
            'is_active' => true,
        ]);
    }

    private function branchAdmin(Branch $branch): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
    }

    private function branch(string $code, string $name): Branch
    {
        return Branch::firstOrCreate(
            ['branch_code' => $code],
            ['branch_name' => $name, 'address' => 'Test Address', 'is_active' => true]
        );
    }

    private function case(Branch $branch, array $overrides = []): FuneralCase
    {
        $client = Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Client ' . $branch->branch_code . uniqid(),
            'relationship_to_deceased' => 'Spouse',
            'contact_number' => '09170000000',
            'address' => 'Test Address',
        ]);

        $deceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => 'Deceased ' . $branch->branch_code . uniqid(),
            'date_of_death' => now()->subDay()->toDateString(),
            'age' => 70,
        ]);

        $package = Package::firstOrCreate(
            ['name' => 'Basic Package'],
            ['coffin_type' => 'Standard', 'price' => 10000, 'is_active' => true]
        );

        return FuneralCase::create(array_merge([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_number' => FuneralCase::nextCaseNumber($branch->id),
            'case_code' => 'CASE-' . uniqid(),
            'service_type' => 'Burial',
            'service_requested_at' => now()->toDateString(),
            'service_package' => 'Basic Package',
            'coffin_type' => 'Standard',
            'wake_location' => 'Test Wake',
            'funeral_service_at' => now()->addDay()->toDateString(),
            'subtotal_amount' => 10000,
            'discount_type' => 'NONE',
            'discount_value_type' => 'AMOUNT',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 10000,
            'total_paid' => 0,
            'balance_amount' => 10000,
            'payment_status' => 'UNPAID',
            'case_status' => 'ACTIVE',
            'entry_source' => 'MAIN',
            'verification_status' => 'VERIFIED',
            'encoded_by' => null,
        ], $overrides));
    }
}
