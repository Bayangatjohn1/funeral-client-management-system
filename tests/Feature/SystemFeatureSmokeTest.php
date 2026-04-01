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

class SystemFeatureSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_management_features_work(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createUser('admin');

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/branches')->assertOk();
        $this->actingAs($admin)->get('/admin/packages')->assertOk();

        $this->actingAs($admin)
            ->post('/admin/branches', [
                'branch_name' => 'North Branch',
                'address' => 'North St',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.branches.index', absolute: false));

        $this->assertDatabaseHas('branches', [
            'branch_name' => 'North Branch',
            'is_active' => 1,
        ]);

        $this->actingAs($admin)
            ->post('/admin/packages', [
                'name' => 'Premium Plan',
                'coffin_type' => 'Mahogany',
                'price' => 50000,
                'inclusions' => 'Basic inclusions',
                'freebies' => 'Flowers',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.packages.index', absolute: false));

        $this->assertDatabaseHas('packages', [
            'name' => 'Premium Plan',
            'price' => 50000,
        ]);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Staff One',
                'email' => 'staff.one@example.com',
                'password' => 'secret123',
                'role' => 'staff',
                'branch_id' => $mainBranch->id,
                'can_encode_any_branch' => 1,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'staff.one@example.com',
            'role' => 'staff',
            'branch_id' => $mainBranch->id,
        ]);
    }

    public function test_staff_case_and_payment_features_work(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $this->createBranch('BR002', 'Other Branch');
        $staff = $this->createUser('staff', $mainBranch);
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Client One');
        $deceased = $this->createDeceased($mainBranch, $client, 'Deceased One');

        $this->actingAs($staff)->get('/staff')->assertOk();
        $this->actingAs($staff)->get('/intake/main')->assertOk();
        $this->actingAs($staff)->get('/funeral-cases')->assertOk();
        $this->actingAs($staff)->get('/completed-cases')->assertOk();
        $this->actingAs($staff)->get('/payments')->assertOk();
        $this->actingAs($staff)->get('/payments/history')->assertOk();
        $this->actingAs($staff)->get('/clients')->assertOk();
        $this->actingAs($staff)->get('/deceased')->assertOk();

        $this->actingAs($staff)
            ->post('/funeral-cases', [
                'client_id' => $client->id,
                'deceased_id' => $deceased->id,
                'package_id' => $package->id,
                'case_status' => 'ACTIVE',
            ])
            ->assertRedirect(route('funeral-cases.index', absolute: false));

        $case = FuneralCase::query()->first();
        $this->assertNotNull($case);

        $this->actingAs($staff)
            ->post('/payments/pay', [
                'funeral_case_id' => $case->id,
                'paid_at' => now()->format('Y-m-d H:i:s'),
                'amount_paid' => (float) $case->total_amount,
            ])
            ->assertRedirect(route('payments.index', absolute: false));

        $case->refresh();
        $this->assertSame('PAID', $case->payment_status);
        $this->assertEquals((float) $case->total_amount, (float) $case->total_paid);
        $this->assertEquals(0.0, (float) $case->balance_amount);
    }

    public function test_owner_reporting_features_work(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');
        $package = $this->createPackage();
        $client = $this->createClient($mainBranch, 'Owner Client');
        $deceased = $this->createDeceased($mainBranch, $client, 'Owner Deceased');

        $case = FuneralCase::create([
            'branch_id' => $mainBranch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_code' => 'FC9001',
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
            'total_paid' => 20000,
            'balance_amount' => 0,
            'payment_status' => 'PAID',
            'paid_at' => now(),
            'case_status' => 'COMPLETED',
            'reported_branch_id' => $mainBranch->id,
            'reported_at' => now(),
            'entry_source' => 'MAIN',
            'verification_status' => 'VERIFIED',
            'verified_by' => $owner->id,
            'verified_at' => now(),
            'verification_note' => 'Smoke test',
            'encoded_by' => $owner->id,
        ]);

        $this->actingAs($owner)->get('/owner')->assertOk();
        $this->actingAs($owner)->get('/owner/branch-analytics')->assertOk();
        $this->actingAs($owner)->get('/owner/case-history')->assertOk();
        $this->actingAs($owner)->get('/owner/sales-per-branch')->assertOk();
        $this->actingAs($owner)->get("/owner/cases/{$case->id}")->assertOk();

        $export = $this->actingAs($owner)->get('/owner/sales-per-branch/export');
        $export->assertOk();
        $export->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_other_branch_report_access_depends_on_staff_scope(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Other Branch');
        $package = $this->createPackage();

        $client = $this->createClient($otherBranch, 'Other Client');
        $deceased = $this->createDeceased($otherBranch, $client, 'Other Deceased');

        FuneralCase::create([
            'branch_id' => $otherBranch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_code' => 'FC7001',
            'service_requested_at' => now()->toDateString(),
            'service_package' => $package->name,
            'coffin_type' => $package->coffin_type,
            'wake_location' => 'Family Residence',
            'funeral_service_at' => now()->addDay()->toDateString(),
            'subtotal_amount' => 15000,
            'discount_type' => 'NONE',
            'discount_value_type' => 'AMOUNT',
            'discount_value' => 0,
            'discount_amount' => 0,
            'total_amount' => 15000,
            'total_paid' => 15000,
            'balance_amount' => 0,
            'payment_status' => 'PAID',
            'paid_at' => now(),
            'case_status' => 'COMPLETED',
            'reported_branch_id' => $otherBranch->id,
            'reporter_name' => 'Reporter One',
            'reporter_contact' => '09170000000',
            'reported_at' => now(),
            'entry_source' => 'OTHER_BRANCH',
            'verification_status' => 'PENDING',
            'encoded_by' => null,
        ]);

        $mainStaff = $this->createUser('staff', $mainBranch, true);
        $otherStaff = $this->createUser('staff', $otherBranch, false);

        $this->actingAs($mainStaff)->get('/other-branch-reports')->assertOk();
        $this->actingAs($otherStaff)->get('/other-branch-reports')->assertForbidden();
    }

    private function createUser(string $role, ?Branch $branch = null, bool $canEncodeAnyBranch = false): User
    {
        return User::factory()->create([
            'role' => $role,
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

    private function createClient(Branch $branch, string $name): Client
    {
        return Client::create([
            'branch_id' => $branch->id,
            'full_name' => $name,
            'relationship_to_deceased' => 'Other',
            'contact_number' => '09171234567',
            'valid_id_type' => 'Legacy Record',
            'valid_id_number' => 'LEGACY-' . strtoupper((string) \Illuminate\Support\Str::ulid()),
            'address' => 'Client Address',
        ]);
    }

    private function createDeceased(Branch $branch, Client $client, string $name): Deceased
    {
        return Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => $name,
            'address' => 'Same Address',
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
}
