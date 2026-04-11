<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\ClientController;
use App\Http\Controllers\Staff\DeceasedController;
use App\Http\Controllers\Staff\FuneralCaseController;
use App\Http\Controllers\Staff\IntakeController;
use App\Http\Controllers\Staff\PaymentController;
use App\Http\Controllers\Staff\ReminderController;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'no_cache', 'active'])->get('/dashboard', function () {
    $user = auth()->user();

    if ($user->role === 'owner') {
        return redirect()->route('owner.dashboard');
    }

    if ($user->role === 'admin') {
        return redirect('/admin');
    }

    if ($user->role === 'staff') {
        return redirect('/staff');
    }

    return redirect()->route('profile.edit');
})->name('dashboard');


Route::middleware(['auth', 'no_cache', 'active', 'owner'])->group(function () {
    Route::get('/owner', [OwnerDashboardController::class, 'dashboard'])->name('owner.dashboard');
    Route::get('/owner/branch-analytics', [OwnerDashboardController::class, 'analytics'])->name('owner.analytics');
    Route::get('/owner/case-history', [OwnerDashboardController::class, 'history'])->name('owner.history');
    Route::get('/owner/sales-per-branch', [OwnerDashboardController::class, 'salesPerBranch'])->name('owner.sales.index');
    Route::get('/owner/sales-per-branch/export', [OwnerDashboardController::class, 'export'])->name('owner.sales.export');
    Route::get('/owner/cases/{funeral_case}', [OwnerDashboardController::class, 'show'])->name('owner.cases.show');
});

Route::middleware(['auth', 'no_cache', 'active', 'admin', 'branch.scope'])->get('/admin', function (Request $request) {
    $validated = $request->validate([
        'branch_id' => 'nullable|integer|exists:branches,id',
        'date_filter' => 'nullable|in:all,today,this_week,this_month,this_year',
    ]);
    $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
    $dateFilter = $validated['date_filter'] ?? 'this_month';

    $now = now();
    $dateStart = null;
    $dateEnd = null;

    switch ($dateFilter) {
        case 'today':
            $dateStart = $now->copy()->startOfDay();
            $dateEnd = $now->copy()->endOfDay();
            break;
        case 'this_week':
            $dateStart = $now->copy()->startOfWeek();
            $dateEnd = $now->copy()->endOfWeek();
            break;
        case 'this_month':
            $dateStart = $now->copy()->startOfMonth();
            $dateEnd = $now->copy()->endOfMonth();
            break;
        case 'this_year':
            $dateStart = $now->copy()->startOfYear();
            $dateEnd = $now->copy()->endOfYear();
            break;
    }

    $casesQuery = FuneralCase::query()
        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
        ->when($dateStart && $dateEnd, fn ($q) => $q->whereBetween('created_at', [$dateStart, $dateEnd]));
    $totalCases = (clone $casesQuery)->count();
    $totalSales = (clone $casesQuery)->where('payment_status', 'PAID')->sum('total_amount');
    $paidCases = (clone $casesQuery)->where('payment_status', 'PAID')->count();
    $partialCases = (clone $casesQuery)->where('payment_status', 'PARTIAL')->count();
    $unpaidCases = (clone $casesQuery)->where('payment_status', 'UNPAID')->count();
    $totalCollected = (clone $casesQuery)->sum('total_paid');
    $totalOutstanding = (clone $casesQuery)->sum('balance_amount');
    $ongoingCases = (clone $casesQuery)->whereIn('case_status', ['DRAFT', 'ACTIVE'])->count();

    $branches = Branch::orderBy('branch_code')->get();
    $selectedBranches = $branchId
        ? $branches->where('id', $branchId)->values()
        : $branches;

    $branchMetrics = FuneralCase::query()
        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
        ->when($dateStart && $dateEnd, fn ($q) => $q->whereBetween('created_at', [$dateStart, $dateEnd]))
        ->selectRaw(
            "branch_id, COUNT(*) as case_count, SUM(CASE WHEN payment_status = 'PAID' THEN total_amount ELSE 0 END) as paid_sales"
        )
        ->groupBy('branch_id')
        ->get()
        ->keyBy('branch_id');

    $branchRevenueCards = $selectedBranches->map(function ($branch) use ($branchMetrics) {
        $metric = $branchMetrics->get($branch->id);

        return [
            'branch' => $branch,
            'sales' => (float) ($metric->paid_sales ?? 0),
        ];
    });

    $caseVolume = $selectedBranches->map(function ($branch) use ($branchMetrics) {
        $metric = $branchMetrics->get($branch->id);

        return [
            'branch_code' => $branch->branch_code,
            'branch_name' => $branch->branch_name,
            'count' => (int) ($metric->case_count ?? 0),
        ];
    });

    $activeStaffCount = User::where('role', 'staff')
        ->where('is_active', true)
        ->count();
    $activePackageCount = Package::where('is_active', true)->count();

    return view('dashboards.admin', [
        'branchCount' => Branch::count(),
        'userCount' => User::count(),
        'packageCount' => Package::count(),
        'branches' => $branches,
        'selectedBranchId' => $branchId,
        'selectedDateFilter' => $dateFilter,
        'totalCases' => $totalCases,
        'totalSales' => $totalSales,
        'paidCases' => $paidCases,
        'partialCases' => $partialCases,
        'unpaidCases' => $unpaidCases,
        'totalCollected' => $totalCollected,
        'totalOutstanding' => $totalOutstanding,
        'ongoingCases' => $ongoingCases,
        'branchRevenueCards' => $branchRevenueCards,
        'caseVolume' => $caseVolume,
        'activeStaffCount' => $activeStaffCount,
        'activePackageCount' => $activePackageCount,
    ]);
});

