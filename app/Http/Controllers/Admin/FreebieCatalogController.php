<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FreebieCatalog;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class FreebieCatalogController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCanView();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
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

        if (($validated['status'] ?? null) === 'active') {
            $query->where('is_active', true);
        } elseif (($validated['status'] ?? null) === 'inactive') {
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

        $catalog = FreebieCatalog::create($validated);

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

        return redirect($request->input('return_to') ?: route('admin.freebie-catalogs.index'))
            ->with('success', 'Freebie saved successfully.');
    }

    public function show(Request $request, FreebieCatalog $freebie_catalog)
    {
        $this->ensureCanView();

        if ($request->user()->isBranchAdmin() && ! $freebie_catalog->is_active) {
            abort(404);
        }

        $freebie_catalog->loadCount('packageFreebies');

        return view('admin.freebie-catalogs.show', [
            'catalog' => $freebie_catalog,
            'canManage' => $request->user()->isMainAdmin(),
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

        $freebie_catalog->update($validated);

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

        return redirect($request->input('return_to') ?: route('admin.freebie-catalogs.index'))
            ->with('success', 'Freebie updated successfully.');
    }

    public function toggleActive(FreebieCatalog $freebie_catalog)
    {
        $this->ensureCanManage();

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

        return back()->with('success', $freebie_catalog->is_active ? 'Freebie restored.' : 'Freebie archived.');
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
