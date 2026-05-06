@extends('layouts.panel')

@section('page_title', 'Owner Dashboard')
@section('page_desc', 'Business health, branch performance, collection risk, and oversight reports.')

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
     1. BUSINESS HEALTH
════════════════════════════════════════════════════════════ --}}
<section class="eb-section" aria-labelledby="sectionBusinessHealth">
    <div class="eb-section-header">
        <div>
            <h2 class="eb-section-title" id="sectionBusinessHealth">Business Health</h2>
            <p class="eb-section-sub">Financial overview for the selected period and branch scope.</p>
        </div>
    </div>

    <div class="eb-health-grid">

        {{-- Hero: Total Service Value --}}
        <div class="eb-health-hero">
            <div class="eb-health-hero-eyebrow">
                <i class="bi bi-graph-up-arrow"></i>
                Total Service Value
            </div>
            <div class="eb-health-hero-value">&#8369; {{ number_format((float) ($totalSales ?? 0), 2) }}</div>
            <div class="eb-health-hero-sub">Gross service amount for all cases in period</div>
        </div>

        {{-- Total Collected --}}
        <a href="{{ route('owner.history', array_merge($historyQuery, ['payment_status' => 'PAID'])) }}"
           class="eb-health-card eb-health-card-green"
           title="View fully paid cases">
            <div class="eb-health-card-top">
                <span class="eb-health-card-label">Total Collected</span>
                <span class="eb-health-card-icon eb-icon-green"><i class="bi bi-cash-stack"></i></span>
            </div>
            <div class="eb-health-card-value eb-val-green">&#8369; {{ number_format((float) ($totalCollected ?? 0), 2) }}</div>
            <div class="eb-health-card-sub">Payments received &mdash; {{ number_format($paidCases ?? 0) }} fully paid</div>
            <span class="eb-health-card-hint">View records →</span>
        </a>

        {{-- Outstanding Balance --}}
        <a href="{{ route('owner.history', $historyQuery) }}"
           class="eb-health-card eb-health-card-red"
           title="View outstanding cases">
            <div class="eb-health-card-top">
                <span class="eb-health-card-label">Outstanding Balance</span>
                <span class="eb-health-card-icon eb-icon-red"><i class="bi bi-exclamation-circle"></i></span>
            </div>
            <div class="eb-health-card-value eb-val-red">&#8369; {{ number_format((float) ($totalOutstanding ?? 0), 2) }}</div>
            <div class="eb-health-card-sub">Remaining unpaid across all cases</div>
            <span class="eb-health-card-hint">View records →</span>
        </a>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     2. COLLECTION RISK
