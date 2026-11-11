<?php

namespace Tests\Feature;

use App\Models\AddOnCatalog;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Models\PaymentCorrectionRequest;
use App\Models\User;
use App\Support\ChapelSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChapelCorrectionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function branch(string $code = 'BR001'): Branch
    {
        return Branch::create(['branch_code' => $code, 'branch_name' => 'Branch '.$code, 'branch_type' => $code === 'BR001' ? 'main' : 'branch', 'is_active' => true]);
    }

    private function staff(Branch $branch): User
    {
        return User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
    }

    private function admin(Branch $branch): User
    {
        return User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true]);
    }

    private function case(Branch $branch, array $extra = []): FuneralCase
    {
        $client = Client::create(['branch_id' => $branch->id, 'full_name' => 'Maria Santos', 'relationship_to_deceased' => 'Spouse', 'contact_number' => '09170000000', 'address' => 'Quezon City']);
        $deceased = Deceased::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'full_name' => 'Juan Santos', 'date_of_death' => today()->subDay(), 'age' => 70]);

        return FuneralCase::create(array_merge(['branch_id' => $branch->id, 'client_id' => $client->id, 'deceased_id' => $deceased->id, 'case_number' => FuneralCase::count() + 1, 'case_code' => 'FC'.str_pad(FuneralCase::count() + 1, 4, '0', STR_PAD_LEFT), 'service_type' => 'Burial', 'service_requested_at' => today(), 'service_package' => 'Standard', 'coffin_type' => 'Standard', 'wake_location' => 'Main Chapel', 'funeral_service_at' => today()->addDay(), 'subtotal_amount' => 10000, 'total_amount' => 10000, 'total_paid' => 0, 'balance_amount' => 10000, 'payment_status' => 'UNPAID', 'case_status' => 'ACTIVE', 'entry_source' => 'MAIN', 'verification_status' => 'VERIFIED'], $extra));
    }

    private function payment(Branch $branch, User $staff): Payment
    {
        $case = $this->case($branch);
        $payment = Payment::create(['funeral_case_id' => $case->id, 'branch_id' => $branch->id, 'amount' => 4000, 'paid_at' => now(), 'paid_date' => today(), 'payment_method' => 'cash', 'payment_mode' => 'cash', 'status' => 'POSTED', 'recorded_by' => $staff->id, 'payment_record_no' => 'PAY-2026-'.str_pad(Payment::count() + 1, 6, '0', STR_PAD_LEFT)]);
        Storage::disk('local')->put('payment-proofs/receipt.pdf', 'Receipt fixture');
        $payment->update(['proof_path' => 'payment-proofs/receipt.pdf', 'proof_mime' => 'application/pdf']);
        $case->recalculatePaymentTotals();

        return $payment;
    }

    private function proposal(array $extra = []): array
    {
        return array_replace(['correction_type' => 'CORRECT', 'changes' => ['amount'], 'amount' => 3000, 'reason_category' => 'Incorrect amount', 'reason' => 'Receipt shows three thousand, not four thousand.', 'evidence_reference' => 'Cash collection log and OR-1234'], $extra);
    }

    public function test_receipt_correction_uses_original_source_and_admin_approval_without_manual_category(): void
    {
        $branch = $this->branch();
        $staff = $this->staff($branch);
        $admin = $this->admin($branch);
        $payment = $this->payment($branch, $staff);
        Storage::disk('local')->put('payment-proofs/original.png', 'Original receipt fixture');
        $payment->update(['receipt_or_no' => 'OR-WRONG-001', 'accounting_reference_no' => 'OR-WRONG-001', 'proof_path' => 'payment-proofs/original.png', 'proof_mime' => 'image/png']);
        $other = $this->payment($branch, $staff);
        $other->update(['receipt_or_no' => 'OR-TAKEN-001']);
        $proposal = ['correction_type' => 'CORRECT', 'changes' => ['receipt_or_no'], 'receipt_or_no' => 'OR-CORRECT-001',
            'reason' => 'The original photo shows the correct receipt number.'];
        $this->actingAs($staff)->get(route('payments.corrections.create', $payment))->assertOk()
            ->assertDontSee('name="reason_category"', false)->assertSee('Add Additional Evidence');
        $this->postJson(route('payments.corrections.store', $payment), array_replace($proposal, ['receipt_or_no' => 'OR-TAKEN-001']))
            ->assertUnprocessable()->assertJsonValidationErrors('receipt_or_no');
        $this->postJson(route('payments.corrections.store', $payment), $proposal)->assertOk();
        $correction = PaymentCorrectionRequest::firstOrFail();
        $this->assertSame('Incorrect receipt reference', $correction->reason_category);
        $this->assertSame('OR-WRONG-001', $correction->evidence_reference);
        $this->assertSame('OR-WRONG-001', $payment->fresh()->receipt_or_no);
        $this->assertSame('OR-WRONG-001', $correction->original_values['receipt_or_no']);
        $this->actingAs($admin)->get(route('payments.corrections.show', $correction))->assertOk()
            ->assertSee('OR-WRONG-001')->assertSee('OR-CORRECT-001');
        $case = $payment->funeralCase->fresh();
        $approval = ['review_reason' => 'Verified receipt against the collection log.', 'preview_token' => $correction->approvalPreviewToken($case, $case)];
        $other->update(['receipt_or_no' => 'OR-CORRECT-001']);
        $this->postJson(route('admin.payment-corrections.approve', $correction), $approval)->assertUnprocessable()->assertJsonValidationErrors('receipt_or_no');
        $other->update(['receipt_or_no' => 'OR-TAKEN-001']);
        $this->post(route('admin.payment-corrections.approve', $correction), $approval)->assertSessionHasNoErrors();
        $replacement = $correction->fresh()->replacementPayment;
        $this->assertSame('OR-CORRECT-001', $replacement->receipt_or_no);
        $this->assertSame('OR-CORRECT-001', $replacement->accounting_reference_no);
        $this->assertSame($payment->proof_path, $replacement->proof_path);
        $this->assertSame('OR-WRONG-001', $payment->fresh()->receipt_or_no);
        $this->assertSame('6000.00', $case->fresh()->balance_amount);
    }

    public function test_branch_admin_can_compare_original_and_destination_payment_history_with_branch_isolation(): void
    {
        $branch = $this->branch();
        $staff = $this->staff($branch);
        $admin = $this->admin($branch);
        $payment = $this->payment($branch, $staff);
        $destination = $this->payment($branch, $staff);
        $destination->update(['receipt_or_no' => 'OR-DESTINATION-001']);
        $voided = $payment->replicate();
        $voided->fill(['payment_record_no' => 'PAY-HISTORY-VOID', 'status' => 'VOIDED', 'receipt_or_no' => 'OR-VOID-001']);
        $voided->save();
        $foreignBranch = $this->branch('BR002');
        $foreign = $this->payment($foreignBranch, $this->staff($foreignBranch));
        $foreign->update(['receipt_or_no' => 'OR-FOREIGN-SECRET']);
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), $this->proposal([
            'changes' => ['funeral_case_id'], 'funeral_case_id' => $destination->funeral_case_id,
        ]))->assertSessionHasNoErrors();
        $correction = PaymentCorrectionRequest::firstOrFail();
        $this->actingAs($admin)->get(route('payments.corrections.show', $correction))->assertOk()
            ->assertSee('View Payment History')->assertSee('Requested transaction')->assertSee('Proposed destination case')
            ->assertSee('PAY-HISTORY-VOID')->assertSee('Voided')->assertSee('OR-DESTINATION-001')
            ->assertDontSee('OR-FOREIGN-SECRET')
            ->assertViewHas('historyCases', fn ($cases) => $cases->count() === 2
                && $cases->first()->id === $payment->funeral_case_id
                && $cases->every(fn ($case) => (int) $case->branch_id === (int) $branch->id));
        $this->actingAs($staff)->get(route('payments.corrections.show', $correction))->assertOk()
            ->assertViewHas('historyCases', fn ($cases) => $cases->isEmpty());
    }

    public function test_chapel_configuration_is_branch_unique_and_unmapped_records_are_not_ready(): void
    {
        $a = $this->branch();
        $b = $this->branch('BR002');
        $system = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'is_active' => true]);
        $input = ['name' => 'Main Chapel', 'category' => 'Chapel Viewing', 'unit' => 'day', 'price' => 2000, 'branch_id' => $a->id, 'chapel_address' => '24 Mabini St., Quezon City'];
        $this->actingAs($system)->postJson(route('admin.add-on-catalogs.store'), $input)->assertCreated();
        $this->postJson(route('admin.add-on-catalogs.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson(route('admin.add-on-catalogs.store'), array_replace($input, ['branch_id' => $b->id]))->assertCreated();
        $this->postJson(route('admin.add-on-catalogs.store'), array_replace($input, ['name' => 'West Chapel', 'branch_id' => null]))->assertUnprocessable()->assertJsonValidationErrors('branch_id');
        $legacy = AddOnCatalog::create(['name' => 'Unmapped chapel', 'category' => 'Chapel Viewing', 'unit' => 'day', 'price' => 2000, 'is_active' => true, 'is_available' => true]);
        $this->assertFalse($legacy->chapel_ready);
        $this->assertSame(2, AddOnCatalog::whereNotNull('branch_id')->count());
    }

    public function test_chapel_advisory_uses_physical_identity_full_interval_and_branch_scope(): void
    {
        $a = $this->branch();
        $b = $this->branch('BR002');
        $staff = $this->staff($a);
        $chapel = AddOnCatalog::create(['name' => 'Main Chapel', 'category' => 'Chapel Viewing', 'branch_id' => $a->id, 'chapel_address' => 'Quezon City', 'price' => 2000, 'unit' => 'day', 'is_active' => true, 'is_available' => true]);
        $free = AddOnCatalog::create(['name' => 'Available Chapel', 'category' => 'Chapel Viewing', 'branch_id' => $a->id, 'chapel_address' => 'Quezon City', 'price' => 2200, 'unit' => 'day', 'is_active' => true, 'is_available' => true]);
        $busy = AddOnCatalog::create(['name' => 'Busy Chapel', 'category' => 'Chapel Viewing', 'branch_id' => $a->id, 'chapel_address' => 'Quezon City', 'price' => 1800, 'unit' => 'day', 'is_active' => true, 'is_available' => true]);
        $other = AddOnCatalog::create(['name' => 'Main Chapel', 'category' => 'Chapel Viewing', 'branch_id' => $b->id, 'chapel_address' => 'Manila', 'price' => 2000, 'unit' => 'day', 'is_active' => true, 'is_available' => true]);
        $this->case($a, ['chapel_catalog_id' => $chapel->id, 'wake_start_date' => '2026-11-14', 'wake_start_time' => '14:00', 'wake_end_date' => '2026-11-17', 'wake_end_time' => '10:00']);
        $this->case($a, ['chapel_catalog_id' => $chapel->id, 'case_status' => 'DRAFT', 'wake_start_date' => '2026-11-14', 'wake_start_time' => '14:00', 'wake_end_date' => '2026-11-17', 'wake_end_time' => '10:00']);
        $this->case($a, ['chapel_catalog_id' => $busy->id, 'wake_start_date' => '2026-11-14', 'wake_start_time' => '14:00', 'wake_end_date' => '2026-11-17', 'wake_end_time' => '10:00']);
        $url = route('intake.chapel-advisory');
        $response = $this->actingAs($staff)->getJson($url.'?'.http_build_query(['chapel_id' => $chapel->id, 'start' => '2026-11-16 08:00', 'end' => '2026-11-18 10:00']))->assertOk()->assertJsonCount(1, 'events');
        $this->assertNotEmpty($response->json('signature'));
        $response->assertJsonPath('available_chapel_ids', [$free->id]);
        $period = ['start' => '2026-11-16 08:00', 'end' => '2026-11-18 10:00'];
        $this->getJson($url.'?'.http_build_query(['branch_id' => $a->id] + $period))
            ->assertOk()->assertJsonPath('available_chapel_ids', [$free->id]);
        $this->getJson($url.'?'.http_build_query(['branch_id' => $b->id] + $period))->assertForbidden();
        $this->getJson($url.'?'.http_build_query(['chapel_id' => $chapel->id, 'start' => '2026-11-17 10:00', 'end' => '2026-11-18 10:00']))->assertOk()->assertJsonCount(0, 'events');
        $this->getJson($url.'?'.http_build_query(['chapel_id' => $other->id, 'start' => '2026-11-16 08:00', 'end' => '2026-11-18 10:00']))->assertForbidden();
        $this->assertCount(1, app(ChapelSchedule::class)->context($chapel, '2026-11-16 08:00', '2026-11-18 10:00')['events']);
    }

    public function test_request_approval_notices_private_evidence_and_financial_totals(): void
    {
        $branch = $this->branch();
        $staff = $this->staff($branch);
        $admin = $this->admin($branch);
        $payment = $this->payment($branch, $staff);
        $this->actingAs($staff)->get(route('payments.corrections.create', $payment))->assertOk()->assertSee('data-correction-form', false);
        $this->post(route('payments.corrections.store', $payment), $this->proposal(['evidence' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')]))->assertSessionHasNoErrors();
        $request = PaymentCorrectionRequest::firstOrFail();
        foreach (['#'.$request->id, $payment->display_payment_record_no, $payment->funeralCase->case_code] as $query) {
            $this->get(route('payments.history', ['tab' => 'corrections', 'correction_q' => $query]))
                ->assertOk()->assertViewHas('correctionRequests', fn ($requests) => $requests->total() === 1);
        }
        $this->get(route('payments.history', ['tab' => 'corrections', 'correction_q' => 'missing-record']))
            ->assertOk()->assertViewHas('correctionRequests', fn ($requests) => $requests->total() === 0);
        $this->assertSame('4000.00', $payment->fresh()->amount);
        $this->assertSame('6000.00', $payment->funeralCase->fresh()->balance_amount);
        $this->actingAs($admin)->getJson(route('payments.corrections.notices'))->assertOk()->assertJsonPath('unread', 1)->assertJsonPath('pending', 1);
        $this->get(route('admin.payments.index', ['tab' => 'corrections', 'correction_q' => $payment->display_payment_record_no, 'correction_status' => 'PENDING']))
            ->assertOk()->assertSee('Search: '.$payment->display_payment_record_no)
            ->assertSee('Request status: Pending')->assertSee('Clear filters')
            ->assertSeeInOrder(['</section>', '<footer class="pm-foot" data-correction-pagination>'], false);
        $this->get(route('payments.corrections.show', $request))->assertOk()->assertSee('Financial impact if approved');
        $this->getJson(route('payments.corrections.notices'))->assertJsonPath('unread', 0)->assertJsonPath('pending', 1);
        $this->get(route('payments.corrections.evidence', $request))->assertOk();
        $case = $payment->funeralCase()->firstOrFail();
        $oldPreview = $request->approvalPreviewToken($case, $case);
        $case->update(['total_amount' => 11000, 'balance_amount' => 7000]);
        $this->postJson(route('admin.payment-corrections.approve', $request), [
            'review_reason' => 'Verified against receipt OR-1234.',
            'preview_token' => $oldPreview,
        ])->assertUnprocessable()->assertJsonValidationErrors('preview_token');
        $this->assertSame('POSTED', $payment->fresh()->status);
        $this->assertSame('PENDING', $request->fresh()->status);
        $this->post(route('admin.payment-corrections.approve', $request), ['review_reason' => 'Verified against receipt OR-1234.', 'preview_token' => $request->approvalPreviewToken($case, $case)])->assertSessionHasNoErrors();
        $this->assertSame('VOIDED', $payment->fresh()->status);
        $this->assertSame('8000.00', $payment->funeralCase->fresh()->balance_amount);
        $this->assertDatabaseCount('payments', 2);
        $this->post(route('admin.payment-corrections.approve', $request), ['review_reason' => 'Verified against receipt OR-1234.', 'preview_token' => $request->approvalPreviewToken($request->payment->funeralCase()->firstOrFail(), $request->payment->funeralCase()->firstOrFail())])->assertStatus(409);
        $this->actingAs($staff)->getJson(route('payments.corrections.notices'))->assertOk()->assertJsonPath('unread', 1)->assertJsonPath('items.0.title', 'Payment correction approved');
        $stranger = $this->staff($this->branch('BR002'));
        $this->actingAs($stranger)->get(route('payments.corrections.show', $request))->assertForbidden();
        $this->get(route('payments.corrections.evidence', $request))->assertForbidden();
    }

    public function test_withdrawal_rejection_and_staff_ownership_leave_payments_unchanged(): void
    {
        $branch = $this->branch();
        $staff = $this->staff($branch);
        $other = $this->staff($branch);
        $admin = $this->admin($branch);
        $payment = $this->payment($branch, $staff);
        $this->actingAs($other)->post(route('payments.corrections.store', $payment), $this->proposal())->assertForbidden();
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), $this->proposal())->assertSessionHasNoErrors();
        $request = PaymentCorrectionRequest::firstOrFail();
        $this->post(route('payments.corrections.withdraw', $request))->assertSessionHasNoErrors();
        $this->assertSame('WITHDRAWN', $request->fresh()->status);
        $this->actingAs($admin)->post(route('admin.payment-corrections.approve', $request), ['review_reason' => 'Checked collection log.', 'preview_token' => $request->approvalPreviewToken($request->payment->funeralCase()->firstOrFail(), $request->payment->funeralCase()->firstOrFail())])->assertStatus(409);
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), $this->proposal())->assertSessionHasNoErrors();
        $request = PaymentCorrectionRequest::latest('id')->first();
        $this->actingAs($admin)->post(route('admin.payment-corrections.reject', $request), ['reason' => 'Please provide the matching receipt.'])->assertSessionHasNoErrors();
        $this->assertSame('REJECTED', $request->fresh()->status);
        $this->assertSame('POSTED', $payment->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_void_has_no_replacement_and_cashless_correction_reuses_payment_validation(): void
    {
        $branch = $this->branch();
        $staff = $this->staff($branch);
        $admin = $this->admin($branch);
        $payment = $this->payment($branch, $staff);
        $this->actingAs($staff)->postJson(route('payments.corrections.store', $payment), $this->proposal(['changes' => ['payment_details'], 'payment_method' => 'cashless', 'cashless_type' => 'gcash', 'reference_number' => '12']))->assertUnprocessable()->assertJsonValidationErrors('reference_number');
        $void = $this->proposal(['correction_type' => 'VOID_ONLY', 'changes' => [], 'reason_category' => 'Duplicate entry', 'evidence_reference' => 'PAY-2026-000002']);
        $this->postJson(route('payments.corrections.store', $payment), $void)->assertUnprocessable()->assertJsonValidationErrors('evidence_reference');
        $this->assertSame('POSTED', $payment->fresh()->status);
        $retained = $payment->replicate();
        $retained->payment_record_no = $void['evidence_reference'];
        $retained->save();
        $case = $payment->funeralCase;
        $case->recalculatePaymentTotals();
        $this->post(route('payments.corrections.store', $payment), $void)->assertSessionHasNoErrors();
        $request = PaymentCorrectionRequest::firstOrFail();
        $approval = ['review_reason' => 'Verified duplicate entry against the receipt.', 'preview_token' => $request->approvalPreviewToken($case, $case)];
        $this->actingAs($admin)->postJson(route('admin.payment-corrections.approve', $request), $approval)
            ->assertUnprocessable()->assertJsonValidationErrors('duplicate_reconciled');
        $this->post(route('admin.payment-corrections.approve', $request), $approval + ['duplicate_reconciled' => 1])->assertSessionHasNoErrors();
        $this->assertNull($request->fresh()->replacement_payment_id);
        $this->assertSame('6000.00', $case->fresh()->balance_amount);
        $this->assertSame('POSTED', $retained->fresh()->status);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_cash_method_correction_clears_cashless_data_and_stale_requests_cannot_apply(): void
    {
        $branch = $this->branch();
        $staff = $this->staff($branch);
        $admin = $this->admin($branch);
        $payment = $this->payment($branch, $staff);
        $payment->update(['payment_method' => 'cashless', 'cashless_type' => 'gcash', 'reference_number' => 'GCASH-1234', 'wallet_provider' => 'GCash', 'account_name' => 'Maria Santos', 'mobile_number' => '09170000000']);
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), $this->proposal(['changes' => ['payment_details'], 'payment_method' => 'cash']))->assertSessionHasNoErrors();
        $request = PaymentCorrectionRequest::firstOrFail();
        $this->actingAs($admin)->post(route('admin.payment-corrections.approve', $request), ['review_reason' => 'Confirmed cash collection log.', 'preview_token' => $request->approvalPreviewToken($request->payment->funeralCase()->firstOrFail(), $request->payment->funeralCase()->firstOrFail())])->assertSessionHasNoErrors();
        $replacement = $request->fresh()->replacementPayment;
        $this->assertSame('cash', $replacement->payment_method);
        $this->assertNull($replacement->reference_number);
        $this->assertNull($replacement->cashless_type);
        $this->assertNull($replacement->account_name);
        $this->actingAs($staff)->post(route('payments.corrections.store', $replacement), $this->proposal())->assertSessionHasNoErrors();
        $stale = PaymentCorrectionRequest::latest('id')->first();
        $replacement->update(['payment_notes' => 'Changed during reconciliation']);
        $this->actingAs($admin)->postJson(route('admin.payment-corrections.approve',$stale),['review_reason' => 'Review stale request.'])->assertUnprocessable()->assertJsonValidationErrors('request');
        $this->assertSame('POSTED',$replacement->fresh()->status);
        $this->assertSame('PENDING',$stale->fresh()->status);
        $this->assertDatabaseCount('payments',2);
    }
}
