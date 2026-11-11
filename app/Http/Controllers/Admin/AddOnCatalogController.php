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
        if (auth()->user()->isBranchAdmin()) {
            $query->where(fn ($q) => $q->where('category', '<>', AddOnCatalog::CHAPEL_VIEWING)->orWhere('branch_id', auth()->user()->branch_id));
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        if ($request->query('status', 'active') === 'active') {
            $query->where('is_active', true);
        } elseif (in_array($request->query('status'), ['inactive', 'archived'], true)) {
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

        if ($this->duplicateExists($validated['name'], null, $validated['category'], $validated['branch_id'] ?? null)) {
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

        $catalog = \App\Support\CatalogPhotos::save($request, new AddOnCatalog(), $validated);

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

        return \App\Support\CatalogFormResponse::saved($request, 'Add-on saved successfully.', route('admin.add-on-catalogs.index'));
    }

    public function show(Request $request, AddOnCatalog $add_on_catalog)
    {
        $this->ensureCanView();

        $add_on_catalog->loadCount('legacyPackageAddOns');
        abort_if(auth()->user()->isBranchAdmin() && $add_on_catalog->category === AddOnCatalog::CHAPEL_VIEWING && (int) $add_on_catalog->branch_id !== (int) auth()->user()->branch_id, 403);
        $add_on_catalog->load('legacyPackageAddOns.package');

        return view('admin.add-on-catalogs.show', [
            'catalog' => $add_on_catalog,
            'canManage' => $request->user()->isMainAdmin(),
        ]);
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

        $referenced = $add_on_catalog->legacyPackageAddOns()->exists()
            || \App\Models\PackageInclusion::where('add_on_catalog_id', $add_on_catalog->id)->exists()
            || \App\Models\FuneralCase::withoutGlobalScope('branch_scope')->where('chapel_catalog_id', $add_on_catalog->id)->exists();
        if ($referenced && ($add_on_catalog->category !== $validated['category']
            || ($add_on_catalog->branch_id && (int) $add_on_catalog->branch_id !== (int) ($validated['branch_id'] ?? 0)))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['category' => 'This catalog item is already in use. Its category and assigned branch cannot be changed.']);
        }
        if ($this->duplicateExists($validated['name'], $add_on_catalog->id, $validated['category'], $validated['branch_id'] ?? null)) {
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

        \App\Support\CatalogPhotos::save($request, $add_on_catalog, $validated);

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

        return \App\Support\CatalogFormResponse::saved($request, 'Add-on updated successfully.', route('admin.add-on-catalogs.index'));
    }

    public function toggleAvailability(AddOnCatalog $add_on_catalog)
    {
        $this->ensureCanManage();
        if (! $add_on_catalog->is_active) {
            return back()->withErrors(['catalog' => 'Restore the archived item before changing its availability.']);
        }

        $add_on_catalog->update(['is_available' => ! $add_on_catalog->is_available]);
        AuditLogger::log('add_on_catalog.availability_changed', 'status_change', 'add_on_catalog', $add_on_catalog->id, ['is_available' => $add_on_catalog->is_available]);

        return back()->with('success', $add_on_catalog->name.' marked '.($add_on_catalog->is_available ? 'available.' : 'unavailable.').' Package status is unchanged; intake eligibility is recalculated.');
    }

    public function toggleActive(AddOnCatalog $add_on_catalog)
    {
        $this->ensureCanManage();
        if ($message = app(\App\Support\ServiceCatalogPolicy::class)->archiveBlockMessage($add_on_catalog)) {
            return back()->withErrors(['catalog' => $message]);
        }

        $add_on_catalog->update(['is_active' => ! $add_on_catalog->is_active]);

        return back()->with('success', $add_on_catalog->name.' '.($add_on_catalog->is_active ? 'restored.' : 'archived.'));
    }

    private function rules(): array
    {
        return [
            'branch_id' => ['exclude_unless:category,Chapel Viewing', 'required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'chapel_address' => ['exclude_unless:category,Chapel Viewing', 'required', 'string', 'max:255', 'regex:/\\pL/u'],
            'name' => ['required', 'string', 'max:150', $this->mustContainLetterRule('Add-on name must include letters.'), $this->allowedNameTextRule('Add-on name has unnecessary special characters.')],
            'category' => ['required', 'string', Rule::in(AddOnCatalog::CATEGORY_OPTIONS)],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'unit' => ['required', 'string', Rule::in(AddOnCatalog::UNIT_OPTIONS), function ($attribute, $value, $fail) {
                if (request('category') === AddOnCatalog::CHAPEL_VIEWING && $value !== 'day') {
                    $fail('Select Day as the billing unit for Chapel Viewing.');
                }
            }],
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
        $isChapel = $validated['category'] === AddOnCatalog::CHAPEL_VIEWING;
        $validated['branch_id'] = $isChapel ? (int) $validated['branch_id'] : null;
        $validated['chapel_address'] = $isChapel ? trim($validated['chapel_address']) : null;
        $validated['chapel_identity'] = $isChapel ? $validated['branch_id'].':'.mb_strtolower($validated['name']) : null;

        unset($validated['return_to']);

        return $validated;
    }

    private function duplicateExists(string $name, ?int $ignoreId = null, ?string $category = null, ?int $branchId = null): bool
    {
        return AddOnCatalog::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($category === AddOnCatalog::CHAPEL_VIEWING,
                fn ($q) => $q->where('category', $category)->where('branch_id', $branchId),
                fn ($q) => $q->where('category', '<>', AddOnCatalog::CHAPEL_VIEWING))
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
            'branch_id' => $catalog->branch_id, 'chapel_address' => $catalog->chapel_address,
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

            $pattern = request('category') === AddOnCatalog::CHAPEL_VIEWING
                ? "~^[\pL\pM][\pL\pM\pN\s,'()/&-]*$~u"
                : "~^[\pL\pM][\pL\pM\s,'()/&-]*$~u";
            if (! preg_match($pattern, trim((string) $value))) {
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
