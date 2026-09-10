@extends('layouts.panel')

@section('page_title', 'Reports & Analytics')
@section('page_desc', 'Analytics charts, branch performance, and operational reports.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    $baseQuery = request()->query();
    unset($baseQuery['range'], $baseQuery['date_from'], $baseQuery['date_to']);

    $dateRangeLinks = [
        'TODAY' => route('owner.analytics', array_merge($baseQuery, ['range' => 'TODAY'])),
        'THIS_MONTH' => route('owner.analytics', array_merge($baseQuery, ['range' => 'THIS_MONTH'])),
        'THIS_YEAR' => route('owner.analytics', array_merge($baseQuery, ['range' => 'THIS_YEAR'])),
    ];

    $clearCustomUrl = route('owner.analytics', array_merge($baseQuery, ['range' => 'THIS_YEAR']));
    $isCustomRange = $range === 'CUSTOM';
    $filterScopeLabel = $selectedBranch
        ? ($selectedBranch->branch_code . ' - ' . $selectedBranch->branch_name)
        : 'All Branches';
    $periodChipLabel = $isCustomRange ? 'CUSTOM RANGE' : str_replace('_', ' ', strtoupper($range));
    $periodContextLabel = $isCustomRange
        ? (\Carbon\Carbon::parse($dateFrom)->format('M d, Y') . ' - ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y'))
        : ucwords(strtolower(str_replace('_', ' ', $range)));
    $hasPageFilters = $branchId || $range !== 'TODAY' || $isCustomRange;

    $comparisonLabels = $chart['bar']['labels'] ?? [];
    $comparisonRevenue = $chart['bar']['revenue'] ?? [];
    $comparisonVolume = $chart['bar']['volume'] ?? [];
    $summaryPayload = [
        'totalSales' => (float) $totalSales,
        'totalCollected' => (float) $totalCollected,
        'totalOutstanding' => (float) $totalOutstanding,
        'status' => [
            'paid' => (int) $statusCounts['paid'],
            'partial' => (int) $statusCounts['partial'],
            'unpaid' => (int) $statusCounts['unpaid'],
            'ongoing' => (int) $statusCounts['ongoing'],
        ],
    ];
    $branchMeta = $branches->values()->map(fn ($branch) => [
        'code' => (string) $branch->branch_code,
        'name' => (string) $branch->branch_name,
    ])->all();
    $overallCollectionRate = ($totalCollected + $totalOutstanding) > 0
        ? (($totalCollected / ($totalCollected + $totalOutstanding)) * 100)
        : 0;
    $overallAvgRevenuePerCase = $totalCases > 0 ? ($totalSales / $totalCases) : 0;
    $periodLabels = $chart['period']['labels'] ?? [];
    $periodCases = $chart['period']['cases'] ?? [];
    $periodServiceAmounts = $chart['period']['service_amount'] ?? [];
    $periodCollectedAmounts = $chart['period']['collected_amount'] ?? [];
    $periodOutstandingBalances = $chart['period']['outstanding_balance'] ?? [];
    $trendLineData = $chart['line']['data'] ?? [];
    $masterCaseRecordsUrl = route('owner.history');
    $analyticsCaseCollection = collect($allAnalyticsCases ?? []);
    $branchRankingRows = $branches->map(function ($branch) use ($analyticsCaseCollection) {
        $branchCases = $analyticsCaseCollection->where('branchCode', (string) $branch->branch_code);
        $totalRevenue = (float) $branchCases->sum('totalAmount');
        $totalPaid = (float) $branchCases->sum('totalPaid');
        $outstandingBalance = (float) $branchCases->sum('balanceAmount');
        $totalCases = (int) $branchCases->count();
        $collectionBase = $totalPaid + $outstandingBalance;

        return [
            'branch_code' => (string) $branch->branch_code,
            'branch_name' => (string) $branch->branch_name,
            'total_revenue' => $totalRevenue,
            'total_cases' => $totalCases,
            'average_per_case' => $totalCases > 0 ? $totalRevenue / $totalCases : 0,
            'paid_cases' => (int) $branchCases->where('paymentStatus', 'PAID')->count(),
            'partial_cases' => (int) $branchCases->where('paymentStatus', 'PARTIAL')->count(),
            'unpaid_cases' => (int) $branchCases->where('paymentStatus', 'UNPAID')->count(),
            'collection_rate' => $collectionBase > 0 ? ($totalPaid / $collectionBase) * 100 : 0,
        ];
    })
        ->sortByDesc('total_revenue')
        ->values()
        ->all();
    $hasComparisonData = collect($comparisonRevenue)->merge($comparisonVolume)->contains(fn ($value) => (float) $value > 0);
    $hasPeriodData = collect($periodCases)->merge($periodServiceAmounts)->merge($periodCollectedAmounts)->merge($periodOutstandingBalances)->contains(fn ($value) => (float) $value > 0);
    $hasPaymentData = ((int) $statusCounts['paid'] + (int) $statusCounts['partial'] + (int) $statusCounts['unpaid'] + (int) $statusCounts['ongoing']) > 0;
    $hasTrendData = collect($periodServiceAmounts)->merge($trendLineData)->contains(fn ($value) => (float) $value > 0);
    $hasCollectionData = ((float) $totalCollected + (float) $totalOutstanding + (float) $totalSales) > 0;
    $selectedBranchDisplay = $selectedBranch
        ? ($selectedBranch->branch_code . ' - ' . $selectedBranch->branch_name)
        : null;
    $branchPerformanceTitle = $selectedBranch
        ? ($selectedBranch->branch_code . ' Performance Trend')
        : 'Branch Comparison';
    $branchChartContext = $branchPerformanceTitle . ' · ' . $filterScopeLabel . ' · ' . $periodContextLabel;
    $needsAttentionCount = (int) $statusCounts['partial'] + (int) $statusCounts['unpaid'];
    $needsAttentionAmount = (float) $totalOutstanding;
    $formatCompactPeso = function ($amount) {
        $amount = (float) $amount;

        if (abs($amount) >= 1000000) {
            return 'PHP ' . rtrim(rtrim(number_format($amount / 1000000, 2), '0'), '.') . 'M';
        }

        if (abs($amount) >= 1000) {
            return 'PHP ' . number_format($amount / 1000, 0) . 'K';
        }

        return 'PHP ' . number_format($amount, 2);
    };
    $branchPerformanceReportUrl = route('reports.index', ['report_type' => 'owner_branch_analytics']);
@endphp

