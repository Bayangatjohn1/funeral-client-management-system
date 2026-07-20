<?php

namespace Tests\Feature;

use App\Models\AddOnCatalog;
use App\Models\Branch;
use App\Models\CaseAddOn;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\PackageAddOn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAddOnCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_admin_can_add_edit_archive_and_restore_global_add_ons(): void
    {
        $admin = $this->mainAdmin();

        $this->actingAs($admin)
            ->get(route('admin.add-on-catalogs.index', absolute: false))
            ->assertOk()
            ->assertSee('Add-on Catalog')
            ->assertSee('separate from package inclusions');

        $this->actingAs($admin)
            ->post(route('admin.add-on-catalogs.store', absolute: false), [
                'name' => 'Extra Flower Arrangement',
                'category' => 'Flowers',
                'description' => 'Optional additional arrangement.',
                'price' => 2500,
                'unit' => 'set',
            ])
            ->assertRedirect(route('admin.add-on-catalogs.index', absolute: false));

        $catalog = AddOnCatalog::where('name', 'Extra Flower Arrangement')->firstOrFail();
        $this->assertTrue($catalog->is_active);
        $this->assertDatabaseHas('add_on_catalogs', [
            'id' => $catalog->id,
            'category' => 'Flowers',
            'price' => 2500,
            'unit' => 'set',
            'is_active' => 1,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.add-on-catalogs.update', $catalog, absolute: false), [
                'name' => 'Extra Flower Arrangement Deluxe',
                'category' => 'Memorial Decor',
                'description' => '',
                'price' => 0,
                'unit' => 'service',
            ])
            ->assertRedirect(route('admin.add-on-catalogs.index', absolute: false));

        $this->assertDatabaseHas('add_on_catalogs', [
            'id' => $catalog->id,
            'name' => 'Extra Flower Arrangement Deluxe',
            'category' => 'Memorial Decor',
            'description' => null,
            'price' => 0,
            'unit' => 'service',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.add-on-catalogs.toggleActive', $catalog, absolute: false))
            ->assertRedirect();

        $this->assertFalse($catalog->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.add-on-catalogs.toggleActive', $catalog, absolute: false))
            ->assertRedirect();

        $this->assertTrue($catalog->fresh()->is_active);
    }

    public function test_add_on_catalog_validation_and_duplicate_normalized_name_prevention(): void
    {
        $admin = $this->mainAdmin();

        AddOnCatalog::create([
            'name' => 'Extra Chairs',
            'category' => 'Facilities',
            'price' => 50,
            'unit' => 'item',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.add-on-catalogs.store', absolute: false), [
                'name' => '',
                'price' => -1,
                'unit' => '',
            ])
            ->assertSessionHasErrors(['name', 'price', 'unit']);

        $this->actingAs($admin)
            ->post(route('admin.add-on-catalogs.store', absolute: false), [
                'name' => ' extra   chairs ',
                'category' => 'Facilities',
                'price' => 75,
                'unit' => 'item',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, AddOnCatalog::whereRaw('LOWER(name) = ?', ['extra chairs'])->count());
    }

    public function test_archive_restore_preserves_legacy_package_and_case_add_on_records(): void
    {
        $admin = $this->mainAdmin();
        $branch = $admin->branch;
        $package = Package::create([
            'name' => 'Legacy Package',
            'coffin_type' => 'Legacy Coffin',
            'price' => 20000,
            'is_active' => true,
        ]);
        $legacy = PackageAddOn::create([
            'package_id' => $package->id,
            'name' => 'Legacy Tent',
            'description' => 'Package-level legacy add-on.',
            'price' => 3000,
            'is_active' => true,
        ]);
        $catalog = AddOnCatalog::create([
            'name' => 'Legacy Tent',
            'category' => 'Facilities',
            'description' => 'Package-level legacy add-on.',
            'price' => 3000,
            'unit' => 'item',
            'is_active' => true,
        ]);
        $catalog->legacyPackageAddOns()->attach($legacy->id);

        $client = Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Legacy Client',
            'relationship_to_deceased' => 'Other',
            'valid_id_type' => 'Legacy Record',
            'valid_id_number' => 'LEGACY-ADDON-001',
        ]);
        $deceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => 'Legacy Deceased',
        ]);
        $case = FuneralCase::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_code' => 'CASE-ADDON-001',
            'case_number' => 1,
            'service_type' => 'traditional',
            'service_requested_at' => now()->toDateString(),
            'service_package' => 'Legacy Package',
            'coffin_type' => 'Legacy Coffin',
            'wake_location' => 'Legacy Wake Location',
            'funeral_service_at' => now()->addDays(2)->toDateString(),
            'interment_at' => now()->addDays(2)->setTime(10, 0),
            'total_amount' => 23000,
            'total_paid' => 0,
            'balance_amount' => 23000,
            'payment_status' => 'UNPAID',
            'case_status' => 'Pending',
            'entry_source' => 'walk_in',
        ]);
        CaseAddOn::create([
            'funeral_case_id' => $case->id,
            'package_add_on_id' => $legacy->id,
            'add_on_name_snapshot' => 'Legacy Tent',
            'add_on_description_snapshot' => 'Package-level legacy add-on.',
            'add_on_price_snapshot' => 3000,
            'quantity' => 1,
            'line_total' => 3000,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.add-on-catalogs.toggleActive', $catalog, absolute: false))
            ->assertRedirect();

        $this->assertDatabaseHas('package_add_ons', [
            'id' => $legacy->id,
            'name' => 'Legacy Tent',
            'price' => 3000,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('case_add_ons', [
            'funeral_case_id' => $case->id,
            'package_add_on_id' => $legacy->id,
            'add_on_name_snapshot' => 'Legacy Tent',
            'line_total' => 3000,
        ]);
        $this->assertDatabaseHas('add_on_catalog_package_add_on', [
            'add_on_catalog_id' => $catalog->id,
            'package_add_on_id' => $legacy->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.add-on-catalogs.toggleActive', $catalog, absolute: false))
            ->assertRedirect();

        $this->assertTrue($catalog->fresh()->is_active);
        $this->assertSame('Legacy Tent', $legacy->fresh()->name);
    }

    private function mainAdmin(): User
    {
        $branch = Branch::create([
            'branch_code' => 'BR001',
            'branch_name' => 'Main Branch',
            'branch_type' => 'main',
            'address' => 'Test Address',
            'is_active' => true,
        ]);

        return User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'main',
            'branch_id' => $branch->id,
            'is_active' => true,
            'can_encode_any_branch' => true,
        ]);
    }
}
