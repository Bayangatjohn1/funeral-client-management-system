@extends('layouts.panel')

@section('page_title', 'Executive Board')
@section('page_desc', 'Real-time business performance and branch financial overview.')

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
    $isCustomRange = ($range ?? 'THIS_MONTH') === 'CUSTOM';
    $formattedFrom = \Carbon\Carbon::parse($dateFrom)->format('M d, Y');
    $formattedTo   = \Carbon\Carbon::parse($dateTo)->format('M d, Y');
@endphp

{{-- ── Filter Bar ── --}}
<div class="eb-filter-bar">

    <div class="eb-filter-left">

        {{-- Segmented preset control --}}
        <div class="eb-seg">
            @foreach(['TODAY' => 'Today', 'THIS_MONTH' => 'This Month', 'LAST_30_DAYS' => 'Last 30 Days'] as $val => $label)
                <a href="{{ route('owner.dashboard', array_merge(request()->except(['range','date_from','date_to']), ['range' => $val, 'branch_id' => $branchId])) }}"
                   class="eb-seg-item {{ ($range ?? 'THIS_MONTH') === $val && !$isCustomRange ? 'active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Custom date range trigger --}}
        <div class="eb-date-wrap" id="ebDateWrap">
            <button type="button" class="eb-date-btn {{ $isCustomRange ? 'active' : '' }}" id="ebDateBtn">
                <i class="bi bi-calendar3 eb-date-btn-icon"></i>
                <span id="ebDateBtnLabel">
                    @if($isCustomRange)
                        {{ $formattedFrom }} — {{ $formattedTo }}
                    @else
                        Custom Range
                    @endif
                </span>
                @if($isCustomRange)
                    <a href="{{ route('owner.dashboard', array_merge(request()->except(['range','date_from','date_to']), ['range' => 'THIS_MONTH', 'branch_id' => $branchId])) }}"
                       class="eb-date-clear" title="Clear custom range">
                        <i class="bi bi-x"></i>
                    </a>
                @else
                    <i class="bi bi-chevron-down eb-date-chev"></i>
                @endif
            </button>

            {{-- Popover --}}
            <div class="eb-date-popover" id="ebDatePopover" style="display:none">
                <form method="GET" action="{{ route('owner.dashboard') }}" id="ebCustomForm">
                    <input type="hidden" name="range" value="CUSTOM">
                    @if($branchId)<input type="hidden" name="branch_id" value="{{ $branchId }}">@endif
                    <div class="eb-pop-label">Custom Date Range</div>
                    <div class="eb-pop-fields">
                        <div class="eb-pop-field">
                            <label class="eb-pop-field-label">From</label>
                            <input type="date" name="date_from" id="ebDateFrom" value="{{ $dateFrom }}" class="eb-pop-input">
                        </div>
                        <div class="eb-pop-sep">—</div>
                        <div class="eb-pop-field">
                            <label class="eb-pop-field-label">To</label>
                            <input type="date" name="date_to" id="ebDateTo" value="{{ $dateTo }}" class="eb-pop-input">
                        </div>
                    </div>
                    <button type="submit" class="eb-pop-apply">Apply</button>
                </form>
            </div>
        </div>

    </div>

    {{-- Branch chips --}}
    <div class="eb-branch-chips">
        <a href="{{ route('owner.dashboard', array_merge(request()->except(['branch_id']), ['range' => $range ?? 'THIS_MONTH'])) }}"
           class="eb-branch-chip {{ !$branchId ? 'active' : '' }}">
            All Branches
        </a>
        @foreach($branches as $branch)
            <a href="{{ route('owner.dashboard', array_merge(request()->all(), ['branch_id' => $branch->id])) }}"
               class="eb-branch-chip {{ (string) $branchId === (string) $branch->id ? 'active' : '' }}">
                {{ $branch->branch_code }}
            </a>
        @endforeach
    </div>

</div>

{{-- ── Period context strip ── --}}
<div class="eb-period-strip">
    <div class="eb-period-info">
        <i class="bi bi-calendar3"></i>
        <span>{{ $formattedFrom }} — {{ $formattedTo }}</span>
        @if($branchId && $selectedBranch)
            <span class="eb-period-dot">·</span>
            <span class="eb-period-branch">{{ $selectedBranch->branch_name }}</span>
        @endif
    </div>
</div>

