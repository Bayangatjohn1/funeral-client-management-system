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
    $adminBranch = ($selectedBranchId ?? null) ? ($dashboardBranch ?? null) : ($isBranchAdmin ? $adminUser?->branch : null);
    $adminBranchLabel = $adminBranch
        ? trim($adminBranch->branch_code . ' - ' . $adminBranch->branch_name)
        : 'All Branches';
    $adminSubtitle = $isMainAdmin
        ? 'Managing Main Branch and all branch operations'
        : 'Managing branch operations - ' . $adminBranchLabel;
    $branchLinkParams = [];
    if (($selectedBranchId ?? null)) {
        $branchLinkParams['branch_id'] = $selectedBranchId;
    } elseif ($isBranchAdmin && ($adminBranch?->id ?? null)) {
        $branchLinkParams['branch_id'] = $adminBranch->id;
    }
    $paymentDateLinkParams = ['date_preset' => 'month'];
    $caseDateLinkParams = [];
    $caseRecordsUrl = route('admin.cases.index', $branchLinkParams);
    $activeCasesUrl = route('admin.cases.index', array_merge($branchLinkParams, ['case_status' => 'ACTIVE']));
    $branchCollectionUrl = fn ($branchId) => route('admin.payment-monitoring', array_merge(
        ['branch_id' => (int) $branchId],
        $paymentDateLinkParams,
        ['payment_status' => 'HAS_PAYMENT', 'tab' => 'transactions']
    ));
    $branchServicesUrl = fn ($branchId) => route('admin.cases.index', array_merge(
        ['branch_id' => (int) $branchId],
        $caseDateLinkParams
    ));
    $paymentSummaryScopeParams = ['case_scope' => 'active_completed'];
    $paidMonitoringUrl = route('admin.payment-monitoring', array_merge($branchLinkParams, $paymentSummaryScopeParams, ['payment_status' => 'PAID']));
    $partialMonitoringUrl = route('admin.payment-monitoring', array_merge($branchLinkParams, $paymentSummaryScopeParams, ['payment_status' => 'PARTIAL']));
    $collectedMonitoringUrl = route('admin.payment-monitoring', array_merge(
        $branchLinkParams,
        $paymentDateLinkParams,
        ['payment_status' => 'HAS_PAYMENT', 'tab' => 'transactions']
    ));
    $outstandingMonitoringUrl = route('admin.cases.index', array_merge(
        $branchLinkParams,
        $caseDateLinkParams,
        ['payment_status' => 'WITH_BALANCE']
    ));
    $todaySchedulesUrl = route('admin.reminders.index', array_merge($branchLinkParams, ['tab' => 'today', 'alert_type' => 'all']));
    $balancePaymentMonitoringUrl = route('admin.payment-monitoring', array_merge($branchLinkParams, $paymentSummaryScopeParams, ['payment_status' => 'WITH_BALANCE', 'tab' => 'summary']));
    $paymentProgressUrl = route('admin.payment-monitoring', array_merge($branchLinkParams, $paymentSummaryScopeParams, ['tab' => 'summary']));
    $allRemindersUrl = route('admin.reminders.index', $branchLinkParams);
    $balanceCases = collect($balanceReminders ?? [])
        ->unique('case_id')
        ->sortBy(function ($item) {
            $paymentStatus = strtoupper((string) data_get($item, 'case.payment_status', 'UNPAID'));

            return ($paymentStatus === 'PARTIAL' ? '0-' : '1-').(string) ($item['case_code'] ?? '');
        })
        ->values();
    $upcomingScheduleItems = collect($upcomingSchedule ?? [])->values();
    $upcomingCaseGroups = $upcomingScheduleItems
        ->groupBy(fn ($item) => ($item['branch_id'] ?? 'branch').'-'.($item['case_id'] ?? 'case'))
        ->map(function ($events) {
            $orderedEvents = $events->sortBy('sort_date')->values();

            return [
                'case_id' => $orderedEvents->first()['case_id'] ?? null,
                'case_code' => $orderedEvents->first()['case_code'] ?? 'N/A',
                'deceased_name' => $orderedEvents->first()['deceased_name'] ?? 'N/A',
                'events' => $orderedEvents,
                'next_at' => $orderedEvents->first()['date'] ?? null,
            ];
        })
        ->sortBy('next_at')
        ->values();
    $todayScheduleItems = collect($todaySchedule ?? [])->values();
    $currentlyInWakeItems = collect($currentlyInWake ?? [])->values();
    $showBranchComparison = false;
    $showItemBranch = $isMainAdmin && !($selectedBranchId ?? null);
    $collectionRate = (float) ($totalServiceValue ?? 0) > 0
        ? round(((float) ($summaryCollectedTotal ?? 0) / (float) $totalServiceValue) * 100)
        : 0;
    $servicesWithBalance = (int) ($partialCases ?? 0) + (int) ($unpaidCases ?? 0);
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

    /* UI/UX refinement: simpler hierarchy, consistent card language, quieter chrome. */
    .admin-dashboard-shell {
        padding: .75rem clamp(.75rem, 1.2vw, 1.15rem) 1.4rem;
        color: var(--color-text-primary);
        background:
            linear-gradient(90deg, rgba(62, 74, 61, 0.035) 0 1px, transparent 1px),
            linear-gradient(180deg, rgba(62, 74, 61, 0.03) 0 1px, transparent 1px),
            repeating-linear-gradient(135deg, rgba(62, 74, 61, 0.018) 0 1px, transparent 1px 12px);
        background-size: 44px 44px, 44px 44px, 16px 16px;
    }

    .admin-dashboard-shell *,
    .admin-dashboard-shell *::before,
    .admin-dashboard-shell *::after {
        box-shadow: none !important;
    }

    .admin-dashboard-shell a[href],
    .admin-dashboard-shell button,
    .admin-dashboard-shell select,
    .admin-dashboard-shell [role="button"],
    .admin-dashboard-shell .dashboard-click-card,
    .admin-dashboard-shell .stat-card {
        cursor: pointer;
    }

    .admin-dashboard-shell button:disabled,
    .admin-dashboard-shell select:disabled,
    .admin-dashboard-shell [aria-disabled="true"] {
        cursor: not-allowed;
    }

    .admin-dashboard-shell > :not([hidden]) ~ :not([hidden]) {
        margin-top: 1rem !important;
    }

    .admin-dashboard-shell .card-custom,
    .admin-dashboard-shell .stat-card,
    .admin-dashboard-shell .admin-top-controls,
    .admin-dashboard-shell > .bg-white,
    .admin-dashboard-shell .admin-section-block .bg-transparent,
    .admin-dashboard-greeting,
    .admin-dashboard-date-pill,
    .admin-dashboard-shell .admin-financial-card,
    .admin-dashboard-shell .dashboard-click-card,
    .admin-dashboard-shell .rounded-\[2\.5rem\],
    .admin-dashboard-shell .rounded-\[2rem\],
    .admin-dashboard-shell .rounded-2xl {
        border-radius: 8px !important;
    }

    .admin-dashboard-shell .card-custom,
    .admin-dashboard-shell .stat-card,
    .admin-dashboard-shell .admin-top-controls,
    .admin-dashboard-shell > .bg-white,
    .admin-dashboard-shell .admin-financial-card {
        box-shadow: none !important;
    }

    .admin-dashboard-greeting {
        padding: 1rem;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    .admin-dashboard-greeting h1 {
        font-size: clamp(1.45rem, 2vw, 1.85rem);
        font-weight: 700;
        letter-spacing: 0;
        color: var(--color-text-primary);
    }

    .admin-dashboard-greeting p {
        margin-top: .25rem;
        font-size: .92rem;
        font-weight: 600;
        color: var(--color-text-secondary);
    }

    .admin-dashboard-shell .admin-top-controls {
        padding: .85rem 1rem;
        gap: .75rem;
    }

    .admin-dashboard-shell .admin-top-controls-form,
    .admin-dashboard-shell .admin-top-controls-actions {
        gap: .55rem;
    }

    .admin-dashboard-shell .admin-financial-card {
        min-height: 124px !important;
        padding: 1rem !important;
    }

    .admin-dashboard-shell .admin-financial-card h4 {
        font-size: clamp(1.45rem, 2.4vw, 2rem) !important;
    }

    .admin-dashboard-shell .stat-card {
        min-height: 128px;
        padding: 1rem;
        gap: .65rem !important;
    }

    .admin-dashboard-shell .stat-value {
        font-size: clamp(1.35rem, 2vw, 1.85rem);
        font-weight: 800;
        letter-spacing: 0;
        color: var(--color-text-primary);
    }

    .admin-dashboard-shell .dashboard-card-link-copy {
        margin-top: auto;
        color: var(--color-text-secondary);
        font-size: .72rem;
        letter-spacing: 0;
        text-transform: none;
    }

    .admin-dashboard-shell a.stat-card:hover,
    .admin-dashboard-shell .dashboard-click-card:hover {
        transform: none;
    }

    .admin-dashboard-shell .admin-section-block h3,
    .admin-dashboard-shell .admin-section-block h4,
    .admin-dashboard-shell .admin-section-block h5 {
        letter-spacing: 0 !important;
    }

    .admin-dashboard-shell .admin-section-block h3 {
        font-size: .95rem !important;
        font-weight: 700 !important;
        text-transform: none !important;
        color: var(--color-text-primary) !important;
    }

    .admin-dashboard-shell .admin-section-block p {
        letter-spacing: 0 !important;
        text-transform: none !important;
        color: var(--color-text-secondary) !important;
    }

    .admin-dashboard-shell .rank-badge {
        border-radius: 8px;
        min-width: 38px;
    }

    .admin-dashboard-shell .admin-section-block .flex.items-center.justify-between.p-4,
    .admin-dashboard-shell .admin-section-block .dashboard-click-card {
        transition: background-color .14s ease, border-color .14s ease, color .14s ease;
    }

    .admin-dashboard-shell a[href],
    .admin-dashboard-shell button,
    .admin-dashboard-shell select,
    .admin-dashboard-shell [role="button"],
    .admin-dashboard-shell .stat-card,
    .admin-dashboard-shell .dashboard-click-card,
    .admin-dashboard-shell .admin-financial-card,
    .admin-dashboard-shell .admin-top-controls select,
    .admin-dashboard-shell .input-custom,
    .admin-dashboard-shell .admin-date-filter-link,
    .admin-dashboard-shell .topbar-notification {
        transition: background-color .14s ease, border-color .14s ease, color .14s ease;
    }

    .admin-dashboard-shell a[href]:hover,
    .admin-dashboard-shell button:hover,
    .admin-dashboard-shell [role="button"]:hover,
    .admin-dashboard-shell .stat-card:hover,
    .admin-dashboard-shell .dashboard-click-card:hover,
    .admin-dashboard-shell .admin-financial-card:hover,
    .admin-dashboard-shell .topbar-notification:hover {
        transform: none !important;
        box-shadow: none !important;
        filter: none !important;
    }

    .admin-dashboard-shell a.stat-card:hover,
    .admin-dashboard-shell .dashboard-click-card:hover,
    .admin-dashboard-shell .admin-financial-card:hover,
    .admin-dashboard-shell .admin-top-controls select:hover,
    .admin-dashboard-shell .input-custom:hover,
    .admin-dashboard-shell .topbar-notification:hover {
        background-color: var(--color-bg-muted) !important;
        border-color: var(--color-border-strong) !important;
        color: var(--color-text-primary) !important;
    }

    .admin-dashboard-shell .is-active,
    .admin-dashboard-shell .active,
    .admin-dashboard-shell [aria-pressed="true"],
    .admin-dashboard-shell select:focus,
    .admin-dashboard-shell .input-custom:focus {
        border-color: var(--color-primary) !important;
        background-color: color-mix(in srgb, var(--color-primary) 10%, var(--color-bg-surface)) !important;
    }

    html[data-theme='dark'] .admin-dashboard-shell {
        background:
            linear-gradient(90deg, rgba(148, 163, 184, 0.04) 0 1px, transparent 1px),
            linear-gradient(180deg, rgba(148, 163, 184, 0.035) 0 1px, transparent 1px),
            repeating-linear-gradient(135deg, rgba(148, 163, 184, 0.026) 0 1px, transparent 1px 12px);
        background-size: 44px 44px, 44px 44px, 16px 16px;
    }

    .admin-dashboard-shell,
    .admin-dashboard-shell *,
    .admin-dashboard-shell *::before,
    .admin-dashboard-shell *::after,
    .admin-dashboard-shell [class*="shadow"],
    .admin-dashboard-shell [class*="drop-shadow"],
    .admin-dashboard-shell [class*="hover:shadow"] {
        box-shadow: none !important;
        filter: none !important;
    }

    .admin-dashboard-shell {
        font-family: var(--font-body);
    }

    .admin-dashboard-shell h1,
    .admin-dashboard-shell h2,
    .admin-dashboard-shell h3,
    .admin-dashboard-shell h4,
    .admin-dashboard-shell h5,
    .admin-dashboard-shell .stat-value,
    .admin-dashboard-shell .admin-financial-card h4 {
        font-family: var(--font-heading) !important;
        letter-spacing: 0 !important;
    }

    .admin-dashboard-shell label,
    .admin-dashboard-shell th,
    .admin-dashboard-shell .rank-badge,
    .admin-dashboard-shell .dashboard-card-link-copy,
    .admin-dashboard-shell .admin-date-filter-link,
    .admin-dashboard-shell p[class*="font-black"],
    .admin-dashboard-shell span[class*="font-black"],
    .admin-dashboard-shell div[class*="font-black"],
    .admin-dashboard-shell p[class*="font-bold"],
    .admin-dashboard-shell span[class*="font-bold"],
    .admin-dashboard-shell [class*="tracking-widest"] {
        font-weight: 650 !important;
        letter-spacing: 0 !important;
    }

    .admin-dashboard-shell a[href]:focus,
    .admin-dashboard-shell button:focus,
    .admin-dashboard-shell select:focus,
    .admin-dashboard-shell input:focus,
    .admin-dashboard-shell [role="button"]:focus,
    .admin-dashboard-shell a[href]:focus-visible,
    .admin-dashboard-shell button:focus-visible,
    .admin-dashboard-shell select:focus-visible,
    .admin-dashboard-shell input:focus-visible,
    .admin-dashboard-shell [role="button"]:focus-visible {
        outline: none !important;
        outline-offset: 0 !important;
        box-shadow: none !important;
    }

    .admin-dashboard-shell a.stat-card:hover,
    .admin-dashboard-shell .dashboard-click-card:hover,
    .admin-dashboard-shell .admin-financial-card:hover,
    .admin-dashboard-shell .admin-top-controls select:hover,
    .admin-dashboard-shell .input-custom:hover,
    .admin-dashboard-shell .admin-date-filter-link:hover,
    .admin-dashboard-shell .topbar-notification:hover {
        background-color: #DDE6D8 !important;
        border-color: #8B9A8B !important;
        color: var(--color-text-primary) !important;
    }

    .admin-dashboard-shell .is-active,
    .admin-dashboard-shell .active,
    .admin-dashboard-shell [aria-pressed="true"],
    .admin-dashboard-shell a[href]:focus-visible,
    .admin-dashboard-shell button:focus-visible,
    .admin-dashboard-shell select:focus,
    .admin-dashboard-shell .input-custom:focus {
        border-color: var(--color-primary) !important;
        background-color: #D5DFCF !important;
        color: var(--color-text-primary) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell {
        --dash-card-soft: #D3DEC9;
        --dash-card-alt: #DCE6D6;
        --dash-card-strong: #C7D5BE;
        --dash-card-warm: #E1DFCC;
        --dash-hover: #C5D3BC;
        --dash-active: #B8C9AF;
        --dash-text: #232821;
        --dash-muted: #3F4C3E;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .card-custom,
    html:not([data-theme='dark']) .admin-dashboard-shell .stat-card,
    html:not([data-theme='dark']) .admin-dashboard-shell .dashboard-click-card,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-section-block,
    html:not([data-theme='dark']) .admin-dashboard-shell > .bg-white,
    html:not([data-theme='dark']) .admin-dashboard-shell .bg-white,
    html:not([data-theme='dark']) .admin-dashboard-shell .bg-transparent {
        background: var(--dash-card-soft) !important;
        color: var(--dash-text) !important;
        border-color: #AEBBA8 !important;
        box-shadow: none !important;
        filter: none !important;
        backdrop-filter: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .stat-card:nth-child(even),
    html:not([data-theme='dark']) .admin-dashboard-shell .dashboard-click-card:nth-child(even),
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-section-block:nth-of-type(even) {
        background: var(--dash-card-alt) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card:nth-child(2),
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-section-block .dashboard-click-card:nth-child(odd) {
        background: var(--dash-card-warm) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell h1,
    html:not([data-theme='dark']) .admin-dashboard-shell h2,
    html:not([data-theme='dark']) .admin-dashboard-shell h3,
    html:not([data-theme='dark']) .admin-dashboard-shell h4,
    html:not([data-theme='dark']) .admin-dashboard-shell h5,
    html:not([data-theme='dark']) .admin-dashboard-shell .stat-value,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card h4,
    html:not([data-theme='dark']) .admin-dashboard-shell strong {
        color: var(--dash-text) !important;
        opacity: 1 !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell p,
    html:not([data-theme='dark']) .admin-dashboard-shell small,
    html:not([data-theme='dark']) .admin-dashboard-shell label,
    html:not([data-theme='dark']) .admin-dashboard-shell span,
    html:not([data-theme='dark']) .admin-dashboard-shell .dashboard-card-link-copy,
    html:not([data-theme='dark']) .admin-dashboard-shell [class*="text-slate"],
    html:not([data-theme='dark']) .admin-dashboard-shell [class*="text-gray"] {
        color: var(--dash-muted) !important;
        opacity: 1 !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .stat-card > .w-9,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card__head > div,
    html:not([data-theme='dark']) .admin-dashboard-shell [class*="bg-slate-50"],
    html:not([data-theme='dark']) .admin-dashboard-shell [class*="bg-emerald-50"],
    html:not([data-theme='dark']) .admin-dashboard-shell [class*="bg-red-50"],
    html:not([data-theme='dark']) .admin-dashboard-shell [class*="bg-amber-50"] {
        background: transparent !important;
        box-shadow: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell a.stat-card:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .dashboard-click-card:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls select:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .input-custom:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-date-filter-link:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell button:hover {
        background: var(--dash-hover) !important;
        border-color: #8EA083 !important;
        color: var(--dash-text) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .is-active,
    html:not([data-theme='dark']) .admin-dashboard-shell .active,
    html:not([data-theme='dark']) .admin-dashboard-shell [aria-pressed="true"],
    html:not([data-theme='dark']) .admin-dashboard-shell select:focus,
    html:not([data-theme='dark']) .admin-dashboard-shell .input-custom:focus,
    html:not([data-theme='dark']) .admin-dashboard-shell a[href]:focus-visible,
    html:not([data-theme='dark']) .admin-dashboard-shell button:focus-visible {
        background: var(--dash-active) !important;
        border-color: #3E4A3D !important;
        color: var(--dash-text) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-section-block {
        background: transparent !important;
        border-color: transparent !important;
        box-shadow: none !important;
        filter: none !important;
        backdrop-filter: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls {
        background: var(--dash-card-soft) !important;
        border-color: #AEBBA8 !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .input-custom,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .btn-secondary-custom,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-date-filter-link {
        min-height: 42px;
        background: var(--dash-card-alt) !important;
        border: 1px solid #AEBBA8 !important;
        color: var(--dash-text) !important;
        border-radius: 8px !important;
        box-shadow: none !important;
        filter: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .btn-primary-custom {
        min-height: 42px;
        background: #3E4A3D !important;
        border: 1px solid #3E4A3D !important;
        color: #FFFDF7 !important;
        border-radius: 8px !important;
        box-shadow: none !important;
        filter: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .input-custom:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .btn-secondary-custom:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-date-filter-link:hover {
        background: var(--dash-hover) !important;
        border-color: #8EA083 !important;
        color: var(--dash-text) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .btn-primary-custom:hover {
        background: #2F3A2E !important;
        border-color: #2F3A2E !important;
        color: #FFFDF7 !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card {
        background: var(--dash-card-soft) !important;
        border-color: #AEBBA8 !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card:nth-child(2) {
        background: var(--dash-card-soft) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card__head .inline-flex,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card__head .w-10 {
        background: var(--dash-card-alt) !important;
        border-color: #AEBBA8 !important;
        color: var(--dash-muted) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card:hover {
        background: var(--dash-hover) !important;
        border-color: #8EA083 !important;
    }

    .admin-dashboard-shell .admin-filter-control {
        position: relative;
        display: inline-flex;
        align-items: center;
        min-width: 12rem;
    }

    .admin-dashboard-shell .admin-filter-control.is-period {
        min-width: 10rem;
    }

    .admin-dashboard-shell .admin-filter-control .input-custom {
        width: 100%;
        padding-left: 2.35rem !important;
        padding-right: 2.35rem !important;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .admin-dashboard-shell .admin-filter-icon,
    .admin-dashboard-shell .admin-filter-chevron {
        position: absolute;
        top: 50%;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #3E4A3D;
        opacity: 1;
        pointer-events: none;
        transform: translateY(-50%);
    }

    .admin-dashboard-shell .admin-filter-icon {
        left: .85rem;
        font-size: .95rem;
    }

    .admin-dashboard-shell .admin-filter-chevron {
        right: .85rem;
        font-size: .82rem;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .admin-filter-control:hover .admin-filter-icon,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .admin-filter-control:hover .admin-filter-chevron,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-filter-control:focus-within .admin-filter-icon,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-filter-control:focus-within .admin-filter-chevron {
        color: #232821;
    }

    @media (max-width: 767px) {
        .admin-dashboard-shell .admin-filter-control {
            width: 100%;
            min-width: 0;
        }
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-dashboard-greeting,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls {
        background: var(--dash-card-soft) !important;
        border-color: #AEBBA8 !important;
        overflow-x: clip;
        overflow-y: visible;
    }

    .admin-dashboard-shell .admin-dashboard-greeting,
    .admin-dashboard-shell .admin-top-controls,
    .admin-dashboard-shell .admin-financial-card,
    .admin-dashboard-shell .stat-card {
        overflow: hidden;
    }

    .admin-dashboard-shell .admin-financial-card {
        position: relative;
        min-height: 156px !important;
        padding: 1.1rem 1.2rem 1.1rem 7rem !important;
        justify-content: center !important;
    }

    .admin-dashboard-shell .admin-financial-card::before {
        content: "";
        position: absolute;
        left: -2.7rem;
        top: 50%;
        width: 7.7rem;
        height: 7.7rem;
        border: 1.5px solid #8EA083;
        border-radius: 999px;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .admin-dashboard-shell .admin-financial-card__head {
        margin-bottom: .9rem;
    }

    .admin-dashboard-shell .admin-financial-card__head > .w-10 {
        position: absolute;
        left: 1.05rem;
        top: 50%;
        width: 4rem !important;
        height: 4rem !important;
        border: 0 !important;
        background: transparent !important;
        color: #3E4A3D !important;
        font-size: 2rem !important;
        transform: translateY(-50%);
    }

    .admin-dashboard-shell .admin-financial-card__head > .w-10 i {
        color: inherit !important;
    }

    .admin-dashboard-shell .admin-financial-card .dashboard-card-link-copy {
        display: none !important;
    }

    .admin-dashboard-shell .stat-card .dashboard-card-link-copy {
        display: none !important;
    }

    .admin-dashboard-shell .stat-card {
        justify-content: center;
        min-height: 138px;
    }

    .admin-dashboard-shell .admin-summary-section {
        display: flex;
        flex-direction: column;
        gap: .85rem;
        margin-top: 1.05rem !important;
        padding: 0;
        background: transparent !important;
        border: 0 !important;
        overflow: visible;
    }

    .admin-dashboard-shell .admin-summary-section__title {
        margin: 0;
        padding: 0 .1rem;
        font-family: var(--font-heading);
        font-size: 1.05rem !important;
        font-weight: 700 !important;
        line-height: 1.2;
        text-transform: none !important;
        letter-spacing: 0 !important;
        color: #232821 !important;
    }

    .admin-dashboard-shell .admin-summary-section .grid {
        margin-top: 0 !important;
    }

    @media (max-width: 767px) {
        .admin-dashboard-shell .admin-financial-card {
            padding: 1rem 1rem 1rem 5.5rem !important;
        }

        .admin-dashboard-shell .admin-financial-card::before {
            left: -3.4rem;
        }

        .admin-dashboard-shell .admin-financial-card__head > .w-10 {
            left: .8rem;
            width: 3.5rem !important;
            height: 3.5rem !important;
            font-size: 1.75rem !important;
        }
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card__head > .w-10,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card__head > div.w-10 {
        background: transparent !important;
        border-color: transparent !important;
        color: #3E4A3D !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-financial-card__head > .inline-flex {
        display: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-summary-section,
    html:not([data-theme='dark']) .admin-dashboard-shell section.admin-summary-section,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-summary-section.section,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-summary-section::before,
    html:not([data-theme='dark']) .admin-dashboard-shell .admin-summary-section::after {
        background: transparent !important;
        border-color: transparent !important;
        box-shadow: none !important;
        filter: none !important;
        backdrop-filter: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-summary-section {
        margin-top: .85rem !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-dashboard-date-pill,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-chip,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-card__tag,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-card__pill,
    html:not([data-theme='dark']) .admin-dashboard-shell .rank-badge {
        background: var(--dash-card-alt) !important;
        border-color: #AEBBA8 !important;
        color: var(--dash-text) !important;
        box-shadow: none !important;
        filter: none !important;
        backdrop-filter: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-dashboard-date-pill i,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification i,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-chip i,
    html:not([data-theme='dark']) .admin-dashboard-shell .rank-badge {
        color: #3E4A3D !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification[aria-expanded="true"],
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification.is-active,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-chip:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-card:hover,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-footer-btn:hover {
        background: var(--dash-hover) !important;
        border-color: #8EA083 !important;
        color: var(--dash-text) !important;
        box-shadow: none !important;
        transform: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-chip.is-active,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-footer-btn.is-primary {
        background: var(--dash-active) !important;
        border-color: #3E4A3D !important;
        color: var(--dash-text) !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-menu,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-menu__head,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-menu__chips,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-menu__list,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-menu__footer,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-card,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-empty {
        background: var(--dash-card-soft) !important;
        border-color: #AEBBA8 !important;
        color: var(--dash-text) !important;
        box-shadow: none !important;
        filter: none !important;
        backdrop-filter: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-menu__head small,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-card__text,
    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification-card__meta {
        color: var(--dash-muted) !important;
        opacity: 1 !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .topbar-notification__count {
        background: #9E4B3F !important;
        color: #FFFDF7 !important;
        border-color: #D3DEC9 !important;
        box-shadow: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-summary-section .stat-card > .w-9 {
        width: 2.4rem !important;
        height: 2.4rem !important;
        border-radius: 8px !important;
        background: transparent !important;
        border: 1.5px solid #8EA083 !important;
        color: #3E4A3D !important;
        box-shadow: none !important;
        filter: none !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-summary-section .stat-card > .w-9 i {
        color: #3E4A3D !important;
        font-size: 1rem;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-summary-section .stat-card:hover > .w-9 {
        background: var(--dash-active) !important;
        border-color: #3E4A3D !important;
        color: #232821 !important;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-summary-section .stat-card:hover > .w-9 i {
        color: #232821 !important;
    }

    .admin-dashboard-shell .admin-summary-section .stat-value {
        max-width: 100%;
        overflow-wrap: normal;
        word-break: normal;
    }

    .admin-dashboard-shell .admin-summary-section .stat-value.is-money {
        display: block;
        width: 100%;
        font-size: clamp(1.05rem, 1.25vw, 1.45rem) !important;
        line-height: 1.08;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: clip;
        font-variant-numeric: tabular-nums;
    }

    .admin-dashboard-shell .admin-summary-section .stat-value.is-money .currency-mark {
        margin-right: .08rem;
        font-family: var(--font-body);
    }

    .admin-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .7rem;
    }

    .admin-kpi-card {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: .7rem;
        min-height: 88px;
        padding: .85rem;
        border: 1px solid var(--color-border);
        border-radius: 8px;
        background: var(--color-bg-surface);
        color: var(--color-text-primary);
        text-decoration: none;
        transition: background-color .18s ease, border-color .18s ease;
    }

    .admin-kpi-card:hover,
    .admin-kpi-card:focus-visible {
        border-color: var(--color-primary);
        background: var(--color-bg-muted);
    }

    .admin-kpi-card__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border: 1px solid var(--color-border);
        border-radius: 8px;
        color: var(--color-primary);
        background: var(--color-bg-muted);
    }

    .admin-kpi-card.is-positive .admin-kpi-card__icon {
        color: var(--color-success);
    }

    .admin-kpi-card.is-alert .admin-kpi-card__icon {
        color: var(--color-danger);
    }

    .admin-kpi-card__copy {
        display: flex;
        min-width: 0;
        flex-direction: column;
        gap: .2rem;
    }

    .admin-kpi-card__label {
        color: var(--color-text-secondary);
        font-size: .72rem;
        font-weight: 700;
    }

    .admin-kpi-card__scope {
        color: var(--color-text-secondary);
        font-size: .58rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .admin-kpi-card__value {
        overflow: hidden;
        color: var(--color-text-primary);
        font-family: var(--font-heading);
        font-size: 1.18rem;
        line-height: 1.15;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .admin-kpi-card__arrow {
        color: var(--color-text-secondary);
        font-size: .72rem;
    }

    /* Branch dashboard: warm operational surfaces based on the approved reference. */
    .branch-admin-view .branch-kpi-grid {
        gap: .85rem;
    }

    .branch-admin-view .admin-kpi-card {
        min-height: 116px;
        padding: 1rem 1.05rem;
        gap: .9rem;
        border-radius: 16px;
        border-color: rgba(87, 105, 81, .2);
        box-shadow: 0 8px 22px rgba(41, 58, 42, .06);
    }

    .branch-admin-view .admin-kpi-card:nth-child(1) {
        background: linear-gradient(135deg, #e4eee3, #f1f5ec);
    }

    .branch-admin-view .admin-kpi-card:nth-child(2) {
        background: linear-gradient(135deg, #efe5d2, #f8f1e5);
    }

    .branch-admin-view .admin-kpi-card:nth-child(3) {
        background: linear-gradient(135deg, #eaded5, #f5ebe4);
    }

    .branch-admin-view .admin-kpi-card:nth-child(4) {
        background: linear-gradient(135deg, #e7e5d8, #f3f0e7);
    }

    .branch-admin-view .admin-kpi-card:hover,
    .branch-admin-view .admin-kpi-card:focus-visible {
        border-color:#55745e;
        box-shadow:0 12px 28px rgba(41, 58, 42, .14);
        outline:2px solid rgba(85, 116, 94, .18);
        outline-offset:2px;
    }

    .branch-admin-view .admin-kpi-card:nth-child(1):hover,
    .branch-admin-view .admin-kpi-card:nth-child(1):focus-visible { background:linear-gradient(135deg, #d4e5d2, #e8f0e2); }
    .branch-admin-view .admin-kpi-card:nth-child(2):hover,
    .branch-admin-view .admin-kpi-card:nth-child(2):focus-visible { background:linear-gradient(135deg, #e8d8bb, #f2e7d3); }
    .branch-admin-view .admin-kpi-card:nth-child(3):hover,
    .branch-admin-view .admin-kpi-card:nth-child(3):focus-visible { background:linear-gradient(135deg, #e2cec2, #f0ddd4); }
    .branch-admin-view .admin-kpi-card:nth-child(4):hover,
    .branch-admin-view .admin-kpi-card:nth-child(4):focus-visible { background:linear-gradient(135deg, #ddd9c8, #ebe6d8); }

    .branch-admin-view .admin-kpi-card:hover .admin-kpi-card__arrow,
    .branch-admin-view .admin-kpi-card:focus-visible .admin-kpi-card__arrow {
        background:#315f43;
        color:#fff;
    }

    .branch-admin-view .admin-kpi-card:nth-child(1):hover .admin-kpi-card__arrow,
    .branch-admin-view .admin-kpi-card:nth-child(1):focus-visible .admin-kpi-card__arrow {
        background:#315f43;
    }

    .branch-admin-view .admin-kpi-card:nth-child(2):hover .admin-kpi-card__arrow,
    .branch-admin-view .admin-kpi-card:nth-child(2):focus-visible .admin-kpi-card__arrow {
        background:#8b6a37;
    }

    .branch-admin-view .admin-kpi-card:nth-child(3):hover .admin-kpi-card__arrow,
    .branch-admin-view .admin-kpi-card:nth-child(3):focus-visible .admin-kpi-card__arrow {
        background:#9e4b3f;
    }

    .branch-admin-view .admin-kpi-card:nth-child(4):hover .admin-kpi-card__arrow,
    .branch-admin-view .admin-kpi-card:nth-child(4):focus-visible .admin-kpi-card__arrow {
        background:#686849;
    }

    .branch-admin-view .admin-kpi-card__icon {
        width: 48px;
        height: 48px;
        border: 0;
        border-radius: 12px;
        background: rgba(255, 255, 255, .46);
        box-shadow: inset 0 0 0 1px rgba(62, 74, 61, .08);
        font-size: 1.25rem;
    }

    .branch-admin-view .admin-kpi-card__label {
        color: var(--color-text-primary);
        font-size: .78rem;
        font-weight: 800;
    }

    .branch-admin-view .admin-kpi-card__value {
        font-size: clamp(1.25rem, 1.7vw, 1.65rem);
        font-variant-numeric: tabular-nums;
    }

    .branch-admin-view .admin-kpi-card__scope {
        margin-top: .15rem;
        font-size: .6rem;
    }

    .branch-admin-view .admin-kpi-card__arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .4);
    }

    .branch-admin-view .branch-operations-card,
    .branch-admin-view .branch-balance-card {
        border-color: rgba(87, 105, 81, .2) !important;
        box-shadow: 0 10px 28px rgba(41, 58, 42, .06) !important;
    }

    .branch-admin-view .branch-operations-card {
        background: linear-gradient(145deg, #eef3e9 0%, #e7eee3 100%) !important;
    }

    .branch-admin-view .branch-balance-card {
        background: linear-gradient(145deg, #f4ecdd 0%, #eee5d4 100%) !important;
    }

    .branch-admin-view .branch-operations-card .dashboard-click-card,
    .branch-admin-view .branch-balance-card .dashboard-click-card {
        background: rgba(255, 253, 247, .55);
        border-color: rgba(87, 105, 81, .16) !important;
        box-shadow: none;
    }

    .branch-admin-view .branch-operations-card .dashboard-click-card:nth-of-type(even),
    .branch-admin-view .branch-balance-card .dashboard-click-card:nth-of-type(even) {
        background: rgba(235, 228, 211, .54);
    }

    .branch-admin-view .branch-operations-card .dashboard-click-card:hover,
    .branch-admin-view .branch-operations-card .dashboard-click-card:focus-visible,
    .branch-admin-view .branch-balance-card .dashboard-click-card:hover,
    .branch-admin-view .branch-balance-card .dashboard-click-card:focus-visible {
        background: rgba(221, 231, 216, .92) !important;
        border-color: #71816b !important;
    }

    html[data-theme='dark'] .branch-admin-view .admin-kpi-card,
    html[data-theme='dark'] .branch-admin-view .branch-operations-card,
    html[data-theme='dark'] .branch-admin-view .branch-balance-card {
        background: var(--color-bg-surface) !important;
        border-color: var(--color-border) !important;
    }

    html[data-theme='dark'] .branch-admin-view .branch-operations-card .dashboard-click-card,
    html[data-theme='dark'] .branch-admin-view .branch-balance-card .dashboard-click-card {
        background: var(--color-bg-muted);
        border-color: var(--color-border) !important;
    }

    .branch-board {
        display: grid;
        grid-template-columns: minmax(0, 1.48fr) minmax(330px, 1fr);
        gap: 1rem;
        align-items: start;
    }

    .branch-board__column {
        display: grid;
        gap: 1rem;
    }

    .branch-panel {
        overflow: hidden;
        border: 1px solid rgba(87, 105, 81, .2);
        border-radius: 18px;
        background: linear-gradient(145deg, #edf3e9, #e5ede1);
        box-shadow: 0 10px 28px rgba(41, 58, 42, .055);
    }

    .branch-panel.is-warm {
        background: linear-gradient(145deg, #f3ecdd, #ebe2cf);
    }

    .branch-panel.is-neutral {
        background: linear-gradient(145deg, #eff1e8, #e8ebe0);
    }

    .branch-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.05rem;
    }

    .branch-panel__title {
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .branch-panel__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .46);
        color: #315f43;
        font-size: 1.1rem;
    }

    .branch-panel__head h3 {
        margin: 0;
        color: var(--color-text-primary);
        font-family: var(--font-heading);
        font-size: 1rem;
    }

    .branch-panel__head p {
        margin: .18rem 0 0;
        color: var(--color-text-secondary);
        font-size: .72rem;
    }

    .branch-panel__tools {
        display: flex;
        align-items: center;
        gap: .65rem;
        white-space: nowrap;
    }

    .branch-panel__count {
        color: var(--color-text-primary);
        font-size: .78rem;
        font-weight: 900;
    }

    .branch-panel__link {
        position:relative;
        z-index:4;
        display: inline-flex;
        align-items: center;
        min-height: 38px;
        padding: .5rem .72rem;
        border: 1px solid rgba(87, 105, 81, .25);
        border-radius: 10px;
        background: rgba(255, 255, 255, .34);
        color: var(--color-text-primary);
        font-size: .72rem;
        font-weight: 800;
        text-decoration: none;
        transition:background-color .16s ease, border-color .16s ease, color .16s ease, box-shadow .16s ease;
    }

    .branch-panel__link::after {
        content:attr(data-tooltip);
        position:absolute;
        z-index:8;
        top:calc(100% + .42rem);
        right:0;
        width:max-content;
        max-width:13rem;
        padding:.42rem .58rem;
        border:1px solid #b9c8b8;
        border-radius:.5rem;
        background:#fffdf7;
        color:#1f3528;
        -webkit-text-fill-color:#1f3528;
        box-shadow:0 7px 18px rgba(31, 53, 40, .16);
        font-size:.64rem;
        font-weight:800;
        line-height:1.2;
        opacity:0;
        visibility:hidden;
        pointer-events:none;
        transform:translateY(-.25rem);
        transition:opacity .16s ease, transform .16s ease, visibility 0s linear .16s;
        white-space:nowrap;
    }

    .branch-panel__link:hover,
    .branch-panel__link:focus-visible {
        border-color:#315f43;
        background:#315f43;
        color:#fff;
        box-shadow:0 5px 14px rgba(49, 95, 67, .16);
        outline:2px solid rgba(49, 95, 67, .18);
        outline-offset:2px;
    }

    .branch-panel__link:hover::after,
    .branch-panel__link:focus-visible::after {
        opacity:1;
        visibility:visible;
        transform:translateY(0);
        transition-delay:0s;
    }

    @media (prefers-reduced-motion: reduce) {
        .branch-panel__link::after { transition:none; transform:none; }
    }

    .branch-panel__overflow {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin: -.1rem .75rem .75rem;
        padding: .55rem .7rem;
        border: 1px dashed rgba(87, 105, 81, .24);
        border-radius: 10px;
        background: rgba(255, 253, 247, .28);
        color: var(--color-text-secondary);
        font-size: .68rem;
        font-weight: 800;
        text-decoration: none;
        transition:background-color .16s ease, border-color .16s ease, color .16s ease;
    }

    .branch-panel__overflow:hover,
    .branch-panel__overflow:focus-visible {
        border-color:#55745e;
        background:rgba(215, 228, 209, .76);
        color:var(--color-text-primary);
        outline:2px solid rgba(85, 116, 94, .16);
        outline-offset:2px;
    }

    .branch-panel__overflow strong {
        color: var(--color-primary);
        white-space: nowrap;
    }

    .branch-table-wrap {
        margin: 0 .75rem .75rem;
        overflow: visible;
        border: 1px solid rgba(87, 105, 81, .16);
        border-radius: 12px;
        background: rgba(255, 253, 247, .42);
    }

    .branch-record-list {
        display:grid;
        gap:.6rem;
        padding:0 .75rem .75rem;
    }

    .branch-record-card {
        display:grid;
        grid-template-columns:minmax(0, 1fr) auto;
        align-items:center;
        gap:.75rem;
        min-height:68px;
        padding:.65rem .72rem;
        border:1px solid rgba(87, 105, 81, .16);
        border-radius:12px;
        background:rgba(255, 253, 247, .42);
        transition:background-color .16s ease, border-color .16s ease, box-shadow .16s ease;
    }

    .branch-record-card:hover,
    .branch-record-card:focus-within {
        border-color:#789079;
        background:rgba(225, 235, 220, .88);
        box-shadow:0 7px 18px rgba(41, 58, 42, .09);
    }

    .branch-record-card__head {
        display:contents;
    }

    .branch-record-card__summary {
        display:grid;
        grid-template-columns:minmax(130px, .9fr) minmax(0, 1.6fr);
        align-items:center;
        gap:.75rem;
        min-width:0;
    }

    .branch-record-card__identity { min-width:0; }
    .branch-record-card__identity strong { display:block; color:var(--color-text-primary); font-size:.79rem; }
    .branch-record-card__identity span { display:block; margin-top:.15rem; color:var(--color-text-secondary); font-size:.68rem; }
    .branch-record-card__meta { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); align-items:center; gap:.45rem; min-width:0; }
    .branch-record-card__field { min-width:0; padding:.42rem .52rem; border-radius:9px; background:rgba(255,255,255,.34); }
    .branch-record-card__field { display:flex; flex-direction:column; }
    .branch-record-card__field small { order:2; display:block; margin-top:.22rem; color:var(--color-text-secondary); font-size:.58rem; font-weight:800; letter-spacing:.045em; text-transform:uppercase; }
    .branch-record-card__field > strong,
    .branch-record-card__field > .branch-day-badge { order:1; align-self:flex-start; }
    .branch-record-card__field strong { color:var(--color-text-primary); font-size:.7rem; }
    .branch-record-card__event { display:grid; grid-template-columns:minmax(130px, .8fr) minmax(130px, 1fr); align-items:center; gap:.55rem; min-width:0; }
    .branch-record-card__event-copy { display:flex; align-items:center; gap:.55rem; min-width:0; }
    .branch-record-card__event-support { color:var(--color-text-secondary); font-size:.64rem; font-weight:700; white-space:nowrap; }
    .branch-record-card__event-meta { color:var(--color-text-secondary); font-size:.67rem; }
    .branch-record-empty { margin:0 .75rem .75rem; padding:.9rem; border:1px dashed rgba(87,105,81,.22); border-radius:12px; color:var(--color-text-secondary); font-size:.72rem; text-align:center; }

    .branch-record-card--upcoming {
        grid-template-columns:minmax(125px, .42fr) minmax(0, 1.58fr) auto;
    }

    @media(max-width:760px) {
        .branch-record-card,
        .branch-record-card--upcoming { grid-template-columns:minmax(0, 1fr) auto; align-items:start; }
        .branch-record-card__summary { grid-template-columns:1fr; gap:.5rem; }
        .branch-record-card--upcoming .branch-record-card__identity { grid-column:1; }
        .branch-record-card--upcoming .branch-schedule-stack { grid-column:1 / -1; grid-row:2; }
        .branch-record-card__event { grid-template-columns:1fr; align-items:start; }
        .branch-record-card__event-copy { align-items:flex-start; flex-direction:column; }
    }

    .branch-table {
        width: 100%;
        min-width: 0;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .branch-table th,
    .branch-table td {
        padding: .7rem .75rem;
        border-bottom: 1px solid rgba(87, 105, 81, .14);
        color: var(--color-text-primary);
        font-size: .73rem;
        text-align: left;
        vertical-align: middle;
        overflow-wrap: anywhere;
    }

    .branch-table th {
        background: rgba(87, 105, 81, .07);
        font-size: .65rem;
        font-weight: 900;
        letter-spacing: .02em;
    }

    .branch-table tr:last-child td {
        border-bottom: 0;
    }

    .branch-table tbody tr[data-case-entry] {
        transition: background-color .16s ease;
    }

    .branch-table tbody tr[data-case-entry]:hover,
    .branch-table tbody tr[data-case-entry]:focus-within {
        background: rgba(215, 228, 209, .7);
        outline: none;
    }

    .branch-table__link {
        color: inherit;
        font-weight: 800;
        text-decoration: none;
    }

    .branch-table th:last-child,
    .branch-table td:last-child {
        width: 3rem;
        min-width: 3rem;
        padding-left: .25rem;
        padding-right: .45rem;
        text-align: right;
    }

    .branch-row-destination {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        border: 1px solid rgba(38, 61, 49, .2);
        background: rgba(255, 255, 255, .52);
        color: #263d31;
        text-decoration: none;
        transition: background-color .16s ease, color .16s ease, border-color .16s ease;
    }

    .branch-row-destination__label {
        position: absolute;
        z-index: 6;
        right: calc(100% + .45rem);
        top: 50%;
        width: max-content;
        padding: .4rem .55rem;
        border-radius: .45rem;
        border:1px solid #b9c8b8;
        background:#fffdf7;
        color:#1f3528 !important;
        -webkit-text-fill-color:#1f3528;
        box-shadow:0 6px 16px rgba(31, 53, 40, .18);
        font-size: .63rem;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translate(.3rem, -50%);
        transition: opacity .16s ease, transform .16s ease, visibility 0s linear .16s;
    }

    .branch-row-destination:hover,
    .branch-row-destination:focus-visible {
        border-color: #263d31;
        background: #263d31;
        color: #fff;
        outline: 2px solid rgba(38, 61, 49, .24);
        outline-offset: 2px;
    }

    .branch-row-destination:hover .branch-row-destination__label,
    .branch-row-destination:focus-visible .branch-row-destination__label {
        opacity: 1;
        visibility: visible;
        transform: translate(0, -50%);
        transition-delay: 0s;
    }

    @media (prefers-reduced-motion: reduce) {
        .branch-row-destination,
        .branch-row-destination__label { transition: none; }
    }

    .branch-event-badge,
    .branch-day-badge {
        display: inline-flex;
        align-items: center;
        padding: .3rem .55rem;
        border-radius: 999px;
        background: #d7e6d7;
        color: #244a33;
        font-size: .66rem;
        font-weight: 900;
        max-width: 100%;
        white-space: normal;
        text-align: center;
    }

    .branch-event-badge.is-wake-start { background: #eadfc7; color: #6c5123; }
    .branch-event-badge.is-wake-end { background: #d8e4ef; color: #294c69; }
    .branch-event-badge.is-interment { background: #efd8d4; color: #78382f; }

    .branch-schedule-stack { display:grid; grid-template-columns:repeat(auto-fit, minmax(145px, 1fr)); gap:.4rem; min-width:0; }
    .branch-schedule-item { display:grid; align-content:center; gap:.3rem; min-width:0; min-height:48px; padding:.38rem .45rem; border-radius:9px; background:rgba(255,255,255,.3); }
    .branch-schedule-item__when { order:2; overflow:hidden; color:var(--color-text-secondary); font-size:.63rem; font-variant-numeric:tabular-nums; text-overflow:ellipsis; white-space:nowrap; }
    .branch-schedule-item__detail { display:flex; align-items:center; gap:.45rem; min-width:0; }
    .branch-schedule-item__location { overflow:hidden; color:var(--color-text-secondary); font-size:.64rem; text-overflow:ellipsis; white-space:nowrap; }
    .branch-case-schedule-count { display:block; margin-top:.28rem; color:var(--color-text-secondary); font-size:.62rem; font-weight:700; }
    .branch-table--grouped-schedules th:nth-child(1) { width:16%; }
    .branch-table--grouped-schedules th:nth-child(2) { width:24%; }
    .branch-table--grouped-schedules th:nth-child(3) { width:auto; }

    @media (max-width: 720px) {
        .branch-schedule-stack { grid-template-columns:1fr; }
        .branch-schedule-item { gap:.2rem; }
        .branch-schedule-item__detail { align-items:center; flex-direction:row; }
        .branch-schedule-item__location { max-width:100%; white-space:normal; }
    }

    .branch-balance-list {
        display: grid;
        gap: .55rem;
        padding: 0 .75rem .75rem;
    }

    .branch-balance-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
        gap: .75rem;
        padding: .85rem .9rem;
        border: 1px solid rgba(123, 92, 48, .15);
        border-radius: 12px;
        background: rgba(255, 253, 247, .46);
        color: inherit;
        text-decoration: none;
        transition:background-color .16s ease, border-color .16s ease, box-shadow .16s ease;
    }

    .branch-balance-item:hover,
    .branch-balance-item:focus-visible {
        border-color:#8b6a37;
        background:#f3e6cf;
        box-shadow:0 7px 18px rgba(92, 67, 31, .12);
        outline:2px solid rgba(139, 106, 55, .16);
        outline-offset:2px;
    }

    .branch-balance-item:hover > i,
    .branch-balance-item:focus-visible > i {
        border-color:#8b6a37;
        background:#8b6a37;
        color:#fff;
    }

    .branch-balance-item > i {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:28px;
        height:28px;
        border:1px solid rgba(139, 106, 55, .2);
        border-radius:999px;
        background:rgba(255,255,255,.4);
        color:#8b6a37;
        transition:background-color .16s ease, border-color .16s ease, color .16s ease;
    }

    .branch-balance-item strong,
    .branch-balance-item small { display: block; }
    .branch-balance-item strong { color: var(--color-text-primary); font-size: .82rem; }
    .branch-balance-item small { margin-top: .2rem; color: var(--color-text-secondary); font-size: .68rem; }
    .branch-balance-item__amount { color: #8b332b; font-size: .78rem; font-weight: 900; white-space: nowrap; }

    .branch-payment-summary {
        display: grid;
        grid-template-columns: 130px minmax(0, 1fr);
        align-items: center;
        gap: 1rem;
        padding: .3rem 1rem 1rem;
    }

    .branch-payment-ring {
        display: grid;
        place-items: center;
        width: 116px;
        height: 116px;
        border-radius: 999px;
        background: conic-gradient(#356849 var(--collection-rate), #d8c6a1 0);
    }

    .branch-payment-ring__inside {
        display: grid;
        place-items: center;
        width: 76px;
        height: 76px;
        border-radius: 999px;
        background: #f3f0e6;
        color: var(--color-text-primary);
        text-align: center;
    }

    .branch-payment-ring__inside strong { display: block; font-size: 1.25rem; line-height: 1; }
    .branch-payment-ring__inside span { display: block; margin-top: .25rem; font-size: .62rem; }
    .branch-payment-lines { display: grid; gap: .6rem; }
    .branch-payment-line { display: grid; grid-template-columns: 1fr auto; gap: .75rem; font-size: .72rem; }
    .branch-payment-line strong { font-variant-numeric: tabular-nums; }
    .branch-payment-line.is-total { padding-top: .6rem; border-top: 1px solid rgba(87, 105, 81, .18); }

    .branch-quick-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .6rem;
        padding: 0 .75rem .75rem;
    }

    .branch-quick-action {
        display: flex;
        align-items: center;
        gap: .6rem;
        min-height: 64px;
        padding: .75rem;
        border: 1px solid rgba(87, 105, 81, .16);
        border-radius: 12px;
        background: rgba(255, 253, 247, .42);
        color: var(--color-text-primary);
        font-size: .72rem;
        font-weight: 800;
        text-decoration: none;
    }

    .branch-quick-action i { color: #315f43; font-size: 1.05rem; }

    html[data-theme='dark'] .branch-admin-view .branch-panel,
    html[data-theme='dark'] .branch-admin-view .branch-table-wrap,
    html[data-theme='dark'] .branch-admin-view .branch-balance-item,
    html[data-theme='dark'] .branch-admin-view .branch-quick-action,
    html[data-theme='dark'] .branch-admin-view .branch-payment-ring__inside {
        background: var(--color-bg-surface);
        border-color: var(--color-border);
    }

    @media (max-width: 1023px) {
        .branch-board { grid-template-columns: 1fr; }
    }

    @media (max-width: 639px) {
        .branch-panel__head { align-items: flex-start; flex-direction: column; }
        .branch-panel__tools { width: 100%; justify-content: space-between; }
        .branch-payment-summary { grid-template-columns: 1fr; justify-items: center; }
        .branch-payment-lines { width: 100%; }
        .branch-quick-actions { grid-template-columns: 1fr; }
    }

    .admin-branch-drilldown,
    .admin-service-drilldown {
        color: inherit;
        text-decoration: none;
    }

    .admin-branch-drilldown {
        border-radius: 8px !important;
    }

    .admin-branch-drilldown:hover,
    .admin-branch-drilldown:focus-visible,
    .admin-service-drilldown:hover,
    .admin-service-drilldown:focus-visible {
        border-color: var(--color-primary) !important;
        background: var(--color-bg-muted) !important;
        outline: none;
    }

    .admin-branch-drilldown__arrow {
        flex: 0 0 auto;
        color: var(--color-text-secondary);
        font-size: .72rem;
        transition: transform .18s ease, color .18s ease;
    }

    .admin-branch-drilldown:hover .admin-branch-drilldown__arrow,
    .admin-branch-drilldown:focus-visible .admin-branch-drilldown__arrow {
        color: var(--color-primary);
        transform: translateX(2px);
    }

    .admin-service-drilldown {
        border: 1px solid transparent;
        border-radius: 8px;
        padding: .25rem;
        transition: background-color .18s ease, border-color .18s ease;
    }

    .admin-service-drilldown__bar {
        transition: background-color .18s ease, transform .18s ease;
    }

    .admin-service-drilldown:hover .admin-service-drilldown__bar,
    .admin-service-drilldown:focus-visible .admin-service-drilldown__bar {
        background: var(--color-primary) !important;
        transform: translateY(-2px);
    }

    .admin-case-volume-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(150px, .42fr);
        gap: 1rem;
        min-height: 250px;
    }

    .admin-case-volume-chart {
        min-width: 0;
    }

    .admin-case-volume-plot {
        position: relative;
        min-height: 222px;
    }

    .admin-case-volume-axis-title,
    .admin-case-volume-x-title,
    .admin-case-volume-tick {
        color: var(--color-text-secondary);
        font-size: .6rem;
        font-weight: 700;
    }

    .admin-case-volume-axis-title {
        position: absolute;
        top: 0;
        left: 0;
    }

    .admin-case-volume-scale,
    .admin-case-volume-grid {
        position: absolute;
        top: 30px;
        height: 150px;
        pointer-events: none;
    }

    .admin-case-volume-scale {
        left: 0;
        width: 26px;
    }

    .admin-case-volume-grid {
        right: 0;
        left: 32px;
    }

    .admin-case-volume-tick,
    .admin-case-volume-gridline {
        position: absolute;
        right: 0;
        left: 0;
        bottom: var(--axis-position);
        transform: translateY(50%);
    }

    .admin-case-volume-tick {
        text-align: right;
    }

    .admin-case-volume-gridline {
        border-top: 1px solid color-mix(in srgb, var(--color-border) 78%, transparent);
    }

    .admin-case-volume-bars {
        padding-left: 32px;
    }

    .admin-case-volume-x-title {
        margin-top: .15rem;
        padding-left: 32px;
        text-align: center;
    }

    .admin-case-volume-breakdown {
        display: flex;
        min-width: 0;
        flex-direction: column;
        border-left: 1px solid var(--color-border);
        padding-left: 1rem;
    }

    .admin-case-volume-breakdown__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .6rem;
        padding: .2rem 0 .65rem;
        border-bottom: 1px solid var(--color-border);
    }

    .admin-case-volume-breakdown__header span,
    .admin-case-volume-breakdown__share {
        color: var(--color-text-secondary);
        font-size: .65rem;
        font-weight: 700;
    }

    .admin-case-volume-breakdown__header strong {
        color: var(--color-text-primary);
        font-family: var(--font-heading);
        font-size: 1.15rem;
        line-height: 1;
    }

    .admin-case-volume-breakdown__list {
        display: flex;
        flex: 1;
        flex-direction: column;
        justify-content: center;
    }

    .admin-case-volume-breakdown__row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: .55rem;
        padding: .62rem .25rem;
        border-bottom: 1px solid var(--color-border);
        border-radius: 0;
        color: inherit;
        text-decoration: none;
    }

    .admin-case-volume-breakdown__row:last-child {
        border-bottom: 0;
    }

    .admin-case-volume-breakdown__row:hover,
    .admin-case-volume-breakdown__row:focus-visible {
        background: var(--color-bg-muted);
        outline: none;
    }

    .admin-case-volume-breakdown__branch,
    .admin-case-volume-breakdown__value {
        min-width: 0;
    }

    .admin-case-volume-breakdown__branch strong,
    .admin-case-volume-breakdown__branch span,
    .admin-case-volume-breakdown__value strong,
    .admin-case-volume-breakdown__value span {
        display: block;
    }

    .admin-case-volume-breakdown__branch strong {
        overflow: hidden;
        color: var(--color-text-primary);
        font-size: .72rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .admin-case-volume-breakdown__branch span {
        overflow: hidden;
        color: var(--color-text-secondary);
        font-size: .62rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .admin-case-volume-breakdown__value {
        text-align: right;
    }

    .admin-case-volume-breakdown__value strong {
        color: var(--color-text-primary);
        font-family: var(--font-heading);
        font-size: .9rem;
    }

    @media (max-width: 767px) {
        .admin-case-volume-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .admin-case-volume-breakdown {
            border-top: 1px solid var(--color-border);
            border-left: 0;
            padding-top: .8rem;
            padding-left: 0;
        }
    }

    .admin-action-center {
        min-height: 0 !important;
        overflow: hidden;
    }

    .admin-action-center__header,
    .admin-action-group__title,
    .admin-action-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .admin-action-center__header {
        margin-bottom: .7rem;
    }

    .admin-action-center__header h3,
    .admin-action-group__title h4 {
        margin: 0;
        color: var(--color-text-primary);
        font-family: var(--font-heading);
        font-weight: 800;
        letter-spacing: 0;
    }

    .admin-action-center__header h3 {
        font-size: 1rem;
    }

    .admin-action-center__header p {
        margin: .15rem 0 0;
        color: var(--color-text-secondary);
        font-size: .72rem;
        font-weight: 600;
    }

    .admin-action-group {
        padding: .65rem 0;
        border-top: 1px solid var(--color-border);
    }

    .admin-action-group__title > div {
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .admin-action-group__title h4 {
        font-size: .82rem !important;
    }

    .admin-action-group__scope {
        color: var(--color-text-secondary);
        font-size: .58rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .admin-action-group__title > a {
        color: var(--color-primary);
        font-size: .68rem;
        font-weight: 800;
        text-decoration: none;
    }

    .admin-action-group__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 7px;
        color: var(--color-primary);
        background: var(--color-bg-muted);
    }

    .admin-action-group.is-alert .admin-action-group__icon {
        color: var(--color-danger);
        background: rgba(158, 75, 63, .1);
    }

    .admin-action-list {
        display: grid;
        gap: .35rem;
        margin-top: .45rem;
    }

    .admin-action-item {
        min-height: 42px;
        padding: .4rem .5rem;
        border-radius: 6px;
        color: inherit;
        text-decoration: none;
    }

    .admin-action-item:hover,
    .admin-action-item:focus-visible {
        background: var(--color-bg-muted);
    }

    .admin-action-item > span {
        display: flex;
        min-width: 0;
        flex-direction: column;
        gap: .08rem;
    }

    .admin-action-item strong,
    .admin-action-item small {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .admin-action-item strong {
        color: var(--color-text-primary);
        font-size: .75rem;
    }

    .admin-action-item small,
    .admin-action-item time {
        color: var(--color-text-secondary);
        font-size: .64rem;
        font-style: normal;
        font-weight: 600;
    }

    .admin-action-item em {
        color: var(--color-danger);
        font-size: .62rem;
        font-style: normal;
        font-weight: 800;
    }

    .admin-action-empty {
        display: flex;
        align-items: center;
        gap: .45rem;
        min-height: 42px;
        padding: .4rem .5rem;
        color: var(--color-text-secondary);
        font-size: .7rem;
        font-weight: 700;
    }

    .admin-action-empty i {
        color: var(--color-success);
    }

    .admin-action-more {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        min-height: 34px;
        padding: .42rem .55rem;
        border: 1px dashed var(--color-border);
        border-radius: 7px;
        background: var(--color-bg-muted);
        color: var(--color-text-secondary);
        font-size: .66rem;
        font-weight: 700;
        line-height: 1.25;
        text-decoration: none;
    }

    .admin-action-more strong {
        flex: 0 0 auto;
        padding: .18rem .42rem;
        border-radius: 999px;
        background: rgba(95, 125, 95, .14);
        color: var(--color-primary);
        font-size: .62rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .admin-action-group.is-alert .admin-action-more strong {
        background: rgba(158, 75, 63, .12);
        color: var(--color-danger);
    }

    .admin-action-more:hover,
    .admin-action-more:focus-visible {
        border-color: var(--color-primary);
        color: var(--color-text-primary);
    }

    @media (max-width: 1023px) {
        .admin-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 639px) {
        .admin-kpi-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (min-width: 1024px) {
        body.panel-shell-body:has(.admin-dashboard-shell) .page-content {
            overflow-y: hidden;
        }

        .dashboard-fit-page {
            height: 100vh;
            min-height: 0;
            overflow: hidden;
        }

        .admin-dashboard-shell {
            height: 100vh;
            min-height: 0;
            overflow: hidden;
            display: grid;
            grid-template-rows: auto auto auto minmax(0, 1fr);
            gap: .55rem;
            padding: .55rem clamp(.65rem, 1vw, 1rem);
        }

        .admin-dashboard-shell > :not([hidden]) ~ :not([hidden]) {
            margin-top: 0 !important;
        }

        .admin-dashboard-greeting {
            padding: .62rem .8rem;
        }

        .admin-dashboard-greeting h1 {
            font-size: clamp(1.15rem, 1.35vw, 1.45rem);
        }

        .admin-dashboard-greeting p {
            margin-top: .12rem;
            font-size: .78rem;
        }

        .admin-dashboard-date-pill {
            min-height: 32px;
            padding: .38rem .58rem;
            font-size: .78rem;
        }

        .admin-dashboard-shell .admin-top-controls {
            padding: .45rem .62rem;
        }

        html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .input-custom,
        html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .btn-secondary-custom,
        html:not([data-theme='dark']) .admin-dashboard-shell .admin-date-filter-link,
        html:not([data-theme='dark']) .admin-dashboard-shell .admin-top-controls .btn-primary-custom {
            min-height: 34px;
        }

        .admin-dashboard-shell .admin-filter-control .input-custom {
            padding-top: .4rem !important;
            padding-bottom: .4rem !important;
        }

        .admin-dashboard-shell .admin-financial-card {
            min-height: 88px !important;
            padding: .72rem .8rem .72rem 4.9rem !important;
        }

        .admin-dashboard-shell .admin-financial-card::before {
            left: -2.25rem;
            width: 5.7rem;
            height: 5.7rem;
        }

        .admin-dashboard-shell .admin-financial-card__head {
            margin-bottom: .35rem;
        }

        .admin-dashboard-shell .admin-financial-card__head > .w-10 {
            left: .72rem;
            width: 2.9rem !important;
            height: 2.9rem !important;
            font-size: 1.35rem !important;
        }

        .admin-dashboard-shell .admin-financial-card p {
            margin-bottom: .25rem !important;
            font-size: .68rem !important;
        }

        .admin-dashboard-shell .admin-financial-card h4 {
            font-size: clamp(1.15rem, 1.55vw, 1.55rem) !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
        }

        .admin-dashboard-shell .admin-summary-section {
            gap: .45rem;
            margin-top: 0 !important;
        }

        html:not([data-theme='dark']) .admin-dashboard-shell .admin-summary-section {
            margin-top: 0 !important;
        }

        .admin-dashboard-shell .admin-summary-section__title {
            font-size: .86rem !important;
        }

        .admin-dashboard-shell .admin-summary-section .grid {
            gap: .55rem;
        }

        .admin-dashboard-shell .stat-card {
            min-height: 76px;
            padding: .55rem .62rem;
            gap: .32rem !important;
        }

        .admin-dashboard-shell .admin-summary-section .stat-card > .w-9 {
            width: 1.85rem !important;
            height: 1.85rem !important;
        }

        .admin-dashboard-shell .stat-label {
            font-size: .62rem;
            line-height: 1.1;
        }

        .admin-dashboard-shell .stat-value {
            font-size: clamp(1rem, 1.35vw, 1.3rem);
        }

        .admin-dashboard-shell .admin-summary-section .stat-value.is-money {
            font-size: clamp(.82rem, 1vw, 1.05rem) !important;
        }

        .admin-dashboard-main-board {
            min-height: 0;
            gap: .65rem;
            overflow: hidden;
        }

        .admin-dashboard-main-board > .card-custom {
            min-height: 0 !important;
            height: 100%;
            overflow: hidden;
        }

        .admin-kpi-card {
            min-height: 76px;
            padding: .62rem .7rem;
        }

        .admin-kpi-card__value {
            font-size: 1rem;
        }

        .admin-dashboard-main-board .mb-8,
        .admin-dashboard-main-board .mb-6 {
            margin-bottom: .65rem !important;
        }

        .admin-dashboard-main-board .space-y-4,
        .admin-dashboard-main-board .space-y-3 {
            display: grid;
            gap: .48rem;
        }

        .admin-dashboard-main-board .p-5,
        .admin-dashboard-main-board .p-4,
        .admin-dashboard-main-board .p-3 {
            padding: .62rem !important;
        }

        .admin-dashboard-main-board h4 {
            font-size: clamp(.95rem, 1.15vw, 1.2rem) !important;
        }
    }

    @media (min-width: 1024px) and (max-width: 1279px) {
        .admin-dashboard-main-board {
            grid-template-columns: minmax(0, 7fr) minmax(0, 5fr) !important;
        }
    }

    @media (min-width: 1024px) {
        body.panel-shell-body:has(.branch-admin-view) .page-content {
            overflow-y: auto;
        }

        .dashboard-fit-page:has(.branch-admin-view) {
            height: auto;
            min-height: 100vh;
            overflow: visible;
        }

        .admin-dashboard-shell.branch-admin-view {
            display: flex;
            height: auto;
            min-height: 100vh;
            flex-direction: column;
            gap: .7rem;
            overflow: visible;
        }

        .branch-admin-view .admin-kpi-card {
            min-height: 92px;
            padding: .72rem .82rem;
        }

        .branch-admin-view .admin-kpi-card__icon {
            width: 42px;
            height: 42px;
        }

        .branch-admin-view .branch-board,
        .branch-admin-view .branch-board__column {
            gap: .72rem;
        }

        .branch-admin-view .branch-panel__head {
            padding: .75rem .85rem;
        }

        .branch-admin-view .branch-panel__icon {
            width: 36px;
            height: 36px;
        }

        .branch-admin-view .branch-table-wrap {
            margin: 0 .6rem .6rem;
        }

        .branch-admin-view .branch-table th,
        .branch-admin-view .branch-table td {
            padding: .52rem .62rem;
        }

        .branch-admin-view .branch-balance-list,
        .branch-admin-view .branch-quick-actions {
            padding: 0 .6rem .6rem;
        }

        .branch-admin-view .branch-balance-item {
            padding: .65rem .72rem;
        }

        .branch-admin-view .branch-payment-summary {
            padding: 0 .8rem .75rem;
        }
    }

    @media (max-width: 1023px) {
        body.panel-shell-body:has(.admin-dashboard-shell) .page-content {
            overflow-y: auto;
        }
    }
    /* Flat page heading shared with the rest of the application dashboards. */
    .admin-dashboard-greeting.admin-dashboard-greeting--flat {
        padding: .25rem 0 .75rem;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        overflow: visible;
    }

    html:not([data-theme='dark']) .admin-dashboard-shell .admin-dashboard-greeting.admin-dashboard-greeting--flat {
        border:0 !important;
        border-color:transparent !important;
        background:transparent !important;
        background-color:transparent !important;
        box-shadow:none !important;
        overflow:visible;
    }

    html[data-theme='dark'] .admin-dashboard-shell .admin-dashboard-greeting.admin-dashboard-greeting--flat {
        border: 0 !important;
        background: transparent !important;
        background-color:transparent !important;
    }

    @media (max-width: 767px) {
        .admin-dashboard-greeting.admin-dashboard-greeting--flat {
            padding: .2rem 0 .65rem;
        }
    }
</style>

<div class="dashboard-fit-page">
<div class="admin-dashboard-shell {{ $isBranchAdmin ? 'branch-admin-view' : '' }} w-full space-y-6 antialiased text-slate-900 animate-float-up">
    <section class="admin-dashboard-greeting admin-dashboard-greeting--flat">
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
                <h1>Good day, {{ $adminFirstName }}</h1>
                <p>{{ $adminSubtitle }}</p>
            </div>
        </div>
        <div class="admin-dashboard-greeting__tools">
            <div class="admin-dashboard-date-pill"><i class="bi bi-calendar3"></i> {{ $adminTodayLabel }}</div>
            @include('partials.topbar-notifications', ['notificationBranchId' => (int) ($selectedBranchId ?? $adminBranch?->id ?? 0)])
        </div>
    </section>

    @if($errors->any())
        <div class="bg-red-50 border border-red-100 p-4 text-red-700 rounded-2xl text-[11px] font-black uppercase tracking-widest flex items-center gap-3 shadow-sm">
            <i class="bi bi-exclamation-octagon-fill text-lg"></i>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Main administrators may drill down to one branch; each metric keeps its own fixed time scope. --}}
    @if($isMainAdmin)
    <div class="card-custom admin-top-controls">
        <form method="GET" action="{{ url('/admin') }}" class="admin-top-controls-form">
            <label class="admin-filter-control">
                <i class="bi bi-building admin-filter-icon"></i>
                <select name="branch_id" onchange="this.form.submit()" class="input-custom w-48" aria-label="Dashboard branch scope">
                    <option value="" @selected(!($selectedBranchId ?? null))>All Branches</option>
                    @foreach($branches ?? [] as $branch)
                        <option value="{{ $branch->id }}" {{ (string) ($selectedBranchId ?? '') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->branch_code }} - {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down admin-filter-chevron"></i>
            </label>
        </form>
    </div>
    @endif

    {{-- 2. ROLE-FOCUSED KPIS --}}
    <section class="admin-kpi-grid {{ $isBranchAdmin ? 'branch-kpi-grid' : '' }}" aria-label="Main dashboard indicators">
        @php
            $primaryKpis = [
                ['label' => 'Payments Received', 'scope' => 'This Month', 'value' => '₱' . number_format((float) ($totalCollected ?? 0), 2), 'icon' => 'bi-wallet2', 'url' => $collectedMonitoringUrl, 'tone' => 'is-positive'],
                ['label' => 'Remaining Balance', 'scope' => 'Active & Completed Cases', 'value' => '₱' . number_format((float) ($totalOutstanding ?? 0), 2), 'icon' => 'bi-exclamation-circle', 'url' => $outstandingMonitoringUrl, 'tone' => 'is-alert'],
                ['label' => 'Payment Progress', 'scope' => 'Active & Completed Cases', 'value' => $collectionRate . '%', 'icon' => 'bi-graph-up-arrow', 'url' => $paymentProgressUrl, 'tone' => 'is-neutral'],
                ['label' => 'Active Cases', 'scope' => 'Ongoing Operations', 'value' => (int) ($ongoingCases ?? 0), 'icon' => 'bi-folder2-open', 'url' => $activeCasesUrl, 'tone' => 'is-neutral'],
            ];
        @endphp

        @foreach($primaryKpis as $kpi)
            <a href="{{ $kpi['url'] }}" class="admin-kpi-card {{ $kpi['tone'] }}" aria-label="View {{ $kpi['label'] }} details">
                <span class="admin-kpi-card__icon" aria-hidden="true"><i class="bi {{ $kpi['icon'] }}"></i></span>
                <span class="admin-kpi-card__copy">
                    <span class="admin-kpi-card__label">{{ $kpi['label'] }}</span>
                    <strong class="admin-kpi-card__value">{{ $kpi['value'] }}</strong>
                    <span class="admin-kpi-card__scope">{{ $kpi['scope'] }}</span>
                </span>
                <i class="bi bi-chevron-right admin-kpi-card__arrow" aria-hidden="true"></i>
            </a>
        @endforeach
    </section>

    {{-- 4. BRANCH PERFORMANCE BOARD --}}
    @if($isMainAdmin)
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 section admin-section-block admin-dashboard-main-board">

        @if($showBranchComparison)
        <div class="xl:col-span-7 card-custom flex flex-col">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">Collections by Branch</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Collected vs service value</p>
                </div>
                <i class="bi bi-wallet2 text-xl text-amber-400"></i>
            </div>

            <div class="space-y-4 flex-1">
                @foreach($branchRevenueCards ?? [] as $card)
                    @php
                        $serviceValue = (float) ($card['sales'] ?? 0);
                        $collectedValue = (float) ($card['collected'] ?? 0);
                        $branchCollectionRate = $serviceValue > 0 ? round(($collectedValue / $serviceValue) * 100) : 0;
                        $collectionBranchId = $card['branch']->id ?? null;
                        $collectionUrl = $collectionBranchId ? $branchCollectionUrl($collectionBranchId) : $collectedMonitoringUrl;
                    @endphp
                    <a
                        href="{{ $collectionUrl }}"
                        class="admin-branch-drilldown flex items-center justify-between p-4 border border-slate-100 transition-all group"
                        aria-label="View {{ $card['branch']->branch_name ?? 'branch' }} payments"
                    >
                        <div class="flex items-center gap-4">
                            <div class="rank-badge">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <h5 class="text-sm font-black text-slate-900 tracking-tight">{{ $card['branch']->branch_name ?? 'Branch' }}</h5>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">{{ $card['branch']->branch_code ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <h4 class="text-xl font-black text-slate-900 font-heading">PHP {{ number_format($collectedValue, 2) }}</h4>
                                <p class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest mt-0.5">{{ $branchCollectionRate }}% collected</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">of PHP {{ number_format($serviceValue, 2) }}</p>
                            </div>
                            <i class="bi bi-chevron-right admin-branch-drilldown__arrow" aria-hidden="true"></i>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="xl:col-span-5 card-custom flex flex-col">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">Cases by Branch</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Case records in the selected period</p>
                </div>
                <i class="bi bi-folder2-open text-xl text-[#3E4A3D]"></i>
            </div>

            <div class="flex-1 flex flex-col justify-center">
                @php
                    $volumeCollection = collect($caseVolume ?? []);
                    $maxVolume = max(1, (float) $volumeCollection->max('count'));
                    $totalVolume = (int) $volumeCollection->sum('count');
                    $roughTickStep = max(1, (int) ceil($maxVolume / 4));
                    $tickMagnitude = 10 ** floor(log10($roughTickStep));
                    $normalizedTickStep = $roughTickStep / $tickMagnitude;
                    $niceTickMultiplier = $normalizedTickStep <= 1 ? 1 : ($normalizedTickStep <= 2 ? 2 : ($normalizedTickStep <= 5 ? 5 : 10));
                    $volumeTickStep = max(1, (int) ($niceTickMultiplier * $tickMagnitude));
                    $volumeAxisMax = max($volumeTickStep, (int) (ceil($maxVolume / $volumeTickStep) * $volumeTickStep));
                    $volumeTicks = range(0, $volumeAxisMax, $volumeTickStep);
                @endphp

                @if($volumeCollection->isNotEmpty())
                    <div class="admin-case-volume-layout">
                        <div class="admin-case-volume-chart min-h-[250px] overflow-x-auto pb-1">
                            <div class="admin-case-volume-plot">
                                <span class="admin-case-volume-axis-title">Cases</span>
                                <div class="admin-case-volume-scale" aria-hidden="true">
                                    @foreach($volumeTicks as $tick)
                                        <span class="admin-case-volume-tick" style="--axis-position: {{ ($tick / $volumeAxisMax) * 100 }}%">{{ number_format($tick) }}</span>
                                    @endforeach
                                </div>
                                <div class="admin-case-volume-grid" aria-hidden="true">
                                    @foreach($volumeTicks as $tick)
                                        <span class="admin-case-volume-gridline" style="--axis-position: {{ ($tick / $volumeAxisMax) * 100 }}%"></span>
                                    @endforeach
                                </div>
                                <div class="admin-case-volume-bars grid auto-cols-fr grid-flow-col gap-3 items-end min-w-full min-h-[218px]">
                            @foreach($volumeCollection as $row)
                                @php
                                    $count = is_array($row) ? ($row['count'] ?? 0) : ($row->count ?? 0);
                                    $serviceBranchId = is_array($row) ? ($row['branch_id'] ?? null) : ($row->branch_id ?? null);
                                    $branchCode = is_array($row) ? ($row['branch_code'] ?? '') : ($row->branch_code ?? '');
                                    $branchName = is_array($row) ? ($row['branch_name'] ?? '') : ($row->branch_name ?? '');
                                    $height = $volumeAxisMax > 0 ? max(8, ($count / $volumeAxisMax) * 150) : 8;
                                    $servicesUrl = $serviceBranchId ? $branchServicesUrl($serviceBranchId) : $caseRecordsUrl;
                                @endphp
                                <a
                                    href="{{ $servicesUrl }}"
                                    class="admin-service-drilldown relative z-[1] min-w-[68px] flex flex-col items-center justify-end gap-2"
                                    title="{{ $branchName }} - {{ $count }} {{ Illuminate\Support\Str::plural('case', $count) }}"
                                    aria-label="View {{ $branchName }} case records"
                                >
                                    <div class="w-full h-[180px] flex items-end justify-center px-2">
                                        <div class="flex w-full max-w-[38px] flex-col items-center justify-end">
                                            <div class="admin-service-drilldown__bar w-full rounded-t-lg bg-[#3E4A3D] shadow-sm" style="height: {{ $height }}px"></div>
                                        </div>
                                    </div>
                                    <div class="text-center w-full">
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-700 truncate">{{ $branchCode }}</span>
                                        <span class="block text-[9px] font-bold text-slate-400 truncate">{{ $branchName }}</span>
                                    </div>
                                </a>
                            @endforeach
                                </div>
                            </div>
                            <div class="admin-case-volume-x-title">Branches</div>
                        </div>

                        <aside class="admin-case-volume-breakdown" aria-label="Case totals by branch">
                            <div class="admin-case-volume-breakdown__header">
                                <span>Branch breakdown</span>
                                <strong>{{ number_format($totalVolume) }}</strong>
                            </div>
                            <div class="admin-case-volume-breakdown__list">
                                @foreach($volumeCollection as $row)
                                    @php
                                        $count = (int) (is_array($row) ? ($row['count'] ?? 0) : ($row->count ?? 0));
                                        $serviceBranchId = is_array($row) ? ($row['branch_id'] ?? null) : ($row->branch_id ?? null);
                                        $branchCode = is_array($row) ? ($row['branch_code'] ?? '') : ($row->branch_code ?? '');
                                        $branchName = is_array($row) ? ($row['branch_name'] ?? '') : ($row->branch_name ?? '');
                                        $share = $totalVolume > 0 ? round(($count / $totalVolume) * 100) : 0;
                                        $servicesUrl = $serviceBranchId ? $branchServicesUrl($serviceBranchId) : $caseRecordsUrl;
                                    @endphp
                                    <a href="{{ $servicesUrl }}" class="admin-case-volume-breakdown__row" aria-label="View {{ $branchName }} case records">
                                        <span class="admin-case-volume-breakdown__branch">
                                            <strong>{{ $branchCode }}</strong>
                                            <span>{{ $branchName }}</span>
                                        </span>
                                        <span class="admin-case-volume-breakdown__value">
                                            <strong>{{ number_format($count) }}</strong>
                                            <span class="admin-case-volume-breakdown__share">{{ $share }}%</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </aside>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center text-slate-300 text-2xl mb-4">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No branch data available</p>
                    </div>
                @endif
            </div>
        </div>
        @else
        <div class="xl:col-span-7 card-custom flex flex-col">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">Payment Summary</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">{{ $adminBranchLabel }} · Active &amp; completed cases</p>
                </div>
                <a href="{{ $collectedMonitoringUrl }}" class="btn-secondary-custom btn-sm">View payments</a>
            </div>

            @php
                $summaryServiceValue = (float) ($totalServiceValue ?? $totalSales ?? 0);
                $summaryCollected = (float) ($summaryCollectedTotal ?? 0);
                $summaryOutstanding = (float) ($totalOutstanding ?? 0);
                $collectedWidth = min(100, max(0, $collectionRate));
                $outstandingWidth = max(0, 100 - $collectedWidth);
                @endphp

            <div class="flex flex-col gap-5">
                <div class="p-5 rounded-2xl border border-slate-100">
                    <div class="flex items-end justify-between gap-4 mb-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Payment Progress</p>
                            <h4 class="text-4xl font-black text-slate-900 font-heading leading-none">{{ $collectionRate }}%</h4>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Amount</p>
                            <p class="text-lg font-black text-slate-900 font-heading">PHP {{ number_format($summaryServiceValue, 2) }}</p>
                        </div>
                    </div>

                    <div class="h-5 bg-[#9E4B3F]/25 rounded-full overflow-hidden flex" aria-label="Collection progress">
                        <div class="h-full bg-[#5F7D5F]" style="width: {{ $collectedWidth }}%"></div>
                        <div class="h-full bg-[#9E4B3F]" style="width: {{ $outstandingWidth }}%"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-[#5F7D5F]"></span>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Received</p>
                                <p class="text-sm font-black text-slate-900">PHP {{ number_format($summaryCollected, 2) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-[#9E4B3F]"></span>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Remaining</p>
                                <p class="text-sm font-black text-slate-900">PHP {{ number_format($summaryOutstanding, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <a href="{{ $paidMonitoringUrl }}" class="dashboard-click-card p-4 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Fully Paid</p>
                        <h4 class="text-xl font-black text-slate-900 font-heading">{{ (int) ($paidCases ?? 0) }}</h4>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Active &amp; completed cases</p>
                    </a>
                    <a href="{{ $partialMonitoringUrl }}" class="dashboard-click-card p-4 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Partially Paid</p>
                        <h4 class="text-xl font-black text-slate-900 font-heading">{{ (int) ($partialCases ?? 0) }}</h4>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Active &amp; completed cases</p>
                    </a>
                    <a href="{{ $balancePaymentMonitoringUrl }}" class="dashboard-click-card p-4 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">With Balance</p>
                        <h4 class="text-xl font-black text-slate-900 font-heading">{{ $servicesWithBalance }}</h4>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Active + completed with balance</p>
                    </a>
                </div>
            </div>
        </div>

        <div class="xl:col-span-5 card-custom admin-action-center flex flex-col">
            <div class="admin-action-center__header">
                <div>
                    <h3>Operations Overview</h3>
                    <p>{{ $adminBranchLabel }}</p>
                </div>
                <a href="{{ $allRemindersUrl }}" class="btn-secondary-custom btn-sm">View all reminders</a>
            </div>

            <section class="admin-action-group" aria-labelledby="currently-in-wake-title">
                <div class="admin-action-group__title">
                    <div>
                        <span class="admin-action-group__icon"><i class="bi bi-moon-stars" aria-hidden="true"></i></span>
                        <div>
                            <h4 id="currently-in-wake-title">Currently in Wake</h4>
                            <small class="admin-action-group__scope">Active wake or viewing periods</small>
                        </div>
                    </div>
                    <a href="{{ route('admin.cases.index', $branchLinkParams) }}">{{ $currentlyInWakeItems->count() }} active {{ \Illuminate\Support\Str::plural('case', $currentlyInWakeItems->count()) }}</a>
                </div>
                <div class="admin-action-list">
                    @forelse($currentlyInWakeItems->take(2) as $item)
                        <a href="{{ route('admin.cases.edit', $item['case_id']) }}" class="admin-action-item">
                            <span>
                                <strong>{{ $item['case_code'] }} — {{ $item['deceased_name'] }}</strong>
                                <small>Day {{ $item['current_day'] }} of {{ $item['total_days'] }} · Ends {{ $item['ends_at']?->format('M d, Y · h:i A') }}@if($showItemBranch) · {{ $item['branch_label'] }}@endif</small>
                            </span>
                            <em>View case</em>
                        </a>
                    @empty
                        <div class="admin-action-empty"><i class="bi bi-check2-circle" aria-hidden="true"></i> No active wakes in this scope</div>
                    @endforelse
                </div>
            </section>

            <section class="admin-action-group" aria-labelledby="today-schedules-title">
                <div class="admin-action-group__title">
                    <div>
                        <span class="admin-action-group__icon"><i class="bi bi-calendar2-check"></i></span>
                        <div><h4 id="today-schedules-title">Today’s Scheduled Events</h4><small class="admin-action-group__scope">Wake, ceremony, and interment activities today</small></div>
                    </div>
                    <a href="{{ $todaySchedulesUrl }}">{{ $todayScheduleItems->count() }} today</a>
                </div>
                <div class="admin-action-list">
                    @forelse($todayScheduleItems->take(2) as $item)
                        <a href="{{ route('admin.reminders.index', ['branch_id' => $item['branch_id'] ?? ($selectedBranchId ?? null), 'tab' => 'today', 'focus_case' => $item['case_id']]) }}#case-reminder-{{ $item['case_id'] }}" class="admin-action-item">
                            <span>
                                <strong>{{ $item['label'] ?? $item['title'] ?? 'Scheduled event' }}</strong>
                                <small>{{ $item['case_code'] ?? 'Service record' }} — {{ $item['deceased_name'] ?? 'Deceased' }}@if(!empty($item['location'])) · {{ $item['location'] }}@endif @if($showItemBranch) · {{ $item['branch_label'] ?? 'Branch' }}@endif</small>
                            </span>
                            <time>{{ isset($item['date']) && $item['date'] ? $item['date']->format('h:i A') : 'Today' }}</time>
                        </a>
                    @empty
                        <div class="admin-action-empty"><i class="bi bi-check2-circle"></i> No scheduled events today</div>
                    @endforelse
                    @if($todayScheduleItems->count() > 2)
                        @php
                            $moreTodayCount = $todayScheduleItems->count() - 2;
                        @endphp
                        <a href="{{ $todaySchedulesUrl }}" class="admin-action-more">
                            <span>Showing 2 of {{ $todayScheduleItems->count() }} scheduled today</span>
                            <strong>+{{ $moreTodayCount }} more</strong>
                        </a>
                    @endif
                </div>
            </section>

            <section class="admin-action-group" aria-labelledby="upcoming-schedules-title">
                <div class="admin-action-group__title">
                    <div>
                        <span class="admin-action-group__icon"><i class="bi bi-calendar3"></i></span>
                        <div><h4 id="upcoming-schedules-title">Upcoming Events</h4><small class="admin-action-group__scope">Next branch activities within seven days</small></div>
                    </div>
                    <a href="{{ route('admin.reminders.index', array_merge($branchLinkParams, ['tab' => 'upcoming'])) }}">{{ $upcomingScheduleItems->count() }} upcoming</a>
                </div>
                <div class="admin-action-list">
                    @forelse($upcomingScheduleItems->take(2) as $item)
                        <a href="{{ route('admin.reminders.index', ['branch_id' => $item['branch_id'] ?? ($selectedBranchId ?? null), 'tab' => 'upcoming', 'focus_case' => $item['case_id']]) }}#case-reminder-{{ $item['case_id'] }}" class="admin-action-item">
                            <span>
                                <strong>{{ $item['label'] ?? $item['title'] ?? 'Upcoming event' }}</strong>
                                <small>{{ $item['case_code'] ?? 'Service record' }} — {{ $item['deceased_name'] ?? 'Deceased' }}@if(!empty($item['location'])) · {{ $item['location'] }}@endif @if($showItemBranch) · {{ $item['branch_label'] ?? 'Branch' }}@endif</small>
                            </span>
                            <time>{{ isset($item['date']) && $item['date'] ? $item['date']->format('M d · h:i A') : 'Upcoming' }}</time>
                        </a>
                    @empty
                        <div class="admin-action-empty"><i class="bi bi-check2-circle"></i> No schedules in the next 7 days</div>
                    @endforelse
                    @if($upcomingScheduleItems->count() > 2)
                        @php
                            $moreUpcomingCount = $upcomingScheduleItems->count() - 2;
                        @endphp
                        <a href="{{ route('admin.reminders.index', array_merge($branchLinkParams, ['tab' => 'upcoming'])) }}" class="admin-action-more">
                            <span>Showing 2 of {{ $upcomingScheduleItems->count() }} upcoming schedules</span>
                            <strong>+{{ $moreUpcomingCount }} more</strong>
                        </a>
                    @endif
                </div>
            </section>

            <section class="admin-action-group is-alert" aria-labelledby="attention-title">
                <div class="admin-action-group__title">
                    <div>
                        <span class="admin-action-group__icon"><i class="bi bi-exclamation-triangle"></i></span>
                        <h4 id="attention-title">Cases With Balance</h4>
                        <small class="admin-action-group__scope">Outstanding amounts requiring review</small>
                    </div>
                    <a href="{{ $balancePaymentMonitoringUrl }}">{{ $balanceCases->count() }} {{ \Illuminate\Support\Str::plural('case', $balanceCases->count()) }}</a>
                </div>
                <div class="admin-action-list">
                    @forelse($balanceCases->take(2) as $item)
                        @php
                            $itemPaymentUrl = route('admin.payment-monitoring', array_merge($branchLinkParams, [
                                'branch_id' => $item['branch_id'] ?? ($selectedBranchId ?? null),
                                'payment_status' => 'WITH_BALANCE',
                                'q' => $item['case_code'] ?? '',
                                'tab' => 'summary',
                            ]));
                        @endphp
                        <a href="{{ $itemPaymentUrl }}" class="admin-action-item">
                            <span>
                                <strong>{{ $item['case_code'] ?? 'Service record' }}</strong>
                                <small>{{ $item['deceased_name'] ?? 'Client' }} · {{ $item['case']->client?->full_name ?? 'N/A' }}@if($showItemBranch) · {{ $item['branch_label'] ?? 'Branch' }}@endif</small>
                            </span>
                            <em>PHP {{ number_format((float) data_get($item, 'case.balance_amount', 0), 2) }}</em>
                        </a>
                    @empty
                        <div class="admin-action-empty"><i class="bi bi-check2-circle"></i> No cases with remaining balance</div>
                    @endforelse
                    @if($balanceCases->count() > 2)
                        @php
                            $moreBalanceCount = $balanceCases->count() - 2;
                        @endphp
                        <a href="{{ $balancePaymentMonitoringUrl }}" class="admin-action-more">
                            <span>Showing 2 of {{ $balanceCases->count() }} cases with balance</span>
                            <strong>+{{ $moreBalanceCount }} {{ \Illuminate\Support\Str::plural('case', $moreBalanceCount) }}</strong>
                        </a>
                    @endif
                </div>
            </section>
        </div>
        @endif
    </div>
    @else
    <div class="branch-board" aria-label="{{ $adminBranchLabel }} operations dashboard">
        <div class="branch-board__column">
            <section class="branch-panel" aria-labelledby="branch-wake-title">
                <div class="branch-panel__head">
                    <div class="branch-panel__title">
                        <span class="branch-panel__icon"><i class="bi bi-moon-stars" aria-hidden="true"></i></span>
                        <div><h3 id="branch-wake-title">Currently in Wake</h3><p>Cases whose wake period is active right now</p></div>
                    </div>
                    <div class="branch-panel__tools"><span class="branch-panel__count">{{ $currentlyInWakeItems->count() }} {{ \Illuminate\Support\Str::plural('case', $currentlyInWakeItems->count()) }}</span><a href="{{ route('admin.reminders.index', array_merge($branchLinkParams, ['tab' => 'current_wake'])) }}" class="branch-panel__link" aria-label="View Currently in Wake" data-tooltip="View Currently in Wake">View &rarr;</a></div>
                </div>
                <div class="branch-record-list">
                    @forelse($currentlyInWakeItems->take(2) as $item)
                        @php
                            $caseDetailsUrl = route('funeral-cases.show', [
                                'funeral_case' => $item['case_id'],
                                'return_to' => request()->fullUrl(),
                                'focus_section' => 'wake',
                            ]);
                        @endphp
                        <article class="branch-record-card" data-case-entry>
                            <div class="branch-record-card__summary">
                                <div class="branch-record-card__identity"><strong>{{ $item['case_code'] }}</strong><span>{{ $item['deceased_name'] }}</span></div>
                                <div class="branch-record-card__meta">
                                    <div class="branch-record-card__field"><span class="branch-day-badge">Day {{ $item['current_day'] }} of {{ $item['total_days'] }}</span><small>Wake Progress</small></div>
                                    <div class="branch-record-card__field"><strong>{{ $item['ends_at']?->format('M d, Y · g:i A') ?? 'Not set' }}</strong><small>Expected End</small></div>
                                </div>
                            </div>
                            <a class="branch-row-destination" href="{{ $caseDetailsUrl }}" aria-label="View Case Details for {{ $item['case_code'] }}"><i class="bi bi-eye" aria-hidden="true"></i><span class="branch-row-destination__label" aria-hidden="true">View Case Details</span></a>
                        </article>
                    @empty
                        <div class="branch-record-empty">No cases are currently in wake.</div>
                    @endforelse
                </div>
                @if($currentlyInWakeItems->count() > 2)
                    <a href="{{ route('admin.reminders.index', array_merge($branchLinkParams, ['tab' => 'current_wake'])) }}" class="branch-panel__overflow"><span>Showing 2 of {{ $currentlyInWakeItems->count() }} cases</span><strong>+{{ $currentlyInWakeItems->count() - 2 }} more</strong></a>
                @endif
            </section>

            <section class="branch-panel is-neutral" aria-labelledby="branch-today-title">
                <div class="branch-panel__head">
                    <div class="branch-panel__title"><span class="branch-panel__icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span><div><h3 id="branch-today-title">Scheduled Today</h3><p>Wake, ceremony, and interment events occurring today</p></div></div>
                    <div class="branch-panel__tools"><span class="branch-panel__count">{{ $todayScheduleItems->count() }} {{ \Illuminate\Support\Str::plural('event', $todayScheduleItems->count()) }}</span><a href="{{ $todaySchedulesUrl }}" class="branch-panel__link" aria-label="View Today's Schedule" data-tooltip="View Today's Schedule">View &rarr;</a></div>
                </div>
                <div class="branch-record-list">
                    @forelse($todayScheduleItems->take(3) as $item)
                            @php
                                $eventType = (string) ($item['type'] ?? '');
                                $eventClass = str_contains($eventType, 'wake_start') ? 'is-wake-start' : (str_contains($eventType, 'wake_end') ? 'is-wake-end' : (str_contains($eventType, 'interment') ? 'is-interment' : ''));
                                $focusEvent = str_contains($eventType, 'wake_start') ? 'wake-start' : (str_contains($eventType, 'wake_end') ? 'wake-end' : (str_contains($eventType, 'interment') ? 'interment' : 'funeral-ceremony'));
                                $caseDetailsUrl = route('funeral-cases.show', [
                                    'funeral_case' => $item['case_id'],
                                    'return_to' => request()->fullUrl(),
                                    'focus_event' => $focusEvent,
                                ]);
                            @endphp
                        <article class="branch-record-card" data-case-entry>
                            <div class="branch-record-card__summary">
                                <div class="branch-record-card__identity"><strong>{{ $item['case_code'] ?? 'N/A' }}</strong><span>{{ $item['deceased_name'] ?? 'N/A' }}</span></div>
                                <div class="branch-record-card__event">
                                    <div class="branch-record-card__event-copy"><span class="branch-event-badge {{ $eventClass }}">{{ $item['label'] ?? 'Scheduled Event' }}</span><span class="branch-record-card__event-support">Today · {{ $item['date']?->format('g:i A') ?? 'Time not set' }}</span></div>
                                    <span class="branch-record-card__event-meta"><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $item['location'] ?: 'Location not set' }}</span>
                                </div>
                            </div>
                            <a class="branch-row-destination" href="{{ $caseDetailsUrl }}" aria-label="View Case Details for {{ $item['case_code'] ?? 'case' }}"><i class="bi bi-eye" aria-hidden="true"></i><span class="branch-row-destination__label" aria-hidden="true">View Case Details</span></a>
                        </article>
                    @empty
                        <div class="branch-record-empty">No scheduled events today.</div>
                    @endforelse
                </div>
                @if($todayScheduleItems->count() > 3)
                    <a href="{{ $todaySchedulesUrl }}" class="branch-panel__overflow"><span>Showing 3 of {{ $todayScheduleItems->count() }} events</span><strong>+{{ $todayScheduleItems->count() - 3 }} more</strong></a>
                @endif
            </section>

            <section class="branch-panel" aria-labelledby="branch-upcoming-title">
                <div class="branch-panel__head">
                    <div class="branch-panel__title"><span class="branch-panel__icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span><div><h3 id="branch-upcoming-title">Upcoming Events</h3><p>Next valid branch events within seven days</p></div></div>
                    <div class="branch-panel__tools"><span class="branch-panel__count">{{ $upcomingScheduleItems->count() }} {{ \Illuminate\Support\Str::plural('event', $upcomingScheduleItems->count()) }}</span><a href="{{ route('admin.reminders.index', array_merge($branchLinkParams, ['tab' => 'upcoming'])) }}" class="branch-panel__link" aria-label="View Upcoming Schedule" data-tooltip="View Upcoming Schedule">View &rarr;</a></div>
                </div>
                <div class="branch-record-list">
                    @forelse($upcomingCaseGroups->take(3) as $caseGroup)
                            @php
                                $focusEvents = $caseGroup['events']->map(function ($event) {
                                    $eventType = (string) ($event['type'] ?? '');
                                    return str_contains($eventType, 'wake_start') ? 'wake-start' : (str_contains($eventType, 'wake_end') ? 'wake-end' : (str_contains($eventType, 'interment') ? 'interment' : 'funeral-ceremony'));
                                })->unique()->implode(',');
                                $caseDetailsUrl = route('funeral-cases.show', [
                                    'funeral_case' => $caseGroup['case_id'],
                                    'return_to' => request()->fullUrl(),
                                    'focus_events' => $focusEvents,
                                ]);
                            @endphp
                        <article class="branch-record-card branch-record-card--upcoming" data-case-entry>
                            <div class="branch-record-card__identity"><strong>{{ $caseGroup['case_code'] }}</strong><span>{{ $caseGroup['deceased_name'] }} · {{ $caseGroup['events']->count() }} {{ \Illuminate\Support\Str::plural('schedule', $caseGroup['events']->count()) }}</span></div>
                            <div class="branch-schedule-stack">
                                        @foreach($caseGroup['events'] as $event)
                                            @php
                                                $eventType = (string) ($event['type'] ?? '');
                                                $eventClass = str_contains($eventType, 'wake_start') ? 'is-wake-start' : (str_contains($eventType, 'wake_end') ? 'is-wake-end' : (str_contains($eventType, 'interment') ? 'is-interment' : ''));
                                            @endphp
                                            <div class="branch-schedule-item">
                                                <span class="branch-schedule-item__detail"><span class="branch-event-badge {{ $eventClass }}">{{ $event['label'] ?? 'Upcoming Schedule' }}</span></span>
                                                <span class="branch-schedule-item__when">{{ $event['date']?->format('M d · g:i A') ?? 'Date not set' }} · {{ $event['location'] ?: 'Location not set' }}</span>
                                            </div>
                                        @endforeach
                            </div>
                            <a class="branch-row-destination" href="{{ $caseDetailsUrl }}" aria-label="View Case Details for {{ $caseGroup['case_code'] }}"><i class="bi bi-eye" aria-hidden="true"></i><span class="branch-row-destination__label" aria-hidden="true">View Case Details</span></a>
                        </article>
                    @empty
                        <div class="branch-record-empty">No upcoming schedules in the next seven days.</div>
                    @endforelse
                </div>
                @if($upcomingCaseGroups->count() > 3)
                    <a href="{{ route('admin.reminders.index', array_merge($branchLinkParams, ['tab' => 'upcoming'])) }}" class="branch-panel__overflow"><span>Showing 3 of {{ $upcomingCaseGroups->count() }} cases · {{ $upcomingScheduleItems->count() }} schedules</span><strong>+{{ $upcomingCaseGroups->count() - 3 }} more cases</strong></a>
                @endif
            </section>
        </div>

        <aside class="branch-board__column" aria-label="Branch financial overview">
            <section class="branch-panel is-warm" aria-labelledby="branch-balance-title">
                <div class="branch-panel__head">
                    <div class="branch-panel__title"><span class="branch-panel__icon"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i></span><div><h3 id="branch-balance-title">Cases With Balance</h3><p>Active and completed cases with outstanding amounts</p></div></div>
                    <a href="{{ $balancePaymentMonitoringUrl }}" class="branch-panel__link" aria-label="View Cases With Balance" data-tooltip="View Cases With Balance">View &rarr;</a>
                </div>
                <div class="branch-balance-list">
                    @forelse($balanceCases->take(3) as $item)
                        @php $itemPaymentUrl = route('admin.payment-monitoring', array_merge($branchLinkParams, ['payment_status' => 'WITH_BALANCE', 'q' => $item['case_code'] ?? '', 'tab' => 'summary'])); @endphp
                        <a href="{{ $itemPaymentUrl }}" class="branch-balance-item">
                            <span><strong>{{ $item['case_code'] ?? 'Service record' }}</strong><small>{{ $item['deceased_name'] ?? 'Deceased' }} · {{ $item['case']->client?->full_name ?? 'Client not set' }}</small></span>
                            <span class="branch-balance-item__amount">PHP {{ number_format((float) data_get($item, 'case.balance_amount', 0), 2) }}</span>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    @empty
                        <div class="branch-command__empty"><i class="bi bi-check2-circle" aria-hidden="true"></i> No cases with remaining balance.</div>
                    @endforelse
                </div>
                @if($balanceCases->count() > 3)
                    <a href="{{ $balancePaymentMonitoringUrl }}" class="branch-panel__overflow"><span>Showing 3 of {{ $balanceCases->count() }} cases</span><strong>+{{ $balanceCases->count() - 3 }} more</strong></a>
                @endif
            </section>

            <section class="branch-panel is-neutral" aria-labelledby="branch-payment-title">
                <div class="branch-panel__head">
                    <div class="branch-panel__title"><span class="branch-panel__icon"><i class="bi bi-pie-chart-fill" aria-hidden="true"></i></span><div><h3 id="branch-payment-title">Payment Summary</h3><p>Current financial position of active and completed cases</p></div></div>
                    <a href="{{ $paymentProgressUrl }}" class="branch-panel__link" aria-label="Open Payment Monitoring" data-tooltip="Open Payment Monitoring">View &rarr;</a>
                </div>
                <div class="branch-payment-summary">
                    <div class="branch-payment-ring" style="--collection-rate: {{ min(max($collectionRate, 0), 100) }}%" role="img" aria-label="{{ $collectionRate }} percent collected"><div class="branch-payment-ring__inside"><span><strong>{{ $collectionRate }}%</strong><span>Collected</span></span></div></div>
                    <div class="branch-payment-lines">
                        <div class="branch-payment-line"><span>Collected to date</span><strong>PHP {{ number_format((float) ($summaryCollectedTotal ?? 0), 2) }}</strong></div>
                        <div class="branch-payment-line"><span>Outstanding</span><strong>PHP {{ number_format((float) ($totalOutstanding ?? 0), 2) }}</strong></div>
                        <div class="branch-payment-line is-total"><span>Total service amount</span><strong>PHP {{ number_format((float) ($totalServiceValue ?? 0), 2) }}</strong></div>
                    </div>
                </div>
            </section>

        </aside>
    </div>
    @endif

</div>
</div>
@endsection
