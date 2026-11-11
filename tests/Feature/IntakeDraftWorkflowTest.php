<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\FuneralCase;
use App\Models\IntakeDraft;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntakeDraftWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_returning_client_is_branch_scoped_and_keeps_each_cases_relationship(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $otherBranch = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Other Branch', 'address' => 'Other Address', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Standard Package', 'price' => 50000, 'is_active' => true]);
        $otherClient = Client::create([
            'branch_id' => $otherBranch->id, 'first_name' => 'Maria', 'last_name' => 'Other',
            'relationship_to_deceased' => 'Other', 'contact_number' => '09170000000', 'address' => 'Other Address',
        ]);

        $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package))->assertSessionHasNoErrors();
        $client = Client::where('branch_id', $branch->id)->firstOrFail();
        $this->getJson(route('intake.existing-clients', ['q' => '0917 000 0000']))->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.id', $client->id)->assertJsonMissing(['id' => $otherClient->id]);

        $sameNameClient = Client::create([
            'branch_id' => $branch->id,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'relationship_to_deceased' => 'Sibling',
            'contact_number' => $client->contact_number,
            'address' => 'Different Address',
        ]);
        $firstCase = $client->funeralCases()->sole();
        $this->getJson(route('intake.existing-clients', ['q' => $client->full_name]))
            ->assertOk()->assertJsonCount(2);
        $this->getJson(route('intake.existing-clients', ['q' => strtolower($sameNameClient->directoryCode())]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $sameNameClient->id)
            ->assertJsonPath('0.directory_id', $sameNameClient->directoryCode());
        $this->getJson(route('intake.existing-clients', ['q' => $otherClient->directoryCode()]))
            ->assertOk()->assertJsonCount(0);
        $this->getJson(route('intake.existing-clients', ['q' => strtolower($firstCase->case_code)]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $client->id)
            ->assertJsonPath('0.matched_case_code', $firstCase->case_code);
        $otherStaff = User::factory()->create(['role' => 'staff', 'branch_id' => $otherBranch->id, 'is_active' => true]);
        $this->actingAs($otherStaff)->getJson(route('intake.existing-clients', ['q' => $firstCase->case_code]))
            ->assertOk()->assertJsonCount(0);
        $this->actingAs($staff);

        $payload = $this->payload($branch, $package, [
            'existing_client_id' => $otherClient->id, 'client_relationship' => 'Sibling',
            'deceased_first_name' => 'Rosa', 'client_address' => 'Tampered Address',
        ]);
        $this->post(route('intake.main.store'), $payload)->assertSessionHasErrors('existing_client_id');
        $payload['existing_client_id'] = $client->id;
        $this->post(route('intake.drafts.save'), $payload)->assertRedirect();
        $draft = IntakeDraft::firstOrFail();
        $this->assertSame($client->id, (int) $draft->payload['fields']['existing_client_id']);
        $this->get(route('intake.drafts.edit', $draft))->assertOk()->assertSee('name="existing_client_id" value="'.$client->id.'"', false);
        $this->post(route('intake.main.store'), $payload + ['intake_draft_id' => $draft->id])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('clients', 3);
        $this->assertSame('Client Address', $client->refresh()->address);
        $this->assertSame('Daughter', $client->relationship_to_deceased);
        $this->assertSame(['Daughter', 'Sibling'], $client->funeralCases()->orderBy('id')->pluck('client_relationship')->all());
        $this->get(route('clients.show', $client))->assertOk()->assertSee('Daughter')->assertSee('Sibling');
    }

    public function test_staff_navbar_intake_modal_reuses_wizard_validation_and_saved_destinations(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Modal Intake Package', 'price' => 50000, 'is_active' => true]);
        $modal = ['intake_modal' => 1];
        $frame = route('intake.main.create', $modal);
        $submit = route('intake.main.store', $modal);

        $this->actingAs($staff)->get('/staff')->assertOk()
            ->assertSee('data-intake-trigger="'.e($frame).'"', false)
            ->assertSee('id="staff-intake-dialog"', false);
        $this->get($frame)->assertOk()
            ->assertSee('action="'.e($submit).'"', false)
            ->assertSee('id="intakeWizardForm"', false)
            ->assertSee('You have unsaved changes.')
            ->assertDontSee('id="appSidebar"', false);

        $this->from($frame)->post($submit, $this->payload($branch, $package, ['client_first_name' => '']))
            ->assertRedirect($frame)->assertSessionHasErrors('client_first_name');
        $this->assertDatabaseCount('funeral_cases', 0);

        $this->post(route('intake.drafts.save', $modal), ['entry_mode' => 'main', 'branch_id' => $branch->id, 'client_first_name' => 'Maria'])
            ->assertOk()->assertViewIs('staff.intake.modal-saved')
            ->assertViewHas('destination', route('funeral-cases.index', ['tab' => 'draft', 'record_scope' => 'main']));

        $chapel = \App\Models\AddOnCatalog::create([
            'name' => 'Main Chapel Viewing', 'category' => 'Chapel Viewing', 'unit' => 'day', 'branch_id' => $branch->id, 'chapel_address' => '24 Mabini Street, Quezon City',
            'price' => 2000, 'is_active' => true, 'is_available' => true,
        ]);
        $context = \Illuminate\Support\Facades\Crypt::encryptString(json_encode(['items' => [$chapel->id => [
            'name' => $chapel->name, 'price' => '2000.00', 'unit' => 'day', 'is_active' => true, 'is_available' => true,
        ]]], JSON_THROW_ON_ERROR));
        $payload = $this->payload($branch, $package, [
            'viewing_type' => 'chapel', 'wake_location' => 'Main Branch Chapel', 'chapel_address' => '24 Mabini St., Quezon City',
            'chapel_days' => 99, 'chapel_add_on_id' => $chapel->id, 'add_on_context_token' => $context,
            'chapel_coverage_flow' => 1,
        ]);
        unset($payload['payment_method']);
        $this->post($submit, $payload)->assertSessionHasErrors('chapel_coverage');
        $this->post($submit, $payload + ['chapel_coverage' => 'included'])->assertSessionHasErrors('package_id');
        $payload['chapel_coverage'] = 'separate';
        $this->post($submit, $payload)
            ->assertOk()->assertViewIs('staff.intake.modal-saved')
            ->assertViewHas('destination', route('funeral-cases.index', ['record_scope' => 'main']))
            ->assertSessionHas('success');
        $this->assertDatabaseCount('funeral_cases', 1);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('funeral_cases', [
            'viewing_type' => 'chapel', 'wake_location' => $chapel->name, 'chapel_catalog_id' => $chapel->id,
            'chapel_address' => $chapel->chapel_address, 'total_amount' => 54000,
        ]);

        $package->packageInclusions()->create([
            'service_type' => Package::SERVICE_CUSTOM, 'inclusion_name' => $chapel->name,
            'add_on_catalog_id' => $chapel->id, 'included_quantity' => 1,
        ]);
        $includedPayload = array_replace($payload, [
            'deceased_first_name' => 'Rafael',
            'wake_start_date' => '2026-07-23', 'wake_end_date' => '2026-07-24',
            'funeral_service_at' => '2026-07-25', 'interment_at' => '2026-07-25',
            'client_contact_number' => '09170000003',
            'package_allowance_context' => $package->fresh()->addOnAllowanceFingerprint(),
        ]);
        $this->post($submit, $includedPayload)->assertSessionHasErrors('package_id');
        unset($includedPayload['chapel_add_on_id']);
        $includedPayload['chapel_coverage'] = 'included';
        $this->post($submit, $includedPayload)->assertSessionHasNoErrors()->assertOk()->assertViewIs('staff.intake.modal-saved');
        $this->assertDatabaseHas('funeral_cases', ['viewing_type' => 'chapel', 'total_amount' => 52000]);
    }

    public function test_selected_chapel_rates_require_resolution_but_allow_drafts_and_explicit_zero_rates(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Chapel rules package', 'price' => 50000, 'is_active' => true]);
        $payload = $this->payload($branch, $package, [
            'viewing_type' => 'chapel', 'wake_location' => 'Main Branch Chapel',
            'chapel_address' => '24 Mabini Street, Quezon City',
            'chapel_add_on_id' => 999999,
            'add_on_context_token' => 'invalid-context',
        ]);
        $submit = route('intake.main.store');
        $this->actingAs($staff)->from('/intake/main')->post($submit, $payload)
            ->assertSessionHasErrors('chapel_add_on_id');
        $this->assertDatabaseCount('funeral_cases', 0);
        $this->post(route('intake.drafts.save'), $payload + ['entry_mode' => 'main', 'chapel_coverage_flow' => '1', 'chapel_coverage' => 'separate'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('intake_drafts', 1);
        $this->assertSame('separate', \App\Models\IntakeDraft::firstOrFail()->payload['fields']['chapel_coverage']);

        $first = \App\Models\AddOnCatalog::create([
            'name' => 'Chapel A', 'category' => 'Chapel Viewing', 'unit' => 'day', 'branch_id' => $branch->id, 'chapel_address' => '24 Mabini Street, Quezon City',
            'price' => 0, 'is_active' => true, 'is_available' => true,
        ]);
        \App\Models\AddOnCatalog::create([
            'name' => 'Chapel B', 'category' => 'Chapel Viewing', 'unit' => 'day', 'branch_id' => $branch->id, 'chapel_address' => '24 Mabini Street, Quezon City',
            'price' => 2000, 'is_active' => true, 'is_available' => true,
        ]);
        $this->post($submit, $payload)->assertSessionHasErrors('chapel_add_on_id');
        $context = $this->get(route('intake.main.create'))->viewData('addOnContextToken');
        $payload['add_on_context_token'] = $context;
        unset($payload['chapel_add_on_id']);

        $package->packageInclusions()->create([
            'service_type' => Package::SERVICE_CUSTOM, 'inclusion_name' => $first->name,
            'add_on_catalog_id' => $first->id, 'included_quantity' => 1,
        ]);
        $first->update(['is_available' => false]);
        $this->post($submit, $payload)->assertSessionHasErrors('chapel_add_on_id');
        $this->assertDatabaseCount('funeral_cases', 0);
        $first->update(['is_available' => true]);
        $payload['package_allowance_context'] = $package->fresh()->addOnAllowanceFingerprint();
        $this->post($submit, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('funeral_cases', ['viewing_type' => 'chapel', 'total_amount' => 50000]);
    }

    public function test_chapel_viewing_without_package_coverage_requires_explicit_rate_selection(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Location only package', 'price' => 50000, 'is_active' => true]);
        \App\Models\AddOnCatalog::create([
            'name' => 'Paid Chapel', 'category' => 'Chapel Viewing', 'unit' => 'day', 'branch_id' => $branch->id, 'chapel_address' => '24 Mabini Street, Quezon City',
            'price' => 2000, 'is_active' => true, 'is_available' => true,
        ]);
        $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package, [
            'viewing_type' => 'chapel', 'wake_location' => 'Main Branch Chapel',
            'chapel_address' => '24 Mabini Street, Quezon City',
        ]))->assertSessionHasErrors('chapel_add_on_id')->assertRedirect();
        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_chapel_overlap_blocks_both_coverage_paths_and_wake_extensions_but_allows_drafts(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch=$this->branch();
        $staff=User::factory()->create(['role'=>'staff','branch_id'=>$branch->id,'is_active'=>true]);
        $package=Package::create(['name'=>'Separate chapel package','price'=>50000,'is_active'=>true]);
        $includedPackage=Package::create(['name'=>'Included chapel package','price'=>50000,'is_active'=>true]);
        $chapel=\App\Models\AddOnCatalog::create(['name'=>'Main Chapel','category'=>'Chapel Viewing','branch_id'=>$branch->id,'chapel_address'=>'24 Mabini St., Quezon City','unit'=>'day','price'=>2000,'is_active'=>true,'is_available'=>true]);
        $includedPackage->packageInclusions()->create([
            'service_type' => Package::SERVICE_CUSTOM, 'inclusion_name' => $chapel->name,
            'add_on_catalog_id' => $chapel->id, 'included_quantity' => 2,
        ]);
        $this->actingAs($staff);
        $token=$this->get(route('intake.main.create'))->viewData('addOnContextToken');
        $payload=$this->payload($branch,$package,[
            'viewing_type'=>'chapel','chapel_address'=>$chapel->chapel_address,'chapel_add_on_id'=>$chapel->id,'add_on_context_token'=>$token,
            'chapel_coverage_flow' => 1, 'chapel_coverage' => 'separate',
        ]);
        $this->post(route('intake.main.store'),$payload)->assertSessionHasNoErrors();
        $first = FuneralCase::firstOrFail();
        $second=array_replace($payload,['deceased_first_name'=>'Rafael','client_contact_number'=>'09170000003']);
        $this->post(route('intake.main.store'),$second)->assertSessionHasErrors('chapel_add_on_id')
            ->assertSessionHas('errors', fn ($errors) => str_contains($errors->first('chapel_add_on_id'), $first->case_code));
        $context=app(\App\Support\ChapelSchedule::class)->context($chapel,'2026-07-20 08:00','2026-07-21 08:00');
        $included = array_replace($second, [
            'package_id' => $includedPackage->id, 'package_allowance_context' => $includedPackage->addOnAllowanceFingerprint(),
            'chapel_coverage' => 'included', 'chapel_advisory_signature' => $context['signature'], 'chapel_schedule_confirmed' => 1,
        ]);
        unset($included['chapel_add_on_id']);
        $this->post(route('intake.main.store'), $included)->assertSessionHasErrors('chapel_add_on_id');

        $schedule = $this->mock(\App\Support\ChapelSchedule::class);
        $schedule->shouldReceive('context')->once()->ordered()->andReturn(['events' => []]);
        $schedule->shouldReceive('context')->once()->ordered()
            ->withArgs(fn (...$args) => ($args[4] ?? false) === true)
            ->andReturn($context);
        $this->post(route('intake.main.store'), $included)->assertSessionHasErrors('chapel_add_on_id');
        $this->app->forgetInstance(\App\Support\ChapelSchedule::class);

        $this->post(route('intake.drafts.save'), $included + ['entry_mode' => 'main'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('intake_drafts', 1);
        $this->assertDatabaseCount('funeral_cases', 1);

        $this->post(route('intake.main.store'), array_replace($included, [
            'wake_start_date' => '2026-07-21', 'wake_end_date' => '2026-07-22',
            'funeral_service_at' => '2026-07-22', 'interment_at' => '2026-07-22',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('funeral_cases',2);
        $edit = [
            'client_id' => $first->client_id, 'deceased_id' => $first->deceased_id, 'date_of_death' => '2026-07-19',
            'wake_start_date' => '2026-07-20', 'wake_start_time' => '08:00',
            'wake_end_date' => '2026-07-21', 'wake_end_time' => '08:30',
            'funeral_service_at' => '2026-07-21', 'funeral_service_time' => '09:00',
            'interment_at' => '2026-07-21', 'interment_time' => '10:00',
        ];
        $this->put(route('funeral-cases.update', $first), $edit)->assertSessionHasErrors('wake_end_date');
        $this->assertSame('08:00', substr($first->fresh()->wake_end_time, 0, 5));
        $this->put(route('funeral-cases.update', $first), array_replace($edit, ['wake_end_time' => '07:30']))
            ->assertSessionHasNoErrors();
        $this->assertSame('07:30', substr($first->fresh()->wake_end_time, 0, 5));
    }

    public function test_review_schedule_advisory_is_branch_scoped_and_excludes_deleted_cases(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Advisory Package', 'price' => 50000, 'is_active' => true]);
        $payload = $this->payload($branch, $package);
        $this->actingAs($staff)->post(route('intake.main.store'), $payload)->assertSessionHasNoErrors();
        $case = FuneralCase::firstOrFail();
        $params = ['branch_id' => $branch->id, 'funeral_service_at' => $payload['funeral_service_at'], 'interment_at' => $payload['interment_at']];
        $hearse = \App\Models\HearseCatalog::create(['name' => 'Main branch hearse', 'is_active' => true]);
        $case->forceFill(['hearse_catalog_id' => $hearse->id])->save();
        $this->getJson(route('intake.schedule-advisory', $params))->assertOk()->assertJsonPath('case_count', 1)
            ->assertJsonPath('events.0.case_code', $case->case_code)
            ->assertJsonPath('events.0.event_type', 'funeral')
            ->assertJsonPath('events.1.event_type', 'interment')
            ->assertJsonPath('events.0.assigned_hearse_id', $hearse->id)
            ->assertJsonPath('events.0.assigned_hearse', $hearse->name)
            ->assertJsonCount(2, 'events');
        $this->getJson(route('intake.schedule-advisory', ['branch_id' => $branch->id, 'funeral_service_at' => $payload['funeral_service_at']]))
            ->assertOk()->assertJsonPath('branch_name', $branch->branch_name)
            ->assertJsonPath('events.0.date_key', $payload['funeral_service_at'])
            ->assertJsonPath('events.0.start_time', $payload['funeral_service_time'])
            ->assertJsonCount(2, 'events');
        $case->update(['case_status' => 'COMPLETED']);
        $this->getJson(route('intake.schedule-advisory', $params))->assertJsonPath('events.0.status', 'COMPLETED');
        $other = Branch::create(['branch_code' => 'BR009', 'branch_name' => 'Other Branch', 'is_active' => true]);
        $this->getJson(route('intake.schedule-advisory', array_merge($params, ['branch_id' => $other->id])))->assertForbidden();
        $case->delete();
        $this->getJson(route('intake.schedule-advisory', $params))->assertOk()->assertJsonPath('case_count', 0)->assertJsonCount(0, 'events');
    }

    public function test_address_service_locations_and_schedule_rules_reject_invalid_values_together(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Validation Package', 'price' => 50000, 'is_active' => true]);
        $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package, [
            'client_address' => '1234234sdfsdf!@@@!----', 'deceased_address' => '123456',
            'wake_location' => 'Funeral Home!!!', 'place_of_cemetery' => 'Forest Lake~~',
            'wake_start_date' => '2026-02-31', 'wake_end_time' => '25:00',
        ]))->assertSessionHasErrors(['client_address', 'deceased_address', 'wake_location', 'place_of_cemetery', 'wake_start_date', 'wake_end_time']);
        $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package, [
            'client_address' => "#12 Blk. 4, O'Connor St. / Brgy. San Jose (Phase 2)",
            'deceased_address' => 'Unit 2-A, Peña & Sons Compound',
            'wake_location' => 'St. Peter Chapel (Room 2)', 'place_of_cemetery' => 'Forest Lake - Phase 2',
            'case_status' => 'INVALID',
        ]))->assertSessionHasErrors('case_status')
            ->assertSessionDoesntHaveErrors(['client_address', 'deceased_address', 'wake_location', 'place_of_cemetery']);
        $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package, [
            'wake_start_date' => '2026-07-21', 'wake_start_time' => '10:00',
            'wake_end_date' => '2026-07-21', 'wake_end_time' => '09:00',
            'funeral_service_at' => '2026-07-21', 'funeral_service_time' => '08:00',
            'interment_at' => '2026-07-21', 'interment_time' => '07:00',
        ]))->assertSessionHasErrors(['wake_end_time', 'funeral_service_time', 'interment_time']);
        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_staff_can_save_incomplete_intake_draft_without_creating_official_records(): void
    {
        $branch = $this->branch();
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)->post(route('intake.drafts.save'), [
            'entry_mode' => 'main',
            'branch_id' => $branch->id,
            'current_step' => 1,
            'client_first_name' => 'Maria',
            'client_contact_number' => '09170000000',
        ]);

        $draft = IntakeDraft::first();
        $draftTab = route('funeral-cases.index', ['tab' => 'draft', 'record_scope' => 'main']);
        $response->assertRedirect($draftTab);
        $this->actingAs($staff)
            ->get(route('intake.drafts.index'))
            ->assertOk()
            ->assertSee($draft->draft_number);
        $this->actingAs($staff)
            ->get($draftTab)
            ->assertOk()
            ->assertSee('Incomplete Intake Drafts')
            ->assertSee($draft->draft_number)
            ->assertDontSee('class="table-base table-system-table case-records-table', false)
            ->assertSee('data-intake-trigger="'.e(route('intake.drafts.edit', [
                'draft' => $draft, 'return_to' => $draftTab, 'intake_modal' => 1,
            ])).'"', false);
        $this->get(route('intake.drafts.edit', ['draft' => $draft, 'intake_modal' => 1]))
            ->assertOk()
            ->assertSee('Resuming ' . $draft->draft_number)
            ->assertDontSee('class="intake-draft-banner', false)
            ->assertSee('action="'.e(route('intake.main.store', ['intake_modal' => 1])).'"', false)
            ->assertDontSee('id="appSidebar"', false);
        $this->actingAs($staff)
            ->get(route('intake.drafts.edit', $draft))
            ->assertOk()
            ->assertSee('Resuming ' . $draft->draft_number)
            ->assertSee('value="Maria"', false)
            ->assertSee('value="09170000000"', false);
        $returnTo = route('funeral-cases.index', ['tab' => 'draft', 'record_scope' => 'main']);
        $this->actingAs($staff)
            ->get(route('intake.drafts.edit', ['draft' => $draft, 'return_to' => $returnTo]))
            ->assertOk()
            ->assertSee('name="return_to" value="' . e($returnTo) . '"', false);
        $this->assertDatabaseCount('intake_drafts', 1);
        $this->assertSame(IntakeDraft::STATUS_IN_PROGRESS, $draft->status);
        $this->assertSame('Maria', $draft->payload['fields']['client_first_name']);
        $this->assertSame(1, $draft->current_step);
        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('deceased', 0);
        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_submitting_resumed_draft_marks_it_submitted_after_case_is_created(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        $branch = $this->branch();
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $package = Package::create([
            'name' => 'Standard Package',
            'price' => 50000,
            'is_active' => true,
        ]);
        $draft = IntakeDraft::create([
            'branch_id' => $branch->id,
            'created_by' => $staff->id,
            'payload' => ['fields' => ['client_first_name' => 'Maria']],
            'current_step' => 3,
            'entry_mode' => 'main',
            'status' => IntakeDraft::STATUS_IN_PROGRESS,
            'last_saved_at' => now(),
        ]);

        $response = $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package, [
            'intake_draft_id' => $draft->id,
            'funeral_ceremony_location_mode' => 'wake',
            'funeral_ceremony_location' => 'Previously entered church',
        ]));

        $response->assertRedirect(route('funeral-cases.index', ['record_scope' => 'main']));
        $draft->refresh();
        $this->assertSame(IntakeDraft::STATUS_SUBMITTED, $draft->status);
        $this->assertNotNull($draft->submitted_case_id);
        $case = FuneralCase::findOrFail($draft->submitted_case_id);
        $this->assertTrue($case->ceremony_at_wake_location);
        $this->assertNull($case->funeral_ceremony_location);
        $this->assertSame($case->wake_location, $case->ceremonyLocation());
        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseCount('deceased', 1);
    }

    public function test_resuming_draft_renders_saved_schedule_payment_and_step(): void
    {
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $draft = IntakeDraft::create([
            'branch_id' => $branch->id,
            'created_by' => $staff->id,
            'entry_mode' => 'main',
            'status' => IntakeDraft::STATUS_IN_PROGRESS,
            'current_step' => 4,
            'last_saved_at' => now(),
            'payload' => ['fields' => [
                'client_first_name' => 'Maria',
                'wake_start_date' => '2026-07-20',
                'wake_start_time' => '08:15',
                'funeral_ceremony_location_mode' => 'custom',
                'funeral_ceremony_location' => 'St. Joseph Parish Church',
                'payment_method' => 'cashless',
                'cashless_type' => 'gcash',
                'payment_type' => 'PARTIAL',
                'amount_paid' => '12000.00',
                'reference_number' => 'DRAFT-REF-123',
            ]],
        ]);
        $this->actingAs($staff)->get(route('intake.drafts.edit', $draft))
            ->assertOk()
            ->assertSee('id="current_step" value="4"', false)
            ->assertSee('value="Maria"', false)
            ->assertSee('value="2026-07-20"', false)
            ->assertSee('value="08:15"', false)
            ->assertSee('value="St. Joseph Parish Church"', false)
            ->assertSee('value="cashless" class="payment-method-radio sr-only" checked', false)
            ->assertSee('value="gcash" selected', false)
            ->assertSee('value="PARTIAL" class="payment-type-radio sr-only" checked', false)
            ->assertSee('value="12000.00"', false)
            ->assertSee('value="DRAFT-REF-123"', false);
    }

    public function test_discarding_intake_draft_returns_to_case_records_draft_tab(): void
    {
        $branch = $this->branch();
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $draft = IntakeDraft::create([
            'branch_id' => $branch->id,
            'created_by' => $staff->id,
            'payload' => ['fields' => ['client_first_name' => 'Maria']],
            'current_step' => 1,
            'entry_mode' => 'main',
            'status' => IntakeDraft::STATUS_IN_PROGRESS,
            'last_saved_at' => now(),
        ]);

        $this->actingAs($staff)
            ->delete(route('intake.drafts.destroy', $draft))
            ->assertRedirect(route('funeral-cases.index', ['tab' => 'draft', 'record_scope' => 'main']));

        $this->assertSoftDeleted('intake_drafts', ['id' => $draft->id]);
        $this->assertSame(
            IntakeDraft::STATUS_DISCARDED,
            IntakeDraft::withTrashed()->findOrFail($draft->id)->status
        );
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
        ], $overrides);
    }

    public function test_actual_payment_receipt_can_precede_death_but_cannot_be_future(): void
    {
        $this->travelTo('2026-07-20 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::create(['name' => 'Standard Package', 'price' => 50000, 'is_active' => true]);
        $paymentFields = ['mark_as_paid' => 1, 'payment_method' => 'cash', 'payment_type' => 'FULL', 'amount_paid' => 50000];
        $this->actingAs($staff)->get(route('intake.main.create'))
            ->assertOk()
            ->assertSee('name="paid_at" id="paid_at" value=""', false);
        foreach (['', 'none'] as $invalidMethod) {
            $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package,
                array_merge($paymentFields, ['payment_method' => $invalidMethod, 'paid_at' => '2026-07-18T08:30'])))
                ->assertSessionHasErrors($invalidMethod === '' ? 'payment_method' : 'mark_as_paid');
        }
        foreach (['', '2026-07-19'] as $invalidReceiptTime) {
            $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package,
                array_merge($paymentFields, ['paid_at' => $invalidReceiptTime])))
                ->assertSessionHasErrors('paid_at');
        }
        $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package,
            array_merge($paymentFields, ['paid_at' => '2026-07-21T10:00'])))
            ->assertSessionHasErrors('paid_at');
        $this->assertDatabaseCount('funeral_cases', 0);
        $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package,
            array_merge($paymentFields, ['paid_at' => '2026-07-18T08:30'])))
            ->assertSessionHasErrors('receipt_or_no');
        \Illuminate\Support\Facades\Storage::fake('local');
        $proofForm = (string) \Illuminate\Support\Str::uuid();
        $upload = $this->postJson(route('payment-proof.store'), ['form_id' => $proofForm, 'context' => 'intake'])->assertOk()->json('id');
        $this->postJson(route('payment-proof.upload', $upload), ['proof' => \Illuminate\Http\UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4\n%%EOF")])->assertOk();
        $this->actingAs($staff)->post(route('intake.main.store'), $this->payload($branch, $package,
            array_merge($paymentFields, ['paid_at' => '2026-07-18T08:30', 'receipt_or_no' => 'OR-INTAKE-001',
                'payment_proof_id' => $upload, 'payment_proof_form_id' => $proofForm])))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', ['paid_at' => '2026-07-18 08:30:00', 'receipt_or_no' => 'OR-INTAKE-001']);
        $payment = \App\Models\Payment::firstOrFail();
        $this->assertSame($upload, $payment->proof_upload_id);
        $this->assertNotNull($payment->proof_path);
        $this->assertSame($payment->id, session('intake_payment.id'));
        $savedPaymentSummary = session('intake_payment');
        $savedCaseSummary = session('summary');
        $this->actingAs($staff)->get(route('payments.summary', $payment))->assertOk();
        $this->get(route('payments.summary', $payment))->assertOk();
        $this->actingAs($staff)->get(route('intake.saved'))
            ->assertOk()
            ->assertSee('id="intakePaymentSuccess"', false)
            ->assertSee(route('payments.summary', $payment), false)
            ->assertSee('PHP 50,000.00')
            ->assertDontSee('frame.contentWindow.print()', false);
        $this->actingAs($staff)->get(route('intake.saved', ['intake_modal' => 1]))
            ->assertOk()->assertSee('id="intakePaymentSuccess"', false)
            ->assertSee('prepareStaffIntakeSuccess', false)
            ->assertDontSee('body .main-area,body .flash-toast-stack{display:none !important}', false);
        $this->withSession(['intake_payment' => $savedPaymentSummary, 'summary' => $savedCaseSummary])
            ->get(route('intake.main.create'))
            ->assertOk()
            ->assertDontSee('Last Saved Summary')
            ->assertDontSee('id="intakePaymentSuccess"', false);
    }
}
