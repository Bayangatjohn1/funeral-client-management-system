<?php

namespace Tests\Feature;

use App\Models\{Branch, Client, Deceased, FuneralCase, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseRecordPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_record_print_uses_existing_template_and_enforces_branch_and_draft_access(): void
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main', 'is_active' => true]);
        $other = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Other', 'is_active' => true]);
        $client = Client::create(['branch_id' => $branch->id, 'full_name' => 'Print Client', 'relationship_to_deceased' => 'Other', 'contact_number' => '09170000000', 'address' => 'Test']);
        $deceased = Deceased::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'full_name' => 'Print Deceased', 'address' => 'Test', 'died' => '2026-04-28', 'date_of_death' => '2026-04-28', 'age' => 60]);
        $case = FuneralCase::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'deceased_id' => $deceased->id, 'case_code' => 'FC0001', 'service_requested_at' => '2026-04-28', 'funeral_service_at' => '2026-04-29', 'interment_at' => '2026-05-01 09:00:00', 'wake_location' => 'Test', 'case_status' => 'ACTIVE', 'verification_status' => 'VERIFIED', 'entry_source' => 'MAIN', 'payment_status' => 'UNPAID', 'total_amount' => 10000, 'total_paid' => 0, 'balance_amount' => 10000]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $otherStaff = User::factory()->create(['role' => 'staff', 'branch_id' => $other->id, 'is_active' => true]);
        $owner = User::factory()->create(['role' => 'owner', 'branch_id' => null, 'is_active' => true]);
        $url = route('funeral-cases.print', $case);
        $this->actingAs($staff)->get($url)->assertOk()->assertSee('casePrintTemplate')->assertSee('Print Deceased');
        $response = $this->actingAs($otherStaff)->get($url);
        $this->assertContains($response->status(), [403, 404]);
        $this->actingAs($owner)->get($url)->assertOk();
        $case->update(['case_status' => 'DRAFT']);
        $this->actingAs($owner)->get($url)->assertForbidden();
        $this->actingAs($staff)->get($url)->assertOk();
    }
}
