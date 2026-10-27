<?php

namespace Tests\Browser;

use App\Models\FuneralCase;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CustomPackageSavedViewTest extends DuskTestCase
{
    public function test_saved_custom_package_is_clear_in_case_details(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $case = FuneralCase::where('branch_id', $staff->branch_id)->firstOrFail()->replicate();
        $deceased = $case->deceased->replicate();
        $deceased->save();
        $case->fill([
            'deceased_id' => $deceased->id,
            'case_code' => 'FC-CUSTOM-QA',
            'case_number' => 9999,
            'package_id' => null,
            'service_package' => 'Family Custom QA',
            'package_name_snapshot' => 'Family Custom QA',
            'package_price_snapshot' => 50000,
            'coffin_type' => 'CUSTOM',
            'custom_package_name' => 'Family Custom QA',
            'custom_package_price' => 50000,
            'custom_package_inclusions' => "Transport\nMemorial setup",
            'custom_package_freebies' => 'Flowers',
            'pricing_snapshot' => [
                'custom_package' => true,
                'inclusions' => [
                    ['service_type' => 'custom', 'name' => 'Transport'],
                    ['service_type' => 'custom', 'name' => 'Memorial setup'],
                ],
                'freebies' => [['name' => 'Flowers', 'quantity' => null, 'unit' => '']],
                'service_charges' => [],
                'add_ons' => [],
                'additional_items' => [],
            ],
            'subtotal_amount' => 50000,
            'total_amount' => 50000,
            'balance_amount' => 50000,
            'total_paid' => 0,
            'payment_status' => 'UNPAID',
        ]);
        $case->save();

        $this->browse(function (Browser $browser) use ($case) {
            $browser->visit('/login')
                ->type('email', 'owner@funeral.test')
                ->type('password', 'Owner12345!')
                ->press('Sign in')
                ->visit(route('funeral-cases.show', $case, false))
                ->waitFor('#caseViewContent')
                ->assertSeeIn('#case-service', 'Family Custom QA')
                ->assertSeeIn('#case-service', 'Transport')
                ->assertSeeIn('#case-service', 'Memorial setup')
                ->assertSeeIn('#case-service', 'Flowers')
                ->assertDontSeeIn('#case-service', 'Custom Name')
                ->assertDontSeeIn('#case-service', 'CUSTOM');

            $browser->script('document.querySelector("#case-service").scrollIntoView({block:"start"})');
            $browser->screenshot('custom-package-saved-light');
            $browser->resize(390, 844);
            $browser->script('document.querySelector("#case-service").scrollIntoView({block:"start"})');
            $browser->screenshot('custom-package-saved-mobile');
            $this->assertTrue($browser->script('return document.documentElement.scrollWidth <= window.innerWidth + 1')[0]);
        });
    }
}
