<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\FreebieCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageFormPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_freebies_have_named_editable_controls_and_service_fields_have_labels(): void
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'branch_type' => 'main', 'address' => 'Test Address', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'main', 'branch_id' => $branch->id, 'is_active' => true, 'can_encode_any_branch' => true]);
        foreach (['Memorial Guest Registry Book', 'Sympathy Cards', 'Wake Vigil Candles', 'Portrait Stand'] as $name) {
            FreebieCatalog::create(['name' => $name, 'default_unit' => 'pcs', 'is_active' => true]);
        }
        $response = $this->actingAs($admin)->get(route('admin.packages.create', absolute: false))->assertOk();
        $response
            ->assertSee('All freebies are complimentary.')
            ->assertDontSee('Complimentary &#8369;0.00', false)
            ->assertSee('Assigned Promotion');
        $html = $response->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//form[@id="packageForm"]//label[@for]') as $label) {
            $id = $label->getAttribute('for');
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'"]')->length, 'Label must identify exactly one field: '.$id);
        }
        $this->assertSame(4, $xpath->query('//input[@data-freebie-check]')->length);
        if ($preview = getenv('PACKAGE_UI_PREVIEW_PATH')) {
            // Isolated test data only. Capture blocks submission to the real application.
            $guard = '<script>document.addEventListener("submit",function(e){e.preventDefault();e.stopImmediatePropagation();},true);</script>';
            file_put_contents($preview, str_replace('</head>', $guard.'</head>', $html));
        }
    }
}