<div class="ba-shell">
    @include('reports.partials.module-tabs', [
        'activeModule' => 'analytics',
        'reportTypes' => [
            'owner_branch_analytics' => 'Branch Performance Report',
        ],
        'branches' => $branches,
        'branchId' => $branchId,
        'range' => $range,
    ])

    <div class="reports-module-layout">
        @include('reports.partials.module-rail', [
            'activeModule' => 'analytics',
            'quickStats' => [
                ['label' => 'Active Cases', 'value' => number_format($totalCases)],
                ['label' => 'Branches', 'value' => number_format($branches->count())],
                ['label' => 'Period', 'value' => $periodContextLabel],
            ],
        ])

        <div class="reports-module-main">
    <section class="ba-card ba-workspace">
        <header class="ba-page-intro ba-visually-hidden">
            <div>
                <h3 class="ba-title">Branch Analytics</h3>
                <p class="ba-subtitle">Review branch performance, payments, collections, and revenue trends for the selected period.</p>
            </div>
        </header>

        <section class="ba-summary-grid" aria-label="Overview summary">
            <article class="ba-summary-card">
                <div class="ba-summary-icon">
                    <i class="bi bi-briefcase"></i>
                </div>
                <div class="ba-summary-content">
                    <span>Total Cases</span>
                    <strong>{{ number_format($totalCases) }}</strong>
                    <small>{{ $filterScopeLabel }} · {{ $periodContextLabel }}</small>
                    <div class="ba-kpi-actions" aria-label="Total cases actions">
                        <a class="ba-kpi-action" href="{{ $branchPerformanceReportUrl }}" aria-label="Open branch performance report">
                            Open Report
                            <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </article>
            <article class="ba-summary-card">
                <div class="ba-summary-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="ba-summary-content">
                    <span>Gross Amount</span>
                    <strong>{{ $formatCompactPeso($totalSales) }}</strong>
                    <small>For selected range</small>
                    <div class="ba-kpi-actions" aria-label="Gross amount actions">
                        <button type="button" class="ba-kpi-action ba-kpi-jump" data-target="ba-panel-trend" aria-label="View revenue trend analytics">
                            View Trend
                            <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </article>
            <article class="ba-summary-card">
                <div class="ba-summary-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="ba-summary-content">
                    <span>Collected</span>
                    <strong>{{ $formatCompactPeso($totalCollected) }}</strong>
                    <small>{{ number_format($overallCollectionRate, 1) }}% collection rate</small>
                    <div class="ba-kpi-actions" aria-label="Collected amount actions">
                        <button type="button" class="ba-kpi-action ba-kpi-jump" data-target="ba-panel-collection" aria-label="View collections analytics">
                            View Collections
                            <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </article>
            <article class="ba-summary-card ba-summary-card-warning">
                <div class="ba-summary-icon">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
                <div class="ba-summary-content">
                    <span>Outstanding</span>
                    <strong>{{ $formatCompactPeso($totalOutstanding) }}</strong>
                    <small>{{ number_format(max(0, 100 - $overallCollectionRate), 1) }}% remaining</small>
                    <div class="ba-kpi-actions" aria-label="Outstanding amount actions">
                        <button type="button" class="ba-kpi-action ba-kpi-jump" data-target="ba-panel-payment" aria-label="View payment status analytics">
                            View Payments
                            <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </article>
        </section>

        <header class="ba-workspace-head">
            <div class="ba-head-row ba-head-row-top">
                <div class="ba-tabs" role="tablist" aria-label="Analytics views">
                    <button class="ba-tab-btn active" data-target="ba-panel-performance" role="tab" aria-selected="true">
                        <i class="bi bi-bar-chart-line"></i>
                        Overview
                    </button>
                    <button class="ba-tab-btn" data-target="ba-panel-payment" role="tab" aria-selected="false">
                        <i class="bi bi-wallet2"></i>
                        Payments
                    </button>
                    <button class="ba-tab-btn" data-target="ba-panel-collection" role="tab" aria-selected="false">
                        <i class="bi bi-cash-stack"></i>
                        Collections
                    </button>
                    <button class="ba-tab-btn" data-target="ba-panel-trend" role="tab" aria-selected="false">
                        <i class="bi bi-graph-up-arrow"></i>
                        Revenue Trend
                    </button>
                </div>
            </div>

            <div class="ba-head-row ba-filter-row">
                <div class="ba-workspace-filters" role="group" aria-label="Branch Analytics Filters">
                    <form method="GET" action="{{ route('owner.analytics') }}" class="ba-branch-form ba-branch-form-inline">
                        @if($isCustomRange)
                            <input type="hidden" name="range" value="CUSTOM">
                            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                            <input type="hidden" name="date_to" value="{{ $dateTo }}">
                        @else
                            <input type="hidden" name="range" value="{{ $range }}">
                        @endif
                        <label for="baBranchFilter" class="ba-filter-label ba-visually-hidden">Branch Filter</label>
                        <div class="ba-branch-select-wrap" title="{{ $filterScopeLabel }}">
                            <i class="bi bi-building"></i>
                            <select id="baBranchFilter" name="branch_id" class="ba-branch-select" onchange="this.form.submit()">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>
                                        {{ $branch->branch_code }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="bi bi-chevron-down ba-branch-select-chev"></i>
                        </div>
                    </form>

                    <div class="ba-filter-group">
                        <span class="ba-filter-label ba-visually-hidden">Period Filter</span>
                        <div class="ba-seg" role="group" aria-label="Period Filter">
                            <form method="GET" action="{{ route('owner.analytics') }}" class="ba-period-form">
                                @if($branchId)
                                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                                @endif
                                <label for="baPeriodFilter" class="ba-filter-label ba-visually-hidden">Date Range Filter</label>
                                <div class="ba-period-select-wrap">
                                    <i class="bi bi-calendar3"></i>
                                    <select id="baPeriodFilter" name="range" class="ba-period-select">
                                        @foreach (['TODAY', 'THIS_MONTH', 'THIS_YEAR'] as $rangeKey)
                                            <option value="{{ $rangeKey }}" @selected($range === $rangeKey)>
                                                {{ ucwords(strtolower(str_replace('_', ' ', $rangeKey))) }}
                                            </option>
                                        @endforeach
                                        <option value="CUSTOM" @selected($isCustomRange)>Custom Range</option>
                                    </select>
                                    <i class="bi bi-chevron-down ba-branch-select-chev"></i>
                                </div>
                            </form>
                            <div class="ba-custom-range-wrap">
                                <div class="ba-date-popover ba-date-popover-inline" id="baDatePopover" style="{{ $isCustomRange ? 'display: block;' : 'display: none;' }}">
                                    <form method="GET" action="{{ route('owner.analytics') }}">
                                        @if($branchId)
                                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                                        @endif
                                        <input type="hidden" name="range" value="CUSTOM">

                                        <div class="ba-pop-label">Custom Date Range</div>
                                        <div class="ba-pop-fields">
                                            <div class="ba-pop-field">
                                                <label class="ba-pop-field-label" for="baDateFrom">Date From</label>
                                                <input
                                                    id="baDateFrom"
                                                    type="date"
                                                    name="date_from"
                                                    value="{{ old('date_from', $dateFrom) }}"
                                                    class="ba-pop-input"
                                                    required
                                                >
                                            </div>
                                            <div class="ba-pop-field">
                                                <label class="ba-pop-field-label" for="baDateTo">Date To</label>
                                                <input
                                                    id="baDateTo"
                                                    type="date"
                                                    name="date_to"
                                                    value="{{ old('date_to', $dateTo) }}"
                                                    class="ba-pop-input"
                                                    required
                                                >
                                            </div>
                                        </div>

                                        <div class="ba-pop-actions">
                                            <button type="submit" class="ba-pop-apply">Apply</button>
                                            <a href="{{ $clearCustomUrl }}" class="ba-pop-reset">Reset</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($hasPageFilters)
                    <a href="{{ route('owner.analytics') }}" class="ba-filter-clear">
                        <i class="bi bi-x-circle"></i>
                        <span>Clear filters</span>
                    </a>
                @endif
            </div>

            <div class="ba-head-row ba-head-row-nav">
                <div class="ba-workspace-chips" aria-label="Applied filters">
                    <div class="ba-context-chip" title="Applied Date Range">
                        <i class="bi bi-calendar3"></i>
                        <span>{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</span>
                    </div>
                    <div class="ba-context-chip" title="Applied Branch">
                        <i class="bi bi-building"></i>
                        <span>{{ $filterScopeLabel }}</span>
                    </div>
                    <div class="ba-context-chip ba-context-muted" title="Applied Period">
                        <i class="bi bi-funnel"></i>
                        <span>{{ $periodChipLabel }}</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="ba-global-filterbar" id="baGlobalFilterBar" hidden>
            <div class="ba-global-filter-main">
                <span class="ba-global-filter-label">Active chart filters</span>
                <div class="ba-global-filter-chips" id="baGlobalFilterChips"></div>
            </div>
            <div class="ba-global-filter-actions">
                <button type="button" class="ba-global-clear" id="baGlobalClearFiltersBtn">
                    <i class="bi bi-x-circle"></i>
                    <span>Clear all filters</span>
                </button>
            </div>
        </div>

        <div class="ba-panels">
            <article class="ba-panel active" id="ba-panel-performance" role="tabpanel">
                @if($chart['mode'] === 'all')
                    <div class="ba-performance-card">
                        <div class="ba-panel-head">
                            <div>
                                <h4 class="ba-panel-title">Branch Comparison</h4>
                            </div>
                            <div class="ba-panel-actions">
                                <span class="ba-panel-note">Revenue and case volume</span>
                                <button type="button" class="ba-expand-chart-btn" data-chart-expand="serviceCasesChart" data-chart-title="{{ $branchChartContext }}">
                                    <i class="bi bi-arrows-fullscreen"></i>
                                    <span>Full screen</span>
                                </button>
                            </div>
                        </div>

                        <div class="ba-chart-frame ba-branch-comparison-chart {{ ! $hasComparisonData ? 'has-empty-state' : '' }}">
                            @unless($hasComparisonData)
                                <div class="ba-chart-empty">
                                    <i class="bi bi-bar-chart"></i>
                                    <strong>No data available for {{ $periodContextLabel }}</strong>
                                    <span>Try selecting a wider date range or another branch.</span>
                                </div>
                            @endunless
                            <canvas id="serviceCasesChart"></canvas>
                        </div>
                        <div class="ba-insight-row ba-insights-hidden" id="baBranchInsights" aria-label="Branch performance insights"></div>
                    </div>

                    <div class="ba-compare-card ba-branch-ranking-card">
                        <div class="ba-compare-title">
                            <i class="bi bi-list-ol"></i>
                            <span>Branch Ranking</span>
                        </div>
                        <div class="ba-compare-table-wrap">
                            <table class="ba-compare-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Branch</th>
                                        <th>Total Revenue</th>
                                        <th>Total Cases</th>
                                        <th>Avg per Case</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($branchRankingRows as $index => $row)
                                        <tr
                                            class="ba-ranking-row"
                                            data-branch-code="{{ $row['branch_code'] }}"
                                            role="button"
                                            tabindex="0"
                                            aria-label="View {{ $row['branch_code'] }} - {{ $row['branch_name'] }} records"
                                        >
                                            <td>{{ $index + 1 }}</td>
                                            <td title="{{ $row['branch_code'] }} - {{ $row['branch_name'] }}">
                                                {{ $row['branch_code'] }} - {{ $row['branch_name'] }}
                                            </td>
                                            <td>PHP {{ number_format($row['total_revenue'], 2) }}</td>
                                            <td>{{ number_format($row['total_cases']) }}</td>
                                            <td>PHP {{ number_format($row['average_per_case'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="ba-empty-cell">No branch performance data available for selected filters.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="ba-branch-perf-head">
                        <h4 class="ba-panel-title">{{ $branchPerformanceTitle }}</h4>
                        <p class="ba-panel-subtitle">Period movement for {{ $selectedBranchDisplay }}.</p>
                    </div>

                    <div class="ba-panel-head">
                        <h4 class="ba-panel-title">{{ $branchPerformanceTitle }}</h4>
                        <div class="ba-panel-actions">
                            <span class="ba-panel-note">Cases and service amount by period</span>
                            <button type="button" class="ba-expand-chart-btn" data-chart-expand="branchPerformanceChart" data-chart-title="{{ $branchChartContext }}">
                                <i class="bi bi-arrows-fullscreen"></i>
                                <span>Full screen</span>
                            </button>
                        </div>
                    </div>
                    <div class="ba-chart-frame {{ ! $hasPeriodData ? 'has-empty-state' : '' }}">
                        @unless($hasPeriodData)
                            <div class="ba-chart-empty">
                                <i class="bi bi-calendar-range"></i>
                                <strong>No data available for {{ $periodContextLabel }}</strong>
                                <span>Try selecting a wider date range or another branch.</span>
                            </div>
                        @endunless
                        <canvas id="branchPerformanceChart"></canvas>
                    </div>

                    <div class="ba-branch-kpi-grid ba-branch-kpi-grid-compact">
                        <article class="ba-branch-kpi-card">
                            <span>Average Service Amount per Case</span>
                            <strong>PHP {{ number_format($overallAvgRevenuePerCase, 2) }}</strong>
                        </article>
                    </div>

                    <div class="ba-compare-card">
                        <div class="ba-compare-title">Branch Performance Table</div>
                        <div class="ba-compare-table-wrap">
                            <table class="ba-compare-table">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Total Cases</th>
                                        <th>Total Service Amount</th>
                                        <th>Average Service Amount per Case</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($periodLabels as $index => $_periodLabel)
                                        @php
                                            $periodCaseCount = (float) ($periodCases[$index] ?? 0);
                                            $periodService = (float) ($periodServiceAmounts[$index] ?? 0);
                                            $periodAvgRevenue = $periodCaseCount > 0 ? ($periodService / $periodCaseCount) : 0;
                                        @endphp
                                        <tr>
                                            <td>{{ (string) ($periodLabels[$index] ?? $_periodLabel ?? '-') }}</td>
                                            <td>{{ number_format($periodCaseCount) }}</td>
                                            <td>PHP {{ number_format($periodService, 2) }}</td>
                                            <td>PHP {{ number_format($periodAvgRevenue, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="ba-empty-cell">No period performance data available for selected filters.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </article>

            <article class="ba-panel" id="ba-panel-payment" role="tabpanel" hidden>
                <div class="ba-panel-head">
                    <h4 class="ba-panel-title">Payment Status</h4>
                    <div class="ba-panel-actions">
                        <span class="ba-panel-note" data-default-note="Paid vs partial vs unpaid case distribution">Paid vs partial vs unpaid case distribution</span>
                        <button type="button" class="ba-expand-chart-btn" data-chart-expand="paymentChart" data-chart-title="Payment Status · {{ $filterScopeLabel }} · {{ $periodContextLabel }}">
                            <i class="bi bi-arrows-fullscreen"></i>
                            <span>Full screen</span>
                        </button>
                    </div>
                </div>
                <div class="ba-tab-context" data-context-tab="payment" hidden></div>
                <div class="ba-status-pills">
                    <span class="ba-status-pill ba-paid">Paid: {{ number_format($statusCounts['paid']) }}</span>
                    <span class="ba-status-pill ba-partial">Partial: {{ number_format($statusCounts['partial']) }}</span>
                    <span class="ba-status-pill ba-unpaid">Unpaid: {{ number_format($statusCounts['unpaid']) }}</span>
                    <span class="ba-status-pill ba-ongoing">Ongoing: {{ number_format($statusCounts['ongoing']) }}</span>
                </div>
                <div class="ba-chart-frame ba-chart-frame-narrow {{ ! $hasPaymentData ? 'has-empty-state' : '' }}">
                    @unless($hasPaymentData)
                        <div class="ba-chart-empty">
                            <i class="bi bi-wallet2"></i>
                            <strong>No data available for {{ $periodContextLabel }}</strong>
                            <span>Try selecting a wider date range or another branch.</span>
                        </div>
                    @endunless
                    <canvas id="paymentChart"></canvas>
                </div>
                <div class="ba-insight-row ba-insights-hidden" id="baPaymentInsights" aria-label="Payment status insights"></div>
            </article>

            <article class="ba-panel" id="ba-panel-trend" role="tabpanel" hidden>
                <div class="ba-panel-head">
                    <h4 class="ba-panel-title">Revenue Trend</h4>
                    <div class="ba-panel-actions">
                        <span class="ba-panel-note" data-default-note="Period movement">Period movement</span>
                        <button type="button" class="ba-expand-chart-btn" data-chart-expand="trendChart" data-chart-title="Revenue Trend · {{ $filterScopeLabel }} · {{ $periodContextLabel }}">
                            <i class="bi bi-arrows-fullscreen"></i>
                            <span>Full screen</span>
                        </button>
                    </div>
                </div>
                <div class="ba-tab-context" data-context-tab="trend" hidden></div>
                <div class="ba-insight-row ba-insights-hidden" id="baRevenueInsights" aria-label="Revenue trend key metrics"></div>
                <div class="ba-chart-frame {{ ! $hasTrendData ? 'has-empty-state' : '' }}">
                    @unless($hasTrendData)
                        <div class="ba-chart-empty">
                            <i class="bi bi-graph-up"></i>
                            <strong>No data available for {{ $periodContextLabel }}</strong>
                            <span>Try selecting a wider date range or another branch.</span>
                        </div>
                    @endunless
                    <canvas id="trendChart"></canvas>
                </div>
            </article>

            <article class="ba-panel" id="ba-panel-collection" role="tabpanel" hidden>
                <div class="ba-panel-head">
                    <h4 class="ba-panel-title">Collections</h4>
                    <div class="ba-panel-actions">
                        <span class="ba-panel-note" data-default-note="Collected vs outstanding">Collected vs outstanding</span>
                        <div class="ba-chart-mode-toggle" role="group" aria-label="Collections chart view">
                            <button type="button" class="ba-chart-mode-btn active" data-collection-view="snapshot" aria-pressed="true">
                                Snapshot
                            </button>
                            <button type="button" class="ba-chart-mode-btn" data-collection-view="by_period" aria-pressed="false">
                                By Period
                            </button>
                        </div>
                        <button type="button" class="ba-expand-chart-btn" data-chart-expand="collectionChart" data-chart-title="Collections · {{ $filterScopeLabel }} · {{ $periodContextLabel }}">
                            <i class="bi bi-arrows-fullscreen"></i>
                            <span>Full screen</span>
                        </button>
                    </div>
                </div>
                <div class="ba-tab-context" data-context-tab="collection" hidden></div>

                <div class="ba-chart-frame {{ ! $hasCollectionData ? 'has-empty-state' : '' }}">
                    @unless($hasCollectionData)
                        <div class="ba-chart-empty">
                            <i class="bi bi-cash-stack"></i>
                            <strong>No data available for {{ $periodContextLabel }}</strong>
                            <span>Try selecting a wider date range or another branch.</span>
                        </div>
                    @endunless
                    <canvas id="collectionChart"></canvas>
                </div>
                <div class="ba-insight-row ba-insights-hidden" id="baCollectionInsights" aria-label="Collection status insights"></div>
            </article>
        </div>

        <section class="ba-drilldown" aria-label="Analytics drill-down results">
            <div class="ba-drilldown-head">
                <div>
                    <h4 class="ba-panel-title" id="baDrilldownTitle">Overall Branch Analytics Summary</h4>
                    <p class="ba-panel-subtitle" id="baDrilldownSubtitle">Showing cases for the current analytics range.</p>
                </div>
            </div>

            <div class="ba-drilldown-actionbar">
                <div class="ba-active-filters" id="baActiveFilters" aria-label="Active analytics filters"></div>
                <div class="ba-drilldown-actions">
                    <button type="button" class="ba-clear-filters" id="baClearFiltersBtn">
                        <i class="bi bi-x-circle"></i>
                        <span>Clear Filters</span>
                    </button>
                    <button type="button" class="ba-master-records-btn" id="baMasterRecordsBtn">
                        <i class="bi bi-folder2-open"></i>
                        <span>View full Master Case Records</span>
                    </button>
                </div>
            </div>

            <div class="ba-drilldown-kpis">
                <article class="ba-branch-kpi-card ba-drill-kpi ba-kpi-revenue">
                    <span>Total Revenue</span>
                    <strong id="baFilteredRevenue">PHP 0.00</strong>
                </article>
                <article class="ba-branch-kpi-card ba-drill-kpi ba-kpi-cases">
                    <span>Total Cases</span>
                    <strong id="baFilteredCases">0</strong>
                </article>
                <article class="ba-branch-kpi-card ba-drill-kpi ba-kpi-average">
                    <span>Average per Case</span>
                    <strong id="baFilteredAverage">PHP 0.00</strong>
                </article>
                <article class="ba-branch-kpi-card ba-drill-kpi ba-kpi-outstanding" id="baOutstandingCard">
                    <span>Outstanding Balance</span>
                    <strong id="baFilteredOutstanding">PHP 0.00</strong>
                </article>
            </div>

            <details class="ba-breakdown-section" open>
                <summary class="ba-breakdown-summary">
                    <span>
                        <i class="bi bi-pie-chart" aria-hidden="true"></i>
                        Status Breakdowns
                    </span>
                    <small>Payment and collection mix</small>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </summary>

                <div class="ba-breakdown-grid">
                    <div class="ba-compare-card ba-breakdown-card">
                        <div class="ba-compare-title">Payment Status Breakdown</div>
                        <div class="ba-breakdown-list" id="baPaymentBreakdown"></div>
                    </div>
                    <div class="ba-compare-card ba-breakdown-card">
                        <div class="ba-compare-title">Collection Status Breakdown</div>
                        <div class="ba-breakdown-list" id="baCollectionBreakdown"></div>
                    </div>
                </div>
            </details>

            <div class="ba-compare-card">
                <div class="ba-compare-title" id="baCasesTableTitle">All Cases</div>
                <div class="ba-compare-table-wrap">
                    <table class="ba-compare-table ba-cases-table">
                        <thead>
                            <tr>
                                <th>Case</th>
                                <th>Date</th>
                                <th>Branch</th>
                                <th>Client</th>
                                <th>Deceased</th>
                                <th>Payment</th>
                                <th>Collection</th>
                                <th>Total Revenue</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody id="baFilteredCasesBody"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </section>
        </div>
    </div>
</div>

<div class="ba-chart-modal" id="baChartModal" hidden aria-hidden="true">
    <div class="ba-chart-modal__backdrop" data-chart-modal-close aria-hidden="true"></div>
    <section class="ba-chart-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="baChartModalTitle">
        <header class="ba-chart-modal__head">
            <h3 id="baChartModalTitle">Analytics Chart</h3>
            <div class="ba-chart-modal__workspace" aria-label="Expanded analytics sections">
                <button type="button" class="ba-chart-modal__view is-active" data-modal-view="chart" aria-pressed="true">Chart</button>
                <button type="button" class="ba-chart-modal__view" data-modal-view="records" aria-pressed="false" disabled>Records</button>
                <span class="ba-chart-modal__record-count" id="baModalRecordCount">0 records</span>
            </div>
            <button type="button" class="ba-chart-modal__close" data-chart-modal-close aria-label="Close expanded chart">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>
        <div class="ba-chart-modal__body" id="baChartModalBody">
            <div class="ba-chart-modal__chart-slot" id="baChartModalChartSlot"></div>
            <section class="ba-modal-drilldown" id="baModalDrilldown" aria-live="polite" hidden></section>
        </div>
    </section>
</div>

<style>
.ba-shell {
    padding: 1.25rem var(--panel-content-inline, 1.5rem) 2.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    color: #0f172a;
    background: #f5f7fb;
    min-height: calc(100vh - var(--panel-topbar-height, 0px));
}

.ba-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.ba-filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.ba-filter-group-inline {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.ba-visually-hidden {
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

.ba-filter-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
}

.ba-seg {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 0.35rem;
    flex: 0 0 auto;
}

.ba-seg-item,
.ba-date-btn {
    height: 32px;
    padding: 0 0.72rem;
    border: 1px solid #dbe3ef;
    background: #fff;
    border-radius: 9px;
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    line-height: 1;
    transition: all 0.15s ease;
    cursor: pointer;
    white-space: nowrap;
    width: auto;
    min-width: fit-content;
    flex: 0 0 auto;
}

.ba-seg-item:hover,
.ba-date-btn:hover {
    border-color: #3E4A3D;
    color: #3E4A3D;
    background: #F3F0E8;
}

.ba-seg-item.active,
.ba-date-btn.active {
    background: #3E4A3D;
    border-color: #3E4A3D;
    color: #fff;
}

.ba-date-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex: 0 0 auto;
}

.ba-custom-range-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    flex: 0 0 auto;
}

.ba-date-btn span {
    display: inline-block;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ba-date-chev {
    font-size: 10px;
    opacity: 0.65;
}

.ba-date-clear {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    border: 1px solid #dbe3ef;
    background: #fff;
    color: #475569;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.ba-date-clear:hover {
    border-color: #3E4A3D;
    color: #3E4A3D;
    background: #F3F0E8;
}

.ba-date-popover {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    left: auto;
    z-index: 240;
    min-width: 300px;
    max-width: min(340px, calc(100vw - 3rem));
    background: #fff;
    border: 1px solid #dbe3ef;
    border-radius: 12px;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.14);
    padding: 0.8rem;
}

.ba-date-popover-inline {
    position: static;
    min-width: min(460px, calc(100vw - 3rem));
    max-width: 100%;
    box-shadow: none;
}

.ba-pop-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.7rem;
}

.ba-pop-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.55rem;
    margin-bottom: 0.75rem;
}

.ba-pop-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.ba-pop-field-label {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
}

.ba-pop-input {
    height: 34px;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    padding: 0 0.6rem;
    font-size: 12px;
    color: #0f172a;
    background: #f8fafc;
}

.ba-pop-input:focus {
    outline: none;
    border-color: #3E4A3D;
    box-shadow: 0 0 0 2px rgba(62,74,61,0.18);
    background: #fff;
}

.ba-pop-apply {
    width: auto;
    flex: 1;
    border: 0;
    border-radius: 8px;
    height: 34px;
    background: #3E4A3D;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s ease;
}

.ba-pop-apply:hover {
    background: #2D372D;
}

.ba-pop-actions {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.ba-pop-reset {
    height: 34px;
    padding: 0 0.7rem;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    background: #fff;
    color: #475569;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.ba-pop-reset:hover {
    border-color: #3E4A3D;
    color: #3E4A3D;
    background: #F3F0E8;
}

.ba-branch-form {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    min-width: min(100%, 340px);
}

.ba-branch-form-inline {
    min-width: 0;
    width: auto;
    gap: 0;
    flex: 0 0 auto;
}

.ba-branch-select-wrap {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    height: 32px;
    box-sizing: border-box;
    border: 1px solid #dbe3ef;
    border-radius: 9px;
    background: #fff;
    padding: 0 0.42rem;
    min-width: 132px;
    max-width: 148px;
}

.ba-branch-select-wrap i {
    font-size: 14px;
    color: #64748b;
}

.ba-branch-select-chev {
    font-size: 10px;
    opacity: 0.65;
    pointer-events: none;
}

.ba-branch-select {
    flex: 0 1 auto;
    min-width: 0;
    height: 100%;
    border: 0;
    background: transparent;
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
    outline: none;
    text-overflow: ellipsis;
    white-space: nowrap;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 0.1rem;
}

.ba-context-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.ba-context-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    color: #334155;
    border: 1px solid #dbe3ef;
    background: #fff;
    white-space: nowrap;
}

.ba-context-chip span {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ba-context-muted {
    color: #64748b;
    background: #f8fafc;
}

.ba-kpi-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.65rem;
}

.ba-kpi-tile {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    padding: 0.8rem;
    min-height: 76px;
}

.ba-kpi-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.ba-kpi-value {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.ba-green {
    color: #166534;
}

.ba-red {
    color: #b91c1c;
}

.ba-workspace {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    background: #fff;
}

.ba-page-intro {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: 0.2rem;
}

.ba-workspace-head {
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
}

.ba-global-filterbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.8rem;
    flex-wrap: wrap;
    border: 1px solid #bfdbfe;
    border-radius: 14px;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    box-shadow: 0 8px 22px rgba(30, 64, 175, 0.07);
    padding: 0.7rem 0.8rem;
}

.ba-global-filter-main {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
}

.ba-global-filter-label {
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.ba-global-filter-chips {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.ba-filter-chip-remove {
    border: 0;
    background: transparent;
    color: inherit;
    font: inherit;
    font-size: 14px;
    line-height: 1;
    padding: 0 0 0 0.15rem;
    cursor: pointer;
    opacity: 0.75;
}

.ba-filter-chip-remove:hover {
    opacity: 1;
}

.ba-global-clear {
    height: 30px;
    border-radius: 8px;
    border: 1px solid #dbe3ef;
    background: #fff;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    padding: 0 0.65rem;
    cursor: pointer;
}

.ba-global-clear:hover {
    background: #F3F0E8;
    border-color: #3E4A3D;
    color: #3E4A3D;
}

.ba-tab-context {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    flex-wrap: wrap;
    border: 1px solid rgba(62,74,61,0.22);
    border-radius: 12px;
    background: #F3F0E8;
    color: #3E4A3D;
    padding: 0.55rem 0.7rem;
    font-size: 12px;
    font-weight: 700;
}

.ba-tab-context button {
    border: 1px solid rgba(62,74,61,0.30);
    background: #fff;
    color: #3E4A3D;
    border-radius: 8px;
    height: 28px;
    padding: 0 0.55rem;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.ba-tab-context button:hover {
    background: #F3F0E8;
    border-color: #3E4A3D;
}

.ba-head-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.ba-head-row-top {
    align-items: center;
}

.ba-head-row-nav {
    align-items: center;
    justify-content: space-between;
    flex-wrap: nowrap;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.75rem;
    gap: 1rem;
}

.ba-workspace-head-copy {
    min-width: 260px;
}

.ba-workspace-chips {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    flex-wrap: nowrap;
    flex: 0 0 auto;
}

.ba-workspace-filters {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.45rem;
    flex-wrap: nowrap;
    flex: 0 0 auto;
}

.ba-workspace-filters form {
    margin: 0;
}

.ba-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
}

.ba-subtitle {
    margin: 0.2rem 0 0;
    font-size: 12px;
    color: #64748b;
}

.ba-tabs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: nowrap;
    min-width: fit-content;
    flex: 0 0 auto;
}

.ba-tab-btn {
    border: 1px solid #dbe3ef;
    background: #fff;
    border-radius: 8px;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    height: 34px;
    padding: 0 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.15s ease;
    white-space: nowrap;
    width: auto;
    min-width: fit-content;
    flex: 0 0 auto;
}

.ba-tab-btn:hover {
    color: #3E4A3D;
    border-color: #3E4A3D;
    background: #F3F0E8;
    box-shadow: 0 6px 16px rgba(62, 74, 61, 0.06);
}

.ba-tab-btn.active {
    background: #3E4A3D;
    border-color: #3E4A3D;
    color: #fff;
    box-shadow: 0 8px 20px rgba(62, 74, 61, 0.18);
}

.ba-panel {
    display: none;
    flex-direction: column;
    gap: 0.8rem;
}

.ba-panel.active {
    display: flex;
}

.ba-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.ba-panel-title {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.ba-panel-subtitle {
    margin: 0.15rem 0 0;
    font-size: 12px;
    color: #64748b;
}

.ba-panel-note {
    font-size: 11px;
    color: #64748b;
    padding: 0.28rem 0.55rem;
    border: 1px solid #dbe3ef;
    border-radius: 999px;
    background: #f8fafc;
}

.ba-chart-frame {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #fff;
    padding: 0.85rem;
    height: 360px;
    position: relative;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.ba-chart-frame:hover {
    border-color: #3E4A3D;
    box-shadow: 0 10px 26px rgba(62, 74, 61, 0.08);
}

.ba-chart-frame canvas {
    cursor: pointer;
}

.ba-chart-frame.has-empty-state canvas {
    display: none !important;
}

.ba-chart-helper {
    margin: -0.35rem 0 0;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
}

.ba-insight-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 0.55rem;
}

.ba-insight-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    padding: 0.68rem 0.72rem;
    min-height: 82px;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.25rem;
}

.ba-insight-card.is-clickable {
    cursor: pointer;
    transition: transform 0.15s ease, border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.ba-insight-card.is-clickable:hover {
    background: #F3F0E8;
    border-color: #3E4A3D;
    box-shadow: 0 6px 18px rgba(62, 74, 61, 0.12);
    transform: translateY(-1px);
}

/* Revenue insight card — selected (active period) state */
#baRevenueInsights .ba-insight-card.is-selected {
    border-color: #3E4A3D !important;
    box-shadow: inset 0 0 0 2px #3E4A3D, 0 4px 12px rgba(62, 74, 61, 0.10) !important;
}

/* Keyboard focus + pressed state for clickable insight cards */
.ba-insight-card.is-clickable:focus-visible {
    outline: none;
    border-color: #3E4A3D;
    box-shadow: 0 0 0 3px rgba(62, 74, 61, 0.18);
}

.ba-insight-card.is-clickable:active {
    transform: scale(0.97);
    box-shadow: none;
}

/* Non-clickable insight cards: default cursor, no hover effect */
.ba-insight-card:not(.is-clickable) {
    cursor: default;
}

.ba-insight-hint {
    color: #3E4A3D;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.03em;
    margin-top: 1px;
    opacity: 0.75;
}

.ba-insight-label {
    color: #64748b;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.ba-insight-value {
    color: #0f172a;
    font-size: 15px;
    font-weight: 800;
    line-height: 1.2;
}

.ba-insight-note {
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.3;
}

.ba-insight-card.tone-blue {
    background: #f8faff;
    border-color: #dbeafe;
}

.ba-insight-card.tone-green {
    background: #ecfdf5;
    border-color: #a7f3d0;
}

.ba-insight-card.tone-amber {
    background: #fff7ed;
    border-color: #fed7aa;
}

.ba-insight-card.tone-red {
    background: #fef2f2;
    border-color: #fecaca;
}

.ba-chart-frame-narrow {
    max-width: 560px;
}

.ba-branch-perf-head {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.ba-branch-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.55rem;
}

.ba-branch-kpi-grid-compact {
    grid-template-columns: minmax(260px, 420px);
}

.ba-branch-kpi-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    padding: 0.65rem 0.7rem;
    min-height: 74px;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
}

.ba-branch-kpi-card span {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 0.2rem;
}

.ba-branch-kpi-card strong {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: 0;
}

.ba-branch-kpi-card small {
    display: block;
    margin-top: 0.18rem;
    color: #64748b;
    font-size: 11px;
    font-weight: 650;
    line-height: 1.25;
}

.ba-compare-card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.ba-compare-title {
    padding: 0.7rem 0.8rem;
    font-size: 12px;
    font-weight: 800;
    color: #334155;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.ba-compare-table-wrap {
    overflow-x: auto;
}

.ba-compare-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 980px;
}

.ba-compare-table th,
.ba-compare-table td {
    text-align: left;
    padding: 0.62rem 0.75rem;
    border-bottom: 1px solid #e5e7eb;
    font-size: 12px;
}

.ba-compare-table th {
    background: #f1f5f9;
    color: #64748b;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-size: 10.5px;
}

.ba-compare-table td {
    color: #1e293b;
}

.ba-compare-table tbody tr {
    transition: background 0.15s ease;
}

.ba-compare-table tbody tr:hover {
    background: #f8fafc;
}

.ba-ranking-row {
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.ba-ranking-row:focus-visible td {
    outline: 2px solid #3E4A3D;
    outline-offset: -2px;
}

.ba-ranking-row.is-selected {
    background: #eff6ff;
    box-shadow: inset 3px 0 0 #2563eb;
}

.ba-ranking-row.is-selected:hover {
    background: #eff6ff;
}

.ba-row-filter-btn {
    height: 28px;
    padding: 0 0.58rem;
    border-radius: 7px;
    border: 1px solid #dbe3ef;
    background: #fff;
    color: #334155;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.ba-row-filter-btn:hover {
    background: #F3F0E8;
    border-color: #3E4A3D;
    color: #3E4A3D;
}

.ba-ranking-row.is-selected .ba-row-filter-btn {
    background: #3E4A3D;
    border-color: #3E4A3D;
    color: #fff;
}

.ba-empty-cell {
    text-align: center;
    color: #64748b;
}

.ba-status-pills {
    display: flex;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.ba-status-pill {
    border-radius: 999px;
    padding: 0.35rem 0.65rem;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid transparent;
}

.ba-paid {
    background: #ecfdf3;
    border-color: #bbf7d0;
    color: #166534;
}

.ba-partial {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
}

.ba-unpaid {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}

.ba-ongoing {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
}

.ba-collection-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.55rem;
}

.ba-collection-metric {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    padding: 0.7rem;
}

.ba-collection-metric span {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 0.2rem;
}

.ba-collection-metric strong {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
}

.ba-drilldown {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
    padding: 0.95rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}

.ba-drilldown.has-active-filter {
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    border-color: #bfdbfe;
    box-shadow: 0 10px 28px rgba(30, 64, 175, 0.08);
}

.ba-drilldown-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.ba-drilldown-actions {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.ba-drilldown-actionbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.7rem;
    flex-wrap: wrap;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    padding: 0.65rem;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
}

.ba-clear-filters,
.ba-master-records-btn {
    height: 34px;
    border-radius: 8px;
    padding: 0 0.75rem;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.ba-clear-filters {
    border: 1px solid #dbe3ef;
    background: #fff;
    color: #475569;
}

.ba-master-records-btn {
    border: 1px solid #3E4A3D;
    background: #3E4A3D;
    color: #fff;
}

.ba-clear-filters:hover {
    border-color: #3E4A3D;
    color: #3E4A3D;
    background: #F3F0E8;
}

.ba-master-records-btn:hover {
    background: #2D372D;
    border-color: #2D372D;
}

.ba-active-filters {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
    min-height: 28px;
}

.ba-active-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.34rem 0.62rem;
    border-radius: 999px;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 700;
}

.ba-active-filter-chip.filter-positive {
    background: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}

.ba-active-filter-chip.filter-warning {
    background: #fff7ed;
    color: #c2410c;
    border-color: #fed7aa;
}

.ba-active-filter-chip.filter-danger {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}

.ba-active-filter-empty {
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
}

.ba-drilldown-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.55rem;
}

.ba-drill-kpi {
    background: #fff;
    border-color: #e2e8f0;
}

.ba-kpi-revenue {
    background: #f8faff;
    border-color: #dbeafe;
}

.ba-kpi-cases {
    background: #fff;
}

.ba-kpi-average {
    background: #f8fafc;
}

.ba-kpi-outstanding {
    background: #fff7ed;
    border-color: #fed7aa;
}

.ba-kpi-outstanding.is-zero {
    background: #ecfdf5;
    border-color: #a7f3d0;
}

.ba-breakdown-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
}

.ba-breakdown-section {
    border: 1px solid #d9e5d2;
    border-radius: 12px;
    background: rgba(244, 247, 237, 0.56);
    overflow: hidden;
}

.ba-breakdown-section[open] {
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.48);
}

.ba-breakdown-summary {
    min-height: 44px;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 0.7rem;
    padding: 0.72rem 0.85rem;
    color: #263126;
    cursor: pointer;
    list-style: none;
    user-select: none;
}

.ba-breakdown-summary::-webkit-details-marker {
    display: none;
}

.ba-breakdown-summary > span {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 850;
}

.ba-breakdown-summary small {
    color: #6B7568;
    font-size: 0.78rem;
    font-weight: 700;
    text-align: right;
}

.ba-breakdown-summary > i:last-child {
    color: #6B7568;
    transition: transform 0.16s ease;
}

.ba-breakdown-section[open] .ba-breakdown-summary > i:last-child {
    transform: rotate(180deg);
}

.ba-breakdown-section .ba-breakdown-grid {
    padding: 0 0.72rem 0.72rem;
}

.ba-breakdown-card {
    overflow: hidden;
}

.ba-breakdown-list {
    display: flex;
    flex-direction: column;
}

.ba-breakdown-row {
    display: grid;
    grid-template-columns: minmax(120px, 1fr) auto auto auto;
    gap: 0.65rem;
    align-items: center;
    padding: 0.62rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
    color: #1e293b;
}

.ba-breakdown-row::after {
    content: "";
    grid-column: 1 / -1;
    height: 5px;
    border-radius: 999px;
    background:
        linear-gradient(90deg, var(--ba-breakdown-tone, #8EA083) var(--ba-breakdown-percent, 0%), rgba(142, 160, 131, 0.18) 0);
}

.ba-breakdown-row:last-child {
    border-bottom: 0;
}

.ba-breakdown-label {
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}

.ba-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #94a3b8;
    flex: 0 0 auto;
}

.ba-status-dot.status-positive {
    background: #059669;
}

.ba-status-dot.status-warning {
    background: #d97706;
}

.ba-status-dot.status-danger {
    background: #dc2626;
}

.ba-breakdown-count {
    color: #64748b;
    font-weight: 700;
}

.ba-breakdown-amount {
    font-weight: 800;
    text-align: right;
}

.ba-breakdown-percent {
    color: #64748b;
    font-weight: 800;
    text-align: right;
}

.ba-empty-state {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    align-items: center;
    padding: 1rem;
    margin: 0.3rem;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
}

.ba-empty-state strong {
    color: #334155;
}

.ba-empty-state span {
    color: #64748b;
    font-size: 11px;
}

.ba-cases-table {
    min-width: 1120px;
}

.ba-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 22px;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    border: 1px solid #dbe3ef;
    background: #f8fafc;
    color: #334155;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
}

.ba-status-badge.status-positive {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}

.ba-status-badge.status-warning {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #c2410c;
}

.ba-status-badge.status-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #b91c1c;
}

@media (max-width: 1100px) {
    .ba-kpi-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ba-breakdown-grid {
        grid-template-columns: 1fr;
    }

    .ba-drilldown-kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .ba-head-row-top,
    .ba-head-row-nav {
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .ba-tabs {
        width: 100%;
        flex-wrap: wrap;
    }

    .ba-workspace-filters {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .ba-workspace-chips {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .ba-chart-frame {
        height: 320px;
    }

    .ba-insight-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ba-branch-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ba-collection-grid {
        grid-template-columns: 1fr;
    }

    .ba-drilldown-kpis {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .ba-shell {
        padding-top: 1rem;
        gap: 0.8rem;
    }

    .ba-workspace {
        padding: 0.8rem;
    }

    .ba-workspace-chips {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .ba-kpi-strip {
        grid-template-columns: 1fr;
    }

    .ba-branch-kpi-grid {
        grid-template-columns: 1fr;
    }

    .ba-date-popover {
        right: 0;
        min-width: min(265px, calc(100vw - 2rem));
    }

    .ba-chart-frame {
        height: 300px;
    }

    .ba-insight-row {
        grid-template-columns: 1fr;
    }
}

html:not([data-theme='dark']) .ba-shell {
    color: var(--ink);
    background: var(--surface);
}

html:not([data-theme='dark']) .ba-card,
html:not([data-theme='dark']) .ba-kpi-tile,
html:not([data-theme='dark']) .ba-chart-frame,
html:not([data-theme='dark']) .ba-compare-card,
html:not([data-theme='dark']) .ba-drilldown,
html:not([data-theme='dark']) .ba-insight-card,
html:not([data-theme='dark']) .ba-drill-kpi {
    background: var(--card);
    border-color: var(--border);
    box-shadow: var(--shadow-sm);
}

html:not([data-theme='dark']) .ba-title,
html:not([data-theme='dark']) .ba-panel-title,
html:not([data-theme='dark']) .ba-kpi-value,
html:not([data-theme='dark']) .ba-insight-value,
html:not([data-theme='dark']) .ba-collection-metric strong,
html:not([data-theme='dark']) .ba-branch-kpi-card strong,
html:not([data-theme='dark']) .ba-compare-table td,
html:not([data-theme='dark']) .ba-context-chip,
html:not([data-theme='dark']) .ba-breakdown-row {
    color: var(--ink);
}

html:not([data-theme='dark']) .ba-filter-label,
html:not([data-theme='dark']) .ba-subtitle,
html:not([data-theme='dark']) .ba-panel-subtitle,
html:not([data-theme='dark']) .ba-panel-note,
html:not([data-theme='dark']) .ba-chart-helper,
html:not([data-theme='dark']) .ba-kpi-label,
html:not([data-theme='dark']) .ba-insight-label,
html:not([data-theme='dark']) .ba-insight-note,
html:not([data-theme='dark']) .ba-context-muted,
html:not([data-theme='dark']) .ba-pop-field-label,
html:not([data-theme='dark']) .ba-pop-label,
html:not([data-theme='dark']) .ba-compare-table th,
html:not([data-theme='dark']) .ba-empty-cell,
html:not([data-theme='dark']) .ba-collection-metric span,
html:not([data-theme='dark']) .ba-branch-kpi-card span,
html:not([data-theme='dark']) .ba-breakdown-count,
html:not([data-theme='dark']) .ba-breakdown-percent,
html:not([data-theme='dark']) .ba-empty-state span {
    color: var(--ink-muted);
}

html:not([data-theme='dark']) .ba-seg-item,
html:not([data-theme='dark']) .ba-date-btn,
html:not([data-theme='dark']) .ba-date-clear,
html:not([data-theme='dark']) .ba-date-popover,
html:not([data-theme='dark']) .ba-branch-select-wrap,
html:not([data-theme='dark']) .ba-tab-btn,
html:not([data-theme='dark']) .ba-pop-input,
html:not([data-theme='dark']) .ba-pop-reset,
html:not([data-theme='dark']) .ba-panel-note,
html:not([data-theme='dark']) .ba-context-chip,
html:not([data-theme='dark']) .ba-compare-title,
html:not([data-theme='dark']) .ba-drilldown-actionbar,
html:not([data-theme='dark']) .ba-clear-filters,
html:not([data-theme='dark']) .ba-row-filter-btn,
html:not([data-theme='dark']) .ba-collection-metric,
html:not([data-theme='dark']) .ba-branch-kpi-card,
html:not([data-theme='dark']) .ba-empty-state,
html:not([data-theme='dark']) .ba-status-badge {
    background: var(--surface-muted);
    border-color: var(--border);
    color: var(--ink-muted);
}

html:not([data-theme='dark']) .ba-seg-item:hover,
html:not([data-theme='dark']) .ba-date-btn:hover,
html:not([data-theme='dark']) .ba-tab-btn:hover,
html:not([data-theme='dark']) .ba-pop-reset:hover,
html:not([data-theme='dark']) .ba-clear-filters:hover,
html:not([data-theme='dark']) .ba-row-filter-btn:hover {
    background: var(--card);
    border-color: var(--border-strong);
    color: var(--ink);
}

html:not([data-theme='dark']) .ba-seg-item.active,
html:not([data-theme='dark']) .ba-date-btn.active,
html:not([data-theme='dark']) .ba-tab-btn.active,
html:not([data-theme='dark']) .ba-pop-apply,
html:not([data-theme='dark']) .ba-master-records-btn,
html:not([data-theme='dark']) .ba-ranking-row.is-selected .ba-row-filter-btn {
    background: var(--brand);
    border-color: var(--brand);
    color: #fff;
    box-shadow: 0 8px 20px rgba(62, 74, 61, 0.18);
}

html:not([data-theme='dark']) .ba-master-records-btn:hover {
    background: var(--brand-hover);
}

html:not([data-theme='dark']) .ba-chart-frame:hover {
    border-color: var(--border-strong);
    box-shadow: var(--shadow-md);
}

html:not([data-theme='dark']) .ba-compare-table th,
html:not([data-theme='dark']) .ba-compare-title {
    background: var(--surface-muted);
}

html:not([data-theme='dark']) .ba-compare-table td,
html:not([data-theme='dark']) .ba-compare-table th,
html:not([data-theme='dark']) .ba-breakdown-row {
    border-bottom-color: var(--border);
}

html:not([data-theme='dark']) .ba-compare-table tbody tr:hover {
    background: rgba(139, 154, 139, 0.12);
}

html:not([data-theme='dark']) .ba-ranking-row.is-selected {
    background: rgba(139, 154, 139, 0.16);
    box-shadow: inset 3px 0 0 var(--brand);
}

html:not([data-theme='dark']) .ba-ranking-row.is-selected:hover {
    background: rgba(139, 154, 139, 0.20);
}

html:not([data-theme='dark']) .ba-insight-card.tone-blue,
html:not([data-theme='dark']) .ba-kpi-revenue {
    background: rgba(139, 154, 139, 0.14);
    border-color: rgba(139, 154, 139, 0.34);
}

html:not([data-theme='dark']) .ba-insight-card.tone-green,
html:not([data-theme='dark']) .ba-kpi-outstanding.is-zero,
html:not([data-theme='dark']) .ba-paid,
html:not([data-theme='dark']) .ba-status-badge.status-positive,
html:not([data-theme='dark']) .ba-active-filter-chip.filter-positive {
    background: rgba(111, 138, 109, 0.14);
    border-color: rgba(111, 138, 109, 0.35);
    color: #4F6F4D;
}

html:not([data-theme='dark']) .ba-insight-card.tone-amber,
html:not([data-theme='dark']) .ba-kpi-outstanding,
html:not([data-theme='dark']) .ba-partial,
html:not([data-theme='dark']) .ba-status-badge.status-warning,
html:not([data-theme='dark']) .ba-active-filter-chip.filter-warning {
    background: rgba(184, 121, 86, 0.14);
    border-color: rgba(184, 121, 86, 0.30);
    color: var(--warning);
}

html:not([data-theme='dark']) .ba-insight-card.tone-red,
html:not([data-theme='dark']) .ba-unpaid,
html:not([data-theme='dark']) .ba-status-badge.status-danger,
html:not([data-theme='dark']) .ba-active-filter-chip.filter-danger {
    background: rgba(158, 75, 63, 0.12);
    border-color: rgba(158, 75, 63, 0.26);
    color: var(--danger);
}

html:not([data-theme='dark']) .ba-ongoing,
html:not([data-theme='dark']) .ba-active-filter-chip,
html:not([data-theme='dark']) .ba-global-filterbar,
html:not([data-theme='dark']) .ba-tab-context {
    background: rgba(139, 154, 139, 0.14);
    border-color: rgba(139, 154, 139, 0.34);
    color: var(--brand);
}

html:not([data-theme='dark']) .ba-status-dot.status-positive {
    background: #6F8A6D;
}

html:not([data-theme='dark']) .ba-status-dot.status-warning {
    background: #B87956;
}

html:not([data-theme='dark']) .ba-status-dot.status-danger {
    background: #9E4B3F;
}

html[data-theme='dark'] .ba-shell {
    color: #e2ecf9;
    background: #102033;
}

html[data-theme='dark'] .ba-card,
html[data-theme='dark'] .ba-kpi-tile,
html[data-theme='dark'] .ba-chart-frame,
html[data-theme='dark'] .ba-compare-card,
html[data-theme='dark'] .ba-drilldown {
    background: #17283b;
    border-color: #2e4560;
}

html[data-theme='dark'] .ba-controls {
    background: linear-gradient(180deg, #17283b 0%, #152335 100%);
}

html[data-theme='dark'] .ba-filter-label,
html[data-theme='dark'] .ba-subtitle,
html[data-theme='dark'] .ba-panel-subtitle,
html[data-theme='dark'] .ba-panel-note,
html[data-theme='dark'] .ba-kpi-label,
html[data-theme='dark'] .ba-context-muted,
html[data-theme='dark'] .ba-pop-field-label,
html[data-theme='dark'] .ba-pop-label,
html[data-theme='dark'] .ba-compare-table th,
html[data-theme='dark'] .ba-empty-cell,
html[data-theme='dark'] .ba-collection-metric span,
html[data-theme='dark'] .ba-branch-kpi-card span {
    color: #8ca6c4;
}

html[data-theme='dark'] .ba-title,
html[data-theme='dark'] .ba-panel-title,
html[data-theme='dark'] .ba-kpi-value,
html[data-theme='dark'] .ba-collection-metric strong,
html[data-theme='dark'] .ba-branch-kpi-card strong,
html[data-theme='dark'] .ba-compare-table td,
html[data-theme='dark'] .ba-context-chip {
    color: #e2ecf9;
}

html[data-theme='dark'] .ba-context-chip,
html[data-theme='dark'] .ba-panel-note,
html[data-theme='dark'] .ba-date-clear,
html[data-theme='dark'] .ba-date-popover,
html[data-theme='dark'] .ba-branch-select-wrap,
html[data-theme='dark'] .ba-tab-btn,
html[data-theme='dark'] .ba-seg-item,
html[data-theme='dark'] .ba-date-btn,
html[data-theme='dark'] .ba-pop-input,
html[data-theme='dark'] .ba-pop-reset,
html[data-theme='dark'] .ba-collection-metric,
html[data-theme='dark'] .ba-branch-kpi-card,
html[data-theme='dark'] .ba-compare-title,
html[data-theme='dark'] .ba-drilldown-actionbar,
html[data-theme='dark'] .ba-clear-filters {
    background: #1a2f46;
    border-color: #2e4560;
    color: #cfe0f5;
}

html[data-theme='dark'] .ba-drilldown {
    border-color: #2e4560;
}

html[data-theme='dark'] .ba-drilldown.has-active-filter {
    background: linear-gradient(180deg, #142a43 0%, #17283b 100%);
    border-color: #315f9b;
    box-shadow: 0 10px 28px rgba(30, 64, 175, 0.16);
}

html[data-theme='dark'] .ba-breakdown-row {
    border-bottom-color: #2e4560;
    color: #e2ecf9;
}

html[data-theme='dark'] .ba-ranking-row.is-selected {
    background: #16365f;
    box-shadow: inset 3px 0 0 #60a5fa;
}

html[data-theme='dark'] .ba-row-filter-btn {
    background: #1a2f46;
    border-color: #2e4560;
    color: #cfe0f5;
}

html[data-theme='dark'] .ba-ranking-row.is-selected .ba-row-filter-btn {
    background: #e2ecf9;
    border-color: #e2ecf9;
    color: #10253a;
}

html[data-theme='dark'] .ba-active-filter-empty,
html[data-theme='dark'] .ba-breakdown-count,
html[data-theme='dark'] .ba-breakdown-percent,
html[data-theme='dark'] .ba-empty-state span {
    color: #8ca6c4;
}

html[data-theme='dark'] .ba-empty-state strong {
    color: #e2ecf9;
}

html[data-theme='dark'] .ba-insight-card {
    background: #1a2f46;
    border-color: #2e4560;
}

html[data-theme='dark'] .ba-insight-value {
    color: #e2ecf9;
}

html[data-theme='dark'] .ba-insight-label,
html[data-theme='dark'] .ba-insight-note {
    color: #8ca6c4;
}

html[data-theme='dark'] .ba-insight-card.tone-blue {
    background: #16365f;
    border-color: #315f9b;
}

html[data-theme='dark'] .ba-insight-card.tone-green {
    background: #0b3b2b;
    border-color: #047857;
}

html[data-theme='dark'] .ba-insight-card.tone-amber {
    background: #432a11;
    border-color: #c2410c;
}

html[data-theme='dark'] .ba-insight-card.tone-red {
    background: #4a1515;
    border-color: #b91c1c;
}

html[data-theme='dark'] .ba-active-filter-chip {
    background: #16365f;
    color: #93c5fd;
    border-color: #315f9b;
}

html[data-theme='dark'] .ba-active-filter-chip.filter-positive,
html[data-theme='dark'] .ba-status-badge.status-positive {
    background: #0b3b2b;
    color: #6ee7b7;
    border-color: #047857;
}

html[data-theme='dark'] .ba-active-filter-chip.filter-warning,
html[data-theme='dark'] .ba-status-badge.status-warning {
    background: #432a11;
    color: #fdba74;
    border-color: #c2410c;
}

html[data-theme='dark'] .ba-active-filter-chip.filter-danger,
html[data-theme='dark'] .ba-status-badge.status-danger {
    background: #4a1515;
    color: #fca5a5;
    border-color: #b91c1c;
}

html[data-theme='dark'] .ba-global-filterbar,
html[data-theme='dark'] .ba-tab-context {
    background: #16365f;
    border-color: #315f9b;
    color: #93c5fd;
}

html[data-theme='dark'] .ba-global-filter-label {
    color: #cfe0f5;
}

html[data-theme='dark'] .ba-global-clear,
html[data-theme='dark'] .ba-tab-context button {
    background: #1a2f46;
    border-color: #2e4560;
    color: #cfe0f5;
}

html[data-theme='dark'] .ba-tab-btn.active,
html[data-theme='dark'] .ba-seg-item.active,
html[data-theme='dark'] .ba-date-btn.active {
    background: #e2ecf9;
    color: #10253a;
    border-color: #e2ecf9;
}

html[data-theme='dark'] .ba-compare-table th {
    background: #1f344d;
}

html[data-theme='dark'] .ba-head-row-nav {
    border-bottom-color: #2e4560;
}

/* Botanical Operations Console alignment for Owner Branch Analytics. */
.ba-shell {
    padding: 1rem var(--panel-content-inline, 1.5rem) 2rem;
    gap: 1rem;
    color: var(--ink);
    background: var(--surface);
}

.ba-card,
.ba-workspace,
.ba-chart-frame,
.ba-compare-card,
.ba-drilldown,
.ba-insight-card,
.ba-branch-kpi-card,
.ba-summary-card {
    border-color: var(--border);
    border-radius: var(--radius-ops);
    background: var(--card);
    box-shadow: var(--shadow-sm);
}

.ba-workspace {
    padding: 1rem;
    gap: 1rem;
}

.ba-workspace-head {
    gap: 0.55rem;
}

.ba-head-row-top {
    justify-content: center;
    padding: 0.15rem 0 0.25rem;
    border: 0;
    border-radius: 0;
    background: transparent;
}

.ba-filter-row {
    justify-content: space-between;
    padding: 0.62rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-ops);
    background: var(--surface-muted);
    gap: 0.75rem;
}

.ba-head-row-nav {
    justify-content: flex-end;
    padding-bottom: 0;
    border-bottom: 0;
    display: none;
}

.ba-title {
    font-family: var(--font-heading);
    font-size: 1.22rem;
    font-weight: 700;
    color: var(--ink);
}

.ba-subtitle,
.ba-panel-subtitle,
.ba-panel-note,
.ba-chart-helper,
.ba-filter-label,
.ba-branch-kpi-card span,
.ba-summary-card span,
.ba-insight-label,
.ba-insight-note,
.ba-compare-table th {
    color: var(--ink-muted);
}

.ba-head-row-nav {
    border-bottom-color: var(--border);
}

.ba-workspace-filters {
    width: auto;
    flex: 1 1 auto;
    flex-wrap: wrap;
    justify-content: flex-start;
}

.ba-tabs {
    width: auto;
    flex: 0 1 auto;
    min-width: 0;
    overflow-x: auto;
    scrollbar-width: thin;
    justify-content: center;
    padding-inline: 0.25rem;
}

.ba-tab-btn,
.ba-seg-item,
.ba-branch-select-wrap,
.ba-period-select-wrap,
.ba-filter-clear {
    min-height: 38px;
    height: 38px;
}

.ba-tab-btn,
.ba-seg-item {
    padding-inline: 0.78rem;
}

.ba-tab-btn {
    position: relative;
    border-color: transparent;
    background: transparent;
    border-radius: 0;
    color: var(--ink-muted);
    box-shadow: none;
}

.ba-tab-btn::after {
    content: "";
    position: absolute;
    left: 0.75rem;
    right: 0.75rem;
    bottom: 0;
    height: 2px;
    border-radius: 999px;
    background: transparent;
}

.ba-branch-select-wrap {
    min-width: 168px;
    max-width: 210px;
}

.ba-period-form {
    margin: 0;
}

.ba-period-select-wrap {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    box-sizing: border-box;
    border: 1px solid var(--border);
    border-radius: var(--radius-ops);
    background: var(--surface-muted);
    padding: 0 0.42rem;
    min-width: 160px;
}

.ba-period-select-wrap i {
    font-size: 14px;
    color: var(--ink-muted);
}

.ba-period-select {
    flex: 1 1 auto;
    min-width: 0;
    height: 100%;
    border: 0;
    background: transparent;
    color: var(--ink-muted);
    font-size: 12px;
    font-weight: 700;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.ba-filter-clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.38rem;
    flex: 0 0 auto;
    padding: 0 0.75rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-ops);
    background: var(--surface-muted);
    color: var(--ink-muted);
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}

.ba-filter-clear:hover {
    background: var(--card);
    border-color: var(--border-strong);
    color: var(--brand);
}

.ba-workspace-chips {
    width: 100%;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.ba-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.65rem;
}

.ba-summary-card {
    min-height: 82px;
    padding: 0.72rem;
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    background: var(--surface-panel);
}

.ba-summary-card:nth-child(2) {
    background: rgba(139, 154, 139, 0.14);
}

.ba-summary-card:nth-child(3) {
    background: rgba(139, 154, 139, 0.18);
}

.ba-summary-card-warning {
    background: rgba(184, 121, 86, 0.12);
}

.ba-summary-card-wide {
    grid-column: span 2;
}

.ba-summary-icon {
    width: 34px;
    height: 34px;
    border-radius: var(--radius-ops);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    color: var(--brand);
    background: rgba(139, 154, 139, 0.16);
    border: 1px solid rgba(139, 154, 139, 0.32);
}

.ba-summary-card-warning .ba-summary-icon {
    color: var(--warning);
    background: rgba(184, 121, 86, 0.14);
    border-color: rgba(184, 121, 86, 0.28);
}

.ba-summary-card span {
    display: block;
    margin-bottom: 0.18rem;
    font-size: 11px;
    font-weight: 700;
}

.ba-summary-card strong {
    display: block;
    color: var(--ink);
    font-size: 0.95rem;
    font-weight: 800;
    line-height: 1.25;
}

.ba-branch-ranking-card {
    order: 1;
}

.ba-insights-hidden {
    display: none;
}

.ba-branch-comparison-chart {
    order: 2;
}

.ba-seg-item,
.ba-branch-select-wrap,
.ba-tab-btn,
.ba-context-chip,
.ba-panel-note,
.ba-pop-input,
.ba-pop-apply,
.ba-pop-reset,
.ba-row-filter-btn,
.ba-clear-filters,
.ba-master-records-btn,
.ba-global-clear {
    border-radius: var(--radius-ops);
}

.ba-seg-item,
.ba-branch-select-wrap,
.ba-tab-btn,
.ba-context-chip,
.ba-panel-note,
.ba-pop-input,
.ba-pop-reset,
.ba-row-filter-btn,
.ba-clear-filters,
.ba-global-clear {
    background: var(--surface-muted);
    border-color: var(--border);
    color: var(--ink-muted);
}

.ba-seg-item:hover,
.ba-tab-btn:hover,
.ba-pop-reset:hover,
.ba-clear-filters:hover,
.ba-row-filter-btn:hover,
.ba-global-clear:hover {
    background: var(--card);
    border-color: var(--border-strong);
    color: var(--ink);
    box-shadow: none;
}

.ba-tab-btn:hover {
    background: transparent;
    border-color: transparent;
    color: var(--brand);
}

.ba-row-filter-btn,
.ba-global-clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
}

.ba-seg-item.active,
.ba-pop-apply,
.ba-master-records-btn,
.ba-ranking-row.is-selected .ba-row-filter-btn {
    background: var(--brand);
    border-color: var(--brand);
    color: #fff;
    box-shadow: none;
}

.ba-tab-btn.active {
    background: transparent;
    border-color: transparent;
    color: var(--brand);
    box-shadow: none;
}

.ba-tab-btn.active::after {
    background: var(--brand);
}

html:not([data-theme='dark']) .ba-tab-btn.active,
html[data-theme='dark'] .ba-tab-btn.active {
    background: transparent;
    border-color: transparent;
    color: var(--brand);
    box-shadow: none;
}

.ba-pop-apply:hover,
.ba-master-records-btn:hover {
    background: var(--brand-hover);
    border-color: var(--brand-hover);
}

.ba-global-filterbar,
.ba-tab-context,
.ba-drilldown.has-active-filter {
    background: rgba(139, 154, 139, 0.14);
    border-color: rgba(139, 154, 139, 0.34);
    box-shadow: none;
    color: var(--brand);
}

.ba-drilldown:not(.has-active-filter) {
    display: none;
}

.ba-chart-frame {
    position: relative;
    height: 340px;
    padding: 0.9rem;
    overflow: hidden;
}

.ba-chart-frame:hover {
    border-color: var(--border-strong);
    box-shadow: var(--shadow-sm);
}

.ba-chart-frame canvas {
    position: relative;
    z-index: 2;
}

.ba-chart-empty {
    position: absolute;
    inset: 0.9rem;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    text-align: center;
    color: var(--ink-muted);
    pointer-events: none;
}

.ba-chart-empty i {
    font-size: 1.35rem;
    color: var(--info);
}

.ba-chart-empty strong {
    color: var(--ink);
    font-size: 13px;
}

.ba-chart-empty span {
    max-width: 280px;
    font-size: 11px;
}

.ba-insight-card.tone-blue,
.ba-kpi-revenue,
.ba-active-filter-chip {
    background: rgba(139, 154, 139, 0.14);
    border-color: rgba(139, 154, 139, 0.34);
    color: var(--brand);
}

.ba-insight-card.tone-green,
.ba-paid,
.ba-status-badge.status-positive,
.ba-active-filter-chip.filter-positive,
.ba-kpi-outstanding.is-zero {
    background: rgba(139, 154, 139, 0.16);
    border-color: rgba(139, 154, 139, 0.36);
    color: var(--brand);
}

.ba-insight-card.tone-amber,
.ba-partial,
.ba-status-badge.status-warning,
.ba-active-filter-chip.filter-warning,
.ba-kpi-outstanding {
    background: rgba(184, 121, 86, 0.14);
    border-color: rgba(184, 121, 86, 0.30);
    color: var(--warning);
}

.ba-insight-card.tone-red,
.ba-unpaid,
.ba-status-badge.status-danger,
.ba-active-filter-chip.filter-danger {
    background: rgba(158, 75, 63, 0.12);
    border-color: rgba(158, 75, 63, 0.26);
    color: var(--danger);
}

.ba-ongoing {
    background: var(--surface-muted);
    border-color: var(--border);
    color: var(--ink-muted);
}

.ba-compare-title,
.ba-compare-table th {
    background: var(--surface-muted);
    color: var(--ink-muted);
    text-transform: none;
    letter-spacing: 0;
}

.ba-compare-title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.ba-compare-table td,
.ba-compare-table th,
.ba-breakdown-row {
    border-bottom-color: var(--border);
}

.ba-compare-table tbody tr:hover {
    background: rgba(139, 154, 139, 0.10);
}

.ba-ranking-row.is-selected {
    background: rgba(139, 154, 139, 0.16);
    box-shadow: inset 3px 0 0 var(--brand);
}

.ba-ranking-row.is-selected:hover {
    background: rgba(139, 154, 139, 0.20);
}

.ba-empty-state,
.ba-empty-cell {
    color: var(--ink-muted);
}

@media (max-width: 1280px) {
    .ba-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .ba-shell {
        padding-inline: 0.85rem;
    }

    .ba-summary-grid,
    .ba-branch-kpi-grid,
    .ba-drilldown-kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ba-summary-card,
    .ba-summary-card-wide {
        grid-column: auto;
    }

    .ba-summary-card {
        min-height: 76px;
        padding: 0.62rem;
    }

    .ba-summary-icon {
        display: none;
    }

    .ba-summary-card strong {
        font-size: 0.88rem;
    }

    .ba-tabs,
    .ba-seg {
        overflow-x: auto;
        padding-bottom: 0.15rem;
    }

    .ba-chart-frame {
        height: 290px;
    }
}

@media (max-width: 480px) {
    .ba-summary-grid,
    .ba-branch-kpi-grid,
    .ba-drilldown-kpis {
        grid-template-columns: 1fr;
    }
}

/* Readability and hierarchy polish. */
.ba-shell {
    background: linear-gradient(180deg, var(--surface) 0%, var(--surface-panel) 100%);
}

.ba-workspace {
    background: var(--records-card-alt);
    border-color: var(--border-strong);
}

.ba-filter-row {
    background: var(--records-card);
}

.ba-summary-card {
    padding: 0.9rem;
    background: var(--surface-panel);
    border-color: var(--border-strong);
}

.ba-summary-card:nth-child(1) {
    background: linear-gradient(180deg, var(--surface-panel) 0%, var(--records-card-alt) 100%);
}

.ba-summary-card:nth-child(2),
.ba-summary-card:nth-child(3) {
    background: rgba(139, 154, 139, 0.20);
}

.ba-summary-card-warning {
    background: rgba(184, 121, 86, 0.16);
}

.ba-summary-card span,
.ba-branch-kpi-card span,
.ba-panel-note,
.ba-filter-label {
    font-size: 12px;
    font-weight: 800;
    color: var(--ink);
}

.ba-summary-card strong {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--ink);
}

.ba-panel-title {
    font-size: 1rem;
    font-weight: 800;
    color: var(--ink);
}

.ba-chart-frame {
    order: 1;
    height: 380px;
    padding: 1rem;
    background: var(--card);
    border-color: var(--border-strong);
}

.ba-compare-card {
    background: var(--card);
    border-color: var(--border-strong);
}

.ba-branch-ranking-card {
    order: 2;
}

.ba-compare-title,
.ba-compare-table th {
    background: var(--records-card-alt);
    color: var(--ink);
}

.ba-compare-title {
    font-size: 13px;
    font-weight: 800;
}

.ba-compare-table th {
    font-size: 12px;
    font-weight: 800;
}

.ba-compare-table td {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--ink);
}

.ba-tab-btn,
.ba-period-select,
.ba-branch-select,
.ba-filter-clear,
.ba-seg-item {
    font-size: 13px;
    font-weight: 800;
}

@media (max-width: 760px) {
    .ba-chart-frame {
        height: 320px;
    }
}

/* Single-page analytics command center. */
.ba-head-row-top {
    display: none;
}

.ba-panels {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    align-items: start;
}

.ba-panel {
    display: flex !important;
    min-width: 0;
    height: auto;
    padding: 0.75rem;
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-ops);
    background: var(--card);
    box-shadow: var(--shadow-sm);
    gap: 0.55rem;
}

#ba-panel-performance {
    display: contents !important;
}

#ba-panel-performance > .ba-panel-head {
    grid-column: 1 / -1;
    order: 0;
}

#ba-panel-performance > .ba-chart-frame {
    grid-column: span 1;
    order: 1;
}

