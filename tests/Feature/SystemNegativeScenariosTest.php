<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemNegativeScenariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_main_staff_uses_assigned_branch_modules_only(): void
    {
        $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Other Branch');
        $staff = $this->createUser('staff', $otherBranch, false);

        $this->actingAs($staff)->get('/clients')->assertOk();
        $this->actingAs($staff)->get('/deceased')->assertRedirect(route('clients.index', absolute: false));
        $this->actingAs($staff)->get('/payments')->assertOk();
        $this->actingAs($staff)->get('/intake/other')->assertStatus(302);
        $this->actingAs($staff)->get('/other-branch-reports')->assertStatus(302);
    }

    public function test_admin_cannot_create_staff_user_without_branch_assignment(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->actingAs($admin)->from('/admin/users/create')->post('/admin/users', [
            'name' => 'Staff No Branch',
            'email' => 'staff.nobranch@example.com',
            'password' => 'secret123',
            'role' => 'staff',
        ]);

        $response->assertRedirect('/admin/users/create');
        $response->assertSessionHasErrors('branch_id');
        $this->assertDatabaseMissing('users', ['email' => 'staff.nobranch@example.com']);
    }

    public function test_staff_cannot_create_case_with_mismatched_client_and_deceased(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $clientA = $this->createClient($mainBranch, 'Client One');
        $clientB = $this->createClient($mainBranch, 'Client Two');
        $deceasedOfClientB = $this->createDeceased($mainBranch, $clientB, 'Deceased Two');

        $response = $this->actingAs($staff)->from('/funeral-cases/create')->post('/funeral-cases', [
            'client_id' => $clientA->id,
            'deceased_id' => $deceasedOfClientB->id,
            'package_id' => $package->id,
            'case_status' => 'ACTIVE',
        ]);

        $response->assertRedirect('/funeral-cases/create');
        $response->assertSessionHasErrors('deceased_id');
        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_staff_cannot_create_duplicate_active_case_for_same_deceased(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Client Three');
        $deceased = $this->createDeceased($mainBranch, $client, 'Deceased Three');

        $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC0001',
            'case_status' => 'ACTIVE',
            'payment_status' => 'UNPAID',
            'total_paid' => 0,
            'balance_amount' => 20000,
        ]);

        $response = $this->actingAs($staff)->from('/funeral-cases/create')->post('/funeral-cases', [
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_status' => 'ACTIVE',
        ]);

        $response->assertRedirect('/funeral-cases/create');
        $response->assertSessionHasErrors('deceased_id');
        $this->assertDatabaseCount('funeral_cases', 1);
    }

    public function test_payment_rejects_overpayment_amount(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Client Four');
        $deceased = $this->createDeceased($mainBranch, $client, 'Deceased Four');

        $case = $this->createCase($mainBranch, $client, $deceased, $package, [
            'case_code' => 'FC0002',
            'total_amount' => 10000,
            'total_paid' => 4000,
            'balance_amount' => 6000,
            'payment_status' => 'PARTIAL',
            'case_status' => 'ACTIVE',
            'entry_source' => 'MAIN',
        ]);

        $response = $this->actingAs($staff)->from('/payments')->post('/payments/pay', [
            'funeral_case_id' => $case->id,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount_paid' => 7000,
        ]);

        $response->assertRedirect('/payments');
        $response->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 0);

        $case->refresh();
        $this->assertSame('PARTIAL', $case->payment_status);
        $this->assertEquals(4000.0, (float) $case->total_paid);
        $this->assertEquals(6000.0, (float) $case->balance_amount);
    }

    public function test_intake_rejects_duplicate_client_in_same_branch(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        $this->createClient($mainBranch, 'Juan Dela Cruz', '09171234567', 'Sample Address');

        $response = $this->actingAs($staff)->from('/intake/main')->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'client_name' => 'Juan Dela Cruz',
            'client_contact_number' => '09171234567',
            'client_address' => 'Sample Address',
            'deceased_name' => 'Maria Santos',
            'deceased_address' => 'Sample Address',
            'died' => now()->subDay()->toDateString(),
        ]));

        $response->assertRedirect('/intake/main');
        $response->assertSessionHasErrors('client_name');
        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_intake_rejects_interment_on_same_date_as_death(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $deathDate = now()->toDateString();

        $response = $this->actingAs($staff)->from('/intake/main')->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'client_name' => 'Client Date Rule',
            'client_contact_number' => '09179990000',
            'client_address' => 'Sample Address',
            'deceased_name' => 'Deceased Date Rule',
            'deceased_address' => 'Sample Address',
            'died' => $deathDate,
            'funeral_service_at' => $deathDate,
            'interment_at' => now()->setTime(10, 0)->format('Y-m-d H:i:s'),
        ]));

        $response->assertRedirect('/intake/main');
        $response->assertSessionHasErrors('interment_at');
        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_intake_rejects_future_date_of_death(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        $response = $this->actingAs($staff)->from('/intake/main')->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'died' => now()->addDay()->toDateString(),
            'funeral_service_at' => now()->addDays(2)->toDateString(),
            'interment_at' => now()->addDays(3)->setTime(10, 0)->format('Y-m-d H:i:s'),
        ]));

        $response->assertRedirect('/intake/main');
        $response->assertSessionHasErrors('died');
        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_intake_rejects_funeral_service_date_before_date_of_death(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();
        $deathDate = now()->subDay()->toDateString();

        $response = $this->actingAs($staff)->from('/intake/main')->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'died' => $deathDate,
            'funeral_service_at' => now()->subDays(2)->toDateString(),
            'interment_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
        ]));

        $response->assertRedirect('/intake/main');
        $response->assertSessionHasErrors('funeral_service_at');
        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_intake_rejects_invalid_client_contact_format(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $package = $this->createPackage();

        $response = $this->actingAs($staff)->from('/intake/main')->post('/intake/main', $this->baseIntakePayload($mainBranch, $package, [
            'client_contact_number' => '9170000000',
        ]));

        $response->assertRedirect('/intake/main');
        $response->assertSessionHasErrors('client_contact_number');
        $this->assertDatabaseCount('funeral_cases', 0);
    }

    public function test_deceased_update_rejects_interment_on_same_date_as_death(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $staff = $this->createUser('staff', $mainBranch, true);
        $client = $this->createClient($mainBranch, 'Client Date Guard');
        $existing = $this->createDeceased($mainBranch, $client, 'Date Guard Deceased');
        $originalInterment = $existing->interment?->toDateString();
        $deathDate = now()->toDateString();

        $response = $this->actingAs($staff)->from("/deceased/{$existing->id}/edit")->put("/deceased/{$existing->id}", [
            'client_id' => $client->id,
            'full_name' => 'Date Guard Deceased',
            'address' => 'Client Address',
            'born' => now()->subYears(70)->toDateString(),
            'died' => $deathDate,
            'interment_at' => now()->setTime(11, 30)->format('Y-m-d H:i:s'),
            'place_of_cemetery' => 'Public Cemetery',
            'embalming_required' => 1,
            'embalming_status' => 'PENDING',
        ]);

        $response->assertRedirect("/deceased/{$existing->id}/edit");
        $response->assertSessionHasErrors('interment_at');

        $existing->refresh();
        $this->assertSame($originalInterment, $existing->interment?->toDateString());
    }

    private function createUser(string $role, ?Branch $branch = null, bool $canEncodeAnyBranch = false): User
    {
        return User::factory()->create([
            'role' => $role,
            'admin_scope' => $role === 'admin' ? 'main' : null,
            'is_active' => true,
            'branch_id' => $branch?->id,
            'can_encode_any_branch' => $canEncodeAnyBranch,
        ]);
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

    private function createClient(Branch $branch, string $name, string $contact = '09170000000', string $address = 'Client Address'): Client
    {
        return Client::create([
            'branch_id' => $branch->id,
            'full_name' => $name,
            'relationship_to_deceased' => 'Other',
            'contact_number' => $contact,
            'valid_id_type' => 'Legacy Record',
            'valid_id_number' => 'LEGACY-' . strtoupper((string) \Illuminate\Support\Str::ulid()),
            'address' => $address,
        ]);
    }

    private function createDeceased(Branch $branch, Client $client, string $name): Deceased
    {
        return Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => $name,
            'address' => 'Client Address',
            'born' => now()->subYears(60)->toDateString(),
            'died' => now()->subDays(2)->toDateString(),
            'date_of_death' => now()->subDays(2)->toDateString(),
            'age' => 60,
            'interment' => now()->addDay()->toDateString(),
            'interment_at' => now()->addDay(),
            'place_of_cemetery' => 'Public Cemetery',
            'embalming_required' => true,
            'embalming_status' => 'PENDING',
        ]);
    }

    private function createCase(Branch $branch, Client $client, Deceased $deceased, Package $package, array $overrides = []): FuneralCase
    {
        return FuneralCase::create(array_merge([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_code' => 'FC0009',
            'service_requested_at' => now()->toDateString(),
            'service_package' => $package->name,
            'coffin_type' => $package->coffin_type,
            'wake_location' => 'Family Residence',
            'funeral_service_at' => now()->addDay()->toDateString(),
            'subtotal_amount' => 20000,
            'discount_type' => 'NONE',
            'discount_value_type' => 'AMOUNT',
            'discount_value' => 0,
            'discount_amount' => 0,
            'total_amount' => 20000,
            'total_paid' => 0,
            'balance_amount' => 20000,
            'payment_status' => 'UNPAID',
            'paid_at' => null,
            'case_status' => 'ACTIVE',
            'reported_branch_id' => $branch->id,
            'reported_at' => now(),
            'encoded_by' => null,
            'entry_source' => 'MAIN',
            'verification_status' => 'VERIFIED',
            'verified_by' => null,
            'verified_at' => now(),
            'verification_note' => null,
        ], $overrides));
    }

    private function baseIntakePayload(Branch $branch, Package $package, array $overrides = []): array
    {
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
            'died' => now()->subDay()->toDateString(),
            'civil_status' => 'MARRIED',
            'pwd_status' => 0,
            'wake_location' => 'Family Residence',
            'funeral_service_at' => now()->addDay()->toDateString(),
            'interment_at' => now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i:s'),
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
