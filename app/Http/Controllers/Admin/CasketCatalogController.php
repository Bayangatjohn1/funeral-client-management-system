<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CasketCatalog;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class CasketCatalogController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCanView();

        $query = CasketCatalog::query();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('type_or_material', 'like', "%{$search}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $catalogs = $query->orderBy('name')->paginate(20)->withQueryString();
        $needsPrice = CasketCatalog::query()->where('standard_price', '<=', 0)->orderBy('name')->get();

        return view('admin.casket-catalogs.index', compact('catalogs', 'needsPrice'));
    }

    public function create()
    {
        $this->ensureCanManage();

        return view('admin.casket-catalogs.create', [
            'catalog' => new CasketCatalog(['is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage();

        $validated = $request->validate($this->rules(), $this->messages());
        $validated = $this->normalize($validated);
        $validated['is_active'] = true;

        $duplicate = $this->duplicateExists($validated['name'], $validated['type_or_material']);
        if ($duplicate) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'A casket with the same name and material already exists.',
                    'errors' => [
                        'name' => ['A casket with the same name and material already exists.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'name' => 'A casket with the same name and material already exists.',
            ])->withInput();
        }

        $catalog = CasketCatalog::create($validated);

        AuditLogger::log(
            'casket_catalog.created',
            'create',
            'casket_catalog',
            $catalog->id,
            ['name' => $catalog->name, 'standard_price' => $catalog->standard_price],
            null,
            null,
            'success',
            null,
            'Casket catalog item created'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Casket saved successfully.',
                'catalog' => $this->catalogPayload($catalog),
            ], 201);
        }

        return redirect($request->input('return_to') ?: route('admin.casket-catalogs.index'))
            ->with('success', 'Casket saved successfully.');
    }

    public function edit(CasketCatalog $casket_catalog)
    {
        $this->ensureCanManage();

        return view('admin.casket-catalogs.edit', ['catalog' => $casket_catalog]);
    }

    public function update(Request $request, CasketCatalog $casket_catalog)
    {
        $this->ensureCanManage();

        $validated = $request->validate($this->rules($casket_catalog), $this->messages());
        $validated = $this->normalize($validated);

        $duplicate = $this->duplicateExists($validated['name'], $validated['type_or_material'], $casket_catalog->id);
        if ($duplicate) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'A casket with the same name and material already exists.',
                    'errors' => [
                        'name' => ['A casket with the same name and material already exists.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'name' => 'A casket with the same name and material already exists.',
            ])->withInput();
        }

        $casket_catalog->update($validated);

        AuditLogger::log(
            'casket_catalog.updated',
            'update',
            'casket_catalog',
            $casket_catalog->id,
            ['name' => $casket_catalog->name, 'standard_price' => $casket_catalog->standard_price],
            null,
            null,
            'success',
            'Casket catalog item updated',
            'Casket catalog item updated'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Casket updated successfully.',
                'catalog' => $this->catalogPayload($casket_catalog->fresh()),
            ]);
        }

        return redirect($request->input('return_to') ?: route('admin.casket-catalogs.index'))
            ->with('success', 'Casket updated successfully.');
    }

    public function toggleActive(CasketCatalog $casket_catalog)
    {
        $this->ensureCanManage();

        $casket_catalog->update(['is_active' => ! $casket_catalog->is_active]);

        return back()->with('success', $casket_catalog->is_active ? 'Casket restored.' : 'Casket archived.');
    }

    private function rules(?CasketCatalog $catalog = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150', $this->mustContainLetterRule('Casket name must include letters.'), $this->allowedNameTextRule('Casket name has unnecessary special characters.')],
            'type_or_material' => ['nullable', 'string', 'max:100', $this->mustContainLetterRule('Material must include letters.'), $this->allowedNameTextRule('Material has unnecessary special characters.')],
            'standard_price' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:500'],
            'return_to' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Casket name is required.',
            'standard_price.required' => 'Reference value is required.',
            'standard_price.numeric' => 'Reference value must be a valid amount.',
            'standard_price.min' => 'Reference value must be greater than zero.',
        ];
    }

    private function normalize(array $validated): array
    {
        foreach (['name', 'type_or_material', 'description'] as $field) {
            $validated[$field] = isset($validated[$field])
                ? trim(preg_replace('/\s+/', ' ', (string) $validated[$field]))
                : null;
        }

        $validated['type_or_material'] = $validated['type_or_material'] ?: null;
        $validated['description'] = $validated['description'] ?: null;
        $validated['standard_price'] = round((float) $validated['standard_price'], 2);

        unset($validated['return_to']);

        return $validated;
    }

    private function duplicateExists(string $name, ?string $material, ?int $ignoreId = null): bool
    {
        return CasketCatalog::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where(function ($query) use ($material) {
                if ($material === null || $material === '') {
                    $query->whereNull('type_or_material')->orWhere('type_or_material', '');
                    return;
                }

                $query->whereRaw('LOWER(type_or_material) = ?', [mb_strtolower($material)]);
            })
            ->exists();
    }

    private function catalogPayload(CasketCatalog $catalog): array
    {
        return [
            'id' => $catalog->id,
            'name' => $catalog->name,
            'material' => $catalog->type_or_material,
            'price' => (float) $catalog->standard_price,
            'description' => $catalog->description,
            'display' => $catalog->display_name,
            'is_active' => (bool) $catalog->is_active,
            'update_url' => route('admin.casket-catalogs.update', $catalog, absolute: false),
        ];
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

            if (! preg_match("~^[\pL\pM\pN][\pL\pM\pN\s.,'()/&-]*$~u", trim((string) $value))) {
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
            abort(403, 'Branch admins have read-only access to casket catalog.');
        }
    }
}
