<?php

namespace Tests\Feature;

use App\Models\AddOnCatalog;
use App\Models\Branch;
use App\Models\CaseDocument;
use App\Models\CasketCatalog;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\PackageInclusion;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseSnapshotSafeEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_catalog_and_package_changes_do_not_reprice_saved_snapshot_case(): void
    {
        $this->travelTo('2026-07-23 09:00:00');
        [$case, $package, $casket, $addOn, $staff] = $this->snapshotCase();

        $package->update(['price' => 999999, 'promo_is_active' => true, 'promo_value_type' => 'PERCENT', 'promo_value' => 80]);
        $casket->update(['standard_price' => 999999]);
        $addOn->update(['price' => 999999]);

        $this->actingAs($staff)
            ->get(route('funeral-cases.edit', $case, absolute: false))
            ->assertOk()
            ->assertSee('Saved Service Package')
            ->assertDontSee('name="package_id"', false);

        $this->actingAs($staff)
            ->put(route('funeral-cases.update', $case, absolute: false), $this->staffPayload($case))
            ->assertRedirect();

        $case->refresh();
        $this->assertSame(117100.0, (float) $case->subtotal_amount);
        $this->assertSame(116100.0, (float) $case->total_amount);
        $this->assertSame(20000.0, (float) $case->total_paid);
        $this->assertSame(96100.0, (float) $case->balance_amount);
        $this->assertSame('Snapshot Package', $case->service_package);
        $this->assertSame($package->id, $case->package_id);
        $this->assertSame(100000.0, (float) data_get($case->pricing_snapshot, 'package.base_price'));
        $this->assertSame(700.0, (float) data_get($case->pricing_snapshot, 'add_ons.0.price'));
        $this->assertSame(30000.0, (float) data_get($case->pricing_snapshot, 'included_casket.reference_value'));
    }

    public function test_legacy_case_without_snapshot_preserves_saved_totals(): void
    {
        $this->travelTo('2026-07-23 09:00:00');
        [$case, , , , $staff] = $this->snapshotCase([
            'pricing_snapshot' => null,
            'subtotal_amount' => 77777,
            'discount_type' => 'PWD',
            'discount_value_type' => 'PERCENT',
            'discount_value' => 20,
            'discount_amount' => 15555,
            'total_amount' => 62222,
            'total_paid' => 11111,
            'balance_amount' => 51111,
            'payment_status' => 'PARTIAL',
        ]);

        $this->actingAs($staff)
            ->put(route('funeral-cases.update', $case, absolute: false), $this->staffPayload($case, [
                'wake_start_date' => '2026-07-20',
                'interment_at' => '2026-07-24',
            ]))
            ->assertRedirect();

        $case->refresh();
        $this->assertNull($case->pricing_snapshot);
        $this->assertSame(77777.0, (float) $case->subtotal_amount);
        $this->assertSame(15555.0, (float) $case->discount_amount);
        $this->assertSame(62222.0, (float) $case->total_amount);
        $this->assertSame(11111.0, (float) $case->total_paid);
        $this->assertSame(51111.0, (float) $case->balance_amount);
        $this->assertSame('PARTIAL', $case->payment_status);
    }

    public function test_only_embalming_and_home_viewing_extensions_change_after_schedule_update(): void
    {
        $this->travelTo('2026-07-23 09:00:00');
        [$case, , , , $staff] = $this->snapshotCase();

        $this->actingAs($staff)
            ->put(route('funeral-cases.update', $case, absolute: false), $this->staffPayload($case, [
                'interment_at' => '2026-07-24',
                'interment_time' => '10:00',
            ]))
            ->assertRedirect();

        $case->refresh();
        $charges = collect($case->pricing_snapshot['service_charges'])->keyBy('type');

        $this->assertSame(1000.0, (float) $charges[Package::SERVICE_EMBALMING]['amount']);
        $this->assertSame(1500.0, (float) $charges[Package::SERVICE_HOME_VIEWING]['amount']);
        $this->assertSame(500.0, (float) $charges[Package::SERVICE_BODY_RETRIEVAL]['amount']);
        $this->assertSame(600.0, (float) $charges[Package::SERVICE_HEARSE]['amount']);
        $this->assertSame(15000.0, (float) $charges['casket_upgrade']['amount']);
        $this->assertSame(119600.0, (float) $case->subtotal_amount);
        $this->assertSame(118600.0, (float) $case->total_amount);
        $this->assertSame(20000.0, (float) $case->total_paid);
        $this->assertSame(98600.0, (float) $case->balance_amount);
        $this->assertSame('PARTIAL', $case->payment_status);
        $this->assertSame('5D/4N', $case->pricing_snapshot['wake_duration']);
    }

    public function test_finalized_case_cannot_change_pricing_related_fields_or_schedule(): void
    {
        $this->travelTo('2026-07-23 09:00:00');
        [$case, , , , $staff] = $this->snapshotCase(['case_status' => 'COMPLETED']);
        CaseDocument::create([
            'case_id' => $case->id,
            'document_type' => CaseDocument::TYPE_FUNERAL_CONTRACT,
            'contract_number' => 'FC-TEST',
            'file_name' => 'contract.pdf',
            'file_path' => 'contracts/contract.pdf',
            'generated_by' => $staff->id,
            'generated_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('funeral-cases.edit', $case, absolute: false))
            ->assertOk()
            ->assertSee('Pricing is locked because this case is completed or already has an issued Funeral Contract.');

        $this->actingAs($staff)
            ->put(route('funeral-cases.update', $case, absolute: false), $this->staffPayload($case, [
                'wake_start_date' => '2026-07-18',
                'interment_at' => '2026-07-24',
                'additional_services' => 'Attempted pricing change',
            ]))
            ->assertRedirect();

        $case->refresh();
        $this->assertSame('2026-07-20', $case->wake_start_date->toDateString());
        $this->assertSame('2026-07-23', $case->interment_at->toDateString());
        $this->assertSame('Saved additional services', $case->additional_services);
        $this->assertSame(116100.0, (float) $case->total_amount);
        $this->assertSame('4D/3N', $case->pricing_snapshot['wake_duration']);
    }

    public function test_package_cannot_be_replaced_and_staff_admin_paths_match(): void
    {
        $this->travelTo('2026-07-23 09:00:00');
        [$staffCase, $package, , , $staff] = $this->snapshotCase();
        [$adminCase, $adminPackage, , , , $admin] = $this->snapshotCase([], 'FC-ADMIN');
        $otherPackage = Package::create(['name' => 'Live Replacement', 'price' => 1, 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.cases.edit', $adminCase, absolute: false))
            ->assertOk()
            ->assertSee('Saved Service Package')
            ->assertDontSee('name="package_id"', false);

        $this->actingAs($staff)
            ->put(route('funeral-cases.update', $staffCase, absolute: false), $this->staffPayload($staffCase, [
                'package_id' => $otherPackage->id,
                'interment_at' => '2026-07-24',
            ]))
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.cases.update', $adminCase, absolute: false), $this->adminPayload($adminCase, [
                'package_id' => $otherPackage->id,
                'interment_at' => '2026-07-24',
            ]))
            ->assertRedirect();

        $staffCase->refresh();
        $adminCase->refresh();
        $this->assertSame($package->id, $staffCase->package_id);
        $this->assertSame($adminPackage->id, $adminCase->package_id);
        $this->assertSame((float) $staffCase->total_amount, (float) $adminCase->total_amount);
        $this->assertSame($staffCase->pricing_snapshot['service_charges'], $adminCase->pricing_snapshot['service_charges']);
    }

    public function test_full_case_details_use_saved_snapshot_display_not_live_catalog_values(): void
    {
        $this->travelTo('2026-07-23 09:00:00');
        [$case, $package, $casket, $addOn, $staff] = $this->snapshotCase();

        $package->update(['name' => 'Changed Live Package', 'price' => 999999]);
        $casket->update(['name' => 'Changed Live Casket', 'standard_price' => 999999]);
        $addOn->update(['name' => 'Changed Live Add-on', 'price' => 999999]);

        $this->actingAs($staff)
            ->get(route('funeral-cases.show', $case, absolute: false))
            ->assertOk()
            ->assertSee('Snapshot Package')
            ->assertSee('Snapshot Casket')
            ->assertSee('Selected Upgrade')
            ->assertSee('Snapshot Add-on')
            ->assertSee('Casket replacement / upgrade')
            ->assertSee('Package Adjustments &amp; Extra Charges', false)
            ->assertSee('Saved Promo / Discount')
            ->assertSee('Snapshot Promo')
            ->assertSee('Saved itemized service')
            ->assertSee('Snapshot Freebie - 2 pcs')
            ->assertDontSee('Changed Live Package')
            ->assertDontSee('Changed Live Casket')
            ->assertDontSee('Changed Live Add-on');
    }

    public function test_legacy_case_details_use_saved_case_columns_not_live_package_price(): void
    {
        $this->travelTo('2026-07-23 09:00:00');
        [$case, $package, , , $staff] = $this->snapshotCase([
            'pricing_snapshot' => null,
            'package_price_snapshot' => null,
            'package_inclusions_snapshot' => 'Saved legacy inclusion',
            'package_freebies_snapshot' => 'Saved legacy freebie',
            'subtotal_amount' => 77777,
            'total_amount' => 62222,
            'total_paid' => 11111,
            'balance_amount' => 51111,
        ]);
        $package->update(['price' => 999999, 'inclusions' => 'Changed live inclusion', 'freebies' => 'Changed live freebie']);

        $this->actingAs($staff)
            ->get(route('funeral-cases.show', $case, absolute: false))
            ->assertOk()
            ->assertSee('Saved legacy inclusion')
            ->assertSee('Saved legacy freebie')
            ->assertSee('77,777.00')
            ->assertSee('62,222.00')
            ->assertSee('11,111.00')
            ->assertSee('51,111.00')
            ->assertDontSee('999,999.00')
            ->assertDontSee('Changed live inclusion')
            ->assertDontSee('Changed live freebie');
    }

    public function test_payment_recording_updates_only_payment_columns_and_history(): void
    {
        $this->travelTo('2026-07-23 09:00:00');
        [$case, , , , $staff] = $this->snapshotCase([
            'paid_at' => '2026-07-20 08:00:00',
            'total_paid' => 20000,
            'balance_amount' => 96100,
            'payment_status' => 'PARTIAL',
        ]);
        $before = $case->fresh()->getAttributes();

        $this->actingAs($staff)
            ->post(route('payments.store', absolute: false), [
                'funeral_case_id' => $case->id,
                'paid_at' => '2026-07-23 09:00:00',
                'amount_paid' => 1000,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $case->refresh();
        $after = $case->getAttributes();
        foreach ($before as $key => $value) {
            if (in_array($key, ['total_paid', 'balance_amount', 'payment_status', 'updated_at'], true)) {
                continue;
            }

            $this->assertSame((string) $value, (string) ($after[$key] ?? null), "Unexpected payment-side case change on {$key}.");
        }

        $this->assertSame(21000.0, (float) $case->total_paid);
        $this->assertSame(95100.0, (float) $case->balance_amount);
        $this->assertSame('PARTIAL', $case->payment_status);
        $this->assertSame('2026-07-20 08:00:00', $case->paid_at?->format('Y-m-d H:i:s'));

        $payment = Payment::query()->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertSame($case->id, $payment->funeral_case_id);
        $this->assertSame(1000.0, (float) $payment->amount);
        $this->assertSame(95100.0, (float) $payment->balance_after_payment);
        $this->assertSame('PARTIAL', $payment->payment_status_after_payment);
    }

    public function test_owner_case_view_is_read_only_and_uses_snapshot_display(): void
    {
        $this->travelTo('2026-07-23 09:00:00');
        [$case, $package, , , ] = $this->snapshotCase();
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $package->update(['name' => 'Changed Live Package', 'price' => 999999]);

        $this->actingAs($owner)
            ->get(route('owner.cases.show', $case, absolute: false))
            ->assertOk()
            ->assertSee('Snapshot Package')
            ->assertSee('Snapshot Add-on')
            ->assertDontSee('Changed Live Package')
            ->assertDontSee('Modify')
            ->assertDontSee('Add Payment');
    }

    private function snapshotCase(array $overrides = [], string $caseCode = 'FC-SNAP'): array
    {
        $branch = Branch::create([
            'branch_code' => $caseCode,
            'branch_name' => 'Main Branch ' . $caseCode,
            'branch_type' => 'main',
            'address' => 'Main',
            'is_active' => true,
        ]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'main', 'branch_id' => $branch->id, 'is_active' => true]);
        $client = Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Snapshot Client',
            'relationship_to_deceased' => 'Daughter',
            'relationship' => 'Daughter',
            'contact_number' => '09170000000',
            'address' => 'Client Address',
        ]);
        $deceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => 'Snapshot Deceased',
            'address' => 'Client Address',
            'born' => '1950-01-01',
            'died' => '2026-07-19',
            'date_of_death' => '2026-07-19',
            'age' => 76,
            'interment' => '2026-07-23',
            'interment_at' => '2026-07-23 10:00:00',
            'wake_days' => 4,
            'place_of_cemetery' => 'Public Cemetery',
        ]);
        $casket = CasketCatalog::create(['name' => 'Snapshot Casket ' . $caseCode, 'type_or_material' => 'Maple', 'standard_price' => 30000, 'is_active' => true]);
        $addOn = AddOnCatalog::create(['name' => 'Snapshot Add-on ' . $caseCode, 'category' => 'Service', 'price' => 700, 'unit' => 'service', 'is_active' => true]);
        $package = Package::create([
            'name' => 'Snapshot Package',
            'coffin_type' => 'Snapshot Casket',
            'price' => 100000,
            'is_active' => true,
        ]);
        PackageInclusion::create(['package_id' => $package->id, 'service_type' => Package::SERVICE_EMBALMING, 'inclusion_name' => 'Embalming', 'included_days' => 4, 'price_per_extended_day' => 1000, 'sort_order' => 1]);
        PackageInclusion::create(['package_id' => $package->id, 'service_type' => Package::SERVICE_HOME_VIEWING, 'inclusion_name' => 'Home Viewing', 'included_days' => 4, 'price_per_extended_day' => 1500, 'sort_order' => 2]);

        $snapshot = $this->snapshot($package, $casket, $addOn);
        $case = FuneralCase::create(array_merge([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_code' => $caseCode,
            'service_requested_at' => '2026-07-20',
            'service_package' => 'Snapshot Package',
            'coffin_type' => 'Snapshot Casket',
            'package_name_snapshot' => 'Snapshot Package',
            'package_price_snapshot' => 100000,
            'wake_location' => 'Family Residence',
            'wake_start_date' => '2026-07-20',
            'wake_start_time' => '08:00:00',
            'funeral_service_at' => '2026-07-21',
            'funeral_service_time' => '09:00:00',
            'interment_at' => '2026-07-23 10:00:00',
            'interment_time' => '10:00:00',
            'additional_services' => 'Saved additional services',
            'subtotal_amount' => 117100,
            'discount_type' => 'SENIOR',
            'discount_value_type' => 'AMOUNT',
            'discount_value' => 1000,
            'discount_amount' => 1000,
            'discount_note' => 'Saved senior discount',
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 116100,
            'total_paid' => 20000,
            'balance_amount' => 96100,
            'payment_status' => 'PARTIAL',
            'case_status' => 'ACTIVE',
            'entry_source' => 'MAIN',
            'verification_status' => 'VERIFIED',
            'pricing_snapshot' => $snapshot,
        ], $overrides));

        return [$case, $package, $casket, $addOn, $staff, $admin];
    }

    private function snapshot(Package $package, CasketCatalog $casket, AddOnCatalog $addOn): array
    {
        return [
            'version' => 1,
            'package' => ['id' => $package->id, 'name' => 'Snapshot Package', 'base_price' => 100000],
            'inclusions' => [
                ['service_type' => Package::SERVICE_EMBALMING, 'name' => 'Embalming', 'included_days' => 4, 'price_per_extended_day' => 1000],
                ['service_type' => Package::SERVICE_HOME_VIEWING, 'name' => 'Home Viewing', 'included_days' => 4, 'price_per_extended_day' => 1500],
                ['service_type' => Package::SERVICE_CASKET, 'name' => 'Casket', 'casket_type' => $casket->name, 'casket_catalog_id' => $casket->id],
            ],
            'freebies' => [['name' => 'Snapshot Freebie', 'quantity' => 2, 'unit' => 'pcs']],
            'included_casket' => ['id' => $casket->id, 'name' => $casket->name, 'material' => $casket->type_or_material, 'reference_value' => 30000],
            'selected_casket' => ['id' => $casket->id + 1000, 'name' => 'Selected Upgrade', 'material' => 'Hardwood', 'reference_value' => 45000],
            'promo' => ['label' => 'Snapshot Promo', 'value_type' => 'AMOUNT', 'value' => 1000, 'starts_at' => '2026-07-20 00:00:00', 'ends_at' => '2026-07-23 23:59:59', 'status' => 'Applied at intake'],
            'wake_days' => 4,
            'wake_nights' => 3,
            'wake_duration' => '4D/3N',
            'service_charges' => [
                ['type' => Package::SERVICE_BODY_RETRIEVAL, 'label' => 'Body Retrieval', 'applied' => true, 'included' => 10, 'rate' => 100, 'excess' => 5, 'amount' => 500],
                ['type' => Package::SERVICE_HEARSE, 'label' => 'Hearse', 'applied' => true, 'included' => 20, 'rate' => 200, 'excess' => 3, 'amount' => 600],
                ['type' => 'casket_upgrade', 'label' => 'Casket replacement / upgrade', 'included' => 30000, 'selected' => 45000, 'amount' => 15000],
            ],
            'add_ons' => [['id' => $addOn->id, 'name' => $addOn->name, 'category' => $addOn->category, 'price' => 700, 'unit' => $addOn->unit]],
            'additional_items' => [['description' => 'Saved itemized service', 'amount' => 300]],
            'discount' => ['discount_type' => 'CUSTOM', 'discount_value_type' => 'AMOUNT', 'discount_value' => 1000, 'discount_amount' => 1000, 'discount_note' => 'Auto promo discount (Snapshot Promo)', 'source' => 'PROMO'],
            'totals' => ['subtotal' => 117100, 'discount' => 1000, 'tax' => 0, 'total' => 116100],
        ];
    }

    private function staffPayload(FuneralCase $case, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $case->client_id,
            'deceased_id' => $case->deceased_id,
            'package_id' => $case->package_id,
            'date_of_death' => '2026-07-19',
            'wake_start_date' => '2026-07-20',
            'wake_start_time' => '08:00',
            'funeral_service_at' => '2026-07-21',
            'funeral_service_time' => '09:00',
            'interment_at' => '2026-07-23',
            'interment_time' => '10:00',
            'notes' => 'Snapshot-safe edit test',
        ], $overrides);
    }

    private function adminPayload(FuneralCase $case, array $overrides = []): array
    {
        return array_merge([
            'client_full_name' => 'Snapshot Client',
            'client_contact' => '09170000000',
            'client_relationship' => 'Daughter',
            'client_address' => 'Client Address',
            'deceased_full_name' => 'Snapshot Deceased',
            'date_of_birth' => '1950-01-01',
            'date_of_death' => '2026-07-19',
            'age' => 76,
            'deceased_address' => 'Client Address',
            'place_of_cemetery' => 'Public Cemetery',
            'package_id' => $case->package_id,
            'wake_location' => 'Family Residence',
            'wake_start_date' => '2026-07-20',
            'wake_start_time' => '08:00',
            'funeral_service_at' => '2026-07-21',
            'funeral_service_time' => '09:00',
            'interment_at' => '2026-07-23',
            'interment_time' => '10:00',
            'additional_services' => 'Saved additional services',
            'case_status' => 'ACTIVE',
            'admin_note' => 'Snapshot-safe edit test',
        ], $overrides);
    }
}
