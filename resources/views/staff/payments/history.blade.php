@extends('layouts.panel')

@section('page_title', 'Payment History')
@section('page_desc', 'All recorded payments and status history across cases.')

@section('content')
@php
    $statusTabs = ['' => 'All', 'PAID' => 'Paid', 'PARTIAL' => 'Partial', 'UNPAID' => 'Unpaid'];
@endphp

<style>
.ph * { box-sizing: border-box; }

.ph {
    color: #0f172a;
    width: 100%;
    padding: 1.5rem var(--panel-content-inline, 1.5rem) 3rem;
}

/* KPI Strip */
.ph-kpi {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.ph-k { padding: 16px 18px; border-right: 0.5px solid #e2e8f0; }
.ph-k:last-child { border-right: none; }
.ph-kl {
    font-size: 11px; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 5px;
}
.ph-kv { font-size: 20px; font-weight: 500; font-variant-numeric: tabular-nums; }
.ph-kv.green { color: #16a34a; }
.ph-kv.amber { color: #b45309; }
.ph-kv.red   { color: #b91c1c; }

/* Controls */
.ph-bar { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1rem; }
.ph-search { position: relative; flex: 1; min-width: 200px; }
.ph-search-icon {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; pointer-events: none; width: 13px; height: 13px;
}
.ph-search input {
    width: 100%; padding: 8px 12px 8px 32px;
    border: 0.5px solid #cbd5e1; border-radius: 8px;
    background: #fff; font-size: 13px; font-family: inherit; color: #0f172a;
}
.ph-search input:focus { outline: none; border-color: #94a3b8; }
.ph-search input::placeholder { color: #94a3b8; }
.ph-select {
    padding: 8px 12px; border: 0.5px solid #cbd5e1;
    border-radius: 8px; background: #fff; font-size: 13px;
    font-family: inherit; color: #0f172a; height: 36px;
}
.ph-btn-row { display: flex; gap: 6px; }
.ph-btn {
    padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 500;
    border: 0.5px solid #e2e8f0; background: #fff; cursor: pointer;
    font-family: inherit; text-decoration: none; color: #0f172a; white-space: nowrap;
    display: inline-flex; align-items: center; height: 36px;
}
.ph-btn:hover { background: #f1f5f9; }

/* Status Tabs */
.ph-tabs { display: flex; gap: 2px; background: #f1f5f9; border-radius: 8px; padding: 2px; margin-bottom: 1rem; width: fit-content; }
.ph-tab {
    padding: 5px 14px; border-radius: 6px; font-size: 12px;
    font-weight: 500; background: none; border: none;
    color: #64748b; font-family: inherit; text-decoration: none; cursor: pointer; white-space: nowrap;
}
.ph-tab.active { background: #fff; color: #0f172a; box-shadow: 0 0 0 0.5px #cbd5e1; }

/* Column Labels */
.ph-col {
    display: grid;
    grid-template-columns: 10px 1fr 130px 130px 90px 26px;
    gap: 12px; padding: 0 14px 8px;
    font-size: 11px; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.05em;
}

/* Transaction Card */
.ph-card {
    display: grid;
    grid-template-columns: 10px 1fr 130px 130px 90px 26px;
    gap: 12px; align-items: center;
    padding: 13px 14px;
    border: 0.5px solid #e2e8f0; border-radius: 10px;
    margin-bottom: 6px; cursor: pointer;
    background: #fff; transition: border-color 0.12s; text-decoration: none; color: inherit;
    display: grid;
}
.ph-card:hover { border-color: #94a3b8; }
.ph-card.open  {
    border-color: #64748b;
    border-bottom-left-radius: 0; border-bottom-right-radius: 0;
    margin-bottom: 0;
}

.ph-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.ph-dot.paid    { background: #22c55e; }
.ph-dot.partial { background: #f59e0b; }
.ph-dot.unpaid  { background: #ef4444; }

.ph-name { font-size: 14px; font-weight: 500; margin-bottom: 3px; line-height: 1.3; }
.ph-name .dim { color: #94a3b8; font-weight: 400; }
.ph-meta { font-size: 12px; color: #94a3b8; display: flex; gap: 10px; flex-wrap: wrap; }
.ph-meta .case-ref { color: var(--accent); }

.ph-amt { font-size: 14px; font-weight: 500; text-align: right; font-variant-numeric: tabular-nums; }
.ph-bal { font-size: 13px; text-align: right; color: #94a3b8; }
.ph-bal.owed { color: #b45309; }

.ph-badge { display: inline-block; padding: 3px 9px; border-radius: 4px; font-size: 11px; font-weight: 500; }
.ph-badge.paid    { background: #f0fdf4; color: #15803d; }
.ph-badge.partial { background: #fffbeb; color: #a16207; }
.ph-badge.unpaid  { background: #fef2f2; color: #b91c1c; }

.ph-chev { font-size: 13px; color: #94a3b8; transition: transform 0.15s; text-align: center; user-select: none; }
.ph-card.open .ph-chev { transform: rotate(90deg); }

/* Expanded Detail */
.ph-detail {
    border: 0.5px solid #64748b; border-top: none;
    border-radius: 0 0 10px 10px;
    background: #f8fafc;
    padding: 14px 14px 14px 36px;
    margin-bottom: 6px;
}
.ph-prog { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.ph-pbar { flex: 1; height: 3px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
.ph-pfill { height: 100%; border-radius: 999px; }
.ph-pfill.paid    { background: #22c55e; }
.ph-pfill.partial { background: #f59e0b; }
.ph-pfill.unpaid  { background: #ef4444; }
.ph-pct { font-size: 11px; color: #94a3b8; white-space: nowrap; }

.ph-dg { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px; }
.ph-di { padding: 10px 12px; background: #fff; border-radius: 8px; border: 0.5px solid #e2e8f0; }
.ph-dl { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px; }
.ph-dv { font-size: 13px; font-weight: 500; word-break: break-word; }
.ph-dv.case-c  { color: var(--accent); }
.ph-dv.paid-c  { color: #15803d; }
.ph-dv.owed-c  { color: #b45309; }

.ph-acts { display: flex; gap: 6px; flex-wrap: wrap; }
.ph-act {
    padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 500;
    border: 0.5px solid #e2e8f0; background: #fff; cursor: pointer;
    font-family: inherit; text-decoration: none; color: #0f172a;
    display: inline-flex; align-items: center; gap: 5px;
}
.ph-act:hover { background: #f1f5f9; }
.ph-act.dark { background: #0f172a; color: #fff; border-color: #0f172a; }
.ph-act.dark:hover { background: #1e293b; }

/* Footer */
.ph-foot {
    display: flex; justify-content: space-between; align-items: center;
    padding-top: 1rem; margin-top: .75rem; border-top: 0.5px solid #e2e8f0;
    flex-wrap: wrap; gap: 8px;
}
.ph-fl { font-size: 12px; color: #94a3b8; }

/* Responsive */
@media (max-width: 900px) {
    .ph-kpi { grid-template-columns: 1fr 1fr; }
    .ph-k:nth-child(1), .ph-k:nth-child(2) { border-bottom: 0.5px solid #e2e8f0; }
    .ph-k:nth-child(2) { border-right: none; }
}
@media (max-width: 680px) {
    .ph-col { display: none; }
    .ph-card { grid-template-columns: 10px 1fr auto 26px; }
    .ph-card .ph-bal, .ph-card .ph-badge { display: none; }
    .ph-dg { grid-template-columns: 1fr 1fr; }
    .ph-kpi { grid-template-columns: 1fr 1fr; }
    .ph-k:nth-child(1), .ph-k:nth-child(2) { border-bottom: 0.5px solid #e2e8f0; }
    .ph-k:nth-child(2) { border-right: none; }
}

/* Dark mode */
html[data-theme='dark'] .ph-kpi { border-color: #334a69; }
html[data-theme='dark'] .ph-k { border-color: #334a69; }
html[data-theme='dark'] .ph-kl { color: #7a94b4; }
html[data-theme='dark'] .ph-kv { color: #e2ecf9; }
html[data-theme='dark'] .ph-kv.green { color: #4ade80; }
html[data-theme='dark'] .ph-kv.amber { color: #fbbf24; }
html[data-theme='dark'] .ph-kv.red   { color: #f87171; }
html[data-theme='dark'] .ph-search input,
html[data-theme='dark'] .ph-select {
    background: #1e334f; border-color: #4a6888; color: #e2ecf9;
}
html[data-theme='dark'] .ph-search input::placeholder { color: #5a7a9f; }
html[data-theme='dark'] .ph-tabs { background: #192d47; }
html[data-theme='dark'] .ph-tab { color: #8aa7c5; }
html[data-theme='dark'] .ph-tab.active { background: #243d5a; color: #e2ecf9; box-shadow: 0 0 0 0.5px #4a6888; }
html[data-theme='dark'] .ph-card {
    background: #182638; border-color: #2e4560; color: #d5e0f0;
}
html[data-theme='dark'] .ph-card:hover { border-color: #5a7898; }
html[data-theme='dark'] .ph-card.open { border-color: #7a9ab8; }
html[data-theme='dark'] .ph-name .dim { color: #5a7898; }
html[data-theme='dark'] .ph-meta { color: #5a7898; }
html[data-theme='dark'] .ph-meta .case-ref { color: #c88a4a; }
html[data-theme='dark'] .ph-amt { color: #d5e0f0; }
html[data-theme='dark'] .ph-bal { color: #5a7898; }
html[data-theme='dark'] .ph-bal.owed { color: #fbbf24; }
html[data-theme='dark'] .ph-chev { color: #5a7898; }
html[data-theme='dark'] .ph-detail { background: #182638; border-color: #7a9ab8; }
html[data-theme='dark'] .ph-pbar { background: #2e4560; }
html[data-theme='dark'] .ph-pct { color: #5a7898; }
html[data-theme='dark'] .ph-di { background: #1e334f; border-color: #2e4560; }
html[data-theme='dark'] .ph-dl { color: #5a7898; }
html[data-theme='dark'] .ph-dv { color: #d5e0f0; }
html[data-theme='dark'] .ph-dv.case-c { color: #c88a4a; }
html[data-theme='dark'] .ph-dv.paid-c { color: #4ade80; }
html[data-theme='dark'] .ph-dv.owed-c { color: #fbbf24; }
html[data-theme='dark'] .ph-act { background: #1e334f; border-color: #2e4560; color: #d5e0f0; }
html[data-theme='dark'] .ph-act:hover { background: #243d5a; }
html[data-theme='dark'] .ph-act.dark { background: #e2ecf9; color: #0f172a; border-color: #e2ecf9; }
html[data-theme='dark'] .ph-act.dark:hover { background: #cbd5e1; }
html[data-theme='dark'] .ph-foot { border-color: #2e4560; }
html[data-theme='dark'] .ph-fl { color: #5a7898; }
html[data-theme='dark'] .ph-col { color: #5a7898; }
html[data-theme='dark'] .ph-btn {
    background: #1e334f; border-color: #2e4560; color: #d5e0f0;
}
html[data-theme='dark'] .ph-btn:hover { background: #243d5a; }
</style>

<div class="ph">

@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="flash-error">{{ $errors->first() }}</div>
@endif

{{-- Controls --}}
<form method="GET" action="{{ route('payments.history') }}" id="phFilterForm">
    @if(filled($statusAfterPayment))
        <input type="hidden" name="status_after_payment" value="{{ $statusAfterPayment }}">
    @endif
    <input type="hidden" name="paid_from" id="phHiddenFrom" value="{{ $paidFrom ?? '' }}">
    <input type="hidden" name="paid_to"   id="phHiddenTo"   value="{{ $paidTo ?? '' }}">

    <div class="ph-bar">
        <div class="ph-search">
            <svg class="ph-search-icon" viewBox="0 0 13 13" fill="none">
                <circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/>
                <path d="M9 9l2.5 2.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
            <input type="text" name="q" value="{{ $q ?? '' }}"
                   placeholder="Search client, deceased, or case ID..."
                   autocomplete="off" id="phSearch">
        </div>

        <select id="phRange" class="ph-select" title="Date range">
            <option value="">Any time</option>
            <option value="today" {{ (!$paidFrom && !$paidTo) ? '' : '' }}>Today</option>
            <option value="week">This week</option>
            <option value="month">This month</option>
            <option value="custom">Custom range</option>
        </select>

        <div id="phCustomDates" style="display:none; gap:6px; align-items:flex-end;" class="ph-btn-row">
            <input type="date" id="phFromInput" class="ph-select" value="{{ $paidFrom ?? '' }}" title="From date">
            <input type="date" id="phToInput"   class="ph-select" value="{{ $paidTo ?? '' }}"   title="To date">
        </div>

        <select name="sort" class="ph-select" title="Sort order" onchange="this.form.submit()">
            <option value="desc" {{ ($sort ?? 'desc') === 'desc' ? 'selected' : '' }}>Newest first</option>
            <option value="asc"  {{ ($sort ?? 'desc') === 'asc'  ? 'selected' : '' }}>Oldest first</option>
        </select>

        <div class="ph-btn-row">
            <button type="submit" class="ph-btn">Search</button>
            <a href="{{ route('payments.history') }}" class="ph-btn">Reset</a>
        </div>
    </div>
</form>

{{-- Status tabs --}}
<div class="ph-tabs">
    @foreach($statusTabs as $val => $label)
        @php
            $tabQuery = array_filter(
                array_merge(request()->except(['status_after_payment', 'page']), $val !== '' ? ['status_after_payment' => $val] : []),
                fn($v) => $v !== null && $v !== ''
            );
        @endphp
        <a href="{{ route('payments.history', $tabQuery) }}"
           class="ph-tab {{ ($statusAfterPayment ?? '') === $val ? 'active' : '' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

{{-- Column headers --}}
<div class="ph-col">
    <div></div>
    <div>Client - Deceased</div>
    <div style="text-align:right">Amount Paid</div>
    <div style="text-align:right">Balance</div>
    <div>Status</div>
    <div></div>
</div>

{{-- Cards --}}
@forelse($payments as $payment)
    @php
        $st      = strtolower($payment->payment_status_after_payment ?? 'unpaid');
        $total   = (float) ($payment->funeralCase->total_amount ?? 0);
        $paid    = (float) ($payment->amount ?? 0);
        $pct     = $total > 0 ? (int) round($paid / $total * 100) : 0;
        $balance = (float) ($payment->balance_after_payment ?? 0);
    @endphp

    <div class="ph-card {{ $st }}" data-ph-toggle="ph-detail-{{ $payment->id }}" role="button" tabindex="0">

        <div class="ph-dot {{ $st }}"></div>

        <div>
            <div class="ph-name">
                {{ $payment->funeralCase?->client?->full_name ?? '-' }}
                <span class="dim"> - {{ $payment->funeralCase?->deceased?->full_name ?? '-' }}</span>
            </div>
            <div class="ph-meta">
                <span>{{ $payment->paid_at?->format('Y-m-d H:i') ?? '-' }}</span>
                <span>{{ $payment->receipt_number ?? '-' }}</span>
                <span class="case-ref">{{ $payment->funeralCase?->case_code ?? '-' }}</span>
                <span>{{ $payment->funeralCase?->branch?->branch_code ?? '-' }}</span>
            </div>
        </div>

        <div class="ph-amt">PHP {{ number_format($paid, 2) }}</div>

        <div class="ph-bal {{ $balance > 0 ? 'owed' : '' }}">
            {{ $balance > 0 ? 'PHP ' . number_format($balance, 2) : '-' }}
        </div>

        <div><span class="ph-badge {{ $st }}">{{ ucfirst($st) }}</span></div>

        <div class="ph-chev">></div>
    </div>

    <div class="ph-detail" id="ph-detail-{{ $payment->id }}" style="display:none">
        <div class="ph-prog">
            <div class="ph-pbar">
                <div class="ph-pfill {{ $st }}" style="width:{{ $pct }}%"></div>
            </div>
            <span class="ph-pct">
                {{ $pct }}% collected -
                PHP {{ number_format($paid, 2) }} of PHP {{ number_format($total, 2) }}
            </span>
        </div>

        <div class="ph-dg">
            <div class="ph-di">
                <div class="ph-dl">Receipt No.</div>
                <div class="ph-dv">{{ $payment->receipt_number ?? '-' }}</div>
            </div>
            <div class="ph-di">
                <div class="ph-dl">Case ID</div>
                <div class="ph-dv case-c">{{ $payment->funeralCase?->case_code ?? '-' }}</div>
            </div>
            <div class="ph-di">
                <div class="ph-dl">Branch</div>
                <div class="ph-dv">{{ $payment->funeralCase?->branch?->branch_code ?? '-' }}</div>
            </div>
            <div class="ph-di">
                <div class="ph-dl">Recorded By</div>
                <div class="ph-dv">{{ $payment->recordedBy?->name ?? '-' }}</div>
            </div>
            <div class="ph-di">
                <div class="ph-dl">Amount Paid</div>
                <div class="ph-dv paid-c">PHP {{ number_format($paid, 2) }}</div>
            </div>
            <div class="ph-di">
                <div class="ph-dl">Remaining Balance</div>
                <div class="ph-dv {{ $balance > 0 ? 'owed-c' : '' }}">
                    {{ $balance > 0 ? 'PHP ' . number_format($balance, 2) : 'Settled' }}
                </div>
            </div>
            <div class="ph-di">
                <div class="ph-dl">Total Amount</div>
                <div class="ph-dv">PHP {{ number_format($total, 2) }}</div>
            </div>
            <div class="ph-di">
                <div class="ph-dl">Payment Date</div>
                <div class="ph-dv">{{ $payment->paid_at?->format('M d, Y - h:i A') ?? '-' }}</div>
            </div>
        </div>

        <div class="ph-acts">
            @if($payment->funeralCase)
                <a href="{{ route('funeral-cases.show', ['funeral_case' => $payment->funeralCase, 'return_to' => request()->fullUrl()]) }}"
                   class="ph-act dark">
                    <i class="bi bi-eye"></i>
                    View case
                </a>
                @if($balance > 0)
                    <a href="{{ route('payments.index', ['case_id' => $payment->funeral_case_id, 'open_payment' => 1]) }}"
                       class="ph-act">
                        <i class="bi bi-cash-stack"></i>
                        Add payment
                    </a>
                @endif
            @endif
        </div>
    </div>

@empty
    <div style="text-align:center; padding:3rem; color:#94a3b8; font-size:13px;">
        No transactions match your filters.
    </div>
@endforelse

{{-- Footer --}}
@if($payments->total() > 0)
<div class="ph-foot">
    <span class="ph-fl">
        Showing {{ $payments->firstItem() }}-{{ $payments->lastItem() }}
        of {{ number_format($payments->total()) }} records
    </span>
    <div>
        {{ $payments->links() }}
    </div>
</div>
@endif

</div>

<script>
(function () {
    const form       = document.getElementById('phFilterForm');
    const rangeSelect = document.getElementById('phRange');
    const customDates = document.getElementById('phCustomDates');
    const fromInput  = document.getElementById('phFromInput');
    const toInput    = document.getElementById('phToInput');
    const hiddenFrom = document.getElementById('phHiddenFrom');
    const hiddenTo   = document.getElementById('phHiddenTo');
    const searchInput = document.getElementById('phSearch');
    if (!form || !rangeSelect) return;

    const fmt = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const dy = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${dy}`;
    };

    const presets = {
        today: () => {
            const t = fmt(new Date());
            return { from: t, to: t };
        },
        week: () => {
            const now = new Date();
            const mon = new Date(now);
            mon.setDate(now.getDate() - ((now.getDay() + 6) % 7));
            return { from: fmt(mon), to: fmt(now) };
        },
        month: () => {
            const now = new Date();
            const first = new Date(now.getFullYear(), now.getMonth(), 1);
            return { from: fmt(first), to: fmt(now) };
        },
    };

    // Detect current state from hidden inputs
    const initFrom = hiddenFrom.value;
    const initTo   = hiddenTo.value;
    if (initFrom || initTo) {
        // Try to match a preset, else fall back to custom
        let matched = false;
        for (const [key, fn] of Object.entries(presets)) {
            const r = fn();
            if (r.from === initFrom && r.to === initTo) {
                rangeSelect.value = key;
                matched = true;
                break;
            }
        }
        if (!matched && (initFrom || initTo)) {
            rangeSelect.value = 'custom';
            customDates.style.display = 'flex';
            fromInput.value = initFrom;
            toInput.value   = initTo;
        }
    }

    rangeSelect.addEventListener('change', () => {
        const val = rangeSelect.value;
        if (val === 'custom') {
            customDates.style.display = 'flex';
        } else {
            customDates.style.display = 'none';
            if (val === '') {
                hiddenFrom.value = '';
                hiddenTo.value   = '';
            } else {
                const r = presets[val]?.();
                if (r) {
                    hiddenFrom.value = r.from;
                    hiddenTo.value   = r.to;
                }
            }
            form.submit();
        }
    });

    if (fromInput) {
        fromInput.addEventListener('change', () => {
            hiddenFrom.value = fromInput.value;
            if (fromInput.value && toInput.value) form.submit();
        });
    }
    if (toInput) {
        toInput.addEventListener('change', () => {
            hiddenTo.value = toInput.value;
            if (fromInput.value && toInput.value) form.submit();
        });
    }

    // Card expand/collapse
    document.querySelectorAll('[data-ph-toggle]').forEach(card => {
        const toggle = () => {
            const detail = document.getElementById(card.dataset.phToggle);
            if (!detail) return;
            const isOpen = detail.style.display !== 'none';
            detail.style.display = isOpen ? 'none' : 'block';
            card.classList.toggle('open', !isOpen);
        };
        card.addEventListener('click', toggle);
        card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
    });

    // Debounced search
    let searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => form.submit(), 400);
        });
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); clearTimeout(searchTimer); form.submit(); }
        });
    }
})();
</script>
@endsection

