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
use App\Services\CaseDocument\FuneralContractMapper;
use App\Services\CaseDocument\FuneralContractSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FuneralContractSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_preview_loads_saved_case_mapping(): void
    {
        [$case, , , , $admin] = $this->contractCase();

        $this->actingAs($admin)
            ->get(route('funeral-cases.documents.contract.preview', $case, absolute: false))
            ->assertOk()
            ->assertSee('Review Funeral Contract')
            ->assertSee('First Class')
            ->assertSee('Contract Casket')
            ->assertSee('Contract Freebie')
            ->assertSee('Deposit')
            ->assertSee('Body Retrieval: 10.00 km included')
            ->assertSee('Embalming: 4D/3N included')
            ->assertSee(route('funeral-cases.documents.contract.preview-pdf', $case, absolute: false));

        $this->actingAs($admin)
            ->get(route('funeral-cases.documents.contract.preview-pdf', $case, absolute: false))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        /*
            ->assertSee('Extended # of days')
            ->assertSee('1 day(s) x ₱1,500.00/day')
            ->assertSee('1 day(s) x ₱500.00/day')
            ->assertSee('Contract Add-on - 1 service x ₱700.00 - ₱700.00')
            ->assertSee('Manual extra service - ₱2,300.00');
        */
    }

    public function test_generated_contract_saves_snapshot_and_uses_first_payment_as_deposit(): void
    {
        Storage::fake('local');
        [$case, , , , $admin] = $this->contractCase();

        $this->actingAs($admin)
            ->post(route('funeral-cases.documents.contract.store', $case, absolute: false), [
                'interment_time' => '14:30',
                'excess_kilometers' => 'Retrieval 2 km',
                'extended_service_days' => 'Home viewing +1 day',
                'casket_upgrade' => 'Premium metal upgrade',
                'remarks' => 'Family requested afternoon interment.',
            ])
            ->assertRedirect(route('funeral-cases.show', $case, absolute: false));

        $document = CaseDocument::query()->where('case_id', $case->id)->firstOrFail();

        $this->assertSame(CaseDocument::TYPE_FUNERAL_CONTRACT, $document->document_type);
        $this->assertNotEmpty($document->contract_snapshot);
        $this->assertSame('First Class', data_get($document->contract_snapshot, 'package.name'));
        $this->assertSame('2026-07-20', data_get($document->contract_snapshot, 'contract_date'));
        $this->assertSame(10, data_get($document->contract_snapshot, 'structured_inclusions.1.included_kilometers'));
        $this->assertSame(100.0, (float) data_get($document->contract_snapshot, 'structured_inclusions.1.price_per_excess_kilometer'));
        $this->assertSame(1, data_get($document->contract_snapshot, 'service_charges.1.extended_days'));
        $this->assertSame(1500.0, (float) data_get($document->contract_snapshot, 'service_charges.1.rate'));
        $this->assertSame('Contract Add-on', data_get($document->contract_snapshot, 'add_ons.0.name'));
        $this->assertSame('Manual extra service', data_get($document->contract_snapshot, 'additional_items.0.description'));
        $this->assertSame([], data_get($document->contract_snapshot, 'contract_only_additional_services'));
        $this->assertSame(10000.0, (float) data_get($document->contract_snapshot, 'totals.deposit'));
        $this->assertSame(90100.0, (float) data_get($document->contract_snapshot, 'totals.balance'));
        $this->assertSame('14:30', data_get($document->contract_snapshot, 'schedule.interment_time'));
        $this->assertSame('Family requested afternoon interment.', data_get($document->contract_snapshot, 'remarks'));
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_issued_contract_snapshot_is_not_changed_by_live_catalog_updates(): void
    {
        Storage::fake('local');
        [$case, $package, $casket, $addOn, $admin] = $this->contractCase();

        $this->actingAs($admin)
            ->post(route('funeral-cases.documents.contract.store', $case, absolute: false), [
                'remarks' => 'Saved contract snapshot.',
            ])
            ->assertRedirect();

        $document = CaseDocument::query()->where('case_id', $case->id)->firstOrFail();
        $snapshot = $document->contract_snapshot;

        $package->update(['name' => 'Changed Package', 'price' => 999999]);
        $casket->update(['name' => 'Changed Casket', 'standard_price' => 999999]);
        $addOn->update(['name' => 'Changed Add-on', 'price' => 999999]);
        $case->update(['total_amount' => 1, 'balance_amount' => 0]);

        $document->refresh();

        $this->assertSame($snapshot, $document->contract_snapshot);
        $this->assertSame('First Class', data_get($document->contract_snapshot, 'package.name'));
        $this->assertSame('Contract Casket', data_get($document->contract_snapshot, 'included_casket.name'));
        $this->assertSame('Contract Add-on', data_get($document->contract_snapshot, 'add_ons.0.name'));
        $this->assertSame(105100.0, (float) data_get($document->contract_snapshot, 'totals.total'));
    }

    public function test_mapper_does_not_invent_missing_coverage_or_casket_values(): void
    {
        $mapped = app(FuneralContractMapper::class)->map([
            'contract_number' => 'FCN-2026-000009',
            'case' => ['case_code' => 'CASE-009'],
            'package' => ['name' => 'Second Class', 'base_price' => 50000],
            'structured_inclusions' => [],
            'basic_inclusions' => [],
            'included_casket' => null,
            'selected_casket' => null,
            'totals' => ['total' => 50000, 'deposit' => 0, 'balance' => 50000],
        ]);

        $values = collect($mapped['basic_rows'])->pluck('value')->all();

        $this->assertContains('Not configured', $values);
        $this->assertContains('Not included', $values);
        $this->assertNotContains('within 25kms', $values);
        $this->assertNotContains('4D/3N', $values);
        $this->assertNotContains('Basic Wood Casket', $values);
        $this->assertSame('No excess kilometers', $mapped['physical_additional_rows'][0]['details']);
        $this->assertSame('No extended days', $mapped['physical_additional_rows'][1]['details']);
        $this->assertSame('No casket replacement/upgrade', $mapped['physical_additional_rows'][2]['details']);
        $this->assertSame('No extended days', $mapped['physical_additional_rows'][3]['details']);
        $this->assertSame('No excess kilometers', $mapped['physical_additional_rows'][4]['details']);
        $this->assertSame('No other additional services', $mapped['physical_additional_rows'][5]['details']);
        $this->assertSame(0.0, (float) $mapped['physical_additional_rows'][0]['amount']);
    }

    public function test_mapper_shows_saved_quantities_rates_amounts_and_casket_upgrade(): void
    {
        [$case] = $this->contractCase();

        $snapshot = app(FuneralContractSnapshotService::class)->make($case, 'FCN-2026-000123');
        $mapped = app(FuneralContractMapper::class)->map($snapshot);

        $this->assertSame('FC-CONTRACT', $mapped['case_number']);
        $this->assertSame('FCN-2026-000123', $mapped['contract_number']);
        $this->assertSame('10.00 km included', $mapped['basic_rows'][0]['value']);
        $this->assertSame('4D/3N included', $mapped['basic_rows'][1]['value']);
        $this->assertStringContainsString('Contract Upgrade Casket - Metal', $mapped['physical_additional_rows'][2]['details']);
        $this->assertSame(5000.0, (float) $mapped['automatic_charges'][2]['amount']);
        $this->assertSame(700.0, (float) $mapped['add_ons'][0]['amount']);
        $this->assertSame(2300.0, (float) $mapped['itemized_services'][0]['amount']);
        $this->assertSame('Senior Discount', $mapped['totals']['discount_label']);
        $this->assertSame(10000.0, (float) $mapped['totals']['initial_deposit']);
    }

    public function test_finalized_contract_is_not_regenerated_or_overwritten(): void
    {
        Storage::fake('local');
        [$case, , , , $admin] = $this->contractCase();

        $this->actingAs($admin)
            ->post(route('funeral-cases.documents.contract.store', $case, absolute: false), [
                'remarks' => 'Original final remarks.',
            ])
            ->assertRedirect();

        $document = CaseDocument::query()->where('case_id', $case->id)->firstOrFail();
        $snapshot = $document->contract_snapshot;
        $filePath = $document->file_path;

        $this->actingAs($admin)
            ->post(route('funeral-cases.documents.contract.store', $case, absolute: false), [
                'remarks' => 'Attempted replacement remarks.',
            ])
            ->assertRedirect();

        $document->refresh();

        $this->assertSame($snapshot, $document->contract_snapshot);
        $this->assertSame($filePath, $document->file_path);
        $this->assertSame('Original final remarks.', data_get($document->contract_snapshot, 'remarks'));
    }

    public function test_preview_uses_existing_finalized_contract_snapshot(): void
    {
        Storage::fake('local');
        [$case, , , , $admin] = $this->contractCase();

        $this->actingAs($admin)
            ->post(route('funeral-cases.documents.contract.store', $case, absolute: false), [
                'remarks' => 'Finalized saved remarks.',
            ])
            ->assertRedirect();

        $case->update(['total_amount' => 1, 'balance_amount' => 1, 'discount_note' => 'Changed live case note.']);

        $this->actingAs($admin)
            ->get(route('funeral-cases.documents.contract.preview', $case, absolute: false))
            ->assertOk()
            ->assertSee('Finalized saved remarks.')
            ->assertDontSee('Changed live case note.');
    }

    public function test_document_preview_renders_current_layout_from_snapshot_without_overwriting_stored_pdf(): void
    {
        Storage::fake('local');
        [$case, , , , $admin] = $this->contractCase();

        $this->actingAs($admin)
            ->post(route('funeral-cases.documents.contract.store', $case, absolute: false), [
                'remarks' => 'Finalized saved remarks.',
            ])
            ->assertRedirect();

        $document = CaseDocument::query()->where('case_id', $case->id)->firstOrFail();
        Storage::disk('local')->put($document->file_path, 'OLD STORED PDF CONTENT');

        $this->actingAs($admin)
            ->get(route('funeral-cases.documents.preview', [$case, $document], absolute: false))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDontSee('OLD STORED PDF CONTENT', false);

        $this->actingAs($admin)
            ->get(route('funeral-cases.documents.print', [$case, $document], absolute: false))
            ->assertOk()
            ->assertSee('Preparing Funeral Contract for printing')
            ->assertSee(route('funeral-cases.documents.preview', [$case, $document], absolute: false));

        $this->actingAs($admin)
            ->get(route('funeral-cases.documents.download', [$case, $document], absolute: false))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="' . $document->file_name . '"')
            ->assertDontSee('OLD STORED PDF CONTENT', false);

        $this->assertSame('OLD STORED PDF CONTENT', Storage::disk('local')->get($document->file_path));
    }

    public function test_preview_escapes_user_entered_contract_text(): void
    {
        [$case, , , , $admin] = $this->contractCase();
        $case->update(['discount_note' => '<img src=x onerror=alert(1)>']);

        $this->actingAs($admin)
            ->get(route('funeral-cases.documents.contract.preview', $case, absolute: false))
            ->assertOk()
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }

    public function test_long_inclusions_are_not_silently_discarded_by_mapper(): void
    {
        $inclusions = collect(range(1, 16))->map(fn ($i) => 'Other inclusion ' . $i)->all();
        $mapped = app(FuneralContractMapper::class)->map([
            'contract_number' => 'FCN-2026-000010',
            'case' => ['case_code' => 'CASE-010'],
            'package' => ['name' => 'First Class', 'base_price' => 100000],
            'basic_inclusions' => $inclusions,
            'totals' => ['total' => 100000, 'deposit' => 0, 'balance' => 100000],
        ]);

        $this->assertCount(16, $mapped['other_inclusions']);
        $this->assertContains('Other inclusion 16', $mapped['other_inclusions']);
    }

    public function test_selected_package_is_separate_and_printed_inclusions_are_filtered(): void
    {
        $mapper = app(FuneralContractMapper::class);

        $firstClass = [
            'contract_number' => 'FCN-2026-000011',
            'case' => ['case_code' => 'CASE-FIRST'],
            'package' => ['name' => 'First Class', 'base_price' => 250000],
            'basic_inclusions' => ['Full service package.', 'Organ assistance'],
            'structured_inclusions' => [
                ['service_type' => Package::SERVICE_BODY_RETRIEVAL, 'included_kilometers' => 25],
                ['service_type' => Package::SERVICE_CASKET, 'casket_type' => 'First Class Casket'],
            ],
            'included_casket' => ['name' => 'First Class Casket', 'material' => 'Metal', 'reference_value' => 75000],
            'totals' => ['total' => 250000, 'deposit' => 0, 'balance' => 250000],
        ];
        $secondClass = [
            'contract_number' => 'FCN-2026-000012',
            'case' => ['case_code' => 'CASE-SECOND'],
            'package' => ['name' => 'Second Class', 'base_price' => 150000],
            'basic_inclusions' => ['Full service package.', 'Chapel setup'],
            'structured_inclusions' => [
                ['service_type' => Package::SERVICE_BODY_RETRIEVAL, 'included_kilometers' => 15],
                ['service_type' => Package::SERVICE_CASKET, 'casket_type' => 'Second Class Casket'],
            ],
            'included_casket' => ['name' => 'Second Class Casket', 'material' => 'Wood', 'reference_value' => 45000],
            'totals' => ['total' => 150000, 'deposit' => 0, 'balance' => 150000],
        ];
        $custom = [
            'contract_number' => 'FCN-2026-000013',
            'case' => ['case_code' => 'CASE-CUSTOM'],
            'package' => ['name' => 'Family Arrangement', 'base_price' => 88000, 'custom_package' => true],
            'basic_inclusions' => ['Full service package.', 'Family-selected viewing setup'],
            'totals' => ['total' => 88000, 'deposit' => 0, 'balance' => 88000],
        ];

        $firstMapped = $mapper->map($firstClass);
        $secondMapped = $mapper->map($secondClass);
        $customMapped = $mapper->map($custom);

        $this->assertSame('First Class', $firstMapped['selected_package']['name']);
        $this->assertSame(250000.0, (float) $firstMapped['selected_package']['base_price']);
        $this->assertSame('25.00 km included', $firstMapped['basic_rows'][0]['value']);
        $this->assertSame('First Class Casket - Metal', $firstMapped['basic_rows'][2]['value']);
        $this->assertContains('Organ assistance', $firstMapped['other_inclusions']);
        $this->assertNotContains('Full service package.', $firstMapped['other_inclusions']);

        $this->assertSame('Second Class', $secondMapped['selected_package']['name']);
        $this->assertSame(150000.0, (float) $secondMapped['selected_package']['base_price']);
        $this->assertSame('15.00 km included', $secondMapped['basic_rows'][0]['value']);
        $this->assertSame('Second Class Casket - Wood', $secondMapped['basic_rows'][2]['value']);
        $this->assertContains('Chapel setup', $secondMapped['other_inclusions']);
        $this->assertNotContains('First Class Casket - Metal', collect($secondMapped['basic_rows'])->pluck('value')->all());

        $this->assertSame('Custom - Family Arrangement', $customMapped['selected_package']['name']);
        $this->assertContains('Family-selected viewing setup', $customMapped['other_inclusions']);
        $this->assertNotContains('Full service package.', $customMapped['other_inclusions']);
        $this->assertContains('Full service package.', $custom['basic_inclusions']);
    }

    public function test_additional_services_are_normalized_from_saved_snapshot_without_double_counting(): void
    {
        [$case] = $this->contractCase();
        $snapshot = app(FuneralContractSnapshotService::class)->make($case, 'FCN-2026-000222');
        $snapshot['adjustments'] = [
            'body_retrieval' => ['charge' => 700, 'excess_kilometers' => 7, 'rate' => 100],
            'casket' => ['charge' => 5000],
            'hearse' => ['charge' => 400, 'excess_kilometers' => 4, 'rate' => 100],
        ];

        $mapped = app(FuneralContractMapper::class)->map($snapshot);

        $this->assertSame('Excess # of Km.', $mapped['physical_additional_rows'][0]['label']);
        $this->assertSame('7 km x ₱100.00/km', $mapped['physical_additional_rows'][0]['details']);
        $this->assertSame(700.0, (float) $mapped['physical_additional_rows'][0]['amount']);
        $this->assertSame('Extended # of days', $mapped['physical_additional_rows'][1]['label']);
        $this->assertSame('1 day(s) x ₱1,500.00/day', $mapped['physical_additional_rows'][1]['details']);
        $this->assertSame('Extended # of days', $mapped['physical_additional_rows'][3]['label']);
        $this->assertSame(500.0, (float) $mapped['physical_additional_rows'][3]['amount']);
        $this->assertSame('Excess # of Km.', $mapped['physical_additional_rows'][4]['label']);
        $this->assertSame('4 km x ₱100.00/km', $mapped['physical_additional_rows'][4]['details']);
        $this->assertStringContainsString('Contract Upgrade Casket - Metal', $mapped['physical_additional_rows'][2]['details']);
        $this->assertSame(11100.0, (float) $mapped['totals']['total_additional_services']);
        $this->assertSame(106100.0, (float) $mapped['totals']['subtotal']);
        $this->assertTrue($mapped['reconciliation']['reconciled']);
    }

    public function test_printed_contract_excludes_freebies_and_simplifies_financial_summary(): void
    {
        [$case] = $this->contractCase();
        $snapshot = app(FuneralContractSnapshotService::class)->make($case, 'FCN-2026-000333');
        $view = app(FuneralContractMapper::class)->map($snapshot);

        $html = view('pdf.funeral_contract_physical', [
            'funeral_case' => $case,
            'generatedAt' => now(),
            'contractNumber' => 'FCN-2026-000333',
            'contractSnapshot' => $snapshot,
            'contractView' => $view,
        ])->render();

        $this->assertStringNotContainsString('Contract Freebie', $html);
        $this->assertStringContainsString('Additional Services:', $html);
        $this->assertStringNotContainsString('Details / Quantity and Rate', $html);
        $this->assertStringNotContainsString('Printed Name:', $html);
        $this->assertStringNotContainsString('Automatic Adjustments:', $html);
        $this->assertStringNotContainsString('Itemized Services:', $html);
    }

    public function test_unpaid_case_can_finalize_with_zero_deposit_and_saved_balance(): void
    {
        Storage::fake('local');
        [$case, , , , $admin] = $this->contractCase();
        $case->payments()->delete();
        $case->update([
            'total_paid' => 0,
            'balance_amount' => 105100,
            'payment_status' => 'UNPAID',
        ]);

        $this->actingAs($admin)
            ->post(route('funeral-cases.documents.contract.store', $case, absolute: false), [
                'remarks' => 'Unpaid but confirmed.',
            ])
            ->assertRedirect();

        $document = CaseDocument::query()->where('case_id', $case->id)->firstOrFail();
        $this->assertSame(0.0, (float) data_get($document->contract_snapshot, 'totals.deposit'));
        $this->assertSame(0.0, (float) data_get($document->contract_snapshot, 'payment.total_paid'));
        $this->assertSame(105100.0, (float) data_get($document->contract_snapshot, 'totals.balance'));
    }

    public function test_pricing_mismatch_blocks_contract_finalization(): void
    {
        Storage::fake('local');
        [$case, , , , $admin] = $this->contractCase();
        $case->update(['subtotal_amount' => 999999]);

        $this->actingAs($admin)
            ->post(route('funeral-cases.documents.contract.store', $case, absolute: false), [])
            ->assertSessionHasErrors('contract');

        $this->assertDatabaseMissing('case_documents', [
            'case_id' => $case->id,
            'document_type' => CaseDocument::TYPE_FUNERAL_CONTRACT,
        ]);
    }

    public function test_legacy_case_uses_saved_totals_and_historical_additional_service_only(): void
    {
        [$case] = $this->contractCase();
        $case->update([
            'pricing_snapshot' => null,
            'package_inclusions_snapshot' => null,
            'package_freebies_snapshot' => null,
            'package_name_snapshot' => 'Legacy First Class',
            'package_price_snapshot' => 70000,
            'additional_services' => 'Historical extra service',
            'additional_service_amount' => 1200,
            'subtotal_amount' => 71200,
            'discount_amount' => 200,
            'total_amount' => 71000,
            'balance_amount' => 56000,
        ]);
        $case->package?->update(['name' => 'Changed Live Package', 'price' => 999999]);

        $snapshot = app(FuneralContractSnapshotService::class)->make($case->fresh(['client', 'deceased', 'branch', 'payments', 'serviceDetail']), 'FCN-2026-000444');
        $mapped = app(FuneralContractMapper::class)->map($snapshot);

        $this->assertTrue((bool) $snapshot['legacy']);
        $this->assertSame('Legacy First Class', $mapped['selected_package']['name']);
        $this->assertSame('Not available in historical record', $mapped['basic_rows'][0]['value']);
        $this->assertSame('Historical extra service', $mapped['itemized_services'][0]['label']);
        $this->assertSame(1200.0, (float) $mapped['itemized_services'][0]['amount']);
        $this->assertSame(71000.0, (float) $mapped['totals']['contract_total']);
        $this->assertTrue($mapped['reconciliation']['reconciled']);
    }

    public function test_unauthorized_staff_cannot_preview_or_finalize_another_branch_contract(): void
    {
        Storage::fake('local');
        [$case] = $this->contractCase();
        $otherBranch = Branch::create([
            'branch_code' => 'OTHER',
            'branch_name' => 'Other Branch',
            'branch_type' => 'branch',
            'address' => 'Other Address',
            'contact_number' => '09000000000',
            'is_active' => true,
        ]);
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $otherBranch->id,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('funeral-cases.documents.contract.preview', $case, absolute: false))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('funeral-cases.documents.contract.store', $case, absolute: false), [])
            ->assertForbidden();
    }

    private function contractCase(): array
    {
        $branch = Branch::create([
            'branch_code' => 'MAIN',
            'branch_name' => 'Sabangan-Caguioa Funeral Services',
            'branch_type' => 'main',
            'address' => 'Bayambang, Pangasinan',
            'contact_number' => '09398755349',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'main',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $client = Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Contract Client',
            'relationship' => 'Son',
            'relationship_to_deceased' => 'Son',
            'contact_number' => '09171234567',
            'address' => 'Client Address',
        ]);

        $deceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => 'Contract Deceased',
            'address' => 'Client Address',
            'date_of_death' => '2026-07-19',
            'age' => 72,
            'interment' => '2026-07-23',
            'interment_at' => '2026-07-23 10:00:00',
            'wake_days' => 4,
            'place_of_cemetery' => 'Public Cemetery',
        ]);

        $casket = CasketCatalog::create([
            'name' => 'Contract Casket',
            'type_or_material' => 'Maple',
            'standard_price' => 30000,
            'is_active' => true,
        ]);

        $upgradeCasket = CasketCatalog::create([
            'name' => 'Contract Upgrade Casket',
            'type_or_material' => 'Metal',
            'standard_price' => 35000,
            'is_active' => true,
        ]);

        $addOn = AddOnCatalog::create([
            'name' => 'Contract Add-on',
            'category' => 'Service',
            'price' => 700,
            'unit' => 'service',
            'is_active' => true,
        ]);

        $package = Package::create([
            'name' => 'First Class',
            'coffin_type' => 'Contract Casket',
            'price' => 95000,
            'is_active' => true,
        ]);

        PackageInclusion::create([
            'package_id' => $package->id,
            'service_type' => Package::SERVICE_CASKET,
            'inclusion_name' => 'Casket',
            'casket_type' => 'Contract Casket',
            'casket_catalog_id' => $casket->id,
            'sort_order' => 1,
        ]);

        $snapshot = [
            'version' => 1,
            'package' => ['id' => $package->id, 'name' => 'First Class', 'base_price' => 95000],
            'inclusions' => [
                ['service_type' => Package::SERVICE_CASKET, 'name' => 'Casket', 'casket_type' => 'Contract Casket', 'casket_catalog_id' => $casket->id],
                ['service_type' => Package::SERVICE_BODY_RETRIEVAL, 'name' => 'Body Retrieval', 'included_kilometers' => 10, 'price_per_excess_kilometer' => 100],
                ['service_type' => Package::SERVICE_EMBALMING, 'name' => 'Embalming', 'included_days' => 4, 'price_per_extended_day' => 1500],
                ['service_type' => Package::SERVICE_HOME_VIEWING, 'name' => 'Home Viewing', 'included_days' => 4, 'price_per_extended_day' => 500],
                ['service_type' => Package::SERVICE_HEARSE, 'name' => 'Hearse', 'included_kilometers' => 20, 'price_per_excess_kilometer' => 100],
            ],
            'freebies' => [['name' => 'Contract Freebie', 'quantity' => 2, 'unit' => 'pcs']],
            'included_casket' => ['id' => $casket->id, 'name' => 'Contract Casket', 'material' => 'Maple', 'reference_value' => 30000],
            'selected_casket' => ['id' => $upgradeCasket->id, 'name' => 'Contract Upgrade Casket', 'material' => 'Metal', 'reference_value' => 35000],
            'wake_days' => 4,
            'wake_nights' => 3,
            'wake_duration' => '4D/3N',
            'service_charges' => [
                ['type' => Package::SERVICE_BODY_RETRIEVAL, 'label' => 'Body Retrieval excess distance', 'applied' => true, 'excess_kilometers' => 7, 'rate' => 100, 'amount' => 700],
                ['type' => Package::SERVICE_EMBALMING, 'label' => 'Embalming Extension', 'extended_days' => 1, 'rate' => 1500, 'amount' => 1500],
                ['type' => Package::SERVICE_CASKET, 'label' => 'Casket upgrade', 'amount' => 5000],
                ['type' => Package::SERVICE_HOME_VIEWING, 'label' => 'Home Viewing Extension', 'extended_days' => 1, 'rate' => 500, 'amount' => 500],
                ['type' => Package::SERVICE_HEARSE, 'label' => 'Hearse excess distance', 'applied' => true, 'excess_kilometers' => 4, 'rate' => 100, 'amount' => 400],
            ],
            'add_ons' => [['id' => $addOn->id, 'name' => 'Contract Add-on', 'category' => 'Service', 'price' => 700, 'unit' => 'service', 'quantity' => 1, 'line_total' => 700]],
            'additional_items' => [['description' => 'Manual extra service', 'amount' => 2300]],
            'discount' => ['source' => 'SENIOR', 'discount_amount' => 1000, 'discount_note' => 'Senior discount'],
            'totals' => ['subtotal' => 106100, 'discount' => 1000, 'tax' => 0, 'total' => 105100],
        ];

        $case = FuneralCase::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_code' => 'FC-CONTRACT',
            'case_number' => 7,
            'service_requested_at' => '2026-07-20',
            'service_package' => 'First Class',
            'coffin_type' => 'Contract Casket',
            'package_name_snapshot' => 'First Class',
            'package_price_snapshot' => 95000,
            'wake_location' => 'Family Residence',
            'wake_start_date' => '2026-07-20',
            'wake_start_time' => '08:00:00',
            'funeral_service_at' => '2026-07-22',
            'funeral_service_time' => '09:00:00',
            'interment_at' => '2026-07-23 10:00:00',
            'interment_time' => '10:00:00',
            'subtotal_amount' => 106100,
            'discount_type' => 'SENIOR',
            'discount_value_type' => 'AMOUNT',
            'discount_value' => 1000,
            'discount_amount' => 1000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 105100,
            'total_paid' => 15000,
            'balance_amount' => 90100,
            'payment_status' => 'PARTIAL',
            'case_status' => 'ACTIVE',
            'entry_source' => 'MAIN',
            'verification_status' => 'VERIFIED',
            'pricing_snapshot' => $snapshot,
        ]);

        Payment::create([
            'funeral_case_id' => $case->id,
            'branch_id' => $branch->id,
            'amount' => 10000,
            'paid_at' => '2026-07-20 09:00:00',
            'payment_method' => 'Cash',
            'status' => 'VALID',
        ]);

        Payment::create([
            'funeral_case_id' => $case->id,
            'branch_id' => $branch->id,
            'amount' => 5000,
            'paid_at' => '2026-07-21 09:00:00',
            'payment_method' => 'Cash',
            'status' => 'VALID',
        ]);

        return [$case, $package, $casket, $addOn, $admin];
    }
}
