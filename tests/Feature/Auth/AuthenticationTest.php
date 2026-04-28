<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $response->assertStatus(200)
            ->assertSee('data-theme-toggle', false)
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_authenticated_users_are_redirected_away_from_login_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/staff');
    }

    public function test_branch_admin_login_redirects_to_admin_dashboard(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR002',
            'branch_name' => 'Branch Two',
            'address' => 'Test Address',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin');
    }

    public function test_authenticated_dashboard_pages_are_sent_with_no_cache_headers(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $response->assertRedirect('/staff')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_logged_out_users_can_no_longer_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->get('/dashboard')
            ->assertRedirect('/login');
        $this->assertGuest();
    }
}