#ba-panel-payment {
    order: 2;
}

#ba-panel-collection {
    order: 3;
}

#ba-panel-trend {
    order: 4;
}

#ba-panel-performance > .ba-compare-card {
    grid-column: 1 / -1;
    order: 8;
}

.ba-panel .ba-chart-frame,
#ba-panel-performance > .ba-chart-frame {
    width: 100%;
    height: 270px;
    box-shadow: none;
}

.ba-panel.is-expanded,
#ba-panel-performance > .ba-chart-frame.is-expanded {
    grid-column: 1 / -1;
}

.ba-panel.is-expanded .ba-chart-frame,
#ba-panel-performance > .ba-chart-frame.is-expanded {
    height: 520px;
}

.ba-panel.is-expanded .ba-expand-chart-btn,
#ba-panel-performance > .ba-panel-head:has(+ .ba-insights-hidden + .ba-chart-frame.is-expanded) .ba-expand-chart-btn {
    background: var(--brand);
    border-color: var(--brand);
    color: #fff;
}

.ba-panel .ba-status-pills,
.ba-panel .ba-chart-helper {
    margin-top: 0;
}

.ba-global-filterbar[hidden],
.ba-tab-context[hidden] {
    display: none !important;
}

.ba-panel-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.ba-panel-head {
    align-items: center;
    gap: 0.5rem;
}

