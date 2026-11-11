<?php
namespace Tests\Feature;
use App\Models\{Branch,User,Package,PackageInclusion,PackageFreebie,CasketCatalog,HearseCatalog,FreebieCatalog};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PackageCatalogItemDisplayTest extends TestCase
{
    use RefreshDatabase;
    public function test_table_and_details_derive_item_labels_from_current_catalog_state(): void
    {
        $branch=Branch::create(['branch_code'=>'BR001','branch_name'=>'Main Branch','is_active'=>true]);
        $admin=User::factory()->create(['role'=>'admin','admin_scope'=>'system','branch_id'=>$branch->id,'is_active'=>true]);
        $this->actingAs($admin);
        $casket=CasketCatalog::create(['name'=>'Included Oak','standard_price'=>20000,'is_active'=>true,'is_available'=>true]);
        $hearse=HearseCatalog::create(['name'=>'Included Coach','is_active'=>true,'is_available'=>true]);
        $freebie=FreebieCatalog::create(['name'=>'Included Book','default_unit'=>'item','is_active'=>true,'is_available'=>true]);
        $package=Package::create(['name'=>'Catalog Display Package','price'=>50000,'is_active'=>true]);
        $package->packageInclusions()->create(['service_type'=>Package::SERVICE_CASKET,'inclusion_name'=>'Casket','casket_catalog_id'=>$casket->id]);
        $package->packageInclusions()->create(['service_type'=>Package::SERVICE_HEARSE,'inclusion_name'=>'Hearse','hearse_catalog_id'=>$hearse->id,'included_kilometers'=>25]);
        $package->packageInclusions()->create(['service_type'=>Package::SERVICE_BODY_RETRIEVAL,'inclusion_name'=>'Body Retrieval','included_kilometers'=>25]);
        $package->packageFreebies()->create(['freebie_catalog_id'=>$freebie->id,'freebie_name'=>$freebie->name,'quantity'=>1,'unit'=>'item']);
        $catalogs=[$casket,$hearse,$freebie];
        foreach ([null,'unavailable','archived',null] as $state) {
            foreach ($catalogs as $catalog) $catalog->update(['is_active'=>$state!=='archived','is_available'=>$state===null]);
            foreach ([route('admin.service-management.index',['tab'=>'packages']),route('admin.packages.show',['package'=>$package,'modal'=>1])] as $url) {
                $response=$this->get($url)->assertOk();
                $dom=new \DOMDocument(); $old=libxml_use_internal_errors(true); $dom->loadHTML($response->getContent()); libxml_clear_errors();libxml_use_internal_errors($old);
                $xpath=new \DOMXPath($dom);
                $scope=str_contains($url,'service-management') ? '//tr[@data-service-row and contains(@data-search,"catalog display package")]' : '//article[contains(@class,"svc-detail-card")]';
                $items=$xpath->query($scope.'//span[contains(concat(" ",normalize-space(@class)," ")," package-catalog-item ")]');
                $isDetails = !str_contains($url, 'service-management');
                $this->assertSame(3,$items->length);
                foreach ($items as $item) {
                    $this->assertSame($state!==null,str_contains($item->getAttribute('class'),'is-'.($state??'valid')));
                    $labels=$xpath->query('.//span[contains(@class,"package-catalog-state")]',$item);
                    $this->assertSame($state===null?0:1,$labels->length);
                    if ($state) $this->assertSame(ucfirst($state),trim($labels->item(0)->textContent));
                    $this->assertSame(!$isDetails && $state ? 1 : 0,$xpath->query('.//*[@popover or @title]',$item)->length);
                }
                $this->assertSame(0, $xpath->query('//section[contains(@class,"svc-intake-readiness")]')->length);
                $this->assertSame($isDetails && $state ? 3 : 0, $xpath->query($scope.'//*[contains(@class,"package-catalog-advice")]')->length);
                if ($isDetails && $state) {
                    $response->assertSee('Choose an '.($state === 'archived' ? 'active' : 'available').' casket.');
                    $response->assertSee('Replace or remove this freebie.');
                }
                foreach(['Included Oak','Included Coach','Included Book'] as $name) $this->assertStringContainsString($name,$response->getContent());
            }
            $this->assertTrue($package->fresh()->is_active);
            $this->assertSame($casket->id,$package->fresh()->packageInclusions->firstWhere('service_type',Package::SERVICE_CASKET)->casket_catalog_id);
            $this->assertSame($hearse->id,$package->fresh()->packageInclusions->firstWhere('service_type',Package::SERVICE_HEARSE)->hearse_catalog_id);
            $this->assertSame($freebie->id,$package->fresh()->packageFreebies->first()->freebie_catalog_id);
        }
        $casket->update(['is_available'=>false]);
        $response=$this->get(route('admin.packages.show',['package'=>$package,'modal'=>1]))->assertOk();
        $this->assertSame(1,substr_count($response->getContent(),'package-catalog-item is-unavailable'));
        $package->packageInclusions()->where('service_type', Package::SERVICE_HEARSE)->update(['hearse_catalog_id' => null]);
        $this->get(route('admin.packages.show', ['package' => $package, 'modal' => 1]))
            ->assertOk()->assertSee('No hearse selected. Choose one.')
            ->assertDontSee('Included catalog items need attention');
    }
}

