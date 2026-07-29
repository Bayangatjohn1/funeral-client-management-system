@extends('layouts.panel')

@section('page_title', 'Owner Dashboard')
@section('page_desc', 'Business health, branch performance, collection risk, and oversight reports.')
@section('hide_layout_topbar', '1')

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
    $clearCustomUrl = route('owner.dashboard', array_merge($baseQuery, ['range' => 'THIS_MONTH']));
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
    $topBranchBars    = $branchByRevenue->take(3)->values();
    $maxSales = max(1, (float) ($branchByRevenue->max('sales') ?? 1));
    $barColors = ['#3E4A3D', '#8B9A8B', '#6F8A6D', '#B87956', '#7A8076'];
@endphp

<span class="sr-only">Owner Overview</span>
<div class="eb-owner-toast-stack" data-owner-toast-stack aria-live="polite" aria-atomic="true"></div>

{{--
    ═══════════════════════════════════════════════════════════════════════
    OWNER DASHBOARD — premium executive-ledger redesign.

      [ Filter Bar: Branch ▼  Period ▼ ]
      [ Business Overview: Service Value | Collected | Outstanding | Total Cases ]
      [ Revenue Trend (large chart) ]
      [ Branch Performance + Collection Mix ]
      [ Top Service Packages | Needs Attention ]
      [ Quick Access: Branch Analytics | Master Records | Reports ]

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
        <form method="GET" action="{{ route('owner.dashboard') }}" class="eb-branch-form-inline" data-owner-filter-form>
            @if($isCustomRange)
                <input type="hidden" name="range"     value="CUSTOM">
                <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to"   value="{{ $dateTo }}">
            @else
                <input type="hidden" name="range" value="{{ $range }}">
            @endif
            <div class="eb-branch-select-wrap" title="{{ $filterScopeLabel }}">
                <i class="bi bi-building"></i>
                <select name="branch_id" class="eb-branch-select" data-owner-filter-control>
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
        <form method="GET" action="{{ route('owner.dashboard') }}" class="eb-period-form-inline" data-owner-filter-form>
            @if($branchId)<input type="hidden" name="branch_id" value="{{ $branchId }}">@endif
            <div class="eb-period-select-wrap" title="{{ $periodChipLabel }}">
                <i class="bi bi-funnel"></i>
                <select name="range" class="eb-period-select" data-owner-period-select data-owner-filter-control>
                    <option value="TODAY" @selected(($range ?? 'THIS_MONTH') === 'TODAY')>Today</option>
                    <option value="THIS_MONTH" @selected(($range ?? 'THIS_MONTH') === 'THIS_MONTH')>This Month</option>
                    <option value="THIS_YEAR" @selected(($range ?? 'THIS_MONTH') === 'THIS_YEAR')>This Year</option>
                    <option value="CUSTOM" @selected($isCustomRange)>Custom Range</option>
                </select>
            </div>
        </form>

        {{-- Custom range popover --}}
        <div class="eb-custom-wrap {{ $isCustomRange ? 'is-open' : '' }}" id="ebDateWrap">
            <div class="eb-date-popover" id="ebDatePopover">
                <form method="GET" action="{{ route('owner.dashboard') }}" data-owner-filter-form>
                    <input type="hidden" name="range" value="CUSTOM">
                    @if($branchId)<input type="hidden" name="branch_id" value="{{ $branchId }}">@endif
                    <button
                        type="button"
                        class="eb-pop-close"
                        aria-label="Close custom range"
                        data-owner-custom-close
                        data-default-url="{{ $clearCustomUrl }}"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
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
                        <a href="{{ $clearCustomUrl }}" class="eb-pop-reset" data-owner-filter-link>Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="eb-filter-actions" aria-label="Quick actions">
        <a href="{{ route('owner.analytics', $analyticsQuery) }}" class="eb-quick-button">
            <span class="eb-quick-icon eb-ri-blue"><i class="bi bi-bar-chart-line"></i></span>
            <span class="eb-quick-label">Branch Analytics</span>
            <i class="bi bi-arrow-up-right eb-quick-arrow"></i>
        </a>
        <a href="{{ route('owner.history', $historyQuery) }}" class="eb-quick-button">
            <span class="eb-quick-icon eb-ri-slate"><i class="bi bi-clipboard-data"></i></span>
            <span class="eb-quick-label">Master Records</span>
            <i class="bi bi-arrow-up-right eb-quick-arrow"></i>
        </a>
        <a href="{{ route('reports.index', $reportsQuery) }}" class="eb-quick-button" aria-label="Reports & Analytics">
            <span class="eb-quick-icon eb-ri-green"><i class="bi bi-wallet2"></i></span>
            <span class="eb-quick-label">Reports</span>
            <i class="bi bi-arrow-up-right eb-quick-arrow"></i>
        </a>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     1. BUSINESS OVERVIEW  (Service Value · Collected · Outstanding · Total Cases)
════════════════════════════════════════════════════════════ --}}
<section class="eb-section" aria-label="Business overview">
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
     2. BRANCH PERFORMANCE + COLLECTION MIX
