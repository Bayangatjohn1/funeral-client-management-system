<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AccountSecurity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdleSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Session deadlines are exact-second assertions; database/request latency must not move the clock.
        $this->freezeTime();
    }

    private function signedIn(string $role): User
    {
        $user = User::factory()->create(['role' => $role === 'system' || $role === 'branch' ? 'admin' : $role,
            'admin_scope' => $role === 'system' ? 'system' : ($role === 'branch' ? 'branch' : null), 'is_active' => true]);
        $this->actingAs($user);
        return $user;
    }

    public function test_admin_and_owner_sessions_expire_at_fifteen_minutes(): void
    {
        foreach (['system', 'branch', 'owner'] as $role) {
            $this->signedIn($role);
            $this->withSession(['idle_last_activity' => now()->subMinutes(15)->timestamp])->get('/dashboard')->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }

    public function test_staff_has_thirty_minutes_and_polling_does_not_extend_it(): void
    {
        $this->signedIn('staff');
        $last = now()->subMinutes(20)->timestamp;
        $this->withSession(['idle_last_activity' => $last])->getJson(route('session.status'))->assertOk()->assertJsonPath('expires_at', $last + 1800);
        $this->assertSame($last, session('idle_last_activity'));
        $this->travel(10)->minutes();
        $this->getJson(route('session.status'))->assertUnauthorized()->assertJsonPath('expired', true);
        $this->assertGuest();
    }

    public function test_keep_alive_extends_only_a_valid_matching_session(): void
    {
        $this->signedIn('owner');
        $this->withSession(['idle_last_activity' => now()->subMinutes(14)->timestamp])->withHeader('X-Idle-Session', 'wrong')->postJson(route('session.keep_alive'))->assertStatus(409);
        $this->withHeader('X-Idle-Session', 'test-session')->postJson(route('session.keep_alive'))->assertOk()->assertJsonPath('expires_at', now()->timestamp + 900);
        $this->travel(15)->minutes();
        $this->postJson(route('session.keep_alive'))->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_expired_mutation_is_denied_and_remembered_sessions_cannot_bypass_timeout(): void
    {
        $this->signedIn('staff');
        $this->withSession(['idle_last_activity' => now()->subMinutes(30)->timestamp])->patchJson('/profile', ['name' => 'Changed'])->assertUnauthorized();
        $this->assertGuest();
        $this->signedIn('staff');
        $this->withSession(['idle_last_activity' => null])->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_foreground_navigation_refreshes_activity(): void
    {
        $this->signedIn('staff');
        $this->withSession(['idle_last_activity' => now()->subMinutes(29)->timestamp])->get('/dashboard')->assertRedirect('/staff');
        $this->assertSame(now()->timestamp, session('idle_last_activity'));
    }
}
