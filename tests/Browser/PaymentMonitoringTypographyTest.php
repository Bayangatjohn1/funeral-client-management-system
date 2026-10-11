<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PaymentMonitoringTypographyTest extends DuskTestCase
{
    public function test_payment_case_and_summary_values_are_not_bold_in_both_themes(): void
    {
        $branch = Branch::where('branch_code', 'BR001')->firstOrFail();
        $branchAdmin = User::updateOrCreate(
            ['email' => 'branch-payment-type@funeral.test'],
            [
                'name' => 'Branch Payment Typography Tester',
                'password' => Hash::make('BranchPaymentType12345!'),
                'role' => 'admin',
                'admin_scope' => 'branch',
                'branch_id' => $branch->id,
            ]
        );
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $systemAdmin = User::where('email', 'admin@funeral.test')->firstOrFail();

        foreach ([[$staff, '/payments/history'], [$branchAdmin, '/admin/payments'], [$systemAdmin, '/admin/payments']] as [$user, $url]) {
            $this->browse(function (Browser $browser) use ($user, $url) {
                $browser->loginAs($user)->visit($url)->waitFor('.payment-monitoring-page .pm-case-row');
                foreach (['light', 'dark'] as $theme) {
                    $browser->script('document.documentElement.dataset.theme = '.json_encode($theme));
                    $weights = $browser->script('const page=document.querySelector(".payment-monitoring-page"); return [".pm-case-row .pm-case", ".pm-case-row .pm-row-title", ".pm-case-row .pm-row-date", ".pm-status", ".pm-summary-detail .pm-summary-stat strong"].map(selector => getComputedStyle(page.querySelector(selector)).fontWeight)')[0];
                    $this->assertSame(['500', '500', '500', '500', '500'], $weights);
                }

                $browser->click('.pm-case-item [data-pm-open-transactions-modal]')
                    ->waitFor('.pm-transactions-backdrop[aria-hidden="false"]');
                foreach (['light', 'dark'] as $theme) {
                    $browser->script('document.documentElement.dataset.theme = '.json_encode($theme));
                    $weights = $browser->script('const modal=document.querySelector(".pm-transactions-backdrop[aria-hidden=false]"); return [".pm-summary-stat strong", ".pm-txn-rec", ".pm-txn-amt"].map(selector => { const item=modal.querySelector(selector); return item ? getComputedStyle(item).fontWeight : null; })')[0];
                    $this->assertSame('500', $weights[0]);
                    $this->assertContains($weights[1], ['500', null]);
                    $this->assertContains($weights[2], ['500', null]);
                }
            });
            static::closeAll();
        }
    }
}
