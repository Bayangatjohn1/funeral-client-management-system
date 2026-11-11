@extends('layouts.panel')

@section('page_title', auth()->user()?->isBranchAdmin() ? 'Case Records' : 'Master Case Records')
@section('page_desc', auth()->user()?->isBranchAdmin()
    ? 'Review case status, schedules, and payment health for your assigned branch.'
    : 'Monitor branch activity, case status, and payment health from one admin worklist.')
@section('hide_layout_topbar', '1')

@section('content')
@include('partials.historical_cases_transition')
<div class="admin-table-page master-records-page">
    @php
        $isBranchAdmin = auth()->user()?->isBranchAdmin() ?? false;
        $recordsPageTitle = $isBranchAdmin ? 'Case Records' : 'Master Case Records';
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
        <span>You are viewing {{ $recordsPageTitle }}.</span>
    </div>

    <section class="table-system-card admin-table-card">
        <div class="case-records-tabs-row">
            <div class="table-quick-tabs case-records-tabs" role="tablist" aria-label="{{ $recordsPageTitle }} status tabs">
                <a
                    href="{{ $masterAllUrl }}"
                    role="tab"
                    aria-selected="{{ blank($caseStatus ?? null) ? 'true' : 'false' }}"
                    class="table-quick-tab {{ blank($caseStatus ?? null) ? 'table-quick-tab-active' : '' }}"
                >
                    <i class="bi bi-collection" aria-hidden="true"></i><span>All</span>
                </a>

                <a
                    href="{{ $masterActiveUrl }}"
                    role="tab"
                    aria-selected="{{ ($caseStatus ?? null) === 'ACTIVE' ? 'true' : 'false' }}"
                    class="table-quick-tab {{ ($caseStatus ?? null) === 'ACTIVE' ? 'table-quick-tab-active' : '' }}"
                >
                    <i class="bi bi-clock" aria-hidden="true"></i><span>Active</span>
                </a>
                <a
                    href="{{ $masterCompletedUrl }}"
                    role="tab"
                    aria-selected="{{ ($caseStatus ?? null) === 'COMPLETED' ? 'true' : 'false' }}"
                    class="table-quick-tab {{ ($caseStatus ?? null) === 'COMPLETED' ? 'table-quick-tab-active' : '' }}"
                >
                    <i class="bi bi-check2-all" aria-hidden="true"></i><span>Completed</span>
                </a>
            </div>

            @if(config('funeral.historical_cases_enabled', false))
                <a href="{{ route('historical-cases.index') }}" class="historical-cases-entry-link inline-flex shrink-0 items-center gap-2 rounded-xl bg-amber-100 px-4 py-2.5 text-sm font-semibold text-amber-950 hover:bg-amber-200 historical-cases-nav-link" data-historical-transition>
                    <i class="bi bi-clock-history" aria-hidden="true"></i><span>Historical Cases</span>
                </a>
            @endif

        </div>

<div class="table-system-toolbar admin-table-toolbar">
            @include('partials.case_filter_toolbar', [
                'showPerPage' => true,
                'showCaseStatus' => true,
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
                'hiddenInputs' => [],
                'showSort' => true,
                'sortOptions' => [
                    'newest' => 'Newest first',
                    'oldest' => 'Oldest first',
                    'alpha_asc' => 'Client A-Z',
                    'alpha_desc' => 'Client Z-A',




                ],
                'sort' => $sort,
                'showInlineChips' => false,
                'showBranchField' => !$isBranchAdmin,
                'dateInMoreFilters' => false,
                'datePreset' => $resolvedDatePreset === 'ANY' ? '' : $resolvedDatePreset,
                'searchPlaceholder' => 'e.g., FC0008, Juan Luna...',
                'dateChipLabel' => 'Date',
                'compactDateChip' => true,
            ])

            <form method="GET" action="{{ route('admin.cases.index') }}" class="admin-master-toolbar hidden" data-table-toolbar data-search-debounce="400">
                <div class="admin-master-toolbar-row admin-master-toolbar-row-top admin-master-toolbar-row-primary">
                    <div class="table-toolbar-field">
                        <input
                            name="q"
                            value="{{ $q }}"
                            class="table-toolbar-search"
                            placeholder="e.g., FC0008, Juan Luna..."
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
                    @include('partials.case_worklist_head', ['showBranch' => false])
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

                        <tr
@include('partials.case_search_metadata')
 class="{{ $needsAttention ? 'row-needs-attention' : '' }}">
                            @include('partials.case_worklist_cells', ['case' => $case, 'worklistInterment' => $intermentDate, 'showBranch' => false])
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-system-empty"><div class="records-empty-state">No case records found.</div></td>
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

@include('partials.master_case_tab_navigation')
<script>
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
            if (select.closest('[data-case-more-panel]')) return;
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
            const finishSkeleton = window.PanelSkeleton?.begin(content, {variant:'form'});
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
            } finally { finishSkeleton?.(); }
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