════════════════════════════════════════════════════════════ --}}
<div class="eb-branch-performance-wrap">
    <section
        class="eb-section eb-card eb-branch-card"
        aria-labelledby="sectionBranchPerf"
        data-branch-performance-card
        data-default-paid="{{ (int) ($paidCases ?? 0) }}"
        data-default-partial="{{ (int) ($partialCases ?? 0) }}"
        data-default-unpaid="{{ (int) ($unpaidCases ?? 0) }}"
    >
        <div class="eb-section-header">
            <div>
                <h2 class="eb-section-title" id="sectionBranchPerf">Branch Performance</h2>
                <p class="eb-section-sub">Top 3 branches by revenue. Select a bar to view payment status.</p>
            </div>
            <div class="eb-branch-actions">
                <a href="{{ route('owner.analytics', $analyticsQuery) }}" class="eb-link-btn">Full Analytics <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>

        <div class="eb-branch-performance-grid">
            <div class="eb-branch-bar-chart" aria-label="Top 3 branch revenue bar chart">
                @forelse($topBranchBars as $i => $row)
                    @php
                        $bSales = (float) ($row['sales'] ?? 0);
                        $bWidth = (int) round(($bSales / $maxSales) * 100);
                        $rank   = $i + 1;
                        $barColor = $barColors[$i % count($barColors)];
                        $branchPaid = (int) ($row['paid_cases'] ?? 0);
                        $branchPartial = (int) ($row['partial_cases'] ?? 0);
                        $branchUnpaid = (int) ($row['unpaid_cases'] ?? 0);
                        $rankClass = $rank <= 3 ? 'is-rank-' . $rank : '';
                        $rankLabel = $rank <= 3 ? 'Top ' . $rank : '#' . $rank;
                        $branchLabel = trim(($row['branch']?->branch_code ?? 'N/A') . ' ' . ($row['branch']?->branch_name ?? ''));
                    @endphp
                    <button
                        type="button"
                        class="eb-branch-bar-item {{ $rankClass }}"
                        tabindex="0"
                        aria-pressed="false"
                        title="Show this branch collection mix"
                        data-branch-row
                        data-branch-label="{{ $branchLabel }}"
                        data-paid="{{ $branchPaid }}"
                        data-partial="{{ $branchPartial }}"
                        data-unpaid="{{ $branchUnpaid }}"
                    >
                        <span class="eb-branch-bar-meta">
                            <span class="eb-rank-badge {{ $rankClass }}">
                                @if($rank === 1)
                                    <i class="bi bi-trophy-fill"></i> Top 1
                                @else
                                    {{ $rankLabel }}
                                @endif
                            </span>
                            <span class="eb-branch-copy">
                                <span class="eb-branch-code">{{ $row['branch']?->branch_code ?? 'N/A' }}</span>
                                <span class="eb-branch-name">{{ $row['branch']?->branch_name ?? '&mdash;' }}</span>
                            </span>
                        </span>
                        <span class="eb-branch-bar-track">
                            <span
                                class="eb-branch-bar-fill"
                                style="width:{{ max(4, $bWidth) }}%;background:{{ $barColor }};"
                                aria-hidden="true"
                            ></span>
                            <span class="eb-branch-bar-value">&#8369;{{ number_format($bSales, 2) }}</span>
                        </span>
                        <div class="eb-branch-bar-caption">
                            <span>{{ number_format($row['total_cases'] ?? 0) }} {{ Str::plural('case', $row['total_cases'] ?? 0) }}</span>
                            <span>{{ $bWidth }}% of top branch revenue</span>
                        </div>
                    </button>
                @empty
                    <div class="eb-empty">
                        <i class="bi bi-bar-chart-steps eb-empty-icon"></i>
                        <p>No branch data for this period.</p>
                    </div>
                @endforelse
            </div>

            <div class="eb-branch-pie-panel">
                <div class="eb-branch-pie-head">
                    <span class="eb-branch-pie-title">Payment Status</span>
                    <span class="eb-branch-pie-branch" data-pie-branch-label>Selected branch</span>
                </div>
                <div
                    class="eb-branch-pie"
                    data-branch-pie
                    role="img"
                    aria-label="Collection status chart"
                ></div>
                <div class="eb-branch-pie-values" aria-live="polite">
                    <div class="eb-branch-pie-value">
                        <span class="eb-branch-pie-key"><span class="eb-status-dot eb-status-dot-green"></span>Paid</span>
                        <span class="eb-branch-pie-count" data-pie-paid-value>0</span>
                    </div>
                    <div class="eb-branch-pie-value">
                        <span class="eb-branch-pie-key"><span class="eb-status-dot eb-status-dot-amber"></span>Partial</span>
                        <span class="eb-branch-pie-count" data-pie-partial-value>0</span>
                    </div>
                    <div class="eb-branch-pie-value">
                        <span class="eb-branch-pie-key"><span class="eb-status-dot eb-status-dot-red"></span>Unpaid</span>
                        <span class="eb-branch-pie-count" data-pie-unpaid-value>0</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- ═══════════════════════════════════════════════════════════
     3. REVENUE TREND
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
    <div class="eb-card eb-trend-card">
        <div class="eb-trend-card-head">
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
</div>{{-- /.eb-shell --}}

<style>
/* ══════════════════════════════════════════════════════════════
   TOKENS — light (default) + dark, scoped to .eb-shell
   A "financial ledger" system: paper surfaces, ink-sage accents,
   tabular figures. Dark mode swaps the accent family to navy/blue
   while keeping paid/partial/unpaid semantics constant.
══════════════════════════════════════════════════════════════ */
.eb-shell {
    --eb-page:        #D2DACB;
    --eb-paper:       #E8EDE2;
    --eb-paper-2:     #E1E7D9;
    --eb-card:        #FBFCF7;
    --eb-border:      #C9C5BB;
    --eb-text:        #232821;
    --eb-text-muted:  #4E5A4E;
    --eb-text-faint:  #737D72;
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
    --eb-shadow-sm:   none;
    --eb-shadow-md:   none;
    --eb-shadow-lg:   none;
    --eb-hero-grad:   linear-gradient(150deg, #2D372D 0%, #1B2420 100%);

    width: 100%;
    max-width: none;
    margin: 0 auto;
    padding: .9rem clamp(.8rem, 1.2vw, 1.25rem) 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    color: var(--eb-text);
    font-family: var(--font-body);
    background:
        linear-gradient(90deg, rgba(73, 87, 69, 0.04) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73, 87, 69, 0.034) 0 1px, transparent 1px),
        repeating-linear-gradient(135deg, rgba(73, 87, 69, 0.02) 0 1px, transparent 1px 12px);
    background-size: 44px 44px, 44px 44px, 16px 16px;
}

.eb-shell *,
.eb-shell *::before,
.eb-shell *::after {
    box-shadow: none !important;
    filter: none;
}

.eb-shell a[href],
.eb-shell button,
.eb-shell select,
.eb-shell [role="button"],
.eb-seg-item,
.eb-quick-item,
.eb-quick-button,
.eb-link-btn,
.eb-overview-card,
.eb-status-row {
    cursor: pointer;
}

