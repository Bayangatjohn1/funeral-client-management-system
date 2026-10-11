<?php

namespace Tests\Browser;

use App\Models\IntakeDraft;
use App\Models\Package;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class IntakeSaveModalTest extends DuskTestCase
{
    public function test_paid_intake_shows_summary_after_actual_form_submission(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $package = Package::create(['name' => 'Modal Flow Package', 'price' => 50000, 'is_active' => true]);
        $tomorrow = now()->addDay()->toDateString();
        $scheduled = \App\Models\FuneralCase::where('branch_id', $staff->branch_id)->firstOrFail();
        $scheduled->update(['funeral_service_at' => $tomorrow.' 09:00:00', 'funeral_service_time' => '09:00', 'interment_at' => $tomorrow.' 10:00:00', 'interment_time' => '10:00']);
        $draft = IntakeDraft::create([
            'branch_id' => $staff->branch_id, 'created_by' => $staff->id,
            'entry_mode' => 'main', 'status' => IntakeDraft::STATUS_IN_PROGRESS,
            'current_step' => 4, 'last_saved_at' => now(),
            'payload' => ['fields' => [
                'branch_id' => $staff->branch_id, 'client_first_name' => 'Maria', 'client_last_name' => 'Modal',
                'client_relationship' => 'Child', 'client_contact_number' => '09170000000', 'client_address' => 'QA Address',
                'deceased_first_name' => 'Jose', 'deceased_last_name' => 'Modal', 'deceased_address' => 'QA Address',
                'born' => '1980-01-01', 'died' => now()->subDay()->toDateString(), 'civil_status' => 'WIDOWED',
                'wake_location' => 'Family Residence', 'wake_start_date' => now()->toDateString(), 'wake_start_time' => '08:00',
                'wake_end_date' => $tomorrow, 'wake_end_time' => '08:00', 'funeral_service_at' => $tomorrow,
                'funeral_service_time' => '09:00', 'interment_at' => $tomorrow, 'interment_time' => '10:00',
                'place_of_cemetery' => 'Public Cemetery', 'case_status' => 'ACTIVE', 'transport_option' => 'HEARSE',
                'coffin_length_cm' => 175, 'coffin_size' => 'LARGE', 'embalming_required' => 1, 'embalming_status' => 'PENDING',
                'package_id' => $package->id, 'additional_service_amount' => 0, 'senior_citizen_status' => 0,
                'pwd_status' => 0, 'tax_rate' => 0, 'payment_method' => 'cash',
                'payment_type' => 'PARTIAL', 'amount_paid' => 25000, 'paid_at' => now()->subMinutes(10)->format('Y-m-d\TH:i'),
            ]],
        ]);
        $this->browse(function (Browser $browser) use ($staff, $draft) {
            $browser->loginAs($staff)->visit(route('intake.drafts.edit', $draft, false))
                ->waitFor('#wizardNext')->click('#wizardNext')->waitFor('#saveIntakeRecord')
                ->waitForText('Other cases are scheduled on the same date')
                ->assertSeeIn('[data-schedule-advisory]', 'Funeral ceremony')
                ->assertSeeIn('[data-schedule-advisory]', 'Interment')
                ->click('label[for="confirm_review"]')->click('#saveIntakeRecord');
            $browser->waitFor('#intakePaymentSuccess', 15)->assertSeeIn('#intakePaymentSuccess', 'Maria Modal')
                ->assertSeeIn('#intakePaymentSuccess', 'PHP 25,000.00');
            $browser->assertPathIs('/intake/saved')->assertMissing('#intakeWizardForm');
            $browser->assertMissing('#intakeSavedPrint');
            $browser->assertVisible('#appSidebar');
            $this->assertTrue($browser->script('return !document.querySelector("#intakeSavedConfirmation").open && document.querySelector(".app-shell").inert && getComputedStyle(document.querySelector(".main-area")).display === "none"')[0]);
            $this->assertTrue($browser->script('const link=document.querySelector("#appSidebar a"); link.focus(); return document.activeElement!==link && document.querySelector("#intakePaymentSuccess").matches(":modal")')[0]);
            $browser->click('.ips-close')->assertVisible('#intakeSavedConfirmation')->assertMissing('#intakePaymentSuccess');
            $this->assertTrue($browser->script('const link=document.querySelector("#appSidebar a"); link.focus(); return document.activeElement!==link && document.querySelector("#intakeSavedConfirmation").matches(":modal")')[0]);
            $browser->refresh()->waitFor('#intakeSavedConfirmation')
                ->assertVisible('#intakeSavedConfirmation')->assertMissing('#intakePaymentSuccess')->assertMissing('#saveIntakeRecord');
            $browser->click('#intakeSavedDone')->waitFor('.records-page')->assertPathIs('/funeral-cases');
            $browser->visit('/intake/main')->waitFor('#wizardNext');
            $fields = $draft->payload['fields'];
            $fields['client_last_name'] = 'Newmodal';
            $fields['deceased_last_name'] = 'Newmodal';
            $browser->script('const values='.json_encode($fields).'; const changes=[]; document.querySelectorAll("#intakeWizardForm [name]").forEach(field => {if(!(field.name in values))return; if(field.type==="radio"){field.checked=String(field.value)===String(values[field.name]);if(field.checked)changes.push(field)}else{field.value=values[field.name];if(field._flatpickr)field._flatpickr.setDate(field.value,false);changes.push(field)}});changes.forEach(field=>field.dispatchEvent(new Event("change",{bubbles:true})))');
            for ($step = 1; $step <= 4; $step++) {
                $browser->click('#wizardNext');
                $browser->waitUntil('document.querySelector(".wizard-panel:not(.hidden)").dataset.step === "'.($step + 1).'"');
            }
            $browser->click('label[for="confirm_review"]')->click('#saveIntakeRecord');
            $browser->waitFor('#intakePaymentSuccess', 15)->assertSeeIn('#intakePaymentSuccess', 'Maria Newmodal')
                ->assertSeeIn('#intakePaymentSuccess', 'PHP 25,000.00');
            $originalTab = $browser->driver->getWindowHandle();
            $browser->click('#intakePrintPaymentCopy');
            $browser->driver->switchTo()->window($originalTab);
            $browser->assertVisible('#intakePaymentSuccess');
            $this->assertFalse($browser->script('return document.querySelector("#intakeSavedConfirmation").open')[0]);
            $browser->refresh()->waitFor('#intakePaymentSuccess')->assertPathIs('/intake/saved');
            $this->assertFalse($browser->script('return document.querySelector("#intakeSavedConfirmation").open')[0]);
            $browser->click('#intakePaymentDone');
            $browser->waitFor('#intakeSavedConfirmation')->assertPathIs('/intake/saved');
            $browser->refresh()->waitFor('#intakeSavedConfirmation')->assertPathIs('/intake/saved')->assertMissing('#intakePaymentSuccess');
            $browser->screenshot('intake-next-action-modal');
            $browser->click('#intakeSavedNew')->waitFor('#wizardNext')->assertPathIs('/intake/main')->assertInputValue('client_first_name', '');
        });
    }
}

