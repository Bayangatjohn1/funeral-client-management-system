<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FreebieCatalog;
use App\Support\AuditLogger;
use App\Support\ServiceCatalogPolicy;
use Illuminate\Http\Request;

class FreebieCatalogController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCanView();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive,archived,all'],
            'sort' => ['nullable', 'in:name_asc,latest,usage_desc'],
        ]);

        $query = FreebieCatalog::query()
            ->withCount('packageFreebies');

        if ($search = trim((string) ($validated['q'] ?? ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('default_unit', 'like', "%{$search}%");
            });
        }

        if (($validated['status'] ?? 'active') === 'active') {
            $query->where('is_active', true);
        } elseif (in_array($validated['status'] ?? null, ['inactive', 'archived'], true)) {
            $query->where('is_active', false);
        }

        match ($validated['sort'] ?? 'name_asc') {
            'latest' => $query->latest(),
            'usage_desc' => $query->orderByDesc('package_freebies_count')->orderBy('name'),
            default => $query->orderBy('name'),
        };

        $catalogs = $query
            ->paginate(20)
            ->withQueryString();

        return view('admin.freebie-catalogs.index', [
            'catalogs' => $catalogs,
        ]);
    }

    public function create()
    {
        $this->ensureCanManage();

        return view('admin.freebie-catalogs.create', [
            'catalog' => new FreebieCatalog([
                'default_unit' => 'item',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage();

        $validated = $request->validate($this->rules(), $this->messages());
        $validated = $this->normalize($validated);
        $validated['is_active'] = true;

        if ($this->duplicateExists($validated['name'])) {
            return back()->withErrors([
                'name' => 'A freebie with this name already exists.',
            ])->withInput();
        }

        $catalog = \App\Support\CatalogPhotos::save($request, new FreebieCatalog(), $validated);

        AuditLogger::log(
            'freebie_catalog.created',
            'create',
            'freebie_catalog',
            $catalog->id,
            ['name' => $catalog->name, 'default_unit' => $catalog->default_unit],
            null,
            null,
            'success',
            null,
            'Freebie catalog item created'
        );

        return \App\Support\CatalogFormResponse::saved($request, 'Freebie saved successfully.', route('admin.freebie-catalogs.index'));
    }

    public function show(Request $request, FreebieCatalog $freebie_catalog)
    {
        $this->ensureCanView();

        $freebie_catalog->loadCount('packageFreebies');
        $freebie_catalog->load('packageFreebies.package');
        $affectedPackages = app(ServiceCatalogPolicy::class)->activePackagesUsingFreebie($freebie_catalog);

        return view('admin.freebie-catalogs.show', [
            'catalog' => $freebie_catalog,
            'canManage' => $request->user()->isMainAdmin(),
            'affectedPackages' => $affectedPackages,
        ]);
    }

    public function edit(FreebieCatalog $freebie_catalog)
    {
        $this->ensureCanManage();

        return view('admin.freebie-catalogs.edit', [
            'catalog' => $freebie_catalog,
        ]);
    }

    public function update(Request $request, FreebieCatalog $freebie_catalog)
    {
        $this->ensureCanManage();

        $validated = $request->validate($this->rules(), $this->messages());
        $validated = $this->normalize($validated);

        if ($this->duplicateExists($validated['name'], $freebie_catalog->id)) {
            return back()->withErrors([
                'name' => 'A freebie with this name already exists.',
            ])->withInput();
        }

        $before = [
            'name' => $freebie_catalog->name,
            'default_unit' => $freebie_catalog->default_unit,
        ];

        \App\Support\CatalogPhotos::save($request, $freebie_catalog, $validated);

        AuditLogger::log(
            'freebie_catalog.updated',
            'update',
            'freebie_catalog',
            $freebie_catalog->id,
            [
                'before' => $before,
                'after' => [
                    'name' => $freebie_catalog->name,
                    'default_unit' => $freebie_catalog->default_unit,
                ],
            ],
            null,
            null,
            'success',
            null,
            'Freebie catalog item updated'
        );

        return \App\Support\CatalogFormResponse::saved($request, 'Freebie updated successfully.', route('admin.freebie-catalogs.index'));
    }

    public function toggleAvailability(FreebieCatalog $freebie_catalog)
    {
        $this->ensureCanManage();
        if (! $freebie_catalog->is_active) return back()->withErrors(['catalog' => 'Restore the archived item before changing its availability.']);

        $freebie_catalog->update(['is_available' => ! $freebie_catalog->is_available]);
        AuditLogger::log('freebie_catalog.availability_changed', 'status_change', 'freebie_catalog', $freebie_catalog->id, ['is_available' => $freebie_catalog->is_available]);
        return back()->with('success', $freebie_catalog->name.' marked '.($freebie_catalog->is_available ? 'available.' : 'unavailable.').' Package status is unchanged; intake eligibility is recalculated.');
    }

    public function toggleActive(FreebieCatalog $freebie_catalog)
    {
        $this->ensureCanManage();
        if ($message = app(\App\Support\ServiceCatalogPolicy::class)->archiveBlockMessage($freebie_catalog)) {
            return back()->withErrors(['catalog' => $message]);
        }


        $freebie_catalog->update([
            'is_active' => ! $freebie_catalog->is_active,
        ]);

        AuditLogger::log(
            $freebie_catalog->is_active ? 'freebie_catalog.restored' : 'freebie_catalog.archived',
            'status_change',
            'freebie_catalog',
            $freebie_catalog->id,
            ['name' => $freebie_catalog->name, 'is_active' => $freebie_catalog->is_active],
            null,
            null,
            'success',
            null,
            $freebie_catalog->is_active ? 'Freebie catalog item restored' : 'Freebie catalog item archived'
        );

        return back()->with('success', $freebie_catalog->name.' '.($freebie_catalog->is_active ? 'restored.' : 'archived.'));
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', $this->mustContainLetterRule('Freebie name must include letters.'), $this->allowedNameTextRule('Freebie name has unnecessary special characters.')],
            'default_unit' => ['required', 'string', 'max:40', $this->mustContainLetterRule('Default unit must include letters.'), $this->allowedSimpleTextRule('Default unit must use letters, spaces, or hyphen only.')],
            'return_to' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Freebie name is required.',
            'default_unit.required' => 'Default unit is required.',
        ];
    }

    private function normalize(array $validated): array
    {
        foreach (['name', 'default_unit'] as $field) {
            $validated[$field] = trim(preg_replace('/\s+/', ' ', (string) ($validated[$field] ?? '')));
        }

        $validated['default_unit'] = $validated['default_unit'] ?: 'item';

        unset($validated['return_to']);

        return $validated;
    }

    private function duplicateExists(string $name, ?int $ignoreId = null): bool
    {
        return FreebieCatalog::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
    }

    private function mustContainLetterRule(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if ($value === null || trim((string) $value) === '') {
                return;
            }

            if (! preg_match('/[\pL\pM]/u', (string) $value) || preg_match('/^\d+(?:\.\d+)?$/', trim((string) $value))) {
                $fail($message);
            }
        };
    }

    private function allowedNameTextRule(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if ($value === null || trim((string) $value) === '') {
                return;
            }

            if (! preg_match("~^[\pL\pM][\pL\pM\s,'()/&-]*$~u", trim((string) $value))) {
                $fail($message);
            }
        };
    }

    private function allowedSimpleTextRule(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if ($value === null || trim((string) $value) === '') {
                return;
            }

            if (! preg_match('/^[\pL\pM][\pL\pM\s-]*$/u', trim((string) $value))) {
                $fail($message);
            }
        };
    }

    private function ensureCanView(): void
    {
        $user = auth()->user();

        if (! $user || (! $user->isMainAdmin() && ! $user->isBranchAdmin())) {
            abort(403, 'Unauthorized');
        }
    }

    private function ensureCanManage(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->isMainAdmin()) {
            abort(403, 'Branch admins have read-only access to freebie catalog.');
        }
    }
}
