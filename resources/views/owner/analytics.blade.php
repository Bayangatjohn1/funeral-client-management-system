@extends('layouts.panel')

@section('page_title', 'Branch Analytics')
@section('page_desc', 'Analyze branch trends, revenue, and operational metrics.')

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
@endphp

<div class="ba-shell">

    <section class="ba-card ba-workspace">
        <header class="ba-workspace-head">
            <div class="ba-head-row ba-head-row-top">
                <div class="ba-workspace-head-copy">
                    <h3 class="ba-title">Branch Analytics</h3>
                    <p class="ba-subtitle">View branch summaries, payment status, revenue trends, and collection updates.</p>
                </div>

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
                            @foreach (['TODAY', 'THIS_MONTH', 'THIS_YEAR'] as $rangeKey)
                                <a href="{{ $dateRangeLinks[$rangeKey] }}" class="ba-seg-item {{ $range === $rangeKey ? 'active' : '' }}">
                                    {{ ucwords(strtolower(str_replace('_', ' ', $rangeKey))) }}
                                </a>
                            @endforeach
                            <div class="ba-custom-range-wrap">
                                <button
                                    type="button"
                                    id="baCustomRangeBtn"
                                    class="ba-seg-item {{ $isCustomRange ? 'active' : '' }}"
                                    aria-expanded="false"
                                    aria-controls="baDatePopover"
                                    title="Open custom date range"
                                >
                                    <i class="bi bi-calendar3"></i>
                                    <span>Custom Range</span>
                                    <i class="bi bi-chevron-down ba-date-chev"></i>
                                </button>

                                <div class="ba-date-popover" id="baDatePopover" style="display: none;">
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
            </div>

            <div class="ba-head-row ba-head-row-nav">
                <div class="ba-tabs" role="tablist" aria-label="Analytics views">
                    <button class="ba-tab-btn active" data-target="ba-panel-performance" role="tab" aria-selected="true">
                        <i class="bi bi-bar-chart-line"></i>
                        Branch Performance
                    </button>
                    <button class="ba-tab-btn" data-target="ba-panel-payment" role="tab" aria-selected="false">
                        <i class="bi bi-wallet2"></i>
                        Payment Status
                    </button>
                    <button class="ba-tab-btn" data-target="ba-panel-trend" role="tab" aria-selected="false">
                        <i class="bi bi-graph-up-arrow"></i>
                        Gross Revenue Trend
                    </button>
                    <button class="ba-tab-btn" data-target="ba-panel-collection" role="tab" aria-selected="false">
                        <i class="bi bi-cash-stack"></i>
                        Collection Status
                    </button>
                </div>

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
            <button type="button" class="ba-global-clear" id="baGlobalClearFiltersBtn">
                Clear all filters
            </button>
        </div>

        <div class="ba-panels">
            <article class="ba-panel active" id="ba-panel-performance" role="tabpanel">
                @if($chart['mode'] === 'all')
                    <div class="ba-panel-head">
                        <h4 class="ba-panel-title">Branch Performance Overview</h4>
                        <span class="ba-panel-note">Total service amount and case comparison</span>
                    </div>
                    <div class="ba-insight-row" id="baBranchInsights" aria-label="Branch performance insights"></div>
                    <p class="ba-chart-helper"><i class="bi bi-cursor"></i> Shows total service amount and total number of cases per branch for the selected period. Click a branch bar or insight card to view matching cases below.</p>
                    <div class="ba-chart-frame">
                        <canvas id="serviceCasesChart"></canvas>
                    </div>

                    <div class="ba-compare-card">
                        <div class="ba-compare-title">Branch Performance Ranking</div>
                        <div class="ba-compare-table-wrap">
                            <table class="ba-compare-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Branch</th>
                                        <th>Total Revenue</th>
                                        <th>Total Cases</th>
                                        <th>Avg per Case</th>
                                        <th>Payment Mix</th>
                                        <th>Collection Rate</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($branchRankingRows as $index => $row)
                                        <tr class="ba-ranking-row" data-branch-code="{{ $row['branch_code'] }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td title="{{ $row['branch_code'] }} - {{ $row['branch_name'] }}">
                                                {{ $row['branch_code'] }} - {{ $row['branch_name'] }}
                                            </td>
                                            <td>PHP {{ number_format($row['total_revenue'], 2) }}</td>
                                            <td>{{ number_format($row['total_cases']) }}</td>
                                            <td>PHP {{ number_format($row['average_per_case'], 2) }}</td>
                                            <td>
                                                Paid {{ number_format($row['paid_cases']) }} /
                                                Partial {{ number_format($row['partial_cases']) }} /
                                                Unpaid {{ number_format($row['unpaid_cases']) }}
                                            </td>
                                            <td>{{ number_format($row['collection_rate'], 1) }}%</td>
                                            <td>
                                                <button type="button" class="ba-row-filter-btn" data-branch-code="{{ $row['branch_code'] }}">
                                                    View branch
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="ba-empty-cell">No branch performance data available for selected filters.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="ba-branch-perf-head">
                        <h4 class="ba-panel-title">Branch Performance Summary</h4>
                        <p class="ba-panel-subtitle">Operational and financial overview of the selected branch.</p>
                    </div>

                    <div class="ba-branch-kpi-grid">
                        <article class="ba-branch-kpi-card">
                            <span>Total Cases</span>
                            <strong>{{ number_format($totalCases) }}</strong>
                        </article>
                        <article class="ba-branch-kpi-card">
                            <span>Total Service Amount</span>
                            <strong>PHP {{ number_format($totalSales, 2) }}</strong>
                        </article>
                        <article class="ba-branch-kpi-card">
                            <span>Collected Amount</span>
                            <strong>PHP {{ number_format($totalCollected, 2) }}</strong>
                        </article>
                        <article class="ba-branch-kpi-card">
                            <span>Outstanding Balance</span>
                            <strong>PHP {{ number_format($totalOutstanding, 2) }}</strong>
                        </article>
                        <article class="ba-branch-kpi-card">
                            <span>Collection Rate</span>
                            <strong>{{ number_format($overallCollectionRate, 1) }}%</strong>
                        </article>
                        <article class="ba-branch-kpi-card">
                            <span>Average Service Amount per Case</span>
                            <strong>PHP {{ number_format($overallAvgRevenuePerCase, 2) }}</strong>
                        </article>
                    </div>

                    <div class="ba-panel-head">
                        <h4 class="ba-panel-title">Cases and Total Service Amount by Period</h4>
                        <span class="ba-panel-note">Total service amount and case comparison</span>
                    </div>
                    <div class="ba-insight-row" id="baBranchInsights" aria-label="Branch performance insights"></div>
                    <p class="ba-chart-helper"><i class="bi bi-cursor"></i> Shows total service amount and total number of cases by period for the selected branch. Click a period bar or insight card to view matching cases below.</p>
                    <div class="ba-chart-frame">
                        <canvas id="branchPerformanceChart"></canvas>
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
                                        <th>Collected Amount</th>
                                        <th>Outstanding Balance</th>
                                        <th>Collection Rate</th>
                                        <th>Average Service Amount per Case</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($periodLabels as $index => $_periodLabel)
                                        @php
                                            $periodCaseCount = (float) ($periodCases[$index] ?? 0);
                                            $periodService = (float) ($periodServiceAmounts[$index] ?? 0);
                                            $periodCollected = (float) ($periodCollectedAmounts[$index] ?? 0);
                                            $periodOutstanding = (float) ($periodOutstandingBalances[$index] ?? 0);
                                            $periodCollectionRate = ($periodCollected + $periodOutstanding) > 0
                                                ? (($periodCollected / ($periodCollected + $periodOutstanding)) * 100)
                                                : 0;
                                            $periodAvgRevenue = $periodCaseCount > 0 ? ($periodService / $periodCaseCount) : 0;
                                        @endphp
                                        <tr>
                                            <td>{{ (string) ($periodLabels[$index] ?? $_periodLabel ?? '-') }}</td>
                                            <td>{{ number_format($periodCaseCount) }}</td>
                                            <td>PHP {{ number_format($periodService, 2) }}</td>
                                            <td>PHP {{ number_format($periodCollected, 2) }}</td>
                                            <td>PHP {{ number_format($periodOutstanding, 2) }}</td>
                                            <td>{{ number_format($periodCollectionRate, 1) }}%</td>
                                            <td>PHP {{ number_format($periodAvgRevenue, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="ba-empty-cell">No period performance data available for selected filters.</td>
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
                    <span class="ba-panel-note" data-default-note="Paid vs partial vs unpaid case distribution">Paid vs partial vs unpaid case distribution</span>
                </div>
                <div class="ba-tab-context" data-context-tab="payment" hidden></div>
                <div class="ba-insight-row" id="baPaymentInsights" aria-label="Payment status insights"></div>
                <div class="ba-status-pills">
                    <span class="ba-status-pill ba-paid">Paid: {{ number_format($statusCounts['paid']) }}</span>
                    <span class="ba-status-pill ba-partial">Partial: {{ number_format($statusCounts['partial']) }}</span>
                    <span class="ba-status-pill ba-unpaid">Unpaid: {{ number_format($statusCounts['unpaid']) }}</span>
                    <span class="ba-status-pill ba-ongoing">Ongoing: {{ number_format($statusCounts['ongoing']) }}</span>
                </div>
                <div class="ba-chart-frame ba-chart-frame-narrow">
                    <canvas id="paymentChart"></canvas>
                </div>
                <p class="ba-chart-helper"><i class="bi bi-cursor"></i> Shows paid, partial, and unpaid case distribution for the selected period. Click a slice or insight card to view matching cases below.</p>
            </article>

            <article class="ba-panel" id="ba-panel-trend" role="tabpanel" hidden>
                <div class="ba-panel-head">
                    <h4 class="ba-panel-title">Gross Revenue Trend</h4>
                    <span class="ba-panel-note" data-default-note="Trend line for period-based movement analysis">Trend line for period-based movement analysis</span>
                </div>
                <div class="ba-tab-context" data-context-tab="trend" hidden></div>
                <div class="ba-insight-row" id="baRevenueInsights" aria-label="Gross revenue trend insights"></div>
                <div class="ba-chart-frame">
                    <canvas id="trendChart"></canvas>
                </div>
                <p class="ba-chart-helper"><i class="bi bi-cursor"></i> Shows revenue movement by period. Click a point or insight card to view matching cases below.</p>
            </article>

            <article class="ba-panel" id="ba-panel-collection" role="tabpanel" hidden>
                <div class="ba-panel-head">
                    <h4 class="ba-panel-title">Collection Status</h4>
                    <span class="ba-panel-note" data-default-note="Collected vs outstanding amount performance">Collected vs outstanding amount performance</span>
                </div>
                <div class="ba-tab-context" data-context-tab="collection" hidden></div>

                <div class="ba-insight-row" id="baCollectionInsights" aria-label="Collection status insights"></div>
                <div class="ba-chart-frame">
                    <canvas id="collectionChart"></canvas>
                </div>
                <p class="ba-chart-helper"><i class="bi bi-cursor"></i> Shows collected amount, outstanding balance, and total service amount. Click a bar or insight card to view matching cases below.</p>
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

            <div class="ba-breakdown-grid">
                <div class="ba-compare-card">
                    <div class="ba-compare-title">Payment Status Breakdown</div>
                    <div class="ba-breakdown-list" id="baPaymentBreakdown"></div>
                </div>
                <div class="ba-compare-card">
                    <div class="ba-compare-title">Collection Status Breakdown</div>
                    <div class="ba-breakdown-list" id="baCollectionBreakdown"></div>
                </div>
            </div>

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
    border-color: #9fb0c5;
    color: #0f172a;
    background: #f8fafc;
}

.ba-seg-item.active,
.ba-date-btn.active {
    background: #0f172a;
    border-color: #0f172a;
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
    border-color: #9fb0c5;
    color: #0f172a;
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
    border-color: #94a3b8;
    background: #fff;
}

.ba-pop-apply {
    width: auto;
    flex: 1;
    border: 0;
    border-radius: 8px;
    height: 34px;
    background: #0f172a;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
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
    border-color: #9fb0c5;
    color: #0f172a;
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
    background: #f8fafc;
    border-color: #9fb0c5;
    color: #0f172a;
}

.ba-tab-context {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    flex-wrap: wrap;
    border: 1px solid #dbeafe;
    border-radius: 12px;
    background: #eff6ff;
    color: #1d4ed8;
    padding: 0.55rem 0.7rem;
    font-size: 12px;
    font-weight: 700;
}

.ba-tab-context button {
    border: 1px solid #bfdbfe;
    background: #fff;
    color: #1d4ed8;
    border-radius: 8px;
    height: 28px;
    padding: 0 0.55rem;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
}

.ba-tab-context button:hover {
    background: #f8fbff;
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
    color: #0f172a;
    border-color: #9fb0c5;
    background: #f8fafc;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
}

.ba-tab-btn.active {
    background: #0f172a;
    border-color: #0f172a;
    color: #fff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.16);
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
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.ba-chart-frame:hover {
    border-color: #cbd5e1;
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
}

.ba-chart-frame canvas {
    cursor: pointer;
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
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}

.ba-insight-card.is-clickable:hover {
    transform: translateY(-1px);
    border-color: #bfdbfe;
    box-shadow: 0 10px 24px rgba(30, 64, 175, 0.08);
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
    background: #f8fafc;
    border-color: #9fb0c5;
    color: #0f172a;
}

.ba-ranking-row.is-selected .ba-row-filter-btn {
    background: #0f172a;
    border-color: #0f172a;
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
    border: 1px solid #0f172a;
    background: #0f172a;
    color: #fff;
}

.ba-clear-filters:hover {
    border-color: #9fb0c5;
    color: #0f172a;
    background: #f8fafc;
}

.ba-master-records-btn:hover {
    background: #1e293b;
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
</style>

<script>
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
    const periodLabels = payload.period?.labels ?? payload.bar.labels ?? [];
    const periodCases = payload.period?.cases ?? payload.bar.volume ?? [];
    const periodServiceAmounts = payload.period?.service_amount ?? payload.bar.revenue ?? [];
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
    const gridColor = isDark ? 'rgba(138, 167, 197, 0.18)' : 'rgba(100, 116, 139, 0.18)';
    const textColor = isDark ? '#cfe0f5' : '#334155';

    const money = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    });

    const number = new Intl.NumberFormat('en-PH');
    const charts = {};
    let chartLoader = null;
    let analyticsFilters = {
        branchCode: null,
        paymentStatus: null,
        revenuePeriod: null,
        collectionStatus: null,
    };

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

        target.innerHTML = cards.map((card, index) => `
            <article class="ba-insight-card tone-${card.tone || 'blue'} ${card.onClick ? 'is-clickable' : ''}" data-insight-index="${index}" ${card.onClick ? 'role="button" tabindex="0"' : ''}>
                <span class="ba-insight-label">${escapeHtml(card.label)}</span>
                <strong class="ba-insight-value">${escapeHtml(card.value)}</strong>
                <span class="ba-insight-note">${escapeHtml(card.note || '')}</span>
            </article>
        `).join('');

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
            rowsByBranch.set(code, row);
        });

        return Array.from(rowsByBranch.values()).map((row) => ({
            ...row,
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

    const buildRevenueInsightRows = () => {
        const labels = payload.line?.labels ?? [];
        const values = payload.line?.data ?? [];
        const rows = labels.map((label, index) => ({
            label,
            revenue: Number(values[index] || 0),
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
        const highestAverage = maxBy(branchRows, (row) => row.average);
        const zeroActivityBranch = branchRows.find((row) => row.cases === 0 || row.revenue === 0);
        const needsAttention = zeroActivityBranch || minBy(branchRows, (row) => row.revenue);

        renderInsightCards('baBranchInsights', [
            {
                label: 'Top Revenue Branch',
                value: topRevenue ? `${shortMoney(topRevenue.revenue)} revenue` : 'No revenue yet',
                note: branchDisplay(topRevenue),
                tone: 'blue',
                onClick: topRevenue?.code ? () => handleBranchClick(topRevenue.code) : null,
            },
            {
                label: 'Most Active Branch',
                value: mostActive ? pluralizeCase(mostActive.cases) : 'No cases yet',
                note: branchDisplay(mostActive),
                tone: 'blue',
                onClick: mostActive?.code ? () => handleBranchClick(mostActive.code) : null,
            },
            {
                label: 'Highest Average per Case',
                value: highestAverage ? `${shortMoney(highestAverage.average)} avg / case` : 'No average yet',
                note: branchDisplay(highestAverage),
                tone: 'blue',
                onClick: highestAverage?.code ? () => handleBranchClick(highestAverage.code) : null,
            },
            {
                label: 'Needs Attention',
                value: needsAttention
                    ? (needsAttention.cases === 0 ? 'No cases recorded' : `${shortMoney(needsAttention.revenue)} revenue`)
                    : 'No cases recorded',
                note: branchDisplay(needsAttention),
                tone: 'amber',
                onClick: needsAttention?.code ? () => handleBranchClick(needsAttention.code) : null,
            },
        ]);

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
            },
            {
                label: 'Partial Payment Cases',
                value: number.format(paymentRows.PARTIAL.count),
                note: `${shortMoney(paymentRows.PARTIAL.balance)} balance`,
                tone: 'amber',
                onClick: () => handlePaymentStatusClick('PARTIAL'),
            },
            {
                label: 'Unpaid Cases',
                value: number.format(paymentRows.UNPAID.count),
                note: `${shortMoney(paymentRows.UNPAID.balance)} outstanding`,
                tone: 'red',
                onClick: () => handlePaymentStatusClick('UNPAID'),
            },
            {
                label: 'Outstanding Balance',
                value: shortMoney(paymentRows.PARTIAL.balance + paymentRows.UNPAID.balance),
                note: `${number.format(needsFollowUpCount)} cases need follow-up`,
                tone: needsFollowUpBalance > 0 ? 'amber' : 'green',
                onClick: needsFollowUpBalance > 0 ? () => handlePaymentStatusClick(paymentRows.PARTIAL.count > 0 ? 'PARTIAL' : 'UNPAID') : null,
            },
            {
                label: 'Needs Follow-up',
                value: number.format(needsFollowUpCount),
                note: `${shortMoney(needsFollowUpBalance)} still open`,
                tone: needsFollowUpCount > 0 ? 'red' : 'green',
                onClick: needsFollowUpCount > 0 ? () => handlePaymentStatusClick(paymentRows.UNPAID.count > 0 ? 'UNPAID' : 'PARTIAL') : null,
            },
        ]);

        const revenueRows = buildRevenueInsightRows();
        renderInsightCards('baRevenueInsights', [
            {
                label: 'Total Revenue',
                value: shortMoney(revenueRows.total),
                note: 'For selected range',
                tone: 'blue',
            },
            {
                label: 'Peak Revenue Period',
                value: revenueRows.peak?.period?.label || revenueRows.peak?.label || '-',
                note: revenueRows.peak ? shortMoney(revenueRows.peak.revenue) : 'No revenue yet',
                tone: 'blue',
                onClick: revenueRows.peak?.period ? () => handleRevenuePeriodClick(revenueRows.peak.period) : null,
            },
            {
                label: 'Lowest Revenue Period',
                value: revenueRows.lowest?.period?.label || revenueRows.lowest?.label || '-',
                note: revenueRows.lowest ? shortMoney(revenueRows.lowest.revenue) : 'No revenue yet',
                tone: 'amber',
                onClick: revenueRows.lowest?.period ? () => handleRevenuePeriodClick(revenueRows.lowest.period) : null,
            },
            {
                label: 'Average Revenue per Period',
                value: shortMoney(revenueRows.average),
                note: `${number.format(revenueRows.rows.length)} periods included`,
                tone: 'blue',
            },
            {
                label: 'Revenue Trend',
                value: 'No comparison',
                note: 'No comparison available.',
                tone: 'blue',
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
            },
            {
                label: 'Outstanding Amount',
                value: shortMoney(collectionRows.outstanding),
                note: `${number.format(collectionRows.outstandingCases)} cases open`,
                tone: collectionRows.outstanding > 0 ? 'amber' : 'green',
                onClick: collectionRows.outstanding > 0 ? () => handleCollectionStatusClick('OUTSTANDING') : null,
            },
            {
                label: 'Collection Rate',
                value: `${collectionRows.collectionRate.toFixed(1)}%`,
                note: 'Collected vs collectible',
                tone: collectionRows.collectionRate >= 80 ? 'green' : 'amber',
            },
            {
                label: 'Cases Needing Collection',
                value: number.format(collectionRows.outstandingCases),
                note: 'Not fully collected',
                tone: collectionRows.outstandingCases > 0 ? 'amber' : 'green',
                onClick: collectionRows.outstandingCases > 0 ? () => handleCollectionStatusClick('OUTSTANDING') : null,
            },
            {
                label: 'Collection Risk',
                value: shortMoney(collectionRows.outstanding),
                note: `${number.format(collectionRows.outstandingCases)} affected cases`,
                tone: collectionRows.outstanding > 0 ? 'red' : 'green',
                onClick: collectionRows.outstanding > 0 ? () => handleCollectionStatusClick('OUTSTANDING') : null,
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
            return `
                <div class="ba-breakdown-row">
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
                <button type="button" class="ba-filter-chip-remove" data-filter-key="${key}" aria-label="Remove ${escapeHtml(label)} filter">x</button>
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
                note: branchLabel ? `Showing payment status for ${branchLabel}.` : 'Paid vs partial vs unpaid case distribution',
                context: branchLabel ? `This view is filtered by ${branchLabel}.` : '',
            },
            trend: {
                note: branchLabel ? `Showing revenue trend for ${branchLabel}.` : 'Trend line for period-based movement analysis',
                context: branchLabel ? `This view is filtered by ${branchLabel}.` : '',
            },
            collection: {
                note: branchLabel ? `Showing collection status for ${branchLabel}.` : 'Collected vs outstanding amount performance',
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

    const renderFilteredTable = (cases) => {
        const body = document.getElementById('baFilteredCasesBody');
        if (!body) return;

        if (!cases.length) {
            body.innerHTML = `
                <tr>
                    <td colspan="9" class="ba-empty-cell">
                        <div class="ba-empty-state">
                            <strong>No cases match the selected filters.</strong>
                            <span>Try clearing filters or selecting a different chart item.</span>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        body.innerHTML = cases.map((item) => `
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
    };

    const setAnalyticsFilters = (nextFilters) => {
        analyticsFilters = { ...analyticsFilters, ...nextFilters };
        renderAnalyticsResults();
    };

    const handleBranchClick = (branchCode) => {
        setAnalyticsFilters({ branchCode: analyticsFilters.branchCode === branchCode ? null : branchCode });
    };

    const handlePaymentStatusClick = (status) => {
        setAnalyticsFilters({ paymentStatus: analyticsFilters.paymentStatus === status ? null : status });
    };

    const handleRevenuePeriodClick = (period) => {
        setAnalyticsFilters({
            revenuePeriod: analyticsFilters.revenuePeriod?.label === period?.label ? null : period,
        });
    };

    const handleCollectionStatusClick = (status) => {
        setAnalyticsFilters({ collectionStatus: analyticsFilters.collectionStatus === status ? null : status });
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
        row.addEventListener('click', () => {
            if (row.dataset.branchCode) {
                handleBranchClick(row.dataset.branchCode);
            }
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
            ? fadeHex(color, 0.28)
            : color;
    };

    const periodBarColor = (ctx, color) => {
        const period = parsePeriodFromLabel(periodLabels[ctx.dataIndex]);
        return analyticsFilters.revenuePeriod && period?.label !== analyticsFilters.revenuePeriod.label
            ? fadeHex(color, 0.28)
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
                backgroundColor: isDark ? '#10253a' : '#0f172a',
                titleColor: '#fff',
                bodyColor: '#fff',
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
                            backgroundColor: (ctx) => branchBarColor(ctx, '#1E293B'),
                            borderColor: (ctx) => branchBarColor(ctx, '#1E293B'),
                            borderWidth: 1,
                            borderRadius: 6,
                            yAxisID: 'yRevenue',
                        },
                        {
                            type: 'bar',
                            label: 'Total Cases',
                            data: payload.bar.volume ?? [],
                            backgroundColor: (ctx) => branchBarColor(ctx, '#3B82F6'),
                            borderColor: (ctx) => branchBarColor(ctx, '#3B82F6'),
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
                        if (branchCode) handleBranchClick(branchCode);
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
                        backgroundColor: (ctx) => periodBarColor(ctx, '#1E293B'),
                        borderColor: (ctx) => periodBarColor(ctx, '#1E293B'),
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'yRevenue',
                    },
                    {
                        type: 'bar',
                        label: 'Total Cases',
                        data: periodCases,
                        backgroundColor: (ctx) => periodBarColor(ctx, '#3B82F6'),
                        borderColor: (ctx) => periodBarColor(ctx, '#3B82F6'),
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
                    if (period) handleRevenuePeriodClick(period);
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
                            const colors = ['#16a34a', '#d97706', '#dc2626'];
                            const index = ctx.dataIndex ?? 0;
                            return analyticsFilters.paymentStatus && statuses[index] !== analyticsFilters.paymentStatus
                                ? fadeHex(colors[index], 0.28)
                                : colors[index];
                        },
                        borderColor: isDark ? '#17283b' : '#fff',
                        borderWidth: 2,
                        hoverOffset: 6,
                    },
                ],
            },
            options: {
                ...sharedOptions,
                cutout: '60%',
                onHover: (event, elements) => {
                    if (event.native?.target) event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
                onClick: (event, elements, chart) => {
                    const hit = elements?.[0] || chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true)?.[0];
                    const statuses = ['PAID', 'PARTIAL', 'UNPAID'];
                    if (hit && statuses[hit.index]) handlePaymentStatusClick(statuses[hit.index]);
                },
                plugins: {
                    ...sharedOptions.plugins,
                    tooltip: {
                        ...sharedOptions.plugins.tooltip,
                        callbacks: {
                            label: (ctx) => `${ctx.label}: ${number.format(Number(ctx.raw || 0))} cases`,
                        },
                    },
                },
            },
        }),
        trendChart: () => buildChart('trendChart', {
            type: 'line',
            data: {
                labels: payload.line.labels ?? [],
                datasets: [
                    {
                        label: 'Total Service Amount',
                        data: payload.line.data ?? [],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.10)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.32,
                        pointRadius: (ctx) => {
                            const period = parsePeriodFromLabel((payload.line.labels ?? [])[ctx.dataIndex]);
                            return analyticsFilters.revenuePeriod?.label === period?.label ? 5.5 : 2.8;
                        },
                        pointHoverRadius: 4.5,
                        pointBackgroundColor: (ctx) => {
                            const period = parsePeriodFromLabel((payload.line.labels ?? [])[ctx.dataIndex]);
                            return analyticsFilters.revenuePeriod && analyticsFilters.revenuePeriod.label !== period?.label
                                ? fadeHex('#2563eb', 0.28)
                                : '#2563eb';
                        },
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
                    const period = parsePeriodFromLabel((payload.line.labels ?? [])[hit.index]);
                    if (period) handleRevenuePeriodClick(period);
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
        collectionChart: () => buildChart('collectionChart', {
            type: 'bar',
            data: {
                labels: ['Collected', 'Outstanding', 'Total Service Amount'],
                datasets: [
                    {
                        label: 'Amount',
                        data: [summary.totalCollected, summary.totalOutstanding, summary.totalSales],
                        backgroundColor: (ctx) => {
                            const statuses = ['COLLECTED', 'OUTSTANDING', null];
                            const colors = ['#15803d', '#b91c1c', '#334155'];
                            const index = ctx.dataIndex ?? 0;
                            return analyticsFilters.collectionStatus && statuses[index] !== analyticsFilters.collectionStatus
                                ? fadeHex(colors[index], 0.28)
                                : colors[index];
                        },
                        borderRadius: 6,
                    },
                ],
            },
            options: {
                ...sharedOptions,
                indexAxis: 'y',
                onHover: (event, elements) => {
                    if (event.native?.target) event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
                onClick: (event, elements, chart) => {
                    const hit = elements?.[0] || chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true)?.[0];
                    if (!hit) return;
                    const statuses = ['COLLECTED', 'OUTSTANDING', null];
                    if (statuses[hit.index]) {
                        handleCollectionStatusClick(statuses[hit.index]);
                    } else {
                        setAnalyticsFilters({ collectionStatus: null });
                    }
                },
                scales: {
                    x: {
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
                            callback: (value) => money.format(Number(value)),
                        },
                    },
                    y: {
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
                        text: `Collection Rate: ${collectionRate.toFixed(1)}%`,
                        color: textColor,
                        font: { size: 11, weight: '700' },
                        padding: { bottom: 8 },
                    },
                    tooltip: {
                        ...sharedOptions.plugins.tooltip,
                        callbacks: {
                            label: (ctx) => `${ctx.label}: ${money.format(Number(ctx.raw || 0))}`,
                        },
                    },
                    legend: { display: false },
                },
            },
        }),
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

    const activatePanel = async (targetId) => {
        if (!validPanelIds.has(targetId)) {
            targetId = 'ba-panel-performance';
        }

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
        const canvas = activePanel?.querySelector('canvas');
        if (canvas) {
            const chart = await ensureChart(canvas.id);
            if (chart) {
                requestAnimationFrame(() => chart.resize());
            }
        }

        rememberActivePanel(targetId);
        syncGlobalFilterControls(targetId);
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activatePanel(button.dataset.target).catch(() => {});
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

    const customRangeBtn = document.getElementById('baCustomRangeBtn');
    const datePopover = document.getElementById('baDatePopover');
    if (customRangeBtn && datePopover) {
        const setPopoverState = (isOpen) => {
            datePopover.style.display = isOpen ? 'block' : 'none';
            customRangeBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        const togglePopover = (event) => {
            event.stopPropagation();
            const isOpen = datePopover.style.display === 'block';
            setPopoverState(!isOpen);
        };

        setPopoverState(false);
        customRangeBtn.addEventListener('click', togglePopover);

        document.addEventListener('click', () => {
            setPopoverState(false);
        });

        datePopover.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    }
})();
</script>
@endsection
