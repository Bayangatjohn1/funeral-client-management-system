<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOwnerInputConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_date_filter_options_are_consistent_on_desktop_and_mobile(): void
    {
        $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createUser('admin');

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertOk();

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'value="this_week"'));
    }

    public function test_admin_filter_validation_handles_valid_and_invalid_values(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get('/admin?date_filter=this_week&branch_id=' . $branch->id)
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin?date_filter=INVALID_OPTION')
            ->assertSessionHasErrors('date_filter');
    }

    public function test_owner_dashboard_filter_validation_is_consistent(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->get('/owner?range=LAST_30_DAYS&branch_id=' . $branch->id)
            ->assertOk();

        $this->actingAs($owner)
            ->get('/owner?range=INVALID_RANGE')
            ->assertSessionHasErrors('range');
    }

    public function test_owner_analytics_filter_validation_is_consistent(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->get('/owner/branch-analytics?range=THIS_MONTH&branch_id=' . $branch->id)
            ->assertOk();

        $this->actingAs($owner)
            ->get('/owner/branch-analytics?range=LAST_30_DAYS')
            ->assertSessionHasErrors('range');
    }

    public function test_owner_reporting_filters_validate_consistently_across_pages(): void
    {
        $branch = $this->createBranch('BR001', 'Main Branch');
        $owner = $this->createUser('owner');

        $validQuery = http_build_query([
            'branch_id' => $branch->id,
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
            'transport_option' => 'HEARSE',
            'embalming_status' => 'PENDING',
            'interment_from' => now()->subDays(3)->toDateString(),
            'interment_to' => now()->addDay()->toDateString(),
            'wake_days_min' => 1,
            'wake_days_max' => 7,
            'q' => 'CASE-123',
        ]);

        $this->actingAs($owner)
            ->get('/owner/sales-per-branch?' . $validQuery)
            ->assertOk();

        $this->actingAs($owner)
            ->get('/owner/case-history?' . $validQuery)
            ->assertOk();

        $this->actingAs($owner)
            ->get('/owner/sales-per-branch?transport_option=PLANE')
            ->assertSessionHasErrors('transport_option');

        $this->actingAs($owner)
            ->get('/owner/case-history?q=DROP_TABLE!')
            ->assertSessionHasErrors('q');
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
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
}
