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

        $response->assertRedirect(route('funeral-cases.other-reports', absolute: false));
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
                    && $cases->perPage() === 20
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
            ->assertSee('Assigned Branch: BR002 - Branch Two')
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

        $response->assertRedirect(route('funeral-cases.index', ['record_scope' => 'main'], absolute: false));

        $case = FuneralCase::query()->latest('id')->first();
        $this->assertNotNull($case);
        $this->assertSame('PARTIAL', $case->payment_status);
        $this->assertEquals(5000.0, (float) $case->total_paid);
        $this->assertEquals(round((float) $case->total_amount - 5000.0, 2), (float) $case->balance_amount);

        $payment = Payment::query()->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(5000.0, (float) $payment->amount);
        $this->assertSame($case->id, $payment->funeral_case_id);
        $this->assertNotNull($payment->receipt_number);
        $this->assertSame('PARTIAL', $payment->payment_status_after_payment);
        $this->assertEquals(round((float) $case->total_amount - 5000.0, 2), (float) $payment->balance_after_payment);
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

        $response->assertRedirect(route('funeral-cases.index', ['record_scope' => 'main'], absolute: false));

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

        $response->assertRedirect(route('funeral-cases.index', ['record_scope' => 'main'], absolute: false));

        $this->assertDatabaseHas('funeral_cases', [
            'discount_type' => 'PWD',
            'discount_amount' => 4000,
            'additional_service_amount' => 5000,
            'total_amount' => 21000,
        ]);
    }

    public function test_case_details_support_case_centered_payment_entry(): void
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
            ->assertSee('Add Payment')
            ->assertSee('Resulting Payment Status');

        $this->actingAs($staff)
            ->post('/payments/pay', [
                'funeral_case_id' => $case->id,
                'paid_at' => now()->format('Y-m-d H:i:s'),
                'amount_paid' => 10000,
                'payment_method' => 'cash',
                'accounting_reference_no' => 'OR-CASE-001',
                'received_by' => 'Accounting Staff',
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
        $this->assertSame('PARTIAL', $payment->payment_status_after_payment);
        $this->assertEquals(5000.0, (float) $payment->balance_after_payment);

        $this->assertDatabaseHas('payments', [
            'funeral_case_id' => $case->id,
            'amount' => 10000,
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
            'funeral_service_at' => $funeralDate,
            'interment_at' => $intermentAt,
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
        ], $overrides);
    }
}
