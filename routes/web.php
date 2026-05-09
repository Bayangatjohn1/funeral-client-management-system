<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Staff\ClientController;
use App\Http\Controllers\Staff\DeceasedController;
use App\Http\Controllers\Staff\FuneralCaseController;
use App\Http\Controllers\Staff\IntakeController;
use App\Http\Controllers\Staff\PaymentController;
use App\Http\Controllers\Staff\ReminderController;
use App\Models\AuditLog;
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

    if ($user->isOwner()) {
        return redirect()->route('owner.dashboard');
    }

    if ($user->isAdmin()) {
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
    Route::get('/owner/sales-per-branch', function () {
        return redirect()->route('reports.index', ['report_type' => 'owner_branch_analytics']);
    })->name('owner.sales.index');
    Route::get('/owner/sales-per-branch/export', function (Request $request) {
        return redirect()->route('reports.exportCsv', array_merge(
            $request->query(),
            ['report_type' => 'owner_branch_analytics']
        ));
    })->name('owner.sales.export');
    Route::get('/owner/cases/{funeral_case}', [OwnerDashboardController::class, 'show'])->name('owner.cases.show');
});

Route::middleware(['auth', 'no_cache', 'active'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/preview', [ReportController::class, 'preview'])->name('reports.preview');
    Route::get('/reports/print', [ReportController::class, 'print'])->name('reports.print');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.exportPdf');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.exportCsv');
});

