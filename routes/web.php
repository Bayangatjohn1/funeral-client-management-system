<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\AddOnCatalogController;
use App\Http\Controllers\Admin\CasketCatalogController;
use App\Http\Controllers\Admin\FreebieCatalogController;
use App\Http\Controllers\Admin\HearseCatalogController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ServiceManagementController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BackupRecoveryController;
use App\Http\Controllers\Admin\RetentionController;
use App\Http\Controllers\Admin\SystemAdminDashboardController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentCorrectionController;
use App\Http\Controllers\NotificationStateController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Staff\CaseAttachmentController;
use App\Http\Controllers\Staff\CaseDocumentController;
use App\Http\Controllers\Staff\ClientController;
use App\Http\Controllers\Staff\DeceasedController;
use App\Http\Controllers\Staff\IntakeDraftController;
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

// Phone links grant a short-lived upload capability without a staff login.
Route::middleware(['signed', 'throttle:30,1'])->withoutMiddleware([
    \App\Http\Middleware\EnsureIdleSession::class, \App\Http\Middleware\EnsureAccountSecurity::class,
])->group(function () {
    Route::get('/payment-proof/phone/{upload}', [PaymentProofController::class, 'phone'])->name('payment-proof.phone');
    Route::post('/payment-proof/phone/{upload}', [PaymentProofController::class, 'phoneUpload'])->name('payment-proof.phone-upload');
});
Route::middleware(['auth', 'no_cache', 'active', 'throttle:90,1'])->prefix('payment-proof')->name('payment-proof.')->group(function () {
    Route::post('/sessions', [PaymentProofController::class, 'store'])->name('store');
    Route::get('/sessions/{upload}', [PaymentProofController::class, 'status'])->name('status');
    Route::post('/sessions/{upload}/file', [PaymentProofController::class, 'desktopUpload'])->name('upload');
    Route::delete('/sessions/{upload}', [PaymentProofController::class, 'cancel'])->name('cancel');
    Route::get('/sessions/{upload}/preview', [PaymentProofController::class, 'preview'])->name('preview');
    Route::get('/payments/{payment}', [PaymentProofController::class, 'payment'])->name('payment');
    Route::post('/payments/{payment}', [PaymentProofController::class, 'attach'])->name('attach');
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
    Route::get('/owner/dashboard/details', [OwnerDashboardController::class, 'dashboardDetails'])->name('owner.dashboard.details');
    Route::get('/owner/collections', [OwnerDashboardController::class, 'collections'])->name('owner.collections');
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

Route::middleware(['auth', 'no_cache', 'active', 'admin', 'main_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/backup-recovery', [BackupRecoveryController::class, 'index'])->name('backups.index');
    Route::post('/backup-recovery', [BackupRecoveryController::class, 'store'])->name('backups.store');
    Route::post('/backup-recovery/{systemBackup}/verify', [BackupRecoveryController::class, 'verify'])->name('backups.verify');
    Route::get('/backup-recovery/{systemBackup}/download', [BackupRecoveryController::class, 'download'])->name('backups.download');
    Route::post('/backup-recovery/{systemBackup}/restore-request', [BackupRecoveryController::class, 'requestRestore'])->name('backups.restore-request');
    Route::get('/record-retention', [RetentionController::class, 'index'])->name('retention.index');
    Route::post('/record-retention/{funeralCase}/legal-hold', [RetentionController::class, 'placeHold'])->name('retention.place-hold');
    Route::post('/record-retention/{funeralCase}/release-hold', [RetentionController::class, 'releaseHold'])->name('retention.release-hold');
});

Route::middleware(['auth', 'no_cache', 'active'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/analytics', [ReportController::class, 'analytics'])->name('reports.analytics');
    Route::get('/reports/preview', [ReportController::class, 'preview'])->name('reports.preview');
    Route::get('/reports/print', [ReportController::class, 'print'])->name('reports.print');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.exportPdf');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.exportCsv');
    Route::get('/reports/owner-drilldown', [ReportController::class, 'ownerDrilldown'])->name('reports.ownerDrilldown');
});

