<?php

namespace Tests\Feature;

use App\Models\AddOnCatalog;
use App\Models\Branch;
use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use App\Models\PackageFreebie;
use App\Models\User;
use App\Support\IntakePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class ServiceCatalogConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_drawers_are_read_only_and_existing_list_actions_remain_available(): void
    {
        $branch=Branch::create(['branch_code'=>'BR001','branch_name'=>'Main Branch','type'=>'main','is_active'=>true]);
        $admin=User::factory()->create(['role'=>'admin','admin_scope'=>'system','branch_id'=>$branch->id,'is_active'=>true]);
        $package=Package::create(['name'=>'Readonly Package','price'=>20000,'is_active'=>true]);
        $casket=CasketCatalog::create(['name'=>'Readonly Casket','standard_price'=>10000,'is_active'=>true,'is_available'=>true]);
        $addon=AddOnCatalog::create(['name'=>'Readonly Addon','category'=>'Service','unit'=>'item','price'=>100,'is_active'=>true,'is_available'=>true]);
        $freebie=FreebieCatalog::create(['name'=>'Readonly Freebie','default_unit'=>'item','is_active'=>true,'is_available'=>true]);
        $hearse=\App\Models\HearseCatalog::create(['name'=>'Readonly Hearse','is_active'=>true,'is_available'=>true]);
        $promotion=\App\Models\Promotion::create(['name'=>'Readonly Promotion','package_id'=>$package->id,'discount_type'=>'PERCENT','discount_value'=>10,'starts_at'=>now()->subDay(),'ends_at'=>now()->addMonth(),'is_enabled'=>true]);
        foreach ([['packages','packages',$package],['caskets','casket-catalogs',$casket],['addons','add-on-catalogs',$addon],['freebies','freebie-catalogs',$freebie],['hearses','hearse-catalogs',$hearse],['promotions','promotions',$promotion]] as [$tab,$catalog,$record]) {
            $html=$this->actingAs($admin)->get(route('admin.'.$catalog.'.show',[$record,'modal'=>1]))->assertOk()->getContent();
            $dom=new \DOMDocument();@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);$x=new \DOMXPath($dom);
            $footer=$x->query('//footer[contains(@class,"svc-detail-actions")]')->item(0);
            $this->assertNull($footer,$catalog);
            $this->assertSame(0,$x->query('//*[@data-parent-modal-close or @data-service-modal-url or @data-status-open]')->length,$catalog);

            $standalone=$this->actingAs($admin)->get(route('admin.'.$catalog.'.show',$record))->assertOk()->getContent();
            $standaloneDom=new \DOMDocument();@$standaloneDom->loadHTML($standalone);$sx=new \DOMXPath($standaloneDom);
            $this->assertSame(1,$sx->query('//footer[contains(@class,"svc-detail-actions")]//*[@data-service-modal-url]')->length,$catalog);

            $list=$this->actingAs($admin)->get(route('admin.service-management.index',['tab'=>$tab]))->assertOk()->getContent();
            $listDom=new \DOMDocument();@$listDom->loadHTML($list);$lx=new \DOMXPath($listDom);
            $edit=route('admin.'.$catalog.'.edit',$record);
            $this->assertGreaterThan(0,$lx->query('//*[@data-row-menu]//*[@data-service-modal-url and starts-with(@data-service-modal-url,"'.$edit.'")]')->length,$catalog);
            if($catalog!=='promotions') {
                $archive=route('admin.'.$catalog.'.toggleActive',$record);
                $this->assertGreaterThan(0,$lx->query('//*[@data-row-menu]//form[@action="'.$archive.'"]')->length,$catalog);
                if($catalog!=='packages') {
                    $availability=route('admin.'.$catalog.'.toggleAvailability',$record);
                    $this->assertGreaterThan(0,$lx->query('//*[@data-row-menu]//form[@action="'.$availability.'"]')->length,$catalog);
                }
            }
        }
    }

    public function test_staff_intake_only_exposes_catalog_items_valid_for_new_cases(): void
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'address' => 'Main', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $activePackage = Package::create(['name' => 'Selectable Package', 'price' => 20000, 'is_active' => true]);
        Package::create(['name' => 'Archived Package Hidden', 'price' => 21000, 'is_active' => false]);
        AddOnCatalog::create(['name' => 'Active Add-on Visible', 'category' => 'Service', 'price' => 500, 'unit' => 'service', 'is_active' => true]);
        AddOnCatalog::create(['name' => 'Archived Add-on Hidden', 'category' => 'Service', 'price' => 600, 'unit' => 'service', 'is_active' => false]);
        CasketCatalog::create(['name' => 'Available Casket Visible', 'standard_price' => 20000, 'is_active' => true, 'is_available' => true]);
        CasketCatalog::create(['name' => 'Unavailable Casket Visible', 'standard_price' => 22000, 'is_active' => true, 'is_available' => false]);
        CasketCatalog::create(['name' => 'Archived Casket Hidden', 'standard_price' => 23000, 'is_active' => false, 'is_available' => false]);
        $archivedFreebie = FreebieCatalog::create(['name' => 'Archived Freebie Hidden', 'default_unit' => 'item', 'is_active' => false]);
        PackageFreebie::create(['package_id' => $activePackage->id, 'freebie_catalog_id' => $archivedFreebie->id, 'freebie_name' => $archivedFreebie->name, 'quantity' => 1, 'unit' => 'item']);

        $this->actingAs($staff)
            ->get(route('intake.main.create', absolute: false))
            ->assertOk()
            ->assertDontSee('Selectable Package')
            ->assertSee('Active Add-on Visible')
            ->assertSee('Available Casket Visible')
            ->assertDontSee('Archived Package Hidden')
            ->assertDontSee('Archived Add-on Hidden')
            ->assertSee('Unavailable Casket Visible')
            ->assertSee('— Unavailable')
            ->assertDontSee('Archived Casket Hidden')
            ->assertDontSee('Archived Freebie Hidden');
    }

    public function test_new_admin_add_ons_reach_a_fresh_staff_intake_and_follow_availability(): void
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => $branch->id, 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.add-on-catalogs.store'), [
            'name' => 'New Flower Arrangement', 'category' => 'Flowers', 'unit' => 'set', 'price' => 1500,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $addOn = AddOnCatalog::where('name', 'New Flower Arrangement')->firstOrFail();
        $this->assertTrue($addOn->is_active);
        $this->assertTrue($addOn->is_available);

        $intake = route('intake.main.create', ['intake_modal' => 1]);
        $page = $this->actingAs($staff)->get($intake)->assertOk();
        $this->assertContains($addOn->id, $page->viewData('activeAddOns')->pluck('id')->all());
        $this->actingAs($admin)->patch(route('admin.add-on-catalogs.toggleAvailability', $addOn))->assertRedirect();
        $page = $this->actingAs($staff)->get($intake)->assertOk();
        $this->assertNotContains($addOn->id, $page->viewData('activeAddOns')->pluck('id')->all());
    }

    public function test_catalog_archive_flags_dependencies_without_changing_package_status(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'address' => 'Main', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'First Class', 'price' => 20000, 'is_active' => true]);
        $casket = CasketCatalog::create(['name' => 'Oak Casket', 'standard_price' => 20000, 'is_active' => true, 'is_available' => true]);
        $freebie = FreebieCatalog::create(['name' => 'Memorial Card', 'default_unit' => 'item', 'is_active' => true]);
        $package->packageInclusions()->create(['service_type' => 'casket', 'inclusion_name' => 'Casket', 'casket_catalog_id' => $casket->id]);
        PackageFreebie::create(['package_id' => $package->id, 'freebie_catalog_id' => $freebie->id, 'freebie_name' => $freebie->name, 'quantity' => 1, 'unit' => 'item']);

        $this->actingAs($admin)->get(route('admin.casket-catalogs.show', $casket, absolute: false))
            ->assertOk()->assertSee('First Class')->assertDontSee('class="svc-detail-primary" href=', false);

        $this->actingAs($admin)->get(route('admin.freebie-catalogs.show', ['freebie_catalog' => $freebie, 'modal' => 1], absolute: false))
            ->assertOk()->assertSee('Packages using this item')->assertSee('First Class');
        $this->actingAs($admin)->get(route('admin.service-management.index', ['tab' => 'freebies'], absolute: false))
            ->assertOk()->assertSee('data-affected-packages=', false)->assertSee('First Class');

        $this->actingAs($admin)->patch(route('admin.casket-catalogs.toggleActive', $casket, absolute: false))
            ->assertSessionHasErrors('catalog');
        $this->actingAs($admin)->patch(route('admin.freebie-catalogs.toggleActive', $freebie, absolute: false))
            ->assertSessionHasErrors('catalog');
        $this->assertTrue($casket->fresh()->is_active);
        $this->assertTrue($freebie->fresh()->is_active);

        $this->assertTrue($package->fresh()->is_active);
        $this->assertEmpty(app(\App\Support\ServiceCatalogPolicy::class)->packageBlockers($package->fresh()));
        $this->assertTrue($casket->fresh()->is_active);
        $this->assertTrue($freebie->fresh()->is_active);
    }

    public function test_unavailable_casket_keeps_package_active_and_requires_available_replacement(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'address' => 'Main', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => $branch->id, 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Oak Package', 'price' => 20000, 'is_active' => true]);
        $casket = CasketCatalog::create(['name' => 'Oak Casket', 'standard_price' => 20000, 'is_active' => true, 'is_available' => true]);
        $package->packageInclusions()->create(['service_type' => 'casket', 'inclusion_name' => 'Casket', 'casket_catalog_id' => $casket->id]);

        $replacement = CasketCatalog::create(['name' => 'Available Replacement', 'standard_price' => 25000, 'is_active' => true, 'is_available' => true]);
        $this->actingAs($staff)->get(route('intake.main.create', absolute: false))->assertSee('Oak Package');
        $this->actingAs($admin)->patch(route('admin.casket-catalogs.toggleAvailability', $casket, absolute: false))->assertRedirect();
        $this->actingAs($staff)->get(route('intake.main.create', absolute: false))->assertSee('Oak Package');
        $this->assertTrue($package->fresh()->is_active);
        $pricing = app(IntakePricingService::class);
        $this->assertArrayHasKey('replacement_casket_catalog_id', $pricing->validateSelections($package->fresh(), [], false));
        $this->assertSame([], $pricing->validateSelections($package->fresh(), ['replacement_casket_catalog_id' => $replacement->id], false));
        $priced = $pricing->price($package->fresh(), ['replacement_casket_catalog_id' => $replacement->id]);
        $this->assertSame(5000.0, (float) collect($priced['service_charges'])->firstWhere('type', 'casket_upgrade')['amount']);
        $this->assertSame($replacement->id, $priced['selected_casket']['id']);
        $this->assertArrayHasKey('replacement_casket_catalog_id', $pricing->validateSelections($package->fresh(), ['replacement_casket_catalog_id' => $casket->id], false));
        $this->assertFalse(app(\App\Support\ServiceCatalogPolicy::class)->intakePackages(Package::whereKey($package->id))->exists());
        $this->actingAs($admin)->get(route('admin.packages.edit', $package, absolute: false))->assertOk()->assertSee('Choose another casket.');

    }

    public function test_package_save_rejects_unavailable_catalog_links_and_restore_rechecks_them(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'address' => 'Main', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => $branch->id, 'is_active' => true]);
        $casket = CasketCatalog::create(['name' => 'Unavailable Oak', 'standard_price' => 20000, 'is_active' => true, 'is_available' => false]);
        $freebie = FreebieCatalog::create(['name' => 'Archived Card', 'default_unit' => 'item', 'is_active' => false]);

        $this->actingAs($admin)->post(route('admin.packages.store', absolute: false), [
            'name' => 'Blocked Package',
            'price' => 20000,
            'included_services' => ['casket' => ['enabled' => 1, 'casket_catalog_id' => $casket->id]],
            'freebies' => [['freebie_catalog_id' => $freebie->id, 'freebie_name' => $freebie->name]],
        ])->assertSessionHasErrors(['included_services', 'freebies.0.freebie_catalog_id']);
        $this->assertDatabaseMissing('packages', ['name' => 'Blocked Package']);

        $package = Package::create(['name' => 'Archived Package', 'price' => 20000, 'is_active' => false]);
        $package->packageInclusions()->create(['service_type' => 'casket', 'inclusion_name' => 'Casket', 'casket_catalog_id' => $casket->id]);
        $this->actingAs($admin)->patch(route('admin.packages.toggleActive', $package, absolute: false))
            ->assertSessionHasNoErrors();
        $this->assertTrue($package->fresh()->is_active);
        $this->assertNotEmpty(app(\App\Support\ServiceCatalogPolicy::class)->packageBlockers($package->fresh()));
    }

    public function test_intake_pricing_rejects_archived_add_ons_and_unavailable_caskets(): void
    {
        $package = Package::create(['name' => 'Active Package', 'price' => 20000, 'is_active' => true]);
        $archivedAddOn = AddOnCatalog::create(['name' => 'Archived Add-on', 'category' => 'Service', 'price' => 500, 'unit' => 'service', 'is_active' => false]);
        $unavailableCasket = CasketCatalog::create(['name' => 'Unavailable Casket', 'standard_price' => 20000, 'is_active' => true, 'is_available' => false]);
        $service = app(IntakePricingService::class);

        $this->assertArrayHasKey('selected_add_ons', $service->validateSelections($package, ['selected_add_ons' => [$archivedAddOn->id]], false));
        $this->assertArrayHasKey('replacement_casket_catalog_id', $service->validateSelections($package, ['replacement_casket_catalog_id' => $unavailableCasket->id], false));
    }

    public function test_system_admin_can_toggle_availability_and_restore_preserves_it(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'address' => 'Main', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => $branch->id, 'is_active' => true]);
        $casket = CasketCatalog::create(['name' => 'Availability Casket', 'standard_price' => 20000, 'is_active' => true, 'is_available' => true]);

        $this->actingAs($admin)->patch(route('admin.casket-catalogs.toggleAvailability', $casket, absolute: false))->assertRedirect();
        $this->assertFalse($casket->fresh()->is_available);

        $this->actingAs($admin)->patch(route('admin.casket-catalogs.toggleActive', $casket, absolute: false))->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.casket-catalogs.toggleActive', $casket, absolute: false))->assertRedirect();

        $restored = $casket->fresh();
        $this->assertTrue($restored->is_active);
        $this->assertFalse($restored->is_available);
    }
}