Route::middleware(['auth', 'no_cache', 'active', 'admin', 'branch.scope'])->get('/admin', function (Request $request) {
    $user = $request->user();
    $branchScopeIds = $user->branchScopeIds();
    $isBranchAdmin = $user->isBranchAdmin();
    $isMainAdmin = $user->isMainBranchAdmin();
    $validated = $request->validate([
        'branch_id' => 'nullable|integer|exists:branches,id',
        'date_filter' => 'nullable|in:all,today,this_week,this_month,this_year',
    ]);
    $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
    if ($isBranchAdmin) {
        $branchId = (int) $user->branch_id;
    }
    if ($branchId && $branchScopeIds !== null && !in_array($branchId, $branchScopeIds, true)) {
        abort(403, 'Branch is outside your admin scope.');
    }
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

    $paymentDateScope = function ($query) use ($dateStart, $dateEnd) {
        if (!$dateStart || !$dateEnd) {
            return;
        }

        $query->where(function ($dateQuery) use ($dateStart, $dateEnd) {
            $dateQuery->whereBetween('paid_at', [$dateStart, $dateEnd])
                ->orWhere(function ($fallback) use ($dateStart, $dateEnd) {
                    $fallback->whereNull('paid_at')
                        ->whereBetween('paid_date', [$dateStart->toDateString(), $dateEnd->toDateString()]);
                });
        });
    };

    $casesQuery = FuneralCase::query()
        ->when($branchScopeIds !== null, fn ($q) => $q->whereIn('branch_id', $branchScopeIds))
        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
        ->when($dateStart && $dateEnd, fn ($q) => $q->whereBetween('created_at', [$dateStart, $dateEnd]));
    $totalCases = (clone $casesQuery)->count();
    $totalSales = (clone $casesQuery)->where('payment_status', 'PAID')->sum('total_amount');
    $totalServiceValue = (clone $casesQuery)->sum('total_amount');
    $paidCases = (clone $casesQuery)->where('payment_status', 'PAID')->count();
    $partialCases = (clone $casesQuery)->where('payment_status', 'PARTIAL')->count();
    $unpaidCases = (clone $casesQuery)->where('payment_status', 'UNPAID')->count();
    $totalCollected = Payment::query()
        ->when($branchScopeIds !== null, fn ($q) => $q->whereIn('branch_id', $branchScopeIds))
        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
        ->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'VOID');
        })
        ->when($dateStart && $dateEnd, $paymentDateScope)
        ->sum('amount');
    $totalOutstanding = (clone $casesQuery)->sum('balance_amount');
    $ongoingCases = (clone $casesQuery)->whereIn('case_status', ['DRAFT', 'ACTIVE'])->count();

    $branches = Branch::query()
        ->when($branchScopeIds !== null, fn ($query) => $query->whereIn('id', $branchScopeIds))
        ->orderBy('branch_code')
        ->get();
    $selectedBranches = $branchId
        ? $branches->where('id', $branchId)->values()
        : $branches;

    $branchMetrics = FuneralCase::query()
        ->when($branchScopeIds !== null, fn ($q) => $q->whereIn('branch_id', $branchScopeIds))
        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
        ->when($dateStart && $dateEnd, fn ($q) => $q->whereBetween('created_at', [$dateStart, $dateEnd]))
        ->selectRaw(
            "branch_id, COUNT(*) as case_count, COALESCE(SUM(total_amount), 0) as service_value, COALESCE(SUM(total_paid), 0) as collected_amount"
        )
        ->groupBy('branch_id')
        ->get()
        ->keyBy('branch_id');

    $branchPaymentMetrics = Payment::query()
        ->when($branchScopeIds !== null, fn ($q) => $q->whereIn('branch_id', $branchScopeIds))
        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
        ->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'VOID');
        })
        ->when($dateStart && $dateEnd, $paymentDateScope)
        ->selectRaw('branch_id, COALESCE(SUM(amount), 0) as collected_amount')
        ->groupBy('branch_id')
        ->get()
        ->keyBy('branch_id');

    $branchRevenueCards = $selectedBranches->map(function ($branch) use ($branchMetrics, $branchPaymentMetrics) {
        $metric = $branchMetrics->get($branch->id);
        $paymentMetric = $branchPaymentMetrics->get($branch->id);

        return [
            'branch' => $branch,
            'sales' => (float) ($metric->service_value ?? 0),
            'collected' => (float) ($paymentMetric->collected_amount ?? 0),
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
        ->when($branchScopeIds !== null, fn ($query) => $query->whereIn('branch_id', $branchScopeIds))
        ->where('is_active', true)
        ->count();
    $activePackageCount = Package::where('is_active', true)->count();
    $dashboardBranch = $branchId ? $branches->firstWhere('id', $branchId) : $user->branch;
    $auditLogs = AuditLog::with(['actor:id,name,role', 'branch:id,branch_code,branch_name'])
        ->when($branchScopeIds !== null, function ($query) use ($branchScopeIds) {
            $query->where(function ($scope) use ($branchScopeIds) {
                $scope->whereIn('branch_id', $branchScopeIds)
                    ->orWhereIn('target_branch_id', $branchScopeIds);
            });
        })
        ->latest()
        ->take(5)
        ->get();

    $todaySchedule = collect();
    $attentionReminders = collect();
    if ($isBranchAdmin && $branchId) {
        $dashboardReminders = app(\App\Services\ReminderService::class)->buildDashboard($branchId, now()->startOfDay());
        $todaySchedule = $dashboardReminders['today'] ?? collect();
        $attentionReminders = $dashboardReminders['attention'] ?? collect();
    }

    return view('dashboards.admin', [
        'branchCount' => $branches->count(),
        'userCount' => User::when($branchScopeIds !== null, fn ($query) => $query->whereIn('branch_id', $branchScopeIds))->count(),
        'packageCount' => Package::count(),
        'branches' => $branches,
        'selectedBranchId' => $branchId,
        'selectedDateFilter' => $dateFilter,
        'totalCases' => $totalCases,
        'totalSales' => $totalSales,
        'totalServiceValue' => $totalServiceValue,
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
        'isMainAdmin' => $isMainAdmin,
        'isBranchAdmin' => $isBranchAdmin,
        'dashboardBranch' => $dashboardBranch,
        'dashboardDateStart' => $dateStart,
        'dashboardDateEnd' => $dateEnd,
        'auditLogs' => $auditLogs,
        'todaySchedule' => $todaySchedule,
        'attentionReminders' => $attentionReminders,
    ]);
});

Route::middleware(['auth', 'no_cache', 'active', 'staff', 'branch.scope'])->get('/staff', function (Request $request) {
    $user = auth()->user();
    $dashboardBranchId = (int) ($user->operationalBranchId() ?? 0);
    $canEncodeAnyBranch = $user->canEncodeAnyBranch();
    $dashboardBranch = Branch::select(['id', 'branch_code', 'branch_name'])->find($dashboardBranchId);
    $today = now()->startOfDay();

    $clientCount = Client::where('branch_id', $dashboardBranchId)->count();
    $deceasedCount = Deceased::where('branch_id', $dashboardBranchId)->count();
    $mainCasesBase = FuneralCase::query()
        ->where('branch_id', $dashboardBranchId)
        ->where(function ($query) {
            $query->where('entry_source', 'MAIN')
                ->orWhereNull('entry_source');
        });
    $caseSummary = (clone $mainCasesBase)
        ->selectRaw('COUNT(*) as total')
        ->selectRaw("SUM(CASE WHEN case_status IN ('DRAFT', 'ACTIVE') THEN 1 ELSE 0 END) as ongoing")
        ->selectRaw("SUM(CASE WHEN payment_status IN ('UNPAID', 'PARTIAL') THEN 1 ELSE 0 END) as unpaid")
        ->selectRaw("SUM(CASE WHEN payment_status = 'PARTIAL' THEN 1 ELSE 0 END) as partial")
        ->selectRaw("SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END) as paid")
        ->first();
    $caseCount    = (int) ($caseSummary->total   ?? 0);
    $ongoingCount = (int) ($caseSummary->ongoing ?? 0);
    $unpaidCount  = (int) ($caseSummary->unpaid  ?? 0);
    $partialCount = (int) ($caseSummary->partial ?? 0);
    $paidCount    = (int) ($caseSummary->paid    ?? 0);
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

    $currentMonthStart = now()->startOfMonth();
    $currentMonthEnd = now()->endOfMonth();
    $monthCasesEncoded = (clone $mainCasesBase)
        ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
        ->count();
    $monthPaymentsCollected = Payment::where('branch_id', $dashboardBranchId)
        ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
        ->whereHas('funeralCase', function ($query) use ($dashboardBranchId) {
            $query->where('branch_id', $dashboardBranchId)
                ->where(function ($scopeQuery) {
                    $scopeQuery->where('entry_source', 'MAIN')
                        ->orWhereNull('entry_source');
                });
        })
        ->sum('amount');
    $outstandingBalanceTotal = (clone $mainCasesBase)
        ->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
        ->sum('balance_amount');

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

    $recentCases = FuneralCase::with(['client', 'deceased'])
        ->where('branch_id', $dashboardBranchId)
        ->where(function ($query) {
            $query->where('entry_source', 'MAIN')
                ->orWhereNull('entry_source');
        })
        ->latest()
        ->paginate(5, ['*'], 'recent_cases_page')
        ->withQueryString();

    $recentPayments = Payment::with([
            'funeralCase:id,case_code,client_id,deceased_id,payment_status,balance_amount',
            'funeralCase.client:id,full_name',
            'funeralCase.deceased:id,full_name',
        ])
        ->where('branch_id', $dashboardBranchId)
        ->whereHas('funeralCase', function ($query) use ($dashboardBranchId) {
            $query->where('branch_id', $dashboardBranchId)
                ->where(function ($scopeQuery) {
                    $scopeQuery->where('entry_source', 'MAIN')
                        ->orWhereNull('entry_source');
                });
        })
        ->orderByDesc('paid_at')
        ->orderByDesc('id')
        ->take(5)
        ->get();

    $upcomingSchedule = $reminderService
        ->buildFullList($dashboardBranchId, [], $today)
        ->whereIn('type', ['upcoming_service', 'upcoming_interment'])
        ->sortBy('sort_date')
        ->unique('case_id')
        ->take(6)
        ->values();

    return view('dashboards.staff', compact(
        'dashboardBranch',
        'clientCount',
        'deceasedCount',
        'caseCount',
        'ongoingCount',
        'unpaidCount',
        'partialCount',
        'paidCount',
        'todayPaidTotal',
        'monthCasesEncoded',
        'monthPaymentsCollected',
        'outstandingBalanceTotal',
        'unpaidCases',
        'todaySchedule',
        'upcomingSchedule',
        'attentionReminders',
        'recentCases',
        'recentPayments',
        'canEncodeAnyBranch'
    ));
});

Route::middleware(['auth', 'no_cache', 'active'])->get(
    'funeral-cases/{funeral_case}',
    [FuneralCaseController::class, 'show']
)->name('funeral-cases.show');

Route::middleware(['auth', 'no_cache', 'active', 'staff', 'branch.scope'])->group(function () {
    Route::get('intake', [IntakeController::class, 'create'])->name('intake.create');
    Route::post('intake', [IntakeController::class, 'store'])->name('intake.store');
    Route::get('intake/main', [IntakeController::class, 'createMain'])->name('intake.main.create');
    Route::post('intake/main', [IntakeController::class, 'storeMain'])->name('intake.main.store');
    Route::get('intake/other', [IntakeController::class, 'createOther'])->name('intake.other.create');
    Route::post('intake/other', [IntakeController::class, 'storeOther'])->name('intake.other.store');
    Route::resource('clients', ClientController::class)->except(['create', 'store', 'destroy']);
    Route::get('deceased', [DeceasedController::class, 'index'])->name('deceased.index');
    Route::resource('deceased', DeceasedController::class)->only(['edit', 'update', 'show']);
    Route::resource('funeral-cases', FuneralCaseController::class)->except(['show']);
    Route::get('completed-cases', [FuneralCaseController::class, 'completedIndex'])->name('funeral-cases.completed');
    Route::get('other-branch-reports', [FuneralCaseController::class, 'otherReportsIndex'])->name('funeral-cases.other-reports');
    Route::get('reminders', [ReminderController::class, 'index'])->name('staff.reminders.index');
});

Route::middleware(['auth', 'no_cache', 'active'])->get('payments/history', [PaymentController::class, 'history'])->name('payments.history');

Route::middleware(['auth', 'no_cache', 'active', 'staff', 'branch.scope'])->group(function () {
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments/pay', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('payments/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');
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
});

Route::middleware(['auth', 'no_cache', 'active', 'main_admin'])->prefix('admin')->group(function () {
    // Branches
    Route::get('/branches', [BranchController::class, 'index'])->name('admin.branches.index');
    Route::get('/branches/create', [BranchController::class, 'create'])->name('admin.branches.create');
    Route::post('/branches', [BranchController::class, 'store'])->name('admin.branches.store');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('admin.branches.edit');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('admin.branches.update');
    Route::patch('/branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('admin.branches.toggleStatus');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
    Route::get('/audit-logs/{audit_log}', [AuditLogController::class, 'show'])->name('admin.audit-logs.show');
});

Route::middleware(['auth', 'no_cache', 'active', 'admin', 'branch.scope'])->prefix('admin')->group(function () {
    Route::get('/packages', [PackageController::class, 'index'])->name('admin.packages.index');
    Route::get('/packages/create', [PackageController::class, 'create'])->name('admin.packages.create');
    Route::post('/packages', [PackageController::class, 'store'])->name('admin.packages.store');
    Route::get('/packages/{package}/edit', [PackageController::class, 'edit'])->name('admin.packages.edit');
    Route::put('/packages/{package}', [PackageController::class, 'update'])->name('admin.packages.update');
    Route::patch('/packages/{package}/quick-price', [PackageController::class, 'quickUpdatePrice'])->name('admin.packages.quickPrice');

    // Monitoring
    Route::get('/cases', [AdminReportController::class, 'masterCases'])->name('admin.cases.index');
    Route::patch('/cases/{funeral_case}/verification', [AdminReportController::class, 'updateVerification'])->name('admin.cases.verification');
    Route::get('/payments', [PaymentController::class, 'history'])->name('admin.payments.index');
    Route::get('/payment-monitoring', [PaymentController::class, 'history'])->name('admin.payment-monitoring');
    Route::get('/reports/sales', [AdminReportController::class, 'sales'])->name('admin.reports.sales');
    Route::get('/reminders', [ReminderController::class, 'index'])->name('admin.reminders.index');
});

Route::middleware(['auth', 'no_cache', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';
