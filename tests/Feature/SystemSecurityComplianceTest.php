<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSecurityComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_routes_require_active_users(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    public function test_application_sends_baseline_security_headers(): void
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
            'can_encode_any_branch' => true,
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
