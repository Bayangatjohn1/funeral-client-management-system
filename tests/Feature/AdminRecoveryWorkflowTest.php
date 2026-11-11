<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentCorrectionRequest;
use App\Models\RestoreRequest;
use App\Models\SystemBackup;
use App\Models\User;
use App\Services\SystemBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AdminRecoveryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_system_admin_can_open_backup_and_retention_modules(): void
    {
        $branch = $this->branch();
        $system = $this->admin('system');
        $branchAdmin = $this->admin('branch', $branch);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);

        $this->actingAs($system)->get(route('admin.backups.index'))->assertOk();
        $this->actingAs($system)->get(route('admin.retention.index'))->assertOk();
        $this->actingAs($branchAdmin)->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.retention.index'))->assertForbidden();
    }

    public function test_payment_correction_is_pending_until_same_branch_admin_approves(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment-proofs/receipt.pdf', 'Receipt fixture');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $admin = $this->admin('branch', $branch);
        $case = $this->case($branch);
        $payment = Payment::create(['funeral_case_id' => $case->id, 'branch_id' => $branch->id, 'payment_record_no' => 'PAY-2026-000001', 'payment_method' => 'cash', 'payment_mode' => 'cash', 'amount' => 4000, 'paid_at' => now(), 'paid_date' => today(), 'status' => 'POSTED', 'recorded_by' => $staff->id]);
        $payment->update(['proof_path' => 'payment-proofs/receipt.pdf', 'proof_mime' => 'application/pdf']);
        $case->recalculatePaymentTotals();
        $payment->update(['submission_id' => (string) \Illuminate\Support\Str::uuid(), 'submission_hash' => str_repeat('a', 64)]);

        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'changes' => ['amount'], 'reason_category' => 'Incorrect amount', 'evidence_reference' => 'Receipt and collection log verified', 'amount' => 4000,
            'reason' => 'The payment details require review.',
        ])->assertSessionHasErrors('correction_type');
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'changes' => ['amount'], 'reason_category' => 'Incorrect amount', 'evidence_reference' => 'Receipt and collection log verified', 'amount' => 12.345,
            'paid_date' => today()->addDay()->toDateString(), 'payment_method' => 'unsupported',
            'reason' => 'The payment details require review.',
        ])->assertSessionHasErrors(['amount', 'paid_date', 'payment_method']);
        $this->assertSame(0, PaymentCorrectionRequest::count());

        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), ['correction_type' => 'CORRECT', 'changes' => ['amount'], 'reason_category' => 'Incorrect amount', 'evidence_reference' => 'Receipt and collection log verified', 'changes' => ['amount', 'paid_date'], 'amount' => 3000, 'paid_date' => today()->subDay()->toDateString(), 'reason' => 'The encoded amount does not match the receipt.'])->assertSessionHasNoErrors();
        $correction = PaymentCorrectionRequest::firstOrFail();
        $this->assertSame('PENDING', $correction->status);
        $this->assertSame('PENDING', \App\Models\AuditLog::where('action', 'PAYMENT_CORRECTION_REQUESTED')->firstOrFail()->metadata['request_status']);
        $this->assertSame('POSTED', $payment->fresh()->status);

        $this->actingAs($admin)->get(route('admin.payments.index', ['tab' => 'corrections']))
            ->assertOk()->assertSee('PAY-2026-000001')->assertSee('Correct payment');

        $this->actingAs($admin)->post(route('admin.payment-corrections.approve', $correction), ['review_reason' => 'Checked the supporting receipt and collection log.', 'preview_token' => $correction->approvalPreviewToken($correction->payment->funeralCase()->firstOrFail(), $correction->payment->funeralCase()->firstOrFail())])->assertSessionHasNoErrors();
        $this->assertSame('VOIDED', $payment->fresh()->status);
        $this->assertSame('APPROVED', $correction->fresh()->status);
        $this->assertSame('3000.00', $correction->fresh()->replacementPayment->amount);
        $this->assertSame('3000.00', $case->fresh()->total_paid);
        $replacement = $correction->fresh()->replacementPayment;
        $this->assertNull($replacement->submission_id);
        $this->assertNull($replacement->submission_hash);
        $this->assertSame('7000.00', $replacement->balance_after_payment);
        $this->assertSame('PARTIAL', $replacement->payment_status_after_payment);
        $this->assertNotNull($payment->fresh()->submission_id);
        $this->assertSame(today()->subDay()->toDateString(), $replacement->paid_at->toDateString());
        $this->assertSame($payment->paid_at->format('H:i:s'), $replacement->paid_at->format('H:i:s'));

        $ledger = $this->actingAs($staff)->get(route('payments.history'))->assertOk()
            ->assertSee('1 valid · 1 voided')
            ->assertSeeInOrder([
                'data-payment-record="'.$replacement->display_payment_record_no.'"',
                'data-payment-record="'.$payment->display_payment_record_no.'"',
                'class="pm-payment-card__correction pm-payment-card__related"',
                'Replacement payment',
                '<strong>'.$replacement->display_payment_record_no.'</strong>',
            ], false);
        $voidPrintUrl = route('payments.summary', $payment);
        $ledger->assertSeeInOrder([
            'data-payment-record="'.$payment->display_payment_record_no.'"',
            'data-print-url="'.$voidPrintUrl.'"',
            'data-print-label="Print Void Record"',
        ], false)
            ->assertDontSee('data-pm-transaction-print href="'.$voidPrintUrl.'"', false)
            ->assertSee('data-pm-transaction-print href="'.route('payments.summary', $replacement).'"', false);
        $this->actingAs($staff)->get(route('payments.summary', $replacement))->assertOk()
            ->assertSee($replacement->display_payment_record_no)
            ->assertSee($replacement->paid_at->format('M d, Y h:i A'))
            ->assertSeeInOrder(['Amount recorded', 'PHP 3,000.00', 'Remaining balance', 'PHP 7,000.00'])
            ->assertDontSee('VOIDED · Historical record')
            ->assertDontSee('PHP 4,000.00');
        $this->get(route('payments.summary', $payment))->assertOk()
            ->assertSee('Print Void Record')
            ->assertSee('VOIDED · Historical record')
            ->assertSeeInOrder(['Amount recorded', 'PHP 4,000.00', 'Total paid', 'PHP 3,000.00', 'Remaining balance', 'PHP 7,000.00']);
        $this->get(route('payments.history.print', $case))->assertOk()
            ->assertSee($replacement->display_payment_record_no)
            ->assertSee('PHP 3,000.00')
            ->assertSee('PHP 7,000.00')
            ->assertDontSee($payment->display_payment_record_no)
            ->assertDontSee('PHP 4,000.00');
        $monitoringFilters = [
            'q' => $replacement->display_payment_record_no,
            'paid_from' => $replacement->paid_at->toDateString(),
            'paid_to' => $replacement->paid_at->toDateString(),
        ];
        $this->actingAs($admin)->get(route('admin.payments.index', $monitoringFilters))->assertOk()
            ->assertSee(route('payments.monitoring.print', $monitoringFilters));
        $this->get(route('payments.monitoring.print', $monitoringFilters))->assertOk()
            ->assertSee($case->case_code)
            ->assertSee($replacement->paid_at->format('M d, Y h:i A'))
            ->assertSee('PHP 3,000.00')
            ->assertSee('PHP 7,000.00')
            ->assertDontSee('PHP 4,000.00');
        $log = \App\Models\AuditLog::where('action', 'PAYMENT_CORRECTION_APPROVED')->firstOrFail();
        $this->assertSame($correction->reason, $log->metadata['reason']);
        $this->assertSame('PHP 4,000.00', $log->metadata['changes'][0]['before']);
        $this->assertSame('PHP 3,000.00', $log->metadata['changes'][0]['after']);
        $system = $this->admin('system');
        $this->actingAs($system)->get(route('admin.audit-logs.index', ['action' => 'PAYMENT_CORRECTION']))
            ->assertOk()
            ->assertSee('<div class="audit-secondary">Branch Admin</div>', false)
            ->assertSee('<div class="audit-secondary">Staff</div>', false)
            ->assertSeeInOrder(['Payment Correction Approved', 'status-badge status-badge-success', 'Approved'], false)
            ->assertSeeInOrder(['Payment Correction Requested', 'status-badge status-badge-warning', 'Pending'], false);
        $this->get(route('admin.audit-logs.show', $log))->assertOk()->assertJsonPath('metadata.reason', $correction->reason);
        $otherBranch = Branch::create(['branch_code' => 'BR099', 'branch_type' => 'branch', 'branch_name' => 'Other Branch', 'is_active' => true]);
        $otherAdmin = $this->admin('branch', $otherBranch);
        $this->actingAs($otherAdmin)->get(route('admin.audit-logs.index', ['action' => 'PAYMENT_CORRECTION']))
            ->assertOk()->assertDontSee('Payment Correction Approved');
        $this->get(route('admin.audit-logs.show', $log))->assertForbidden();

    }

    public function test_payment_history_searches_the_role_scoped_correction_queue_by_record_number(): void
    {
        $branch = $this->branch();
        [$staff, $otherStaff] = User::factory()->count(2)->create([
            'role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true,
        ])->all();
        $case = $this->case($branch);
        $fields = [
            'funeral_case_id' => $case->id, 'branch_id' => $branch->id, 'status' => 'POSTED',
            'amount' => 1000, 'payment_method' => 'cash', 'paid_at' => now(),
        ];
        $payment = Payment::create($fields + ['payment_record_no' => 'PAY-BR001-2026-000101']);
        $otherPayment = Payment::create($fields + ['payment_record_no' => 'PAY-BR001-2026-0001019']);
        $requestFields = [
            'branch_id' => $branch->id, 'correction_type' => 'CORRECT', 'reason' => 'Verify this payment.',
            'original_values' => ['amount' => 1000], 'proposed_values' => ['amount' => 900],
        ];
        $rejected = PaymentCorrectionRequest::create($requestFields + [
            'payment_id' => $payment->id, 'requested_by' => $staff->id, 'status' => 'REJECTED',
        ]);
        $pending = PaymentCorrectionRequest::create($requestFields + [
            'payment_id' => $payment->id, 'requested_by' => $otherStaff->id, 'status' => 'PENDING',
        ]);
        $otherRequest = PaymentCorrectionRequest::create($requestFields + [
            'payment_id' => $otherPayment->id, 'requested_by' => $staff->id, 'status' => 'PENDING',
        ]);

        $params = ['tab' => 'corrections', 'correction_q' => $payment->display_payment_record_no];
        foreach ([
            [$staff, 'payments.history', [$rejected->id], 'Rejected', 1],
            [$this->admin('branch', $branch), 'admin.payments.index', [$pending->id, $rejected->id], 'Pending', 2],
            [$this->admin('system'), 'admin.payments.index', [$pending->id, $rejected->id], 'Pending', 2],
        ] as [$viewer, $route, $ids, $status, $count]) {
            $url = route($route, $params);
            $this->actingAs($viewer)->get($url)->assertOk()
                ->assertSee('name="correction_q" value="'.$payment->display_payment_record_no.'"', false)
                ->assertDontSee('class="pm-filter-count"', false)
                ->assertViewHas('correctionRequests', fn ($requests) => $requests->pluck('id')->sort()->values()->all() === collect($ids)->sort()->values()->all())
                ->assertViewHas('pendingCorrectionCount', $count);
        }

        $this->actingAs($otherStaff)->get(route('payments.history'))->assertOk()
            ->assertDontSee('View correction requests for '.$otherPayment->display_payment_record_no);
        $this->get(route('payments.history', ['tab' => 'corrections', 'correction_q' => $otherPayment->display_payment_record_no]))
            ->assertOk()->assertViewHas('correctionRequests', fn ($requests) => $requests->isEmpty());

        $otherPayment->update(['payment_record_no' => null, 'receipt_number' => '123456']);
        $legacyUrl = route('payments.history', ['tab' => 'corrections', 'correction_q' => '#'.$otherRequest->id]);
        $this->actingAs($staff)->get($legacyUrl)->assertOk()
            ->assertViewHas('correctionRequests', fn ($requests) => $requests->pluck('id')->all() === [$otherRequest->id]);
    }

    public function test_system_admin_can_follow_up_on_overdue_corrections_across_branches_without_reviewing_them(): void
    {
        $this->freezeSecond();
        $main = $this->branch();
        $other = Branch::create(['branch_code' => 'BR099', 'branch_type' => 'branch', 'branch_name' => 'Other Branch', 'is_active' => true]);
        $makeRequest = function (Branch $branch, string $record) {
            $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
            $case = $this->case($branch, ['case_code' => 'CASE-'.$branch->branch_code]);
            $payment = Payment::create([
                'funeral_case_id' => $case->id, 'branch_id' => $branch->id, 'payment_record_no' => $record,
                'payment_method' => 'cash', 'payment_mode' => 'cash', 'amount' => 1000,
                'paid_at' => now(), 'paid_date' => today(), 'status' => 'POSTED', 'recorded_by' => $staff->id,
            ]);

            return PaymentCorrectionRequest::create([
                'payment_id' => $payment->id, 'branch_id' => $branch->id, 'requested_by' => $staff->id,
                'status' => 'PENDING', 'correction_type' => 'CORRECT', 'original_values' => ['amount' => '1000.00'],
                'proposed_values' => ['amount' => '500.00'], 'reason' => 'Verify against the original receipt.',
            ]);
        };
        $due = $makeRequest($other, 'PAY-2026-000099');
        $due->forceFill(['created_at' => now()->subDay()->addMinute()])->save();
        $fresh = $makeRequest($main, 'PAY-2026-000001');

        $systemAdmin = $this->admin('system');
        $branchAdmin = $this->admin('branch', $other);
        $this->actingAs($systemAdmin);
        $tab = ['tab' => 'corrections'];
        $this->get(route('admin.payments.index', $tab))->assertOk()
            ->assertDontSee('data-workflow-notices-url=', false)
            ->assertSee('data-sidebar-correction-count', false)->assertSee('data-current="true"', false)
            ->assertSee('2 pending correction requests')->assertSee('PAY-2026-000099')->assertSee('PAY-2026-000001');
        $this->get(route('admin.payments.index', $tab + ['correction_branch_id' => $other->id]))
            ->assertOk()->assertSee('PAY-2026-000099')->assertDontSee('PAY-2026-000001');
        $this->get(route('admin.payments.index', $tab + ['correction_urgency' => 'overdue']))
            ->assertOk()->assertViewHas('correctionRequests', fn ($requests) => $requests->isEmpty())
            ->assertViewHas('pendingCorrectionCount', 2);
        $this->getJson(route('payments.corrections.notices'))->assertOk()
            ->assertJsonPath('pending', 2)->assertJsonPath('latest_pending_id', $fresh->id)
            ->assertJsonPath('unread', 0)->assertJsonCount(0, 'items');
        $this->actingAs($due->requester)->get(route('payments.history', $tab))->assertOk()
            ->assertSee('1 pending correction requests')->assertSee('PAY-2026-000099')->assertDontSee('PAY-2026-000001');
        $this->get(route('payments.history', $tab + ['correction_type' => 'CORRECT', 'correction_period' => 'week', 'correction_branch_id' => $main->id]))
            ->assertOk()->assertViewHas('correctionRequests', fn ($requests) => $requests->pluck('id')->all() === [$due->id]);
        $this->get(route('payments.history', $tab + ['correction_type' => 'VOID_ONLY']))
            ->assertOk()->assertViewHas('correctionRequests', fn ($requests) => $requests->isEmpty());
        $this->actingAs($branchAdmin)->get(route('admin.payments.index', $tab))->assertOk()
            ->assertSee('1 pending correction requests')->assertSee('PAY-2026-000099')->assertDontSee('PAY-2026-000001');
        $this->get(route('admin.payments.index', $tab + ['correction_requester_id' => $due->requested_by]))
            ->assertOk()->assertViewHas('correctionRequests', fn ($requests) => $requests->pluck('id')->all() === [$due->id])
            ->assertViewHas('correctionRequesters', fn ($requesters) => $requesters->pluck('id')->all() === [$due->requested_by]);
        $this->get(route('admin.payments.index', $tab + ['correction_requester_id' => $fresh->requested_by]))
            ->assertOk()->assertViewHas('correctionRequests', fn ($requests) => $requests->isEmpty());
        $this->actingAs($systemAdmin);

        $this->artisan('payment-corrections:remind')->assertSuccessful();
        $this->assertDatabaseMissing('workflow_notices', ['event' => 'overdue_reminder']);
        $this->get(route('payments.corrections.show', $due))->assertOk()->assertSee('Remind Branch Admin');
        $this->postJson(route('admin.payment-corrections.remind', $fresh))->assertUnprocessable();
        $this->postJson(route('admin.payment-corrections.remind', $due))->assertOk()
            ->assertJsonPath('message', 'Reminder sent to the Branch Admin.');
        $this->postJson(route('admin.payment-corrections.remind', $due))->assertStatus(429);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PAYMENT_CORRECTION_REMINDER_SENT', 'actor_id' => $systemAdmin->id, 'entity_id' => $due->id]);
        $this->actingAs($branchAdmin)->getJson(route('payments.corrections.notices'))
            ->assertJsonCount(1, 'items')->assertJsonPath('items.0.event', 'reminded')
            ->assertJsonPath('items.0.url', route('payments.corrections.show', $due));
        $this->postJson(route('admin.payment-corrections.remind', $due))->assertForbidden();

        $this->travel(1)->minute();
        $this->artisan('payment-corrections:remind')->assertSuccessful();
        $this->artisan('payment-corrections:remind')->assertSuccessful();
        $this->assertDatabaseHas('workflow_notices', ['user_id' => $branchAdmin->id, 'event' => 'overdue_reminder']);
        $this->getJson(route('payments.corrections.notices'))
            ->assertJsonCount(2, 'items')->assertJsonPath('items.0.event', 'overdue_reminder');
        $this->actingAs($systemAdmin);
        $this->get(route('admin.payments.index', $tab + ['correction_urgency' => 'overdue']))
            ->assertOk()->assertViewHas('correctionRequests', fn ($requests) => $requests->pluck('id')->all() === [$due->id])
            ->assertViewHas('pendingCorrectionCount', 2);
        $destination = route('admin.payments.index', $tab + ['open_correction' => $due->id]);
        $this->get('/admin')->assertOk()->assertSee('Payment follow-ups')
            ->assertSee('Payment correction needs follow-up')->assertSee('1 unread')
            ->assertSee($destination)->assertSee('data-unread="true"', false);
        $notice = $this->getJson(route('payments.corrections.notices'))->assertOk()
            ->assertJsonPath('unread', 1)->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.event', 'overdue')->assertJsonPath('items.0.url', $destination)
            ->json('items.0.id');
        $this->getJson(route('payments.corrections.notices'))->assertJsonPath('items.0.id', $notice);
        $this->get($destination)->assertOk()->assertSee('data-open-correction-url=', false);
        $this->get(route('payments.corrections.show', $due))->assertOk()
            ->assertSee('Verify against the original receipt.')
            ->assertDontSee('data-workflow-reject=', false)->assertDontSee('id="pwDecisionForm"', false);
        $this->getJson(route('payments.corrections.notices'))->assertJsonPath('unread', 0)
            ->assertJsonPath('items.0.read', true);
        $this->post(route('admin.payment-corrections.approve', $due))->assertForbidden();
        $this->post(route('admin.payment-corrections.reject', $due))->assertForbidden();
        $this->post(route('payments.corrections.withdraw', $due))->assertForbidden();
        $this->travel(14)->minutes();
        $this->postJson(route('admin.payment-corrections.remind', $due))->assertOk();

        $this->actingAs($this->admin('branch', $main))->get(route('payments.corrections.show', $due))->assertForbidden();
        $this->getJson(route('payments.corrections.notices'))->assertJsonCount(0, 'items');
        $this->actingAs($branchAdmin)->post(route('admin.payment-corrections.reject', $due), [
            'reason' => 'Receipt does not match the proposed change.',
        ])->assertSessionHasNoErrors();
        $this->getJson(route('payments.corrections.notices'))->assertJsonCount(0, 'items');
        $this->actingAs($systemAdmin)->getJson(route('payments.corrections.notices'))
            ->assertJsonPath('pending', 1)->assertJsonCount(0, 'items');
        $this->postJson(route('admin.payment-corrections.remind', $due))->assertConflict();
    }

    public function test_payment_correction_history_links_follow_request_read_access(): void
    {
        $branch = $this->branch();
        $otherBranch = Branch::create(['branch_code' => 'BR098', 'branch_type' => 'branch', 'branch_name' => 'Other Branch', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $otherStaff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $case = $this->case($branch);
        $payment = Payment::create([
            'branch_id' => $branch->id, 'funeral_case_id' => $case->id, 'recorded_by' => $staff->id,
            'payment_record_no' => 'PAY-2026-000098', 'amount' => 1000,
            'paid_at' => now(), 'payment_method' => 'cash', 'status' => 'POSTED',
        ]);
        $details = [
            'payment_id' => $payment->id, 'branch_id' => $branch->id, 'correction_type' => 'CORRECT',
            'original_values' => ['amount' => '1000.00'], 'proposed_values' => ['amount' => '500.00'],
            'reason' => 'Verify the payment against the original receipt.',
        ];
        $current = PaymentCorrectionRequest::create($details + ['requested_by' => $staff->id, 'status' => 'PENDING']);
        $branchAdmin = $this->admin('branch', $branch);
        $previous = PaymentCorrectionRequest::create($details + [
            'requested_by' => $otherStaff->id, 'status' => 'REJECTED',
            'reviewed_by' => $branchAdmin->id, 'reviewed_at' => now(), 'review_reason' => 'Receipt proof was missing.',
        ]);
        $currentUrl = route('payments.corrections.show', $current);
        $previousUrl = route('payments.corrections.show', $previous);
        $previousLinkLabel = 'aria-label="View request #'.$previous->id.'"';

        foreach ([$branchAdmin, $this->admin('system')] as $admin) {
            $this->actingAs($admin)->get($currentUrl)->assertOk()
                ->assertSee('Viewing request #'.$current->id)->assertSee('Viewing now')
                ->assertDontSee('data-correction-history-open', false)->assertSee('<template data-correction-history-template>', false)
                ->assertSeeText('2 requests')->assertSeeText('Pending 1')->assertSeeText('Rejected 1')
                ->assertSeeText('Original payment PAY-2026-000098 · Case '.$case->case_code)
                ->assertSeeText('Reviewed by')->assertSeeText($branchAdmin->name)->assertSeeText('Receipt proof was missing.')
                ->assertSee($previousLinkLabel, false)->assertSee($previousUrl);
            $this->get($previousUrl)->assertOk()->assertSee('Viewing request #'.$previous->id)
                ->assertSeeText('Original payment PAY-2026-000098 · Case '.$case->case_code);
            $this->get(route('admin.payments.index'))->assertOk()
                ->assertSee('Correction Request History')->assertSee($previousUrl);
        }

        $this->actingAs($staff)->get($currentUrl)->assertOk()
            ->assertSeeText('1 request')->assertDontSeeText('Rejected 1')
            ->assertDontSee($previousLinkLabel, false)->assertDontSee($previousUrl);
        $this->get($previousUrl)->assertForbidden();
        $this->actingAs($this->admin('branch', $otherBranch))->get($currentUrl)->assertForbidden();
    }

    public function test_duplicate_void_preserves_audit_history_and_case_balance(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment-proofs/duplicate.pdf', 'Receipt fixture');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $admin = $this->admin('branch', $branch);
        $case = $this->case($branch, ['total_amount' => 50000, 'subtotal_amount' => 50000]);
        $records = [
            'funeral_case_id' => $case->id, 'branch_id' => $branch->id, 'amount' => 10000,
            'payment_method' => 'cash', 'status' => 'POSTED', 'recorded_by' => $staff->id,
            'proof_path' => 'payment-proofs/duplicate.pdf', 'proof_mime' => 'application/pdf',
        ];
        $older = Payment::create($records + ['payment_record_no' => 'PAY-2026-000201', 'paid_at' => now()->subDay()]);
        $newer = Payment::create($records + [
            'payment_record_no' => 'PAY-2026-000202', 'paid_at' => now(),
            'balance_after_payment' => 30000, 'payment_status_after_payment' => 'PARTIAL',
        ]);
        $wrongMethod = Payment::create(array_replace($records, [
            'payment_record_no' => 'PAY-2026-000203', 'payment_method' => 'cashless', 'cashless_type' => 'gcash',
            'paid_at' => now()->subDays(2),
        ]));
        $wrongMethod->update(['status' => 'VOIDED']);
        $case->recalculatePaymentTotals();
        $this->actingAs($staff)->get(route('payments.history'))->assertOk()
            ->assertSeeInOrder([
                'data-payment-record="'.$newer->display_payment_record_no.'"',
                'Previous Balance</span><strong><small>PHP</small>40,000.00',
                'Balance After Payment</span><strong><small>PHP</small>30,000.00',
                'data-payment-record="'.$older->display_payment_record_no.'"',
                'Previous Balance</span><strong><small>PHP</small>50,000.00',
                'Balance After Payment</span><strong><small>PHP</small>40,000.00',
                'data-payment-record="'.$wrongMethod->display_payment_record_no.'"',
            ], false);
        $this->actingAs($staff)->get(route('payments.corrections.create', $older))->assertOk()
            ->assertViewHas('retainedCandidates', fn ($payments) => $payments->modelKeys() === [$newer->id]);
        $this->get(route('payments.corrections.create', $newer))->assertOk()
            ->assertViewHas('retainedCandidates', fn ($payments) => $payments->modelKeys() === [$older->id]);

        $submit = route('payments.corrections.store', $older);
        $payload = ['correction_type' => 'VOID_ONLY', 'reason' => 'Both records refer to one actual payment.'];
        $this->postJson($submit, $payload + ['evidence_reference' => $older->display_payment_record_no])
            ->assertUnprocessable()->assertJsonValidationErrors('evidence_reference');
        $this->postJson($submit, $payload + ['evidence_reference' => $newer->display_payment_record_no])->assertOk();
        $request = PaymentCorrectionRequest::firstOrFail();
        $this->assertSame('30000.00', $case->fresh()->balance_amount);
        $this->postJson($submit, $payload + ['evidence_reference' => $newer->display_payment_record_no])->assertUnprocessable();
        $this->get(route('payments.history'))->assertOk()->assertSee('Pending Void Request')->assertSee('View Pending Request');
        $this->actingAs($admin)->get(route('payments.corrections.show', $request))->assertOk()
            ->assertSee('Payment to void')->assertSee('Payment to keep')->assertSee($newer->display_payment_record_no);

        $approve = route('admin.payment-corrections.approve', $request);
        $review = ['review_reason' => 'Matched the receipt and collection log.', 'duplicate_reconciled' => true];
        $newer->update(['status' => 'VOIDED']);
        $this->postJson($approve, $review + ['preview_token' => $request->approvalPreviewToken($case->fresh(), $case->fresh())])
            ->assertUnprocessable()->assertJsonValidationErrors('evidence_reference');
        $this->assertSame('POSTED', $older->fresh()->status);
        $newer->update(['status' => 'POSTED']);
        $this->postJson($approve, $review + ['preview_token' => $request->approvalPreviewToken($case->fresh(), $case->fresh())])->assertOk();
        $this->assertSame('40000.00', $case->fresh()->balance_amount);
        $this->assertSame('POSTED', $newer->fresh()->status);
        $this->assertSame('VOIDED', $older->fresh()->status);
        $this->assertNull($request->fresh()->replacement_payment_id);
        foreach (['PAYMENT_CORRECTION_REQUESTED', 'PAYMENT_CORRECTION_APPROVED'] as $action) {
            $metadata = \App\Models\AuditLog::where('action', $action)->firstOrFail()->metadata;
            $this->assertSame($older->display_payment_record_no, $metadata['void_payment_record_no']);
            $this->assertSame($newer->display_payment_record_no, $metadata['retained_payment_record_no']);
        }
        $this->get(route('admin.payments.index'))->assertOk()
            ->assertSee('1 valid · 2 voided')->assertSee('Voided · Duplicate')->assertSee('Payment kept');
        $this->get(route('funeral-cases.show', $case))->assertOk()
            ->assertSee('Voided · Duplicate')->assertSee('View Proof')->assertSee('Payment kept')->assertSee('Correction history')
            ->assertSee('Last Payment')->assertSee($newer->paid_at->format('M d, Y g:i A'));
        $this->get(route('payments.summary', $newer))->assertOk()
            ->assertSee($newer->display_payment_record_no)
            ->assertSeeInOrder(['Amount recorded', 'PHP 10,000.00', 'Total paid', 'PHP 10,000.00', 'Remaining balance', 'PHP 40,000.00'])
            ->assertDontSee('PHP 30,000.00');
        $this->get(route('payments.history.print', $case))->assertOk()
            ->assertSee($newer->display_payment_record_no)
            ->assertSee('PHP 10,000.00')
            ->assertSee('PHP 40,000.00')
            ->assertDontSee($older->display_payment_record_no);
        $this->get(route('payments.monitoring.print', ['q' => $case->case_code]))->assertOk()
            ->assertSee($case->case_code)
            ->assertSee($newer->paid_at->format('M d, Y h:i A'))
            ->assertSee('PHP 10,000.00')
            ->assertSee('PHP 40,000.00')
            ->assertDontSee('PHP 20,000.00');
        $this->actingAs($this->admin('system'))->get(route('admin.audit-logs.index', ['action' => 'PAYMENT_CORRECTION']))
            ->assertOk()->assertSee('Payment to Void')->assertSee('Payment to Keep')
            ->assertSee($older->display_payment_record_no)->assertSee($newer->display_payment_record_no);
    }

    public function test_case_details_show_saved_service_context_and_authorize_attachments(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('deceased-photos/portrait.jpg', 'Photo fixture');
        Storage::disk('public')->put('senior-proofs/id.pdf', 'Proof fixture');
        $branch = $this->branch();
        $other = Branch::create(['branch_code' => 'BR091', 'branch_type' => 'branch', 'branch_name' => 'Other Branch', 'is_active' => true]);
        $chapel = \App\Models\AddOnCatalog::create([
            'branch_id' => $branch->id, 'name' => 'Test Chapel', 'category' => \App\Models\AddOnCatalog::CHAPEL_VIEWING,
            'unit' => 'day', 'price' => 1000, 'chapel_address' => '123 Test Street', 'is_active' => true,
        ]);
        $case = $this->case($branch, [
            'viewing_type' => 'chapel', 'wake_location' => 'Test Chapel', 'chapel_address' => '123 Test Street',
            'chapel_catalog_id' => $chapel->id,
            'pricing_snapshot' => ['included_add_ons' => [['id' => $chapel->id, 'included_quantity' => 4, 'requested_quantity' => 5]]],
            'transport_notes' => 'Use the side entrance.', 'embalming_notes' => 'Preparation recorded.',
        ]);
        $case->deceased->update(['photo_path' => 'deceased-photos/portrait.jpg', 'senior_proof_path' => 'senior-proofs/id.pdf',
            'senior_citizen_status' => true, 'pwd_status' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $photo = route('funeral-cases.saved-attachment', [$case, 'deceased-photo']);
        $proof = route('funeral-cases.senior-proof', $case);
        $this->actingAs($staff)->get(route('funeral-cases.show', $case))->assertOk()
            ->assertSee('Service Requested')->assertSee('Chapel Viewing')->assertSee('123 Test Street')->assertSee('4 covered · 1 billable')
            ->assertSee('Use the side entrance.')->assertSee('Preparation recorded.')
            ->assertSee('Person with disability')->assertSee($photo)->assertSee($proof);
        $this->get($photo)->assertOk();
        $this->get($proof)->assertOk();
        $this->actingAs($this->admin('branch', $other))->get($proof)->assertForbidden();
    }

    public function test_legal_hold_overrides_pending_disposal_status(): void
    {
        $system = $this->admin('system');
        $case = $this->case($this->branch(), ['case_status' => 'COMPLETED', 'completed_at' => now()->subYears(6), 'retention_end_date' => today()->subYear(), 'retention_status' => 'pending_disposal_review']);

        $this->actingAs($system)->post(route('admin.retention.place-hold', $case), ['reason' => 'The case is subject to an unresolved legal review.'])->assertSessionHasNoErrors();
        $this->assertSame('legal_hold', $case->fresh()->retention_status);
        $this->assertNotNull($case->fresh()->legal_hold_at);
    }

    public function test_manual_backup_failure_returns_a_safe_user_facing_error(): void
    {
        $system = $this->admin('system');
        $this->mock(SystemBackupService::class)
            ->shouldReceive('create')
            ->once()
            ->andThrow(new RuntimeException('Technical database dump failure.'));

        $response = $this->actingAs($system)->post(route('admin.backups.store'), [
            'password' => 'password',
        ]);

        $response->assertRedirect()->assertSessionHasErrors('backup');
        $this->assertDatabaseCount('system_backups', 0);
    }

    public function test_correction_approval_cannot_overpay_and_rolls_back_original_and_audit(): void
    {
        $branch = $this->branch();
        $case = $this->case($branch);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $payment = Payment::create(['funeral_case_id' => $case->id, 'branch_id' => $branch->id,
            'amount' => 4000, 'payment_method' => 'cash', 'paid_at' => now(), 'status' => 'POSTED', 'recorded_by' => $staff->id]);
        $this->attachOriginalProof($payment);
        $case->recalculatePaymentTotals();
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'changes' => ['amount'], 'reason_category' => 'Incorrect amount', 'evidence_reference' => 'Receipt and collection log verified', 'amount' => 11000, 'reason' => 'Review the receipt amount carefully.',
        ])->assertSessionHasNoErrors();
        $correction = PaymentCorrectionRequest::firstOrFail();
        $this->actingAs($this->admin('branch', $branch))
            ->post(route('admin.payment-corrections.approve', $correction), ['review_reason' => 'Checked the supporting receipt and collection log.', 'preview_token' => $correction->approvalPreviewToken($correction->payment->funeralCase()->firstOrFail(), $correction->payment->funeralCase()->firstOrFail())])->assertStatus(422);
        $this->assertSame('POSTED', $payment->fresh()->status);
        $this->assertSame('PENDING', $correction->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('4000.00', $case->fresh()->total_paid);
        $this->assertSame(0, \App\Models\AuditLog::where('action', 'PAYMENT_CORRECTION_APPROVED')->count());
    }

    public function test_repeated_correction_request_and_approval_preserve_one_replacement(): void
    {
        $branch = $this->branch();
        $case = $this->case($branch);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $payment = Payment::create(['funeral_case_id' => $case->id, 'branch_id' => $branch->id,
            'amount' => 4000, 'payment_method' => 'cash', 'paid_at' => now(), 'status' => 'POSTED', 'recorded_by' => $staff->id]);
        $this->attachOriginalProof($payment);
        $case->recalculatePaymentTotals();
        $payload = ['correction_type' => 'CORRECT', 'changes' => ['amount'], 'reason_category' => 'Incorrect amount', 'evidence_reference' => 'Receipt and collection log verified', 'amount' => 3000, 'reason' => 'Correct the receipt amount carefully.'];
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), $payload)->assertSessionHasNoErrors();
        $this->post(route('payments.corrections.store', $payment), $payload)->assertStatus(422);
        $this->assertDatabaseCount('payment_correction_requests', 1);
        $correction = PaymentCorrectionRequest::firstOrFail();
        $this->actingAs($this->admin('branch', $branch))->post(route('admin.payment-corrections.approve', $correction), ['review_reason' => 'Checked the supporting receipt and collection log.', 'preview_token' => $correction->approvalPreviewToken($correction->payment->funeralCase()->firstOrFail(), $correction->payment->funeralCase()->firstOrFail())])->assertSessionHasNoErrors();
        $this->post(route('admin.payment-corrections.approve', $correction), ['review_reason' => 'Checked the supporting receipt and collection log.', 'preview_token' => $correction->approvalPreviewToken($correction->payment->funeralCase()->firstOrFail(), $correction->payment->funeralCase()->firstOrFail())])->assertStatus(409);
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame('VOIDED', $payment->fresh()->status);
        $this->assertSame('3000.00', $case->fresh()->total_paid);
        $this->assertSame(1, \App\Models\AuditLog::where('action', 'PAYMENT_CORRECTION_APPROVED')->count());
    }

    public function test_administrative_other_branch_report_cannot_be_changed_through_corrections(): void
    {
        $branch = $this->branch();
        $case = $this->case($branch, ['entry_source' => 'OTHER_BRANCH']);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $payment = Payment::create(['funeral_case_id' => $case->id, 'branch_id' => $branch->id,
            'amount' => 10000, 'payment_method' => 'cash', 'paid_at' => now(), 'status' => 'POSTED', 'recorded_by' => $staff->id]);
        $case->recalculatePaymentTotals();
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'changes' => ['amount'], 'reason_category' => 'Incorrect amount', 'evidence_reference' => 'Receipt and collection log verified', 'amount' => 9000, 'reason' => 'Attempt to modify an administrative report.',
        ])->assertStatus(422);
        $this->assertDatabaseCount('payment_correction_requests', 0);
        // Even a historical pending request must not provide a report-payment write bypass.
        $correction = PaymentCorrectionRequest::create(['payment_id' => $payment->id, 'branch_id' => $branch->id,
            'requested_by' => $staff->id, 'correction_type' => 'CORRECT', 'changes' => ['amount'], 'reason_category' => 'Incorrect amount', 'evidence_reference' => 'Receipt and collection log verified', 'reason' => 'Legacy pending request.',
            'original_values' => ['amount' => 10000], 'proposed_values' => ['amount' => 9000]]);
        $this->actingAs($this->admin('branch', $branch))->post(route('admin.payment-corrections.approve', $correction), ['review_reason' => 'Checked the supporting receipt and collection log.', 'preview_token' => $correction->approvalPreviewToken($correction->payment->funeralCase()->firstOrFail(), $correction->payment->funeralCase()->firstOrFail())])->assertStatus(422);
        $this->assertSame('POSTED', $payment->fresh()->status);
        $this->assertSame('PENDING', $correction->fresh()->status);
        $this->assertSame('10000.00', $case->fresh()->total_paid);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_activated_branch_payment_correction_cannot_be_approved_on_central(): void
    {
        $branch = $this->branch();
        $case = $this->case($branch);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $admin = $this->admin('branch', $branch);
        $payment = Payment::create(['funeral_case_id' => $case->id, 'branch_id' => $branch->id,
            'payment_method' => 'cash', 'amount' => 4000, 'status' => 'POSTED', 'paid_at' => now(), 'recorded_by' => $staff->id]);
        $this->attachOriginalProof($payment);
        $case->recalculatePaymentTotals();
        $installation = (string) \Illuminate\Support\Str::uuid();
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => $branch->branch_code]);
        \App\Services\BranchSync\SyncState::put('installation', $installation);
        \App\Services\BranchSync\SyncState::put('references_at', now()->toIso8601String());
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'changes' => ['amount'], 'reason_category' => 'Incorrect amount', 'evidence_reference' => 'Receipt and collection log verified', 'amount' => 3000, 'paid_date' => today()->toDateString(), 'reason' => 'Correct the received amount.',
        ])->assertSessionHasNoErrors();
        $correction = PaymentCorrectionRequest::firstOrFail();
        \Illuminate\Support\Facades\DB::table('branch_sync_peers')->insert([
            'branch_code' => $branch->branch_code, 'token_hash' => hash('sha256', 'test-only'),
            'installation_id' => $installation, 'activation_state' => 'active',
        ]);
        config(['branch_sync.mode' => 'central']);
        $this->actingAs($admin)->post(route('admin.payment-corrections.approve', $correction), ['review_reason' => 'Checked the supporting receipt and collection log.', 'preview_token' => $correction->approvalPreviewToken($correction->payment->funeralCase()->firstOrFail(), $correction->payment->funeralCase()->firstOrFail())])->assertStatus(409);
        $this->assertSame('PENDING', $correction->fresh()->status);
        $this->assertSame('POSTED', $payment->fresh()->status);
        config(['branch_sync.mode' => 'branch']);
        $this->post(route('admin.payment-corrections.approve', $correction), ['review_reason' => 'Checked the supporting receipt and collection log.', 'preview_token' => $correction->approvalPreviewToken($correction->payment->funeralCase()->firstOrFail(), $correction->payment->funeralCase()->firstOrFail())])->assertSessionHasNoErrors();
        $this->assertSame('APPROVED', $correction->fresh()->status);
        $this->assertSame('3000.00', $case->fresh()->total_paid);
        $this->assertTrue(\App\Services\BranchSync\SyncState::get('dirty'));
    }

    public function test_wrong_backup_password_reopens_the_dialog_with_an_inline_error(): void
    {
        $system = $this->admin('system');

        $response = $this->actingAs($system)
            ->from(route('admin.backups.index'))
            ->post(route('admin.backups.store'), ['password' => 'wrong-password']);

        $response->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('open_backup_dialog', true)
            ->assertSessionHasErrorsIn('createBackup', 'password');
        $this->assertDatabaseCount('system_backups', 0);
    }

    public function test_expired_backup_with_an_active_restore_request_is_not_deleted(): void
    {
        Storage::fake('local');
        $system = $this->admin('system');
        $backup = SystemBackup::create([
            'created_by' => $system->id,
            'type' => 'manual',
            'status' => 'verified',
            'disk' => 'local',
            'path' => 'system-backups/protected.backup',
            'size_bytes' => 8,
            'checksum' => str_repeat('a', 64),
            'verified_at' => now()->subDay(),
            'expires_at' => now()->subMinute(),
        ]);
        Storage::disk('local')->put($backup->path, 'protected');
        RestoreRequest::create([
            'system_backup_id' => $backup->id,
            'requested_by' => $system->id,
            'status' => 'validated_pending_execution',
            'reason' => 'Restore required for integrity verification.',
            'validated_at' => now(),
        ]);

        $deleted = app(SystemBackupService::class)->deleteExpired();

        $this->assertSame(0, $deleted);
        Storage::disk('local')->assertExists($backup->path);
        $this->assertSame('verified', $backup->fresh()->status);
    }

    public function test_second_backup_is_rejected_while_another_backup_is_running(): void
    {
        $lock = Cache::lock('system-backup:create', 900);
        $this->assertTrue($lock->get());

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('already being created');
            app(SystemBackupService::class)->create('manual');
        } finally {
            $lock->release();
        }
    }

    private function attachOriginalProof(Payment $payment): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment-proofs/receipt.pdf', 'Receipt fixture');
        $payment->update(['proof_path' => 'payment-proofs/receipt.pdf', 'proof_mime' => 'application/pdf']);
    }

    private function branch(): Branch
    {
        return Branch::firstOrCreate(['branch_code' => 'BR001'], ['branch_type' => 'main', 'branch_name' => 'Main Branch', 'is_active' => true]);
    }

    private function admin(string $scope, ?Branch $branch = null): User
    {
        return User::factory()->create(['role' => 'admin', 'admin_scope' => $scope, 'branch_id' => $scope === 'branch' ? $branch?->id : null, 'is_active' => true]);
    }

    private function case(Branch $branch, array $overrides = []): FuneralCase
    {
        $client = Client::create(['branch_id' => $branch->id, 'full_name' => 'Test Client', 'relationship_to_deceased' => 'Spouse', 'contact_number' => '09170000000', 'address' => 'Test Address']);
        $deceased = Deceased::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'full_name' => 'Test Deceased', 'date_of_death' => today()->subDay(), 'age' => 70]);
        $package = Package::create(['name' => 'Test Package', 'coffin_type' => 'Standard', 'price' => 10000, 'is_active' => true]);
        return FuneralCase::create(array_merge(['branch_id' => $branch->id, 'client_id' => $client->id, 'deceased_id' => $deceased->id, 'package_id' => $package->id, 'case_number' => 1, 'case_code' => 'CASE-001', 'service_type' => 'Burial', 'service_requested_at' => today(), 'service_package' => 'Test Package', 'coffin_type' => 'Standard', 'wake_location' => 'Test Wake', 'funeral_service_at' => today()->addDay(), 'subtotal_amount' => 10000, 'discount_type' => 'NONE', 'discount_value_type' => 'AMOUNT', 'discount_value' => 0, 'discount_amount' => 0, 'tax_rate' => 0, 'tax_amount' => 0, 'total_amount' => 10000, 'total_paid' => 0, 'balance_amount' => 10000, 'payment_status' => 'UNPAID', 'case_status' => 'ACTIVE', 'entry_source' => 'MAIN', 'verification_status' => 'VERIFIED'], $overrides));
    }
}
