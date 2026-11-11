<?php

namespace Tests\Feature;

use App\Models\{Branch, User};
use App\Support\UiTextCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorklistAdoptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_actions_move_once_to_the_toolbar_and_preserve_role_choices(): void
    {
        $branch=Branch::create(['branch_code'=>'BR-ACTION','branch_name'=>'Toolbar Fixture','type'=>'main','is_active'=>true]);
        foreach([['admin','system','admin.branches.index'],['admin','system','admin.users.index'],['admin','branch','admin.users.index'],['staff',null,'payments.history'],['admin','system','admin.payments.index'],['admin','branch','admin.payments.index']] as [$role,$scope,$route]){
            $actor=User::factory()->create(['role'=>$role,'admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            $response=$this->actingAs($actor)->get(route($route,['q'=>'Toolbar','per_page'=>10]))->assertOk();
            $dom=new \DOMDocument();@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());$x=new \DOMXPath($dom);
            $action=$x->query('//*[contains(concat(" ",normalize-space(@class)," ")," record-toolbar-action ")]/*[self::a or self::button]');
            $this->assertSame(1,$action->length,$route);
            $this->assertSame(1,$x->query('ancestor::form[@data-worklist-form or @id="pmFilterForm"]',$action->item(0))->length);
            $this->assertSame(0,$x->query('//*[contains(@class,"panel-page-header")]//*[contains(@class,"reference-page-action")]')->length);
            if(str_contains($route,'payments')){
                $this->assertSame($role==='staff'?1:0,$x->query('//*[@id="openPaymentForm"]')->length);
                $this->assertSame($role==='staff'?0:1,$x->query('//*[@data-pm-print-preview]')->length);
            }elseif($route==='admin.users.index'){
                $this->assertSame($scope==='system'?1:0,$x->query('//*[@data-create-role-trigger]')->length);
                $this->assertStringContainsString('return_to=', $action->item(0)->getAttribute('href'));
            }else{
                $this->assertSame('openBranchCreateModal',$action->item(0)->getAttribute('id'));
                $this->assertSame('button',$action->item(0)->getAttribute('type'));
            }
        }
        $corrections=$this->actingAs(User::factory()->create(['role'=>'admin','admin_scope'=>'branch','branch_id'=>$branch->id,'is_active'=>true]))
            ->get(route('admin.payments.index',['tab'=>'corrections']))->assertOk();
        $dom=new \DOMDocument();@$dom->loadHTML($corrections->getContent());
        $this->assertSame(0,(new \DOMXPath($dom))->query('//*[@data-pm-print-preview]')->length);
    }

    public function test_directory_and_audit_keep_role_scopes_and_one_detached_footer(): void
    {
        $branch=Branch::create(['branch_code'=>'BR-UI','branch_name'=>'Local Branch','is_active'=>true]);
        $other=Branch::create(['branch_code'=>'BR-OTHER','branch_name'=>'Other Branch','is_active'=>true]);
        User::factory()->create(['name'=>'Scoped Staff','role'=>'staff','branch_id'=>$branch->id]);
        User::factory()->create(['name'=>'Remote Staff','role'=>'staff','branch_id'=>$other->id]);
        foreach(['system','branch']as$scope){
            $actor=User::factory()->create(['role'=>'admin','admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            foreach(['admin.users.index','admin.audit-logs.index',...($scope==='system'?['admin.branches.index']:[])]as$route){
                $response=$this->actingAs($actor)->get(route($route))->assertOk();
                $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());$x=new \DOMXPath($dom);
                $this->assertSame(1,$x->query('//*[@data-record-results="footer"]')->length);
                if($route!=='admin.audit-logs.index')$this->assertStringContainsString('is-pagination-hidden',$x->query('//*[@data-record-results="footer"]')->item(0)?->getAttribute('class')??'');
                $this->assertSame(0,$x->query('//*[@data-record-results="footer"]/ancestor::*[@data-record-results or contains(@class,"table-system-wrap")]')->length);
                $this->assertSame(1,$x->query('//form[@data-worklist-form]')->length);
                if($scope==='branch')$this->assertSame(0,$x->query('//form[@data-worklist-form]//select[@name="branch_id"]')->length);
                if($route==='admin.users.index'&&$scope==='branch'){
                    $response->assertSee('Scoped Staff')->assertDontSee('Remote Staff');
                    $this->actingAs($actor)->get(route($route,['branch_id'=>$other->id,'q'=>'Remote Staff']))->assertDontSee('data-worklist-name="Remote Staff"',false);
                }
            }
        }
    }

    public function test_directory_pagination_appears_only_after_twenty_five_matching_records(): void
    {
        $main=Branch::create(['branch_code'=>'BR-PAGE-00','branch_name'=>'Paging Main','address'=>'Test','is_active'=>true]);
        for($number=1;$number<26;$number++)Branch::create(['branch_code'=>sprintf('BR-PAGE-%02d',$number),'branch_name'=>sprintf('Paging Branch %02d',$number),'address'=>'Test','is_active'=>true]);
        $actor=User::factory()->create(['role'=>'admin','admin_scope'=>'system','branch_id'=>$main->id,'is_active'=>true]);
        for($number=1;$number<=26;$number++)User::factory()->create(['name'=>sprintf('Paging User %02d',$number),'email'=>sprintf('paging%02d@example.com',$number),'role'=>'staff','branch_id'=>$main->id,'is_active'=>true]);

        foreach(['admin.branches.index','admin.users.index']as$route){
            $html=$this->actingAs($actor)->get(route($route))->assertOk()->getContent();
            $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);$x=new \DOMXPath($dom);
            $footer=$x->query('//*[@data-record-results="footer"]')->item(0);
            $this->assertNotNull($footer);
            $this->assertStringNotContainsString('is-pagination-hidden',$footer->getAttribute('class'));
            $this->assertGreaterThan(0,$x->query('.//a[@href]',$footer)->length);

            $short=$this->actingAs($actor)->get(route($route,['q'=>$route==='admin.branches.index'?'Paging Branch 01':'Paging User 01']))->assertOk()->getContent();
            $shortDom=new \DOMDocument;@$shortDom->loadHTML('<?xml encoding="utf-8" ?>'.$short);$shortXPath=new \DOMXPath($shortDom);
            $this->assertStringContainsString('is-pagination-hidden',$shortXPath->query('//*[@data-record-results="footer"]')->item(0)->getAttribute('class'));
        }
    }

    public function test_user_status_actions_render_their_confirmation_forms_for_each_admin_scope(): void
    {
        $branch=Branch::create(['branch_code'=>'BR-STATUS','branch_name'=>'Status Branch','is_active'=>true]);
        $staff=User::factory()->create(['name'=>'Status Staff','role'=>'staff','branch_id'=>$branch->id,'is_active'=>true]);

        foreach(['system','branch'] as $scope){
            $actor=User::factory()->create(['role'=>'admin','admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            $html=$this->actingAs($actor)->get(route('admin.users.index'))->assertOk()->getContent();
            $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);$x=new \DOMXPath($dom);
            $dialogId='user-status-'.$staff->id;
            $this->assertSame(1,$x->query("//dialog[@id='$dialogId']")->length);
            $this->assertGreaterThan(0,$x->query("//*[@data-user-status-dialog='$dialogId']")->length);
            $form=$x->query("//dialog[@id='$dialogId']//form[@data-user-status-form]")->item(0);
            $this->assertNotNull($form);
            $this->assertStringContainsString('/admin/users/'.$staff->id.'/toggle-active',$form->getAttribute('action'));
            $this->assertSame('PATCH',$x->query(".//input[@name='_method']",$form)->item(0)?->getAttribute('value'));
        }
    }

    public function test_report_filters_use_branch_before_date_and_analytics_has_no_date_tabs(): void
    {
        $branch=Branch::create(['branch_code'=>'BR-REPORT','branch_name'=>'Reports','is_active'=>true]);
        foreach([['admin','system'],['admin','branch'],['owner',null],['staff',null]]as[$role,$scope]){
            $actor=User::factory()->create(['role'=>$role,'admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            if($role==='staff'){$this->actingAs($actor)->get(route('reports.index'))->assertForbidden();continue;}
            $html=$this->actingAs($actor)->get(route('reports.index',['report_type'=>'sales']))->assertOk()->getContent();
            $this->assertLessThan(strpos($html,'id="reportsHeaderDatePreset"'),strpos($html,'id="reportsHeaderBranch"'));
            $this->assertStringContainsString('popover="manual" data-report-date-panel',$html);
            if($role==='staff')continue;
            $html=$this->actingAs($actor)->get(route($role==='owner'?'owner.analytics':'reports.analytics'))->assertOk()->getContent();
            $this->assertStringNotContainsString('id="analyticsDatePresets"',$html);
            $this->assertStringContainsString('id="analyticsHeaderRange"',$html);
            if($scope!=='branch')$this->assertLessThan(strpos($html,'id="analyticsHeaderRange"'),strpos($html,'id="analyticsHeaderBranch"'));
        }
    }

    public function test_branch_create_and_edit_persist_word_initials_without_changing_codes(): void
    {
        $branch=Branch::create(['branch_code'=>'BR001','branch_name'=>'Main Branch','is_active'=>true]);
        $actor=User::factory()->create(['role'=>'admin','admin_scope'=>'system','branch_id'=>$branch->id,'is_active'=>true]);
        $this->actingAs($actor)->post(route('admin.branches.store'),['branch_name'=>'san juan','address'=>'purok uno, san juan','is_active'=>1])->assertSessionHasNoErrors()->assertRedirect();
        $created=Branch::where('branch_name','San Juan')->firstOrFail();$code=$created->branch_code;
        $this->actingAs($actor)->put(route('admin.branches.update',$created),['branch_name'=>'san pedro','address'=>'barangay dos, BGC city','is_active'=>1])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('San Pedro',$created->fresh()->branch_name);
        $this->assertSame('Barangay Dos, BGC City',$created->fresh()->address);
        $this->assertSame($code,$created->fresh()->branch_code);
        $this->assertSame("De La Cruz O'Neil BGC McArthur",UiTextCase::words(" de la cruz o'neil BGC McArthur "));
    }

    public function test_user_attribute_preparation_persists_names_and_address_but_preserves_email(): void
    {
        $controller=new \App\Http\Controllers\Admin\UserController;
        $prepare=new \ReflectionMethod($controller,'prepareValidatedUserAttributes');
        $attributes=$prepare->invoke($controller,['first_name'=>'juan','middle_name'=>'de la','last_name'=>'cruz','address'=>'purok uno, BGC city','email'=>'Juan.Cruz@example.com','role'=>'staff']);
        $this->assertSame('De La',$attributes['middle_name']);
        $attributes=array_filter($attributes,fn($value,$key)=>\Illuminate\Support\Facades\Schema::hasColumn('users',$key),ARRAY_FILTER_USE_BOTH);
        $user=User::factory()->create($attributes);
        $this->assertSame('Juan De La Cruz',$user->fresh()->name);
        $this->assertSame('Purok Uno, BGC City',$user->fresh()->address);
        $this->assertSame('Juan.Cruz@example.com',$user->fresh()->email);
    }
}

