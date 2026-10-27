<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\IntakeDraft;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntakeDraftWorkflowTest extends TestCase
{
    use RefreshDatabase;
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
        $this->getJson(route('intake.schedule-advisory', $params))->assertOk()->assertJsonPath('case_count', 1)
            ->assertJsonPath('events.0.case_code', $case->case_code)->assertJsonCount(2, 'events');
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
        ]))->assertSessionHasErrors(['wake_end_date', 'funeral_service_at', 'interment_at']);
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
        $response->assertRedirect(route('intake.drafts.edit', $draft));
        $this->actingAs($staff)
            ->get(route('intake.drafts.index'))
            ->assertOk()
            ->assertSee($draft->draft_number);
        $this->actingAs($staff)
            ->get(route('funeral-cases.index', ['tab' => 'draft', 'record_scope' => 'main']))
            ->assertOk()
            ->assertSee('Incomplete Intake Drafts')
            ->assertSee($draft->draft_number);
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
        ]));

        $response->assertRedirect(route('funeral-cases.index', ['record_scope' => 'main']));
        $draft->refresh();
        $this->assertSame(IntakeDraft::STATUS_SUBMITTED, $draft->status);
        $this->assertNotNull($draft->submitted_case_id);
        $this->assertTrue(FuneralCase::whereKey($draft->submitted_case_id)->exists());
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
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', ['paid_at' => '2026-07-18 08:30:00']);
        $payment = \App\Models\Payment::firstOrFail();
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
        $this->withSession(['intake_payment' => $savedPaymentSummary, 'summary' => $savedCaseSummary])
            ->get(route('intake.main.create'))
            ->assertOk()
            ->assertDontSee('Last Saved Summary')
            ->assertDontSee('id="intakePaymentSuccess"', false);
    }
}
