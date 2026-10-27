<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use PragmaRX\Google2FA\Google2FA;
use Tests\DuskTestCase;

class PageHeaderCenteringTest extends DuskTestCase
{
    public function test_non_dashboard_headers_remain_centered_without_context_badges(): void
    {
        $admin = User::where('email', 'admin@funeral.test')->firstOrFail();
        $secret = (new Google2FA)->generateSecretKey();
        $admin->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'must_change_password' => false,
        ])->save();
        $pages = [
            ['/admin/users', '.panel-page-header', '.panel-page-header__copy'],
            ['/admin/service-management', '.service-reference-header', '.service-reference-heading'],
            ['/reports/analytics', '.reports-reference-heading', '.reports-module-heading-copy'],
            ['/admin/payments', '.panel-page-header', '.panel-page-header__copy'],
        ];

        $this->browse(function (Browser $browser) use ($secret, $pages) {
            $browser->visit('/login')
                ->type('email', 'admin@funeral.test')
                ->type('password', 'Admin12345!')
                ->press('Sign in')
                ->waitForLocation('/security/challenge', 10)
                ->type('code', (new Google2FA)->getCurrentOtp($secret))
                ->press('Verify and continue')
                ->waitForLocation('/admin', 10);

            foreach (['light', 'dark'] as $theme) {
                foreach ([[1440, 900], [390, 900]] as [$width, $height]) {
                    $browser->resize($width, $height);

                    foreach ($pages as [$path, $headerSelector, $copySelector]) {
                        $browser->visit($path)
                            ->waitFor($headerSelector)
                            ->script('localStorage.setItem("app-theme", '.json_encode($theme).'); document.documentElement.dataset.theme = '.json_encode($theme));
                        $browser->pause(150);

                        $layout = $browser->script(<<<JS
                            const header = document.querySelector('$headerSelector');
                            const copy = document.querySelector('$copySelector');
                            const headerBox = header.getBoundingClientRect();
                            const copyBox = copy.getBoundingClientRect();
                            return {
                                delta: Math.abs((headerBox.left + headerBox.width / 2) - (copyBox.left + copyBox.width / 2)),
                                overflow: document.documentElement.scrollWidth > innerWidth,
                                titleIcon: !!header.querySelector('.page-title-icon'),
                            };
                        JS)[0];

                        $context = "$path at {$width}px in $theme mode";
                        $this->assertLessThanOrEqual(2, $layout['delta'], "Header title is off-center on $context.");
                        $this->assertFalse($layout['overflow'], "Unexpected horizontal overflow on $context.");
                        $this->assertFalse($layout['titleIcon'], "A title icon remains on $context.");
                    }
                }
            }

            $browser->resize(1440, 900)->visit('/admin/users');
            $this->assertNull($browser->script('return document.querySelector(".panel-page-header__badge")')[0]);

            $browser->visit('/admin/service-management');
            $this->assertNull($browser->script('return document.querySelector(".service-reference-title > span")')[0]);

            $browser->visit('/reports/analytics');
            $this->assertNull($browser->script('return document.querySelector(".reports-reference-scope")')[0]);
        });
    }
}
