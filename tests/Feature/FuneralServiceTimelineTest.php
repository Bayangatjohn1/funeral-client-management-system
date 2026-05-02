<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuneralServiceTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_funeral_service_same_day_as_death_is_allowed(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        $today = now()->toDateString();

        $response = $this->actingAs($staff)->from('/intake/main')->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'client_name' => 'Client Same Day',
            'client_contact_number' => '09170001111',
            'client_address' => 'Sample Address',
            'deceased_name' => 'Deceased Same Day',
            'deceased_address' => 'Sample Address',
            'died' => $today,
            'service_requested_at' => now()->toDateString(),
            'funeral_service_at' => $today,
            'interment_at' => now()->setTime(10, 0)->addDay()->format('Y-m-d H:i:s'),
        ]));

        $this->assertStringContainsString('/funeral-cases', $response->headers->get('Location'));
        $response->assertSessionMissing('errors');
        $this->assertDatabaseCount('funeral_cases', 1);
    }

    public function test_funeral_service_before_date_of_death_is_rejected(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        $deathDate = now()->toDateString();
        $before = now()->subDay()->toDateString();

        $response = $this->actingAs($staff)->from('/intake/main')->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'client_name' => 'Client Before',
            'client_contact_number' => '09170002222',
            'client_address' => 'Sample Address',
            'deceased_name' => 'Deceased Before',
            'deceased_address' => 'Sample Address',
            'died' => $deathDate,
            'service_requested_at' => now()->toDateString(),
            'funeral_service_at' => $before,
            'interment_at' => now()->setTime(10, 0)->addDay()->format('Y-m-d H:i:s'),
        ]));

        $response->assertRedirect('/intake/main');
        $response->assertSessionHasErrors('funeral_service_at');
        $this->assertDatabaseCount('funeral_cases', 0);
    }

    private function createBranch(string $code, string $name): Branch
    {
        return Branch::create([
            'branch_code' => $code,
            'branch_name' => $name,
            'address' => 'Test Address',
            'is_active' => true,
        ]);
    }

    private function createPackage(): Package
    {
        return Package::create([
            'name' => 'Standard Package',
            'coffin_type' => 'Standard',
            'price' => 20000,
            'is_active' => true,
        ]);
    }

    private function createUser(string $role, Branch $branch = null, bool $isMainAdmin = false)
    {
        $user = \App\Models\User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
        if ($isMainAdmin) {
            $user->assignRole('admin');
        }
        return $user;
    }

    private function baseIntakePayload(Branch $branch, Package $package, array $overrides = []): array
    {
        $deathDate = now()->subDay()->toDateString();
        $funeralDate = now()->addDay()->toDateString();
        $intermentAt = now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i:s');

        return array_merge([
            'service_requested_at' => now()->toDateString(),
            'branch_id' => $branch->id,
            'client_name' => 'Client Intake',
            'client_relationship' => 'Son',
            'client_contact_number' => '09170000000',
            'client_email' => 'client@example.com',
            'client_valid_id_type' => 'National ID',
            'client_valid_id_number' => 'ID-2000',
            'client_address' => 'Client Address',
            'deceased_name' => 'Deceased Intake',
            'deceased_address' => 'Client Address',
            'born' => now()->subYears(65)->toDateString(),
            'died' => $deathDate,
            'civil_status' => 'MARRIED',
            'pwd_status' => 0,
            'wake_location' => 'Family Residence',
            'funeral_service_at' => $funeralDate,
            'interment_at' => $intermentAt,
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
            'senior_citizen_id_number' => null,
            'pwd_id_number' => null,
        ], $overrides);
    }
}
