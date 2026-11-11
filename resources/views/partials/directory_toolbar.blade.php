@php
    $isUsers = $directoryKind === 'users';
    $isClients = $directoryKind === 'clients';
    $systemDirectory = auth()->user()?->isSystemAdmin();
    $sortChoices = $isUsers
        ? ($systemDirectory ? ['latest'=>'Newest first','name_asc'=>'Name A–Z','role_asc'=>'Role A–Z','branch_asc'=>'Branch A–Z'] : ['latest'=>'Newest first','oldest'=>'Oldest first','name_asc'=>'Name A–Z'])
        : ['code_asc'=>'Branch ID','name_asc'=>'Branch Name A–Z','records_desc'=>'Most Records','records_asc'=>'Fewest Records','revenue_desc'=>'Highest Sales','revenue_asc'=>'Lowest Sales'];
    $fields = ['status' => ['label'=>'Status','options'=>[''=>'All Statuses','active'=>'Active','inactive'=>'Inactive']]];
    if ($isUsers && $systemDirectory) {
        $fields = ['branch_id'=>['label'=>'Branch','options'=>[''=>'All Branches'] + $branches->mapWithKeys(fn($branch)=>[$branch->id=>$branch->branch_code.' - '.$branch->branch_name])->all()]] + $fields;
        $fields['role'] = ['label'=>'Role','options'=>[''=>'All Roles','admin'=>'Admin','staff'=>'Staff']];
    }
    $scopeFields = $isUsers && $systemDirectory ? ['assignment'=>'Assignment','admin_scope'=>'Admin scope'] : [];
    $activeCount = collect($fields + $scopeFields)->filter(fn($field,$key)=>request()->filled($key))->count();
    $clearQuery = request()->except(array_merge(array_keys($fields),['page','assignment','admin_scope']));
    $directoryRoute = $isUsers ? 'admin.users.index' : 'admin.branches.index';
    $selectedSort = request('sort',$isUsers?'latest':'code_asc');
    if ($isClients) {
        $directoryRoute = 'clients.index';
        $sortChoices = ['newest'=>'Newest first','oldest'=>'Oldest first','name_asc'=>'Name A–Z','name_desc'=>'Name Z–A'];
        $selectedSort = request('sort', 'newest');
        $selectedDate = request('date_range', request()->filled('added_from') || request()->filled('added_to') ? 'custom' : 'any');
        $fields = ['date_range' => ['label'=>'Date added','options'=>['any'=>'All Dates','today'=>'Today','7d'=>'Last 7 Days','30d'=>'Last 30 Days','this_month'=>'This Month','custom'=>'Custom Range']]];
        $clientScopeLabels = ['needs_attention'=>'Needs attention','recent'=>'Recently added','with_balance'=>'With balance'];
        $clientScopeLabel = $clientScopeLabels[request('type_filter')] ?? null;
        $activeCount = (int) ($selectedDate !== 'any') + (int) ($clientScopeLabel !== null);
        $clearQuery = request()->except(['date_range','added_from','added_to','type_filter','page']);
    }
