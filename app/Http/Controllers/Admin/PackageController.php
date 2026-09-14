<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCanViewPackages();

        $user = $request->user();
        $query = Package::query()->with(['packageInclusions', 'packageFreebies']);

        if ($user->isBranchAdmin()) {
            $query->where('is_active', true);
        }

        if ($q = $request->input('q')) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                        ->orWhere('coffin_type', 'like', "%{$q}%");
            });
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->input('promo') === 'with_promo') {
            $query->where('promo_is_active', true);
        } elseif ($request->input('promo') === 'no_promo') {
            $query->where(function ($builder) {
                $builder->where('promo_is_active', false)->orWhereNull('promo_is_active');
            });
        }

        [$sortCol, $sortDir] = match ($request->input('sort', 'name_asc')) {
            'price_desc'   => ['price', 'desc'],
            'price_asc'    => ['price', 'asc'],
            'updated_desc' => ['updated_at', 'desc'],
            default        => ['name', 'asc'],
        };
        $query->orderBy($sortCol, $sortDir);

        $packages = $query->paginate(20)->withQueryString();

        $statsQuery = Package::query();

        if ($user->isBranchAdmin()) {
            $statsQuery->where('is_active', true);
        }

        $stats = $statsQuery->selectRaw(
            'COUNT(*) as total,
             SUM(is_active) as active_count,
             SUM(promo_is_active) as promo_count,
             MAX(price) as max_price'
        )->first();

        return view('admin.packages.index', [
            'packages'       => $packages,
            'totalPackages'  => (int) ($stats->total ?? 0),
            'activePackages' => (int) ($stats->active_count ?? 0),
            'promoPackages'  => (int) ($stats->promo_count ?? 0),
            'highestPrice'   => (float) ($stats->max_price ?? 0),
        ]);
    }

    public function create()
    {
        $this->ensureCanManagePackages();

        return view('admin.packages.create', [
            'serviceTypeOptions' => Package::serviceTypeOptions(),
            'freebieCatalogs' => FreebieCatalog::query()->where('is_active', true)->orderBy('name')->get(),
            'casketCatalogs' => CasketCatalog::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManagePackages();
        $this->trimPackageInput($request);

        $validated = $request->validate($this->packageValidationRules(), $this->packageValidationMessages());
        $inclusions = $this->cleanIncludedServices($validated['included_services'] ?? []);
        $freebies = $this->cleanFreebies($validated['freebies'] ?? []);

        $promoPayload = $this->resolvePromoPayload($request, $validated);

        $package = DB::transaction(function () use ($validated, $promoPayload, $inclusions, $freebies) {
            $package = Package::create([
                'name' => $validated['name'],
                'short_description' => $validated['short_description'] ?? null,
                'coffin_type' => $this->coffinTypeFromInclusions($inclusions),
                'price' => $validated['price'],
                'inclusions' => implode("\n", $this->includedServiceNames($inclusions)),
                'freebies' => $freebies === [] ? null : implode("\n", array_column($freebies, 'freebie_name')),
                'promo_label' => $promoPayload['promo_label'],
                'promo_value_type' => $promoPayload['promo_value_type'],
                'promo_value' => $promoPayload['promo_value'],
                'promo_starts_at' => $promoPayload['promo_starts_at'],
                'promo_ends_at' => $promoPayload['promo_ends_at'],
                'promo_is_active' => $promoPayload['promo_is_active'],
                'is_active' => true,
            ]);

            $this->syncPackageItems($package, $inclusions, $freebies);

            return $package;
        });

        AuditLogger::log(
            'package.created',
            'create',
            'package',
            $package->id,
            [
                'price' => $package->price,
                'promo_label' => $package->promo_label,
            ],
            null,
            null,
            'success',
            null,
            'Package created'
        );

        $returnTo = $request->input('return_to');
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Package created successfully.',
                'package_id' => $package->id,
            ], 201);
        }

        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'Package created successfully.');
        }

        return redirect()->route('admin.packages.index')->with('success', 'Package created successfully.');
    }

    public function show(Request $request, Package $package)
    {
        $this->ensureCanViewPackages();

        if ($request->user()->isBranchAdmin() && ! $package->is_active) {
            abort(404);
        }

        $package->load(['packageInclusions.casketCatalog', 'packageFreebies.catalog']);

        return view('admin.packages.show', [
            'package' => $package,
            'canManage' => $request->user()->isMainAdmin(),
        ]);
    }

    public function edit(Package $package)
    {
        $this->ensureCanManagePackages();

        $package->load(['packageInclusions.casketCatalog', 'packageFreebies']);
        $selectedFreebieCatalogIds = $package->packageFreebies
            ->pluck('freebie_catalog_id')
            ->filter()
            ->values();

        return view('admin.packages.edit', [
            'package' => $package,
            'serviceTypeOptions' => Package::serviceTypeOptions(),
            'freebieCatalogs' => FreebieCatalog::query()
                ->where(function ($query) use ($selectedFreebieCatalogIds) {
                    $query->where('is_active', true)
                        ->when($selectedFreebieCatalogIds->isNotEmpty(), fn ($builder) => $builder->orWhereIn('id', $selectedFreebieCatalogIds));
                })
                ->orderBy('name')
                ->get(),
            'casketCatalogs' => CasketCatalog::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Package $package)
    {
        $this->ensureCanManagePackages();
        $this->trimPackageInput($request);

        $validated = $request->validate($this->packageValidationRules(), $this->packageValidationMessages());
        $inclusions = $this->cleanIncludedServices($validated['included_services'] ?? []);
        $freebies = $this->cleanFreebies($validated['freebies'] ?? []);

        $promoPayload = $this->resolvePromoPayload($request, $validated);

        $before = [
            'price' => $package->price,
            'promo_label' => $package->promo_label,
            'promo_is_active' => $package->promo_is_active,
        ];

        DB::transaction(function () use ($package, $validated, $promoPayload, $inclusions, $freebies) {
            $package->update([
                'name' => $validated['name'],
                'short_description' => $validated['short_description'] ?? null,
                'coffin_type' => $this->coffinTypeFromInclusions($inclusions),
                'price' => $validated['price'],
                'inclusions' => implode("\n", $this->includedServiceNames($inclusions)),
                'freebies' => $freebies === [] ? null : implode("\n", array_column($freebies, 'freebie_name')),
                'promo_label' => $promoPayload['promo_label'],
                'promo_value_type' => $promoPayload['promo_value_type'],
                'promo_value' => $promoPayload['promo_value'],
                'promo_starts_at' => $promoPayload['promo_starts_at'],
                'promo_ends_at' => $promoPayload['promo_ends_at'],
                'promo_is_active' => $promoPayload['promo_is_active'],
            ]);

            $this->syncPackageItems($package, $inclusions, $freebies);
        });

        AuditLogger::log(
            'package.updated',
            'update',
            'package',
            $package->id,
            [
                'price' => $package->price,
                'promo_label' => $package->promo_label,
                'promo_active' => $package->promo_is_active,
                'changes' => [
                    ['field' => 'price', 'before' => $before['price'], 'after' => $package->price],
                    ['field' => 'promo_label', 'before' => $before['promo_label'], 'after' => $package->promo_label],
                    ['field' => 'promo_is_active', 'before' => $before['promo_is_active'], 'after' => $package->promo_is_active],
                ],
            ],
            null,
            null,
            'success',
            'Package updated',
            'Package updated'
        );

        $returnTo = $request->input('return_to');
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Package updated successfully.',
                'package_id' => $package->id,
            ]);
        }

        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'Package updated successfully.');
        }

        return redirect()->route('admin.packages.index')->with('success', 'Package updated successfully.');
    }

    public function quickUpdatePrice(Request $request, Package $package)
    {
        $this->ensureCanManagePackages();

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
        ], $this->packageValidationMessages());

        $beforePrice = $package->price;

        $package->update([
            'price' => $validated['price'],
        ]);

        AuditLogger::log(
            'package.price_changed',
            'update',
            'package',
            $package->id,
            [
                'price' => $validated['price'],
                'changes' => [
                    ['field' => 'price', 'before' => $beforePrice, 'after' => $validated['price']],
                ],
            ],
            null,
            null,
            'success',
            'Package price updated',
            'Package price updated'
        );

        return redirect()->route('admin.packages.index')->with('success', 'Package price updated.');
    }

    private function packageValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', $this->mustContainLetterRule('Package name must include letters.'), $this->allowedNameTextRule('Package name has unnecessary special characters.')],
            'short_description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'included_services' => ['required', 'array', $this->atLeastOneIncludedServiceRule(), $this->requiredIncludedCasketRule()],
            'included_services.*.enabled' => ['nullable', 'boolean'],
            'included_services.*.description' => ['nullable', 'string', 'max:255'],
            'included_services.*.included_kilometers' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'included_services.*.price_per_excess_kilometer' => ['nullable', 'numeric', 'min:0'],
            'included_services.*.included_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'included_services.*.price_per_extended_day' => ['nullable', 'numeric', 'min:0'],
            'included_services.*.casket_catalog_id' => ['nullable', 'integer', 'exists:casket_catalogs,id'],
            'included_services.casket.casket_catalog_id' => [$this->requiredIncludedCasketRule()],
            'included_services.*.casket_type' => ['nullable', 'string', 'max:150', $this->allowedNameTextRule('Casket type has unnecessary special characters.')],
            'custom_inclusions' => ['nullable', 'array'],
            'custom_inclusions.*.description' => ['nullable', 'string', 'max:255', $this->mustContainLetterRule('Custom inclusion must include valid description text.'), $this->allowedNameTextRule('Custom inclusion has unnecessary special characters.')],
            'freebies' => ['nullable', 'array'],
            'freebies.*.freebie_catalog_id' => ['nullable', 'integer', 'exists:freebie_catalogs,id'],
            'freebies.*.freebie_name' => ['nullable', 'string', 'max:255', $this->mustContainLetterRule('Freebie must include valid description text.'), $this->allowedNameTextRule('Freebie has unnecessary special characters.')],
            'freebies.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'freebies.*.unit' => ['nullable', 'string', 'max:40'],
            'promo_label' => ['nullable', 'string', 'max:120', $this->allowedNameTextRule('Promo label has unnecessary special characters.')],
            'promo_value_type' => 'nullable|in:AMOUNT,PERCENT',
            'promo_value' => 'nullable|numeric|min:0',
            'promo_starts_at' => 'nullable|date',
            'promo_ends_at' => 'nullable|date|after_or_equal:promo_starts_at',
            'promo_is_active' => 'boolean',
        ];
    }

    private function packageValidationMessages(): array
    {
        return [
            'name.required' => 'Package name is required.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid amount.',
            'price.min' => 'Price cannot be negative.',
            'included_services.required' => 'At least one included service is required.',
            'included_services.casket.casket_catalog_id.exists' => 'Please select a valid casket.',
            'freebies.*.freebie_name.max' => 'Freebie must include valid description text.',
            'freebies.*.quantity.min' => 'Freebie quantity must be at least 1.',
            'promo_value.numeric' => 'Promo value must be a valid amount.',
            'promo_value.min' => 'Promo value cannot be negative.',
            'promo_ends_at.after_or_equal' => 'Promo end date must be on or after the start date.',
        ];
    }

    private function trimPackageInput(Request $request): void
    {
        $trimmed = [];

        if (! $request->has('included_services') && ($request->has('inclusions') || $request->has('coffin_type'))) {
            $legacyIncludedServices = [];
            $legacyCustomInclusions = [];

            $coffinType = trim((string) $request->input('coffin_type', ''));
            if ($coffinType !== '') {
                $legacyIncludedServices[Package::SERVICE_CASKET] = [
                    'enabled' => 1,
                    'casket_type' => $coffinType,
                    'legacy_casket_text' => 1,
                ];
            }

            $legacyInclusions = $request->input('inclusions', []);
            if (is_string($legacyInclusions)) {
                $legacyInclusions = Package::parseLegacyItems($legacyInclusions);
            }

            if (is_array($legacyInclusions)) {
                foreach ($legacyInclusions as $item) {
                    $item = trim(preg_replace('/\s+/', ' ', (string) $item));
                    if ($item !== '' && strcasecmp($item, $coffinType) !== 0) {
                        $legacyCustomInclusions[] = ['description' => $item];
                    }
                }
            }

            $request->merge([
                'included_services' => $legacyIncludedServices,
                'custom_inclusions' => $legacyCustomInclusions,
            ]);
        }

        if (! $request->has('freebies') && $request->filled('legacy_freebies')) {
            $request->merge(['freebies' => Package::parseLegacyItems($request->input('legacy_freebies'))]);
        }

        if ($request->has('freebies')) {
            $freebies = $request->input('freebies');
            if (is_string($freebies)) {
                $freebies = Package::parseLegacyItems($freebies);
            }
            if (is_array($freebies) && array_is_list($freebies) && collect($freebies)->every(fn ($item) => ! is_array($item))) {
                $request->merge([
                    'freebies' => collect($freebies)
                        ->map(fn ($item) => ['freebie_name' => $item, 'quantity' => 1, 'unit' => 'item'])
                        ->all(),
                ]);
            }
        }

        foreach (['name', 'short_description', 'promo_label'] as $field) {
            if ($request->has($field)) {
                $trimmed[$field] = trim(preg_replace('/\s+/', ' ', (string) $request->input($field)));
            }
        }

        foreach (['included_services', 'custom_inclusions', 'freebies'] as $group) {
            if (! $request->has($group) || ! is_array($request->input($group))) {
                continue;
            }

            $trimmed[$group] = collect($request->input($group))
                ->map(function ($row) {
                    if (! is_array($row)) {
                        return $row;
                    }

                    foreach (['description', 'casket_type', 'freebie_name', 'unit'] as $field) {
                        if (array_key_exists($field, $row) && is_scalar($row[$field])) {
                            $row[$field] = trim(preg_replace('/\s+/', ' ', (string) $row[$field]));
                        }
                    }

                    return $row;
                })
                ->all();
        }

        if ($trimmed !== []) {
            $request->merge($trimmed);
        }
    }

    private function mustContainLetterRule(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if ($value === null || ! is_scalar($value) || trim((string) $value) === '') {
                return;
            }

            $value = trim((string) $value);

            if (! preg_match('/[\pL\pM]/u', $value) || preg_match('/^\d+(?:\.\d+)?$/', $value)) {
                $fail($message);
            }
        };
    }

    private function allowedNameTextRule(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if ($value === null || ! is_scalar($value) || trim((string) $value) === '') {
                return;
            }

            if (! preg_match("~^[\pL\pM\pN][\pL\pM\pN\s.,'()/&-]*$~u", trim((string) $value))) {
                $fail($message);
            }
        };
    }

    private function atLeastOneIncludedServiceRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_array($value) || $this->cleanIncludedServices($value) === []) {
                $fail('At least one included service is required.');
            }
        };
    }

    private function requiredIncludedCasketRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $casket = is_array($value)
                ? ($value[Package::SERVICE_CASKET] ?? [])
                : request()->input('included_services.' . Package::SERVICE_CASKET, []);
            if (! is_array($casket) || empty($casket['enabled'])) {
                return;
            }

            $catalogId = $casket['casket_catalog_id'] ?? null;
            $legacyCasketType = trim((string) ($casket['casket_type'] ?? ''));
            if ((! is_numeric($catalogId) || (int) $catalogId <= 0) && $legacyCasketType === '') {
                $fail('Please select a casket before saving.');
            }
        };
    }

    private function cleanIncludedServices(array $rows): array
    {
        $labels = Package::serviceTypeOptions();
        $enabled = [];

        foreach ($rows as $rawType => $row) {
            if (! is_array($row) || empty($row['enabled'])) {
                continue;
            }

            $type = Package::normalizeServiceType((string) $rawType);
            if (! array_key_exists($type, $labels) || $type === Package::SERVICE_CUSTOM) {
                continue;
            }

            $payload = [
                'service_type' => $type,
                'casket_catalog_id' => null,
                'is_custom' => false,
                'inclusion_name' => $labels[$type],
                'included_kilometers' => null,
                'price_per_excess_kilometer' => null,
                'included_days' => null,
                'price_per_extended_day' => null,
                'casket_type' => null,
            ];

            if (in_array($type, [Package::SERVICE_BODY_RETRIEVAL, Package::SERVICE_HEARSE], true)) {
                $payload['included_kilometers'] = $this->nullableInteger($row['included_kilometers'] ?? null);
                $payload['price_per_excess_kilometer'] = $this->nullableMoney($row['price_per_excess_kilometer'] ?? null);
            }

            if (in_array($type, [Package::SERVICE_EMBALMING, Package::SERVICE_HOME_VIEWING], true)) {
                $payload['included_days'] = $this->nullableInteger($row['included_days'] ?? null);
                $payload['price_per_extended_day'] = $this->nullableMoney($row['price_per_extended_day'] ?? null);
            }

            if ($type === Package::SERVICE_CASKET) {
                $catalogId = isset($row['casket_catalog_id']) && is_numeric($row['casket_catalog_id'])
                    ? (int) $row['casket_catalog_id']
                    : null;

                if ($catalogId) {
                    $catalog = CasketCatalog::query()->whereKey($catalogId)->first();
                    if ($catalog) {
                        $payload['casket_catalog_id'] = $catalog->id;
                        $payload['casket_type'] = $catalog->display_name;
                    }
                }

                if (empty($payload['casket_type'])) {
                    $payload['casket_type'] = trim((string) ($row['casket_type'] ?? ''));
                }
            }

            $enabled[] = $payload;
        }

        $customRows = request()->input('custom_inclusions', []);
        if (is_array($customRows)) {
            foreach ($customRows as $row) {
                $description = is_array($row) ? trim((string) ($row['description'] ?? '')) : '';
                if ($description === '') {
                    continue;
                }

                $enabled[] = [
                    'service_type' => Package::SERVICE_CUSTOM,
                    'casket_catalog_id' => null,
                    'is_custom' => true,
                    'inclusion_name' => $description,
                    'included_kilometers' => null,
                    'price_per_excess_kilometer' => null,
                    'included_days' => null,
                    'price_per_extended_day' => null,
                    'casket_type' => null,
                ];
            }
        }

        return $enabled;
    }

    private function cleanFreebies(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                $catalogId = isset($row['freebie_catalog_id']) && is_numeric($row['freebie_catalog_id'])
                    ? (int) $row['freebie_catalog_id']
                    : null;
                $name = trim((string) ($row['freebie_name'] ?? ''));

                if ($catalogId && $name === '') {
                    $name = (string) FreebieCatalog::whereKey($catalogId)->value('name');
                }

                return [
                    'freebie_catalog_id' => $catalogId,
                    'freebie_name' => $name,
                    'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
                    'unit' => trim((string) ($row['unit'] ?? 'item')) ?: 'item',
                    'is_custom' => ! $catalogId,
                ];
            })
            ->filter(fn ($row) => $row['freebie_name'] !== '')
            ->unique(fn ($row) => mb_strtolower($row['freebie_name']) . '|' . mb_strtolower($row['unit']))
            ->values()
            ->all();
    }

    private function syncPackageItems(Package $package, array $inclusions, array $freebies): void
    {
        $package->packageInclusions()->delete();
        $package->packageFreebies()->delete();

        foreach ($inclusions as $index => $item) {
            $package->packageInclusions()->create($item + ['sort_order' => $index]);
        }

        foreach ($freebies as $index => $item) {
            $catalogId = $item['freebie_catalog_id'];
            if (! $catalogId && $item['freebie_name'] !== '') {
                $catalog = FreebieCatalog::firstOrCreate(
                    ['name' => $item['freebie_name']],
                    ['default_unit' => $item['unit'], 'is_active' => true]
                );
                $catalogId = $catalog->id;
            }

            $package->packageFreebies()->create([
                'freebie_catalog_id' => $catalogId,
                'freebie_name' => $item['freebie_name'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'is_custom' => $item['is_custom'],
                'sort_order' => $index,
            ]);
        }
    }

    private function includedServiceNames(array $inclusions): array
    {
        return collect($inclusions)
            ->map(function (array $row) {
                if ($row['service_type'] === Package::SERVICE_CASKET && ! empty($row['casket_type'])) {
                    return $row['inclusion_name'] . ': ' . $row['casket_type'];
                }

                return $row['inclusion_name'];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function coffinTypeFromInclusions(array $inclusions): ?string
    {
        foreach ($inclusions as $row) {
            if (($row['service_type'] ?? null) === Package::SERVICE_CASKET && ! empty($row['casket_type'])) {
                return $row['casket_type'];
            }
        }

        return null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    private function nullableMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round(max(0, (float) $value), 2);
    }

    private function cleanAddOns(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                return [
                    'id' => isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null,
                    'name' => trim((string) ($row['name'] ?? '')),
                    'description' => trim((string) ($row['description'] ?? '')),
                    'price' => round((float) ($row['price'] ?? 0), 2),
                    'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                ];
            })
            ->filter(fn ($row) => $row['name'] !== '' || (string) $row['description'] !== '' || (float) $row['price'] > 0)
            ->values()
            ->all();
    }

    private function syncPackageAddOns(Package $package, array $addOns): void
    {
        $keptIds = [];

        foreach ($addOns as $row) {
            $payload = [
                'name' => $row['name'],
                'description' => $row['description'] !== '' ? $row['description'] : null,
                'price' => $row['price'],
                'is_active' => (bool) $row['is_active'],
            ];

            if (! empty($row['id'])) {
                $addOn = $package->packageAddOns()->whereKey($row['id'])->first();
                if ($addOn) {
                    $addOn->update($payload);
                    $keptIds[] = $addOn->id;
                    continue;
                }
            }

            $addOn = $package->packageAddOns()->create($payload);
            $keptIds[] = $addOn->id;
        }

        $package->packageAddOns()
            ->whereNotIn('id', $keptIds ?: [0])
            ->get()
            ->each(function ($addOn) {
                if ($addOn->caseAddOns()->exists()) {
                    $addOn->update(['is_active' => false]);
                    return;
                }

                $addOn->delete();
            });
    }

    private function ensureCanViewPackages(): void
    {
        $user = auth()->user();

        if (! $user || (! $user->isMainAdmin() && ! $user->isBranchAdmin())) {
            abort(403, 'Unauthorized');
        }
    }

    private function ensureCanManagePackages(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->isMainAdmin()) {
            abort(403, 'Branch admins have read-only access to packages.');
        }
    }

    public function toggleActive(Package $package)
    {
        $this->ensureCanManagePackages();

        $package->is_active = !$package->is_active;
        $package->save();

        AuditLogger::log(
            action: $package->is_active ? 'package.activated' : 'package.deactivated',
            actionType: 'status_change',
            entityType: 'package',
            entityId: $package->id,
            metadata: [
                'package_name' => $package->package_name,
                'to' => $package->is_active ? 'active' : 'inactive',
            ],
        );

        return back()->with('success', 'Package status updated.');
    }

    private function resolvePromoPayload(Request $request, array $validated): array
    {
        $promoActive = $request->boolean('promo_is_active');
        $hasPromoValue = isset($validated['promo_value']) && (float) $validated['promo_value'] > 0;
        $hasPromoType = !empty($validated['promo_value_type']);

        if (!$promoActive || !$hasPromoValue || !$hasPromoType) {
            return [
                'promo_label' => null,
                'promo_value_type' => null,
                'promo_value' => null,
                'promo_starts_at' => null,
                'promo_ends_at' => null,
                'promo_is_active' => false,
            ];
        }

        return [
            'promo_label' => $validated['promo_label'] ?? null,
            'promo_value_type' => $validated['promo_value_type'],
            'promo_value' => (float) $validated['promo_value'],
            'promo_starts_at' => ! empty($validated['promo_starts_at'])
                ? Carbon::parse($validated['promo_starts_at'], 'Asia/Manila')
                : null,
            'promo_ends_at' => ! empty($validated['promo_ends_at'])
                ? Carbon::parse($validated['promo_ends_at'], 'Asia/Manila')->endOfDay()
                : null,
            'promo_is_active' => true,
        ];
    }
}
