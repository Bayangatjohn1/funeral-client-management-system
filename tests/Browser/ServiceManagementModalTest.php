<?php

namespace Tests\Browser;

use App\Models\Package;
use App\Models\User;
use Laravel\Dusk\Browser;
use PragmaRX\Google2FA\Google2FA;
use Tests\DuskTestCase;

class ServiceManagementModalTest extends DuskTestCase
{
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
