<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOnCatalog;
use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use Illuminate\Http\Request;

class ServiceManagementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || (! $user->isMainAdmin() && ! $user->isBranchAdmin())) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'tab' => 'nullable|in:packages,caskets,addons,freebies',
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,archived,all,available,unavailable,out_of_stock',
        ]);

        $canManage = $user->isMainAdmin();
        $activeTab = $validated['tab'] ?? 'packages';
        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? ($activeTab === 'caskets' ? 'available' : 'active');

        $packagesQuery = Package::query()
            ->with(['packageInclusions.casketCatalog', 'packageFreebies'])
            ->orderBy('name');

        $packageStatsQuery = clone $packagesQuery;
        $casketsQuery = CasketCatalog::query()->orderBy('name');
        $addOnsQuery = AddOnCatalog::query()->withCount('legacyPackageAddOns')->orderBy('category')->orderBy('name');
        $freebiesQuery = FreebieCatalog::query()->withCount('packageFreebies')->orderBy('name');

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
                })
                ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'));
        } elseif ($activeTab === 'caskets') {
            $casketsQuery
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('type_or_material', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($status === 'available', fn ($query) => $query->where('is_active', true)->where('is_available', true))
                ->when(in_array($status, ['unavailable', 'out_of_stock'], true), fn ($query) => $query->where('is_active', true)->where('is_available', false))
                ->when($status === 'archived', fn ($query) => $query->where('is_active', false));
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
                ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'));
        } else {
            $freebiesQuery
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('default_unit', 'like', "%{$search}%");
                    });
                })
                ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'));
        }

        $packages = $packagesQuery->paginate(20, ['*'], 'packages_page')->withQueryString();
        $caskets = $casketsQuery->paginate(20, ['*'], 'caskets_page')->withQueryString();
        $addOns = $addOnsQuery->paginate(20, ['*'], 'addons_page')->withQueryString();
        $freebies = $freebiesQuery->paginate(20, ['*'], 'freebies_page')->withQueryString();

        $packageStats = (clone $packageStatsQuery)
            ->reorder()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN promo_is_active = 1 THEN 1 ELSE 0 END) as promo')
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
            'canManage' => $canManage,
            'activeTab' => $activeTab,
            'search' => $search,
            'status' => $status,
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
            ],
        ]);
    }
}