{{-- ── Financial KPIs ── --}}
<div class="eb-kpi-row">

    {{-- Hero: Total Service Amount --}}
    <div class="eb-kpi-hero">
        <div class="eb-kpi-hero-label">
            <i class="bi bi-graph-up-arrow"></i>
            Total Service Amount
        </div>
        <div class="eb-kpi-hero-value">₱ {{ number_format((float) ($totalSales ?? 0), 2) }}</div>
        <div class="eb-kpi-hero-sub">Fully paid cases in period</div>
    </div>

    {{-- Secondary KPIs --}}
    <div class="eb-kpi-secondary">
        <div class="eb-kpi-card">
            <div class="eb-kpi-card-top">
                <span class="eb-kpi-card-label">Collected Amount</span>
                <span class="eb-kpi-card-icon eb-icon-green"><i class="bi bi-cash-stack"></i></span>
            </div>
            <div class="eb-kpi-card-value eb-val-green">₱ {{ number_format((float) ($totalCollected ?? 0), 2) }}</div>
            <div class="eb-kpi-card-sub">Total payments received</div>
        </div>

        <div class="eb-kpi-card">
            <div class="eb-kpi-card-top">
                <span class="eb-kpi-card-label">Outstanding Balance</span>
                <span class="eb-kpi-card-icon eb-icon-red"><i class="bi bi-exclamation-circle"></i></span>
            </div>
            <div class="eb-kpi-card-value eb-val-red">₱ {{ number_format((float) ($totalOutstanding ?? 0), 2) }}</div>
            <div class="eb-kpi-card-sub">Remaining unpaid amounts</div>
        </div>
    </div>

</div>

{{-- ── Case Operation Status ── --}}
<section class="eb-section">
    <div class="eb-section-header">
        <h3 class="eb-section-title">Case Operation Status</h3>
    </div>
    <div class="eb-ops-grid">
        @php
            $ops = [
                ['label' => 'Total Cases',       'val' => $totalCases   ?? 0, 'icon' => 'bi-folder2-open',   'cls' => 'eb-op-default'],
                ['label' => 'Ongoing Services',  'val' => $ongoingCases ?? 0, 'icon' => 'bi-activity',        'cls' => 'eb-op-slate'],
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

{{-- ── Bottom Grid: Branch Performance + Recent Cases ── --}}
<div class="eb-bottom-grid">

    {{-- Branch Performance Matrix --}}
    <section class="eb-section eb-card">
        <div class="eb-section-header">
            <div>
                <h3 class="eb-section-title">Branch Performance</h3>
                <p class="eb-section-sub">Revenue by branch in selected period</p>
            </div>
            <a href="{{ route('owner.analytics', ['branch_id' => $branchId, 'range' => $range]) }}" class="eb-link-btn">
                Analytics <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        @php
            $branchCardsCollection = collect($branchCards);
            $maxSales = max(1, (float) ($branchCardsCollection->max('sales') ?? 1));
            $barColors = ['#0f172a', '#9C5A1A', '#15803d', '#7c3aed', '#0369a1'];
        @endphp

        <div class="eb-branch-list">
            @forelse($branchCardsCollection as $i => $row)
                @php
                    $bSales = (float) ($row['sales'] ?? 0);
                    $bWidth = (int) round(($bSales / $maxSales) * 100);
                    $barColor = $barColors[$i % count($barColors)];
                @endphp
                <div class="eb-branch-row">
                    <div class="eb-branch-row-head">
                        <div>
                            <span class="eb-branch-code">{{ $row['branch']?->branch_code ?? 'N/A' }}</span>
                            <span class="eb-branch-name">{{ $row['branch']?->branch_name ?? '—' }}</span>
                        </div>
                        <div class="eb-branch-row-stats">
                            <span class="eb-branch-stat-pill eb-bsp-green">{{ $row['paid_cases'] ?? 0 }} paid</span>
                            @if(($row['partial_cases'] ?? 0) > 0)
                                <span class="eb-branch-stat-pill eb-bsp-amber">{{ $row['partial_cases'] }} partial</span>
                            @endif
                            @if(($row['unpaid_cases'] ?? 0) > 0)
                                <span class="eb-branch-stat-pill eb-bsp-red">{{ $row['unpaid_cases'] }} unpaid</span>
                            @endif
                            <span class="eb-branch-amount">₱ {{ number_format($bSales, 2) }}</span>
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

    {{-- Right column: Recent Cases + Top Packages --}}
    <div class="eb-right-col">

        {{-- Recent Cases --}}
        <section class="eb-section eb-card">
            <div class="eb-section-header">
                <div>
                    <h3 class="eb-section-title">Recent Cases</h3>
                    <p class="eb-section-sub">Latest verified cases in period</p>
                </div>
                <a href="{{ route('owner.history', ['branch_id' => $branchId]) }}" class="eb-link-btn">
                    View all <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="eb-recent-list">
                @forelse($recentCases as $rc)
                    <a href="{{ route('owner.cases.show', $rc) }}" class="eb-recent-row">
                        <div class="eb-recent-left">
                            <span class="eb-recent-code">{{ $rc?->case_code ?? '—' }}</span>
                            <span class="eb-recent-names">
                                {{ $rc->client?->full_name ?? '—' }}
                                <span class="eb-dim">/ {{ $rc->deceased?->full_name ?? '—' }}</span>
                            </span>
                        </div>
                        <div class="eb-recent-right">
                            <span class="eb-recent-branch">{{ $rc->branch?->branch_code ?? '—' }}</span>
                            @php
                                $rSt = strtolower($rc?->payment_status ?? 'unpaid');
                            @endphp
                            <span class="eb-recent-badge eb-rbadge-{{ $rSt }}">{{ ucfirst($rSt) }}</span>
                        </div>
                    </a>
                @empty
                    <div class="eb-empty">
                        <i class="bi bi-inbox eb-empty-icon"></i>
                        <p>No recent cases found.</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Top Packages (branch-specific only) --}}
        @if($branchId && $topPackages && $topPackages->count())
        <section class="eb-section eb-card">
            <div class="eb-section-header">
                <div>
                    <h3 class="eb-section-title">Top Packages</h3>
                    <p class="eb-section-sub">By case volume — paid cases only</p>
                </div>
            </div>
            <div class="eb-pkg-list">
                @foreach($topPackages as $pkg)
                    <div class="eb-pkg-row">
                        <div class="eb-pkg-name">{{ $pkg->service_package }}</div>
                        <div class="eb-pkg-stats">
                            <span class="eb-pkg-cases">{{ $pkg->total_cases }} {{ Str::plural('case', $pkg->total_cases) }}</span>
                            <span class="eb-pkg-amount">₱ {{ number_format((float) $pkg->total_sales, 2) }}</span>
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
/* ── Shell ── */
.eb-shell {
    padding: 1.5rem var(--panel-content-inline, 1.5rem) 3rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    color: #0f172a;
    font-family: inherit;
}

