@extends('layouts.panel')

@section('page_title', 'Owner Dashboard')
@section('page_desc', 'Business health, branch performance, collection risk, and oversight reports.')

@section('header_actions')
    <div class="hidden md:flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-bold uppercase tracking-widest shadow-sm">
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
        'TODAY'      => route('owner.dashboard', array_merge($baseQuery, ['range' => 'TODAY'])),
        'THIS_MONTH' => route('owner.dashboard', array_merge($baseQuery, ['range' => 'THIS_MONTH'])),
        'THIS_YEAR'  => route('owner.dashboard', array_merge($baseQuery, ['range' => 'THIS_YEAR'])),
        'CUSTOM'     => '#',
    ];
    $clearCustomUrl = route('owner.dashboard', array_merge($baseQuery, ['range' => 'THIS_YEAR']));
    $filterScopeLabel = $selectedBranch
        ? ($selectedBranch->branch_code . ' — ' . $selectedBranch->branch_name)
        : 'All Branches';
    $periodChipLabel = $isCustomRange
        ? 'Custom Range'
        : collect(['TODAY' => 'Today', 'THIS_MONTH' => 'This Month', 'THIS_YEAR' => 'This Year'])
            ->get($range ?? 'THIS_MONTH', str_replace('_', ' ', $range ?? 'THIS_MONTH'));
    $analyticsQuery = array_filter([
        'branch_id' => $branchId,
        'range'     => $range,
        'date_from' => $isCustomRange ? $dateFrom : null,
        'date_to'   => $isCustomRange ? $dateTo : null,
    ], fn ($v) => filled($v));
    $historyQuery = array_filter([
        'branch_id'  => $branchId,
        'date_preset'=> $isCustomRange ? 'CUSTOM' : ($range ?? 'THIS_MONTH'),
        'date_from'  => $isCustomRange ? $dateFrom : null,
        'date_to'    => $isCustomRange ? $dateTo : null,
    ], fn ($v) => filled($v));
    $reportsQuery = array_merge(['report_type' => 'owner_branch_analytics'], $analyticsQuery);

    // Needs Attention: branch with highest outstanding balance
    $branchCardsCollection = collect($branchCards)->sortByDesc(fn ($r) => (float) ($r['outstanding'] ?? 0));
    $attentionBranch = $branchCardsCollection->firstWhere('outstanding', '>', 0);
    $branchByRevenue  = collect($branchCards)->sortByDesc(fn ($r) => (float) ($r['sales'] ?? 0))->values();
    $maxSales = max(1, (float) ($branchByRevenue->max('sales') ?? 1));
    $barColors = ['#3E4A3D', '#8B9A8B', '#6F8A6D', '#B87956', '#7A8076'];
@endphp

{{--
    ═══════════════════════════════════════════════════════════════════════
    OWNER DASHBOARD — premium executive-ledger redesign.

      [ Filter Bar: Branch ▼  Period ▼ ]
      [ Business Overview: Service Value | Collected | Outstanding | Total Cases ]
      [ Revenue Trend (large chart) ]
      [ Collection Status | Branch Performance ]
      [ Top Service Packages | Needs Attention ]
      [ Quick Access: Branch Analytics | Master Records | Payment Monitoring | Reports ]

    ASSUMPTIONS / NEW CONTROLLER DATA NEEDED (unchanged from prior revision)
    ─────────────────────────────────────────────────────────────────────
    1. $revenueTrend — optional variable for the Revenue Trend chart. Expected shape:
         collect([
             ['label' => 'Jan', 'value' => 125000.00],
             ['label' => 'Feb', 'value' => 98000.00],
             ...
         ])
       If it isn't set yet, the section renders a clean empty state instead
       of breaking — see $revenueTrend ?? collect() below.

    2. $topPackages — shown for both all-branch and single-branch scope.
       If your controller currently only computes $topPackages for a
       branch-scoped query, also compute an "all branches" aggregate
       version when $branchId is empty.

    Everything else reuses the exact variable names from the original view
    ($totalSales, $totalCollected, $totalOutstanding, $totalCases,
    $paidCases, $partialCases, $unpaidCases, $branchByRevenue, $maxSales,
    $barColors, $attentionBranch, $historyQuery, $analyticsQuery,
    $reportsQuery, $branches, $branchId, $range, $dateRangeLinks, etc.)
    so no controller changes are required beyond #1 and #2 above.
    This pass only touches markup/CSS — no Blade variables, routes,
    filters, or JavaScript behavior were changed.
    ═══════════════════════════════════════════════════════════════════════
--}}