Route::middleware(['auth', 'no_cache', 'active', 'admin', 'branch.scope'])->get('/admin', function (Request $request) {
    $user = $request->user();
    $branchScopeIds = $user->branchScopeIds();
    $isBranchAdmin = $user->isBranchAdmin();
    $isMainAdmin = $user->isMainBranchAdmin();
    if ($isMainAdmin) {
        return app(SystemAdminDashboardController::class)->index();
    }
    $validated = $request->validate([
        'branch_id' => 'nullable|integer|exists:branches,id',
    ]);
    $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
    if ($isBranchAdmin && $request->filled('branch_id') && (int) $validated['branch_id'] !== (int) $user->branch_id) {
        abort(403, 'Branch is outside your admin scope.');
    }
    if ($isBranchAdmin) {
        $branchId = (int) $user->branch_id;
    }
    if ($branchId && $branchScopeIds !== null && !in_array($branchId, $branchScopeIds, true)) {
        abort(403, 'Branch is outside your admin scope.');
    }
    $now = now();
    $monthStart = $now->copy()->startOfMonth();
    $monthEnd = $now->copy()->endOfMonth();

    $paymentDateScope = function ($query) use ($monthStart, $monthEnd) {
        $query->where(function ($dateQuery) use ($monthStart, $monthEnd) {
            $dateQuery->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->orWhere(function ($fallback) use ($monthStart, $monthEnd) {
                    $fallback->whereNull('paid_at')
                        ->whereBetween('paid_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
                });
        });
    };

    $casesQuery = FuneralCase::query()
        ->when($branchScopeIds !== null, fn ($q) => $q->whereIn('branch_id', $branchScopeIds))
        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
        ->whereIn('case_status', ['ACTIVE', 'COMPLETED'])
        ->where(function ($scopeQuery) {
            $scopeQuery->where('entry_source', 'MAIN')->orWhereNull('entry_source');
        });
    $totalCases = (clone $casesQuery)->count();
    $totalSales = (clone $casesQuery)->where('payment_status', 'PAID')->sum('total_amount');
    $totalServiceValue = (clone $casesQuery)->sum('total_amount');
    $summaryCollectedTotal = (clone $casesQuery)->sum('total_paid');
    // Current payment-status snapshot. This intentionally does not use the
    // dashboard period because these cards link to the current case summary
    // in Payment Monitoring, not to transaction activity for a date range.
    $paymentStatusQuery = FuneralCase::query()
        ->when($branchScopeIds !== null, fn ($q) => $q->whereIn('branch_id', $branchScopeIds))
        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
        ->whereIn('case_status', ['ACTIVE', 'COMPLETED'])
        ->where(function ($scopeQuery) {
            $scopeQuery->where('entry_source', 'MAIN')->orWhereNull('entry_source');
        });
    $paidCases = (clone $paymentStatusQuery)
        ->where('payment_status', 'PAID')
        ->where('balance_amount', '<=', 0)
        ->count();
    $partialCases = (clone $paymentStatusQuery)
        ->where('payment_status', 'PARTIAL')
        ->where('total_paid', '>', 0)
        ->where('balance_amount', '>', 0)
        ->count();
    $unpaidCases = (clone $paymentStatusQuery)
        ->where('payment_status', 'UNPAID')
        ->where('balance_amount', '>', 0)
        ->count();
    $totalCollected = Payment::query()
        ->when($branchScopeIds !== null, fn ($q) => $q->whereIn('branch_id', $branchScopeIds))
        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
        ->where(function ($q) {
            $q->whereNull('status')->orWhereNotIn('status', ['VOID', 'VOIDED']);
        })
        ->tap($paymentDateScope)
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
        ->whereBetween('created_at', [$monthStart, $monthEnd])
        ->selectRaw(
            "branch_id, COUNT(*) as case_count, COALESCE(SUM(total_amount), 0) as service_value, COALESCE(SUM(total_paid), 0) as collected_amount"
        )
        ->groupBy('branch_id')
        ->get()
        ->keyBy('branch_id');

    $branchRevenueCards = $selectedBranches->map(function ($branch) use ($branchMetrics) {
        $metric = $branchMetrics->get($branch->id);
        return [
            'branch' => $branch,
            'sales' => (float) ($metric->service_value ?? 0),
            'collected' => (float) ($metric->collected_amount ?? 0),
        ];
    });

    $caseVolume = $selectedBranches->map(function ($branch) use ($branchMetrics) {
        $metric = $branchMetrics->get($branch->id);

        return [
            'branch_id' => $branch->id,
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
    $dashboardBranch = $branchId ? $branches->firstWhere('id', $branchId) : null;
    $auditLogs = AuditLog::with(['actor:id,name,role', 'branch:id,branch_code,branch_name'])
        ->when($isBranchAdmin, function ($query) use ($user) {
            $assignedBranchId = (int) $user->branch_id;

            $query->where(function ($scope) use ($assignedBranchId) {
                $scope->where('branch_id', $assignedBranchId)
                    ->orWhere('target_branch_id', $assignedBranchId);
            });
        })
        ->when(!$isBranchAdmin && $branchScopeIds !== null, function ($query) use ($branchScopeIds) {
            $query->where(function ($scope) use ($branchScopeIds) {
                $scope->whereIn('branch_id', $branchScopeIds)
                    ->orWhereIn('target_branch_id', $branchScopeIds);
            });
        })
        ->latest()
        ->take(5)
        ->get();

    $todaySchedule = collect();
    $currentlyInWake = collect();
    $attentionReminders = collect();
    $upcomingSchedule = collect();
    $balanceReminders = collect();
    $reminderService = app(\App\Services\ReminderService::class);
    $reminderBranches = $branchId ? $selectedBranches->where('id', $branchId) : $selectedBranches;
    foreach ($reminderBranches as $reminderBranch) {
        $fullReminders = $reminderService
            ->buildFullList((int) $reminderBranch->id, ['alert_type' => 'all'], now())
            ->map(fn ($item) => array_merge($item, [
                'branch_id' => (int) $reminderBranch->id,
                'branch_label' => $reminderBranch->branch_code.' - '.$reminderBranch->branch_name,
            ]));
        $currentlyInWake = $currentlyInWake->merge(
            $reminderService->currentlyInWake((int) $reminderBranch->id, now())
                ->map(fn ($item) => array_merge($item, [
                    'branch_id' => (int) $reminderBranch->id,
                    'branch_label' => $reminderBranch->branch_code.' - '.$reminderBranch->branch_name,
                ]))
        );
        $upcomingEnd = now()->startOfDay()->addDays(7)->endOfDay();
        $todaySchedule = $todaySchedule->merge($fullReminders
            ->whereIn('type', ['wake_start_today', 'wake_end_today', 'service_today', 'interment_today'])
            ->sortBy('sort_date'));
        $attentionReminders = $attentionReminders->merge($fullReminders
            ->whereIn('type', ['interment_completed', 'interment_date_completed', 'interment_approaching', 'interment_date_today_balance']));
        $upcomingSchedule = $upcomingSchedule->merge($fullReminders
            ->whereIn('type', ['upcoming_wake_start', 'upcoming_wake_end', 'upcoming_service', 'upcoming_interment'])
            ->filter(fn ($item) => $item['date'] && $item['date']->lessThanOrEqualTo($upcomingEnd))
            ->sortBy('sort_date'));
        $balanceReminders = $balanceReminders->merge($fullReminders->where('type', 'balance'));
    }
    $todaySchedule = $reminderService->sortReminders($todaySchedule)->values();
    $currentlyInWake = $currentlyInWake->unique(fn ($item) => $item['branch_id'].'-'.$item['case_id'])->values();
    $attentionReminders = $reminderService->sortReminders($attentionReminders, true)
        ->unique(fn ($item) => $item['branch_id'].'-'.$item['case_id'])->values();
    $upcomingSchedule = $reminderService->sortReminders($upcomingSchedule)->unique(fn ($item) => implode('|', [
        $item['branch_id'],
        $item['case_id'],
        $item['type'],
        $item['date']?->format('Y-m-d H:i:s') ?? '',
    ]))->values();
    $balanceReminders = $balanceReminders->unique('case_id')
        ->sortBy(fn ($item) => ($item['case']->payment_status === 'PARTIAL' ? '0-' : '1-').($item['case_code'] ?? ''))
        ->values();
    $balancePreviewLimit = 5;
    $lastBalancePayments = $isBranchAdmin && $balanceReminders->isNotEmpty()
        ? Payment::query()
            ->validForReporting()
            ->where('branch_id', $branchId)
            ->whereIn('funeral_case_id', $balanceReminders->take($balancePreviewLimit)->pluck('case_id'))
            ->orderByRaw('COALESCE(paid_at, paid_date, created_at) DESC')
            ->orderByDesc('id')
            ->groupLimit(1, 'funeral_case_id')
            ->get(['id', 'funeral_case_id', 'amount', 'paid_at', 'paid_date', 'created_at'])
            ->unique('funeral_case_id')
            ->keyBy('funeral_case_id')
        : collect();

    $pendingCorrections = \App\Models\PaymentCorrectionRequest::forMonitoring($user)
        ->where('status', 'PENDING')->toBase()
        ->selectRaw('COUNT(*) AS pending_count, MIN(created_at) AS oldest_at')->first();
    $scheduleConflicts = $reminderService->resourceConflicts(
        $currentlyInWake->merge($todaySchedule)->merge($upcomingSchedule)->pluck('case')->unique('id'),
        $now,
        $now->copy()->addDays(7)->endOfDay(),
    );

    return view('dashboards.admin', [
        'branchCount' => $branches->count(),
        'userCount' => User::when($branchScopeIds !== null, fn ($query) => $query->whereIn('branch_id', $branchScopeIds))->count(),
        'packageCount' => Package::count(),
        'branches' => $branches,
        'selectedBranchId' => $branchId,
        'totalCases' => $totalCases,
        'totalSales' => $totalSales,
        'totalServiceValue' => $totalServiceValue,
        'summaryCollectedTotal' => $summaryCollectedTotal,
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
        'dashboardMonthStart' => $monthStart,
        'dashboardMonthEnd' => $monthEnd,
        'auditLogs' => $auditLogs,
        'todaySchedule' => $todaySchedule,
        'currentlyInWake' => $currentlyInWake,
        'attentionReminders' => $attentionReminders,
        'upcomingSchedule' => $upcomingSchedule,
        'balanceReminders' => $balanceReminders,
        'balancePreviewLimit' => $balancePreviewLimit,
        'lastBalancePayments' => $lastBalancePayments,
        'pendingCorrections' => $pendingCorrections,
        'scheduleConflicts' => $scheduleConflicts,
    ]);
});

Route::middleware(['auth', 'no_cache', 'active', 'staff', 'branch.scope'])->get('/staff', function (Request $request) {
    $user = auth()->user();
    $dashboardBranchId = (int) ($user->operationalBranchId() ?? 0);
    $canEncodeAnyBranch = $user->canEncodeAnyBranch();
    $dashboardBranch = Branch::select(['id', 'branch_code', 'branch_name', 'address'])->find($dashboardBranchId);
    $dashboardNow = now();
    $today = $dashboardNow->copy()->startOfDay();

    $mainCasesBase = FuneralCase::query()
        ->where('branch_id', $dashboardBranchId)
        ->where(function ($query) {
            $query->where('entry_source', 'MAIN')
                ->orWhereNull('entry_source');
        });
    $unpaidCases = (clone $mainCasesBase)->with('deceased')
        ->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
        ->where('balance_amount', '>', 0)
        ->latest()
        ->orderByDesc('id')
        ->paginate(5, ['*'], 'balances_page')
        ->withQueryString()
        ->fragment('outstanding-balances');

    if ($unpaidCases->currentPage() > $unpaidCases->lastPage()) {
        return redirect()->to($unpaidCases->url($unpaidCases->lastPage()));
    }

    if ($request->header('X-Staff-Dashboard-Fragment') === 'balances') {
        return response()->view('staff.partials.dashboard-outstanding-balances', compact('unpaidCases'))
            ->header('X-Staff-Dashboard-Fragment', 'balances')
            ->header('Vary', 'X-Staff-Dashboard-Fragment');
    }

    $clientCount = Client::where('branch_id', $dashboardBranchId)->count();
    $deceasedCount = Deceased::where('branch_id', $dashboardBranchId)->count();
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

    $reminderService = app(\App\Services\ReminderService::class);
    // Use the actual current time for approaching/after-interment classification;
    // $today remains the date boundary for dashboard totals and date queries.
    $dashboardReminders = $reminderService->buildDashboard($dashboardBranchId, $dashboardNow);
    $scheduleGroups = $reminderService->scheduleGroups($dashboardBranchId);
    $attentionReminders = $dashboardReminders['attention']
        ->whereIn('type', ['interment_completed', 'interment_date_completed'])
        ->unique('case_id')
        ->values();
    $todaySchedule = $dashboardReminders['today'];

    $recentCases = FuneralCase::with(['client', 'deceased', 'encodedBy:id,name', 'package:id,name'])
        ->where('branch_id', $dashboardBranchId)
        ->where(function ($query) {
            $query->where('entry_source', 'MAIN')
                ->orWhereNull('entry_source');
        })
        ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
        ->latest('created_at')
        ->paginate(3, ['*'], 'recent_cases_page')
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
        ->where(function ($query) {
            $query->whereNull('status')->orWhereNotIn('status', ['VOID', 'VOIDED']);
        })
        ->where(function ($query) use ($today) {
            $query->whereDate('paid_at', $today->toDateString())
                ->orWhere(function ($fallback) use ($today) {
                    $fallback->whereNull('paid_at')
                        ->whereDate('created_at', $today->toDateString());
                });
        })
        ->orderByDesc('paid_at')
        ->orderByDesc('id')
        ->take(5)
        ->get();

    $upcomingSchedule = $reminderService
        ->buildFullList($dashboardBranchId, [], $today)
        ->whereIn('type', ['upcoming_wake_start', 'upcoming_wake_end', 'upcoming_service', 'upcoming_interment'])
        ->sortBy('sort_date')
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
        'unpaidCases',
        'dashboardNow',
        'todaySchedule',
        'upcomingSchedule',
        'attentionReminders',
        'scheduleGroups',
        'recentCases',
        'recentPayments',
        'canEncodeAnyBranch'
    ));
});

Route::middleware(['auth', 'no_cache', 'active', 'branch.scope'])->get(
    'funeral-cases/{funeral_case}',
    [FuneralCaseController::class, 'show']
)->whereNumber('funeral_case')->name('funeral-cases.show');

Route::middleware(['auth', 'no_cache', 'active', 'branch.scope'])->get(
    'funeral-cases/{funeral_case}/saved-attachment/{kind}',
    [FuneralCaseController::class, 'savedAttachment']
)->whereNumber('funeral_case')->whereIn('kind', ['deceased-photo', 'senior-proof'])->name('funeral-cases.saved-attachment');

Route::middleware(['auth', 'no_cache', 'active', 'branch.scope'])->get(
    'funeral-cases/{funeral_case}/senior-proof',
    [FuneralCaseController::class, 'seniorProof']
)->whereNumber('funeral_case')->name('funeral-cases.senior-proof');

Route::middleware(['auth', 'no_cache', 'active', 'branch.scope'])->get(
    'funeral-cases/{funeral_case}/print',
    [FuneralCaseController::class, 'printRecord']
)->whereNumber('funeral_case')->name('funeral-cases.print');

Route::middleware(['auth', 'no_cache', 'active'])->group(function () {
    Route::get('funeral-cases/{funeral_case}/documents/funeral-contract/preview', [CaseDocumentController::class, 'contractPreview'])
        ->name('funeral-cases.documents.contract.preview');
    Route::get('funeral-cases/{funeral_case}/documents/funeral-contract/preview-pdf', [CaseDocumentController::class, 'contractPreviewPdf'])
        ->name('funeral-cases.documents.contract.preview-pdf');
    Route::post('funeral-cases/{funeral_case}/documents/funeral-contract', [CaseDocumentController::class, 'store'])
        ->name('funeral-cases.documents.contract.store');
    Route::post('funeral-cases/{funeral_case}/documents/funeral-contract/correct-birth-date', [CaseDocumentController::class, 'correctBirthDate'])
        ->name('funeral-cases.documents.contract.correct-birth-date');
    Route::get('funeral-cases/{funeral_case}/documents/{document}/revisions/{revision}/preview', [CaseDocumentController::class, 'revisionPreview'])
        ->name('funeral-cases.documents.revisions.preview');
    Route::get('funeral-cases/{funeral_case}/documents/{document}/preview', [CaseDocumentController::class, 'preview'])
        ->name('funeral-cases.documents.preview');
    Route::get('funeral-cases/{funeral_case}/documents/{document}/print', [CaseDocumentController::class, 'print'])
        ->name('funeral-cases.documents.print');
});

Route::middleware(['auth', 'no_cache', 'active', 'staff', 'branch.scope'])->group(function () {
    Route::get('intake', [IntakeController::class, 'create'])->name('intake.create');
    Route::post('intake', [IntakeController::class, 'store'])->name('intake.store');
    Route::get('intake/saved', [IntakeController::class, 'saved'])->name('intake.saved');
    Route::get('intake/schedule-advisory', [IntakeController::class, 'scheduleAdvisory'])->name('intake.schedule-advisory');
    Route::get('intake/existing-clients', [IntakeController::class, 'existingClients'])->name('intake.existing-clients');
    Route::get('intake/main', [IntakeController::class, 'createMain'])->name('intake.main.create');
    Route::post('intake/main', [IntakeController::class, 'storeMain'])->name('intake.main.store');
    Route::get('intake/other', [IntakeController::class, 'createOther'])->name('intake.other.create');
    Route::post('intake/other', [IntakeController::class, 'storeOther'])->name('intake.other.store');
    Route::get('intake/drafts', [IntakeDraftController::class, 'index'])->name('intake.drafts.index');
    Route::post('intake/drafts', [IntakeDraftController::class, 'save'])->name('intake.drafts.save');
    Route::get('intake/drafts/{draft}', [IntakeDraftController::class, 'edit'])->name('intake.drafts.edit');
    Route::delete('intake/drafts/{draft}', [IntakeDraftController::class, 'destroy'])->name('intake.drafts.destroy');
    Route::resource('clients', ClientController::class)->except(['create', 'store', 'destroy']);
    Route::get('deceased', [DeceasedController::class, 'index'])->name('deceased.index');
    Route::resource('deceased', DeceasedController::class)->only(['edit', 'update', 'show']);
    // Staff-only: create, store, delete. Edit/update are open to branch admins (see group below).
    Route::resource('funeral-cases', FuneralCaseController::class)->except(['show', 'edit', 'update']);
    Route::get('completed-cases', [FuneralCaseController::class, 'completedIndex'])->name('funeral-cases.completed');
    Route::get('other-branch-reports', [FuneralCaseController::class, 'otherReportsIndex'])->name('funeral-cases.other-reports');
    Route::get('reminders', [ReminderController::class, 'index'])->name('staff.reminders.index');
});

Route::middleware(['auth', 'no_cache', 'active', 'branch.scope', 'historical_cases.enabled'])->group(function () {
    Route::get('historical-intake', [IntakeController::class, 'createHistorical'])->name('historical-intake.create');
    Route::post('historical-intake', [IntakeController::class, 'storeHistorical'])->name('historical-intake.store');
    Route::get('historical-cases', [\App\Http\Controllers\HistoricalCaseSubmissionController::class, 'index'])->name('historical-cases.index');
    Route::get('historical-cases/{submission}/resume', [\App\Http\Controllers\HistoricalCaseSubmissionController::class, 'resume'])->name('historical-cases.resume');
    Route::get('historical-cases/{submission}/payment-proof/{paymentIndex}', [\App\Http\Controllers\HistoricalCaseSubmissionController::class, 'paymentProof'])->whereNumber('paymentIndex')->name('historical-cases.payment-proof');
    Route::get('historical-cases/{submission}', [\App\Http\Controllers\HistoricalCaseSubmissionController::class, 'show'])->name('historical-cases.show');
    Route::post('historical-cases/{submission}/decision', [\App\Http\Controllers\HistoricalCaseSubmissionController::class, 'decide'])->name('historical-cases.decide');
});

// Case edit/update: accessible to staff AND branch/main admins (policy enforces own-branch restriction).
Route::middleware(['auth', 'no_cache', 'active', 'staff_or_admin', 'branch.scope'])->group(function () {
    Route::get('funeral-cases/{funeral_case}/edit', [FuneralCaseController::class, 'edit'])->name('funeral-cases.edit');
    Route::put('funeral-cases/{funeral_case}', [FuneralCaseController::class, 'update'])->name('funeral-cases.update');
    Route::patch('funeral-cases/{funeral_case}', [FuneralCaseController::class, 'update']);
    Route::post('funeral-cases/{funeral_case}/tarpaulin-photo', [CaseAttachmentController::class, 'store'])->name('funeral-cases.tarpaulin.store');
    Route::put('funeral-cases/{funeral_case}/tarpaulin-photo', [CaseAttachmentController::class, 'update'])->name('funeral-cases.tarpaulin.update');
    Route::delete('funeral-cases/{funeral_case}/tarpaulin-photo', [CaseAttachmentController::class, 'destroy'])->name('funeral-cases.tarpaulin.destroy');
    Route::post('funeral-cases/{funeral_case}/media/{kind}', [CaseAttachmentController::class, 'saveCaseMedia'])
        ->whereIn('kind', ['deceased-photo', 'senior-proof'])
        ->name('funeral-cases.media.save');
});

Route::middleware(['auth', 'no_cache', 'active'])->get('payments/history', [PaymentController::class, 'history'])->name('payments.history');
Route::middleware(['auth', 'no_cache', 'active', 'admin'])->get('payments/history/print', [PaymentController::class, 'history'])->name('payments.monitoring.print');
Route::middleware(['auth', 'no_cache', 'active'])->get('payments/cases/{funeral_case}/history/print', [PaymentController::class, 'printHistory'])->name('payments.history.print');
Route::middleware(['auth', 'no_cache', 'active'])->get('payments/{payment}/summary', [PaymentController::class, 'summary'])->name('payments.summary');
Route::middleware(['auth', 'no_cache', 'active'])->get('intake/chapel-advisory', \App\Http\Controllers\ChapelScheduleController::class)->name('intake.chapel-advisory');
Route::middleware(['auth', 'no_cache', 'active'])->group(function () {
    Route::post('notifications/read-all', [NotificationStateController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/state', [NotificationStateController::class, 'update'])->name('notifications.state');
    Route::get('payment-corrections/notices', [PaymentCorrectionController::class, 'notices'])->name('payments.corrections.notices');
    Route::get('payment-corrections/cases', [PaymentCorrectionController::class, 'caseSearch'])->name('payments.corrections.cases');
    Route::get('payments/{payment}/correction-request', [PaymentCorrectionController::class, 'create'])->name('payments.corrections.create');
    Route::get('payment-corrections/{correction}', [PaymentCorrectionController::class, 'show'])->name('payments.corrections.show');
    Route::get('payment-corrections/{correction}/evidence', [PaymentCorrectionController::class, 'evidence'])->name('payments.corrections.evidence');
    Route::post('payment-corrections/{correction}/withdraw', [PaymentCorrectionController::class, 'withdraw'])->name('payments.corrections.withdraw');
});

Route::middleware(['auth', 'no_cache', 'active', 'branch.scope'])->post(
    'payments/identifier-check',
    [PaymentController::class, 'checkIdentifier']
)->name('payments.identifier-check');

Route::middleware(['auth', 'no_cache', 'active', 'staff', 'branch.scope'])->group(function () {
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/recordable-cases', [PaymentController::class, 'recordableCaseSuggestions'])->name('payments.recordable-cases');
    Route::post('payments/pay', [PaymentController::class, 'store'])->name('payments.store');
    Route::patch('payments/{payment}/receipt', [PaymentController::class, 'updateReceipt'])->name('payments.receipt.update');
    Route::post('payments/{payment}/correction-request', [PaymentCorrectionController::class, 'store'])->name('payments.corrections.store');
});

Route::middleware(['auth', 'no_cache', 'active', 'admin', 'branch.scope'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/payment-corrections', [PaymentCorrectionController::class, 'index'])->name('payment-corrections.index');
    Route::post('/payment-corrections/{correction}/remind', [PaymentCorrectionController::class, 'remind'])->name('payment-corrections.remind');
    Route::post('/payment-corrections/{correction}/approve', [PaymentCorrectionController::class, 'approve'])->name('payment-corrections.approve');
    Route::post('/payment-corrections/{correction}/reject', [PaymentCorrectionController::class, 'reject'])->name('payment-corrections.reject');
});

Route::middleware(['auth', 'no_cache', 'active', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::get('/system-admins/create', [UserController::class, 'createSystemAdmin'])->name('admin.system-admins.create');
    Route::post('/system-admins', [UserController::class, 'store'])->middleware('throttle:6,1')->name('admin.system-admins.store');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');

    // Edit
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::get('/users/{user}/role', [UserController::class, 'editRole'])->name('admin.users.role.edit');
    Route::patch('/users/{user}/role', [UserController::class, 'changeRole'])->middleware('throttle:6,1')->name('admin.users.role.update');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');

    // Activate/Deactivate
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('admin.users.toggleActive');

    // Optional: Reset Password
    Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('throttle:6,1')->name('admin.users.resetPassword');
});

Route::middleware(['auth', 'no_cache', 'active', 'main_admin'])->prefix('admin')->group(function () {
    // Branches
    Route::get('/branches', [BranchController::class, 'index'])->name('admin.branches.index');
    Route::get('/branches/create', [BranchController::class, 'create'])->name('admin.branches.create');
    Route::post('/branches', [BranchController::class, 'store'])->name('admin.branches.store');
    Route::get('/branches/{branch}/disable-check', [BranchController::class, 'disableCheck'])->name('admin.branches.disableCheck');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('admin.branches.edit');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('admin.branches.update');
    Route::patch('/branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('admin.branches.toggleStatus');
});

Route::middleware(['auth', 'no_cache', 'active', 'admin', 'branch.scope'])->prefix('admin')->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
    Route::get('/audit-logs/export-pdf', [AuditLogController::class, 'exportPdf'])->name('admin.audit-logs.exportPdf');
    Route::get('/audit-logs/{audit_log}', [AuditLogController::class, 'show'])->name('admin.audit-logs.show');
});

Route::middleware(['auth', 'no_cache', 'active', 'admin', 'branch.scope'])->prefix('admin')->group(function () {
    Route::get('/service-management', [ServiceManagementController::class, 'index'])->name('admin.service-management.index');
    Route::get('/service-management/print', [ServiceManagementController::class, 'printPreview'])->middleware('main_admin')->name('admin.service-management.print');
    Route::get('/service-management/photos/{type}/{id}', [\App\Http\Controllers\Admin\CatalogPhotoController::class, 'show'])->whereIn('type', array_keys(\App\Support\CatalogPhotos::TYPES))->whereNumber('id')->name('admin.catalog-photo');
    Route::get('/service-management/photos/{type}/{id}/edit', [\App\Http\Controllers\Admin\CatalogPhotoController::class, 'edit'])
        ->whereIn('type', array_keys(\App\Support\CatalogPhotos::TYPES))->whereNumber('id')->middleware('main_admin')->name('admin.catalog-photo.edit');
    Route::patch('/service-management/photos/{type}/{id}', [\App\Http\Controllers\Admin\CatalogPhotoController::class, 'update'])
        ->whereIn('type', array_keys(\App\Support\CatalogPhotos::TYPES))->whereNumber('id')->middleware('main_admin')->name('admin.catalog-photo.update');
    Route::put('/service-management/custom-package-defaults', [ServiceManagementController::class, 'updateCustomPackageDefaults'])->middleware('main_admin')->name('admin.custom-package-defaults.update');
    Route::get('/promotions/create', [PromotionController::class, 'create'])->name('admin.promotions.create');
    Route::post('/promotions', [PromotionController::class, 'store'])->name('admin.promotions.store');
    Route::get('/promotions/{promotion}', [PromotionController::class, 'show'])->name('admin.promotions.show');
    Route::get('/promotions/{promotion}/edit', [PromotionController::class, 'edit'])->name('admin.promotions.edit');
    Route::put('/promotions/{promotion}', [PromotionController::class, 'update'])->name('admin.promotions.update');
    Route::patch('/promotions/{promotion}/stop', [PromotionController::class, 'stop'])->name('admin.promotions.stop');
    Route::patch('/promotions/{promotion}/reactivate', [PromotionController::class, 'reactivate'])->name('admin.promotions.reactivate');
    Route::get('/packages', [PackageController::class, 'index'])->name('admin.packages.index');
    Route::get('/packages/create', [PackageController::class, 'create'])->name('admin.packages.create');
    Route::post('/packages', [PackageController::class, 'store'])->name('admin.packages.store');
    Route::get('/packages/{package}', [PackageController::class, 'show'])->name('admin.packages.show');
    Route::get('/packages/{package}/edit', [PackageController::class, 'edit'])->name('admin.packages.edit');
    Route::put('/packages/{package}', [PackageController::class, 'update'])->name('admin.packages.update');
    Route::patch('/packages/{package}/quick-price', [PackageController::class, 'quickUpdatePrice'])->name('admin.packages.quickPrice');
    Route::patch('/packages/{package}/toggle-active', [PackageController::class, 'toggleActive'])->name('admin.packages.toggleActive');
    Route::get('/add-on-catalogs', [AddOnCatalogController::class, 'index'])->name('admin.add-on-catalogs.index');
    Route::get('/add-on-catalogs/create', [AddOnCatalogController::class, 'create'])->name('admin.add-on-catalogs.create');
    Route::post('/add-on-catalogs', [AddOnCatalogController::class, 'store'])->name('admin.add-on-catalogs.store');
    Route::get('/add-on-catalogs/{add_on_catalog}', [AddOnCatalogController::class, 'show'])->name('admin.add-on-catalogs.show');
    Route::get('/add-on-catalogs/{add_on_catalog}/edit', [AddOnCatalogController::class, 'edit'])->name('admin.add-on-catalogs.edit');
    Route::put('/add-on-catalogs/{add_on_catalog}', [AddOnCatalogController::class, 'update'])->name('admin.add-on-catalogs.update');
    Route::patch('/add-on-catalogs/{add_on_catalog}/toggle-availability', [AddOnCatalogController::class, 'toggleAvailability'])->name('admin.add-on-catalogs.toggleAvailability');
    Route::patch('/add-on-catalogs/{add_on_catalog}/toggle-active', [AddOnCatalogController::class, 'toggleActive'])->name('admin.add-on-catalogs.toggleActive');
    Route::get('/freebie-catalogs', [FreebieCatalogController::class, 'index'])->name('admin.freebie-catalogs.index');
    Route::get('/freebie-catalogs/create', [FreebieCatalogController::class, 'create'])->name('admin.freebie-catalogs.create');
    Route::post('/freebie-catalogs', [FreebieCatalogController::class, 'store'])->name('admin.freebie-catalogs.store');
    Route::get('/freebie-catalogs/{freebie_catalog}', [FreebieCatalogController::class, 'show'])->name('admin.freebie-catalogs.show');
    Route::get('/freebie-catalogs/{freebie_catalog}/edit', [FreebieCatalogController::class, 'edit'])->name('admin.freebie-catalogs.edit');
    Route::put('/freebie-catalogs/{freebie_catalog}', [FreebieCatalogController::class, 'update'])->name('admin.freebie-catalogs.update');
    Route::patch('/freebie-catalogs/{freebie_catalog}/toggle-availability', [FreebieCatalogController::class, 'toggleAvailability'])->name('admin.freebie-catalogs.toggleAvailability');
    Route::patch('/freebie-catalogs/{freebie_catalog}/toggle-active', [FreebieCatalogController::class, 'toggleActive'])->name('admin.freebie-catalogs.toggleActive');
    Route::get('/hearse-catalogs/create', [HearseCatalogController::class, 'create'])->name('admin.hearse-catalogs.create');
    Route::post('/hearse-catalogs', [HearseCatalogController::class, 'store'])->name('admin.hearse-catalogs.store');
    Route::get('/hearse-catalogs/{hearse_catalog}', [HearseCatalogController::class, 'show'])->name('admin.hearse-catalogs.show');
    Route::get('/hearse-catalogs/{hearse_catalog}/edit', [HearseCatalogController::class, 'edit'])->name('admin.hearse-catalogs.edit');
    Route::put('/hearse-catalogs/{hearse_catalog}', [HearseCatalogController::class, 'update'])->name('admin.hearse-catalogs.update');
    Route::patch('/hearse-catalogs/{hearse_catalog}/toggle-availability', [HearseCatalogController::class, 'toggleAvailability'])->name('admin.hearse-catalogs.toggleAvailability');
    Route::patch('/hearse-catalogs/{hearse_catalog}/toggle-active', [HearseCatalogController::class, 'toggleActive'])->name('admin.hearse-catalogs.toggleActive');
    Route::get('/casket-catalogs', [CasketCatalogController::class, 'index'])->name('admin.casket-catalogs.index');
    Route::get('/casket-catalogs/create', [CasketCatalogController::class, 'create'])->name('admin.casket-catalogs.create');
    Route::post('/casket-catalogs', [CasketCatalogController::class, 'store'])->name('admin.casket-catalogs.store');
    Route::get('/casket-catalogs/{casket_catalog}', [CasketCatalogController::class, 'show'])->name('admin.casket-catalogs.show');
    Route::get('/casket-catalogs/{casket_catalog}/edit', [CasketCatalogController::class, 'edit'])->name('admin.casket-catalogs.edit');
    Route::put('/casket-catalogs/{casket_catalog}', [CasketCatalogController::class, 'update'])->name('admin.casket-catalogs.update');
    Route::patch('/casket-catalogs/{casket_catalog}/toggle-active', [CasketCatalogController::class, 'toggleActive'])->name('admin.casket-catalogs.toggleActive');
    Route::patch('/casket-catalogs/{casket_catalog}/toggle-availability', [CasketCatalogController::class, 'toggleAvailability'])->name('admin.casket-catalogs.toggleAvailability');

    // Monitoring
    Route::get('/cases', [AdminReportController::class, 'masterCases'])->name('admin.cases.index');
    Route::get('/cases/{funeral_case}/edit', [AdminReportController::class, 'editCase'])->name('admin.cases.edit');
    Route::put('/cases/{funeral_case}', [AdminReportController::class, 'updateCase'])->name('admin.cases.update');
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

Route::middleware(['auth', 'no_cache', 'active'])->get('/sync/status', function () {
    abort_unless(config('branch_sync.mode') === 'branch', 404);
    return response()->json(app(\App\Services\BranchSync\BranchSyncService::class)->status())
        ->header('Cache-Control', 'no-store, private');
})->name('sync.status');