════════════════════════════════════════════════════════════ --}}
<section class="eb-section" aria-labelledby="sectionCollectionRisk">
    <div class="eb-section-header">
        <div>
            <h2 class="eb-section-title" id="sectionCollectionRisk">Collection Risk</h2>
            <p class="eb-section-sub">Accounts requiring follow-up and attention.</p>
        </div>
        <a href="{{ route('owner.history', $historyQuery) }}" class="eb-link-btn">
            All Records <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="eb-risk-grid">

        {{-- Unpaid Accounts --}}
        <a href="{{ route('owner.history', array_merge($historyQuery, ['payment_status' => 'UNPAID'])) }}"
           class="eb-risk-card eb-risk-card-red">
            <div class="eb-risk-card-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="eb-risk-card-val">{{ number_format($unpaidCases ?? 0) }}</div>
            <div class="eb-risk-card-label">Unpaid Accounts</div>
            <div class="eb-risk-card-hint">View details →</div>
        </a>

        {{-- Partial Payments --}}
        <a href="{{ route('owner.history', array_merge($historyQuery, ['payment_status' => 'PARTIAL'])) }}"
           class="eb-risk-card eb-risk-card-amber">
            <div class="eb-risk-card-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="eb-risk-card-val">{{ number_format($partialCases ?? 0) }}</div>
            <div class="eb-risk-card-label">Partial Payments</div>
            <div class="eb-risk-card-hint">View details →</div>
        </a>

        {{-- Total Cases --}}
        <a href="{{ route('owner.history', $historyQuery) }}"
           class="eb-risk-card eb-risk-card-slate">
            <div class="eb-risk-card-icon"><i class="bi bi-folder2-open"></i></div>
            <div class="eb-risk-card-val">{{ number_format($totalCases ?? 0) }}</div>
            <div class="eb-risk-card-label">Total Cases</div>
            <div class="eb-risk-card-hint">View records →</div>
        </a>

    </div>

    {{-- Needs Attention Branch --}}
    @if($attentionBranch)
    <div class="eb-attention-strip">
        <div class="eb-attention-left">
            <span class="eb-attention-badge">
                <i class="bi bi-flag-fill"></i> Needs Attention
            </span>
            <div class="eb-attention-branch">
                <span class="eb-attention-code">{{ $attentionBranch['branch']?->branch_code ?? '—' }}</span>
                <span class="eb-attention-name">{{ $attentionBranch['branch']?->branch_name ?? '—' }}</span>
            </div>
            <div class="eb-attention-stats">
                @if(($attentionBranch['unpaid_cases'] ?? 0) > 0)
                    <span class="eb-bsp eb-bsp-red">{{ $attentionBranch['unpaid_cases'] }} unpaid</span>
                @endif
                @if(($attentionBranch['partial_cases'] ?? 0) > 0)
                    <span class="eb-bsp eb-bsp-amber">{{ $attentionBranch['partial_cases'] }} partial</span>
                @endif
            </div>
        </div>
        <div class="eb-attention-right">
            <div class="eb-attention-amount-label">Outstanding Balance</div>
            <div class="eb-attention-amount">&#8369; {{ number_format((float) ($attentionBranch['outstanding'] ?? 0), 2) }}</div>
            <a href="{{ route('owner.analytics', array_merge($analyticsQuery, ['branch_id' => $attentionBranch['branch']?->id])) }}"
               class="eb-attention-link">View in Analytics →</a>
        </div>
    </div>
    @endif

</section>

{{-- ═══════════════════════════════════════════════════════════
     3. BRANCH PERFORMANCE + REPORTS & OVERSIGHT
════════════════════════════════════════════════════════════ --}}
<div class="eb-bottom-grid">

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
                                {{ $rank === 1 ? 'Top 1' : '#' . $rank }}
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
                            <span class="eb-branch-amount">&#8369; {{ number_format($bSales, 2) }}</span>
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

    {{-- Right column --}}
    <div class="eb-right-col">

        {{-- Reports & Oversight --}}
        <section class="eb-section eb-card eb-reports-card" aria-labelledby="sectionReports">
            <div class="eb-section-header">
                <div>
                    <h2 class="eb-section-title" id="sectionReports">Reports &amp; Oversight</h2>
                    <p class="eb-section-sub">Monitoring, analytics, and printable reports</p>
                </div>
                <a href="{{ route('reports.index', $reportsQuery) }}" class="eb-link-btn">
                    All Reports <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="eb-report-list">
                <a href="{{ route('owner.analytics', $analyticsQuery) }}" class="eb-report-row">
                    <span class="eb-report-icon eb-ri-blue"><i class="bi bi-bar-chart-line"></i></span>
                    <span class="eb-report-copy">
                        <strong>Branch Analytics</strong>
                        <small>Compare performance, revenue, and trends by branch.</small>
                    </span>
                    <i class="bi bi-arrow-up-right eb-report-arrow"></i>
                </a>
                <a href="{{ route('owner.history', $historyQuery) }}" class="eb-report-row">
                    <span class="eb-report-icon eb-ri-amber"><i class="bi bi-exclamation-diamond"></i></span>
                    <span class="eb-report-copy">
                        <strong>Payment Monitoring</strong>
                        <small>Track partial and unpaid accounts across branches.</small>
                    </span>
                    <i class="bi bi-arrow-up-right eb-report-arrow"></i>
                </a>
                <a href="{{ route('owner.history', $historyQuery) }}" class="eb-report-row">
                    <span class="eb-report-icon eb-ri-slate"><i class="bi bi-clipboard-data"></i></span>
                    <span class="eb-report-copy">
                        <strong>Master Case Records</strong>
                        <small>View and search all verified case records.</small>
                    </span>
                    <i class="bi bi-arrow-up-right eb-report-arrow"></i>
                </a>
                <a href="{{ route('reports.index', $reportsQuery) }}" class="eb-report-row">
                    <span class="eb-report-icon eb-ri-green"><i class="bi bi-wallet2"></i></span>
                    <span class="eb-report-copy">
                        <strong>Reports</strong>
                        <small>Owner-level summaries and printable reports.</small>
                    </span>
                    <i class="bi bi-arrow-up-right eb-report-arrow"></i>
                </a>
            </div>
        </section>

        {{-- Top Packages (branch-specific only) --}}
        @if($branchId && $topPackages && $topPackages->count())
        <section class="eb-section eb-card" aria-labelledby="sectionTopPackages">
            <div class="eb-section-header">
                <div>
                    <h2 class="eb-section-title" id="sectionTopPackages">Top Packages</h2>
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

