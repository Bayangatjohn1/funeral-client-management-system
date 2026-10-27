<?php

namespace Tests\Browser;

use App\Models\{Branch, FuneralCase, Package, Payment, User};
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use PragmaRX\Google2FA\Google2FA;
use Tests\DuskTestCase;

class IntakeTimeoutRecoveryTest extends DuskTestCase
{
    public function test_retry_recovers_a_committed_initial_payment_without_a_success_page(): void
    {
        $branch = Branch::where('branch_code', 'BR001')->firstOrFail();
        $package = Package::where('is_active', true)->firstOrFail();
        $secret = (new Google2FA)->generateSecretKey();
        $user = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true,
            'email' => 'intake-timeout@funeral.test', 'password' => 'TimeoutRecovery123!', 'must_change_password' => false]);
        $user->two_factor_secret = $secret; $user->two_factor_confirmed_at = now(); $user->save();
        $identity = (string) Str::uuid();
        $payload = [
            'branch_id' => $branch->id, 'submission_id' => $identity, 'service_requested_at' => today()->toDateString(),
            'client_name' => 'Timeout Representative', 'client_relationship' => 'Daughter', 'client_contact_number' => '09170009988',
            'client_email' => 'timeout@example.com', 'client_valid_id_type' => 'National ID', 'client_valid_id_number' => 'QA-TIMEOUT',
            'client_address' => 'Timeout QA Address', 'deceased_name' => 'Timeout Deceased', 'deceased_address' => 'Timeout QA Address',
            'born' => today()->subYears(65)->toDateString(), 'died' => today()->subDay()->toDateString(), 'civil_status' => 'WIDOWED',
            'wake_location' => 'QA Chapel', 'wake_start_date' => today()->toDateString(), 'wake_start_time' => '08:00',
            'wake_end_date' => today()->addDay()->toDateString(), 'wake_end_time' => '07:00',
            'funeral_service_at' => today()->addDay()->toDateString(), 'funeral_service_time' => '08:00',
            'interment_at' => today()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i:s'), 'interment_time' => '09:00',
            'place_of_cemetery' => 'QA Cemetery', 'case_status' => 'ACTIVE', 'transport_option' => 'HEARSE',
            'coffin_length_cm' => 175, 'coffin_size' => 'LARGE', 'embalming_required' => 1, 'embalming_status' => 'PENDING',
            'package_id' => $package->id, 'additional_service_amount' => 0, 'senior_citizen_status' => 0, 'pwd_status' => 0,
            'confirm_review' => 1, 'mark_as_paid' => 1, 'payment_type' => 'PARTIAL', 'payment_method' => 'cash',
            'paid_at' => now()->format('Y-m-d H:i:s'), 'amount_paid' => 5000,
        ];
        $casesBefore = FuneralCase::count(); $paymentsBefore = Payment::count();
        $this->browse(function (Browser $browser) use ($secret, $payload, $identity, $casesBefore, $paymentsBefore) {
            $browser->visit('/login')->type('email', 'intake-timeout@funeral.test')->type('password', 'TimeoutRecovery123!')
                ->press('Sign in')->waitForLocation('/security/challenge', 10)
                ->type('code', (new Google2FA)->getCurrentOtp($secret))->press('Verify and continue')->waitForLocation('/staff', 10);
            // Submit a real request but intentionally never show/process its success page.
            $browser->script('window.edgePayload = '.json_encode($payload).'; window.edgePayload._token=document.querySelector("meta[name=csrf-token]").content; fetch("/intake/main", {method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams(window.edgePayload)}).then(()=>{window.edgeIgnoredResponse=true})');
            $browser->waitUntil('window.edgeIgnoredResponse === true', 15)->assertPathIs('/staff');
            $payment = Payment::where('submission_id', $identity)->firstOrFail();
            $case = FuneralCase::findOrFail($payment->funeral_case_id);
            $this->assertSame($casesBefore + 1, FuneralCase::count());
            $this->assertSame($paymentsBefore + 1, Payment::count());
            $browser->script('fetch("/intake/main", {method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams(window.edgePayload)}).then(r=>{window.edgeRecoveredUrl=r.url})');
            $browser->waitUntil('!!window.edgeRecoveredUrl', 15);
            $url = $browser->script('return window.edgeRecoveredUrl')[0];
            $this->assertStringContainsString('/funeral-cases/'.$case->id, $url);
            $browser->visit($url)->assertSee($case->case_code)->assertSee('Payment Summary')->assertSee('Transactions')->assertDontSeeLink('Record Payment');
            $this->assertSame($casesBefore + 1, FuneralCase::count());
            $this->assertSame($paymentsBefore + 1, Payment::count());
            $this->assertSame(5000.0, (float) $case->fresh()->total_paid);
            $browser->logout();
        });
    }
}