Route::middleware(['auth', 'no_cache', 'active', 'staff', 'branch.scope'])->get('/staff', function (Request $request) {
    $user = auth()->user();
    $scopeBranchIds = $user->branchScopeIds();
    $canEncodeAnyBranch = $user->canEncodeAnyBranch();
    $mainBranchId = (int) Branch::whereIn('id', $scopeBranchIds)
        ->where('branch_code', 'BR001')
        ->value('id');
    $dashboardBranchId = $mainBranchId > 0 ? $mainBranchId : (int) $user->branch_id;
    $today = now()->startOfDay();

    $clientCount = Client::where('branch_id', $dashboardBranchId)->count();
    $deceasedCount = Deceased::where('branch_id', $dashboardBranchId)->count();
    $mainCasesBase = FuneralCase::query()
        ->where('branch_id', $dashboardBranchId)
        ->where(function ($query) {
            $query->where('entry_source', 'MAIN')
                ->orWhereNull('entry_source');
        });
    $caseCount = (clone $mainCasesBase)->count();
    $ongoingCount = (clone $mainCasesBase)->whereIn('case_status', ['DRAFT', 'ACTIVE'])->count();
    $unpaidCount = (clone $mainCasesBase)->whereIn('payment_status', ['UNPAID', 'PARTIAL'])->count();
    $partialCount = (clone $mainCasesBase)->where('payment_status', 'PARTIAL')->count();
    $paidCount = (clone $mainCasesBase)->where('payment_status', 'PAID')->count();
    $todayPaidTotal = Payment::where('branch_id', $dashboardBranchId)
        ->whereDate('paid_at', $today->toDateString())
        ->whereHas('funeralCase', function ($query) use ($dashboardBranchId) {
            $query->where('branch_id', $dashboardBranchId)
                ->where(function ($scopeQuery) {
                    $scopeQuery->where('entry_source', 'MAIN')
                        ->orWhereNull('entry_source');
                });
        })
        ->sum('amount');

    $unpaidCases = FuneralCase::with(['client', 'deceased'])
        ->where('branch_id', $dashboardBranchId)
        ->where(function ($query) {
            $query->where('entry_source', 'MAIN')
                ->orWhereNull('entry_source');
        })
        ->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
        ->latest()
        ->take(5)
        ->get();

    $reminderService = app(\App\Services\ReminderService::class);
    $dashboardReminders = $reminderService->buildDashboard($dashboardBranchId, $today);
    $attentionReminders = $dashboardReminders['attention'];
    $todaySchedule = $dashboardReminders['today'];

    $recentCasesCutoff = now()->subDays(2);

    $recentCases = FuneralCase::with(['client', 'deceased'])
        ->where('branch_id', $dashboardBranchId)
        ->where(function ($query) {
            $query->where('entry_source', 'MAIN')
                ->orWhereNull('entry_source');
        })
        ->where('created_at', '>=', $recentCasesCutoff)
        ->latest()
        ->paginate(5, ['*'], 'recent_cases_page')
        ->withQueryString();

    return view('dashboards.staff', compact(
        'clientCount',
        'deceasedCount',
        'caseCount',
        'ongoingCount',
        'unpaidCount',
        'partialCount',
        'paidCount',
        'todayPaidTotal',
        'unpaidCases',
        'todaySchedule',
        'attentionReminders',
        'recentCases',
        'canEncodeAnyBranch'
    ));
});

