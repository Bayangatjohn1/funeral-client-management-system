<?php

namespace Tests\Browser;

use App\Models\IntakeDraft;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class IntakePaymentWorkflowTest extends DuskTestCase
{
    public function test_autosave_restore_preserves_distinct_address_and_schedule_values(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $fields = [
            'client_address' => '12 Client Street, Manila', 'deceased_address' => '34 Deceased Street, Quezon City',
            'born' => '1980-01-01', 'died' => now()->subDay()->toDateString(),
            'wake_start_date' => now()->addDay()->toDateString(), 'wake_start_time' => '08:30',
            'wake_end_date' => now()->addDays(2)->toDateString(), 'wake_end_time' => '07:00',
            'funeral_service_at' => now()->addDays(2)->toDateString(), 'funeral_service_time' => '09:00',
            'interment_at' => now()->addDays(3)->toDateString(), 'interment_time' => '10:30',
            'wake_location' => 'Family Residence', 'place_of_cemetery' => 'Public Cemetery',
        ];
        $this->browse(function (Browser $browser) use ($staff, $fields) {
            $browser->loginAs($staff)->visit('/intake/main')->waitFor('#wizardNext');
            $browser->script('localStorage.clear();const field=document.querySelector("[name=client_first_name]");field.value="Maria";field.dispatchEvent(new Event("input",{bubbles:true}))');
            $browser->pause(500);
            $browser->script('const key=Object.keys(localStorage).find(k=>k.endsWith("new"));const draft=JSON.parse(localStorage.getItem(key));Object.assign(draft.fields,'.json_encode($fields).');localStorage.setItem(key,JSON.stringify(draft))');
            $browser->refresh()->waitFor('#restore_intake_autosave')->click('#restore_intake_autosave');
            foreach ($fields as $name => $value) $browser->assertInputValue($name, $value);
            $browser->assertInputValue('[data-time-display-for="wake_start_time"]', '08:30 AM');
            $browser->refresh()->waitFor('#restore_intake_autosave')->click('#restore_intake_autosave');
            $browser->assertInputValue('deceased_address', $fields['deceased_address'])->assertInputValue('interment_at', $fields['interment_at']);
        });
    }

    public function test_continue_shows_all_current_step_errors_and_updates_them_when_corrected(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $this->browse(function (Browser $browser) use ($staff) {
            $browser->loginAs($staff)->visit('/intake/main')->waitFor('#wizardNext');
            $browser->script('localStorage.clear()');
            $browser->refresh()->waitFor('#wizardNext')->assertMissing('[data-validation-summary]');
            $browser->type('client_contact_number', '123')->click('[name="client_first_name"]');
            $browser->assertVisible('#client_contact_number_inline_error')->assertMissing('[data-validation-summary]');
            $browser->click('#wizardNext')->waitFor('[data-validation-summary]');
            foreach (['client_first_name', 'client_last_name', 'client_relationship', 'client_contact_number', 'client_address'] as $field) {
                $browser->assertVisible('#'.$field.'_inline_error');
            }
            $this->assertGreaterThanOrEqual(5, $browser->script('return document.querySelectorAll("[data-validation-summary] li").length')[0]);
            $browser->script('document.querySelector("[data-validation-summary]").scrollIntoView({block:"center"})');
            $browser->screenshot('intake-validation-summary-light');
            $this->assertSame('rgb(255, 241, 242)', $browser->script('return getComputedStyle(document.querySelector("[data-validation-summary]")).backgroundColor')[0]);
            $browser->script('document.documentElement.dataset.theme="dark"');
            $browser->pause(350)->screenshot('intake-validation-summary-dark');
            $this->assertSame('rgb(41, 25, 31)', $browser->script('return getComputedStyle(document.querySelector("[data-validation-summary]")).backgroundColor')[0]);
            $browser->script('document.documentElement.dataset.theme="light"');
            $browser->click('[data-validation-summary] button');
            $this->assertSame('client_first_name', $browser->script('return document.activeElement.name')[0]);
            $this->assertTrue($browser->script('return document.activeElement.classList.contains("validation-highlight")')[0]);
            $browser->type('client_first_name', 'Maria')->pause(400);
            $this->assertTrue($browser->script('return document.querySelector("#client_first_name_inline_error").classList.contains("hidden")')[0]);
            $browser->assertVisible('#client_last_name_inline_error');
            $browser->refresh()->waitFor('#wizardNext');
            $browser->script('const values={client_first_name:"Maria",client_last_name:"Santos",client_relationship:"Child",client_contact_number:"09170000000",client_address:"Manila"};Object.entries(values).forEach(([name,value])=>{document.querySelector(`[name=${name}]`).value=value})');
            $browser->click('#wizardNext')->waitUntil('document.querySelector(".wizard-panel:not(.hidden)").dataset.step==="2"');
            $browser->click('#wizardNext');
            $browser->assertVisible('#deceased_first_name_inline_error')->assertVisible('#deceased_last_name_inline_error');
            $this->assertGreaterThanOrEqual(5, $browser->script('return document.querySelectorAll(".wizard-panel")[1].querySelectorAll("[data-validation-summary] li").length')[0]);
            $browser->script('document.querySelector("[name=deceased_first_name]").value="Jose";document.querySelector("[name=deceased_last_name]").value="Santos";const values={born:"1980-01-01",died:"'.now()->subDay()->toDateString().'"};Object.entries(values).forEach(([id,value])=>{const field=document.getElementById(id);field.value=value;if(field._flatpickr)field._flatpickr.setDate(value,false);field.dispatchEvent(new Event("change",{bubbles:true}))})');
            $browser->type('deceased_address', 'Manila');
            $browser->pause(400)->click('#wizardNext');
            $browser->waitUntil('document.querySelector(".wizard-panel:not(.hidden)").dataset.step==="3"');
            $browser->click('#wizardNext');
            $this->assertGreaterThanOrEqual(3, $browser->script('return document.querySelectorAll(".wizard-panel")[2].querySelectorAll("[data-validation-summary] li").length')[0]);
        });
    }

    public function test_resumed_draft_collects_missing_payment_amount_and_receipt_time_together(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $draft = IntakeDraft::create([
            'branch_id' => $staff->branch_id, 'created_by' => $staff->id,
            'entry_mode' => 'main', 'status' => IntakeDraft::STATUS_IN_PROGRESS,
            'current_step' => 4, 'last_saved_at' => now(),
            'payload' => ['fields' => ['payment_method' => 'cash', 'payment_type' => 'PARTIAL']],
        ]);
        $this->browse(function (Browser $browser) use ($staff, $draft) {
            $browser->loginAs($staff)->visit(route('intake.drafts.edit', $draft, false))->waitFor('#wizardNext');
            $browser->assertMissing('[data-validation-summary]');
            $browser->click('#wizardNext')->assertVisible('#amount_paid_inline_error')->assertVisible('#paid_at_inline_error');
            $this->assertGreaterThanOrEqual(2, $browser->script('return document.querySelectorAll(".wizard-panel")[3].querySelectorAll("[data-validation-summary] li").length')[0]);
            $browser->assertSee('Payment Received Date & Time');
            $browser->screenshot('intake-billing-validation-summary');
            $browser->script('document.getElementById("intakeWizardForm").requestSubmit(document.getElementById("saveIntakeDraft"))');
            $browser->waitForText('Draft saved');
        });
    }

    public function test_service_step_rejects_symbol_locations_and_invalid_schedule_times(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $draft = IntakeDraft::create([
            'branch_id' => $staff->branch_id, 'created_by' => $staff->id,
            'entry_mode' => 'main', 'status' => IntakeDraft::STATUS_IN_PROGRESS,
            'current_step' => 3, 'last_saved_at' => now(),
            'payload' => ['fields' => ['wake_location' => 'Funeral Home!!!', 'place_of_cemetery' => 'Forest Lake~~',
                'died' => now()->subDay()->toDateString(),
                'wake_start_date' => now()->addDay()->toDateString(), 'wake_start_time' => '25:00',
                'wake_end_date' => now()->addDay()->toDateString(), 'wake_end_time' => '09:00',
                'funeral_service_at' => now()->addDay()->toDateString(), 'funeral_service_time' => '08:00',
                'interment_at' => now()->addDay()->toDateString(), 'interment_time' => '07:00']],
        ]);
        $this->browse(function (Browser $browser) use ($staff, $draft) {
            $browser->loginAs($staff)->visit(route('intake.drafts.edit', $draft, false))->waitFor('#wizardNext');
            $browser->script('document.getElementById("wake_start_time").value="25:00"');
            $browser->click('#wizardNext')->assertVisible('#wake_location_inline_error')->assertVisible('#place_of_cemetery_inline_error');
            $browser->assertSeeIn('#wake_start_error', 'valid time')->assertVisible('#funeral_service_at_error')->assertVisible('#interment_at_error');
            $browser->script('document.querySelector(".wizard-panel:not(.hidden) [data-validation-summary]").scrollIntoView({block:"start"})');
            $browser->screenshot('intake-service-validation-summary');
        });
    }

    public function test_intake_success_modal_reuses_the_monitoring_transaction_summary(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $payment = \App\Models\Payment::with(['funeralCase.client', 'funeralCase.deceased'])->firstOrFail();
        $case = $payment->funeralCase;
        $this->actingAs($staff)->withSession([
            'intake_payment' => ['id' => $payment->id, 'record_no' => $payment->display_payment_record_no,
                'case_code' => $case->case_code, 'client' => $case->client->full_name, 'deceased' => $case->deceased->full_name,
                'amount' => (float) $payment->amount, 'balance' => (float) $case->balance_amount,
                'method' => 'Cash', 'received_at' => now()->format('M d, Y h:i A')],
            'summary' => ['package' => $case->service_package, 'total' => $case->total_amount, 'payment_status' => 'PARTIAL'],
        ]);
        $preview = public_path('intake-summary-qa.html');
        file_put_contents($preview, $this->get(route('intake.saved'))->assertOk()->assertSee('id="intakePaymentSuccess"', false)->getContent());
        try {
            $this->browse(function (Browser $browser) use ($staff, $payment, $case) {
                $browser->loginAs($staff)->visit('/intake-summary-qa.html');
                $browser->waitFor('#intakePaymentSuccess', 10)->assertSeeIn('#intakePaymentSuccess', 'PHP '.number_format($payment->amount, 2))
                    ->assertSeeIn('#intakePaymentSuccess', $case->client->full_name)->assertSeeIn('#intakePaymentSuccess', 'Partially paid');
                $browser->assertAttribute('#intakePrintPaymentCopy', 'href', route('payments.summary', $payment));
                $this->assertTrue($browser->script('return document.querySelector("#intakePaymentSuccess").matches(":modal")')[0]);
                $browser->resize(1280, 900)->screenshot('intake-payment-success-desktop');
                foreach (['light', 'dark'] as $theme) {
                    $browser->script('document.documentElement.dataset.theme="'.$theme.'"');
                    $browser->pause(350);
                    $contrasts = $browser->script(<<<'JS'
                        const modal = document.querySelector('#intakePaymentSuccess');
                        const luminance = color => {
                            const rgb = color.match(/[\d.]+/g).slice(0, 3).map(v => {
                                const c = Number(v) / 255;
                                return c <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4;
                            });
                            return rgb[0] * .2126 + rgb[1] * .7152 + rgb[2] * .0722;
                        };
                        const background = luminance(getComputedStyle(modal).backgroundColor);
                        return [...modal.querySelectorAll('h2,h3,p,dt,dd')].map(el => {
                            const text = luminance(getComputedStyle(el).color);
                            return (Math.max(text, background) + .05) / (Math.min(text, background) + .05);
                        });
                        JS)[0];
                    foreach ($contrasts as $contrast) {
                        $this->assertGreaterThanOrEqual(4.5, $contrast, 'Payment modal text contrast in '.$theme.' theme');
                    }
                    $browser->screenshot('intake-payment-success-'.$theme);
                }
                $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                    'cmd' => 'Emulation.setDeviceMetricsOverride',
                    'params' => ['width' => 390, 'height' => 844, 'deviceScaleFactor' => 1, 'mobile' => true],
                ]);
                $browser->screenshot('intake-payment-success-mobile');
                $this->assertSame(390, $browser->script('return innerWidth')[0]);
                $this->assertTrue($browser->script('const r=document.querySelector("#intakePaymentSuccess").getBoundingClientRect(); return r.left>=0 && r.right<=innerWidth && r.top>=0 && r.bottom<=innerHeight')[0]);
                $browser->click('#intakePaymentDone')->assertMissing('#intakePaymentSuccess');
                $browser->visit(route('payments.summary', $payment))->assertSee('Payment Summary')
                    ->assertSee($case->client->full_name)->assertSee('PHP '.number_format($payment->amount, 2))->assertSee('Print Payment Summary');
            });
        } finally {
            @unlink($preview);
        }
    }

    public function test_new_case_and_explicit_autosave_recovery_keep_payment_choices_consistent(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $this->browse(function (Browser $browser) use ($staff) {
            $browser->loginAs($staff)->visit('/intake/main')->waitUntil('document.querySelector("#paid_at") !== null');
            $browser->script('localStorage.clear()');
            $browser->refresh()->waitUntil('document.querySelector("#paid_at") !== null');
            $browser->assertInputValue('paid_at', '');
            $this->assertTrue($browser->script('return document.getElementById("payment_type_section").classList.contains("hidden")')[0]);
            $this->assertTrue($browser->script('return [...document.querySelectorAll(".payment-type-radio")].every(r => r.disabled)')[0]);
            $browser->script('document.querySelector("[name=client_first_name]").value="Recovery"; document.querySelector("[name=client_first_name]").dispatchEvent(new Event("input", {bubbles:true})); document.querySelector(".payment-method-radio[value=cash]").click(); document.querySelector(".payment-type-radio[value=PARTIAL]").click(); document.querySelector("#paid_at").value="2026-07-18T08:30"; document.querySelector("#paid_at").dispatchEvent(new Event("input", {bubbles:true}))');
            $browser->pause(500)->refresh()->waitFor('#restore_intake_autosave');
            $browser->script('document.getElementById("intake_autosave_recovery").scrollIntoView({block:"center"})');
            $browser->screenshot('intake-autosave-recovery');
            $this->assertTrue($browser->script('const a=document.getElementById("restore_intake_autosave").getBoundingClientRect(),b=document.getElementById("dismiss_intake_autosave").getBoundingClientRect();return a.height>=44 && b.height>=44 && (b.left>a.right || b.top>=a.bottom)')[0]);
            $browser->assertInputValue('client_first_name', '')->assertInputValue('paid_at', '');
            $browser->click('#restore_intake_autosave');
            $browser->assertInputValue('client_first_name', 'Recovery')->assertInputValue('paid_at', '2026-07-18T08:30');
            $this->assertSame('cash', $browser->script('return document.querySelector(".payment-method-radio:checked").value')[0]);
            $this->assertFalse($browser->script('return document.getElementById("payment_type_section").classList.contains("hidden")')[0]);
            $this->assertSame('PARTIAL', $browser->script('return document.querySelector(".payment-type-radio:checked").value')[0]);
            $browser->script('document.querySelector(".payment-method-radio[value=none]").click()');
            $this->assertTrue($browser->script('return document.getElementById("payment_type_section").classList.contains("hidden")')[0]);
            $browser->assertInputValue('paid_at', '');
            $this->assertTrue($browser->script('return document.querySelector("#paid_at").disabled && [...document.querySelectorAll(".payment-type-radio")].every(r => r.disabled && !r.checked)')[0]);
            $browser->script('document.querySelector(".payment-method-radio[value=cashless]").click(); document.querySelector("#cashless_type").value="gcash"; document.querySelector("#cashless_type").dispatchEvent(new Event("change", {bubbles:true})); document.querySelector("#reference_number").value="GCASH-12345"; document.querySelector("#reference_number").dispatchEvent(new Event("input", {bubbles:true})); const account=document.querySelector("[data-intake-cashless-panel=gcash] [data-intake-account-name-field]"); account.value="Maria Client"; account.dispatchEvent(new Event("input", {bubbles:true}))');
            $browser->pause(500)->refresh()->waitFor('#restore_intake_autosave')->click('#restore_intake_autosave');
            $browser->assertInputValue('#reference_number', 'GCASH-12345');
            $browser->assertInputValue('[data-intake-cashless-panel="gcash"] [data-intake-account-name-field]', 'Maria Client');
        });
    }

    public function test_saved_draft_retains_actual_receipt_time_and_has_its_own_autosave(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $draft = IntakeDraft::create([
            'branch_id' => $staff->branch_id, 'created_by' => $staff->id,
            'entry_mode' => 'main', 'status' => IntakeDraft::STATUS_IN_PROGRESS,
            'current_step' => 4, 'last_saved_at' => now(),
            'payload' => ['fields' => ['client_first_name' => 'Saved Draft', 'payment_method' => 'cash',
                'payment_type' => 'PARTIAL', 'amount_paid' => 100, 'paid_at' => '2026-07-18T08:30']],
        ]);
        $this->browse(function (Browser $browser) use ($staff, $draft) {
            $browser->loginAs($staff)->visit(route('intake.drafts.edit', $draft, false))->waitUntil('document.querySelector("#paid_at") !== null');
            $browser->assertInputValue('paid_at', '2026-07-18T08:30');
            $this->assertTrue($browser->script('return document.querySelector("#paid_at").required && !document.querySelector("#paid_at").disabled')[0]);
            $browser->script('document.querySelector("#paid_at").value="2026-07-17T07:15"; document.querySelector("#paid_at").dispatchEvent(new Event("input", {bubbles:true}))');
            $browser->pause(500);
            $this->assertTrue($browser->script('return Object.keys(localStorage).some(k => k.endsWith("saved-'.$draft->id.'"))')[0]);
            $browser->refresh()->waitFor('#restore_intake_autosave')->assertInputValue('paid_at', '2026-07-18T08:30');
            $browser->click('#restore_intake_autosave')->assertInputValue('paid_at', '2026-07-17T07:15');
            $browser->visit('/intake/main')->waitUntil('document.querySelector("#paid_at") !== null')->assertInputValue('paid_at', '');
        });
    }

    public function test_billing_cannot_proceed_to_review_without_an_explicit_payment_choice(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $draft = IntakeDraft::create([
            'branch_id' => $staff->branch_id, 'created_by' => $staff->id,
            'entry_mode' => 'main', 'status' => IntakeDraft::STATUS_IN_PROGRESS,
            'current_step' => 4, 'last_saved_at' => now(),
            'payload' => ['fields' => []],
        ]);
        $this->browse(function (Browser $browser) use ($staff, $draft) {
            $browser->loginAs($staff)->visit(route('intake.drafts.edit', $draft, false))
                ->waitFor('#wizardNext')->click('#wizardNext');
            $browser->assertVisible('#payment_method_error');
            $this->assertSame('4', $browser->script('return document.querySelector(".wizard-panel:not(.hidden)").dataset.step')[0]);
            $browser->click('.payment-method-radio[value="cash"] + span');
            $this->assertTrue($browser->script('return [...document.querySelectorAll(".payment-type-radio")].every(r => !r.disabled)')[0]);
            $browser->click('#wizardNext')->assertVisible('#payment_type_error');
            $browser->click('.payment-type-radio[value="PARTIAL"] + span');
            $browser->type('amount_paid', '100')->click('#wizardNext');
            $this->assertSame('4', $browser->script('return document.querySelector(".wizard-panel:not(.hidden)").dataset.step')[0]);
            $browser->assertInputValue('paid_at', '');
            $browser->click('.payment-method-radio[value="none"] + span')->click('#wizardNext');
            $this->assertSame('5', $browser->script('return document.querySelector(".wizard-panel:not(.hidden)").dataset.step')[0]);
        });
    }
}
