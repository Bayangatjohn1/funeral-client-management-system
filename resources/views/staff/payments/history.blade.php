@extends('layouts.panel')

@section('page_title', 'Payment Monitoring')
@section('page_desc', 'Review case payment summaries and recorded payment transactions.')

@section('content')
@php
    $user = auth()->user();
    $isBranchOnly = ($branches ?? collect())->count() === 1;
    $isBranchAdmin = $user?->isBranchAdmin();
    $isMainAdmin = $user?->isMainBranchAdmin();
    $isOwner = $user?->isOwner();
    $activeTab = $activeTab ?? 'summary';
    $monitoringRoute = request()->routeIs('admin.payments.index') || request()->routeIs('admin.payment-monitoring')
        ? 'admin.payments.index'
        : 'payments.history';
    $paymentStatus = $paymentStatus ?? $statusAfterPayment ?? null;
    $dateRange = request('date_preset') ?: ((request()->filled('paid_from') || request()->filled('paid_to')) ? 'custom' : 'all');
    $emptyMessage = $isBranchAdmin
        ? 'No payment records found for your assigned branch.'
        : 'No payment records found for the selected filters.';

    $tabQuery = fn (string $tab) => array_filter(array_merge(request()->except(['tab', 'page', 'transactions_page', 'open_case']), ['tab' => $tab]), fn ($v) => $v !== null && $v !== '');
    $statusClass = fn ($status) => match (strtoupper((string) $status)) {
        'PAID' => 'is-paid',
        'PARTIAL' => 'is-partial',
        default => 'is-unpaid',
    };
    $caseRoute = function ($case) use ($isOwner) {
        if (!$case) {
            return '#';
        }

        return $isOwner
            ? route('owner.cases.show', $case)
            : route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]);
    };
@endphp

