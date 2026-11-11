<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HearseCatalog;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class HearseCatalogController extends Controller
{
    public function create()
    {
        $this->ensureCanManage();
        return view('admin.hearse-catalogs.create', ['catalog' => new HearseCatalog(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage();
        $data = $this->validated($request);
        $data['is_active'] = true;
        $catalog = \App\Support\CatalogPhotos::save($request, new HearseCatalog(), $data);
        AuditLogger::log('hearse_catalog.created', 'create', 'hearse_catalog', $catalog->id, $data, null, null, 'success', null, 'Hearse catalog item created');
        return \App\Support\CatalogFormResponse::saved($request, 'Hearse saved successfully.', route('admin.service-management.index', ['tab' => 'hearses']));
    }

    public function show(Request $request, HearseCatalog $hearse_catalog)
    {
        $this->ensureCanView();
        $hearse_catalog->load(['funeralCases.package', 'packageInclusions.package']);
        return view('admin.hearse-catalogs.show', ['catalog' => $hearse_catalog, 'canManage' => $request->user()->isMainAdmin()]);
    }

    public function edit(HearseCatalog $hearse_catalog)
    {
        $this->ensureCanManage();
        return view('admin.hearse-catalogs.edit', ['catalog' => $hearse_catalog]);
    }

    public function update(Request $request, HearseCatalog $hearse_catalog)
    {
        $this->ensureCanManage();
        $before = $hearse_catalog->only(['name', 'description', 'reference_value']);
        $data = $this->validated($request, $hearse_catalog->id);
        \App\Support\CatalogPhotos::save($request, $hearse_catalog, $data);
        AuditLogger::log('hearse_catalog.updated', 'update', 'hearse_catalog', $hearse_catalog->id, ['before' => $before, 'after' => $data], null, null, 'success', null, 'Hearse catalog item updated');
        return \App\Support\CatalogFormResponse::saved($request, 'Hearse updated successfully.', route('admin.service-management.index', ['tab' => 'hearses']));
    }

    public function toggleAvailability(HearseCatalog $hearse_catalog)
    {
        $this->ensureCanManage();
        if (! $hearse_catalog->is_active) return back()->withErrors(['catalog' => 'Restore the archived item before changing its availability.']);

        $hearse_catalog->update(['is_available' => ! $hearse_catalog->is_available]);
        AuditLogger::log('hearse_catalog.availability_changed', 'status_change', 'hearse_catalog', $hearse_catalog->id, ['is_available' => $hearse_catalog->is_available]);
        return back()->with('success', $hearse_catalog->name.' marked '.($hearse_catalog->is_available ? 'available.' : 'unavailable.').' Package status is unchanged; intake eligibility is recalculated.');
    }

    public function toggleActive(HearseCatalog $hearse_catalog)
    {
        $this->ensureCanManage();
        if ($message = app(\App\Support\ServiceCatalogPolicy::class)->archiveBlockMessage($hearse_catalog)) {
            return back()->withErrors(['catalog' => $message]);
        }

        $hearse_catalog->update(['is_active' => ! $hearse_catalog->is_active]);
        AuditLogger::log($hearse_catalog->is_active ? 'hearse_catalog.restored' : 'hearse_catalog.archived', 'status_change', 'hearse_catalog', $hearse_catalog->id, ['name' => $hearse_catalog->name, 'is_active' => $hearse_catalog->is_active], null, null, 'success', null, 'Hearse catalog status changed');
        return back()->with('success', $hearse_catalog->name.' '.($hearse_catalog->is_active ? 'restored.' : 'archived.'));
    }

    private function validated(Request $request, ?int $ignore = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'regex:/[\pL\pM]/u', 'unique:hearse_catalogs,name'.($ignore ? ','.$ignore : '')],
            'reference_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'description' => ['nullable', 'string', 'max:1000'],
            'return_to' => ['nullable', 'string', 'max:1000'],
        ], ['name.regex' => 'Hearse name must include letters.']);
        $data['name'] = trim(preg_replace('/\s+/', ' ', $data['name']));
        $data['description'] = filled($data['description'] ?? null) ? trim($data['description']) : null;
        unset($data['return_to']);
        return $data;
    }

    private function ensureCanView(): void
    {
        abort_unless(auth()->user()?->isMainAdmin() || auth()->user()?->isBranchAdmin(), 403);
    }

    private function ensureCanManage(): void
    {
        abort_unless(auth()->user()?->isMainAdmin(), 403, 'Branch admins have read-only access to hearse catalog.');
    }
}