.ba-status-pills {
    gap: 0.35rem;
}

.ba-status-pill {
    padding: 0.28rem 0.55rem;
    font-size: 11.5px;
}

.ba-chart-helper {
    font-size: 11.5px;
    line-height: 1.35;
}

.ba-expand-chart-btn {
    height: 30px;
    padding: 0 0.58rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-ops);
    background: var(--surface-muted);
    color: var(--ink);
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.ba-expand-chart-btn:hover {
    border-color: var(--border-strong);
    background: var(--records-card-alt);
    color: var(--brand);
}

@media (max-width: 1180px) {
    .ba-panels {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .ba-panels {
        grid-template-columns: 1fr;
    }

    #ba-panel-performance > .ba-chart-frame {
        grid-column: 1;
    }

    .ba-panel .ba-chart-frame,
    #ba-panel-performance > .ba-chart-frame {
        height: 260px;
    }

    .ba-panel.is-expanded .ba-chart-frame,
    #ba-panel-performance > .ba-chart-frame.is-expanded {
        height: 380px;
    }
}

/* Expanded modal and balanced 2x2 chart cards. */
.ba-performance-card,
.ba-panel {
    min-width: 0;
    padding: 0.75rem;
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-ops);
    background: var(--card);
    box-shadow: var(--shadow-sm);
}

.ba-performance-card {
    order: 1;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

#ba-panel-payment {
    order: 2;
}

#ba-panel-collection {
    order: 3;
}

#ba-panel-trend {
    order: 4;
}

#ba-panel-performance > .ba-panel-head {
    display: none;
}

#ba-panel-performance > .ba-compare-card {
    grid-column: 1 / -1;
    order: 9;
}

.ba-panel-head {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 0.5rem;
}

.ba-panel-title {
    grid-column: 2;
    text-align: center;
}

.ba-panel-actions {
    grid-column: 3;
    justify-content: flex-end;
}

.ba-performance-card .ba-chart-frame,
.ba-panel .ba-chart-frame {
    height: 300px;
}

.ba-chart-frame-narrow {
    max-width: none;
}

.ba-chart-frame-narrow canvas {
    display: block;
    max-width: 520px;
    margin: 0 auto;
}

.ba-global-filter-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.ba-filter-downcue {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: var(--brand);
    font-size: 12px;
    font-weight: 800;
}

.ba-chart-modal[hidden] {
    display: none;
}

.ba-chart-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    width: 100vw;
    height: 100vh;
    height: 100dvh;
    display: block;
    padding: 0;
    overflow: hidden;
    overscroll-behavior: contain;
}

.ba-chart-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(35, 43, 34, 0.52);
}

.ba-chart-modal__dialog {
    position: absolute;
    inset: 0;
    z-index: 1;
    width: 100vw;
    height: 100vh;
    height: 100dvh;
    min-height: 0;
    max-height: none;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 0;
    border-radius: 0;
    background: var(--card);
    box-shadow: none;
}

.ba-chart-modal__head {
    flex: 0 0 auto;
    z-index: 4;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--border);
    background: var(--records-card-alt);
}

.ba-chart-modal__head h3 {
    grid-column: 2;
    margin: 0;
    text-align: center;
    font-family: var(--font-heading);
    font-size: 1.12rem;
    font-weight: 800;
    color: var(--ink);
}

.ba-chart-modal__close {
    grid-column: 3;
    justify-self: end;
    width: 34px;
    height: 34px;
    border: 1px solid var(--border);
    border-radius: var(--radius-ops);
    background: var(--card);
    color: var(--ink);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.ba-chart-modal__body {
    flex: 1 1 auto;
    min-height: 0;
    padding: 0.9rem;
    background: var(--surface-panel);
    overflow-x: auto;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    overscroll-behavior: contain;
}

.ba-chart-modal__body.has-drilldown {
    overflow-x: auto;
    overflow-y: auto;
}

.ba-chart-modal__body.has-drilldown > .ba-chart-modal__chart-slot,
.ba-chart-modal__body.has-drilldown > .ba-modal-drilldown {
    min-width: 1240px;
}

.ba-chart-modal__chart-slot {
    flex: 0 0 auto;
    display: flex;
    min-height: 0;
    height: clamp(320px, 42dvh, 460px);
    overflow: visible;
}

.ba-chart-modal__body.has-drilldown .ba-chart-modal__chart-slot {
    height: clamp(240px, 32dvh, 340px);
}

.ba-chart-modal__chart-slot .ba-chart-frame,
.ba-chart-modal__chart-slot .ba-chart-frame.is-in-modal {
    flex: 1 1 auto;
    width: 100%;
    height: 100%;
    min-height: 0;
    margin: 0;
    padding: 0.65rem;
    overflow: hidden;
}

.ba-chart-modal__chart-slot .ba-chart-frame canvas {
    width: 100% !important;
    height: 100% !important;
    cursor: pointer;
}

.ba-modal-drilldown {
    flex: 0 0 auto;
    min-height: 0;
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-ops);
    background: var(--card);
    overflow: visible;
    display: flex;
    flex-direction: column;
}

.ba-modal-drilldown[hidden] {
    display: none;
}

.ba-modal-drilldown__head {
    flex: 0 0 auto;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.55rem 0.75rem;
    border-bottom: 1px solid var(--border);
    background: var(--records-card-alt);
}

.ba-modal-drilldown__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.12rem;
    color: var(--brand);
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
}

.ba-modal-drilldown__title {
    margin: 0;
    font-family: var(--font-heading);
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--ink);
}

.ba-modal-drilldown__subtitle {
    margin: 0.08rem 0 0;
    color: var(--text-muted);
    font-size: 0.8rem;
    font-weight: 650;
}

.ba-modal-drilldown__content {
    min-height: 0;
    padding: 0.65rem;
    display: grid;
    grid-template-rows: auto auto;
    gap: 0.6rem;
    overflow: visible;
}

.ba-modal-drilldown__kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.45rem;
}

.ba-modal-kpi {
    border: 1px solid var(--border);
    border-radius: var(--radius-ops-sm);
    background: var(--surface-muted);
    padding: 0.42rem 0.55rem;
}

.ba-modal-kpi span {
    display: block;
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 800;
}

.ba-modal-kpi strong {
    display: block;
    margin-top: 0.1rem;
    color: var(--ink);
    font-size: 0.9rem;
    font-weight: 900;
}

.ba-modal-drilldown .ba-compare-table-wrap {
    max-height: none;
    min-height: 0;
    flex: 1 1 auto;
    height: auto;
    overflow-x: visible;
    overflow-y: visible;
    scrollbar-gutter: stable;
}

.ba-modal-drilldown .ba-compare-card {
    min-height: 0;
    height: auto;
    display: flex;
    flex-direction: column;
    overflow: visible;
}

.ba-modal-drilldown .ba-compare-title {
    flex: 0 0 auto;
    padding: 0.48rem 0.65rem;
}

.ba-modal-drilldown .ba-compare-table {
    width: 100%;
    min-width: 1240px;
    table-layout: fixed;
}

.ba-modal-drilldown .ba-compare-table th,
.ba-modal-drilldown .ba-compare-table td {
    padding: 0.48rem 0.45rem;
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: normal;
    line-height: 1.25;
    font-size: 12px;
}

.ba-modal-drilldown .ba-compare-table th {
    position: sticky;
    top: 0;
    z-index: 2;
}

.ba-modal-drilldown .ba-compare-table th:nth-child(1),
.ba-modal-drilldown .ba-compare-table td:nth-child(1) {
    width: 7%;
}

.ba-modal-drilldown .ba-compare-table th:nth-child(2),
.ba-modal-drilldown .ba-compare-table td:nth-child(2) {
    width: 8%;
}

.ba-modal-drilldown .ba-compare-table th:nth-child(3),
.ba-modal-drilldown .ba-compare-table td:nth-child(3) {
    width: 15%;
}

.ba-modal-drilldown .ba-compare-table th:nth-child(4),
.ba-modal-drilldown .ba-compare-table td:nth-child(4),
.ba-modal-drilldown .ba-compare-table th:nth-child(5),
.ba-modal-drilldown .ba-compare-table td:nth-child(5) {
    width: 13%;
}

.ba-modal-drilldown .ba-compare-table th:nth-child(6),
.ba-modal-drilldown .ba-compare-table td:nth-child(6),
.ba-modal-drilldown .ba-compare-table th:nth-child(7),
.ba-modal-drilldown .ba-compare-table td:nth-child(7) {
    width: 10%;
}

.ba-modal-drilldown .ba-compare-table th:nth-child(8),
.ba-modal-drilldown .ba-compare-table td:nth-child(8),
.ba-modal-drilldown .ba-compare-table th:nth-child(9),
.ba-modal-drilldown .ba-compare-table td:nth-child(9) {
    width: 12%;
}

.ba-modal-drilldown .ba-status-badge {
    max-width: 100%;
    white-space: normal;
    overflow-wrap: anywhere;
}

@media (max-width: 760px) {
    .ba-panel-head,
    .ba-chart-modal__head {
        grid-template-columns: 1fr auto;
    }

    .ba-panel-title,
    .ba-chart-modal__head h3 {
        grid-column: 1;
        text-align: left;
    }

    .ba-panel-actions,
    .ba-chart-modal__close {
        grid-column: 2;
    }

    .ba-performance-card .ba-chart-frame,
    .ba-panel .ba-chart-frame {
        height: 270px;
    }

    .ba-chart-modal__dialog {
        width: 100vw;
        height: 100vh;
        height: 100dvh;
    }

    .ba-chart-modal__body {
        padding: 0.65rem;
    }

    .ba-chart-modal__body.has-drilldown {
        overflow: visible;
    }

    .ba-chart-modal__body.has-drilldown > .ba-chart-modal__chart-slot,
    .ba-chart-modal__body.has-drilldown > .ba-modal-drilldown {
        min-width: 1100px;
    }

    .ba-chart-modal__chart-slot .ba-chart-frame {
        height: 100%;
    }

    .ba-chart-modal__chart-slot {
        height: clamp(220px, 34dvh, 300px);
    }

    .ba-chart-modal__body.has-drilldown .ba-chart-modal__chart-slot {
        height: clamp(200px, 30dvh, 260px);
    }

    .ba-modal-drilldown__head {
        flex-direction: column;
    }

    .ba-modal-drilldown__kpis {
        grid-template-columns: repeat(4, minmax(120px, 1fr));
        overflow-x: auto;
    }

    .ba-modal-drilldown .ba-compare-table th,
    .ba-modal-drilldown .ba-compare-table td {
        padding: 0.42rem 0.35rem;
        font-size: 11px;
    }

    .ba-modal-drilldown .ba-compare-table {
        min-width: 1100px;
    }
}

.ba-shell {
    background: #f6f7f2;
}

.ba-workspace {
    max-width: 1440px;
    margin-inline: auto;
    border-radius: 10px;
    box-shadow: none;
}

.ba-page-intro {
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border);
}

.ba-title,
.ba-panel-title,
.ba-chart-modal__head h3 {
    letter-spacing: 0;
}

.ba-summary-card,
.ba-branch-kpi-card,
.ba-insight-card,
.ba-compare-card,
.ba-chart-frame {
    border-radius: var(--radius-ops);
    box-shadow: none;
}

.ba-summary-card {
    min-height: 86px;
    background: var(--card);
    border-color: var(--border);
}

.ba-summary-card strong {
    font-size: 1.04rem;
}

.ba-tabs {
    width: 100%;
    gap: 0.45rem;
    border-bottom: 1px solid var(--border);
    padding-inline: 0.35rem;
}

.ba-tab-btn {
    height: 42px;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    color: var(--ink-muted);
    padding-inline: 1rem;
}

.ba-tab-btn:hover,
.ba-tab-btn.active {
    background: transparent;
    box-shadow: none;
    color: var(--brand);
}

.ba-tab-btn.active::after {
    left: 1rem;
    right: 1rem;
    height: 3px;
    background: var(--brand);
}

.ba-panel-head {
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: end;
}

.ba-panel-title {
    grid-column: 1;
    text-align: left;
    font-size: 1rem;
}

.ba-panel-actions {
    grid-column: 2;
}

.ba-panel-note {
    border: 0;
    background: transparent;
    padding: 0;
    color: var(--text-muted);
}

.ba-chart-helper,
.ba-status-pills {
    display: none;
}

.ba-chart-frame {
    height: 340px;
    background: #fffdf8;
}

.ba-chart-frame:hover {
    border-color: var(--border-strong);
    box-shadow: none;
}

.ba-insights-hidden {
    display: grid;
}

.ba-insight-card {
    min-height: 76px;
    background: var(--card);
    border-color: var(--border);
}

.ba-insight-card.is-clickable:hover,
.ba-insight-card.is-clickable:active {
    transform: none;
    background: var(--surface-muted);
    box-shadow: none;
}

.ba-insight-card.tone-blue,
.ba-insight-card.tone-green,
.ba-insight-card.tone-amber,
.ba-insight-card.tone-red {
    background: var(--card);
}

.ba-insight-card.tone-blue {
    border-left: 3px solid #496458;
}

.ba-insight-card.tone-green {
    border-left: 3px solid #2f6f4e;
}

.ba-insight-card.tone-amber {
    border-left: 3px solid #9a6a2f;
}

.ba-insight-card.tone-red {
    border-left: 3px solid #a04747;
}

.ba-insight-hint {
    color: var(--brand);
    letter-spacing: 0;
}

.ba-compare-title {
    min-height: 40px;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.ba-compare-table-wrap {
    overflow-x: auto;
    scrollbar-gutter: stable;
}

.ba-compare-table {
    min-width: 980px;
}

.ba-compare-table th {
    position: sticky;
    top: 0;
    z-index: 2;
}

.ba-row-filter-btn,
.ba-expand-chart-btn,
.ba-clear-filters,
.ba-master-records-btn,
.ba-global-clear,
.ba-filter-clear,
.ba-chart-modal__close,
.ba-filter-chip-remove,
.ba-tab-btn,
.ba-period-select,
.ba-branch-select {
    outline-offset: 2px;
}

.ba-row-filter-btn:focus-visible,
.ba-expand-chart-btn:focus-visible,
.ba-clear-filters:focus-visible,
.ba-master-records-btn:focus-visible,
.ba-global-clear:focus-visible,
.ba-filter-clear:focus-visible,
.ba-chart-modal__close:focus-visible,
.ba-filter-chip-remove:focus-visible,
.ba-tab-btn:focus-visible,
.ba-period-select:focus-visible,
.ba-branch-select:focus-visible {
    outline: 3px solid rgba(62, 74, 61, 0.28);
    outline-offset: 2px;
}

.ba-chart-modal__head {
    min-height: 64px;
    grid-template-columns: minmax(0, 1fr) auto;
}

.ba-chart-modal__head h3 {
    grid-column: 1;
    text-align: left;
    font-size: 1rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ba-chart-modal__close {
    grid-column: 2;
    width: 40px;
    height: 40px;
}

.ba-chart-modal__body {
    padding: 1rem;
    gap: 1rem;
}

.ba-chart-modal__body.has-drilldown {
    overflow-x: hidden;
    overflow-y: auto;
}

.ba-chart-modal__body.has-drilldown > .ba-chart-modal__chart-slot,
.ba-chart-modal__body.has-drilldown > .ba-modal-drilldown {
    min-width: 0;
    width: 100%;
}

.ba-chart-modal__chart-slot {
    height: clamp(360px, 50dvh, 560px);
}

.ba-chart-modal__body.has-drilldown .ba-chart-modal__chart-slot {
    height: clamp(280px, 38dvh, 420px);
}

.ba-modal-drilldown {
    border-radius: var(--radius-ops);
}

.ba-modal-drilldown__head {
    position: sticky;
    top: -1rem;
    z-index: 3;
}

.ba-modal-drilldown__content {
    overflow: hidden;
}

.ba-modal-drilldown .ba-compare-table-wrap {
    overflow-x: auto;
    overflow-y: visible;
}

.ba-modal-drilldown .ba-compare-table th {
    top: 0;
    box-shadow: inset 0 -1px 0 var(--border);
}

@media (max-width: 760px) {
    .ba-tabs {
        overflow-x: auto;
        scrollbar-width: thin;
    }

    .ba-tab-btn {
        min-width: max-content;
    }

    .ba-panel-head,
    .ba-chart-modal__head {
        grid-template-columns: minmax(0, 1fr) auto;
    }

    .ba-chart-modal__body {
        padding: 0.75rem;
    }

    .ba-chart-modal__chart-slot {
        height: clamp(260px, 42dvh, 340px);
    }

    .ba-chart-modal__body.has-drilldown {
        overflow-x: auto;
        overflow-y: auto;
    }
}

.ba-chart-modal__head {
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: 0.75rem;
}

.ba-chart-modal__workspace {
    grid-column: 2;
    justify-self: end;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
}

.ba-chart-modal__view {
    height: 34px;
    padding: 0 0.75rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-ops-sm);
    background: var(--card);
    color: var(--ink-muted);
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.ba-chart-modal__view.is-active {
    background: var(--brand);
    border-color: var(--brand);
    color: #fff;
}

.ba-chart-modal__view:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.ba-chart-modal__record-count {
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 750;
    white-space: nowrap;
}

.ba-chart-modal__close {
    grid-column: 3;
}

.ba-chart-modal__body {
    scroll-padding-top: 1rem;
}

.ba-chart-modal__body[data-view="records"] .ba-chart-modal__chart-slot {
    display: none;
}

.ba-chart-modal__body.has-drilldown[data-view="chart"] .ba-chart-modal__chart-slot {
    height: clamp(300px, 44dvh, 480px);
    min-height: 300px;
}

.ba-chart-modal__body.has-drilldown[data-view="chart"] .ba-modal-drilldown {
    display: flex;
}

.ba-chart-modal__body.has-drilldown[data-view="records"] .ba-modal-drilldown {
    display: flex;
}

.ba-modal-drilldown .ba-compare-title {
    justify-content: space-between;
}

.ba-table-scroll-hint {
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 750;
    white-space: nowrap;
}

.ba-modal-drilldown .ba-compare-table th:first-child,
.ba-modal-drilldown .ba-compare-table td:first-child {
    position: sticky;
    left: 0;
    z-index: 3;
    background: var(--card);
    box-shadow: inset -1px 0 0 var(--border);
}

.ba-modal-drilldown .ba-compare-table th:first-child {
    z-index: 4;
    background: var(--records-card-alt);
}

@media (max-width: 760px) {
    .ba-chart-modal__head {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: start;
    }

    .ba-chart-modal__workspace {
        grid-column: 1 / -1;
        justify-self: stretch;
        order: 3;
        overflow-x: auto;
        padding-top: 0.25rem;
    }

    .ba-chart-modal__view {
        min-width: max-content;
    }

    .ba-chart-modal__record-count {
        margin-left: auto;
    }

    .ba-chart-modal__body.has-drilldown[data-view="chart"] .ba-chart-modal__chart-slot {
        height: clamp(240px, 38dvh, 340px);
        min-height: 240px;
    }
}

.ba-panels {
    display: block !important;
}

.ba-panel,
.ba-panel[hidden],
.ba-panel:not(.active) {
    display: none !important;
}

.ba-panel {
    scroll-margin-top: 1rem;
}

.ba-panel.active:not([hidden]) {
    display: flex !important;
    width: 100%;
}

.ba-performance-card {
    order: 1 !important;
}

.ba-performance-card > .ba-panel-head,
.ba-panel.active > .ba-panel-head,
.ba-panel.active > .ba-branch-perf-head {
    order: 0;
}

.ba-performance-card > .ba-chart-frame,
.ba-panel.active > .ba-chart-frame {
    order: 1 !important;
}

.ba-performance-card > .ba-insight-row,
.ba-panel.active > .ba-insight-row,
.ba-panel.active > .ba-branch-kpi-grid {
    order: 2 !important;
}

.ba-panel.active > .ba-compare-card,
.ba-branch-ranking-card {
    order: 3 !important;
}

#ba-panel-performance,
#ba-panel-payment,
#ba-panel-collection,
#ba-panel-trend {
    grid-column: auto !important;
}

#ba-panel-performance[hidden],
#ba-panel-payment[hidden],
#ba-panel-collection[hidden],
#ba-panel-trend[hidden],
#ba-panel-performance:not(.active),
#ba-panel-payment:not(.active),
#ba-panel-collection:not(.active),
#ba-panel-trend:not(.active) {
    display: none !important;
}

#ba-panel-performance.active:not([hidden]),
#ba-panel-payment.active:not([hidden]),
#ba-panel-collection.active:not([hidden]),
#ba-panel-trend.active:not([hidden]) {
    display: flex !important;
}

.ba-head-row-top {
    display: flex !important;
    align-items: center;
    justify-content: center;
    width: 100%;
    order: 0;
    margin-top: 0.15rem;
    padding: 0.45rem 0.5rem 0.35rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-ops);
    background: var(--surface-muted);
}

.ba-workspace-head {
    position: relative;
    z-index: 2;
    background: var(--card);
    padding-top: 0;
}

.ba-head-row-top .ba-tabs {
    display: flex !important;
    justify-content: center;
    width: 100%;
    gap: 0.55rem;
    padding-inline: 0.45rem;
    border-bottom: 0;
    background: transparent;
}

.ba-head-row-top .ba-tab-btn {
    border-radius: var(--radius-ops-sm);
}

.ba-head-row-top .ba-tab-btn.active {
    background: var(--card);
}

.ba-filter-row {
    order: 1;
}

.ba-head-row-nav {
    display: flex !important;
    order: 2;
    justify-content: flex-end;
    padding-top: 0.1rem;
    border-bottom: 0;
}

.ba-global-filterbar {
    margin-top: 0.15rem;
}

