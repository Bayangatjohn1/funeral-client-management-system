<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = $this->createActiveUser();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_staff_dashboard_uses_dashboard_header_without_layout_topbar(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR001',
            'branch_name' => 'Main Branch',
            'address' => 'Test Address',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertDontSee('<header class="topbar">', false)
            ->assertSee('prof-shell', false);

        $this->actingAs($user)
            ->get('/staff')
            ->assertOk()
            ->assertDontSee('<header class="topbar">', false)
            ->assertSee('staff-header-card', false)
            ->assertSee('topbar-notification', false);
    }

    public function test_staff_non_dashboard_pages_use_inline_header_without_notification_bell(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR001',
            'branch_name' => 'Main Branch',
            'address' => 'Test Address',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/funeral-cases?tab=active&record_scope=main')
            ->assertOk()
            ->assertDontSee('<header class="topbar">', false)
            ->assertSee('panel-page-header', false)
            ->assertDontSee('<div class="topbar-notification-wrap"', false);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->createActiveUser();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = $this->createActiveUser();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = $this->createActiveUser();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = $this->createActiveUser();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_position_and_contact_number_can_be_updated(): void
    {
        $user = $this->createActiveUser();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'contact_number' => '09171234567',
                'position' => 'Senior Staff',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('09171234567', $user->contact_number);
        $this->assertSame('Senior Staff', $user->position);
    }

    public function test_protected_fields_cannot_be_changed_through_profile_update(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR001',
            'branch_name' => 'Main Branch',
            'address' => 'Test Address',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'staff',
            'admin_scope' => null,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'admin',
                'admin_scope' => 'main',
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('staff', $user->role);
        $this->assertNull($user->admin_scope);
        $this->assertTrue($user->is_active);
    }

    public function test_any_role_can_access_own_profile_page(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR001',
            'branch_name' => 'Main Branch',
            'address' => 'Test',
            'is_active' => true,
        ]);

        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'main', 'branch_id' => $branch->id, 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);

        $this->actingAs($owner)->get('/profile')->assertOk();
        $this->actingAs($admin)->get('/profile')->assertOk();
        $this->actingAs($staff)->get('/profile')->assertOk();
    }

    private function createActiveUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
        ]);
    }
}
