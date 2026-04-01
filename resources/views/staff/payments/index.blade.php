@extends('layouts.panel')

@section('page_title', 'Payments')

@section('content')
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

<div class="flex flex-wrap items-center gap-3 mb-4">
    <button id="openPaymentForm" type="button" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-[#9c5a1a] text-white font-semibold shadow-sm hover:bg-[#7d4515] focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-[#9c5a1a]/30 transition">
        <i class="bi bi-cash-stack text-base"></i>
        Record Payment
    </button>
    <div class="flash-info mb-0">
        Main branch payments only.
    </div>
</div>

<div id="paymentFormModal" class="fixed inset-0 z-40 hidden">
    <div class="absolute inset-0 bg-black/40" id="paymentFormBackdrop"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-3xl border border-surface-muted bg-white p-5 shadow-xl">
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

<div class="filter-panel mb-6">
    <form method="GET" action="{{ route('payments.index') }}" class="space-y-4">
        <div class="filter-grid">
            <input name="q" value="{{ request('q') }}" class="form-input w-full md:w-[22rem]" placeholder="Search by case, client, or deceased..." onchange="this.form.submit()">

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
    </div>

    <div class="table-wrapper rounded-none border-0">
        <table class="table-base text-sm">
            <thead>
                <tr>
                    <th class="text-left">Case Code</th>
                    <th class="text-left">Client</th>
                    <th class="text-left">Deceased</th>
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
                    <td colspan="8" class="py-6 text-center text-slate-500">No open cases.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $openCases->links() }}
</div>

<div class="list-card mt-6 p-5">
    <div class="list-card-title mb-2">Payment History</div>
    <div class="list-card-copy">Payment history is on a separate page with its own date and status filters.</div>
    <a href="{{ route('payments.history') }}" class="btn-outline mt-4 inline-flex">Open Payment History</a>
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
@endsection

