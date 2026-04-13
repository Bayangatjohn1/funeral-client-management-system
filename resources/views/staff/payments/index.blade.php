@extends('layouts.panel')

@section('page_title', 'Payments')
@section('page_desc', 'Record and manage case payment transactions.')

@section('content')
<style>
    .payments-page {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        min-height: 100%;
        height: 100%;
        width: 100%;
    }

    .payments-unified-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 0;
        box-shadow: none;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        width: 100%;
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

    .payments-unified-card .list-card {
        border-top: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .payments-unified-card .list-card-header {
        flex-shrink: 0;
    }

    .payments-record-action {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.58rem 0.95rem;
        border-radius: 0.72rem;
        border: 1px solid #9c5a1a;
        background: #9c5a1a;
        color: #fff;
        font-size: 0.92rem;
        font-weight: 700;
        transition: background-color 0.18s ease, border-color 0.18s ease;
    }

    .payments-record-action:hover {
        background: #7d4515;
        border-color: #7d4515;
    }

    .payments-record-action:focus {
        outline: none;
    }

    .payments-unified-card .table-wrapper {
        border: 0;
        border-radius: 0;
        box-shadow: none;
    }

    .payments-meta-section {
        border-top: 1px solid var(--border);
        padding: 16px var(--panel-content-inline) 18px;
    }

    .payments-pagination {
        border-top: 1px solid var(--border);
        padding: 12px var(--panel-content-inline);
    }

    #paymentFormModal .payment-modal-viewport {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    #paymentFormModal .payment-modal-sheet {
        width: min(1120px, 100%);
        max-height: calc(100vh - 32px);
        margin: 0 auto;
        overflow-y: auto;
        border-radius: 24px;
        box-shadow: none;
    }

    @media (max-width: 640px) {
        .payments-record-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="payments-page">
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

<div id="paymentFormModal" class="fixed inset-0 z-40 hidden panel-overlay-content">
    <div class="absolute inset-0 bg-black/40" id="paymentFormBackdrop"></div>
    <div class="payment-modal-viewport">
        <div class="payment-modal-sheet border border-surface-muted bg-white p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="text-base font-semibold text-slate-900">Record Payment</div>
                <button type="button" id="closePaymentFormTop" class="btn-outline btn-sm">Close</button>
            </div>
            @include('staff.payments._form')
            <div class="mt-4 text-right">
                <button type="button" id="closePaymentFormBottom" class="btn-outline">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="payments-unified-card">
    <div class="filter-panel">
        <form method="GET" action="{{ route('payments.index') }}" class="space-y-4">
            <div class="filter-grid">
                <input name="q" value="{{ request('q') }}" class="form-input w-full md:w-[22rem]" placeholder="Search case, client, or deceased..." onchange="this.form.submit()">

                <select name="payment_status" class="form-select w-full md:w-44" onchange="this.form.submit()">
                    <option value="">All Payment Status</option>
                    <option value="UNPAID" {{ request('payment_status') === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                    <option value="PARTIAL" {{ request('payment_status') === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                </select>

                <select name="case_status" class="form-select w-full md:w-44" onchange="this.form.submit()">
                    <option value="">All Case Status</option>
                    <option value="DRAFT" {{ request('case_status') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                    <option value="ACTIVE" {{ request('case_status') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="COMPLETED" {{ request('case_status') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                </select>

                <input type="date" name="request_date_from" value="{{ request('request_date_from') }}" class="form-input w-full md:w-44" title="Request date from" onchange="this.form.submit()">
                <input type="date" name="request_date_to" value="{{ request('request_date_to') }}" class="form-input w-full md:w-44" title="Request date to" onchange="this.form.submit()">
            </div>

            <div class="filter-actions">
                <a href="{{ route('payments.index') }}" class="btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <div class="list-card">
        <div class="list-card-header">
            <div>
                <div class="list-card-title">Open Cases</div>
                <div class="list-card-copy">Unpaid and partial-payment cases available for follow-up collection.</div>
            </div>
            <div>
                <button id="openPaymentForm" type="button" class="payments-record-action">
                    <i class="bi bi-cash-stack text-base"></i>
                    Record Payment
                </button>
            </div>
        </div>

        <div class="table-wrapper rounded-none border-0">
            <table class="table-base text-sm">
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
                    <tr>
                        <td>{{ $case->case_code }}</td>
                        <td>{{ $case->client?->full_name ?? '-' }}</td>
                        <td>{{ $case->deceased?->full_name ?? '-' }}</td>
                        <td>{{ $case->service_package ?: ($case->custom_package_name ?: '-') }}</td>
                        <td>{{ number_format($case->total_amount, 2) }}</td>
                        <td>{{ number_format((float) $case->total_paid, 2) }}</td>
                        <td>{{ number_format((float) $case->balance_amount, 2) }}</td>
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
                        <td colspan="9" class="py-6 text-center text-slate-500">No open cases.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="payments-pagination">
            {{ $openCases->links() }}
        </div>

        <div class="payments-meta-section">
            <div class="list-card-title mb-2">Payment History</div>
            <div class="list-card-copy">Payment history is on a separate page with its own date and status filters.</div>
            <a href="{{ route('payments.history') }}" class="btn-outline mt-4 inline-flex">Open Payment History</a>
        </div>
    </div>
</div>

<script>
    (function () {
        const openBtn = document.getElementById('openPaymentForm');
        const modal = document.getElementById('paymentFormModal');
        const backdrop = document.getElementById('paymentFormBackdrop');
        const closeTop = document.getElementById('closePaymentFormTop');
        const closeBottom = document.getElementById('closePaymentFormBottom');
        const caseSelect = document.getElementById('funeral_case_id');
        const preselectCaseId = @json($preselectCase->id ?? null);
        const autoOpenPayment = @json($autoOpenPayment ?? false);
        if (!openBtn || !modal) {
            return;
        }

        function openModal() {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        openBtn.addEventListener('click', openModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);
        if (closeTop) closeTop.addEventListener('click', closeModal);
        if (closeBottom) closeBottom.addEventListener('click', closeModal);

        if (caseSelect && preselectCaseId) {
            caseSelect.value = String(preselectCaseId);
            caseSelect.dispatchEvent(new Event('change'));
        }

        if (preselectCaseId || autoOpenPayment) {
            openModal();
        }

        @if($errors->any())
            openModal();
        @endif
    })();
</script>
</div>
@endsection
