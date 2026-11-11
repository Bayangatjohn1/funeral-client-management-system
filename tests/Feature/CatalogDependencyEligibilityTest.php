<?php
namespace Tests\Feature;
use App\Models\{Branch,User,Package,CasketCatalog,HearseCatalog,FreebieCatalog,AddOnCatalog};
use App\Support\{ServiceCatalogPolicy,IntakePricingService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CatalogDependencyEligibilityTest extends TestCase {
 use RefreshDatabase;
 private function admin() { $b=Branch::create(['branch_code'=>'BR001','branch_name'=>'Main','branch_type'=>'main','is_active'=>true]); return User::factory()->create(['branch_id'=>$b->id,'role'=>'admin','admin_scope'=>'main','is_active'=>true]); }
 public function test_required_dependencies_derive_eligibility_without_overwriting_status_or_references(): void {
  $admin=$this->admin(); $this->actingAs($admin);
  $p=Package::create(['name'=>'Dependent Package','price'=>50000,'is_active'=>true]);
  $h=HearseCatalog::create(['name'=>'Configured Coach','is_active'=>true,'is_available'=>true]);
  $p->packageInclusions()->create(['service_type'=>'hearse','inclusion_name'=>'Hearse','hearse_catalog_id'=>$h->id]);
  $f=FreebieCatalog::create(['name'=>'Memorial Book','is_active'=>true,'is_available'=>true,'default_unit'=>'item']);
  $p->packageFreebies()->create(['freebie_catalog_id'=>$f->id,'freebie_name'=>$f->name,'quantity'=>1,'unit'=>'item']);
  $policy=app(ServiceCatalogPolicy::class);
  $this->assertTrue($policy->intakePackages(Package::whereKey($p->id))->exists());
  foreach ([['hearse-catalogs',$h],['freebie-catalogs',$f]] as [$route,$catalog]) {
   $this->patch(route('admin.'.$route.'.toggleAvailability',$catalog,absolute:false))->assertRedirect();
   $this->assertTrue($p->fresh()->is_active); $this->assertFalse($policy->intakePackages(Package::whereKey($p->id))->exists());
   $this->assertNotEmpty($policy->packageBlockers($p->fresh(), true));
   $this->patch(route('admin.'.$route.'.toggleAvailability',$catalog,absolute:false))->assertRedirect();
   $this->assertTrue($policy->intakePackages(Package::whereKey($p->id))->exists());
   $p->update(['is_active'=>false]);
   $this->patch(route('admin.'.$route.'.toggleActive',$catalog,absolute:false))->assertRedirect();
   $p->update(['is_active'=>true]);
   $this->assertTrue($p->fresh()->is_active); $this->assertFalse($policy->intakePackages(Package::whereKey($p->id))->exists());
   $this->patch(route('admin.'.$route.'.toggleActive',$catalog,absolute:false))->assertRedirect();
  }
  $this->assertSame($h->id,$p->fresh()->packageInclusions->first()->hearse_catalog_id);
  $this->assertSame($f->id,$p->fresh()->packageFreebies->first()->freebie_catalog_id);
  $p->update(['is_active'=>false]); $h->update(['is_available'=>false]); $h->update(['is_available'=>true]);
  $this->assertFalse($p->fresh()->is_active); $this->assertFalse($policy->intakePackages(Package::whereKey($p->id))->exists());
 }
 public function test_optional_addons_do_not_block_packages_and_archived_casket_requires_valid_substitution(): void {
  $this->actingAs($this->admin()); $p=Package::create(['name'=>'Casket Package','price'=>50000,'is_active'=>true]);
  $c=CasketCatalog::create(['name'=>'Included Oak','standard_price'=>20000,'is_active'=>true,'is_available'=>true]);
  $r=CasketCatalog::create(['name'=>'Replacement','standard_price'=>25000,'is_active'=>true,'is_available'=>true]);
  $p->packageInclusions()->create(['service_type'=>'casket','inclusion_name'=>'Casket','casket_catalog_id'=>$c->id]);
  $this->patch(route('admin.casket-catalogs.toggleAvailability',$c,absolute:false))->assertRedirect();
  $policy=app(ServiceCatalogPolicy::class); $pricing=app(IntakePricingService::class);
  $this->assertTrue($p->fresh()->is_active); $this->assertFalse($policy->intakePackages(Package::whereKey($p->id))->exists());
  $this->assertTrue($policy->intakePackages(Package::whereKey($p->id),true)->exists());
  $this->assertArrayHasKey('replacement_casket_catalog_id',$pricing->validateSelections($p->fresh(),[],false));
  $this->assertSame([],$pricing->validateSelections($p->fresh(),['replacement_casket_catalog_id'=>$r->id],false));
  $a=AddOnCatalog::create(['name'=>'Flowers','price'=>500,'unit'=>'item','category'=>'Flowers','is_active'=>true,'is_available'=>true]);
  $this->patch(route('admin.add-on-catalogs.toggleAvailability',$a,absolute:false))->assertRedirect();
  $this->assertTrue($policy->intakePackages(Package::whereKey($p->id),true)->exists());
  $this->assertArrayHasKey('selected_add_ons',$pricing->validateSelections($p->fresh(),['selected_add_ons'=>[$a->id]],false));
 }
 public function test_branch_admin_cannot_change_catalog_availability(): void {
  $admin=$this->admin(); $branchAdmin=User::factory()->create(['branch_id'=>$admin->branch_id,'role'=>'admin','admin_scope'=>'branch','is_active'=>true]);
  foreach ([['hearse-catalogs',HearseCatalog::create(['name'=>'Permission Coach','is_active'=>true])],['freebie-catalogs',FreebieCatalog::create(['name'=>'Permission Freebie','is_active'=>true,'default_unit'=>'item'])],['add-on-catalogs',AddOnCatalog::create(['name'=>'Permission Extra','is_active'=>true,'price'=>100,'unit'=>'item','category'=>'General'])],['casket-catalogs',CasketCatalog::create(['name'=>'Permission Casket','is_active'=>true,'standard_price'=>20000])]] as [$route,$catalog]) {
   $this->actingAs($admin)->get(route('admin.'.$route.'.show',$catalog,absolute:false).'?modal=1')->assertOk()->assertDontSee('class="svc-detail-primary" href=', false)->assertDontSee('Edit Freebie');
   $this->actingAs($branchAdmin)->patch(route('admin.'.$route.'.toggleAvailability',$catalog,absolute:false))->assertForbidden();
   $this->assertTrue($catalog->fresh()->is_available);
  }
 }
 public function test_restoring_old_casket_never_replaces_current_package_configuration(): void {
  $this->actingAs($this->admin());
  $p=Package::create(['name'=>'Configured Package','price'=>50000,'is_active'=>true]);
  $old=CasketCatalog::create(['name'=>'Original Casket','standard_price'=>20000,'is_active'=>true,'is_available'=>true]);
  $new=CasketCatalog::create(['name'=>'New Casket','standard_price'=>25000,'is_active'=>true,'is_available'=>true]);
  $row=$p->packageInclusions()->create(['service_type'=>'casket','inclusion_name'=>'Casket','casket_catalog_id'=>$old->id]);
  $policy=app(ServiceCatalogPolicy::class);
  foreach (['toggleAvailability','toggleActive'] as $action) {
   $row->update(['casket_catalog_id'=>$old->id]);
   if ($action === 'toggleActive') $p->update(['is_active'=>false]);
   $this->patch(route('admin.casket-catalogs.'.$action,$old,absolute:false))->assertRedirect();
   $this->assertNotEmpty($policy->packageBlockers($p->fresh(), true));
   $this->patch(route('admin.casket-catalogs.'.$action,$old,absolute:false))->assertRedirect();
   $this->assertSame([],$policy->packageBlockers($p->fresh(), true));
   $this->patch(route('admin.casket-catalogs.'.$action,$old,absolute:false))->assertRedirect();
   $row->update(['casket_catalog_id'=>$new->id]);
   $this->patch(route('admin.casket-catalogs.'.$action,$old,absolute:false))->assertRedirect();
   $this->assertSame($new->id,$row->fresh()->casket_catalog_id);
   $this->assertSame([],$policy->packageBlockers($p->fresh(), true));
   if ($action === 'toggleActive') $p->update(['is_active'=>true]);
   $this->assertTrue($p->fresh()->is_active);
  }
 }
 public function test_restore_preserves_availability_and_latest_selection_for_required_catalogs(): void {
  $this->actingAs($this->admin());
  foreach (['casket','hearse','freebie'] as $type) {
   $class=['casket'=>CasketCatalog::class,'hearse'=>HearseCatalog::class,'freebie'=>FreebieCatalog::class][$type];
   $route=['casket'=>'casket-catalogs','hearse'=>'hearse-catalogs','freebie'=>'freebie-catalogs'][$type];
   $old=$class::create(['name'=>'Original '.$type,'is_active'=>false,'is_available'=>false,'standard_price'=>20000,'default_unit'=>'item']);
   $new=$class::create(['name'=>'Current '.$type,'is_active'=>true,'is_available'=>true,'standard_price'=>25000,'default_unit'=>'item']);
   $p=Package::create(['name'=>'Restore '.$type,'price'=>50000,'is_active'=>true]);
   $field=$type.'_catalog_id';
   $row=$type==='freebie' ? $p->packageFreebies()->create([$field=>$old->id,'freebie_name'=>$old->name,'quantity'=>1,'unit'=>'item']) : $p->packageInclusions()->create(['service_type'=>$type,'inclusion_name'=>$type,$field=>$old->id]);
   $policy=app(ServiceCatalogPolicy::class);
   $this->assertStringContainsString('is archived.',implode(' ',$policy->packageBlockers($p)));
   $this->patch(route('admin.'.$route.'.toggleActive',$old,absolute:false))->assertRedirect();
   $this->assertFalse($old->fresh()->is_available);
   $this->assertStringContainsString('is unavailable.',implode(' ',$policy->packageBlockers($p)));
   $row->update([$field=>$new->id]);
   $this->assertSame([],$policy->packageBlockers($p));
   $this->patch(route('admin.'.$route.'.toggleAvailability',$old,absolute:false))->assertRedirect();
   $this->assertSame($new->id,$row->fresh()->{$field});
   $this->assertSame([],$policy->packageBlockers($p));
   $this->patch(route('admin.'.$route.'.toggleActive',$new,absolute:false))->assertSessionHasErrors('catalog');
   $this->assertTrue($new->fresh()->is_active);
   $this->assertTrue($p->fresh()->is_active);
  }
 }

 public function test_optional_addon_and_non_service_references_do_not_block_archive(): void {
  $this->actingAs($this->admin());
  $p=Package::create(['name'=>'Active Optional Package','price'=>50000,'is_active'=>true]);
  $a=AddOnCatalog::create(['name'=>'Optional Flowers','category'=>'Flowers','price'=>500,'unit'=>'item','is_active'=>true]);
  $legacy=$p->packageAddOns()->create(['name'=>'Optional Flowers','price'=>500,'is_active'=>true]);
  $a->legacyPackageAddOns()->attach($legacy);
  $this->patch(route('admin.add-on-catalogs.toggleActive',$a,absolute:false))->assertSessionHasNoErrors();
  $this->assertFalse($a->fresh()->is_active);
  $this->assertTrue($p->fresh()->is_active);
  $this->assertSame([] ,app(ServiceCatalogPolicy::class)->packageBlockers($p));
  $c=CasketCatalog::create(['name'=>'Not An Enabled Casket Service','standard_price'=>20000,'is_active'=>true,'is_available'=>true]);
  $p->packageInclusions()->create(['service_type'=>'custom','inclusion_name'=>'Legacy note','is_custom'=>true,'casket_catalog_id'=>$c->id]);
  $this->patch(route('admin.casket-catalogs.toggleActive',$c,absolute:false))->assertSessionHasNoErrors();
  $this->assertFalse($c->fresh()->is_active);
 }
 public function test_archive_only_counts_current_active_required_packages(): void {
  $this->actingAs($this->admin());
  $c=CasketCatalog::create(['name'=>'Required Casket','standard_price'=>20000,'is_active'=>true,'is_available'=>true]);
  $p=Package::create(['name'=>'Current Required Package','price'=>50000,'is_active'=>true]);
  $row=$p->packageInclusions()->create(['service_type'=>'casket','inclusion_name'=>'Casket','casket_catalog_id'=>$c->id]);
  $this->patch(route('admin.casket-catalogs.toggleActive',$c,absolute:false))->assertSessionHasErrors(['catalog'=>'Cannot archive this item. It is still used by these active packages: Current Required Package. Update those packages first.']);
  $this->assertTrue($c->fresh()->is_active);
  $p->update(['is_active'=>false]);
  $this->patch(route('admin.casket-catalogs.toggleActive',$c,absolute:false))->assertSessionHasNoErrors();
  $this->assertFalse($c->fresh()->is_active);
  $this->assertSame($c->id,$row->fresh()->casket_catalog_id);
  $this->assertFalse($p->fresh()->is_active);
 }

 public function test_catalog_tabs_use_one_combined_status_filter_in_grid_and_table_data(): void {
  $this->actingAs($this->admin());
  foreach (['caskets'=>CasketCatalog::class,'hearses'=>HearseCatalog::class,'freebies'=>FreebieCatalog::class,'addons'=>AddOnCatalog::class] as $tab=>$class) {
   $yes=$class::create(['name'=>'Filterfixture Available '.$tab,'is_active'=>true,'is_available'=>true,'standard_price'=>1000,'price'=>100,'unit'=>'item','default_unit'=>'item','category'=>'General']);
   $no=$class::create(['name'=>'Filterfixture Unavailable '.$tab,'is_active'=>true,'is_available'=>false,'standard_price'=>1000,'price'=>100,'unit'=>'item','default_unit'=>'item','category'=>'General']);
   $archAvailable=$class::create(['name'=>'Filterfixture Archived Available '.$tab,'is_active'=>false,'is_available'=>true,'standard_price'=>1000,'price'=>100,'unit'=>'item','default_unit'=>'item','category'=>'General']);
   $archUnavailable=$class::create(['name'=>'Filterfixture Archived Unavailable '.$tab,'is_active'=>false,'is_available'=>false,'standard_price'=>1000,'price'=>100,'unit'=>'item','default_unit'=>'item','category'=>'General']);
   $key=['caskets'=>'caskets','hearses'=>'hearses','freebies'=>'freebies','addons'=>'addOns'][$tab];
   foreach ([['all',[$yes->id,$no->id,$archAvailable->id,$archUnavailable->id]],['available',[$yes->id]],['unavailable',[$no->id]],['archived',[$archAvailable->id,$archUnavailable->id]]] as [$status,$ids]) {
    $this->get(route('admin.service-management.index',['tab'=>$tab,'q'=>'Filterfixture','status'=>$status],absolute:false))->assertOk()->assertViewHas($key,function($rows) use ($ids) { $this->assertEqualsCanonicalizing($ids,$rows->pluck('id')->all()); return true; })->assertViewHas('status',$status);
   }
   $this->get(route('admin.service-management.index',['tab'=>$tab,'q'=>'Filterfixture'],absolute:false))->assertOk()->assertViewHas('status','all')->assertSee('value="available"',false)->assertSee('value="unavailable"',false)->assertSee('value="archived"',false)->assertDontSee('name="availability"',false)->assertDontSee('aria-label="Catalog availability"',false);
   $this->get(route('admin.service-management.index',['tab'=>$tab,'q'=>'Available'],absolute:false))->assertOk()->assertDontSee('data-service-reset>',false);
  }
 }

 public function test_package_status_filter_includes_active_packages_with_currently_unavailable_required_catalog_items(): void {
  $this->actingAs($this->admin());
  $makePackage = fn (string $name) => Package::create(['name'=>'CatalogHealth '.$name,'price'=>50000,'is_active'=>true]);
  $makeCasket = fn (string $name, bool $active, bool $available) => CasketCatalog::create(['name'=>$name,'standard_price'=>20000,'is_active'=>$active,'is_available'=>$available]);
  $makeHearse = fn (string $name, bool $active, bool $available) => HearseCatalog::create(['name'=>$name,'is_active'=>$active,'is_available'=>$available]);
  $makeFreebie = fn (string $name, bool $active, bool $available) => FreebieCatalog::create(['name'=>$name,'default_unit'=>'item','is_active'=>$active,'is_available'=>$available]);

  $unavailableCasket = $makePackage('Unavailable Casket');
  $unavailableCasket->packageInclusions()->create(['service_type'=>'casket','inclusion_name'=>'Casket','casket_catalog_id'=>$makeCasket('Health Casket Unavailable',true,false)->id]);
  $unavailableHearse = $makePackage('Unavailable Hearse');
  $unavailableHearse->packageInclusions()->create(['service_type'=>'hearse','inclusion_name'=>'Hearse','hearse_catalog_id'=>$makeHearse('Health Hearse Unavailable',true,false)->id]);
  $unavailableFreebie = $makePackage('Unavailable Freebie');
  $unavailableFreebie->packageFreebies()->create(['freebie_catalog_id'=>$makeFreebie('Health Freebie Unavailable',true,false)->id,'freebie_name'=>'Health Freebie Unavailable','quantity'=>1,'unit'=>'item']);

  $archivedCasket = $makePackage('Archived Casket');
  $archivedCasket->packageInclusions()->create(['service_type'=>'casket','inclusion_name'=>'Casket','casket_catalog_id'=>$makeCasket('Health Casket Archived',false,true)->id]);
  $archivedHearse = $makePackage('Archived Hearse');
  $archivedHearse->packageInclusions()->create(['service_type'=>'hearse','inclusion_name'=>'Hearse','hearse_catalog_id'=>$makeHearse('Health Hearse Archived',false,true)->id]);
  $archivedFreebie = $makePackage('Archived Freebie');
  $archivedFreebie->packageFreebies()->create(['freebie_catalog_id'=>$makeFreebie('Health Freebie Archived',false,true)->id,'freebie_name'=>'Health Freebie Archived','quantity'=>1,'unit'=>'item']);

  $both = $makePackage('Both Conditions');
  $both->packageInclusions()->create(['service_type'=>'casket','inclusion_name'=>'Casket','casket_catalog_id'=>$makeCasket('Health Casket Both Unavailable',true,false)->id]);
  $both->packageFreebies()->create(['freebie_catalog_id'=>$makeFreebie('Health Freebie Both Archived',false,true)->id,'freebie_name'=>'Health Freebie Both Archived','quantity'=>1,'unit'=>'item']);

  $archivedPackage = Package::create(['name'=>'CatalogHealth Archived Package Status','price'=>50000,'is_active'=>false]);
  $archivedPackage->packageInclusions()->create(['service_type'=>'casket','inclusion_name'=>'Casket','casket_catalog_id'=>$makeCasket('Health Casket On Archived Package',true,false)->id]);

  $noIssues = $makePackage('No Issues');
  $noIssues->packageInclusions()->create(['service_type'=>'casket','inclusion_name'=>'Casket','casket_catalog_id'=>$makeCasket('Health Casket Available',true,true)->id]);
  $noIssues->packageFreebies()->create(['freebie_catalog_id'=>$makeFreebie('Health Freebie Available',true,true)->id,'freebie_name'=>'Health Freebie Available','quantity'=>1,'unit'=>'item']);

  $optionalAddonOnly = $makePackage('Optional Add-on Only');
  $addonCatalog = AddOnCatalog::create(['name'=>'Health Optional Add-on','category'=>'Optional','price'=>100,'unit'=>'item','is_active'=>false,'is_available'=>false]);
  $legacyAddon = $optionalAddonOnly->packageAddOns()->create(['name'=>$addonCatalog->name,'price'=>100,'is_active'=>true]);
  $addonCatalog->legacyPackageAddOns()->attach($legacyAddon);

  $filter = fn (string $status, array $extra = []) => $this->get(route('admin.service-management.index',array_merge([
   'tab'=>'packages','q'=>'CatalogHealth','sort'=>'name_desc','package_status'=>$status,
  ],$extra),absolute:false))->assertOk()->viewData('packages')->pluck('id')->all();

  $this->assertEqualsCanonicalizing(
   [$unavailableCasket->id,$unavailableHearse->id,$unavailableFreebie->id,$archivedCasket->id,$archivedHearse->id,$archivedFreebie->id,$both->id,$noIssues->id,$optionalAddonOnly->id],
   $filter('active')
  );
  $this->assertEqualsCanonicalizing(
   [$unavailableCasket->id,$unavailableHearse->id,$unavailableFreebie->id,$both->id],
   $filter('active_unavailable')
  );
  $this->assertEqualsCanonicalizing(
   [$archivedPackage->id],
   $filter('archived')
  );
  $this->assertEqualsCanonicalizing(
   [$unavailableCasket->id,$unavailableHearse->id,$unavailableFreebie->id,$archivedCasket->id,$archivedHearse->id,$archivedFreebie->id,$both->id,$archivedPackage->id,$noIssues->id,$optionalAddonOnly->id],
   $filter('all')
  );
  $this->assertEqualsCanonicalizing(
   [$unavailableCasket->id,$unavailableHearse->id,$unavailableFreebie->id,$both->id],
   $filter('active_unavailable')
  );
  $this->get(route('admin.service-management.index',[
   'tab'=>'packages','q'=>'CatalogHealth','catalog_items'=>'unavailable',
  ],absolute:false))->assertOk()->assertViewHas('packages',function($rows) use ($unavailableCasket,$unavailableHearse,$unavailableFreebie,$both) {
   $this->assertEqualsCanonicalizing([$unavailableCasket->id,$unavailableHearse->id,$unavailableFreebie->id,$both->id],$rows->pluck('id')->all());
   return true;
  });
  $this->get(route('admin.service-management.index',[
   'tab'=>'packages','q'=>'CatalogHealth','package_status'=>'active_unavailable',
  ],absolute:false))->assertOk()
   ->assertViewHas('packageStatus', 'active_unavailable')
   ->assertSee('Active with Unavailable Items')
   ->assertDontSee('Included Catalog Items')
   ->assertViewHas('packages',function($rows) use ($unavailableCasket,$unavailableHearse,$unavailableFreebie,$both) {
    $this->assertEqualsCanonicalizing([$unavailableCasket->id,$unavailableHearse->id,$unavailableFreebie->id,$both->id],$rows->pluck('id')->all());
    return true;
   });
  $this->assertTrue($unavailableCasket->fresh()->is_active);
  $this->assertTrue($unavailableHearse->fresh()->is_active);
  $this->assertTrue($unavailableFreebie->fresh()->is_active);
  $this->assertFalse($archivedPackage->fresh()->is_active);
 }

 public function test_archiving_and_restoring_each_catalog_preserves_its_previous_availability(): void {
  $this->actingAs($this->admin());
  $catalogs = [
   ['casket-catalogs', CasketCatalog::class, ['standard_price'=>1000]],
   ['hearse-catalogs', HearseCatalog::class, []],
   ['freebie-catalogs', FreebieCatalog::class, ['default_unit'=>'item']],
   ['add-on-catalogs', AddOnCatalog::class, ['category'=>'General','price'=>100,'unit'=>'item']],
  ];
  foreach ($catalogs as [$route, $class, $extra]) {
   foreach ([true, false] as $wasAvailable) {
    $catalog = $class::create(array_merge(['name'=>'Restore fixture '.str_replace('-', ' ', $route).' '.($wasAvailable?'yes':'no'),'is_active'=>true,'is_available'=>$wasAvailable], $extra));
    $toggle = 'admin.'.$route.'.toggleActive';
    $this->patch(route($toggle, $catalog, absolute:false))->assertRedirect();
    $this->assertFalse($catalog->fresh()->is_active);
    $this->patch(route($toggle, $catalog, absolute:false))->assertRedirect();
    $this->assertTrue($catalog->fresh()->is_active);
    $this->assertSame($wasAvailable, (bool) $catalog->fresh()->is_available);
   }
  }
 }

}
