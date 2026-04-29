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
    $advancedFilterCount = collect([
        filled(request('case_status')),
        filled(request('payment_status')),
        filled(request('verification_status')),
        filled(request('service_type')),
        filled(request('package_id')),
        filled(request('encoded_by')),
        filled($intermentFrom ?? null),
        filled($intermentTo ?? null),
    ])->filter()->count();
    $activeFilters = collect([
        filled(request('q')),
        filled($branchId),
        filled($datePreset),
        $advancedFilterCount > 0,
    ])->filter()->count();
    $dateLabel = null;
    if ($datePreset === 'TODAY') {
        $dateLabel = 'Date Encoded: Today';
    } elseif ($datePreset === 'THIS_MONTH') {
        $dateLabel = 'Date Encoded: This Month';
    } elseif ($datePreset === 'THIS_YEAR') {
        $dateLabel = 'Date Encoded: This Year';
    } elseif ($datePreset === 'CUSTOM' || filled($dateFrom ?? null) || filled($dateTo ?? null)) {
        $dateLabel = 'Date Encoded: ' . (($dateFrom ?? null) ?: 'Start') . ' - ' . (($dateTo ?? null) ?: 'Today');
    }
    $filterChips = collect();
    if (filled(request('q'))) {
        $filterChips->push(['icon' => 'bi-search', 'label' => 'Search: ' . request('q')]);
    }
    if ($branchMode === 'locked' || filled($branchId)) {
        $filterChips->push(['icon' => $branchMode === 'locked' ? 'bi-lock-fill' : 'bi-building', 'label' => 'Branch: ' . $branchLabel]);
    }
    if ($dateLabel) {
        $filterChips->push(['icon' => 'bi-calendar3', 'label' => $dateLabel]);
    }
    if (filled(request('case_status'))) {
        $filterChips->push(['icon' => 'bi-clipboard-check', 'label' => 'Case Status: ' . \Illuminate\Support\Str::headline(strtolower(request('case_status')))]);
    }
    if (filled(request('payment_status'))) {
        $filterChips->push(['icon' => 'bi-wallet2', 'label' => 'Payment Status: ' . \Illuminate\Support\Str::headline(strtolower(request('payment_status')))]);
    }
    if (filled(request('verification_status'))) {
        $filterChips->push(['icon' => 'bi-shield-check', 'label' => 'Verification Status: ' . \Illuminate\Support\Str::headline(strtolower(request('verification_status')))]);
    }
    if (filled(request('service_type'))) {
        $filterChips->push(['icon' => 'bi-tag', 'label' => 'Service Type: ' . request('service_type')]);
    }
    if ($packageLabel) {
        $filterChips->push(['icon' => 'bi-box-seam', 'label' => 'Package: ' . $packageLabel]);
    }
    if ($encoderLabel) {
        $filterChips->push(['icon' => 'bi-person', 'label' => 'Encoded By: ' . $encoderLabel]);
    }
    if (filled($intermentFrom ?? null) || filled($intermentTo ?? null)) {
        $filterChips->push(['icon' => 'bi-calendar-event', 'label' => 'Interment Date: ' . (($intermentFrom ?? null) ?: 'Start') . ' - ' . (($intermentTo ?? null) ?: 'Today')]);
    }
@endphp

