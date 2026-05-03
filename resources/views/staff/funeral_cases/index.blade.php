@extends('layouts.panel')

@section('page_title', 'Case Records')
@section('page_desc', 'Manage ongoing and completed case records.')

@section('content')
@php
    $activeTab = $currentTab ?? 'active';
    $isActiveTab = $activeTab === 'active';
    $recordScope = $recordScope ?? 'main';
    $quickFilter = $quickFilter ?? 'all';
    $sort = $sort ?? 'newest';
    $selectedDateRange = request('date_range', 'any');
    $usesCustomDate = $selectedDateRange === 'custom'
        || (!request()->filled('date_range') && (request()->filled('request_date_from') || request()->filled('request_date_to')));
    if ($usesCustomDate) {
        $selectedDateRange = 'custom';
    }
    $openWizard = request()->boolean('open_wizard') && $isActiveTab;
    $resetUrl = route('funeral-cases.index', ['tab' => $activeTab, 'record_scope' => $recordScope]);
    $caseRecordsBranchLabel = $operationalBranch
        ? trim(($operationalBranch->branch_code ?? '') . ' - ' . ($operationalBranch->branch_name ?? ''))
        : 'Assigned Branch';
    $caseRecordsChips = collect([
        ['icon' => 'bi-lock-fill', 'label' => 'Branch: ' . $caseRecordsBranchLabel, 'locked' => true],
    ]);
    if (filled(request('q'))) {
        $caseRecordsChips->push(['icon' => 'bi-search', 'label' => 'Search: ' . request('q')]);
    }
    if (filled(request('payment_status'))) {
        $caseRecordsChips->push(['icon' => 'bi-wallet2', 'label' => 'Payment: ' . \Illuminate\Support\Str::headline(strtolower(request('payment_status')))]);
    }
    if (filled(request('case_status'))) {
        $caseRecordsChips->push(['icon' => 'bi-clipboard-check', 'label' => 'Case: ' . \Illuminate\Support\Str::headline(strtolower(request('case_status')))]);
    }
    if (filled(request('service_type'))) {
        $caseRecordsChips->push(['icon' => 'bi-tag', 'label' => 'Service: ' . request('service_type')]);
    }
    if (filled(request('package_id'))) {
        $selectedPackage = ($packages ?? collect())->firstWhere('id', (int) request('package_id'));
        $caseRecordsChips->push(['icon' => 'bi-box', 'label' => 'Package: ' . ($selectedPackage?->name ?? 'Selected Package')]);
    }
    if (filled($datePreset ?? null)) {
        $caseRecordsChips->push(['icon' => 'bi-calendar3', 'label' => 'Encoded: ' . \Illuminate\Support\Str::headline(strtolower((string) $datePreset))]);
    }
    if (filled($intermentFrom ?? null) || filled($intermentTo ?? null)) {
        $caseRecordsChips->push(['icon' => 'bi-calendar-event', 'label' => 'Interment: ' . (($intermentFrom ?? null) ?: 'Start') . ' - ' . (($intermentTo ?? null) ?: 'Today')]);
    }

    $activeTabUrl = route('funeral-cases.index', array_filter([
        'tab' => 'active',
        'record_scope' => $recordScope,
        'q' => request('q'),
        'case_status' => request('case_status'),
        'payment_status' => request('payment_status'),
        'service_type' => request('service_type'),
        'package_id' => request('package_id'),
        'date_preset' => request('date_preset'),
        'date_from' => request('date_from'),
        'date_to' => request('date_to'),
        'date_range' => request('date_range'),
        'request_date_from' => request('request_date_from'),
        'request_date_to' => request('request_date_to'),
        'interment_from' => request('interment_from'),
        'interment_to' => request('interment_to'),
        'sort' => 'newest',
        'quick_filter' => 'all',
    ], fn ($value) => !is_null($value) && $value !== ''));

    $completedTabUrl = route('funeral-cases.index', array_filter([
        'tab' => 'completed',
        'record_scope' => $recordScope,
        'q' => request('q'),
        'case_status' => request('case_status'),
        'payment_status' => request('payment_status'),
        'service_type' => request('service_type'),
        'package_id' => request('package_id'),
        'date_preset' => request('date_preset'),
        'date_from' => request('date_from'),
        'date_to' => request('date_to'),
        'date_range' => request('date_range'),
        'request_date_from' => request('request_date_from'),
        'request_date_to' => request('request_date_to'),
        'interment_from' => request('interment_from'),
        'interment_to' => request('interment_to'),
        'sort' => 'newest',
        'quick_filter' => 'all',
    ], fn ($value) => !is_null($value) && $value !== ''));
