<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BranchManagementModalTest extends DuskTestCase
{
    public function test_add_branch_form_is_readable_and_usable_in_both_themes(): void
    {
        $admin = User::where('email', 'admin@funeral.test')->firstOrFail();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)->visit('/admin/branches')
                ->waitFor('#openBranchCreateModal')
                ->click('#openBranchCreateModal')
                ->waitUntil('document.querySelector("#branchCreateModalOverlay").open')
                ->assertVisible('#branch_create_name')
                ->assertVisible('#branch_create_address')
                ->assertAttribute('#branch_create_code', 'readonly', 'true');

            foreach (['light', 'dark'] as $theme) {
                $browser->script('document.documentElement.dataset.theme = '.json_encode($theme));
                foreach ([1440, 390] as $width) {
                    $browser->resize($width, 900)->pause(150);
                    $layout = $browser->script('const sheet=document.querySelector("#branchCreateModalSheet").getBoundingClientRect(); const name=document.querySelector("#branch_create_name").getBoundingClientRect(); const address=document.querySelector("#branch_create_address").getBoundingClientRect(); return {sheetLeft:sheet.left,sheetRight:sheet.right,nameWidth:name.width,addressWidth:address.width,viewport:innerWidth,background:getComputedStyle(document.querySelector("#branchCreateModalOverlay")).backgroundColor};')[0];
                    $this->assertGreaterThanOrEqual(0, $layout['sheetLeft']);
                    $this->assertLessThanOrEqual($layout['viewport'], $layout['sheetRight']);
                    $this->assertGreaterThan(120, $layout['nameWidth']);
                    $this->assertGreaterThan(250, $layout['addressWidth']);
                    $this->assertSame('rgba(15, 23, 42, 0.72)', $layout['background']);
                }
            }

            $browser->click('#branch_create_is_active')
                ->assertSeeIn('#branch-create-status-pill', 'Inactive')
                ->click('#branchCreateModalCancel')
                ->waitUntil('!document.querySelector("#branchCreateModalOverlay").open');
        });
    }

    public function test_card_footer_actions_use_the_existing_branch_modal(): void
    {
        $admin = User::where('email', 'admin@funeral.test')->firstOrFail();
        Branch::firstOrCreate(['branch_code' => 'BR011'], ['branch_name' => 'Footer Action Branch', 'address' => 'Test Address', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)->visit('/admin/branches?q=Footer+Action+Branch')
                ->waitFor('.directory-item-card[data-live-search-title="Footer Action Branch"]')
                ->click('.directory-item-card[data-live-search-title="Footer Action Branch"] [data-branch-card-action="edit"]')
                ->waitUntil('document.querySelector("#branchModalOverlay").open')
                ->waitFor('#branchModalContent #branchEditForm')
                ->assertInputValue('#branchModalContent input[name="branch_name"]', 'Footer Action Branch')
                ->click('#branchModalContent .branch-modal-cancel')
                ->waitUntil('!document.querySelector("#branchModalOverlay").open')
                ->click('.directory-item-card[data-live-search-title="Footer Action Branch"] .open-branch-view')
                ->waitForText('Branch details')
                ->click('#branchModalContent [data-branch-edit]')
                ->waitFor('#branchModalContent #branchEditForm')
                ->click('#branchModalContent .branch-modal-cancel')
                ->waitForText('Branch details')
                ->assertMissing('#branchModalContent #branchEditForm')
                ->click('#branchModalContent [data-branch-close]')
                ->waitUntil('!document.querySelector("#branchModalOverlay").open')
                ->click('.directory-item-card[data-live-search-title="Footer Action Branch"] [data-branch-card-action="status"]')
                ->waitForText('Disable this branch?')
                ->assertPresent('#branchModalContent form');
        });
    }

    public function test_branch_directory_visual_foundation_and_view_toggle_in_both_themes(): void
    {
        $admin = User::where('email', 'admin@funeral.test')->firstOrFail();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)->visit('/admin/branches')->waitFor('.branch-kpi-card')
                ->assertPresent('.branch-card-metric-label');
            $this->assertCount(2, $browser->elements('.branch-kpi-card'));

            foreach (['light', 'dark'] as $theme) {
                $browser->script('document.documentElement.dataset.theme = '.json_encode($theme));
                $browser->pause(250);
                foreach ([1915, 1440, 390] as $width) {
                    $browser->resize($width, 900);
                    $browser->pause(200);
                    $page = $browser->script('const root=document.querySelector(".branch-management-page"); const card=document.querySelector(".branch-kpi-card"); const branch=document.querySelector(".directory-item-card"); const toolbar=document.querySelector(".table-system-toolbar"); const filter=document.querySelector(".table-toolbar-sort"); const kpiClipped=[...document.querySelectorAll(".branch-kpi-card")].some(item=>{const box=item.getBoundingClientRect();return [...item.querySelectorAll(".branch-kpi-card__label,.branch-kpi-card__value,.branch-kpi-card__desc,.branch-kpi-card__action")].some(el=>{const r=el.getBoundingClientRect();return r.right>box.right+1||r.bottom>box.bottom+1||el.scrollWidth>el.clientWidth+1;});}); return {canvas:getComputedStyle(root).backgroundColor,token:getComputedStyle(root).getPropertyValue("--bm-card").trim(),cardToken:getComputedStyle(card).getPropertyValue("--bm-card").trim(),card:getComputedStyle(card).backgroundColor,branch:getComputedStyle(branch).backgroundColor,toolbar:getComputedStyle(toolbar).backgroundColor,filter:getComputedStyle(filter).backgroundColor,overflow:document.documentElement.scrollWidth>innerWidth+1,kpiClipped}')[0];
                    $this->assertSame($theme === 'light' ? 'rgb(220, 232, 224)' : 'rgb(7, 19, 14)', $page['canvas']);
                    $this->assertSame($theme === 'light' ? '#F4F8F5' : '#112218', $page['token']);
                    $this->assertSame($page['token'], $page['cardToken']);
                    $this->assertContains($page['card'], $theme === 'light'
                        ? ['rgb(255, 255, 255)']
                        : ['rgb(17, 34, 24)', 'rgb(27, 53, 39)']);
                    $this->assertSame($theme === 'light' ? 'rgb(255, 255, 255)' : 'rgb(17, 34, 24)', $page['toolbar']);
                    $this->assertContains($page['branch'], $theme === 'light'
                        ? ['rgb(255, 255, 255)']
                        : ['rgb(17, 34, 24)', 'rgb(27, 53, 39)']);
                    $this->assertSame($theme === 'light' ? 'rgb(208, 226, 214)' : 'rgb(20, 38, 29)', $page['filter']);
                    $this->assertFalse($page['overflow']);
                    $this->assertFalse($page['kpiClipped']);
                }
            }

            $browser->resize(1440, 900)
                ->click('.branch-status-segments button[data-branch-status-filter="inactive"]')
                ->waitUntil('new URLSearchParams(location.search).get("status") === "inactive"')
                ->assertPresent('.branch-status-segments button[data-branch-status-filter="inactive"].is-selected')
                ->click('.branch-status-segments button[data-branch-status-filter=""]')
                ->waitUntil('new URLSearchParams(location.search).get("status") === ""')
                ->waitUntil('!!document.querySelector(".branch-management-page")?._x_dataStack?.length')
                ->click('.branch-view-toggle button[title="Table view"]')
                ->waitUntil('document.querySelector(".branch-directory-table-view").offsetParent !== null')
                ->assertPresent('.table-system-table th.table-col-actions')
                ->click('.branch-view-toggle button[title="Grid view"]')
                ->waitUntil('document.querySelector(".branch-directory-card-view").offsetParent !== null')
                ->assertPresent('.directory-item-card .branch-card-view-button');

            $browser->visit('/admin/branches?highlight_branch='.Branch::where('branch_code', 'BR001')->firstOrFail()->id)
                ->assertQueryStringHas('highlight_branch')
                ->assertVisible('.directory-item-card[data-live-search-title="Main Branch"]')
                ->assertVisible('.directory-item-card[data-live-search-title="Branch 2"]')
                ->assertVisible('.directory-item-card[data-live-search-title="Branch 3"]');

            foreach (['light', 'dark'] as $theme) {
                $browser->script('document.documentElement.dataset.theme = '.json_encode($theme));
                $browser->pause(250);
                $highlight = $browser->script('const selected=document.querySelector(".directory-item-card[data-highlighted=true]"); const other=document.querySelector(".directory-item-card[data-highlighted=false]"); return {selected:getComputedStyle(selected).boxShadow,other:getComputedStyle(other).boxShadow,chip:!!document.querySelector(".branch-highlight-pill")};')[0];
                $this->assertNotSame($highlight['other'], $highlight['selected']);
                $this->assertFalse($highlight['chip']);
            }

            $browser->click('.branch-view-toggle button[title="Table view"]')
                ->waitUntil('document.querySelector(".branch-directory-table-view").offsetParent !== null')
                ->assertVisible('.branch-directory-table-view tr[data-live-search-title="Main Branch"]')
                ->assertVisible('.branch-directory-table-view tr[data-live-search-title="Branch 2"]')
                ->assertVisible('.branch-directory-table-view tr[data-live-search-title="Branch 3"]');

            $numericAlignment = $browser->script('return [...document.querySelectorAll(".branch-directory-table-view .table-system-table thead th.table-col-number, .branch-directory-table-view .table-system-table tbody td.table-col-number")].map(cell => getComputedStyle(cell).textAlign);')[0];
            $this->assertNotEmpty($numericAlignment);
            $this->assertSame(['right'], array_values(array_unique($numericAlignment)));

            $highlightedRow = $browser->script('const selected=document.querySelector(".branch-directory-table-view tr[data-highlighted=true] td"); const other=document.querySelector(".branch-directory-table-view tr[data-highlighted=false] td"); return {selected:getComputedStyle(selected).backgroundColor,other:getComputedStyle(other).backgroundColor};')[0];
            $this->assertNotSame($highlightedRow['other'], $highlightedRow['selected']);
            $browser->click('.branch-view-toggle button[title="Grid view"]');
        });
    }

    public function test_unfinished_cases_show_a_plain_english_blocker_in_the_same_modal(): void
    {
        $admin = User::where('email', 'admin@funeral.test')->firstOrFail();
        $branch = Branch::firstOrCreate(
            ['branch_code' => 'BR010'],
            ['branch_name' => 'Busy Test Branch', 'address' => 'Test Address', 'is_active' => true]
        );
        $client = Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Modal Test Client',
            'relationship_to_deceased' => 'Other',
            'contact_number' => '09170000000',
            'valid_id_type' => 'Legacy Record',
            'valid_id_number' => 'MODAL-TEST-010',
            'address' => 'Test Address',
        ]);
        $deceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => 'Modal Test Deceased',
            'address' => 'Test Address',
            'born' => '1960-01-01',
            'died' => '2026-10-01',
            'age' => 66,
        ]);
        $case = FuneralCase::query()->firstOrFail()->replicate();
        $case->branch_id = $branch->id;
        $case->client_id = $client->id;
        $case->deceased_id = $deceased->id;
        $case->case_code = 'FC9010';
        $case->case_status = 'ACTIVE';
        $case->save();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/branches')
                ->waitFor('.open-branch-view[data-code="BR010"]')
                ->click('.open-branch-view[data-code="BR010"]')
                ->click('#branchModalContent [data-branch-status]')
                ->waitForText('This branch cannot be disabled')
                ->assertSeeIn('#branchModalContent', 'Please resolve these cases before disabling this branch.')
                ->assertSeeIn('#branchModalContent', '1 unfinished case')
                ->assertMissing('#branchModalContent form')
                ->click('#branchModalContent [data-branch-back]')
                ->assertSeeIn('#branchModalContent', 'Branch details');
        });
    }

    public function test_branch_eye_opens_one_modal_with_english_disable_explanation(): void
    {
        $admin = User::where('email', 'admin@funeral.test')->firstOrFail();
        Branch::firstOrCreate(
            ['branch_code' => 'BR009'],
            ['branch_name' => 'Modal Test Branch', 'address' => 'Test Address', 'is_active' => true]
        );

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/branches')
                ->waitFor('.open-branch-view[data-code="BR009"]')
                ->assertMissing('.directory-item-card[role="link"]')
                ->click('.open-branch-view[data-code="BR009"]')
                ->waitForText('Branch details')
                ->assertSeeIn('#branchModalContent', 'Modal Test Branch')
                ->assertPresent('#branchModalContent .branch-overview-cases')
                ->assertPresent('#branchModalContent .branch-overview-metrics')
                ->assertDontSee('To disable this branch, there must be no Draft or Active cases')
                ->assertMissing('#branchModalContent .branch-detail-actions [data-branch-close]')
                ->assertPresent('#branchModalContent .branch-detail-actions-group [data-branch-edit]')
                ->click('#branchModalContent [data-branch-status]')
                ->waitForText('Disable this branch?')
                ->assertSeeIn('#branchModalContent', 'Existing records will not be deleted.')
                ->assertPresent('#branchModalContent [data-branch-back]')
                ->assertPresent('#branchModalContent form');

            foreach (['light', 'dark'] as $theme) {
                $browser->script('document.documentElement.dataset.theme = '.json_encode($theme));
                foreach ([1440, 390] as $width) {
                    $browser->resize($width, 900);
                    $modal = $browser->script('const dialog=document.querySelector("#branchModalOverlay"); const box=document.querySelector("#branchModalSheet").getBoundingClientRect(); return {left:box.left,right:box.right,viewport:innerWidth,background:getComputedStyle(dialog).backgroundColor,backdrop:getComputedStyle(dialog,"::backdrop").backgroundColor}')[0];
                    $this->assertGreaterThanOrEqual(-1, $modal['left']);
                    $this->assertLessThanOrEqual($modal['viewport'] + 1, $modal['right']);
                    $this->assertSame('rgba(15, 23, 42, 0.72)', $modal['backdrop']);
                    $this->assertSame('rgba(0, 0, 0, 0)', $modal['background']);
                }
            }
        });
    }

    public function test_table_status_action_cancel_returns_to_branch_details(): void
    {
        $admin = User::where('email', 'admin@funeral.test')->firstOrFail();
        Branch::firstOrCreate(
            ['branch_code' => 'BR012'],
            ['branch_name' => 'Table Action Branch', 'address' => 'Test Address', 'is_active' => true]
        );

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)->visit('/admin/branches?q=Table+Action+Branch')
                ->waitFor('.branch-view-toggle button[title="Table view"]')
                ->click('.branch-view-toggle button[title="Table view"]')
                ->waitFor('tr[data-live-search-title="Table Action Branch"] [data-branch-card-action="status"]')
                ->click('tr[data-live-search-title="Table Action Branch"] [data-branch-card-action="status"]')
                ->waitForText('Disable this branch?')
                ->assertPresent('#branchModalContent .branch-detail-header__icon .bi-slash-circle')
                ->click('#branchModalContent [data-branch-back]')
                ->waitForText('Branch details')
                ->assertPresent('#branchModalContent .branch-detail-header__icon .bi-building')
                ->assertPresent('#branchModalContent [data-branch-status]');
        });
    }
}
