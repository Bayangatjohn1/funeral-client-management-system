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
        $response->assertRedirect(route('security.setup'));
    }

    public function test_branch_admin_login_redirects_to_mfa_setup(): void
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
        $response->assertRedirect(route('security.setup'));
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

    public function test_login_throttle_survives_refresh_and_releases_after_the_window(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->assertGuest();
        $this->get('/login')->assertOk()->assertViewHas('throttleEmail', $user->email)->assertViewHas('throttleSeconds', 60);
        $this->travel(20)->seconds();
        $this->get('/login')->assertViewHas('throttleSeconds', 40);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->travel(40)->seconds();
        $this->get('/login')->assertViewHas('throttleSeconds', 0);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('security.setup'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_credentials_from_a_stale_login_page_do_not_resume_pending_mfa(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('security.setup'));
        $this->assertAuthenticatedAs($user);
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrect-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrect-password'])->assertSessionHasErrors('email');
            $this->assertGuest();
        }
        $this->get('/login')->assertOk()->assertViewHas('throttleSeconds', fn ($seconds) => $seconds > 0);
        $this->get(route('security.setup'))->assertRedirect(route('login'));
    }

    public function test_enrolled_account_cannot_resume_mfa_after_a_wrong_password_submission(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $user->two_factor_secret = (new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();
        $user->two_factor_confirmed_at = now();
        $user->save();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('security.challenge'));
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->get(route('security.challenge'))->assertRedirect(route('login'));
    }

    public function test_returning_to_login_clears_an_incomplete_mfa_sign_in(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('security.setup'));
        $this->get('/login')->assertOk();
        $this->assertGuest();
        $this->get(route('security.setup'))->assertRedirect(route('login'));
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
