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

        <div class="ba-panels">
            <article class="ba-panel active" id="ba-panel-performance" role="tabpanel">
                @if($chart['mode'] === 'all')
                    <div class="ba-panel-head">
                        <h4 class="ba-panel-title">Branch Comparison Summary</h4>
                        <span class="ba-panel-note">Total service amount and case comparison</span>
                    </div>
                    <div class="ba-chart-frame">
                        <canvas id="serviceCasesChart"></canvas>
                    </div>

                    <div class="ba-compare-card">
                        <div class="ba-compare-title">Branch Comparison Table</div>
                        <div class="ba-compare-table-wrap">
                            <table class="ba-compare-table">
                                <thead>
                                    <tr>
                                        <th>Branch (Code)</th>
                                        <th>Total Service Amount</th>
                                        <th>Cases</th>
                                        <th>Avg per Case</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($comparisonLabels as $index => $label)
                                        @php
                                            $revenueVal = (float) ($comparisonRevenue[$index] ?? 0);
                                            $volumeVal = (float) ($comparisonVolume[$index] ?? 0);
                                            $avgPerCase = $volumeVal > 0 ? ($revenueVal / $volumeVal) : 0;
                                            $rowCode = $branchMeta[$index]['code'] ?? null;
                                            $rowLabel = $rowCode ? ($rowCode . ' - ' . $label) : $label;
                                        @endphp
                                        <tr>
                                            <td title="{{ $rowLabel }}">{{ $rowLabel }}</td>
                                            <td>PHP {{ number_format($revenueVal, 2) }}</td>
                                            <td>{{ number_format($volumeVal) }}</td>
                                            <td>PHP {{ number_format($avgPerCase, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="ba-empty-cell">No analytics data available for selected filters.</td>
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
                                    @forelse($periodLabels as $index => $periodLabel)
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
                                            <td>{{ $periodLabel }}</td>
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
                    <span class="ba-panel-note">Paid vs partial vs unpaid case distribution</span>
                </div>
                <div class="ba-status-pills">
                    <span class="ba-status-pill ba-paid">Paid: {{ number_format($statusCounts['paid']) }}</span>
                    <span class="ba-status-pill ba-partial">Partial: {{ number_format($statusCounts['partial']) }}</span>
                    <span class="ba-status-pill ba-unpaid">Unpaid: {{ number_format($statusCounts['unpaid']) }}</span>
                    <span class="ba-status-pill ba-ongoing">Ongoing: {{ number_format($statusCounts['ongoing']) }}</span>
                </div>
                <div class="ba-chart-frame ba-chart-frame-narrow">
                    <canvas id="paymentChart"></canvas>
                </div>
            </article>

            <article class="ba-panel" id="ba-panel-trend" role="tabpanel" hidden>
                <div class="ba-panel-head">
                    <h4 class="ba-panel-title">Gross Revenue Trend</h4>
                    <span class="ba-panel-note">Trend line for period-based movement analysis</span>
                </div>
                <div class="ba-chart-frame">
                    <canvas id="trendChart"></canvas>
                </div>
            </article>

            <article class="ba-panel" id="ba-panel-collection" role="tabpanel" hidden>
                <div class="ba-panel-head">
                    <h4 class="ba-panel-title">Collection Status</h4>
                    <span class="ba-panel-note">Collected vs outstanding amount performance</span>
                </div>

                <div class="ba-collection-grid">
                    <div class="ba-collection-metric">
                        <span>Collected Amount</span>
                        <strong>PHP {{ number_format($totalCollected, 2) }}</strong>
                    </div>
                    <div class="ba-collection-metric">
                        <span>Outstanding Balance</span>
                        <strong>PHP {{ number_format($totalOutstanding, 2) }}</strong>
                    </div>
                    <div class="ba-collection-metric">
                        <span>Collection Rate</span>
                        <strong>
                            {{ ($totalCollected + $totalOutstanding) > 0 ? number_format(($totalCollected / ($totalCollected + $totalOutstanding)) * 100, 1) : '0.0' }}%
                        </strong>
                    </div>
                </div>

                <div class="ba-chart-frame">
                    <canvas id="collectionChart"></canvas>
                </div>
            </article>
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
}

.ba-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
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
}

.ba-workspace-head {
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
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
}

.ba-tab-btn.active {
    background: #0f172a;
    border-color: #0f172a;
    color: #fff;
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
    border-radius: 12px;
    background: #fff;
    padding: 0.85rem;
    height: 360px;
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
}

.ba-branch-kpi-card span {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.2rem;
}

.ba-branch-kpi-card strong {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
}

.ba-compare-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fcfdff;
}

