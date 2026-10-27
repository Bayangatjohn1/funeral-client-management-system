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
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $admin = $this->admin('branch', $branch);
        $case = $this->case($branch);
        $payment = Payment::create(['funeral_case_id' => $case->id, 'branch_id' => $branch->id, 'payment_record_no' => 'PAY-2026-000001', 'payment_method' => 'cash', 'payment_mode' => 'cash', 'amount' => 4000, 'paid_at' => now(), 'paid_date' => today(), 'status' => 'POSTED', 'recorded_by' => $staff->id]);
        $case->recalculatePaymentTotals();
        $payment->update(['submission_id' => (string) \Illuminate\Support\Str::uuid(), 'submission_hash' => str_repeat('a', 64)]);

        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'amount' => 4000,
            'reason' => 'The payment details require review.',
        ])->assertSessionHasErrors('correction_type');
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'amount' => 12.345,
            'paid_date' => today()->addDay()->toDateString(), 'payment_method' => 'unsupported',
            'reason' => 'The payment details require review.',
        ])->assertSessionHasErrors(['amount', 'paid_date', 'payment_method']);
        $this->assertSame(0, PaymentCorrectionRequest::count());

        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), ['correction_type' => 'CORRECT', 'amount' => 3000, 'paid_date' => today()->subDay()->toDateString(), 'reason' => 'The encoded amount does not match the receipt.'])->assertSessionHasNoErrors();
        $correction = PaymentCorrectionRequest::firstOrFail();
        $this->assertSame('PENDING', $correction->status);
        $this->assertSame('POSTED', $payment->fresh()->status);

        $this->actingAs($admin)->get(route('admin.payments.index', ['tab' => 'corrections']))
            ->assertOk()->assertSee('Original payment')->assertSee('Requested correction');

        $this->actingAs($admin)->post(route('admin.payment-corrections.approve', $correction))->assertSessionHasNoErrors();
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
        $log = \App\Models\AuditLog::where('action', 'PAYMENT_CORRECTION_APPROVED')->firstOrFail();
        $this->assertSame($correction->reason, $log->metadata['reason']);
        $this->assertSame('PHP 4,000.00', $log->metadata['changes'][0]['before']);
        $this->assertSame('PHP 3,000.00', $log->metadata['changes'][0]['after']);
        $system = $this->admin('system');
        $this->actingAs($system)->get(route('admin.audit-logs.index', ['action' => 'PAYMENT_CORRECTION']))
            ->assertOk()->assertSee('Payment Correction Approved');
        $this->get(route('admin.audit-logs.show', $log))->assertOk()->assertJsonPath('metadata.reason', $correction->reason);
        $otherBranch = Branch::create(['branch_code' => 'BR099', 'branch_type' => 'branch', 'branch_name' => 'Other Branch', 'is_active' => true]);
        $otherAdmin = $this->admin('branch', $otherBranch);
        $this->actingAs($otherAdmin)->get(route('admin.audit-logs.index', ['action' => 'PAYMENT_CORRECTION']))
            ->assertOk()->assertDontSee('Payment Correction Approved');
        $this->get(route('admin.audit-logs.show', $log))->assertForbidden();

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
        $case->recalculatePaymentTotals();
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'amount' => 11000, 'reason' => 'Review the receipt amount carefully.',
        ])->assertSessionHasNoErrors();
        $correction = PaymentCorrectionRequest::firstOrFail();
        $this->actingAs($this->admin('branch', $branch))
            ->post(route('admin.payment-corrections.approve', $correction))->assertStatus(422);
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
        $case->recalculatePaymentTotals();
        $payload = ['correction_type' => 'CORRECT', 'amount' => 3000, 'reason' => 'Correct the receipt amount carefully.'];
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), $payload)->assertSessionHasNoErrors();
        $this->post(route('payments.corrections.store', $payment), $payload)->assertStatus(422);
        $this->assertDatabaseCount('payment_correction_requests', 1);
        $correction = PaymentCorrectionRequest::firstOrFail();
        $this->actingAs($this->admin('branch', $branch))->post(route('admin.payment-corrections.approve', $correction))->assertSessionHasNoErrors();
        $this->post(route('admin.payment-corrections.approve', $correction))->assertStatus(409);
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
            'correction_type' => 'CORRECT', 'amount' => 9000, 'reason' => 'Attempt to modify an administrative report.',
        ])->assertStatus(422);
        $this->assertDatabaseCount('payment_correction_requests', 0);
        // Even a historical pending request must not provide a report-payment write bypass.
        $correction = PaymentCorrectionRequest::create(['payment_id' => $payment->id, 'branch_id' => $branch->id,
            'requested_by' => $staff->id, 'correction_type' => 'CORRECT', 'reason' => 'Legacy pending request.',
            'original_values' => ['amount' => 10000], 'proposed_values' => ['amount' => 9000]]);
        $this->actingAs($this->admin('branch', $branch))->post(route('admin.payment-corrections.approve', $correction))->assertStatus(422);
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
        $case->recalculatePaymentTotals();
        $installation = (string) \Illuminate\Support\Str::uuid();
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => $branch->branch_code]);
        \App\Services\BranchSync\SyncState::put('installation', $installation);
        \App\Services\BranchSync\SyncState::put('references_at', now()->toIso8601String());
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'amount' => 3000, 'paid_date' => today()->toDateString(), 'reason' => 'Correct the received amount.',
        ])->assertSessionHasNoErrors();
        $correction = PaymentCorrectionRequest::firstOrFail();
        \Illuminate\Support\Facades\DB::table('branch_sync_peers')->insert([
            'branch_code' => $branch->branch_code, 'token_hash' => hash('sha256', 'test-only'),
            'installation_id' => $installation, 'activation_state' => 'active',
        ]);
        config(['branch_sync.mode' => 'central']);
        $this->actingAs($admin)->post(route('admin.payment-corrections.approve', $correction))->assertStatus(409);
        $this->assertSame('PENDING', $correction->fresh()->status);
        $this->assertSame('POSTED', $payment->fresh()->status);
        config(['branch_sync.mode' => 'branch']);
        $this->post(route('admin.payment-corrections.approve', $correction))->assertSessionHasNoErrors();
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
