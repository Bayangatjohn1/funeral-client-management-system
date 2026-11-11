<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Package;
use App\Models\Promotion;
use App\Models\User;
use App\Services\BranchSync\SyncSchema;
use App\Services\BranchSync\SyncSnapshot;
use App\Support\IntakePricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PackagePromotionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function systemAdmin(): User
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'is_active' => true]);
        $user = User::factory()->create([
            'role' => 'admin', 'admin_scope' => 'system', 'branch_id' => $branch->id, 'is_active' => true,
        ]);
        $this->actingAs($user);

        return $user;
    }

    private function promotion(array $attributes = []): Promotion
    {
        return Promotion::create(array_replace([
            'name' => 'Memorial Care',
            'discount_type' => 'PERCENT',
            'discount_value' => 10,
            'is_enabled' => true,
        ], $attributes));
    }

    public function test_effective_statuses_are_distinct_and_use_manila_calendar_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 12:00:00', 'Asia/Manila'));
        $this->assertSame('No Promotion', Package::create(['name' => 'No Promo', 'price' => 10000])->promo_status);

        $scheduled = $this->promotion(['starts_at' => '2026-11-02', 'ends_at' => '2026-11-30']);
        $this->assertSame('Scheduled', $scheduled->statusAt());
        $this->travelTo(Carbon::parse('2026-11-02 00:00:00', 'Asia/Manila'));
        $this->assertSame('Active', $scheduled->statusAt());

        $expired = $this->promotion(['ends_at' => '2026-10-31']);
        $this->assertSame('Expired', $expired->statusAt());

        $stopped = $this->promotion(['manually_stopped_at' => now('Asia/Manila'), 'manually_stopped_by' => null]);
        $this->assertSame('Turned Off', $stopped->statusAt());

        $invalid = $this->promotion(['name' => null, 'discount_type' => null, 'discount_value' => null]);
        $this->assertSame('Incomplete Setup', $invalid->statusAt());
        $this->assertArrayHasKey('name', $invalid->reviewFields());
        $this->assertArrayHasKey('discount_type', $invalid->reviewFields());
        $this->assertArrayHasKey('discount_value', $invalid->reviewFields());
    }

    public function test_incomplete_legacy_promotion_is_preserved_and_never_applied(): void
    {
        $package = Package::create(['name' => 'Legacy Package', 'price' => 10000, 'is_active' => true]);
        $promotion = $this->promotion([
            'package_id' => $package->id, 'name' => null, 'discount_type' => 'UNKNOWN', 'discount_value' => 0,
        ]);
        $package->load('promotion');

        $this->assertSame('Incomplete Setup', $package->promotionStatusAt());
        $this->assertFalse($package->isPromoEffectiveAt());
        $this->assertSame(0.0, app(IntakePricingService::class)->price($package, [])['discount_amount']);

        $this->actingAs($this->systemAdmin())
            ->get(route('admin.promotions.show', $promotion))
            ->assertOk()
            ->assertSee('Incomplete Setup')
            ->assertSee('Promotion Name is required.')
            ->assertSee('Select a valid discount type: Amount or Percentage.')
            ->assertSee('Discount Value must be greater than zero.');
    }

    public function test_legacy_package_promotion_requires_a_recorded_manual_stop_and_valid_configuration(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 12:00:00', 'Asia/Manila'));

        $unassigned = Package::create(['name' => 'No Promotion', 'price' => 10000, 'is_active' => true]);
        $this->assertSame('No Promotion', $unassigned->promotionStatusAt());

        $ambiguousDisabled = Package::create([
            'name' => 'Legacy Disabled', 'price' => 10000, 'is_active' => true,
            'promo_is_active' => false, 'promo_label' => 'Old Offer', 'promo_value_type' => 'AMOUNT', 'promo_value' => 500,
        ]);
        $this->assertSame('Incomplete Setup', $ambiguousDisabled->promotionStatusAt());
        $this->assertContains('Promotion Status', $ambiguousDisabled->legacyPromotionReviewFields());
        $this->assertFalse($ambiguousDisabled->isPromoEffectiveAt());

        $manuallyStopped = Package::create([
            'name' => 'Legacy Stopped', 'price' => 10000, 'is_active' => true,
            'promo_is_active' => false, 'promo_label' => 'Paused Offer', 'promo_value_type' => 'AMOUNT', 'promo_value' => 500,
            'promo_manually_stopped_at' => now('Asia/Manila'),
        ]);
        $this->assertSame('Turned Off', $manuallyStopped->promotionStatusAt());

        $invalidValue = Package::create([
            'name' => 'Legacy Invalid', 'price' => 10000, 'is_active' => true,
            'promo_is_active' => true, 'promo_label' => 'Invalid Offer', 'promo_value_type' => 'PERCENT', 'promo_value' => 150,
        ]);
        $this->assertSame('Incomplete Setup', $invalidValue->promotionStatusAt());
        $this->assertContains('Discount Value', $invalidValue->legacyPromotionReviewFields());
    }

    public function test_promotion_migration_preserves_valid_incomplete_stopped_and_unassigned_legacy_rows(): void
    {
        $timestamp = now()->toDateTimeString();
        $packages = [
            ['name' => 'No Promotion', 'promo_label' => null, 'promo_value_type' => null, 'promo_value' => null, 'promo_starts_at' => null, 'promo_ends_at' => null, 'promo_is_active' => false, 'promo_manually_stopped_at' => null],
            ['name' => 'Valid Promotion', 'promo_label' => 'November Offer', 'promo_value_type' => 'PERCENT', 'promo_value' => 10, 'promo_starts_at' => '2026-11-01', 'promo_ends_at' => '2026-11-30', 'promo_is_active' => true, 'promo_manually_stopped_at' => null],
            ['name' => 'Incomplete Promotion', 'promo_label' => 'Legacy Offer', 'promo_value_type' => null, 'promo_value' => null, 'promo_starts_at' => null, 'promo_ends_at' => null, 'promo_is_active' => true, 'promo_manually_stopped_at' => null],
            ['name' => 'Stopped Promotion', 'promo_label' => 'Stopped Offer', 'promo_value_type' => 'AMOUNT', 'promo_value' => 500, 'promo_starts_at' => '2026-11-01', 'promo_ends_at' => '2026-11-30', 'promo_is_active' => false, 'promo_manually_stopped_at' => '2026-10-30 10:00:00'],
        ];

        foreach ($packages as $row) {
            DB::table('packages')->insert([
                ...$row,
                'price' => 10000,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        Schema::dropIfExists('promotions');
        $migration = require database_path('migrations/2026_11_01_000001_create_promotions_and_migrate_package_promotions.php');
        $migration->up();

        $this->assertSame(3, DB::table('promotions')->count());
        $valid = Promotion::where('name', 'November Offer')->firstOrFail();
        $stopped = Promotion::where('name', 'Stopped Offer')->firstOrFail();
        $incomplete = Promotion::where('name', 'Legacy Offer')->firstOrFail();
        $this->assertSame('PERCENT', $valid->discount_type);
        $this->assertSame('2026-11-01', $valid->starts_at->format('Y-m-d'));
        $this->assertSame('2026-11-30', $valid->ends_at->format('Y-m-d'));
        $this->assertNotNull($stopped->manually_stopped_at);
        $this->assertSame('Incomplete Setup', $incomplete->statusAt());
        $this->assertSame('November Offer', DB::table('packages')->where('name', 'Valid Promotion')->value('promo_label'));
        $this->assertNull(DB::table('promotions')->where('name', 'November Offer')->whereNull('package_id')->value('id'));
    }

    public function test_system_admin_can_create_and_correct_promotions_but_invalid_values_are_rejected(): void
    {
        $admin = $this->systemAdmin();
        $this->post(route('admin.promotions.store'), [
            'name' => 'October Offer', 'discount_type' => 'PERCENT', 'discount_value' => 15,
            'starts_at' => '2026-10-01', 'ends_at' => '2026-10-31',
        ])->assertRedirect()->assertSessionHas('success');
        $promotion = Promotion::firstOrFail();
        $this->assertSame($admin->id, $promotion->created_by);
        $this->assertSame('Active', $promotion->statusAt(Carbon::parse('2026-10-15', 'Asia/Manila')));

        $this->put(route('admin.promotions.update', $promotion), [
            'name' => 'October Offer', 'discount_type' => 'PERCENT', 'discount_value' => 101,
        ])->assertSessionHasErrors('discount_value');
        $this->assertSame(15.0, (float) $promotion->fresh()->discount_value);

        $this->put(route('admin.promotions.update', $promotion), [
            'name' => 'Corrected Offer', 'discount_type' => 'AMOUNT', 'discount_value' => 100,
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('Corrected Offer', $promotion->fresh()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'promotion.updated', 'actor_id' => $admin->id]);
    }

    public function test_promotion_form_validation_uses_specific_messages_and_preserves_submitted_values(): void
    {
        $this->systemAdmin();

        $this->post(route('admin.promotions.store'), [])->assertSessionHasErrors([
            'name' => 'Promotion Name is required.',
            'discount_type' => 'Discount Type is required.',
            'discount_value' => 'Discount Value is required.',
        ]);

        $this->post(route('admin.promotions.store'), [
            'name' => str_repeat('A', 121), 'discount_type' => 'UNKNOWN', 'discount_value' => 0,
        ])->assertSessionHasErrors([
            'name' => 'Promotion Name must not exceed 120 characters.',
            'discount_type' => 'Select a valid discount type: Amount or Percentage.',
            'discount_value' => 'Discount Value must be greater than zero.',
        ]);

        $this->post(route('admin.promotions.store'), [
            'name' => 'Offer <script>', 'discount_type' => 'AMOUNT', 'discount_value' => '12.345',
        ])->assertSessionHasErrors([
            'name' => 'Promotion Name contains unsupported characters. Use valid letters, numbers, spaces, and punctuation.',
            'discount_value' => 'Discount Value can have up to two decimal places.',
        ])->assertSessionHasInput('name', 'Offer <script>');

        $this->post(route('admin.promotions.store'), [
            'name' => 'Negative Offer', 'discount_type' => 'AMOUNT', 'discount_value' => -1,
        ])->assertSessionHasErrors('discount_value', 'Discount Value must be greater than zero.');

        $this->post(route('admin.promotions.store'), [
            'name' => 'Too Large', 'discount_type' => 'AMOUNT', 'discount_value' => '10000000000.00',
        ])->assertSessionHasErrors('discount_value', 'Discount Value cannot exceed the maximum allowed amount.');
    }

    public function test_promotion_discount_limits_and_assigned_package_price_are_enforced(): void
    {
        $this->systemAdmin();
        $package = Package::create(['name' => 'Promotion Package', 'price' => 5000, 'is_active' => true]);

        $this->post(route('admin.promotions.store'), [
            'name' => 'Full Percentage', 'discount_type' => 'PERCENT', 'discount_value' => 100,
        ])->assertRedirect()->assertSessionHas('success');

        $this->post(route('admin.promotions.store'), [
            'name' => 'Over Percentage', 'discount_type' => 'PERCENT', 'discount_value' => 100.01,
        ])->assertSessionHasErrors('discount_value', 'Percentage discount cannot exceed 100%.');

        $promotion = $this->promotion(['package_id' => $package->id, 'discount_type' => 'AMOUNT', 'discount_value' => 5000]);
        $this->put(route('admin.promotions.update', $promotion), [
            'name' => 'Equal to Base', 'discount_type' => 'AMOUNT', 'discount_value' => '5000.00',
        ])->assertRedirect()->assertSessionHas('success');

        $this->put(route('admin.promotions.update', $promotion), [
            'name' => 'Greater than Base', 'discount_type' => 'AMOUNT', 'discount_value' => '5000.01',
        ])->assertSessionHasErrors('discount_value', "Fixed discount cannot exceed the assigned package's base price.");
        $this->assertSame('Equal to Base', $promotion->fresh()->name);
    }

    public function test_lowering_an_assigned_packages_base_price_revalidates_its_fixed_promotion(): void
    {
        $this->systemAdmin();
        $package = Package::create(['name' => 'Discounted Package', 'price' => 5000, 'is_active' => true]);
        $promotion = $this->promotion([
            'name' => 'Fixed Offer', 'package_id' => $package->id, 'discount_type' => 'AMOUNT', 'discount_value' => 4000,
        ]);

        $this->put(route('admin.packages.update', $package), [
            'name' => 'Discounted Package', 'price' => 3999, 'promotion_id' => $promotion->id,
            'included_services' => ['body_retrieval' => ['enabled' => 1, 'included_kilometers' => 0, 'price_per_excess_kilometer' => 0]],
        ])->assertSessionHasErrors('promotion_id', "Fixed discount cannot exceed the assigned package's base price.");

        $this->assertSame(5000.0, (float) $package->fresh()->price);
        $this->assertSame($package->id, $promotion->fresh()->package_id);
    }

    public function test_promotion_date_rules_accept_optional_dates_and_reject_invalid_or_reversed_dates(): void
    {
        $this->systemAdmin();
        $base = ['name' => 'Date Offer', 'discount_type' => 'AMOUNT', 'discount_value' => 100];

        $this->post(route('admin.promotions.store'), [...$base, 'starts_at' => '', 'ends_at' => ''])
            ->assertRedirect()->assertSessionHas('success');
        $this->post(route('admin.promotions.store'), [...$base, 'starts_at' => '2026-02-30'])
            ->assertSessionHasErrors('starts_at', 'Enter a valid Promotion Start Date.');
        $this->post(route('admin.promotions.store'), [...$base, 'starts_at' => '2026-10-20', 'ends_at' => '2026-10-19'])
            ->assertSessionHasErrors('ends_at', 'End Date must be on or after Start Date.');
        $this->post(route('admin.promotions.store'), [...$base, 'ends_at' => 'not-a-date'])
            ->assertSessionHasErrors('ends_at', 'Enter a valid Promotion End Date.');
    }

    public function test_editing_a_manually_stopped_promotion_preserves_its_state_and_form_feedback(): void
    {
        $admin = $this->systemAdmin();
        $this->travelTo(Carbon::parse('2026-11-01 12:00:00', 'Asia/Manila'));
        $promotion = $this->promotion([
            'name' => 'Stopped Offer', 'discount_type' => 'AMOUNT', 'discount_value' => 500,
            'is_enabled' => false, 'manually_stopped_at' => now('Asia/Manila'), 'manually_stopped_by' => $admin->id,
        ]);

        $this->get(route('admin.promotions.edit', ['promotion' => $promotion, 'modal' => 1, '_service_modal' => 1]))
            ->assertOk()
            ->assertSee('e.g., Christmas Promo, Anniversary Offer...')
            ->assertSee('Select discount type')
            ->assertSee('e.g., 5000.00')
            ->assertSee('Start Date')
            ->assertSee('End Date')
            ->assertDontSee('name="status"', false);

        $this->put(route('admin.promotions.update', $promotion), [
            'name' => 'Stopped Offer Corrected',
            'discount_type' => 'AMOUNT',
            'discount_value' => 500,
            'starts_at' => null,
            'ends_at' => null,
        ])
            ->assertRedirect()->assertSessionHas('success');
        $promotion->refresh();
        $this->assertFalse($promotion->is_enabled);
        $this->assertNotNull($promotion->manually_stopped_at);
        $this->assertSame($admin->id, $promotion->manually_stopped_by);
        $this->assertSame(500.0, (float) $promotion->discount_value);

        $this->get(route('admin.promotions.create', ['modal' => 1, '_service_modal' => 1]))
            ->assertOk()
            ->assertSee('name="starts_at"', false)
            ->assertSee('name="ends_at"', false);
    }

    public function test_turn_off_requires_confirmation_preserves_configuration_and_audits_actor(): void
    {
        $admin = $this->systemAdmin();
        $promotion = $this->promotion(['starts_at' => '2026-10-01', 'ends_at' => '2026-12-31']);
        $before = $promotion->only(['name', 'discount_type', 'discount_value', 'starts_at', 'ends_at']);

        $this->patch(route('admin.promotions.stop', $promotion))->assertSessionHasErrors('confirmed');
        $this->patch(route('admin.promotions.stop', $promotion), ['confirmed' => 1])->assertRedirect()->assertSessionHas('success');

        $promotion->refresh();
        $this->assertSame('Turned Off', $promotion->statusAt());
        $this->assertEquals($before, $promotion->only(array_keys($before)));
        $this->assertSame($admin->id, $promotion->manually_stopped_by);
        $this->assertNotNull($promotion->manually_stopped_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'promotion.stopped', 'actor_id' => $admin->id, 'entity_id' => $promotion->id]);
    }

    public function test_reenable_recalculates_schedule_and_never_reactivates_expired_promotions(): void
    {
        $admin = $this->systemAdmin();
        $this->travelTo(Carbon::parse('2026-11-01 12:00:00', 'Asia/Manila'));
        session(['idle_last_activity' => now()->timestamp]);
        $scheduled = $this->promotion(['starts_at' => '2026-11-10', 'ends_at' => '2026-11-30', 'is_enabled' => false,
            'manually_stopped_at' => now('Asia/Manila'), 'manually_stopped_by' => $admin->id]);
        $this->patch(route('admin.promotions.reactivate', $scheduled))->assertRedirect()->assertSessionHas('success');
        $this->assertSame('Scheduled', $scheduled->fresh()->statusAt());
        $this->assertDatabaseHas('audit_logs', ['action' => 'promotion.reenabled', 'actor_id' => $admin->id]);

        $expired = $this->promotion(['ends_at' => '2026-10-31', 'is_enabled' => false,
            'manually_stopped_at' => now('Asia/Manila'), 'manually_stopped_by' => $admin->id]);
        $this->patch(route('admin.promotions.reactivate', $expired))->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($expired->fresh()->manually_stopped_at);
        $this->assertSame('Expired', $expired->fresh()->statusAt());
    }

    public function test_branch_admin_can_view_but_cannot_manage_promotions(): void
    {
        $branch = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Branch Two', 'is_active' => true]);
        $branchAdmin = User::factory()->create([
            'role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true,
        ]);
        $promotion = $this->promotion();

        $this->actingAs($branchAdmin)->get(route('admin.service-management.index', ['tab' => 'promotions']))
            ->assertOk()->assertSee('Memorial Care')->assertDontSee('Add Promotion');
        $this->actingAs($branchAdmin)->get(route('admin.promotions.show', $promotion))->assertOk();
        $this->actingAs($branchAdmin)->get(route('admin.promotions.create'))->assertForbidden();
        $this->actingAs($branchAdmin)->put(route('admin.promotions.update', $promotion), [])->assertForbidden();
        $this->actingAs($branchAdmin)->patch(route('admin.promotions.stop', $promotion), ['confirmed' => 1])->assertForbidden();
        $this->actingAs($branchAdmin)->patch(route('admin.promotions.reactivate', $promotion))->assertForbidden();
        $this->assertSame('Active', $promotion->fresh()->statusAt());
    }

    public function test_package_assignment_is_single_package_and_unassignment_does_not_reactivate_legacy_columns(): void
    {
        $this->systemAdmin();
        $first = Package::create(['name' => 'First Package', 'price' => 1000, 'is_active' => true]);
        $second = Package::create(['name' => 'Second Package', 'price' => 1000, 'is_active' => true]);
        $promotion = $this->promotion(['package_id' => $first->id]);

        $this->put(route('admin.packages.update', $first), [
            'name' => 'First Package Edited', 'price' => 1000, 'included_services' => ['body_retrieval' => ['enabled' => 1, 'included_kilometers' => 0, 'price_per_excess_kilometer' => 0]],
        ])->assertRedirect();
        $first->refresh();
        $this->assertSame($promotion->id, $first->promotion?->id);
        $this->assertNull($first->promo_label);

        $this->put(route('admin.packages.update', $second), [
            'name' => 'Second Package Edited', 'price' => 1000, 'promotion_id' => $promotion->id,
            'included_services' => ['body_retrieval' => ['enabled' => 1, 'included_kilometers' => 0, 'price_per_excess_kilometer' => 0]],
        ])->assertSessionHasErrors('promotion_id');
        $this->assertSame($first->id, $promotion->fresh()->package_id);

        $turnedOff = $this->promotion([
            'name' => 'Turned Off Offer', 'is_enabled' => false,
            'manually_stopped_at' => now('Asia/Manila'), 'manually_stopped_by' => auth()->id(),
        ]);
        $this->put(route('admin.packages.update', $second), [
            'name' => 'Second Package Edited', 'price' => 1000, 'promotion_id' => $turnedOff->id,
            'included_services' => ['body_retrieval' => ['enabled' => 1, 'included_kilometers' => 0, 'price_per_excess_kilometer' => 0]],
        ])->assertSessionHasErrors(['promotion_id' => 'This promotion is currently turned off and cannot be used for new cases.']);
    }

    public function test_promotions_are_reference_data_and_schema_mismatch_is_rejected(): void
    {
        $this->systemAdmin();
        $branch = Branch::firstOrFail();
        $promotion = $this->promotion();
        $schema = app(SyncSchema::class);
        $snapshotService = app(SyncSnapshot::class);
        $snapshot = $snapshotService->export($branch->branch_code, 'reference');

        $this->assertContains('promotions', SyncSchema::REFERENCE);
        $this->assertArrayHasKey('promotions', $snapshot['entities']);
        $this->assertSame($promotion->name, $snapshot['entities']['promotions'][0]['data']['name']);
        $snapshotService->apply($snapshot, $branch->branch_code, 'reference');

        $before = Promotion::count();
        $snapshot['schema'] = 'incompatible-schema';
        try {
            $snapshotService->apply($snapshot, $branch->branch_code, 'reference');
            $this->fail('An incompatible branch schema must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Sync protocol, branch or database schema does not match.', $exception->getMessage());
        }
        $this->assertSame($before, Promotion::count());
    }
}
