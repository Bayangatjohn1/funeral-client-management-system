<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOnCatalog;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use App\Models\SystemBackup;
use App\Models\User;

class SystemAdminDashboardController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount([
            'users as active_staff_count' => fn ($q) => $q->where('role', 'staff')->where('is_active', true),
            'users as active_admin_count' => fn ($q) => $q->where('role', 'admin')->where('admin_scope', 'branch')->where('is_active', true),
        ])->orderBy('branch_code')->get();
        $roleCounts = User::where('is_active', true)->selectRaw('role, COUNT(*) total')->groupBy('role')->pluck('total', 'role');
        $adminCounts = [
            'system' => User::where('role', 'admin')->where('admin_scope', 'system')->where('is_active', true)->count(),
            'branch' => User::where('role', 'admin')->where('admin_scope', 'branch')->where('is_active', true)->count(),
        ];
        $unassignedUsers = User::where('is_active', true)->whereIn('role', ['staff'])->whereNull('branch_id')->count();
        $branchesWithoutAdmin = $branches->where('is_active', true)->where('active_admin_count', 0);
        $unavailableCaskets = CasketCatalog::where('is_active', true)->where('is_available', false)->count();
        $lastBackup = SystemBackup::where('status', 'verified')->latest('verified_at')->first();
        $backupOverdue = !$lastBackup || $lastBackup->verified_at->lt(now()->subHours(26));

        $attention = collect();
        foreach ($branchesWithoutAdmin as $branch) $attention->push(['severity' => 'warning', 'message' => $branch->branch_code.' has no enabled Branch Admin account.', 'url' => route('admin.users.index', ['branch_id' => $branch->id, 'role' => 'admin', 'admin_scope' => 'branch'])]);
        if ($unassignedUsers) $attention->push(['severity' => 'warning', 'message' => "$unassignedUsers enabled staff account(s) have no assigned branch.", 'url' => route('admin.users.index', ['role' => 'staff', 'status' => 'active', 'assignment' => 'unassigned'])]);
        if ($backupOverdue) $attention->push(['severity' => 'critical', 'message' => $lastBackup ? 'The latest verified backup is overdue.' : 'No verified system backup exists.', 'url' => route('admin.backups.index')]);

        return view('dashboards.system-admin', [
            'branches' => $branches,
            'roleCounts' => $roleCounts,
            'adminCounts' => $adminCounts,
            'activeBranches' => $branches->where('is_active', true)->count(),
            'activeUsers' => $roleCounts->sum(),
            'activePackages' => Package::where('is_active', true)->count(),
            'activeCaskets' => CasketCatalog::where('is_active', true)->where('is_available', true)->count(),
            'unavailableCaskets' => $unavailableCaskets,
            'activeAddOns' => AddOnCatalog::where('is_active', true)->count(),
            'activeFreebies' => FreebieCatalog::where('is_active', true)->count(),
            'inactiveUsers' => User::where('is_active', false)->count(),
            'unassignedUsers' => $unassignedUsers,
            'attention' => $attention,
            'activities' => AuditLog::with('actor')->latest()->limit(8)->get(),
            'lastBackup' => $lastBackup,
            'backupOverdue' => $backupOverdue,
        ]);
    }
}