@endphp

<div class="records-page">
    @if(session('success'))
        <div class="flash-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('summary') && $isActiveTab)
        <div class="list-card p-5 text-sm text-slate-700">
            <div class="mb-3 text-base font-semibold text-slate-900">Last Saved Summary</div>
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                <div><span class="font-semibold text-slate-900">Package:</span> {{ session('summary.package') }}</div>
                <div><span class="font-semibold text-slate-900">Subtotal:</span> {{ number_format(session('summary.subtotal'), 2) }}</div>
                <div><span class="font-semibold text-slate-900">Discount:</span> {{ number_format(session('summary.discount'), 2) }}</div>
                <div><span class="font-semibold text-slate-900">Discount Rule:</span> {{ session('summary.discount_source', 'NONE') }}</div>
                <div><span class="font-semibold text-slate-900">Total:</span> {{ number_format(session('summary.total'), 2) }}</div>
                <div><span class="font-semibold text-slate-900">Payment Status:</span> {{ session('summary.payment_status') }}</div>
            </div>
        </div>
    @endif

    @if(!empty($canEncodeAnyBranch) && $canEncodeAnyBranch)
        <div class="flash-info">
            Other-branch records remain completed-only and are managed under <strong>Branch Reports</strong>.
        </div>
    @endif

    <section class="table-system-card">

        @if($openWizard)
            <div class="p-4 md:p-5">
                @php
                    $showCancelButton = false;
                    $cancelUrl = route('funeral-cases.index', ['tab' => 'active', 'record_scope' => $recordScope]);
                    $formAction = route('intake.main.store');
                    $entryMode = 'main';
                @endphp
                @include('staff.intake._form')
            </div>
        @else
            <div class="table-system-toolbar case-records-controls">
                @include('partials.case_filter_toolbar', [
                    'action' => route('funeral-cases.index'),
                    'resetUrl' => $resetUrl,
                    'branchMode' => 'locked',
                    'assignedBranch' => $operationalBranch ?? null,
                    'branchId' => $operationalBranch?->id ?? auth()->user()?->branch_id,
                    'branches' => $branches,
                    'datePreset' => $datePreset ?? '',
                    'dateFrom' => $dateFrom ?? null,
                    'dateTo' => $dateTo ?? null,
                    'intermentFrom' => $intermentFrom ?? null,
                    'intermentTo' => $intermentTo ?? null,
                    'serviceTypes' => $serviceTypes ?? collect(),
                    'packages' => $packages ?? collect(),
                    'hiddenInputs' => ['tab' => $activeTab, 'record_scope' => $recordScope, 'sort' => $sort],
                    'showVerificationStatus' => false,
                    'showPackage' => true,
                    'showEncodedBy' => false,
                    'showBranchChip' => true,
                    'showInlineChips' => false,
                ])

                <form id="caseRecordsFilterForm" method="GET" action="{{ route('funeral-cases.index') }}" class="table-toolbar hidden" data-table-toolbar data-search-debounce="400">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input type="hidden" name="record_scope" value="{{ $recordScope }}">
                    <input type="hidden" name="branch_id" value="{{ $operationalBranch?->id ?? auth()->user()?->branch_id }}">

                    <div class="table-toolbar-field">
                        <label for="case-record-search" class="table-toolbar-label">Search</label>
                        <input
                            id="case-record-search"
                            name="q"
                            value="{{ request('q') }}"
                            class="form-input table-toolbar-search"
                            data-table-search
                            placeholder="Search case, client, or deceased..."
                            pattern="[A-Za-zÀ-öø-ÿĀ-žḀ-ỿ0-9.'\- ]+"
                            title="Letters (including accented like Ñ, É), numbers, spaces, apostrophes, dots, and hyphens only"
                        >
                    </div>

                    <div class="table-toolbar-field">
                        <label for="case-record-payment-status" class="table-toolbar-label">Payment Status</label>
                        <select id="case-record-payment-status" name="payment_status" class="form-select table-toolbar-select">
                            <option value="">All Payment Status</option>
                            <option value="UNPAID" @selected(request('payment_status') === 'UNPAID')>Unpaid</option>
                            <option value="PARTIAL" @selected(request('payment_status') === 'PARTIAL')>Partial</option>
                            <option value="PAID" @selected(request('payment_status') === 'PAID')>Paid</option>
                        </select>
                    </div>

                    <div class="table-toolbar-field">
                        <label for="case-record-date-range" class="table-toolbar-label">Date</label>
                        <select id="case-record-date-range" name="date_range" class="form-select table-toolbar-select">
                            <option value="any" @selected($selectedDateRange === 'any')>Any Time</option>
                            <option value="today" @selected($selectedDateRange === 'today')>Today</option>
                            <option value="7d" @selected($selectedDateRange === '7d')>Last 7 Days</option>
                            <option value="30d" @selected($selectedDateRange === '30d')>Last 30 Days</option>
                            <option value="this_month" @selected($selectedDateRange === 'this_month')>This Month</option>
                            <option value="custom" @selected($selectedDateRange === 'custom')>Custom Range</option>
                        </select>
                    </div>

                    <div class="table-toolbar-field" data-custom-date-field @if(!$usesCustomDate) hidden @endif>
                        <label for="case-record-date-from" class="table-toolbar-label">Date From</label>
                        <input
                            id="case-record-date-from"
                            type="date"
                            name="request_date_from"
                            value="{{ request('request_date_from') }}"
                            class="form-input table-toolbar-select"
                            data-custom-date-input
                            @if(!$usesCustomDate) disabled @endif
                        >
                    </div>

                    <div class="table-toolbar-field" data-custom-date-field @if(!$usesCustomDate) hidden @endif>
                        <label for="case-record-date-to" class="table-toolbar-label">To Date</label>
                        <input
                            id="case-record-date-to"
                            type="date"
                            name="request_date_to"
                            value="{{ request('request_date_to') }}"
                            class="form-input table-toolbar-select"
                            data-custom-date-input
                            @if(!$usesCustomDate) disabled @endif
                        >
                    </div>

                    <div class="table-toolbar-field">
                        <label for="case-record-sort" class="table-toolbar-label">Sort</label>
                        <select id="case-record-sort" name="sort" class="form-select table-toolbar-sort" data-table-sort>
                            @foreach(($sortOptions ?? []) as $sortKey => $sortLabel)
                                <option value="{{ $sortKey }}" @selected($sort === $sortKey)>{{ $sortLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-toolbar-reset-wrap">
                        <span class="table-toolbar-label opacity-0 select-none">Actions</span>
                        <div class="filter-actions">
                            <a href="{{ route('funeral-cases.index', ['tab' => $activeTab, 'record_scope' => $recordScope]) }}" class="btn-outline btn-filter-reset">
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

            <div class="case-records-tabs-row">
                <div class="table-quick-tabs case-records-tabs" role="tablist" aria-label="Case record tabs">
                    <a
                        href="{{ $activeTabUrl }}"
                        role="tab"
                        aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                        class="table-quick-tab {{ $isActiveTab ? 'table-quick-tab-active' : '' }}"
                    >
                        Active Cases
                    </a>
                    <a
                        href="{{ $completedTabUrl }}"
                        role="tab"
                        aria-selected="{{ $isActiveTab ? 'false' : 'true' }}"
                        class="table-quick-tab {{ $isActiveTab ? '' : 'table-quick-tab-active' }}"
                    >
                        Completed Cases
                    </a>
                </div>
            </div>

            <div class="case-records-quick-row">
                <div class="table-quick-tabs table-system-quick-tabs" aria-label="Quick filters">
                    @foreach(($quickFilterOptions ?? []) as $filterKey => $filterLabel)
                        <a
                            href="{{ route('funeral-cases.index', array_filter(array_merge(request()->except(['page', 'quick_filter', 'open_wizard']), ['quick_filter' => $filterKey]), fn ($value) => !is_null($value) && $value !== '')) }}"
                            class="table-quick-tab {{ $quickFilter === $filterKey ? 'table-quick-tab-active' : '' }}"
                        >
                            {{ $filterLabel }}
                        </a>
                    @endforeach
                </div>

                <div class="case-compact-inline-chips case-records-quick-chips" aria-label="Applied branch and filters">
                    @foreach($caseRecordsChips as $chip)
                        <span class="case-compact-chip {{ !empty($chip['locked']) ? 'case-compact-chip-locked' : '' }}">
                            <i class="bi {{ $chip['icon'] }}"></i>{{ $chip['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="table-system-list">
                <div class="table-system-list-header">
                    <div>
                        <div class="table-system-list-title">{{ $isActiveTab ? 'Active Case Records' : 'Completed Case Records' }}</div>
                        <div class="table-system-list-copy">
                            {{ $isActiveTab
                                ? 'Track ongoing case activity, balances, and workflow status.'
                                : 'Review completed records, payment standing, and follow-up actions.' }}
                        </div>
                    </div>
                </div>

                <div class="table-wrapper table-system-wrap">
                    <table class="table-base table-system-table case-records-table records-worklist-table">
                        <colgroup>
                            <col class="records-col-case">
                            <col class="records-col-family">
                            <col class="records-col-service">
                            <col class="records-col-schedule">
                            <col class="records-col-financials">
                            <col class="records-col-case-status">
                            <col class="records-col-payment-status">
                            <col class="records-col-actions">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-left">Case</th>
                                <th class="text-left">Family / Client</th>
                                <th class="text-left">Service</th>
                                <th class="text-left">Schedule</th>
                                <th class="table-col-number">Financials</th>
                                <th class="table-status-col">Case Status</th>
                                <th class="table-status-col table-payment-status-col">Payment Status</th>
                                <th class="table-col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cases as $case)
                                @php
                                    $intermentAt = $case->interment_at
                                        ?? $case->serviceDetail?->internment_date;
                                    if (is_string($intermentAt)) {
                                        $intermentAt = \Carbon\Carbon::parse($intermentAt);
                                    }
                                    $needsAttention = $case->payment_status === 'UNPAID'
                                        || (float) $case->balance_amount > 0;
                                @endphp
                                <tr
                                    class="{{ $needsAttention ? 'row-needs-attention' : '' }}"
                                    data-clickable-row
                                    data-row-href="{{ route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}"
                                    tabindex="0"
                                    role="link"
                                    aria-label="Open case details for {{ $case->case_code }}"
                                >
                                    <td>
                                        <div class="table-primary whitespace-nowrap records-case-code">{{ $case->case_code }}</div>
                                        <div class="table-secondary">{{ $case->branch?->branch_code ?? 'Assigned Branch' }} &middot; Encoded {{ $case->created_at?->format('M d, Y') }}</div>
                                    </td>
                                    <td>
                                        <div class="table-primary">{{ \Illuminate\Support\Str::limit($case->deceased?->full_name ?? '-', 30) }}</div>
                                        <div class="table-secondary">
                                            {{ \Illuminate\Support\Str::limit($case->client?->full_name ?? '-', 28) }}
                                            @if($isActiveTab && $case->client?->contact_number)
                                                &middot; {{ $case->client->contact_number }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-primary">{{ $case->service_type ?? '-' }}</div>
                                        <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->package?->name ?? $case->service_package ?? '-', 30) }}</div>
                                    </td>
                                    <td>
                                        <div class="table-primary whitespace-nowrap">{{ $intermentAt ? $intermentAt->format('M d, Y') : '-' }}</div>
                                        <div class="table-secondary">{{ $intermentAt ? $intermentAt->format('h:i A') : 'Interment time' }}</div>
                                    </td>
                                    <td class="table-col-number">
                                        <div class="table-primary table-financial-total whitespace-nowrap">{{ number_format((float) $case->total_amount, 2) }}</div>
                                        <div class="table-secondary table-financial-breakdown whitespace-nowrap">Paid {{ number_format((float) $case->total_paid, 2) }} &middot; Bal {{ number_format((float) $case->balance_amount, 2) }}</div>
                                    </td>
                                    <td class="table-status-cell">
                                        <x-status-badge :status="$case->case_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->case_status))" />
                                    </td>
                                    <td class="table-status-cell table-payment-status-cell">
                                        <x-status-badge :status="$case->payment_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->payment_status))" class="table-payment-status-badge" />
                                    </td>

                                    <td class="table-col-actions">
                                        <div class="table-row-actions">
                                            @unless($isActiveTab)
                                                <a
                                                    href="{{ route('payments.history', ['q' => $case->case_code]) }}"
                                                    class="action-chip table-row-actions-visible"
                                                    data-no-row-click
                                                >
                                                    <i class="bi bi-clock-history"></i>
                                                    <span>Payments</span>
                                                </a>
                                            @endunless
                                            <div class="row-action-menu" data-row-menu>
                                                <button
                                                    type="button"
                                                    class="row-action-trigger"
                                                    data-row-menu-trigger
                                                    data-no-row-click
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
                                                        data-row-view-trigger
                                                    >
                                                        <i class="bi bi-eye"></i>
                                                        <span>Open details</span>
                                                    </a>

                                                    @unless($isActiveTab)
                                                        <a
                                                            href="{{ route('payments.history', ['q' => $case->case_code]) }}"
                                                            class="row-action-item"
                                                            data-row-menu-item
                                                        >
                                                            <i class="bi bi-clock-history"></i>
                                                            <span>Payment Monitoring</span>
                                                        </a>
                                                    @endunless

                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="table-system-empty">
                                        No case records found.
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
        @endif
    </section>

    <div id="caseEditOverlay" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-200 panel-overlay-content">
        <div id="caseEditSheet" class="relative w-[90vw] max-w-4xl max-h-[94vh] rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0" style="background:var(--card);border:1px solid var(--border)">
            <button id="caseEditClose" type="button" class="absolute top-4 right-4 z-10 inline-flex items-center justify-center w-9 h-9 rounded-xl transition-colors focus:outline-none shadow-sm" style="background:var(--card);border:1px solid var(--border);color:var(--ink-muted)">
                <i class="bi bi-x-lg" style="font-size:.8rem"></i>
            </button>
            <div id="caseEditContent" class="overflow-y-auto max-h-[84vh]" style="padding:16px;background:var(--card);">
                <div class="flex flex-col items-center justify-center py-16 gap-3">
                    <div class="w-7 h-7 rounded-full animate-spin" style="border:2px solid var(--border);border-top-color:var(--ink-muted)"></div>
                    <span class="text-sm" style="color:var(--ink-muted)">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const filterForm = document.getElementById('caseRecordsFilterForm');
        if (filterForm) {
            const dateRangeSelect = filterForm.querySelector('select[name="date_range"]');
            const customDateFields = filterForm.querySelectorAll('[data-custom-date-field]');
            const customDateInputs = filterForm.querySelectorAll('[data-custom-date-input]');

            const toggleCustomDate = () => {
                const isCustom = dateRangeSelect && dateRangeSelect.value === 'custom';
                customDateFields.forEach((field) => {
                    if (isCustom) {
                        field.removeAttribute('hidden');
                    } else {
                        field.setAttribute('hidden', '');
                    }
                });
                customDateInputs.forEach((input) => {
                    input.disabled = !isCustom;
                    if (!isCustom) {
                        input.value = '';
                    }
                });
            };

            if (dateRangeSelect) {
                dateRangeSelect.addEventListener('change', toggleCustomDate);
            }
            toggleCustomDate();
        }

        const overlay = document.getElementById('caseEditOverlay');
        const sheet = document.getElementById('caseEditSheet');
        const content = document.getElementById('caseEditContent');
        const closeBtn = document.getElementById('caseEditClose');
        const openLinks = [...document.querySelectorAll('.open-edit-modal, .open-view-modal')];
        const transitionMs = 180;
        let hideTimer = null;
        let activeRequestId = 0;

        const loadingMarkup = `
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full animate-spin" style="border:2px solid var(--border);border-top-color:var(--ink-muted)"></div>
                <span class="text-sm" style="color:var(--ink-muted)">Loading...</span>
            </div>`;

        const dispatchUiReset = () => {
            document.dispatchEvent(new CustomEvent('panel-ui:reset'));
        };

        const syncPageScrollLock = (isOpen) => {
            document.documentElement.classList.toggle('overflow-hidden', !!isOpen);
            document.body.classList.toggle('overflow-hidden', !!isOpen);
        };

        const resetContent = () => {
            if (content) {
                content.innerHTML = loadingMarkup;
            }
        };

        const showShell = () => {
            if (!overlay || !sheet) return;
            window.clearTimeout(hideTimer);
            dispatchUiReset();
            overlay.classList.remove('hidden');
            syncPageScrollLock(true);
            requestAnimationFrame(() => {
                sheet.classList.remove('scale-95', 'opacity-0');
                sheet.classList.add('scale-100', 'opacity-100');
                overlay.classList.add('opacity-100');
            });
        };

        const hideShell = () => {
            if (!overlay || !sheet || !content) return;
            activeRequestId += 1;
            window.clearTimeout(hideTimer);
            sheet.classList.add('scale-95', 'opacity-0');
            sheet.classList.remove('scale-100', 'opacity-100');
            overlay.classList.remove('opacity-100');
            syncPageScrollLock(false);
            dispatchUiReset();
            hideTimer = window.setTimeout(() => {
                overlay.classList.add('hidden');
                syncPageScrollLock(false);
                resetContent();
            }, transitionMs);
        };

        const loadContent = async (url) => {
            if (!content) return;
            const requestId = ++activeRequestId;
            resetContent();
            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const html = await res.text();
                if (requestId !== activeRequestId || overlay.classList.contains('hidden')) return;
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const form = doc.querySelector('#caseEditForm');
                const view = doc.querySelector('#caseViewContent');
                const payload = form || view;
                if (payload) {
                    content.innerHTML = payload.outerHTML;
                    const scripts = [...doc.querySelectorAll('script')];
                    scripts.forEach((oldScript) => {
                        const script = document.createElement('script');
                        if (oldScript.src) {
                            script.src = oldScript.src;
                        } else {
                            script.textContent = oldScript.textContent;
                        }
                        content.appendChild(script);
                    });
                } else {
                    content.innerHTML = html;
                }
            } catch (err) {
                if (requestId !== activeRequestId || overlay.classList.contains('hidden')) return;
                content.innerHTML = `<div class="p-6 text-sm text-rose-600">Unable to load. Please try again.</div>`;
            }
        };

        const openModal = (url) => {
            showShell();
            loadContent(url);
        };

        openLinks.forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const url = link.dataset.url || link.href;
                openModal(url);
            });
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', hideShell);
        }

        if (overlay) {
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) hideShell();
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && overlay && !overlay.classList.contains('hidden')) {
                hideShell();
            }
        });
    })();
</script>
@endsection
