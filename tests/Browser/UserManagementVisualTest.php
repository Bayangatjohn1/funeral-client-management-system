<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UserManagementVisualTest extends DuskTestCase
{
    public function test_system_admin_filters_and_information_modal_work_in_both_themes(): void
    {
        $admin = User::where('email', 'admin@funeral.test')->firstOrFail();
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();

        $this->browse(function (Browser $browser) use ($admin, $staff) {
            $browser->loginAs($admin)->visit('/admin/users')
                ->waitFor('.user-more-filters > summary')
                ->assertVisible('.user-directory-stats .user-directory-stat')
                ->assertMissing('.user-page-size')
                ->click('.user-more-filters > summary')
                ->assertVisible('#user-filter-role')
                ->assertVisible('#user-filter-status');

            foreach (['light', 'dark'] as $theme) {
                $browser->script('document.documentElement.dataset.theme = '.json_encode($theme));
                $browser->pause(300);
                $surface = $browser->script('return {theme:document.documentElement.dataset.theme,body:document.body.className,toolbar:getComputedStyle(document.querySelector(".directory-page .table-system-toolbar")).backgroundColor,card:getComputedStyle(document.querySelector(".directory-page .directory-item-card")).backgroundColor,toggleInToolbar:!!document.querySelector(".directory-page .table-system-toolbar > .user-directory-view-toggle")};')[0];
                $this->assertSame($theme, $surface['theme']);
                $this->assertStringContainsString('system-admin-ui', $surface['body']);
                $this->assertTrue($surface['toggleInToolbar']);
                $this->assertSame($theme === 'light' ? 'rgb(255, 255, 255)' : 'rgb(17, 34, 24)', $surface['toolbar'], json_encode($surface));
                $this->assertSame($theme === 'light' ? 'rgb(255, 255, 255)' : 'rgb(17, 34, 24)', $surface['card']);
                $neutral = $browser->script('const wrap=document.querySelector(".user-filters-system .table-toolbar-input-wrap"); const badge=document.querySelector(".directory-card-view .user-card-status"); return {search:getComputedStyle(wrap).backgroundColor,badgeBorder:getComputedStyle(badge).borderTopWidth,badgeBackground:getComputedStyle(badge).backgroundColor};')[0];
                $this->assertSame($theme === 'light' ? 'rgb(255, 255, 255)' : 'rgb(17, 34, 24)', $neutral['search']);
                $this->assertSame('0px', $neutral['badgeBorder']);
                $this->assertSame('rgba(0, 0, 0, 0)', $neutral['badgeBackground']);
                $browser->resize(1440, 900);
                $filterWidths = $browser->script('return {search:document.querySelector(".user-filters-system [name=q]").getBoundingClientRect().width,branch:document.querySelector(".user-filters-system [name=branch_id]").getBoundingClientRect().width};')[0];
                $this->assertGreaterThan($filterWidths['branch'], $filterWidths['search']);
                foreach ([1440, 390] as $width) {
                    $browser->resize($width, 900)->pause(100);
                    $layout = $browser->script('const panel=document.querySelector(".user-more-filters-panel").getBoundingClientRect(); return {left:panel.left,right:panel.right,width:innerWidth};')[0];
                    $this->assertGreaterThanOrEqual(0, $layout['left']);
                    $this->assertLessThanOrEqual($layout['width'] + 1, $layout['right']);
                }
            }

            $browser->resize(1440, 900)
                ->select('#user-filter-role', 'staff')
                ->waitUntil('new URLSearchParams(location.search).get("role") === "staff"')
                ->assertSeeIn('.user-more-filters-count', '1')
                ->assertVisible('.user-filter-clear');

            foreach ([1440, 390] as $width) {
                $browser->resize($width, 900)->pause(100);
                $layout = $browser->script('return {scrollWidth:document.documentElement.scrollWidth,viewport:innerWidth};')[0];
                $this->assertLessThanOrEqual($layout['viewport'] + 1, $layout['scrollWidth']);
            }

            $browser->resize(1440, 900)
                ->assertVisible('.directory-card-view .user-view-button--card .user-view-label')
                ->assertVisible('.directory-card-view .user-card-status')
                ->assertVisible('.directory-card-view .user-directory-avatar')
                ->assertVisible('.directory-card-view .user-directory-card-action-group');
            $actionGap = $browser->script('const group=document.querySelector(".directory-card-view .user-directory-card-action-group"); const edit=group.querySelector("a").getBoundingClientRect(); const status=group.querySelector("button").getBoundingClientRect(); return status.left-edit.right;')[0];
            $this->assertLessThanOrEqual(8, $actionGap);

            $browser->click('.directory-card-view [data-user-status-dialog]')
                ->waitFor('.user-status-dialog[open]')
                ->assertSeeIn('.user-status-dialog[open] h2', 'Deactivate user?')
                ->click('.user-status-dialog[open] [data-user-dialog-close]')
                ->waitUntil('!document.querySelector(".user-status-dialog[open]")')
                ->click('.directory-card-view .user-view-button')
                ->waitFor('.user-information-dialog[open]')
                ->assertVisible('.user-information-dialog[open] .user-information-heading-icon .bi-person-vcard')
                ->assertSeeIn('.user-information-dialog[open]', 'Date Created');

            $backdrop = $browser->script('return getComputedStyle(document.querySelector(".user-information-dialog[open]"),"::backdrop").backgroundColor;')[0];
            $this->assertSame('rgba(15, 23, 42, 0.72)', $backdrop);

            $browser->click('.user-information-dialog[open] [data-user-status-dialog]')
                ->waitFor('.user-status-dialog[open]')
                ->click('.user-status-dialog[open] [data-user-dialog-close]')
                ->waitUntil('!document.querySelector(".user-status-dialog[open]")')
                ->assertVisible('.user-information-dialog[open] .user-information-heading-icon .bi-person-vcard')
                ->click('.user-information-dialog[open] [data-user-dialog-close]')
                ->click('[title="Table view"]')
                ->waitFor('.directory-table-view .user-table-actions')
                ->assertVisible('.directory-table-view .user-table-actions .user-view-button')
                ->click('.directory-table-view [data-user-status-dialog]')
                ->waitFor('.user-status-dialog[open]')
                ->click('.user-status-dialog[open] [data-user-dialog-close]')
                ->waitUntil('!document.querySelector(".user-status-dialog[open]")')
                ->click('.user-filter-clear')
                ->waitUntil('!new URLSearchParams(location.search).has("role")')
                ->assertMissing('.user-more-filters-count')
                ->pause(300)
                ->visit('/admin/users/create')
                ->assertVisible('.user-page-title-icon .bi-person-plus')
                ->assertVisible('.user-create-head .user-form-heading-icon')
                ->assertVisible('#generatePassword');
            $createHeader = $browser->script('return getComputedStyle(document.querySelector(".user-create-head")).backgroundColor;')[0];
            $this->assertSame('rgb(27, 67, 50)', $createHeader);
            $browser->resize(390, 900);
            $createWidth = $browser->script('return {scroll:document.documentElement.scrollWidth,viewport:innerWidth};')[0];
            $this->assertLessThanOrEqual($createWidth['viewport'] + 1, $createWidth['scroll']);

            $browser->resize(1440, 900)
                ->visit('/admin/users/'.$staff->id.'/edit')
                ->assertVisible('.user-page-title-icon .bi-pencil-square')
                ->assertVisible('.user-edit-page .user-form-heading-icon')
                ->assertVisible('.user-edit-page [name=password_confirmation]');
            $browser->resize(390, 900);
            $editWidth = $browser->script('return {scroll:document.documentElement.scrollWidth,viewport:innerWidth};')[0];
            $this->assertLessThanOrEqual($editWidth['viewport'] + 1, $editWidth['scroll']);
        });
    }
}
