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
        border: 1px solid var(--accent);
        background: var(--accent);
        color: #fff;
        font-size: 0.92rem;
        font-weight: 700;
        transition: background-color 0.18s ease, border-color 0.18s ease;
    }

    .payments-record-action:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
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
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="paymentFormBackdrop"></div>
    <div class="payment-modal-viewport">
        <div class="payment-modal-sheet rounded-2xl overflow-hidden" style="border:1px solid var(--border);background:var(--card);box-shadow:0 25px 60px rgba(0,0,0,0.22)">
            <div class="flex items-center justify-between px-6 py-5" style="border-bottom:1px solid var(--border);background:var(--surface-muted)">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#1b3358] text-white flex items-center justify-center flex-shrink-0 shadow-md">
                        <i class="bi bi-cash-stack text-base"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold" style="color:var(--ink)">Record Payment</div>
                        <div class="text-xs mt-0.5" style="color:var(--ink-muted)">Log a cash payment against an open case</div>
                    </div>
                </div>
                <button type="button" id="closePaymentFormTop" class="inline-flex items-center justify-center w-9 h-9 rounded-xl transition-colors focus:outline-none shadow-sm" style="background:var(--card);border:1px solid var(--border);color:var(--ink-muted)">
                    <i class="bi bi-x-lg" style="font-size:.8rem"></i>
                </button>
            </div>
            <div class="p-6">
                @include('staff.payments._form')
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
                <div class="list-card-copy">Click any row to record a payment, or use the button to open the form manually.</div>
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
                    <tr data-open-payment-case="{{ $case->id }}" title="Click to record payment for this case" class="hover:bg-slate-50 transition-colors cursor-pointer">
                        <td class="font-mono font-bold text-slate-800">{{ $case->case_code }}</td>
                        <td>{{ $case->client?->full_name ?? '-' }}</td>
                        <td>{{ $case->deceased?->full_name ?? '-' }}</td>
                        <td>{{ $case->service_package ?: ($case->custom_package_name ?: '-') }}</td>
                        <td class="font-semibold">{{ number_format($case->total_amount, 2) }}</td>
                        <td class="text-emerald-700 font-semibold">{{ number_format((float) $case->total_paid, 2) }}</td>
                        <td class="text-rose-700 font-bold">{{ number_format((float) $case->balance_amount, 2) }}</td>
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
    const openBtn    = document.getElementById('openPaymentForm');
    const modal      = document.getElementById('paymentFormModal');
    const backdrop   = document.getElementById('paymentFormBackdrop');
    const closeTop   = document.getElementById('closePaymentFormTop');
    const closeBottom= document.getElementById('closePaymentFormBottom');
    const caseSelect = document.getElementById('funeral_case_id');
    const preselectCaseId  = @json($preselectCase->id ?? null);
    const autoOpenPayment  = @json($autoOpenPayment ?? false);

    if (!openBtn || !modal) return;

    function openModal() {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => modal.classList.add('opacity-100'));
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.remove('opacity-100');
        setTimeout(() => {
            modal.classList.add('hidden');
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

    document.querySelectorAll('[data-open-payment-case]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => {
            const caseId = row.dataset.openPaymentCase;
            if (caseSelect && caseId) {
                caseSelect.value = caseId;
                caseSelect.dispatchEvent(new Event('change'));
            }
            openModal();
        });
    });

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
