<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use App\Support\CatalogPhotos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogPhotoController extends Controller
{
    public function show(Request $request, string $type, int $id)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        if ($type === 'branches') abort_unless($request->user()->isMainAdmin(), 403);

        $catalog = $this->findRecord($type, $id);
        if ($type === 'addons' && $catalog->category === \App\Models\AddOnCatalog::CHAPEL_VIEWING && $request->user()->isBranchAdmin()) {
            abort_unless((int) $catalog->branch_id === (int) $request->user()->branch_id, 403);
        }
        abort_unless($catalog->photo_path && in_array($catalog->photo_mime, ['image/jpeg', 'image/png'], true)
            && Storage::disk('local')->exists($catalog->photo_path), 404);
        return Storage::disk('local')->response($catalog->photo_path, 'catalog-photo.'.($catalog->photo_mime === 'image/png' ? 'png' : 'jpg'),
            ['Content-Type' => $catalog->photo_mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function edit(Request $request, string $type, int $id)
    {
        abort_unless($request->user()?->isMainAdmin(), 403);

        $catalog = $this->findRecord($type, $id);
        $view = $type === 'branches' && $request->ajax()
            ? 'admin.service-management.partials.quick-photo-form'
            : 'admin.service-management.photo-edit';

        return view($view, [
            'catalog' => $catalog,
            'type' => $type,
            'photoName' => $type === 'branches' ? $catalog->branch_name : $catalog->name,
            'returnTo' => $this->indexUrl($type),
        ]);
    }

    public function update(Request $request, string $type, int $id)
    {
        abort_unless($request->user()?->isMainAdmin(), 403);
        $catalog = $this->findRecord($type, $id);

        if (! $request->hasFile('catalog_photo') && ! $request->boolean('catalog_photo_remove')) {
            throw ValidationException::withMessages(['catalog_photo' => 'Attach a photo before saving.']);
        }

        CatalogPhotos::save($request, $catalog, []);
        $message = $catalog->photo_path ? 'Photo saved.' : 'Photo removed.';
        $auditAction = $type === 'branches' ? 'branch.photo_updated' : 'service_catalog.photo_updated';
        AuditLogger::log($auditAction, 'update', Str::singular($catalog->getTable()), $id, [
            'photo_attached' => (bool) $catalog->photo_path,
        ]);

        if ($request->boolean('_service_modal')) {
            return response()->view('admin.service-management.partials.saved-frame', ['message' => $message]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->to($this->indexUrl($type))->with('success', $message);
    }

    private function indexUrl(string $type): string
    {
        return $type === 'branches'
            ? route('admin.branches.index')
            : route('admin.service-management.index', ['tab' => $type]);
    }

    private function findRecord(string $type, int $id): Model
    {
        $model = CatalogPhotos::TYPES[$type] ?? abort(404);

        return $model::findOrFail($id);
    }
}