.ba-compare-title {
    padding: 0.7rem 0.8rem;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    border-bottom: 1px solid #e2e8f0;
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
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
}

.ba-compare-table th {
    background: #f8fafc;
    color: #64748b;
    font-weight: 700;
}

.ba-compare-table td {
    color: #1e293b;
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

@media (max-width: 1100px) {
    .ba-kpi-strip {
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

    .ba-branch-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ba-collection-grid {
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
}

html[data-theme='dark'] .ba-shell {
    color: #e2ecf9;
}

html[data-theme='dark'] .ba-card,
html[data-theme='dark'] .ba-kpi-tile,
html[data-theme='dark'] .ba-chart-frame,
html[data-theme='dark'] .ba-compare-card {
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
html[data-theme='dark'] .ba-compare-title {
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
                            backgroundColor: '#1E293B',
                            borderColor: '#1E293B',
                            borderWidth: 1,
                            borderRadius: 6,
                            yAxisID: 'yRevenue',
                        },
                        {
                            type: 'bar',
                            label: 'Total Cases',
                            data: payload.bar.volume ?? [],
                            backgroundColor: '#3B82F6',
                            borderColor: '#3B82F6',
                            borderWidth: 1,
                            borderRadius: 6,
                            yAxisID: 'yCases',
                        },
                    ],
                },
                options: {
                    ...sharedOptions,
                    scales: {
                        yRevenue: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: {
                                color: textColor,
                                callback: (value) => money.format(Number(value)),
                            },
                        },
                        yCases: {
                            beginAtZero: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: {
                                precision: 0,
                                color: textColor,
                                callback: (value) => number.format(Number(value)),
                            },
                        },
                        x: {
                            grid: { display: false },
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
                        backgroundColor: '#1E293B',
                        borderColor: '#1E293B',
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'yRevenue',
                    },
                    {
                        type: 'bar',
                        label: 'Total Cases',
                        data: periodCases,
                        backgroundColor: '#3B82F6',
                        borderColor: '#3B82F6',
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'yCases',
                    },
                ],
            },
            options: {
                ...sharedOptions,
                scales: {
                    yRevenue: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            callback: (value) => money.format(Number(value)),
                        },
                    },
                    yCases: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            precision: 0,
                            color: textColor,
                            callback: (value) => number.format(Number(value)),
                        },
                    },
                    x: {
                        grid: { display: false },
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
                        backgroundColor: ['#16a34a', '#d97706', '#dc2626'],
                        borderColor: isDark ? '#17283b' : '#fff',
                        borderWidth: 2,
                        hoverOffset: 6,
                    },
                ],
            },
            options: {
                ...sharedOptions,
                cutout: '60%',
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
                        pointRadius: 2.8,
                        pointHoverRadius: 4.5,
                        pointBackgroundColor: '#2563eb',
                    },
                ],
            },
            options: {
                ...sharedOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            callback: (value) => money.format(Number(value)),
                        },
                    },
                    x: {
                        grid: { display: false },
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
                        backgroundColor: ['#15803d', '#b91c1c', '#334155'],
                        borderRadius: 6,
                    },
                ],
            },
            options: {
                ...sharedOptions,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            callback: (value) => money.format(Number(value)),
                        },
                    },
                    y: {
                        grid: { display: false },
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

    const activatePanel = async (targetId) => {
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
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activatePanel(button.dataset.target).catch(() => {});
        });
    });

    const initialPanelId = payload.mode === 'all'
        ? 'ba-panel-performance'
        : 'ba-panel-performance';
    activatePanel(initialPanelId).catch(() => {});

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
