@extends('layouts.panel')

@section('page_title', ($recordScope ?? 'main') === 'other' ? 'Branch Report' : 'Completed Cases')

@section('content')
<div class="records-page">
@if(session('success'))
    <div class="flash-success">
        {{ session('success') }}
    </div>
@endif

@php($isOtherView = ($viewType ?? (($recordScope ?? 'main') === 'other' ? 'other' : 'main')) === 'other')
@php($listingRoute = $isOtherView ? route('funeral-cases.other-reports') : route('funeral-cases.completed'))

<div class="{{ $isOtherView ? 'flash-warning' : 'flash-info' }}">
    {{ $isOtherView ? 'Viewing: Branch Report records' : 'Viewing: Completed Cases' }}
</div>

<section class="table-system-card">
    <div class="table-system-head">
        <h2 class="table-system-title">{{ $isOtherView ? 'Branch Report' : 'Completed Case Records' }}</h2>
        <p class="table-system-copy">
            {{ $isOtherView
                ? 'Review completed external branch submissions and verification status.'
                : 'Browse completed main-branch cases, including payment standing and follow-up actions.' }}
        </p>
    </div>

    <div class="table-system-toolbar">
        <form method="GET" action="{{ $listingRoute }}" class="table-toolbar" data-table-toolbar data-search-debounce="400">
            <div class="table-toolbar-field">
                <label for="completed-search" class="table-toolbar-label">Search</label>
                <input
                    id="completed-search"
                    name="q"
                    value="{{ request('q') }}"
                    class="form-input table-toolbar-search"
                    data-table-search
                    placeholder="{{ $isOtherView ? 'Search case, client, deceased, or reporter...' : 'Search client, deceased, or case...' }}"
                    pattern="[A-Za-zÀ-öø-ÿĀ-žḀ-ỿ0-9.'\- ]+"
                    title="Letters (including accented like Ñ, É), numbers, spaces, apostrophes, dots, and hyphens only">
            </div>

            @if(!$isOtherView)
                <div class="table-toolbar-field">
                    <label for="completed-date-from" class="table-toolbar-label">Request From</label>
                    <input id="completed-date-from" type="date" name="request_date_from" value="{{ request('request_date_from') }}" class="form-input table-toolbar-select">
                </div>
                <div class="table-toolbar-field">
                    <label for="completed-date-to" class="table-toolbar-label">Request To</label>
                    <input id="completed-date-to" type="date" name="request_date_to" value="{{ request('request_date_to') }}" class="form-input table-toolbar-select">
                </div>
            @else
                @if(!empty($canEncodeAnyBranch) && $canEncodeAnyBranch)
                    <div class="table-toolbar-field">
                        <label for="branch-filter" class="table-toolbar-label">Branch</label>
                        <select id="branch-filter" name="branch_id" class="form-select table-toolbar-select">
                            <option value="">All Other Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->branch_code }} - {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="table-toolbar-field">
                    <label for="verification-filter" class="table-toolbar-label">Verification</label>
                    <select id="verification-filter" name="verification_status" class="form-select table-toolbar-select">
                        <option value="">All Verification</option>
                        <option value="PENDING" {{ ($verificationStatus ?? '') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                        <option value="VERIFIED" {{ ($verificationStatus ?? '') === 'VERIFIED' ? 'selected' : '' }}>Verified</option>
                        <option value="DISPUTED" {{ ($verificationStatus ?? '') === 'DISPUTED' ? 'selected' : '' }}>Disputed</option>
                    </select>
                </div>

                <div class="table-toolbar-field">
                    <label for="reported-from" class="table-toolbar-label">Reported From</label>
                    <input id="reported-from" type="date" name="reported_from" value="{{ $reportedFrom ?? '' }}" class="form-input table-toolbar-select">
                </div>

                <div class="table-toolbar-field">
                    <label for="reported-to" class="table-toolbar-label">Reported To</label>
                    <input id="reported-to" type="date" name="reported_to" value="{{ $reportedTo ?? '' }}" class="form-input table-toolbar-select">
                </div>
            @endif

            <div class="table-toolbar-reset-wrap">
                <span class="table-toolbar-label opacity-0 select-none">Actions</span>
                <div class="filter-actions">
                    <a href="{{ $listingRoute }}" class="btn-outline btn-filter-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset</span>
                    </a>
                    <button type="submit" class="btn-secondary">
                        <i class="bi bi-funnel"></i>
                        <span>Apply</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-system-list">
        <div class="table-system-list-header">
            <div>
                <div class="table-system-list-title">{{ $isOtherView ? 'Branch Report Records' : 'Completed Case Records' }}</div>
                <div class="table-system-list-copy">
                    {{ $isOtherView
                        ? 'Review completed external reports and their verification status.'
                        : 'Review completed cases, package totals, and payment status.' }}
                </div>
            </div>
        </div>

        <div class="table-wrapper table-system-wrap">
            <table class="table-base table-system-table">
            <thead>
                <tr>
                    <th class="text-left">Case ID</th>
                    <th class="text-left">{{ $isOtherView ? 'Encoded Date' : 'Request Date' }}</th>
                    <th class="text-left">Branch</th>
                    <th class="text-left">Client</th>
                    <th class="text-left">Deceased</th>
                    <th class="text-left">Interment Date</th>
                    @if($isOtherView)
                        <th class="text-left">Submitted By</th>
                        <th class="text-left">Submission Date</th>
                        <th class="text-left">Report Status</th>
                    @endif
                    <th class="text-left">Package</th>
                    <th class="table-col-number">Total</th>
                    <th class="table-col-number">Total Paid</th>
                    <th class="text-left">Payment Status</th>
                    <th class="table-col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                <tr>
                    <td class="table-primary">{{ $case->case_code }}</td>
                    <td>{{ $case->service_requested_at?->format('Y-m-d') ?? $case->created_at?->format('Y-m-d') }}</td>
                    <td>{{ $case->branch?->branch_code ?? '-' }}</td>
                    <td>{{ $case->client?->full_name ?? '-' }}</td>
                    <td>{{ $case->deceased?->full_name ?? '-' }}</td>
                    <td>{{ $case->interment_at?->format('Y-m-d h:i A') ?? '-' }}</td>
                    @if($isOtherView)
                        <td>{{ $case->reporter_name ?? '-' }}</td>
                        <td>{{ $case->reported_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>
                            <x-status-badge :status="$case->verification_status ?? 'VERIFIED'" />
                        </td>
                    @endif
                    <td>{{ $case->service_package ?? '-' }}</td>
                    <td class="table-col-number">{{ number_format((float) $case->total_amount, 2) }}</td>
                    <td class="table-col-number">{{ number_format((float) $case->total_paid, 2) }}</td>
                    <td>
                        <x-status-badge :status="$case->payment_status" />
                        @if($case->payment_status === 'PARTIAL')
                            <div class="table-secondary mt-1">With Balance</div>
                        @endif
                    </td>
                    <td class="table-col-actions">
                        <div class="table-row-actions">
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
                                        href="{{ route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}"
                                        class="row-action-item"
                                        data-row-menu-item
                                    >
                                        <i class="bi bi-eye"></i>
                                        <span>View</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isOtherView ? 14 : 11 }}" class="table-system-empty">
                        No completed cases found.
                    </td>
                </tr>
            @endforelse
            </tbody>
            </table>
        </div>
    </div>

    <div class="table-system-pagination">
        {{ $cases->links() }}
    </div>
</section>

</div>
@endsection