/* Owner visual-system consistency pass */
.ba-shell {
    --ba-card: var(--records-card, #D3DEC9);
    --ba-card-alt: var(--records-card-alt, #DCE6D6);
    --ba-card-strong: var(--records-card-strong, #C7D5BE);
    --ba-card-warm: #E1DFCC;
    --ba-hover: var(--records-hover, #C5D3BC);
    --ba-active: var(--records-active, #B8C9AF);
    --ba-border: var(--records-border, #AEBCA5);
    --ba-text: var(--records-text, #2D342C);
    --ba-muted: var(--records-muted, #5F685F);
    font-family: var(--font-body);
    color: var(--ba-text);
    background: var(--surface);
}

.ba-workspace,
.ba-workspace-head,
.ba-card,
.ba-performance-card,
.ba-summary-card,
.ba-branch-kpi-card,
.ba-insight-card,
.ba-compare-card,
.ba-modal-drilldown,
.ba-modal-kpi {
    background: var(--ba-card) !important;
    border-color: var(--ba-border) !important;
    color: var(--ba-text) !important;
    box-shadow: none !important;
}

.ba-summary-card:nth-child(even),
.ba-branch-kpi-card:nth-child(even),
.ba-insight-card:nth-child(even),
.ba-modal-kpi:nth-child(even) {
    background: var(--ba-card-alt) !important;
}

.ba-summary-card-warning,
.ba-insight-card.tone-amber,
.ba-insight-card.tone-red {
    background: var(--ba-card-warm) !important;
}

.ba-title,
.ba-panel-title,
.ba-compare-title,
.ba-summary-card strong,
.ba-branch-kpi-card strong,
.ba-insight-value,
.ba-modal-kpi strong,
.ba-chart-modal__head h3 {
    font-family: var(--font-heading);
    color: #232821 !important;
    letter-spacing: 0;
}

.ba-subtitle,
.ba-filter-label,
.ba-panel-note,
.ba-summary-card span,
.ba-branch-kpi-card span,
.ba-insight-label,
.ba-insight-note,
.ba-global-filter-label,
.ba-context-chip,
.ba-modal-drilldown__eyebrow,
.ba-modal-drilldown__subtitle,
.ba-table-scroll-hint {
    color: var(--ba-muted) !important;
    opacity: 1;
}

.ba-head-row-top,
.ba-filter-row,
.ba-global-filterbar,
.ba-tab-context {
    background: var(--ba-card-alt) !important;
    border-color: var(--ba-border) !important;
    box-shadow: none !important;
}

.ba-chart-frame,
.ba-chart-empty,
.ba-date-popover,
.ba-chart-modal__dialog {
    background: var(--card) !important;
    border-color: var(--ba-border) !important;
    box-shadow: none !important;
}

.ba-chart-modal__head,
.ba-compare-title,
.ba-compare-table th,
.ba-modal-drilldown .ba-compare-table th:first-child {
    background: var(--ba-card-strong) !important;
    border-color: var(--ba-border) !important;
}

.ba-compare-table td,
.ba-compare-table th {
    border-color: var(--ba-border) !important;
    color: var(--ba-text) !important;
}

.ba-compare-table tbody tr:hover td {
    background: var(--ba-hover) !important;
}

.ba-seg-item,
.ba-date-btn,
.ba-date-clear,
.ba-pop-reset,
.ba-global-clear,
.ba-filter-clear,
.ba-row-filter-btn,
.ba-expand-chart-btn,
.ba-clear-filters,
.ba-master-records-btn,
.ba-period-select-wrap,
.ba-branch-select-wrap,
.ba-context-chip,
.ba-chart-modal__view,
.ba-chart-modal__close {
    background: var(--card) !important;
    border-color: var(--ba-border) !important;
    color: var(--ba-text) !important;
    box-shadow: none !important;
}

.ba-seg-item:hover,
.ba-date-btn:hover,
.ba-date-clear:hover,
.ba-pop-reset:hover,
.ba-global-clear:hover,
.ba-filter-clear:hover,
.ba-row-filter-btn:hover,
.ba-expand-chart-btn:hover,
.ba-clear-filters:hover,
.ba-master-records-btn:hover,
.ba-period-select-wrap:hover,
.ba-branch-select-wrap:hover,
.ba-chart-modal__view:hover,
.ba-chart-modal__close:hover,
.ba-insight-card.is-clickable:hover {
    background: var(--ba-hover) !important;
    border-color: #8EA083 !important;
    color: #232821 !important;
}

.ba-seg-item.active,
.ba-date-btn.active,
.ba-tab-btn.active,
.ba-chart-modal__view.is-active,
.ba-filter-chip {
    background: var(--ba-active) !important;
    border-color: var(--brand) !important;
    color: #232821 !important;
}

.ba-pop-apply,
.ba-tab-btn.active::after {
    background: var(--brand) !important;
}

.ba-pop-input,
.ba-branch-select,
.ba-period-select {
    color: var(--ba-text) !important;
    font-family: var(--font-body);
}

.ba-pop-input {
    background: var(--card) !important;
    border-color: var(--ba-border) !important;
}

.ba-shell {
    background-color: var(--surface);
    background-image:
        linear-gradient(90deg, rgba(73, 87, 69, 0.026) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73, 87, 69, 0.022) 0 1px, transparent 1px),
        repeating-linear-gradient(135deg, rgba(73, 87, 69, 0.014) 0 1px, transparent 1px 12px);
    background-size: 24px 24px, 24px 24px, 18px 18px;
    transition: background-color .22s ease, color .18s ease;
}

.ba-summary-card,
.ba-branch-kpi-card,
.ba-insight-card,
.ba-compare-card,
.ba-chart-frame,
.ba-tab-btn,
.ba-expand-chart-btn,
.ba-row-filter-btn,
.ba-global-clear,
.ba-filter-clear,
.ba-chart-modal__view,
.ba-chart-modal__close,
.ba-period-select-wrap,
.ba-branch-select-wrap {
    transition:
        background-color .18s ease,
        border-color .18s ease,
        color .18s ease,
        opacity .18s ease,
        transform .18s ease;
}

.ba-chart-modal {
    animation: baModalFadeIn .16s ease-out both;
}

.ba-chart-modal.is-closing {
    animation: baModalFadeOut .14s ease-in both;
}

.ba-chart-modal__dialog {
    animation: baModalSettle .18s ease-out both;
}

.ba-chart-modal.is-closing .ba-chart-modal__dialog {
    animation: baModalLeave .14s ease-in both;
}

.ba-chart-modal__backdrop {
    background:
        linear-gradient(rgba(35, 43, 34, 0.52), rgba(35, 43, 34, 0.52)),
        linear-gradient(90deg, rgba(232, 237, 226, 0.035) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(232, 237, 226, 0.03) 0 1px, transparent 1px);
    background-size: auto, 24px 24px, 24px 24px;
}

.ba-chart-modal__body {
    background: var(--surface-panel) !important;
    transition: background-color .18s ease;
}

.ba-chart-modal__body .ba-chart-frame,
.ba-chart-modal .ba-compare-card,
.ba-chart-modal .ba-modal-drilldown,
.ba-chart-modal .ba-modal-kpi {
    border-color: rgba(174, 188, 165, 0.62) !important;
}

.ba-chart-modal__body .ba-chart-frame {
    background: rgba(251, 252, 247, 0.92) !important;
}

.ba-chart-modal .ba-compare-title,
.ba-chart-modal .ba-compare-table th,
.ba-chart-modal .ba-modal-drilldown .ba-compare-table th:first-child {
    background: rgba(199, 213, 190, 0.82) !important;
}

.ba-chart-modal .ba-modal-drilldown .ba-compare-table td:first-child {
    box-shadow: inset -1px 0 0 rgba(174, 188, 165, 0.45);
}

@keyframes baModalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes baModalFadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}

@keyframes baModalSettle {
    from { transform: translateY(8px); }
    to { transform: translateY(0); }
}

@keyframes baModalLeave {
    from { transform: translateY(0); }
    to { transform: translateY(6px); }
}

@media (prefers-reduced-motion: reduce) {
    .ba-summary-card,
    .ba-branch-kpi-card,
    .ba-insight-card,
    .ba-compare-card,
    .ba-chart-frame,
    .ba-tab-btn,
    .ba-expand-chart-btn,
    .ba-row-filter-btn,
    .ba-global-clear,
    .ba-filter-clear,
    .ba-chart-modal,
    .ba-chart-modal__dialog {
        animation: none !important;
        transition: none !important;
    }
}

.ba-shell {
    padding: 0.75rem clamp(0.75rem, 1.1vw, 1.25rem) 1.5rem;
}

.ba-workspace {
    width: 100%;
    max-width: none;
    margin-inline: 0;
}

.ba-summary-grid {
    margin-top: 0;
}

.ba-summary-card,
.ba-branch-kpi-card,
.ba-insight-card,
.ba-modal-kpi {
    border-width: 1.25px;
}

.ba-summary-card {
    min-height: 98px;
    padding: 1.05rem 1.1rem;
}

.ba-summary-icon {
    width: 42px;
    height: 42px;
    color: var(--brand) !important;
    background: rgba(251, 252, 247, 0.58) !important;
    border-color: rgba(62, 74, 61, 0.18) !important;
}

.ba-summary-card span,
.ba-branch-kpi-card span,
.ba-insight-label,
.ba-modal-kpi span,
.ba-compare-table th {
    font-size: 12px;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: .035em;
}

.ba-summary-card strong {
    margin-top: 0.2rem;
    display: block;
    font-size: clamp(1.18rem, 1.45vw, 1.55rem);
    line-height: 1.1;
    font-weight: 800;
}

.ba-branch-kpi-card strong,
.ba-insight-value,
.ba-modal-kpi strong {
    font-size: clamp(1rem, 1.05vw, 1.22rem);
    line-height: 1.16;
    font-weight: 800;
}

.ba-panel-title {
    font-size: 1.16rem;
    font-weight: 800;
}

.ba-panel-note,
.ba-tab-btn,
.ba-expand-chart-btn,
.ba-row-filter-btn,
.ba-global-clear,
.ba-filter-clear,
.ba-context-chip,
.ba-branch-select,
.ba-period-select {
    font-weight: 800;
}

.ba-compare-table td {
    font-size: 13.5px;
    font-weight: 650;
}

.ba-compare-table td:nth-child(1),
.ba-compare-table td:nth-child(2),
.ba-compare-table td:nth-child(8),
.ba-compare-table td:nth-child(9) {
    font-weight: 800;
}

.ba-status-badge,
.ba-active-filter-chip {
    font-weight: 850;
}

.ba-chart-frame {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    background:
        linear-gradient(180deg, rgba(251, 252, 247, 0.98), rgba(245, 248, 240, 0.95)),
        linear-gradient(90deg, rgba(73, 87, 69, 0.03) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73, 87, 69, 0.026) 0 1px, transparent 1px) !important;
    background-size: auto, 28px 28px, 28px 28px !important;
    border-color: rgba(142, 160, 131, 0.82) !important;
}

.ba-chart-frame::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    background:
        linear-gradient(90deg, rgba(62, 74, 61, 0.045), transparent 24%, transparent 76%, rgba(184, 121, 86, 0.035)),
        radial-gradient(circle at 15% 10%, rgba(255, 255, 255, 0.55), transparent 32%);
    opacity: .85;
}

.ba-chart-frame canvas {
    position: relative;
    z-index: 1;
    filter: saturate(1.04) contrast(1.02);
}

.ba-chart-modal__body .ba-chart-frame {
    background:
        linear-gradient(180deg, rgba(251, 252, 247, 0.98), rgba(241, 246, 236, 0.96)),
        linear-gradient(90deg, rgba(73, 87, 69, 0.026) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73, 87, 69, 0.024) 0 1px, transparent 1px) !important;
    background-size: auto, 30px 30px, 30px 30px !important;
}

.ba-shell {
    --ba-panel-surface: #DCE6D6;
    --ba-graph-surface: #EAF1E2;
    --ba-graph-surface-soft: #F4F7ED;
    --ba-graph-surface-glow: #FFF7E7;
    --ba-control-surface: #F6F8F1;
    --ba-control-hover: #C5D3BC;
    --ba-control-active: #B8C9AF;
    --ba-control-border: #AEBCA5;
}

.ba-filter-row,
.ba-head-row-top,
.ba-global-filterbar,
.ba-tab-context,
.ba-chart-modal__body,
.ba-performance-card,
.ba-panel.active > .ba-chart-frame,
.ba-panel.active > .ba-insight-row,
.ba-panel.active > .ba-branch-kpi-grid {
    background: var(--ba-panel-surface) !important;
    border-color: var(--ba-control-border) !important;
}

.ba-chart-frame,
.ba-chart-empty,
.ba-chart-modal__body .ba-chart-frame {
    background:
        radial-gradient(circle at 12% 10%, rgba(255, 247, 231, 0.78), transparent 30%),
        radial-gradient(circle at 88% 0%, rgba(197, 211, 188, 0.46), transparent 34%),
        linear-gradient(180deg, var(--ba-graph-surface-soft) 0%, var(--ba-graph-surface) 100%),
        linear-gradient(90deg, rgba(73, 87, 69, 0.035) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73, 87, 69, 0.03) 0 1px, transparent 1px) !important;
    background-size: auto, 28px 28px, 28px 28px !important;
    border-color: var(--ba-control-border) !important;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.72),
        inset 0 -1px 0 rgba(73, 87, 69, 0.08),
        0 14px 34px rgba(45, 52, 44, 0.08) !important;
}

.ba-chart-frame::before {
    background:
        linear-gradient(90deg, rgba(62, 74, 61, 0.04), transparent 24%, transparent 74%, rgba(184, 121, 86, 0.055)),
        radial-gradient(circle at 16% 18%, rgba(255, 255, 255, 0.58), transparent 32%);
}

.ba-seg-item,
.ba-date-btn,
.ba-date-clear,
.ba-pop-reset,
.ba-global-clear,
.ba-filter-clear,
.ba-row-filter-btn,
.ba-expand-chart-btn,
.ba-clear-filters,
.ba-master-records-btn,
.ba-period-select-wrap,
.ba-branch-select-wrap,
.ba-context-chip,
.ba-chart-modal__view,
.ba-chart-modal__close,
.ba-pop-input {
    background: var(--ba-control-surface) !important;
    border-color: var(--ba-control-border) !important;
    color: var(--ba-text) !important;
}

.ba-branch-select,
.ba-period-select {
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    background: transparent !important;
    background-image: none !important;
    box-shadow: none !important;
    padding-right: 0.15rem !important;
}

.ba-branch-select::-ms-expand,
.ba-period-select::-ms-expand {
    display: none;
}

.ba-branch-select-chev {
    flex: 0 0 auto;
    margin-left: 0.15rem;
    color: var(--ba-muted) !important;
}

.ba-seg-item:hover,
.ba-date-btn:hover,
.ba-date-clear:hover,
.ba-pop-reset:hover,
.ba-global-clear:hover,
.ba-filter-clear:hover,
.ba-row-filter-btn:hover,
.ba-expand-chart-btn:hover,
.ba-clear-filters:hover,
.ba-master-records-btn:hover,
.ba-period-select-wrap:hover,
.ba-branch-select-wrap:hover,
.ba-chart-modal__view:hover,
.ba-chart-modal__close:hover {
    background: var(--ba-control-hover) !important;
    border-color: #8EA083 !important;
    color: #232821 !important;
}

.ba-seg-item.active,
.ba-date-btn.active,
.ba-tab-btn.active,
.ba-chart-modal__view.is-active,
.ba-filter-chip,
.ba-pop-apply {
    background: var(--brand) !important;
    border-color: var(--brand) !important;
    color: #fff !important;
}

.ba-tab-btn.active {
    color: #232821 !important;
    background: var(--ba-control-active) !important;
}

.ba-expand-chart-btn {
    min-height: 38px;
}

.ba-chart-modal__dialog {
    background: var(--ba-panel-surface) !important;
}

.ba-chart-modal__head {
    background: var(--ba-card-strong) !important;
}

.ba-chart-modal__view,
.ba-chart-modal__close {
    background: var(--ba-control-surface) !important;
}

.ba-ranking-row {
    cursor: pointer;
}

.ba-ranking-row:hover td {
    background: var(--ba-control-hover) !important;
}

.ba-ranking-row:focus-visible td {
    outline: 2px solid var(--brand);
    outline-offset: -2px;
}

.ba-ranking-row.is-selected td {
    background: var(--ba-control-active) !important;
}

.ba-ranking-row.is-selected td:first-child {
    box-shadow: inset 3px 0 0 var(--brand);
}

.ba-chart-modal {
    --ba-card: var(--records-card, #D3DEC9);
    --ba-card-alt: var(--records-card-alt, #DCE6D6);
    --ba-card-strong: var(--records-card-strong, #C7D5BE);
    --ba-panel-surface: #DCE6D6;
    --ba-graph-surface: #EAF1E2;
    --ba-graph-surface-soft: #F4F7ED;
    --ba-graph-surface-glow: #FFF7E7;
    --ba-control-surface: #F6F8F1;
    --ba-control-hover: #C5D3BC;
    --ba-control-active: #B8C9AF;
    --ba-control-border: #AEBCA5;
    --ba-text: var(--records-text, #2D342C);
    --ba-muted: var(--records-muted, #5F685F);
}

.ba-chart-modal__backdrop {
    background: rgba(35, 43, 34, 0.72) !important;
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
}

.ba-chart-modal__dialog {
    background: var(--ba-panel-surface) !important;
    color: var(--ba-text) !important;
}

.ba-chart-modal__head {
    background: var(--ba-card-strong) !important;
    border-bottom-color: var(--ba-control-border) !important;
}

.ba-chart-modal__body {
    background: var(--ba-panel-surface) !important;
}

.ba-chart-modal__body .ba-chart-frame,
.ba-chart-modal__body .ba-chart-frame.is-in-modal {
    background:
        radial-gradient(circle at 12% 10%, rgba(255, 247, 231, 0.78), transparent 30%),
        radial-gradient(circle at 88% 0%, rgba(197, 211, 188, 0.46), transparent 34%),
        linear-gradient(180deg, var(--ba-graph-surface-soft) 0%, var(--ba-graph-surface) 100%),
        linear-gradient(90deg, rgba(73, 87, 69, 0.035) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73, 87, 69, 0.03) 0 1px, transparent 1px) !important;
    background-size: auto, 28px 28px, 28px 28px !important;
    border-color: var(--ba-control-border) !important;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.72),
        inset 0 -1px 0 rgba(73, 87, 69, 0.08),
        0 16px 36px rgba(45, 52, 44, 0.1) !important;
}

.ba-chart-modal .ba-modal-drilldown,
.ba-chart-modal .ba-compare-card,
.ba-chart-modal .ba-modal-kpi {
    background: var(--ba-card) !important;
    border-color: var(--ba-control-border) !important;
    color: var(--ba-text) !important;
}

.ba-chart-modal .ba-modal-drilldown__head,
.ba-chart-modal .ba-compare-title,
.ba-chart-modal .ba-compare-table th {
    background: var(--ba-card-strong) !important;
    border-color: var(--ba-control-border) !important;
    color: var(--ba-text) !important;
}

.ba-chart-modal .ba-compare-table td {
    background: var(--card) !important;
    border-color: var(--ba-control-border) !important;
    color: var(--ba-text) !important;
}

.ba-chart-modal .ba-compare-table tbody tr:hover td {
    background: var(--ba-control-hover) !important;
}

.ba-chart-modal .ba-chart-modal__view,
.ba-chart-modal .ba-chart-modal__close {
    background: var(--ba-control-surface) !important;
    border-color: var(--ba-control-border) !important;
    color: var(--ba-text) !important;
}

.ba-chart-modal .ba-chart-modal__view.is-active {
    background: var(--brand) !important;
    border-color: var(--brand) !important;
    color: #fff !important;
}

.ba-workspace,
.ba-card.ba-workspace {
    background:
        linear-gradient(180deg, rgba(220, 230, 214, 0.98), rgba(211, 222, 201, 0.96)),
        linear-gradient(90deg, rgba(73, 87, 69, 0.018) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73, 87, 69, 0.016) 0 1px, transparent 1px) !important;
    background-size: auto, 28px 28px, 28px 28px !important;
    border-color: var(--ba-control-border) !important;
    box-shadow: inset 0 1px 0 rgba(251, 252, 247, 0.48) !important;
}

.ba-summary-card,
.ba-performance-card,
.ba-compare-card,
.ba-drilldown {
    background: var(--ba-panel-surface) !important;
}

@media (max-width: 760px) {
    .ba-shell {
        padding-inline: 0.65rem;
    }

    .ba-summary-card {
        min-height: 86px;
    }

    .ba-summary-card strong {
        font-size: 1.08rem;
    }
}

/* Final chart-surface pass: keeps every embedded and fullscreen graph off plain white. */
.ba-shell,
.ba-chart-modal {
    --ba-chart-panel: #DCE6D6;
    --ba-chart-panel-strong: #D2DEC9;
    --ba-chart-canvas: #EAF1E2;
    --ba-chart-canvas-soft: #F5F7ED;
    --ba-chart-canvas-warm: #FFF5DD;
    --ba-chart-border: #A7B79F;
}

.ba-panel.active,
.ba-performance-card {
    background:
        linear-gradient(180deg, var(--ba-chart-panel) 0%, var(--ba-chart-panel-strong) 100%) !important;
    border-color: var(--ba-chart-border) !important;
}

.ba-panel.active > .ba-panel-head,
.ba-performance-card > .ba-panel-head {
    background: transparent !important;
    border-color: transparent !important;
}

.ba-performance-card .ba-chart-frame,
.ba-panel.active > .ba-chart-frame,
.ba-chart-modal__body .ba-chart-frame,
.ba-chart-modal__body .ba-chart-frame.is-in-modal {
    background:
        radial-gradient(circle at 10% 8%, rgba(255, 245, 221, 0.78), transparent 30%),
        radial-gradient(circle at 92% 0%, rgba(188, 204, 178, 0.52), transparent 36%),
        linear-gradient(180deg, var(--ba-chart-canvas-soft) 0%, var(--ba-chart-canvas) 100%),
        linear-gradient(90deg, rgba(59, 75, 55, 0.04) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(59, 75, 55, 0.035) 0 1px, transparent 1px) !important;
    background-size: auto, auto, auto, 30px 30px, 30px 30px !important;
    border-color: var(--ba-chart-border) !important;
}

.ba-chart-modal__body,
.ba-chart-modal__dialog {
    background:
        linear-gradient(180deg, var(--ba-chart-panel) 0%, #CAD8C1 100%) !important;
}

.ba-chart-empty {
    color: var(--ba-text) !important;
}

/* Branch Analytics reference chart UI: preserve old graph layout inside the unified module. */
html body .ba-shell .ba-panel.active,
html body .ba-shell .ba-performance-card {
    background: #FAFBF7 !important;
    border: 1.25px solid #AEBFA6 !important;
    border-radius: 8px !important;
    box-shadow: none !important;
}

html body .ba-shell .ba-panel.active {
    padding: 1rem !important;
}

html body .ba-shell .ba-performance-card {
    padding: 0.9rem !important;
}

html body .ba-shell .ba-panel.active > .ba-panel-head,
html body .ba-shell .ba-performance-card > .ba-panel-head {
    align-items: center !important;
    background: transparent !important;
    border: 0 !important;
    margin: 0 !important;
    padding: 0 0 0.65rem !important;
}

html body .ba-shell .ba-panel-title {
    color: #263126 !important;
    font-size: 1.22rem !important;
    font-weight: 800 !important;
    letter-spacing: 0 !important;
    line-height: 1.2 !important;
}

html body .ba-shell .ba-panel-actions {
    align-items: center !important;
    gap: 0.45rem !important;
}

html body .ba-shell .ba-panel-note {
    background: #E7EEDF !important;
    border: 0 !important;
    border-radius: 999px !important;
    color: #667160 !important;
    font-size: 0.78rem !important;
    font-weight: 800 !important;
    letter-spacing: 0 !important;
    line-height: 1 !important;
    padding: 0.28rem 0.5rem !important;
    white-space: nowrap !important;
}

html body .ba-shell .ba-expand-chart-btn {
    background: #FCFDF9 !important;
    border: 1.25px solid #AEBFA6 !important;
    border-radius: 7px !important;
    box-shadow: none !important;
    color: #354232 !important;
    font-size: 0.86rem !important;
    font-weight: 800 !important;
    min-height: 40px !important;
    padding: 0.45rem 0.65rem !important;
}

html body .ba-shell .ba-expand-chart-btn:hover {
    background: #F2F6EC !important;
    border-color: #8EA083 !important;
    transform: none !important;
}

html body .ba-shell .ba-chart-mode-toggle {
    min-height: 40px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.2rem !important;
    border: 1.25px solid #AEBFA6 !important;
    border-radius: 7px !important;
    background: #FCFDF9 !important;
    padding: 0.22rem !important;
}

html body .ba-shell .ba-chart-mode-btn {
    min-height: 30px !important;
    border: 0 !important;
    border-radius: 5px !important;
    background: transparent !important;
    color: #667160 !important;
    padding: 0.36rem 0.64rem !important;
    font: inherit !important;
    font-size: 0.78rem !important;
    font-weight: 800 !important;
    line-height: 1 !important;
    cursor: pointer !important;
    transition: background-color 0.16s ease, color 0.16s ease !important;
}

html body .ba-shell .ba-chart-mode-btn:hover,
html body .ba-shell .ba-chart-mode-btn:focus-visible {
    background: rgba(142, 160, 131, 0.18) !important;
    color: #2F5233 !important;
    outline: none !important;
}

html body .ba-shell .ba-chart-mode-btn.active,
html body .ba-shell .ba-chart-mode-btn[aria-pressed="true"] {
    background: #2F5233 !important;
    color: #FAFBF7 !important;
}

html body .ba-shell .ba-performance-card .ba-chart-frame,
html body .ba-shell .ba-panel.active > .ba-chart-frame {
    background: linear-gradient(180deg, #FFFDF8 0%, #F8FAF2 100%) !important;
    background-size: auto !important;
    border: 1.25px solid #AEBFA6 !important;
    border-radius: 8px !important;
    box-shadow: none !important;
    height: clamp(335px, 40vh, 425px) !important;
    min-height: 335px !important;
    overflow: hidden !important;
    padding: 1.05rem !important;
}

html body .ba-shell .ba-chart-frame::before {
    display: none !important;
}

html body .ba-shell .ba-chart-frame canvas {
    filter: none !important;
}

html body .ba-shell .ba-chart-frame-narrow {
    margin-inline: 0 !important;
    max-width: none !important;
}

html body .ba-shell .ba-performance-card > .ba-insight-row,
html body .ba-shell .ba-panel.active > .ba-insight-row,
html body .ba-shell .ba-panel.active > .ba-branch-kpi-grid {
    background: transparent !important;
    border: 0 !important;
    gap: 0.5rem !important;
    margin-top: 0.55rem !important;
    padding: 0 !important;
}

html body .ba-shell .ba-insight-card,
html body .ba-shell .ba-branch-kpi-card {
    background: #DCE6D6 !important;
    border: 1.25px solid #AEBFA6 !important;
    border-radius: 7px !important;
    box-shadow: none !important;
    min-height: 82px !important;
    padding: 0.85rem 0.95rem !important;
}

html body .ba-shell .ba-insight-card:nth-child(even),
html body .ba-shell .ba-branch-kpi-card:nth-child(even),
html body .ba-shell .ba-insight-card.tone-amber,
html body .ba-shell .ba-insight-card.tone-red {
    background: #E4DFCB !important;
}

html body .ba-shell .ba-insight-label,
html body .ba-shell .ba-branch-kpi-card span {
    color: #65715F !important;
    font-size: 0.78rem !important;
    font-weight: 800 !important;
    letter-spacing: 0.08em !important;
    text-transform: uppercase !important;
}

html body .ba-shell .ba-insight-value,
html body .ba-shell .ba-branch-kpi-card strong {
    color: #263126 !important;
    font-size: 1.1rem !important;
    font-weight: 800 !important;
    letter-spacing: 0 !important;
}

html body .ba-shell .ba-insight-note,
html body .ba-shell .ba-branch-kpi-card small {
    color: #65715F !important;
    font-size: 0.82rem !important;
    font-weight: 700 !important;
}

html body .ba-shell .ba-summary-card small {
    color: #6B8065 !important;
    display: block !important;
    font-size: 0.82rem !important;
    font-weight: 700 !important;
    letter-spacing: 0 !important;
    line-height: 1.35 !important;
    margin-top: 0.35rem !important;
    text-transform: none !important;
}

html body .ba-shell .ba-summary-content {
    min-width: 0 !important;
}

html body .ba-shell .ba-kpi-actions {
    position: absolute !important;
    top: 0.62rem !important;
    right: 0.62rem !important;
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 0.35rem !important;
    margin-top: 0 !important;
    opacity: 0 !important;
    pointer-events: none !important;
    transform: translateY(-2px) !important;
    transition: opacity 0.16s ease, transform 0.16s ease !important;
    z-index: 2 !important;
}

html body .ba-shell .ba-summary-card {
    position: relative !important;
}

html body .ba-shell .ba-summary-card:hover .ba-kpi-actions,
html body .ba-shell .ba-summary-card:focus-within .ba-kpi-actions {
    opacity: 1 !important;
    pointer-events: auto !important;
    transform: translateY(0) !important;
}

html body .ba-shell .ba-kpi-action {
    min-height: 24px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.12rem !important;
    border: 1px solid rgba(62, 74, 61, 0.20) !important;
    border-radius: 999px !important;
    background: rgba(250, 251, 247, 0.92) !important;
    color: #2F5233 !important;
    padding: 0.23rem 0.48rem !important;
    font: inherit !important;
    font-size: 0.72rem !important;
    font-weight: 800 !important;
    line-height: 1 !important;
    text-decoration: none !important;
    cursor: pointer !important;
    box-shadow: 0 8px 18px rgba(35, 40, 33, 0.08) !important;
    transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease, transform 0.16s ease !important;
}

html body .ba-shell .ba-kpi-action:hover,
html body .ba-shell .ba-kpi-action:focus-visible {
    background: #2F5233 !important;
    border-color: #2F5233 !important;
    color: #FAFBF7 !important;
    outline: none !important;
    transform: translateY(-1px) !important;
}

html body .ba-shell .reports-module-main > .ba-workspace {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
}

html body .ba-shell .ba-workspace[data-active-analytics-panel="ba-panel-payment"] > .ba-summary-grid,
html body .ba-shell .ba-workspace[data-active-analytics-panel="ba-panel-collection"] > .ba-summary-grid,
html body .ba-shell .ba-workspace[data-active-analytics-panel="ba-panel-trend"] > .ba-summary-grid {
    display: none !important;
}

html body .ba-shell #ba-panel-trend.active > .ba-panel-head {
    order: 0 !important;
}

html body .ba-shell #ba-panel-trend.active > .ba-tab-context {
    order: 1 !important;
}

html body .ba-shell #ba-panel-trend.active > #baRevenueInsights {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    order: 2 !important;
    margin: 0 0 0.75rem !important;
}

html body .ba-shell #ba-panel-trend.active > .ba-chart-frame {
    order: 3 !important;
}

html body .ba-shell .ba-breakdown-section {
    border-color: rgba(174, 191, 166, 0.78) !important;
    border-radius: 8px !important;
    background: rgba(220, 230, 214, 0.42) !important;
}

html body .ba-shell .ba-breakdown-summary {
    min-height: 46px !important;
    padding: 0.72rem 0.9rem !important;
}

html body .ba-shell .ba-breakdown-card {
    background: #FAFBF7 !important;
    border-color: rgba(174, 191, 166, 0.78) !important;
}

html body .ba-shell .ba-breakdown-row {
    grid-template-columns: minmax(132px, 1fr) auto minmax(96px, auto) auto !important;
    row-gap: 0.42rem !important;
}

html[data-theme='dark'] body .ba-shell .ba-breakdown-section {
    background: rgba(26, 47, 70, 0.58) !important;
    border-color: #2e4560 !important;
}

html[data-theme='dark'] body .ba-shell .ba-breakdown-summary,
html[data-theme='dark'] body .ba-shell .ba-breakdown-summary small {
    color: #d8e6f7 !important;
}

@media (max-width: 1180px) {
    html body .ba-shell #ba-panel-trend.active > #baRevenueInsights {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 760px) {
    html body .ba-shell .ba-breakdown-summary {
        grid-template-columns: minmax(0, 1fr) auto !important;
    }

    html body .ba-shell .ba-breakdown-summary small {
        display: none !important;
    }

    html body .ba-shell .ba-breakdown-row {
        grid-template-columns: 1fr auto !important;
    }

    html body .ba-shell .ba-breakdown-amount,
    html body .ba-shell .ba-breakdown-percent {
        text-align: left !important;
    }
}

@media (max-width: 640px) {
    html body .ba-shell #ba-panel-trend.active > #baRevenueInsights {
        grid-template-columns: 1fr !important;
    }
}

