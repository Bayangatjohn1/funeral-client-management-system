<?php

namespace Tests\Feature;

use App\Models\{Branch,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryEmptyStatePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_directory_views_keep_result_regions_and_disabled_footers(): void
    {
        $branch = Branch::create(['branch_code'=>'BR-DIR-EMPTY','branch_name'=>'Empty directory','address'=>'Test','type'=>'main','is_active'=>true]);
        foreach ([['system','admin.branches.index'],['system','admin.users.index'],['branch','admin.users.index']] as [$scope,$route]) {
            $user = User::factory()->create(['role'=>'admin','admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            $response = $this->actingAs($user)->get(route($route,['q'=>'no-directory-record-matches-this']))->assertOk();
            $dom = new \DOMDocument(); @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
            $xpath = new \DOMXPath($dom);
            $this->assertSame(3,$xpath->query('//*[@data-record-results]')->length);
            $this->assertSame(1,$xpath->query('//*[@data-skeleton-empty]')->length);
            $footers = $xpath->query('//*[@data-record-results="footer"]');
            $this->assertSame(1,$footers->length);
            $this->assertStringContainsString('is-pagination-hidden',$footers->item(0)->getAttribute('class'));
            $this->assertSame(0,$xpath->query('//*[@data-record-results="footer"]/ancestor::*[@data-record-results]')->length);
            foreach ($footers as $footer) {
                $this->assertStringContainsString('0 records',$footer->textContent);
                $this->assertSame(0,$xpath->query('.//a[@href]',$footer)->length);
            }
        }
    }
}
