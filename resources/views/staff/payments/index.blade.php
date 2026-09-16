@extends('layouts.panel')

@section('hide_layout_topbar', '1')
@section('page_title', 'Record Payment')
@section('page_desc', '')

@section('content')
<style>
    .payments-page {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        min-height: 100%;
        height: 100%;
        width: 100%;
        box-sizing: border-box;
        padding: 12px var(--panel-content-inline) 20px;
        color: var(--ink);
    }

    .payments-unified-card {
        background: #D3DEC9;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: none;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        width: 100%;
    }

    .payments-unified-card.is-updating .list-card {
        opacity: .58;
        pointer-events: none;
        transition: opacity .16s ease;
    }

    .payments-unified-card .filter-panel,
    .payments-unified-card .list-card {
        border: 0;
        border-radius: 0;
        box-shadow: none;
        background: transparent;
        margin: 0 !important;
        width: 100%;
    }

    .payments-unified-card .filter-panel {
        padding: 20px var(--panel-content-inline);
    }

    .payments-filter-shell {
        padding: 12px var(--panel-content-inline);
        background: #D3DEC9;
    }

    .payments-filter-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    .payments-filter-toolbar .table-toolbar-field {
        gap: 0;
        min-width: 10rem;
    }

    .payments-filter-toolbar .payments-filter-search {
        flex: 0 1 34rem;
        min-width: min(100%, 18rem);
        max-width: 34rem;
    }

    .payments-filter-toolbar .payments-filter-date {
        flex: 0 0 14.5rem;
        min-width: 14.5rem;
    }

    .payments-filter-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .payments-filter-control {
        position: relative;
        width: 100%;
    }

    .payments-filter-control > i {
        position: absolute;
        left: 0.9rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--ink-muted);
        font-size: 0.92rem;
        pointer-events: none;
        z-index: 1;
    }

    .payments-filter-control .table-toolbar-search,
    .payments-filter-control .table-toolbar-select {
        padding-left: 2.45rem;
    }

    .payments-filter-control.has-dropdown #payment-date-range {
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        background-image: none !important;
        padding-right: 2.6rem;
    }

    .payments-filter-dropdown-icon {
        position: absolute;
        right: 0.95rem;
        top: 50%;
        width: 0.5rem;
        height: 0.5rem;
        transform: translateY(-62%) rotate(45deg);
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        color: var(--ink-muted);
        pointer-events: none;
        transition: transform .16s ease, color .16s ease;
    }

    .payments-filter-control.has-dropdown.is-open .payments-filter-dropdown-icon {
        color: var(--ink);
        transform: translateY(-38%) rotate(225deg);
    }

    .payments-filter-control .table-toolbar-search.has-clear-action {
        padding-right: 2.55rem;
        cursor: text;
    }

    .payments-search-clear {
        position: absolute;
        right: 0.55rem;
        top: 50%;
        width: 2rem;
        height: 2rem;
        transform: translateY(-50%);
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: var(--ink-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color .16s ease, color .16s ease;
    }

    .payments-search-clear:hover,
    .payments-search-clear:focus-visible {
        outline: none;
        background: #C7D5BE;
        color: var(--ink);
    }

    .payments-filter-toolbar .table-toolbar-label {
        display: none;
    }

    .payments-filter-toolbar .table-toolbar-search,
    .payments-filter-toolbar .table-toolbar-select {
        width: 100%;
        min-height: 2.75rem;
        height: 2.75rem;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        background: #E1E7D9;
        color: var(--ink);
        font-size: 0.875rem;
        box-shadow: none;
        cursor: pointer;
        transition: background-color .16s ease, border-color .16s ease, color .16s ease;
    }

    .payments-filter-toolbar .table-toolbar-search:focus,
    .payments-filter-toolbar .table-toolbar-select:focus {
        border-color: var(--accent);
        box-shadow: none;
        background: #FBFCF7;
    }

    .payments-filter-toolbar .table-toolbar-search:hover,
    .payments-filter-toolbar .table-toolbar-select:hover {
        background: #C7D5BE;
        border-color: #8EA083;
    }

    .payments-unified-card .list-card {
        border-top: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        background: #D3DEC9;
    }

    .payments-record-action {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.58rem 0.95rem;
        border-radius: 0.72rem;
        border: 1px solid var(--accent);
        background: var(--accent);
        color: #fff;
        font-size: 0.92rem;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .payments-record-action:hover {
        background: var(--brand-hover);
        border-color: var(--brand-hover);
    }

    .payments-record-action:focus-visible {
        outline: none;
        box-shadow: none;
        border-color: var(--brand);
    }

    .payments-record-action:active {
        transform: scale(0.97);
    }

    .payments-unified-card .table-wrapper {
        border: 0;
        border-radius: 14px;
        box-shadow: none;
        padding: 0;
        margin: 14px var(--panel-content-inline) 18px;
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        background: #D3DEC9;
        border: 1px solid var(--border);
    }

    .payments-unified-card .payments-table {
        background: #D3DEC9;
        border-collapse: separate;
        border-spacing: 0;
        border: 0;
        border-radius: 14px;
        overflow: hidden;
    }

    .payments-unified-card .payments-table thead tr {
        background: #C7D5BE;
    }

    .payments-unified-card .payments-table thead th {
        background: #C7D5BE;
        color: #3F4C3E;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.055em;
        text-transform: uppercase;
        border-bottom: 1px solid var(--border);
        padding-top: 13px;
        padding-bottom: 13px;
    }

    .payments-unified-card .payments-table tbody tr,
    .payments-unified-card .payments-table tbody td {
        background: #D3DEC9;
        transition: background-color .16s ease, color .16s ease;
    }

    .payments-unified-card .payments-table tbody tr:nth-child(even),
    .payments-unified-card .payments-table tbody tr:nth-child(even) td {
        background: #DCE6D6;
    }

    .payments-unified-card .payments-table tbody tr:hover,
    .payments-unified-card .payments-table tbody tr:hover td {
        background: #C5D3BC !important;
    }

    .payments-unified-card .payments-table tbody td {
        border-bottom: 1px solid rgba(142, 160, 131, .42);
        color: #232821;
        font-size: .86rem;
        line-height: 1.35;
        vertical-align: middle;
    }

    .payments-unified-card .payments-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .payments-case-code {
        color: #232821;
        font-weight: 700;
        letter-spacing: .015em;
    }

    .payments-money {
        color: #232821;
        font-weight: 650;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    body.panel-shell-body .payments-unified-card .payments-table tbody td[data-payment-total-received] {
        background: transparent !important;
        background-color: transparent !important;
        color: #5F7D5F !important;
        border-color: rgba(142, 160, 131, .42) !important;
        box-shadow: none !important;
    }

    .payments-money.is-balance {
        color: #9E4B3F;
        font-weight: 700;
    }

    body.panel-shell-body .payments-unified-card .payments-table tbody td > span.status-pill-success,
    body.panel-shell-body .payments-unified-card .payments-table tbody td > span.status-pill-warning,
    body.panel-shell-body .payments-unified-card .payments-table tbody td > span.status-pill-danger {
        background: transparent !important;
        background-color: transparent !important;
        border-color: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
        border-radius: 0 !important;
    }

    body.panel-shell-body .payments-unified-card .payments-table tbody td > span.status-pill-success {
        color: #4D5852 !important;
    }

    body.panel-shell-body .payments-unified-card .payments-table tbody td > span.status-pill-warning {
        color: #8C5A3F !important;
    }

    body.panel-shell-body .payments-unified-card .payments-table tbody td > span.status-pill-danger {
        color: #9E4B3F !important;
    }

    .payments-meta-section {
        padding: 0 var(--panel-content-inline) 18px;
        background: #D3DEC9;
    }

    .payments-pagination {
        border-top: 1px solid var(--border);
        padding: 12px var(--panel-content-inline);
    }

    html.payment-modal-open,
    html.payment-modal-open body,
    html.payment-modal-open .panel-shell-body {
        overflow: hidden !important;
    }

    #paymentFormModal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 2500 !important;
        width: 100vw;
        height: 100vh;
        min-height: 100vh;
        min-height: 100svh;
        height: 100dvh;
        min-height: 100dvh;
        overflow: hidden;
        background: #C6D4BE;
        isolation: isolate;
        padding: 0 !important;
        left: 0 !important;
        right: 0 !important;
    }

    #paymentFormBackdrop {
        position: fixed !important;
        inset: 0 !important;
        width: 100vw;
        height: 100vh;
        min-height: 100vh;
        min-height: 100svh;
        height: 100dvh;
        min-height: 100dvh;
        background: #C6D4BE !important;
    }

    #paymentFormModal .payment-modal-viewport {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 18px;
        overflow: hidden;
        background: #C6D4BE;
    }

    #paymentFormModal .payment-modal-sheet {
        width: min(1120px, 100%);
        height: auto;
        max-height: calc(100vh - 80px);
        max-height: calc(100dvh - 80px);
        margin: 0 auto;
        overflow-y: auto;
        border-radius: 20px;
        box-shadow: none !important;
    }

    #paymentFormModal .payment-modal-close,
    .payments-date-modal .payments-date-modal-close {
        cursor: pointer;
        box-shadow: none !important;
    }

    #paymentFormModal .payment-modal-close:hover,
    .payments-date-modal .payments-date-modal-close:hover {
        background: #C7D5BE !important;
        color: var(--ink) !important;
    }

    .payments-date-backdrop {
        position: fixed;
        inset: 0;
        z-index: 55;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(31, 38, 31, .42);
    }

    .payments-date-backdrop.open {
        display: flex;
    }

    .payments-date-modal {
        width: min(100%, 28rem);
        border: 1px solid var(--border);
        border-radius: 16px;
        background: #D3DEC9;
        overflow: hidden;
        box-shadow: none;
    }

    .payments-date-modal-head,
    .payments-date-modal-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .9rem 1rem;
        border-bottom: 1px solid var(--border);
    }

    .payments-date-modal-foot {
        justify-content: flex-end;
        border-top: 1px solid var(--border);
        border-bottom: 0;
    }

    .payments-date-modal-title {
        font-size: .95rem;
        font-weight: 700;
        color: var(--ink);
    }

    .payments-date-modal-body {
        display: grid;
        gap: .8rem;
        padding: 1rem;
    }

    .payments-date-modal-label {
        display: block;
        margin-bottom: .38rem;
        color: var(--ink-muted);
        font-size: .72rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    @media (max-width: 640px) {
        .payments-record-action {
            width: 100%;
            justify-content: center;
        }

        .payments-filter-toolbar,
        .payments-filter-toolbar .table-toolbar-field,
        .payments-filter-toolbar .table-toolbar-field {
            width: 100%;
        }

        .payments-filter-toolbar .payments-filter-date {
            flex-basis: 100%;
            min-width: 0;
        }

        .payments-filter-right {
            width: 100%;
            margin-left: 0;
            justify-content: stretch;
        }

        .payments-filter-right .payments-record-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="payments-page">
@php
    $paymentDateRange = request('date_range', 'any');
    $usesPaymentCustomDate = $paymentDateRange === 'custom'
        || (!request()->filled('date_range') && (request()->filled('request_date_from') || request()->filled('request_date_to')));
    if ($usesPaymentCustomDate) {
        $paymentDateRange = 'custom';
    }
@endphp

@if(session('success'))
    <div class="flash-success">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="flash-error">
        {{ $errors->first() }}
    </div>
@endif

@if($canRecordPayment ?? false)
<div id="paymentFormModal" class="fixed inset-0 z-40 hidden panel-overlay-content">
    <div class="absolute inset-0 bg-black/60" id="paymentFormBackdrop"></div>
    <div class="payment-modal-viewport">
        <div class="payment-modal-sheet rounded-2xl overflow-hidden">
            <div class="payment-modal-head flex items-center justify-between px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="payment-modal-icon w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-cash-stack text-base"></i>
                    </div>
                    <div>
                        <div class="payment-modal-title text-sm font-bold">Record a Case Payment</div>
                        <div class="payment-modal-copy text-xs mt-0.5">Choose a case, enter the received amount, then verify the payment preview before saving.</div>
                    </div>
                </div>
                <button type="button" id="closePaymentFormTop" class="payment-modal-close inline-flex items-center justify-center w-9 h-9 rounded-xl transition-colors focus:outline-none">
                    <i class="bi bi-x-lg text-[.8rem]"></i>
                </button>
            </div>
            <div class="p-6">
                @include('staff.payments._form')
            </div>
        </div>
    </div>
</div>
@endif

<div class="payments-unified-card">
    <div class="payments-filter-shell table-system-toolbar">
        <form id="paymentRecordingFilterForm" method="GET" action="{{ route('payments.index') }}" class="payments-filter-toolbar" data-table-toolbar data-search-debounce="400">
            <div class="table-toolbar-field payments-filter-search">
                <label for="payment-filter-q" class="table-toolbar-label">Search</label>
                <div class="payments-filter-control">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input
                        id="payment-filter-q"
                        name="q"
                        value="{{ request('q') }}"
                        class="form-input table-toolbar-search has-clear-action"
                        data-table-search
                        placeholder="Search case no., client, or deceased..."
                        autocomplete="off"
                    >
                    <button
                        type="button"
                        class="payments-search-clear"
                        data-payment-search-clear
                        aria-label="Clear payment search"
                        @if(blank(request('q'))) hidden @endif
                    >
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            @if(($branches ?? collect())->count() > 1)
                <div class="table-toolbar-field">
                    <label for="payment-branch-filter" class="table-toolbar-label">Branch</label>
                    <select id="payment-branch-filter" name="branch_id" class="form-select table-toolbar-select" data-table-auto-submit>
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) ($selectedBranchId ?? '') === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->branch_code }} - {{ $branch->branch_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="payments-filter-right">
                <div class="table-toolbar-field payments-filter-date">
                    <label for="payment-date-range" class="table-toolbar-label">Service Date</label>
                    <div class="payments-filter-control has-dropdown" data-payment-select-control>
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <select id="payment-date-range" name="date_range" class="form-select table-toolbar-select" data-payment-date-range>
                            <option value="any" @selected($paymentDateRange === 'any')>All Dates</option>
                            <option value="today" @selected($paymentDateRange === 'today')>Today</option>
                            <option value="this_month" @selected($paymentDateRange === 'this_month')>This Month</option>
                            <option value="this_year" @selected($paymentDateRange === 'this_year')>This Year</option>
                            <option value="custom" @selected($paymentDateRange === 'custom')>Custom</option>
                        </select>
                        <span class="payments-filter-dropdown-icon" aria-hidden="true"></span>
                    </div>
                </div>

                @if($canRecordPayment ?? false)
                    <button id="openPaymentForm" type="button" class="payments-record-action">
                        <i class="bi bi-cash-stack text-base"></i>
                        Record Payment
                    </button>
                @endif
            </div>

            <input id="payment-service-date-from" type="hidden" name="request_date_from" value="{{ request('request_date_from') }}" data-payment-custom-date-input>
            <input id="payment-service-date-to" type="hidden" name="request_date_to" value="{{ request('request_date_to') }}" data-payment-custom-date-input>

        </form>
    </div>

    <div id="paymentDateModal" class="payments-date-backdrop" aria-hidden="true">
        <div class="payments-date-modal" role="dialog" aria-modal="true" aria-labelledby="paymentDateModalTitle">
            <div class="payments-date-modal-head">
                <div class="payments-date-modal-title" id="paymentDateModalTitle">Custom Date</div>
                <button type="button" class="payments-date-modal-close btn-outline" data-payment-date-cancel aria-label="Close custom date">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="payments-date-modal-body">
                <label>
                    <span class="payments-date-modal-label">From</span>
                    <input id="payment-modal-date-from" type="date" value="{{ request('request_date_from') }}" class="form-input table-toolbar-select">
                </label>
                <label>
                    <span class="payments-date-modal-label">To</span>
                    <input id="payment-modal-date-to" type="date" value="{{ request('request_date_to') }}" class="form-input table-toolbar-select">
                </label>
            </div>
            <div class="payments-date-modal-foot">
                <button type="button" class="btn-outline" data-payment-date-cancel>Cancel</button>
                <button type="button" class="btn-secondary" id="paymentApplyCustomDates">Apply</button>
            </div>
        </div>
    </div>

    <div class="list-card">
        <div class="table-wrapper">
            <table class="table-base payments-table">
                <thead>
                    <tr>
                        <th class="text-left">Case Code</th>
                        <th class="text-left">Client</th>
                        <th class="text-left">Deceased</th>
                        <th class="text-left">Service</th>
                        <th class="text-left">Total Due</th>
                        <th class="text-left">Total Paid</th>
                        <th class="text-left">Balance</th>
                        <th class="text-left">Payment Status</th>
                        <th class="text-left">Case Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($openCases as $case)
                    <tr
                        @if($canRecordPayment ?? false)
                            data-open-payment-case="{{ $case->id }}"
                            data-case-code="{{ $case->case_code }}"
                            data-case-client="{{ $case->client?->full_name ?? '' }}"
                            data-case-total="{{ $case->total_amount }}"
                            data-case-paid="{{ $case->total_paid }}"
                            data-case-balance="{{ $case->balance_amount }}"
                        @endif
                        class="{{ ($canRecordPayment ?? false) ? 'cursor-pointer' : '' }}"
                    >
                        <td class="payments-case-code">{{ $case->case_code }}</td>
                        <td>{{ $case->client?->full_name ?? '-' }}</td>
                        <td>{{ $case->deceased?->full_name ?? '-' }}</td>
                        <td>{{ $case->service_package ?: ($case->custom_package_name ?: '-') }}</td>
                        <td class="payments-money">{{ number_format($case->total_amount, 2) }}</td>
                        <td class="payments-money" data-payment-total-received>{{ number_format((float) $case->total_paid, 2) }}</td>
                        <td class="payments-money is-balance">{{ number_format((float) $case->balance_amount, 2) }}</td>
                        <td>
                            <span class="{{ $case->payment_status === 'PARTIAL' ? 'status-pill-warning' : 'status-pill-danger' }}">
                                {{ $case->payment_status }}
                            </span>
                        </td>
                        <td>
                            <span class="{{ $case->case_status === 'COMPLETED' ? 'status-pill-success' : 'status-pill-warning' }}">
                                {{ $case->case_status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-10 text-center">
                            <div class="text-slate-400 text-sm font-medium">No open cases found.</div>
                            <div class="text-slate-300 text-xs mt-1">All cases are fully paid or no cases match the filters.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="payments-pagination">
            @if($openCases->hasPages()){{ $openCases->links('components.pagination.table') }}@endif
        </div>

        <div class="payments-meta-section">
            <a href="{{ route('payments.history') }}" class="btn-outline inline-flex">
                <i class="bi bi-clock-history"></i>
                <span>Payment Monitoring</span>
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    const pageShell = document.querySelector('.payments-unified-card');

    const setPaymentUpdating = (isUpdating) => {
        pageShell?.classList.toggle('is-updating', isUpdating);
    };

    const paymentResultsUrl = (form) => {
        const url = new URL(form.action, window.location.origin);
        const data = new FormData(form);

        Array.from(url.searchParams.keys()).forEach((key) => url.searchParams.delete(key));
        data.forEach((value, key) => {
            if (value !== null && String(value) !== '') {
                url.searchParams.append(key, value);
            }
        });

        return url;
    };

    const loadPaymentResults = async (url, pushState = true) => {
        const currentList = document.querySelector('.payments-unified-card .list-card');
        if (!currentList) {
            window.location.href = url.toString();
            return;
        }

        setPaymentUpdating(true);

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error(`Payment results request failed: ${response.status}`);

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextList = nextDocument.querySelector('.payments-unified-card .list-card');
            if (!nextList) throw new Error('Payment results list not found.');

            await new Promise((resolve) => window.setTimeout(resolve, 90));
            currentList.replaceWith(nextList);

            if (pushState) {
                window.history.pushState({}, '', url.toString());
            }

            document.dispatchEvent(new CustomEvent('panel-ui:reset'));
        } catch (error) {
            window.location.href = url.toString();
        } finally {
            requestAnimationFrame(() => setPaymentUpdating(false));
        }
    };

    const filterForm = document.getElementById('paymentRecordingFilterForm');
    if (filterForm) {
        const searchInput = filterForm.querySelector('[data-table-search]');
        const searchClear = filterForm.querySelector('[data-payment-search-clear]');
        const dateRange = filterForm.querySelector('[data-payment-date-range]');
        const customDateInputs = filterForm.querySelectorAll('[data-payment-custom-date-input]');
        const dateModal = document.getElementById('paymentDateModal');
        const modalFrom = document.getElementById('payment-modal-date-from');
        const modalTo = document.getElementById('payment-modal-date-to');
        const applyCustomDates = document.getElementById('paymentApplyCustomDates');

        const syncSearchClear = () => {
            if (searchClear) searchClear.hidden = !(searchInput?.value || '').trim();
        };

        searchInput?.addEventListener('input', syncSearchClear);
        searchClear?.addEventListener('click', () => {
            if (!searchInput) return;
            searchInput.value = '';
            syncSearchClear();
            searchInput.focus();
            filterForm.requestSubmit();
        });
        syncSearchClear();

        filterForm.addEventListener('submit', (event) => {
            event.preventDefault();
            loadPaymentResults(paymentResultsUrl(filterForm), true);
        });

        const toDateValue = (date) => date.toISOString().slice(0, 10);

        const setPresetDates = (preset) => {
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            let from = '';
            let to = '';

            if (preset === 'today') {
                from = to = toDateValue(today);
            } else if (preset === 'this_month') {
                from = toDateValue(new Date(today.getFullYear(), today.getMonth(), 1));
                to = toDateValue(new Date(today.getFullYear(), today.getMonth() + 1, 0));
            } else if (preset === 'this_year') {
                from = toDateValue(new Date(today.getFullYear(), 0, 1));
                to = toDateValue(new Date(today.getFullYear(), 11, 31));
            }

            const [dateFrom, dateTo] = customDateInputs;
            if (dateFrom) dateFrom.value = from;
            if (dateTo) dateTo.value = to;
        };

        const openDateModal = () => {
            const [dateFrom, dateTo] = customDateInputs;
            if (modalFrom && dateFrom) modalFrom.value = dateFrom.value;
            if (modalTo && dateTo) modalTo.value = dateTo.value;
            dateModal?.classList.add('open');
            dateModal?.setAttribute('aria-hidden', 'false');
            modalFrom?.focus();
        };

        const closeDateModal = () => {
            dateModal?.classList.remove('open');
            dateModal?.setAttribute('aria-hidden', 'true');
        };

        dateRange?.addEventListener('change', () => {
            if (dateRange.value === 'custom') {
                openDateModal();
                return;
            }

            setPresetDates(dateRange.value);
            filterForm.requestSubmit();
        });

        applyCustomDates?.addEventListener('click', () => {
            const [dateFrom, dateTo] = customDateInputs;
            if (dateFrom && modalFrom) dateFrom.value = modalFrom.value;
            if (dateTo && modalTo) dateTo.value = modalTo.value;
            closeDateModal();
            filterForm.requestSubmit();
        });

        document.querySelectorAll('[data-payment-date-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                closeDateModal();
                const [dateFrom, dateTo] = customDateInputs;
                if (dateRange && !dateFrom?.value && !dateTo?.value) dateRange.value = 'any';
            });
        });

        dateModal?.addEventListener('click', (event) => {
            if (event.target === dateModal) closeDateModal();
        });
    }

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) return;

        const link = target.closest('.payments-unified-card .payments-pagination a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;

        event.preventDefault();
        loadPaymentResults(url, true);
    }, true);

    window.addEventListener('popstate', () => {
        loadPaymentResults(new URL(window.location.href), false);
    });

    const canRecordPayment = @json($canRecordPayment ?? false);
    if (!canRecordPayment) return;

    const openBtn    = document.getElementById('openPaymentForm');
    const modal      = document.getElementById('paymentFormModal');
    const backdrop   = document.getElementById('paymentFormBackdrop');
    const closeTop   = document.getElementById('closePaymentFormTop');
    const closeBottom= document.getElementById('closePaymentFormBottom');
    const caseSelect = document.getElementById('funeral_case_id');
    const preselectCaseId  = @json($preselectCase->id ?? null);
    const autoOpenPayment  = @json($autoOpenPayment ?? false);

    if (!openBtn || !modal) return;

    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    function openModal() {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => modal.classList.add('opacity-100'));
        document.documentElement.classList.add('payment-modal-open');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.remove('opacity-100');
        setTimeout(() => {
            modal.classList.add('hidden');
            document.documentElement.classList.remove('payment-modal-open');
            document.body.classList.remove('overflow-hidden');
        }, 190);
    }

    openBtn.addEventListener('click', openModal);
    if (backdrop)    backdrop.addEventListener('click', closeModal);
    if (closeTop)    closeTop.addEventListener('click', closeModal);
    if (closeBottom) closeBottom.addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    const selectPaymentCaseFromRow = (row) => {
        const caseId = row?.dataset?.openPaymentCase;
        if (caseSelect && caseId) {
            let option = [...caseSelect.options].find((item) => item.value === String(caseId));
            if (!option) {
                option = new Option(
                    `${row.dataset.caseCode || 'Selected case'} - ${row.dataset.caseClient || 'Client'} - Balance: PHP ${Number(row.dataset.caseBalance || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                    String(caseId),
                    false,
                    false
                );
                option.dataset.total = row.dataset.caseTotal || '0';
                option.dataset.paid = row.dataset.casePaid || '0';
                option.dataset.balance = row.dataset.caseBalance || '0';
                caseSelect.appendChild(option);
            }
            caseSelect.value = String(caseId);
            caseSelect.dispatchEvent(new Event('change'));
        }
        openModal();
    };

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        const row = target?.closest?.('[data-open-payment-case]');
        if (!row || event.defaultPrevented) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        selectPaymentCaseFromRow(row);
    }, true);

    if (caseSelect && preselectCaseId) {
        caseSelect.value = String(preselectCaseId);
        caseSelect.dispatchEvent(new Event('change'));
    }

    if (preselectCaseId || autoOpenPayment) openModal();

    @if($errors->any())
        openModal();
    @endif
})();
</script>
</div>
@endsection
