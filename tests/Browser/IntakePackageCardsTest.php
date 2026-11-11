<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class IntakePackageCardsTest extends DuskTestCase
{
    public function test_intake_scroll_area_reaches_footer_and_last_field_remains_accessible(): void
    {
        $source = file_get_contents(resource_path('views/staff/intake/_form.blade.php'));
        $start = strpos($source, '<style>');
        $styles = substr($source, $start, strpos($source, '</style>', $start) - $start + 8);
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $built = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach ($manifest['resources/js/app.js']['css'] ?? [] as $file) $built .= file_get_contents(public_path('build/'.$file));
        $frame = file_get_contents(resource_path('views/layouts/intake-frame.blade.php'));
        $start = strpos($frame, '<style>');
        $styles .= substr($frame, $start, strpos($frame, '</style>', $start) - $start + 8);
        $html = '<!doctype html><html data-theme="light"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>'.$built.'</style>'.$styles.'</head><body class="panel-shell-body staff-ui intake-frame"><main class="page-content"><div class="intake-root intake-reference"><div class="intake-section-shell"><form id="intakeWizardForm"><div class="staff-intake-dialog-head" style="height:56px;flex-shrink:0">Add New Case</div><div class="wizard-steps-shell" style="height:80px;flex-shrink:0">Service Selection</div><div id="intakeFormContent"><section class="wizard-panel"><div style="height:1600px"></div><input id="last-field" aria-label="Last field"></section></div><div class="footer-action-bar" style="height:60px;flex-shrink:0">Continue</div></form></div></div></main></body></html>';
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            foreach ([1440, 390] as $width) {
                $browser->resize($width, 800);
                $browser->assertScript('Math.abs(document.querySelector(".footer-action-bar").getBoundingClientRect().top-document.querySelector("#intakeFormContent").getBoundingClientRect().bottom)<2', true)
                    ->assertScript('document.querySelector("#intakeFormContent").scrollHeight > document.querySelector("#intakeFormContent").clientHeight', true);
                $browser->script('const pane=document.querySelector("#intakeFormContent");pane.scrollTop=pane.scrollHeight;');
                $browser->assertScript('document.querySelector("#last-field").getBoundingClientRect().bottom <= document.querySelector(".footer-action-bar").getBoundingClientRect().top', true);
            }
        });
    }

    public function test_switching_package_cards_preserves_one_copy_of_each_control_and_its_value(): void
    {
        $source = file_get_contents(resource_path('views/staff/intake/_form.blade.php'));
        $start = strpos($source, '    const mountPackageInclusionCards =');
        $end = strpos($source, '    const syncIncludedAddOnAdjustments =', $start);
        $script = substr($source, $start, $end - $start);
        $html = '<!doctype html><html><body>';
        foreach (['first', 'second'] as $id) {
            $html .= '<section class="package-card" id="'.$id.'"><input type="radio" id="'.$id.'-radio"><div data-package-asset-slot="casket"></div><div data-package-asset-slot="hearse"></div><div data-package-other-slot></div></section>';
        }
        foreach (['package-casket-options', 'package-hearse-options', 'package-inclusion-options'] as $id) $html .= '<section id="'.$id.'"><div data-option-body></div></section>';
        $html .= '<div id="casket-card"><select id="casket-value"><option value="premium" selected>Premium</option></select></div><div id="included-addon-adjustments"><input id="quantity" type="number" value="4"></div><div id="hearse-assignment-home"><div id="hearse_assignment_fields"><select id="hearse-value"><option value="coach" selected>Coach</option></select></div></div><div id="custom-hearse-slot"></div>';
        $html .= '<script>const casketInclusionCard=document.querySelector("#casket-card"),includedAddOnAdjustments=document.querySelector("#included-addon-adjustments"),hearseAssignmentFields=document.querySelector("#hearse_assignment_fields"),hearseAssignmentHome=document.querySelector("#hearse-assignment-home"),packageCasketOptions=document.querySelector("#package-casket-options"),packageHearseOptions=document.querySelector("#package-hearse-options"),packageInclusionOptions=document.querySelector("#package-inclusion-options");'.$script.'</script></body></html>';
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            foreach (['first', 'second', 'first'] as $id) {
                $browser->script('mountPackageInclusionCards(document.querySelector("#'.$id.'-radio"),false,true,false)');
                $browser->assertScript('document.querySelector("#'.$id.' [data-package-asset-slot=casket] #casket-card") !== null', true)
                    ->assertScript('document.querySelector("#'.$id.' [data-package-asset-slot=hearse] #hearse_assignment_fields") !== null', true)
                    ->assertScript('document.querySelector("#'.$id.' [data-package-other-slot] #quantity").value', '4')
                    ->assertScript('document.querySelectorAll("#casket-card").length', 1)
                    ->assertScript('document.querySelector("#casket-value").value', 'premium')
                    ->assertScript('document.querySelector("#hearse-value").value', 'coach');
            }
            $browser->script('mountPackageInclusionCards(null,true,false,true)');
            $browser->assertScript('document.querySelector("#custom-hearse-slot #hearse_assignment_fields") !== null', true)
                ->assertScript('document.querySelector("#package-casket-options").hidden', true);
            $browser->script('mountPackageInclusionCards(null,true,false,false)');
            $browser->assertScript('document.querySelector("#hearse-assignment-home #hearse_assignment_fields") !== null', true);
        });
    }
}
