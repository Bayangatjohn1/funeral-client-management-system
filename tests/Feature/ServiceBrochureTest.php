<?php

namespace Tests\Feature;

use App\Models\{Branch, CasketCatalog, Package, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceBrochureTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role='admin', ?string $scope='system'): User
    {
        $branch=Branch::firstOrCreate(['branch_code'=>'BR001'],['branch_name'=>'Main Branch','is_active'=>true]);
        return User::factory()->create(['role'=>$role,'admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
    }

    public function test_system_admin_can_print_every_available_package_beyond_pagination_or_one_selected_package(): void
    {
        $this->actingAs($this->actor());
        for($i=1;$i<=25;$i++) Package::create(['name'=>'Brochure Package '.str_pad($i,2,'0',STR_PAD_LEFT),'price'=>10000+$i,'is_active'=>true]);
        Package::create(['name'=>'Do Not Advertise Archived','price'=>20000,'is_active'=>false]);
        $this->get(route('admin.service-management.print',['tab'=>'packages','scope'=>'all']))->assertOk()
            ->assertSee('Brochure Package 25')->assertDontSee('Do Not Advertise Archived')->assertViewHas('items',fn($items)=>$items->where('name','Brochure Package 25')->isNotEmpty());
        $package=Package::where('name','Brochure Package 25')->firstOrFail();
        $this->get(route('admin.service-management.print',['tab'=>'packages','scope'=>'selected','ids'=>[$package->id]]))->assertOk()
            ->assertViewHas('items',fn($items)=>$items->count()===1&&$items[0]['name']===$package->name);
        $this->get(route('admin.service-management.print',['tab'=>'packages','scope'=>'filtered','q'=>'Brochure Package 25']))->assertOk()
            ->assertViewHas('items',fn($items)=>$items->count()===1);
    }

    public function test_catalog_printing_excludes_unavailable_items_and_handles_a_stale_individual_link(): void
    {
        $this->actingAs($this->actor());
        $available=CasketCatalog::create(['name'=>'Available Brochure Casket','standard_price'=>10000,'is_active'=>true,'is_available'=>true]);
        $unavailable=CasketCatalog::create(['name'=>'Unavailable Brochure Casket','standard_price'=>10000,'is_active'=>true,'is_available'=>false]);
        $this->get(route('admin.service-management.print',['tab'=>'caskets','scope'=>'all']))->assertOk()->assertViewHas('items',fn($items)=>$items->contains('name',$available->name)&&!$items->contains('name',$unavailable->name));
        $this->get(route('admin.service-management.print',['tab'=>'caskets','scope'=>'selected','ids'=>[$unavailable->id]]))->assertOk()->assertSee('No available items to print')->assertSee('Some selected items are no longer available.');
        $this->get(route('admin.service-management.print',['tab'=>'caskets','scope'=>'filtered','status'=>'archived']))->assertOk()->assertViewHas('items',fn($items)=>$items->isEmpty());
    }

    public function test_branch_admin_can_print_only_own_branch_specific_catalog_entries(): void
    {
        $own = Branch::create(['branch_code'=>'BR-OWN','branch_name'=>'Own Branch','address'=>'Own branch address','is_active'=>true]);
        $other = Branch::create(['branch_code'=>'BR-OTHER','branch_name'=>'Other Branch','address'=>'Other branch address','is_active'=>true]);
        $branchAdmin = User::factory()->create(['role'=>'admin','admin_scope'=>'branch','branch_id'=>$own->id,'is_active'=>true]);
        $global = \App\Models\AddOnCatalog::create(['name'=>'Global Add-on','category'=>'General','price'=>100,'unit'=>'item','is_active'=>true,'is_available'=>true]);
        $globalCasket = CasketCatalog::create(['name'=>'Global Casket','standard_price'=>8000,'is_active'=>true,'is_available'=>true]);
        $ownChapel = \App\Models\AddOnCatalog::create(['name'=>'Own Chapel','category'=>\App\Models\AddOnCatalog::CHAPEL_VIEWING,'branch_id'=>$own->id,'price'=>1000,'unit'=>'day','is_active'=>true,'is_available'=>true]);
        $otherChapel = \App\Models\AddOnCatalog::create(['name'=>'Other Chapel','category'=>\App\Models\AddOnCatalog::CHAPEL_VIEWING,'branch_id'=>$other->id,'price'=>1000,'unit'=>'day','is_active'=>true,'is_available'=>true]);
        $ownPackage = Package::create(['name'=>'Own Branch Package','price'=>20000,'is_active'=>true]);
        $ownPackage->packageInclusions()->create(['service_type'=>Package::SERVICE_CUSTOM,'add_on_catalog_id'=>$ownChapel->id,'inclusion_name'=>$ownChapel->name]);
        $otherPackage = Package::create(['name'=>'Other Branch Package','price'=>20000,'is_active'=>true]);
        $otherPackage->packageInclusions()->create(['service_type'=>Package::SERVICE_CUSTOM,'add_on_catalog_id'=>$otherChapel->id,'inclusion_name'=>$otherChapel->name]);

        $this->actingAs($branchAdmin)
            ->get(route('admin.service-management.index',['tab'=>'addons']))
            ->assertOk()
            ->assertSee('Print Preview');
        $this->get(route('admin.add-on-catalogs.show', $ownChapel))
            ->assertOk()
            ->assertSee('Print Preview');

        $this->get(route('admin.service-management.print',['tab'=>'addons','scope'=>'all']))
            ->assertOk()
            ->assertSee($global->name)
            ->assertSee($ownChapel->name)
            ->assertDontSee($otherChapel->name)
            ->assertSee('BR-OWN')
            ->assertSee('Own Branch')
            ->assertSee('Own branch address');

        $this->get(route('admin.service-management.print',['tab'=>'caskets','scope'=>'all']))
            ->assertOk()
            ->assertSee($globalCasket->name);

        $this->get(route('admin.service-management.print',['tab'=>'packages','scope'=>'all']))
            ->assertOk()
            ->assertSee($ownPackage->name)
            ->assertDontSee($otherPackage->name);

        $this->get(route('admin.service-management.print',['tab'=>'addons','scope'=>'selected','ids'=>[$otherChapel->id]]))
            ->assertOk()
            ->assertSee('No available items to print')
            ->assertSee('Some selected items are no longer available.');
    }

    public function test_staff_owner_and_guests_cannot_access_service_brochures(): void
    {
        foreach([['staff',null],['owner',null]] as [$role,$scope]){
            $this->actingAs($this->actor($role,$scope))->get(route('admin.service-management.print'))->assertForbidden();
        }
        auth()->logout();
        $this->get(route('admin.service-management.print'))->assertRedirect(route('login'));
    }

    public function test_brochure_preserves_configured_package_inclusions_units_and_extra_rates(): void
    {
        $this->actingAs($this->actor());
        $package=Package::create(['name'=>'Coverage Package','price'=>45000,'is_active'=>true]);
        $package->packageInclusions()->create(['service_type'=>Package::SERVICE_BODY_RETRIEVAL,'inclusion_name'=>'Body Retrieval','included_kilometers'=>25,'price_per_excess_kilometer'=>100]);
        $package->packageInclusions()->create(['service_type'=>Package::SERVICE_HOME_VIEWING,'inclusion_name'=>'Home Viewing','included_days'=>4,'price_per_extended_day'=>1000]);
        $package->packageFreebies()->create(['freebie_name'=>'Memorial Set','quantity'=>2,'unit'=>'sets']);
        $this->get(route('admin.service-management.print',['tab'=>'packages','scope'=>'selected','ids'=>[$package->id]]))->assertOk()
            ->assertSee('25 km included')->assertSee('4 days / 3 nights included')->assertSee('100.00 / extra km')->assertSee('2 sets included')->assertDontSee('No Promotion');
    }
}
