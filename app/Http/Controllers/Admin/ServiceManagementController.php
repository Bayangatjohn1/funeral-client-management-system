<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOnCatalog;
use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\HearseCatalog;
use App\Models\Package;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceManagementController extends Controller
{
    public function updateCustomPackageDefaults(Request $request)
    {
        abort_unless($request->user()?->isMainAdmin(), 403);
        $validated = $request->validate([
            'embalming_days' => ['required', 'integer', 'min:1', 'max:100000'],
            'home_viewing_days' => ['required', 'integer', 'min:1', 'max:100000'],
            'retrieval_kilometers' => ['required', 'numeric', 'min:0', 'max:100000'],
            'hearse_kilometers' => ['nullable', 'numeric', 'min:0', 'max:100000', 'decimal:0,2'],
            'embalming_extra_rate' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'home_viewing_extra_rate' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'retrieval_extra_rate' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'hearse_extra_rate' => ['required_with:hearse_kilometers', 'nullable', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
        ]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $defaults = \App\Models\CustomPackageDefault::find(1) ?? new \App\Models\CustomPackageDefault;
            $before = ['coverage' => $defaults->coverage(), 'rates' => $defaults->rates()];
            $defaults->id = 1;
            $defaults->fill($validated)->save();
            \App\Support\AuditLogger::log('custom_package_defaults.updated', 'update', 'custom_package_defaults', 1, [
                'before' => $before, 'after' => ['coverage' => $defaults->coverage(), 'rates' => $defaults->rates()],
            ]);
        });
        return redirect()->route('admin.service-management.index', ['tab' => 'packages'])->with('success', 'Custom package defaults updated. Existing case records are unchanged.');
    }

    public function printPreview(Request $request)
    {
        return $this->index($request, true);
    }

    public function index(Request $request, bool $printing = false)
    {
        $user = $request->user();
        if ($printing) abort_unless($user?->isMainAdmin(), 403);
        $printOptions = $printing ? $request->validate([
            'scope' => ['nullable', 'in:all,filtered,selected'],
            'ids' => ['nullable', 'array', 'max:500'],
            'ids.*' => ['integer', 'min:1', 'distinct'],
        ]) : [];

        if (! $user || (! $user->isMainAdmin() && ! $user->isBranchAdmin())) {
            abort(403, 'Unauthorized');
        }

        // Keep old unavailable-dependency links useful while the condition is
        // now part of the single Package Status filter.
        $legacyCatalogFilter = $request->input('catalog_items');
        $legacyCatalogValues = is_array($legacyCatalogFilter) ? $legacyCatalogFilter : [$legacyCatalogFilter];
        if (! $request->filled('package_status') && in_array('unavailable', $legacyCatalogValues, true)) {
            $request->merge(['package_status' => 'active_unavailable']);
        }

        $validated = $request->validate([
            'tab' => 'nullable|in:packages,caskets,addons,freebies,hearses,promotions',
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive,archived,all,available,unavailable,out_of_stock',
            'sort' => 'nullable|in:name_asc,name_desc,latest,oldest,discount_asc,discount_desc,start_date,end_date',
            'availability' => 'nullable|in:all,available,unavailable',
            'category' => 'nullable|string|max:100',
            'package_status' => 'nullable|in:all,active,active_unavailable,archived',
            'promotion_status' => 'nullable|in:all,incomplete_setup,scheduled,active,expired,turned_off',
        ]);

        $canManage = $user->isMainAdmin();
        $activeTab = $validated['tab'] ?? 'packages';
        $search = trim((string) ($validated['q'] ?? ''));
        $statusCatalogTab = in_array($activeTab, ['caskets', 'hearses', 'freebies', 'addons'], true);
        $status = $validated['status'] ?? ($statusCatalogTab ? 'all' : 'active');
        // Keep legacy query links working while exposing one combined Status filter.
        $legacyAvailability = $validated['availability'] ?? null;
        if ($statusCatalogTab && in_array($status, ['inactive', 'out_of_stock'], true)) {
            if ($status === 'inactive') $status = 'archived';
            elseif ($status === 'out_of_stock') $status = 'unavailable';
        } elseif ($statusCatalogTab && $status === 'active' && in_array($legacyAvailability, ['available', 'unavailable'], true)) {
            $status = $legacyAvailability;
        }
        $availability = 'all';
        // Keep old package status URLs valid while the Packages toolbar uses
        // separate, clearly named filter categories.
        $packageStatus = $validated['package_status']
            ?? (in_array($validated['status'] ?? null, ['active', 'archived', 'all'], true) ? $validated['status'] : 'all');
        $promotionStatus = $activeTab === 'promotions' ? ($validated['promotion_status'] ?? 'all') : 'all';
        if ($printing && ($printOptions['scope'] ?? 'all') !== 'filtered') {
            $search = ''; $status = 'all'; $packageStatus = 'all'; $promotionStatus = 'all';
        }

        $packagesQuery = Package::query()
            ->with(['packageInclusions.casketCatalog', 'packageInclusions.hearseCatalog', 'packageFreebies.catalog', 'promotion'])
            ->orderBy('name');

        $packageStatsQuery = clone $packagesQuery;
        $casketsQuery = CasketCatalog::query()->with('packageInclusions.package')->orderBy('name');
        $addOnsQuery = AddOnCatalog::query()->with('legacyPackageAddOns.package')->withCount('legacyPackageAddOns')->orderBy('category')->orderBy('name');
        if ($user->isBranchAdmin()) $addOnsQuery->where(fn ($q) => $q->where('category', '<>', AddOnCatalog::CHAPEL_VIEWING)->orWhere('branch_id', $user->branch_id));
        $freebiesQuery = FreebieCatalog::query()->with('packageFreebies.package')->withCount('packageFreebies')->orderBy('name');
        $hearsesQuery = HearseCatalog::query()->with('packageInclusions.package')->orderBy('name');
        $promotionsQuery = Promotion::query()->with('package');

        if ($activeTab === 'packages') {
            $packagesQuery
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('short_description', 'like', "%{$search}%")
                            ->orWhere('coffin_type', 'like', "%{$search}%")
                            ->orWhereHas('packageInclusions', fn ($inclusionQuery) => $inclusionQuery->where('inclusion_name', 'like', "%{$search}%"))
                            ->orWhereHas('packageFreebies', fn ($freebieQuery) => $freebieQuery->where('freebie_name', 'like', "%{$search}%"));
                    });
                });
        } elseif ($activeTab === 'caskets') {
            $casketsQuery
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('type_or_material', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($statusCatalogTab, fn ($query) => $this->applyCatalogStatus($query, $status))
                ->when(! $statusCatalogTab && $status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'));
        } elseif ($activeTab === 'addons') {
            $addOnsQuery
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%")
                            ->orWhere('unit', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when(filled($validated['category'] ?? null), fn ($query) => $query->where('category', $validated['category']))
                ->when($statusCatalogTab, fn ($query) => $this->applyCatalogStatus($query, $status))
                ->when(! $statusCatalogTab && $status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'));
        } elseif ($activeTab === 'freebies') {
            $freebiesQuery
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('default_unit', 'like', "%{$search}%");
                    });
                })
                ->when($statusCatalogTab, fn ($query) => $this->applyCatalogStatus($query, $status))
                ->when(! $statusCatalogTab && $status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'));
        } else {
            $hearsesQuery
                ->when($search !== '', fn ($query) => $query->where(fn ($searchQuery) => $searchQuery->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
                ->when($statusCatalogTab, fn ($query) => $this->applyCatalogStatus($query, $status))
                ->when(! $statusCatalogTab && $status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'));
        }

        if ($activeTab === 'promotions' && $search !== '') {
            $promotionsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('discount_type', 'like', "%{$search}%")
                    ->orWhere('discount_value', 'like', "%{$search}%")
                    ->orWhereHas('package', fn ($packageQuery) => $packageQuery->where('name', 'like', "%{$search}%"));
            });
        }

        foreach ([$packagesQuery, $casketsQuery, $addOnsQuery, $freebiesQuery, $hearsesQuery] as $catalogQuery) {
            $catalogQuery->reorder();
            match ($validated['sort'] ?? 'name_asc') {
                'latest' => $catalogQuery->orderByDesc('created_at')->orderByDesc('id'),
                'oldest' => $catalogQuery->orderBy('created_at')->orderBy('id'),
                'name_desc' => $catalogQuery->orderByDesc('name')->orderBy('id'),
                default => $catalogQuery->orderBy('name')->orderBy('id'),
            };
        }
        if ($printing) {
            $printQuery = match ($activeTab) {
                'packages' => $packagesQuery, 'caskets' => $casketsQuery, 'addons' => $addOnsQuery,
                'freebies' => $freebiesQuery, 'hearses' => $hearsesQuery, 'promotions' => $promotionsQuery,
            };
            if (($activeTab === 'packages' && in_array($packageStatus, ['archived', 'active_unavailable'], true))
                || ($activeTab === 'promotions' && !in_array($promotionStatus, ['all', 'active'], true))) $printQuery->whereRaw('1 = 0');
            return app(\App\Support\ServiceBrochure::class)->preview($request, $activeTab, $printQuery, $printOptions);
        }
        if ($activeTab === 'packages' && $packageStatus !== 'all') {
            $matchingPackages = $packagesQuery->get()->filter(function (Package $package) use ($packageStatus) {
                if ($packageStatus === 'active' && ! (bool) $package->is_active) return false;
                if ($packageStatus === 'archived' && (bool) $package->is_active) return false;
                if ($packageStatus === 'active_unavailable'
                    && (! (bool) $package->is_active || ! $this->hasUnavailableRequiredCatalogItem($package))) return false;
                return true;
            })->values();

            $perPage = 20;
            $page = LengthAwarePaginator::resolveCurrentPage('packages_page');
            $packages = new LengthAwarePaginator(
                $matchingPackages->forPage($page, $perPage)->values(),
                $matchingPackages->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'packages_page']
            );
        } else {
            $packages = $packagesQuery->paginate(20, ['*'], 'packages_page')->withQueryString();
        }
        $caskets = $casketsQuery->paginate(20, ['*'], 'caskets_page')->withQueryString();
        $addOns = $addOnsQuery->paginate(20, ['*'], 'addons_page')->withQueryString();
        $freebies = $freebiesQuery->paginate(20, ['*'], 'freebies_page')->withQueryString();
        $hearses = $hearsesQuery->paginate(20, ['*'], 'hearses_page')->withQueryString();
        $allPromotionRows = $promotionsQuery->get();
        $hasIncompleteSetupPromotions = $allPromotionRows->contains(
            fn (Promotion $promotion) => $promotion->statusAt(now('Asia/Manila'), $promotion->package) === 'Incomplete Setup'
        );
        $promotionRows = $allPromotionRows->filter(function (Promotion $promotion) use ($promotionStatus) {
            if ($promotionStatus === 'all') return true;
            $statusKey = [
                'Incomplete Setup' => 'incomplete_setup',
                'Scheduled' => 'scheduled',
                'Active' => 'active',
                'Expired' => 'expired',
                'Turned Off' => 'turned_off',
            ][$promotion->statusAt(now('Asia/Manila'), $promotion->package)] ?? '';
            return $statusKey === $promotionStatus;
        });
        $promotionRows = match ($validated['sort'] ?? 'name_asc') {
            'name_desc' => $promotionRows->sortByDesc(fn ($row) => mb_strtolower($row->name ?? '')),
            'latest' => $promotionRows->sortByDesc('created_at'),
            'oldest' => $promotionRows->sortBy('created_at'),
            'discount_asc' => $promotionRows->sortBy(fn ($row) => (float) $row->discount_value),
            'discount_desc' => $promotionRows->sortByDesc(fn ($row) => (float) $row->discount_value),
            'start_date' => $promotionRows->sortBy(fn ($row) => $row->starts_at?->format('Y-m-d') ?? '0000-00-00'),
            'end_date' => $promotionRows->sortBy(fn ($row) => $row->ends_at?->format('Y-m-d') ?? '9999-12-31'),
            default => $promotionRows->sortBy(fn ($row) => mb_strtolower($row->name ?? '')),
        };
        $promotionRows = $promotionRows->values();
        $promotionPage = LengthAwarePaginator::resolveCurrentPage('promotions_page');
        $promotions = new LengthAwarePaginator(
            $promotionRows->forPage($promotionPage, 20)->values(),
            $promotionRows->count(),
            20,
            $promotionPage,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'promotions_page']
        );

        $packageStats = (clone $packageStatsQuery)
            ->reorder()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('0 as promo')
            ->first();
        $casketStats = CasketCatalog::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN standard_price <= 0 THEN 1 ELSE 0 END) as needs_price')
            ->first();
        $addOnStats = AddOnCatalog::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('COUNT(DISTINCT category) as types')
            ->first();
        $freebieStats = FreebieCatalog::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->first();

        return view('admin.service-management.index', [
            'packages' => $packages,
            'caskets' => $caskets,
            'addOns' => $addOns,
            'freebies' => $freebies,
            'hearses' => $hearses,
            'canManage' => $canManage,
            'activeTab' => $activeTab,
            'search' => $search,
            'status' => $status,
            'availability' => $availability,
            'packageStatus' => $packageStatus,
            'promotionStatus' => $promotionStatus,
            'hasIncompleteSetupPromotions' => $hasIncompleteSetupPromotions,
            'stats' => [
                'packages' => [
                    'total' => (int) ($packageStats->total ?? 0),
                    'active' => (int) ($packageStats->active ?? 0),
                    'promo' => (int) ($packageStats->promo ?? 0),
                ],
                'caskets' => [
                    'total' => (int) ($casketStats->total ?? 0),
                    'active' => (int) ($casketStats->active ?? 0),
                    'needs_price' => (int) ($casketStats->needs_price ?? 0),
                ],
                'add_ons' => [
                    'total' => (int) ($addOnStats->total ?? 0),
                    'active' => (int) ($addOnStats->active ?? 0),
                    'types' => (int) ($addOnStats->types ?? 0),
                ],
                'freebies' => [
                    'total' => (int) ($freebieStats->total ?? 0),
                    'active' => (int) ($freebieStats->active ?? 0),
                    'used' => FreebieCatalog::query()->whereHas('packageFreebies')->count(),
                ],
                'hearses' => [
                    'total' => HearseCatalog::count(),
                    'active' => HearseCatalog::where('is_active', true)->count(),
                ],
                'promotions' => [
                    'total' => Promotion::count(),
                    'active' => Promotion::where('is_enabled', true)->count(),
                ],
            ],
            'promotions' => $promotions,
        ]);
    }

    private function applyCatalogStatus($query, string $status): void
    {
        match ($status) {
            'active' => $query->where('is_active', true),
            'available' => $query->where('is_active', true)->where('is_available', true),
            'unavailable' => $query->where('is_active', true)->where('is_available', false),
            'archived' => $query->where('is_active', false),
            default => null,
        };
    }

    private function hasUnavailableRequiredCatalogItem(Package $package): bool
    {
        foreach ($package->packageInclusions as $inclusion) {
            $catalog = match ($inclusion->service_type) {
                Package::SERVICE_CASKET => $inclusion->casketCatalog,
                Package::SERVICE_HEARSE => $inclusion->hearseCatalog,
                default => null,
            };

            // Archived items have their own catalog state and are not treated
            // as currently Unavailable. Optional add-ons are intentionally ignored.
            if ($catalog && (bool) $catalog->is_active && ! (bool) $catalog->is_available) {
                return true;
            }
        }

        foreach ($package->packageFreebies as $freebie) {
            $catalog = $freebie->catalog;
            if ($catalog && (bool) $catalog->is_active && ! (bool) $catalog->is_available) {
                return true;
            }
        }

        return false;
    }
}
