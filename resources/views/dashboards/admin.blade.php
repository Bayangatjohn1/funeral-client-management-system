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
    $showBranchComparison = $isMainAdmin && !($selectedBranchId ?? null) && collect($branchRevenueCards ?? [])->count() > 1;
    $collectionRate = (float) ($totalServiceValue ?? 0) > 0
        ? round(((float) ($totalCollected ?? 0) / (float) $totalServiceValue) * 100)
        : 0;
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
        overflow: visible;
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
            <label class="admin-filter-control">
                <i class="bi bi-building admin-filter-icon"></i>
                <select name="branch_id" onchange="this.form.submit()" class="input-custom w-48" @if($isBranchAdmin) disabled @endif>
                    <option value="">{{ $isMainAdmin ? 'All Branches' : 'Assigned Branch' }}</option>
                    @foreach($branches ?? [] as $branch)
                        <option value="{{ $branch->id }}" {{ (string) ($selectedBranchId ?? '') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->branch_code }} - {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down admin-filter-chevron"></i>
            </label>

            <label class="admin-filter-control is-period">
                <i class="bi bi-funnel admin-filter-icon"></i>
                <select name="date_filter" onchange="this.form.submit()" class="input-custom w-40">
                    <option value="all" {{ ($selectedDateFilter ?? 'this_month') === 'all' ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ ($selectedDateFilter ?? 'this_month') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="this_week" {{ ($selectedDateFilter ?? 'this_month') === 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ ($selectedDateFilter ?? 'this_month') === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="this_year" {{ ($selectedDateFilter ?? 'this_month') === 'this_year' ? 'selected' : '' }}>This Year</option>
                </select>
                <i class="bi bi-chevron-down admin-filter-chevron"></i>
            </label>
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
                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 text-lg">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>

            <div>
                <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-black mb-2">{{ $isMainAdmin ? 'Total Collected' : 'Collected Amount' }}</p>
                <h4 class="font-black font-heading tracking-tight">
                    <span class="text-emerald-400 font-sans mr-1">₱</span>{{ number_format((float) ($totalCollected ?? 0), 2) }}
                </h4>
            </div>
        </a>

        {{-- Outstanding Balance --}}
        <a href="{{ $outstandingMonitoringUrl }}" class="dashboard-click-card admin-financial-card flex flex-col justify-between">
            <div class="admin-financial-card__head flex items-center justify-between gap-4">
                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 text-lg">
                    <i class="bi bi-graph-down-arrow"></i>
                </div>
            </div>

            <div>
                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500 font-semibold mb-2">{{ $isMainAdmin ? 'Total Outstanding Balance' : 'Outstanding Balance' }}</p>
                <h4 class="font-black font-heading tracking-tight">
                    <span class="font-sans mr-1">₱</span>{{ number_format((float) ($totalOutstanding ?? 0), 2) }}
                </h4>
            </div>
        </a>
    </div>

    {{-- 3. SERVICE METRICS --}}
    <section class="section admin-section-block admin-summary-section">
        <h3 class="admin-summary-section__title">Service and Payment Summary</h3>
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            @php
                $caseStats = [
                    ['label' => 'Total Services', 'val' => $totalCases ?? 0, 'icon' => 'bi-folder2-open', 'color' => 'text-slate-900', 'url' => $caseRecordsUrl],
                    ['label' => 'Ongoing Services', 'val' => $ongoingCases ?? 0, 'icon' => 'bi-arrow-repeat', 'color' => 'text-slate-900', 'url' => $activeCasesUrl],
                    ['label' => 'Paid in Full', 'val' => $paidCases ?? 0, 'icon' => 'bi-check-circle', 'color' => 'text-emerald-600', 'url' => $paidMonitoringUrl],
                    ['label' => 'Partially Paid', 'val' => $partialCases ?? 0, 'icon' => 'bi-pie-chart', 'color' => 'text-amber-600', 'url' => $partialMonitoringUrl],
                    ['label' => 'Unpaid Services', 'val' => $unpaidCases ?? 0, 'icon' => 'bi-exclamation-triangle', 'color' => 'text-red-600', 'url' => $unpaidMonitoringUrl],
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
                        <div class="stat-value {{ $s['color'] }} {{ isset($s['is_money']) ? 'is-money' : '' }}">
                            @if(isset($s['is_money']))
                                <span class="currency-mark">₱</span>
                            @endif{{ $s['val'] }}
                        </div>
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
                    @endphp
                    <div class="flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:bg-slate-50 transition-all group">
                        <div class="flex items-center gap-4">
                            <div class="rank-badge">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <h5 class="text-sm font-black text-slate-900 tracking-tight">{{ $card['branch']->branch_name ?? 'Branch' }}</h5>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">{{ $card['branch']->branch_code ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h4 class="text-xl font-black text-slate-900 font-heading">PHP {{ number_format($collectedValue, 2) }}</h4>
                            <p class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest mt-0.5">{{ $branchCollectionRate }}% collected</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">of PHP {{ number_format($serviceValue, 2) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="xl:col-span-5 card-custom flex flex-col">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">Services by Branch</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Service records per branch</p>
                </div>
                <i class="bi bi-folder2-open text-xl text-[#3E4A3D]"></i>
            </div>
            
            <div class="flex-1 flex flex-col justify-center">
                @php
                    $volumeCollection = collect($caseVolume ?? []);
                    $maxVolume = max(1, (float) $volumeCollection->max('count'));
                @endphp

                @if($volumeCollection->isNotEmpty())
                    <div class="relative min-h-[250px] overflow-x-auto pb-1">
                        <div class="absolute inset-x-0 bottom-[3.85rem] border-t border-slate-200"></div>
                        <div class="grid auto-cols-fr grid-flow-col gap-4 items-end min-w-full min-h-[238px]">
                        @foreach($volumeCollection as $row)
                            @php
                                $count = is_array($row) ? ($row['count'] ?? 0) : ($row->count ?? 0);
                                $branchCode = is_array($row) ? ($row['branch_code'] ?? '') : ($row->branch_code ?? '');
                                $branchName = is_array($row) ? ($row['branch_name'] ?? '') : ($row->branch_name ?? '');
                                $height = $maxVolume > 0 ? max(8, ($count / $maxVolume) * 170) : 8;
                            @endphp
                            <div class="relative z-[1] min-w-[72px] flex flex-col items-center justify-end gap-2" title="{{ $branchName }} - {{ $count }} services">
                                <div class="w-full h-[180px] flex items-end justify-center px-2">
                                    <div class="flex w-full max-w-[42px] flex-col items-center justify-end gap-1">
                                        <span class="text-sm font-black text-[#3E4A3D] font-heading leading-none">{{ $count }}</span>
                                        <div class="w-full rounded-t-xl bg-[#3E4A3D] shadow-sm" style="height: {{ $height }}px"></div>
                                    </div>
                                </div>
                                <div class="text-center w-full">
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-slate-700 truncate">{{ $branchCode }}</span>
                                    <span class="block text-[9px] font-bold text-slate-400 truncate">{{ $branchName }}</span>
                                </div>
                            </div>
                        @endforeach
                        </div>
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
                    <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">Collection Summary</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">{{ $adminBranchLabel }}</p>
                </div>
                <a href="{{ $collectedMonitoringUrl }}" class="btn-secondary-custom btn-sm">View payments</a>
            </div>

            @php
                $summaryServiceValue = (float) ($totalServiceValue ?? $totalSales ?? 0);
                $summaryCollected = (float) ($totalCollected ?? 0);
                $summaryOutstanding = (float) ($totalOutstanding ?? 0);
                $collectedWidth = min(100, max(0, $collectionRate));
                $outstandingWidth = max(0, 100 - $collectedWidth);
                $servicesWithBalance = (int) ($partialCases ?? 0) + (int) ($unpaidCases ?? 0);
            @endphp

            <div class="flex flex-col gap-5">
                <div class="p-5 rounded-2xl border border-slate-100">
                    <div class="flex items-end justify-between gap-4 mb-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Collection Rate</p>
                            <h4 class="text-4xl font-black text-slate-900 font-heading leading-none">{{ $collectionRate }}%</h4>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Service Value</p>
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
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Collected</p>
                                <p class="text-sm font-black text-slate-900">PHP {{ number_format($summaryCollected, 2) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-[#9E4B3F]"></span>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Unpaid Balance</p>
                                <p class="text-sm font-black text-slate-900">PHP {{ number_format($summaryOutstanding, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <a href="{{ $paidMonitoringUrl }}" class="dashboard-click-card p-4 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Paid Services</p>
                        <h4 class="text-xl font-black text-slate-900 font-heading">{{ (int) ($paidCases ?? 0) }}</h4>
                    </a>
                    <a href="{{ $partialMonitoringUrl }}" class="dashboard-click-card p-4 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Partial Services</p>
                        <h4 class="text-xl font-black text-slate-900 font-heading">{{ (int) ($partialCases ?? 0) }}</h4>
                    </a>
                    <a href="{{ $unpaidMonitoringUrl }}" class="dashboard-click-card p-4 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Services With Balance</p>
                        <h4 class="text-xl font-black text-slate-900 font-heading">{{ $servicesWithBalance }}</h4>
                    </a>
                </div>
            </div>
        </div>

        <div class="xl:col-span-5 card-custom flex flex-col">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">Service Status Summary</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Current branch view</p>
                </div>
                <a href="{{ $caseRecordsUrl }}" class="btn-secondary-custom btn-sm">View services</a>
            </div>

            <div class="space-y-4 flex-1 flex flex-col justify-center">
                @php
                    $statusRows = [
                        ['label' => 'Ongoing', 'count' => (int) ($ongoingCases ?? 0), 'url' => $activeCasesUrl],
                        ['label' => 'Paid in Full', 'count' => (int) ($paidCases ?? 0), 'url' => $paidMonitoringUrl],
                        ['label' => 'Partial', 'count' => (int) ($partialCases ?? 0), 'url' => $partialMonitoringUrl],
                        ['label' => 'Unpaid', 'count' => (int) ($unpaidCases ?? 0), 'url' => $unpaidMonitoringUrl],
                    ];
                    $maxStatusCount = max(1, collect($statusRows)->max('count'));
                @endphp

                @foreach($statusRows as $row)
                    @php
                        $width = $maxStatusCount > 0 ? ($row['count'] / $maxStatusCount) * 100 : 0;
                    @endphp
                    <a href="{{ $row['url'] }}" class="dashboard-click-card block p-3 rounded-2xl border border-slate-100">
                        <div class="flex items-end justify-between mb-2">
                            <span class="text-xs font-black text-slate-900">{{ $row['label'] }}</span>
                            <span class="text-lg font-black text-[#3E4A3D] font-heading">{{ $row['count'] }}</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-[#3E4A3D]" style="width: {{ $width }}%"></div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
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
                                {{ $item['case_code'] ?? 'Service record' }} - {{ $item['client_name'] ?? 'Client' }}
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
                            <p class="text-sm font-black text-slate-900">{{ $item['case_code'] ?? 'Service record' }}</p>
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
            <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-slate-50 border border-slate-200 text-[11px] font-black text-slate-500 uppercase tracking-widest hover:text-slate-900 hover:bg-slate-100 rounded-full transition-colors w-full sm:w-auto">
                View All Audit Logs
            </a>
            @else
            <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-slate-50 border border-slate-200 text-[11px] font-black text-slate-500 uppercase tracking-widest hover:text-slate-900 hover:bg-slate-100 rounded-full transition-colors w-full sm:w-auto">
                View All Audit Logs
            </a>
            @endif
        </div>

        <div class="relative max-w-4xl mx-auto">
            {{-- Vertical Line --}}
            <div class="absolute left-[23px] top-4 bottom-4 w-px bg-slate-100"></div>

            <div class="space-y-6 relative">
                @php
                    $mockupLogs = [
                        ['time' => '10 mins ago', 'user' => 'Admin Juan', 'action' => 'Approved package void request for Service #1029', 'icon' => 'bi-shield-check', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50', 'ring' => 'ring-emerald-50'],
                        ['time' => '1 hour ago', 'user' => 'Staff Maria', 'action' => 'Encoded initial payment (₱15,000) for Service #1030', 'icon' => 'bi-cash-stack', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50', 'ring' => 'ring-blue-50'],
                        ['time' => '3 hours ago', 'user' => 'Owner', 'action' => 'Updated Executive Package pricing matrix', 'icon' => 'bi-tags-fill', 'color' => 'text-[#3E4A3D]', 'bg' => 'bg-[#3E4A3D]/10', 'ring' => 'ring-[#3E4A3D]/5'],
                        ['time' => 'Yesterday', 'user' => 'Staff Pedro', 'action' => 'Created new intake record for Deceased: Dela Cruz', 'icon' => 'bi-file-earmark-plus-fill', 'color' => 'text-slate-500', 'bg' => 'bg-slate-100', 'ring' => 'ring-slate-50'],
                    ];
                @endphp

                @php
                    $actionTypeMap = [
                        'create'        => ['icon' => 'bi-file-earmark-plus-fill', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50',  'ring' => 'ring-emerald-50'],
                        'update'        => ['icon' => 'bi-pencil-fill',             'color' => 'text-blue-500',    'bg' => 'bg-blue-50',     'ring' => 'ring-blue-50'],
                        'delete'        => ['icon' => 'bi-trash3-fill',             'color' => 'text-red-500',     'bg' => 'bg-red-50',      'ring' => 'ring-red-50'],
                        'status_change' => ['icon' => 'bi-arrow-repeat',            'color' => 'text-amber-500',   'bg' => 'bg-amber-50',    'ring' => 'ring-amber-50'],
                        'financial'     => ['icon' => 'bi-cash-stack',              'color' => 'text-indigo-500',  'bg' => 'bg-indigo-50',   'ring' => 'ring-indigo-50'],
                        'security'      => ['icon' => 'bi-shield-check',            'color' => 'text-purple-500',  'bg' => 'bg-purple-50',   'ring' => 'ring-purple-50'],
                        'permission'    => ['icon' => 'bi-key-fill',                'color' => 'text-slate-500',   'bg' => 'bg-slate-100',   'ring' => 'ring-slate-50'],
                    ];
                    $actionTypeDefault = ['icon' => 'bi-journal-text', 'color' => 'text-slate-500', 'bg' => 'bg-slate-100', 'ring' => 'ring-slate-50'];
                @endphp

                @forelse($auditLogs ?? $mockupLogs as $log)
                    @php
                        $isArray = is_array($log);
                        $logAction = $isArray ? ($log['action'] ?? 'No action') : ($log->action_label ?? $log->action ?? 'No action');
                        $logUser   = $isArray ? ($log['user'] ?? 'System') : ($log->actor?->name ?? 'System');
                        $logRole   = $isArray ? null : ($log->actor_role ?? null);
                        $logTime   = $isArray ? ($log['time'] ?? '-') : ($log->created_at?->diffForHumans() ?? '-');
                        $logBranch = $isArray ? null : ($log->branch?->branch_code ?? null);

                        // For real model entries, derive visual style from action_type
                        if ($isArray) {
                            $logIcon  = $log['icon']  ?? $actionTypeDefault['icon'];
                            $logColor = $log['color'] ?? $actionTypeDefault['color'];
                            $logBg    = $log['bg']    ?? $actionTypeDefault['bg'];
                            $logRing  = $log['ring']  ?? $actionTypeDefault['ring'];
                        } else {
                            $typeStyle = $actionTypeMap[$log->action_type ?? ''] ?? $actionTypeDefault;
                            $logIcon  = $typeStyle['icon'];
                            $logColor = $typeStyle['color'];
                            $logBg    = $typeStyle['bg'];
                            $logRing  = $typeStyle['ring'];
                        }
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
                                        By <span class="text-slate-700">{{ $logUser }}</span>
                                        @if($logRole)
                                            <span class="text-slate-400">· {{ ucfirst(str_replace('_', ' ', $logRole)) }}</span>
                                        @endif
                                        @if($logBranch)
                                            <span class="text-slate-400">· {{ $logBranch }}</span>
                                        @endif
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
                    ['label' => 'Open Service Records', 'val' => $totalCases ?? 0, 'icon' => 'bi-folder2-open', 'url' => $caseRecordsUrl],
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
