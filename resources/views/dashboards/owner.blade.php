@extends('layouts.panel')

@section('page_title', 'Owner Overview')
@section('page_desc', 'Monitor branch performance, collections, and operational summaries.')

@section('header_actions')
    <div class="hidden md:flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-bold uppercase tracking-widest">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            Live Data
        </span>
    </div>
@endsection

@section('content')
<div class="eb-shell">

@php
    $baseQuery = request()->query();
    unset($baseQuery['range'], $baseQuery['date_from'], $baseQuery['date_to']);
    $isCustomRange = ($range ?? 'THIS_MONTH') === 'CUSTOM';
    $formattedFrom = \Carbon\Carbon::parse($dateFrom)->format('M d, Y');
    $formattedTo   = \Carbon\Carbon::parse($dateTo)->format('M d, Y');
    $dateRangeLinks = [
        'TODAY' => route('owner.dashboard', array_merge($baseQuery, ['range' => 'TODAY'])),
        'LAST_30_DAYS' => route('owner.dashboard', array_merge($baseQuery, ['range' => 'LAST_30_DAYS'])),
        'THIS_MONTH' => route('owner.dashboard', array_merge($baseQuery, ['range' => 'THIS_MONTH'])),
        'THIS_YEAR' => route('owner.dashboard', array_merge($baseQuery, ['range' => 'THIS_YEAR'])),
    ];
    $clearCustomUrl = route('owner.dashboard', array_merge($baseQuery, ['range' => 'THIS_YEAR']));
    $filterScopeLabel = $selectedBranch
        ? ($selectedBranch->branch_code . ' - ' . $selectedBranch->branch_name)
        : 'All Branches';
    $periodChipLabel = $isCustomRange ? 'CUSTOM RANGE' : str_replace('_', ' ', strtoupper($range ?? 'THIS_MONTH'));
    $analyticsQuery = array_filter([
        'branch_id' => $branchId,
        'range' => $range,
        'date_from' => $isCustomRange ? $dateFrom : null,
        'date_to' => $isCustomRange ? $dateTo : null,
    ], fn ($value) => filled($value));
    $reportsQuery = array_merge(['report_type' => 'owner_branch_analytics'], $analyticsQuery);
@endphp

{{-- Filter Bar --}}
<div class="eb-filter-bar">
    <div class="eb-filter-left">
        <form method="GET" action="{{ route('owner.dashboard') }}" class="eb-branch-form eb-branch-form-inline">
            @if($isCustomRange)
                <input type="hidden" name="range" value="CUSTOM">
                <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" value="{{ $dateTo }}">
            @else
                <input type="hidden" name="range" value="{{ $range }}">
            @endif
            <label for="ebBranchFilter" class="eb-filter-label eb-visually-hidden">Branch Filter</label>
            <div class="eb-branch-select-wrap" title="{{ $filterScopeLabel }}">
                <i class="bi bi-building"></i>
                <select id="ebBranchFilter" name="branch_id" class="eb-branch-select" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>
                            {{ $branch->branch_code }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down eb-branch-select-chev"></i>
            </div>
        </form>

        <div class="eb-filter-group">
            <span class="eb-filter-label eb-visually-hidden">Period Filter</span>
            <div class="eb-seg" role="group" aria-label="Period Filter">
                @foreach (['TODAY', 'LAST_30_DAYS', 'THIS_MONTH', 'THIS_YEAR'] as $rangeKey)
                    <a href="{{ $dateRangeLinks[$rangeKey] }}" class="eb-seg-item {{ ($range ?? 'THIS_MONTH') === $rangeKey ? 'active' : '' }}">
                        {{ ucwords(strtolower(str_replace('_', ' ', $rangeKey))) }}
                    </a>
                @endforeach
                <div class="eb-custom-range-wrap" id="ebDateWrap">
                    <button type="button" class="eb-seg-item {{ $isCustomRange ? 'active' : '' }}" id="ebDateBtn" aria-expanded="false" aria-controls="ebDatePopover">
                        <i class="bi bi-calendar3 eb-date-btn-icon"></i>
                        <span>Custom Range</span>
                        <i class="bi bi-chevron-down eb-date-chev"></i>
                    </button>

                    <div class="eb-date-popover" id="ebDatePopover" style="display:none">
                        <form method="GET" action="{{ route('owner.dashboard') }}" id="ebCustomForm">
                            <input type="hidden" name="range" value="CUSTOM">
                            @if($branchId)<input type="hidden" name="branch_id" value="{{ $branchId }}">@endif
                            <div class="eb-pop-label">Custom Date Range</div>
                            <div class="eb-pop-fields">
                                <div class="eb-pop-field">
                                    <label class="eb-pop-field-label" for="ebDateFrom">Date From</label>
                                    <input type="date" name="date_from" id="ebDateFrom" value="{{ $dateFrom }}" class="eb-pop-input">
                                </div>
                                <div class="eb-pop-field">
                                    <label class="eb-pop-field-label" for="ebDateTo">Date To</label>
                                    <input type="date" name="date_to" id="ebDateTo" value="{{ $dateTo }}" class="eb-pop-input">
                                </div>
                            </div>
                            <div class="eb-pop-actions">
                                <button type="submit" class="eb-pop-apply">Apply</button>
                                <a href="{{ $clearCustomUrl }}" class="eb-pop-reset">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Period context strip --}}
