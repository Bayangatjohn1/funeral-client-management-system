<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use App\Models\PackageAddOn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPackageStructuredWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_admin_can_save_and_reload_structured_first_and_second_class_packages(): void
    {
        $admin = $this->mainAdmin();
        $folder = FreebieCatalog::create(['name' => 'Memorial Folder', 'default_unit' => 'set', 'is_active' => true]);
        $flowers = FreebieCatalog::create(['name' => 'Flower Arrangement', 'default_unit' => 'piece', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.packages.store', absolute: false), [
                'name' => 'First Class Verification',
                'short_description' => 'Premium service setup for verification.',
                'price' => 250000,
                'included_services' => [
                    Package::SERVICE_BODY_RETRIEVAL => [
                        'enabled' => 1,
                        'included_kilometers' => 25,
                        'price_per_excess_kilometer' => 75,
                    ],
                    Package::SERVICE_EMBALMING => [
                        'enabled' => 1,
                        'included_days' => 3,
                        'price_per_extended_day' => 1500,
                    ],
                    Package::SERVICE_CASKET => [
                        'enabled' => 1,
                        'casket_type' => 'Metal Casket',
                    ],
                    Package::SERVICE_HOME_VIEWING => [
                        'enabled' => 1,
                        'included_days' => 5,
                        'price_per_extended_day' => 2000,
                    ],
                    Package::SERVICE_HEARSE => [
                        'enabled' => 1,
                        'included_kilometers' => 20,
                        'price_per_excess_kilometer' => 100,
                    ],
                ],
                'custom_inclusions' => [
                    ['description' => 'Memorial candle setup'],
                ],
                'freebies' => [
                    ['freebie_catalog_id' => $folder->id, 'quantity' => 2, 'unit' => 'sets'],
                    ['freebie_catalog_id' => $flowers->id, 'quantity' => 1, 'unit' => 'piece'],
                    ['freebie_name' => 'Thank-you cards', 'quantity' => 50, 'unit' => 'pcs'],
                ],
                'promo_is_active' => 1,
                'promo_label' => 'Launch Promo',
                'promo_value_type' => 'PERCENT',
                'promo_value' => 10,
                'promo_starts_at' => '2026-07-19',
                'promo_ends_at' => '2026-07-20',
            ])
            ->assertRedirect(route('admin.packages.index', absolute: false));

        $firstClass = Package::where('name', 'First Class Verification')->firstOrFail();
        $this->assertTrue($firstClass->is_active);
        $this->assertSame('Metal Casket', $firstClass->coffin_type);
        $this->assertStringContainsString('Casket: Metal Casket', $firstClass->inclusions);
        $this->assertStringContainsString('Memorial candle setup', $firstClass->inclusions);
        $this->assertSame('2026-07-20 23:59:59', $firstClass->promo_ends_at->setTimezone('Asia/Manila')->format('Y-m-d H:i:s'));

        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $firstClass->id,
            'service_type' => Package::SERVICE_BODY_RETRIEVAL,
            'included_kilometers' => 25,
            'price_per_excess_kilometer' => 75,
        ]);
        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $firstClass->id,
            'service_type' => Package::SERVICE_EMBALMING,
            'included_days' => 3,
            'price_per_extended_day' => 1500,
        ]);
        $this->assertDatabaseHas('package_freebies', [
            'package_id' => $firstClass->id,
            'freebie_catalog_id' => $folder->id,
            'freebie_name' => 'Memorial Folder',
            'quantity' => 2,
            'unit' => 'sets',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.packages.edit', $firstClass, absolute: false))
            ->assertOk()
            ->assertSee('First Class Verification')
            ->assertSee('Metal Casket')
            ->assertSee('Memorial candle setup')
            ->assertSee('Launch Promo')
            ->assertSee('Review and Save');

        $this->actingAs($admin)
            ->put(route('admin.packages.update', $firstClass, absolute: false), [
                'name' => 'First Class Verification',
                'short_description' => 'Premium service setup for verification.',
                'price' => 255000,
                'included_services' => [
                    Package::SERVICE_BODY_RETRIEVAL => [
                        'enabled' => 1,
                        'included_kilometers' => 30,
                        'price_per_excess_kilometer' => 80,
                    ],
                    Package::SERVICE_CASKET => [
                        'enabled' => 1,
                        'casket_type' => 'Premium Hardwood Casket',
                    ],
                ],
                'freebies' => [
                    ['freebie_catalog_id' => $flowers->id, 'quantity' => 2, 'unit' => 'pieces'],
                ],
                'promo_is_active' => 1,
                'promo_label' => 'Launch Promo Updated',
                'promo_value_type' => 'AMOUNT',
                'promo_value' => 5000,
                'promo_starts_at' => '2026-07-19',
                'promo_ends_at' => '2026-07-21',
            ])
            ->assertRedirect(route('admin.packages.index', absolute: false));

        $firstClass->refresh();
        $this->assertSame('Premium Hardwood Casket', $firstClass->coffin_type);
        $this->assertEquals('255000.00', $firstClass->price);
        $this->assertSame('Launch Promo Updated', $firstClass->promo_label);
        $this->assertDatabaseMissing('package_inclusions', [
            'package_id' => $firstClass->id,
            'service_type' => Package::SERVICE_HOME_VIEWING,
        ]);
        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $firstClass->id,
            'service_type' => Package::SERVICE_BODY_RETRIEVAL,
            'included_kilometers' => 30,
            'price_per_excess_kilometer' => 80,
        ]);
        $this->assertDatabaseHas('package_freebies', [
            'package_id' => $firstClass->id,
            'freebie_name' => 'Flower Arrangement',
            'quantity' => 2,
            'unit' => 'pieces',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.packages.store', absolute: false), [
                'name' => 'Second Class Verification',
                'short_description' => 'Standard service setup for verification.',
                'price' => 125000,
                'included_services' => [
                    Package::SERVICE_BODY_RETRIEVAL => [
                        'enabled' => 1,
                        'included_kilometers' => 15,
                        'price_per_excess_kilometer' => 60,
                    ],
                    Package::SERVICE_CASKET => [
                        'enabled' => 1,
                        'casket_type' => 'Standard Wood Casket',
                    ],
                    Package::SERVICE_HEARSE => [
                        'enabled' => 1,
                        'included_kilometers' => 10,
                        'price_per_excess_kilometer' => 90,
                    ],
                ],
                'freebies' => [
                    ['freebie_catalog_id' => $folder->id, 'quantity' => 1, 'unit' => 'set'],
                ],
                'promo_is_active' => 0,
                'promo_label' => 'Should clear',
                'promo_value_type' => 'PERCENT',
                'promo_value' => 5,
            ])
            ->assertRedirect(route('admin.packages.index', absolute: false));

        $secondClass = Package::where('name', 'Second Class Verification')->firstOrFail();
        $this->assertSame('Standard Wood Casket', $secondClass->coffin_type);
        $this->assertFalse($secondClass->promo_is_active);
        $this->assertNull($secondClass->promo_label);
        $this->assertSame('Inactive', $secondClass->promo_status);

        $this->actingAs($admin)
            ->get(route('admin.packages.edit', $secondClass, absolute: false))
            ->assertOk()
            ->assertSee('Second Class Verification')
            ->assertSee('Standard Wood Casket')
            ->assertSee('Review and Save');
    }

    public function test_legacy_packages_still_display_and_package_updates_do_not_touch_legacy_add_ons(): void
    {
        $admin = $this->mainAdmin();

        $this->actingAs($admin)
            ->post(route('admin.packages.store', absolute: false), [
                'name' => 'Legacy Display Package',
                'coffin_type' => 'Classic Coffin',
                'price' => 50000,
                'inclusions' => "Basic setup\nHearse use",
                'freebies' => "Flowers\nTarpaulin",
                'promo_is_active' => 0,
            ])
            ->assertRedirect(route('admin.packages.index', absolute: false));

        $legacy = Package::where('name', 'Legacy Display Package')->firstOrFail();
        $legacyAddOn = PackageAddOn::create([
            'package_id' => $legacy->id,
            'name' => 'Legacy Extra Chairs',
            'description' => 'Existing package-specific add-on',
            'price' => 1000,
            'is_active' => true,
        ]);

        $this->assertSame('Classic Coffin', $legacy->coffin_type);
        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $legacy->id,
            'service_type' => Package::SERVICE_CASKET,
            'casket_type' => 'Classic Coffin',
        ]);
        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $legacy->id,
            'service_type' => Package::SERVICE_CUSTOM,
            'inclusion_name' => 'Basic setup',
        ]);
        $this->assertDatabaseHas('package_freebies', [
            'package_id' => $legacy->id,
            'freebie_name' => 'Flowers',
            'quantity' => 1,
            'unit' => 'item',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.packages.index', absolute: false))
            ->assertOk()
            ->assertSee('Legacy Display Package')
            ->assertSee('Classic Coffin')
            ->assertSee('Basic setup')
            ->assertSee('Flowers');

        $this->actingAs($admin)
            ->get(route('admin.packages.edit', $legacy, absolute: false))
            ->assertOk()
            ->assertSee('Legacy Display Package')
            ->assertSee('Classic Coffin')
            ->assertSee('Basic setup')
            ->assertSee('Flowers');

        $this->actingAs($admin)
            ->put(route('admin.packages.update', $legacy, absolute: false), [
                'name' => 'Legacy Display Package',
                'price' => 52000,
                'included_services' => [
                    Package::SERVICE_CASKET => [
                        'enabled' => 1,
                        'casket_type' => 'Classic Coffin',
                    ],
                ],
                'custom_inclusions' => [
                    ['description' => 'Basic setup'],
                ],
                'freebies' => [
                    ['freebie_name' => 'Flowers', 'quantity' => 1, 'unit' => 'item'],
                ],
                'promo_is_active' => 0,
            ])
            ->assertRedirect(route('admin.packages.index', absolute: false));

        $this->assertDatabaseHas('package_add_ons', [
            'id' => $legacyAddOn->id,
            'package_id' => $legacy->id,
            'name' => 'Legacy Extra Chairs',
            'price' => 1000,
            'is_active' => true,
        ]);
    }

    public function test_casket_catalog_integrates_with_package_setup_and_preserves_legacy_snapshots(): void
    {
        $admin = $this->mainAdmin();
        $premium = CasketCatalog::create([
            'name' => 'Premium Hardwood Casket',
            'type_or_material' => 'Hardwood',
            'standard_price' => 45000,
            'description' => 'Catalog-backed casket.',
            'is_active' => true,
        ]);
        $unpriced = CasketCatalog::create([
            'name' => 'Migrated Standard Casket',
            'type_or_material' => null,
            'standard_price' => 0,
            'description' => 'Migrated from existing package casket text. Price not configured.',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.packages.store', absolute: false), [
                'name' => 'Catalog Casket Package',
                'price' => 85000,
                'included_services' => [
                    Package::SERVICE_CASKET => [
                        'enabled' => 1,
                        'casket_catalog_id' => $premium->id,
                    ],
                ],
                'promo_is_active' => 0,
            ])
            ->assertRedirect(route('admin.packages.index', absolute: false));

        $package = Package::where('name', 'Catalog Casket Package')->firstOrFail();
        $this->assertSame('Premium Hardwood Casket (Hardwood)', $package->coffin_type);
        $this->assertStringContainsString('Casket: Premium Hardwood Casket (Hardwood)', $package->inclusions);
        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_CASKET,
            'casket_catalog_id' => $premium->id,
            'casket_type' => 'Premium Hardwood Casket (Hardwood)',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.packages.edit', $package, absolute: false))
            ->assertOk()
            ->assertSee('Premium Hardwood Casket')
            ->assertSee('data-price="45000"', false);

        $this->actingAs($admin)
            ->get(route('admin.casket-catalogs.index', absolute: false))
            ->assertOk()
            ->assertSee('Price not configured')
            ->assertSee('Migrated Standard Casket');

        $catalogCountBeforeDuplicate = CasketCatalog::count();

        $this->actingAs($admin)
            ->post(route('admin.casket-catalogs.store', absolute: false), [
                'name' => 'Premium Hardwood Casket',
                'type_or_material' => 'Hardwood',
                'standard_price' => 50000,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame($catalogCountBeforeDuplicate, CasketCatalog::count());
        $this->assertFalse($unpriced->fresh()->price_configured);
    }

    public function test_main_admin_can_manage_casket_catalog_with_duplicate_prevention(): void
    {
        $admin = $this->mainAdmin();

        $this->actingAs($admin)
            ->post(route('admin.casket-catalogs.store', absolute: false), [
                'name' => 'First Class Hardwood',
                'type_or_material' => 'Hardwood',
                'standard_price' => 35000,
                'description' => 'Included casket for premium packages.',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.casket-catalogs.index', absolute: false));

        $this->actingAs($admin)
            ->post(route('admin.casket-catalogs.store', absolute: false), [
                'name' => 'Zero Value New Casket',
                'type_or_material' => 'Wood',
                'standard_price' => 0,
            ])
            ->assertSessionHasErrors('standard_price');

        $catalog = CasketCatalog::where('name', 'First Class Hardwood')->firstOrFail();
        $this->assertSame('First Class Hardwood (Hardwood)', $catalog->display_name);
        $this->assertTrue($catalog->price_configured);
        $this->assertTrue($catalog->is_active);

        $this->actingAs($admin)
            ->put(route('admin.casket-catalogs.update', $catalog, absolute: false), [
                'name' => 'First Class Hardwood',
                'type_or_material' => 'Hardwood',
                'standard_price' => 38000,
                'description' => '',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.casket-catalogs.index', absolute: false));

        $this->assertDatabaseHas('casket_catalogs', [
            'id' => $catalog->id,
            'standard_price' => 38000,
            'description' => null,
        ]);

        $catalogCountBeforeDuplicate = CasketCatalog::count();

        $this->actingAs($admin)
            ->post(route('admin.casket-catalogs.store', absolute: false), [
                'name' => ' first   class hardwood ',
                'type_or_material' => ' hardwood ',
                'standard_price' => 39000,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame($catalogCountBeforeDuplicate, CasketCatalog::count());
    }

    public function test_package_casket_modal_uses_catalog_validation_and_json_updates(): void
    {
        $admin = $this->mainAdmin();
        $catalog = CasketCatalog::create([
            'name' => 'Modal Starter Casket',
            'type_or_material' => 'Wood',
            'standard_price' => 22000,
            'description' => 'Original details',
            'is_active' => true,
        ]);
        $package = Package::create([
            'name' => 'Modal Reference Package',
            'coffin_type' => 'Modal Starter Casket (Wood)',
            'price' => 125000,
            'inclusions' => 'Casket: Modal Starter Casket (Wood)',
            'is_active' => true,
        ]);
        $package->packageInclusions()->create([
            'service_type' => Package::SERVICE_CASKET,
            'casket_catalog_id' => $catalog->id,
            'inclusion_name' => 'Casket',
            'casket_type' => 'Modal Starter Casket (Wood)',
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.casket-catalogs.store', absolute: false), [
                'name' => '',
                'standard_price' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'standard_price']);

        $this->actingAs($admin)
            ->postJson(route('admin.casket-catalogs.store', absolute: false), [
                'name' => 'Modal Starter Casket',
                'type_or_material' => 'Wood',
                'standard_price' => 23000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $created = $this->actingAs($admin)
            ->postJson(route('admin.casket-catalogs.store', absolute: false), [
                'name' => 'Modal Added Casket',
                'type_or_material' => 'Metal',
                'standard_price' => 33000,
                'description' => 'Saved from package modal',
            ])
            ->assertCreated()
            ->assertJsonPath('catalog.name', 'Modal Added Casket')
            ->assertJsonPath('catalog.material', 'Metal')
            ->json('catalog');

        $this->assertDatabaseHas('casket_catalogs', [
            'id' => $created['id'],
            'name' => 'Modal Added Casket',
            'is_active' => 1,
        ]);

        $this->actingAs($admin)
            ->putJson(route('admin.casket-catalogs.update', $catalog, absolute: false), [
                'name' => 'Modal Edited Casket',
                'type_or_material' => 'Hardwood',
                'standard_price' => 25000,
                'description' => 'Updated from package modal',
            ])
            ->assertOk()
            ->assertJsonPath('catalog.name', 'Modal Edited Casket')
            ->assertJsonPath('catalog.update_url', route('admin.casket-catalogs.update', $catalog, absolute: false));

        $this->assertDatabaseHas('casket_catalogs', [
            'id' => $catalog->id,
            'name' => 'Modal Edited Casket',
            'type_or_material' => 'Hardwood',
            'standard_price' => 25000,
        ]);
        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'price' => 125000,
            'coffin_type' => 'Modal Starter Casket (Wood)',
        ]);
        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $package->id,
            'casket_catalog_id' => $catalog->id,
            'casket_type' => 'Modal Starter Casket (Wood)',
        ]);
    }

    public function test_casket_reference_value_rules_for_add_configure_edit_and_archive(): void
    {
        $admin = $this->mainAdmin();
        $historicalZero = CasketCatalog::create([
            'name' => 'Historical Zero Casket',
            'type_or_material' => 'Legacy',
            'standard_price' => 0,
            'description' => 'Migrated without a configured value.',
            'is_active' => true,
        ]);
        $configured = CasketCatalog::create([
            'name' => 'Configured Casket',
            'type_or_material' => 'Hardwood',
            'standard_price' => 18000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.casket-catalogs.store', absolute: false), [
                'name' => 'Invalid Added Casket',
                'type_or_material' => 'Metal',
                'standard_price' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['standard_price']);

        $this->actingAs($admin)
            ->putJson(route('admin.casket-catalogs.update', $historicalZero, absolute: false), [
                'name' => 'Historical Zero Casket',
                'type_or_material' => 'Legacy',
                'standard_price' => 0,
                'description' => 'Trying to configure without a value.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['standard_price']);

        $this->actingAs($admin)
            ->putJson(route('admin.casket-catalogs.update', $historicalZero, absolute: false), [
                'name' => 'Historical Zero Casket',
                'type_or_material' => 'Legacy',
                'standard_price' => 12000,
                'description' => 'Configured with a usable value.',
            ])
            ->assertOk()
            ->assertJsonPath('catalog.price', 12000);

        $this->actingAs($admin)
            ->putJson(route('admin.casket-catalogs.update', $configured, absolute: false), [
                'name' => 'Configured Casket',
                'type_or_material' => 'Hardwood',
                'standard_price' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['standard_price']);

        $archivableZero = CasketCatalog::create([
            'name' => 'Archivable Historical Zero',
            'type_or_material' => null,
            'standard_price' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.casket-catalogs.toggleActive', $archivableZero, absolute: false))
            ->assertRedirect();

        $this->assertDatabaseHas('casket_catalogs', [
            'id' => $archivableZero->id,
            'standard_price' => 0,
            'is_active' => 0,
        ]);
    }

    public function test_package_form_renders_in_page_casket_modal_without_new_tab_catalog_flow(): void
    {
        $admin = $this->mainAdmin();
        CasketCatalog::create([
            'name' => 'Inline Modal Casket',
            'type_or_material' => 'Wood',
            'standard_price' => 20000,
            'description' => 'Visible in modal data',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.packages.create', absolute: false))
            ->assertOk()
            ->assertSee('data-casket-modal', false)
            ->assertSee('Add New Casket')
            ->assertSee('data-open-casket-modal', false)
            ->assertSee('data-casket-modal-cancel', false)
            ->assertSee('showToast(data.message', false)
            ->assertSee('data-package-submit', false)
            ->assertSee('data-update-url', false)
            ->assertSee('Edit Casket')
            ->assertSee('Configure Casket')
            ->assertSee("casketField('standard_price').min = '0.01'", false)
            ->assertDontSee('target="_blank"', false)
            ->assertDontSee('Open Casket Catalog');
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