Route::middleware(['auth', 'no_cache', 'active', 'staff', 'branch.scope'])->group(function () {
    Route::get('intake', [IntakeController::class, 'create'])->name('intake.create');
    Route::post('intake', [IntakeController::class, 'store'])->name('intake.store');
    Route::get('intake/main', [IntakeController::class, 'createMain'])->name('intake.main.create');
    Route::post('intake/main', [IntakeController::class, 'storeMain'])->name('intake.main.store');
    Route::get('intake/other', [IntakeController::class, 'createOther'])->name('intake.other.create');
    Route::post('intake/other', [IntakeController::class, 'storeOther'])->name('intake.other.store');
    Route::resource('clients', ClientController::class)->except(['create', 'store', 'destroy']);
    Route::resource('deceased', DeceasedController::class)->only(['index', 'edit', 'update', 'show']);
    Route::resource('funeral-cases', FuneralCaseController::class);
    Route::get('completed-cases', [FuneralCaseController::class, 'completedIndex'])->name('funeral-cases.completed');
    Route::get('other-branch-reports', [FuneralCaseController::class, 'otherReportsIndex'])->name('funeral-cases.other-reports');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/history', [PaymentController::class, 'history'])->name('payments.history');
    Route::post('payments/pay', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('payments/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');
    Route::get('reminders', [ReminderController::class, 'index'])->name('staff.reminders.index');
});

Route::middleware(['auth', 'no_cache', 'active', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');

    // Edit
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');

    // Activate/Deactivate
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('admin.users.toggleActive');

    // Optional: Reset Password
    Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.resetPassword');

    // Branches
    Route::get('/branches', [BranchController::class, 'index'])->name('admin.branches.index');
    Route::get('/branches/create', [BranchController::class, 'create'])->name('admin.branches.create');
    Route::post('/branches', [BranchController::class, 'store'])->name('admin.branches.store');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('admin.branches.edit');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('admin.branches.update');
    Route::patch('/branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('admin.branches.toggleStatus');

    Route::get('/packages', [PackageController::class, 'index'])->name('admin.packages.index');
    Route::get('/packages/create', [PackageController::class, 'create'])->name('admin.packages.create');
    Route::post('/packages', [PackageController::class, 'store'])->name('admin.packages.store');
    Route::get('/packages/{package}/edit', [PackageController::class, 'edit'])->name('admin.packages.edit');
    Route::put('/packages/{package}', [PackageController::class, 'update'])->name('admin.packages.update');
    Route::patch('/packages/{package}/quick-price', [PackageController::class, 'quickUpdatePrice'])->name('admin.packages.quickPrice');

    // Monitoring
    Route::get('/cases', [ReportController::class, 'masterCases'])->name('admin.cases.index');
    Route::patch('/cases/{funeral_case}/verification', [ReportController::class, 'updateVerification'])->name('admin.cases.verification');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('admin.reports.sales');
    Route::get('/reminders', [ReminderController::class, 'index'])->name('admin.reminders.index');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
    Route::get('/audit-logs/{audit_log}', [AuditLogController::class, 'show'])->name('admin.audit-logs.show');
});

Route::middleware(['auth', 'no_cache', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';
