@php
    $pmFilterChips = collect();
    if (filled($q ?? null)) $pmFilterChips->push(['key'=>'q','label'=>'Search: '.$q]);
    if ($branchFilterIsActive) {
        $selectedPaymentBranch = $branches->firstWhere('id', $selectedBranchId);
        $pmFilterChips->push(['key'=>'branch_id','label'=>'Branch: '.($selectedPaymentBranch ? $selectedPaymentBranch->branch_code.' - '.$selectedPaymentBranch->branch_name : 'All Branches')]);
    }
    if (filled($paymentStatus)) $pmFilterChips->push(['key'=>'payment_status','label'=>'Payment status: '.(['HAS_PAYMENT'=>'With Payments','WITH_BALANCE'=>'With Balance'][$paymentStatus] ?? \Illuminate\Support\Str::headline(strtolower($paymentStatus)))]);
    if ($dateRange !== 'all' && $dateRange !== 'any') $pmFilterChips->push(['key'=>'date_preset','label'=>'Payment date: '.($dateRange === 'custom' ? ($paidFrom ?: 'Any date').' – '.($paidTo ?: 'Any date') : \Illuminate\Support\Str::headline($dateRange))]);
    if (filled($caseStatus ?? null)) $pmFilterChips->push(['key'=>'case_status','label'=>'Case status: '.\Illuminate\Support\Str::headline(strtolower($caseStatus))]);
    if (filled($paymentMethod ?? null)) $pmFilterChips->push(['key'=>'payment_method','label'=>'Method: '.\Illuminate\Support\Str::headline($paymentMethod)]);
    $pmClearFilterUrl = route($monitoringRoute, array_filter(['q'=>$q ?? null,'tab'=>$activeTab,'sort'=>$sort ?? null,'per_page'=>request('per_page'),'case_scope'=>$caseScopePreset ?? null], fn ($value) => $value !== null && $value !== ''));
    $pmAdditionalFilterCount = $pmFilterChips->reject(fn ($chip) => $chip['key'] === 'q')->count();
