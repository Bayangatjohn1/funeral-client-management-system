<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MasterCaseTabsTest extends DuskTestCase
{
    public function test_clear_filter_stays_in_the_compact_master_toolbar_row(): void
    {
        $systemAdmin = User::where('email', 'admin@funeral.test')->firstOrFail();

        $this->browse(function (Browser $browser) use ($systemAdmin) {
            $browser->loginAs($systemAdmin)
                ->visit('/admin/cases?date_preset=THIS_MONTH')
                ->waitFor('.master-records-page .case-compact-actions');

            foreach ([1900, 1440] as $width) {
                $browser->resize($width, 925);
                $positions = $browser->script('const form=document.querySelector(".master-records-page form.case-record-filters"); const date=form.querySelector(".case-compact-date-filter").getBoundingClientRect(); const clear=form.querySelector(".case-compact-actions").getBoundingClientRect(); return {dateTop:date.top, clearTop:clear.top, formHeight:form.getBoundingClientRect().height, formWidth:form.getBoundingClientRect().width, scrollWidth:form.scrollWidth}')[0];
                $this->assertEqualsWithDelta($positions['dateTop'], $positions['clearTop'], 3);
                $this->assertLessThan(90, $positions['formHeight']);
                $this->assertLessThanOrEqual($positions['formWidth'] + 2, $positions['scrollWidth']);
            }
        });
    }

    public function test_staff_and_admin_case_record_values_use_readable_non_bold_weights(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $systemAdmin = User::where('email', 'admin@funeral.test')->firstOrFail();
        $branch = Branch::where('branch_code', 'BR001')->firstOrFail();
        $branchAdmin = User::updateOrCreate(
            ['email' => 'branch-typography@funeral.test'],
            [
                'name' => 'Branch Typography Tester',
                'password' => Hash::make('BranchTypography12345!'),
                'role' => 'admin',
                'admin_scope' => 'branch',
                'branch_id' => $branch->id,
            ]
        );

        foreach ([[$staff, '/funeral-cases'], [$branchAdmin, '/admin/cases'], [$systemAdmin, '/admin/cases']] as [$user, $url]) {
            $this->browse(function (Browser $browser) use ($user, $url) {
                $browser->loginAs($user)->visit($url)->waitFor('.records-worklist-table tbody .table-primary');
                foreach (['light', 'dark'] as $theme) {
                    $browser->script('document.documentElement.dataset.theme = '.json_encode($theme));
                    $weights = $browser->script('const table=document.querySelector(".records-worklist-table tbody"); return [getComputedStyle(table.querySelector(".table-primary")).fontWeight, getComputedStyle(table.querySelector(".table-secondary")).fontWeight, getComputedStyle(table.querySelector(".table-secondary")).fontSize]')[0];
                    $this->assertSame(['500', '400', '13px'], $weights);
                }
            });
            static::closeAll();
        }
    }

    public function test_master_case_status_tabs_update_without_reloading_for_both_admin_scopes(): void
    {
        $branch = Branch::where('branch_code', 'BR001')->firstOrFail();
        $branchAdmin = User::updateOrCreate(
            ['email' => 'branch-tabs@funeral.test'],
            [
                'name' => 'Branch Tab Tester',
                'password' => Hash::make('BranchTabs12345!'),
                'role' => 'admin',
                'admin_scope' => 'branch',
                'branch_id' => $branch->id,
            ]
        );
        $systemAdmin = User::where('email', 'admin@funeral.test')->firstOrFail();

        foreach ([$branchAdmin, $systemAdmin] as $user) {
            $this->browse(function (Browser $browser) use ($user) {
                $browser->loginAs($user)->visit('/admin/cases')->waitFor('.master-records-page .case-records-tabs');
                $browser->script('window.__masterTabNoReload = true');

                foreach (['DRAFT' => 'Draft', 'ACTIVE' => 'Active', '' => 'All'] as $status => $label) {
                    $browser->click('.master-records-page .case-records-tabs a[href]'.($status ? '[href*="case_status='.$status.'"]' : ':not([href*="case_status="])'))
                        ->waitUntil('document.querySelector(".master-records-page .case-records-tabs .table-quick-tab-active")?.textContent.trim() === '.json_encode($label), 10)
                        ->waitUntil('new URL(location.href).searchParams.get("case_status") === '.json_encode($status ?: null), 10);

                    $this->assertTrue($browser->script('return window.__masterTabNoReload === true')[0]);
                    $browser->assertPresent('.master-records-page .table-system-list');
                }
            });
            static::closeAll();
        }
    }
}