.eb-shell button:disabled,
.eb-shell select:disabled,
.eb-shell [aria-disabled="true"] {
    cursor: not-allowed;
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
    --eb-shadow-sm:   none;
    --eb-shadow-md:   none;
    --eb-shadow-lg:   none;
    --eb-hero-grad:   linear-gradient(150deg, #0f1f35 0%, #0a1420 100%);
}

html[data-theme='dark'] .eb-shell {
    background:
        linear-gradient(90deg, rgba(148, 163, 184, 0.04) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(148, 163, 184, 0.035) 0 1px, transparent 1px),
        repeating-linear-gradient(135deg, rgba(148, 163, 184, 0.026) 0 1px, transparent 1px 12px);
    background-size: 44px 44px, 44px 44px, 16px 16px;
}

.eb-live-chip {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    min-height: 2rem;
    padding: .38rem .65rem;
    border: 1px solid var(--eb-border);
    border-radius: 8px;
    background: var(--eb-card);
    color: var(--eb-text-muted);
    font-size: .78rem;
    font-weight: 800;
}

.eb-live-chip span {
    width: .45rem;
    height: .45rem;
    border-radius: 50%;
    background: var(--eb-green);
}

.eb-owner-toast-stack {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 260;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: .6rem;
    pointer-events: none;
}

.eb-owner-toast {
    width: min(360px, calc(100vw - 2rem));
    display: grid;
    grid-template-columns: 2rem minmax(0, 1fr) auto;
    align-items: center;
    gap: .7rem;
    padding: .75rem .85rem;
    border: 1px solid rgba(255, 255, 255, .18);
    border-radius: 8px;
    background: #2F3B30;
    color: #FFFFFF;
    opacity: 0;
    transform: translateY(-8px);
    transition: opacity .2s ease, transform .2s ease;
    pointer-events: auto;
}

.eb-owner-toast.is-visible {
    opacity: 1;
    transform: translateY(0);
}

.eb-owner-toast.is-leaving {
    opacity: 0;
    transform: translateY(-6px);
}

.eb-owner-toast__icon {
    width: 2rem;
    height: 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, .28);
    border-radius: 8px;
    background: rgba(255, 255, 255, .14);
    color: #FFFFFF;
}

.eb-owner-toast__title {
    display: block;
    color: #FFFFFF;
    font-size: .88rem;
    font-weight: 900;
}

.eb-owner-toast__body {
    display: block;
    margin-top: .1rem;
    color: rgba(255, 255, 255, .86);
    font-size: .78rem;
    font-weight: 800;
}

.eb-owner-toast__close {
    width: 1.8rem;
    height: 1.8rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, .24);
    border-radius: 8px;
    background: rgba(255, 255, 255, .12);
    color: #FFFFFF;
    cursor: pointer;
}

.eb-owner-toast__close:hover {
    color: #FFFFFF;
    background: rgba(255, 255, 255, .2);
    border-color: rgba(255, 255, 255, .4);
}

@media (max-width: 640px) {
    .eb-owner-toast-stack {
        right: .75rem;
        left: .75rem;
        align-items: stretch;
    }

    .eb-owner-toast {
        width: 100%;
    }
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
    background:
        linear-gradient(180deg, rgba(250, 250, 247, .92), rgba(243, 240, 232, .92)),
        var(--eb-paper);
    border: 1px solid var(--eb-border);
    border-radius: 16px;
    padding: 12px 14px;
    box-shadow: none;
    backdrop-filter: none;
    overflow: visible;
}
.eb-filter-left {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    flex: 1 1 auto;
}
.eb-filter-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
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
    cursor: pointer;
}
.eb-period-select-wrap {
    display: flex;
    align-items: center;
    gap: 7px;
    height: 40px;
    border: 1px solid var(--eb-border);
    border-radius: 11px;
    background: var(--eb-card);
    padding: 0 11px;
    min-width: 170px;
    cursor: pointer;
}
.eb-branch-select-wrap:hover,
.eb-branch-select-wrap:focus-within,
.eb-period-select-wrap:hover,
.eb-period-select-wrap:focus-within {
    border-color: var(--eb-accent);
    background: var(--eb-accent-soft);
}
.eb-branch-select-wrap i,
.eb-period-select-wrap i { font-size: 13px; color: var(--eb-text-muted); }
.eb-chev { font-size: 10px; opacity: .55; pointer-events: none; }
.eb-branch-select,
.eb-period-select {
    flex: 1;
    min-width: 0;
    border: 0;
    background: transparent;
    font-size: 12px;
    font-weight: 700;
    color: var(--eb-text);
    outline: none;
    cursor: pointer;
}

.eb-branch-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.eb-branch-select::-ms-expand {
    display: none;
}

.eb-period-form-inline {
    display: inline-flex;
}

.eb-filter-bar.is-loading {
    border-color: var(--eb-accent);
    background:
        linear-gradient(180deg, rgba(232, 238, 229, .96), rgba(243, 240, 232, .94)),
        var(--eb-paper);
    pointer-events: none;
}

