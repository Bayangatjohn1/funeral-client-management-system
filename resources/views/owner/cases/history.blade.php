@extends('layouts.panel')

@section('page_title', 'Global Case History')
@section('page_desc', 'Review completed case records across all branches.')

@section('content')
<div class="owner-page-shell">
@if($errors->any())
    <div class="flash-error">
        {{ $errors->first() }}
    </div>
@endif

<div class="filter-panel mb-5">
    <form method="GET" action="{{ route('owner.history') }}" class="space-y-4">
        <div class="filter-grid">
            <div>
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select w-full md:w-56" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->branch_code }} - {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input" onchange="this.form.submit()">
            </div>

            <div>
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input" onchange="this.form.submit()">
            </div>

            <div>
                <label class="form-label">Search</label>
                <input name="q" value="{{ $q }}" class="form-input w-full md:w-72" placeholder="Search case, client, or deceased..." onchange="this.form.submit()">
            </div>
        </div>

        <div class="filter-actions">
            <a href="{{ route('owner.history') }}" class="btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="list-card">
    <div class="list-card-header">
        <div>
            <div class="list-card-title">Global Case Timeline</div>
            <div class="list-card-copy">Track completed case records, branch output, and payment health across the business.</div>
        </div>
    </div>

    <div class="table-wrapper rounded-none border-0">
        <table class="table-base text-sm">
            <thead>
                <tr>
                    <th class="text-left">Case Code</th>
                    <th class="text-left">Service Date</th>
                    <th class="text-left">Branch</th>
                    <th class="text-left">Client</th>
                    <th class="text-left">Deceased</th>
                    <th class="text-left">Interment</th>
                    <th class="text-left">Package</th>
                    <th class="text-left">Payment</th>
                    <th class="text-left">Total Paid</th>
                    <th class="text-left">Balance</th>
                    <th class="text-left">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                <tr>
                    <td>{{ $case->case_code }}</td>
                    <td>{{ $case->service_requested_at?->format('Y-m-d') ?? $case->created_at?->format('Y-m-d') }}</td>
                    <td>{{ $case->branch?->branch_code ?? '-' }}</td>
                    <td>{{ $case->client?->full_name ?? '-' }}</td>
                    <td>{{ $case->deceased?->full_name ?? '-' }}</td>
                    <td>{{ $case->deceased?->interment_at?->format('Y-m-d H:i') ?? $case->deceased?->interment?->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ $case->service_package ?? '-' }}</td>
                    <td>
                        <span class="{{
                            $case->payment_status === 'PAID'
                                ? 'status-pill-success'
                                : ($case->payment_status === 'PARTIAL' ? 'status-pill-warning' : 'status-pill-danger')
                        }}">
                            {{ $case->payment_status }}
                        </span>
                    </td>
                    <td>{{ number_format((float) $case->total_paid, 2) }}</td>
                    <td>{{ number_format((float) $case->balance_amount, 2) }}</td>
                    <td>
                        <a href="{{ route('owner.cases.show', $case) }}" class="table-action-link">View Details</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="py-6 text-center text-slate-500">No records found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $cases->links() }}
</div>
</div>
@endsection