<div class="eb-period-strip">
    <div class="eb-period-info">
        <i class="bi bi-calendar3"></i>
        <span>{{ $formattedFrom }} &mdash; {{ $formattedTo }}</span>
    </div>
    <div class="eb-period-info">
        <i class="bi bi-building"></i>
        <span>{{ $filterScopeLabel }}</span>
    </div>
    <div class="eb-period-info eb-period-info-muted">
        <i class="bi bi-funnel"></i>
        <span>{{ $periodChipLabel }}</span>
    </div>
</div>

{{-- Financial KPIs --}}
<div class="eb-kpi-row">

    {{-- Hero: Total Service Amount --}}
    <div class="eb-kpi-hero">
        <div class="eb-kpi-hero-label">
            <i class="bi bi-graph-up-arrow"></i>
            Total Service Amount
        </div>
        <div class="eb-kpi-hero-value">&#8369; {{ number_format((float) ($totalSales ?? 0), 2) }}</div>
        <div class="eb-kpi-hero-sub">Fully paid cases in period</div>
    </div>

    {{-- Secondary KPIs --}}
    <div class="eb-kpi-secondary">
        <div class="eb-kpi-card">
            <div class="eb-kpi-card-top">
                <span class="eb-kpi-card-label">Total Collected</span>
                <span class="eb-kpi-card-icon eb-icon-green"><i class="bi bi-cash-stack"></i></span>
            </div>
            <div class="eb-kpi-card-value eb-val-green">&#8369; {{ number_format((float) ($totalCollected ?? 0), 2) }}</div>
            <div class="eb-kpi-card-sub">Total payments received</div>
        </div>

        <div class="eb-kpi-card">
            <div class="eb-kpi-card-top">
                <span class="eb-kpi-card-label">Outstanding Balance</span>
                <span class="eb-kpi-card-icon eb-icon-red"><i class="bi bi-exclamation-circle"></i></span>
            </div>
            <div class="eb-kpi-card-value eb-val-red">&#8369; {{ number_format((float) ($totalOutstanding ?? 0), 2) }}</div>
            <div class="eb-kpi-card-sub">Remaining unpaid amounts</div>
        </div>
    </div>

</div>

{{-- Case Operation Status --}}
<section class="eb-section">
    <div class="eb-section-header">
        <h3 class="eb-section-title">Case Operation Status</h3>
    </div>
    <div class="eb-ops-grid">
        @php
            $ops = [
                ['label' => 'Total Cases',       'val' => $totalCases   ?? 0, 'icon' => 'bi-folder2-open',   'cls' => 'eb-op-default'],
                ['label' => 'Ongoing Cases',     'val' => $ongoingCases ?? 0, 'icon' => 'bi-activity',        'cls' => 'eb-op-slate'],
                ['label' => 'Fully Paid',        'val' => $paidCases    ?? 0, 'icon' => 'bi-check-circle',    'cls' => 'eb-op-green'],
                ['label' => 'Partial Payments',  'val' => $partialCases ?? 0, 'icon' => 'bi-hourglass-split', 'cls' => 'eb-op-amber'],
                ['label' => 'Unpaid Accounts',   'val' => $unpaidCases  ?? 0, 'icon' => 'bi-exclamation-triangle', 'cls' => 'eb-op-red'],
            ];
        @endphp
        @foreach($ops as $op)
            <div class="eb-op-card {{ $op['cls'] }}">
                <div class="eb-op-card-icon"><i class="bi {{ $op['icon'] }}"></i></div>
                <div class="eb-op-card-val">{{ number_format($op['val']) }}</div>
                <div class="eb-op-card-label">{{ $op['label'] }}</div>
            </div>
        @endforeach
    </div>
