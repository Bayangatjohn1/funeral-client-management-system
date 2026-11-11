<?php

namespace Tests\Feature;

use App\Models\{Branch, Client, Deceased, FuneralCase, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseSummaryDrawerTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $branch = Branch::create(['branch_code'=>'BR001','branch_name'=>'Summary Branch','is_active'=>true]);
        $client = Client::create(['branch_id'=>$branch->id,'full_name'=>'Summary Client','relationship_to_deceased'=>'Other','contact_number'=>'09170000000','address'=>'Test']);
        $deceased = Deceased::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'full_name'=>'Summary Deceased','address'=>'Test','died'=>'2026-04-28','date_of_death'=>'2026-04-28','age'=>60]);
        $case = FuneralCase::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'deceased_id'=>$deceased->id,'case_code'=>'FC0001','service_requested_at'=>'2026-04-28','funeral_service_at'=>'2026-04-29','interment_at'=>'2026-05-01 09:00:00','wake_location'=>'Summary Chapel','case_status'=>'ACTIVE','verification_status'=>'VERIFIED','entry_source'=>'MAIN','payment_status'=>'PARTIAL','total_amount'=>10000,'total_paid'=>1000,'balance_amount'=>9000]);
        return [$branch,$case];
    }

    public function test_summary_reuses_authorized_record_values_and_full_details_remains_available(): void
    {
        [$branch,$case] = $this->fixture();
        foreach ([['staff',null,'funeral-cases.show','funeral-cases.index'],['admin','branch','funeral-cases.show','admin.cases.index'],['admin','system','funeral-cases.show','admin.cases.index'],['owner',null,'owner.cases.show','owner.history']] as [$role,$scope,$show,$index]) {
            $actor = User::factory()->create(['role'=>$role,'admin_scope'=>$scope,'branch_id'=>$role==='owner'?null:$branch->id,'is_active'=>true]);
            $html = $this->actingAs($actor)->get(route($show,[$case,'drawer_summary'=>1]))->assertOk()->getContent();
            $dom = new \DOMDocument(); @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html); $x = new \DOMXPath($dom);
            $summary = $x->query('//*[@id="caseRecordSummary"]')->item(0);
            $this->assertNotNull($summary, $role.' '.$scope);
            foreach (['FC0001','Summary Deceased','Summary Client','09170000000','Summary Chapel','10,000.00','1,000.00','9,000.00','May 01, 2026'] as $value) $this->assertStringContainsString($value,$summary->textContent);
            $this->assertSame(0,$x->query('.//script | .//form | .//table', $summary)->length);
            $this->assertSame(0,$x->query('//*[@id="caseViewContent"]')->length);
            $this->actingAs($actor)->get(route($show,$case))->assertOk()->assertSee('id="caseViewContent"',false)->assertDontSee('id="caseRecordSummary"',false);
            $list = $this->actingAs($actor)->get(route($index))->assertOk();
            $list->assertSee('id="caseSummaryDrawer"',false)->assertSee('data-case-summary-open',false)->assertSee('Open Full Details');
            $tableDom = new \DOMDocument(); @$tableDom->loadHTML('<?xml encoding="utf-8" ?>'.$list->getContent()); $tableXPath = new \DOMXPath($tableDom);
            $table = $tableXPath->query('//table[contains(concat(" ",normalize-space(@class)," ")," records-worklist-table ")]')->item(0);
            $this->assertNotNull($table);
            $headings = array_map(fn ($node) => trim($node->textContent), iterator_to_array($tableXPath->query('.//thead/tr/th',$table)));
            $this->assertSame(['Case ID',...($role==='owner'?['Branch']:[]),'Deceased','Interment Date','Case Status','Payment Status','Remaining Balance','Actions'],$headings);
            $row = $tableXPath->query('.//tbody/tr[@data-record-search-case="FC0001"]',$table)->item(0);
            $this->assertNotNull($row);
            $this->assertSame(count($headings),$tableXPath->query('./td',$row)->length);
            $this->assertSame('Summary Client',$row->getAttribute('data-record-search-client'));
            $this->assertStringContainsString('9,000.00',$row->textContent);
            foreach (['Summary Client','09170000000','10,000.00','1,000.00','Recorded by:'] as $hidden) $this->assertStringNotContainsString($hidden,$row->textContent);
            $this->assertSame('Summary Deceased',trim($tableXPath->query('./td[@data-label="Deceased"]',$row)->item(0)->textContent));
            $this->assertSame('May 01, 2026',trim($tableXPath->query('./td[@data-label="Interment Date"]',$row)->item(0)->textContent));
            $this->actingAs($actor)->get(route($index,['q'=>'Summary Client']))->assertOk()->assertSee('data-record-search-case="FC0001"',false);
            $empty = $this->actingAs($actor)->get(route($index,['q'=>'no-matching-case']))->assertOk();
            $emptyDom = new \DOMDocument(); @$emptyDom->loadHTML($empty->getContent()); $emptyXPath = new \DOMXPath($emptyDom);
            $emptyCell = $emptyXPath->query('//table[contains(@class,"records-worklist-table")]//td[@colspan]')->item(0);
            $this->assertNotNull($emptyCell);
            $this->assertSame((string) count($headings),$emptyCell->getAttribute('colspan'));
        }
    }

    public function test_summary_query_does_not_bypass_branch_or_owner_access(): void
    {
        [$branch,$case] = $this->fixture();
        $other = Branch::create(['branch_code'=>'BR002','branch_name'=>'Other','is_active'=>true]);
        $staff = User::factory()->create(['role'=>'staff','branch_id'=>$other->id,'is_active'=>true]);
        $this->assertContains($this->actingAs($staff)->get(route('funeral-cases.show',[$case,'drawer_summary'=>1]))->status(),[403,404]);
        $owner = User::factory()->create(['role'=>'owner','branch_id'=>null,'is_active'=>true]);
        $case->update(['case_status'=>'DRAFT']);
        $this->actingAs($owner)->get(route('owner.cases.show',[$case,'drawer_summary'=>1]))->assertForbidden();
        $case->update(['case_status'=>'ACTIVE','verification_status'=>'PENDING']);
        $this->actingAs($owner)->get(route('owner.cases.show',[$case,'drawer_summary'=>1]))->assertNotFound();
    }
}
