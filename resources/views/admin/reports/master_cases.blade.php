@extends('layouts.panel')

@section('page_title', 'Master Case Records')

@section('content')
@if($errors->any())
    <div class="flash-error">
        {{ $errors->first() }}
    </div>
@endif
@php($advancedFiltersActive = false)

<div class="filter-panel mb-5">
    <form method="GET" action="{{ route('admin.cases.index') }}" class="space-y-4">
        <div class="filter-grid">
            <input name="q" value="{{ $q }}" class="form-input w-full md:w-72" placeholder="Search case, client, or deceased..." onchange="this.form.submit()">

            <select name="branch_id" class="form-select w-full md:w-56" onchange="this.form.submit()">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->branch_code }} - {{ $branch->branch_name }}
                    </option>
                @endforeach
            </select>

            <select name="case_status" class="form-select w-full md:w-48" onchange="this.form.submit()">
                <option value="">All Case Status</option>
                <option value="DRAFT" {{ $caseStatus === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                <option value="ACTIVE" {{ $caseStatus === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                <option value="COMPLETED" {{ $caseStatus === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
            </select>

            <select name="verification_status" class="form-select w-full md:w-48" onchange="this.form.submit()">
                <option value="">All Verification</option>
                <option value="PENDING" {{ ($verificationStatus ?? null) === 'PENDING' ? 'selected' : '' }}>Pending</option>
                <option value="VERIFIED" {{ ($verificationStatus ?? null) === 'VERIFIED' ? 'selected' : '' }}>Verified</option>
                <option value="DISPUTED" {{ ($verificationStatus ?? null) === 'DISPUTED' ? 'selected' : '' }}>Disputed</option>
            </select>

            <select name="payment_status" class="form-select w-full md:w-48" onchange="this.form.submit()">
                <option value="">All Payment Status</option>
                <option value="PAID" {{ $paymentStatus === 'PAID' ? 'selected' : '' }}>Paid</option>
                <option value="PARTIAL" {{ $paymentStatus === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                <option value="UNPAID" {{ $paymentStatus === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
            </select>

            <input type="date" name="interment_from" value="{{ $intermentFrom ?? '' }}" class="form-input w-full md:w-44" title="Interment from" onchange="this.form.submit()">
            <input type="date" name="interment_to" value="{{ $intermentTo ?? '' }}" class="form-input w-full md:w-44" title="Interment to" onchange="this.form.submit()">
        </div>

        <div class="filter-actions">
            <a href="{{ route('admin.cases.index') }}" class="btn-outline">Reset</a>
        </div>

        {{-- Advanced filters removed per UX direction --}}
    </form>
</div>

<div class="list-card">
    <div class="list-card-header">
        <div>
            <div class="list-card-title">All Recorded Cases</div>
            <div class="list-card-copy">Monitor branch activity, payment health, and verification status from one admin worklist.</div>
        </div>
    </div>

    <div class="table-wrapper rounded-none border-0">
        <table class="table-base text-sm">
            <thead>
                <tr>
                    <th class="text-left">Case ID</th>
                    <th class="text-left">Service Date</th>
                    <th class="text-left">Branch</th>
                    <th class="text-left">Client</th>
                    <th class="text-left">Deceased</th>
                    <th class="text-left">Interment</th>
                    <th class="text-left">Verification</th>
                    <th class="text-left">Case Status</th>
                    <th class="text-left">Payment Status</th>
                    <th class="text-left">Total</th>
                    <th class="text-left">Total Paid</th>
                    <th class="text-left">Balance</th>
                    <th class="text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                <tr>
                    <td>{{ $case->case_code }}</td>
                    <td>{{ $case->service_requested_at?->format('Y-m-d') ?? $case->created_at?->format('Y-m-d') }}</td>
                    <td>{{ $case->branch?->branch_code }} - {{ $case->branch?->branch_name }}</td>
                    <td>{{ $case->client?->full_name ?? '-' }}</td>
                    <td>{{ $case->deceased?->full_name ?? '-' }}</td>
                    <td>{{ $case->deceased?->interment_at?->format('Y-m-d H:i') ?? $case->deceased?->interment?->format('Y-m-d') ?? '-' }}</td>
                    <td>
                        <div class="{{ ($case->verification_status ?? 'VERIFIED') === 'VERIFIED' ? 'text-emerald-700' : (($case->verification_status ?? 'VERIFIED') === 'DISPUTED' ? 'text-red-700' : 'text-amber-700') }} text-xs font-semibold uppercase tracking-wide">
                            {{ $case->verification_status ?? 'VERIFIED' }}
                        </div>
                        @if($case->verification_note)
                            <div class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($case->verification_note, 80) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="{{ in_array($case->case_status, ['DRAFT', 'ACTIVE']) ? 'status-pill-warning' : 'status-pill-success' }}">
                            {{ $case->case_status }}
                        </span>
                    </td>
                    <td>
                        <span class="{{
                            $case->payment_status === 'PAID'
                                ? 'status-pill-success'
                                : ($case->payment_status === 'PARTIAL' ? 'status-pill-warning' : 'status-pill-danger')
                        }}">
                            {{ $case->payment_status }}
                        </span>
                    </td>
                    <td>{{ number_format($case->total_amount, 2) }}</td>
                    <td>{{ number_format((float) $case->total_paid, 2) }}</td>
                    <td>{{ number_format((float) $case->balance_amount, 2) }}</td>
                    <td class="min-w-[14rem]">
                        @if($case->entry_source === 'OTHER_BRANCH')
                            <form method="POST" action="{{ route('admin.cases.verification', $case) }}" class="grid gap-2 md:grid-cols-2 items-center">
                                @csrf
                                @method('PATCH')
                                <select name="verification_status" class="form-select text-xs">
                                    <option value="VERIFIED">Mark Verified</option>
                                    <option value="DISPUTED">Mark Disputed</option>
                                </select>
                                <input type="text" name="verification_note" class="form-input text-xs" placeholder="Note (required for disputed)">
                                <button class="btn btn-primary-custom btn-sm md:col-span-2 bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white">
                                    Save
                                </button>
                            </form>
                        @else
                            <span class="text-xs text-slate-500">Main intake auto-verified</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="py-6 text-center text-slate-500">No case records found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $cases->links() }}
</div>
@endsection



