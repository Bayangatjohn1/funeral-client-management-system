<?php

namespace Tests\Feature;

use App\Models\{AddOnCatalog, Branch, CasketCatalog, FreebieCatalog, HearseCatalog, PaymentProofUpload, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Auth, Storage, URL};
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogPhotoTest extends TestCase
{
    use RefreshDatabase;

    private function photo(string $name = 'item.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg=='));
    }

    private function actor(string $role = 'admin', ?string $scope = 'system'): User
    {
        $branch = Branch::firstOrCreate(['branch_code' => 'BR001'], ['branch_name' => 'Main Branch', 'is_active' => true]);
        return User::factory()->create(['role' => $role, 'admin_scope' => $scope, 'branch_id' => $branch->id, 'is_active' => true]);
    }

    public function test_all_catalogs_accept_optional_photos_preserve_them_on_failed_edits_and_replace_or_remove_on_save(): void
    {
        Storage::fake('local');
        $this->actingAs($this->actor());
        foreach ([
            ['casket-catalogs', CasketCatalog::class, ['name' => 'Photo Casket', 'standard_price' => 20000]],
            ['hearse-catalogs', HearseCatalog::class, ['name' => 'Photo Hearse']],
            ['add-on-catalogs', AddOnCatalog::class, ['name' => 'Photo Item', 'category' => 'General', 'unit' => 'item', 'price' => 100]],
            ['freebie-catalogs', FreebieCatalog::class, ['name' => 'Photo Freebie', 'default_unit' => 'item']],
        ] as [$route, $model, $data]) {
            $this->post(route('admin.'.$route.'.store'), $data + ['catalog_photo' => $this->photo()])->assertSessionHasNoErrors();
            $record = $model::where('name', $data['name'])->firstOrFail();
            $original = $record->photo_path;
            Storage::disk('local')->assertExists($original);
            $this->put(route('admin.'.$route.'.update', $record), array_replace($data, ['name' => '', 'catalog_photo_remove' => '1']))->assertSessionHasErrors('name');
            $this->assertSame($original, $record->fresh()->photo_path);
            Storage::disk('local')->assertExists($original);
            $this->put(route('admin.'.$route.'.update', $record), $data + ['catalog_photo' => UploadedFile::fake()->createWithContent('fake.png', '<script>invalid</script>')])->assertSessionHasErrors('catalog_photo');
            $this->assertSame($original, $record->fresh()->photo_path);
            $this->put(route('admin.'.$route.'.update', $record), $data + ['catalog_photo' => $this->photo('replacement.png')])->assertSessionHasNoErrors();
            $this->assertNotSame($original, $record->fresh()->photo_path);
            $replacement = $record->fresh()->photo_path;
            Storage::disk('local')->assertExists($replacement);
            $this->put(route('admin.'.$route.'.update', $record), $data)->assertSessionHasNoErrors();
            $this->assertSame($replacement, $record->fresh()->photo_path);
            $this->put(route('admin.'.$route.'.update', $record), $data + ['catalog_photo_remove' => '1'])->assertSessionHasNoErrors();
            $this->assertNull($record->fresh()->photo_path);
        }
    }

    public function test_photo_access_preserves_admin_permissions_and_chapel_branch_scope(): void
    {
        Storage::fake('local');
        $system = $this->actor();
        $branch = $this->actor('admin', 'branch');
        $staff = $this->actor('staff', null);
        $catalog = CasketCatalog::create(['name' => 'Photo Access', 'standard_price' => 10]);
        Storage::disk('local')->put('catalog-photos/test.png', 'image');
        $catalog->forceFill(['photo_path' => 'catalog-photos/test.png', 'photo_mime' => 'image/png'])->save();
        $url = route('admin.catalog-photo', ['type' => 'caskets', 'id' => $catalog->id]);
        foreach ([$system, $branch] as $admin) $this->actingAs($admin)->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($staff)->get($url)->assertForbidden();
        $other = Branch::create(['branch_code' => 'PHOTO-OTHER', 'branch_name' => 'Other Branch', 'is_active' => true]);
        $chapel = AddOnCatalog::create(['name' => 'Other Branch Chapel', 'category' => AddOnCatalog::CHAPEL_VIEWING, 'unit' => 'day', 'price' => 1000, 'branch_id' => $other->id]);
        $chapel->forceFill(['photo_path' => 'catalog-photos/test.png', 'photo_mime' => 'image/png'])->save();
        $chapelUrl = route('admin.catalog-photo', ['type' => 'addons', 'id' => $chapel->id]);
        $this->actingAs($system)->get($chapelUrl)->assertOk();
        $this->actingAs($branch)->get($chapelUrl)->assertForbidden();
        $this->actingAs($branch)->postJson(route('payment-proof.store'), ['form_id' => (string) Str::uuid(), 'context' => 'catalog'])->assertForbidden();
        $this->actingAs($staff)->postJson(route('payment-proof.store'), ['form_id' => (string) Str::uuid(), 'context' => 'catalog'])->assertForbidden();
        $this->actingAs($system)->get(route('admin.catalog-photo', ['type' => 'packages', 'id' => $catalog->id]))->assertNotFound();
    }

    public function test_catalog_phone_upload_is_signed_image_only_single_use_and_transfers_to_the_saved_item(): void
    {
        Storage::fake('local');
        $admin = $this->actor();
        $created = $this->actingAs($admin)->postJson(route('payment-proof.store'), ['form_id' => (string) Str::uuid(), 'context' => 'catalog'])->assertOk();
        $upload = PaymentProofUpload::findOrFail($created->json('id'));
        $upload->update(['token_hash' => hash('sha256', 'catalog-test')]);
        $url = URL::temporarySignedRoute('payment-proof.phone', now()->addMinutes(5), ['upload' => $upload->id, 'token' => 'catalog-test']);
        Auth::logout();
        $this->get($url)->assertOk()->assertSee('Upload Catalog Photo');
        $this->postJson($url, ['proof' => UploadedFile::fake()->createWithContent('not-a-photo.pdf', '%PDF-1.4 test')])->assertUnprocessable();
        $this->postJson($url, ['proof' => $this->photo('phone.png')])->assertOk();
        $this->postJson($url, ['proof' => $this->photo('again.png')])->assertGone();
        $this->assertStringStartsWith('catalog-photo-uploads/', $upload->fresh()->path);
        $this->actingAs($admin)->getJson(route('payment-proof.status', $upload))->assertOk()->assertJsonPath('state', 'received');
        $bytes = Storage::disk('local')->get($upload->fresh()->path);
        $this->post(route('admin.hearse-catalogs.store'), ['name' => 'Phone Coach', 'catalog_photo' => UploadedFile::fake()->createWithContent('phone.png', $bytes)])->assertSessionHasNoErrors();
        $catalog = HearseCatalog::where('name', 'Phone Coach')->firstOrFail();
        $this->assertStringStartsWith('catalog-photos/', $catalog->photo_path);
        $this->deleteJson(route('payment-proof.cancel', $upload))->assertOk();
        Storage::disk('local')->assertExists($catalog->photo_path);
    }
}
