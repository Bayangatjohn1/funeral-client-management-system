<?php
namespace Tests\Feature;
use App\Models\{Branch,User,CasketCatalog,HearseCatalog,FreebieCatalog,AddOnCatalog};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CatalogModalSaveResponseTest extends TestCase
{
    use RefreshDatabase;
    public function test_modal_create_and_update_return_only_the_success_handoff(): void
    {
        $branch=Branch::create(['branch_code'=>'BR001','branch_name'=>'Main Branch','is_active'=>true]);
        $admin=User::factory()->create(['role'=>'admin','admin_scope'=>'system','branch_id'=>$branch->id,'is_active'=>true]);
        $this->actingAs($admin);
        $catalogs=[
            ['casket-catalogs',CasketCatalog::class,['name'=>'Save Test Casket','standard_price'=>20000,'type_or_material'=>'Metal']],
            ['hearse-catalogs',HearseCatalog::class,['name'=>'Save Test Coach','reference_value'=>5000]],
            ['freebie-catalogs',FreebieCatalog::class,['name'=>'Save Test Book','default_unit'=>'item']],
            ['add-on-catalogs',AddOnCatalog::class,['name'=>'Save Test Tent','category'=>'General','price'=>1000,'unit'=>'item']],
        ];
        foreach($catalogs as [$route,$model,$input]) {
            $input['_service_modal']='1';
            $input['return_to']=route('admin.service-management.index');
            $this->post(route('admin.'.$route.'.store'),$input)->assertOk()->assertViewIs('admin.service-management.partials.saved-frame')->assertSee('service-catalog:saved')->assertDontSee('data-service-hub',false);
            $record=$model::where('name',$input['name'])->firstOrFail();
            $this->put(route('admin.'.$route.'.update',$record),$input)->assertOk()->assertViewIs('admin.service-management.partials.saved-frame')->assertDontSee('Service Management');
        }
    }
    public function test_invalid_modal_casket_keeps_normal_laravel_validation_redirect(): void
    {
        $branch=Branch::create(['branch_code'=>'BR001','branch_name'=>'Main Branch','is_active'=>true]);
        $admin=User::factory()->create(['role'=>'admin','admin_scope'=>'system','branch_id'=>$branch->id,'is_active'=>true]);
        $this->actingAs($admin)->from(route('admin.casket-catalogs.create',['modal'=>1,'service_form'=>1]))
            ->post(route('admin.casket-catalogs.store'),['_service_modal'=>1,'name'=>'','standard_price'=>0])
            ->assertRedirect()->assertSessionHasErrors(['name','standard_price']);
    }
}

