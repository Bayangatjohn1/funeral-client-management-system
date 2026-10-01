<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\AddOnCatalog;
use App\Models\Branch;
use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;
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
        $this->actingAs($branchAdmin)->get('/admin/audit-logs')->assertOk();
    }

    public function test_branch_admin_sidebar_shows_user_management_only(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = $this->createBranchAdmin($branch);

        $this->actingAs($branchAdmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('User Management')
            ->assertSee('>Services</span>', false)
            ->assertDontSee('>Service Management</span>', false)
            ->assertDontSee('Branch Management');
    }

    public function test_branch_admin_user_directory_hides_fixed_branch_and_role_filters(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $otherBranch = $this->createBranch('BR003', 'Branch Three');
        $branchAdmin = $this->createBranchAdmin($branch);
        $staff = User::factory()->create([
            'name' => 'Scoped Staff User',
            'email' => 'scoped.staff@gmail.com',
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($branchAdmin)
            ->get('/admin/users?branch_id='.$otherBranch->id.'&role=admin')
            ->assertOk()
            ->assertSee($staff->email)
            ->assertDontSee('name="branch_id"', false)
            ->assertDontSee('name="role"', false)
            ->assertDontSee('All Branches')
            ->assertDontSee('All Roles')
            ->assertDontSee('Staff Only');
    }

    public function test_service_page_title_reflects_admin_access_level(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $branch = $this->createBranch('BR002', 'Branch Two');
        $mainAdmin = $this->createMainAdmin($mainBranch);
        $branchAdmin = $this->createBranchAdmin($branch);

        $this->actingAs($mainAdmin)
            ->get('/admin/service-management')
            ->assertOk()
            ->assertSee('>Service Management</h1>', false)
            ->assertDontSee('Read-only branch access');

        $this->actingAs($branchAdmin)
            ->get('/admin/service-management')
            ->assertOk()
            ->assertSee('>Services</h1>', false)
            ->assertSee('View active and archived service catalog records in read-only mode.')
            ->assertSee('Read-only branch access')
            ->assertDontSee('>Service Management</h1>', false);
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

    public function test_branch_admin_can_view_active_and_archived_catalog_details(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = $this->createBranchAdmin($branch);
        $activePackage = $this->createPackage();
        $archivedPackage = Package::create(['name' => 'Archived Package', 'price' => 30000, 'is_active' => false]);
        $archivedCasket = CasketCatalog::create(['name' => 'Archived Casket', 'standard_price' => 20000, 'is_active' => false, 'is_available' => false]);
        $archivedAddOn = AddOnCatalog::create(['name' => 'Archived Add-on', 'category' => 'Service', 'price' => 500, 'unit' => 'service', 'is_active' => false]);
        $archivedFreebie = FreebieCatalog::create(['name' => 'Archived Freebie', 'default_unit' => 'item', 'is_active' => false]);

        foreach ([
            route('admin.packages.show', $activePackage, absolute: false),
            route('admin.packages.show', $archivedPackage, absolute: false),
            route('admin.casket-catalogs.show', $archivedCasket, absolute: false),
            route('admin.add-on-catalogs.show', $archivedAddOn, absolute: false),
            route('admin.freebie-catalogs.show', $archivedFreebie, absolute: false),
        ] as $url) {
            $this->actingAs($branchAdmin)->get($url)->assertOk()->assertDontSee('Edit ');
        }

        $this->actingAs($branchAdmin)
            ->get('/admin/service-management?tab=packages&status=archived')
            ->assertOk()
            ->assertSee('Archived Package')
            ->assertSee('View Package')
            ->assertDontSee('Add Package');
    }

    public function test_branch_admin_catalog_write_routes_remain_forbidden(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $branch = $this->createBranch('BR002', 'Branch Two');
        $branchAdmin = $this->createBranchAdmin($branch);
        $casket = CasketCatalog::create(['name' => 'Protected Casket', 'standard_price' => 20000, 'is_active' => true, 'is_available' => true]);
        $addOn = AddOnCatalog::create(['name' => 'Protected Add-on', 'category' => 'Service', 'price' => 500, 'unit' => 'service', 'is_active' => true]);
        $freebie = FreebieCatalog::create(['name' => 'Protected Freebie', 'default_unit' => 'item', 'is_active' => true]);

        $this->actingAs($branchAdmin)->get(route('admin.casket-catalogs.create', absolute: false))->assertForbidden();
        $this->actingAs($branchAdmin)->patch(route('admin.casket-catalogs.toggleActive', $casket, absolute: false))->assertForbidden();
        $this->actingAs($branchAdmin)->patch(route('admin.casket-catalogs.toggleAvailability', $casket, absolute: false))->assertForbidden();
        $this->actingAs($branchAdmin)->get(route('admin.add-on-catalogs.create', absolute: false))->assertForbidden();
        $this->actingAs($branchAdmin)->patch(route('admin.add-on-catalogs.toggleActive', $addOn, absolute: false))->assertForbidden();
        $this->actingAs($branchAdmin)->get(route('admin.freebie-catalogs.create', absolute: false))->assertForbidden();
        $this->actingAs($branchAdmin)->patch(route('admin.freebie-catalogs.toggleActive', $freebie, absolute: false))->assertForbidden();
    }

    public function test_service_management_paginates_and_searches_the_full_package_catalog(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $mainAdmin = $this->createMainAdmin($mainBranch);

        foreach (range(1, 21) as $number) {
            Package::create([
                'name' => sprintf('Catalog Package %02d', $number),
                'coffin_type' => 'Standard',
                'price' => 20000 + $number,
                'is_active' => true,
            ]);
        }
        $expectedTotal = Package::count();

        $this->actingAs($mainAdmin)
            ->get('/admin/service-management?tab=packages')
            ->assertOk()
            ->assertSee("Showing records 1-20 of {$expectedTotal}")
            ->assertDontSee('Catalog Package 21');

        $this->actingAs($mainAdmin)
            ->get('/admin/service-management?tab=packages&packages_page=2')
            ->assertOk()
            ->assertSee("Showing records 21-{$expectedTotal} of {$expectedTotal}")
            ->assertSee('Catalog Package 21');

        $this->actingAs($mainAdmin)
            ->get('/admin/service-management?tab=packages&q=Catalog+Package+21')
            ->assertOk()
            ->assertSee('Catalog Package 21')
            ->assertDontSee('Catalog Package 20');
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
            'wake_start_date' => now()->toDateString(),
            'wake_start_time' => '08:00',
            'wake_end_date' => now()->addDay()->toDateString(),
            'wake_end_time' => '08:00',
            'funeral_service_at' => now()->addDay()->toDateString(),
            'funeral_service_time' => '09:00',
            'interment_at' => now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i:s'),
            'interment_time' => '09:00',
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
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'New Staff Member',
                'email' => 'new.staff@gmail.com',
                'password' => 'secret123',
                'role' => 'staff',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'new.staff@gmail.com',
            'role' => 'staff',
            'branch_id' => $mainBranch->id,
            'admin_scope' => null,
        ]);
    }

    public function test_created_user_password_is_hashed_by_laravel(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);
        $plainPassword = 'K7#mQ2pW9!sR4x';

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Generated Password Staff',
                'email' => 'generated.password@gmail.com',
                'password' => $plainPassword,
                'role' => 'staff',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $user = User::where('email', 'generated.password@gmail.com')->firstOrFail();

        $this->assertNotSame($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    public function test_unassigned_system_admin_can_assign_staff_to_an_active_branch(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Branch Two');
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'main',
            'branch_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Legacy Main Staff',
                'email' => 'legacy.main.staff@gmail.com',
                'password' => 'K7#mQ2pW9!sR4x',
                'role' => 'staff',
                'branch_id' => $otherBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'legacy.main.staff@gmail.com',
            'branch_id' => $otherBranch->id,
        ]);
    }

    public function test_create_staff_form_shows_the_main_branch_and_no_position_field(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $this->createBranch('BR002', 'Branch Two');
        $admin = $this->createMainAdmin($mainBranch);

        $this->actingAs($admin)
            ->get('/admin/users/create')
            ->assertOk()
            ->assertSee('Main Branch')
            ->assertSee('BR001 - Main Branch')
            ->assertSee('BR002 - Branch Two')
            ->assertDontSee('data-current-branch', false)
            ->assertDontSee('Select position')
            ->assertDontSee('name="position"', false);
    }

    public function test_user_directory_does_not_show_or_search_the_legacy_position_field(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createMainAdmin($mainBranch);
        User::factory()->create([
            'name' => 'Legacy Position User',
            'email' => 'legacy.position@gmail.com',
            'role' => 'staff',
            'branch_id' => $mainBranch->id,
            'position' => 'UniquePositionNeedle',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertDontSee('Position</th>', false)
            ->assertDontSee('No position set')
            ->assertDontSee('UniquePositionNeedle');

        $this->actingAs($admin)
            ->get('/admin/users?q=UniquePositionNeedle')
            ->assertOk()
            ->assertDontSee('legacy.position@gmail.com');
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
            'position' => 'Legacy Position',
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

        $this->assertSame('Legacy Position', $staff->fresh()->position);

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

    public function test_system_admin_created_staff_uses_the_selected_branch(): void
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
            ->get('/admin/users/create')
            ->assertOk()
            ->assertDontSee('<select id="role"', false)
            ->assertDontSee('<select id="branch_id"', false)
            ->assertSee('Assigned Role')
            ->assertSee('Assigned Branch')
            ->assertSee('Staff')
            ->assertSee('BR002 - Branch Two')
            ->assertSee('<input type="hidden" name="role" value="staff">', false)
            ->assertSee('<input type="hidden" name="branch_id" value="'.$branch->id.'">', false);

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

    public function test_branch_admin_audit_log_page_is_limited_to_assigned_branch(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $otherBranch = $this->createBranch('BR003', 'Branch Three');
        $branchAdmin = $this->createBranchAdmin($branch);

        $assignedLog = AuditLog::create([
            'actor_id' => $branchAdmin->id,
            'actor_role' => 'admin',
            'action' => 'assigned.action',
            'action_label' => 'Assigned Branch Action',
            'action_type' => 'create',
            'entity_type' => 'funeral_case',
            'entity_id' => 1,
            'branch_id' => $branch->id,
            'target_branch_id' => $branch->id,
            'status' => 'success',
        ]);

        $otherLog = AuditLog::create([
            'actor_id' => $branchAdmin->id,
            'actor_role' => 'admin',
            'action' => 'other.action',
            'action_label' => 'Other Branch Action',
            'action_type' => 'create',
            'entity_type' => 'funeral_case',
            'entity_id' => 2,
            'branch_id' => $otherBranch->id,
            'target_branch_id' => $otherBranch->id,
            'status' => 'success',
        ]);

        $this->actingAs($branchAdmin)
            ->get('/admin/audit-logs?branch_id=' . $otherBranch->id)
            ->assertOk()
            ->assertSee('Assigned Branch Action')
            ->assertDontSee('Other Branch Action');

        $this->actingAs($branchAdmin)
            ->getJson("/admin/audit-logs/{$assignedLog->id}")
            ->assertOk()
            ->assertJsonPath('action_label', 'Assigned Branch Action');

        $this->actingAs($branchAdmin)
            ->getJson("/admin/audit-logs/{$otherLog->id}")
            ->assertForbidden();
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
        $this->assertStringContainsString('id="togglePassword"', $html);
        $this->assertStringContainsString('id="copyPassword"', $html);
        $this->assertStringContainsString('id="generatePassword"', $html);
        $this->assertStringContainsString('window.crypto.getRandomValues', $html);
        $this->assertStringContainsString('data-name-case', $html);
        $this->assertStringContainsString('normalizeLowercaseName', $html);
    }

    public function test_system_admin_can_create_a_branch_admin_for_the_main_branch(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $systemAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'system',
            'branch_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($systemAdmin)
            ->post('/admin/users', [
                'name' => 'Main Branch Administrator',
                'email' => 'main.branch.admin@gmail.com',
                'password' => 'K7#mQ2pW9!sR4x',
                'role' => 'branch_admin',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $createdAdmin = User::where('email', 'main.branch.admin@gmail.com')->firstOrFail();

        $this->assertTrue($createdAdmin->isBranchAdmin());
        $this->assertFalse($createdAdmin->isSystemAdmin());
        $this->assertSame([$mainBranch->id], $createdAdmin->branchScopeIds());
    }

    public function test_system_admin_can_create_a_branch_admin_for_another_branch(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $otherBranch = $this->createBranch('BR002', 'Branch Two');
        $systemAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'system',
            'branch_id' => null,
            'is_active' => true,
        ]);

        $formResponse = $this->actingAs($systemAdmin)->get('/admin/users/create');
        $formResponse
            ->assertOk()
            ->assertSee('<option value="branch_admin"', false)
            ->assertSee('BR001 - Main Branch')
            ->assertSee('BR002 - Branch Two')
            ->assertSee("branchHint.textContent = isBranchAdmin", false)
            ->assertSee('including the Main Branch', false);

        $mainBranchOptionPattern = '/<option\s+value="'.preg_quote((string) $mainBranch->id, '/').'"[^>]*>\s*BR001 - Main Branch/s';
        $disabledMainBranchPattern = '/<option\s+value="'.preg_quote((string) $mainBranch->id, '/').'"[^>]*disabled/s';
        $this->assertMatchesRegularExpression($mainBranchOptionPattern, $formResponse->getContent());
        $this->assertDoesNotMatchRegularExpression($disabledMainBranchPattern, $formResponse->getContent());

        $this->actingAs($systemAdmin)
            ->post('/admin/users', [
                'name' => 'Branch Two Administrator',
                'email' => 'branch.two.admin@gmail.com',
                'password' => 'K7#mQ2pW9!sR4x',
                'role' => 'branch_admin',
                'branch_id' => $otherBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false))
            ->assertSessionHas('success', 'User created successfully.');

        $createdAdmin = User::where('email', 'branch.two.admin@gmail.com')->firstOrFail();

        $this->assertTrue($createdAdmin->isBranchAdmin());
        $this->assertFalse($createdAdmin->isSystemAdmin());
        $this->assertSame($otherBranch->id, (int) $createdAdmin->branch_id);
        $this->assertSame([$otherBranch->id], $createdAdmin->branchScopeIds());
        $this->assertNotContains($mainBranch->id, $createdAdmin->branchScopeIds());
    }

    public function test_system_admin_can_create_multiple_active_branch_admins_for_one_branch(): void
    {
        $branch = $this->createBranch('BR002', 'Branch Two');
        $systemAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'system',
            'branch_id' => null,
            'is_active' => true,
        ]);
        $this->createBranchAdmin($branch);

        $form = $this->actingAs($systemAdmin)->get('/admin/users/create');
        $form->assertOk()->assertSee('BR002 - Branch Two');
        $this->assertStringNotContainsString('already has Branch Admin', $form->getContent());

        $this->actingAs($systemAdmin)
            ->post('/admin/users', [
                'name' => 'Second Branch Administrator',
                'email' => 'second.branch.admin@gmail.com',
                'password' => 'K7#mQ2pW9!sR4x',
                'role' => 'branch_admin',
                'branch_id' => $branch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $this->assertSame(2, User::query()
            ->where('role', 'admin')
            ->where('admin_scope', 'branch')
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->count());
    }

    public function test_system_admin_selection_creates_an_unassigned_global_admin(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $systemAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'system',
            'branch_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($systemAdmin)
            ->post('/admin/users', [
                'name' => 'Second System Administrator',
                'email' => 'second.system.admin@gmail.com',
                'password' => 'K7#mQ2pW9!sR4x',
                'role' => 'system_admin',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'second.system.admin@gmail.com',
            'role' => 'admin',
            'admin_scope' => 'system',
            'branch_id' => null,
        ]);
    }

    public function test_crafted_admin_scope_cannot_escalate_a_branch_admin_request(): void
    {
        $mainBranch = $this->createBranch('BR001', 'Main Branch');
        $systemAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'system',
            'branch_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($systemAdmin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Crafted Scope User',
                'email' => 'crafted.scope@gmail.com',
                'password' => 'K7#mQ2pW9!sR4x',
                'role' => 'admin',
                'admin_scope' => 'system',
                'branch_id' => $mainBranch->id,
            ])
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'crafted.scope@gmail.com']);
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