.eb-filter-bar.is-loading::after {
    content: "Applying filters...";
    position: absolute;
    right: 14px;
    bottom: -34px;
    z-index: 330;
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    padding: 0 12px;
    border: 1px solid var(--eb-accent);
    border-radius: 999px;
    background: var(--eb-accent);
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .03em;
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

/* Custom range panel */
.eb-custom-wrap {
    display: none;
}
.eb-custom-wrap.is-open {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    z-index: 320;
    display: block;
}
.eb-date-popover {
    position: relative;
    background: var(--eb-card);
    border: 1px solid var(--eb-border);
    border-radius: 8px;
    padding: 10px 42px 10px 10px;
    box-shadow: none;
    width: min(560px, calc(100vw - 2rem));
    min-width: 0;
}
.eb-date-popover form {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: end;
    gap: 8px;
}
.eb-pop-close {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 26px;
    height: 26px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--eb-border);
    border-radius: 8px;
    background: var(--eb-card);
    color: var(--eb-text-muted);
    font-size: 11px;
    cursor: pointer;
}
.eb-pop-close:hover {
    border-color: var(--eb-accent);
    color: var(--eb-accent);
}
.eb-pop-label {
    grid-column: 1 / -1;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--eb-text-muted);
    margin-bottom: 0;
}
.eb-pop-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
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
.eb-pop-actions { display: flex; gap: 6px; margin-top: 0; }
.eb-pop-apply {
    min-width: 130px;
    height: 34px;
    background: var(--eb-accent); color: #fff;
    border: none; border-radius: 8px;
    font-size: 12px; font-weight: 600; cursor: pointer;
}
html[data-theme='dark'] .eb-pop-apply { color: #0d1826; }
.eb-pop-apply:hover { background: var(--eb-accent-strong); }
html[data-theme='dark'] .eb-pop-apply:hover { background: var(--eb-accent); opacity: .85; }
.eb-pop-reset {
    height: 34px; padding: 0 12px;
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
    background:
        linear-gradient(180deg, rgba(250, 250, 247, .94), rgba(243, 240, 232, .86)),
        var(--eb-paper);
    border: 1px solid var(--eb-border);
    border-radius: 16px;
    padding: 20px;
    box-shadow: none;
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
.eb-link-btn:hover,
.eb-link-btn:focus-visible {
    background: var(--eb-accent);
    color: #fff;
    border-color: var(--eb-accent);
}
html[data-theme='dark'] .eb-link-btn:hover,
html[data-theme='dark'] .eb-link-btn:focus-visible { color: #0d1826; }

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
    transition: border-color .15s ease, background .15s ease, color .15s ease;
    box-shadow: none !important;
    filter: none !important;
    outline-offset: 2px;
}
a.eb-overview-card:hover,
a.eb-overview-card:focus-visible {
    border-color: var(--eb-accent);
    background:
        linear-gradient(180deg, rgba(232, 238, 229, .72), rgba(250, 250, 247, .96)),
        var(--eb-accent-soft);
    transform: none;
    box-shadow: none !important;
    filter: none !important;
}
.eb-accent-green { border-top-color: var(--eb-green); }
.eb-accent-red    { border-top-color: var(--eb-red); }
.eb-accent-slate  { border-top-color: var(--eb-text-muted); }

.eb-accent-green {
    background: var(--eb-card);
}

.eb-accent-red {
    background: var(--eb-card);
}

.eb-accent-slate {
    background: var(--eb-card);
}

.eb-overview-hero {
    background: var(--eb-card);
    color: var(--eb-text);
    border: 1px solid var(--eb-border);
    border-top: 3px solid var(--eb-accent);
    box-shadow: none !important;
    filter: none !important;
}
.eb-overview-hero .eb-overview-label { color: var(--eb-text-muted); }
.eb-overview-hero .eb-overview-value { color: var(--eb-text); }
.eb-overview-hero .eb-overview-sub   { color: var(--eb-text-muted); }
.eb-overview-watermark { display: none; }

.eb-overview-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; position: relative; z-index: 1; }
.eb-overview-label { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .07em; color: var(--eb-text-muted); }
.eb-overview-icon {
    width: 44px; height: 44px; border-radius: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    background: transparent !important;
    flex-shrink: 0;
}
.eb-icon-gold  { color: var(--eb-gold); }
.eb-icon-green { color: var(--eb-green); }
.eb-icon-red   { color: var(--eb-red); }
.eb-icon-slate { color: var(--eb-text-muted); }
.eb-overview-value {
    font-size: 1.72rem; font-weight: 900; letter-spacing: 0; line-height: 1.08;
    font-variant-numeric: tabular-nums; position: relative; z-index: 1;
}
.eb-val-green { color: var(--eb-green); }
.eb-val-red   { color: var(--eb-red); }
.eb-overview-sub { font-size: 12px; font-weight: 700; color: var(--eb-text-muted); margin-top: 4px; position: relative; z-index: 1; }

/* ══════════════════════════════════════════
   2. Revenue Trend
══════════════════════════════════════════ */
.eb-trend-card { display: flex; flex-direction: column; gap: 10px; position: relative; }
.eb-trend-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
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
   3. Branch Performance + Collection Mix
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
.eb-branch-performance-wrap {
    display: block;
}

.eb-branch-card {
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.eb-branch-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .5rem;
    flex-wrap: wrap;
}

.eb-branch-performance-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(240px, 320px);
    gap: 1rem;
    align-items: stretch;
}

@media (max-width: 900px) {
    .eb-branch-performance-grid {
        grid-template-columns: 1fr;
    }
}