<style>
    .pm-page { color: var(--ink); padding: 1.5rem var(--panel-content-inline, 1.5rem) 3rem; }
    .pm-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
    .pm-kpi { background: var(--card); border: 1px solid var(--border); border-radius: .5rem; padding: 1rem; }
    .pm-kpi span { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-muted); font-weight:700; }
    .pm-kpi strong { display:block; margin-top:.35rem; font-size:1.25rem; font-weight:800; font-variant-numeric:tabular-nums; }
    .pm-kpi .good { color:#15803d; }
    .pm-kpi .warn { color:#b91c1c; }

    .pm-toolbar-shell { background: var(--card); border:1px solid var(--border); border-radius:.75rem; padding:.75rem; margin-bottom:1rem; overflow:visible; }
    .pm-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:.75rem; }
    .pm-field { flex:1 1 11rem; min-width:10rem; }
    .pm-field.branch { flex:1 1 15rem; }
    .pm-field.search { flex:2 1 24rem; min-width:18rem; }
    .pm-field.has-icon { position:relative; }
    .pm-field.has-icon > i { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:var(--ink-muted); pointer-events:none; z-index:1; }
    .pm-field.has-icon .pm-control { padding-left:2.35rem; }
    .pm-control {
        width:100%; height:2.75rem; border:1px solid var(--border); border-radius:.75rem;
        background:#fff; color:var(--ink); font-size:.875rem; padding:0 .85rem;
    }
    .pm-control:disabled { background:var(--surface-muted); color:var(--ink-muted); opacity:1; }
    .pm-actions { display:flex; gap:.75rem; flex:0 0 auto; align-items:center; }
    .pm-btn {
        height:2.75rem; border-radius:.75rem; border:1px solid var(--border); background:#fff;
        color:var(--ink); padding:0 .95rem; display:inline-flex; align-items:center; gap:.45rem;
        font-weight:700; font-size:.875rem; text-decoration:none;
    }
    .pm-btn.primary { background:var(--accent); border-color:var(--accent); color:#fff; }
    .pm-hidden-date-fields { display:none; }
    .pm-modal-backdrop { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(15,23,42,.45); z-index:60; padding:1rem; }
    .pm-modal-backdrop.open { display:flex; }
    .pm-modal { width:min(100%,28rem); background:var(--card); border:1px solid var(--border); border-radius:1rem; box-shadow:0 20px 45px rgba(15,23,42,.22); overflow:hidden; }
    .pm-modal-hd { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.1rem; border-bottom:1px solid var(--border); }
    .pm-modal-title { font-weight:900; }
    .pm-modal-body { display:grid; gap:.8rem; padding:1rem 1.1rem; }
    .pm-modal-ft { display:flex; justify-content:flex-end; gap:.65rem; padding:1rem 1.1rem; border-top:1px solid var(--border); }

    .pm-tabs { display:flex; gap:.35rem; border-bottom:1px solid var(--border); margin:1rem 0; }
    .pm-tab {
        display:inline-flex; align-items:center; gap:.45rem; padding:.75rem .95rem; color:var(--ink-muted);
        border-bottom:2px solid transparent; text-decoration:none; font-weight:800; font-size:.9rem;
    }
    .pm-tab.active { color:var(--accent); border-color:var(--accent); }

    .pm-panel { background:var(--card); border:1px solid var(--border); border-radius:.75rem; overflow:hidden; }
    .pm-money { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; font-weight:700; }
    .pm-row-list { background:var(--card); }
    .pm-case-row { display:grid; grid-template-columns:minmax(7rem,.65fr) minmax(0,1.8fr) minmax(12rem,.9fr) auto; gap:1rem; align-items:center; width:100%; padding:1rem; border:0; border-bottom:1px solid var(--border); background:transparent; color:inherit; text-align:left; }
    .pm-case-row.is-toggle { cursor:pointer; }
    .pm-case-row.is-toggle:hover { background:rgba(15,23,42,.025); }
    .pm-case-row[aria-expanded="true"] .pm-chev { transform:rotate(180deg); }
    .pm-row-main { min-width:0; }
    .pm-row-title { font-weight:850; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pm-row-meta { margin-top:.22rem; color:var(--ink-muted); font-size:.82rem; display:flex; gap:.4rem; flex-wrap:wrap; align-items:center; }
    .pm-row-date { color:var(--ink-muted); font-size:.86rem; white-space:nowrap; }
    .pm-row-actions { display:flex; align-items:center; justify-content:flex-end; gap:.65rem; min-width:max-content; }
    .pm-row-link { display:inline-flex; align-items:center; gap:.4rem; color:var(--accent); font-weight:850; font-size:.84rem; text-decoration:none; background:transparent; border:0; padding:0; }
    .pm-light-link { display:inline-flex; align-items:center; gap:.35rem; color:var(--ink-muted); font-weight:750; font-size:.84rem; text-decoration:none; background:transparent; border:0; padding:0; }
    .pm-light-link:hover { color:var(--accent); }
    .pm-icon-toggle { display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; border:1px solid var(--border); border-radius:.6rem; background:#fff; color:var(--ink-muted); }
    .pm-summary-detail { display:none; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; padding:.85rem 1rem 1rem; background:var(--surface-muted); border-bottom:1px solid var(--border); }
    .pm-summary-detail.open { display:grid; }
    .pm-summary-stat { background:var(--card); border:1px solid var(--border); border-radius:.75rem; padding:.75rem; min-width:0; }
    .pm-summary-stat span { display:block; color:var(--ink-muted); font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .pm-summary-stat strong { display:block; margin-top:.25rem; color:var(--ink); font-size:.95rem; font-weight:900; font-variant-numeric:tabular-nums; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .pm-muted { color:var(--ink-muted); }
    .pm-case { font-weight:800; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; }
    .pm-name { font-weight:800; }
    .pm-sub { margin-top:.15rem; font-size:.78rem; color:var(--ink-muted); }
    .pm-status { display:inline-flex; align-items:center; border-radius:999px; padding:.22rem .58rem; font-size:.72rem; font-weight:800; }
    .pm-status.is-paid { background:#dcfce7; color:#166534; }
    .pm-status.is-partial { background:#fef3c7; color:#92400e; }
    .pm-status.is-unpaid { background:#fee2e2; color:#991b1b; }
    .pm-link { color:var(--accent); font-weight:800; text-decoration:none; white-space:nowrap; }
    .pm-empty { padding:3rem 1rem; text-align:center; color:var(--ink-muted); font-weight:700; }
    .pm-foot { padding:.85rem 1rem; border-top:1px solid var(--border); display:flex; justify-content:space-between; gap:.75rem; flex-wrap:wrap; align-items:center; }

    .pm-trans-list { background:var(--card); }
    .pm-trans-item { background:var(--card); border-bottom:1px solid var(--border); overflow:hidden; }
    .pm-trans-row { width:100%; display:grid; grid-template-columns:minmax(7rem,.65fr) minmax(0,1.8fr) minmax(12rem,.9fr) auto; align-items:center; gap:1rem; padding:1rem; border:0; background:transparent; color:inherit; text-align:left; cursor:pointer; }
    .pm-trans-main { min-width:0; }
    .pm-trans-title { font-weight:850; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pm-trans-meta { margin-top:.25rem; color:var(--ink-muted); font-size:.82rem; display:flex; gap:.45rem; flex-wrap:wrap; }
    .pm-trans-money { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; min-width:0; }
    .pm-trans-stat span { display:block; color:var(--ink-muted); font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .pm-trans-stat strong { display:block; margin-top:.2rem; font-size:.9rem; font-weight:900; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .pm-trans-side { display:flex; align-items:center; justify-content:flex-end; gap:.75rem; }
    .pm-trans-count { color:var(--ink-muted); font-size:.78rem; font-weight:800; white-space:nowrap; }
    .pm-expand-label { color:var(--accent); font-size:.82rem; font-weight:900; white-space:nowrap; }
    .pm-chev { color:var(--ink-muted); transition:transform .16s ease; }
    .pm-trans-row[aria-expanded="true"] .pm-chev { transform:rotate(180deg); }
    .pm-detail { display:none; background:var(--surface-muted); padding:0 0 .75rem; }
    .pm-detail.open { display:block; }
    .pm-case-overview { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; padding:.85rem 1rem; background:var(--surface-muted); border-bottom:1px solid var(--border); }
    .pm-detail-actions { display:flex; gap:.5rem; padding:.75rem 1rem 0; flex-wrap:wrap; }

    .pm-txn-list { display:flex; flex-direction:column; gap:.5rem; padding:.75rem 1rem 0; }
    .pm-txn-card { background:var(--card); border:1px solid var(--border); border-radius:.6rem; overflow:hidden; }
    .pm-txn-hd { display:flex; justify-content:space-between; align-items:flex-start; padding:.85rem 1rem; gap:1rem; }
    .pm-txn-info { min-width:0; flex:1 1 0; }
    .pm-txn-rec { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-weight:900; font-size:.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:24rem; color:var(--ink); }
    .pm-txn-rec-label { display:block; margin-bottom:.16rem; color:var(--ink-muted); font-size:.65rem; font-family:inherit; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .pm-txn-sub { display:flex; flex-wrap:wrap; gap:.3rem; font-size:.78rem; color:var(--ink-muted); margin-top:.2rem; align-items:center; }
    .pm-dot { color:var(--ink-muted); }
    .pm-txn-right { display:flex; flex-direction:column; align-items:flex-end; gap:.2rem; flex-shrink:0; }
    .pm-txn-amt { font-size:1rem; font-weight:900; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .pm-txn-bal { display:flex; flex-direction:column; align-items:flex-end; margin-top:.15rem; }
    .pm-txn-bal-lbl { font-size:.65rem; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-muted); font-weight:700; }
    .pm-txn-bal-val { font-size:.8rem; font-weight:700; font-variant-numeric:tabular-nums; color:var(--ink-muted); white-space:nowrap; }
    .pm-txn-foot { padding:.1rem 1rem .55rem; display:flex; flex-wrap:wrap; align-items:center; gap:.35rem; font-size:.78rem; color:var(--ink-muted); }
    .pm-txn-foot strong { color:var(--ink); font-weight:700; }
    .pm-txn-tog {
        display:inline-flex; align-items:center; gap:.3rem;
        margin:0 1rem .55rem; padding:.28rem .6rem;
        background:transparent; border:1px solid var(--border); border-radius:.4rem;
        color:var(--ink-muted); font-size:.75rem; font-weight:700; cursor:pointer;
    }
    .pm-txn-tog:hover { background:var(--surface-muted); color:var(--ink); }
    .pm-txn-tog[aria-expanded="true"] .pm-chev { transform:rotate(180deg); }
    .pm-txn-det { border-top:1px solid var(--border); padding:.65rem 1rem .85rem; background:var(--surface-muted); }
    .pm-txn-det-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(11rem,1fr)); gap:.5rem; }
    .pm-txn-det-cell span { display:block; font-size:.65rem; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-muted); font-weight:700; margin-bottom:.2rem; }
    .pm-txn-det-cell strong { font-size:.8rem; word-break:break-word; }
    .pm-txn-det-full { grid-column:1/-1; }

    html[data-theme='dark'] .pm-control,
    html[data-theme='dark'] .pm-btn { background:#1e334f; color:#e2ecf9; }

    @media (max-width: 900px) {
        .pm-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .pm-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width: 640px) {
        .pm-kpis { grid-template-columns:1fr; }
        .pm-toolbar { flex-wrap:wrap; min-width:0; }
        .pm-field, .pm-field.search, .pm-field.branch, .pm-actions, .pm-actions .pm-btn { width:100%; flex-basis:100%; min-width:0; }
        .pm-summary-top { flex-direction:column; }
        .pm-summary-grid { grid-template-columns:1fr; }
        .pm-summary-actions, .pm-summary-actions .pm-btn { width:100%; justify-content:center; }
        .pm-case-row, .pm-trans-row { grid-template-columns:minmax(0,1fr); }
        .pm-summary-detail, .pm-case-overview, .pm-trans-money { grid-template-columns:1fr 1fr; }
        .pm-trans-side, .pm-row-actions { justify-content:flex-start; }
        .pm-txn-hd { flex-direction:column; gap:.5rem; }
        .pm-txn-right { align-items:flex-start; }
    }
</style>

<div class="pm-page">
    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}</div>
    @endif

    <div class="pm-kpis">
        <div class="pm-kpi"><span>Total Cases with Payments</span><strong>{{ number_format($totalCasesWithPayments ?? 0) }}</strong></div>
        <div class="pm-kpi"><span>Total Payment Transactions</span><strong>{{ number_format($paymentRecordsCount ?? 0) }}</strong></div>
        <div class="pm-kpi"><span>Total Collected</span><strong class="good">PHP {{ number_format((float) ($totalCollected ?? 0), 2) }}</strong></div>
        <div class="pm-kpi"><span>Outstanding Balance</span><strong class="warn">PHP {{ number_format((float) ($totalOutstanding ?? 0), 2) }}</strong></div>
    </div>

    <div class="pm-toolbar-shell">
        <form id="pmFilterForm" method="GET" action="{{ route($monitoringRoute) }}" class="pm-toolbar">
            <input type="hidden" name="tab" value="{{ $activeTab }}">

            <div class="pm-field search has-icon">
                <i class="bi bi-search"></i>
                <input class="pm-control" name="q" value="{{ $q ?? '' }}" placeholder="Search client, deceased, case no., payment record, accounting ref, transaction ref..." autocomplete="off">
            </div>

            @if(!$isBranchOnly)
                <div class="pm-field branch has-icon">
                    <i class="bi bi-building"></i>
                    <select name="branch_id" class="pm-control" title="Branch">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) ($selectedBranchId ?? '') === (string) $branch->id)>
                                {{ $branch->branch_code }} - {{ $branch->branch_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @elseif($assignedBranch)
                <div class="pm-field branch has-icon">
                    <i class="bi bi-building"></i>
                    <div class="pm-control" role="status">
                        Assigned Branch: {{ $assignedBranch->branch_code }} - {{ $assignedBranch->branch_name }}
                    </div>
                </div>
            @endif

            <div class="pm-field has-icon">
                <i class="bi bi-credit-card"></i>
                <select name="payment_status" class="pm-control" title="Payment Status">
                    <option value="">All Payment Status</option>
                    <option value="UNPAID" @selected($paymentStatus === 'UNPAID')>Unpaid</option>
                    <option value="PARTIAL" @selected($paymentStatus === 'PARTIAL')>Partial</option>
                    <option value="PAID" @selected($paymentStatus === 'PAID')>Paid</option>
                </select>
            </div>

            <div class="pm-field has-icon">
                <i class="bi bi-clipboard-check"></i>
                <select name="case_status" class="pm-control" title="Case Status">
                    <option value="">All Case Status</option>
                    <option value="DRAFT" @selected(($caseStatus ?? '') === 'DRAFT')>Draft</option>
                    <option value="ACTIVE" @selected(($caseStatus ?? '') === 'ACTIVE')>Active</option>
                    <option value="COMPLETED" @selected(($caseStatus ?? '') === 'COMPLETED')>Completed</option>
                </select>
            </div>

            <div class="pm-field has-icon">
                <i class="bi bi-wallet2"></i>
                <select name="payment_method" class="pm-control" title="Payment Method">
                    <option value="">All Payment Methods</option>
                    <option value="cash" @selected(($paymentMethod ?? '') === 'cash')>Cash</option>
                    <option value="bank_transfer" @selected(($paymentMethod ?? '') === 'bank_transfer')>Bank Transfer</option>
                </select>
            </div>

            <div class="pm-field has-icon">
                <i class="bi bi-calendar3"></i>
                <select id="pmDateRange" name="date_preset" class="pm-control" title="Date Range">
                    <option value="all" @selected($dateRange === 'all' || $dateRange === 'any')>All Payment Dates</option>
                    <option value="today" @selected($dateRange === 'today')>Today</option>
                    <option value="week" @selected($dateRange === 'week')>This Week</option>
                    <option value="month" @selected($dateRange === 'month')>This Month</option>
                    <option value="year" @selected($dateRange === 'year')>This Year</option>
                    <option value="custom" @selected($dateRange === 'custom')>Custom</option>
                </select>
            </div>

            <div class="pm-hidden-date-fields">
                <input id="pmPaidFrom" type="date" name="paid_from" value="{{ $paidFrom ?? '' }}">
                <input id="pmPaidTo" type="date" name="paid_to" value="{{ $paidTo ?? '' }}">
            </div>

            <div class="pm-actions">
                <a href="{{ route($monitoringRoute) }}" class="pm-btn"><i class="bi bi-arrow-counterclockwise"></i><span>Reset</span></a>
            </div>
        </form>
    </div>

    <div class="pm-tabs" role="tablist">
        <a class="pm-tab {{ $activeTab === 'summary' ? 'active' : '' }}" href="{{ route($monitoringRoute, $tabQuery('summary')) }}">
            <i class="bi bi-folder2-open"></i> Case Payment Summary
        </a>
        <a class="pm-tab {{ $activeTab === 'transactions' ? 'active' : '' }}" href="{{ route($monitoringRoute, $tabQuery('transactions')) }}">
            <i class="bi bi-list-ul"></i> Transaction History
        </a>
    </div>

    @if($activeTab === 'summary')
        <div class="pm-panel">
            <div class="pm-row-list">
                @forelse($paymentCases as $case)
                    @php
                        $latestPaymentAt = $case->payments_max_paid_at ? \Illuminate\Support\Carbon::parse($case->payments_max_paid_at) : null;
                        $summaryId = 'summary-case-' . $case->id;
                    @endphp
                    <button type="button" class="pm-case-row is-toggle" data-pm-summary-toggle="{{ $summaryId }}" aria-expanded="false">
                        <div class="pm-case">{{ $case->case_code ?? '-' }}</div>
                        <div class="pm-row-main">
                            <div class="pm-row-title">{{ $case->client?->full_name ?? '-' }} &ndash; {{ $case->deceased?->full_name ?? '-' }}</div>
                            <div class="pm-row-meta">
                                <span>{{ $case->branch?->branch_code ?? '-' }}{{ $case->branch?->branch_name ? ' · ' . $case->branch->branch_name : '' }}</span>
                            </div>
                        </div>
                        <div class="pm-row-date">Last payment: {{ $latestPaymentAt?->format('M d, Y h:i A') ?? '-' }}</div>
                        <div class="pm-row-actions">
                            <span class="pm-status {{ $statusClass($case->payment_status) }}">{{ \Illuminate\Support\Str::headline($case->payment_status ?? 'UNPAID') }}</span>
                            <a class="pm-light-link" data-pm-stop-row-toggle href="{{ route($monitoringRoute, array_filter(array_merge(request()->except(['tab', 'q', 'page', 'transactions_page', 'open_case']), ['tab' => 'transactions', 'q' => $case->case_code, 'open_case' => $case->case_code]))) }}">
                                <i class="bi bi-list-ul"></i><span>View Transactions</span>
                            </a>
                            <span class="pm-icon-toggle" aria-hidden="true"><i class="bi bi-chevron-down pm-chev"></i></span>
                        </div>
                    </button>
                    <div id="{{ $summaryId }}" class="pm-summary-detail">
                        <div class="pm-summary-stat"><span>Total Case Amount</span><strong>PHP {{ number_format((float) $case->total_amount, 2) }}</strong></div>
                        <div class="pm-summary-stat"><span>Total Paid</span><strong>PHP {{ number_format((float) $case->total_paid, 2) }}</strong></div>
                        <div class="pm-summary-stat"><span>Remaining Balance</span><strong>PHP {{ number_format((float) $case->balance_amount, 2) }}</strong></div>
                        <div class="pm-summary-stat"><span>Transactions</span><strong>{{ number_format($case->payments_count ?? 0) }}</strong></div>
                    </div>
                @empty
                    <div class="pm-empty">{{ $emptyMessage }}</div>
                @endforelse
            </div>
            @if($paymentCases->total() > 0)
                <div class="pm-foot">
                    <span class="pm-muted">Showing {{ $paymentCases->firstItem() }}-{{ $paymentCases->lastItem() }} of {{ number_format($paymentCases->total()) }} cases</span>
                    {{ $paymentCases->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="pm-panel">
            <div class="pm-trans-list">
                @forelse($transactionCases as $case)
                    @php
                        $casePayments = $case->payments ?? collect();
                        $latestPayment = $casePayments->first();
                        $latestPaymentAt = $latestPayment?->paid_at
                            ?? ($case->payments_max_paid_at ? \Illuminate\Support\Carbon::parse($case->payments_max_paid_at) : null);
                        $latestAmount = $latestPayment?->amount;
                        $detailId = 'case-transactions-' . $case->id;
                    @endphp
                    <div class="pm-trans-item">
                        <button type="button" class="pm-trans-row" data-pm-transaction-toggle="{{ $detailId }}" data-case-code="{{ $case->case_code }}" aria-expanded="false">
                            <div class="pm-case">{{ $case->case_code ?? '-' }}</div>
                            <div class="pm-trans-main">
                                <div class="pm-trans-title">{{ $case->client?->full_name ?? '-' }} &ndash; {{ $case->deceased?->full_name ?? '-' }}</div>
                                <div class="pm-trans-meta">
                                    <span>{{ $case->branch?->branch_code ?? '-' }}{{ $case->branch?->branch_name ? ' · ' . $case->branch->branch_name : '' }}</span>
                                </div>
                            </div>
                            <div class="pm-row-date">Last payment: {{ $latestPaymentAt?->format('M d, Y h:i A') ?? '-' }}</div>
                            <div class="pm-trans-side">
                                <span class="pm-status {{ $statusClass($case->payment_status) }}">{{ \Illuminate\Support\Str::headline($case->payment_status ?? 'UNPAID') }}</span>
                                <span class="pm-expand-label">View Full Transactions</span>
                                <i class="bi bi-chevron-down pm-chev"></i>
                            </div>
                        </button>

                        <div id="{{ $detailId }}" class="pm-detail">
                            <div class="pm-case-overview">
                                <div class="pm-summary-stat"><span>Total Paid / Total Case Amount</span><strong>PHP {{ number_format((float) $case->total_paid, 2) }} / PHP {{ number_format((float) $case->total_amount, 2) }}</strong></div>
                                <div class="pm-summary-stat"><span>Remaining Balance</span><strong>PHP {{ number_format((float) $case->balance_amount, 2) }}</strong></div>
                                <div class="pm-summary-stat"><span>Latest Payment Amount</span><strong>{{ $latestAmount !== null ? 'PHP ' . number_format((float) $latestAmount, 2) : 'Not available' }}</strong></div>
                                <div class="pm-summary-stat"><span>Total Transactions</span><strong>{{ number_format($case->payments_count ?? $casePayments->count()) }}</strong></div>
                            </div>
                            <div class="pm-txn-list">
                                @forelse($casePayments as $payment)
                                    @php
                                        $method = $payment->payment_method ?: $payment->payment_mode ?: 'cash';
                                        $isBank = $method === 'bank_transfer';
                                        $methodLabel = $isBank ? 'Bank Transfer' : 'Cash';
                                        $channel = $payment->bank_or_channel === 'Other'
                                            ? ($payment->other_bank_or_channel ?: 'Other')
                                            : ($payment->bank_or_channel ?: null);
                                        $paidAt = $payment->paid_at ?? $payment->paid_date;
                                        $hasBalSnap = $payment->balance_after_payment !== null;
                                        $balanceLabel = $hasBalSnap ? 'Balance After Payment' : 'Current Balance';
                                        $balanceValue = $hasBalSnap ? $payment->balance_after_payment : $case->balance_amount;
                                        $txnDetId = 'txnd-' . $payment->id;
                                        $txnRef = $payment->transaction_reference_no ?: $payment->reference_number ?: null;
                                        $acctRef = $payment->accounting_reference_no ?: null;
                                        $remarks = $payment->remarks ?: null;
                                        $receivedBy = $payment->received_by ?: null;
                                        $encodedBy = $payment->encodedBy?->name ?? $payment->recordedBy?->name ?? null;
                                        $senderName = $payment->sender_name ?: null;
                                        $statusAfter = $payment->payment_status_after_payment ?? null;
                                        $recordNo = $payment->display_payment_record_no ?? null;
                                    @endphp
                                    <div class="pm-txn-card">
                                        <div class="pm-txn-hd">
                                            <div class="pm-txn-info">
                                                <span class="pm-txn-rec-label">Payment Record No.</span>
                                                <div class="pm-txn-rec">{{ $recordNo ?? '—' }}</div>
                                                <div class="pm-txn-sub">
                                                    <span>{{ $methodLabel }}</span>
                                                    <span class="pm-dot">&middot;</span>
                                                    <span>{{ $paidAt?->format('M d, Y h:i A') ?? 'Not provided' }}</span>
                                                </div>
                                            </div>
                                            <div class="pm-txn-right">
                                                <div class="pm-txn-amt">PHP {{ number_format((float) $payment->amount, 2) }}</div>
                                                @if($statusAfter)
                                                    <span class="pm-status {{ $statusClass($statusAfter) }}">{{ \Illuminate\Support\Str::headline($statusAfter) }}</span>
                                                @endif
                                                <div class="pm-txn-bal">
                                                    <span class="pm-txn-bal-lbl">{{ $balanceLabel }}</span>
                                                    <span class="pm-txn-bal-val">PHP {{ number_format((float) $balanceValue, 2) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        @if($encodedBy || $receivedBy)
                                            <div class="pm-txn-foot">
                                                @if($encodedBy)<span>Encoded by <strong>{{ $encodedBy }}</strong></span>@endif
                                                @if($encodedBy && $receivedBy)<span class="pm-dot">&middot;</span>@endif
                                                @if($receivedBy)<span>Received by <strong>{{ $receivedBy }}</strong></span>@endif
                                            </div>
                                        @endif
                                        <button type="button" class="pm-txn-tog" data-pm-txn-det="{{ $txnDetId }}" aria-expanded="false">
                                            <i class="bi bi-info-circle"></i> Details <i class="bi bi-chevron-down pm-chev"></i>
                                        </button>
                                        <div id="{{ $txnDetId }}" class="pm-txn-det" hidden>
                                            <div class="pm-txn-det-grid">
                                                <div class="pm-txn-det-cell">
                                                    <span>Payment Record No.</span>
                                                    <strong>{{ $recordNo ?? 'Not provided' }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Accounting Reference No.</span>
                                                    <strong>{{ $acctRef ?: 'Not provided' }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Payment Method</span>
                                                    <strong>{{ $methodLabel }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Payment Amount</span>
                                                    <strong>PHP {{ number_format((float) $payment->amount, 2) }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Payment Date &amp; Time</span>
                                                    <strong>{{ $paidAt?->format('M d, Y h:i A') ?? 'Not provided' }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>{{ $balanceLabel }}</span>
                                                    <strong>PHP {{ number_format((float) $balanceValue, 2) }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Encoded By</span>
                                                    <strong>{{ $encodedBy ?: 'Not provided' }}</strong>
                                                </div>
                                                <div class="pm-txn-det-cell">
                                                    <span>Received By</span>
                                                    <strong>{{ $receivedBy ?: 'Not provided' }}</strong>
                                                </div>
                                                @if($isBank)
                                                    <div class="pm-txn-det-cell">
                                                        <span>Bank / Payment Channel</span>
                                                        <strong>{{ $channel ?: 'Not provided' }}</strong>
                                                    </div>
                                                    <div class="pm-txn-det-cell">
                                                        <span>Transaction Reference No.</span>
                                                        <strong>{{ $txnRef ?: 'Not provided' }}</strong>
                                                    </div>
                                                @endif
                                                @if($senderName)
                                                    <div class="pm-txn-det-cell">
                                                        <span>Sender / Account Name</span>
                                                        <strong>{{ $senderName }}</strong>
                                                    </div>
                                                @endif
                                                @if($remarks)
                                                    <div class="pm-txn-det-cell pm-txn-det-full">
                                                        <span>Remarks</span>
                                                        <strong>{{ $remarks }}</strong>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="pm-empty">No payment transactions match the selected filters for this case.</div>
                                @endforelse
                            </div>
                            <div class="pm-detail-actions">
                                @if($case)
                                    <a class="pm-btn primary" href="{{ $caseRoute($case) }}"><i class="bi bi-eye"></i><span>View Case</span></a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="pm-empty">{{ $emptyMessage }}</div>
                @endforelse
            </div>
            @if($transactionCases->total() > 0)
                <div class="pm-foot">
                    <span class="pm-muted">Showing {{ $transactionCases->firstItem() }}-{{ $transactionCases->lastItem() }} of {{ number_format($transactionCases->total()) }} cases with payment transactions</span>
                    {{ $transactionCases->links() }}
                </div>
            @endif
        </div>
    @endif
</div>

<div id="pmDateModal" class="pm-modal-backdrop" aria-hidden="true">
    <div class="pm-modal" role="dialog" aria-modal="true" aria-labelledby="pmDateModalTitle">
        <div class="pm-modal-hd">
            <div class="pm-modal-title" id="pmDateModalTitle">Custom Payment Date Range</div>
            <button type="button" class="pm-btn compact" data-pm-date-cancel aria-label="Close date range modal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="pm-modal-body">
            <label>
                <span class="pm-txn-rec-label">Date Range From</span>
                <input id="pmModalPaidFrom" class="pm-control" type="date" value="{{ $paidFrom ?? '' }}">
            </label>
            <label>
                <span class="pm-txn-rec-label">Date Range To</span>
                <input id="pmModalPaidTo" class="pm-control" type="date" value="{{ $paidTo ?? '' }}">
            </label>
        </div>
        <div class="pm-modal-ft">
            <button type="button" class="pm-btn" data-pm-date-cancel>Cancel</button>
            <button type="button" class="pm-btn primary" id="pmApplyCustomDates">Apply Date Range</button>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('pmFilterForm');
    const range = document.getElementById('pmDateRange');
    const paidFrom = document.getElementById('pmPaidFrom');
    const paidTo = document.getElementById('pmPaidTo');
    const modal = document.getElementById('pmDateModal');
    const modalFrom = document.getElementById('pmModalPaidFrom');
    const modalTo = document.getElementById('pmModalPaidTo');
    const applyCustomDates = document.getElementById('pmApplyCustomDates');
    const openCase = new URLSearchParams(window.location.search).get('open_case');
    let searchTimer = null;

    const toDateValue = (date) => date.toISOString().slice(0, 10);
    const submitFilters = () => form?.requestSubmit();

    const setDateRange = (preset) => {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        let from = '';
        let to = '';

        if (preset === 'today') {
            from = to = toDateValue(today);
        } else if (preset === 'week') {
            const day = today.getDay();
            const diff = day === 0 ? -6 : 1 - day;
            const start = new Date(today);
            start.setDate(today.getDate() + diff);
            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            from = toDateValue(start);
            to = toDateValue(end);
        } else if (preset === 'month') {
            from = toDateValue(new Date(today.getFullYear(), today.getMonth(), 1));
            to = toDateValue(new Date(today.getFullYear(), today.getMonth() + 1, 0));
        } else if (preset === 'year') {
            from = toDateValue(new Date(today.getFullYear(), 0, 1));
            to = toDateValue(new Date(today.getFullYear(), 11, 31));
        }

        if (paidFrom) paidFrom.value = from;
        if (paidTo) paidTo.value = to;
    };

    const openDateModal = () => {
        if (modalFrom && paidFrom) modalFrom.value = paidFrom.value;
        if (modalTo && paidTo) modalTo.value = paidTo.value;
        modal?.classList.add('open');
        modal?.setAttribute('aria-hidden', 'false');
        modalFrom?.focus();
    };

    const closeDateModal = () => {
        modal?.classList.remove('open');
        modal?.setAttribute('aria-hidden', 'true');
    };

    range?.addEventListener('change', () => {
        if (range.value === 'custom') {
            openDateModal();
            return;
        }

        setDateRange(range.value);
        submitFilters();
    });

    applyCustomDates?.addEventListener('click', () => {
        if (paidFrom && modalFrom) paidFrom.value = modalFrom.value;
        if (paidTo && modalTo) paidTo.value = modalTo.value;
        closeDateModal();
        submitFilters();
    });

    document.querySelectorAll('[data-pm-date-cancel]').forEach(button => {
        button.addEventListener('click', () => {
            closeDateModal();
            if (range && (!paidFrom?.value && !paidTo?.value)) range.value = 'all';
        });
    });

    modal?.addEventListener('click', event => {
        if (event.target === modal) closeDateModal();
    });

    document.querySelectorAll('[data-pm-stop-row-toggle]').forEach(link => {
        link.addEventListener('click', event => event.stopPropagation());
    });

    document.querySelectorAll('[data-pm-summary-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.pmSummaryToggle);
            if (!target) return;
            const nextOpen = !target.classList.contains('open');
            target.classList.toggle('open', nextOpen);
            button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
        });
    });

    document.querySelectorAll('[data-pm-transaction-toggle]').forEach(button => {
        const target = document.getElementById(button.dataset.pmTransactionToggle);
        const toggle = (forceOpen = null) => {
            if (!target) return;
            const nextOpen = forceOpen === null ? !target.classList.contains('open') : forceOpen;
            target.classList.toggle('open', nextOpen);
            button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
        };

        button.addEventListener('click', () => toggle());

        if (openCase && button.dataset.caseCode === openCase) {
            toggle(true);
        }
    });

    document.querySelectorAll('[data-pm-txn-det]').forEach(button => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.pmTxnDet);
            if (!target) return;
            const nextOpen = target.hidden;
            target.hidden = !nextOpen;
            button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
        });
    });

    form?.querySelectorAll('select[name="branch_id"], select[name="payment_status"], select[name="case_status"], select[name="payment_method"]').forEach(control => {
        control.addEventListener('change', submitFilters);
    });

    form?.querySelector('input[name="q"]')?.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(submitFilters, 450);
    });
})();
</script>
@endsection
