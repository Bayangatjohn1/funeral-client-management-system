<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class IntakeBillingPresentationTest extends DuskTestCase
{
    public function test_payment_choices_preserve_conditional_fields_and_required_flags_in_flat_layout(): void
    {
        $source = file_get_contents(resource_path('views/staff/intake/_form.blade.php'));
        $start = strpos($source, '<section class="wizard-panel hidden" data-step="4"');
        $end = strpos($source, '<section class="wizard-panel hidden" data-step="5"', $start);
        $markup = Blade::render(substr($source, $start, $end - $start), ['intakeValue' => fn ($name, $default = null) => $default, 'isOtherEntryMode' => false, 'addOnReview' => null, 'errors' => new ViewErrorBag]);
        $start = strpos($source, '<style>');
        $styles = substr($source, $start, strpos($source, '</style>', $start) - $start + 8);
        $styles .= file_get_contents(resource_path('views/staff/intake/_interaction-styles.blade.php'));
        $styles .= file_get_contents(resource_path('views/staff/intake/_wizard-surface-styles.blade.php'));
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $built = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach ($manifest['resources/js/app.js']['css'] ?? [] as $file) $built .= file_get_contents(public_path('build/'.$file));
        $start = strpos($source, '    const payMethodRadios =');
        $end = strpos($source, '    const clearPaymentMethodValidity =', $start);
        $script = substr($source, $start, $end - $start);
        $html = '<!doctype html><html data-theme="light"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>'.$built.'</style>'.$styles.'</head><body class="panel-shell-body staff-ui"><div class="intake-root intake-reference"><form id="intakeWizardForm"><div id="intakeFormContent">'.$markup.'</div></form></div><script>const mark=document.querySelector("#mark_as_paid"),amountPaid=document.querySelector("#amount_paid"),paidAt=document.querySelector("#paid_at"),payTypeRadios=[...document.querySelectorAll(".payment-type-radio")],syncControls=()=>{},syncMarkLabel=()=>{};'.$script.'payMethodRadios.forEach(r=>r.addEventListener("change",syncPayMethod));payTypeRadios.forEach(r=>r.addEventListener("change",syncPayMethod));cashlessType.addEventListener("change",syncPayMethod);document.querySelector("#change_intake_payment_method").addEventListener("click",()=>{payMethodRadios.forEach(r=>r.checked=false);syncPayMethod();});document.querySelector("#intake-step-4").classList.remove("hidden");syncPayMethod();</script></body></html>';
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            foreach (['light', 'dark'] as $theme) {
                foreach ([1440, 390] as $width) {
                    $browser->resize($width, 1000);
                    $browser->script('document.documentElement.dataset.theme="'.$theme.'";');
                    $browser->click('.payment-method-card:has(input[value="cash"])')
                        ->assertScript('getComputedStyle(document.querySelector(".payment-method-card:has(input[value=cashless])")).display', 'none')
                        ->assertScript('!document.querySelector("#payment_details_section").classList.contains("hidden")', true)
                        ->assertScript('document.querySelector("#cashless_type").required', false)
                        ->click('.payment-type-card:has(input[value="PARTIAL"])')
                        ->assertScript('!document.querySelector("#payment_amount_section").classList.contains("hidden")', true)
                        ->click('#change_intake_payment_method')
                        ->assertScript('document.querySelector("#change_intake_payment_method").classList.contains("hidden")', true)
                        ->click('.payment-method-card:has(input[value="cashless"])')
                        ->assertScript('getComputedStyle(document.querySelector(".payment-method-card:has(input[value=cash])")).display', 'none')
                        ->assertScript('document.querySelector("#cashless_type").required', true)
                        ->select('#cashless_type', 'bank_transfer')
                        ->assertScript('document.querySelector("#bank_name").required', true)
                        ->assertScript('document.querySelector("#reference_number").required', true)
                        ->click('#change_intake_payment_method')
                        ->assertScript('document.querySelector("#payment_details_section").classList.contains("hidden")', true)
                        ->assertScript('document.querySelector("#cashless_type").required', false)
                        ->assertScript('document.querySelector("#amount_paid").value', '')
                        ->assertScript('document.querySelectorAll("#payment_method_group input[name=payment_method]").length', 2);
                    $browser->driver->executeScript('window.scrollTo(0,0)');
                    $browser->screenshot('intake-billing-'.$theme.'-'.$width);
                }
            }
        });
    }
}