.eb-branch-bar-chart {
    min-height: 430px;
    max-height: 430px;
    display: grid;
    grid-template-rows: repeat(3, minmax(0, 1fr));
    gap: .85rem;
    padding: 1rem;
    border: 1px solid var(--eb-border);
    border-radius: 8px;
    background:
        linear-gradient(180deg, rgba(250, 250, 247, .92), rgba(243, 240, 232, .82)),
        var(--eb-card);
}
.eb-branch-bar-item {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: .75rem;
    width: 100%;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--eb-border);
    background: var(--eb-card);
    transition: border-color .14s ease, background .14s ease, color .14s ease;
    cursor: pointer;
    overflow: hidden;
    text-align: left;
    color: inherit;
    font: inherit;
}
.eb-branch-bar-item::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: transparent;
    transition: width .12s ease, background .12s ease;
}
.eb-branch-bar-item.is-rank-1 {
    background: linear-gradient(90deg, rgba(192, 141, 53, .18), rgba(255, 255, 255, .92));
    border-color: rgba(192, 141, 53, .55);
}
.eb-branch-bar-item.is-rank-2 {
    background: linear-gradient(90deg, rgba(122, 128, 118, .16), rgba(255, 255, 255, .92));
    border-color: rgba(122, 128, 118, .48);
}
.eb-branch-bar-item.is-rank-3 {
    background: linear-gradient(90deg, rgba(184, 121, 86, .16), rgba(255, 255, 255, .92));
    border-color: rgba(184, 121, 86, .48);
}
.eb-branch-bar-item.is-rank-1::before { background: var(--eb-gold); }
.eb-branch-bar-item.is-rank-2::before { background: #7A8076; }
.eb-branch-bar-item.is-rank-3::before { background: var(--eb-amber); }

.eb-branch-bar-item:hover:not(.is-selected),
.eb-branch-bar-item:focus:not(.is-selected) {
    border-color: rgba(62, 74, 61, .72);
    background:
        linear-gradient(90deg, rgba(62, 74, 61, .14), rgba(250, 250, 247, .98)),
        var(--eb-paper-2);
}
.eb-branch-bar-item:hover::before,
.eb-branch-bar-item:focus::before {
    width: 7px;
}
.eb-branch-bar-item.is-rank-1:hover:not(.is-selected),
.eb-branch-bar-item.is-rank-1:focus:not(.is-selected) {
    border-color: rgba(192, 141, 53, .82);
    background: linear-gradient(90deg, rgba(192, 141, 53, .3), rgba(255, 255, 255, .98));
}
.eb-branch-bar-item.is-rank-2:hover:not(.is-selected),
.eb-branch-bar-item.is-rank-2:focus:not(.is-selected) {
    border-color: rgba(122, 128, 118, .8);
    background: linear-gradient(90deg, rgba(122, 128, 118, .28), rgba(255, 255, 255, .98));
}
.eb-branch-bar-item.is-rank-3:hover:not(.is-selected),
.eb-branch-bar-item.is-rank-3:focus:not(.is-selected) {
    border-color: rgba(184, 121, 86, .8);
    background: linear-gradient(90deg, rgba(184, 121, 86, .28), rgba(255, 255, 255, .98));
}
.eb-branch-bar-item.is-active,
.eb-branch-bar-item.is-selected {
    border-color: var(--eb-accent);
    background:
        linear-gradient(90deg, rgba(62, 74, 61, .24), rgba(232, 238, 229, .98)),
        var(--eb-paper-2);
}
.eb-branch-bar-item.is-selected::before {
    width: 8px;
    background: var(--eb-accent);
}
.eb-branch-bar-item.is-preview:not(.is-selected) {
    border-color: var(--eb-amber);
    background:
        linear-gradient(90deg, rgba(184, 121, 86, .2), rgba(255, 247, 235, .96)),
        var(--eb-amber-soft);
}
.eb-branch-bar-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
}
.eb-branch-row-head {
    display: flex; align-items: center;
    justify-content: space-between; gap: 10px; flex-wrap: wrap;
}
.eb-branch-main  { display: flex; align-items: center; gap: 9px; min-width: 0; }
.eb-branch-copy  { display: flex; align-items: baseline; gap: 5px; flex-wrap: wrap; min-width: 0; }
.eb-branch-code  { font-size: 10.5px; font-weight: 900; color: var(--eb-text-muted); text-transform: uppercase; letter-spacing: .08em; }
.eb-branch-name  { font-size: 14px; font-weight: 800; color: var(--eb-text); }
.eb-branch-row-stats { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.eb-branch-amount { font-size: 13px; font-weight: 700; color: var(--eb-accent); font-variant-numeric: tabular-nums; }
.eb-branch-bar-track {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    height: 34px;
    background:
        linear-gradient(90deg, rgba(62, 74, 61, .08), rgba(62, 74, 61, .035)),
        var(--eb-border);
    border-radius: 8px;
    overflow: hidden;
}
.eb-branch-bar-fill  {
    position: absolute;
    inset: 0 auto 0 0;
    height: 100%;
    border-radius: 8px;
    transition: width .6s ease;
}
.eb-branch-bar-value {
    position: relative;
    z-index: 1;
    margin-left: auto;
    margin-right: .55rem;
    padding: .25rem .5rem;
    border: 1px solid var(--eb-border);
    border-radius: 6px;
    background: rgba(255, 255, 255, .9);
    color: var(--eb-text);
    font-size: .92rem;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
    text-shadow: none;
}
html[data-theme='dark'] .eb-branch-bar-value {
    background: rgba(13, 24, 38, .86);
}
.eb-branch-bar-caption {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    color: var(--eb-text-muted);
    font-size: .78rem;
    font-weight: 800;
}

.eb-branch-pie-panel {
    min-height: 430px;
    height: 430px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    border: 1px solid var(--eb-border);
    border-radius: 8px;
    background:
        linear-gradient(180deg, rgba(139, 154, 139, .12), rgba(250, 250, 247, .88)),
        var(--eb-card);
    padding: 1.2rem;
}

.eb-branch-pie {
    width: min(100%, 220px);
    aspect-ratio: 1;
    border-radius: 50%;
    border: 1px solid var(--eb-border);
    background:
        conic-gradient(
            var(--eb-green) 0 var(--pie-paid, 33%),
            var(--eb-amber) var(--pie-paid, 33%) var(--pie-partial, 66%),
            var(--eb-red) var(--pie-partial, 66%) 100%
        );
    transition: background .18s ease;
}

.eb-branch-pie::after {
    content: "";
    display: block;
    width: 45%;
    aspect-ratio: 1;
    margin: 27.5%;
    border-radius: 50%;
    background: var(--eb-card);
    border: 1px solid var(--eb-border);
}

.eb-branch-pie-head {
    width: min(100%, 260px);
    display: flex;
    flex-direction: column;
    gap: .2rem;
    text-align: center;
}

.eb-branch-pie-title {
    color: var(--eb-text);
    font-size: .96rem;
    font-weight: 900;
}

.eb-branch-pie-branch {
    color: var(--eb-accent);
    font-size: .82rem;
    font-weight: 900;
}

.eb-branch-pie-values {
    width: min(100%, 260px);
    display: flex;
    flex-direction: column;
    gap: .5rem;
}

.eb-branch-pie-value {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    min-height: 2.15rem;
    padding: .45rem .6rem;
    border: 1px solid var(--eb-border);
    border-radius: 8px;
    background: var(--eb-card);
}

.eb-branch-pie-key {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    color: var(--eb-text);
    font-size: .82rem;
    font-weight: 900;
}

.eb-branch-pie-count {
    color: var(--eb-accent);
    font-size: .9rem;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.eb-rank-badge {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 68px;
    height: 30px;
    padding: 0 12px;
    border-radius: 999px;
    background: var(--eb-accent);
    border: 1px solid var(--eb-accent);
    color: #fff;
    font-size: 12px;
    font-weight: 900;
    line-height: 1;
    white-space: nowrap;
}
.eb-rank-badge.is-rank-1 { background: var(--eb-gold); border-color: var(--eb-gold); color: #fff; font-size: 12.5px; }
.eb-rank-badge.is-rank-2 { background: #596058; border-color: #596058; color: #fff; }
.eb-rank-badge.is-rank-3 { background: var(--eb-amber); border-color: var(--eb-amber); color: #fff; }
.eb-branch-bar-item:hover .eb-rank-badge,
.eb-branch-bar-item:focus .eb-rank-badge,
.eb-branch-bar-item.is-selected .eb-rank-badge {
    background: var(--eb-accent);
    border-color: var(--eb-accent);
    color: #fff;
}
.eb-branch-bar-item.is-selected .eb-rank-badge::after {
    content: "Selected";
    margin-left: 8px;
    padding-left: 8px;
    border-left: 1px solid rgba(255, 255, 255, .45);
    font-size: 10px;
    font-weight: 900;
}

html[data-theme='dark'] .eb-branch-bar-item.is-rank-1 {
    background: linear-gradient(90deg, rgba(245, 158, 11, .16), rgba(24, 38, 56, .92));
}
html[data-theme='dark'] .eb-branch-bar-item.is-rank-2 {
    background: linear-gradient(90deg, rgba(148, 163, 184, .14), rgba(24, 38, 56, .92));
}
html[data-theme='dark'] .eb-branch-bar-item.is-rank-3 {
    background: linear-gradient(90deg, rgba(251, 146, 60, .14), rgba(24, 38, 56, .92));
}
html[data-theme='dark'] .eb-rank-badge.is-rank-2 {
    color: #CBD5E1;
}

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
    align-items: stretch;
}
@media (max-width: 1100px) { .eb-bottom-grid { grid-template-columns: 1fr; } }
.eb-bottom-grid > .eb-section {
    min-height: 300px;
}

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
    flex: 1;
    min-height: 0;
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
.eb-quick-buttons,
.eb-filter-actions {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
}
.eb-quick-button {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    gap: .65rem;
    padding: .55rem .8rem;
    border: 1px solid var(--eb-border);
    border-radius: 8px;
    background: var(--eb-card);
    color: inherit;
    text-decoration: none;
    transition: border-color .12s, background .12s, color .12s;
    cursor: pointer;
}
.eb-quick-button:hover,
.eb-quick-button:focus-visible {
    background: var(--eb-accent-soft);
    border-color: var(--eb-accent);
    color: var(--eb-accent);
}
.eb-quick-button .eb-quick-arrow {
    opacity: 1;
    transform: none;
}
@media (max-width: 900px) { .eb-quick-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 480px) { .eb-quick-grid { grid-template-columns: 1fr; } }
@media (max-width: 640px) { .eb-quick-button { width: 100%; justify-content: space-between; } }
@media (max-width: 900px) { .eb-filter-actions { width: 100%; justify-content: flex-start; } }
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
    transition: border-color .12s, background .12s, transform .12s;
}
.eb-quick-item:hover { background: var(--eb-paper-2); border-color: var(--eb-accent); transform: translateY(-2px); }
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
    .eb-custom-wrap.is-open {
        left: 0;
        right: 0;
    }
    .eb-custom-wrap,
    .eb-date-popover {
        width: 100%;
        min-width: 0;
    }
    .eb-pop-fields {
        grid-template-columns: 1fr;
    }
    .eb-overview-value { font-size: 1.4rem; }
    .eb-trend-svg { height: 190px; }
    .eb-card { padding: 16px; }
}

/* UI/UX refinement: consistent dashboard rhythm and quieter executive surfaces. */
.eb-shell {
    gap: 1rem;
    width: 100%;
    max-width: none;
    padding: .9rem clamp(.8rem, 1.2vw, 1.25rem) 1.5rem;
    color: var(--eb-text);
}

.eb-filter-bar,
.eb-card,
.eb-overview-card,
.eb-quick-item,
.eb-quick-button,
.eb-status-row,
.eb-branch-row,
.eb-branch-bar-item,
.eb-pkg-row,
.eb-attention-card,
.eb-date-popover,
.eb-seg,
.eb-chip,
.eb-branch-select-wrap,
.eb-period-select-wrap {
    border-radius: 8px;
}

.eb-filter-bar,
.eb-card,
.eb-overview-card,
.eb-quick-item {
    box-shadow: none;
}

.eb-filter-bar {
    top: 0;
    padding: .8rem 1rem;
    gap: .75rem;
}

.eb-section {
    gap: .75rem;
}

.eb-section-header {
    align-items: center;
}

.eb-section-title {
    padding-bottom: 0;
    font-size: .98rem;
    font-weight: 800;
    color: var(--eb-text);
    letter-spacing: 0;
}

.eb-section-title::after {
    display: none;
}

.eb-section-sub {
    font-size: .84rem;
    font-weight: 600;
    color: var(--eb-text-muted);
}

.eb-overview-grid,
.eb-bottom-grid,
.eb-quick-grid,
.eb-quick-buttons {
    gap: .75rem;
}

.eb-overview-card {
    min-height: 126px;
    padding: 1rem;
}

.eb-overview-hero {
    background: var(--eb-card);
    color: var(--eb-text);
    border: 1px solid var(--eb-border);
    border-top: 3px solid var(--eb-accent);
}

.eb-overview-hero .eb-overview-label {
    color: var(--eb-text-muted);
}

.eb-overview-hero .eb-overview-value {
    color: var(--eb-text);
}

.eb-overview-hero .eb-overview-sub {
    color: var(--eb-text-muted);
}

.eb-overview-watermark {
    display: none;
}

.eb-overview-value {
    font-size: clamp(1.35rem, 2vw, 1.75rem);
    font-weight: 800;
    color: var(--eb-text);
    letter-spacing: 0;
}

.eb-overview-label,
.eb-status-name,
.eb-status-count,
.eb-branch-name,
.eb-pkg-name,
.eb-quick-label {
    color: var(--eb-text);
}

.eb-overview-sub,
.eb-status-pct,
.eb-branch-code,
.eb-pkg-cases,
.eb-filter-actions,
.eb-empty {
    color: var(--eb-text-muted);
}

.eb-card {
    padding: 1rem;
}

.eb-trend-svg {
    height: 210px;
}

.eb-status-row {
    grid-template-columns: 10px 64px minmax(80px, 1fr) auto 40px;
    padding: .7rem .75rem;
}

.eb-branch-card {
    max-height: none;
}

.eb-quick-item {
    padding: .85rem;
}
.eb-quick-button {
    padding: .55rem .8rem;
}

.eb-quick-item:hover,
a.eb-overview-card:hover {
    transform: none;
}

.eb-attention-card {
    padding: 1rem;
}

.eb-shell a[href],
.eb-shell button,
.eb-shell select,
.eb-shell [role="button"],
.eb-shell .eb-overview-card,
.eb-shell .eb-quick-button,
.eb-shell .eb-link-btn,
.eb-shell .eb-branch-bar-item,
.eb-shell .eb-status-row,
.eb-shell .eb-pkg-row,
.eb-shell .eb-pop-apply,
.eb-shell .eb-pop-reset,
.eb-shell .eb-branch-select-wrap,
.eb-shell .eb-period-select-wrap {
    transition: background-color .14s ease, border-color .14s ease, color .14s ease;
}

.eb-shell a[href]:hover,
.eb-shell button:hover,
.eb-shell [role="button"]:hover,
.eb-shell .eb-overview-card:hover,
.eb-shell .eb-quick-button:hover,
.eb-shell .eb-link-btn:hover,
.eb-shell .eb-branch-bar-item:hover,
.eb-shell .eb-status-row:hover,
.eb-shell .eb-pkg-row:hover,
.eb-shell .eb-branch-select-wrap:hover,
.eb-shell .eb-period-select-wrap:hover {
    transform: none !important;
    box-shadow: none !important;
    filter: none !important;
}

.eb-shell .eb-overview-card:hover,
.eb-shell .eb-quick-button:hover,
.eb-shell .eb-branch-select-wrap:hover,
.eb-shell .eb-period-select-wrap:hover,
.eb-shell .eb-status-row:hover,
.eb-shell .eb-pkg-row:hover {
    background: var(--eb-paper-2);
    border-color: var(--eb-accent);
    color: var(--eb-text);
}

.eb-shell .eb-branch-bar-item:hover:not(.is-selected) {
    border-color: var(--eb-accent);
}

.eb-shell .eb-branch-bar-item.is-selected,
.eb-shell .eb-branch-bar-item.is-active,
.eb-shell [aria-pressed="true"] {
    border-color: var(--eb-accent);
}

.eb-shell h1,
.eb-shell h2,
.eb-shell h3,
.eb-shell h4,
.eb-shell .eb-section-title,
.eb-shell .eb-overview-value,
.eb-shell .eb-branch-card-title,
.eb-shell .eb-branch-bar-value,
.eb-shell .eb-branch-pie-title {
    font-family: var(--font-heading);
    letter-spacing: 0;
}

.eb-shell .eb-overview-label,
.eb-shell [class*="label"],
.eb-shell .eb-branch-code,
.eb-shell .eb-attention-code,
.eb-shell .eb-pop-field-label,
.eb-shell .eb-live-chip,
.eb-shell .eb-status-label,
.eb-shell .eb-rank-badge {
    font-weight: 650 !important;
    letter-spacing: 0 !important;
}

.eb-shell a[href]:focus,
.eb-shell button:focus,
.eb-shell select:focus,
.eb-shell input:focus,
.eb-shell [role="button"]:focus,
.eb-shell a[href]:focus-visible,
.eb-shell button:focus-visible,
.eb-shell select:focus-visible,
.eb-shell input:focus-visible,
.eb-shell [role="button"]:focus-visible {
    outline: none !important;
    outline-offset: 0 !important;
}

.eb-shell a[href]:focus-visible,
.eb-shell button:focus-visible,
.eb-shell select:focus-visible,
.eb-shell input:focus-visible,
.eb-shell [role="button"]:focus-visible,
.eb-shell .eb-branch-select-wrap:focus-within,
.eb-shell .eb-period-select-wrap:focus-within {
    background: #DDE6D8;
    border-color: var(--eb-accent);
    color: var(--eb-text);
}

.eb-shell .eb-overview-card:hover,
.eb-shell .eb-quick-button:hover,
.eb-shell .eb-branch-select-wrap:hover,
.eb-shell .eb-period-select-wrap:hover,
.eb-shell .eb-status-row:hover,
.eb-shell .eb-pkg-row:hover,
.eb-shell .eb-pop-apply:hover,
.eb-shell .eb-pop-reset:hover {
    background: #DDE6D8;
    border-color: #8B9A8B;
    color: var(--eb-text);
}

.eb-shell .eb-branch-bar-item:hover:not(.is-selected) {
    background: #E0E7DA;
    border-color: #8B9A8B;
}

.eb-shell .eb-branch-bar-item.is-selected,
.eb-shell .eb-branch-bar-item.is-active,
.eb-shell [aria-pressed="true"] {
    background: #D5DFCF;
    border-color: var(--eb-accent);
    color: var(--eb-text);
}

@media (max-width: 640px) {
    .eb-shell {
        max-width: none;
        padding: 1rem .95rem 2.5rem;
    }
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
    var initOwnerDashboardToasts = function () {
        var stack = document.querySelector('[data-owner-toast-stack]');
        if (!stack || stack.dataset.ready === '1') return;
        stack.dataset.ready = '1';

        var showToast = function () {
            window.setTimeout(function () {
                var toast = document.createElement('div');
                toast.className = 'eb-owner-toast';
                toast.setAttribute('role', 'status');

                var icon = document.createElement('span');
                icon.className = 'eb-owner-toast__icon';
                icon.innerHTML = '<i class="bi bi-speedometer2"></i>';

                var copy = document.createElement('span');
                var toastTitle = document.createElement('strong');
                toastTitle.className = 'eb-owner-toast__title';
                toastTitle.textContent = 'Owner Dashboard';
                var toastBody = document.createElement('span');
                toastBody.className = 'eb-owner-toast__body';
                toastBody.textContent = 'You are currently on the dashboard.';
                copy.appendChild(toastTitle);
                copy.appendChild(toastBody);

                var close = document.createElement('button');
                close.type = 'button';
                close.className = 'eb-owner-toast__close';
                close.setAttribute('aria-label', 'Dismiss message');
                close.innerHTML = '<i class="bi bi-x-lg"></i>';

                var dismiss = function () {
                    toast.classList.remove('is-visible');
                    toast.classList.add('is-leaving');
                    window.setTimeout(function () { toast.remove(); }, 220);
                };

                close.addEventListener('click', dismiss);
                toast.appendChild(icon);
                toast.appendChild(copy);
                toast.appendChild(close);
                stack.appendChild(toast);

                requestAnimationFrame(function () {
                    toast.classList.add('is-visible');
                });
                window.setTimeout(dismiss, 4200);
            }, 120);
        };

        showToast();
    };

    initOwnerDashboardToasts();
    document.addEventListener('DOMContentLoaded', initOwnerDashboardToasts);
    window.addEventListener('pageshow', initOwnerDashboardToasts);
})();

(function () {
    var initBranchPerformancePie = function () {
        var card = document.querySelector('[data-branch-performance-card]');
        var pie = document.querySelector('[data-branch-pie]');
        if (!card || !pie || card.dataset.pieReady === '1') return;

        card.dataset.pieReady = '1';

        var rows = Array.prototype.slice.call(card.querySelectorAll('[data-branch-row]'));
        var branchLabel = card.querySelector('[data-pie-branch-label]');
        var paidValue = card.querySelector('[data-pie-paid-value]');
        var partialValue = card.querySelector('[data-pie-partial-value]');
        var unpaidValue = card.querySelector('[data-pie-unpaid-value]');
        var toNumber = function (value) {
            var parsed = Number(value);
            return isFinite(parsed) && parsed > 0 ? parsed : 0;
        };
        var formatNumber = function (value) {
            return value.toLocaleString ? value.toLocaleString() : String(value);
        };

        var paintPie = function (paid, partial, unpaid) {
            var total = paid + partial + unpaid;
            if (paidValue) paidValue.textContent = formatNumber(paid);
            if (partialValue) partialValue.textContent = formatNumber(partial);
            if (unpaidValue) unpaidValue.textContent = formatNumber(unpaid);

            if (total <= 0) {
                pie.style.background = 'conic-gradient(var(--eb-border) 0 100%)';
                pie.setAttribute('aria-label', 'Collection status chart with no cases');
                pie.title = 'No collection records for this branch';
                return;
            }

            var paidEnd = ((paid / total) * 100).toFixed(4);
            var partialEnd = ((paid + partial) / total * 100).toFixed(4);
            pie.style.background = 'conic-gradient(var(--eb-green) 0 ' + paidEnd + '%, var(--eb-amber) ' + paidEnd + '% ' + partialEnd + '%, var(--eb-red) ' + partialEnd + '% 100%)';
            pie.setAttribute('aria-label', 'Collection status chart: ' + paid + ' paid, ' + partial + ' partial, ' + unpaid + ' unpaid');
            pie.title = paid + ' paid, ' + partial + ' partial, ' + unpaid + ' unpaid';
        };

        var paintRow = function (row, stateLabel) {
            if (!row) return;

            paintPie(
                toNumber(row.dataset.paid),
                toNumber(row.dataset.partial),
                toNumber(row.dataset.unpaid)
            );
            if (branchLabel) {
                var label = row.dataset.branchLabel || 'Selected branch';
                branchLabel.textContent = stateLabel ? stateLabel + ': ' + label : label;
            }
        };

        var clearPreview = function () {
            rows.forEach(function (item) {
                item.classList.remove('is-preview');
            });
        };

        var setActiveRow = function (row, stateLabel) {
            if (!row) return;

            rows.forEach(function (item) {
                var isActive = item === row;
                item.classList.toggle('is-active', isActive);
            });

            paintRow(row, stateLabel);
        };

        var selectedRow = null;

        var previewRow = function (row) {
            if (!row) return;

            clearPreview();

            if (selectedRow) {
                if (row !== selectedRow) {
                    row.classList.add('is-preview');
                }
                return;
            }

            setActiveRow(row, 'Preview');
        };

        var selectRow = function (row) {
            if (!row) return;

            selectedRow = row;
            clearPreview();

            rows.forEach(function (item) {
                var isSelected = item === row;
                item.classList.toggle('is-active', isSelected);
                item.classList.toggle('is-selected', isSelected);
                item.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
            });

            paintRow(row, 'Selected');
        };

        var resolveRow = function (event) {
            return event.target.closest ? event.target.closest('[data-branch-row]') : null;
        };

        card.addEventListener('pointerover', function (event) {
            previewRow(resolveRow(event));
        });
        card.addEventListener('focusin', function (event) {
            previewRow(resolveRow(event));
        });
        card.addEventListener('click', function (event) {
            selectRow(resolveRow(event));
        });
        card.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            var row = resolveRow(event);
            if (!row) return;
            event.preventDefault();
            selectRow(row);
        });

        if (rows.length) {
            rows.forEach(function (item) {
                item.setAttribute('aria-pressed', 'false');
            });
            setActiveRow(rows[0], 'Preview');
        } else {
            paintPie(
                toNumber(card.dataset.defaultPaid),
                toNumber(card.dataset.defaultPartial),
                toNumber(card.dataset.defaultUnpaid)
            );
        }
    };

    window.initOwnerBranchPerformancePie = initBranchPerformancePie;
    initBranchPerformancePie();
    document.addEventListener('DOMContentLoaded', initBranchPerformancePie);
    window.addEventListener('pageshow', initBranchPerformancePie);
    document.addEventListener('livewire:navigated', initBranchPerformancePie);
    document.addEventListener('owner-dashboard:updated', initBranchPerformancePie);
})();

