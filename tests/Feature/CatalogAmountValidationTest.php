<?php
namespace Tests\Feature;
use App\Models\{Branch,User,Package,CasketCatalog,HearseCatalog,AddOnCatalog};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CatalogAmountValidationTest extends TestCase
{
    use RefreshDatabase;
    public function test_oversized_and_overprecise_amounts_are_rejected_on_create_and_update(): void
    {
        $branch = Branch::create(['branch_code'=>'BR001','branch_name'=>'Main Branch','is_active'=>true]);
        $admin = User::factory()->create(['role'=>'admin','admin_scope'=>'system','branch_id'=>$branch->id,'is_active'=>true]);
        $this->actingAs($admin);
        $catalogs = [
            ['packages',Package::class,'price'],
            ['casket-catalogs',CasketCatalog::class,'standard_price'],
            ['hearse-catalogs',HearseCatalog::class,'reference_value'],
            ['add-on-catalogs',AddOnCatalog::class,'price'],
        ];
        foreach ($catalogs as [$route,$model,$field]) {
            $record = $model::create(['name'=>'Existing '.$route,$field=>5000,'is_active'=>true]);
            $beforeCount = $model::count();
            foreach (['123123123123123','123.456'] as $amount) {
                $payload = ['name'=>'New '.$route,$field=>$amount,'category'=>'Service','unit'=>'item'];
                $this->post(route('admin.'.$route.'.store'),$payload)->assertSessionHasErrors($field);
                $this->assertSame($beforeCount,$model::count());
                $payload['name'] = $record->name;
                $this->put(route('admin.'.$route.'.update',$record),$payload)->assertSessionHasErrors($field);
                $this->assertSame(5000.0,(float)$record->fresh()->getAttribute($field));
            }
        }
    }
}