</section>

{{-- Bottom Grid: Branch Performance + Reports --}}
<div class="eb-bottom-grid">

    {{-- Branch Performance Matrix --}}
    <section class="eb-section eb-card eb-branch-performance-card">
        <div class="eb-section-header">
            <div>
                <h3 class="eb-section-title">Branch Performance</h3>
                <p class="eb-section-sub">Collections and payment status by branch</p>
            </div>
            <a href="{{ route('owner.analytics', $analyticsQuery) }}" class="eb-link-btn">
                Analytics <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        @php
            $branchCardsCollection = collect($branchCards)
                ->sortByDesc(fn ($row) => (float) ($row['sales'] ?? 0))
                ->values();
            $maxSales = max(1, (float) ($branchCardsCollection->max('sales') ?? 1));
            $barColors = ['#3E4A3D', '#8B9A8B', '#6F8A6D', '#B87956', '#7A8076'];
        @endphp

        <div class="eb-branch-list">
            @forelse($branchCardsCollection as $i => $row)
                @php
                    $bSales = (float) ($row['sales'] ?? 0);
                    $bWidth = (int) round(($bSales / $maxSales) * 100);
                    $rank = $i + 1;
                    $barColor = $barColors[$i % count($barColors)];
                @endphp
                <div class="eb-branch-row">
                    <div class="eb-branch-row-head">
                        <div class="eb-branch-main">
                            <span class="eb-rank-badge {{ $rank === 1 ? 'is-top' : '' }}">
                                {{ $rank === 1 ? 'Top 1' : '#' . $rank }}
                            </span>
                            <span class="eb-branch-copy">
                                <span class="eb-branch-code">{{ $row['branch']?->branch_code ?? 'N/A' }}</span>
                                <span class="eb-branch-name">{{ $row['branch']?->branch_name ?? '&mdash;' }}</span>
                            </span>
                        </div>
                        <div class="eb-branch-row-stats">
                            <span class="eb-branch-stat-pill eb-bsp-green">{{ $row['paid_cases'] ?? 0 }} paid</span>
                            @if(($row['partial_cases'] ?? 0) > 0)
                                <span class="eb-branch-stat-pill eb-bsp-amber">{{ $row['partial_cases'] }} partial</span>
                            @endif
                            @if(($row['unpaid_cases'] ?? 0) > 0)
                                <span class="eb-branch-stat-pill eb-bsp-red">{{ $row['unpaid_cases'] }} unpaid</span>
                            @endif
                            <span class="eb-branch-amount">&#8369; {{ number_format($bSales, 2) }}</span>
                        </div>
                    </div>
                    <div class="eb-branch-bar-track">
                        <div class="eb-branch-bar-fill" style="width: {{ max(2, $bWidth) }}%; background-color: {{ $barColor }};"></div>
                    </div>
                </div>
            @empty
                <div class="eb-empty">
                    <i class="bi bi-bar-chart-steps eb-empty-icon"></i>
                    <p>No branch data for this period.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Right column: Reports + Top Packages --}}
    <div class="eb-right-col">

        {{-- Reports shortcuts --}}
        <section class="eb-section eb-card eb-reports-card">
            <div class="eb-section-header">
                <div>
                    <h3 class="eb-section-title">Reports & Analytics</h3>
                    <p class="eb-section-sub">Owner-level summaries and printable reports</p>
                </div>
                <a href="{{ route('reports.index', $reportsQuery) }}" class="eb-link-btn">
                    Open Reports <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="eb-report-list">
                <a href="{{ route('owner.analytics', $analyticsQuery) }}" class="eb-report-row">
                    <span class="eb-report-icon eb-report-icon-blue"><i class="bi bi-bar-chart-line"></i></span>
                    <span class="eb-report-copy">
                        <strong>Branch Analytics</strong>
                        <small>Compare branch performance and collections.</small>
                    </span>
                    <i class="bi bi-arrow-up-right eb-report-arrow"></i>
                </a>
                <a href="{{ route('reports.index', $reportsQuery) }}" class="eb-report-row">
                    <span class="eb-report-icon eb-report-icon-green"><i class="bi bi-wallet2"></i></span>
                    <span class="eb-report-copy">
                        <strong>Payment Summary Report</strong>
                        <small>Review collected and outstanding amounts.</small>
                    </span>
                    <i class="bi bi-arrow-up-right eb-report-arrow"></i>
                </a>
                <a href="{{ route('reports.index', $reportsQuery) }}" class="eb-report-row">
                    <span class="eb-report-icon eb-report-icon-amber"><i class="bi bi-exclamation-diamond"></i></span>
                    <span class="eb-report-copy">
                        <strong>Outstanding Balance Report</strong>
                        <small>Track partial and unpaid accounts by branch.</small>
                    </span>
                    <i class="bi bi-arrow-up-right eb-report-arrow"></i>
                </a>
                <a href="{{ route('reports.index', $reportsQuery) }}" class="eb-report-row">
                    <span class="eb-report-icon eb-report-icon-slate"><i class="bi bi-clipboard-data"></i></span>
                    <span class="eb-report-copy">
                        <strong>Case Summary Report</strong>
                        <small>View summarized case volume and status.</small>
                    </span>
                    <i class="bi bi-arrow-up-right eb-report-arrow"></i>
                </a>
            </div>
        </section>

        {{-- Top Packages (branch-specific only) --}}
        @if($branchId && $topPackages && $topPackages->count())
        <section class="eb-section eb-card">
            <div class="eb-section-header">
                <div>
                    <h3 class="eb-section-title">Top Packages</h3>
                    <p class="eb-section-sub">By case volume &mdash; paid cases only</p>
                </div>
            </div>
            <div class="eb-pkg-list">
                @foreach($topPackages as $pkg)
                    <div class="eb-pkg-row">
                        <div class="eb-pkg-name">{{ $pkg->service_package }}</div>
                        <div class="eb-pkg-stats">
                            <span class="eb-pkg-cases">{{ $pkg->total_cases }} {{ Str::plural('case', $pkg->total_cases) }}</span>
                            <span class="eb-pkg-amount">&#8369; {{ number_format((float) $pkg->total_sales, 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

    </div>