/* ── Filter Bar ── */
.eb-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.eb-filter-left {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* Filter chips */
.eb-seg {
    display: flex;
    gap: 6px;
    align-items: center;
}
.eb-seg-item {
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    text-decoration: none;
    white-space: nowrap;
    border: 0.5px solid #e2e8f0;
    background: #fff;
    transition: all 0.15s;
    line-height: 1;
}
.eb-seg-item:hover { border-color: #94a3b8; color: #0f172a; }
.eb-seg-item.active {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
    font-weight: 600;
}

/* Custom date trigger */
.eb-date-wrap { position: relative; }
.eb-date-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 12px 6px 10px;
    height: 34px;
    border-radius: 10px;
    border: 0.5px solid #e2e8f0;
    background: #fff;
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    font-family: inherit;
    white-space: nowrap;
    transition: all 0.15s;
}
.eb-date-btn:hover { border-color: #94a3b8; color: #0f172a; }
.eb-date-btn.active {
    border-color: #0f172a;
    background: #0f172a;
    color: #fff;
}
.eb-date-btn-icon { font-size: 13px; opacity: 0.7; }
.eb-date-chev { font-size: 10px; opacity: 0.5; }
.eb-date-clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px; height: 16px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    color: #fff;
    font-size: 11px;
    text-decoration: none;
    margin-left: 2px;
    transition: background 0.12s;
}
.eb-date-clear:hover { background: rgba(255,255,255,0.35); }

/* Date popover */
.eb-date-popover {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    z-index: 100;
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
    min-width: 280px;
}
.eb-pop-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #94a3b8;
    margin-bottom: 12px;
}
.eb-pop-fields {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    margin-bottom: 12px;
}
.eb-pop-field { display: flex; flex-direction: column; gap: 4px; flex: 1; }
.eb-pop-field-label {
    font-size: 10px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.eb-pop-input {
    padding: 7px 10px;
    border: 0.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12px;
    font-family: inherit;
    color: #0f172a;
    background: #f8fafc;
    width: 100%;
    transition: border-color 0.12s;
}
.eb-pop-input:focus { outline: none; border-color: #94a3b8; background: #fff; }
.eb-pop-sep { font-size: 14px; color: #cbd5e1; padding-bottom: 8px; }
.eb-pop-apply {
    width: 100%;
    padding: 8px;
    background: #0f172a;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.12s;
}
.eb-pop-apply:hover { background: #1e293b; }

/* Branch chips */
.eb-branch-chips {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}
.eb-branch-chip {
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    border: 0.5px solid #e2e8f0;
    background: #fff;
    white-space: nowrap;
    transition: all 0.15s;
    line-height: 1;
}
.eb-branch-chip:hover { border-color: #94a3b8; color: #0f172a; }
.eb-branch-chip.active { background: #0f172a; color: #fff; border-color: #0f172a; }

/* ── Period strip ── */
.eb-period-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.eb-period-info {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #94a3b8;
}
.eb-period-dot { color: #cbd5e1; }
.eb-period-branch { color: #9c5a1a; font-weight: 600; }

/* ── KPI Row ── */
.eb-kpi-row {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1rem;
}
@media (max-width: 860px) { .eb-kpi-row { grid-template-columns: 1fr; } }

.eb-kpi-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
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
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #d6b073;
    display: flex;
    align-items: center;
    gap: 6px;
    position: relative;
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
    color: #64748b;
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
    border: 0.5px solid #e2e8f0;
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
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #94a3b8;
}
.eb-kpi-card-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
}
.eb-icon-green { background: #f0fdf4; color: #16a34a; }
.eb-icon-red   { background: #fff1f2; color: #dc2626; }

.eb-kpi-card-value {
    font-size: 1.6rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.eb-val-green { color: #15803d; }
.eb-val-red   { color: #b91c1c; }
.eb-kpi-card-sub { font-size: 11px; color: #94a3b8; margin-top: 4px; }

/* ── Ops Grid ── */
.eb-ops-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}
@media (max-width: 900px) { .eb-ops-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 560px) { .eb-ops-grid { grid-template-columns: repeat(2, 1fr); } }

.eb-op-card {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    border-bottom: 3px solid transparent;
}
.eb-op-card-icon { font-size: 18px; }
.eb-op-card-val  { font-size: 1.8rem; font-weight: 700; line-height: 1; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.eb-op-card-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }

.eb-op-default { border-bottom-color: #0f172a; }
.eb-op-default .eb-op-card-icon { color: #0f172a; }
.eb-op-default .eb-op-card-val  { color: #0f172a; }

.eb-op-slate { border-bottom-color: #64748b; }
.eb-op-slate .eb-op-card-icon { color: #475569; }
.eb-op-slate .eb-op-card-val  { color: #475569; }

.eb-op-green { border-bottom-color: #16a34a; }
.eb-op-green .eb-op-card-icon { color: #16a34a; }
.eb-op-green .eb-op-card-val  { color: #15803d; }

.eb-op-amber { border-bottom-color: #d97706; }
.eb-op-amber .eb-op-card-icon { color: #d97706; }
.eb-op-amber .eb-op-card-val  { color: #b45309; }

.eb-op-red { border-bottom-color: #dc2626; }
.eb-op-red .eb-op-card-icon { color: #dc2626; }
.eb-op-red .eb-op-card-val  { color: #b91c1c; }

/* ── Bottom Grid ── */
.eb-bottom-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1rem;
    align-items: start;
}
@media (max-width: 1100px) { .eb-bottom-grid { grid-template-columns: 1fr; } }

.eb-right-col { display: flex; flex-direction: column; gap: 1rem; }

/* ── Section ── */
.eb-section { display: flex; flex-direction: column; gap: 1rem; }
.eb-section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.eb-section-title { font-size: 14px; font-weight: 700; color: #0f172a; }
.eb-section-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }
.eb-card {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px;
}
.eb-link-btn {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    padding: 5px 10px;
    border: 0.5px solid #e2e8f0;
    border-radius: 7px;
    transition: all 0.12s;
}
.eb-link-btn:hover { background: #0f172a; color: #fff; border-color: #0f172a; }

/* ── Branch list ── */
.eb-branch-list { display: flex; flex-direction: column; gap: 14px; }
.eb-branch-row { display: flex; flex-direction: column; gap: 6px; }
.eb-branch-row-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.eb-branch-code { font-size: 10px; font-weight: 700; color: #9c5a1a; text-transform: uppercase; letter-spacing: 0.1em; margin-right: 6px; }
.eb-branch-name { font-size: 13px; font-weight: 600; color: #0f172a; }
.eb-branch-row-stats { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.eb-branch-stat-pill {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 999px;
}
.eb-bsp-green { background: #f0fdf4; color: #15803d; border: 0.5px solid #bbf7d0; }
.eb-bsp-amber { background: #fffbeb; color: #a16207; border: 0.5px solid #fde68a; }
.eb-bsp-red   { background: #fef2f2; color: #b91c1c; border: 0.5px solid #fecaca; }
.eb-branch-amount { font-size: 13px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }

.eb-branch-bar-track {
    width: 100%;
    height: 5px;
    background: #f1f5f9;
    border-radius: 999px;
    overflow: hidden;
}
.eb-branch-bar-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.6s ease;
}

/* ── Recent Cases ── */
.eb-recent-list { display: flex; flex-direction: column; }
.eb-recent-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 0.5px solid #f1f5f9;
    text-decoration: none;
    color: inherit;
    transition: background 0.1s;
}
.eb-recent-row:last-child { border-bottom: none; }
.eb-recent-row:hover { opacity: 0.75; }
.eb-recent-left { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.eb-recent-code { font-size: 10px; font-weight: 700; color: #9c5a1a; letter-spacing: 0.05em; }
.eb-recent-names { font-size: 12px; font-weight: 500; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
.eb-dim { color: #94a3b8; font-weight: 400; }
.eb-recent-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.eb-recent-branch { font-size: 10px; font-weight: 600; color: #64748b; }
.eb-recent-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 999px; }
.eb-rbadge-paid    { background: #f0fdf4; color: #15803d; }
.eb-rbadge-partial { background: #fffbeb; color: #a16207; }
.eb-rbadge-unpaid  { background: #fef2f2; color: #b91c1c; }

/* ── Top Packages ── */
.eb-pkg-list { display: flex; flex-direction: column; gap: 8px; }
.eb-pkg-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    background: #f8fafc;
    border-radius: 8px;
    border: 0.5px solid #e2e8f0;
}
.eb-pkg-name  { font-size: 12px; font-weight: 600; color: #0f172a; }
.eb-pkg-stats { display: flex; align-items: center; gap: 8px; }
.eb-pkg-cases { font-size: 11px; color: #64748b; }
.eb-pkg-amount { font-size: 12px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }

/* ── Empty state ── */
.eb-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 2rem; color: #94a3b8; font-size: 12px; text-align: center; }
.eb-empty-icon { font-size: 2rem; }

/* ── Dark mode ── */
html[data-theme='dark'] .eb-shell { color: #e2ecf9; }
html[data-theme='dark'] .eb-seg { background: #192d47; }
html[data-theme='dark'] .eb-seg-item { color: #8aa7c5; }
html[data-theme='dark'] .eb-seg-item:hover { color: #e2ecf9; background: rgba(255,255,255,0.06); }
html[data-theme='dark'] .eb-seg-item.active { background: #243d5a; color: #e2ecf9; box-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 0 0 0.5px #4a6888; }
html[data-theme='dark'] .eb-date-btn { background: #182638; border-color: #2e4560; color: #8aa7c5; }
html[data-theme='dark'] .eb-date-btn:hover { border-color: #5a7898; color: #e2ecf9; }
html[data-theme='dark'] .eb-date-btn.active { background: #e2ecf9; color: #0f172a; border-color: #e2ecf9; }
html[data-theme='dark'] .eb-date-popover { background: #182638; border-color: #2e4560; box-shadow: 0 8px 24px rgba(0,0,0,0.4); }
html[data-theme='dark'] .eb-pop-input { background: #1e334f; border-color: #2e4560; color: #e2ecf9; }
html[data-theme='dark'] .eb-pop-input:focus { border-color: #5a7898; background: #243d5a; }
html[data-theme='dark'] .eb-branch-chip { background: #182638; border-color: #2e4560; color: #8aa7c5; }
html[data-theme='dark'] .eb-branch-chip:hover { border-color: #5a7898; color: #e2ecf9; }
html[data-theme='dark'] .eb-branch-chip.active { background: #e2ecf9; color: #0f172a; border-color: #e2ecf9; }
html[data-theme='dark'] .eb-period-info { color: #5a7898; }
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
html[data-theme='dark'] .eb-recent-names,
html[data-theme='dark'] .eb-pkg-name,
html[data-theme='dark'] .eb-pkg-amount { color: #e2ecf9; }
html[data-theme='dark'] .eb-op-slate .eb-op-card-val { color: #94a3b8; }
html[data-theme='dark'] .eb-link-btn { background: #1e334f; border-color: #2e4560; color: #8aa7c5; }
html[data-theme='dark'] .eb-link-btn:hover { background: #e2ecf9; color: #0f172a; border-color: #e2ecf9; }
html[data-theme='dark'] .eb-branch-bar-track { background: #2e4560; }
html[data-theme='dark'] .eb-recent-row { border-bottom-color: #1e334f; }
html[data-theme='dark'] .eb-recent-code { color: #c88a4a; }
html[data-theme='dark'] .eb-recent-branch { color: #5a7898; }
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
