<?php

namespace Tests\Feature;

use App\Models\{Branch, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordsEmptyStatePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_case_and_payment_results_keep_non_navigable_footers_across_roles(): void
    {
        $branch = Branch::create(['branch_code'=>'BR-EMPTY','branch_name'=>'Empty Results','address'=>'Test','type'=>'main','is_active'=>true]);
        foreach ([['staff',null,'funeral-cases.index','payments.history'],['admin','branch','admin.cases.index','admin.payment-monitoring'],['admin','system','admin.cases.index','admin.payment-monitoring'],['owner',null,'owner.history',null]] as [$role,$scope,$cases,$payments]) {
            $user = User::factory()->create(['role'=>$role,'admin_scope'=>$scope,'branch_id'=>$role==='owner'?null:$branch->id,'is_active'=>true]);
            $routes = [[route($cases,['q'=>'no matching records','page'=>5]),false]];
            if ($payments) {
                $routes[] = [route($payments,['q'=>'no matching records','page'=>5]),true];
                $routes[] = [route($payments,['q'=>'no matching records','tab'=>'transactions','transactions_page'=>5]),true];
            }
            foreach ($routes as [$url,$payment]) {
                $response = $this->followingRedirects()->actingAs($user)->get($url)->assertOk();
                $dom = new \DOMDocument();
                @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
                $xpath = new \DOMXPath($dom);
                $footer = $payment
                    ? "//div[contains(concat(' ',normalize-space(@class),' '),' pm-foot ')]"
                    : "//div[contains(concat(' ',normalize-space(@class),' '),' table-system-pagination ')]";
                $nodes = $xpath->query($footer);
                $this->assertSame(1,$nodes->length,$url);
                $this->assertStringContainsString('0 records',$nodes->item(0)->textContent,$url);
                $this->assertSame(0,$xpath->query('.//a[@href]',$nodes->item(0))->length,$url);
                $this->assertSame(5,$xpath->query('.//*[@aria-disabled="true"]',$nodes->item(0))->length,$url);
            }
        }
    }
}