</div>

</div>

<style>
/* Shell */
.eb-shell {
    padding: 1.5rem var(--panel-content-inline, 1.5rem) 3rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    color: #3E4A3D;
    font-family: var(--font-body);
}

.eb-shell button,
.eb-shell input,
.eb-shell select,
.eb-shell textarea,
.eb-shell a {
    font-family: var(--font-body);
}

/* Filter Bar */
.eb-filter-bar {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 12px;
    flex-wrap: wrap;
    background: var(--card, #fff);
    border: 1px solid var(--border, #C9C5BB);
    border-radius: 12px;
    padding: 12px;
}
.eb-filter-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.eb-filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.eb-filter-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #5F685F;
}

.eb-visually-hidden {
    position: absolute !important;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Filter controls */
.eb-seg {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.eb-seg-item {
    min-height: 44px;
    padding: 0 13px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 800;
    color: #5F685F;
    text-decoration: none;
    white-space: nowrap;
    border: 1px solid #dbe3ef;
    background: #fff;
    transition: all 0.15s ease;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    width: auto;
    min-width: fit-content;
    flex: 0 0 auto;
}
.eb-seg-item:hover { border-color: #7A8076; color: #3E4A3D; }
.eb-seg-item.active {
    background: #3E4A3D;
    color: #fff;
    border-color: #3E4A3D;
    font-weight: 600;
}

/* Custom date trigger */
.eb-custom-range-wrap { position: relative; display: inline-flex; align-items: center; }
.eb-date-btn-icon { font-size: 13px; opacity: 0.7; }
.eb-date-chev { font-size: 10px; opacity: 0.5; }

/* Date popover */
.eb-date-popover {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    left: auto;
    z-index: 240;
    background: #fff;
    border: 1px solid #dbe3ef;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
    min-width: 300px;
    max-width: min(340px, calc(100vw - 3rem));
}
.eb-pop-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #5F685F;
    margin-bottom: 12px;
}
.eb-pop-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 12px;
}
.eb-pop-field { display: flex; flex-direction: column; gap: 4px; flex: 1; }
.eb-pop-field-label {
    font-size: 11px;
    font-weight: 600;
    color: #5F685F;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.eb-pop-input {
    height: 34px;
    padding: 0 12px;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    font-size: 12px;
    font-family: var(--font-body);
    color: #3E4A3D;
    background: #FAFAF7;
    width: 100%;
    transition: border-color 0.12s;
}
.eb-pop-input:focus { outline: none; border-color: #7A8076; background: #fff; }
.eb-pop-actions { display: flex; align-items: center; gap: 0.45rem; }
.eb-pop-apply {
    width: auto;
    flex: 1;
    height: 38px;
    background: #3E4A3D;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: var(--font-body);
    transition: background 0.12s;
}
.eb-pop-apply:hover { background: #1e293b; }

.eb-pop-reset {
    height: 38px;
    padding: 0 0.75rem;
    border-radius: 8px;
    border: 1px solid #dbe3ef;
    background: #fff;
    color: #5F685F;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}
.eb-pop-reset:hover { border-color: #7A8076; color: #3E4A3D; }

.eb-branch-form {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    min-width: min(100%, 340px);
}

.eb-branch-form-inline {
    min-width: 0;
    width: auto;
    gap: 0;
    flex: 0 0 auto;
}

.eb-branch-select-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    box-sizing: border-box;
    border: 1px solid #dbe3ef;
    border-radius: 12px;
    background: #fff;
    padding: 0 12px;
    min-width: 220px;
    max-width: 320px;
}

.eb-branch-select-wrap i {
    font-size: 14px;
    color: #5F685F;
}

.eb-branch-select-chev {
    font-size: 10px;
    opacity: 0.65;
    pointer-events: none;
}

.eb-branch-select {
    flex: 0 1 auto;
    min-width: 0;
    height: 100%;
    border: 0;
    background: transparent;
    font-size: 12px;
    font-weight: 700;
    color: #333333;
    outline: none;
    text-overflow: ellipsis;
    white-space: nowrap;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 0.1rem;
    font-family: var(--font-body);
}

/* Period strip */
.eb-period-strip {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}
.eb-period-info {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 11px;
    color: #333333;
    border: 1px solid #dbe3ef;
    background: #fff;
    border-radius: 999px;
    padding: 0.35rem 0.65rem;
    white-space: nowrap;
}
.eb-period-info-muted { color: #5F685F; background: #FAFAF7; }

/* KPI Row */
.eb-kpi-row {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1rem;
}
@media (max-width: 860px) { .eb-kpi-row { grid-template-columns: 1fr; } }

.eb-kpi-hero {
    background: linear-gradient(135deg, #3E4A3D 0%, #1e293b 100%);
    border-radius: 16px;
    padding: 28px 28px 24px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    position: relative;
    overflow: hidden;
    min-height: 160px;
    justify-content: flex-end;
}
.eb-kpi-hero::before {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 130px; height: 130px;
    border-radius: 50%;
    background: rgba(214, 176, 115, 0.12);
}
.eb-kpi-hero-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #d6b073;
    display: flex;
    align-items: center;
    gap: 6px;
    position: relative;
    font-family: var(--font-body);
}
.eb-kpi-hero-value {
    font-size: 2.4rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.02em;
    line-height: 1;
    position: relative;
    font-variant-numeric: tabular-nums;
}
.eb-kpi-hero-sub {
    font-size: 11px;
    color: #5F685F;
    position: relative;
}

.eb-kpi-secondary {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
@media (max-width: 500px) { .eb-kpi-secondary { grid-template-columns: 1fr; } }

.eb-kpi-card {
    background: #fff;
    border: 0.5px solid #C9C5BB;
    border-radius: 16px;
    padding: 22px 22px 18px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.eb-kpi-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.eb-kpi-card-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #5F685F;
    font-family: var(--font-body);
}
.eb-kpi-card-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
}
.eb-icon-green { background: #f0fdf4; color: #6F8A6D; }
.eb-icon-red   { background: #fff1f2; color: #9E4B3F; }

.eb-kpi-card-value {
    font-size: 1.6rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.eb-val-green { color: #6F8A6D; }
.eb-val-red   { color: #9E4B3F; }
.eb-kpi-card-sub { font-size: 12px; color: #5F685F; margin-top: 4px; font-family: var(--font-body); }

/* Ops Grid */
.eb-ops-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}
@media (max-width: 900px) { .eb-ops-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 560px) { .eb-ops-grid { grid-template-columns: repeat(2, 1fr); } }

.eb-op-card {
    background: #fff;
    border: 0.5px solid #C9C5BB;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    border-bottom: 3px solid transparent;
}
.eb-op-card-icon { font-size: 18px; }
.eb-op-card-val  { font-size: 1.8rem; font-weight: 700; line-height: 1; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.eb-op-card-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #5F685F; font-family: var(--font-body); }

.eb-op-default { border-bottom-color: #3E4A3D; }
.eb-op-default .eb-op-card-icon { color: #3E4A3D; }
.eb-op-default .eb-op-card-val  { color: #3E4A3D; }

.eb-op-slate { border-bottom-color: #5F685F; }
.eb-op-slate .eb-op-card-icon { color: #5F685F; }
.eb-op-slate .eb-op-card-val  { color: #5F685F; }

.eb-op-green { border-bottom-color: #6F8A6D; }
.eb-op-green .eb-op-card-icon { color: #6F8A6D; }
.eb-op-green .eb-op-card-val  { color: #6F8A6D; }

.eb-op-amber { border-bottom-color: #B87956; }
.eb-op-amber .eb-op-card-icon { color: #B87956; }
.eb-op-amber .eb-op-card-val  { color: #b45309; }

.eb-op-red { border-bottom-color: #9E4B3F; }
.eb-op-red .eb-op-card-icon { color: #9E4B3F; }
.eb-op-red .eb-op-card-val  { color: #9E4B3F; }

/* Bottom Grid */
.eb-bottom-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1rem;
    align-items: stretch;
}
@media (max-width: 1100px) { .eb-bottom-grid { grid-template-columns: 1fr; } }

.eb-right-col { display: flex; flex-direction: column; gap: 1rem; min-height: 0; }
.eb-branch-performance-card,
.eb-reports-card {
    min-height: 354px;
    padding: 18px;
    gap: 14px;
}
.eb-branch-performance-card {
    max-height: 354px;
    overflow: hidden;
}
@media (max-width: 1100px) {
    .eb-branch-performance-card,
    .eb-reports-card {
        min-height: auto;
        max-height: none;
    }
}

/* Section */
.eb-section { display: flex; flex-direction: column; gap: 1rem; }
.eb-section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.eb-section-title { font-size: 14px; font-weight: 700; color: #3E4A3D; font-family: var(--font-heading); letter-spacing: -0.02em; }
.eb-section-sub { font-size: 12px; color: #5F685F; margin-top: 2px; font-family: var(--font-body); }
.eb-card {
    background: #fff;
    border: 0.5px solid #C9C5BB;
    border-radius: 14px;
    padding: 20px;
}
.eb-link-btn {
    font-size: 11px;
    font-weight: 600;
    color: #5F685F;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    padding: 5px 10px;
    border: 0.5px solid #C9C5BB;
    border-radius: 7px;
    transition: all 0.12s;
}
.eb-link-btn:hover { background: #3E4A3D; color: #fff; border-color: #3E4A3D; }

.eb-report-list { display: flex; flex-direction: column; gap: 8px; flex: 1; justify-content: space-between; }
.eb-report-row {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    padding: 10px;
    min-height: 56px;
    border: 1px solid #C9C5BB;
    border-radius: 10px;
    background: #FAFAF7;
    color: inherit;
    text-decoration: none;
    transition: border-color 0.12s ease, background-color 0.12s ease, transform 0.12s ease;
}
.eb-report-row:hover {
    background: #fff;
    border-color: #C9C5BB;
    transform: translateY(-1px);
}
.eb-report-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}
.eb-report-icon-blue { background: #eff6ff; color: #2D372D; }
.eb-report-icon-green { background: #f0fdf4; color: #6F8A6D; }
.eb-report-icon-amber { background: #fffbeb; color: #b45309; }
.eb-report-icon-slate { background: #f1f5f9; color: #333333; }
.eb-report-copy { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.eb-report-copy strong { font-size: 12px; color: #3E4A3D; font-weight: 800; }
.eb-report-copy small { font-size: 11px; color: #5F685F; line-height: 1.35; }
.eb-report-arrow { color: #7A8076; font-size: 13px; }

/* Branch list */
.eb-branch-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
    justify-content: space-between;
    min-height: 0;
    overflow-y: auto;
    padding-right: 2px;
}
.eb-branch-row {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-height: 56px;
    padding: 10px;
    border: 1px solid #C9C5BB;
    border-radius: 10px;
    background: #FAFAF7;
}
.eb-branch-row-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.eb-branch-main {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}
.eb-rank-badge {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: #f1f5f9;
    border: 1px solid #C9C5BB;
    color: #5F685F;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.02em;
}
.eb-rank-badge.is-top {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
}
.eb-branch-copy {
    min-width: 0;
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;
}
.eb-branch-code { font-size: 10px; font-weight: 700; color: var(--accent); text-transform: uppercase; letter-spacing: 0.1em; margin-right: 6px; }
.eb-branch-name { font-size: 13px; font-weight: 600; color: #3E4A3D; font-family: var(--font-body); }
.eb-branch-row-stats { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.eb-branch-stat-pill {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 999px;
}
.eb-bsp-green { background: #f0fdf4; color: #6F8A6D; border: 0.5px solid #bbf7d0; }
.eb-bsp-amber { background: #fffbeb; color: #a16207; border: 0.5px solid #fde68a; }
.eb-bsp-red   { background: #fef2f2; color: #9E4B3F; border: 0.5px solid #fecaca; }
.eb-branch-amount { font-size: 13px; font-weight: 700; color: #3E4A3D; font-variant-numeric: tabular-nums; }

.eb-branch-bar-track {
    width: 100%;
    height: 4px;
    background: #C9C5BB;
    border-radius: 999px;
    overflow: hidden;
}
.eb-branch-bar-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.6s ease;
}

/* Top Packages */
.eb-pkg-list { display: flex; flex-direction: column; gap: 8px; }
.eb-pkg-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    background: #FAFAF7;
    border-radius: 8px;
    border: 0.5px solid #C9C5BB;
}
.eb-pkg-name  { font-size: 12px; font-weight: 600; color: #3E4A3D; font-family: var(--font-body); }
.eb-pkg-stats { display: flex; align-items: center; gap: 8px; }
.eb-pkg-cases { font-size: 11px; color: #5F685F; }
.eb-pkg-amount { font-size: 12px; font-weight: 700; color: #3E4A3D; font-variant-numeric: tabular-nums; }

/* Empty state */
.eb-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 2rem; color: #7A8076; font-size: 12px; text-align: center; }
.eb-empty-icon { font-size: 2rem; }

/* Dark mode */
html[data-theme='dark'] .eb-shell { color: #e2ecf9; }
html[data-theme='dark'] .eb-seg-item { color: #8aa7c5; }
html[data-theme='dark'] .eb-seg-item:hover { color: #e2ecf9; background: rgba(255,255,255,0.06); }
html[data-theme='dark'] .eb-seg-item.active { background: #243d5a; color: #e2ecf9; box-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 0 0 0.5px #4a6888; }
html[data-theme='dark'] .eb-date-popover { background: #182638; border-color: #2e4560; box-shadow: 0 8px 24px rgba(0,0,0,0.4); }
html[data-theme='dark'] .eb-pop-input { background: #1e334f; border-color: #2e4560; color: #e2ecf9; }
html[data-theme='dark'] .eb-pop-input:focus { border-color: #5a7898; background: #243d5a; }
html[data-theme='dark'] .eb-branch-select-wrap,
html[data-theme='dark'] .eb-pop-reset,
html[data-theme='dark'] .eb-period-info {
    background: #182638;
    border-color: #2e4560;
    color: #8aa7c5;
}
html[data-theme='dark'] .eb-branch-select,
html[data-theme='dark'] .eb-branch-select-wrap i,
html[data-theme='dark'] .eb-branch-select-chev,
html[data-theme='dark'] .eb-period-info i {
    color: #8aa7c5;
}
html[data-theme='dark'] .eb-period-info { color: #5a7898; }
html[data-theme='dark'] .eb-period-info-muted { background: #1e334f; color: #8aa7c5; }
html[data-theme='dark'] .eb-kpi-hero { background: linear-gradient(135deg, #0a1628 0%, #0f1f35 100%); }
html[data-theme='dark'] .eb-kpi-card,
html[data-theme='dark'] .eb-op-card,
html[data-theme='dark'] .eb-card { background: #182638; border-color: #2e4560; }
html[data-theme='dark'] .eb-kpi-card-label,
html[data-theme='dark'] .eb-kpi-card-sub,
html[data-theme='dark'] .eb-kpi-hero-sub,
html[data-theme='dark'] .eb-op-card-label,
html[data-theme='dark'] .eb-section-sub { color: #5a7898; }
html[data-theme='dark'] .eb-kpi-card-value,
html[data-theme='dark'] .eb-section-title,
html[data-theme='dark'] .eb-op-default .eb-op-card-val,
html[data-theme='dark'] .eb-branch-name,
html[data-theme='dark'] .eb-branch-amount,
html[data-theme='dark'] .eb-pkg-name,
html[data-theme='dark'] .eb-pkg-amount { color: #e2ecf9; }
html[data-theme='dark'] .eb-op-slate .eb-op-card-val { color: #7A8076; }
html[data-theme='dark'] .eb-link-btn { background: #1e334f; border-color: #2e4560; color: #8aa7c5; }
html[data-theme='dark'] .eb-link-btn:hover { background: #e2ecf9; color: #3E4A3D; border-color: #e2ecf9; }
html[data-theme='dark'] .eb-report-row { background: #1e334f; border-color: #2e4560; }
html[data-theme='dark'] .eb-report-row:hover { background: #243d5a; border-color: #4a6888; }
html[data-theme='dark'] .eb-report-copy strong { color: #e2ecf9; }
html[data-theme='dark'] .eb-report-copy small,
html[data-theme='dark'] .eb-report-arrow { color: #8aa7c5; }
html[data-theme='dark'] .eb-report-icon-blue { background: #172554; color: #93c5fd; }
html[data-theme='dark'] .eb-report-icon-green { background: #052e16; color: #4ade80; }
html[data-theme='dark'] .eb-report-icon-amber { background: #451a03; color: #fbbf24; }
html[data-theme='dark'] .eb-report-icon-slate { background: #333333; color: #C9C5BB; }
html[data-theme='dark'] .eb-branch-row { background: #1e334f; border-color: #2e4560; }
html[data-theme='dark'] .eb-branch-bar-track { background: #2e4560; }
html[data-theme='dark'] .eb-rank-badge { background: #243d5a; border-color: #4a6888; color: #C9C5BB; }
html[data-theme='dark'] .eb-rank-badge.is-top { background: #451a03; border-color: #92400e; color: #fbbf24; }
html[data-theme='dark'] .eb-pkg-row { background: #1e334f; border-color: #2e4560; }
html[data-theme='dark'] .eb-pkg-cases { color: #5a7898; }
html[data-theme='dark'] .eb-empty { color: #4a6888; }
html[data-theme='dark'] .eb-icon-green { background: #052e16; color: #4ade80; }
html[data-theme='dark'] .eb-icon-red   { background: #450a0a; color: #f87171; }
html[data-theme='dark'] .eb-val-green  { color: #4ade80; }
html[data-theme='dark'] .eb-val-red    { color: #f87171; }
html[data-theme='dark'] .eb-op-green .eb-op-card-val,
html[data-theme='dark'] .eb-op-green .eb-op-card-icon { color: #4ade80; }
html[data-theme='dark'] .eb-op-amber .eb-op-card-val,
html[data-theme='dark'] .eb-op-amber .eb-op-card-icon { color: #fbbf24; }
html[data-theme='dark'] .eb-op-red .eb-op-card-val,
html[data-theme='dark'] .eb-op-red .eb-op-card-icon { color: #f87171; }
html[data-theme='dark'] .eb-op-default .eb-op-card-icon { color: #e2ecf9; }
</style>

<script>
(function () {
    var btn     = document.getElementById('ebDateBtn');
    var popover = document.getElementById('ebDatePopover');
    if (!btn || !popover) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = popover.style.display !== 'none';
        popover.style.display = open ? 'none' : 'block';
    });

    document.addEventListener('click', function (e) {
        var wrap = document.getElementById('ebDateWrap');
        if (wrap && !wrap.contains(e.target)) {
            popover.style.display = 'none';
        }
    });
})();
</script>

@endsection
