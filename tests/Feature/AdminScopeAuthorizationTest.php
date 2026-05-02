<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScopeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_branch_admin_keeps_global_admin_access(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/branches')->assertOk();
        $this->actingAs($admin)->get('/admin/packages')->assertOk();
    }

    public function test_legacy_main_branch_admin_without_scope_still_keeps_admin_access(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => null,
            'branch_id' => $mainBranch->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->assertTrue($admin->fresh()->isMainBranchAdmin());
    }

    public function test_branch_admin_is_sent_to_admin_dashboard_and_can_manage_own_branch_users(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = $this->createBranchAdmin($branch);

        $this->actingAs($branchAdmin)->get('/dashboard')->assertRedirect('/admin');
        $this->actingAs($branchAdmin)->get('/admin')->assertOk();
        $this->actingAs($branchAdmin)->get('/admin/users')->assertOk();
        $this->actingAs($branchAdmin)->get('/admin/branches')->assertForbidden();
        $this->actingAs($branchAdmin)->get('/admin/packages')->assertOk();
        $this->actingAs($branchAdmin)->get('/admin/reports/sales')->assertOk();
        $this->actingAs($branchAdmin)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_branch_admin_sidebar_shows_user_management_only(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = $this->createBranchAdmin($branch);

        $this->actingAs($branchAdmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('User Management')
            ->assertDontSee('Branch Management');
    }

    public function test_branch_admin_has_read_only_package_access_to_active_packages(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = $this->createBranchAdmin($branch);
        $activePackage = $this->createPackage();
        $inactivePackage = Package::create([
            'name' => 'Inactive Package',
            'coffin_type' => 'Premium',
            'price' => 30000,
            'is_active' => false,
        ]);

        $response = $this->actingAs($branchAdmin)->get('/admin/packages');

        $response->assertOk()
            ->assertSee('Read-only access')
            ->assertSee($activePackage->name)
            ->assertDontSee($inactivePackage->name)
            ->assertDontSee('Add Package')
            ->assertDontSee('Update Price')
            ->assertDontSee('Edit Package');
    }

    public function test_branch_admin_cannot_submit_package_write_routes(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = $this->createBranchAdmin($branch);
        $package = $this->createPackage();

        $this->actingAs($branchAdmin)
            ->get('/admin/packages/create')
            ->assertForbidden()
            ->assertSee('Branch admins have read-only access to packages.');

        $this->actingAs($branchAdmin)
            ->get("/admin/packages/{$package->id}/edit")
            ->assertForbidden()
            ->assertSee('Branch admins have read-only access to packages.');

        $this->actingAs($branchAdmin)
            ->patch("/admin/packages/{$package->id}/quick-price", ['price' => 25000])
            ->assertForbidden()
            ->assertSee('Branch admins have read-only access to packages.');

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'price' => 20000,
        ]);
    }

    public function test_branch_admin_main_intake_uses_assigned_branch_instead_of_br001(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $branch = $this->createBranch('BR002', 'Branch Two');
        $package = $this->createPackage();
        $branchAdmin = $this->createBranchAdmin($branch);

        $response = $this->actingAs($branchAdmin)->post('/intake/main', [
            'service_requested_at' => now()->toDateString(),
            'branch_id' => $mainBranch->id,
            'client_first_name' => 'Branch',
            'client_last_name' => 'Client',
            'client_name' => 'Branch Admin Client',
            'client_relationship' => 'Daughter',
            'client_contact_number' => '09170000000',
            'client_email' => 'branch-admin@example.com',
            'client_valid_id_type' => 'National ID',
            'client_valid_id_number' => 'ID-5000',
            'client_address' => 'Branch Two Address',
            'deceased_first_name' => 'Branch',
            'deceased_last_name' => 'Deceased',
            'deceased_name' => 'Branch Admin Deceased',
            'deceased_address' => 'Branch Two Address',
            'born' => now()->subYears(65)->toDateString(),
            'died' => now()->subDay()->toDateString(),
            'civil_status' => 'MARRIED',
            'pwd_status' => 0,
            'wake_location' => 'Branch Two Chapel',
            'funeral_service_at' => now()->addDay()->toDateString(),
            'interment_at' => now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i:s'),
            'place_of_cemetery' => 'Branch Two Cemetery',
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
            'confirm_review' => 1,
        ]);

        $response->assertRedirect(route('funeral-cases.index', ['record_scope' => 'main'], absolute: false));

        $this->assertDatabaseHas('clients', [
            'full_name' => 'Branch Client',
            'branch_id' => $branch->id,
        ]);
        $this->assertDatabaseHas('funeral_cases', [
            'branch_id' => $branch->id,
            'entry_source' => 'MAIN',
            'case_status' => 'ACTIVE',
        ]);
    }

    public function test_main_branch_admin_cannot_create_owner_accounts_or_assign_main_scope_through_user_management(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Owner Attempt',
                'email' => 'owner.attempt@gmail.com',
                'password' => 'secret123',
                'role' => 'owner',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors('role');

        $this->actingAs($admin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Tampered Main Admin',
                'email' => 'tampered.main@gmail.com',
                'password' => 'secret123',
                'role' => 'admin',
                'admin_scope' => 'main',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors('admin_scope');

        $this->assertDatabaseMissing('users', ['email' => 'owner.attempt@gmail.com']);
        $this->assertDatabaseMissing('users', ['email' => 'tampered.main@gmail.com']);
    }

    public function test_main_branch_admin_can_create_branch_admin_but_new_admin_is_stored_with_branch_scope(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Branch Two');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Branch Admin Created',
                'email' => 'branch.admin.created@gmail.com',
                'password' => 'secret123',
                'role' => 'admin',
                'branch_id' => $otherBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'branch.admin.created@gmail.com',
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $otherBranch->id,
        ]);
    }

    public function test_main_admin_can_create_staff_account(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Branch Two');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'New Staff Member',
                'email' => 'new.staff@gmail.com',
                'password' => 'secret123',
                'role' => 'staff',
                'branch_id' => $otherBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'new.staff@gmail.com',
            'role' => 'staff',
            'branch_id' => $otherBranch->id,
            'admin_scope' => null,
        ]);
    }

    public function test_create_user_rejects_invalid_email_format(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Invalid Email Format',
                'email' => 'abc',
                'password' => 'secret123',
                'role' => 'staff',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors(['email' => 'Please enter a valid email address.']);
    }

    public function test_create_user_rejects_email_with_invalid_domain(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Invalid Email Domain',
                'email' => 'user@fake-domain-that-does-not-exist.test',
                'password' => 'secret123',
                'role' => 'staff',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors(['email' => 'The email domain appears to be invalid.']);
    }

    public function test_create_user_rejects_duplicate_email(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);
        User::factory()->create(['email' => 'duplicate.user@gmail.com']);

        $this->actingAs($admin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Duplicate Email',
                'email' => 'duplicate.user@gmail.com',
                'password' => 'secret123',
                'role' => 'staff',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors(['email' => 'This email address is already taken.']);
    }

    public function test_create_user_accepts_valid_email_domain(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Valid Email Domain',
                'email' => 'valid.user.domain@gmail.com',
                'password' => 'secret123',
                'role' => 'staff',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'valid.user.domain@gmail.com',
            'role' => 'staff',
            'branch_id' => $mainBranch->id,
        ]);
    }

    public function test_update_user_allows_current_email_but_rejects_duplicate_email(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);
        $staff = User::factory()->create([
            'email' => 'current.staff@gmail.com',
            'role' => 'staff',
            'branch_id' => $mainBranch->id,
            'is_active' => true,
        ]);
        User::factory()->create(['email' => 'taken.staff@gmail.com']);

        $this->actingAs($admin)
            ->put("/admin/users/{$staff->id}", [
                'name' => 'Current Staff',
                'email' => 'current.staff@gmail.com',
                'role' => 'staff',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $this->actingAs($admin)
            ->from("/admin/users/{$staff->id}/edit")
            ->put("/admin/users/{$staff->id}", [
                'name' => 'Current Staff',
                'email' => 'taken.staff@gmail.com',
                'role' => 'staff',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect("/admin/users/{$staff->id}/edit")
            ->assertSessionHasErrors(['email' => 'This email address is already taken.']);
    }

    public function test_created_staff_is_limited_to_assigned_branch(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Branch Two');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Branch Staff',
                'email' => 'branch.staff@gmail.com',
                'password' => 'secret123',
                'role' => 'staff',
                'branch_id' => $otherBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $staff = User::where('email', 'branch.staff@gmail.com')->first();

        $this->assertEquals($otherBranch->id, $staff->branch_id);
        $this->assertNull($staff->admin_scope);

        $this->actingAs($staff)->get('/admin')->assertForbidden();
        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
    }

    public function test_created_branch_admin_is_limited_to_assigned_branch(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Branch Two');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Branch Admin Test',
                'email' => 'new.branch.admin@gmail.com',
                'password' => 'secret123',
                'role' => 'admin',
                'branch_id' => $otherBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $branchAdmin = User::where('email', 'new.branch.admin@gmail.com')->first();

        $this->assertEquals($otherBranch->id, $branchAdmin->branch_id);
        $this->assertEquals('branch', $branchAdmin->admin_scope);

        $this->actingAs($branchAdmin)->get('/dashboard')->assertRedirect('/admin');
        $this->actingAs($branchAdmin)->get('/admin')->assertOk();
        $this->actingAs($branchAdmin)->get('/admin/users')->assertOk();
    }

    public function test_branch_admin_can_create_staff_only_for_own_branch(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $otherBranch = $this->createBranch('BR003', 'Branch Three');
        $branchAdmin = $this->createBranchAdmin($branch);

        $this->actingAs($branchAdmin)
            ->post('/admin/users', [
                'name' => 'Own Branch Staff',
                'email' => 'own.branch.staff@gmail.com',
                'password' => 'secret123',
                'role' => 'staff',
                'branch_id' => $otherBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'own.branch.staff@gmail.com',
            'role' => 'staff',
            'branch_id' => $branch->id,
            'admin_scope' => null,
        ]);
    }

    public function test_branch_admin_cannot_create_branch_admin_accounts(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $otherBranch = $this->createBranch('BR003', 'Branch Three');
        $branchAdmin = $this->createBranchAdmin($branch);

        $this->actingAs($branchAdmin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Unauthorized Branch Admin',
                'email' => 'unauthorized.branch.admin@gmail.com',
                'password' => 'secret123',
                'role' => 'admin',
                'branch_id' => $otherBranch->id,
            ])
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'unauthorized.branch.admin@gmail.com',
        ]);
    }

    public function test_staff_cannot_access_any_admin_routes(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Branch Two');
        $staff = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $otherBranch->id,
            'is_active' => true,
        ]);

        $this->actingAs($staff)->get('/admin')->assertForbidden();
        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
        $this->actingAs($staff)->get('/admin/branches')->assertForbidden();
        $this->actingAs($staff)->get('/admin/packages')->assertForbidden();
        $this->actingAs($staff)->get('/admin/reports/sales')->assertForbidden();
        $this->actingAs($staff)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_branch_admin_is_blocked_from_other_branch_intake(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = $this->createBranchAdmin($branch);

        $this->actingAs($branchAdmin)->get('/intake/other')->assertForbidden();
        $this->actingAs($branchAdmin)->post('/intake/other', [])->assertForbidden();
    }

    public function test_main_admin_can_access_monitoring_modules(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)->get('/admin/cases')->assertOk();
        $this->actingAs($admin)->get('/admin/payments')->assertOk();
        $this->actingAs($admin)->get('/admin/payment-monitoring')->assertOk();
        $this->actingAs($admin)->get('/admin/reminders')->assertOk();
        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();
    }

    public function test_branch_admin_can_access_admin_monitoring_and_reminders(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = $this->createBranchAdmin($branch);

        $this->actingAs($branchAdmin)->get('/admin')->assertOk();
        $this->actingAs($branchAdmin)->get('/admin/cases')->assertOk();
        $this->actingAs($branchAdmin)->get('/admin/payments')->assertOk();
        $this->actingAs($branchAdmin)->get('/payments/history')->assertOk();
        $this->actingAs($branchAdmin)->get('/admin/reminders')->assertOk();
        $this->actingAs($branchAdmin)->get('/reminders')->assertOk();
    }

    public function test_branch_admin_cannot_filter_admin_dashboard_to_another_branch(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $otherBranch = $this->createBranch('BR003', 'Branch Three');
        $branchAdmin = $this->createBranchAdmin($branch);

        $this->actingAs($branchAdmin)
            ->get('/admin?branch_id=' . $otherBranch->id)
            ->assertForbidden();

        $this->actingAs($branchAdmin)
            ->get('/admin/reports/sales?branch_id=' . $otherBranch->id)
            ->assertForbidden();

        $this->actingAs($branchAdmin)
            ->get('/admin/cases?branch_id=' . $otherBranch->id)
            ->assertForbidden();
    }

    public function test_create_user_form_has_no_pre_filled_email_or_password(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);

        $response = $this->actingAs($admin)->get('/admin/users/create');
        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringNotContainsString('owner@funeral.test', $html);
        $this->assertStringNotContainsString('admin@funeral.test', $html);
        $this->assertStringNotContainsString('staff@funeral.test', $html);

        // Password field must never carry a value attribute
        $this->assertDoesNotMatchRegularExpression('/name="password"[^>]*value=/i', $html);
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

    private function createMainAdmin(Branch $branch): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'main',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
    }

    private function createBranchAdmin(Branch $branch): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
    }
}
