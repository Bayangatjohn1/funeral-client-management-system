<?php

namespace Tests\Browser;

use App\Models\FuneralCase;
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

        $this->browse(function (Browser $browser) use ($case) {
            $this->loginThroughForm($browser, 'staff@funeral.test', 'Staff12345!', '/staff');

            $browser->visit('/staff')
                ->waitUntil("document.body && document.body.innerText.includes('Good morning, Staff')", 10)
                ->click('[data-activity-tab="payments"]')
                ->click('[data-schedule-tab="upcoming"]');

            $this->assertPageContains($browser, ['Good morning, Staff', 'Cases Encoded', 'Latest payments recorded', 'Next schedules in queue']);

            $this->assertHealthyPage($browser);

            $this->visitAndCheck($browser, '/intake/main', ['Case Intake - Main Branch', 'Client Information', 'Service Selection']);
            $this->visitAndCheck($browser, '/intake/drafts', ['Saved Intake Drafts', 'Client', 'Deceased']);
            $this->visitAndCheck($browser, '/funeral-cases', ['Case Records', 'CASE-DEMO-001', 'Juan Dela Cruz']);
            $this->visitAndCheck($browser, '/completed-cases', ['Completed Case Records']);
            $this->visitAndCheck($browser, '/payments', ['Record Payment', 'Record a Case Payment', 'CASE-DEMO-001']);
            $this->visitAndCheck($browser, '/payments/history', ['Payment Monitoring', 'CASE-DEMO-001']);
            $this->visitAndCheck($browser, '/clients', ['Client Directory', 'Juan Dela Cruz']);
            $this->visitAndCheck($browser, '/deceased', ['Deceased Records', 'Maria Dela Cruz']);
            $this->visitAndCheck($browser, '/reminders', ['Reminders & Schedule', 'Case Status']);
            $this->visitAndCheck($browser, "/funeral-cases/{$case->id}", ['Case Information', 'Client Information', 'Deceased Information']);
        });
    }

    public function test_admin_daily_operation_pages_load_and_catalog_tabs_work(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginThroughForm($browser, 'admin@funeral.test', 'Admin12345!', '/admin');

            $browser->visit('/admin')
                ->waitUntil("document.body && document.body.innerText.includes('Good morning, Admin')", 10);

            $this->assertPageContains($browser, ['Good morning, Admin', 'Service Status Summary']);

            $this->assertHealthyPage($browser);

            $browser->visit('/admin/service-management')
                ->click('[data-service-tab="caskets"]')
                ->click('[data-service-tab="addons"]')
                ->click('[data-service-tab="freebies"]');

            $this->assertPageContains($browser, ['Service Management', 'Service Catalogs', 'Caskets', 'Add-ons', 'Freebies']);

            $this->assertHealthyPage($browser);

            $this->visitAndCheck($browser, '/admin/users', ['User Management', 'User Directory', 'Add User']);
            $this->visitAndCheck($browser, '/admin/branches', ['Branch Management', 'Branch Directory', 'Add Branch']);
            $this->visitAndCheck($browser, '/admin/cases', ['Master Case Records', 'CASE-DEMO-001']);
            $this->visitAndCheck($browser, '/admin/payments', ['Payment Monitoring']);
            $this->visitAndCheck($browser, '/admin/reports/sales', ['Sales Reports', 'Total Service Amt.', 'Collected']);
            $this->visitAndCheck($browser, '/admin/audit-logs', ['Audit Logs']);
            $this->visitAndCheck($browser, '/admin/reminders', ['Reminders & Schedule']);
        });
    }

    public function test_owner_daily_operation_pages_and_reports_load(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginThroughForm($browser, 'owner@funeral.test', 'Owner12345!', '/owner');

            $browser->visit('/owner')
                ->waitUntil("document.body && document.body.innerText.toLowerCase().includes('service value')", 10);

            $this->assertPageContains($browser, ['Service Value', 'Branch Performance']);

            $this->assertHealthyPage($browser);

            $this->visitAndCheck($browser, '/owner/branch-analytics', ['Branch Analytics', 'Overall Branch Analytics Summary']);
            $this->visitAndCheck($browser, '/owner/case-history', ['Global Case History', 'CASE-DEMO-001']);
            $this->visitAndCheck($browser, '/reports?report_type=owner_branch_analytics', ['Reports & Analytics', 'Summary Metrics', 'Report Preview']);
        });
    }

    private function visitAndCheck(Browser $browser, string $path, array $expectedText): void
    {
        $browser->visit($path)
            ->waitUntil('document.body !== null', 10);

        $this->assertPageContains($browser, $expectedText);
        $this->assertHealthyPage($browser);
    }

    private function loginThroughForm(Browser $browser, string $email, string $password, string $expectedPath): void
    {
        $browser->driver->manage()->deleteAllCookies();

        $browser->visit('/login')
            ->waitUntil("document.querySelector('#email') !== null && document.querySelector('#password') !== null", 10)
            ->script(
                "document.querySelector('#email').value = " . json_encode($email) . ";"
                . "document.querySelector('#password').value = " . json_encode($password) . ";"
                . "document.querySelector('form').submit();"
            );

        $browser
            ->waitForLocation($expectedPath, 10)
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
            $this->assertStringContainsString($expected, $text);
        }
    }

}