(function () {
    var openCustomPopover = function () {
        var wrap = document.getElementById('ebDateWrap');
        if (wrap) wrap.classList.add('is-open');
    };

    var closeCustomPopover = function () {
        var wrap = document.getElementById('ebDateWrap');
        if (wrap) wrap.classList.remove('is-open');
    };

    var submitOwnerFilter = function (form) {
        if (!form) return;

        var url = new URL(form.action, window.location.origin);
        var data = new FormData(form);
        data.forEach(function (value, key) {
            if (value !== null && String(value) !== '') {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
        });

        loadOwnerDashboard(url, true);
    };

    var loadOwnerDashboard = function (url, pushUrl) {
        var shell = document.querySelector('.eb-shell');
        var filterBar = document.querySelector('.eb-filter-bar');
        if (!shell) {
            window.location.href = url.toString();
            return;
        }

        if (filterBar) filterBar.classList.add('is-loading');

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Dashboard filter request failed.');
                return response.text();
            })
            .then(function (html) {
                var parsed = new DOMParser().parseFromString(html, 'text/html');
                var nextShell = parsed.querySelector('.eb-shell');
                if (!nextShell) throw new Error('Dashboard shell was not found.');

                shell.replaceWith(nextShell);
                if (pushUrl !== false) {
                    window.history.pushState({}, '', url.toString());
                }
                document.dispatchEvent(new CustomEvent('owner-dashboard:updated'));
            })
            .catch(function () {
                window.location.href = url.toString();
            })
            .finally(function () {
                var nextFilterBar = document.querySelector('.eb-filter-bar');
                if (nextFilterBar) nextFilterBar.classList.remove('is-loading');
            });
    };

    document.addEventListener('change', function (event) {
        var control = event.target.closest('[data-owner-filter-control]');
        if (!control) return;

        if (control.matches('[data-owner-period-select]') && control.value === 'CUSTOM') {
            openCustomPopover();
            control.blur();
            return;
        }

        closeCustomPopover();
        submitOwnerFilter(control.form);
    });

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-owner-filter-form]');
        if (!form) return;

        event.preventDefault();
        submitOwnerFilter(form);
    });

    document.addEventListener('click', function (event) {
        var closeButton = event.target.closest('[data-owner-custom-close]');
        if (closeButton) {
            event.preventDefault();
            event.stopImmediatePropagation();
            var periodSelect = document.querySelector('[data-owner-period-select]');
            if (periodSelect) periodSelect.value = 'THIS_MONTH';
            closeCustomPopover();
            loadOwnerDashboard(new URL(closeButton.dataset.defaultUrl, window.location.origin), true);
            return;
        }

        var link = event.target.closest('[data-owner-filter-link]');
        if (link) {
            event.preventDefault();
            event.stopImmediatePropagation();
            var resetPeriodSelect = document.querySelector('[data-owner-period-select]');
            if (resetPeriodSelect) resetPeriodSelect.value = 'THIS_MONTH';
            closeCustomPopover();
            loadOwnerDashboard(new URL(link.href, window.location.origin), true);
            return;
        }

    });

    window.addEventListener('popstate', function () {
        loadOwnerDashboard(new URL(window.location.href), false);
    });
})();

(function () {
    var periodSelect = document.querySelector('[data-owner-period-select]');
    if (periodSelect && periodSelect.value === 'CUSTOM') {
        var wrap = document.getElementById('ebDateWrap');
        if (wrap) wrap.classList.add('is-open');
    }
})();
</script>

@endsection