html.ba-soft-loading .ba-shell {
    cursor: progress;
    opacity: 0.68;
    pointer-events: none;
    transition: opacity 0.16s ease;
}

html body .ba-shell .ba-panels > .ba-panel[hidden],
html body .ba-shell .ba-panels > .ba-panel:not(.active) {
    display: none !important;
}

html body .ba-shell .ba-panels > .ba-panel.active:not([hidden]) {
    display: flex !important;
}
</style>

<script data-ba-analytics-script>
window.SabanganAnalyticsSoftNav = window.SabanganAnalyticsSoftNav || (() => {
    let isLoading = false;

    const analyticsPath = @json(parse_url(route('owner.analytics'), PHP_URL_PATH));
    const isAnalyticsUrl = (url) => {
        try {
            return new URL(url, window.location.origin).pathname === analyticsPath;
        } catch (_) {
            return false;
        }
    };

    const formUrl = (form) => {
        const data = new FormData(form);
        const url = new URL(form.action || window.location.href, window.location.origin);
        url.search = '';
        data.forEach((value, key) => {
            if (value !== null && String(value) !== '') {
                url.searchParams.set(key, value);
            }
        });
        return url;
    };

    const executeAnalyticsScript = (doc) => {
        const script = doc.querySelector('script[data-ba-analytics-script]');
        if (!script?.textContent) return;
        window.setTimeout(() => {
            try {
                new Function(script.textContent)();
            } catch (error) {
                console.error('Unable to initialize analytics after soft navigation.', error);
            }
        }, 0);
    };

    const load = async (url) => {
        if (isLoading) return;
        isLoading = true;
        document.documentElement.classList.add('ba-soft-loading');

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                window.location.href = url.toString();
                return;
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextShell = doc.querySelector('.ba-shell');
            const currentShell = document.querySelector('.ba-shell');

            if (!nextShell || !currentShell) {
                window.location.href = url.toString();
                return;
            }

            currentShell.replaceWith(nextShell);
            document.title = doc.title || document.title;
            window.history.pushState({}, '', url.toString());
            executeAnalyticsScript(doc);
        } catch (error) {
            console.error('Analytics soft navigation failed.', error);
            window.location.href = url.toString();
        } finally {
            isLoading = false;
            document.documentElement.classList.remove('ba-soft-loading');
        }
    };

    const init = () => {
        if (window.__sabanganAnalyticsSoftNavBound) return;
        window.__sabanganAnalyticsSoftNavBound = true;

        document.addEventListener('change', (event) => {
            const select = event.target;
            if (!(select instanceof HTMLSelectElement)) return;
            const form = select.form;
            if (!form || !isAnalyticsUrl(form.action)) return;

            event.preventDefault();
            event.stopImmediatePropagation();
            load(formUrl(form));
        }, true);

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !isAnalyticsUrl(form.action)) return;

            event.preventDefault();
            load(formUrl(form));
        }, true);

        window.addEventListener('popstate', () => {
            if (isAnalyticsUrl(window.location.href)) {
                load(new URL(window.location.href));
            }
        });
    };

    return { init, load };
})();

window.SabanganAnalyticsSoftNav.init();

