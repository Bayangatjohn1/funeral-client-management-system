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

        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), ['correction_type' => 'CORRECT', 'amount' => 3000, 'paid_date' => today()->toDateString(), 'reason' => 'The encoded amount does not match the receipt.'])->assertSessionHasNoErrors();
        $correction = PaymentCorrectionRequest::firstOrFail();
        $this->assertSame('PENDING', $correction->status);
        $this->assertSame('POSTED', $payment->fresh()->status);

        $this->actingAs($admin)->post(route('admin.payment-corrections.approve', $correction))->assertSessionHasNoErrors();
        $this->assertSame('VOIDED', $payment->fresh()->status);
        $this->assertSame('APPROVED', $correction->fresh()->status);
        $this->assertSame('3000.00', $correction->fresh()->replacementPayment->amount);
        $this->assertSame('3000.00', $case->fresh()->total_paid);
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
