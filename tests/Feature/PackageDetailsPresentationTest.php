<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Package;
use App\Models\PackageFreebie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageDetailsPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_details_keep_legacy_freebies_and_show_backend_promotion_states(): void
    {
        $this->travelTo('2026-10-28 10:00:00');
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'branch_type' => 'main', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'main', 'branch_id' => $branch->id, 'is_active' => true]);

        $scheduled = Package::create([
            'name' => 'Scheduled Package', 'price' => 50000, 'is_active' => true,
            'promo_is_active' => true, 'promo_label' => 'Future Care', 'promo_value_type' => 'AMOUNT',
            'promo_value' => 1000, 'promo_starts_at' => now()->addDay(), 'promo_ends_at' => now()->addWeek(),
        ]);
        PackageFreebie::create(['package_id' => $scheduled->id, 'freebie_name' => 'Legacy Memorial Card', 'quantity' => 2, 'unit' => 'pieces']);

        $this->actingAs($admin)->get(route('admin.packages.show', $scheduled, absolute: false))
            ->assertOk()
            ->assertSee('Scheduled')
            ->assertSee('Start Date: Oct 29, 2026')
            ->assertSee('End Date: Nov 04, 2026')
            ->assertSee('Future Care')
            ->assertSee('Legacy Memorial Card')
            ->assertSee('Legacy package freebie')
            ->assertSee('The included casket reference value is not added separately.');

        $expired = Package::create([
            'name' => 'Expired Package', 'price' => 50000, 'is_active' => true,
            'promo_is_active' => true, 'promo_label' => 'Past Care', 'promo_value_type' => 'AMOUNT',
            'promo_value' => 1000, 'promo_starts_at' => now()->subWeek(), 'promo_ends_at' => now()->subDay(),
        ]);
        $this->actingAs($admin)->get(route('admin.packages.show', $expired, absolute: false))
            ->assertOk()->assertSee('Expired')->assertSee('End Date: Oct 27, 2026')->assertSee('Past Care');

        $disabled = Package::create([
            'name' => 'Disabled Package', 'price' => 50000, 'is_active' => true,
            'promo_is_active' => false, 'promo_label' => 'Paused Care', 'promo_value_type' => 'AMOUNT', 'promo_value' => 1000,
        ]);
        $this->actingAs($admin)->get(route('admin.packages.show', $disabled, absolute: false))
            ->assertOk()->assertSee('Incomplete Setup')->assertSee('Paused Care')->assertSee('Promotion Status');
    }
}
