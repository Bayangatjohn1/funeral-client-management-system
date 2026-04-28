@extends('layouts.panel')

@section('page_title', 'Sales Reports')
@section('page_desc', 'Track revenue, collections and branch performance.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    $isBranchAdmin = auth()->user()?->isBranchAdmin() ?? false;
    $selectedBranch = collect($branches)->firstWhere('id', (int) $branchId);
    $selectedBranchLabel = $selectedBranch
        ? $selectedBranch->branch_code . ' - ' . $selectedBranch->branch_name
        : 'All Branches';

    $resolvedDatePreset = $datePreset ?? ((filled($dateFrom ?? null) || filled($dateTo ?? null)) ? 'CUSTOM' : 'THIS_MONTH');
    if ($resolvedDatePreset === 'ANY' && (filled($dateFrom ?? null) || filled($dateTo ?? null))) {
        $resolvedDatePreset = 'CUSTOM';
    }

    $usesCustomCreatedDate = $resolvedDatePreset === 'CUSTOM';
    $createdPresetLabel = match ($resolvedDatePreset) {
        'TODAY' => 'Today',
        'LAST_7_DAYS' => 'Last 7 Days',
        'LAST_30_DAYS' => 'Last 30 Days',
        'THIS_MONTH' => 'This Month',
        'CUSTOM' => 'Custom Range',
        default => 'Any Time',
    };

    $createdFromLabel = filled($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('M d, Y') : 'Any';
    $createdToLabel = filled($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('M d, Y') : 'Any';
    $createdRangeLabel = $resolvedDatePreset === 'CUSTOM' ? ($createdFromLabel . ' to ' . $createdToLabel) : $createdPresetLabel;

    $totalCasesSafe = max((int) $totalCases, 1);
    $statusTotal = (int) ($paidCases + $partialCases + $unpaidCases);
    $paidPct = $statusTotal > 0 ? round(($paidCases / $statusTotal) * 100, 1) : 0;
    $partialPct = $statusTotal > 0 ? round(($partialCases / $statusTotal) * 100, 1) : 0;
    $statusGradient = $statusTotal > 0
        ? sprintf(
            'conic-gradient(#169a6d 0%% %.2f%%, #c17821 %.2f%% %.2f%%, #b42318 %.2f%% 100%%)',
            $paidPct,
            $paidPct,
            $paidPct + $partialPct,
            $paidPct + $partialPct
        )
        : 'conic-gradient(#e2e8f0 0% 100%)';

    $chartRows = $branchSales->take(4)->values();
    $chartPeak = max(1, (float) $chartRows->max('sales'), (float) $chartRows->max('collected'), (float) $chartRows->max('outstanding'));
    $printParams = array_filter([
        'report_type' => 'sales',
        'branch_id' => $branchId ?: null,
        'date_from' => $dateFrom ?: null,
        'date_to' => $dateTo ?: null,
    ], fn ($value) => filled($value));
@endphp

<style>
    .sales-ref-page {
        display:flex;
        flex-direction:column;
        gap:0;
        min-height:0;
        height:100%;
        width:100%;
        max-width:none;
        margin-inline:0;
        padding-inline:0;
        box-sizing:border-box;
    }

    .sales-page-body {
        display:flex;
        flex-direction:column;
        gap:12px;
        flex:1 1 auto;
        min-height:0;
        overflow-y:auto;
        overflow-x:hidden;
        padding-top:12px;
        padding-bottom:12px;
    }
    .sales-page-body > * {
        margin-left:18px;
        margin-right:18px;
        width:auto;
        max-width:none;
        min-width:0;
    }
    .sales-ref-page .table-system-card {
        flex:0 0 auto;
        min-height:auto;
        border-color:#e4ebf3;
        width:auto;
        min-width:0;
        box-shadow:none !important;
        outline:none !important;
    }

    .sales-ref-page .sales-page-head {
        background: #fff;
        border: 1px solid #E5E4DF;
        border-left: 0;
        border-right: 0;
        border-radius: 0;
        overflow: hidden;
        position: sticky;
        top: 0;
        z-index: 40;
        flex-shrink: 0;
    }

    /* Top Bar */
    .sales-ref-page .sales-topbar {
        background: #fff;
        border-bottom: 1px solid #E5E4DF;
        padding: 13px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .sales-ref-page .sales-topbar-leading {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .sales-ref-page .page-title {
        font-family: 'Syne', sans-serif;
        font-size: 17px;
        font-weight: 700;
        color: #111210;
    }

    .sales-ref-page .page-desc {
        font-size: 11.5px;
        color: #5A5955;
        margin-top: 1px;
    }

    .sales-ref-page .topbar-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .sales-ref-page .topbar-actions .btn {
        min-height: 34px;
        padding: 0 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .sales-ref-page .topbar-actions .btn-dark {
        background: #0f172a;
        border-color: #0f172a;
        color: #fff;
    }

    
    /* Filter Bar */
    .sales-ref-page .sales-filterbar {
        background: #fff;
        border-bottom: 1px solid #E5E4DF;
        padding: 9px 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sales-ref-page .sales-filter {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .sales-ref-page .filter-select {
        padding: 5px 10px;
        border: 1px solid #E5E4DF;
        border-radius: 7px;
        font-size: 12px;
        background: #FAFAF8;
        color: #111210;
        outline: none;
    }

    .sales-ref-page .custom-created-field[hidden] {
        display: none !important;
    }

    .sales-ref-page .filter-apply {
        min-height: 36px;
        border-radius: 9px;
        border: 1px solid #0f172a;
        background: #0f172a;
        color: #fff;
        font-size: 12px;
        font-weight: 500;
        padding: 0 16px;
    }

    .sales-ref-page .filter-apply:hover {
        background: #111c33;
        border-color: #111c33;
    }

    .sales-ref-page .filter-sep {
        width: 1px;
        height: 20px;
        background: #E5E4DF;
    }

    .sales-ref-page .filter-tag {
        padding: 4px 10px;
        background: #F2F1EB;
        border-radius: 20px;
        font-size: 11px;
        color: #5A5955;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        min-height: 28px;
    }

    .sales-ref-page .filter-tag strong { color: #111210; }

    .sales-ref-page .filter-reset {
        font-size: 11px;
        color: #9A9891;
        min-height: 30px;
        padding: 0 10px;
        border-radius: 999px;
        border: 1px solid #E5E4DF;
        background: #fff;
        display: inline-flex;
        align-items: center;
    }

    .sales-ref-page .filter-reset:hover {
        background: #F8F7F3;
        color: #7f7d76;
    }

    .sales-kpis { display:grid; grid-template-columns:repeat(7,minmax(120px,1fr)); border-top:0; }
    .sales-kpi {
        padding:14px 16px;
        border-right:1px solid #edf2f7;
        display:flex;
        flex-direction:column;
        align-items:flex-start;
        justify-content:flex-start;
        box-shadow:none !important;
        outline:none !important;
    }
    .sales-kpi:last-child { border-right:0; }
    .sales-kpi .k-label { font-size:11px; color:#8b97a8; font-weight:600; }
    .sales-kpi .k-value { margin-top:6px; font-size:26px; font-weight:700; line-height:1.05; color:#0f172a; white-space:normal; overflow-wrap:anywhere; font-family: var(--font-heading); }
    .sales-kpi .k-copy { margin-top:4px; font-size:11px; color:#8b97a8; }
    .sales-kpi .k-money { font-size:18px; letter-spacing:0; white-space:normal; overflow-wrap:anywhere; font-weight:700; font-family: var(--font-heading); }
    .sales-kpi .k-paid { color:#14986c; }
    .sales-kpi .k-partial { color:#c17821; }
    .sales-kpi .k-unpaid { color:#b42318; }

    .sales-charts { display:grid; grid-template-columns:minmax(0,1.55fr) minmax(280px,1fr); gap:12px; align-items:stretch; }
    .sales-panel {
        border:1px solid #e4ebf3;
        border-radius:14px;
        background:#fff;
        padding:14px;
        display:flex;
        flex-direction:column;
        min-height:300px;
        box-shadow:none !important;
    }
    .sales-panel h3 { margin:0; font-size:16px; font-weight:700; }
    .sales-panel p { margin:3px 0 10px; color:#8b97a8; font-size:12px; }

    .sales-bars { display:grid; grid-template-columns:repeat(4,minmax(80px,1fr)); gap:12px; min-height:190px; align-items:end; padding:8px; border-radius:10px; background:linear-gradient(180deg,#fff,#f8fafc); flex:1; }
    .sales-bars-col { display:grid; gap:8px; justify-items:center; }
    .sales-stack { display:flex; align-items:end; gap:6px; height:140px; }
    .sales-stack i { width:15px; border-radius:6px 6px 3px 3px; min-height:4px; display:block; }
    .s1 { background:#d8dde6; } .s2 { background:#169a6d; } .s3 { background:#e5b9bc; }
    .sales-bars-label { font-size:12px; color:#64748b; }
    .sales-legend { margin-top:10px; display:flex; gap:14px; flex-wrap:wrap; font-size:12px; color:#64748b; }
    .sales-legend span { display:inline-flex; align-items:center; gap:6px; }
    .sales-dot { width:10px; height:10px; border-radius:999px; }

    .sales-donut-wrap { display:grid; justify-items:center; gap:10px; }
    .sales-donut { width:156px; height:156px; border-radius:999px; background:var(--donut); position:relative; }
    .sales-donut:after { content:""; position:absolute; inset:27px; border-radius:999px; background:#fff; box-shadow:inset 0 0 0 1px #ecf1f7; }
    .sales-donut-mid { position:absolute; inset:0; z-index:1; display:grid; place-items:center; text-align:center; }
    .sales-donut-mid strong { font-size:20px; color:#0f172a; line-height:1; font-weight:700; }
    .sales-donut-mid span { font-size:12px; color:#8b97a8; }
    .sales-donut-legend { display:flex; gap:8px; flex-wrap:wrap; justify-content:center; }
    .sales-donut-legend span { border:1px solid #e2e8f0; border-radius:999px; padding:2px 8px; font-size:12px; color:#4b5563; display:inline-flex; gap:5px; align-items:center; }

    .sales-branch-head {
        display:flex;
        justify-content:space-between;
        align-items:baseline;
        gap:8px;
        flex-wrap:wrap;
        width:100%;
        padding:2px 0 2px;
    }
    .sales-branch-head h3 { margin:0; font-size:18px; font-weight:700; }
    .sales-branch-head span { color:#8b97a8; font-size:12px; font-weight:500; }

    .sales-branch {
        background:transparent !important;
        border:0 !important;
        border-radius:0 !important;
        box-shadow:none !important;
        overflow:visible;
        padding:0;
    }

    .sales-branch-item {
        margin:10px 0 0;
        border:1px solid #e4ebf3;
        border-radius:14px;
        background:#fff;
        overflow:hidden;
        box-shadow:none !important;
    }
    .sales-branch-item:last-child { margin-bottom:10px; }
    .sales-branch-item > summary {
        list-style:none;
        cursor:pointer;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        padding:13px 14px;
    }
    .sales-branch-item > summary::-webkit-details-marker { display:none; }
    .sales-branch-item[open] > summary { border-bottom:1px solid #ebf1f7; }
    .sales-branch-top {
        display:flex;
        justify-content:space-between;
        gap:12px;
        width:100%;
        align-items:center;
    }
    .sales-bname { display:inline-flex; align-items:center; gap:8px; font-family: var(--font-heading); font-weight:700; color:#0f172a; font-size:16px; }
    .sales-bdot { width:11px; height:11px; border-radius:999px; }
    .sales-branch-right {
        display:flex;
        align-items:center;
        gap:12px;
        min-width:0;
    }
    .sales-bmetrics {
        display:grid;
        grid-auto-flow:column;
        grid-auto-columns:minmax(82px,auto);
        gap:8px;
        align-items:start;
    }
    .sales-bmetrics > div {
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:2px;
        min-width:0;
        text-align:center;
    }
    .sales-bmetrics strong { display:block; font-size:14px; line-height:1.1; color:#111827; font-weight:700; font-family: var(--font-heading); white-space:nowrap; }
    .sales-bmetrics span { font-size:11px; color:#7b8797; font-weight:500; line-height:1.1; }
    .sales-pillrow { margin-top:0; display:inline-flex; gap:7px; flex-wrap:nowrap; align-items:center; }
    .sales-pill { border-radius:999px; padding:4px 11px; font-size:12px; font-weight:500; line-height:1; }
    .sales-pill.paid { background:#e8f7f0; color:#0d8a64; }
    .sales-pill.partial { background:#f7eddc; color:#a6651f; }
    .sales-pill.unpaid { background:#f8e8ea; color:#aa1f2b; }

    .sales-branch-toggle {
        color:#9aa7b6;
        font-size:11px;
        line-height:1;
        flex-shrink:0;
        margin-left:2px;
        transition: transform 0.18s ease;
    }
    .sales-branch-item[open] .sales-branch-toggle { transform: rotate(180deg); }

    .sales-bars-rows {
        padding:12px 14px 14px;
        display:grid;
        gap:8px;
        box-shadow:none !important;
    }
    .sales-row { display:grid; grid-template-columns:98px 1fr auto; gap:10px; align-items:center; }
    .sales-row span { font-size:11px; color:#8b97a8; }
    .sales-line { height:6px; border-radius:999px; background:#e8edf3; overflow:hidden; }
    .sales-line i { display:block; height:100%; border-radius:inherit; }
    .r1 i { background:#179a6f; } .r2 i { background:#5ec89a; } .r3 i { background:#9ac2ea; }

    .sales-detail details>summary { list-style:none; cursor:pointer; display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid #ebf1f7; font-weight:700; color:#0f172a; font-size:16px; }
    .sales-detail details>summary::-webkit-details-marker { display:none; }
    .sales-detail .table-wrapper {
        padding:0 10px 10px;
        overflow-x:hidden;
        border:none;
        background:transparent;
    }
    .sales-detail .table-base {
        width:100%;
        table-layout:fixed;
        border:none;
        box-shadow:none !important;
    }
    .sales-detail th {
        text-transform:uppercase;
        font-size:11.5px;
        color:#6b7f96;
        letter-spacing:.04em;
        background:#fff;
        white-space:normal;
        word-break:break-word;
        overflow-wrap:anywhere;
    }
    .sales-detail td {
        font-size:13px;
        white-space:normal;
        word-break:break-word;
        overflow-wrap:anywhere;
    }
    .sales-detail .table-col-number {
        white-space:normal;
        overflow-wrap:anywhere;
        word-break:break-word;
    }
    .sales-detail th:first-child,
    .sales-detail td:first-child {
        width:26%;
    }
    .sales-detail .total-row td { background:#f8fafc; font-weight:700; }

    .sales-branch,
    .sales-detail {
        border-color:#e4ebf3 !important;
        box-shadow:none !important;
    }

    .sales-donut:after {
        box-shadow:none !important;
        border:1px solid #ebf1f7;
    }

    @media (max-width:1400px) { .sales-kpis { grid-template-columns:repeat(4,minmax(150px,1fr)); } }
    @media (max-width:1200px) {
        .sales-charts { grid-template-columns:1fr; }
        .sales-kpis { grid-template-columns:repeat(3,minmax(140px,1fr)); }
        .sales-branch-top {
            flex-direction:column;
            align-items:flex-start;
        }
        .sales-branch-right {
            width:100%;
            flex-wrap:wrap;
            gap:10px;
        }
        .sales-bmetrics {
            grid-auto-flow:row;
            grid-template-columns:repeat(2,minmax(110px,1fr));
        }
        .sales-bmetrics > div { align-items:flex-start; text-align:left; }
        .sales-pillrow { flex-wrap:wrap; }
        .sales-detail th,
        .sales-detail td {
            font-size:12px;
            padding:9px 8px;
        }
    }
    @media (max-width:760px) {
        .sales-page-body > * {
            margin-left:12px;
            margin-right:12px;
            width:auto;
            min-width:0;
        }
        .sales-ref-page .sales-topbar,
        .sales-ref-page .sales-filterbar { padding-left: 12px; padding-right: 12px; }
        .sales-ref-page .sales-topbar {
            align-items: flex-start;
            gap: 8px;
            flex-wrap: wrap;
        }
        .sales-ref-page .sales-filter { display:grid; grid-template-columns:1fr; }
        .sales-ref-page .filter-select,
        .sales-ref-page .filter-apply { width:100%; }
        .sales-ref-page .filter-sep { display:none; }
        .sales-kpis { grid-template-columns:repeat(2,minmax(130px,1fr)); }
        .sales-kpi .k-value { font-size:22px; }
        .sales-kpi .k-money { font-size:16px; }
        .sales-ref-page .page-title { font-size:16px; }
        .sales-branch-head h3 { font-size:16px; }
        .sales-bname { font-size:15px; }
    }
</style>

<div class="admin-table-page sales-ref-page">
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}</div>
    @endif

    <section class="sales-page-head">
        <div class="sales-topbar">
            <div class="sales-topbar-leading">
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
                    <div class="page-title">Sales Reports</div>
                    <div class="page-desc">Track revenue, collections and branch performance</div>
                </div>
            </div>
            <div class="topbar-actions">
                <button type="button" class="btn btn-secondary">↓ Export CSV</button>
                <a href="{{ route('reports.print', $printParams) }}" target="_blank" rel="noopener" class="btn btn-secondary">↓ Export PDF</a>
                <a href="{{ route('reports.index') }}" class="btn btn-dark">+ Generate Report</a>
            </div>
        </div>

        <div class="sales-filterbar">
            <form method="GET" action="{{ route('admin.reports.sales') }}" class="sales-filter" data-sales-toolbar>
                @if($isBranchAdmin && $branchId)
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                @endif
                <select id="sales-branch" name="branch_id" class="filter-select" data-branch-auto-submit @if($isBranchAdmin) disabled @endif>
                    @unless($isBranchAdmin)
                        <option value="">All Branches</option>
                    @endunless
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->branch_code }} - {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
                @if($isBranchAdmin)
                    <span class="filter-tag"><strong>Assigned Branch Only</strong></span>
                @endif

                <select id="sales-created-preset" name="date_preset" class="filter-select">
                    <option value="ANY" {{ $resolvedDatePreset === 'ANY' ? 'selected' : '' }}>Any Time</option>
                    <option value="TODAY" {{ $resolvedDatePreset === 'TODAY' ? 'selected' : '' }}>Today</option>
                    <option value="LAST_7_DAYS" {{ $resolvedDatePreset === 'LAST_7_DAYS' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="LAST_30_DAYS" {{ $resolvedDatePreset === 'LAST_30_DAYS' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="THIS_MONTH" {{ $resolvedDatePreset === 'THIS_MONTH' ? 'selected' : '' }}>This Month</option>
                    <option value="CUSTOM" {{ $resolvedDatePreset === 'CUSTOM' ? 'selected' : '' }}>Custom Range</option>
                </select>

                <div class="custom-created-field" data-custom-created-field>
                    <input
                        id="sales-date-from"
                        type="date"
                        name="date_from"
                        value="{{ $dateFromInput ?? '' }}"
                        class="filter-select"
                        data-custom-created-input
                    >
                </div>

                <div class="custom-created-field" data-custom-created-field>
                    <input
                        id="sales-date-to"
                        type="date"
                        name="date_to"
                        value="{{ $dateToInput ?? '' }}"
                        class="filter-select"
                        data-custom-created-input
                    >
                </div>

                <button type="submit" class="filter-apply">Apply</button>
                <div class="filter-sep"></div>
                <div class="filter-tag">Branch: <strong>{{ $selectedBranchLabel }}</strong></div>
                <div class="filter-tag">Range: <strong>{{ $createdRangeLabel }}</strong></div>
                <a href="{{ route('admin.reports.sales') }}" class="filter-reset">✕ Reset</a>
            </form>
        </div>

    </section>

    <div class="sales-page-body">
    <section class="table-system-card">
        <div class="sales-kpis">
            <div class="sales-kpi"><div class="k-label">Total Cases</div><div class="k-value">{{ number_format($totalCases) }}</div><div class="k-copy">All statuses</div></div>
            <div class="sales-kpi"><div class="k-label">Paid</div><div class="k-value k-paid">{{ number_format($paidCases) }}</div><div class="k-copy">Fully settled</div></div>
            <div class="sales-kpi"><div class="k-label">Partial</div><div class="k-value k-partial">{{ number_format($partialCases) }}</div><div class="k-copy">In progress</div></div>
            <div class="sales-kpi"><div class="k-label">Unpaid</div><div class="k-value k-unpaid">{{ number_format($unpaidCases) }}</div><div class="k-copy">Needs action</div></div>
            <div class="sales-kpi"><div class="k-label">Total Service Amt.</div><div class="k-value k-money">PHP {{ number_format($totalSales, 0) }}</div><div class="k-copy">Gross revenue</div></div>
            <div class="sales-kpi"><div class="k-label">Collected</div><div class="k-value k-money k-paid">PHP {{ number_format($totalCollected, 0) }}</div><div class="k-copy">{{ number_format(($totalCollected / max($totalSales, 1)) * 100, 1) }}% of sales</div></div>
            <div class="sales-kpi"><div class="k-label">Outstanding</div><div class="k-value k-money k-unpaid">PHP {{ number_format($totalOutstanding, 0) }}</div><div class="k-copy">Balance due</div></div>
        </div>
    </section>

    <section class="sales-charts">
        <article class="sales-panel">
            <h3>Sales vs Collected vs Outstanding</h3>
            <p>Branch-level comparison for current filters.</p>
            <div class="sales-bars">
                @forelse($chartRows as $row)
                    @php
                        $h1 = round(($row['sales'] / $chartPeak) * 100, 1);
                        $h2 = round(($row['collected'] / $chartPeak) * 100, 1);
                        $h3 = round(($row['outstanding'] / $chartPeak) * 100, 1);
                    @endphp
                    <div class="sales-bars-col">
                        <div class="sales-stack">
                            <i class="s1" style="height: {{ $h1 }}%"></i>
                            <i class="s2" style="height: {{ $h2 }}%"></i>
                            <i class="s3" style="height: {{ $h3 }}%"></i>
                        </div>
                        <div class="sales-bars-label">{{ $row['branch']->branch_code }}</div>
                    </div>
                @empty
                    <div class="table-system-empty" style="grid-column:1/-1;">No branch data for current filters.</div>
                @endforelse
            </div>
            <div class="sales-legend">
                <span><i class="sales-dot" style="background:#d8dde6;"></i>Total Sales</span>
                <span><i class="sales-dot" style="background:#169a6d;"></i>Collected</span>
                <span><i class="sales-dot" style="background:#e5b9bc;"></i>Outstanding</span>
            </div>
        </article>

        <article class="sales-panel">
            <h3>Case Status Distribution</h3>
            <p>Paid | Partial | Unpaid</p>
            <div class="sales-donut-wrap">
                <div class="sales-donut" style="--donut: {{ $statusGradient }};">
                    <div class="sales-donut-mid"><div><strong>{{ number_format($totalCases) }}</strong><span>Total Cases</span></div></div>
                </div>
                <div class="sales-donut-legend">
                    <span><i class="sales-dot" style="background:#169a6d;"></i>Paid {{ number_format($paidCases) }}</span>
                    <span><i class="sales-dot" style="background:#c17821;"></i>Partial {{ number_format($partialCases) }}</span>
                    <span><i class="sales-dot" style="background:#b42318;"></i>Unpaid {{ number_format($unpaidCases) }}</span>
                </div>
            </div>
        </article>
    </section>

    <section class="table-system-card sales-branch">
        <div class="sales-branch-head">
            <h3>Branch Performance</h3>
            <span>{{ number_format($branchSales->count()) }} branches · {{ $createdPresetLabel }}</span>
        </div>

        @php $colors = ['#16946a', '#3f83d6', '#b87418', '#8b5cf6', '#db2777']; @endphp
        @foreach($branchSales as $index => $row)
            @php
                $collectionRate = $row['sales'] > 0 ? min(100, round(($row['collected'] / $row['sales']) * 100, 1)) : 0;
                $paidRate = $row['cases'] > 0 ? min(100, round(($row['paid_cases'] / $row['cases']) * 100, 1)) : 0;
                $branchShare = min(100, round(($row['cases'] / $totalCasesSafe) * 100, 1));
                $color = $colors[$index % count($colors)];
            @endphp

            <details class="sales-branch-item">
                <summary>
                    <div class="sales-branch-top">
                        <div class="sales-bname"><i class="sales-bdot" style="background:{{ $color }};"></i>{{ $row['branch']->branch_code }} - {{ $row['branch']->branch_name }}</div>
                        <div class="sales-branch-right">
                            <div class="sales-bmetrics">
                                <div><strong>{{ number_format($row['cases']) }}</strong><span>Cases</span></div>
                                <div><strong>PHP {{ number_format($row['sales'], 0) }}</strong><span>Sales</span></div>
                                <div><strong style="color:#169a6d;">PHP {{ number_format($row['collected'], 0) }}</strong><span>Collected</span></div>
                                <div><strong style="color:#b42318;">PHP {{ number_format($row['outstanding'], 0) }}</strong><span>Outstanding</span></div>
                            </div>
                            <div class="sales-pillrow">
                                <span class="sales-pill paid">{{ number_format($row['paid_cases']) }} Paid</span>
                                <span class="sales-pill partial">{{ number_format($row['partial_cases']) }} Partial</span>
                                <span class="sales-pill unpaid">{{ number_format($row['unpaid_cases']) }} Unpaid</span>
                            </div>
                            <i class="bi bi-chevron-down sales-branch-toggle"></i>
                        </div>
                    </div>
                </summary>

                <div class="sales-bars-rows">
                    <div class="sales-row"><span>Collection rate</span><div class="sales-line r1"><i style="width:{{ $collectionRate }}%;"></i></div><strong>{{ number_format($collectionRate, 1) }}%</strong></div>
                    <div class="sales-row"><span>Paid cases</span><div class="sales-line r2"><i style="width:{{ $paidRate }}%;"></i></div><strong>{{ number_format($paidRate, 1) }}%</strong></div>
                    <div class="sales-row"><span>Branch share</span><div class="sales-line r3"><i style="width:{{ $branchShare }}%;"></i></div><strong>{{ number_format($branchShare, 1) }}%</strong></div>
                </div>
            </details>
        @endforeach
    </section>

    <section class="table-system-card sales-detail">
        <details open>
            <summary><span>View Detailed Records</span><i class="bi bi-chevron-down"></i></summary>
            <div class="table-wrapper">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th class="text-left">Branch</th>
                            <th class="table-col-number">Total Cases</th>
                            <th class="table-col-number">Paid</th>
                            <th class="table-col-number">Partial</th>
                            <th class="table-col-number">Unpaid</th>
                            <th class="table-col-number">Sales (Paid)</th>
                            <th class="table-col-number">Collected</th>
                            <th class="table-col-number">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branchSales as $row)
                            <tr>
                                <td class="table-primary">{{ $row['branch']->branch_code }} - {{ $row['branch']->branch_name }}</td>
                                <td class="table-col-number">{{ number_format($row['cases']) }}</td>
                                <td class="table-col-number">{{ number_format($row['paid_cases']) }}</td>
                                <td class="table-col-number">{{ number_format($row['partial_cases']) }}</td>
                                <td class="table-col-number">{{ number_format($row['unpaid_cases']) }}</td>
                                <td class="table-col-number">PHP {{ number_format($row['sales'], 0) }}</td>
                                <td class="table-col-number">PHP {{ number_format($row['collected'], 0) }}</td>
                                <td class="table-col-number">PHP {{ number_format($row['outstanding'], 0) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td>Total</td>
                            <td class="table-col-number">{{ number_format($totalCases) }}</td>
                            <td class="table-col-number">{{ number_format($paidCases) }}</td>
                            <td class="table-col-number">{{ number_format($partialCases) }}</td>
                            <td class="table-col-number">{{ number_format($unpaidCases) }}</td>
                            <td class="table-col-number">PHP {{ number_format($totalSales, 0) }}</td>
                            <td class="table-col-number">PHP {{ number_format($totalCollected, 0) }}</td>
                            <td class="table-col-number">PHP {{ number_format($totalOutstanding, 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    </section>
    </div>
</div>

<script>
    (() => {
        const form = document.querySelector('form[data-sales-toolbar]');
        if (!form) return;

        const branchSelect = form.querySelector('select[data-branch-auto-submit]');
        const presetSelect = form.querySelector('select[name="date_preset"]');
        const customFields = form.querySelectorAll('[data-custom-created-field]');
        const customInputs = form.querySelectorAll('[data-custom-created-input]');

        const submitForm = () => {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }
            form.submit();
        };

        const syncCustomDateVisibility = () => {
            if (!presetSelect) return;
            const isCustom = presetSelect.value === 'CUSTOM';
            customFields.forEach((field) => {
                field.hidden = !isCustom;
            });
            customInputs.forEach((input) => {
                input.disabled = !isCustom;
            });
        };

        if (branchSelect) {
            branchSelect.addEventListener('change', () => {
                submitForm();
            });
        }

        if (presetSelect) {
            presetSelect.addEventListener('change', syncCustomDateVisibility);
        }

        // Custom date inputs intentionally do NOT auto-submit.
        // User must click Apply when preset is CUSTOM.
        syncCustomDateVisibility();
    })();
</script>

@endsection
