<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\HearseCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HearseCatalogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_manage_hearse_catalog_and_branch_admin_is_read_only(): void
    {
        $main = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'branch_type' => 'main', 'is_active' => true]);
        $branch = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Branch Two', 'branch_type' => 'branch', 'is_active' => true]);
        $systemAdmin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'main', 'branch_id' => $main->id, 'is_active' => true]);
        $branchAdmin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true]);

        $this->actingAs($systemAdmin)->post(route('admin.hearse-catalogs.store', absolute: false), [
            'name' => 'Memorial Coach',
            'description' => 'Primary hearse',
            'reference_value' => 5000,
        ])->assertRedirect();

        $catalog = HearseCatalog::where('name', 'Memorial Coach')->firstOrFail();
        $this->actingAs($branchAdmin)->get(route('admin.hearse-catalogs.show', $catalog, absolute: false))
            ->assertOk()
            ->assertSee('Hearse Details')
            ->assertSee('5,000.00')
            ->assertSee('Primary hearse')
            ->assertDontSee('Last Updated')
            ->assertDontSee('Edit Hearse');
        $this->actingAs($branchAdmin)->get(route('admin.hearse-catalogs.edit', $catalog, absolute: false))->assertForbidden();
        $this->actingAs($branchAdmin)->put(route('admin.hearse-catalogs.update', $catalog, absolute: false), ['name' => 'Changed'])->assertForbidden();
        $this->actingAs($branchAdmin)->patch(route('admin.hearse-catalogs.toggleActive', $catalog, absolute: false))->assertForbidden();

        $this->actingAs($systemAdmin)->patch(route('admin.hearse-catalogs.toggleActive', $catalog, absolute: false))->assertRedirect();
        $this->assertFalse($catalog->fresh()->is_active);
    }

    public function test_hearse_forms_and_service_catalog_use_the_approved_ui_content(): void
    {
        $main = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'branch_type' => 'main', 'is_active' => true]);
        $systemAdmin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'main', 'branch_id' => $main->id, 'is_active' => true]);
        $catalog = HearseCatalog::create(['name' => 'Memorial Coach', 'description' => null, 'is_active' => true]);

        $this->actingAs($systemAdmin)->get(route('admin.hearse-catalogs.create', absolute: false))
            ->assertOk()
            ->assertSee('Add a hearse for package configuration and custom case intake.')
            ->assertSee('placeholder="Enter hearse name"', false)
            ->assertSee('Regular intake upgrades charge only the positive difference.')
            ->assertSee('name="reference_value"', false);

        $this->actingAs($systemAdmin)->get(route('admin.service-management.index', ['tab' => 'hearses'], absolute: false))
            ->assertOk()
            ->assertSee('Hearses')
            ->assertSee('Ceremonial funeral coaches and reference values for package inclusions and upgrades.')
            ->assertSee('Last Updated')
            ->assertSee('Memorial Coach')
            ->assertSee('—');

        $this->actingAs($systemAdmin)->get(route('admin.hearse-catalogs.show', $catalog, absolute: false))
            ->assertOk()
            ->assertDontSee('No description provided.')
            ->assertDontSee('Usage');
    }

    public function test_reference_value_can_be_saved_and_negative_values_are_rejected(): void
    {
        $main = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => $main->id, 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.hearse-catalogs.store'), ['name' => 'Priced Coach', 'reference_value' => 8000])->assertSessionHasNoErrors();
        $catalog = HearseCatalog::where('name', 'Priced Coach')->firstOrFail();
        $this->assertSame('8000.00', $catalog->reference_value);
        $this->actingAs($admin)->put(route('admin.hearse-catalogs.update', $catalog), ['name' => 'Priced Coach', 'reference_value' => -1])->assertSessionHasErrors('reference_value');
        $this->assertSame('8000.00', $catalog->fresh()->reference_value);
        $this->actingAs($admin)->put(route('admin.hearse-catalogs.update', $catalog), ['name' => 'Priced Coach', 'reference_value' => 0])->assertSessionHasNoErrors();
        $this->assertSame('0.00', $catalog->fresh()->reference_value);
    }

    public function test_name_is_required_and_duplicate_names_are_rejected(): void
    {
        $main = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'branch_type' => 'main', 'is_active' => true]);
        $systemAdmin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'main', 'branch_id' => $main->id, 'is_active' => true]);
        HearseCatalog::create(['name' => 'Memorial Coach', 'is_active' => true]);

        $this->actingAs($systemAdmin)->post(route('admin.hearse-catalogs.store', absolute: false), ['name' => ''])
            ->assertSessionHasErrors(['name']);
        $this->actingAs($systemAdmin)->post(route('admin.hearse-catalogs.store', absolute: false), ['name' => 'Memorial Coach'])
            ->assertSessionHasErrors(['name']);
    }
}