{{-- ═══════════════════════════════════════════════════════════
     FILTER BAR
════════════════════════════════════════════════════════════ --}}
<div class="eb-filter-bar">
    <div class="eb-filter-left">

        {{-- Branch selector --}}
        <form method="GET" action="{{ route('owner.dashboard') }}" class="eb-branch-form-inline">
            @if($isCustomRange)
                <input type="hidden" name="range"     value="CUSTOM">
                <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to"   value="{{ $dateTo }}">
            @else
                <input type="hidden" name="range" value="{{ $range }}">
            @endif
            <div class="eb-branch-select-wrap" title="{{ $filterScopeLabel }}">
                <i class="bi bi-building"></i>
                <select name="branch_id" class="eb-branch-select" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>
                            {{ $branch->branch_code }} — {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down eb-chev"></i>
            </div>
        </form>

        {{-- Period selector --}}
        <div class="eb-seg" role="group" aria-label="Period filter">
            <a href="{{ $dateRangeLinks['TODAY'] }}"
               class="eb-seg-item {{ ($range ?? 'THIS_MONTH') === 'TODAY' ? 'active' : '' }}">Today</a>
            <a href="{{ $dateRangeLinks['THIS_MONTH'] }}"
               class="eb-seg-item {{ ($range ?? 'THIS_MONTH') === 'THIS_MONTH' ? 'active' : '' }}">This Month</a>
            <a href="{{ $dateRangeLinks['THIS_YEAR'] }}"
               class="eb-seg-item {{ ($range ?? 'THIS_MONTH') === 'THIS_YEAR' ? 'active' : '' }}">This Year</a>

            {{-- Custom range popover --}}
            <div class="eb-custom-wrap" id="ebDateWrap">
                <button type="button"
                        class="eb-seg-item {{ $isCustomRange ? 'active' : '' }}"
                        id="ebDateBtn"
                        aria-expanded="false"
                        aria-controls="ebDatePopover">
                    <i class="bi bi-calendar3" style="font-size:12px;opacity:.7;"></i>
                    <span>Custom</span>
                    <i class="bi bi-chevron-down" style="font-size:10px;opacity:.5;"></i>
                </button>
                <div class="eb-date-popover" id="ebDatePopover" style="display:none">
                    <form method="GET" action="{{ route('owner.dashboard') }}">
                        <input type="hidden" name="range" value="CUSTOM">
                        @if($branchId)<input type="hidden" name="branch_id" value="{{ $branchId }}">@endif
                        <div class="eb-pop-label">Custom Date Range</div>
                        <div class="eb-pop-fields">
                            <div class="eb-pop-field">
                                <label class="eb-pop-field-label" for="ebDateFrom">From</label>
                                <input type="date" name="date_from" id="ebDateFrom" value="{{ $dateFrom }}" class="eb-pop-input">
                            </div>
                            <div class="eb-pop-field">
                                <label class="eb-pop-field-label" for="ebDateTo">To</label>
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

    {{-- Active scope chips --}}
    <div class="eb-filter-chips">
        <span class="eb-chip eb-chip-period">
            <i class="bi bi-calendar3"></i>
            {{ $formattedFrom }} &mdash; {{ $formattedTo }}
        </span>
        <span class="eb-chip">
            <i class="bi bi-building"></i>
            {{ $filterScopeLabel }}
        </span>
        <span class="eb-chip eb-chip-muted">
            <i class="bi bi-funnel"></i>
            {{ $periodChipLabel }}
        </span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     1. BUSINESS OVERVIEW  (Service Value · Collected · Outstanding · Total Cases)
════════════════════════════════════════════════════════════ --}}
<section class="eb-section" aria-labelledby="sectionBusinessOverview">
    <div class="eb-section-header">
        <div>
            <h2 class="eb-section-title" id="sectionBusinessOverview">Business Overview</h2>
            <p class="eb-section-sub">Financial snapshot for the selected period and branch scope.</p>
        </div>
    </div>

    <div class="eb-overview-grid">

        {{-- Service Value --}}
        <div class="eb-overview-card eb-overview-hero">
            <i class="bi bi-graph-up-arrow eb-overview-watermark"></i>
            <div class="eb-overview-top">
                <span class="eb-overview-label">Service Value</span>
                <span class="eb-overview-icon eb-icon-gold"><i class="bi bi-graph-up-arrow"></i></span>
            </div>
            <div class="eb-overview-value">&#8369;{{ number_format((float) ($totalSales ?? 0), 2) }}</div>
            <div class="eb-overview-sub">Gross amount for all cases in period</div>
        </div>

        {{-- Collected --}}
        <a href="{{ route('owner.history', array_merge($historyQuery, ['payment_status' => 'PAID'])) }}"
           class="eb-overview-card eb-accent-green" title="View fully paid cases">
            <div class="eb-overview-top">
                <span class="eb-overview-label">Collected</span>
                <span class="eb-overview-icon eb-icon-green"><i class="bi bi-cash-stack"></i></span>
            </div>
            <div class="eb-overview-value eb-val-green">&#8369;{{ number_format((float) ($totalCollected ?? 0), 2) }}</div>
            <div class="eb-overview-sub">{{ number_format($paidCases ?? 0) }} fully paid</div>
        </a>

        {{-- Outstanding --}}
        <a href="{{ route('owner.history', $historyQuery) }}"
           class="eb-overview-card eb-accent-red" title="View outstanding cases">
            <div class="eb-overview-top">
                <span class="eb-overview-label">Outstanding</span>
                <span class="eb-overview-icon eb-icon-red"><i class="bi bi-exclamation-circle"></i></span>
            </div>
            <div class="eb-overview-value eb-val-red">&#8369;{{ number_format((float) ($totalOutstanding ?? 0), 2) }}</div>
            <div class="eb-overview-sub">Remaining unpaid balance</div>
        </a>

        {{-- Total Cases --}}
        <a href="{{ route('owner.history', $historyQuery) }}"
           class="eb-overview-card eb-accent-slate" title="View all cases">
            <div class="eb-overview-top">
                <span class="eb-overview-label">Total Cases</span>
                <span class="eb-overview-icon eb-icon-slate"><i class="bi bi-folder2-open"></i></span>
            </div>
            <div class="eb-overview-value">{{ number_format($totalCases ?? 0) }}</div>
            <div class="eb-overview-sub">All cases in period</div>
        </a>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     2. REVENUE TREND
════════════════════════════════════════════════════════════ --}}
@php
    $revenueTrendPoints = collect($revenueTrend ?? []);
    $trendMax = $revenueTrendPoints->max('value') ?: 1;
    $trendCount = max($revenueTrendPoints->count(), 1);
    $trendChartW = 900;
    $trendChartH = 240;
    $trendPad = 28;
    $trendStepX = $trendCount > 1 ? ($trendChartW - $trendPad * 2) / ($trendCount - 1) : 0;

    $trendPolyPoints = $revenueTrendPoints->values()->map(function ($pt, $i) use ($trendMax, $trendChartH, $trendPad, $trendStepX) {
        $x = $trendPad + $i * $trendStepX;
        $y = $trendChartH - $trendPad - ((float) ($pt['value'] ?? 0) / $trendMax) * ($trendChartH - $trendPad * 2);
        return round($x, 1) . ',' . round($y, 1);
    })->implode(' ');

    $trendAreaPoints = $revenueTrendPoints->isNotEmpty()
        ? ($trendPad . ',' . ($trendChartH - $trendPad) . ' ' . $trendPolyPoints . ' ' . round($trendPad + ($trendCount - 1) * $trendStepX, 1) . ',' . ($trendChartH - $trendPad))
        : '';

    // Decorative ledger gridlines — presentation only, derived from existing chart geometry.
    $trendGridLines = collect(range(0, 3))->map(fn ($gi) => round($trendPad + ($gi / 3) * ($trendChartH - $trendPad * 2), 1));

    // Presentation-only period delta, derived entirely from the already-supplied $revenueTrend series.
    $trendFirstVal = (float) ($revenueTrendPoints->first()['value'] ?? 0);
    $trendLastVal  = (float) ($revenueTrendPoints->last()['value'] ?? 0);
    $trendDeltaPct = ($revenueTrendPoints->count() >= 2 && $trendFirstVal != 0.0)
        ? round((($trendLastVal - $trendFirstVal) / abs($trendFirstVal)) * 100, 1)
        : null;
