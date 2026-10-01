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
            ->assertSee('Selectable Package')
            ->assertSee('Active Add-on Visible')
            ->assertSee('Available Casket Visible')
            ->assertDontSee('Archived Package Hidden')
            ->assertDontSee('Archived Add-on Hidden')
            ->assertSee('Unavailable Casket Visible')
            ->assertSee('— Unavailable')
            ->assertDontSee('Archived Casket Hidden')
            ->assertDontSee('Archived Freebie Hidden');
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
