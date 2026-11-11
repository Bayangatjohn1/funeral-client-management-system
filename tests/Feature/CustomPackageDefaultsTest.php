<?php

namespace Tests\Feature;

use App\Models\{Branch, CustomPackageDefault, Package, User};
use App\Support\CustomPackageSelection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomPackageDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_system_admin_can_save_custom_defaults(): void
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main', 'is_active' => true]);
        $coverage = ['embalming_days' => 3, 'home_viewing_days' => 4, 'retrieval_kilometers' => 25.5];
        $payload = $coverage + ['embalming_extra_rate' => 500, 'home_viewing_extra_rate' => 0, 'retrieval_extra_rate' => 50];
        foreach ([['admin', 'branch'], ['staff', null], ['owner', null]] as [$role, $scope]) {
            $user = User::factory()->create(['role' => $role, 'admin_scope' => $scope, 'branch_id' => $branch->id, 'is_active' => true]);
            $this->actingAs($user)->put(route('admin.custom-package-defaults.update'), $payload)->assertForbidden();
        }
        $this->assertDatabaseCount('custom_package_defaults', 0);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->actingAs($admin)->put(route('admin.custom-package-defaults.update'), $coverage)
            ->assertSessionHasErrors(['embalming_extra_rate', 'home_viewing_extra_rate', 'retrieval_extra_rate']);
        $this->put(route('admin.custom-package-defaults.update'), $payload + ['hearse_kilometers' => 0])
            ->assertSessionHasErrors('hearse_extra_rate');
        $this->actingAs($admin)->put(route('admin.custom-package-defaults.update'), $payload)->assertRedirect(route('admin.service-management.index', ['tab' => 'packages']));
        $this->assertDatabaseHas('custom_package_defaults', ['id' => 1] + $payload);
        $this->get(route('admin.service-management.index'))->assertOk()->assertSee('Basic Funeral Inclusions')->assertSee('Save defaults');
        $this->put(route('admin.custom-package-defaults.update'), ['embalming_days' => 0, 'home_viewing_days' => 1.5, 'retrieval_kilometers' => -1])->assertSessionHasErrors(array_keys($coverage));
        $this->assertDatabaseHas('custom_package_defaults', ['id' => 1] + $payload);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('intake.main.create'))->assertOk()->assertSee('3 day(s) included')->assertSee('4 day(s) included')->assertSee('25.50 km included')->assertSee('Choose a casket');
    }

    public function test_current_admin_coverage_overrides_posted_values_and_prior_snapshots_remain_stable(): void
    {
        $defaults = new CustomPackageDefault(['embalming_days' => 3, 'home_viewing_days' => 4, 'retrieval_kilometers' => 25.5]);
        $defaults->id = 1;
        $defaults->save();
        $input = ['custom_service_types' => [Package::SERVICE_EMBALMING, Package::SERVICE_HOME_VIEWING, Package::SERVICE_BODY_RETRIEVAL], 'custom_service_coverage' => ['embalming' => 999, 'home_viewing' => 999, 'body_retrieval' => 999]];
        $selection = app(CustomPackageSelection::class)->resolve($input);
        $this->assertSame([3, 4], array_column($selection['inclusions'], 'included_days'));
        $this->assertSame(25.5, $selection['inclusions'][2]['included_kilometers']);
        $defaults->update(['embalming_days' => 6]);
        $this->assertSame(6, app(CustomPackageSelection::class)->resolve($input)['inclusions'][0]['included_days']);
        $this->assertSame(3, $selection['inclusions'][0]['included_days']);
    }

    public function test_missing_admin_days_keep_existing_validation_message(): void
    {
        try {
            app(CustomPackageSelection::class)->resolve(['custom_service_types' => [Package::SERVICE_EMBALMING], 'custom_service_coverage' => ['embalming' => 999]]);
            $this->fail('Posted coverage must not replace missing admin defaults.');
        } catch (ValidationException $e) {
            $this->assertSame(['Enter the number of included days.'], $e->errors()['custom_service_coverage.embalming']);
        }
    }

    public function test_custom_coverage_overage_uses_admin_rates_and_case_quantities(): void
    {
        $defaults = new CustomPackageDefault([
            'embalming_days' => 3, 'home_viewing_days' => 4, 'retrieval_kilometers' => 25.5, 'hearse_kilometers' => 20,
            'embalming_extra_rate' => 500, 'retrieval_extra_rate' => 50, 'home_viewing_extra_rate' => 0,
        ]);
        $defaults->id = 1;
        $defaults->save();
        $selection = app(CustomPackageSelection::class)->resolve([
            'custom_service_types' => array_keys(CustomPackageDefault::SERVICE_FIELDS),
            'custom_included' => ['embalming' => 4],
            'custom_requested' => ['embalming' => 6, 'home_viewing' => 6, 'body_retrieval' => 32, 'hearse' => 24],
        ]);
        $this->assertSame([1000.0, 325.0], array_column($selection['charges'], 'amount'));
        $this->assertSame([4, 4], array_column($selection['inclusions'], 'included_days'));
        $result = app(\App\Support\IntakePricingService::class)->price(null, [
            'custom_package_price' => 50000,
            'custom_package_configuration' => $selection,
        ], true);
        $this->assertSame(51325.0, $result['total']);
        $this->assertSame(1325.0, $result['service_charges_total']);
    }
}
