<?php

namespace Tests\Browser;

use App\Models\FuneralCase;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DailyOperationsQaTest extends DuskTestCase
{
    public function test_login_form_accepts_staff_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginThroughForm($browser, 'staff@funeral.test', 'Staff12345!', '/staff');

            $this->assertHealthyPage($browser);
        });
    }

    public function test_staff_daily_operation_pages_load_and_basic_widgets_work(): void
    {
        $case = FuneralCase::where('case_code', 'CASE-DEMO-001')->firstOrFail();
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();

        $this->browse(function (Browser $browser) use ($staff) {
            $browser->loginAs($staff)
                ->visit('/staff')
                ->waitUntil("document.body && document.body.innerText.includes('Your daily workspace')", 10)
                ->click('[data-activity-tab="payments"]')
                ->click('[data-schedule-tab="upcoming"]');

            $this->assertPageContains($browser, ['Your daily workspace', 'Cases Encoded', 'Latest payments recorded', 'Next schedules in queue']);

            $this->assertHealthyPage($browser);
        });

        static::closeAll();

        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        foreach ([
            ['/intake/main', ['Case Intake - Main Branch', 'Client Information', 'Service Selection']],
            ['/intake/drafts', ['Saved Intake Drafts', 'Client', 'Deceased']],
            ['/funeral-cases', ['Case Records', 'CASE-DEMO-001', 'Juan Dela Cruz']],
            ['/completed-cases', ['Completed Case Records']],
            ['/payments', ['Record Payment', 'Record a Case Payment', 'CASE-DEMO-001']],
            ['/payments/history', ['Payment Monitoring', 'CASE-DEMO-001']],
            ['/clients', ['Client Directory', 'Juan Dela Cruz']],
            ['/deceased', ['Deceased Records', 'Maria Dela Cruz']],
            ['/reminders', ['Reminders & Schedule', 'Case Status']],
            ["/funeral-cases/{$case->id}", ['Case Information', 'Client Information', 'Deceased Information']],
        ] as [$path, $expectedText]) {
            $response = $this->actingAs($staff)->get($path)->assertOk();
            foreach ($expectedText as $expected) {
                $response->assertSee($expected);
            }
        }
    }

    public function test_admin_daily_operation_pages_load_and_catalog_tabs_work(): void
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where('email', 'admin@funeral.test')->firstOrFail();

            $browser->loginAs($admin)
                ->visit('/admin')
                ->waitUntil("document.body && document.body.innerText.includes('Good morning, Admin')", 10);

            $this->assertPageContains($browser, ['Good morning, Admin', 'Service Status Summary']);

            $this->assertHealthyPage($browser);
        });

        static::closeAll();

        $admin = User::where('email', 'admin@funeral.test')->firstOrFail();
        foreach ([
            ['/admin/users', ['User Management', 'User Directory', 'Add User']],
            ['/admin/branches', ['Branch Management', 'Branch Directory', 'Add Branch']],
            ['/admin/cases', ['Master Case Records', 'CASE-DEMO-001']],
            ['/admin/payments', ['Payment Monitoring']],
            ['/admin/reports/sales', ['Sales Reports', 'Total Service Amt.', 'Collected']],
            ['/admin/audit-logs', ['Audit Logs']],
            ['/admin/reminders', ['Reminders & Schedule']],
        ] as [$path, $expectedText]) {
            $response = $this->actingAs($admin)->get($path)->assertOk();
            foreach ($expectedText as $expected) {
                $response->assertSee($expected);
            }
        }

        $this->browse(function (Browser $browser) {
            $admin = User::where('email', 'admin@funeral.test')->firstOrFail();

            $browser->loginAs($admin)
                ->visit('/admin/service-management')
                ->click('[data-service-tab="caskets"]')
                ->click('[data-service-tab="addons"]')
                ->click('[data-service-tab="freebies"]')
                ->pause(250);

            $this->assertPageContains($browser, ['Service Management', 'Service Catalogs', 'Caskets', 'Add-ons', 'Freebies']);

            $this->assertHealthyPage($browser);
        });
    }

    public function test_owner_daily_operation_pages_and_reports_load(): void
    {
        $this->browse(function (Browser $browser) {
            $owner = User::where('email', 'owner@funeral.test')->firstOrFail();

            $browser->loginAs($owner)
                ->visit('/owner')
                ->waitUntil("document.body && document.body.innerText.toLowerCase().includes('service value')", 10);

            $this->assertPageContains($browser, ['Service Value', 'Branch Performance']);

            $this->assertHealthyPage($browser);
        });

        static::closeAll();

        $owner = User::where('email', 'owner@funeral.test')->firstOrFail();
        foreach ([
            ['/owner/branch-analytics', ['Branch Analytics', 'Overall Branch Analytics Summary']],
            ['/owner/case-history', ['Master Case Records', 'CASE-DEMO-001']],
            ['/reports?report_type=owner_branch_analytics', ['Reports & Analytics', 'Summary Metrics', 'Report Preview']],
        ] as [$path, $expectedText]) {
            $response = $this->actingAs($owner)->get($path)->assertOk();
            foreach ($expectedText as $expected) {
                $response->assertSee($expected);
            }
        }
    }

    private function visitAndCheck(Browser $browser, string $path, array $expectedText): void
    {
        $expectedPath = parse_url($path, PHP_URL_PATH) ?: $path;
        $expectedJson = json_encode(array_values($expectedText));
        $baseUrl = rtrim((string) ($_SERVER['APP_URL'] ?? $_ENV['APP_URL'] ?? config('app.url')), '/');

        $browser->pause(150);
        $browser->driver->navigate()->to($baseUrl . $path);

        $browser
            ->pause(250)
            ->waitUntil('window.location.pathname === ' . json_encode($expectedPath), 30)
            ->waitUntil('document.body !== null', 10)
            ->waitUntil($expectedJson . '.some((text) => document.body.innerText.includes(text))', 30)
            ->assertPathIs($expectedPath);

        $this->assertPageContains($browser, $expectedText);
        $this->assertHealthyPage($browser);
    }

    private function loginThroughForm(Browser $browser, string $email, string $password, string $expectedPath): void
    {
        $browser->driver->manage()->deleteAllCookies();

        $baseUrl = rtrim((string) ($_SERVER['APP_URL'] ?? $_ENV['APP_URL'] ?? config('app.url')), '/');
        $browser->driver->navigate()->to($baseUrl . '/login');
        $browser->pause(250);

        $currentPath = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
        if ($currentPath === $expectedPath) {
            return;
        }

        $browser->waitUntil("document.querySelector('#email') !== null && document.querySelector('#password') !== null", 10)
            ->script(
                "document.querySelector('#email').value = " . json_encode($email) . ";"
                . "document.querySelector('#password').value = " . json_encode($password) . ";"
                . "document.querySelector('form').submit();"
            );

        $browser
            ->waitUntil('window.location.pathname === ' . json_encode($expectedPath), 30)
            ->assertPathIs($expectedPath);
    }

    private function assertHealthyPage(Browser $browser): void
    {
        $source = $browser->driver->getPageSource();
        $text = trim(html_entity_decode(strip_tags($source)));

        foreach (['Server Error', 'This action is unauthorized', 'Whoops', 'Undefined variable', 'Call to undefined', 'Stack trace'] as $unexpected) {
            $this->assertStringNotContainsString($unexpected, $text);
            $this->assertStringNotContainsString($unexpected, $source);
        }
    }

    private function assertPageContains(Browser $browser, array $expectedText): void
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($browser->driver->getPageSource()))));

        foreach ($expectedText as $expected) {
            $this->assertStringContainsString(
                $expected,
                $text,
                'Missing expected text [' . $expected . '] on ' . $browser->driver->getCurrentURL() . '. Page text excerpt: ' . substr($text, 0, 900)
            );
        }
    }

}
