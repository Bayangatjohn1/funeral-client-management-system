<?php

namespace Tests\Feature;

use App\Models\{Branch,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentUiConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_toolbar_preserves_role_scope_and_existing_control_values(): void
    {
        $main = Branch::create(['branch_code'=>'PM-UI-MAIN','branch_name'=>'Main','address'=>'Test','type'=>'main','is_active'=>true]);
        $other = Branch::create(['branch_code'=>'PM-UI-OTHER','branch_name'=>'Other','address'=>'Test','type'=>'satellite','is_active'=>true]);
        foreach ([['staff',null,$main,'payments.history'],['staff',null,$other,'payments.history'],['admin','branch',$main,'admin.payments.index'],['admin','branch',$other,'admin.payments.index'],['admin','system',$main,'admin.payments.index']] as [$role,$scope,$branch,$route]) {
            $actor = User::factory()->create(['role'=>$role,'admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            $response = $this->actingAs($actor)->get(route($route,['q'=>'Example','payment_status'=>'PARTIAL','date_preset'=>'month','sort'=>'asc','per_page'=>10]))->assertOk();
            $dom = new \DOMDocument(); @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent()); $xpath = new \DOMXPath($dom);
            $this->assertSame($role === 'admin' ? 1 : 0, $xpath->query('//a[@data-pm-print-preview]')->length);
            $this->actingAs($actor)->get(route('payments.monitoring.print'))->assertStatus($role === 'admin' ? 200 : 403);
            $response->assertSeeInOrder(['.pm-record-head { display:none; }', '--rs-item-hover:var(--record-row-hover)', '</head>', 'id="pmFilterForm"'], false);
            $headerActions = '//*[contains(concat(" ", normalize-space(@class), " "), " panel-page-header__actions ")]';
            $this->assertSame(1, $xpath->query($headerActions.'//a[@data-pm-section-action and contains(@class, "pm-header-action--corrections") and contains(@href, "tab=corrections")]')->length);
            $this->assertSame(1, $xpath->query($headerActions.'//*[@data-workflow-pending]')->length);
            $this->assertSame(0, $xpath->query($headerActions.'//*[@data-notification-toggle]')->length);
            $this->assertSame(0, $xpath->query('//nav[@aria-label="Payment monitoring sections"]')->length);
            $queue = $this->actingAs($actor)->get(route($route, ['tab' => 'corrections']))->assertOk()
                ->assertSee('Back to Payment Records')->assertSee('id="paymentCorrectionQueueTitle"', false)
                ->assertSee('name="correction_type"', false)->assertSee('name="correction_period"', false);
            if ($scope === 'branch') {
                $queue->assertSee('name="correction_requester_id"', false);
            } else {
                $queue->assertDontSee('name="correction_requester_id"', false);
            }
            if ($scope === 'system') {
                $queue->assertSee('name="correction_branch_id"', false)->assertSee('name="correction_urgency"', false);
            } else {
                $queue->assertDontSee('name="correction_branch_id"', false)->assertDontSee('name="correction_urgency"', false);
            }
            $this->assertSame(1, $xpath->query('//form[@id="pmFilterForm"]//input[@name="q"][contains(@class,"shared-record-search-input")]/parent::*[contains(@class,"shared-record-search")]')->length);
            $this->assertSame($scope==='system'?1:0,$xpath->query('//form[@id="pmFilterForm"]//select[@name="branch_id"]')->length);
            $this->assertSame(1,$xpath->query('//details[contains(@class,"pm-more-filters")]//select[@name="payment_status"]')->length);
            $this->assertSame(1,$xpath->query('//form[@id="pmFilterForm"]//select[@name="sort"]/option[@value="asc"][@selected]')->length);
            $this->assertSame('2',trim($xpath->query('//summary/span[@class="pm-filter-count"]')->item(0)->textContent));
            $searchOnly = $this->actingAs($actor)->get(route($route,['q'=>'Example']))->assertOk();
            $searchDom = new \DOMDocument(); @$searchDom->loadHTML($searchOnly->getContent());
            $this->assertSame(0,(new \DOMXPath($searchDom))->query('//summary/span[@class="pm-filter-count"]')->length);
            $clearLink = $xpath->query('//a[@data-pm-clear-filters]')->item(0);
            parse_str(parse_url($clearLink->getAttribute('href'),PHP_URL_QUERY),$clearQuery);
            $this->assertSame('Example',$clearQuery['q']);
            $this->assertArrayNotHasKey('payment_status',$clearQuery);
            $this->assertArrayNotHasKey('date_preset',$clearQuery);
            $this->assertSame('asc',$clearQuery['sort']);
            $this->assertSame('10',$clearQuery['per_page']);
            $this->assertTrue((new \DOMXPath($searchDom))->query('//a[@data-pm-clear-filters]')->item(0)->hasAttribute('hidden'));
            $this->actingAs($actor)->get($clearLink->getAttribute('href'))->assertOk()->assertViewHas('q','Example');
            $this->assertSame(4,$xpath->query('//select[@name="per_page"]/option')->length);
            $this->assertSame(1,$xpath->query('//*[@id="pmDateModal"][@popover="manual"]')->length);
            $this->assertSame(0,$xpath->query('//*[@id="pmDateModal"]//*[@aria-modal="true"]')->length);
            $this->assertStringContainsString('Cases per page',$response->getContent());
            $this->assertStringContainsString('Clear filters',$response->getContent());
            $response->assertViewHas('paymentCases',fn($cases)=>$cases->perPage()===10);
            $this->actingAs($actor)->get(route($route,['tab'=>'transactions','per_page'=>50]))->assertOk()->assertViewHas('transactionCases',fn($cases)=>$cases->perPage()===50);
        }
        $owner = User::factory()->create(['role'=>'owner','branch_id'=>null,'is_active'=>true]);
        foreach (['payments.history','admin.payments.index','admin.payment-monitoring'] as $route) $this->actingAs($owner)->get(route($route))->assertRedirect(route('owner.analytics'));
    }
}


