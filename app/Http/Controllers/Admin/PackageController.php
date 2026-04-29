<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Support\AuditLogger;
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

        return view('admin.packages.create');
    }

    public function store(Request $request)
    {
        $this->ensureCanManagePackages();
        $this->trimPackageInput($request);

        $validated = $request->validate($this->packageValidationRules(), $this->packageValidationMessages());
        $inclusions = $this->cleanPackageItems($validated['inclusions'] ?? []);
        $freebies = $this->cleanPackageItems($validated['freebies'] ?? []);

        $promoPayload = $this->resolvePromoPayload($request, $validated);

        $package = DB::transaction(function () use ($request, $validated, $promoPayload, $inclusions, $freebies) {
            $package = Package::create([
                'name' => $validated['name'],
                'coffin_type' => $validated['coffin_type'],
                'price' => $validated['price'],
                'inclusions' => implode("\n", $inclusions),
                'freebies' => $freebies === [] ? null : implode("\n", $freebies),
                'promo_label' => $promoPayload['promo_label'],
                'promo_value_type' => $promoPayload['promo_value_type'],
                'promo_value' => $promoPayload['promo_value'],
                'promo_starts_at' => $promoPayload['promo_starts_at'],
                'promo_ends_at' => $promoPayload['promo_ends_at'],
                'promo_is_active' => $promoPayload['promo_is_active'],
                'is_active' => $request->boolean('is_active'),
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
        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'Package created successfully.');
        }

        return redirect()->route('admin.packages.index')->with('success', 'Package created successfully.');
    }

    public function edit(Package $package)
    {
        $this->ensureCanManagePackages();

        $package->load(['packageInclusions', 'packageFreebies']);

        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $this->ensureCanManagePackages();
        $this->trimPackageInput($request);

        $validated = $request->validate($this->packageValidationRules(), $this->packageValidationMessages());
        $inclusions = $this->cleanPackageItems($validated['inclusions'] ?? []);
        $freebies = $this->cleanPackageItems($validated['freebies'] ?? []);

        $promoPayload = $this->resolvePromoPayload($request, $validated);

        $before = [
            'price' => $package->price,
            'promo_label' => $package->promo_label,
            'promo_is_active' => $package->promo_is_active,
        ];

        DB::transaction(function () use ($request, $package, $validated, $promoPayload, $inclusions, $freebies) {
            $package->update([
                'name' => $validated['name'],
                'coffin_type' => $validated['coffin_type'],
                'price' => $validated['price'],
                'inclusions' => implode("\n", $inclusions),
                'freebies' => $freebies === [] ? null : implode("\n", $freebies),
                'promo_label' => $promoPayload['promo_label'],
                'promo_value_type' => $promoPayload['promo_value_type'],
                'promo_value' => $promoPayload['promo_value'],
                'promo_starts_at' => $promoPayload['promo_starts_at'],
                'promo_ends_at' => $promoPayload['promo_ends_at'],
                'promo_is_active' => $promoPayload['promo_is_active'],
                'is_active' => $request->boolean('is_active'),
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
            'name' => ['required', 'string', 'max:150', $this->mustContainLetterRule('Package name must include letters.')],
            'coffin_type' => ['required', 'string', 'max:150', $this->mustContainLetterRule('Coffin type must include valid description text.')],
            'price' => ['required', 'numeric', 'min:0'],
            'inclusions' => ['required', 'array', 'min:1', $this->atLeastOneValidItemRule('At least one inclusion is required.')],
            'inclusions.*' => ['nullable', 'string', 'max:255', $this->mustContainLetterRule('Inclusion must include valid description text.')],
            'freebies' => ['nullable', 'array'],
            'freebies.*' => ['nullable', 'string', 'max:255', $this->mustContainLetterRule('Freebie must include valid description text.')],
            'promo_label' => ['nullable', 'string', 'max:120'],
            'promo_value_type' => 'nullable|in:AMOUNT,PERCENT',
            'promo_value' => 'nullable|numeric|min:0',
            'promo_starts_at' => 'nullable|date',
            'promo_ends_at' => 'nullable|date|after_or_equal:promo_starts_at',
            'promo_is_active' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    private function packageValidationMessages(): array
    {
        return [
            'name.required' => 'Package name is required.',
            'coffin_type.required' => 'Coffin type is required.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid amount.',
            'price.min' => 'Price cannot be negative.',
            'inclusions.required' => 'At least one inclusion is required.',
            'inclusions.array' => 'At least one inclusion is required.',
            'inclusions.min' => 'At least one inclusion is required.',
            'inclusions.*.max' => 'Inclusion must include valid description text.',
            'freebies.*.max' => 'Freebie must include valid description text.',
            'promo_value.numeric' => 'Promo value must be a valid amount.',
            'promo_value.min' => 'Promo value cannot be negative.',
            'promo_ends_at.after_or_equal' => 'Promo end date must be on or after the start date.',
        ];
    }

    private function trimPackageInput(Request $request): void
    {
        $trimmed = [];

        foreach (['name', 'coffin_type', 'promo_label'] as $field) {
            if ($request->has($field)) {
                $trimmed[$field] = trim(preg_replace('/\s+/', ' ', (string) $request->input($field)));
            }
        }

        foreach (['inclusions', 'freebies'] as $field) {
            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);

            if (is_string($value)) {
                $value = Package::parseLegacyItems($value);
            }

            if (is_array($value)) {
                $trimmed[$field] = array_map(
                    fn ($item) => trim(preg_replace('/\s+/', ' ', (string) $item)),
                    $value
                );
            }
        }

        if ($trimmed !== []) {
            $request->merge($trimmed);
        }
    }

    private function mustContainLetterRule(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if ($value === null || trim((string) $value) === '') {
                return;
            }

            $value = trim((string) $value);

            if (! preg_match('/[\pL\pM]/u', $value) || preg_match('/^\d+(?:\.\d+)?$/', $value)) {
                $fail($message);
            }
        };
    }

    private function atLeastOneValidItemRule(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if (! is_array($value) || $this->cleanPackageItems($value) === []) {
                $fail($message);
            }
        };
    }

    private function cleanPackageItems(array $items): array
    {
        return array_values(array_filter(array_map(
            fn ($item) => trim(preg_replace('/\s+/', ' ', (string) $item)),
            $items
        ), fn ($item) => $item !== ''));
    }

    private function syncPackageItems(Package $package, array $inclusions, array $freebies): void
    {
        $package->packageInclusions()->delete();
        $package->packageFreebies()->delete();

        foreach ($inclusions as $index => $item) {
            $package->packageInclusions()->create([
                'inclusion_name' => $item,
                'sort_order' => $index,
            ]);
        }

        foreach ($freebies as $index => $item) {
            $package->packageFreebies()->create([
                'freebie_name' => $item,
                'sort_order' => $index,
            ]);
        }
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
            'promo_starts_at' => $validated['promo_starts_at'] ?? null,
            'promo_ends_at' => $validated['promo_ends_at'] ?? null,
            'promo_is_active' => true,
        ];
    }
}
