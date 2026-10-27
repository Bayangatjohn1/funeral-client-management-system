<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword12345',
                'password_confirmation' => 'NewPassword12345',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_unknown_email_and_known_email_receive_identical_response(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $known = $this->post('/forgot-password', ['email' => $user->email]);
        $status = session('status');
        $known->assertSessionHasNoErrors();
        $this->post('/forgot-password', ['email' => 'unknown@example.com'])
            ->assertSessionHasNoErrors()->assertSessionHas('status', $status);
    }

    public function test_log_mailer_never_receives_reset_secrets(): void
    {
        Notification::fake();
        config(['mail.default' => 'log']);
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_recovery_revokes_sessions_preserves_mfa_and_consumes_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->two_factor_secret = 'ABCDEFGHIJKLMNOP';
        $user->two_factor_confirmed_at = now();
        $user->must_change_password = true;
        $user->save();
        $version = (int) $user->security_version;
        $token = \Illuminate\Support\Facades\Password::createToken($user);
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword12345', 'password_confirmation' => 'NewPassword12345'];
        $this->post('/reset-password', $data)->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($data['password'], $user->password));
        $this->assertEquals($version + 1, $user->security_version);
        $this->assertEquals('ABCDEFGHIJKLMNOP', $user->two_factor_secret);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->must_change_password);
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.password_recovered', 'entity_id' => $user->id]);
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
    }

    public function test_expired_token_and_weak_password_are_rejected(): void
    {
        $user = User::factory()->create();
        $token = \Illuminate\Support\Facades\Password::createToken($user);
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'short', 'password_confirmation' => 'short'];
        $this->post('/reset-password', $data)->assertSessionHasErrors('password');
        $this->assertNull(session('_old_input.token'));
        $this->travel(61)->minutes();
        $data['password'] = $data['password_confirmation'] = 'NewPassword12345';
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user.password_recovered']);
    }
}
