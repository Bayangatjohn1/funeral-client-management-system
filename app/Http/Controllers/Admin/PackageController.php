<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCanViewPackages();

        $user = $request->user();
        $query = Package::query();

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

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'coffin_type' => 'nullable|string|max:150',
            'price' => 'required|numeric|min:0.01',
            'inclusions' => 'nullable|string|max:3000',
            'freebies' => 'nullable|string|max:3000',
            'promo_label' => 'nullable|string|max:120',
            'promo_value_type' => 'nullable|in:AMOUNT,PERCENT',
            'promo_value' => 'nullable|numeric|min:0',
            'promo_starts_at' => 'nullable|date',
            'promo_ends_at' => 'nullable|date|after_or_equal:promo_starts_at',
            'promo_is_active' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $promoPayload = $this->resolvePromoPayload($request, $validated);

        $package = Package::create([
            'name' => $validated['name'],
            'coffin_type' => $validated['coffin_type'] ?? null,
            'price' => $validated['price'],
            'inclusions' => $validated['inclusions'] ?? null,
            'freebies' => $validated['freebies'] ?? null,
            'promo_label' => $promoPayload['promo_label'],
            'promo_value_type' => $promoPayload['promo_value_type'],
            'promo_value' => $promoPayload['promo_value'],
            'promo_starts_at' => $promoPayload['promo_starts_at'],
            'promo_ends_at' => $promoPayload['promo_ends_at'],
            'promo_is_active' => $promoPayload['promo_is_active'],
            'is_active' => $request->boolean('is_active'),
        ]);

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

        return redirect()->route('admin.packages.index')->with('success', 'Package created successfully.');
    }

    public function edit(Package $package)
    {
        $this->ensureCanManagePackages();

        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $this->ensureCanManagePackages();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'coffin_type' => 'nullable|string|max:150',
            'price' => 'required|numeric|min:0.01',
            'inclusions' => 'nullable|string|max:3000',
            'freebies' => 'nullable|string|max:3000',
            'promo_label' => 'nullable|string|max:120',
            'promo_value_type' => 'nullable|in:AMOUNT,PERCENT',
            'promo_value' => 'nullable|numeric|min:0',
            'promo_starts_at' => 'nullable|date',
            'promo_ends_at' => 'nullable|date|after_or_equal:promo_starts_at',
            'promo_is_active' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $promoPayload = $this->resolvePromoPayload($request, $validated);

        $before = [
            'price' => $package->price,
            'promo_label' => $package->promo_label,
            'promo_is_active' => $package->promo_is_active,
        ];

        $package->update([
            'name' => $validated['name'],
            'coffin_type' => $validated['coffin_type'] ?? null,
            'price' => $validated['price'],
            'inclusions' => $validated['inclusions'] ?? null,
            'freebies' => $validated['freebies'] ?? null,
            'promo_label' => $promoPayload['promo_label'],
            'promo_value_type' => $promoPayload['promo_value_type'],
            'promo_value' => $promoPayload['promo_value'],
            'promo_starts_at' => $promoPayload['promo_starts_at'],
            'promo_ends_at' => $promoPayload['promo_ends_at'],
            'promo_is_active' => $promoPayload['promo_is_active'],
            'is_active' => $request->boolean('is_active'),
        ]);

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

        return redirect()->route('admin.packages.index')->with('success', 'Package updated successfully.');
    }

    public function quickUpdatePrice(Request $request, Package $package)
    {
        $this->ensureCanManagePackages();

        $validated = $request->validate([
            'price' => 'required|numeric|min:0.01',
        ]);

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