(function () {
    const payload = @json($chart);
    if (!payload) return;

    const summary = @json($summaryPayload);
    const branchMeta = @json($branchMeta);
    const selectedBranchMeta = @json($selectedBranch ? [
        'code' => (string) $selectedBranch->branch_code,
        'name' => (string) $selectedBranch->branch_name,
    ] : null);
    const allAnalyticsCases = @json($allAnalyticsCases ?? []);
    const globalBranchId = @json($branchId ? (int) $branchId : null);
    const globalDateFrom = @json($dateFrom);
    const globalDateTo = @json($dateTo);
    const globalRange = @json($range);
    const masterCaseRecordsUrl = @json($masterCaseRecordsUrl);
    const barRawLabels = payload.bar.labels ?? [];
    const periodLabels = payload.period?.labels ?? payload.line?.labels ?? [];
    const periodCases = payload.period?.cases ?? [];
    const periodServiceAmounts = payload.period?.service_amount ?? payload.line?.data ?? [];
    const periodCollectedAmounts = payload.period?.collected_amount ?? [];
    const periodOutstandingBalances = payload.period?.outstanding_balance ?? [];
    const barAxisLabels = payload.mode === 'all'
        ? barRawLabels.map((label, index) => branchMeta[index]?.code || label)
        : barRawLabels;
    const barTooltipLabels = payload.mode === 'all'
        ? barRawLabels.map((label, index) => {
            const code = branchMeta[index]?.code;
            return code ? `${code} - ${label}` : label;
        })
        : barRawLabels;

    const isDark = document.documentElement.dataset.theme === 'dark';
    const chartTheme = {
        primary: '#344332',
        secondary: '#93A58D',
        success: '#56755A',
        warning: '#BD7A55',
        danger: '#A24B42',
        neutral: '#6F796B',
        surface: '#EAF1E2',
        border: '#AEBCA5',
        text: '#232821',
        textMuted: '#4E5B4B',
        grid: '#C8D4C0',
    };
    const gridColor = isDark ? 'rgba(138, 167, 197, 0.18)' : 'rgba(166, 180, 156, 0.44)';
    const textColor = isDark ? '#cfe0f5' : chartTheme.textMuted;

    const money = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    });

    const number = new Intl.NumberFormat('en-PH');
    const shortPeso = (value) => {
        const amount = Number(value || 0);
        if (Math.abs(amount) >= 1000000) {
            return `₱${(amount / 1000000).toFixed(amount % 1000000 === 0 ? 0 : 1)}M`;
        }
        if (Math.abs(amount) >= 1000) {
            return `₱${Math.round(amount / 1000)}K`;
        }
        return `₱${number.format(amount)}`;
    };
    const charts = {};
    const collectionViewStorageKey = 'ownerBranchAnalytics.collectionView';
    const storedCollectionChartView = sessionStorage.getItem(collectionViewStorageKey);
    let collectionChartView = ['snapshot', 'by_period'].includes(storedCollectionChartView)
        ? storedCollectionChartView
        : (storedCollectionChartView === 'monthly' ? 'by_period' : 'snapshot');
    let chartLoader = null;
    let analyticsFilters = {
        branchCode: null,
        paymentStatus: null,
        revenuePeriod: null,
        collectionStatus: null,
    };
    const chartModal = document.getElementById('baChartModal');
    const chartModalChartSlot = document.getElementById('baChartModalChartSlot');
    const chartModalBody = document.getElementById('baChartModalBody');
    const chartModalTitle = document.getElementById('baChartModalTitle');
    const chartModalCloseBtn = chartModal?.querySelector('.ba-chart-modal__close');
    const modalViewButtons = Array.from(chartModal?.querySelectorAll('[data-modal-view]') || []);
    const modalRecordCount = document.getElementById('baModalRecordCount');
    const modalDrilldown = document.getElementById('baModalDrilldown');
    let activeModalChart = null;
    let modalView = 'chart';

    const statusLabels = {
        PAID: 'Paid',
        PARTIAL: 'Partial',
        UNPAID: 'Unpaid',
        COLLECTED: 'Collected',
        OUTSTANDING: 'Outstanding',
    };

    const statusSentenceLabels = {
        PAID: 'Paid',
        PARTIAL: 'Partial',
        UNPAID: 'Unpaid',
        COLLECTED: 'Collected',
        OUTSTANDING: 'Outstanding',
    };

    const activeFilterCount = () => [
        analyticsFilters.branchCode,
        analyticsFilters.paymentStatus,
        analyticsFilters.revenuePeriod,
        analyticsFilters.collectionStatus,
    ].filter(Boolean).length;

    const pluralizeCase = (count) => `${number.format(count)} ${count === 1 ? 'case' : 'cases'}`;

    const branchLabelForCode = (branchCode) => {
        if (!branchCode) return '';
        const branch = branchMeta.find((item) => item.code === branchCode);
        return branch?.name ? `${branchCode} - ${branch.name}` : branchCode;
    };

    const activeFilterItems = () => {
        const items = [];
        if (analyticsFilters.branchCode) {
            items.push({
                key: 'branchCode',
                label: 'Branch',
                value: branchLabelForCode(analyticsFilters.branchCode),
                rawValue: analyticsFilters.branchCode,
            });
        }
        if (analyticsFilters.paymentStatus) {
            items.push({
                key: 'paymentStatus',
                label: 'Payment',
                value: statusLabels[analyticsFilters.paymentStatus] || analyticsFilters.paymentStatus,
                rawValue: analyticsFilters.paymentStatus,
            });
        }
        if (analyticsFilters.collectionStatus) {
            items.push({
                key: 'collectionStatus',
                label: 'Collection',
                value: statusLabels[analyticsFilters.collectionStatus] || analyticsFilters.collectionStatus,
                rawValue: analyticsFilters.collectionStatus,
            });
        }
        if (analyticsFilters.revenuePeriod) {
            items.push({
                key: 'revenuePeriod',
                label: 'Period',
                value: analyticsFilters.revenuePeriod.label,
                rawValue: analyticsFilters.revenuePeriod.label,
            });
        }

        return items;
    };

    const statusTone = (status) => {
        if (['PAID', 'COLLECTED'].includes(status)) return 'status-positive';
        if (['PARTIAL', 'OUTSTANDING'].includes(status)) return 'status-warning';
        if (['UNPAID', 'OVERDUE'].includes(status)) return 'status-danger';
        return '';
    };

    const filterTone = (label, value) => {
        if (label === 'Payment' || label === 'Collection') {
            return statusTone(value).replace('status-', 'filter-');
        }
        return '';
    };

    const periodMatches = (caseDate, period) => {
        if (!period || !caseDate) return true;
        const date = new Date(`${caseDate}T00:00:00`);
        if (Number.isNaN(date.getTime())) return false;

        if (period.type === 'month') {
            return date.getFullYear() === period.year && date.getMonth() + 1 === period.month;
        }

        if (period.type === 'day') {
            return caseDate === period.date;
        }

        if (period.type === 'range') {
            return caseDate >= period.start && caseDate <= period.end;
        }

        return true;
    };

    const parsePeriodFromLabel = (label) => {
        const startYear = Number((globalDateFrom || '').slice(0, 4)) || new Date().getFullYear();
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthIndex = monthLabels.indexOf(label);
        if (globalRange === 'THIS_YEAR' && monthIndex >= 0) {
            return {
                type: 'month',
                label: `${label} ${startYear}`,
                year: startYear,
                month: monthIndex + 1,
            };
        }

        const parseMonthDay = (value) => {
            const parsed = new Date(`${value}, ${startYear}`);
            if (Number.isNaN(parsed.getTime())) return null;
            const month = String(parsed.getMonth() + 1).padStart(2, '0');
            const day = String(parsed.getDate()).padStart(2, '0');
            return `${parsed.getFullYear()}-${month}-${day}`;
        };

        if (String(label).includes(' - ')) {
            const [startLabel, endLabel] = String(label).split(' - ');
            const start = parseMonthDay(startLabel);
            const end = parseMonthDay(endLabel);
            return start && end ? { type: 'range', label, start, end } : null;
        }

        const day = parseMonthDay(label);
        return day ? { type: 'day', label: `${label} ${startYear}`, date: day } : null;
    };

    const getFilteredCases = () => allAnalyticsCases.filter((caseItem) => {
        const matchesBranch = !analyticsFilters.branchCode || caseItem.branchCode === analyticsFilters.branchCode;
        const matchesPayment = !analyticsFilters.paymentStatus || caseItem.paymentStatus === analyticsFilters.paymentStatus;
        const matchesCollection = !analyticsFilters.collectionStatus
            || (analyticsFilters.collectionStatus === 'COLLECTED' && Number(caseItem.totalPaid || 0) > 0)
            || (analyticsFilters.collectionStatus === 'OUTSTANDING' && Number(caseItem.balanceAmount || 0) > 0);
        const matchesPeriod = periodMatches(caseItem.caseDate, analyticsFilters.revenuePeriod);

        return matchesBranch && matchesPayment && matchesCollection && matchesPeriod;
    });

    const summarizeCases = (cases) => {
        const totalRevenue = cases.reduce((sum, item) => sum + Number(item.totalAmount || 0), 0);
        const totalOutstanding = cases.reduce((sum, item) => sum + Number(item.balanceAmount || 0), 0);
        const paymentBreakdown = { PAID: { count: 0, amount: 0 }, PARTIAL: { count: 0, amount: 0 }, UNPAID: { count: 0, amount: 0 } };
        const collectionBreakdown = { COLLECTED: { count: 0, amount: 0 }, OUTSTANDING: { count: 0, amount: 0 } };

        cases.forEach((item) => {
            const amount = Number(item.totalAmount || 0);
            if (paymentBreakdown[item.paymentStatus]) {
                paymentBreakdown[item.paymentStatus].count += 1;
                paymentBreakdown[item.paymentStatus].amount += amount;
            }
            if (Number(item.totalPaid || 0) > 0) {
                collectionBreakdown.COLLECTED.count += 1;
                collectionBreakdown.COLLECTED.amount += Number(item.totalPaid || 0);
            }
            if (Number(item.balanceAmount || 0) > 0) {
                collectionBreakdown.OUTSTANDING.count += 1;
                collectionBreakdown.OUTSTANDING.amount += Number(item.balanceAmount || 0);
            }
        });

        return {
            totalRevenue,
            totalOutstanding,
            totalCases: cases.length,
            averagePerCase: cases.length > 0 ? totalRevenue / cases.length : 0,
            paymentBreakdown,
            collectionBreakdown,
        };
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    const shortMoney = (value) => {
        const amount = Number(value || 0);
        if (Math.abs(amount) >= 1000000) return `PHP ${(amount / 1000000).toFixed(1)}M`;
        if (Math.abs(amount) >= 1000) return `PHP ${(amount / 1000).toFixed(0)}K`;
        return money.format(amount);
    };

    const branchDisplay = (branch) => {
        if (!branch) return '-';
        return branch.name ? `${branch.code} - ${branch.name}` : branch.code;
    };

    const maxBy = (items, getter) => items.reduce((best, item) => (
        !best || getter(item) > getter(best) ? item : best
    ), null);

    const minBy = (items, getter) => items.reduce((best, item) => (
        !best || getter(item) < getter(best) ? item : best
    ), null);

    const renderInsightCards = (targetId, cards) => {
        const target = document.getElementById(targetId);
        if (!target) return;

        target.innerHTML = cards.map((card, index) => {
            const accessibleLabel = `${card.label}: ${card.value}. ${card.note || 'Open matching records.'}`;

            return `
            <article class="ba-insight-card tone-${card.tone || 'blue'} ${card.onClick ? 'is-clickable' : ''} ${card.isSelected ? 'is-selected' : ''}" data-insight-index="${index}" ${card.onClick ? `role="button" tabindex="0" aria-label="${escapeHtml(accessibleLabel)}"` : ''}>
                <span class="ba-insight-label">${escapeHtml(card.label)}</span>
                <strong class="ba-insight-value">${escapeHtml(card.value)}</strong>
                <span class="ba-insight-note">${escapeHtml(card.note || '')}</span>
            </article>
        `;
        }).join('');

        cards.forEach((card, index) => {
            if (!card.onClick) return;
            const el = target.querySelector(`[data-insight-index="${index}"]`);
            if (!el) return;
            el.addEventListener('click', card.onClick);
            el.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    card.onClick();
                }
            });
        });
    };

    const buildBranchInsightRows = () => {
        const scopedBranches = selectedBranchMeta ? [selectedBranchMeta] : branchMeta;
        const rowsByBranch = new Map(scopedBranches.map((branch) => [branch.code, {
            code: branch.code,
            name: branch.name || '',
            cases: 0,
            revenue: 0,
            outstanding: 0,
        }]));

        allAnalyticsCases.forEach((item) => {
            const code = item.branchCode || 'Unassigned';
            const row = rowsByBranch.get(code) || {
                code,
                name: item.branchName || '',
                cases: 0,
                revenue: 0,
                outstanding: 0,
            };
            row.cases += 1;
            row.revenue += Number(item.totalAmount || 0);
            row.outstanding += Number(item.balanceAmount || 0);
            if (Number(item.balanceAmount || 0) > 0) {
                row.needsAttention = (row.needsAttention || 0) + 1;
            }
            rowsByBranch.set(code, row);
        });

        return Array.from(rowsByBranch.values()).map((row) => ({
            ...row,
            needsAttention: row.needsAttention || 0,
            average: row.cases > 0 ? row.revenue / row.cases : 0,
        }));
    };

    const buildPaymentInsightRows = () => {
        const rows = {
            PAID: { count: 0, amount: 0, balance: 0 },
            PARTIAL: { count: 0, amount: 0, balance: 0 },
            UNPAID: { count: 0, amount: 0, balance: 0 },
        };

        allAnalyticsCases.forEach((item) => {
            if (!rows[item.paymentStatus]) return;
            rows[item.paymentStatus].count += 1;
            rows[item.paymentStatus].amount += Number(item.totalAmount || 0);
            rows[item.paymentStatus].balance += Number(item.balanceAmount || 0);
        });

        return rows;
    };

    const buildCollectionInsightRows = () => {
        const collected = allAnalyticsCases.reduce((sum, item) => sum + Number(item.totalPaid || 0), 0);
        const outstanding = allAnalyticsCases.reduce((sum, item) => sum + Number(item.balanceAmount || 0), 0);
        const outstandingCases = allAnalyticsCases.filter((item) => item.collectionStatus === 'OUTSTANDING');
        const collectible = collected + outstanding;

        return {
            collected,
            outstanding,
            collectionRate: collectible > 0 ? (collected / collectible) * 100 : 0,
            outstandingCases: outstandingCases.length,
        };
    };

    const trendChartLabels = () => payload.line?.labels ?? periodLabels;

    const buildTrendSeries = () => {
        const labels = trendChartLabels();
        const lineValues = payload.line?.data ?? [];
        const revenue = labels.map((label, index) => Number(lineValues[index] ?? periodServiceAmounts[index] ?? 0));

        return { labels, revenue };
    };

    const buildRevenueInsightRows = () => {
        const { labels, revenue: revenueValues } = buildTrendSeries();
        const rows = labels.map((label, index) => ({
            label,
            revenue: Number(revenueValues[index] || 0),
            period: parsePeriodFromLabel(label),
        }));
        const peak = maxBy(rows, (row) => row.revenue);
        const lowest = minBy(rows, (row) => row.revenue);
        const total = rows.reduce((sum, row) => sum + row.revenue, 0);

        return {
            rows,
            total,
            peak,
            lowest,
            average: rows.length > 0 ? total / rows.length : 0,
        };
    };

    const renderAnalyticsInsights = () => {
        const branchRows = buildBranchInsightRows();
        const topRevenue = maxBy(branchRows, (row) => row.revenue);
        const mostActive = maxBy(branchRows, (row) => row.cases);
        const needsAttention = maxBy(branchRows, (row) => row.needsAttention || 0);

        if (selectedBranchMeta) {
            const selected = branchRows[0] || { cases: 0, revenue: 0, average: 0 };

            renderInsightCards('baBranchInsights', [
                {
                    label: 'Average per Case',
                    value: shortMoney(selected.average || 0),
                    note: `${number.format(selected.cases || 0)} cases included`,
                    tone: 'blue',
                },
            ]);
        } else {
            renderInsightCards('baBranchInsights', [
                {
                    label: 'Top Revenue Branch',
                    value: topRevenue ? `${shortMoney(topRevenue.revenue)} revenue` : 'No revenue yet',
                    note: branchDisplay(topRevenue),
                    tone: 'blue',
                    onClick: topRevenue?.code ? () => handleBranchClick(topRevenue.code) : null,
                    hint: topRevenue?.code ? 'Open records' : null,
                },
                {
                    label: 'Most Active Branch',
                    value: mostActive ? pluralizeCase(mostActive.cases) : 'No cases yet',
                    note: branchDisplay(mostActive),
                    tone: 'blue',
                    onClick: mostActive?.code ? () => handleBranchClick(mostActive.code) : null,
                    hint: mostActive?.code ? 'Open records' : null,
                },
                {
                    label: 'Needs Attention',
                    value: needsAttention && needsAttention.needsAttention > 0
                        ? pluralizeCase(needsAttention.needsAttention)
                        : 'No open balances',
                    note: needsAttention && needsAttention.outstanding > 0
                        ? `${shortMoney(needsAttention.outstanding)} outstanding`
                        : 'All selected balances are clear',
                    tone: needsAttention && needsAttention.needsAttention > 0 ? 'amber' : 'green',
                    onClick: needsAttention?.code && needsAttention.needsAttention > 0
                        ? () => handleBranchClick(needsAttention.code)
                        : null,
                    hint: needsAttention?.code && needsAttention.needsAttention > 0 ? 'Open records' : null,
                },
            ]);
        }

        const paymentRows = buildPaymentInsightRows();
        const needsFollowUpCount = paymentRows.PARTIAL.count + paymentRows.UNPAID.count;
        const needsFollowUpBalance = paymentRows.PARTIAL.balance + paymentRows.UNPAID.balance;
        renderInsightCards('baPaymentInsights', [
            {
                label: 'Paid Cases',
                value: number.format(paymentRows.PAID.count),
                note: `${shortMoney(paymentRows.PAID.amount)} fully paid`,
                tone: 'green',
                onClick: () => handlePaymentStatusClick('PAID'),
                hint: 'Open records',
            },
            {
                label: 'Partial Payment Cases',
                value: number.format(paymentRows.PARTIAL.count),
                note: `${shortMoney(paymentRows.PARTIAL.balance)} balance`,
                tone: 'amber',
                onClick: () => handlePaymentStatusClick('PARTIAL'),
                hint: 'Open records',
            },
            {
                label: 'Unpaid Cases',
                value: number.format(paymentRows.UNPAID.count),
                note: `${shortMoney(paymentRows.UNPAID.balance)} outstanding`,
                tone: 'red',
                onClick: () => handlePaymentStatusClick('UNPAID'),
                hint: 'Open records',
            },
        ]);

        const revenueRows = buildRevenueInsightRows();
        const firstRevenueRow = revenueRows.rows[0] || null;
        const lastRevenueRow = revenueRows.rows[revenueRows.rows.length - 1] || null;
        const firstRevenue = Number(firstRevenueRow?.revenue || 0);
        const lastRevenue = Number(lastRevenueRow?.revenue || 0);
        const growthPct = firstRevenue === 0
            ? (lastRevenue > 0 ? 100 : 0)
            : ((lastRevenue - firstRevenue) / firstRevenue) * 100;
        const growthPrefix = growthPct > 0 ? '+' : growthPct < 0 ? '-' : '';
        const growthValue = `${growthPrefix}${Math.abs(growthPct).toFixed(0)}%`;
        const growthNote = firstRevenueRow && lastRevenueRow
            ? `${firstRevenueRow.label} -> ${lastRevenueRow.label}`
            : 'Selected range';
        renderInsightCards('baRevenueInsights', [
            {
                label: 'Total Revenue',
                value: shortMoney(revenueRows.total),
                note: 'For selected range',
                tone: 'blue',
            },
            {
                label: 'Avg Monthly Revenue',
                value: shortMoney(revenueRows.average),
                note: 'Gross service amount',
                tone: 'blue',
            },
            {
                label: 'Peak Month',
                value: revenueRows.peak?.period?.label || revenueRows.peak?.label || '-',
                note: revenueRows.peak ? `${shortMoney(revenueRows.peak.revenue)} revenue` : 'No revenue yet',
                tone: 'blue',
                onClick: revenueRows.peak?.period ? () => handleRevenuePeriodClick(revenueRows.peak.period) : null,
                isSelected: !!revenueRows.peak?.period && analyticsFilters.revenuePeriod?.label === revenueRows.peak?.period?.label,
                hint: revenueRows.peak?.period ? 'Open records' : null,
            },
            {
                label: 'Growth',
                value: growthValue,
                note: growthNote,
                tone: growthPct >= 0 ? 'green' : 'red',
            },
        ]);

        const collectionRows = buildCollectionInsightRows();
        renderInsightCards('baCollectionInsights', [
            {
                label: 'Collected Amount',
                value: shortMoney(collectionRows.collected),
                note: `${collectionRows.collectionRate.toFixed(1)}% collection rate`,
                tone: 'green',
                onClick: () => handleCollectionStatusClick('COLLECTED'),
                hint: 'Open records',
            },
            {
                label: 'Outstanding Amount',
                value: shortMoney(collectionRows.outstanding),
                note: `${number.format(collectionRows.outstandingCases)} cases open`,
                tone: collectionRows.outstanding > 0 ? 'amber' : 'green',
                onClick: collectionRows.outstanding > 0 ? () => handleCollectionStatusClick('OUTSTANDING') : null,
                hint: collectionRows.outstanding > 0 ? 'Open records' : null,
            },
            {
                label: 'Collection Rate',
                value: `${collectionRows.collectionRate.toFixed(1)}%`,
                note: 'Collected vs collectible',
                tone: collectionRows.collectionRate >= 80 ? 'green' : 'amber',
                // Non-clickable: informational only
            },
        ]);
    };

    const renderBreakdown = (targetId, breakdown, order, totalCases) => {
        const target = document.getElementById(targetId);
        if (!target) return;

        target.innerHTML = order.map((key) => {
            const row = breakdown[key] || { count: 0, amount: 0 };
            const percentage = totalCases > 0 ? ((row.count / totalCases) * 100) : 0;
            const tone = statusTone(key);
            const toneColor = tone === 'status-positive'
                ? '#597A5C'
                : tone === 'status-warning'
                    ? '#BF7A49'
                    : tone === 'status-danger'
                        ? '#AE3D31'
                        : '#8EA083';
            return `
                <div class="ba-breakdown-row" style="--ba-breakdown-percent: ${percentage.toFixed(2)}%; --ba-breakdown-tone: ${toneColor};">
                    <span class="ba-breakdown-label"><span class="ba-status-dot ${tone}"></span>${statusLabels[key] || key}</span>
                    <span class="ba-breakdown-count">${pluralizeCase(row.count)}</span>
                    <span class="ba-breakdown-amount">${money.format(row.amount)}</span>
                    <span class="ba-breakdown-percent">${percentage.toFixed(0)}%</span>
                </div>
            `;
        }).join('');
    };

    const renderActiveFilters = () => {
        const target = document.getElementById('baActiveFilters');
        if (!target) return;

        const chips = activeFilterItems();

        target.innerHTML = chips.length
            ? chips.map(({ label, value, rawValue }) => `<span class="ba-active-filter-chip ${filterTone(label, rawValue)}">${label}: ${escapeHtml(value)}</span>`).join('')
            : '<span class="ba-active-filter-empty">No chart filters applied.</span>';

        const clearButton = document.getElementById('baClearFiltersBtn');
        if (clearButton) {
            clearButton.hidden = chips.length === 0;
        }
    };

    const clearSingleAnalyticsFilter = (key) => {
        if (!Object.prototype.hasOwnProperty.call(analyticsFilters, key)) return;
        setAnalyticsFilters({ [key]: null });
    };

    const renderGlobalFilterBar = () => {
        const bar = document.getElementById('baGlobalFilterBar');
        const chipTarget = document.getElementById('baGlobalFilterChips');
        if (!bar || !chipTarget) return;

        const chips = activeFilterItems();
        bar.hidden = chips.length === 0;
        chipTarget.innerHTML = chips.map(({ key, label, value, rawValue }) => `
            <span class="ba-active-filter-chip ${filterTone(label, rawValue)}">
                ${label}: ${escapeHtml(value)}
                <button type="button" class="ba-filter-chip-remove" data-filter-key="${key}" aria-label="Remove ${escapeHtml(label)} filter"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </span>
        `).join('');

        chipTarget.querySelectorAll('[data-filter-key]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                clearSingleAnalyticsFilter(button.dataset.filterKey);
            });
        });
    };

    const renderTabContext = () => {
        const branchLabel = branchLabelForCode(analyticsFilters.branchCode);
        const messages = {
            payment: {
                note: branchLabel ? `Payments for ${branchLabel}.` : 'Paid, partial, and unpaid',
                context: branchLabel ? `This view is filtered by ${branchLabel}.` : '',
            },
            trend: {
                note: branchLabel ? `Revenue trend for ${branchLabel}.` : 'Total revenue movement',
                context: branchLabel ? `This view is filtered by ${branchLabel}.` : '',
            },
            collection: {
                note: branchLabel ? `Collections for ${branchLabel}.` : 'Collected vs outstanding',
                context: branchLabel ? `This view is filtered by ${branchLabel}.` : '',
            },
        };

        Object.entries(messages).forEach(([tab, message]) => {
            const context = document.querySelector(`[data-context-tab="${tab}"]`);
            const panel = context?.closest('.ba-panel');
            const note = panel?.querySelector('.ba-panel-note');
            if (note) note.textContent = message.note;
            if (!context) return;

            context.hidden = !message.context;
            context.innerHTML = message.context
                ? `<span>${escapeHtml(message.context)}</span><button type="button" data-clear-branch-filter>View all branches</button>`
                : '';

            context.querySelector('[data-clear-branch-filter]')?.addEventListener('click', () => {
                clearSingleAnalyticsFilter('branchCode');
            });
        });
    };

    const filteredRowsHtml = (cases) => {
        if (!cases.length) {
            const onlyPeriodFilter = analyticsFilters.revenuePeriod
                && !analyticsFilters.branchCode
                && !analyticsFilters.paymentStatus
                && !analyticsFilters.collectionStatus;
            const emptyTitle = onlyPeriodFilter
                ? 'No revenue records found for this period.'
                : 'No cases match the selected filters.';
            const emptyHint = onlyPeriodFilter
                ? 'There are no cases recorded for the selected revenue period.'
                : 'Try clearing filters or selecting a different chart item.';
            return `
                <tr>
                    <td colspan="9" class="ba-empty-cell">
                        <div class="ba-empty-state">
                            <strong>${emptyTitle}</strong>
                            <span>${emptyHint}</span>
                        </div>
                    </td>
                </tr>
            `;
        }

        return cases.map((item) => `
            <tr>
                <td>${escapeHtml(item.caseCode || '-')}</td>
                <td>${escapeHtml(item.caseDateLabel || '-')}</td>
                <td>${escapeHtml(item.branchCode ? `${item.branchCode} - ${item.branchName}` : item.branchName || '-')}</td>
                <td>${escapeHtml(item.client || '-')}</td>
                <td>${escapeHtml(item.deceased || '-')}</td>
                <td><span class="ba-status-badge ${statusTone(item.paymentStatus)}">${escapeHtml(statusLabels[item.paymentStatus] || item.paymentStatus || '-')}</span></td>
                <td><span class="ba-status-badge ${statusTone(item.collectionStatus)}">${escapeHtml(statusLabels[item.collectionStatus] || item.collectionStatus || '-')}</span></td>
                <td>${money.format(Number(item.totalAmount || 0))}</td>
                <td>${money.format(Number(item.balanceAmount || 0))}</td>
            </tr>
        `).join('');
    };

    const renderFilteredTable = (cases) => {
        const body = document.getElementById('baFilteredCasesBody');
        if (!body) return;
        body.innerHTML = filteredRowsHtml(cases);
    };

    const syncModalWorkspace = (recordsCount = 0) => {
        const hasRecords = activeFilterCount() > 0;
        if (!hasRecords) {
            modalView = 'chart';
        }

        chartModalBody?.setAttribute('data-view', modalView);
        if (modalRecordCount) {
            modalRecordCount.textContent = `${number.format(recordsCount)} ${recordsCount === 1 ? 'record' : 'records'}`;
        }

        modalViewButtons.forEach((button) => {
            const view = button.dataset.modalView;
            const isRecords = view === 'records';
            button.disabled = isRecords && !hasRecords;
            button.classList.toggle('is-active', view === modalView);
            button.setAttribute('aria-pressed', view === modalView ? 'true' : 'false');
        });
    };

    const setModalView = (view) => {
        if (view === 'records' && activeFilterCount() === 0) return;
        modalView = view === 'records' ? 'records' : 'chart';
        syncModalWorkspace(modalDrilldown?.hidden ? 0 : getFilteredCases().length);
    };

    const renderModalDrilldown = (cases, filteredSummary, title, subtitle, tableTitle) => {
        if (!modalDrilldown || !chartModalBody) return;

        const filtersCount = activeFilterCount();
        chartModalBody.classList.toggle('has-drilldown', filtersCount > 0);
        modalDrilldown.hidden = filtersCount === 0;
        if (filtersCount === 0) {
            modalDrilldown.textContent = '';
            syncModalWorkspace(0);
            return;
        }

        syncModalWorkspace(cases.length);
        const activeChips = activeFilterItems();
        const chipHtml = activeChips.map(({ label, value, rawValue }) => `<span class="ba-active-filter-chip ${filterTone(label, rawValue)}">${label}: ${escapeHtml(value)}</span>`).join('');

        modalDrilldown.innerHTML = `
            <div class="ba-modal-drilldown__head">
                <div>
                    <span class="ba-modal-drilldown__eyebrow">
                        <i class="bi bi-arrow-down-circle"></i>
                        Matching records
                    </span>
                    <h4 class="ba-modal-drilldown__title">${escapeHtml(title)}</h4>
                    <p class="ba-modal-drilldown__subtitle">${escapeHtml(subtitle)}</p>
                </div>
                <div class="ba-active-filters">${chipHtml}</div>
            </div>
            <div class="ba-modal-drilldown__content">
                <div class="ba-modal-drilldown__kpis">
                    <article class="ba-modal-kpi">
                        <span>Total Revenue</span>
                        <strong>${money.format(filteredSummary.totalRevenue)}</strong>
                    </article>
                    <article class="ba-modal-kpi">
                        <span>Total Cases</span>
                        <strong>${number.format(filteredSummary.totalCases)}</strong>
                    </article>
                    <article class="ba-modal-kpi">
                        <span>Average per Case</span>
                        <strong>${money.format(filteredSummary.averagePerCase)}</strong>
                    </article>
                    <article class="ba-modal-kpi">
                        <span>Outstanding</span>
                        <strong>${money.format(filteredSummary.totalOutstanding)}</strong>
                    </article>
                </div>
                <div class="ba-compare-card">
                    <div class="ba-compare-title">
                        <span>${escapeHtml(filtersCount > 0 ? tableTitle : 'Matching Cases')}</span>
                        <span class="ba-table-scroll-hint">Scroll sideways for all columns</span>
                    </div>
                    <div class="ba-compare-table-wrap">
                        <table class="ba-compare-table ba-cases-table">
                            <thead>
                                <tr>
                                    <th>Case</th>
                                    <th>Date</th>
                                    <th>Branch</th>
                                    <th>Client</th>
                                    <th>Deceased</th>
                                    <th>Payment</th>
                                    <th>Collection</th>
                                    <th>Total Revenue</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>${filteredRowsHtml(cases)}</tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    };

    const updateMasterRecordsLink = () => {
        const button = document.getElementById('baMasterRecordsBtn');
        if (!button) return;

        button.onclick = () => {
            const params = new URLSearchParams();
            const branch = analyticsFilters.branchCode
                ? allAnalyticsCases.find((item) => item.branchCode === analyticsFilters.branchCode)
                : null;

            if (branch?.branchId || globalBranchId) params.set('branch_id', branch?.branchId || globalBranchId);
            if (analyticsFilters.paymentStatus) params.set('payment_status', analyticsFilters.paymentStatus);
            if (analyticsFilters.revenuePeriod?.type === 'day') {
                params.set('date_preset', 'CUSTOM');
                params.set('date_from', analyticsFilters.revenuePeriod.date);
                params.set('date_to', analyticsFilters.revenuePeriod.date);
            } else if (analyticsFilters.revenuePeriod?.type === 'range') {
                params.set('date_preset', 'CUSTOM');
                params.set('date_from', analyticsFilters.revenuePeriod.start);
                params.set('date_to', analyticsFilters.revenuePeriod.end);
            } else {
                params.set('date_preset', 'CUSTOM');
                params.set('date_from', globalDateFrom);
                params.set('date_to', globalDateTo);
            }

            window.location.href = `${masterCaseRecordsUrl}?${params.toString()}`;
        };
    };

    const updateChartHighlights = () => {
        Object.values(charts).forEach((chart) => {
            chart.update('none');
        });
    };

    const updateBranchRankingSelection = () => {
        document.querySelectorAll('.ba-ranking-row').forEach((row) => {
            row.classList.toggle('is-selected', row.dataset.branchCode === analyticsFilters.branchCode);
        });
    };

    const renderAnalyticsResults = () => {
        const filteredCases = getFilteredCases();
        const filteredSummary = summarizeCases(filteredCases);
        const filtersCount = activeFilterCount();
        const branchLabel = branchLabelForCode(analyticsFilters.branchCode);
        const paymentLabel = statusSentenceLabels[analyticsFilters.paymentStatus] || analyticsFilters.paymentStatus;
        const collectionLabel = statusSentenceLabels[analyticsFilters.collectionStatus] || analyticsFilters.collectionStatus;
        const periodLabel = analyticsFilters.revenuePeriod?.label || '';

        let title = 'Overall Branch Analytics Summary';
        if (filtersCount === 1 && analyticsFilters.branchCode) title = `${branchLabel} Summary`;
        if (filtersCount === 1 && analyticsFilters.paymentStatus) title = `${paymentLabel} Payment Summary`;
        if (filtersCount === 1 && analyticsFilters.collectionStatus) title = `${collectionLabel} Collection Summary`;
        if (filtersCount === 1 && analyticsFilters.revenuePeriod) title = `${periodLabel} Revenue Summary`;
        if (filtersCount > 1) title = 'Filtered Analytics Summary';

        let subtitle = 'Showing all cases for the selected date range.';
        if (filtersCount === 1 && analyticsFilters.branchCode) {
            subtitle = `Showing ${pluralizeCase(filteredSummary.totalCases)} for ${branchLabel}.`;
        } else if (filtersCount === 1 && analyticsFilters.paymentStatus) {
            subtitle = `Showing ${pluralizeCase(filteredSummary.totalCases)} with ${paymentLabel} payment across all branches.`;
        } else if (filtersCount === 1 && analyticsFilters.collectionStatus) {
            subtitle = `Showing ${pluralizeCase(filteredSummary.totalCases)} ${collectionLabel} collection cases.`;
        } else if (filtersCount === 1 && analyticsFilters.revenuePeriod) {
            subtitle = `Showing revenue and cases for ${periodLabel}.`;
        } else if (filtersCount > 1 && analyticsFilters.branchCode && analyticsFilters.paymentStatus) {
            subtitle = `Showing ${number.format(filteredSummary.totalCases)} ${paymentLabel} payment ${filteredSummary.totalCases === 1 ? 'case' : 'cases'} for ${branchLabel}.`;
        } else if (filtersCount > 1) {
            subtitle = `Showing ${pluralizeCase(filteredSummary.totalCases)} matching the current chart filters.`;
        }

        let tableTitle = 'All Cases';
        if (filtersCount === 1 && analyticsFilters.paymentStatus) tableTitle = `${paymentLabel} Payment Cases`;
        if (filtersCount === 1 && analyticsFilters.branchCode) tableTitle = `Cases from ${analyticsFilters.branchCode}`;
        if (filtersCount === 1 && analyticsFilters.collectionStatus) tableTitle = `${collectionLabel} Collection Cases`;
        if (filtersCount === 1 && analyticsFilters.revenuePeriod) tableTitle = `${periodLabel} Cases`;
        if (filtersCount > 1) tableTitle = 'Cases matching current filters';

        document.getElementById('baDrilldownTitle').textContent = title;
        document.getElementById('baDrilldownSubtitle').textContent = subtitle;
        document.getElementById('baCasesTableTitle').textContent = tableTitle;
        document.getElementById('baFilteredRevenue').textContent = money.format(filteredSummary.totalRevenue);
        document.getElementById('baFilteredCases').textContent = number.format(filteredSummary.totalCases);
        document.getElementById('baFilteredAverage').textContent = money.format(filteredSummary.averagePerCase);
        document.getElementById('baFilteredOutstanding').textContent = money.format(filteredSummary.totalOutstanding);
        document.querySelector('.ba-drilldown')?.classList.toggle('has-active-filter', filtersCount > 0);
        document.getElementById('baOutstandingCard')?.classList.toggle('is-zero', filteredSummary.totalOutstanding <= 0);

        renderActiveFilters();
        renderBreakdown('baPaymentBreakdown', filteredSummary.paymentBreakdown, ['PAID', 'PARTIAL', 'UNPAID'], filteredSummary.totalCases);
        renderBreakdown('baCollectionBreakdown', filteredSummary.collectionBreakdown, ['COLLECTED', 'OUTSTANDING'], filteredSummary.totalCases);
        renderFilteredTable(filteredCases);
        updateMasterRecordsLink();
        updateChartHighlights();
        updateBranchRankingSelection();
        renderGlobalFilterBar();
        renderTabContext();
        if (activeModalChart) {
            renderModalDrilldown(filteredCases, filteredSummary, title, subtitle, tableTitle);
        }
    };

    const setAnalyticsFilters = (nextFilters, options = {}) => {
        analyticsFilters = { ...analyticsFilters, ...nextFilters };
        renderAnalyticsResults();
        if (options.expandChartId) {
            setTimeout(() => {
                openChartModalById(options.expandChartId, { focusDrilldown: true }).catch(() => {});
            }, 0);
        }
    };

    const handleBranchClick = (branchCode, expandChartId = null, forceSelect = false) => {
        setAnalyticsFilters(
            { branchCode: !forceSelect && analyticsFilters.branchCode === branchCode ? null : branchCode },
            { expandChartId }
        );
    };

    const handlePaymentStatusClick = (status, expandChartId = null, forceSelect = false) => {
        setAnalyticsFilters(
            { paymentStatus: !forceSelect && analyticsFilters.paymentStatus === status ? null : status },
            { expandChartId }
        );
    };

    const handleRevenuePeriodClick = (period, expandChartId = null, forceSelect = false) => {
        setAnalyticsFilters(
            { revenuePeriod: !forceSelect && analyticsFilters.revenuePeriod?.label === period?.label ? null : period },
            { expandChartId }
        );
    };

    const handleCollectionStatusClick = (status, expandChartId = null, forceSelect = false) => {
        setAnalyticsFilters(
            { collectionStatus: !forceSelect && analyticsFilters.collectionStatus === status ? null : status },
            { expandChartId }
        );
    };

    const clearAnalyticsFilters = () => {
        setAnalyticsFilters({
            branchCode: null,
            paymentStatus: null,
            revenuePeriod: null,
            collectionStatus: null,
        });
    };

    document.getElementById('baClearFiltersBtn')?.addEventListener('click', clearAnalyticsFilters);
    document.getElementById('baGlobalClearFiltersBtn')?.addEventListener('click', clearAnalyticsFilters);
    document.querySelectorAll('.ba-ranking-row').forEach((row) => {
        const selectBranchRow = () => {
            if (row.dataset.branchCode) {
                handleBranchClick(row.dataset.branchCode);
            }
        };

        row.addEventListener('click', selectBranchRow);
        row.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            selectBranchRow();
        });
    });

    const fadeHex = (hex, alpha) => {
        const normalized = String(hex).replace('#', '');
        const bigint = parseInt(normalized.length === 3
            ? normalized.split('').map((char) => char + char).join('')
            : normalized, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    };

    const branchBarColor = (ctx, color) => {
        const branchCode = branchMeta[ctx.dataIndex]?.code;
        return analyticsFilters.branchCode && branchCode !== analyticsFilters.branchCode
            ? fadeHex(color, 0.40)
            : color;
    };

    const periodBarColor = (ctx, color) => {
        const period = parsePeriodFromLabel(periodLabels[ctx.dataIndex]);
        return analyticsFilters.revenuePeriod && period?.label !== analyticsFilters.revenuePeriod.label
            ? fadeHex(color, 0.40)
            : color;
    };

    const sharedOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                labels: {
                    color: textColor,
                    font: { size: 11, weight: '600' },
                    usePointStyle: true,
                    boxWidth: 8,
                },
            },
            tooltip: {
                backgroundColor: isDark ? '#10253a' : chartTheme.surface,
                titleColor: isDark ? '#fff' : chartTheme.text,
                bodyColor: isDark ? '#fff' : chartTheme.text,
                borderColor: isDark ? '#315f9b' : chartTheme.border,
                borderWidth: 1,
                displayColors: true,
            },
        },
    };

    const loadChartJs = () => {
        if (window.Chart) {
            return Promise.resolve(window.Chart);
        }
        if (chartLoader) {
            return chartLoader;
        }

        chartLoader = new Promise((resolve, reject) => {
            const existing = document.querySelector('script[data-chartjs-loader]');
            if (existing) {
                existing.addEventListener('load', () => resolve(window.Chart), { once: true });
                existing.addEventListener('error', reject, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.async = true;
            script.dataset.chartjsLoader = 'true';
            script.onload = () => resolve(window.Chart);
            script.onerror = () => reject(new Error('Unable to load Chart.js'));
            document.head.appendChild(script);
        });

        return chartLoader;
    };

    const buildChart = async (id, config) => {
        const el = document.getElementById(id);
        if (!el || charts[id]) return charts[id];

        const Chart = await loadChartJs();
        charts[id] = new Chart(el, config);
        return charts[id];
    };

    const buildPrimaryChart = () => {
        if (payload.mode === 'all') {
            return buildChart('serviceCasesChart', {
                type: 'bar',
                data: {
                    labels: barAxisLabels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Total Service Amount',
                            data: payload.bar.revenue ?? [],
                            backgroundColor: (ctx) => branchBarColor(ctx, chartTheme.primary),
                            borderColor: (ctx) => branchBarColor(ctx, chartTheme.primary),
                            borderWidth: 1,
                            borderRadius: 6,
                            yAxisID: 'yRevenue',
                        },
                        {
                            type: 'bar',
                            label: 'Total Cases',
                            data: payload.bar.volume ?? [],
                            backgroundColor: (ctx) => branchBarColor(ctx, chartTheme.secondary),
                            borderColor: (ctx) => branchBarColor(ctx, chartTheme.secondary),
                            borderWidth: 1,
                            borderRadius: 6,
                            yAxisID: 'yCases',
                        },
                    ],
                },
                options: {
                    ...sharedOptions,
                    onHover: (event, elements) => {
                        if (event.native?.target) event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                    },
                    onClick: (event, elements, chart) => {
                        const hit = elements?.[0] || chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true)?.[0];
                        if (!hit) return;
                        const branchCode = branchMeta[hit.index]?.code;
                        if (branchCode) handleBranchClick(branchCode, 'serviceCasesChart', true);
                    },
                    scales: {
                        yRevenue: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            title: {
                                display: true,
                                text: 'Revenue (PHP)',
                                color: textColor,
                                font: { size: 11, weight: '700' },
                            },
                            ticks: {
                                color: textColor,
                                callback: (value) => money.format(Number(value)),
                            },
                        },
                        yCases: {
                            beginAtZero: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            title: {
                                display: true,
                                text: 'Total Cases',
                                color: textColor,
                                font: { size: 11, weight: '700' },
                            },
                            ticks: {
                                precision: 0,
                                color: textColor,
                                callback: (value) => number.format(Number(value)),
                            },
                        },
                        x: {
                            grid: { display: false },
                            title: {
                                display: true,
                                text: 'Branches',
                                color: textColor,
                                font: { size: 11, weight: '700' },
                            },
                            ticks: {
                                color: textColor,
                                maxRotation: 0,
                                minRotation: 0,
                            },
                        },
                    },
                    plugins: {
                        ...sharedOptions.plugins,
                        tooltip: {
                            ...sharedOptions.plugins.tooltip,
                            callbacks: {
                                title: (items) => {
                                    const idx = items?.[0]?.dataIndex ?? -1;
                                    return barTooltipLabels[idx] ?? items?.[0]?.label ?? '';
                                },
                                label: (ctx) => {
                                    if (ctx.dataset.label === 'Total Service Amount') {
                                        return `${ctx.dataset.label}: ${money.format(Number(ctx.raw || 0))}`;
                                    }
                                    return `${ctx.dataset.label}: ${number.format(Number(ctx.raw || 0))}`;
                                },
                            },
                        },
                    },
                },
            });
        }

        return buildChart('branchPerformanceChart', {
            type: 'bar',
            data: {
                labels: periodLabels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Total Service Amount',
                        data: periodServiceAmounts,
                        backgroundColor: (ctx) => periodBarColor(ctx, chartTheme.primary),
                        borderColor: (ctx) => periodBarColor(ctx, chartTheme.primary),
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'yRevenue',
                    },
                    {
                        type: 'bar',
                        label: 'Total Cases',
                        data: periodCases,
                        backgroundColor: (ctx) => periodBarColor(ctx, chartTheme.secondary),
                        borderColor: (ctx) => periodBarColor(ctx, chartTheme.secondary),
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'yCases',
                    },
                ],
            },
            options: {
                ...sharedOptions,
                onHover: (event, elements) => {
                    if (event.native?.target) event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
                onClick: (event, elements, chart) => {
                    const hit = elements?.[0] || chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true)?.[0];
                    if (!hit) return;
                    const period = parsePeriodFromLabel(periodLabels[hit.index]);
                    if (period) handleRevenuePeriodClick(period, 'branchPerformanceChart', true);
                },
                scales: {
                        yRevenue: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            title: {
                                display: true,
                                text: 'Revenue (PHP)',
                                color: textColor,
                                font: { size: 11, weight: '700' },
                            },
                            ticks: {
                                color: textColor,
                                callback: (value) => money.format(Number(value)),
                        },
                    },
                    yCases: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        title: {
                            display: true,
                            text: 'Total Cases',
                            color: textColor,
                            font: { size: 11, weight: '700' },
                        },
                        ticks: {
                            precision: 0,
                            color: textColor,
                            callback: (value) => number.format(Number(value)),
                        },
                    },
                    x: {
                        grid: { display: false },
                        title: {
                            display: true,
                            text: 'Periods',
                            color: textColor,
                            font: { size: 11, weight: '700' },
                        },
                        ticks: { color: textColor },
                    },
                },
                plugins: {
                    ...sharedOptions.plugins,
                    tooltip: {
                        ...sharedOptions.plugins.tooltip,
                        callbacks: {
                            label: (ctx) => {
                                if (ctx.dataset.label === 'Total Service Amount') {
                                    return `${ctx.dataset.label}: ${money.format(Number(ctx.raw || 0))}`;
                                }
                                return `${ctx.dataset.label}: ${number.format(Number(ctx.raw || 0))}`;
                            },
                        },
                    },
                },
            },
        });
    };

    const billedTotal = summary.totalCollected + summary.totalOutstanding;
    const collectionRate = billedTotal > 0 ? ((summary.totalCollected / billedTotal) * 100) : 0;
    const doughnutPercentLabels = {
        id: 'doughnutPercentLabels',
        afterDatasetsDraw(chart) {
            const dataset = chart.data.datasets?.[0];
            const values = dataset?.data?.map((value) => Number(value || 0)) || [];
            const total = values.reduce((sum, value) => sum + value, 0);
            if (!total) return;

            const { ctx } = chart;
            const meta = chart.getDatasetMeta(0);
            ctx.save();
            ctx.font = '700 12px Inter, system-ui, sans-serif';
            ctx.fillStyle = '#FFFDF8';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.shadowColor = 'rgba(35, 40, 33, 0.24)';
            ctx.shadowBlur = 2;

            meta.data.forEach((arc, index) => {
                const value = values[index] || 0;
                const percent = total > 0 ? Math.round((value / total) * 100) : 0;
                if (!percent || percent < 4) return;

                const position = arc.tooltipPosition();
                ctx.fillText(`${percent}%`, position.x, position.y);
            });

            ctx.restore();
        },
    };
    const chartFactories = {
        paymentChart: () => buildChart('paymentChart', {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Partial', 'Unpaid'],
                datasets: [
                    {
                        data: [summary.status.paid, summary.status.partial, summary.status.unpaid],
                        backgroundColor: (ctx) => {
                            const statuses = ['PAID', 'PARTIAL', 'UNPAID'];
                            const colors = [chartTheme.success, chartTheme.warning, chartTheme.danger];
                            const index = ctx.dataIndex ?? 0;
                            return analyticsFilters.paymentStatus && statuses[index] !== analyticsFilters.paymentStatus
                                ? fadeHex(colors[index], 0.40)
                                : colors[index];
                        },
                        borderColor: isDark ? '#17283b' : chartTheme.surface,
                        borderWidth: 2,
                        hoverOffset: 6,
                    },
                ],
            },
            plugins: [doughnutPercentLabels],
            options: {
                ...sharedOptions,
                cutout: '60%',
                onHover: (event, elements) => {
                    if (event.native?.target) event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
                onClick: (event, elements, chart) => {
                    const hit = elements?.[0] || chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true)?.[0];
                    const statuses = ['PAID', 'PARTIAL', 'UNPAID'];
                    if (hit && statuses[hit.index]) handlePaymentStatusClick(statuses[hit.index], 'paymentChart', true);
                },
                plugins: {
                    ...sharedOptions.plugins,
                    tooltip: {
                        ...sharedOptions.plugins.tooltip,
                        callbacks: {
                            label: (ctx) => `${ctx.label}: ${number.format(Number(ctx.raw || 0))} cases`,
                        },
                    },
                    legend: {
                        labels: {
                            ...sharedOptions.plugins.legend.labels,
                            generateLabels: (chart) => {
                                const values = chart.data.datasets?.[0]?.data?.map((value) => Number(value || 0)) || [];
                                const total = values.reduce((sum, value) => sum + value, 0);
                                const colors = [chartTheme.success, chartTheme.warning, chartTheme.danger];

                                return chart.data.labels.map((label, index) => ({
                                    text: `${label} (${number.format(values[index] || 0)}/${number.format(total)})`,
                                    fillStyle: colors[index],
                                    strokeStyle: colors[index],
                                    lineWidth: 0,
                                    hidden: false,
                                    index,
                                    pointStyle: 'circle',
                                }));
                            },
                        },
                    },
                },
            },
        }),
        trendChart: () => buildChart('trendChart', {
            type: 'line',
            data: {
                labels: buildTrendSeries().labels,
                datasets: [
                    {
                        label: 'Total Service Amount',
                        data: buildTrendSeries().revenue,
                        borderColor: chartTheme.primary,
                        backgroundColor: 'rgba(52, 67, 50, 0.10)',
                        borderWidth: 2.5,
                        fill: false,
                        tension: 0.32,
                        pointRadius: (ctx) => {
                            const period = parsePeriodFromLabel(buildTrendSeries().labels[ctx.dataIndex]);
                            return analyticsFilters.revenuePeriod?.label === period?.label ? 5.5 : 2.8;
                        },
                        pointHoverRadius: 4.5,
                        pointBackgroundColor: (ctx) => {
                            const period = parsePeriodFromLabel(buildTrendSeries().labels[ctx.dataIndex]);
                            return analyticsFilters.revenuePeriod && analyticsFilters.revenuePeriod.label !== period?.label
                                ? fadeHex(chartTheme.primary, 0.40)
                                : chartTheme.primary;
                        },
                        pointBorderColor: '#FFFDF8',
                        pointBorderWidth: 2,
                    },
                ],
            },
            options: {
                ...sharedOptions,
                onHover: (event, elements) => {
                    if (event.native?.target) event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
                onClick: (event, elements, chart) => {
                    const hit = elements?.[0] || chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true)?.[0];
                    if (!hit) return;
                    const period = parsePeriodFromLabel(buildTrendSeries().labels[hit.index]);
                    if (period) handleRevenuePeriodClick(period, 'trendChart', true);
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        title: {
                            display: true,
                            text: 'Revenue (PHP)',
                            color: textColor,
                            font: { size: 11, weight: '700' },
                        },
                        ticks: {
                            color: textColor,
                            callback: (value) => money.format(Number(value)),
                        },
                    },
                    x: {
                        grid: { display: false },
                        title: {
                            display: true,
                            text: 'Periods',
                            color: textColor,
                            font: { size: 11, weight: '700' },
                        },
                        ticks: { color: textColor },
                    },
                },
                plugins: {
                    ...sharedOptions.plugins,
                    tooltip: {
                        ...sharedOptions.plugins.tooltip,
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: ${money.format(Number(ctx.raw || 0))}`,
                        },
                    },
                },
            },
        }),
        collectionChart: () => {
            const isByPeriod = collectionChartView === 'by_period';
            const collectionStatusForHit = (hit) => {
                if (!hit) return null;

                if (isByPeriod) {
                    return ['COLLECTED', 'OUTSTANDING'][hit.datasetIndex] || null;
                }

                return ['COLLECTED', 'OUTSTANDING', null][hit.index] || null;
            };
            const periodForHit = (hit) => {
                if (!isByPeriod || !hit) return null;
                return parsePeriodFromLabel(periodLabels[hit.index]);
            };
            const collectionPeriodColor = (ctx, baseColor, dimmedByStatus) => {
                const period = parsePeriodFromLabel(periodLabels[ctx.dataIndex]);
                const dimmedByPeriod = analyticsFilters.revenuePeriod && period?.label !== analyticsFilters.revenuePeriod.label;

                return dimmedByStatus || dimmedByPeriod ? fadeHex(baseColor, 0.38) : baseColor;
            };
            const periodDatasets = [
                {
                    label: 'Collected',
                    data: periodCollectedAmounts,
                    backgroundColor: (ctx) => collectionPeriodColor(ctx, chartTheme.success, analyticsFilters.collectionStatus === 'OUTSTANDING'),
                    borderColor: (ctx) => collectionPeriodColor(ctx, chartTheme.success, analyticsFilters.collectionStatus === 'OUTSTANDING'),
                    borderRadius: 6,
                },
                {
                    label: 'Outstanding',
                    data: periodOutstandingBalances,
                    backgroundColor: (ctx) => collectionPeriodColor(ctx, chartTheme.warning, analyticsFilters.collectionStatus === 'COLLECTED'),
                    borderColor: (ctx) => collectionPeriodColor(ctx, chartTheme.warning, analyticsFilters.collectionStatus === 'COLLECTED'),
                    borderRadius: 6,
                },
            ];

            return buildChart('collectionChart', {
                type: 'bar',
                data: isByPeriod
                    ? {
                        labels: periodLabels,
                        datasets: periodDatasets,
                    }
                    : {
                        labels: ['Collected', 'Outstanding', 'Total Service Amount'],
                        datasets: [
                            {
                                label: 'Amount',
                                data: [summary.totalCollected, summary.totalOutstanding, summary.totalSales],
                                backgroundColor: (ctx) => {
                                    const statuses = ['COLLECTED', 'OUTSTANDING', null];
                                    const colors = [chartTheme.success, chartTheme.warning, chartTheme.primary];
                                    const index = ctx.dataIndex ?? 0;
                                    return analyticsFilters.collectionStatus && statuses[index] !== analyticsFilters.collectionStatus
                                        ? fadeHex(colors[index], 0.40)
                                        : colors[index];
                                },
                                borderRadius: 6,
                            },
                        ],
                    },
                options: {
                    ...sharedOptions,
                    indexAxis: isByPeriod ? 'x' : 'y',
                    onHover: (event, elements) => {
                        if (event.native?.target) {
                            const status = collectionStatusForHit(elements?.[0]);
                            event.native.target.style.cursor = status ? 'pointer' : 'default';
                        }
                    },
                    onClick: (event, elements, chart) => {
                        const hit = elements?.[0] || chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true)?.[0];
                        if (!hit) return;

                        const status = collectionStatusForHit(hit);
                        if (status) {
                            if (isByPeriod) {
                                setAnalyticsFilters({
                                    collectionStatus: status,
                                    revenuePeriod: periodForHit(hit),
                                });
                            } else {
                                handleCollectionStatusClick(status);
                            }
                            return;
                        }

                        if (!isByPeriod) {
                            setAnalyticsFilters({ collectionStatus: null });
                        }
                    },
                    scales: {
                        x: isByPeriod
                            ? {
                                grid: { color: gridColor },
                                title: {
                                    display: true,
                                    text: 'Periods',
                                    color: textColor,
                                    font: { size: 11, weight: '700' },
                                },
                                ticks: { color: textColor },
                            }
                            : {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                title: {
                                    display: true,
                                    text: 'Amount (PHP)',
                                    color: textColor,
                                    font: { size: 11, weight: '700' },
                                },
                                ticks: {
                                    color: textColor,
                                    maxRotation: 0,
                                    minRotation: 0,
                                    callback: (value) => shortPeso(value),
                                },
                            },
                        y: isByPeriod
                            ? {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                title: {
                                    display: true,
                                    text: 'Amount (PHP)',
                                    color: textColor,
                                    font: { size: 11, weight: '700' },
                                },
                                ticks: {
                                    color: textColor,
                                    callback: (value) => shortPeso(value),
                                },
                            }
                            : {
                                grid: { display: false },
                                title: {
                                    display: true,
                                    text: 'Collection Status',
                                    color: textColor,
                                    font: { size: 11, weight: '700' },
                                },
                                ticks: { color: textColor },
                            },
                    },
                    plugins: {
                        ...sharedOptions.plugins,
                        subtitle: {
                            display: true,
                            text: isByPeriod
                                ? `Collected vs outstanding by period · Collection Rate: ${collectionRate.toFixed(1)}%`
                                : `Collection Rate: ${collectionRate.toFixed(1)}%`,
                            color: textColor,
                            font: { size: 11, weight: '700' },
                            padding: { bottom: 8 },
                        },
                        tooltip: {
                            ...sharedOptions.plugins.tooltip,
                            callbacks: {
                                label: (ctx) => isByPeriod
                                    ? `${ctx.dataset.label}: ${money.format(Number(ctx.raw || 0))}`
                                    : `${ctx.label}: ${money.format(Number(ctx.raw || 0))}`,
                            },
                        },
                        legend: {
                            display: isByPeriod,
                            labels: sharedOptions.plugins.legend.labels,
                        },
                    },
                },
            });
        },
    };

    const ensureChart = async (chartId) => {
        if (charts[chartId]) {
            return charts[chartId];
        }

        if (chartId === 'serviceCasesChart' || chartId === 'branchPerformanceChart') {
            return buildPrimaryChart();
        }

        const factory = chartFactories[chartId];
        if (!factory) {
            return null;
        }

        return factory();
    };

    const syncCollectionChartMode = () => {
        document.querySelectorAll('[data-collection-view]').forEach((button) => {
            const active = button.dataset.collectionView === collectionChartView;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        const collectionNote = document.querySelector('#ba-panel-collection .ba-panel-note');
        if (collectionNote) {
            collectionNote.textContent = collectionChartView === 'by_period'
                ? 'Collected vs outstanding by period'
                : (collectionNote.dataset.defaultNote || 'Collected vs outstanding');
        }
    };

    const rebuildCollectionChart = async () => {
        if (charts.collectionChart) {
            charts.collectionChart.destroy();
            delete charts.collectionChart;
        }

        const chart = await ensureChart('collectionChart');
        if (chart) {
            requestAnimationFrame(() => chart.resize());
        }
    };

    document.querySelectorAll('[data-collection-view]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextView = button.dataset.collectionView;
            if (!['snapshot', 'by_period'].includes(nextView) || nextView === collectionChartView) return;

            collectionChartView = nextView;
            sessionStorage.setItem(collectionViewStorageKey, collectionChartView);
            syncCollectionChartMode();
            rebuildCollectionChart().catch(() => {});
        });
    });

    syncCollectionChartMode();

    const tabButtons = Array.from(document.querySelectorAll('.ba-tab-btn'));
    const panels = Array.from(document.querySelectorAll('.ba-panel'));
    const tabStorageKey = 'ownerBranchAnalytics.activeTab';
    const validPanelIds = new Set(tabButtons.map((button) => button.dataset.target).filter(Boolean));

    const rememberActivePanel = (targetId) => {
        if (!validPanelIds.has(targetId)) return;
        sessionStorage.setItem(tabStorageKey, targetId);

        const url = new URL(window.location.href);
        url.searchParams.set('analytics_tab', targetId);
        window.history.replaceState({}, '', url);
    };

    const syncGlobalFilterControls = (targetId) => {
        if (!validPanelIds.has(targetId)) return;

        document.querySelectorAll('.ba-seg a[href]').forEach((link) => {
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('analytics_tab', targetId);
            link.href = url.toString();
        });

        document.querySelectorAll('form[action*="branch-analytics"]').forEach((form) => {
            let input = form.querySelector('input[name="analytics_tab"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'analytics_tab';
                form.appendChild(input);
            }
            input.value = targetId;
        });
    };

    const clearFiltersOutsidePanel = (targetId) => {
        const allowedByPanel = {
            'ba-panel-performance': ['branchCode'],
            'ba-panel-payment': ['paymentStatus'],
            'ba-panel-collection': ['collectionStatus', 'revenuePeriod'],
            'ba-panel-trend': ['revenuePeriod'],
        };
        const allowed = new Set(allowedByPanel[targetId] || []);
        const scopedKeys = ['branchCode', 'paymentStatus', 'collectionStatus', 'revenuePeriod'];
        let changed = false;

        scopedKeys.forEach((key) => {
            if (!allowed.has(key) && analyticsFilters[key]) {
                analyticsFilters[key] = null;
                changed = true;
            }
        });

        if (changed) {
            renderAnalyticsResults();
        }
    };

    const activatePanel = async (targetId, options = {}) => {
        if (!validPanelIds.has(targetId)) {
            targetId = 'ba-panel-performance';
        }

        document.querySelector('.ba-workspace')?.setAttribute('data-active-analytics-panel', targetId);

        tabButtons.forEach((btn) => {
            const active = btn.dataset.target === targetId;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            const active = panel.id === targetId;
            panel.classList.toggle('active', active);
            panel.hidden = !active;
        });

        const activePanel = document.getElementById(targetId);
        const canvases = Array.from(activePanel?.querySelectorAll('canvas') || []);
        await Promise.all(canvases.map(async (canvas) => {
            const chart = await ensureChart(canvas.id);
            if (chart) {
                requestAnimationFrame(() => chart.resize());
            }
        }));

        rememberActivePanel(targetId);
        syncGlobalFilterControls(targetId);
        clearFiltersOutsidePanel(targetId);

        if (options.scrollIntoView) {
            activePanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activatePanel(button.dataset.target, { scrollIntoView: true }).catch(() => {});
        });
    });

    document.querySelectorAll('.ba-kpi-jump').forEach((button) => {
        button.addEventListener('click', () => {
            activatePanel(button.dataset.target, { scrollIntoView: true }).catch(() => {});
        });
    });

    const requestedPanelId = new URLSearchParams(window.location.search).get('analytics_tab');
    const storedPanelId = sessionStorage.getItem(tabStorageKey);
    const initialPanelId = validPanelIds.has(requestedPanelId)
        ? requestedPanelId
        : validPanelIds.has(storedPanelId)
            ? storedPanelId
            : 'ba-panel-performance';
    activatePanel(initialPanelId).catch(() => {});
    renderAnalyticsResults();
    renderAnalyticsInsights();

    const chartFrameFor = (button) => {
        const chartId = button.dataset.chartExpand;
        const canvas = document.getElementById(chartId);
        return canvas ? canvas.closest('.ba-chart-frame') : null;
    };

    const resizeChart = (chart) => {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                chart.resize();
                chart.update('none');
            });
        });
    };

    const setExpandButtonState = (button, isExpanded) => {
        if (!button) return;

        button.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        const label = button.querySelector('span');
        if (label) {
            label.textContent = isExpanded ? 'Exit full screen' : 'Full screen';
        }
    };

    let modalCloseTimer = null;

    const closeChartModal = (immediate = false) => {
        if (!chartModal || !chartModalChartSlot) return;

        if (modalCloseTimer) {
            window.clearTimeout(modalCloseTimer);
            modalCloseTimer = null;
        }

        const closingChart = activeModalChart;
        const finishClose = () => {
            chartModal.classList.remove('is-closing');

            if (closingChart) {
                const {
                    frame,
                    chart,
                    button,
                    placeholder,
                    originalParent,
                    originalNextSibling,
                } = closingChart;

                const placeholderParent = placeholder?.parentNode;
                if (placeholderParent) {
                    placeholderParent.insertBefore(frame, placeholder);
                } else if (originalParent && originalNextSibling?.parentNode === originalParent) {
                    originalParent.insertBefore(frame, originalNextSibling);
                } else if (originalParent) {
                    originalParent.appendChild(frame);
                }

                placeholder?.remove();
                frame.classList.remove('is-in-modal');
                setExpandButtonState(button, false);
                if (button) {
                    button.focus({ preventScroll: true });
                }
                activeModalChart = null;
                resizeChart(chart);
            }

            chartModalChartSlot.textContent = '';
            if (modalDrilldown) {
                modalDrilldown.hidden = true;
                modalDrilldown.textContent = '';
            }
            chartModalBody?.classList.remove('has-drilldown');
            chartModalBody?.setAttribute('data-view', 'chart');
            modalView = 'chart';
            syncModalWorkspace(0);
            chartModal.hidden = true;
            chartModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        };

        if (immediate) {
            finishClose();
            return;
        }

        chartModal.classList.add('is-closing');
        modalCloseTimer = window.setTimeout(finishClose, 150);
    };

    const focusModalDrilldown = () => {
        if (!modalDrilldown || modalDrilldown.hidden || !chartModal || !chartModalBody) return;
        modalView = 'chart';
        syncModalWorkspace(getFilteredCases().length);
        const tableWrap = modalDrilldown.querySelector('.ba-compare-table-wrap');
        if (tableWrap) {
            tableWrap.scrollTop = 0;
            tableWrap.scrollLeft = 0;
        }

        window.requestAnimationFrame(() => {
            chartModalBody.scrollTo({
                top: Math.max(0, modalDrilldown.offsetTop - 12),
                behavior: 'smooth',
            });
        });
    };

    const openChartModal = async (button, options = {}) => {
        if (!chartModal || !chartModalChartSlot || !chartModalTitle) return;

        if (chartModal.parentNode !== document.body) {
            document.body.appendChild(chartModal);
        }

        const chartId = button.dataset.chartExpand;
        const frame = chartFrameFor(button);
        const chart = await ensureChart(chartId);
        if (!frame || !chart) return;

        if (activeModalChart?.chartId === chartId) {
            renderAnalyticsResults();
            resizeChart(chart);
            if (options.focusDrilldown) focusModalDrilldown();
            return;
        }

        if (activeModalChart) {
            closeChartModal(true);
        }

        const placeholder = document.createComment('ba-chart-placeholder');
        const originalParent = frame.parentNode;
        const originalNextSibling = frame.nextSibling;
        if (!originalParent) return;

        originalParent.insertBefore(placeholder, frame);

        chartModalTitle.textContent = button.dataset.chartTitle || 'Analytics Chart';
        modalView = 'chart';
        chartModalBody.setAttribute('data-view', modalView);
        chartModalChartSlot.appendChild(frame);
        frame.classList.add('is-in-modal');
        chartModal.classList.remove('is-closing');
        chartModal.hidden = false;
        chartModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';

        setExpandButtonState(button, true);
        activeModalChart = {
            chartId,
            frame,
            chart,
            button,
            placeholder,
            originalParent,
            originalNextSibling,
        };

        chartModalCloseBtn?.focus({ preventScroll: true });
        renderAnalyticsResults();
        resizeChart(chart);
        if (options.focusDrilldown) focusModalDrilldown();
    };

    const openChartModalById = async (chartId, options = {}) => {
        const button = document.querySelector(`[data-chart-expand="${chartId}"]`);
        if (!button) return;
        await openChartModal(button, options);
    };

    document.querySelectorAll('[data-chart-expand]').forEach((button) => {
        button.setAttribute('aria-expanded', 'false');
        button.addEventListener('click', () => openChartModal(button).catch(() => {}));
    });

    document.querySelectorAll('[data-chart-modal-close]').forEach((control) => {
        control.addEventListener('click', closeChartModal);
    });

    modalViewButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setModalView(button.dataset.modalView);
            if (button.dataset.modalView === 'records') {
                const tableWrap = modalDrilldown?.querySelector('.ba-compare-table-wrap');
                if (tableWrap) {
                    tableWrap.scrollTop = 0;
                    tableWrap.scrollLeft = 0;
                }
            } else if (activeModalChart?.chart) {
                resizeChart(activeModalChart.chart);
            }
        });
    });

    const modalFocusableElements = () => Array.from(chartModal?.querySelectorAll(
        'a[href], button:not([disabled]), select:not([disabled]), textarea:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
    ) || []).filter((el) => {
        const style = window.getComputedStyle(el);
        return style.display !== 'none' && style.visibility !== 'hidden';
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeChartModal();
            return;
        }

        if (event.key !== 'Tab' || !activeModalChart || chartModal?.hidden) {
            return;
        }

        const focusable = modalFocusableElements();
        if (!focusable.length) {
            event.preventDefault();
            chartModalCloseBtn?.focus({ preventScroll: true });
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus({ preventScroll: true });
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus({ preventScroll: true });
        }
    });

    const periodFilter = document.getElementById('baPeriodFilter');
    const datePopover = document.getElementById('baDatePopover');
    if (periodFilter && datePopover) {
        const setCustomRangeState = () => {
            const isCustom = periodFilter.value === 'CUSTOM';
            datePopover.style.display = isCustom ? 'block' : 'none';
            return isCustom;
        };

        periodFilter.addEventListener('change', () => {
            if (setCustomRangeState()) {
                datePopover.querySelector('input')?.focus();
                return;
            }
            periodFilter.form?.submit();
        });

        setCustomRangeState();
    }
})();
</script>
@endsection
