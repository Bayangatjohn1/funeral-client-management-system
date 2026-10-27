@extends('layouts.panel')

@section('page_title', 'Master Case Records')
@section('page_desc', 'Monitor branch activity, case status, and payment health from one admin worklist.')
@section('hide_layout_topbar', '1')

@section('content')
<div class="admin-table-page master-records-page">
    @php
        $isBranchAdmin = auth()->user()?->isBranchAdmin() ?? false;
        $resolvedDatePreset = $datePreset ?? 'ANY';
        $isCustomDate = $resolvedDatePreset === 'CUSTOM';
        $sort = $sort ?? 'newest';
        $secondaryFiltersActive = filled($paymentStatus ?? null)
            || $sort !== 'newest'
            || $resolvedDatePreset !== 'ANY'
            || filled($intermentFrom ?? null)
            || filled($intermentTo ?? null);
        $activeFilterCount = collect([
            filled($q ?? null),
            filled($branchId ?? null),
            filled($caseStatus ?? null),
            filled($paymentStatus ?? null),
            $sort !== 'newest',
            $resolvedDatePreset !== 'ANY',
            filled($intermentFrom ?? null),
            filled($intermentTo ?? null),
        ])->filter()->count();
        $presetLabel = match ($resolvedDatePreset) {
            'TODAY' => 'Today',
            'LAST_7_DAYS' => 'Last 7 Days',
            'LAST_30_DAYS' => 'Last 30 Days',
            'THIS_MONTH' => 'This Month',
            'CUSTOM' => 'Custom Range',
            default => 'All Dates',
        };
        $adminMasterChips = collect();
        if ($isBranchAdmin && $branchId) {
            $adminBranch = $branches->firstWhere('id', (int) $branchId) ?? $branches->first();
            $adminMasterChips->push([
                'icon' => 'bi-lock-fill',
                'label' => 'Branch: ' . ($adminBranch ? trim(($adminBranch->branch_code ?? '') . ' - ' . ($adminBranch->branch_name ?? '')) : 'Assigned Branch'),
                'locked' => true,
            ]);
        } elseif (filled($branchId)) {
            $adminBranch = $branches->firstWhere('id', (int) $branchId);
            $adminMasterChips->push([
                'icon' => 'bi-building',
                'label' => 'Branch: ' . ($adminBranch ? trim(($adminBranch->branch_code ?? '') . ' - ' . ($adminBranch->branch_name ?? '')) : 'Selected Branch'),
            ]);
        }
        if (filled($q ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-search', 'label' => 'Search: ' . $q]);
        }
        if (filled($caseStatus ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-clipboard-check', 'label' => 'Case: ' . \Illuminate\Support\Str::headline(strtolower($caseStatus))]);
        }
        if (filled($paymentStatus ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-wallet2', 'label' => 'Payment: ' . \Illuminate\Support\Str::headline(strtolower($paymentStatus))]);
        }
        if (filled(request('service_type'))) {
            $adminMasterChips->push(['icon' => 'bi-tag', 'label' => 'Service: ' . request('service_type')]);
        }
        if (filled(request('package_id'))) {
            $selectedPackage = ($packages ?? collect())->firstWhere('id', (int) request('package_id'));
            $adminMasterChips->push(['icon' => 'bi-box', 'label' => 'Package: ' . ($selectedPackage?->name ?? 'Selected Package')]);
        }
        if ($resolvedDatePreset !== 'ANY') {
            $adminMasterChips->push(['icon' => 'bi-calendar3', 'label' => 'Date: ' . $presetLabel]);
        }
        if (filled($intermentFrom ?? null) || filled($intermentTo ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-calendar-event', 'label' => 'Interment: ' . (($intermentFrom ?? null) ?: 'Start') . ' - ' . (($intermentTo ?? null) ?: 'Today')]);
        }
        $masterTabParams = request()->except(['case_status', 'page']);
        $masterAllUrl = route('admin.cases.index', array_filter($masterTabParams, fn ($value) => filled($value)));
        $masterDraftUrl = route('admin.cases.index', array_filter(array_merge($masterTabParams, ['case_status' => 'DRAFT']), fn ($value) => filled($value)));
        $masterActiveUrl = route('admin.cases.index', array_filter(array_merge($masterTabParams, ['case_status' => 'ACTIVE']), fn ($value) => filled($value)));
        $masterCompletedUrl = route('admin.cases.index', array_filter(array_merge($masterTabParams, ['case_status' => 'COMPLETED']), fn ($value) => filled($value)));
    @endphp
    @include('partials.master_records_visual_styles')

    @if($errors->any())
        <div class="flash-error">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="master-records-toast no-print" role="status" aria-live="polite" data-page-context-toast>
        <i class="bi bi-folder2-open"></i>
        <span>You are viewing Master Case Records.</span>
    </div>

    <section class="table-system-card admin-table-card">
        <div class="case-records-tabs-row">
            <div class="table-quick-tabs case-records-tabs" role="tablist" aria-label="Master case record tabs">
                <a
                    href="{{ $masterAllUrl }}"
                    role="tab"
                    aria-selected="{{ blank($caseStatus ?? null) ? 'true' : 'false' }}"
                    class="table-quick-tab {{ blank($caseStatus ?? null) ? 'table-quick-tab-active' : '' }}"
                >
                    All
                </a>
                <a
                    href="{{ $masterDraftUrl }}"
                    role="tab"
                    aria-selected="{{ ($caseStatus ?? null) === 'DRAFT' ? 'true' : 'false' }}"
                    class="table-quick-tab {{ ($caseStatus ?? null) === 'DRAFT' ? 'table-quick-tab-active' : '' }}"
                >
                    Draft
                </a>
                <a
                    href="{{ $masterActiveUrl }}"
                    role="tab"
                    aria-selected="{{ ($caseStatus ?? null) === 'ACTIVE' ? 'true' : 'false' }}"
                    class="table-quick-tab {{ ($caseStatus ?? null) === 'ACTIVE' ? 'table-quick-tab-active' : '' }}"
                >
                    Active
                </a>
                <a
                    href="{{ $masterCompletedUrl }}"
                    role="tab"
                    aria-selected="{{ ($caseStatus ?? null) === 'COMPLETED' ? 'true' : 'false' }}"
                    class="table-quick-tab {{ ($caseStatus ?? null) === 'COMPLETED' ? 'table-quick-tab-active' : '' }}"
                >
                    Completed
                </a>
            </div>

        </div>

<div class="table-system-toolbar admin-table-toolbar">
            @include('partials.case_filter_toolbar', [
                'showPerPage' => true,
                    'showCaseStatus' => false,
                    'showServiceType' => false,
                'action' => route('admin.cases.index'),
                'resetUrl' => route('admin.cases.index'),
                'branchMode' => $isBranchAdmin ? 'locked' : 'all',
                'assignedBranch' => $isBranchAdmin ? $branches->first() : null,
                'branchId' => $branchId,
                'branches' => $branches,
                'dateFrom' => $dateFrom ?? null,
                'dateTo' => $dateTo ?? null,
                'intermentFrom' => $intermentFrom ?? null,
                'intermentTo' => $intermentTo ?? null,
                'serviceTypes' => $serviceTypes ?? collect(),
                'packages' => $packages ?? collect(),
                'encoders' => $encoders ?? collect(),
                'showVerificationStatus' => false,
                'showPackage' => true,
                'showEncodedBy' => true,
                'hiddenInputs' => ['case_status' => request('case_status')],
                'showSort' => true,
                'sortOptions' => [
                    'newest' => 'Newest',
                    'oldest' => 'Oldest',
                ],
                'sort' => $sort,
                'showInlineChips' => false,
                'showBranchField' => !$isBranchAdmin,
                'dateInMoreFilters' => false,
                'datePreset' => $resolvedDatePreset === 'ANY' ? '' : $resolvedDatePreset,
            ])

            <form method="GET" action="{{ route('admin.cases.index') }}" class="admin-master-toolbar hidden" data-table-toolbar data-search-debounce="400">
                <div class="admin-master-toolbar-row admin-master-toolbar-row-top admin-master-toolbar-row-primary">
                    <div class="table-toolbar-field">
                        <input
                            name="q"
                            value="{{ $q }}"
                            class="table-toolbar-search"
                            placeholder="Search case, client, or deceased..."
                            data-table-search
                        >
                    </div>

                    <div class="table-toolbar-field">
                        @if($isBranchAdmin && $branchId)
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        @endif
                        <select name="branch_id" class="table-toolbar-select" data-table-auto-submit @if($isBranchAdmin) disabled @endif>
                            @unless($isBranchAdmin)
                                <option value="">All Branches</option>
                            @endunless
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->branch_code }} - {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-toolbar-field">
                        <select name="case_status" class="table-toolbar-select" data-table-auto-submit>
                            <option value="">All Case Status</option>
                            <option value="DRAFT" {{ $caseStatus === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                            <option value="ACTIVE" {{ $caseStatus === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                            <option value="COMPLETED" {{ $caseStatus === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>

                    <div class="table-toolbar-reset-wrap admin-master-toolbar-actions admin-master-primary-actions">
                        <button
                            type="button"
                            class="btn btn-secondary admin-master-more-btn"
                            data-more-filters-toggle
                            data-more-filters-label
                            aria-controls="admin-master-more-filters"
                            aria-expanded="{{ $secondaryFiltersActive ? 'true' : 'false' }}"
                        >
                            <i class="bi bi-sliders"></i>
                            <span>{{ $secondaryFiltersActive ? 'Hide Filters' : 'More Filters' }}</span>
                        </button>
                        <a href="{{ route('admin.cases.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>

                <div id="admin-master-more-filters" class="admin-master-more-filters @if(!$secondaryFiltersActive) hidden @endif" data-more-filters-panel>
                    <div class="admin-master-toolbar-row admin-master-toolbar-row-bottom">
                        <div class="table-toolbar-field">
                            <select name="payment_status" class="table-toolbar-select" data-table-auto-submit>
                                <option value="">All Payment Status</option>
                                <option value="WITH_BALANCE" {{ $paymentStatus === 'WITH_BALANCE' ? 'selected' : '' }}>With Balance</option>
                                <option value="PAID" {{ $paymentStatus === 'PAID' ? 'selected' : '' }}>Paid</option>
                                <option value="PARTIAL" {{ $paymentStatus === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                                <option value="UNPAID" {{ $paymentStatus === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                            </select>
                        </div>

                        <div class="table-toolbar-field">
                            <select name="sort" class="table-toolbar-select" data-table-sort>
                                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                            </select>
                        </div>

                        <div class="table-toolbar-field">
                            <select name="date_preset" class="table-toolbar-select">
                                <option value="ANY" {{ $resolvedDatePreset === 'ANY' ? 'selected' : '' }}>All Dates</option>
                                <option value="TODAY" {{ $resolvedDatePreset === 'TODAY' ? 'selected' : '' }}>Today</option>
                                <option value="LAST_7_DAYS" {{ $resolvedDatePreset === 'LAST_7_DAYS' ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="LAST_30_DAYS" {{ $resolvedDatePreset === 'LAST_30_DAYS' ? 'selected' : '' }}>Last 30 Days</option>
                                <option value="THIS_MONTH" {{ $resolvedDatePreset === 'THIS_MONTH' ? 'selected' : '' }}>This Month</option>
                                <option value="CUSTOM" {{ $resolvedDatePreset === 'CUSTOM' ? 'selected' : '' }}>Custom Range</option>
                            </select>
                        </div>

                        <div class="table-toolbar-field" data-custom-date-field @if(!$isCustomDate) hidden @endif>
                            <input
                                type="date"
                                name="interment_from"
                                value="{{ $intermentFrom ?? '' }}"
                                class="table-toolbar-select"
                                data-custom-date-input
                                title="Date from"
                                @if(!$isCustomDate) disabled @endif
                            >
                        </div>

                        <div class="table-toolbar-field" data-custom-date-field @if(!$isCustomDate) hidden @endif>
                            <input
                                type="date"
                                name="interment_to"
                                value="{{ $intermentTo ?? '' }}"
                                class="table-toolbar-select"
                                data-custom-date-input
                                title="Date to"
                                @if(!$isCustomDate) disabled @endif
                            >
                        </div>
                    </div>
                </div>

                <div class="admin-master-active-summary">
                    <span>Active filters: <strong>{{ $activeFilterCount }}</strong></span>
                    @if($activeFilterCount > 0)
                        <a href="{{ route('admin.cases.index') }}">Clear all filters</a>
                    @endif
                    <span>Date Range: <strong>{{ $presetLabel }}</strong></span>
                    @if($isBranchAdmin)
                        <span><strong>Assigned Branch Only</strong></span>
                    @endif
                </div>
            </form>
        </div>



        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap" @if($isBranchAdmin) tabindex="0" role="region" aria-label="Case records table, scroll horizontally to view all columns" @endif>
                <table class="table-base table-system-table admin-master-table records-worklist-table">
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
                            <th class="text-left">Deceased / Client</th>
                            <th class="text-left">Service</th>
                            <th class="text-left">Schedule</th>
                            <th class="table-col-number">Package Availed</th>
                            <th class="table-status-col">Case Status</th>
                            <th class="table-status-col table-payment-status-col">Payment Status</th>
                            <th class="table-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($cases as $case)
                        @php
                            $isOtherBranch = $case->entry_source === 'OTHER_BRANCH';
                            $needsAttention = $case->payment_status === 'UNPAID'
                                || (float) $case->balance_amount > 0;
                            $intermentDate = $case->interment_at
                                ?? $case->deceased?->interment_at
                                ?? $case->deceased?->interment;
                        @endphp

                        <tr class="{{ $needsAttention ? 'row-needs-attention' : '' }}">
                            <td data-label="Case">
                                <div class="table-primary whitespace-nowrap records-case-code">{{ $case->case_code }}</div>
                                <div class="table-secondary">Recorded by: {{ $case->encodedBy?->name ?: 'Not recorded' }}</div>
                                <div class="table-secondary">{{ $case->created_at?->format('M d, Y h:i A') ?? 'Not recorded' }}</div>
                            </td>
                            <td data-label="Deceased / Client">
                                <div class="table-primary records-person-names">Deceased: {{ $case->deceased?->full_name ?? '-' }}</div>
                                <div class="table-secondary records-person-names">Client: {{ $case->client?->full_name ?? '-' }}</div>
                            </td>
                            <td data-label="Service">
                                <div class="table-primary">{{ $case->service_type ?? '-' }}</div>
                                <div class="table-secondary">{{ $case->package?->name ?? $case->service_package ?? '-' }}</div>
                            </td>
                            <td data-label="Schedule">
                                <div class="table-primary whitespace-nowrap">{{ $intermentDate ? $intermentDate->format('M d, Y') : '-' }}</div>
                                <div class="table-secondary">{{ $intermentDate ? $intermentDate->format('h:i A') : 'Interment time' }}</div>
                            </td>
                            <td class="table-col-number" data-label="Package Availed">
                                <div class="table-primary table-financial-total whitespace-nowrap">{{ number_format((float) $case->total_amount, 2) }}</div>
                                <div class="table-secondary table-financial-breakdown whitespace-nowrap">Paid {{ number_format((float) $case->total_paid, 2) }} &middot; Bal {{ number_format((float) $case->balance_amount, 2) }}</div>
                            </td>
                            <td class="table-status-cell" data-label="Case Status">
                                <x-status-badge :status="$case->case_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->case_status))" />
                            </td>
                            <td class="table-status-cell table-payment-status-cell" data-label="Payment Status">
                                <x-status-badge :status="$case->payment_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->payment_status))" class="table-payment-status-badge" />
                            </td>
                            <td class="table-col-actions" data-label="Actions">
                                <a class="records-view-case" href="{{ route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}" aria-label="View Full Information for {{ $case->case_code }}" @unless($isBranchAdmin) title="View Full Information" @endunless>
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                    @if($isBranchAdmin)<span class="records-action-tooltip" aria-hidden="true">View Full Information</span>@endif
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-system-empty">No case records found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
        <div class="table-system-pagination">
            @include('partials.case_records_pagination', ['cases' => $cases, 'showSinglePageControls' => $isBranchAdmin])
        </div>
    </section>
</div>

{{-- Case view modal --}}
@include('partials.case_records_layout_styles')
<div id="adminCaseViewOverlay" class="case-view-overlay">
    <div id="adminCaseViewSheet" class="case-view-sheet relative w-[92vw] max-w-4xl max-h-[92vh] overflow-hidden transform transition-all duration-200 scale-95 opacity-0">
        <div class="case-view-head">
            <span class="case-view-title">Case Full Information</span>
            <button id="adminCaseViewClose" type="button" class="case-view-close" aria-label="Close">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div id="adminCaseViewContent" class="case-view-content overflow-y-auto">
            <div class="case-view-loading">
                <div class="case-view-spinner"></div>
                <span>Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const pageSelector = '.master-records-page';
        let pendingRequest = null;

        const loadTab = async (url, selectedLink = null, pushState = true) => {
            const page = document.querySelector(pageSelector);
            const currentCard = page?.querySelector('.table-system-card');
            if (!page || !currentCard) {
                window.location.assign(url.href);
                return;
            }

            pendingRequest?.abort();
            const request = new AbortController();
            pendingRequest = request;
            page.classList.add('is-updating');
            currentCard.setAttribute('aria-busy', 'true');

            if (selectedLink) {
                currentCard.querySelectorAll('.case-records-tabs a').forEach((tab) => {
                    const selected = tab === selectedLink;
                    tab.classList.toggle('table-quick-tab-active', selected);
                    tab.setAttribute('aria-selected', String(selected));
                });
            }

            try {
                const response = await fetch(url.href, {
                    signal: request.signal,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(`Case records request failed: ${response.status}`);

                const documentFromResponse = new DOMParser().parseFromString(await response.text(), 'text/html');
                const nextCard = documentFromResponse.querySelector(`${pageSelector} .table-system-card`);
                if (!nextCard) throw new Error('Master Case Records was not found in the response.');
                if (request.signal.aborted) return;

                currentCard.replaceWith(nextCard);
                if (pushState) window.history.pushState({}, '', url.href);
                document.dispatchEvent(new CustomEvent('panel-ui:reset'));
                document.dispatchEvent(new CustomEvent('master-case-records:updated'));
            } catch (error) {
                if (error.name !== 'AbortError' && !request.signal.aborted) window.location.assign(url.href);
            } finally {
                if (pendingRequest === request) {
                    pendingRequest = null;
                    page.classList.remove('is-updating');
                    page.querySelector('.table-system-card')?.removeAttribute('aria-busy');
                }
            }
        };

        document.addEventListener('click', (event) => {
            const link = event.target instanceof Element
                ? event.target.closest(`${pageSelector} .case-records-tabs a[href]`)
                : null;
            if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;
            event.preventDefault();
            event.stopPropagation();
            if (url.href !== window.location.href) loadTab(url, link);
        }, true);

        window.addEventListener('popstate', () => {
            loadTab(new URL(window.location.href), null, false);
        });
    })();

    (() => {
        const page = document.querySelector('.master-records-page');
        if (!page) return;

        const submitCompactForm = (form) => {
            if (!form) return;
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }
            form.submit();
        };

        const setCompactCustomOpen = (form, open) => {
            if (window.setCaseCustomDateOpen) return window.setCaseCustomDateOpen(form, open);
            const select = form?.querySelector('[data-case-date-preset-select]');
            const panel = form?.querySelector('[data-case-custom-panel]');
            if (!select || !panel) return;
            panel.hidden = !open;
            select.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        page.addEventListener('change', (event) => {
            const select = event.target instanceof Element
                ? event.target.closest('[data-case-date-preset-select]')
                : null;
            if (!select) return;

            const form = select.closest('[data-case-filter]');
            if (!form) return;
            if (form.dataset.caseFilterReady === '1') return;

            if (select.value === 'CUSTOM') {
                setCompactCustomOpen(form, true);
                return;
            }

            setCompactCustomOpen(form, false);
            submitCompactForm(form);
        });

        page.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            if (event.target.closest('[data-case-custom-panel], [data-case-date-preset-select], .filter-select-menu')) {
                return;
            }

            page.querySelectorAll('[data-case-filter]').forEach((form) => {
                setCompactCustomOpen(form, false);
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            page.querySelectorAll('[data-case-filter]').forEach((form) => {
                setCompactCustomOpen(form, false);
            });
        });
    })();

    (() => {
        const toggle = document.querySelector('[data-more-filters-toggle]');
        const panel = document.querySelector('[data-more-filters-panel]');
        const labelWrap = document.querySelector('[data-more-filters-label] span');
        if (!toggle || !panel || !labelWrap) return;

        const setOpen = (open) => {
            panel.classList.toggle('hidden', !open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            labelWrap.textContent = open ? 'Hide Filters' : 'More Filters';
        };

        setOpen(toggle.getAttribute('aria-expanded') === 'true');
        toggle.addEventListener('click', () => {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });
    })();

    (() => {
        const overlay  = document.getElementById('adminCaseViewOverlay');
        const sheet    = document.getElementById('adminCaseViewSheet');
        const content  = document.getElementById('adminCaseViewContent');
        const closeBtn = document.getElementById('adminCaseViewClose');
        if (!overlay || !sheet || !content || !closeBtn) return;

        const loadingHtml = `
            <div class="case-view-loading">
                <div class="case-view-spinner"></div>
                <span>Loading...</span>
            </div>`;

        const show = () => {
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                sheet.classList.remove('scale-95', 'opacity-0');
                sheet.classList.add('scale-100', 'opacity-100');
            });
        };

        const hide = () => {
            sheet.classList.add('scale-95', 'opacity-0');
            sheet.classList.remove('scale-100', 'opacity-100');
            setTimeout(() => {
                overlay.style.display = 'none';
                document.body.style.overflow = '';
                content.innerHTML = loadingHtml;
            }, 180);
        };

        const load = async (url) => {
            content.innerHTML = loadingHtml;
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const payload = doc.querySelector('#caseViewContent');
                if (payload) {
                    content.innerHTML = payload.innerHTML;
                    doc.querySelectorAll('script').forEach(oldScript => {
                        const script = document.createElement('script');
                        script.textContent = oldScript.textContent;
                        content.appendChild(script);
                    });
                } else {
                    content.innerHTML = `<div class="case-view-error">Unable to load case details.</div>`;
                }
            } catch {
                content.innerHTML = `<div class="case-view-error">Network error. Please try again.</div>`;
            }
        };

        document.querySelectorAll('.open-case-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                show();
                load(btn.dataset.url);
            });
        });

        closeBtn.addEventListener('click', hide);
        overlay.addEventListener('click', e => { if (e.target === overlay) hide(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.style.display !== 'none') hide(); });
    })();
</script>
@endsection