<form method="GET" action="{{ $action }}" class="case-compact-filter" data-case-filter>
    @foreach(($hiddenInputs ?? []) as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach

    <div class="case-compact-filter-bar" role="group" aria-label="Case record filters">
        <div class="case-compact-branch" title="{{ $branchMode === 'locked' ? 'Assigned Branch' : 'Branch' }}">
            <i class="bi bi-building"></i>
            @if($branchMode === 'locked')
                <input type="hidden" name="branch_id" value="{{ $assignedBranch?->id ?? $branchId }}">
                <select class="case-compact-select" disabled>
                    <option>{{ $assignedBranch?->branch_code ?? 'Assigned Branch' }}{{ $assignedBranch?->branch_name ? ' - ' . $assignedBranch->branch_name : '' }}</option>
                </select>
                <i class="bi bi-lock-fill case-compact-select-chev"></i>
            @else
                <select name="branch_id" class="case-compact-select" aria-label="Branch filter" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach(($branches ?? collect()) as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>
                            {{ $branch->branch_code }} - {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down case-compact-select-chev"></i>
            @endif
        </div>

        <div class="case-compact-seg" role="group" aria-label="Date encoded preset filter">
            <button type="submit" name="date_preset" value="TODAY" title="Filter by Date Encoded / Date Created" class="case-compact-seg-item {{ $datePreset === 'TODAY' ? 'active' : '' }}">Today</button>
            <button type="submit" name="date_preset" value="THIS_MONTH" title="Filter by Date Encoded / Date Created" class="case-compact-seg-item {{ $datePreset === 'THIS_MONTH' ? 'active' : '' }}">This Month</button>
            <button type="submit" name="date_preset" value="THIS_YEAR" title="Filter by Date Encoded / Date Created" class="case-compact-seg-item {{ $datePreset === 'THIS_YEAR' ? 'active' : '' }}">This Year</button>

            <div class="case-compact-custom">
                <button type="button" class="case-compact-seg-item {{ $datePreset === 'CUSTOM' ? 'active' : '' }}" data-case-custom-toggle aria-expanded="false">
                    <i class="bi bi-calendar3"></i>
                    <span>Custom Range</span>
                    <i class="bi bi-chevron-down case-compact-date-chev"></i>
                </button>

                <div class="case-compact-popover" data-case-custom-panel hidden>
                    <div class="case-compact-pop-label">Date Encoded / Date Created Range</div>
                    <div class="case-compact-pop-fields">
                        <div class="case-compact-pop-field">
                            <label>Date Encoded From</label>
                            <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="case-compact-pop-input">
                        </div>
                        <div class="case-compact-pop-field">
                            <label>Date Encoded To</label>
                            <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="case-compact-pop-input">
                        </div>
                    </div>
                    <div class="case-compact-pop-actions">
                        <button type="submit" name="date_preset" value="CUSTOM" class="case-compact-pop-apply">Apply</button>
                        <a href="{{ $resetUrl }}" class="case-compact-pop-reset">Reset</a>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="case-compact-more {{ $advancedFilterCount > 0 ? 'active' : '' }}" data-case-more-toggle aria-expanded="false">
            <i class="bi bi-sliders"></i>
            <span data-case-more-text>More Filters</span>
            <i class="bi bi-chevron-down" data-case-more-icon></i>
        </button>
    </div>
    <div class="case-compact-advanced" data-case-more-panel hidden>
        <div class="case-compact-advanced-head">
            <div>
                <div class="case-compact-advanced-title">More Filters</div>
                <div class="case-compact-advanced-note">
                    Date presets use encoded date. Interment fields narrow the service schedule.
                </div>
            </div>
            <a href="{{ $resetUrl }}" class="case-compact-advanced-clear">
                <i class="bi bi-x-circle"></i>
                <span>Clear filters</span>
            </a>
        </div>

        @if($showCaseStatus)
            <div class="case-compact-field">
                <label>Case Status</label>
                <select name="case_status" class="case-compact-input">
                    <option value="">All Case Status</option>
                    <option value="DRAFT" @selected(request('case_status') === 'DRAFT')>Draft</option>
                    <option value="ACTIVE" @selected(request('case_status') === 'ACTIVE')>Active</option>
                    <option value="COMPLETED" @selected(request('case_status') === 'COMPLETED')>Completed</option>
                </select>
            </div>
        @endif

        @if($showPaymentStatus)
            <div class="case-compact-field">
                <label>Payment Status</label>
                <select name="payment_status" class="case-compact-input">
                    <option value="">All Payment Status</option>
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
                <label>Package</label>
                <select name="package_id" class="case-compact-input">
                    <option value="">All Packages</option>
                    @foreach(($packages ?? collect()) as $package)
                        <option value="{{ $package->id }}" @selected((string) request('package_id') === (string) $package->id)>{{ $package->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if($showEncodedBy)
            <div class="case-compact-field">
                <label>Encoded By</label>
                <select name="encoded_by" class="case-compact-input">
                    <option value="">All Encoders</option>
                    @foreach(($encoders ?? collect()) as $encoder)
                        <option value="{{ $encoder->id }}" @selected((string) request('encoded_by') === (string) $encoder->id)>{{ $encoder->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if($showInterment)
            <div class="case-compact-field">
                <label>Interment Date From</label>
                <input type="date" name="interment_from" value="{{ $intermentFrom ?? '' }}" class="case-compact-input">
            </div>
            <div class="case-compact-field">
                <label>Interment Date To</label>
                <input type="date" name="interment_to" value="{{ $intermentTo ?? '' }}" class="case-compact-input">
            </div>
        @endif
    </div>

    <div class="case-compact-search-row">
        @if($showSearch)
            <div class="case-compact-field case-compact-search-field">
                <label>Search</label>
                <input name="q" value="{{ request('q') }}" class="case-compact-input" placeholder="Search case, client, or deceased...">
            </div>
        @endif

        <div class="case-compact-actions">
            <a href="{{ $resetUrl }}" class="case-compact-reset">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Reset</span>
            </a>
            <button type="submit" class="case-compact-apply">
                <i class="bi bi-funnel"></i>
                <span>Apply</span>
            </button>
        </div>
    </div>

    

    @if($filterChips->isNotEmpty())
        <div class="case-compact-chips" aria-label="Active filters">
            @foreach($filterChips as $chip)
                <span class="case-compact-chip"><i class="bi {{ $chip['icon'] }}"></i>{{ $chip['label'] }}</span>
            @endforeach
        </div>
    @endif
</form>
