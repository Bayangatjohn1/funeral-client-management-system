@php
    $datePreset = $datePreset ?? '';
    $branchMode = $branchMode ?? 'all';
    $branchId = $branchId ?? null;
    $assignedBranch = $assignedBranch ?? null;
    $showSearch = $showSearch ?? true;
    $showCaseStatus = $showCaseStatus ?? true;
    $showPaymentStatus = $showPaymentStatus ?? true;
    $showVerificationStatus = $showVerificationStatus ?? false;
    $showServiceType = $showServiceType ?? true;
    $showPackage = $showPackage ?? false;
    $showEncodedBy = $showEncodedBy ?? false;
    $showInterment = $showInterment ?? true;
    $showBranchChip = $showBranchChip ?? true;
    $showInlineChips = $showInlineChips ?? true;
    $showMoreFilters = $showMoreFilters ?? true;
    $showSort = $showSort ?? false;
    $showBranchField = $showBranchField ?? true;
    $dateInMoreFilters = $dateInMoreFilters ?? false;
    $useDateDisplayLabel = $useDateDisplayLabel ?? false;
    $searchPlaceholder = $searchPlaceholder ?? 'Search case, client, or deceased...';
    $dateChipLabel = $dateChipLabel ?? 'Date Encoded';
    $compactDateChip = $compactDateChip ?? false;
    $sortOptions = $sortOptions ?? [];
    $sort = $sort ?? request('sort', 'newest');
    $branchLabel = 'All Branches';
    if ($branchMode === 'locked') {
        $branchLabel = $assignedBranch
            ? trim(($assignedBranch->branch_code ?? '') . ' - ' . ($assignedBranch->branch_name ?? ''))
            : 'Assigned Branch';
    } elseif (filled($branchId)) {
        $selectedBranch = ($branches ?? collect())->firstWhere('id', (int) $branchId);
        $branchLabel = $selectedBranch
            ? trim(($selectedBranch->branch_code ?? '') . ' - ' . ($selectedBranch->branch_name ?? ''))
            : 'Selected Branch';
    }
    $packageLabel = null;
    if (filled(request('package_id'))) {
        $selectedPackage = ($packages ?? collect())->firstWhere('id', (int) request('package_id'));
        $packageLabel = $selectedPackage?->name ?? 'Selected Package';
    }
    $encoderLabel = null;
    if (filled(request('encoded_by'))) {
        $selectedEncoder = ($encoders ?? collect())->firstWhere('id', (int) request('encoded_by'));
        $encoderLabel = $selectedEncoder?->name ?? 'Selected Encoder';
    }
    $activeFilters = collect([
        $showBranchField && $branchMode !== 'locked' && filled($branchId),
        filled($datePreset) || filled($dateFrom ?? null) || filled($dateTo ?? null),
        $showCaseStatus && filled(request('case_status')),
        $showPaymentStatus && filled(request('payment_status')),
        $showVerificationStatus && filled(request('verification_status')),
        $showServiceType && filled(request('service_type')),
        $showPackage && filled(request('package_id')),
        $showEncodedBy && filled(request('encoded_by')),
        $showInterment && (filled($intermentFrom ?? null) || filled($intermentTo ?? null)),
    ])->filter()->count();
    $dateLabel = null;
    $dateChipValue = null;
    $formatFilterDate = static fn ($value) => filled($value)
        ? \Illuminate\Support\Carbon::parse($value)->format('M j, Y')
        : null;
    if ($compactDateChip) {
        if ($datePreset === 'TODAY') {
            $dateChipValue = $formatFilterDate($dateFrom ?? now()) ?? 'Today';
        } elseif ($datePreset === 'THIS_MONTH') {
            $dateChipValue = filled($dateFrom ?? null)
                ? \Illuminate\Support\Carbon::parse($dateFrom)->format('F Y')
                : 'This Month';
        } elseif ($datePreset === 'THIS_YEAR') {
            $dateChipValue = filled($dateFrom ?? null)
                ? \Illuminate\Support\Carbon::parse($dateFrom)->format('Y')
                : 'This Year';
        } elseif ($datePreset === 'CUSTOM' || filled($dateFrom ?? null) || filled($dateTo ?? null)) {
            $from = $formatFilterDate($dateFrom ?? null);
            $to = $formatFilterDate($dateTo ?? null);
            if ($from && $to) {
                $fromDate = \Illuminate\Support\Carbon::parse($dateFrom);
                $toDate = \Illuminate\Support\Carbon::parse($dateTo);
                if ($fromDate->isSameMonth($toDate)) {
                    $dateChipValue = $fromDate->day === 1 && $toDate->day === $toDate->daysInMonth
                        ? $fromDate->format('F Y')
                        : ($fromDate->isSameDay($toDate)
                            ? $from
                            : $fromDate->format('M j') . '–' . $toDate->format('j, Y'));
                } else {
                    $dateChipValue = $fromDate->isSameYear($toDate)
                        ? $fromDate->format('M j') . ' – ' . $toDate->format('M j, Y')
                        : $from . ' – ' . $to;
                }
            } else {
                $dateChipValue = ($from ?? 'Start') . ' – ' . ($to ?? 'Today');
            }
        }
        if ($dateChipValue !== null) {
            $dateLabel = $dateChipLabel . ': ' . $dateChipValue;
        }
    } elseif ($datePreset === 'TODAY') {
        $dateLabel = 'Date Encoded: Today';
    } elseif ($datePreset === 'THIS_MONTH') {
        $dateLabel = 'Date Encoded: This Month';
    } elseif ($datePreset === 'THIS_YEAR') {
        $dateLabel = 'Date Encoded: This Year';
    } elseif ($datePreset === 'CUSTOM' || filled($dateFrom ?? null) || filled($dateTo ?? null)) {
        $dateLabel = 'Date Encoded: ' . (($dateFrom ?? null) ?: 'Start') . ' - ' . (($dateTo ?? null) ?: 'Today');
    }
    $filterChips = collect();
    if ($showBranchChip && ($branchMode === 'locked' || filled($branchId))) {
        $filterChips->push(['icon' => $branchMode === 'locked' ? 'bi-lock-fill' : 'bi-building', 'label' => 'Branch: ' . $branchLabel, 'tone' => 'neutral']);
    }
    if ($dateLabel) {
        $filterChips->push(['icon' => 'bi-calendar3', 'label' => $dateLabel, 'tone' => 'neutral']);
    }
    if ($showCaseStatus && filled(request('case_status'))) {
        $filterChips->push(['icon' => 'bi-clipboard-check', 'label' => 'Case Status: ' . \Illuminate\Support\Str::headline(strtolower(request('case_status'))), 'tone' => 'neutral']);
    }
    if ($showPaymentStatus && filled(request('payment_status'))) {
        $filterChips->push(['icon' => 'bi-wallet2', 'label' => 'Payment Status: ' . \Illuminate\Support\Str::headline(strtolower(request('payment_status'))), 'tone' => in_array(request('payment_status'), ['UNPAID', 'PARTIAL', 'WITH_BALANCE'], true) ? 'caution' : 'positive']);
    }
    if ($showVerificationStatus && filled(request('verification_status'))) {
        $filterChips->push(['icon' => 'bi-shield-check', 'label' => 'Verification Status: ' . \Illuminate\Support\Str::headline(strtolower(request('verification_status'))), 'tone' => request('verification_status') === 'VERIFIED' ? 'positive' : 'caution']);
    }
    if ($showServiceType && filled(request('service_type'))) {
        $filterChips->push(['icon' => 'bi-tag', 'label' => 'Service Type: ' . request('service_type'), 'tone' => 'neutral']);
    }
    if ($showPackage && $packageLabel) {
        $filterChips->push(['icon' => 'bi-box-seam', 'label' => 'Package: ' . $packageLabel, 'tone' => 'neutral']);
    }
    if ($showEncodedBy && $encoderLabel) {
        $filterChips->push(['icon' => 'bi-person', 'label' => 'Encoded By: ' . $encoderLabel, 'tone' => 'neutral']);
    }
    if ($showInterment && (filled($intermentFrom ?? null) || filled($intermentTo ?? null))) {
        $filterChips->push(['icon' => 'bi-calendar-event', 'label' => 'Interment Date: ' . (($intermentFrom ?? null) ?: 'Start') . ' - ' . (($intermentTo ?? null) ?: 'Today'), 'tone' => 'neutral']);
    }
    $dateControlLabel = match ($datePreset) {
        'TODAY' => 'Today',
        'THIS_MONTH' => 'This Month',
        'THIS_YEAR' => 'This Year',
        'CUSTOM' => 'Custom Range',
        default => 'All Dates',
    };