@endphp
<form method="GET" action="{{ route($directoryRoute) }}" class="table-toolbar uniform-record-filters worklist-toolbar" data-worklist-form data-table-toolbar>
    @if($isClients)
        <input type="hidden" name="added_from" value="{{ $selectedDate === 'custom' ? request('added_from') : '' }}">
        <input type="hidden" name="added_to" value="{{ $selectedDate === 'custom' ? request('added_to') : '' }}">
        <input type="hidden" name="type_filter" value="{{ request('type_filter') }}">
    @endif
    @foreach(array_merge(['highlight_branch','return_to','per_page'],array_keys($scopeFields)) as $preserved)
        @if(request()->filled($preserved))<input type="hidden" name="{{ $preserved }}" value="{{ request($preserved) }}">@endif
    @endforeach
    <div class="worklist-search-track">
        <div class="worklist-search-control shared-record-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ request('q') }}" maxlength="100" data-shared-search-ready="1" data-worklist-search aria-label="Search {{ $directoryKind }}" aria-controls="{{ $directoryKind }}SearchMatches" aria-expanded="false" aria-autocomplete="list" autocomplete="off" placeholder="{{ $isClients ? 'Name, mobile number or address' : ($isUsers ? ($systemDirectory ? 'Name, email, contact number or branch' : 'Staff name or email') : 'Branch code, name or address') }}">
            <button type="button" class="worklist-search-clear" data-worklist-search-clear aria-label="Clear search" @if(!request()->filled('q')) hidden @endif><i class="bi bi-x" aria-hidden="true"></i></button>
            <button type="button" class="worklist-search-submit" data-worklist-search-submit>Search</button>
            <div class="worklist-search-matches" id="{{ $directoryKind }}SearchMatches" data-worklist-matches role="listbox" aria-label="Matching {{ $directoryKind }}" hidden></div>
        </div>
    </div>
    <details class="worklist-filter" data-worklist-filter>
        <summary class="worklist-control"><i class="bi bi-funnel" aria-hidden="true"></i><span>Filter</span><span data-worklist-count @if(!$activeCount) hidden @endif>{{ $activeCount }}</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
        <div class="worklist-filter-panel">
            <header><strong>Filter by</strong><button type="button" data-worklist-close aria-label="Close filters"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
            <div class="worklist-filter-fields">
                @foreach($fields as $key=>$field)
                    <label>{{ $field['label'] }}<span class="table-toolbar-select-wrap worklist-control"><i class="bi {{ $isClients ? 'bi-calendar3' : 'bi-funnel' }} table-toolbar-leading-icon" aria-hidden="true"></i><select name="{{ $key }}" class="form-select" data-worklist-pending @if($isClients) data-worklist-date-preset @endif aria-label="{{ $field['label'] }}">@foreach($field['options'] as $value=>$label)<option value="{{ $value }}" @selected((string)($isClients ? $selectedDate : request($key,''))===(string)$value)>{{ $label }}</option>@endforeach</select><i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i></span></label>
                @endforeach
            </div>
            <footer><button type="submit" class="worklist-apply">Apply filters</button></footer>
        </div>
    </details>
    <div class="table-toolbar-select-wrap worklist-control worklist-sort-control"><i class="bi bi-sort-down table-toolbar-leading-icon" aria-hidden="true"></i><span class="worklist-sort-label" data-worklist-sort-label>Sort: {{ $sortChoices[$selectedSort] ?? reset($sortChoices) }}</span><select name="sort" class="form-select" data-worklist-sort aria-label="Sort {{ $directoryKind }}">@foreach($sortChoices as $value=>$label)<option value="{{ $value }}" @selected($selectedSort===$value)>{{ $label }}</option>@endforeach</select><i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i></div>
    @unless($isClients)
    <div class="directory-view-toggle {{ $isUsers ? 'user-directory-view-toggle' : 'branch-view-toggle' }} catalog-view-switch" role="group" aria-label="{{ $isUsers ? 'Directory view' : 'Branch display mode' }}">
        <button type="button" @click="setView('card')" :aria-pressed="view === 'card'" :class="view === 'card' ? 'is-active' : ''" aria-label="Grid view" title="Grid view"><i class="bi bi-grid" aria-hidden="true"></i><span>Grid</span></button>
        <button type="button" @click="setView('table')" :aria-pressed="view === 'table'" :class="view === 'table' ? 'is-active' : ''" aria-label="Table view" title="Table view"><i class="bi bi-table" aria-hidden="true"></i><span>Table</span></button>
    </div>
    @endunless
    @hasSection('record_toolbar_action')
        <div class="record-toolbar-action">@yield('record_toolbar_action')</div>
    @endif
    <div class="worklist-applied" data-worklist-applied>
        @foreach($fields as $key=>$field)
            @if($isClients ? $selectedDate !== 'any' : request()->filled($key))
                @php
                    $chipValue = $isClients ? $selectedDate : (string) request($key);
                    $chipTone = match (true) {
                        $key === 'status' && $chipValue === 'active' => 'positive',
                        $key === 'status' && $chipValue === 'inactive' => 'caution',
                        in_array($key, ['branch_id', 'role'], true) => 'category',
                        default => 'neutral',
                    };
                    $chipLabel = $field['label'].': '.($field['options'][$chipValue] ?? $chipValue);
                    if ($isClients && $chipValue === 'custom') {
                        $dates = array_filter([request('added_from'), request('added_to')]);
                        if ($dates) $chipLabel .= ' · '.implode(' – ', $dates);
                    }
                @endphp
                <span class="catalog-filter-chip worklist-chip" data-worklist-chip data-tone="{{ $chipTone }}" data-filter-key="{{ $key }}" data-filter-value="{{ $chipValue }}">
                    <span>{{ $chipLabel }}</span>
                    <a class="worklist-chip-remove" href="{{ route($directoryRoute,request()->except($isClients ? ['date_range','added_from','added_to','page'] : [$key,'page'])) }}" aria-label="Remove {{ $chipLabel }} filter"><i class="bi bi-x" aria-hidden="true"></i></a>
                </span>
            @endif
        @endforeach
        @if($isClients && $clientScopeLabel)
            <span class="catalog-filter-chip worklist-chip" data-worklist-chip data-tone="neutral">
                <span>{{ $clientScopeLabel }}</span>
                <a class="worklist-chip-remove" href="{{ route($directoryRoute, request()->except(['type_filter','page'])) }}" aria-label="Remove {{ $clientScopeLabel }} filter"><i class="bi bi-x" aria-hidden="true"></i></a>
            </span>
        @endif
        @foreach($scopeFields as $key=>$label)
            @if(request()->filled($key))
                @php($scopeChipLabel = $label.': '.ucfirst(request($key)))
                <span class="catalog-filter-chip worklist-chip" data-worklist-chip data-tone="{{ $key === 'assignment' ? 'caution' : 'category' }}" data-filter-key="{{ $key }}" data-filter-value="{{ request($key) }}">
                    <span>{{ $scopeChipLabel }}</span>
                    <a class="worklist-chip-remove" href="{{ route($directoryRoute,request()->except([$key,'page'])) }}" aria-label="Remove {{ $scopeChipLabel }} filter"><i class="bi bi-x" aria-hidden="true"></i></a>
                </span>
            @endif
        @endforeach
        <a href="{{ route($directoryRoute,$clearQuery) }}" class="filter-clear-action worklist-clear" @if(!$activeCount && !request()->filled('assignment') && !request()->filled('admin_scope')) hidden @endif>Clear filters</a>
    </div>
    @if($isClients)
        <div popover="manual" role="dialog" aria-label="Custom date added range" class="worklist-date-panel" data-worklist-date-panel data-custom-value="custom" data-from-name="added_from" data-to-name="added_to" data-optional-bounds>
            <strong>Custom Date Range</strong>
            <label>From <input type="date" data-date-from></label>
            <label>To <input type="date" data-date-to></label>
            <div><button type="button" data-date-cancel>Cancel</button><button type="button" data-date-use>Use date range</button></div>
        </div>
    @endif
</form>