@endphp
<section class="eb-section" aria-labelledby="sectionRevenueTrend">
    <div class="eb-section-header">
        <div>
            <h2 class="eb-section-title" id="sectionRevenueTrend">Revenue Trend</h2>
            <p class="eb-section-sub">Service value over the selected period.</p>
        </div>
        @if(!is_null($trendDeltaPct))
            <span class="eb-trend-delta {{ $trendDeltaPct >= 0 ? 'is-up' : 'is-down' }}">
                <i class="bi {{ $trendDeltaPct >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                {{ $trendDeltaPct >= 0 ? '+' : '' }}{{ $trendDeltaPct }}% vs. period start
            </span>
        @endif
    </div>

    <div class="eb-card eb-trend-card">
        @if($revenueTrendPoints->isNotEmpty())
            <div class="eb-trend-scale">
                <span>&#8369;{{ number_format($trendMax, 0) }}</span>
                <span>&#8369;0</span>
            </div>
            <svg class="eb-trend-svg" viewBox="0 0 {{ $trendChartW }} {{ $trendChartH }}" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="ebTrendFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" class="eb-trend-stop-start"></stop>
                        <stop offset="100%" class="eb-trend-stop-end"></stop>
                    </linearGradient>
                </defs>
                @foreach($trendGridLines as $gy)
                    <line x1="{{ $trendPad }}" y1="{{ $gy }}" x2="{{ $trendChartW - $trendPad }}" y2="{{ $gy }}" class="eb-trend-grid"></line>
                @endforeach
                <polygon points="{{ $trendAreaPoints }}" class="eb-trend-area" fill="url(#ebTrendFill)"></polygon>
                <polyline points="{{ $trendPolyPoints }}" class="eb-trend-line"></polyline>
                @foreach($revenueTrendPoints->values() as $i => $pt)
                    @php
                        $px = $trendPad + $i * $trendStepX;
                        $py = $trendChartH - $trendPad - ((float) ($pt['value'] ?? 0) / $trendMax) * ($trendChartH - $trendPad * 2);
                    @endphp
                    <circle cx="{{ round($px, 1) }}" cy="{{ round($py, 1) }}" r="4" class="eb-trend-dot">
                        <title>{{ $pt['label'] ?? '' }}: &#8369;{{ number_format((float) ($pt['value'] ?? 0), 2) }}</title>
                    </circle>
                @endforeach
            </svg>
            <div class="eb-trend-labels">
                @foreach($revenueTrendPoints as $pt)
                    <span>{{ $pt['label'] ?? '' }}</span>
                @endforeach
            </div>
        @else
            <div class="eb-empty">
                <i class="bi bi-graph-up eb-empty-icon"></i>
                <p>No revenue trend data available for this period yet.</p>
            </div>
        @endif
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     3. COLLECTION STATUS + BRANCH PERFORMANCE
════════════════════════════════════════════════════════════ --}}
@php
    $collectionTotal = max(($paidCases ?? 0) + ($partialCases ?? 0) + ($unpaidCases ?? 0), 1);
    $pctPaid    = round((($paidCases ?? 0) / $collectionTotal) * 100);
    $pctPartial = round((($partialCases ?? 0) / $collectionTotal) * 100);
    $pctUnpaid  = round((($unpaidCases ?? 0) / $collectionTotal) * 100);
