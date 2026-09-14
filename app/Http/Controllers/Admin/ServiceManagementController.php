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

        $canManage = $user->isMainAdmin();

        $packagesQuery = Package::query()
            ->with(['packageInclusions.casketCatalog', 'packageFreebies'])
            ->orderBy('name');

        if ($user->isBranchAdmin()) {
            $packagesQuery->where('is_active', true);
        }

        $packages = $packagesQuery->get();
        $caskets = CasketCatalog::query()->orderBy('name')->get();
        $addOns = AddOnCatalog::query()->withCount('legacyPackageAddOns')->orderBy('category')->orderBy('name')->get();
        $freebies = FreebieCatalog::query()->withCount('packageFreebies')->orderBy('name')->get();

        return view('admin.service-management.index', [
            'packages' => $packages,
            'caskets' => $caskets,
            'addOns' => $addOns,
            'freebies' => $freebies,
            'canManage' => $canManage,
            'activeTab' => $request->query('tab', 'packages'),
            'stats' => [
                'packages' => [
                    'total' => $packages->count(),
                    'active' => $packages->where('is_active', true)->count(),
                    'promo' => $packages->where('promo_is_active', true)->count(),
                ],
                'caskets' => [
                    'total' => $caskets->count(),
                    'active' => $caskets->where('is_active', true)->count(),
                    'needs_price' => $caskets->filter(fn ($casket) => (float) $casket->standard_price <= 0)->count(),
                ],
                'add_ons' => [
                    'total' => $addOns->count(),
                    'active' => $addOns->where('is_active', true)->count(),
                    'types' => $addOns->pluck('category')->filter()->unique()->count(),
                ],
                'freebies' => [
                    'total' => $freebies->count(),
                    'active' => $freebies->where('is_active', true)->count(),
                    'used' => $freebies->filter(fn ($freebie) => (int) ($freebie->package_freebies_count ?? 0) > 0)->count(),
                ],
            ],
        ]);
    }
}