@endphp

<form method="GET" action="{{ $action }}" class="case-compact-filter shared-record-filter-toolbar {{ ($showPerPage ?? false) ? 'case-record-filters' : '' }}" data-case-filter data-live-search-commit-only>
    @foreach(($hiddenInputs ?? []) as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach

    <div class="case-compact-search-row">
        @if($showSearch)
            <div class="case-compact-field case-compact-search-field">
                <label>Search</label>
                <div class="case-compact-search-control shared-record-search">
                    <i class="bi bi-search case-compact-search-icon shared-record-search-icon" aria-hidden="true"></i>
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        class="case-compact-input shared-record-search-input"
                        placeholder="Case ID, client or deceased name" aria-label="Search case records" aria-expanded="false" aria-controls="caseSearchMatches" aria-autocomplete="list"
                        autocomplete="off"
                        data-case-search-input
                    >
                    <button
                        type="button"
                        class="case-compact-search-clear shared-record-search-clear"
                        data-case-search-clear
                        @if(blank(request('q'))) hidden @endif
                        aria-label="Clear search"
                    >
                        <i class="bi bi-x"></i>
                    </button>
                    <button type="button" class="case-compact-search-button" data-case-search-submit>Search</button>
                    <div id="caseSearchMatches" class="case-compact-search-suggestions" data-case-search-suggestions hidden></div>
                </div>
            </div>
        @endif

        @if($showInlineChips)
            <div class="case-compact-inline-chips" aria-label="Applied branch and filters">
                @if($branchMode === 'locked' || filled($branchId))
                    <span class="case-compact-chip case-compact-chip-locked">
                        <i class="bi {{ $branchMode === 'locked' ? 'bi-lock-fill' : 'bi-building' }}"></i>
                        Branch: {{ $branchLabel }}
                    </span>
                @endif

                @foreach($filterChips->reject(fn ($chip) => str_starts_with($chip['label'], 'Branch:')) as $chip)
                    <span class="case-compact-chip"><i class="bi {{ $chip['icon'] }}"></i>{{ $chip['label'] }}</span>
                @endforeach
            </div>
        @endif

        @if($activeFilters > 0 && !($showPerPage ?? false))
            <div class="case-compact-actions">
                <a href="{{ $resetUrl }}" class="case-compact-reset filter-clear-action" data-case-clear-filters>
                    <i class="bi bi-x-circle"></i>
                    <span>Clear</span>
                </a>
            </div>
        @endif
    </div>

    <div class="case-compact-filter-bar" role="group" aria-label="Case record filters">
        @if($showMoreFilters)
            <button type="button" class="case-compact-more {{ $activeFilters > 0 ? 'active' : '' }}" data-case-more-toggle data-filter-count="{{ $activeFilters }}" aria-expanded="false" aria-controls="case-filter-options" aria-label="Filter">
                <i class="bi bi-funnel"></i>
                <span data-case-more-text>Filter</span><span class="catalog-filter-count" data-case-filter-count @if($activeFilters === 0) hidden @endif>{{ $activeFilters }}</span>
                <i class="bi bi-chevron-down" data-case-more-icon></i>
            </button>
        @endif
        @if($showSort && !empty($sortOptions))
            <div class="case-compact-seg case-compact-sort-filter" role="group" aria-label="Sort case records">
                <i class="bi bi-sort-down case-compact-sort-icon"></i>
                <select name="sort" class="case-compact-sort-select" data-case-sort-select data-case-auto-submit aria-label="Sort case records">
                    @foreach($sortOptions as $sortKey => $sortLabel)
                        <option value="{{ $sortKey }}" data-menu-label="{{ $sortLabel }}" @selected($sort === $sortKey)>{{ $sortKey === 'newest' ? 'Sort' : $sortLabel }}</option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down case-compact-sort-chev"></i>
            </div>
        @endif
        @if($showPerPage ?? false)
            <div class="case-rows-per-page">
                <label for="case-records-per-page">Cases per page</label>
                <span class="case-page-size-control">
                <input id="case-records-per-page" type="number" name="per_page" value="{{ $cases->perPage() }}" min="1" max="100" step="1" required class="case-compact-input" data-case-auto-submit>
                <div class="filter-select-menu case-page-size-menu" popover="manual" hidden>
                    @foreach([10, 25, 50, 100] as $pageSize)
                        <button type="button" class="filter-select-menu__item" data-case-page-size="{{ $pageSize }}">{{ $pageSize }}</button>
                    @endforeach
                </div>
                <button type="button" class="case-page-size-chevron" data-case-page-size-options aria-label="Choose rows per page"><i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                </span>
            </div>
        @endif

    </div>
    @if(($showPerPage ?? false) && $filterChips->isNotEmpty())
        <div class="case-applied-filters" aria-label="Applied filters">
            <div class="case-applied-filter-chips">
                @foreach($filterChips as $chip)
                    @php
                        $removeKeys = match (true) {
                            str_starts_with($chip['label'], 'Branch:') => ['branch_id'],
                            str_starts_with($chip['label'], $dateChipLabel . ':') => ['date_preset', 'date_from', 'date_to'],
                            str_starts_with($chip['label'], 'Case Status:') => ['case_status'],
                            str_starts_with($chip['label'], 'Payment Status:') => ['payment_status'],
                            str_starts_with($chip['label'], 'Verification Status:') => ['verification_status'],
                            str_starts_with($chip['label'], 'Service Type:') => ['service_type'],
                            str_starts_with($chip['label'], 'Package:') => ['package_id'],
                            str_starts_with($chip['label'], 'Encoded By:') => ['encoded_by'],
                            str_starts_with($chip['label'], 'Interment Date:') => ['interment_from', 'interment_to'],
                            default => [],
                        };
                        $isLockedBranch = str_starts_with($chip['label'], 'Branch:') && $branchMode === 'locked';
                        $isRemovable = $removeKeys !== [] && !$isLockedBranch;
                    @endphp
                    @if($isRemovable)
                        <span class="catalog-filter-chip" data-tone="{{ $chip['tone'] ?? 'neutral' }}">
                            <span>{{ $chip['label'] }}</span>
                            <button type="button" data-case-remove-filter data-remove-keys="{{ implode(',', $removeKeys) }}" aria-label="Remove {{ $chip['label'] }} filter"><i class="bi bi-x" aria-hidden="true"></i></button>
                        </span>
                    @elseif(str_starts_with($chip['label'], 'Branch:') && $branchMode === 'locked')
                        <span class="catalog-filter-chip" data-tone="neutral"><span><i class="bi bi-lock-fill" aria-hidden="true"></i> {{ $chip['label'] }}</span></span>
                    @endif
                @endforeach
            </div>
            @if($activeFilters > 0)
                <button type="button" class="catalog-clear-all case-compact-reset" data-case-clear-filters>
                    <span>Clear filters</span>
                </button>
            @endif
        </div>
    @endif
    @if($showMoreFilters)
        <div id="case-filter-options" class="case-compact-advanced" data-case-more-panel hidden>
            <div class="case-compact-drawer" role="region" aria-labelledby="case-filter-panel-title">
                <div class="case-compact-advanced-head">
                    <div>
                        <div id="case-filter-panel-title" class="case-compact-advanced-title">Filter by</div>
                        <div class="case-compact-advanced-note">Choose the case details to narrow the records.</div>
                    </div>
                    <button type="button" class="case-compact-drawer-close" data-case-more-dismiss aria-label="Close more filters">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="case-compact-advanced-grid">
                    <section class="case-filter-group">
                    @if($showBranchField)
                        <div class="case-compact-field case-compact-branch-field">
                            <label for="case-filter-branch_id">Branch</label>
                            <div class="case-compact-branch" title="{{ $branchMode === 'locked' ? 'Assigned Branch' : 'Branch' }}">
                                <i class="bi bi-building" aria-hidden="true"></i>
                                @if($branchMode === 'locked')
                                    <input type="hidden" name="branch_id" value="{{ $assignedBranch?->id ?? $branchId }}">
                                    <select id="case-filter-branch_id" class="case-compact-select" disabled>
                                        <option>{{ $assignedBranch?->branch_code ?? 'Assigned Branch' }}{{ $assignedBranch?->branch_name ? ' - ' . $assignedBranch->branch_name : '' }}</option>
                                    </select>
                                    <i class="bi bi-lock-fill case-compact-select-chev" aria-hidden="true"></i>
                                @else
                                    <select id="case-filter-branch_id" name="branch_id" class="case-compact-select" aria-label="Branch filter">
                                        <option value="">All Branches</option>
                                        @foreach(($branches ?? collect()) as $branch)
                                            <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->branch_code }} - {{ $branch->branch_name }}</option>
                                        @endforeach
                                    </select>
                                    <i class="bi bi-chevron-down case-compact-select-chev" aria-hidden="true"></i>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="case-compact-field case-compact-advanced-date">
                        <label>Date Encoded</label>
                        @include('partials.case_date_filter_control')
                    </div>
                    @if($showInterment)
                        <fieldset class="case-interment-range" aria-label="Interment date range">
                            <div class="case-interment-fields">
                                <div class="case-compact-field">
                                    <label for="case-interment-from">Interment From</label>
                                    <input id="case-interment-from" type="date" name="interment_from" value="{{ $intermentFrom ?? '' }}" class="case-compact-input">
                                </div>
                                <div class="case-compact-field">
                                    <label for="case-interment-to">Interment To</label>
                                    <input id="case-interment-to" type="date" name="interment_to" value="{{ $intermentTo ?? '' }}" class="case-compact-input">
                                </div>
                            </div>
                        </fieldset>
                    @endif
                    </section>

                    <section class="case-filter-group">
                    @if($showCaseStatus)
                        <div class="case-compact-field">
                            <label>Case Status</label>
                            <select name="case_status" class="case-compact-input">
                                <option value="">All Case Status</option>
                                @if(auth()->user()?->role === 'staff')
                                    <option value="DRAFT" @selected(request('case_status') === 'DRAFT')>Draft</option>
                                @endif
                                <option value="ACTIVE" @selected(request('case_status') === 'ACTIVE')>Active</option>
                                <option value="COMPLETED" @selected(request('case_status') === 'COMPLETED')>Completed</option>
                            </select>
                        </div>
                    @endif

                    @if($showPaymentStatus)
                        <div class="case-compact-field">
                            <label for="case-filter-payment_status">Payment Status</label>
                            <select id="case-filter-payment_status" name="payment_status" class="case-compact-input">
                                <option value="">All Payment Status</option>
                                @if($showPerPage ?? false)
                                    <option value="WITH_BALANCE" @selected(request('payment_status') === 'WITH_BALANCE')>With Balance</option>
                                @endif
                                <option value="UNPAID" @selected(request('payment_status') === 'UNPAID')>Unpaid</option>
                                <option value="PARTIAL" @selected(request('payment_status') === 'PARTIAL')>Partial</option>
                                <option value="PAID" @selected(request('payment_status') === 'PAID')>Paid</option>
                            </select>
                        </div>
                    @endif

                    @if($showVerificationStatus)
                        <div class="case-compact-field">
                            <label>Verification Status</label>
                            <select name="verification_status" class="case-compact-input">
                                <option value="">All Verification Status</option>
                                <option value="PENDING" @selected(request('verification_status') === 'PENDING')>Pending Review</option>
                                <option value="VERIFIED" @selected(request('verification_status') === 'VERIFIED')>Verified</option>
                                <option value="DISPUTED" @selected(request('verification_status') === 'DISPUTED')>Disputed</option>
                            </select>
                        </div>
                    @endif

                    @if($showServiceType)
                        <div class="case-compact-field">
                            <label>Service Type</label>
                            <select name="service_type" class="case-compact-input">
                                <option value="">All Service Types</option>
                                @foreach(($serviceTypes ?? collect()) as $type)
                                    <option value="{{ $type }}" @selected(request('service_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($showPackage)
                        <div class="case-compact-field">
                            <label for="case-filter-package_id">Package</label>
                            <select id="case-filter-package_id" name="package_id" class="case-compact-input">
                                <option value="">All Packages</option>
                                @foreach(($packages ?? collect()) as $package)
                                    <option value="{{ $package->id }}" @selected((string) request('package_id') === (string) $package->id)>{{ $package->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($showEncodedBy)
                        <div class="case-compact-field">
                            <label for="case-filter-encoded_by">Encoded By</label>
                            <select id="case-filter-encoded_by" name="encoded_by" class="case-compact-input">
                                <option value="">All Encoders</option>
                                @foreach(($encoders ?? collect()) as $encoder)
                                    <option value="{{ $encoder->id }}" @selected((string) request('encoded_by') === (string) $encoder->id)>{{ $encoder->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif


                    </section>
                </div>

                <div class="case-compact-advanced-actions">
                    @if($activeFilters > 0)
                        <button type="button" class="catalog-clear-all case-compact-panel-clear" data-case-clear-filters>
                            <span>Clear filters</span>
                        </button>
                    @endif
                    <button type="submit" class="case-compact-pop-apply">
                        <i class="bi bi-funnel"></i>
                        <span>Apply filters</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</form>

@if($showPerPage ?? false)
    @include('partials.case_record_filter_styles')
@endif
