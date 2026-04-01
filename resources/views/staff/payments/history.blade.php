@extends('layouts.panel')

@section('page_title', 'Payment History')

@section('content')
@if($errors->any())
    <div class="flash-error">
        {{ $errors->first() }}
    </div>
@endif

<div class="flash-info">
    Main branch payment history only.
</div>

<div class="filter-panel mb-6">
    <form method="GET" action="{{ route('payments.history') }}" class="space-y-4">
        <div class="filter-grid">
            <input
                name="q"
                value="{{ $q }}"
                class="form-input w-full md:w-[20rem]"
                placeholder="Search by case, client, or deceased..."
                onchange="this.form.submit()">

            <select name="status_after_payment" class="form-select w-full md:w-48" onchange="this.form.submit()">
                <option value="">All Status After Payment</option>
                <option value="UNPAID" {{ ($statusAfterPayment ?? '') === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                <option value="PARTIAL" {{ ($statusAfterPayment ?? '') === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                <option value="PAID" {{ ($statusAfterPayment ?? '') === 'PAID' ? 'selected' : '' }}>Paid</option>
            </select>

            <input type="date" name="paid_from" value="{{ $paidFrom ?? '' }}" class="form-input w-full md:w-44" title="Paid from" onchange="this.form.submit()">
            <input type="date" name="paid_to" value="{{ $paidTo ?? '' }}" class="form-input w-full md:w-44" title="Paid to" onchange="this.form.submit()">
        </div>

        <div class="filter-actions">
            <a href="{{ route('payments.history') }}" class="btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="list-card">
    <div class="list-card-header">
        <div>
            <div class="list-card-title">Payment Transactions</div>
            <div class="list-card-copy">Review each payment record with its receipt number, running balance, and recorder.</div>
        </div>
    </div>

    <div class="table-wrapper rounded-none border-0">
        <table class="table-base text-sm">
            <thead>
                <tr>
                    <th class="text-left">Receipt No.</th>
                    <th class="text-left">Case Code</th>
                    <th class="text-left">Client</th>
                    <th class="text-left">Deceased</th>
                    <th class="text-left">Method</th>
                    <th class="text-left">Amount</th>
                    <th class="text-left">Balance After Payment</th>
                    <th class="text-left">Status After Payment</th>
                    <th class="text-left">Paid At</th>
                    <th class="text-left">Recorded By</th>
                </tr>
            </thead>
            <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->receipt_number ?? '-' }}</td>
                    <td>{{ $payment->funeralCase?->case_code ?? '-' }}</td>
                    <td>{{ $payment->funeralCase?->client?->full_name ?? '-' }}</td>
                    <td>{{ $payment->funeralCase?->deceased?->full_name ?? '-' }}</td>
                    <td>{{ $payment->method }}</td>
                    <td>{{ number_format($payment->amount, 2) }}</td>
                    <td>{{ number_format((float) ($payment->balance_after_payment ?? 0), 2) }}</td>
                    <td>
                        <span class="{{
                            ($payment->payment_status_after_payment ?? '') === 'PAID'
                                ? 'status-pill-success'
                                : ((($payment->payment_status_after_payment ?? '') === 'PARTIAL') ? 'status-pill-warning' : 'status-pill-danger')
                        }}">
                            {{ $payment->payment_status_after_payment ?? '-' }}
                        </span>
                    </td>
                    <td>{{ $payment->paid_at?->format('Y-m-d H:i') ?? $payment->paid_date?->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ $payment->recordedBy?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="py-6 text-center text-slate-500">No payment history found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $payments->links() }}
</div>
@endsection

