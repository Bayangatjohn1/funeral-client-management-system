<?php

namespace Tests\Browser;

use App\Models\Package;
use App\Models\User;
use Laravel\Dusk\Browser;
use PragmaRX\Google2FA\Google2FA;
use Tests\DuskTestCase;

class ServiceManagementModalTest extends DuskTestCase
{
    public function test_discard_confirmation_hides_user_service_and_package_forms_until_keep_editing(): void
    {
        $discard = file_get_contents(resource_path('js/form-discard.js'));
        $motion = str_replace('export function', 'function', file_get_contents(resource_path('js/record-dialog-motion.js')));
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        $html = '<!doctype html><html data-theme="light"><head><style>'.$css.'</style></head><body class="system-admin-ui">'
            .'<div class="user-create-page"><form id="userCreateForm"><input id="user-name" name="name" value="Initial"><a href="#discard" data-record-form-cancel>Cancel user form</a></form></div>'
            .'<form id="packageForm"><input id="package-name" value="Package draft"><button id="cancel-package" type="button">Cancel package form</button></form>'
            .'<button id="open-service">Open service</button><dialog id="service-form"><div class="service-modal__panel" style="visibility:visible"><input id="service-name" value="Service draft"><button id="cancel-service">Cancel service form</button></div></dialog>'
            .'<script>'.$motion.'installRecordDialogMotion();'.$discard
            .'document.querySelector("#cancel-package").onclick = () => window.confirmFormDiscard(document.querySelector("#packageForm"));'
            .'document.querySelector("#open-service").onclick = () => document.querySelector("#service-form").showModal();'
            .'document.querySelector("#cancel-service").onclick = () => window.confirmFormDiscard(document.querySelector("#service-form"));'
            .'</script></body></html>';

        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                'cmd' => 'Emulation.setEmulatedMedia',
                'params' => ['features' => [['name' => 'prefers-reduced-motion', 'value' => 'reduce']]],
            ]);
            $browser->type('#user-name', 'User draft')
                ->assertScript('getComputedStyle(document.querySelector("#user-name")).outlineWidth', '1px')
                ->click('[data-record-form-cancel]')
                ->waitUntil('document.querySelector(".service-feedback-dialog")?.open === true')
                ->assertScript('getComputedStyle(document.querySelector(".user-create-page")).opacity', '0')
                ->click('[data-keep]')
                ->waitUntil('!document.querySelector(".service-feedback-dialog")')
                ->assertInputValue('#user-name', 'User draft');

            $browser->click('#cancel-package')
                ->waitUntil('document.querySelector(".service-feedback-dialog")?.open === true')
                ->assertScript('getComputedStyle(document.querySelector("#packageForm")).opacity', '0')
                ->assertScript('getComputedStyle(document.querySelector("[data-keep]")).backgroundColor', 'rgb(234, 242, 237)')
                ->click('[data-keep]')
                ->waitUntil('!document.querySelector(".service-feedback-dialog")')
                ->assertInputValue('#package-name', 'Package draft');

            $browser->click('#open-service')
                ->click('#cancel-service')
                ->waitUntil('document.querySelector(".service-feedback-dialog")?.open === true')
                ->assertScript('getComputedStyle(document.querySelector("#service-form")).opacity', '0')
                ->click('[data-keep]')
                ->waitUntil('!document.querySelector(".service-feedback-dialog")')
                ->assertInputValue('#service-name', 'Service draft');
        });
    }

    public function test_package_add_view_and_edit_dialogs_keep_the_expected_flow(): void
    {
        $admin = User::where('email', 'admin@funeral.test')->firstOrFail();
        $secret = (new Google2FA)->generateSecretKey();
        $admin->two_factor_secret = $secret;
        $admin->two_factor_confirmed_at = now();
        $admin->must_change_password = false;
        $admin->save();
        Package::create(['name' => 'Modal QA Package', 'price' => 10000, 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($secret) {
            $browser->visit('/login')
                ->type('email', 'admin@funeral.test')
                ->type('password', 'Admin12345!')
                ->press('Sign in')
                ->waitForLocation('/security/challenge', 10)
                ->type('code', (new Google2FA)->getCurrentOtp($secret))
                ->press('Verify and continue')
                ->waitForLocation('/admin', 10)
                ->visit('/admin/service-management?tab=packages');
            $switch = $browser->script('const grid=document.querySelector("[data-catalog-view=cards]"), table=document.querySelector("[data-catalog-view=table]"); return {grid:grid.getAttribute("aria-label"),table:table.getAttribute("aria-label"),gridText:grid.textContent.trim(),tableText:table.textContent.trim(),tooltip:getComputedStyle(grid,"::after").content}')[0];
            $this->assertSame('Grid view', $switch['grid']);
            $this->assertSame('Table view', $switch['table']);
            $this->assertSame('', $switch['gridText']);
            $this->assertSame('', $switch['tableText']);
            $this->assertSame('"Grid view"', $switch['tooltip']);
            $browser->screenshot('service-management-desktop');
            $browser->resize(390, 900);
            $this->assertFalse($browser->script('return document.documentElement.scrollWidth > innerWidth')[0]);
            $browser->screenshot('service-management-mobile');
            $browser->resize(1440, 900);
            $browser->script('localStorage.setItem("app-theme", "dark"); document.documentElement.dataset.theme = "dark"');
            $browser->pause(300);
            $this->assertSame('rgb(203, 213, 209)', $browser->script('return getComputedStyle(document.querySelector(".service-hub-tab:not(.is-active)")).color')[0]);
            $browser->screenshot('service-management-dark');
            $browser->script('localStorage.setItem("app-theme", "light"); document.documentElement.dataset.theme = "light"');
            $browser->pause(300);
            $browser
                ->waitFor('.service-reference-header [data-service-modal-title="Add Package"]')
                ->click('.service-reference-header [data-service-modal-title="Add Package"]')
                ->waitUntil('document.querySelector("[data-service-modal]").open')
                ->waitUntil('document.querySelector("[data-service-modal-frame]").contentDocument?.querySelector("#packageForm.package-wizard") !== null');

            $initialStep = $browser->script('const d=document.querySelector("[data-service-modal-frame]").contentDocument; return {progress:d.querySelector("[data-wizard-progress]")?.textContent, active:d.querySelector("[data-wizard-go][aria-current=step]")?.dataset.wizardGo, visible:[...d.querySelectorAll("[data-wizard-step]")].filter(p=>!p.hidden).map(p=>p.dataset.wizardStep), errors:[...d.querySelectorAll(".form-error")].filter(e=>e.textContent.trim()).map(e=>e.textContent.trim())}')[0];
            $this->assertSame('1', $initialStep['active'], json_encode($initialStep));
            $browser->screenshot('service-package-add-desktop');

            foreach (['light', 'dark'] as $theme) {
                $browser->script('document.documentElement.dataset.theme = '.json_encode($theme));
                foreach ([1440, 390] as $width) {
                    $browser->resize($width, 900);
                    $layout = $browser->script('const dialog=document.querySelector("[data-service-modal]"); const frame=dialog.querySelector("iframe"); const box=dialog.querySelector(".service-modal__panel").getBoundingClientRect(); const inner=frame.contentDocument; return {open:dialog.open, title:dialog.querySelector("h2").textContent.trim(), duplicateHeader:getComputedStyle(inner.querySelector(".pkg-hero")).display, wizard:inner.querySelector(".package-wizard-nav").getBoundingClientRect().width, left:box.left, right:box.right, viewport:innerWidth}')[0];
                    $this->assertTrue($layout['open']);
                    $this->assertSame('Add Package', $layout['title']);
                    $this->assertSame('none', $layout['duplicateHeader']);
                    $this->assertGreaterThan(250, $layout['wizard']);
                    $this->assertGreaterThanOrEqual(0, $layout['left']);
                    $this->assertLessThanOrEqual($layout['viewport'], $layout['right']);
                }
            }

            $browser->script('document.querySelector("[data-service-modal-frame]").contentDocument.querySelector(".package-wizard-footer [data-service-form-cancel]").click()');
            $browser->waitUntil('!document.querySelector("[data-service-modal]").open');

            $browser->resize(1440, 900)
                ->click('.service-package-card[data-search*="modal qa package"] [data-service-modal-title="View Package"]')
                ->waitUntil('document.querySelector("[data-service-modal]").open')
                ->waitUntil('document.querySelector("[data-service-modal-frame]").contentDocument?.readyState === "complete" && !!document.querySelector("[data-service-modal-frame]").contentDocument?.querySelector(".svc-detail-primary")');

            $browser->script('document.querySelector("[data-service-modal-frame]").contentDocument.querySelector(".svc-detail-primary").click()');
            $browser->waitUntil('document.querySelector("[data-service-modal] [data-service-modal-title]").textContent.trim() === "Edit Package"', 10)
                ->waitUntil('document.querySelector("[data-service-modal-frame]").contentDocument?.readyState === "complete" && !!document.querySelector("[data-service-modal-frame]").contentDocument?.querySelector("[data-service-form-cancel]")', 10);
            $browser->script('document.querySelector("[data-service-modal-frame]").contentDocument.querySelector(".package-wizard-footer [data-service-form-cancel]").click()');
            $browser->waitUntil('document.querySelector("[data-service-modal] [data-service-modal-title]").textContent.trim() === "View Package"', 10)
                ->waitUntil('document.querySelector("[data-service-modal-frame]").contentDocument?.querySelector(".svc-detail-card") !== null', 10);
            $browser->click('[data-service-modal-close]')->waitUntil('!document.querySelector("[data-service-modal]").open');
        });
    }
}
