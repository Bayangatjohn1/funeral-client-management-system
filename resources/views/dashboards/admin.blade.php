@extends('layouts.panel')

@section('page_title', 'Admin Dashboard')
@section('page_desc', 'Monitor branch operations, case activity, payments, and system status.')
@section('hide_layout_topbar', '1')

@section('header_actions')
@endsection

@section('content')
@php
    $adminUser = auth()->user();
    $isMainAdmin = $isMainAdmin ?? ($adminUser?->isMainBranchAdmin() ?? false);
    $isBranchAdmin = $isBranchAdmin ?? ($adminUser?->isBranchAdmin() ?? false);
    $adminFirstName = \Illuminate\Support\Str::of($adminUser->name ?? 'Admin')->trim()->explode(' ')->first();
    $adminTodayLabel = now()->format('l, F j, Y');
    $adminBranch = $dashboardBranch ?? $adminUser?->branch;
    $adminBranchLabel = trim(($adminBranch?->branch_code ?? 'BR') . ' - ' . ($adminBranch?->branch_name ?? 'Assigned Branch'));
    $adminSubtitle = $isMainAdmin
        ? 'Managing Main Branch and all branch operations'
        : 'Managing branch operations - ' . $adminBranchLabel;
    $branchLinkParams = [];
    if (($selectedBranchId ?? null)) {
        $branchLinkParams['branch_id'] = $selectedBranchId;
    } elseif ($isBranchAdmin && ($adminBranch?->id ?? null)) {
        $branchLinkParams['branch_id'] = $adminBranch->id;
    }
    $paymentDateLinkParams = [];
    if (($dashboardDateStart ?? null) && ($dashboardDateEnd ?? null)) {
        $paymentDateLinkParams['paid_from'] = $dashboardDateStart->toDateString();
        $paymentDateLinkParams['paid_to'] = $dashboardDateEnd->toDateString();
    }
    $caseDateLinkParams = [];
    if (($dashboardDateStart ?? null) && ($dashboardDateEnd ?? null)) {
        $caseDateLinkParams['date_from'] = $dashboardDateStart->toDateString();
        $caseDateLinkParams['date_to'] = $dashboardDateEnd->toDateString();
    }
    $caseRecordsUrl = route('admin.cases.index', $branchLinkParams);
    $activeCasesUrl = route('admin.cases.index', array_merge($branchLinkParams, ['case_status' => 'ACTIVE']));
    $paidMonitoringUrl = route('admin.payment-monitoring', array_merge($branchLinkParams, ['payment_status' => 'PAID']));
    $partialMonitoringUrl = route('admin.payment-monitoring', array_merge($branchLinkParams, ['payment_status' => 'PARTIAL']));
    $unpaidMonitoringUrl = route('admin.payment-monitoring', array_merge($branchLinkParams, ['payment_status' => 'UNPAID']));
    $collectedMonitoringUrl = route('admin.payment-monitoring', array_merge($branchLinkParams, $paymentDateLinkParams, ['tab' => 'transactions']));
    $outstandingMonitoringUrl = route('admin.cases.index', array_merge($branchLinkParams, $caseDateLinkParams));
    $todaySchedulesUrl = route('admin.reminders.index', array_merge($branchLinkParams, ['tab' => 'today', 'alert_type' => 'all']));
    $balanceAttentionUrl = route('admin.reminders.index', array_merge($branchLinkParams, ['tab' => 'unpaid', 'alert_type' => 'balance']));