@endphp
    <div class="pm-toolbar-shell ops-toolbar-shell">
        <form id="pmFilterForm" method="GET" action="{{ route($monitoringRoute) }}" class="pm-toolbar ops-toolbar uniform-record-filters" data-pm-default-branch="{{ $defaultPaymentBranchId ?? '' }}">
            @if($caseScopePreset ?? null)
                <input type="hidden" name="case_scope" value="{{ $caseScopePreset }}">
            @endif
            <input type="hidden" name="tab" value="{{ $activeTab }}">

            <div class="pm-field search has-icon ops-field ops-field--search shared-record-search">
                <i class="bi bi-search shared-record-search-icon" aria-hidden="true"></i>
                <input class="pm-control ops-control shared-record-search-input" name="q" value="{{ $q ?? '' }}" placeholder="e.g., FC0012, Santos, OR-203456 or TXN-9876" aria-label="Search case, client/deceased name, OR, payment record, or transaction reference" aria-controls="pmSearchMatches" aria-expanded="false" aria-autocomplete="list" autocomplete="off" data-pm-search-input>
                <button type="button" class="pm-search-clear shared-record-search-clear" data-pm-search-clear @if(blank($q ?? null)) hidden @endif aria-label="Clear search">
                    <i class="bi bi-x" aria-hidden="true"></i>
                </button>
                <button type="button" class="pm-search-button" data-pm-search-submit>Search</button>
                <div id="pmSearchMatches" class="pm-search-matches" data-pm-search-matches hidden></div>
            </div>

            <details class="pm-more-filters">
                <summary aria-controls="pmFilterPanel"><i class="bi bi-funnel" aria-hidden="true"></i><span>Filter</span>@if($pmAdditionalFilterCount)<span class="pm-filter-count">{{ $pmAdditionalFilterCount }}</span>@endif<i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                <div class="pm-more-panel" id="pmFilterPanel" role="group" aria-label="Payment filters">
                <div class="pm-filter-head"><strong>Filter by</strong><button type="button" data-pm-filter-close aria-label="Close filters"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
                <div class="pm-filter-fields">
            @if(!$isStaff && ($isMainAdmin || !$isBranchOnly))
                <label class="pm-filter-label" for="pmBranch">Branch</label>
                <div class="pm-field branch has-icon ops-field">
                    <i class="bi bi-building" aria-hidden="true"></i>
                    <select id="pmBranch" name="branch_id" class="pm-control ops-control" title="Branch">
                        <option value="all" @selected(($selectedBranchId ?? null) === 'all')>All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) ($selectedBranchId ?? '') === (string) $branch->id)>
                                {{ $branch->branch_code }} - {{ $branch->branch_name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
                </div>
            @endif

            <label class="pm-filter-label" for="pmPaymentStatus">Payment status</label>
            <div class="pm-field has-icon ops-field">
                <i class="bi bi-credit-card" aria-hidden="true"></i>
                <select id="pmPaymentStatus" name="payment_status" class="pm-control ops-control" title="Payment Status">
                    <option value="">All Payment Statuses</option>
                    <option value="HAS_PAYMENT" @selected($paymentStatus === 'HAS_PAYMENT')>With Payments</option>
                    <option value="WITH_BALANCE" @selected($paymentStatus === 'WITH_BALANCE')>With Balance</option>
                    <option value="UNPAID" @selected($paymentStatus === 'UNPAID')>Unpaid</option>
                    <option value="PARTIAL" @selected($paymentStatus === 'PARTIAL')>Partial</option>
                    <option value="PAID" @selected($paymentStatus === 'PAID')>Paid</option>
                </select>
                <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
            </div>

            <label class="pm-filter-label" for="pmDateRange">Payment date</label>
            <div class="pm-field has-icon ops-field">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <select id="pmDateRange" name="date_preset" class="pm-control ops-control" title="Date Range">
                    <option value="all" @selected($dateRange === 'all' || $dateRange === 'any')>All Dates</option>
                    <option value="today" @selected($dateRange === 'today')>Today</option>
                    <option value="week" @selected($dateRange === 'week')>This Week</option>
                    <option value="month" @selected($dateRange === 'month')>This Month</option>
                    <option value="year" @selected($dateRange === 'year')>This Year</option>
                    <option value="custom" @selected($dateRange === 'custom')>Custom</option>
                </select>
                <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
            </div>


            <label class="pm-filter-label" for="pmCaseStatus">Case status</label>
            <div class="pm-field has-icon ops-field">
                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                <select id="pmCaseStatus" name="case_status" class="pm-control ops-control" title="Case Status">
                    <option value="">All Cases</option>
                    <option value="DRAFT" @selected(($caseStatus ?? '') === 'DRAFT')>Draft</option>
                    <option value="ACTIVE" @selected(($caseStatus ?? '') === 'ACTIVE')>Active</option>
                    <option value="COMPLETED" @selected(($caseStatus ?? '') === 'COMPLETED')>Completed</option>
                </select>
                <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
            </div>

            <label class="pm-filter-label" for="pmMethod">Payment method</label>
            <div class="pm-field has-icon ops-field">
                <i class="bi bi-wallet2" aria-hidden="true"></i>
                <select id="pmMethod" name="payment_method" class="pm-control ops-control" title="Payment Method">
                    <option value="">All Methods</option>
                    <option value="cash" @selected(($paymentMethod ?? '') === 'cash')>Cash</option>
                    <option value="cashless" @selected(($paymentMethod ?? '') === 'cashless')>Cashless</option>
                </select>
                <i class="bi bi-chevron-down pm-sel-chev" aria-hidden="true"></i>
            </div>

                </div>
                <div class="pm-filter-apply"><button type="submit" class="pm-btn primary" data-pm-apply-filters>Apply filters</button></div>
                </div>
            </details>

            <div class="pm-field has-icon pm-sort-control shared-record-filter" data-filter-select-wrap>
                <i class="bi bi-sort-down" aria-hidden="true"></i>
                <select name="sort" class="pm-control ops-control" aria-label="Sort cases">
                    <option value="desc" data-menu-label="Latest Payment" @selected(($sort ?? 'desc') === 'desc')>Sort</option>
                    <option value="asc" data-menu-label="Oldest Payment" @selected(($sort ?? '') === 'asc')>Oldest Payment</option>
                    <option value="balance_high" @selected(($sort ?? '') === 'balance_high')>Highest Balance</option>
                    <option value="balance_low" @selected(($sort ?? '') === 'balance_low')>Lowest Balance</option>
                </select>
                <i class="bi bi-chevron-down pm-sel-chev shared-record-chevron" aria-hidden="true"></i>
            </div>
            <div class="pm-hidden-date-fields">
                <input id="pmPaidFrom" type="date" name="paid_from" value="{{ $paidFrom ?? '' }}">
                <input id="pmPaidTo" type="date" name="paid_to" value="{{ $paidTo ?? '' }}">
            </div>

            <label class="pm-page-size" for="pmPerPage">Cases per page
                <span class="pm-page-size-control global-filter-select-wrap shared-record-filter shared-record-page-size" data-filter-select-wrap>
                    <select id="pmPerPage" data-plain-options name="per_page" class="pm-control ops-control" aria-label="Cases per page">
                        @foreach([10,20,50,100] as $pageSize)
                            <option value="{{ $pageSize }}" @selected(($activeTab === 'transactions' ? $transactionCases : $paymentCases)->perPage() === $pageSize)>{{ $pageSize }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down table-toolbar-select-icon shared-record-chevron" aria-hidden="true" data-filter-select-icon></i>
                </span>
            </label>
            @hasSection('record_toolbar_action')
                <div class="record-toolbar-action">@yield('record_toolbar_action')</div>
            @endif
            <div class="pm-applied-filters" @if(!$hasPaymentFilters) hidden @endif>
                <div class="pm-filter-chips" aria-label="Applied payment filters">
                @foreach($pmFilterChips as $chip)
                    <span class="catalog-filter-chip" data-tone="neutral">{{ $chip['label'] }}<button type="button" data-pm-remove-filter="{{ $chip['key'] }}" aria-label="Remove {{ $chip['label'] }}"><i class="bi bi-x" aria-hidden="true"></i></button></span>
                @endforeach
                </div>
            <div class="pm-actions">
                <a href="{{ $pmClearFilterUrl }}" class="pm-btn ops-btn-outline filter-clear-action" data-pm-clear-filters @if(!$pmAdditionalFilterCount) hidden @endif><span>Clear filters</span></a>
            </div>
            </div>
        </form>
    </div>
