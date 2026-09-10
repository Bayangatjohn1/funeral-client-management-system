<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDirectoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_directory_searches_contact_number_and_address(): void
    {
        $branch = $this->branch();
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $matchingClient = Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Maria Santos',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'relationship_to_deceased' => 'Daughter',
            'contact_number' => '09171234567',
            'address' => 'San Roque, Baguio City',
        ]);
        Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Pedro Reyes',
            'first_name' => 'Pedro',
            'last_name' => 'Reyes',
            'relationship_to_deceased' => 'Brother',
            'contact_number' => '09990000000',
            'address' => 'Camp 7',
        ]);

        $this->actingAs($staff)
            ->get(route('clients.index', ['q' => '09171234567']))
            ->assertOk()
            ->assertSee($matchingClient->full_name)
            ->assertDontSee('Pedro Reyes');

        $this->actingAs($staff)
            ->get(route('clients.index', ['q' => 'San Roque']))
            ->assertOk()
            ->assertSee($matchingClient->full_name)
            ->assertDontSee('Pedro Reyes');
    }

    public function test_view_linked_cases_filter_shows_only_selected_client_cases(): void
    {
        $branch = $this->branch();
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $selectedClient = Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Maria Santos',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'relationship_to_deceased' => 'Daughter',
            'contact_number' => '09171234567',
            'address' => 'San Roque',
        ]);
        $otherClient = Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Pedro Reyes',
            'first_name' => 'Pedro',
            'last_name' => 'Reyes',
            'relationship_to_deceased' => 'Brother',
            'contact_number' => '09990000000',
            'address' => 'Camp 7',
        ]);
        $selectedDeceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $selectedClient->id,
            'full_name' => 'Jose Santos',
        ]);
        $otherDeceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $otherClient->id,
            'full_name' => 'Juan Reyes',
        ]);
        FuneralCase::create([
            'branch_id' => $branch->id,
            'client_id' => $selectedClient->id,
            'deceased_id' => $selectedDeceased->id,
            'case_code' => 'FC-001',
            'service_requested_at' => '2026-09-01',
            'wake_location' => 'Family Residence',
            'funeral_service_at' => '2026-09-03',
            'service_package' => 'First Class',
            'total_amount' => 50000,
            'case_status' => 'ACTIVE',
            'payment_status' => 'UNPAID',
            'entry_source' => 'MAIN',
        ]);
        FuneralCase::create([
            'branch_id' => $branch->id,
            'client_id' => $otherClient->id,
            'deceased_id' => $otherDeceased->id,
            'case_code' => 'FC-002',
            'service_requested_at' => '2026-09-01',
            'wake_location' => 'Chapel',
            'funeral_service_at' => '2026-09-03',
            'service_package' => 'Second Class',
            'total_amount' => 35000,
            'case_status' => 'ACTIVE',
            'payment_status' => 'PAID',
            'entry_source' => 'MAIN',
        ]);

        $this->actingAs($staff)
            ->get(route('funeral-cases.index', [
                'tab' => 'all',
                'record_scope' => 'main',
                'client_id' => $selectedClient->id,
            ]))
            ->assertOk()
            ->assertSee('Representative: Maria Santos')
            ->assertSee('FC-001')
            ->assertDontSee('FC-002');
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
}