@endphp
<div class="eb-bottom-grid">

    {{-- Collection Status --}}
    <section class="eb-section eb-card" aria-labelledby="sectionCollectionStatus">
        <div class="eb-section-header">
            <div>
                <h2 class="eb-section-title" id="sectionCollectionStatus">Collection Status</h2>
                <p class="eb-section-sub">Case breakdown by payment status.</p>
            </div>
            <a href="{{ route('owner.history', $historyQuery) }}" class="eb-link-btn">
                All Records <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="eb-stacked-tape" role="img" aria-label="Paid {{ $pctPaid }}%, Partial {{ $pctPartial }}%, Unpaid {{ $pctUnpaid }}%">
            <span class="eb-tape-seg eb-tape-green" style="width:{{ max(2, $pctPaid) }}%;"></span>
            <span class="eb-tape-seg eb-tape-amber" style="width:{{ max(2, $pctPartial) }}%;"></span>
            <span class="eb-tape-seg eb-tape-red" style="width:{{ max(2, $pctUnpaid) }}%;"></span>
        </div>

        <div class="eb-status-list">
            <a href="{{ route('owner.history', array_merge($historyQuery, ['payment_status' => 'PAID'])) }}" class="eb-status-row">
                <span class="eb-status-dot eb-status-dot-green"></span>
                <span class="eb-status-name">Paid</span>
                <span class="eb-status-track">
                    <span class="eb-status-fill eb-status-fill-green" style="width: {{ max(2, $pctPaid) }}%;"></span>
                </span>
                <span class="eb-status-count">{{ number_format($paidCases ?? 0) }}</span>
                <span class="eb-status-pct">{{ $pctPaid }}%</span>
            </a>
            <a href="{{ route('owner.history', array_merge($historyQuery, ['payment_status' => 'PARTIAL'])) }}" class="eb-status-row">
                <span class="eb-status-dot eb-status-dot-amber"></span>
                <span class="eb-status-name">Partial</span>
                <span class="eb-status-track">
                    <span class="eb-status-fill eb-status-fill-amber" style="width: {{ max(2, $pctPartial) }}%;"></span>
                </span>
                <span class="eb-status-count">{{ number_format($partialCases ?? 0) }}</span>
                <span class="eb-status-pct">{{ $pctPartial }}%</span>
            </a>
            <a href="{{ route('owner.history', array_merge($historyQuery, ['payment_status' => 'UNPAID'])) }}" class="eb-status-row">
                <span class="eb-status-dot eb-status-dot-red"></span>
                <span class="eb-status-name">Unpaid</span>
                <span class="eb-status-track">
                    <span class="eb-status-fill eb-status-fill-red" style="width: {{ max(2, $pctUnpaid) }}%;"></span>
                </span>
                <span class="eb-status-count">{{ number_format($unpaidCases ?? 0) }}</span>
                <span class="eb-status-pct">{{ $pctUnpaid }}%</span>
            </a>
        </div>
    </section>

    {{-- Branch Performance --}}
    <section class="eb-section eb-card eb-branch-card" aria-labelledby="sectionBranchPerf">
        <div class="eb-section-header">
            <div>
                <h2 class="eb-section-title" id="sectionBranchPerf">Branch Performance</h2>
                <p class="eb-section-sub">Collections and payment status by branch</p>
            </div>
            <a href="{{ route('owner.analytics', $analyticsQuery) }}" class="eb-link-btn">
                Full Analytics <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="eb-branch-list">
            @forelse($branchByRevenue as $i => $row)
                @php
                    $bSales = (float) ($row['sales'] ?? 0);
                    $bWidth = (int) round(($bSales / $maxSales) * 100);
                    $rank   = $i + 1;
                    $barColor = $barColors[$i % count($barColors)];
                @endphp
                <div class="eb-branch-row">
                    <div class="eb-branch-row-head">
                        <div class="eb-branch-main">
                            <span class="eb-rank-badge {{ $rank === 1 ? 'is-top' : '' }}">
                                @if($rank === 1)
                                    <i class="bi bi-trophy-fill"></i>
                                @else
                                    #{{ $rank }}
                                @endif
                            </span>
                            <span class="eb-branch-copy">
                                <span class="eb-branch-code">{{ $row['branch']?->branch_code ?? 'N/A' }}</span>
                                <span class="eb-branch-name">{{ $row['branch']?->branch_name ?? '&mdash;' }}</span>
                            </span>
                        </div>
                        <div class="eb-branch-row-stats">
                            <span class="eb-bsp eb-bsp-green">{{ $row['paid_cases'] ?? 0 }} paid</span>
                            @if(($row['partial_cases'] ?? 0) > 0)
                                <span class="eb-bsp eb-bsp-amber">{{ $row['partial_cases'] }} partial</span>
                            @endif
                            @if(($row['unpaid_cases'] ?? 0) > 0)
                                <span class="eb-bsp eb-bsp-red">{{ $row['unpaid_cases'] }} unpaid</span>
                            @endif
                            <span class="eb-branch-amount">&#8369;{{ number_format($bSales, 2) }}</span>
                        </div>
                    </div>
                    <div class="eb-branch-bar-track">
                        <div class="eb-branch-bar-fill" style="width:{{ max(2, $bWidth) }}%;background:{{ $barColor }};"></div>
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
</div>

{{-- ═══════════════════════════════════════════════════════════
     4. TOP SERVICE PACKAGES + NEEDS ATTENTION
════════════════════════════════════════════════════════════ --}}
<div class="eb-bottom-grid">

    {{-- Top Service Packages --}}
    <section class="eb-section eb-card" aria-labelledby="sectionTopPackages">
        <div class="eb-section-header">
            <div>
                <h2 class="eb-section-title" id="sectionTopPackages">Top Service Packages</h2>
                <p class="eb-section-sub">By case volume &mdash; paid cases only</p>
            </div>
        </div>

        <div class="eb-pkg-list">
            @forelse(($topPackages ?? collect()) as $pkg)
                <div class="eb-pkg-row">
                    <span class="eb-pkg-rank">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <div class="eb-pkg-name">{{ $pkg->service_package }}</div>
                    <div class="eb-pkg-stats">
                        <span class="eb-pkg-cases">{{ $pkg->total_cases }} {{ Str::plural('case', $pkg->total_cases) }}</span>
                        <span class="eb-pkg-amount">&#8369;{{ number_format((float) $pkg->total_sales, 2) }}</span>
                    </div>
                </div>
            @empty
                <div class="eb-empty">
                    <i class="bi bi-box-seam eb-empty-icon"></i>
                    <p>No package data for this period.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Needs Attention --}}
    <section class="eb-section eb-card" aria-labelledby="sectionAttention">
        <div class="eb-section-header">
            <div>
                <h2 class="eb-section-title" id="sectionAttention">Needs Attention</h2>
                <p class="eb-section-sub">Branch with the highest outstanding balance</p>
            </div>
        </div>

        @if($attentionBranch)
            <div class="eb-attention-card">
                <span class="eb-attention-badge">
                    <i class="bi bi-flag-fill"></i> Highest Outstanding
                </span>
                <div class="eb-attention-branch">
                    <span class="eb-attention-code">{{ $attentionBranch['branch']?->branch_code ?? '—' }}</span>
                    <span class="eb-attention-name">{{ $attentionBranch['branch']?->branch_name ?? '—' }}</span>
                </div>
                <div class="eb-attention-amount">&#8369;{{ number_format((float) ($attentionBranch['outstanding'] ?? 0), 2) }}</div>
                <div class="eb-attention-stats">
                    @if(($attentionBranch['unpaid_cases'] ?? 0) > 0)
                        <span class="eb-bsp eb-bsp-red">{{ $attentionBranch['unpaid_cases'] }} unpaid</span>
                    @endif
                    @if(($attentionBranch['partial_cases'] ?? 0) > 0)
                        <span class="eb-bsp eb-bsp-amber">{{ $attentionBranch['partial_cases'] }} partial</span>
                    @endif
                </div>
                <a href="{{ route('owner.analytics', array_merge($analyticsQuery, ['branch_id' => $attentionBranch['branch']?->id])) }}"
                   class="eb-attention-link">View in Analytics <i class="bi bi-arrow-right"></i></a>
            </div>
        @else
            <div class="eb-empty">
                <i class="bi bi-check2-circle eb-empty-icon"></i>
                <p>No branches currently need attention.</p>
            </div>
        @endif
    </section>
</div>

