@extends('layouts.panel')

@section('page_title', ($recordScope ?? 'main') === 'other' ? 'Other Branch Completed Reports' : 'Completed Cases')

@section('content')
@if(session('success'))
    <div class="flash-success">
        {{ session('success') }}
    </div>
@endif

@php($isOtherView = ($viewType ?? (($recordScope ?? 'main') === 'other' ? 'other' : 'main')) === 'other')
@php($listingRoute = $isOtherView ? route('funeral-cases.other-reports') : route('funeral-cases.completed'))

<div class="{{ $isOtherView ? 'flash-warning' : 'flash-info' }}">
    {{ $isOtherView ? 'Viewing: Other Branch Completed Reports' : 'Viewing: Completed Cases' }}
</div>

<div class="filter-panel">
    <form method="GET" action="{{ $listingRoute }}" class="space-y-4">
        <div class="filter-grid">
            <input
                name="q"
                value="{{ request('q') }}"
                class="form-input w-full md:w-80"
                placeholder="{{ $isOtherView ? 'Search case, client, deceased, or reporter...' : 'Search client, deceased, or case...' }}"
                pattern="[A-Za-z0-9.'\\- ]+"
                title="Letters, numbers, spaces, apostrophes, periods, and hyphens only"
                onchange="this.form.submit()">

            @if(!$isOtherView)
                <input type="date" name="request_date_from" value="{{ request('request_date_from') }}" class="form-input w-full md:w-44" title="Request date from" onchange="this.form.submit()">
                <input type="date" name="request_date_to" value="{{ request('request_date_to') }}" class="form-input w-full md:w-44" title="Request date to" onchange="this.form.submit()">
                <div></div>
                <div></div>
            @else
                @if(!empty($canEncodeAnyBranch) && $canEncodeAnyBranch)
                    <select name="branch_id" class="form-select w-full md:w-56" onchange="this.form.submit()">
                        <option value="">All Other Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->branch_code }} - {{ $branch->branch_name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select name="verification_status" class="form-select w-full md:w-48" onchange="this.form.submit()">
                    <option value="">All Verification</option>
                    <option value="PENDING" {{ ($verificationStatus ?? '') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                    <option value="VERIFIED" {{ ($verificationStatus ?? '') === 'VERIFIED' ? 'selected' : '' }}>Verified</option>
                    <option value="DISPUTED" {{ ($verificationStatus ?? '') === 'DISPUTED' ? 'selected' : '' }}>Disputed</option>
                </select>

                <input type="date" name="reported_from" value="{{ $reportedFrom ?? '' }}" class="form-input w-full md:w-44" title="Reported from" onchange="this.form.submit()">
                <input type="date" name="reported_to" value="{{ $reportedTo ?? '' }}" class="form-input w-full md:w-44" title="Reported to" onchange="this.form.submit()">
            @endif
        </div>

        <div class="filter-actions">
            <a href="{{ $listingRoute }}" class="btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="list-card mt-4">
    <div class="list-card-header">
        <div>
            <div class="list-card-title">{{ $isOtherView ? 'Other Branch Completed Reports' : 'Completed Case Records' }}</div>
            <div class="list-card-copy">
                {{ $isOtherView
                    ? 'Review completed external reports and their verification status.'
                    : 'Browse completed main-branch cases, including those with outstanding balances.' }}
            </div>
        </div>
    </div>

    <div class="table-wrapper rounded-none border-0">
        <table class="table-base text-sm">
            <thead>
                <tr>
                    <th class="text-left">Case ID</th>
                    <th class="text-left">{{ $isOtherView ? 'Encoded Date' : 'Request Date' }}</th>
                    <th class="text-left">Branch</th>
                    <th class="text-left">Client</th>
                    <th class="text-left">Deceased</th>
                    <th class="text-left">Interment</th>
                    @if(!$isOtherView)
                    @else
                        <th class="text-left">Reporter</th>
                        <th class="text-left">Reported At</th>
                        <th class="text-left">Verification</th>
                    @endif
                    <th class="text-left">Package</th>
                    <th class="text-left">Total</th>
                    <th class="text-left">Total Paid</th>
                    <th class="text-left">Payment Status</th>
                    <th class="text-left">Actions</th>
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
                    @if(!$isOtherView)
                    @else
                        <td>{{ $case->reporter_name ?? '-' }}</td>
                        <td>{{ $case->reported_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>
                            <span class="{{
                                ($case->verification_status ?? 'VERIFIED') === 'VERIFIED'
                                    ? 'status-pill-success'
                                    : ((($case->verification_status ?? 'VERIFIED') === 'DISPUTED') ? 'status-pill-danger' : 'status-pill-warning')
                            }}">
                                {{ $case->verification_status ?? 'VERIFIED' }}
                            </span>
                        </td>
                    @endif
                    <td>{{ $case->service_package ?? '-' }}</td>
                    <td>{{ number_format($case->total_amount, 2) }}</td>
                    <td>{{ number_format((float) $case->total_paid, 2) }}</td>
                    <td>
                        <span class="{{
                            $case->payment_status === 'PAID'
                                ? 'status-pill-success'
                                : ($case->payment_status === 'PARTIAL' ? 'status-pill-warning' : 'status-pill-danger')
                        }}">
                            {{ $case->payment_status }}
                        </span>
                        @if($case->payment_status === 'PARTIAL')
                            <div class="text-[11px] font-semibold text-amber-600 mt-1">With Balance</div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}" class="table-action-link">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isOtherView ? 14 : 13 }}" class="py-6 text-center text-slate-500">
                        No completed cases found.
                    </td>
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

