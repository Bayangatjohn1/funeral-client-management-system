<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Models\PaymentProofUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentProofUploadTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $code = 'BR001'): User
    {
        $branch = Branch::firstOrCreate(['branch_code' => $code], ['branch_name' => $code, 'branch_type' => 'main', 'is_active' => true]);
        return User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
    }

    private function upload(User $user, string $context = 'payment'): PaymentProofUpload
    {
        $response = $this->actingAs($user)->postJson(route('payment-proof.store'), ['form_id' => (string) Str::uuid(), 'context' => $context]);
        $response->assertOk()->assertJsonStructure(['id', 'qr', 'expires_at']);
        return PaymentProofUpload::findOrFail($response->json('id'));
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    private function payload(User $user, ?PaymentProofUpload $upload = null): array
    {
        $client = Client::create(['branch_id' => $user->branch_id, 'full_name' => 'Maria Santos', 'relationship_to_deceased' => 'Spouse', 'contact_number' => '09170000000', 'address' => 'Quezon City']);
        $deceased = Deceased::create(['branch_id' => $user->branch_id, 'client_id' => $client->id, 'full_name' => 'Juan Santos', 'date_of_death' => today()->subDay(), 'age' => 70]);
        $case = FuneralCase::create(['branch_id' => $user->branch_id, 'client_id' => $client->id, 'deceased_id' => $deceased->id, 'case_number' => FuneralCase::count() + 1, 'case_code' => 'FC'.(FuneralCase::count() + 1), 'service_type' => 'Burial', 'service_requested_at' => today(), 'service_package' => 'Standard', 'coffin_type' => 'Standard', 'wake_location' => 'Home', 'funeral_service_at' => today()->addDay(), 'subtotal_amount' => 10000, 'total_amount' => 10000, 'total_paid' => 0, 'balance_amount' => 10000, 'payment_status' => 'UNPAID', 'case_status' => 'ACTIVE', 'entry_source' => 'MAIN']);
        return ['funeral_case_id' => $case->id, 'submission_id' => (string) Str::uuid(), 'amount_paid' => 1000,
            'paid_at' => now()->format('Y-m-d H:i:s'), 'payment_method' => 'cash', 'receipt_or_no' => 'OR-'.$case->id,
            'payment_proof_id' => $upload?->id, 'payment_proof_form_id' => $upload?->form_id];
    }

    public function test_phone_can_upload_without_login_but_cannot_reuse_expired_cancelled_or_used_links(): void
    {
        Storage::fake('local');
        $user = $this->staff();
        $upload = $this->upload($user);
        $upload->update(['token_hash' => hash('sha256', 'test-token')]);
        $url = URL::temporarySignedRoute('payment-proof.phone', now()->addMinutes(5), ['upload' => $upload->id, 'token' => 'test-token']);
        Auth::logout();
        $this->get($url)->assertOk()->assertSee('Take Photo')->assertSee('Send to Form');
        $this->postJson($url, ['proof' => UploadedFile::fake()->createWithContent('receipt.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg=='))])->assertOk();
        $upload->refresh();
        Storage::disk('local')->assertExists($upload->path);
        $this->postJson($url, ['proof' => $this->pdf()])->assertGone();
        $this->get($url)->assertGone();
        $this->actingAs($user)->getJson(route('payment-proof.status', $upload))->assertOk()->assertJsonPath('state', 'received');
        $this->get(route('payment-proof.preview', $upload))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->deleteJson(route('payment-proof.cancel', $upload))->assertOk();
        $this->get(route('payment-proof.preview', $upload))->assertGone();

        $expired = $this->upload($user);
        $expired->update(['token_hash' => hash('sha256', 'token'), 'expires_at' => now()->subSecond()]);
        $expiredUrl = URL::temporarySignedRoute('payment-proof.phone', now()->addMinutes(5), ['upload' => $expired->id, 'token' => 'token']);
        Auth::logout();
        $this->get($expiredUrl)->assertGone();
        $this->get($url.'&changed=1')->assertForbidden();
    }

    public function test_wrong_user_and_invalid_files_cannot_access_or_supply_proof(): void
    {
        Storage::fake('local');
        $user = $this->staff();
        $upload = $this->upload($user);
        $this->postJson(route('payment-proof.upload', $upload), ['proof' => 'not a file'])->assertUnprocessable()->assertJsonValidationErrors('proof');
        $this->postJson(route('payment-proof.upload', $upload), ['proof' => UploadedFile::fake()->createWithContent('malicious.png', '<script>bad()</script>')])
            ->assertUnprocessable()->assertJsonValidationErrors('proof');
        $this->postJson(route('payment-proof.upload', $upload), ['proof' => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf')])
            ->assertUnprocessable()->assertJsonValidationErrors('proof');
        $other = $this->staff('BR002');
        $this->actingAs($other)->getJson(route('payment-proof.status', $upload))->assertForbidden();
        $this->postJson(route('payment-proof.upload', $upload), ['proof' => $this->pdf()])->assertForbidden();
        $this->deleteJson(route('payment-proof.cancel', $upload))->assertForbidden();
    }

    public function test_payment_requires_its_own_proof_preserves_it_on_retry_and_rejects_reuse(): void
    {
        Storage::fake('local');
        $user = $this->staff();
        $upload = $this->upload($user);
        $this->postJson(route('payment-proof.upload', $upload), ['proof' => $this->pdf()])->assertOk();
        $payload = $this->payload($user, $upload);
        $this->postJson('/payments/pay', array_replace($payload, ['payment_proof_id' => null]))->assertUnprocessable()->assertJsonValidationErrors('payment_proof_id');
        $this->post('/payments/pay', array_replace($payload, ['payment_proof_form_id' => (string) Str::uuid()]))->assertSessionHasErrors('payment_proof_id');
        $this->assertDatabaseCount('payments', 0);
        $this->post('/payments/pay', $payload)->assertSessionHasNoErrors();
        $payment = Payment::firstOrFail();
        $this->assertSame($upload->fresh()->path, $payment->proof_path);
        $this->assertNotNull($upload->fresh()->claimed_at);
        $this->post('/payments/pay', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 1);
        $payload['submission_id'] = (string) Str::uuid();
        $payload['receipt_or_no'] = 'OR-NEW';
        $this->post('/payments/pay', $payload)->assertSessionHasErrors('payment_proof_id');
        $this->assertDatabaseCount('payments', 1);
        $this->deleteJson(route('payment-proof.cancel', $upload))->assertConflict();
        $this->get(route('payment-proof.payment', $payment))->assertOk();
        $this->get(route('payments.corrections.create', $payment))->assertOk()->assertSee('View Original Payment Proof');
        $this->get(route('payments.history'))->assertOk()->assertSee('View Proof');
        $this->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'changes' => ['amount'], 'amount' => 500,
            'reason_category' => 'Incorrect amount', 'reason' => 'The receipt records five hundred received.',
            'evidence_reference' => 'Receipt '.$payment->receipt_or_no,
        ])->assertSessionHasNoErrors();
        $correction = \App\Models\PaymentCorrectionRequest::firstOrFail();
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $user->branch_id, 'is_active' => true]);
        $case = $payment->funeralCase->fresh();
        $this->actingAs($admin)->post(route('admin.payment-corrections.approve', $correction), [
            'review_reason' => 'Checked the receipt and collection log.', 'preview_token' => $correction->approvalPreviewToken($case, $case),
        ])->assertSessionHasNoErrors();
        $replacement = $correction->fresh()->replacementPayment;
        $this->assertSame($payment->proof_path, $replacement->proof_path);
        $this->get(route('payment-proof.payment', $replacement))->assertOk();
        $other = $this->staff('BR002');
        $this->actingAs($other)->get(route('payment-proof.payment', $payment))->assertForbidden();
        $this->artisan('payment-proofs:cleanup')->assertSuccessful();
        Storage::disk('local')->assertExists($payment->proof_path);
    }

    public function test_cleanup_deletes_abandoned_files_and_never_deletes_claimed_proofs(): void
    {
        Storage::fake('local');
        $upload = $this->upload($this->staff());
        $this->postJson(route('payment-proof.upload', $upload), ['proof' => $this->pdf()])->assertOk();
        $path = $upload->fresh()->path;
        $upload->update(['retain_until' => now()->subSecond()]);
        $this->artisan('payment-proofs:cleanup')->assertSuccessful();
        Storage::disk('local')->assertMissing($path);
        $this->assertModelMissing($upload);
    }
}