{{-- ═══════════════════════════════════════════════════════════
     5. QUICK ACCESS
════════════════════════════════════════════════════════════ --}}
<section class="eb-section" aria-labelledby="sectionQuickAccess">
    <div class="eb-section-header">
        <div>
            <h2 class="eb-section-title" id="sectionQuickAccess">Quick Access</h2>
            <p class="eb-section-sub">Jump straight to analytics, records, and reports.</p>
        </div>
    </div>

    <div class="eb-quick-grid">
        <a href="{{ route('owner.analytics', $analyticsQuery) }}" class="eb-quick-item">
            <span class="eb-quick-icon eb-ri-blue"><i class="bi bi-bar-chart-line"></i></span>
            <span class="eb-quick-label">Branch Analytics</span>
            <i class="bi bi-arrow-up-right eb-quick-arrow"></i>
        </a>
        <a href="{{ route('owner.history', $historyQuery) }}" class="eb-quick-item">
            <span class="eb-quick-icon eb-ri-slate"><i class="bi bi-clipboard-data"></i></span>
            <span class="eb-quick-label">Master Records</span>
            <i class="bi bi-arrow-up-right eb-quick-arrow"></i>
        </a>
        <a href="{{ route('owner.history', $historyQuery) }}" class="eb-quick-item">
            <span class="eb-quick-icon eb-ri-amber"><i class="bi bi-exclamation-diamond"></i></span>
            <span class="eb-quick-label">Payment Monitoring</span>
            <i class="bi bi-arrow-up-right eb-quick-arrow"></i>
        </a>
        <a href="{{ route('reports.index', $reportsQuery) }}" class="eb-quick-item">
            <span class="eb-quick-icon eb-ri-green"><i class="bi bi-wallet2"></i></span>
            <span class="eb-quick-label">Reports</span>
            <i class="bi bi-arrow-up-right eb-quick-arrow"></i>
        </a>
    </div>
</section>

</div>{{-- /.eb-shell --}}

