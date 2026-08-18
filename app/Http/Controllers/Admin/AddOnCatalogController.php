<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOnCatalog;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddOnCatalogController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCanView();

        $query = AddOnCatalog::query();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->filled('category') && in_array($request->query('category'), AddOnCatalog::CATEGORY_OPTIONS, true)) {
            $query->where('category', $request->query('category'));
        }

        $catalogs = $query
            ->withCount('legacyPackageAddOns')
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.add-on-catalogs.index', [
            'catalogs' => $catalogs,
            'categoryOptions' => AddOnCatalog::CATEGORY_OPTIONS,
        ]);
    }

    public function create()
    {
        $this->ensureCanManage();

        return view('admin.add-on-catalogs.create', [
            'catalog' => new AddOnCatalog([
                'category' => 'General',
                'unit' => 'item',
                'is_active' => true,
            ]),
            'categoryOptions' => AddOnCatalog::CATEGORY_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage();

        $validated = $request->validate($this->rules(), $this->messages());
        $validated = $this->normalize($validated);
        $validated['is_active'] = true;

        if ($this->duplicateExists($validated['name'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'An add-on with this name already exists.',
                    'errors' => ['name' => ['An add-on with this name already exists.']],
                ], 422);
            }

            return back()->withErrors([
                'name' => 'An add-on with this name already exists.',
            ])->withInput();
        }

        $catalog = AddOnCatalog::create($validated);

        AuditLogger::log(
            'add_on_catalog.created',
            'create',
            'add_on_catalog',
            $catalog->id,
            ['name' => $catalog->name, 'price' => $catalog->price, 'unit' => $catalog->unit],
            null,
            null,
            'success',
            null,
            'Add-on catalog item created'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Add-on saved successfully.',
                'catalog' => $this->catalogPayload($catalog),
            ], 201);
        }

        return redirect($request->input('return_to') ?: route('admin.add-on-catalogs.index'))
            ->with('success', 'Add-on saved successfully.');
    }

    public function edit(AddOnCatalog $add_on_catalog)
    {
        $this->ensureCanManage();

        return view('admin.add-on-catalogs.edit', [
            'catalog' => $add_on_catalog,
            'categoryOptions' => AddOnCatalog::CATEGORY_OPTIONS,
        ]);
    }

    public function update(Request $request, AddOnCatalog $add_on_catalog)
    {
        $this->ensureCanManage();

        $validated = $request->validate($this->rules(), $this->messages());
        $validated = $this->normalize($validated);

        if ($this->duplicateExists($validated['name'], $add_on_catalog->id)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'An add-on with this name already exists.',
                    'errors' => ['name' => ['An add-on with this name already exists.']],
                ], 422);
            }

            return back()->withErrors([
                'name' => 'An add-on with this name already exists.',
            ])->withInput();
        }

        $add_on_catalog->update($validated);

        AuditLogger::log(
            'add_on_catalog.updated',
            'update',
            'add_on_catalog',
            $add_on_catalog->id,
            ['name' => $add_on_catalog->name, 'price' => $add_on_catalog->price, 'unit' => $add_on_catalog->unit],
            null,
            null,
            'success',
            'Add-on catalog item updated',
            'Add-on catalog item updated'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Add-on updated successfully.',
                'catalog' => $this->catalogPayload($add_on_catalog->fresh()),
            ]);
        }

        return redirect($request->input('return_to') ?: route('admin.add-on-catalogs.index'))
            ->with('success', 'Add-on updated successfully.');
    }

    public function toggleActive(AddOnCatalog $add_on_catalog)
    {
        $this->ensureCanManage();

        $add_on_catalog->update(['is_active' => ! $add_on_catalog->is_active]);

        return back()->with('success', $add_on_catalog->is_active ? 'Add-on restored.' : 'Add-on archived.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', $this->mustContainLetterRule('Add-on name must include letters.'), $this->allowedNameTextRule('Add-on name has unnecessary special characters.')],
            'category' => ['required', 'string', Rule::in(AddOnCatalog::CATEGORY_OPTIONS)],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', Rule::in(AddOnCatalog::UNIT_OPTIONS)],
            'return_to' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Add-on name is required.',
            'price.required' => 'Standard price is required.',
            'price.numeric' => 'Standard price must be a valid amount.',
            'price.min' => 'Standard price cannot be negative.',
            'category.required' => 'Add-on type is required.',
            'category.in' => 'Select a valid add-on type.',
            'unit.required' => 'Unit is required.',
            'unit.in' => 'Select a valid unit.',
        ];
    }

    private function normalize(array $validated): array
    {
        foreach (['name', 'category', 'description', 'unit'] as $field) {
            $validated[$field] = isset($validated[$field])
                ? trim(preg_replace('/\s+/', ' ', (string) $validated[$field]))
                : null;
        }

        $validated['category'] = $validated['category'] ?: 'General';
        $validated['description'] = $validated['description'] ?: null;
        $validated['unit'] = $validated['unit'] ?: 'item';
        $validated['price'] = round((float) $validated['price'], 2);

        unset($validated['return_to']);

        return $validated;
    }

    private function duplicateExists(string $name, ?int $ignoreId = null): bool
    {
        return AddOnCatalog::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
    }

    private function catalogPayload(AddOnCatalog $catalog): array
    {
        return [
            'id' => $catalog->id,
            'name' => $catalog->name,
            'category' => $catalog->category,
            'description' => $catalog->description,
            'price' => (float) $catalog->price,
            'unit' => $catalog->unit,
            'is_active' => (bool) $catalog->is_active,
            'update_url' => route('admin.add-on-catalogs.update', $catalog, absolute: false),
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

            if (! preg_match("~^[\pL\pM][\pL\pM\s,'()/&-]*$~u", trim((string) $value))) {
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
            abort(403, 'Branch admins have read-only access to add-on catalog.');
        }
    }
}
