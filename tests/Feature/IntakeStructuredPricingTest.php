<?php

namespace Tests\Feature;

use App\Models\AddOnCatalog;
use App\Models\Branch;
use App\Models\CasketCatalog;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\PackageFreebie;
use App\Models\PackageInclusion;
use App\Models\User;
use App\Support\IntakePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntakeStructuredPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_package_uses_structured_limits_catalog_add_ons_and_snapshots(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        $branch = $this->branch();
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $includedCasket = CasketCatalog::create([
            'name' => 'First Class Casket',
            'type_or_material' => 'Metal',
            'standard_price' => 30000,
            'is_active' => true,
        ]);
        $upgradeCasket = CasketCatalog::create([
            'name' => 'Premium Casket',
            'type_or_material' => 'Hardwood',
            'standard_price' => 45000,
            'is_active' => true,
        ]);
        $package = Package::create([
            'name' => 'First Class',
            'short_description' => 'Structured first class package',
            'coffin_type' => 'First Class Casket',
            'price' => 100000,
            'is_active' => true,
        ]);

        PackageInclusion::create([
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_BODY_RETRIEVAL,
            'inclusion_name' => 'Body Retrieval',
            'included_kilometers' => 10,
            'price_per_excess_kilometer' => 100,
            'sort_order' => 1,
        ]);
        PackageInclusion::create([
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_HEARSE,
            'inclusion_name' => 'Hearse',
            'included_kilometers' => 20,
            'price_per_excess_kilometer' => 50,
            'sort_order' => 2,
        ]);
        PackageInclusion::create([
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_EMBALMING,
            'inclusion_name' => 'Embalming',
            'included_days' => 3,
            'price_per_extended_day' => 1000,
            'sort_order' => 3,
        ]);
        PackageInclusion::create([
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_HOME_VIEWING,
            'inclusion_name' => 'Home Viewing',
            'included_days' => 4,
            'price_per_extended_day' => 500,
            'sort_order' => 4,
        ]);
        PackageInclusion::create([
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_CASKET,
            'casket_catalog_id' => $includedCasket->id,
            'inclusion_name' => 'Casket',
            'casket_type' => $includedCasket->name,
            'sort_order' => 5,
        ]);
        PackageFreebie::create([
            'package_id' => $package->id,
            'freebie_name' => 'Flower Stand',
            'quantity' => 2,
            'unit' => 'set',
            'sort_order' => 1,
        ]);

        $addOn = AddOnCatalog::create([
            'name' => 'Organist',
            'category' => 'Music',
            'price' => 3000,
            'unit' => 'service',
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package, [
            'wake_start_date' => '2026-07-20',
            'funeral_service_at' => '2026-07-24',
            'interment_at' => '2026-07-24',
            'replacement_casket_catalog_id' => $upgradeCasket->id,
            'actual_retrieval_kilometers' => 15,
            'actual_hearse_kilometers' => 25,
            'selected_add_ons' => [$addOn->id],
            'additional_service_items' => [
                ['description' => 'Tent extension', 'amount' => 2000],
            ],
            'additional_service_amount' => 2000,
            'additional_services' => 'Tent extension',
        ]));

        $response->assertRedirect('/funeral-cases?record_scope=main');

        $case = FuneralCase::query()->latest('id')->first();
        $this->assertSame(5, (int) $case->deceased->wake_days);
        $this->assertSame(5, (int) $case->serviceDetail->wake_days);
        $this->assertSame(123250.0, (float) $case->subtotal_amount);
        $this->assertSame(123250.0, (float) $case->total_amount);
        $this->assertSame(3000.0, (float) $case->add_ons_total_amount);
        $this->assertSame(2000.0, (float) $case->additional_service_amount);
        $this->assertSame('Premium Casket', $case->coffin_type);
        $this->assertNotNull($case->pricing_snapshot);
        $this->assertSame(5, (int) $case->pricing_snapshot['wake_days']);
        $this->assertSame('First Class', $case->pricing_snapshot['package']['name']);
        $this->assertSame(15000.0, (float) collect($case->pricing_snapshot['service_charges'])->firstWhere('type', 'casket_upgrade')['amount']);
        $this->assertCount(1, $case->pricing_snapshot['add_ons']);

        $this->assertDatabaseHas('case_add_ons', [
            'funeral_case_id' => $case->id,
            'package_add_on_id' => null,
            'add_on_catalog_id' => $addOn->id,
            'add_on_name_snapshot' => 'Organist',
        ]);
    }

    public function test_custom_package_preserves_manual_pricing_and_skips_automatic_catalog_charges(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        $branch = $this->branch();
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $placeholderPackage = Package::create([
            'name' => 'Second Class',
            'price' => 80000,
            'is_active' => true,
        ]);
        $addOn = AddOnCatalog::create([
            'name' => 'Choir',
            'price' => 5000,
            'unit' => 'service',
            'is_active' => true,
        ]);
        $casket = CasketCatalog::create([
            'name' => 'Upgrade',
            'standard_price' => 50000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $placeholderPackage, [
            'package_id' => 'custom',
            'custom_package_name' => 'Family Custom',
            'custom_package_price' => 50000,
            'custom_package_inclusions' => 'Manual inclusion',
            'custom_package_freebies' => 'Manual freebie',
            'replacement_casket_catalog_id' => $casket->id,
            'actual_retrieval_kilometers' => 100,
            'actual_hearse_kilometers' => 100,
            'selected_add_ons' => [$addOn->id],
            'additional_service_items' => [
                ['description' => 'Manual extra', 'amount' => 1000],
            ],
            'additional_service_amount' => 1000,
            'additional_services' => 'Manual extra',
        ]));

        $response->assertRedirect('/funeral-cases?record_scope=main');

        $case = FuneralCase::query()->latest('id')->first();
        $this->assertSame('Family Custom', $case->service_package);
        $this->assertSame(50000.0, (float) $case->package_price_snapshot);
        $this->assertSame(51000.0, (float) $case->total_amount);
        $this->assertSame(0.0, (float) $case->add_ons_total_amount);
        $this->assertSame('CUSTOM', $case->coffin_type);
        $this->assertTrue($case->pricing_snapshot['custom_package']);
        $this->assertSame([], $case->pricing_snapshot['service_charges']);
        $this->assertSame([], $case->pricing_snapshot['add_ons']);
    }

    public function test_admin_package_service_limits_reload_into_intake_details_and_pricing_service(): void
    {
        $branch = $this->branch();
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'main',
            'branch_id' => $branch->id,
            'is_active' => true,
            'can_encode_any_branch' => true,
        ]);
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $casket = CasketCatalog::create([
            'name' => 'Mapping Test Casket',
            'type_or_material' => 'Maple',
            'standard_price' => 43210,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.packages.store', absolute: false), [
                'name' => 'Mapping Verification Package',
                'price' => 150000,
                'included_services' => [
                    'retrieval' => [
                        'enabled' => 1,
                        'included_kilometers' => 12,
                        'price_per_excess_kilometer' => 123,
                    ],
                    'hearse_service' => [
                        'enabled' => 1,
                        'included_kilometers' => 23,
                        'price_per_excess_kilometer' => 321,
                    ],
                    'embalming_service' => [
                        'enabled' => 1,
                        'included_days' => 2,
                        'price_per_extended_day' => 2222,
                    ],
                    'viewing' => [
                        'enabled' => 1,
                        'included_days' => 4,
                        'price_per_extended_day' => 4444,
                    ],
                    'coffin' => [
                        'enabled' => 1,
                        'casket_catalog_id' => $casket->id,
                    ],
                ],
                'freebies' => [
                    ['freebie_name' => 'Mapping Freebie', 'quantity' => 7, 'unit' => 'pcs'],
                ],
                'promo_is_active' => 0,
            ])
            ->assertRedirect(route('admin.packages.index', absolute: false));

        $package = Package::with(['packageInclusions.casketCatalog', 'packageFreebies'])
            ->where('name', 'Mapping Verification Package')
            ->firstOrFail();

        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_BODY_RETRIEVAL,
            'included_kilometers' => 12,
            'price_per_excess_kilometer' => 123,
        ]);
        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_HEARSE,
            'included_kilometers' => 23,
            'price_per_excess_kilometer' => 321,
        ]);
        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_EMBALMING,
            'included_days' => 2,
            'price_per_extended_day' => 2222,
        ]);
        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_HOME_VIEWING,
            'included_days' => 4,
            'price_per_extended_day' => 4444,
        ]);
        $this->assertDatabaseHas('package_inclusions', [
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_CASKET,
            'casket_catalog_id' => $casket->id,
            'casket_type' => $casket->display_name,
        ]);
        $this->assertDatabaseHas('package_freebies', [
            'package_id' => $package->id,
            'freebie_name' => 'Mapping Freebie',
            'quantity' => 7,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($staff)
            ->get(route('intake.main.create', absolute: false))
            ->assertOk();

        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/<input[^>]+name="package_id"[^>]+value="' . $package->id . '"[^>]*>/s',
            $html,
            'The intake package radio should be rendered.'
        );
        preg_match(
            '/<input[^>]+name="package_id"[^>]+value="' . $package->id . '"[^>]*>/s',
            $html,
            $matches
        );
        $packageInput = $matches[0];
        preg_match('/data-structured-inclusions="([^"]+)"/', $packageInput, $inclusionMatches);
        preg_match('/data-structured-freebies="([^"]+)"/', $packageInput, $freebieMatches);

        $intakeInclusions = collect(json_decode(html_entity_decode($inclusionMatches[1]), true))->keyBy('service_type');
        $intakeFreebies = collect(json_decode(html_entity_decode($freebieMatches[1]), true));

        $this->assertTrue(
            $intakeInclusions->has(Package::SERVICE_BODY_RETRIEVAL),
            'Intake inclusion keys: ' . $intakeInclusions->keys()->implode(', ')
        );
        $this->assertSame(12, $intakeInclusions[Package::SERVICE_BODY_RETRIEVAL]['included_kilometers']);
        $this->assertSame(123.0, (float) $intakeInclusions[Package::SERVICE_BODY_RETRIEVAL]['price_per_excess_kilometer']);
        $this->assertSame(23, $intakeInclusions[Package::SERVICE_HEARSE]['included_kilometers']);
        $this->assertSame(321.0, (float) $intakeInclusions[Package::SERVICE_HEARSE]['price_per_excess_kilometer']);
        $this->assertSame(2, $intakeInclusions[Package::SERVICE_EMBALMING]['included_days']);
        $this->assertSame(2222.0, (float) $intakeInclusions[Package::SERVICE_EMBALMING]['price_per_extended_day']);
        $this->assertSame(4, $intakeInclusions[Package::SERVICE_HOME_VIEWING]['included_days']);
        $this->assertSame(4444.0, (float) $intakeInclusions[Package::SERVICE_HOME_VIEWING]['price_per_extended_day']);
        $this->assertSame($casket->id, $intakeInclusions[Package::SERVICE_CASKET]['casket']['id']);
        $this->assertSame('Mapping Test Casket', $intakeInclusions[Package::SERVICE_CASKET]['casket']['name']);
        $this->assertSame('Maple', $intakeInclusions[Package::SERVICE_CASKET]['casket']['material']);
        $this->assertSame(43210.0, (float) $intakeInclusions[Package::SERVICE_CASKET]['casket']['reference_value']);
        $this->assertSame('Mapping Freebie', $intakeFreebies->first()['name']);
        $this->assertSame(7, $intakeFreebies->first()['quantity']);
        $this->assertSame('pcs', $intakeFreebies->first()['unit']);

        $pricing = app(IntakePricingService::class)->price($package->fresh(['packageInclusions.casketCatalog', 'packageFreebies']), [
            'wake_start_date' => '2026-07-20',
            'interment_at' => '2026-07-26',
            'actual_retrieval_kilometers' => 18,
            'actual_hearse_kilometers' => 31,
            'senior_citizen_status' => 0,
            'pwd_status' => 0,
            'tax_rate' => 0,
        ]);

        $charges = collect($pricing['service_charges'])->keyBy('type');
        $this->assertSame(738.0, (float) $charges[Package::SERVICE_BODY_RETRIEVAL]['amount']);
        $this->assertSame(2568.0, (float) $charges[Package::SERVICE_HEARSE]['amount']);
        $this->assertSame(11110.0, (float) $charges[Package::SERVICE_EMBALMING]['amount']);
        $this->assertSame(13332.0, (float) $charges[Package::SERVICE_HOME_VIEWING]['amount']);
        $this->assertSame(27748.0, (float) $pricing['service_charges_total']);
    }

    private function branch(): Branch
    {
        return Branch::create([
            'branch_code' => 'BR001',
            'branch_name' => 'Main Branch',
            'branch_type' => 'main',
            'address' => 'Main',
            'is_active' => true,
        ]);
    }

    private function payload(Branch $branch, Package $package, array $overrides = []): array
    {
        return array_merge([
            'service_requested_at' => '2026-07-20',
            'branch_id' => $branch->id,
            'client_first_name' => 'Maria',
            'client_last_name' => 'Client',
            'client_relationship' => 'Daughter',
            'client_contact_number' => '09170000000',
            'client_address' => 'Client Address',
            'deceased_first_name' => 'Jose',
            'deceased_last_name' => 'Deceased',
            'deceased_address' => 'Client Address',
            'born' => '1980-01-01',
            'died' => '2026-07-19',
            'civil_status' => 'WIDOWED',
            'wake_location' => 'Family Residence',
            'wake_start_date' => '2026-07-20',
            'wake_start_time' => '08:00',
            'funeral_service_at' => '2026-07-21',
            'funeral_service_time' => '09:00',
            'interment_at' => '2026-07-21',
            'interment_time' => '10:00',
            'place_of_cemetery' => 'Public Cemetery',
            'case_status' => 'ACTIVE',
            'transport_option' => 'HEARSE',
            'coffin_length_cm' => 175,
            'coffin_size' => 'LARGE',
            'embalming_required' => 1,
            'embalming_status' => 'PENDING',
            'package_id' => $package->id,
            'additional_service_amount' => 0,
            'senior_citizen_status' => 0,
            'pwd_status' => 0,
            'tax_rate' => 0,
            'confirm_review' => 1,
        ], $overrides);
    }
}