</div>{{-- /.eb-shell --}}

<style>
/* ══════════════════════════════════════════
   Shell & Typography
══════════════════════════════════════════ */
.eb-shell {
    padding: 1.5rem var(--panel-content-inline, 1.5rem) 3rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    color: #333333;
    font-family: var(--font-body);
}
.eb-shell *, .eb-shell button, .eb-shell input,
.eb-shell select, .eb-shell textarea, .eb-shell a {
    font-family: var(--font-body);
}

/* ══════════════════════════════════════════
   Filter Bar
══════════════════════════════════════════ */
.eb-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    background: #FAFAF7;
    border: 1px solid #C9C5BB;
    border-radius: 14px;
    padding: 12px 14px;
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
    color: #3E4A3D;
    border: 1px solid #C9C5BB;
    background: #fff;
    border-radius: 999px;
    padding: 4px 10px;
    white-space: nowrap;
}
.eb-chip-period { border-color: #3E4A3D; background: rgba(62,74,61,.07); }
.eb-chip-muted  { color: #5F685F; background: #FAFAF7; }

/* Branch selector */
.eb-branch-select-wrap {
    display: flex;
    align-items: center;
    gap: 7px;
    height: 40px;
    border: 1px solid #C9C5BB;
    border-radius: 10px;
    background: #fff;
    padding: 0 11px;
    min-width: 200px;
    max-width: 280px;
}
.eb-branch-select-wrap i { font-size: 13px; color: #5F685F; }
.eb-chev { font-size: 10px; opacity: .55; pointer-events: none; }
.eb-branch-select {
    flex: 1;
    min-width: 0;
    border: 0;
    background: transparent;
    font-size: 12px;
    font-weight: 700;
    color: #333333;
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
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    color: #5F685F;
    text-decoration: none;
    border: 1px solid #C9C5BB;
    background: #fff;
    transition: all .15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    white-space: nowrap;
}
.eb-seg-item:hover  { border-color: #3E4A3D; color: #3E4A3D; }
.eb-seg-item.active { background: #3E4A3D; color: #fff; border-color: #3E4A3D; }

/* Custom range popover */
.eb-custom-wrap { position: relative; display: inline-flex; }
.eb-date-popover {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 240;
    background: #fff;
    border: 1px solid #C9C5BB;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 8px 28px rgba(0,0,0,.10);
    min-width: 290px;
}
.eb-pop-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #5F685F;
    margin-bottom: 10px;
}
.eb-pop-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 10px;
}
.eb-pop-field { display: flex; flex-direction: column; gap: 4px; }
.eb-pop-field-label { font-size: 10.5px; font-weight: 600; color: #5F685F; text-transform: uppercase; letter-spacing: .06em; }
.eb-pop-input {
    height: 34px; padding: 0 10px;
    border: 1px solid #C9C5BB; border-radius: 8px;
    font-size: 12px; color: #333333; background: #FAFAF7;
    width: 100%;
}
.eb-pop-input:focus { outline: none; border-color: #3E4A3D; }
.eb-pop-actions { display: flex; gap: 6px; }
.eb-pop-apply {
    flex: 1; height: 36px;
    background: #3E4A3D; color: #fff;
    border: none; border-radius: 8px;
    font-size: 12px; font-weight: 600; cursor: pointer;
}
.eb-pop-apply:hover { background: #2D372D; }
.eb-pop-reset {
    height: 36px; padding: 0 12px;
    border: 1px solid #C9C5BB; border-radius: 8px;
    background: #fff; color: #5F685F;
    font-size: 12px; font-weight: 600;
    text-decoration: none;
    display: inline-flex; align-items: center; justify-content: center;
}
.eb-pop-reset:hover { border-color: #3E4A3D; color: #3E4A3D; }

/* ══════════════════════════════════════════
   Section chrome
══════════════════════════════════════════ */
.eb-section { display: flex; flex-direction: column; gap: .9rem; }
.eb-section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
}
.eb-section-title { font-size: 14px; font-weight: 800; color: #3E4A3D; letter-spacing: -.02em; font-family: var(--font-heading); }
.eb-section-sub   { font-size: 12px; color: #5F685F; margin-top: 2px; }
.eb-card {
    background: #FAFAF7;
    border: 1px solid #C9C5BB;
    border-radius: 14px;
    padding: 18px;
}
.eb-link-btn {
    font-size: 11px; font-weight: 700;
    color: #5F685F; text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
    white-space: nowrap; padding: 5px 10px;
    border: 1px solid #C9C5BB; border-radius: 8px;
    transition: all .12s;
    flex-shrink: 0;
}
.eb-link-btn:hover { background: #3E4A3D; color: #fff; border-color: #3E4A3D; }

/* ══════════════════════════════════════════
   1. Business Health
══════════════════════════════════════════ */
.eb-health-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
}
@media (max-width: 900px) { .eb-health-grid { grid-template-columns: 1fr; } }
@media (max-width: 1200px) and (min-width: 901px) { .eb-health-grid { grid-template-columns: 1fr 1fr; } }

/* Hero card */
.eb-health-hero {
    background: linear-gradient(135deg, #3E4A3D 0%, #2D372D 100%);
    border-radius: 16px;
    padding: 28px 26px 22px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    position: relative;
    overflow: hidden;
    min-height: 150px;
    justify-content: flex-end;
}
.eb-health-hero::before {
    content: '';
    position: absolute;
    top: -28px; right: -28px;
    width: 110px; height: 110px;
    border-radius: 50%;
    background: rgba(214, 176, 115, .12);
}
.eb-health-hero-eyebrow {
    font-size: 10.5px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .09em;
    color: #d6b073;
    display: flex; align-items: center; gap: 5px;
    position: relative;
}
.eb-health-hero-value {
    font-size: 2.2rem; font-weight: 800;
    color: #fff; letter-spacing: -.025em; line-height: 1;
    position: relative; font-variant-numeric: tabular-nums;
}
.eb-health-hero-sub { font-size: 11px; color: rgba(255,255,255,.45); position: relative; }

/* Secondary health cards */
.eb-health-card {
    background: #FAFAF7;
    border: 1px solid #C9C5BB;
    border-radius: 16px;
    padding: 22px 20px 16px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    text-decoration: none;
    color: inherit;
    transition: border-color .15s ease, transform .15s ease, box-shadow .15s ease;
    position: relative;
}
.eb-health-card:hover {
    border-color: #3E4A3D;
    background: #F3F0E8;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(62,74,61,.10);
}
.eb-health-card-top {
    display: flex; align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
}
.eb-health-card-label { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #5F685F; }
.eb-health-card-icon {
    width: 30px; height: 30px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
}
.eb-icon-green { background: rgba(111,138,109,.14); color: #6F8A6D; }
.eb-icon-red   { background: rgba(158,75,63,.12);   color: #9E4B3F; }
.eb-health-card-value { font-size: 1.55rem; font-weight: 800; letter-spacing: -.02em; line-height: 1; font-variant-numeric: tabular-nums; }
.eb-val-green { color: #6F8A6D; }
.eb-val-red   { color: #9E4B3F; }
.eb-health-card-sub   { font-size: 11px; color: #5F685F; margin-top: 3px; }
.eb-health-card-hint  { font-size: 10px; font-weight: 700; color: #3E4A3D; opacity: .7; margin-top: 4px; letter-spacing: .02em; }

/* ══════════════════════════════════════════
   2. Collection Risk
══════════════════════════════════════════ */
.eb-risk-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}
@media (max-width: 680px) { .eb-risk-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 420px) { .eb-risk-grid { grid-template-columns: 1fr; } }

.eb-risk-card {
    background: #FAFAF7;
    border: 1px solid #C9C5BB;
    border-radius: 14px;
    padding: 18px 16px 14px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    text-decoration: none;
    color: inherit;
    transition: border-color .15s, background .15s, transform .15s, box-shadow .15s;
    border-bottom-width: 3px;
}
.eb-risk-card:hover {
    background: #F3F0E8;
    border-color: #3E4A3D;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(62,74,61,.10);
}
.eb-risk-card-icon { font-size: 20px; margin-bottom: 4px; }
.eb-risk-card-val   { font-size: 2rem; font-weight: 800; line-height: 1; letter-spacing: -.03em; font-variant-numeric: tabular-nums; }
.eb-risk-card-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #5F685F; }
.eb-risk-card-hint  { font-size: 10px; font-weight: 700; color: #3E4A3D; opacity: .7; margin-top: 2px; letter-spacing: .02em; }

.eb-risk-card-red   { border-bottom-color: #9E4B3F; }
.eb-risk-card-red   .eb-risk-card-icon { color: #9E4B3F; }
.eb-risk-card-red   .eb-risk-card-val  { color: #9E4B3F; }
.eb-risk-card-amber { border-bottom-color: #B87956; }
.eb-risk-card-amber .eb-risk-card-icon { color: #B87956; }
.eb-risk-card-amber .eb-risk-card-val  { color: #B87956; }
.eb-risk-card-slate { border-bottom-color: #3E4A3D; }
.eb-risk-card-slate .eb-risk-card-icon { color: #3E4A3D; }
.eb-risk-card-slate .eb-risk-card-val  { color: #3E4A3D; }

/* Needs Attention strip */
.eb-attention-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    background: rgba(184,121,86,.08);
    border: 1px solid rgba(184,121,86,.30);
    border-radius: 12px;
    padding: 14px 16px;
}
.eb-attention-left  { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; min-width: 0; }
.eb-attention-right { display: flex; flex-direction: column; align-items: flex-end; gap: 3px; flex-shrink: 0; }
.eb-attention-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px;
    background: rgba(184,121,86,.18); border: 1px solid rgba(184,121,86,.30);
    color: #B87956; font-size: 10.5px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .07em; white-space: nowrap;
}
.eb-attention-branch { display: flex; align-items: baseline; gap: 6px; min-width: 0; }
.eb-attention-code   { font-size: 10px; font-weight: 800; color: #B87956; text-transform: uppercase; letter-spacing: .1em; }
.eb-attention-name   { font-size: 13px; font-weight: 700; color: #333333; }
.eb-attention-stats  { display: flex; align-items: center; gap: 6px; }
.eb-attention-amount-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #B87956; }
.eb-attention-amount { font-size: 1.1rem; font-weight: 800; color: #9E4B3F; font-variant-numeric: tabular-nums; }
.eb-attention-link   { font-size: 11px; font-weight: 700; color: #3E4A3D; text-decoration: none; opacity: .8; }
.eb-attention-link:hover { opacity: 1; text-decoration: underline; }

/* ══════════════════════════════════════════
   3. Bottom grid — Branch Perf + Reports
══════════════════════════════════════════ */
.eb-bottom-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 1rem;
    align-items: start;
}
@media (max-width: 1100px) { .eb-bottom-grid { grid-template-columns: 1fr; } }
.eb-right-col { display: flex; flex-direction: column; gap: 1rem; }

.eb-branch-card { max-height: 380px; overflow: hidden; display: flex; flex-direction: column; }
@media (max-width: 1100px) { .eb-branch-card { max-height: none; } }
.eb-reports-card { display: flex; flex-direction: column; gap: 1rem; }

/* Branch list */
.eb-branch-list {
    display: flex; flex-direction: column; gap: 8px;
    overflow-y: auto; flex: 1; padding-right: 2px;
}
.eb-branch-row {
    display: flex; flex-direction: column; gap: 7px;
    padding: 10px; border-radius: 10px;
    border: 1px solid #C9C5BB; background: #fff;
}
.eb-branch-row-head {
    display: flex; align-items: center;
    justify-content: space-between; gap: 10px; flex-wrap: wrap;
}
.eb-branch-main  { display: flex; align-items: center; gap: 8px; min-width: 0; }
.eb-branch-copy  { display: flex; align-items: baseline; gap: 5px; flex-wrap: wrap; min-width: 0; }
.eb-branch-code  { font-size: 10px; font-weight: 800; color: #5F685F; text-transform: uppercase; letter-spacing: .1em; }
.eb-branch-name  { font-size: 13px; font-weight: 600; color: #333333; }
.eb-branch-row-stats { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.eb-branch-amount { font-size: 13px; font-weight: 700; color: #3E4A3D; font-variant-numeric: tabular-nums; }
.eb-branch-bar-track { width: 100%; height: 4px; background: #C9C5BB; border-radius: 999px; overflow: hidden; }
.eb-branch-bar-fill  { height: 100%; border-radius: 999px; transition: width .6s ease; }

.eb-rank-badge {
    flex: 0 0 auto; display: inline-flex; align-items: center;
    justify-content: center; min-width: 40px; height: 22px; padding: 0 7px;
    border-radius: 999px; background: #f1f5f9; border: 1px solid #C9C5BB;
    color: #5F685F; font-size: 10px; font-weight: 800;
}
.eb-rank-badge.is-top { background: #fffbeb; border-color: #fde68a; color: #92400e; }

/* Shared status pills */
.eb-bsp {
    font-size: 10px; font-weight: 700;
    padding: 2px 8px; border-radius: 999px;
}
.eb-bsp-green { background: rgba(111,138,109,.14); color: #6F8A6D;  border: 0.5px solid rgba(111,138,109,.30); }
.eb-bsp-amber { background: rgba(184,121,86,.12);  color: #B87956;  border: 0.5px solid rgba(184,121,86,.25); }
.eb-bsp-red   { background: rgba(158,75,63,.10);   color: #9E4B3F;  border: 0.5px solid rgba(158,75,63,.22); }

/* Report list */
.eb-report-list { display: flex; flex-direction: column; gap: 8px; }
.eb-report-row {
    display: grid;
    grid-template-columns: 32px minmax(0,1fr) auto;
    align-items: center; gap: 10px;
    padding: 10px 12px; min-height: 54px;
    border: 1px solid #C9C5BB; border-radius: 10px;
    background: #fff; color: inherit; text-decoration: none;
    transition: border-color .12s, background .12s, transform .12s;
}
.eb-report-row:hover { background: #F3F0E8; border-color: #3E4A3D; transform: translateY(-1px); }
.eb-report-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: inline-flex; align-items: center;
    justify-content: center; font-size: 14px;
}
.eb-ri-blue  { background: rgba(62,74,61,.10);  color: #3E4A3D; }
.eb-ri-amber { background: rgba(184,121,86,.12); color: #B87956; }
.eb-ri-slate { background: rgba(95,104,95,.12);  color: #5F685F; }
.eb-ri-green { background: rgba(111,138,109,.14); color: #6F8A6D; }
.eb-report-copy { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.eb-report-copy strong { font-size: 12px; font-weight: 800; color: #3E4A3D; }
.eb-report-copy small  { font-size: 11px; color: #5F685F; line-height: 1.35; }
.eb-report-arrow { color: #5F685F; font-size: 12px; }

/* Top Packages */
.eb-pkg-list { display: flex; flex-direction: column; gap: 7px; }
.eb-pkg-row {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 9px 12px;
    background: #fff; border-radius: 8px; border: 1px solid #C9C5BB;
}
.eb-pkg-name   { font-size: 12px; font-weight: 600; color: #333333; }
.eb-pkg-stats  { display: flex; align-items: center; gap: 8px; }
.eb-pkg-cases  { font-size: 11px; color: #5F685F; }
.eb-pkg-amount { font-size: 12px; font-weight: 700; color: #3E4A3D; font-variant-numeric: tabular-nums; }

/* Empty state */
.eb-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 2rem; color: #7A8076; font-size: 12px; text-align: center; }
.eb-empty-icon { font-size: 2rem; }

/* ══════════════════════════════════════════
   Dark mode
══════════════════════════════════════════ */
html[data-theme='dark'] .eb-shell { color: #e2ecf9; }
html[data-theme='dark'] .eb-filter-bar  { background: #182638; border-color: #2e4560; }
html[data-theme='dark'] .eb-chip        { background: #1e334f; border-color: #2e4560; color: #8aa7c5; }
html[data-theme='dark'] .eb-chip-period { background: #1a3050; border-color: #3d607f; color: #93c5fd; }
html[data-theme='dark'] .eb-branch-select-wrap,
html[data-theme='dark'] .eb-pop-reset,
html[data-theme='dark'] .eb-date-popover { background: #182638; border-color: #2e4560; }
html[data-theme='dark'] .eb-branch-select,
html[data-theme='dark'] .eb-branch-select-wrap i,
html[data-theme='dark'] .eb-chev { color: #8aa7c5; }
html[data-theme='dark'] .eb-seg-item { color: #8aa7c5; background: #182638; border-color: #2e4560; }
html[data-theme='dark'] .eb-seg-item:hover  { color: #e2ecf9; border-color: #5a7898; }
html[data-theme='dark'] .eb-seg-item.active { background: #243d5a; color: #e2ecf9; border-color: #4a6888; }
html[data-theme='dark'] .eb-pop-input { background: #1e334f; border-color: #2e4560; color: #e2ecf9; }
html[data-theme='dark'] .eb-pop-input:focus { border-color: #5a7898; }
html[data-theme='dark'] .eb-pop-reset { color: #8aa7c5; }

html[data-theme='dark'] .eb-section-title { color: #e2ecf9; }
html[data-theme='dark'] .eb-section-sub   { color: #5a7898; }
html[data-theme='dark'] .eb-card { background: #182638; border-color: #2e4560; }
html[data-theme='dark'] .eb-link-btn { background: #1e334f; border-color: #2e4560; color: #8aa7c5; }
html[data-theme='dark'] .eb-link-btn:hover { background: #e2ecf9; color: #3E4A3D; border-color: #e2ecf9; }

html[data-theme='dark'] .eb-health-hero { background: linear-gradient(135deg, #0a1628 0%, #0f1f35 100%); }
html[data-theme='dark'] .eb-health-card { background: #182638; border-color: #2e4560; color: #e2ecf9; }
html[data-theme='dark'] .eb-health-card:hover { background: #1e334f; border-color: #4a6888; }
html[data-theme='dark'] .eb-health-card-label { color: #5a7898; }
html[data-theme='dark'] .eb-health-card-sub   { color: #5a7898; }
html[data-theme='dark'] .eb-health-card-hint  { color: #93c5fd; }
html[data-theme='dark'] .eb-icon-green { background: #052e16; color: #4ade80; }
html[data-theme='dark'] .eb-icon-red   { background: #450a0a; color: #f87171; }
html[data-theme='dark'] .eb-val-green  { color: #4ade80; }
html[data-theme='dark'] .eb-val-red    { color: #f87171; }

html[data-theme='dark'] .eb-risk-card { background: #182638; border-color: #2e4560; color: #e2ecf9; }
html[data-theme='dark'] .eb-risk-card:hover { background: #1e334f; border-color: #4a6888; }
html[data-theme='dark'] .eb-risk-card-label { color: #5a7898; }
html[data-theme='dark'] .eb-risk-card-hint  { color: #93c5fd; }
html[data-theme='dark'] .eb-risk-card-red   .eb-risk-card-val  { color: #f87171; }
html[data-theme='dark'] .eb-risk-card-red   .eb-risk-card-icon { color: #f87171; }
html[data-theme='dark'] .eb-risk-card-amber .eb-risk-card-val  { color: #fbbf24; }
html[data-theme='dark'] .eb-risk-card-amber .eb-risk-card-icon { color: #fbbf24; }
html[data-theme='dark'] .eb-risk-card-slate .eb-risk-card-val  { color: #e2ecf9; }
html[data-theme='dark'] .eb-risk-card-slate .eb-risk-card-icon { color: #8aa7c5; }

html[data-theme='dark'] .eb-attention-strip { background: rgba(184,121,86,.10); border-color: rgba(184,121,86,.20); }
html[data-theme='dark'] .eb-attention-badge { background: rgba(184,121,86,.15); border-color: rgba(184,121,86,.25); color: #fbbf24; }
html[data-theme='dark'] .eb-attention-name   { color: #e2ecf9; }
html[data-theme='dark'] .eb-attention-amount { color: #f87171; }
html[data-theme='dark'] .eb-attention-link   { color: #93c5fd; }

html[data-theme='dark'] .eb-branch-row { background: #1e334f; border-color: #2e4560; }
html[data-theme='dark'] .eb-branch-name   { color: #e2ecf9; }
html[data-theme='dark'] .eb-branch-amount { color: #e2ecf9; }
html[data-theme='dark'] .eb-branch-code   { color: #5a7898; }
html[data-theme='dark'] .eb-rank-badge { background: #243d5a; border-color: #4a6888; color: #8aa7c5; }
html[data-theme='dark'] .eb-rank-badge.is-top { background: #451a03; border-color: #92400e; color: #fbbf24; }
html[data-theme='dark'] .eb-branch-bar-track { background: #2e4560; }
html[data-theme='dark'] .eb-bsp-green { background: #052e16; color: #4ade80; border-color: #065f46; }
html[data-theme='dark'] .eb-bsp-amber { background: #451a03; color: #fbbf24; border-color: #92400e; }
html[data-theme='dark'] .eb-bsp-red   { background: #450a0a; color: #f87171; border-color: #7f1d1d; }

html[data-theme='dark'] .eb-report-row { background: #1e334f; border-color: #2e4560; }
html[data-theme='dark'] .eb-report-row:hover { background: #243d5a; border-color: #4a6888; }
html[data-theme='dark'] .eb-report-copy strong { color: #e2ecf9; }
html[data-theme='dark'] .eb-report-copy small, html[data-theme='dark'] .eb-report-arrow { color: #8aa7c5; }
html[data-theme='dark'] .eb-ri-blue  { background: #172554; color: #93c5fd; }
html[data-theme='dark'] .eb-ri-amber { background: #451a03; color: #fbbf24; }
html[data-theme='dark'] .eb-ri-slate { background: #1e334f; color: #C9C5BB; }
html[data-theme='dark'] .eb-ri-green { background: #052e16; color: #4ade80; }

html[data-theme='dark'] .eb-pkg-row { background: #1e334f; border-color: #2e4560; }
html[data-theme='dark'] .eb-pkg-name   { color: #e2ecf9; }
html[data-theme='dark'] .eb-pkg-cases  { color: #5a7898; }
html[data-theme='dark'] .eb-pkg-amount { color: #e2ecf9; }
html[data-theme='dark'] .eb-empty { color: #4a6888; }
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
