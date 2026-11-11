<?php

namespace App\Support;

use App\Models\{AddOnCatalog, Branch, CasketCatalog, FreebieCatalog, HearseCatalog, Package};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogPhotos
{
    public const TYPES = ['branches' => Branch::class, 'packages' => Package::class, 'caskets' => CasketCatalog::class, 'hearses' => HearseCatalog::class,
        'addons' => AddOnCatalog::class, 'freebies' => FreebieCatalog::class];

    public static function url(Model $catalog): ?string
    {
        $type = array_search($catalog::class, self::TYPES, true);
        return $type && $catalog->photo_path ? route('admin.catalog-photo', ['type' => $type, 'id' => $catalog->getKey()]) : null;
    }

    public static function save(Request $request, Model $catalog, array $attributes): Model
    {
        return self::persist($request, $catalog, fn (Model $record) => $record->fill($attributes)->save());
    }

    /**
     * Persist the record and any related changes together with its photo.
     * The callback receives the locked record and is responsible for saving it.
     */
    public static function persist(Request $request, Model $catalog, \Closure $saveRecord): Model
    {
        abort_unless($request->user()?->isMainAdmin(), 403);
        // Ignore all client-supplied file paths. Only a real, decoded JPG/PNG is accepted.
        $request->validate([
            'catalog_photo' => ['nullable', 'file', 'image', 'mimetypes:image/jpeg,image/png', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
            'catalog_photo_remove' => ['nullable', 'boolean'],
        ]);
        $file = $request->file('catalog_photo');
        $path = null;
        $oldPath = null;
        try {
            if ($file) {
                $path = $file->store('catalog-photos', 'local');
                if (!$path) throw ValidationException::withMessages(['catalog_photo' => 'The photo could not be stored. Please try again.']);
            }
            $saved = DB::transaction(function () use ($request, $catalog, $saveRecord, $file, $path, &$oldPath) {
                $record = $catalog->exists ? $catalog->newQuery()->lockForUpdate()->findOrFail($catalog->getKey()) : $catalog;
                if ($file || $request->boolean('catalog_photo_remove')) {
                    $oldPath = $record->photo_path;
                    $record->forceFill(['photo_path' => $path,
                        'photo_mime' => $file ? (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) : null,
                        'photo_name' => $file ? Str::limit(basename($file->getClientOriginalName()), 180, '') : null]);
                }
                $saveRecord($record);
                return $record;
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        // Delete the replaced file only after the database change is committed.
        if ($oldPath && $oldPath !== $path) DB::afterCommit(fn () => Storage::disk('local')->delete($oldPath));
        $catalog->setRawAttributes($saved->getAttributes(), true);
        return $saved;
    }
}
