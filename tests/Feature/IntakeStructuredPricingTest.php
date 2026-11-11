<?php

namespace Tests\Feature;

use App\Models\AddOnCatalog;
use App\Models\Branch;
use App\Models\CasketCatalog;
use App\Models\FuneralCase;
use App\Models\HearseCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use App\Models\PackageFreebie;
use App\Models\PackageInclusion;
use App\Models\Promotion;
use App\Models\User;
use App\Support\IntakePricingService;
use App\Support\WakeDuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntakeStructuredPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_add_on_allowance_charges_only_extra_units(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-07-20 10:00:00', 'Asia/Manila'));
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Package with Flowers', 'price' => 40000, 'is_active' => true]);
        $catalog = AddOnCatalog::create(['name' => 'Flower Stand', 'unit' => 'item', 'price' => 800, 'is_active' => true, 'is_available' => true]);
        $package->packageInclusions()->create([
            'service_type' => Package::SERVICE_CUSTOM,
            'inclusion_name' => $catalog->name,
            'is_custom' => true,
            'add_on_catalog_id' => $catalog->id,
            'included_quantity' => 2,
        ]);
        $pricing = app(IntakePricingService::class);
        $input = ['included_add_on_requested' => [$catalog->id => 3]];

        $this->assertSame([], $pricing->validateSelections($package, $input, false));
        $this->assertSame(40000.0, $pricing->price($package, [])['subtotal']);
        $result = $pricing->price($package, $input);
        $this->assertSame(40800.0, $result['subtotal']);
        $this->assertSame(1, $result['snapshot']['service_charges'][0]['excess']);
        $this->assertSame(3, $result['snapshot']['included_add_ons'][0]['requested_quantity']);
        $this->assertArrayHasKey('selected_add_ons', $pricing->validateSelections($package, ['selected_add_ons' => [$catalog->id]], false));

        $this->actingAs($staff)->get('/intake/main')->assertOk()
            ->assertSee('name="included_add_on_requested['.$catalog->id.']"', false)
            ->assertSee('data-inclusion-quantity-step="1"', false)
            ->assertDontSee('id="package_adjustments_panel"', false)
            ->assertSeeInOrder(['id="packageCardList"', 'id="package-casket-options"', 'id="package-hearse-options"', 'id="package-inclusion-options"'], false)
            ->assertSee('data-allowance-context="'.$package->addOnAllowanceFingerprint().'"', false);
        $this->from('/intake/main')->post('/intake/main', $this->payload($branch, $package, $input))
            ->assertSessionHasErrors('included_add_on_requested');
        $this->post('/intake/main', $this->payload($branch, $package, $input + [
            'package_allowance_context' => $package->addOnAllowanceFingerprint(),
        ]))->assertSessionHasNoErrors();
        $case = FuneralCase::firstOrFail();
        $this->assertSame(40800.0, (float) $case->total_amount);
        $this->assertSame('Extra Flower Stand', $case->pricing_snapshot['service_charges'][0]['label']);
        $this->assertDatabaseCount('case_add_ons', 0);
    }

    public function test_selected_add_ons_are_priced_by_positive_whole_number_quantity_for_every_billing_unit(): void
    {
        $items = [
            ['Flower Arrangement', 500, 'item', 2, 1000],
            ['Memorial Set', 2000, 'set', 1, 2000],
            ['Tent Rental', 1500, 'day', 3, 4500],
            ['Transport Service', 800, 'trip', 2, 1600],
            ['Special Service', 2500, 'service', 2, 5000],
        ];
        $catalogs = collect($items)->map(fn ($item) => AddOnCatalog::create([
            'name' => $item[0], 'price' => $item[1], 'unit' => $item[2], 'is_active' => true, 'is_available' => true,
        ]));
        $quantities = $catalogs->mapWithKeys(fn (AddOnCatalog $addOn, int $index) => [$addOn->id => $items[$index][3]])->all();
        $input = ['custom_package_price' => 100, 'selected_add_ons' => $catalogs->pluck('id')->all(), 'add_on_quantities' => $quantities];
        $service = app(IntakePricingService::class);

        $pricing = $service->price(null, $input, true);

        $this->assertSame(14100.0, $pricing['add_ons_total']);
        $this->assertSame(14200.0, $pricing['subtotal']);
        $this->assertSame(14100.0, collect($pricing['snapshot']['add_ons'])->sum('line_total'));
        foreach ($items as $index => $item) {
            $snapshot = $pricing['snapshot']['add_ons'][$index];
            $this->assertSame($item[0], $snapshot['name']);
            $this->assertSame($item[2], $snapshot['unit']);
            $this->assertSame($item[3], $snapshot['quantity']);
            $this->assertSame((float) $item[4], $snapshot['line_total']);
        }
        $afterRemovingOne = $service->price(null, [
            'custom_package_price' => 100,
            'selected_add_ons' => $catalogs->skip(1)->pluck('id')->all(),
            'add_on_quantities' => collect($quantities)->except($catalogs->first()->id)->all(),
        ], true);
        $this->assertSame(13100.0, $afterRemovingOne['add_ons_total']);
        $this->assertCount(4, $afterRemovingOne['snapshot']['add_ons']);

        foreach (['0', '-1', '1.5', 'not-a-number'] as $invalid) {
            $errors = $service->validateSelections(null, [
                'selected_add_ons' => [$catalogs->first()->id],
                'add_on_quantities' => [$catalogs->first()->id => $invalid],
            ], true);
            $this->assertArrayHasKey('add_on_quantities.'.$catalogs->first()->id, $errors, "Quantity {$invalid} should be rejected.");
        }
    }

    public function test_add_on_price_change_requires_review_and_acceptance_of_latest_price(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-07-20 10:00:00', 'Asia/Manila'));
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Price Review Package', 'price' => 50000, 'is_active' => true]);
        $addOn = AddOnCatalog::create(['name' => 'Flower Arrangement', 'price' => 500, 'unit' => 'item', 'is_active' => true, 'is_available' => true]);
        $form = $this->actingAs($staff)->get('/intake/main')->assertOk()->getContent();
        $this->assertStringContainsString('data-draft-add-on-quantity', $form);
        $this->assertStringContainsString('min="1" step="1" required', $form);
        $this->assertSame(1, preg_match('/name="add_on_context_token" value="([^"]+)"/', $form, $initialMatch));
        $initialToken = html_entity_decode($initialMatch[1], ENT_QUOTES | ENT_HTML5);

        $this->from('/intake/main')->post('/intake/main', $this->payload($branch, $package, [
            'selected_add_ons' => [$addOn->id],
            'add_on_quantities' => [$addOn->id => '1.5'],
        ]))->assertRedirect('/intake/main')->assertSessionHasErrors(['add_on_quantities.'.$addOn->id]);
        $this->assertDatabaseCount('funeral_cases', 0);

        $addOn->update(['price' => 750]);
        $firstPost = $this->from('/intake/main')->post('/intake/main', $this->payload($branch, $package, [
            'selected_add_ons' => [$addOn->id],
            'add_on_quantities' => [$addOn->id => 2],
            'add_on_context_token' => $initialToken,
        ]));
        $firstPost->assertRedirect('/intake/main')->assertSessionHasNoErrors()->assertSessionHas('add_on_review', function (array $review): bool {
            return $review['items'][0]['previous_price'] === 500.0
                && $review['items'][0]['current_price'] === 750.0
                && $review['items'][0]['quantity'] === 2
                && $review['items'][0]['current_line_total'] === 1500.0;
        });
        $this->assertDatabaseCount('funeral_cases', 0);

        $reviewPage = $this->get('/intake/main')->assertOk()->assertSee('Add-on prices changed')->assertSee('Line total');
        preg_match('/name="add_on_context_token" value="([^"]+)"/', $reviewPage->getContent(), $currentMatch);
        preg_match('/name="add_on_review_token" value="([^"]+)"/', $reviewPage->getContent(), $reviewMatch);
        $currentToken = html_entity_decode($currentMatch[1], ENT_QUOTES | ENT_HTML5);
        $reviewToken = html_entity_decode($reviewMatch[1], ENT_QUOTES | ENT_HTML5);

        $addOn->update(['price' => 800]);
        $this->from('/intake/main')->post('/intake/main', $this->payload($branch, $package, [
            'selected_add_ons' => [$addOn->id],
            'add_on_quantities' => [$addOn->id => 2],
            'add_on_context_token' => $currentToken,
            'add_on_review_token' => $reviewToken,
            'add_on_acknowledged' => 1,
        ]))->assertRedirect('/intake/main')->assertSessionHasNoErrors()->assertSessionHas('add_on_review', function (array $review): bool {
            return $review['items'][0]['current_price'] === 800.0 && $review['items'][0]['current_line_total'] === 1600.0;
        });
        $this->assertDatabaseCount('funeral_cases', 0);

        $latestReviewPage = $this->get('/intake/main')->assertOk()->assertSee('Add-on prices changed');
        preg_match('/name="add_on_context_token" value="([^"]+)"/', $latestReviewPage->getContent(), $latestContextMatch);
        preg_match('/name="add_on_review_token" value="([^"]+)"/', $latestReviewPage->getContent(), $latestReviewMatch);
        $latestContext = html_entity_decode($latestContextMatch[1], ENT_QUOTES | ENT_HTML5);
        $latestReview = html_entity_decode($latestReviewMatch[1], ENT_QUOTES | ENT_HTML5);

        $this->from('/intake/main')->post('/intake/main', $this->payload($branch, $package, [
            'selected_add_ons' => [$addOn->id],
            'add_on_quantities' => [$addOn->id => 2],
            'add_on_context_token' => $latestContext,
            'add_on_review_token' => $latestReview,
            'add_on_acknowledged' => 1,
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $case = FuneralCase::query()->latest('id')->firstOrFail();
        $this->assertSame(1600.0, (float) $case->add_ons_total_amount);
        $this->assertSame(51600.0, (float) $case->total_amount);
        $this->assertSame(2, (int) $case->pricing_snapshot['add_ons'][0]['quantity']);
        $this->assertSame(800.0, (float) $case->pricing_snapshot['add_ons'][0]['price']);
        $this->assertSame(1600.0, (float) $case->pricing_snapshot['add_ons'][0]['line_total']);
        $this->assertSame('item', $case->pricing_snapshot['add_ons'][0]['unit']);
        $this->assertDatabaseHas('case_add_ons', [
            'funeral_case_id' => $case->id,
            'add_on_catalog_id' => $addOn->id,
            'quantity' => 2,
            'add_on_price_snapshot' => 800,
            'line_total' => 1600,
        ]);
        $savedSnapshot = $case->fresh()->pricing_snapshot;
        $addOn->update(['price' => 1200]);
        $this->assertSame($savedSnapshot, $case->fresh()->pricing_snapshot);
        $this->assertDatabaseHas('case_add_ons', [
            'funeral_case_id' => $case->id,
            'add_on_catalog_id' => $addOn->id,
            'add_on_price_snapshot' => 800,
            'line_total' => 1600,
        ]);
    }

    public function test_regular_hearse_upgrade_charges_positive_difference_and_snapshots_values(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $included = HearseCatalog::create(['name' => 'Included Coach', 'reference_value' => 5000, 'is_active' => true, 'is_available' => true]);
        $special = HearseCatalog::create(['name' => 'Special Coach', 'reference_value' => 8000, 'is_active' => true, 'is_available' => true]);
        $package = Package::create(['name' => 'Upgrade Package', 'price' => 50000, 'is_active' => true]);
        PackageInclusion::create(['package_id' => $package->id, 'service_type' => Package::SERVICE_HEARSE, 'inclusion_name' => 'Hearse', 'hearse_catalog_id' => $included->id, 'included_kilometers' => 25]);
        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package, ['hearse_catalog_id' => $special->id]))->assertSessionHasNoErrors();
        $case = FuneralCase::latest('id')->firstOrFail();
        $this->assertSame($special->id, $case->hearse_catalog_id);
        $this->assertSame(53000.0, (float) $case->total_amount);
        $this->assertEquals(5000, data_get($case->pricing_snapshot, 'included_hearse.reference_value'));
        $this->assertEquals(8000, data_get($case->pricing_snapshot, 'selected_hearse.reference_value'));
        $mapped = app(\App\Services\CaseDocument\FuneralContractMapper::class)->map(['service_charges' => $case->pricing_snapshot['service_charges']]);
        $this->assertEquals(3000, collect($mapped['automatic_charges'])->firstWhere('type', 'hearse_upgrade')['amount']);
        $saved = $case->pricing_snapshot;
        $special->update(['reference_value' => 12000, 'is_available' => false]);
        $included->update(['reference_value' => 9000]);
        $this->assertSame($saved, $case->fresh()->pricing_snapshot);
        $this->assertSame(53000.0, (float) $case->fresh()->total_amount);
        $this->assertSame($included->id, $package->fresh()->packageInclusions->first()->hearse_catalog_id);
    }

    public function test_intake_requires_acknowledgement_when_package_promotion_changes_after_selection(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-07-20 10:00:00', 'Asia/Manila'));
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Promotion Review Package', 'price' => 50000, 'is_active' => true]);
        $promotion = Promotion::create([
            'package_id' => $package->id,
            'name' => 'July Offer',
            'discount_type' => 'PERCENT',
            'discount_value' => 10,
            'is_enabled' => true,
        ]);

        $form = $this->actingAs($staff)->get('/intake/main')->assertOk()->getContent();
        $this->assertSame(1, preg_match('/<input(?=[^>]*name="package_id")(?=[^>]*value="'.$package->id.'")(?=[^>]*data-promo-context="([^"]+)")[^>]*>/', $form, $match));
        $contextToken = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
        $this->assertSame($package->id, (int) data_get(json_decode(\Illuminate\Support\Facades\Crypt::decryptString($contextToken), true), 'package_id'));

        $promotion->update(['discount_value' => 20]);
        $this->from('/intake/main')->post('/intake/main', $this->payload($branch, $package, [
            'promotion_context_token' => $contextToken,
        ]))->assertRedirect('/intake/main')->assertSessionHasNoErrors()->assertSessionHas('promotion_review', function (array $review): bool {
            return $review['previous_status'] === 'Active'
                && $review['current_status'] === 'Active'
                && $review['previous_package_amount'] > $review['current_package_amount'];
        });

        $reviewPage = $this->get('/intake/main')->assertOk()->assertSee('Promotion changed since package selection');
        $this->assertSame(1, preg_match('/name="promotion_review_token" value="([^"]+)"/', $reviewPage->getContent(), $reviewMatch));
        $reviewToken = html_entity_decode($reviewMatch[1], ENT_QUOTES | ENT_HTML5);

        $this->from('/intake/main')->post('/intake/main', $this->payload($branch, $package, [
            'promotion_context_token' => $contextToken,
            'promotion_review_token' => $reviewToken,
            'promotion_acknowledged' => 1,
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $case = FuneralCase::latest('id')->firstOrFail();
        $this->assertSame(40000.0, (float) $case->total_amount);
        $this->assertSame(20.0, (float) $promotion->fresh()->discount_value);
    }

    public function test_hearse_upgrade_requires_eligible_selection_and_both_reference_values(): void
    {
        $included = HearseCatalog::create(['name' => 'Baseline', 'reference_value' => 5000, 'is_active' => true, 'is_available' => true]);
        $selected = HearseCatalog::create(['name' => 'Choice', 'reference_value' => 8000, 'is_active' => true, 'is_available' => true]);
        $package = Package::create(['name' => 'Coach Bundle', 'price' => 50000, 'is_active' => true]);
        PackageInclusion::create(['package_id' => $package->id, 'service_type' => Package::SERVICE_HEARSE, 'inclusion_name' => 'Hearse', 'hearse_catalog_id' => $included->id]);
        $service = app(IntakePricingService::class);
        $input = ['hearse_catalog_id' => $selected->id];
        $this->assertSame([], $service->validateSelections($package, $input, false));
        $selected->update(['is_available' => false]);
        $this->assertArrayHasKey('hearse_catalog_id', $service->validateSelections($package->fresh(), $input, false));
        $selected->update(['is_available' => true, 'is_active' => false]);
        $this->assertArrayHasKey('hearse_catalog_id', $service->validateSelections($package->fresh(), $input, false));
        $selected->update(['is_active' => true, 'reference_value' => null]);
        $this->assertArrayHasKey('hearse_catalog_id', $service->validateSelections($package->fresh(), $input, false));
        $this->assertSame([], $service->validateSelections($package->fresh(), ['hearse_catalog_id' => $included->id], false));
        $selected->update(['reference_value' => 8000]);
        $included->update(['reference_value' => null]);
        $this->assertArrayHasKey('hearse_catalog_id', $service->validateSelections($package->fresh(), $input, false));
    }

    public function test_cheaper_hearse_has_no_refund_and_custom_all_in_has_no_upgrade_charge(): void
    {
        $included = HearseCatalog::create(['name' => 'Base Coach', 'reference_value' => 5000, 'is_active' => true]);
        $selected = HearseCatalog::create(['name' => 'Lower Coach', 'reference_value' => 3000, 'is_active' => true]);
        $package = Package::create(['name' => 'Coach Package', 'price' => 50000, 'is_active' => true]);
        PackageInclusion::create(['package_id' => $package->id, 'service_type' => Package::SERVICE_HEARSE, 'inclusion_name' => 'Hearse', 'hearse_catalog_id' => $included->id]);
        $service = app(IntakePricingService::class);
        $input = ['hearse_catalog_id' => $selected->id];
        $this->assertSame(50000.0, $service->price($package, $input)['total']);
        $selected->update(['reference_value' => 8000]);
        $this->assertSame(53000.0, $service->price($package->fresh(), $input)['total']);
        $custom = $service->price(null, $input + ['custom_package_price' => 45000], true);
        $this->assertSame(45000.0, $custom['total']);
        $this->assertSame([], $custom['service_charges']);
    }

    public function test_wake_duration_uses_inclusive_calendar_days(): void
    {
        $sameDay = WakeDuration::calculate('2026-07-20', '2026-07-20');
        $this->assertSame(1, $sameDay['days']);
        $this->assertSame(0, $sameDay['nights']);
        $this->assertSame('1 Wake Day', $sameDay['label']);

        $fourDays = WakeDuration::calculate('2026-07-20', '2026-07-23');
        $this->assertSame(4, $fourDays['days']);
        $this->assertSame(3, $fourDays['nights']);
        $this->assertSame('4 Wake Days', $fourDays['label']);

        $fiveDays = WakeDuration::calculate('2026-07-20', '2026-07-24');
        $this->assertSame(5, $fiveDays['days']);
        $this->assertSame(4, $fiveDays['nights']);
        $this->assertSame('5 Wake Days', $fiveDays['label']);
    }

    public function test_custom_agreed_casket_and_days_are_not_charged_twice_and_transport_needs_no_km(): void
    {
        $defaults = new \App\Models\CustomPackageDefault(['embalming_days' => 5, 'home_viewing_days' => 5, 'retrieval_kilometers' => 0]);
        $defaults->id = 1;
        $defaults->save();
        $casket = CasketCatalog::create(['name' => 'Agreed Casket', 'standard_price' => 15000, 'is_active' => true, 'is_available' => true]);
        $extra = AddOnCatalog::create(['name' => 'Extra flowers', 'price' => 2000, 'unit' => 'set', 'is_active' => true]);
        $input = [
            'custom_package_price' => 50000,
            'custom_service_types' => [Package::SERVICE_CASKET, Package::SERVICE_EMBALMING, Package::SERVICE_HOME_VIEWING, Package::SERVICE_HEARSE, Package::SERVICE_BODY_RETRIEVAL],
            'custom_service_coverage' => [Package::SERVICE_EMBALMING => 5, Package::SERVICE_HOME_VIEWING => 5],
            'custom_casket_catalog_id' => $casket->id,
        ];
        $input['custom_package_configuration'] = app(\App\Support\CustomPackageSelection::class)->resolve($input);
        $service = app(IntakePricingService::class);
        $included = $service->price(null, $input, true);
        $this->assertSame(50000.0, $included['total']);
        $this->assertSame([], $included['service_charges']);
        $withExtra = $service->price(null, $input + ['selected_add_ons' => [$extra->id]], true);
        $this->assertSame(52000.0, $withExtra['total']);
        $this->assertSame(2000.0, $withExtra['add_ons_total']);
        $extra->update(['is_active' => false]);
        $this->assertArrayHasKey('selected_add_ons', $service->validateSelections(null, ['selected_add_ons' => [$extra->id]], true));
    }

    public function test_pricing_service_charges_embalming_and_home_viewing_extensions_separately(): void
    {
        $package = Package::create([
            'name' => 'Four Day Coverage Package',
            'price' => 100000,
            'is_active' => true,
        ]);
        PackageInclusion::create([
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_EMBALMING,
            'inclusion_name' => 'Embalming',
            'included_days' => 4,
            'price_per_extended_day' => 1000,
            'sort_order' => 1,
        ]);
        PackageInclusion::create([
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_HOME_VIEWING,
            'inclusion_name' => 'Home Viewing',
            'included_days' => 4,
            'price_per_extended_day' => 1500,
            'sort_order' => 2,
        ]);

        $pricing = app(IntakePricingService::class)->price($package->fresh(['packageInclusions.casketCatalog', 'packageFreebies']), [
            'wake_start_date' => '2026-07-20',
            'wake_end_date' => '2026-07-24',
            'interment_at' => '2026-07-24',
            'senior_citizen_status' => 0,
            'pwd_status' => 0,
            'tax_rate' => 0,
        ]);

        $charges = collect($pricing['service_charges'])->keyBy('type');
        $this->assertSame(5, $pricing['wake_days']);
        $this->assertSame(4, $pricing['wake_nights']);
        $this->assertSame('5 Wake Days', $pricing['wake_duration']);
        $this->assertSame(1, (int) $charges[Package::SERVICE_EMBALMING]['excess']);
        $this->assertSame(1000.0, (float) $charges[Package::SERVICE_EMBALMING]['amount']);
        $this->assertSame(1, (int) $charges[Package::SERVICE_HOME_VIEWING]['excess']);
        $this->assertSame(1500.0, (float) $charges[Package::SERVICE_HOME_VIEWING]['amount']);
        $this->assertSame(2500.0, (float) $pricing['service_charges_total']);
        $this->assertSame('5 Wake Days', $pricing['snapshot']['wake_duration']);
        $chapel = app(IntakePricingService::class)->price($package, [
            'viewing_type' => 'chapel',
            'wake_start_date' => '2026-07-20',
            'wake_end_date' => '2026-07-24',
        ]);
        $this->assertSame([Package::SERVICE_EMBALMING], array_column($chapel['service_charges'], 'type'));
        $this->assertSame(1000.0, $chapel['service_charges_total']);
    }

    public function test_pricing_service_snapshots_applied_promo_metadata_for_future_display(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        $package = Package::create([
            'name' => 'Promo Package',
            'price' => 100000,
            'promo_label' => 'July Promo',
            'promo_value_type' => 'PERCENT',
            'promo_value' => 10,
            'promo_starts_at' => '2026-07-20 00:00:00',
            'promo_ends_at' => '2026-07-21 23:59:59',
            'promo_is_active' => true,
            'is_active' => true,
        ]);

        $pricing = app(IntakePricingService::class)->price($package->fresh(['packageInclusions.casketCatalog', 'packageFreebies']), [
            'wake_start_date' => '2026-07-20',
            'wake_end_date' => '2026-07-20',
            'interment_at' => '2026-07-20',
            'senior_citizen_status' => 0,
            'pwd_status' => 0,
            'tax_rate' => 0,
        ]);

        $this->assertSame('PROMO', $pricing['discount']['source']);
        $this->assertSame(10000.0, (float) $pricing['discount_amount']);
        $this->assertSame('July Promo', $pricing['snapshot']['promo']['label']);
        $this->assertSame('PERCENT', $pricing['snapshot']['promo']['value_type']);
        $this->assertSame(10.0, (float) $pricing['snapshot']['promo']['value']);
        $this->assertSame('Applied at intake', $pricing['snapshot']['promo']['status']);
    }

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
        $hearse = HearseCatalog::create(['name' => 'Main Funeral Coach', 'is_active' => true]);
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
            'hearse_catalog_id' => $hearse->id,
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
            'wake_end_date' => '2026-07-24',
            'wake_end_time' => '07:00',
            'funeral_service_at' => '2026-07-24',
            'interment_at' => '2026-07-24',
            'replacement_casket_catalog_id' => $upgradeCasket->id,
            'actual_retrieval_kilometers' => 15,
            'actual_hearse_kilometers' => 25,
            'hearse_catalog_id' => $hearse->id,
            'selected_add_ons' => [$addOn->id],
            'add_on_quantities' => [$addOn->id => 2],
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
        $this->assertSame(126250.0, (float) $case->subtotal_amount);
        $this->assertSame(126250.0, (float) $case->total_amount);
        $this->assertSame(6000.0, (float) $case->add_ons_total_amount);
        $this->assertSame(2000.0, (float) $case->additional_service_amount);
        $this->assertSame('Premium Casket', $case->coffin_type);
        $this->assertNotNull($case->pricing_snapshot);
        $this->assertSame(5, (int) $case->pricing_snapshot['wake_days']);
        $this->assertSame(4, (int) $case->pricing_snapshot['wake_nights']);
        $this->assertSame('5 Wake Days', $case->pricing_snapshot['wake_duration']);
        $this->assertSame('First Class', $case->pricing_snapshot['package']['name']);
        $this->assertSame(15000.0, (float) collect($case->pricing_snapshot['service_charges'])->firstWhere('type', 'casket_upgrade')['amount']);
        $this->assertCount(1, $case->pricing_snapshot['add_ons']);
        $this->assertSame(2, (int) $case->pricing_snapshot['add_ons'][0]['quantity']);
        $this->assertSame(6000.0, (float) $case->pricing_snapshot['add_ons'][0]['line_total']);
        $this->assertSame('service', $case->pricing_snapshot['add_ons'][0]['unit']);

        $this->assertDatabaseHas('case_add_ons', [
            'funeral_case_id' => $case->id,
            'package_add_on_id' => null,
            'add_on_catalog_id' => $addOn->id,
            'add_on_name_snapshot' => 'Organist',
            'quantity' => 2,
            'add_on_price_snapshot' => 3000,
            'line_total' => 6000,
        ]);
    }

    public function test_custom_package_preserves_agreed_price_and_adds_selected_catalog_extras(): void
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
        $freebie = FreebieCatalog::create(['name' => 'Catalog freebie', 'default_unit' => 'piece', 'is_active' => true]);

        $response = $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $placeholderPackage, [
            'package_id' => 'custom',
            'custom_package_name' => 'Family Custom',
            'custom_package_price' => 50000,
            'custom_other_inclusions' => 'Manual inclusion',
            'custom_freebie_catalog_ids' => [$freebie->id],
            'replacement_casket_catalog_id' => $casket->id,
            'actual_retrieval_kilometers' => 100,
            'actual_hearse_kilometers' => 100,
            'selected_add_ons' => [$addOn->id],
            'add_on_quantities' => [$addOn->id => 3],
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
        $this->assertSame(66000.0, (float) $case->total_amount);
        $this->assertSame(15000.0, (float) $case->add_ons_total_amount);
        $this->assertSame('CUSTOM', $case->coffin_type);
        $this->assertTrue($case->pricing_snapshot['custom_package']);
        $this->assertSame([], $case->pricing_snapshot['service_charges']);
        $this->assertSame('Choir', $case->pricing_snapshot['add_ons'][0]['name']);
        $this->assertSame(3, (int) $case->pricing_snapshot['add_ons'][0]['quantity']);
        $this->assertSame(15000.0, (float) $case->pricing_snapshot['add_ons'][0]['line_total']);
        $this->assertSame('Manual inclusion', $case->pricing_snapshot['inclusions'][0]['name']);
        $this->assertSame('Catalog freebie', $case->pricing_snapshot['freebies'][0]['name']);
        $display = app(\App\Support\CaseSnapshotDisplayService::class)->data($case);
        $this->assertSame(['Manual inclusion'], $display['inclusions']);
        $this->assertSame([], $display['basic_inclusions']);
        $this->assertSame(['Manual inclusion'], $display['other_inclusions']);
        $this->assertSame(['Catalog freebie'], $display['freebies']);
        $this->actingAs($staff)->get(route('funeral-cases.show', $case))
            ->assertOk()
            ->assertSee('Family Custom')
            ->assertSee('Manual inclusion')
            ->assertSee('Catalog freebie')
            ->assertDontSee('Custom Name')
            ->assertDontSee('>CUSTOM<', false);
        $page = $this->actingAs($staff)->get(route('funeral-cases.show', $case))->getContent();
        $printTemplate = (string) preg_replace('/^.*?<template id="casePrintTemplate">(.*?)<\/template>.*$/s', '$1', $page);
        $this->assertNotSame($page, $printTemplate);
        $this->assertStringContainsString('Family Custom', $printTemplate);
        $this->assertStringContainsString('Manual inclusion', $printTemplate);
        $this->assertStringContainsString('Catalog freebie', $printTemplate);
        $this->assertStringNotContainsString('Included Casket / Coffin', $printTemplate);

        $olderSnapshot = $case->pricing_snapshot;
        $olderSnapshot['inclusions'] = [];
        $olderSnapshot['freebies'] = [];
        $case->update(['pricing_snapshot' => $olderSnapshot]);
        $olderDisplay = app(\App\Support\CaseSnapshotDisplayService::class)->data($case->fresh());
        $this->assertSame(['Manual inclusion'], $olderDisplay['inclusions']);
        $this->assertSame(['Catalog freebie'], $olderDisplay['freebies']);
    }

    public function test_catalog_archive_ignores_historical_case_references_and_preserves_snapshots_and_payments(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role'=>'staff', 'branch_id'=>$branch->id, 'is_active'=>true]);
        $package = Package::create(['name'=>'History Package', 'price'=>50000, 'is_active'=>true]);
        $hearse = HearseCatalog::create(['name'=>'Historical Coach', 'is_active'=>true, 'is_available'=>true]);
        $package->packageInclusions()->create(['service_type'=>'hearse', 'inclusion_name'=>'Hearse', 'hearse_catalog_id'=>$hearse->id, 'included_kilometers'=>25]);
        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package))->assertSessionHasNoErrors();
        $case = FuneralCase::latest('id')->firstOrFail();
        $payment = \App\Models\Payment::create(['funeral_case_id'=>$case->id, 'branch_id'=>$branch->id, 'method'=>'CASH', 'amount'=>100, 'paid_date'=>now()->toDateString()]);
        $beforeCase = $case->fresh()->getRawOriginal();
        $beforePayment = $payment->fresh()->getRawOriginal();
        // Disabling the service removes the configured inclusion, not historical snapshots.
        $package->packageInclusions()->delete();
        $admin = User::factory()->create(['role'=>'admin', 'admin_scope'=>'main', 'branch_id'=>$branch->id, 'is_active'=>true]);
        $this->actingAs($admin)->patch(route('admin.hearse-catalogs.toggleAvailability',$hearse,absolute:false))->assertSessionHasNoErrors();
        $this->patch(route('admin.hearse-catalogs.toggleActive',$hearse,absolute:false))->assertSessionHasNoErrors();
        $this->assertFalse($hearse->fresh()->is_active);
        $this->assertSame($beforeCase, $case->fresh()->getRawOriginal());
        $this->assertSame($beforePayment, $payment->fresh()->getRawOriginal());
        $this->assertTrue($package->fresh()->is_active);
    }

    public function test_custom_package_requires_a_positive_price_and_a_real_inclusion(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        $branch = $this->branch();
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $package = Package::create([
            'name' => 'Fallback Package',
            'price' => 50000,
            'is_active' => true,
        ]);

        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package, [
            'package_id' => 'custom',
            'custom_package_name' => 'Family Custom',
            'custom_package_price' => 0,
            'custom_other_inclusions' => '',
        ]))->assertSessionHasErrors(['custom_package_price']);

        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package, [
            'package_id' => 'custom',
            'custom_package_name' => 'Family Custom',
            'custom_package_price' => 50000,
            'custom_other_inclusions' => '',
        ]))->assertSessionHasErrors(['custom_service_types']);

        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_custom_package_uses_only_available_catalog_items_and_snapshots_the_selection(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Fallback Package', 'price' => 50000, 'is_active' => true]);
        $casket = CasketCatalog::create(['name' => 'Family Casket', 'standard_price' => 15000, 'is_active' => true, 'is_available' => true]);
        $freebie = FreebieCatalog::create(['name' => 'Flower Stand', 'default_unit' => 'piece', 'is_active' => true]);
        $hearse = HearseCatalog::create(['name' => 'Custom Family Coach', 'is_active' => true]);
        $custom = [
            'package_id' => 'custom',
            'custom_package_name' => 'Family Arrangement',
            'custom_package_price' => 50000,
            'custom_service_types' => [Package::SERVICE_CASKET, Package::SERVICE_HEARSE],
            'custom_service_coverage' => [Package::SERVICE_HEARSE => 20],
            'custom_casket_catalog_id' => $casket->id,
            'custom_freebie_catalog_ids' => [$freebie->id],
            'custom_other_inclusions' => 'Memorial photo',
            'hearse_catalog_id' => $hearse->id,
        ];

        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package, $custom))
            ->assertRedirect('/funeral-cases?record_scope=main');

        $case = FuneralCase::query()->latest('id')->firstOrFail();
        $this->assertSame('Family Casket', $case->coffin_type);
        $this->assertSame($casket->id, $case->pricing_snapshot['included_casket']['id']);
        $hearseInclusion = collect($case->pricing_snapshot['inclusions'])->firstWhere('service_type', Package::SERVICE_HEARSE);
        $this->assertNotNull($hearseInclusion);
        $this->assertArrayNotHasKey('included_kilometers', $hearseInclusion);
        $this->assertSame('Flower Stand', $case->pricing_snapshot['freebies'][0]['name']);
        $this->assertSame(50000.0, (float) $case->total_amount);

        $casket->update(['is_available' => false]);
        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package, $custom + ['client_contact_number' => '09170000001', 'deceased_first_name' => 'Juan']))
            ->assertSessionHasErrors(['custom_casket_catalog_id']);
        $this->assertDatabaseCount('funeral_cases', 1);

        $casket->update(['is_available' => true]);
        $freebie->update(['is_available' => false]);
        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package, $custom + ['client_contact_number' => '09170000002', 'deceased_first_name' => 'Pedro']))
            ->assertSessionHasErrors(['custom_freebie_catalog_ids']);
        $this->assertDatabaseCount('funeral_cases', 1);

        $freebie->update(['is_available' => true]);
        $hearse->update(['is_available' => false]);
        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package, $custom + ['client_contact_number' => '09170000003', 'deceased_first_name' => 'Rafael']))
            ->assertSessionHasErrors(['hearse_catalog_id']);
        $this->assertDatabaseCount('funeral_cases', 1);
    }

    public function test_custom_package_requires_days_and_a_catalog_casket_when_selected(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Fallback Package', 'price' => 50000, 'is_active' => true]);

        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package, [
            'package_id' => 'custom',
            'custom_package_name' => 'Family Arrangement',
            'custom_package_price' => 50000,
            'custom_service_types' => [Package::SERVICE_EMBALMING],
        ]))->assertSessionHasErrors(['custom_service_coverage.embalming']);

        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $package, [
            'package_id' => 'custom',
            'custom_package_name' => 'Family Arrangement',
            'custom_package_price' => 50000,
            'custom_service_types' => [Package::SERVICE_CASKET],
        ]))->assertSessionHasErrors(['custom_casket_catalog_id']);

        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_custom_builder_only_lists_active_and_available_catalog_items(): void
    {
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        CasketCatalog::create(['name' => 'Ready Casket', 'standard_price' => 15000, 'is_active' => true, 'is_available' => true]);
        CasketCatalog::create(['name' => 'Unavailable Casket', 'standard_price' => 15000, 'is_active' => true, 'is_available' => false]);
        CasketCatalog::create(['name' => 'Archived Casket', 'standard_price' => 15000, 'is_active' => false, 'is_available' => true]);
        FreebieCatalog::create(['name' => 'Ready Freebie', 'default_unit' => 'piece', 'is_active' => true]);
        FreebieCatalog::create(['name' => 'Archived Freebie', 'default_unit' => 'piece', 'is_active' => false]);
        FreebieCatalog::create(['name' => 'Unavailable Freebie', 'default_unit' => 'piece', 'is_active' => true, 'is_available' => false]);
        HearseCatalog::create(['name' => 'Ready Hearse', 'is_active' => true]);
        HearseCatalog::create(['name' => 'Archived Hearse', 'is_active' => false]);
        HearseCatalog::create(['name' => 'Unavailable Hearse', 'is_active' => true, 'is_available' => false]);

        $response = $this->actingAs($staff)->get('/intake/main')->assertOk();
        $page = $response->getContent();
        preg_match('/<select[^>]+id="custom_casket_catalog_id"[^>]*>(.*?)<\/select>/s', $page, $casketOptions);
        preg_match('/<select[^>]+id="hearse_catalog_id"[^>]*>(.*?)<\/select>/s', $page, $hearseOptions);
        preg_match('/<div[^>]+id="custom_freebie_choices"[^>]*>(.*?)<\/div>/s', $page, $freebieOptions);
        $this->assertStringContainsString('Ready Casket', $casketOptions[1] ?? '');
        $this->assertStringNotContainsString('Unavailable Casket', $casketOptions[1] ?? '');
        $this->assertStringNotContainsString('Archived Casket', $casketOptions[1] ?? '');
        $this->assertStringContainsString('Ready Freebie', $freebieOptions[1] ?? '');
        $this->assertStringNotContainsString('Archived Freebie', $freebieOptions[1] ?? '');
        $this->assertStringNotContainsString('Unavailable Freebie', $freebieOptions[1] ?? '');
        $this->assertStringContainsString('Ready Hearse', $hearseOptions[1] ?? '');
        $this->assertStringNotContainsString('Archived Hearse', $hearseOptions[1] ?? '');
        $this->assertStringNotContainsString('Unavailable Hearse', $hearseOptions[1] ?? '');
        $this->assertStringContainsString('data-custom-asset-trigger="casket"', $page);
        $this->assertStringContainsString('data-custom-asset-trigger="hearse"', $page);
    }

    public function test_regular_package_inline_details_use_saved_values_without_preselecting_the_package(): void
    {
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create([
            'name' => 'Family Care Package',
            'short_description' => 'Saved package description',
            'price' => 88000,
            'is_active' => true,
            'promo_is_active' => true,
            'promo_label' => 'October Care',
            'promo_value_type' => 'FIXED',
            'promo_value' => 1000,
            'promo_starts_at' => now()->subDay(),
            'promo_ends_at' => now()->addDay(),
        ]);
        PackageInclusion::create([
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_HEARSE,
            'hearse_catalog_id' => \App\Models\HearseCatalog::firstOrCreate(['name' => 'Package Coach'], ['is_active' => true])->id,
            'inclusion_name' => 'Hearse',
            'included_kilometers' => 25,
            'price_per_excess_kilometer' => 75,
        ]);
        $package->packageFreebies()->create([
            'freebie_name' => 'Memorial Frame',
            'quantity' => 2,
            'unit' => 'pieces',
        ]);

        $page = $this->actingAs($staff)->get('/intake/main')->assertOk()->getContent();
        $this->assertStringContainsString('id="regular_package_choice"', $page);
        $this->assertStringContainsString('<option value="'.$package->id.'" >Family Care Package</option>', $page);
        $this->assertStringContainsString('data-description="Saved package description"', $page);
        $this->assertStringContainsString('October Care', $page);
        $this->assertStringContainsString('Memorial Frame', $page);
        $this->assertStringContainsString('&quot;included_kilometers&quot;:25', $page);
        $this->assertMatchesRegularExpression('/type="radio"[^>]+value="'.$package->id.'"(?![^>]*checked)/', $page);
        $this->assertStringContainsString('data-inline-package-details', $page);
    }

    public function test_other_branch_intake_rejects_a_posted_custom_package(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $mainBranch = $this->branch();
        $otherBranch = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Other Branch', 'address' => 'Other', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => $mainBranch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Standard', 'price' => 50000, 'is_active' => true]);

        $this->actingAs($admin)
            ->from('/intake/other')
            ->post('/intake/other', $this->payload($otherBranch, $package, [
                'package_id' => 'custom',
                'custom_package_name' => 'Other Branch Custom',
                'custom_package_price' => 50000,
            ]))
            ->assertRedirect('/intake/other')
            ->assertSessionHasErrors('package_id');

        $this->assertDatabaseCount('funeral_cases', 0);
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
                        'hearse_catalog_id' => \App\Models\HearseCatalog::firstOrCreate(['name' => 'Mapping Coach'], ['is_active' => true])->id,
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
            'hearse_catalog_id' => \App\Models\HearseCatalog::firstOrCreate(['name' => 'Mapping Coach'], ['is_active' => true])->id,
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
            'wake_end_date' => '2026-07-26',
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

    public function test_simplified_adjustments_have_zero_charge_when_not_applied(): void
    {
        [$package] = $this->adjustmentPackage();

        $pricing = app(IntakePricingService::class)->price($package, $this->adjustmentPayload([
            'apply_retrieval_excess' => 0,
            'apply_hearse_excess' => 0,
        ]));

        $this->assertSame(0.0, (float) $pricing['service_charges_total']);
        $this->assertSame([], $pricing['service_charges']);
        $this->assertFalse($pricing['snapshot']['adjustments']['body_retrieval']['applied']);
        $this->assertFalse($pricing['snapshot']['adjustments']['hearse']['applied']);
        $this->assertSame(0.0, (float) $pricing['snapshot']['adjustments']['body_retrieval']['charge']);
        $this->assertSame(0.0, (float) $pricing['snapshot']['adjustments']['hearse']['charge']);
    }

    public function test_simplified_adjustments_charge_retrieval_excess_only(): void
    {
        [$package] = $this->adjustmentPackage();

        $pricing = app(IntakePricingService::class)->price($package, $this->adjustmentPayload([
            'apply_retrieval_excess' => 1,
            'retrieval_excess_kilometers' => 6,
            'apply_hearse_excess' => 0,
        ]));

        $charges = collect($pricing['service_charges'])->keyBy('type');
        $this->assertSame(600.0, (float) $charges[Package::SERVICE_BODY_RETRIEVAL]['amount']);
        $this->assertFalse($charges->has(Package::SERVICE_HEARSE));
        $this->assertSame(6.0, (float) $pricing['snapshot']['adjustments']['body_retrieval']['excess_kilometers']);
        $this->assertSame(100.0, (float) $pricing['snapshot']['adjustments']['body_retrieval']['rate']);
        $this->assertSame(600.0, (float) $pricing['snapshot']['adjustments']['body_retrieval']['charge']);
    }

    public function test_simplified_adjustments_charge_hearse_excess_only(): void
    {
        [$package] = $this->adjustmentPackage();

        $pricing = app(IntakePricingService::class)->price($package, $this->adjustmentPayload([
            'apply_retrieval_excess' => 0,
            'apply_hearse_excess' => 1,
            'hearse_excess_kilometers' => 8,
        ]));

        $charges = collect($pricing['service_charges'])->keyBy('type');
        $this->assertFalse($charges->has(Package::SERVICE_BODY_RETRIEVAL));
        $this->assertSame(400.0, (float) $charges[Package::SERVICE_HEARSE]['amount']);
        $this->assertSame(8.0, (float) $pricing['snapshot']['adjustments']['hearse']['excess_kilometers']);
        $this->assertSame(50.0, (float) $pricing['snapshot']['adjustments']['hearse']['rate']);
        $this->assertSame(400.0, (float) $pricing['snapshot']['adjustments']['hearse']['charge']);
    }

    public function test_simplified_adjustments_charge_positive_casket_upgrade_difference_only(): void
    {
        [$package, $upgradeCasket] = $this->adjustmentPackage();

        $pricing = app(IntakePricingService::class)->price($package, $this->adjustmentPayload([
            'replacement_casket_catalog_id' => $upgradeCasket->id,
            'apply_retrieval_excess' => 0,
            'apply_hearse_excess' => 0,
        ]));

        $charge = collect($pricing['service_charges'])->firstWhere('type', 'casket_upgrade');
        $this->assertSame(15000.0, (float) $charge['amount']);
        $this->assertTrue($pricing['snapshot']['adjustments']['casket']['applied']);
        $this->assertSame(30000.0, (float) $pricing['snapshot']['adjustments']['casket']['included_reference_value']);
        $this->assertSame(45000.0, (float) $pricing['snapshot']['adjustments']['casket']['selected_reference_value']);
        $this->assertSame(15000.0, (float) $pricing['snapshot']['adjustments']['casket']['charge']);
    }

    public function test_simplified_adjustments_can_combine_retrieval_hearse_and_casket(): void
    {
        [$package, $upgradeCasket] = $this->adjustmentPackage();

        $pricing = app(IntakePricingService::class)->price($package, $this->adjustmentPayload([
            'replacement_casket_catalog_id' => $upgradeCasket->id,
            'apply_retrieval_excess' => 1,
            'retrieval_excess_kilometers' => 3.5,
            'apply_hearse_excess' => 1,
            'hearse_excess_kilometers' => 2,
        ]));

        $charges = collect($pricing['service_charges'])->keyBy('type');
        $this->assertSame(350.0, (float) $charges[Package::SERVICE_BODY_RETRIEVAL]['amount']);
        $this->assertSame(100.0, (float) $charges[Package::SERVICE_HEARSE]['amount']);
        $this->assertSame(15000.0, (float) $charges['casket_upgrade']['amount']);
        $this->assertSame(15450.0, (float) $pricing['service_charges_total']);
        $this->assertSame(3.5, (float) $pricing['snapshot']['adjustments']['body_retrieval']['excess_kilometers']);
        $this->assertSame(2.0, (float) $pricing['snapshot']['adjustments']['hearse']['excess_kilometers']);
    }

    public function test_hearse_assignment_obeys_package_eligibility_and_keeps_snapshot_without_changing_total(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $hearse = HearseCatalog::create(['name' => 'Original Coach', 'is_active' => true]);
        $plainPackage = Package::create(['name' => 'No Hearse Package', 'price' => 50000, 'is_active' => true]);

        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $plainPackage, [
            'hearse_catalog_id' => $hearse->id,
        ]))->assertRedirect('/funeral-cases?record_scope=main');
        $plainCase = FuneralCase::latest('id')->firstOrFail();
        $this->assertNull($plainCase->hearse_catalog_id);
        $this->assertNull($plainCase->hearse_name_snapshot);
        $this->assertSame(50000.0, (float) $plainCase->total_amount);

        $legacyHearsePackage = Package::create([
            'name' => 'Legacy Hearse Package',
            'price' => 60000,
            'inclusions' => 'Hearse',
            'is_active' => true,
        ]);
        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $legacyHearsePackage, [
            'client_contact_number' => '09170000777',
            'client_first_name' => 'Legacy',
            'deceased_first_name' => 'Coach',
        ]))->assertSessionHasErrors(['hearse_catalog_id']);

        $withHearse = Package::create(['name' => 'Hearse Package', 'price' => 70000, 'is_active' => true]);
        PackageInclusion::create([
            'package_id' => $withHearse->id,
            'service_type' => Package::SERVICE_HEARSE,
            'hearse_catalog_id' => $hearse->id,
            'inclusion_name' => 'Hearse',
            'included_kilometers' => 20,
            'price_per_excess_kilometer' => 50,
        ]);
        $differentPerson = ['client_contact_number' => '09170000999', 'client_first_name' => 'Maria', 'deceased_first_name' => 'Pedro'];

        $hearse->update(['is_active' => false]);
        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $withHearse, $differentPerson + ['hearse_catalog_id' => $hearse->id]))
            ->assertSessionHasErrors(['package_id']);
        $hearse->update(['is_active' => true]);

        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $withHearse, $differentPerson))
            ->assertRedirect('/funeral-cases?record_scope=main');
        $case = FuneralCase::latest('id')->firstOrFail();
        $this->assertSame($hearse->id, $case->hearse_catalog_id);
        $this->assertSame('Original Coach', $case->hearse_name_snapshot);
        $this->assertSame('Original Coach', $case->pricing_snapshot['selected_hearse']['name']);
        $this->assertSame(70000.0, (float) $case->total_amount);

        $hearse->update(['name' => 'Renamed Coach', 'is_active' => false]);
        $this->assertSame('Original Coach', $case->fresh()->hearse_name_snapshot);
    }

    public function test_custom_hearse_selection_is_required_preserved_and_cleared_when_deselected(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $fallback = Package::create(['name' => 'Custom Hearse Fallback', 'price' => 50000, 'is_active' => true]);
        $hearse = HearseCatalog::create(['name' => 'Family Coach', 'is_active' => true]);
        $custom = [
            'package_id' => 'custom',
            'custom_package_name' => 'Custom Coach Arrangement',
            'custom_package_price' => 65000,
            'custom_service_types' => [Package::SERVICE_HEARSE],
            'hearse_catalog_id' => $hearse->id,
        ];

        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $fallback, array_diff_key($custom, ['hearse_catalog_id' => true])))
            ->assertSessionHasErrors(['hearse_catalog_id']);

        $hearse->update(['is_active' => false]);
        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $fallback, $custom))
            ->assertSessionHasErrors(['hearse_catalog_id'])
            ->assertSessionHasInput('hearse_catalog_id', $hearse->id);

        $hearse->update(['is_active' => true]);
        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $fallback, $custom))
            ->assertRedirect('/funeral-cases?record_scope=main');
        $withHearse = FuneralCase::latest('id')->firstOrFail();
        $this->assertSame('Family Coach', $withHearse->hearse_name_snapshot);
        $this->assertSame(65000.0, (float) $withHearse->total_amount);

        $withoutHearse = array_merge($custom, [
            'custom_package_name' => 'Custom Without Coach',
            'custom_service_types' => [Package::SERVICE_BODY_RETRIEVAL],
            'hearse_catalog_id' => $hearse->id,
            'client_contact_number' => '09170000888',
            'client_first_name' => 'Elena',
            'deceased_first_name' => 'Ramon',
        ]);
        $this->actingAs($staff)->post('/intake/main', $this->payload($branch, $fallback, $withoutHearse))
            ->assertRedirect('/funeral-cases?record_scope=main');
        $deselectedCase = FuneralCase::latest('id')->firstOrFail();
        $this->assertNull($deselectedCase->hearse_catalog_id);
        $this->assertNull($deselectedCase->hearse_name_snapshot);
        $this->assertNull(data_get($deselectedCase->pricing_snapshot, 'selected_hearse'));
        $this->assertSame(65000.0, (float) $deselectedCase->total_amount);
    }

    public function test_intake_rejects_stale_service_configuration_before_saving(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Updated package', 'price' => 50000, 'is_active' => true]);
        $shownConfiguration = $package->intakeConfigurationFingerprint();
        $package->update(['price' => 55000]);

        $this->actingAs($staff)->from('/intake/main')->post('/intake/main', $this->payload($branch, $package, [
            'package_configuration_context' => $shownConfiguration,
        ]))->assertSessionHasErrors('package_id');

        $oldDefaults = \Illuminate\Support\Facades\Crypt::encryptString(json_encode([
            'coverage' => ['embalming' => 3], 'rates' => [],
        ], JSON_THROW_ON_ERROR));
        $this->from('/intake/main')->post('/intake/main', $this->payload($branch, $package, [
            'package_id' => 'custom', 'custom_package_name' => 'Custom care', 'custom_package_price' => 50000,
            'custom_defaults_context' => $oldDefaults,
            'custom_service_types' => [Package::SERVICE_EMBALMING, Package::SERVICE_HOME_VIEWING],
            'custom_included' => [Package::SERVICE_EMBALMING => 3, Package::SERVICE_HOME_VIEWING => 6],
        ]))->assertSessionHasErrors('package_id')->assertSessionHasInput('custom_included', [Package::SERVICE_HOME_VIEWING => 6]);
        $this->assertDatabaseCount('funeral_cases', 0);
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
        $addOnContext = ['items' => AddOnCatalog::query()
            ->where('is_active', true)
            ->where('is_available', true)
            ->get()
            ->mapWithKeys(fn (AddOnCatalog $addOn) => [(string) $addOn->id => [
                'name' => (string) $addOn->name,
                'price' => number_format((float) $addOn->price, 2, '.', ''),
                'unit' => (string) $addOn->unit,
                'is_active' => (bool) $addOn->is_active,
                'is_available' => (bool) $addOn->is_available,
            ]])->all()];

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
            'wake_end_date' => '2026-07-21',
            'wake_end_time' => '08:00',
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
            'payment_method' => 'none',
            'add_on_context_token' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($addOnContext, JSON_THROW_ON_ERROR)),
        ], $overrides);
    }

    private function adjustmentPackage(): array
    {
        $includedCasket = CasketCatalog::create([
            'name' => 'Included Contract Casket',
            'type_or_material' => 'Metal',
            'standard_price' => 30000,
            'is_active' => true,
        ]);
        $upgradeCasket = CasketCatalog::create([
            'name' => 'Upgrade Contract Casket',
            'type_or_material' => 'Hardwood',
            'standard_price' => 45000,
            'is_active' => true,
        ]);
        $package = Package::create([
            'name' => 'Contract Adjustment Package',
            'price' => 100000,
            'coffin_type' => $includedCasket->name,
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
            'hearse_catalog_id' => \App\Models\HearseCatalog::firstOrCreate(['name' => 'Package Coach'], ['is_active' => true])->id,
            'inclusion_name' => 'Hearse',
            'included_kilometers' => 20,
            'price_per_excess_kilometer' => 50,
            'sort_order' => 2,
        ]);
        PackageInclusion::create([
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_CASKET,
            'casket_catalog_id' => $includedCasket->id,
            'inclusion_name' => 'Casket',
            'casket_type' => $includedCasket->name,
            'sort_order' => 3,
        ]);

        return [$package->fresh(['packageInclusions.casketCatalog', 'packageFreebies']), $upgradeCasket];
    }

    private function adjustmentPayload(array $overrides = []): array
    {
        return array_merge([
            'wake_start_date' => '2026-07-20',
            'wake_end_date' => '2026-07-20',
            'interment_at' => '2026-07-20',
            'senior_citizen_status' => 0,
            'pwd_status' => 0,
            'tax_rate' => 0,
        ], $overrides);
    }
}
