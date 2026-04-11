@extends('layouts.panel')

@section('page_title', 'Payment History')
@section('page_desc', 'View all recorded payments and payment status history.')

@section('content')
@php
    $visibleCount = $payments->count();
    $selectedDateRange = request('date_range', 'any');
    $usesCustomDate = $selectedDateRange === 'custom'
        || (!request()->filled('date_range') && (filled($paidFrom ?? null) || filled($paidTo ?? null)));
    if ($usesCustomDate) {
        $selectedDateRange = 'custom';
    }

    $activeFilterCount = collect([
        filled($q ?? null),
        filled($statusAfterPayment ?? null),
        filled($paidFrom ?? null),
        filled($paidTo ?? null),
    ])->filter()->count();

    $statusTabs = [
        '' => 'All',
        'UNPAID' => 'Unpaid',
        'PARTIAL' => 'Partial',
        'PAID' => 'Paid',
    ];
@endphp

<style>
    .payments-history-page {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        min-height: calc(100vh - var(--topbar-h));
        width: 100%;
    }

    .payments-history-card {
        border-color: #d9e2ee;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .payments-history-card .table-system-head {
        padding-top: 14px;
        padding-bottom: 14px;
        background: #ffffff;
        border-bottom: 0;
    }

    .payments-history-focusbar {
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .payments-history-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 30px;
        padding: 0 10px;
        border-radius: 999px;
        background: #ffffff;
        border: 1px solid #d8e0eb;
        color: #475569;
        font-size: 11.5px;
        font-weight: 700;
        line-height: 1;
    }

    .payments-history-chip strong {
        color: var(--ink);
    }

    .payments-history-cell-title {
        color: #334155;
        font-size: 13.5px;
        font-weight: 400;
        line-height: 1.4;
    }

    .payments-history-cell-date {
        color: #475569;
        font-size: 13px;
        font-weight: 400;
        font-variant-numeric: tabular-nums;
    }

    .payments-history-cell-ref {
        color: #1e293b;
        font-size: 13.5px;
        font-weight: 500;
        font-family: var(--font-body);
        letter-spacing: 0.01em;
    }

    .payments-history-cell-name {
        color: #1e293b;
        font-size: 13.5px;
        font-weight: 400;
    }

    .payments-history-cell-muted {
        color: #64748b;
        font-size: 13px;
        font-weight: 400;
    }

    .payments-history-amount {
        color: #1e293b;
        font-weight: 500;
        font-size: 13.5px;
        font-family: var(--font-body);
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.01em;
    }

    .payments-history-card .table-system-wrap {
        border-color: #d3ddea;
        box-shadow: inset 0 0 0 1px rgba(211, 221, 234, 0.25);
        max-height: calc(100vh - 360px);
        overflow: auto;
    }

    .payments-history-card .table-system-list-header {
        display: none;
    }

    .payments-history-card .table-system-table thead th {
        position: sticky;
        top: 0;
        z-index: 6;
        background: #f2f6fb;
        border-bottom-color: #d7e0eb;
        box-shadow: inset 0 -1px 0 #d7e0eb;
        color: #607086;
        font-weight: 600;
        letter-spacing: 0.04em;
    }

    .payments-history-card .table-system-table tbody td {
        padding-top: 10px;
        padding-bottom: 10px;
        vertical-align: middle;
        font-size: 13.5px;
        color: #475569;
    }

    .payments-history-card .table-system-table tbody tr:nth-child(even) td {
        background: #fbfdff;
    }

    .payments-history-card .table-system-table tbody tr:hover td {
        background: #f2f7ff;
    }

    .payments-history-card .table-system-pagination {
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .payments-history-card .table-system-toolbar {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .payments-history-page .table-toolbar {
        grid-template-columns: minmax(240px, 2fr) repeat(3, minmax(150px, 1fr)) auto;
    }

    .payments-history-page .table-toolbar-field {
        gap: 4px;
    }

    .payments-history-page .table-toolbar-label {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: none;
        margin-bottom: 0;
    }

    .payments-history-page .table-toolbar-search,
    .payments-history-page .table-toolbar-select {
        border-color: #cbd5e1;
        background: #ffffff;
    }

    .payments-history-page .table-toolbar-reset-wrap {
        justify-content: flex-end;
    }

    .payments-history-page .table-quick-tabs {
        margin-top: 8px;
        gap: 8px;
    }

    .payments-history-page .table-quick-tab {
        min-height: 30px;
        border-color: #cbd5e1;
        border-radius: 999px;
        background: #ffffff;
        color: #52637a;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 12px;
    }

    .payments-history-page .table-quick-tab:hover {
        background: #f1f5f9;
        border-color: #b9c7d8;
        color: #334155;
    }

    .payments-history-page .table-quick-tab-active {
        background: #eaf0f7;
        border-color: #b6c3d3;
        color: #334155;
    }

    .payments-history-page .btn-filter-reset {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #334155;
    }

    .payments-history-page .btn-filter-reset:hover {
        background: #f1f5f9;
        border-color: #b9c7d8;
        color: #1e293b;
    }

    html[data-theme='dark'] .payments-history-card {
        border-color: #3a4d68;
        box-shadow: none;
        background: #182638;
    }

    html[data-theme='dark'] .payments-history-card .table-system-head {
        background: #13253d;
        border-bottom: 1px solid #334a69;
    }

    html[data-theme='dark'] .payments-history-chip {
        background: #1f334f;
        border-color: #446286;
        color: #cbd9ec;
    }

    html[data-theme='dark'] .payments-history-chip strong {
        color: #f8fbff;
    }

    html[data-theme='dark'] .payments-history-card .table-system-toolbar {
        background: #182638;
        border-top-color: #334a69;
        border-bottom-color: #334a69;
    }

    html[data-theme='dark'] .payments-history-page .table-toolbar-label {
        color: #9fb1c8;
    }

    html[data-theme='dark'] .payments-history-page .table-toolbar-search,
    html[data-theme='dark'] .payments-history-page .table-toolbar-select {
        background: #24364f;
        border-color: #4b678b;
        color: #f8fbff;
    }

    html[data-theme='dark'] .payments-history-page .table-toolbar-search::placeholder {
        color: #9fb1c8;
    }

    html[data-theme='dark'] .payments-history-page .table-quick-tab {
        background: #1f334f;
        border-color: #446286;
        color: #ccdaee;
    }

    html[data-theme='dark'] .payments-history-page .table-quick-tab:hover {
        background: #29405f;
        border-color: #5a789f;
        color: #ffffff;
    }

    html[data-theme='dark'] .payments-history-page .table-quick-tab-active {
        background: #f4f7fb;
        border-color: #d7e0eb;
        color: #1b2737;
    }

    html[data-theme='dark'] .payments-history-page .btn-filter-reset {
        background: #1f334f;
        border-color: #446286;
        color: #dbe7f6;
    }

    html[data-theme='dark'] .payments-history-page .btn-filter-reset:hover {
        background: #2a4465;
        border-color: #5a789f;
        color: #ffffff;
    }

    html[data-theme='dark'] .payments-history-card .table-system-wrap {
        border-color: #3a4d68;
        box-shadow: none;
        background: #182638;
    }

    html[data-theme='dark'] .payments-history-card .table-system-table thead th {
        background: #22364f;
        border-bottom-color: #3a4d68;
        box-shadow: inset 0 -1px 0 #3a4d68;
        color: #a9bbd2;
    }

    html[data-theme='dark'] .payments-history-card .table-system-table tbody td {
        color: #d5e0f0;
    }

    html[data-theme='dark'] .payments-history-cell-title,
    html[data-theme='dark'] .payments-history-cell-date,
    html[data-theme='dark'] .payments-history-cell-ref,
    html[data-theme='dark'] .payments-history-cell-name,
    html[data-theme='dark'] .payments-history-cell-muted,
    html[data-theme='dark'] .payments-history-amount {
        color: #d5e0f0;
    }

    html[data-theme='dark'] .payments-history-card .table-system-table tbody tr:nth-child(even) td {
        background: #1d2d44;
    }

    html[data-theme='dark'] .payments-history-card .table-system-table tbody tr:hover td {
        background: #24364f;
    }

    @media (max-width: 1200px) {
        .payments-history-page .table-toolbar {
            grid-template-columns: repeat(2, minmax(220px, 1fr));
        }
    }

    @media (max-width: 680px) {
        .payments-history-page .table-toolbar {
            grid-template-columns: 1fr;
        }

        .payments-history-card .table-system-wrap {
            max-height: none;
        }
    }
</style>

<div class="payments-history-page">
@if($errors->any())
    <div class="flash-error">
        {{ $errors->first() }}
    </div>
@endif

@if(session('success'))
    <div class="flash-success">
        {{ session('success') }}
    </div>
@endif

<section class="table-system-card payments-history-card">
    <div class="table-system-head">
        <div class="payments-history-focusbar">
            <span class="payments-history-chip">
                <i class="bi bi-table"></i>
                <strong>{{ number_format($visibleCount) }}</strong> visible on page
            </span>
            <span class="payments-history-chip">
                <i class="bi bi-collection"></i>
                <strong>{{ number_format($payments->total()) }}</strong> total records
            </span>
            <span class="payments-history-chip">
                <i class="bi bi-funnel"></i>
                <strong>{{ $activeFilterCount }}</strong> active {{ \Illuminate\Support\Str::plural('filter', $activeFilterCount) }}
            </span>
        </div>
    </div>

    <div class="table-system-toolbar">
        <form method="GET" action="{{ route('payments.history') }}" class="table-toolbar" data-table-toolbar data-search-debounce="400">
            @if(filled($statusAfterPayment ?? null))
                <input type="hidden" name="status_after_payment" value="{{ $statusAfterPayment }}">
            @endif

            <div class="table-toolbar-field">
                <label for="payment-history-search" class="table-toolbar-label">Search</label>
                <input
                    id="payment-history-search"
                    name="q"
                    value="{{ $q }}"
                    class="form-input table-toolbar-search"
                    data-table-search
                    placeholder="Search case, client, or deceased...">
            </div>

            <div class="table-toolbar-field">
                <label for="payment-date-range" class="table-toolbar-label">Date Range</label>
                <select id="payment-date-range" name="date_range" class="form-select table-toolbar-select">
                    <option value="any" @selected($selectedDateRange === 'any')>Any Time</option>
                    <option value="today" @selected($selectedDateRange === 'today')>Today</option>
                    <option value="7d" @selected($selectedDateRange === '7d')>Last 7 Days</option>
                    <option value="30d" @selected($selectedDateRange === '30d')>Last 30 Days</option>
                    <option value="this_month" @selected($selectedDateRange === 'this_month')>This Month</option>
                    <option value="custom" @selected($selectedDateRange === 'custom')>Custom Range</option>
                </select>
            </div>

            <div class="table-toolbar-field" data-custom-date-field @if(!$usesCustomDate) hidden @endif>
                <label for="paid-from" class="table-toolbar-label">Paid From</label>
                <input
                    id="paid-from"
                    type="date"
                    name="paid_from"
                    value="{{ $paidFrom ?? '' }}"
                    class="form-input table-toolbar-select"
                    data-custom-date-input
                    @if(!$usesCustomDate) disabled @endif
                >
            </div>

            <div class="table-toolbar-field" data-custom-date-field @if(!$usesCustomDate) hidden @endif>
                <label for="paid-to" class="table-toolbar-label">Paid To</label>
                <input
                    id="paid-to"
                    type="date"
                    name="paid_to"
                    value="{{ $paidTo ?? '' }}"
                    class="form-input table-toolbar-select"
                    data-custom-date-input
                    @if(!$usesCustomDate) disabled @endif
                >
            </div>

            <div class="table-toolbar-reset-wrap">
                <span class="table-toolbar-label opacity-0 select-none">Actions</span>
                <div class="filter-actions">
                    <a href="{{ route('payments.history') }}" class="btn-outline btn-filter-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>
        </form>

        <div class="table-quick-tabs" style="margin: 20px 0 15px 0;">
            @foreach($statusTabs as $value => $label)
                @php
                    $tabQuery = request()->except('page');
                    $tabQuery['status_after_payment'] = $value;
                    if ($value === '') {
                        unset($tabQuery['status_after_payment']);
                    }
                @endphp
                <a
                    href="{{ route('payments.history', $tabQuery) }}"
                    class="table-quick-tab {{ ($statusAfterPayment ?? '') === $value ? 'table-quick-tab-active' : '' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="table-system-list">
        <div class="table-wrapper table-system-wrap">
            <table class="table-base table-system-table">
            <thead>
                <tr>
                    <th class="text-left">Payment Date</th>
                    <th class="text-left">Receipt / Reference No.</th>
                    <th class="text-left">Case ID</th>
                    <th class="text-left">Client / Payer</th>
                    <th class="text-left">Deceased</th>
                    <th class="table-col-number">Amount Paid</th>
                    <th class="table-col-number">Remaining Balance</th>
                    <th class="text-left">Payment Status</th>
                    <th class="text-left">Branch</th>
                    <th class="text-left">Recorded By</th>
                    <th class="table-col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td>
                        <div class="payments-history-cell-title payments-history-cell-date">
                            {{ $payment->paid_at?->format('Y-m-d H:i') ?? $payment->paid_date?->format('Y-m-d') ?? '-' }}
                        </div>
                    </td>
                    <td>
                        <div class="payments-history-cell-title payments-history-cell-ref">{{ $payment->receipt_number ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="payments-history-cell-title payments-history-cell-ref">{{ $payment->funeralCase?->case_code ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="payments-history-cell-title payments-history-cell-name">{{ $payment->funeralCase?->client?->full_name ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="payments-history-cell-title payments-history-cell-name">{{ $payment->funeralCase?->deceased?->full_name ?? '-' }}</div>
                    </td>
                    <td class="table-col-number">
                        <div class="payments-history-amount">PHP {{ number_format((float) $payment->amount, 2) }}</div>
                    </td>
                    <td class="table-col-number">
                        <div class="payments-history-amount">PHP {{ number_format((float) ($payment->balance_after_payment ?? 0), 2) }}</div>
                    </td>
                    <td>
                        <x-status-badge :status="$payment->payment_status_after_payment" />
                    </td>
                    <td><div class="payments-history-cell-muted">{{ $payment->funeralCase?->branch?->branch_code ?? '-' }}</div></td>
                    <td><div class="payments-history-cell-muted">{{ $payment->recordedBy?->name ?? '-' }}</div></td>
                    <td class="table-col-actions">
                        <div class="table-row-actions">
                            @if($payment->funeralCase)
                                <div class="row-action-menu" data-row-menu>
                                    <button
                                        type="button"
                                        class="row-action-trigger"
                                        data-row-menu-trigger
                                        aria-label="Open row actions"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                    >
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <div class="row-action-dropdown" role="menu">
                                        <a
                                            href="{{ route('funeral-cases.show', ['funeral_case' => $payment->funeralCase, 'return_to' => request()->fullUrl()]) }}"
                                            class="row-action-item"
                                            data-row-menu-item
                                        >
                                            <i class="bi bi-eye"></i>
                                            <span>View Case</span>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <span class="table-secondary">-</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="table-system-empty">No payment history found.</td>
                </tr>
            @endforelse
            </tbody>
            </table>
        </div>
    </div>

    <div class="table-system-pagination">
        {{ $payments->links() }}
    </div>
</section>

</div>
<script>
    (function () {
        const filterForm = document.querySelector('form[action="{{ route('payments.history') }}"]');
        if (!filterForm) {
            return;
        }

        const dateRangeSelect = filterForm.querySelector('select[name="date_range"]');
        const searchInput = filterForm.querySelector('[data-table-search]');
        const customDateFields = filterForm.querySelectorAll('[data-custom-date-field]');
        const customDateInputs = filterForm.querySelectorAll('[data-custom-date-input]');
        const paidFromInput = filterForm.querySelector('input[name="paid_from"]');
        const paidToInput = filterForm.querySelector('input[name="paid_to"]');
        const searchDebounce = Number(filterForm.dataset.searchDebounce || 400);
        const customDateDebounce = 800;
        let searchTimer = null;
        let customDateTimer = null;

        const formatLocalDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const toStartOfDay = (date) => {
            const clone = new Date(date);
            clone.setHours(0, 0, 0, 0);
            return clone;
        };

        const getPresetRange = (preset) => {
            const today = toStartOfDay(new Date());

            if (preset === 'today') {
                return { from: formatLocalDate(today), to: formatLocalDate(today) };
            }

            if (preset === '7d') {
                const from = new Date(today);
                from.setDate(from.getDate() - 6);
                return { from: formatLocalDate(from), to: formatLocalDate(today) };
            }

            if (preset === '30d') {
                const from = new Date(today);
                from.setDate(from.getDate() - 29);
                return { from: formatLocalDate(from), to: formatLocalDate(today) };
            }

            if (preset === 'this_month') {
                const from = new Date(today.getFullYear(), today.getMonth(), 1);
                return { from: formatLocalDate(from), to: formatLocalDate(today) };
            }

            return { from: '', to: '' };
        };

        const updateCustomDateVisibility = () => {
            const isCustom = dateRangeSelect && dateRangeSelect.value === 'custom';

            customDateFields.forEach((field) => {
                field.hidden = !isCustom;
            });

            customDateInputs.forEach((input) => {
                input.disabled = !isCustom;
            });
        };

        const isCompleteDate = (value) => value === '' || /^\d{4}-\d{2}-\d{2}$/.test(value);

        const canSubmitCustomRange = () => {
            if (!dateRangeSelect || dateRangeSelect.value !== 'custom') {
                return false;
            }

            const from = (paidFromInput?.value || '').trim();
            const to = (paidToInput?.value || '').trim();

            if (!isCompleteDate(from) || !isCompleteDate(to)) {
                return false;
            }

            if (from === '' && to === '') {
                return false;
            }

            return true;
        };

        const submitFilters = () => {
            if (!dateRangeSelect || !paidFromInput || !paidToInput) {
                if (typeof filterForm.requestSubmit === 'function') {
                    filterForm.requestSubmit();
                    return;
                }
                filterForm.submit();
                return;
            }

            const selectedPreset = dateRangeSelect.value;
            if (selectedPreset === 'custom') {
                paidFromInput.disabled = false;
                paidToInput.disabled = false;
            } else {
                const { from, to } = getPresetRange(selectedPreset);
                paidFromInput.disabled = false;
                paidToInput.disabled = false;
                paidFromInput.value = from;
                paidToInput.value = to;
            }

            if (typeof filterForm.requestSubmit === 'function') {
                filterForm.requestSubmit();
                return;
            }
            filterForm.submit();
        };

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                if (searchTimer) {
                    clearTimeout(searchTimer);
                }
                searchTimer = setTimeout(() => {
                    submitFilters();
                }, searchDebounce);
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (searchTimer) {
                        clearTimeout(searchTimer);
                    }
                    submitFilters();
                }
            });
        }

        const queueCustomDateSubmit = () => {
            if (customDateTimer) {
                clearTimeout(customDateTimer);
            }

            customDateTimer = setTimeout(() => {
                if (canSubmitCustomRange()) {
                    submitFilters();
                }
            }, customDateDebounce);
        };

        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', () => {
                updateCustomDateVisibility();
                if (dateRangeSelect.value !== 'custom') {
                    submitFilters();
                }
            });
        }

        customDateInputs.forEach((input) => {
            input.addEventListener('change', queueCustomDateSubmit);
        });

        filterForm.addEventListener('submit', () => {
            if (searchTimer) {
                clearTimeout(searchTimer);
            }
            if (customDateTimer) {
                clearTimeout(customDateTimer);
            }
        });

        updateCustomDateVisibility();
    })();
</script>
@endsection