<style>
/* ══════════════════════════════════════════════════════════════
   TOKENS — light (default) + dark, scoped to .eb-shell
   A "financial ledger" system: paper surfaces, ink-sage accents,
   tabular figures. Dark mode swaps the accent family to navy/blue
   while keeping paid/partial/unpaid semantics constant.
══════════════════════════════════════════════════════════════ */
.eb-shell {
    --eb-page:        #F2EFE7;
    --eb-paper:       #FAFAF7;
    --eb-paper-2:     #F3F0E8;
    --eb-card:        #FFFFFF;
    --eb-border:      #C9C5BB;
    --eb-text:        #262B25;
    --eb-text-muted:  #5F685F;
    --eb-text-faint:  #8A9188;
    --eb-accent:      #3E4A3D;
    --eb-accent-strong: #2D372D;
    --eb-accent-soft: rgba(62,74,61,.09);
    --eb-gold:        #D6B073;
    --eb-gold-soft:   rgba(214,176,115,.16);
    --eb-green:       #6F8A6D;
    --eb-green-soft:  rgba(111,138,109,.14);
    --eb-amber:       #B87956;
    --eb-amber-soft:  rgba(184,121,86,.13);
    --eb-red:         #9E4B3F;
    --eb-red-soft:    rgba(158,75,63,.11);
    --eb-shadow-sm:   0 1px 2px rgba(38,43,37,.05);
    --eb-shadow-md:   0 10px 30px -12px rgba(38,43,37,.18);
    --eb-shadow-lg:   0 20px 48px -16px rgba(38,43,37,.22);
    --eb-hero-grad:   linear-gradient(150deg, #2D372D 0%, #1B2420 100%);

    max-width: 1560px;
    margin: 0 auto;
    padding: 1.5rem var(--panel-content-inline, 1.5rem) 3rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    color: var(--eb-text);
    font-family: var(--font-body);
}
.eb-shell *, .eb-shell button, .eb-shell input,
.eb-shell select, .eb-shell textarea, .eb-shell a {
    font-family: var(--font-body);
    box-sizing: border-box;
}

html[data-theme='dark'] .eb-shell {
    --eb-page:        #0d1826;
    --eb-paper:       #182638;
    --eb-paper-2:     #1e334f;
    --eb-card:        #182638;
    --eb-border:      #2e4560;
    --eb-text:        #e2ecf9;
    --eb-text-muted:  #8aa7c5;
    --eb-text-faint:  #5a7898;
    --eb-accent:      #93c5fd;
    --eb-accent-strong: #243d5a;
    --eb-accent-soft: rgba(147,197,253,.12);
    --eb-gold:        #fbbf24;
    --eb-gold-soft:   rgba(251,191,36,.14);
    --eb-green:       #4ade80;
    --eb-green-soft:  rgba(74,222,128,.13);
    --eb-amber:       #fbbf24;
    --eb-amber-soft:  rgba(251,191,36,.13);
    --eb-red:         #f87171;
    --eb-red-soft:    rgba(248,113,113,.13);
    --eb-shadow-sm:   0 1px 2px rgba(0,0,0,.3);
    --eb-shadow-md:   0 10px 30px -12px rgba(0,0,0,.55);
    --eb-shadow-lg:   0 20px 48px -16px rgba(0,0,0,.6);
    --eb-hero-grad:   linear-gradient(150deg, #0f1f35 0%, #0a1420 100%);
}

/* ══════════════════════════════════════════
   Filter Bar
══════════════════════════════════════════ */
.eb-filter-bar {
    position: sticky;
    top: 8px;
    z-index: 30;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    background: var(--eb-paper);
    border: 1px solid var(--eb-border);
    border-radius: 16px;
    padding: 12px 14px;
    box-shadow: var(--eb-shadow-sm);
    backdrop-filter: saturate(140%) blur(6px);
}
.eb-filter-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.eb-filter-chips {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.eb-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    color: var(--eb-accent);
    border: 1px solid var(--eb-border);
    background: var(--eb-card);
    border-radius: 999px;
    padding: 4px 10px;
    white-space: nowrap;
}
.eb-chip-period { border-color: var(--eb-accent); background: var(--eb-accent-soft); }
.eb-chip-muted  { color: var(--eb-text-muted); background: var(--eb-paper); }

/* Branch selector */
.eb-branch-select-wrap {
    display: flex;
    align-items: center;
    gap: 7px;
    height: 40px;
    border: 1px solid var(--eb-border);
    border-radius: 11px;
    background: var(--eb-card);
    padding: 0 11px;
    min-width: 200px;
    max-width: 280px;
}
.eb-branch-select-wrap i { font-size: 13px; color: var(--eb-text-muted); }
.eb-chev { font-size: 10px; opacity: .55; pointer-events: none; }
.eb-branch-select {
    flex: 1;
    min-width: 0;
    border: 0;
    background: transparent;
    font-size: 12px;
    font-weight: 700;
    color: var(--eb-text);
    outline: none;
    appearance: none;
    -webkit-appearance: none;
}

/* Period segment */
.eb-seg {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.eb-seg-item {
    height: 40px;
    padding: 0 13px;
    border-radius: 11px;
    font-size: 12px;
    font-weight: 700;
    color: var(--eb-text-muted);
    text-decoration: none;
    border: 1px solid var(--eb-border);
    background: var(--eb-card);
    transition: all .15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    white-space: nowrap;
}
.eb-seg-item:hover  { border-color: var(--eb-accent); color: var(--eb-accent); }
.eb-seg-item.active { background: var(--eb-accent); color: #fff; border-color: var(--eb-accent); }
html[data-theme='dark'] .eb-seg-item.active { color: #0d1826; }

/* Custom range popover */
.eb-custom-wrap { position: relative; display: inline-flex; }
.eb-date-popover {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 240;
    background: var(--eb-card);
    border: 1px solid var(--eb-border);
    border-radius: 14px;
    padding: 16px;
    box-shadow: var(--eb-shadow-lg);
    min-width: 290px;
}
.eb-pop-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--eb-text-muted);
    margin-bottom: 10px;
}
.eb-pop-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 10px;
}
.eb-pop-field { display: flex; flex-direction: column; gap: 4px; }
.eb-pop-field-label { font-size: 10.5px; font-weight: 600; color: var(--eb-text-muted); text-transform: uppercase; letter-spacing: .06em; }
.eb-pop-input {
    height: 34px; padding: 0 10px;
    border: 1px solid var(--eb-border); border-radius: 8px;
    font-size: 12px; color: var(--eb-text); background: var(--eb-paper);
    width: 100%;
}
.eb-pop-input:focus { outline: none; border-color: var(--eb-accent); }
.eb-pop-actions { display: flex; gap: 6px; }
.eb-pop-apply {
    flex: 1; height: 36px;
    background: var(--eb-accent); color: #fff;
    border: none; border-radius: 8px;
    font-size: 12px; font-weight: 600; cursor: pointer;
}
html[data-theme='dark'] .eb-pop-apply { color: #0d1826; }
.eb-pop-apply:hover { background: var(--eb-accent-strong); }
html[data-theme='dark'] .eb-pop-apply:hover { background: var(--eb-accent); opacity: .85; }
.eb-pop-reset {
    height: 36px; padding: 0 12px;
    border: 1px solid var(--eb-border); border-radius: 8px;
    background: var(--eb-card); color: var(--eb-text-muted);
    font-size: 12px; font-weight: 600;
    text-decoration: none;
    display: inline-flex; align-items: center; justify-content: center;
}
.eb-pop-reset:hover { border-color: var(--eb-accent); color: var(--eb-accent); }

/* ══════════════════════════════════════════
   Section chrome
══════════════════════════════════════════ */
.eb-section { display: flex; flex-direction: column; gap: 1rem; }
.eb-section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
}
.eb-section-title {
    font-size: 14px; font-weight: 800; color: var(--eb-accent);
    letter-spacing: -.01em; font-family: var(--font-heading);
    position: relative; padding-bottom: 9px;
}
.eb-section-title::after {
    content: ''; position: absolute; left: 0; bottom: 0;
    width: 26px; height: 3px; border-radius: 2px;
    background: var(--eb-gold);
}
.eb-section-sub   { font-size: 12px; color: var(--eb-text-muted); margin-top: 2px; }
.eb-card {
    background: var(--eb-paper);
    border: 1px solid var(--eb-border);
    border-radius: 16px;
    padding: 20px;
    box-shadow: var(--eb-shadow-sm);
}
.eb-link-btn {
    font-size: 11px; font-weight: 700;
    color: var(--eb-text-muted); text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
    white-space: nowrap; padding: 6px 11px;
    border: 1px solid var(--eb-border); border-radius: 9px;
    transition: all .12s;
    flex-shrink: 0;
}
.eb-link-btn:hover { background: var(--eb-accent); color: #fff; border-color: var(--eb-accent); }
html[data-theme='dark'] .eb-link-btn:hover { color: #0d1826; }

/* ══════════════════════════════════════════
   1. Business Overview (4-up ledger cards)
══════════════════════════════════════════ */
.eb-overview-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
@media (max-width: 1100px) { .eb-overview-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 560px)  { .eb-overview-grid { grid-template-columns: 1fr; } }

.eb-overview-card {
    position: relative;
    background: var(--eb-card);
    border: 1px solid var(--eb-border);
    border-top: 3px solid var(--eb-border);
    border-radius: 16px;
    padding: 20px 18px 18px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    text-decoration: none;
    color: inherit;
    overflow: hidden;
    transition: border-color .15s ease, transform .15s ease, box-shadow .15s ease, background .15s ease;
}
a.eb-overview-card:hover {
    border-color: var(--eb-accent);
    background: var(--eb-paper-2);
    transform: translateY(-2px);
    box-shadow: var(--eb-shadow-md);
}
.eb-accent-green { border-top-color: var(--eb-green); }
.eb-accent-red    { border-top-color: var(--eb-red); }
.eb-accent-slate  { border-top-color: var(--eb-text-muted); }

.eb-overview-hero {
    background: var(--eb-hero-grad);
    color: #fff;
    border: none;
}
.eb-overview-hero .eb-overview-label { color: rgba(255,255,255,.62); }
.eb-overview-hero .eb-overview-value { color: #fff; }
.eb-overview-hero .eb-overview-sub   { color: rgba(255,255,255,.45); }
.eb-overview-watermark {
    position: absolute; right: -10px; bottom: -14px;
    font-size: 92px; color: rgba(255,255,255,.06);
    pointer-events: none; line-height: 1;
}

.eb-overview-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; position: relative; z-index: 1; }
.eb-overview-label { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .09em; color: var(--eb-text-muted); }
.eb-overview-icon {
    width: 30px; height: 30px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
}
.eb-icon-gold  { background: var(--eb-gold-soft);  color: var(--eb-gold); }
.eb-icon-green { background: var(--eb-green-soft); color: var(--eb-green); }
.eb-icon-red   { background: var(--eb-red-soft);   color: var(--eb-red); }
.eb-icon-slate { background: var(--eb-accent-soft); color: var(--eb-text-muted); }
.eb-overview-value {
    font-size: 1.65rem; font-weight: 800; letter-spacing: -.02em; line-height: 1.1;
    font-variant-numeric: tabular-nums; position: relative; z-index: 1;
}
.eb-val-green { color: var(--eb-green); }
.eb-val-red   { color: var(--eb-red); }
.eb-overview-sub { font-size: 11px; color: var(--eb-text-muted); margin-top: 4px; position: relative; z-index: 1; }

/* ══════════════════════════════════════════
   2. Revenue Trend
══════════════════════════════════════════ */
.eb-trend-card { display: flex; flex-direction: column; gap: 10px; position: relative; }
.eb-trend-delta {
    font-size: 11px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 4px;
    padding: 5px 10px; border-radius: 999px; white-space: nowrap;
}
.eb-trend-delta.is-up   { color: var(--eb-green); background: var(--eb-green-soft); }
.eb-trend-delta.is-down { color: var(--eb-red);   background: var(--eb-red-soft); }
.eb-trend-scale {
    display: flex; justify-content: space-between;
    font-size: 10.5px; font-weight: 700; color: var(--eb-text-faint);
    font-variant-numeric: tabular-nums;
}
.eb-trend-svg { width: 100%; height: 240px; display: block; overflow: visible; }
.eb-trend-grid { stroke: var(--eb-border); stroke-width: 1; stroke-dasharray: 3 4; opacity: .7; }
.eb-trend-stop-start { stop-color: var(--eb-accent); stop-opacity: .22; }
.eb-trend-stop-end   { stop-color: var(--eb-accent); stop-opacity: 0; }
.eb-trend-area { stroke: none; }
.eb-trend-line { fill: none; stroke: var(--eb-accent); stroke-width: 2.5; vector-effect: non-scaling-stroke; stroke-linecap: round; stroke-linejoin: round; }
.eb-trend-dot  { fill: var(--eb-accent); stroke: var(--eb-paper); stroke-width: 2; }
.eb-trend-labels {
    display: flex;
    justify-content: space-between;
    font-size: 10.5px;
    font-weight: 600;
    color: var(--eb-text-muted);
    padding: 0 2px;
}

/* ══════════════════════════════════════════
   3. Collection Status + Branch Performance
══════════════════════════════════════════ */
.eb-stacked-tape {
    display: flex; width: 100%; height: 10px;
    border-radius: 999px; overflow: hidden;
    background: var(--eb-border); margin-bottom: 2px;
}
.eb-tape-seg { display: block; height: 100%; }
.eb-tape-green { background: var(--eb-green); }
.eb-tape-amber { background: var(--eb-amber); }
.eb-tape-red   { background: var(--eb-red); }

.eb-status-list { display: flex; flex-direction: column; gap: 10px; }
.eb-status-row {
    display: grid;
    grid-template-columns: 10px 64px 1fr auto 40px;
    align-items: center;
    gap: 10px;
    padding: 11px 12px;
    border: 1px solid var(--eb-border);
    border-radius: 11px;
    background: var(--eb-card);
    text-decoration: none;
    color: inherit;
    transition: border-color .12s, background .12s;
}
.eb-status-row:hover { background: var(--eb-paper-2); border-color: var(--eb-accent); }
.eb-status-dot { width: 10px; height: 10px; border-radius: 50%; }
.eb-status-dot-green { background: var(--eb-green); }
.eb-status-dot-amber { background: var(--eb-amber); }
.eb-status-dot-red   { background: var(--eb-red); }
.eb-status-name  { font-size: 12px; font-weight: 700; color: var(--eb-text); }
.eb-status-track { height: 6px; background: var(--eb-border); border-radius: 999px; overflow: hidden; }
.eb-status-fill  { display: block; height: 100%; border-radius: 999px; }
.eb-status-fill-green { background: var(--eb-green); }
.eb-status-fill-amber { background: var(--eb-amber); }
.eb-status-fill-red   { background: var(--eb-red); }
.eb-status-count { font-size: 12px; font-weight: 700; color: var(--eb-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
.eb-status-pct   { font-size: 11px; font-weight: 700; color: var(--eb-text-muted); text-align: right; font-variant-numeric: tabular-nums; }

/* Branch list (shared with Branch Performance) */
.eb-branch-card { max-height: 420px; overflow: hidden; display: flex; flex-direction: column; }
@media (max-width: 1100px) { .eb-branch-card { max-height: none; } }
.eb-branch-list {
    display: flex; flex-direction: column; gap: 8px;
    overflow-y: auto; flex: 1; padding-right: 2px;
}
.eb-branch-row {
    display: flex; flex-direction: column; gap: 8px;
    padding: 12px; border-radius: 11px;
    border: 1px solid var(--eb-border); background: var(--eb-card);
    transition: border-color .12s;
}
.eb-branch-row:hover { border-color: var(--eb-accent); }
.eb-branch-row-head {
    display: flex; align-items: center;
    justify-content: space-between; gap: 10px; flex-wrap: wrap;
}
.eb-branch-main  { display: flex; align-items: center; gap: 9px; min-width: 0; }
.eb-branch-copy  { display: flex; align-items: baseline; gap: 5px; flex-wrap: wrap; min-width: 0; }
.eb-branch-code  { font-size: 10px; font-weight: 800; color: var(--eb-text-muted); text-transform: uppercase; letter-spacing: .1em; }
.eb-branch-name  { font-size: 13px; font-weight: 600; color: var(--eb-text); }
.eb-branch-row-stats { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.eb-branch-amount { font-size: 13px; font-weight: 700; color: var(--eb-accent); font-variant-numeric: tabular-nums; }
.eb-branch-bar-track { width: 100%; height: 5px; background: var(--eb-border); border-radius: 999px; overflow: hidden; }
.eb-branch-bar-fill  { height: 100%; border-radius: 999px; transition: width .6s ease; }

.eb-rank-badge {
    flex: 0 0 auto; display: inline-flex; align-items: center;
    justify-content: center; min-width: 40px; height: 24px; padding: 0 8px;
    border-radius: 999px; background: var(--eb-accent-soft); border: 1px solid var(--eb-border);
    color: var(--eb-text-muted); font-size: 10.5px; font-weight: 800;
}
.eb-rank-badge.is-top { background: var(--eb-gold-soft); border-color: var(--eb-gold); color: var(--eb-gold); font-size: 12px; }

/* Shared status pills */
.eb-bsp {
    font-size: 10px; font-weight: 700;
    padding: 2px 8px; border-radius: 999px;
}
.eb-bsp-green { background: var(--eb-green-soft); color: var(--eb-green); border: .5px solid var(--eb-green); }
.eb-bsp-amber { background: var(--eb-amber-soft); color: var(--eb-amber); border: .5px solid var(--eb-amber); }
.eb-bsp-red   { background: var(--eb-red-soft);   color: var(--eb-red);   border: .5px solid var(--eb-red); }

/* ══════════════════════════════════════════
   Bottom grid (shared 2-col layout)
══════════════════════════════════════════ */
.eb-bottom-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    align-items: start;
}
@media (max-width: 1100px) { .eb-bottom-grid { grid-template-columns: 1fr; } }

/* ══════════════════════════════════════════
   4. Top Packages + Needs Attention
══════════════════════════════════════════ */
.eb-pkg-list { display: flex; flex-direction: column; gap: 8px; }
.eb-pkg-row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 12px;
    background: var(--eb-card); border-radius: 10px; border: 1px solid var(--eb-border);
    transition: border-color .12s;
}
.eb-pkg-row:hover { border-color: var(--eb-accent); }
.eb-pkg-rank {
    font-size: 10.5px; font-weight: 800; color: var(--eb-text-faint);
    font-variant-numeric: tabular-nums; letter-spacing: .04em; flex-shrink: 0;
}
.eb-pkg-name   { font-size: 12px; font-weight: 600; color: var(--eb-text); flex: 1; min-width: 0; }
.eb-pkg-stats  { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.eb-pkg-cases  { font-size: 11px; color: var(--eb-text-muted); white-space: nowrap; }
.eb-pkg-amount { font-size: 12px; font-weight: 700; color: var(--eb-accent); font-variant-numeric: tabular-nums; white-space: nowrap; }

.eb-attention-card {
    background: var(--eb-amber-soft);
    border: 1px solid var(--eb-amber);
    border-radius: 13px;
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.eb-attention-badge {
    align-self: flex-start;
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px;
    background: var(--eb-card); border: 1px solid var(--eb-amber);
    color: var(--eb-amber); font-size: 10.5px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .07em; white-space: nowrap;
}
.eb-attention-branch { display: flex; align-items: baseline; gap: 6px; }
.eb-attention-code   { font-size: 10px; font-weight: 800; color: var(--eb-amber); text-transform: uppercase; letter-spacing: .1em; }
.eb-attention-name   { font-size: 13px; font-weight: 700; color: var(--eb-text); }
.eb-attention-amount { font-size: 1.5rem; font-weight: 800; color: var(--eb-red); font-variant-numeric: tabular-nums; }
.eb-attention-stats  { display: flex; align-items: center; gap: 6px; }
.eb-attention-link   {
    font-size: 11px; font-weight: 700; color: var(--eb-accent); text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; opacity: .85;
}
.eb-attention-link:hover { opacity: 1; text-decoration: underline; }

/* ══════════════════════════════════════════
   5. Quick Access
══════════════════════════════════════════ */
.eb-quick-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}
@media (max-width: 900px) { .eb-quick-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 480px) { .eb-quick-grid { grid-template-columns: 1fr; } }
.eb-quick-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 15px;
    border: 1px solid var(--eb-border);
    border-radius: 13px;
    background: var(--eb-paper);
    text-decoration: none;
    color: inherit;
    overflow: hidden;
    transition: border-color .12s, background .12s, transform .12s, box-shadow .12s;
}
.eb-quick-item:hover { background: var(--eb-paper-2); border-color: var(--eb-accent); transform: translateY(-2px); box-shadow: var(--eb-shadow-md); }
.eb-quick-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}
.eb-ri-blue  { background: var(--eb-accent-soft); color: var(--eb-accent); }
.eb-ri-amber { background: var(--eb-amber-soft);  color: var(--eb-amber); }
.eb-ri-slate { background: var(--eb-accent-soft); color: var(--eb-text-muted); }
.eb-ri-green { background: var(--eb-green-soft);  color: var(--eb-green); }
.eb-quick-label { font-size: 12.5px; font-weight: 700; color: var(--eb-accent); }
.eb-quick-arrow {
    margin-left: auto; font-size: 13px; color: var(--eb-text-faint);
    opacity: 0; transform: translate(-4px, 4px);
    transition: opacity .15s, transform .15s;
}
.eb-quick-item:hover .eb-quick-arrow { opacity: 1; transform: translate(0,0); }

/* Empty state */
.eb-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 2.25rem 1rem; color: var(--eb-text-faint); font-size: 12px; text-align: center; }
.eb-empty-icon { font-size: 2rem; }

/* ══════════════════════════════════════════
   Small-screen refinements
══════════════════════════════════════════ */
@media (max-width: 640px) {
    .eb-shell { padding: 1rem .9rem 2.5rem; gap: 1.1rem; }
    .eb-filter-bar { position: static; padding: 10px; }
    .eb-overview-value { font-size: 1.4rem; }
    .eb-trend-svg { height: 190px; }
    .eb-card { padding: 16px; }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .eb-overview-card, .eb-quick-item, .eb-branch-bar-fill, .eb-quick-arrow, .eb-branch-row {
        transition: none !important;
    }
}
</style>

<script>
(function () {
    var btn     = document.getElementById('ebDateBtn');
    var popover = document.getElementById('ebDatePopover');
    var wrap    = document.getElementById('ebDateWrap');
    if (!btn || !popover) return;
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = popover.style.display !== 'none';
        popover.style.display = open ? 'none' : 'block';
        btn.setAttribute('aria-expanded', String(!open));
    });
    document.addEventListener('click', function (e) {
        if (wrap && !wrap.contains(e.target)) {
            popover.style.display = 'none';
            btn.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>

@endsection