<?php

namespace Tests\Browser;

use App\Models\FuneralCase;
use App\Models\Payment;
use App\Models\User;
use Laravel\Dusk\Browser;
use PragmaRX\Google2FA\Google2FA;
use Tests\DuskTestCase;

class RegressionWorkflowsTest extends DuskTestCase
{
    public function test_case_payment_reminder_and_read_only_role_workflows(): void
    {
        $seed = User::where('email', 'staff@funeral.test')->firstOrFail();
        $case = FuneralCase::where('branch_id', $seed->branch_id)->firstOrFail()->replicate();
        $deceased = $case->deceased->replicate();
        $deceased->save();
        $case->forceFill([
            'deceased_id' => $deceased->id, 'case_code' => 'REGRESSION-QA', 'case_number' => 90001, 'case_status' => 'ACTIVE',
            'total_amount' => 1000, 'total_paid' => 0, 'balance_amount' => 1000, 'payment_status' => 'UNPAID',
            'entry_source' => 'MAIN', 'wake_start_date' => today()->addDay(), 'wake_start_time' => '07:00',
            'wake_end_date' => today()->addDay(), 'wake_end_time' => '08:00',
            'funeral_service_at' => today()->addDay(), 'funeral_service_time' => '09:00',
            'interment_at' => today()->addDay()->setTime(10, 0), 'interment_time' => '10:00',
        ])->save();

        foreach (['staff', 'branch_admin', 'system_admin', 'owner'] as $role) {
            $secret = (new Google2FA)->generateSecretKey();
            $user = User::factory()->create([
                'email' => 'regression-'.$role.'@funeral.test', 'password' => 'RegressionPassword123!',
                'role' => str_contains($role, 'admin') ? 'admin' : $role,
                'admin_scope' => $role === 'system_admin' ? 'system' : ($role === 'branch_admin' ? 'branch' : null),
                'branch_id' => $role === 'system_admin' ? null : $seed->branch_id,
                'is_active' => true, 'must_change_password' => false,
            ]);
            $user->two_factor_secret = $secret;
            $user->two_factor_confirmed_at = now();
            $user->save();
            $this->browse(function (Browser $browser) use ($user, $role, $secret, $case) {
                $browser->visit('/login')->type('email', $user->email)->type('password', 'RegressionPassword123!')->press('Sign in');
                if ($user->requiresMfa()) {
                    $browser->waitForLocation('/security/challenge', 10)
                        ->type('code', (new Google2FA)->getCurrentOtp($secret))->press('Verify and continue');
                }
                $browser->waitForLocation($role === 'owner' ? '/owner' : ($role === 'staff' ? '/staff' : '/admin'), 10);
                if ($role === 'system_admin') {
                    $browser->assertSee('Needs Attention')->assertSee('Branch Coverage')->assertSee('Users & Access');
                } elseif ($role === 'branch_admin') {
                    $browser->visit('/payments/history?branch_id=999999&q=REGRESSION-QA')->assertSee('REGRESSION-QA');
                } else {
                    $browser->visit($role === 'owner' ? '/owner/cases/'.$case->id : '/funeral-cases/'.$case->id);
                }
                foreach (['light', 'dark'] as $theme) {
                    foreach ([1440, 390] as $width) {
                        $this->viewport($browser, $theme, $width);
                        $this->assertFalse($browser->script('return document.documentElement.scrollWidth > innerWidth + 1')[0]);
                        $browser->assertDontSeeLink('Record Payment');
                        $browser->screenshot('regression-'.$role.'-'.$theme.'-'.$width);
                    }
                }
                if ($role === 'staff') {
                    $this->viewport($browser, 'light', 1440);
                    $browser->visit('/payments/history')
                        ->press('Record Payment')->waitFor('#paymentFormModal:not(.hidden)', 10)
                        ->select('funeral_case_id', (string) $case->id)
                        ->assertSelected('funeral_case_id', (string) $case->id);
                    foreach (['light', 'dark'] as $theme) {
                        foreach ([1440, 390] as $width) {
                            $this->viewport($browser, $theme, $width);
                            $browser->assertVisible('#paymentFormModal')->assertSee('Case & Payment Amount');
                            $browser->screenshot('regression-payment-'.$theme.'-'.$width);
                        }
                    }
                    $this->viewport($browser, 'light', 1440);
                    $browser->type('amount_paid', '500')->press('Continue to Payment Method')
                        ->waitFor('[data-payment-method-option="cash"]')->click('[data-payment-method-option="cash"]')
                        ->press('Continue to Review')->waitFor('#pf_submit_btn');
                    $browser->script('sessionStorage.setItem("regressionRetry", new URLSearchParams(new FormData(document.querySelector("#paymentForm"))).toString())');
                    $browser->press('Confirm Payment')->waitForText('Payment recorded successfully', 10);
                    $this->assertSame(1, Payment::where('funeral_case_id', $case->id)->count());
                    $browser->script('fetch("/payments/pay", {method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body:sessionStorage.getItem("regressionRetry")}).then(r=>{if(!r.ok)throw new Error("Retry failed");return r.text()}).then(()=>{sessionStorage.removeItem("regressionRetry");window.regressionRetryDone=true})');
                    $browser->waitUntil('window.regressionRetryDone === true', 10);
                    $this->assertSame(1, Payment::where('funeral_case_id', $case->id)->count());
                    $this->assertSame(500.0, (float) $case->fresh()->balance_amount);
                    $browser->visit('/reminders?tab=all&focus_case='.$case->id)->waitFor('.calendar-event.is-focused')
                        ->assertSee('REGRESSION-QA')->click('.calendar-event.is-focused')->waitFor('dialog[open]');
                    $browser->screenshot('regression-reminder-focused');
                }
                $browser->logout();
            });
        }
    }

    private function viewport(Browser $browser, string $theme, int $width): void
    {
        $browser->script('document.documentElement.dataset.theme='.json_encode($theme));
        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
            'cmd' => 'Emulation.setDeviceMetricsOverride',
            'params' => ['width' => $width, 'height' => 900, 'deviceScaleFactor' => 1, 'mobile' => false],
        ]);
        $browser->pause(200);
    }
}
