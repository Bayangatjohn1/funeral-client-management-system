<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOnCatalog;
use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use App\Support\CatalogPhotos;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCanViewPackages();

        $user = $request->user();
        $query = Package::query()->with(['packageInclusions', 'packageFreebies', 'promotion']);

        if ($q = $request->input('q')) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                        ->orWhere('coffin_type', 'like', "%{$q}%");
            });
        }

        if ($request->input('status', 'active') === 'active') {
            $query->where('is_active', true);
        } elseif (in_array($request->input('status'), ['inactive', 'archived'], true)) {
            $query->where('is_active', false);
        }

        if ($request->input('promo') === 'with_promo') {
            $query->whereHas('promotion');
        } elseif ($request->input('promo') === 'no_promo') {
            $query->whereDoesntHave('promotion');
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

        $stats = $statsQuery->selectRaw(
            'COUNT(*) as total,
             SUM(is_active) as active_count,
             MAX(price) as max_price'
        )->first();

        return view('admin.packages.index', [
            'packages'       => $packages,
            'totalPackages'  => (int) ($stats->total ?? 0),
            'activePackages' => (int) ($stats->active_count ?? 0),
            'promoPackages'  => Package::whereHas('promotion')->count(),
            'highestPrice'   => (float) ($stats->max_price ?? 0),
        ]);
    }

    public function create()
    {
        $this->ensureCanManagePackages();

        return view('admin.packages.create', [
            'serviceTypeOptions' => Package::serviceTypeOptions(),
            'promotions' => \App\Models\Promotion::query()->whereNull('package_id')->orderBy('name')->get()
                ->reject(fn ($promotion) => $promotion->reviewFields($promotion->package) !== []
                    || in_array($promotion->statusAt(now('Asia/Manila')), ['Incomplete Setup', 'Expired', 'Turned Off'], true)),
            'hearseCatalogs' => \App\Models\HearseCatalog::where('is_active', true)->where('is_available', true)->orderBy('name')->get(),
            'includedAddOnCatalogs' => $this->includedAddOnCatalogs(),
            'freebieCatalogs' => FreebieCatalog::query()->where('is_active', true)->where('is_available', true)->orderBy('name')->get(),
            'casketCatalogs' => CasketCatalog::query()->where('is_active', true)->where('is_available', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManagePackages();
        $this->normalizeIncludedServiceInput($request);
        $this->trimPackageInput($request);

        $validated = $request->validate($this->packageValidationRules(), $this->packageValidationMessages(), $this->packageValidationAttributes());
        $inclusions = $this->cleanIncludedServices($validated['included_services'] ?? []);
        $freebies = $this->cleanFreebies($validated['freebies'] ?? []);

        $promotionId = $validated['promotion_id'] ?? null;

        $package = CatalogPhotos::persist($request, new Package(), function (Package $package) use ($validated, $promotionId, $inclusions, $freebies) {
            $inclusions = array_merge($inclusions, $this->catalogInclusions($validated['included_add_on_quantities'] ?? []));
            $this->assertCatalogItemsAvailable($inclusions, $freebies);
            $promotion = $this->resolvePromotionAssignment($promotionId, null, (float) $validated['price']);
            $package->fill([
                'name' => $validated['name'],
                'short_description' => $validated['short_description'] ?? null,
                'coffin_type' => $this->coffinTypeFromInclusions($inclusions),
                'price' => $validated['price'],
                'inclusions' => implode("\n", $this->includedServiceNames($inclusions)),
                'freebies' => $freebies === [] ? null : implode("\n", array_column($freebies, 'freebie_name')),
                'is_active' => true,
            ])->save();
            $promotion?->forceFill(['package_id' => $package->id])->save();

            $this->syncPackageItems($package, $inclusions, $freebies);

        });
        $package->load('promotion');

        AuditLogger::log(
            'package.created',
            'create',
            'package',
            $package->id,
            [
                'price' => $package->price,
                'promotion' => $package->promotion?->name,
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

        $package->load(['packageInclusions.casketCatalog', 'packageInclusions.hearseCatalog', 'packageInclusions.addOnCatalog', 'packageFreebies.catalog', 'promotion']);

        return view('admin.packages.show', [
            'package' => $package,
            'canManage' => $request->user()->isMainAdmin(),
            'intakeBlockers' => app(\App\Support\ServiceCatalogPolicy::class)->packageBlockers($package),
            'restoreBlockers' => app(\App\Support\ServiceCatalogPolicy::class)->packageBlockers($package, ignoreArchived: true),
        ]);
    }

    public function edit(Package $package)
    {
        $this->ensureCanManagePackages();

        $package->load(['packageInclusions.casketCatalog', 'packageInclusions.hearseCatalog', 'packageFreebies']);
        $package->load('promotion');
        $selectedFreebieCatalogIds = $package->packageFreebies
            ->pluck('freebie_catalog_id')
            ->filter()
            ->values();

        return view('admin.packages.edit', [
            'package' => $package,
            'promotions' => \App\Models\Promotion::query()->where(function ($query) use ($package) {
                $query->whereNull('package_id')->orWhere('package_id', $package->id);
            })->with('package')->orderBy('name')->get()->filter(function ($promotion) use ($package) {
                $isCurrentAssignment = (int) $promotion->package_id === (int) $package->id;
                $status = $promotion->statusAt(now('Asia/Manila'), $promotion->package);
                return $isCurrentAssignment || ($promotion->reviewFields($promotion->package) === []
                    && ! in_array($status, ['Incomplete Setup', 'Expired', 'Turned Off'], true));
            })->values(),
            'serviceTypeOptions' => Package::serviceTypeOptions(),
            'hearseCatalogs' => \App\Models\HearseCatalog::where('is_active', true)->where('is_available', true)->orderBy('name')->get(),
            'includedAddOnCatalogs' => $this->includedAddOnCatalogs($package),
            'freebieCatalogs' => FreebieCatalog::query()
                ->where(function ($query) use ($selectedFreebieCatalogIds) {
                    $query->where('is_active', true)->where('is_available', true)
                        ->when($selectedFreebieCatalogIds->isNotEmpty(), fn ($builder) => $builder->orWhereIn('id', $selectedFreebieCatalogIds));
                })
                ->orderBy('name')
                ->get(),
            'casketCatalogs' => CasketCatalog::query()->where('is_active', true)->where('is_available', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Package $package)
    {
        $this->ensureCanManagePackages();
        $this->normalizeIncludedServiceInput($request);
        $this->trimPackageInput($request);

        $validated = $request->validate($this->packageValidationRules(), $this->packageValidationMessages(), $this->packageValidationAttributes());
        $inclusions = $this->cleanIncludedServices($validated['included_services'] ?? []);
        $freebies = $this->cleanFreebies($validated['freebies'] ?? []);

        $promotionId = array_key_exists('promotion_id', $validated)
            ? $validated['promotion_id']
            : $package->promotion?->id;

        $before = [
            'price' => $package->price,
            'promotion_id' => $package->promotion?->id,
        ];

        CatalogPhotos::persist($request, $package, function (Package $package) use ($validated, $promotionId, $inclusions, $freebies) {
            $current = $package->packageInclusions()->whereNotNull('add_on_catalog_id')->pluck('included_quantity', 'add_on_catalog_id')->all();
            $quantities = ! empty($validated['configure_included_add_ons']) ? ($validated['included_add_on_quantities'] ?? []) : $current;
            $inclusions = array_merge($inclusions, $this->catalogInclusions($quantities, $current));
            $this->assertCatalogItemsAvailable($inclusions, $freebies);
            $promotion = $this->resolvePromotionAssignment($promotionId, $package, (float) $validated['price']);
            $package->update([
                'name' => $validated['name'],
                'short_description' => $validated['short_description'] ?? null,
                'coffin_type' => $this->coffinTypeFromInclusions($inclusions),
                'price' => $validated['price'],
                'inclusions' => implode("\n", $this->includedServiceNames($inclusions)),
                'freebies' => $freebies === [] ? null : implode("\n", array_column($freebies, 'freebie_name')),
                // Promotions are now authoritative in the related record. Clear
                // legacy mirror columns only after the chosen assignment is
                // safely resolved; the original configuration remains in the
                // Promotion row and is never lost when it is unassigned.
                'promo_label' => null,
                'promo_value_type' => null,
                'promo_value' => null,
                'promo_starts_at' => null,
                'promo_ends_at' => null,
                'promo_is_active' => false,
                'promo_manually_stopped_at' => null,
                'promo_manually_stopped_by' => null,
            ]);
            $package->promotion()->where('id', '!=', $promotion?->id ?? 0)->update(['package_id' => null]);
            $promotion?->forceFill(['package_id' => $package->id])->save();

            $this->syncPackageItems($package, $inclusions, $freebies);
        });

        AuditLogger::log(
            'package.updated',
            'update',
            'package',
            $package->id,
            [
                'price' => $package->price,
                'changes' => [
                    ['field' => 'price', 'before' => $before['price'], 'after' => $package->price],
                    ['field' => 'promotion_id', 'before' => $before['promotion_id'], 'after' => $promotionId],
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
            'price' => 'required|numeric|min:0|max:9999999999.99|decimal:0,2',
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
            'name' => ['required', 'string', 'max:150', $this->mustContainLetterRule('Package name must include letters.'), $this->allowedNameTextRule('Start Package Name with a letter or number. Use letters, numbers, spaces, periods, commas, apostrophes, parentheses, /, & or hyphens.')],
            'short_description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
            'included_services' => ['required', 'array', $this->atLeastOneIncludedServiceRule(), $this->requiredIncludedCasketRule()],
            'included_services.*.enabled' => ['nullable', 'boolean'],
            'included_services.*.description' => ['nullable', 'string', 'max:255'],
            'included_services.body_retrieval.included_kilometers' => ['required_if:included_services.body_retrieval.enabled,1', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999'],
            'included_services.hearse.included_kilometers' => ['required_if:included_services.hearse.enabled,1', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999'],
            'included_services.embalming.included_days' => ['required_if:included_services.embalming.enabled,1', 'nullable', 'integer', 'min:1', 'max:365'],
            'included_services.home_viewing.included_days' => ['required_if:included_services.home_viewing.enabled,1', 'nullable', 'integer', 'min:1', 'max:365'],
            'included_services.*.included_kilometers' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999'],
            'included_services.*.price_per_excess_kilometer' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'included_services.*.price_per_extended_day' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'included_services.body_retrieval.price_per_excess_kilometer' => ['required_if:included_services.body_retrieval.enabled,1', 'nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'included_services.hearse.price_per_excess_kilometer' => ['required_if:included_services.hearse.enabled,1', 'nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'included_services.embalming.price_per_extended_day' => ['required_if:included_services.embalming.enabled,1', 'nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'included_services.home_viewing.price_per_extended_day' => ['required_if:included_services.home_viewing.enabled,1', 'nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'included_services.hearse.hearse_catalog_id' => ['required_if:included_services.hearse.enabled,1', 'nullable', 'integer', Rule::exists('hearse_catalogs', 'id')->where('is_active', true)->where('is_available', true)],
            'included_services.*.hearse_catalog_id' => ['nullable', 'integer', Rule::exists('hearse_catalogs', 'id')->where('is_active', true)->where('is_available', true)],
            'included_services.*.casket_catalog_id' => ['nullable', 'integer', 'exists:casket_catalogs,id'],
            'included_services.casket.casket_catalog_id' => [$this->requiredIncludedCasketRule()],
            'included_services.*.casket_type' => ['nullable', 'string', 'max:150', $this->allowedNameTextRule('The casket description contains unsupported characters. Choose the casket again.')],
            'custom_inclusions' => ['nullable', 'array'],
            'configure_included_add_ons' => ['sometimes', 'boolean'],
            'included_add_on_quantities' => ['nullable', 'array'],
            'included_add_on_quantities.*' => ['nullable', 'integer', 'min:1', 'max:999'],
            'custom_inclusions.*.description' => ['nullable', 'string', 'max:255', $this->mustContainLetterRule('Custom Inclusion must contain at least one letter.'), $this->allowedNameTextRule('Use letters, numbers, spaces and supported punctuation in Custom Inclusion.')],
            'freebies' => ['nullable', 'array'],
            'freebies.*.freebie_catalog_id' => ['nullable', 'integer', Rule::exists('freebie_catalogs', 'id')->where('is_active', true)->where('is_available', true)],
            'freebies.*.freebie_name' => ['nullable', 'string', 'max:255', $this->mustContainLetterRule('Freebie Name must contain at least one letter.'), $this->allowedNameTextRule('Use letters, numbers, spaces and supported punctuation in Freebie Name.')],
            'freebies.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'freebies.*.unit' => ['nullable', 'string', 'max:40'],
        ];
    }

    private function packageValidationMessages(): array
    {
        return [
            'name.required' => 'Package name is required.',
            'required' => ':attribute is required.',
            'required_if' => ':attribute is required when this service is included.',
            'string' => 'Enter :attribute as text.',
            'array' => 'The :attribute could not be read. Review this section and try again.',
            'integer' => ':attribute must be a whole number.',
            'numeric' => ':attribute must be a number.',
            'boolean' => 'Choose a valid setting for :attribute.',
            'min.numeric' => ':attribute must be at least :min.',
            'max.numeric' => ':attribute cannot exceed :max.',
            'max.string' => ':attribute must be :max characters or fewer.',
            'decimal' => ':attribute can have up to two decimal places.',
            'date' => 'Enter a valid :attribute.',
            'included_services.*.hearse_catalog_id.exists' => 'Choose an active, available included hearse.',
            'included_services.*.casket_catalog_id.exists' => 'The selected casket no longer exists. Choose another casket.',
            'freebies.*.freebie_catalog_id.exists' => ':attribute is unavailable or archived. Replace or remove this freebie.',
            'included_services.*.included_kilometers.required_if' => ':attribute is required when this service is included.',
            'included_services.*.included_days.required_if' => ':attribute is required when this service is included.',
            'price.required' => 'Base Package Price is required.',
            'price.numeric' => 'Base Package Price must be a number.',
            'price.min' => 'Base Package Price cannot be negative.',
            'included_services.required' => 'At least one included service is required.',
            'included_services.casket.casket_catalog_id.exists' => 'Please select a valid casket.',
            'freebies.*.freebie_name.max' => ':attribute must be 255 characters or fewer.',
            'freebies.*.quantity.min' => 'Freebie quantity must be at least 1.',
        ];
    }

    private function packageValidationAttributes(): array
    {
        $attributes = [
            'name' => 'Package Name', 'short_description' => 'Package Description',
            'price' => 'Base Package Price', 'included_services' => 'Included Services',
            'custom_inclusions' => 'Custom Inclusions', 'custom_inclusions.*.description' => 'Custom Inclusion',
            'freebies' => 'Included Freebies', 'promotion_id' => 'Promotion',
        ];
        foreach (Package::serviceTypeOptions() as $type => $label) {
            foreach (['enabled'=>'Included', 'description'=>'Description', 'included_kilometers'=>'Included Distance (km)',
                'included_days'=>'Included Days', 'price_per_excess_kilometer'=>'Extra Kilometer Rate',
                'price_per_extended_day'=>'Extra Day Rate', 'casket_catalog_id'=>'Casket', 'hearse_catalog_id'=>'Hearse', 'casket_type'=>'Casket Description'] as $field=>$title) {
                $attributes["included_services.$type.$field"] = "$label $title";
            }
        }
        foreach (['freebie_catalog_id'=>'Freebie', 'freebie_name'=>'Freebie Name', 'quantity'=>'Freebie Quantity', 'unit'=>'Freebie Unit'] as $field=>$label) {
            $attributes["freebies.*.$field"] = $label;
        }
        return $attributes;
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

        foreach (['name', 'short_description'] as $field) {
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

    /**
     * Normalize supported legacy service keys before validation so their
     * nested values are subject to the same current rules as canonical keys.
     */
    private function normalizeIncludedServiceInput(Request $request): void
    {
        $rows = $request->input('included_services');
        if (! is_array($rows)) {
            return;
        }

        $normalized = [];
        foreach ($rows as $rawType => $row) {
            $type = Package::normalizeServiceType((string) $rawType);
            if (! array_key_exists($type, $normalized) || (string) $rawType === $type) {
                $normalized[$type] = $row;
            }
        }

        $request->merge(['included_services' => $normalized]);
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
            if (! is_numeric($catalogId) || (int) $catalogId <= 0) {
                if (! empty($casket['legacy_casket_text']) && $legacyCasketType !== '') {
                    return;
                }
                $fail('Select an available casket before saving.');
                return;
            }
            if (! CasketCatalog::query()->whereKey((int) $catalogId)->where('is_active', true)->where('is_available', true)->exists()) {
                $fail('This casket is no longer available. Choose another casket.');
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
                $payload['included_kilometers'] = $this->nullableMoney($row['included_kilometers'] ?? null);
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

            if ($type === Package::SERVICE_HEARSE) $payload['hearse_catalog_id'] = $row['hearse_catalog_id'] ?? null;
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

    private function assertCatalogItemsAvailable(array $inclusions, array $freebies): void
    {
        foreach ($inclusions as $item) {
            if (($item['service_type'] ?? '') === Package::SERVICE_HEARSE && ! \App\Models\HearseCatalog::whereKey($item['hearse_catalog_id'] ?? 0)->where('is_active', true)->where('is_available', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['included_services.hearse.hearse_catalog_id' => 'Choose an available included hearse.']);
            }
            $id = $item['casket_catalog_id'] ?? null;
            if (! $id) {
                continue;
            }
            if (! CasketCatalog::query()->whereKey($id)->where('is_active', true)->where('is_available', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'included_services.casket.casket_catalog_id' => 'This casket is no longer available. Choose another casket.',
                ]);
            }
        }

        foreach ($freebies as $item) {
            $id = $item['freebie_catalog_id'] ?? null;
            if (! $id) {
                continue;
            }
            if (! FreebieCatalog::query()->whereKey($id)->where('is_active', true)->where('is_available', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'freebies' => 'A selected freebie is no longer available. Remove or replace it.',
                ]);
            }
        }
    }

    private function includedAddOnCatalogs(?Package $package = null)
    {
        $existingIds = $package?->packageInclusions->pluck('add_on_catalog_id')->filter() ?? collect();

        return AddOnCatalog::where(fn ($query) => $query->where('is_active', true)->where('is_available', true))
            ->orWhereIn('id', $existingIds)->orderBy('name')->get();
    }

    private function catalogInclusions(array $quantities, array $current = []): array
    {
        $rows = [];
        $quantities = array_filter($quantities, fn ($quantity) => $quantity !== null && $quantity !== '');
        $catalogs = AddOnCatalog::whereIn('id', array_keys($quantities))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $chapelBranches = [];
        foreach ($quantities as $id => $quantity) {
            $catalog = $catalogs->get($id);
            if ($catalog?->category === AddOnCatalog::CHAPEL_VIEWING) {
                if (!$catalog->chapel_ready || in_array($catalog->branch_id, $chapelBranches, true)) {
                    throw ValidationException::withMessages(["included_add_on_quantities.$id" => 'Choose one fully configured, active chapel per branch.']);
                }
                $chapelBranches[] = $catalog->branch_id;
            }
            if (! $catalog || ((! $catalog->is_active || ! $catalog->is_available) && (int) ($current[$id] ?? 0) !== (int) $quantity)) {
                throw ValidationException::withMessages(["included_add_on_quantities.$id" => 'Choose an active, available add-on.']);
            }
            $rows[] = [
                'service_type' => Package::SERVICE_CUSTOM,
                'is_custom' => true,
                'inclusion_name' => $catalog->name,
                'add_on_catalog_id' => $catalog->id,
                'included_quantity' => $quantity,
            ];
        }

        return $rows;
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

        if (! $package->is_active) {
            $package->is_active = true;
        } else {
            $package->is_active = false;
        }
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

        return back()->with('success', $package->name.' '.($package->is_active ? 'restored.' : 'archived.'));
    }

    private function resolvePromotionAssignment(?int $promotionId, ?Package $package, float $packagePrice): ?\App\Models\Promotion
    {
        if (! $promotionId) return null;

        $promotion = \App\Models\Promotion::query()->lockForUpdate()->find($promotionId);
        if (! $promotion) {
            throw ValidationException::withMessages(['promotion_id' => 'The selected promotion no longer exists.']);
        }
        if ($promotion->package_id && $promotion->package_id !== $package?->id) {
            throw ValidationException::withMessages(['promotion_id' => 'This promotion is already assigned to another package.']);
        }

        $promotion->load('package');
        $issues = $promotion->reviewFields($promotion->package);
        $isAlreadyAssignedHere = $package && $promotion->package_id === $package->id;
        if ($issues && ! $isAlreadyAssignedHere) {
            throw ValidationException::withMessages(['promotion_id' => 'This promotion has incomplete saved configuration and must be corrected before it can be assigned.']);
        }
        if (! $isAlreadyAssignedHere && $promotion->statusAt(now('Asia/Manila'), $promotion->package) === 'Expired') {
            throw ValidationException::withMessages(['promotion_id' => 'Expired promotions cannot be newly assigned. Update the schedule first.']);
        }
        if (! $isAlreadyAssignedHere && $promotion->statusAt(now('Asia/Manila'), $promotion->package) === 'Turned Off') {
            throw ValidationException::withMessages(['promotion_id' => 'This promotion is currently turned off and cannot be used for new cases.']);
        }
        if (strtoupper((string) $promotion->discount_type) === 'AMOUNT'
            && (float) $promotion->discount_value > $packagePrice) {
            throw ValidationException::withMessages(['promotion_id' => "Fixed discount cannot exceed the assigned package's base price."]);
        }

        return $promotion;
    }
}
