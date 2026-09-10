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
            ->assertSee('Resuming ' . $draft->draft_number);
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