@endphp
<style>
    html:not([data-theme='dark']) .admin-dashboard-shell {
        color: var(--color-text-primary);
    }

    .admin-dashboard-shell,
    .admin-dashboard-shell * {
        min-width: 0;
    }

    .admin-dashboard-shell .card-custom,
    .admin-dashboard-shell .stat-card,
    .admin-dashboard-shell .admin-top-controls,
    .admin-dashboard-shell > .bg-white,
    .admin-dashboard-shell .admin-section-block .bg-transparent {
        border-radius: clamp(18px, 1.8vw, 28px) !important;
    }

    .admin-dashboard-shell .admin-top-controls {
        align-items: center;
    }

    .admin-dashboard-greeting {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 1.1rem 1.25rem;
        border: 1px solid var(--color-border);
        border-radius: clamp(18px, 1.8vw, 28px);
        border-top-left-radius: 0;
        border-top-right-radius: 0;
        background: linear-gradient(180deg, var(--color-bg-surface) 0%, var(--color-bg-muted) 100%);
    }

    .admin-dashboard-greeting__title {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        min-width: 0;
    }

    .admin-dashboard-greeting h1 {
        margin: 0;
        font-family: var(--font-heading);
        font-size: clamp(1.45rem, 2.3vw, 2rem);
        line-height: 1.1;
        color: var(--color-text-primary);
        letter-spacing: 0;
    }

    .admin-dashboard-greeting p {
        margin: .4rem 0 0;
        color: var(--color-text-secondary);
        font-size: .98rem;
    }

    .admin-dashboard-greeting__tools {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .6rem;
        flex-wrap: wrap;
    }

    .admin-dashboard-date-pill {
        display: inline-flex;
        align-items: center;
        gap: .42rem;
        min-height: 38px;
        border: 1px solid var(--color-border);
        background: var(--color-bg-surface);
        border-radius: .75rem;
        padding: .52rem .74rem;
        font-size: .9rem;
        color: var(--color-text-secondary);
        white-space: nowrap;
    }

    .admin-dashboard-date-pill i {
        color: var(--color-primary);
    }

    .admin-dashboard-shell .admin-top-controls-form select {
        min-height: 40px;
    }

    .admin-dashboard-shell .admin-section-block {
        width: 100%;
    }

    .admin-dashboard-shell .admin-section-block > .card-custom,
    .admin-dashboard-shell .admin-section-block > .bg-white {
        height: 100%;
    }

    .admin-dashboard-shell .stat-card {
        min-height: 152px;
        justify-content: space-between;
    }

    .admin-dashboard-shell a.stat-card,
    .admin-dashboard-shell .dashboard-click-card {
        color: inherit;
        text-decoration: none;
        cursor: pointer;
        transition: background-color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell a.stat-card:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .dashboard-click-card:hover {
        background-color: #F3F0E8 !important;
        border-color: #3E4A3D !important;
        box-shadow: 0 12px 30px rgba(62, 74, 61, .12) !important;
    }

    .admin-dashboard-shell .dashboard-card-link-copy {
        margin-top: .75rem;
        color: var(--color-primary);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .admin-dashboard-shell .stat-value {
        line-height: 1.08;
        word-break: break-word;
    }

    .admin-dashboard-shell .admin-section-block h3,
    .admin-dashboard-shell .admin-section-block h4,
    .admin-dashboard-shell .admin-section-block h5,
    .admin-dashboard-shell .admin-section-block p {
        overflow-wrap: anywhere;
    }

    .admin-dashboard-shell .admin-section-block .rounded-\[2\.5rem\],
    .admin-dashboard-shell .admin-section-block .card-custom {
        min-height: 220px;
    }

    .admin-dashboard-shell .admin-section-block .grid > .stat-card {
        min-height: 152px;
    }

    .admin-dashboard-shell .admin-section-block .flex.items-center.justify-between.p-4 {
        min-height: 86px;
    }

    .admin-dashboard-shell .admin-section-block .bg-transparent.border {
        min-height: 112px;
        background-color: var(--color-bg-surface);
    }

    .admin-dashboard-shell .admin-financial-card {
        min-height: 138px !important;
        padding: 1.15rem 1.25rem !important;
        border-radius: 1.25rem !important;
        background: var(--color-bg-surface) !important;
        border: 1px solid var(--color-border) !important;
        box-shadow: 0 8px 20px rgba(62, 74, 61, .06) !important;
    }

    .admin-dashboard-shell .admin-financial-card__head {
        margin-bottom: 1rem;
    }

    .admin-dashboard-shell .admin-financial-card h4 {
        font-size: clamp(1.75rem, 3vw, 2.35rem) !important;
        line-height: 1.05;
        color: var(--color-text-primary) !important;
    }

    .admin-dashboard-shell .admin-financial-card h4 span {
        color: var(--color-text-primary) !important;
    }

    .admin-dashboard-shell .admin-financial-card .dashboard-card-link-copy {
        margin-top: .55rem;
    }

    @media (max-width: 767px) {
        .admin-dashboard-shell {
            padding-inline: 12px;
        }

        .admin-dashboard-shell .admin-top-controls-form,
        .admin-dashboard-shell .admin-top-controls-form select,
        .admin-dashboard-shell .admin-top-controls-actions,
        .admin-dashboard-shell .admin-top-controls-actions a {
            width: 100% !important;
        }

        .admin-dashboard-shell .admin-section-block .rounded-\[2\.5rem\],
        .admin-dashboard-shell .admin-section-block .card-custom,
        .admin-dashboard-shell > .bg-white {
            border-radius: 20px !important;
            padding: 20px !important;
        }

        .admin-dashboard-shell .stat-card {
            min-height: 132px;
            padding: 14px;
        }

        .admin-dashboard-greeting {
            padding: .95rem;
        }

        .admin-dashboard-greeting__tools {
            width: 100%;
            justify-content: flex-start;
        }

        .admin-dashboard-shell h4.text-5xl,
        .admin-dashboard-shell h4.text-6xl {
            font-size: clamp(2rem, 10vw, 3rem) !important;
            line-height: 1.05;
        }
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls,
    html:not([data-theme='dark']) .admin-dashboard-shell .card-custom,
    html:not([data-theme='dark']) .admin-dashboard-shell .stat-card,
    html:not([data-theme='dark']) .admin-dashboard-shell .bg-white {
        background-color: var(--color-bg-surface) !important;
        border-color: var(--color-border) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .bg-slate-50,
    html:not([data-theme='dark']) .admin-dashboard-shell .bg-slate-100,
    html:not([data-theme='dark']) .admin-dashboard-shell .hover\:bg-slate-50:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .hover\:bg-slate-100:hover {
        background-color: var(--color-bg-muted) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .border-slate-100,
    html:not([data-theme='dark']) .admin-dashboard-shell .border-slate-200,
    html:not([data-theme='dark']) .admin-dashboard-shell .border-slate-200\/60 {
        border-color: var(--color-border) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .text-slate-900,
    html:not([data-theme='dark']) .admin-dashboard-shell .text-slate-800,
    html:not([data-theme='dark']) .admin-dashboard-shell .hover\:text-slate-900:hover {
        color: var(--color-text-primary) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .text-slate-700,
    html:not([data-theme='dark']) .admin-dashboard-shell .text-slate-500,
    html:not([data-theme='dark']) .admin-dashboard-shell .text-slate-400,
    html:not([data-theme='dark']) .admin-dashboard-shell .text-slate-300 {
        color: var(--color-text-secondary) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .text-emerald-600,
    html:not([data-theme='dark']) .admin-dashboard-shell .text-emerald-500,
    html:not([data-theme='dark']) .admin-dashboard-shell .text-emerald-400 {
        color: var(--color-success) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .text-amber-600,
    html:not([data-theme='dark']) .admin-dashboard-shell .text-amber-500,
    html:not([data-theme='dark']) .admin-dashboard-shell .text-amber-400 {
        color: var(--color-warning) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .text-red-700,
    html:not([data-theme='dark']) .admin-dashboard-shell .text-red-600 {
        color: var(--color-danger) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .bg-red-50 {
        background-color: rgba(158, 75, 63, .12) !important;
        border-color: rgba(158, 75, 63, .35) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .bg-emerald-50 {
        background-color: rgba(111, 138, 109, .16) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .bg-blue-50,
    html:not([data-theme='dark']) .admin-dashboard-shell .ring-blue-50 {
        background-color: rgba(139, 154, 139, .16) !important;
        --tw-ring-color: rgba(139, 154, 139, .16) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .text-blue-500 {
        color: var(--color-primary) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .from-slate-900,
    html:not([data-theme='dark']) .admin-dashboard-shell .via-slate-800,
    html:not([data-theme='dark']) .admin-dashboard-shell .to-slate-900 {
        --tw-gradient-from: var(--color-primary) var(--tw-gradient-from-position) !important;
        --tw-gradient-via: var(--color-primary-hover) var(--tw-gradient-via-position) !important;
        --tw-gradient-to: var(--color-primary-active) var(--tw-gradient-to-position) !important;
        border-color: var(--color-primary-active) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell [class*="from-[#22324A]"],
    html:not([data-theme='dark']) .admin-dashboard-shell [class*="to-[#1A2636]"] {
        --tw-gradient-from: var(--color-primary) var(--tw-gradient-from-position) !important;
        --tw-gradient-to: var(--color-accent) var(--tw-gradient-to-position) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .rank-badge {
        background: var(--color-bg-muted);
        border-color: var(--color-border);
        color: var(--color-primary);
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls select,
    html:not([data-theme='dark']) .admin-dashboard-shell .input-custom {
        background-color: var(--color-bg-surface);
        border-color: var(--color-border);
        color: var(--color-text-primary);
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls select:focus,
    html:not([data-theme='dark']) .admin-dashboard-shell .input-custom:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(62, 74, 61, .18);
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .stat-card > .w-9,
    html:not([data-theme='dark']) .admin-dashboard-shell .w-12.h-12.rounded-2xl,
    html:not([data-theme='dark']) .admin-dashboard-shell .w-16.h-16 {
        background-color: var(--color-bg-muted) !important;
        border-color: var(--color-border) !important;
        color: var(--color-primary) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .shadow-2xl,
    html:not([data-theme='dark']) .admin-dashboard-shell .shadow-sm,
    html:not([data-theme='dark']) .admin-dashboard-shell .hover\:shadow-sm:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .hover\:shadow-md:hover {
        box-shadow: 0 10px 28px rgba(62, 74, 61, .08) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .absolute.left-\[23px\] {
        background-color: var(--color-border) !important;
    }
</style>

<div class="dashboard-fit-page">
<div class="admin-dashboard-shell w-full space-y-6 antialiased text-slate-900 animate-float-up">
    <section class="admin-dashboard-greeting">
        <div class="admin-dashboard-greeting__title">
            <button
                type="button"
                id="mobileSidebarToggle"
                class="mobile-menu-btn"
                aria-label="Open navigation"
                aria-expanded="false"
                aria-controls="appSidebar"
            >
                <i class="bi bi-list"></i>
            </button>
            <div>
                <h1>Good morning, {{ $adminFirstName }}</h1>
                <p>{{ $adminSubtitle }}</p>
            </div>
        </div>
        <div class="admin-dashboard-greeting__tools">
            <div class="admin-dashboard-date-pill"><i class="bi bi-calendar3"></i> {{ $adminTodayLabel }}</div>
            @include('partials.topbar-notifications')
        </div>
    </section>

    @if($errors->any())
        <div class="bg-red-50 border border-red-100 p-4 text-red-700 rounded-2xl text-[11px] font-black uppercase tracking-widest flex items-center gap-3 shadow-sm">
            <i class="bi bi-exclamation-octagon-fill text-lg"></i>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Filters + Quick Actions --}}
    <div class="card-custom admin-top-controls">
        <form method="GET" action="{{ url('/admin') }}" class="admin-top-controls-form">
            <select name="branch_id" onchange="this.form.submit()" class="input-custom w-48" @if($isBranchAdmin) disabled @endif>
                <option value="">{{ $isMainAdmin ? 'All Branches' : 'Assigned Branch' }}</option>
                @foreach($branches ?? [] as $branch)
                    <option value="{{ $branch->id }}" {{ (string) ($selectedBranchId ?? '') === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->branch_code }} - {{ $branch->branch_name }}
                    </option>
                @endforeach
            </select>

            <select name="date_filter" onchange="this.form.submit()" class="input-custom w-40">
                <option value="all" {{ ($selectedDateFilter ?? 'this_month') === 'all' ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ ($selectedDateFilter ?? 'this_month') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="this_week" {{ ($selectedDateFilter ?? 'this_month') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ ($selectedDateFilter ?? 'this_month') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="this_year" {{ ($selectedDateFilter ?? 'this_month') === 'this_year' ? 'selected' : '' }}>This Year</option>
            </select>
            <select class="input-custom w-40 md:hidden" aria-hidden="true" disabled>
                <option value="all" {{ ($selectedDateFilter ?? 'this_month') === 'all' ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ ($selectedDateFilter ?? 'this_month') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="this_week" {{ ($selectedDateFilter ?? 'this_month') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ ($selectedDateFilter ?? 'this_month') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="this_year" {{ ($selectedDateFilter ?? 'this_month') === 'this_year' ? 'selected' : '' }}>This Year</option>
            </select>

            <div class="flex items-center gap-2">
                <a href="{{ url('/admin') }}" class="btn-secondary-custom btn-sm">Reset</a>
            </div>
        </form>

        @if($isMainAdmin)
        <div class="admin-top-controls-actions">
            <a href="{{ route('admin.users.create', ['return_to' => request()->fullUrl()]) }}" class="btn-secondary-custom btn-sm flex items-center gap-2">
                <i class="bi bi-person-plus-fill text-sm"></i> Add User
            </a>
            <a href="{{ route('admin.branches.create', ['return_to' => request()->fullUrl()]) }}" class="btn-primary-custom btn-sm flex items-center gap-2">
                <i class="bi bi-diagram-3-fill text-sm"></i> New Branch
            </a>
        </div>
        @endif
    </div>

    {{-- 2. FINANCIAL SUMMARY CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 lg:gap-6 admin-section-block">
        {{-- Collected Amount --}}
        <a href="{{ $collectedMonitoringUrl }}" class="dashboard-click-card admin-financial-card flex flex-col justify-between">
            <div class="admin-financial-card__head flex items-center justify-between gap-4">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">{{ $isMainAdmin ? 'ALL-BRANCH SUMMARY' : 'ASSIGNED BRANCH' }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 text-lg">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>

            <div>
                <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-black mb-2">{{ $isMainAdmin ? 'Total Collected' : 'Collected Amount' }}</p>
                <h4 class="font-black font-heading tracking-tight">
                    <span class="text-emerald-400 font-sans mr-1">₱</span>{{ number_format((float) ($totalCollected ?? 0), 2) }}
                </h4>
                <div class="dashboard-card-link-copy">View details -></div>
            </div>
        </a>

        {{-- Outstanding Balance --}}
        <a href="{{ $outstandingMonitoringUrl }}" class="dashboard-click-card admin-financial-card flex flex-col justify-between">
            <div class="admin-financial-card__head flex items-center justify-between gap-4">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200">
                    <i class="bi bi-exclamation-circle-fill text-amber-600 text-[10px]"></i>
                    <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-[0.16em]">UNSETTLED BALANCE</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 text-lg">
                    <i class="bi bi-graph-down-arrow"></i>
                </div>
            </div>

            <div>
                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500 font-semibold mb-2">{{ $isMainAdmin ? 'Total Outstanding Balance' : 'Outstanding Balance' }}</p>
                <h4 class="font-black font-heading tracking-tight">
                    <span class="font-sans mr-1">₱</span>{{ number_format((float) ($totalOutstanding ?? 0), 2) }}
                </h4>
                <div class="dashboard-card-link-copy">Review balances -></div>
            </div>
        </a>
    </div>

    {{-- 3. CASE METRICS --}}
    <section class="space-y-3 section admin-section-block">
        <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Case and Payment Summary</h3>
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            @php
                $caseStats = [
                    ['label' => 'Total Records', 'val' => $totalCases ?? 0, 'icon' => 'bi-folder2-open', 'color' => 'text-slate-900', 'url' => $caseRecordsUrl],
                    ['label' => 'Ongoing Cases', 'val' => $ongoingCases ?? 0, 'icon' => 'bi-arrow-repeat', 'color' => 'text-slate-900', 'url' => $activeCasesUrl],
                    ['label' => 'Paid in Full', 'val' => $paidCases ?? 0, 'icon' => 'bi-check-circle', 'color' => 'text-emerald-600', 'url' => $paidMonitoringUrl],
                    ['label' => 'Partially Paid', 'val' => $partialCases ?? 0, 'icon' => 'bi-pie-chart', 'color' => 'text-amber-600', 'url' => $partialMonitoringUrl],
                    ['label' => 'Unpaid Cases', 'val' => $unpaidCases ?? 0, 'icon' => 'bi-exclamation-triangle', 'color' => 'text-red-600', 'url' => $unpaidMonitoringUrl],
                    ['label' => $isMainAdmin ? 'Total Contract Value' : 'Total Service Value', 'val' => number_format((float) ($totalServiceValue ?? $totalSales ?? 0), 2), 'icon' => 'bi-cash-coin', 'color' => 'text-emerald-600', 'is_money' => true],
                ];
            @endphp

            @foreach($caseStats as $s)
                @php
                    $statInner = 'admin-dashboard-stat-' . \Illuminate\Support\Str::slug($s['label']);
                @endphp
                @if(isset($s['url']))
                    <a href="{{ $s['url'] }}" class="stat-card flex flex-col gap-3 min-h-[140px]" aria-labelledby="{{ $statInner }}">
                @else
                    <div class="stat-card flex flex-col gap-3 min-h-[140px]">
                @endif
                    <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-base">
                        <i class="bi {{ $s['icon'] }}"></i>
                    </div>
                    <div>
                        <div id="{{ $statInner }}" class="stat-label mb-1">{{ $s['label'] }}</div>
                        <div class="stat-value {{ $s['color'] }}">
                            {{ isset($s['is_money']) ? '₱' : '' }}{{ $s['val'] }}
                        </div>
                        @if(isset($s['url']))
                            <div class="dashboard-card-link-copy">View details -></div>
                        @endif
                    </div>
                @if(isset($s['url']))
                    </a>
                @else
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    {{-- 4. BRANCH PERFORMANCE BOARD --}}
    @if($isMainAdmin)
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 section admin-section-block">
        
        {{-- Left: Elegant Service Amount List --}}
        <div class="xl:col-span-7 card-custom flex flex-col">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">Service Amount by Branch</h3>
                <i class="bi bi-trophy text-xl text-amber-400"></i>
            </div>
            
            <div class="space-y-4 flex-1">
                @foreach($branchRevenueCards ?? [] as $index => $card)
                    <div class="flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:bg-slate-50 transition-all group">
                        <div class="flex items-center gap-4">
                            <div class="rank-badge {{ $index === 0 ? 'bg-[var(--accent)]' : '' }}">#{{ $index + 1 }}</div>
                            <div>
                                <h5 class="text-sm font-black text-slate-900 tracking-tight">{{ $card['branch']->branch_name ?? 'Branch' }}</h5>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">{{ $card['branch']->branch_code ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h4 class="text-xl font-black text-slate-900 font-heading">₱{{ number_format((float) ($card['sales'] ?? 0), 2) }}</h4>
                            <p class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest mt-0.5 group-hover:animate-pulse">Total Service Value</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Right: Case Volume Progress --}}
        <div class="xl:col-span-5 card-custom flex flex-col">
            <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 mb-8 font-heading">Case Volume Distribution</h3>
            
            <div class="space-y-8 flex-1 flex flex-col justify-center">
                @php
                    $volumeCollection = collect($caseVolume ?? []);
                    $maxVolume = max(1, (float) $volumeCollection->max('count'));
                @endphp
                
                @forelse($volumeCollection as $row)
                    @php
                        $count = is_array($row) ? ($row['count'] ?? 0) : ($row->count ?? 0);
                        $branchCode = is_array($row) ? ($row['branch_code'] ?? '') : ($row->branch_code ?? '');
                        $branchName = is_array($row) ? ($row['branch_name'] ?? '') : ($row->branch_name ?? '');
                        $width = $maxVolume > 0 ? ($count / $maxVolume) * 100 : 0;
                    @endphp
                    <div class="group">
                        <div class="flex items-end justify-between mb-2">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-0.5">{{ $branchCode }}</span>
                                <span class="text-xs font-black text-slate-900 truncate pr-4">{{ $branchName }}</span>
                            </div>
                            <span class="text-lg font-black text-[#3E4A3D] font-heading">{{ $count }}</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-[#22324A] to-[#1A2636] transition-all duration-1000 w-0 group-hover:brightness-110 relative" style="width: {{ $width }}%">
                                <div class="absolute top-0 right-0 bottom-0 w-8 bg-gradient-to-l from-white/30 to-transparent"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <div class="w-16 h-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center text-slate-300 text-2xl mb-4">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No branch data available</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    @else
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 section admin-section-block">
        <div class="xl:col-span-7 card-custom flex flex-col">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">Today's Schedules</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">{{ $adminBranchLabel }}</p>
                </div>
                <a href="{{ $todaySchedulesUrl }}" class="btn-secondary-custom btn-sm">View all</a>
            </div>

            <div class="space-y-3">
                @forelse(($todaySchedule ?? collect())->take(5) as $item)
                    <a href="{{ $todaySchedulesUrl }}" class="dashboard-click-card flex items-center justify-between p-4 rounded-2xl border border-slate-100">
                        <div>
                            <p class="text-sm font-black text-slate-900">{{ $item['title'] ?? 'Scheduled service' }}</p>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">
                                {{ $item['case_code'] ?? 'Case record' }} - {{ $item['client_name'] ?? 'Client' }}
                            </p>
                        </div>
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            {{ isset($item['date']) && $item['date'] ? $item['date']->format('h:i A') : 'Today' }}
                        </span>
                    </a>
                @empty
                    <div class="text-center py-8">
                        <div class="w-16 h-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center text-slate-300 text-2xl mb-4">
                            <i class="bi bi-calendar2-check"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No services scheduled today.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="xl:col-span-5 card-custom flex flex-col">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">Needs Attention</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">With Balance</p>
                </div>
                <a href="{{ $balanceAttentionUrl }}" class="btn-secondary-custom btn-sm">Open</a>
            </div>

            <div class="space-y-3">
                @forelse(($attentionReminders ?? collect())->take(5) as $item)
                    <a href="{{ $balanceAttentionUrl }}" class="dashboard-click-card block p-4 rounded-2xl border border-slate-100">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-black text-slate-900">{{ $item['case_code'] ?? 'Case record' }}</p>
                            <span class="text-[10px] font-black text-red-600 uppercase tracking-widest">Balance</span>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">
                            {{ $item['deceased_name'] ?? 'Client' }} - {{ $item['client_name'] ?? 'N/A' }}
                        </p>
                    </a>
                @empty
                    <div class="text-center py-8">
                        <div class="w-16 h-16 mx-auto bg-emerald-50 rounded-full flex items-center justify-center text-emerald-600 text-2xl mb-4">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No balance alerts for this branch.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    {{-- 5. SYSTEM AUDIT TIMELINE (Modern SaaS Look) --}}
    <div class="bg-white border border-slate-100 rounded-[2.5rem] p-8 lg:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.03)]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 gap-4">
            <div>
                <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">{{ $isMainAdmin ? 'System Audit Log' : 'Recent Branch Activity' }}</h3>
                <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">{{ $isMainAdmin ? 'Monitoring User Actions & Security' : $adminBranchLabel }}</p>
            </div>
            @if($isMainAdmin)
            <a href="{{ route('admin.cases.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-slate-50 border border-slate-200 text-[11px] font-black text-slate-500 uppercase tracking-widest hover:text-slate-900 hover:bg-slate-100 rounded-full transition-colors w-full sm:w-auto">
                Open Master Records
            </a>
            @else
            <a href="{{ $caseRecordsUrl }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-slate-50 border border-slate-200 text-[11px] font-black text-slate-500 uppercase tracking-widest hover:text-slate-900 hover:bg-slate-100 rounded-full transition-colors w-full sm:w-auto">
                Open Case Records
            </a>
            @endif
        </div>

        <div class="relative max-w-4xl mx-auto">
            {{-- Vertical Line --}}
            <div class="absolute left-[23px] top-4 bottom-4 w-px bg-slate-100"></div>

            <div class="space-y-6 relative">
                @php
                    $mockupLogs = [
                        ['time' => '10 mins ago', 'user' => 'Admin Juan', 'action' => 'Approved package void request for Case #1029', 'icon' => 'bi-shield-check', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50', 'ring' => 'ring-emerald-50'],
                        ['time' => '1 hour ago', 'user' => 'Staff Maria', 'action' => 'Encoded initial payment (₱15,000) for Case #1030', 'icon' => 'bi-cash-stack', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50', 'ring' => 'ring-blue-50'],
                        ['time' => '3 hours ago', 'user' => 'Owner', 'action' => 'Updated Executive Package pricing matrix', 'icon' => 'bi-tags-fill', 'color' => 'text-[#3E4A3D]', 'bg' => 'bg-[#3E4A3D]/10', 'ring' => 'ring-[#3E4A3D]/5'],
                        ['time' => 'Yesterday', 'user' => 'Staff Pedro', 'action' => 'Created new intake record for Deceased: Dela Cruz', 'icon' => 'bi-file-earmark-plus-fill', 'color' => 'text-slate-500', 'bg' => 'bg-slate-100', 'ring' => 'ring-slate-50'],
                    ];
                @endphp

                @forelse($auditLogs ?? $mockupLogs as $log)
                    @php
                        $isArray = is_array($log);
                        $logAction = $isArray ? ($log['action'] ?? 'No action') : ($log->action_label ?? $log->action ?? 'No action');
                        $logUser = $isArray ? ($log['user'] ?? 'System') : ($log->actor?->name ?? 'System');
                        $logTime = $isArray ? ($log['time'] ?? '-') : ($log->created_at?->diffForHumans() ?? '-');
                        $logIcon = $isArray ? ($log['icon'] ?? 'bi-journal-text') : ($log->icon ?? 'bi-journal-text');
                        $logColor = $isArray ? ($log['color'] ?? 'text-slate-500') : ($log->color ?? 'text-slate-500');
                        $logBg = $isArray ? ($log['bg'] ?? 'bg-white') : ($log->bg ?? 'bg-white');
                        $logRing = $isArray ? ($log['ring'] ?? 'ring-white') : ($log->ring ?? 'ring-white');
                    @endphp
                    <div class="flex items-start gap-5 group">
                        {{-- Timeline Node --}}
                        <div class="relative z-10 w-12 h-12 rounded-full {{ $logBg }} flex items-center justify-center {{ $logColor }} text-lg shrink-0 shadow-sm ring-4 {{ $logRing }} group-hover:scale-110 transition-transform">
                            <i class="bi {{ $logIcon }}"></i>
                        </div>
                        
                        {{-- Log Content --}}
                        <div class="flex-1 bg-white border border-slate-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $logAction }}</p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">
                                        Action by <span class="text-slate-700">{{ $logUser }}</span>
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-50 border border-slate-100 text-[9px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">
                                    {{ $logTime }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="pl-16 py-4 text-slate-400">
                        <p class="text-[10px] font-black uppercase tracking-widest">No recent {{ $isMainAdmin ? 'system activities' : 'branch activities' }}.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 6. SYSTEM STATUS FOOTER --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-0 admin-section-block">
        @php
            $configStats = $isMainAdmin
                ? [
                    ['label' => 'Network Branches', 'val' => $branchCount ?? 0, 'icon' => 'bi-building', 'url' => route('admin.branches.index')],
                    ['label' => 'Active Terminals', 'val' => $activeStaffCount ?? 0, 'icon' => 'bi-laptop'],
                    ['label' => 'Service Catalogs', 'val' => $activePackageCount ?? 0, 'icon' => 'bi-layers', 'url' => route('admin.packages.index')],
                ]
                : [
                    ['label' => 'Open Case Records', 'val' => $totalCases ?? 0, 'icon' => 'bi-folder2-open', 'url' => $caseRecordsUrl],
                    ['label' => 'Payment Monitoring', 'val' => 'Review balances', 'icon' => 'bi-credit-card', 'url' => route('admin.payment-monitoring', $branchLinkParams)],
                    ['label' => 'Service Catalogs', 'val' => $activePackageCount ?? 0, 'icon' => 'bi-layers', 'url' => route('admin.packages.index')],
                ];
        @endphp
        
        @foreach($configStats as $cs)
            @if(isset($cs['url']))
                <a href="{{ $cs['url'] }}" class="dashboard-click-card bg-transparent border border-slate-200/60 rounded-[2rem] p-6 flex items-center gap-5">
            @else
                <div class="bg-transparent border border-slate-200/60 rounded-[2rem] p-6 flex items-center gap-5 transition-all cursor-default">
            @endif
                <div class="w-12 h-12 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 text-xl shadow-sm">
                    <i class="bi {{ $cs['icon'] }}"></i>
                </div>
                <div>
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">{{ $cs['label'] }}</p>
                    <h4 class="text-xl font-black text-slate-900 font-heading">{{ $cs['val'] }}</h4>
                </div>
            @if(isset($cs['url']))
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>

</div>
</div>
@endsection
