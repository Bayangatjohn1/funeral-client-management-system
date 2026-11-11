@php
    $today=now(config('app.audit_timezone','Asia/Manila'));
    $ranges=['All Dates'=>['',''],'Today'=>[$today->toDateString(),$today->toDateString()],'This Week'=>[$today->copy()->startOfWeek()->toDateString(),$today->copy()->endOfWeek()->toDateString()],'This Month'=>[$today->copy()->startOfMonth()->toDateString(),$today->copy()->endOfMonth()->toDateString()],'This Year'=>[$today->copy()->startOfYear()->toDateString(),$today->copy()->endOfYear()->toDateString()]];
    $dateLabel='CUSTOM';foreach($ranges as $label=>$range)if(($filters['date_from']??'')===$range[0]&&($filters['date_to']??'')===$range[1])$dateLabel=$label;
    $fields=[];
    if(!($isBranchAdminView??false))$fields['branch_id']=['label'=>'Branch','options'=>[''=>'All Branches']+$branches->mapWithKeys(fn($branch)=>[$branch->id=>$branch->branch_code.' - '.$branch->branch_name])->all()];
    $fields['user_id']=['label'=>'User','options'=>[''=>'All Users']+$users->pluck('name','id')->all()];
    $fields['action_type']=['label'=>'Action','options'=>[''=>'Any Action']+collect($actionTypes)->mapWithKeys(fn($type)=>[$type=>ucfirst($type)])->all()];
    $activeCount=collect($fields)->filter(fn($field,$key)=>filled($filters[$key]??null))->count()+((!empty($filters['date_from'])||!empty($filters['date_to']))?1:0)+(!empty($filters['audit_log_id'])?1:0);
@endphp
<form method="GET" action="{{ route('admin.audit-logs.index') }}" class="worklist-toolbar worklist-audit-toolbar uniform-record-filters" data-worklist-form>
    <div class="worklist-search-track">
        <div class="worklist-search-control shared-record-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" data-shared-search-ready="1" data-worklist-search aria-label="Search audit logs" aria-controls="auditSearchMatches" aria-expanded="false" aria-autocomplete="list" autocomplete="off" placeholder="User name, action or event ID">
            <button type="button" class="worklist-search-clear" data-worklist-search-clear aria-label="Clear search" @if(!filled($filters['q'] ?? null)) hidden @endif><i class="bi bi-x" aria-hidden="true"></i></button>
            <button type="button" class="worklist-search-submit" data-worklist-search-submit>Search</button>
            <div class="worklist-search-matches" id="auditSearchMatches" data-worklist-matches role="listbox" aria-label="Matching audit events" hidden></div>
        </div>
    </div>
    @if(!empty($filters['audit_log_id']))<input type="hidden" name="audit_log_id" value="{{ $filters['audit_log_id'] }}">@endif
    @foreach(['action', 'entity_type'] as $legacyFilter)
        @if(filled($filters[$legacyFilter] ?? null))<input type="hidden" name="{{ $legacyFilter }}" value="{{ $filters[$legacyFilter] }}">@endif
    @endforeach
    <details class="worklist-filter" data-worklist-filter>
        <summary class="worklist-control"><i class="bi bi-funnel" aria-hidden="true"></i><span>Filter</span><span data-worklist-count @if(!$activeCount) hidden @endif>{{ $activeCount }}</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
        <div class="worklist-filter-panel">
            <header><strong>Filter by</strong><button type="button" data-worklist-close aria-label="Close filters"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
            <div class="worklist-filter-fields">
                @foreach($fields as $key=>$field)
                    <label>{{ $field['label'] }}<span class="table-toolbar-select-wrap worklist-control"><i class="bi bi-funnel table-toolbar-leading-icon" aria-hidden="true"></i><select name="{{ $key }}" class="form-select" data-worklist-pending aria-label="{{ $field['label'] }}">@foreach($field['options'] as $value=>$label)<option value="{{ $value }}" @selected((string)($filters[$key]??'')===(string)$value)>{{ $label }}</option>@endforeach</select><i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i></span></label>
                @endforeach
                <label>Event date<span class="table-toolbar-select-wrap worklist-control"><i class="bi bi-calendar3 table-toolbar-leading-icon" aria-hidden="true"></i><select class="form-select" data-worklist-date-preset aria-label="Event date">@foreach($ranges as $label=>$range)<option value="{{ $label }}" data-from="{{ $range[0] }}" data-to="{{ $range[1] }}" @selected($dateLabel===$label)>{{ $label }}</option>@endforeach<option value="CUSTOM" @selected($dateLabel==='CUSTOM')>Custom Range</option></select><i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i></span></label>
            </div>
            <footer><button type="submit" class="worklist-apply">Apply filters</button></footer>
        </div>
    </details>
    <input type="hidden" name="date_from" value="{{ $filters['date_from']??'' }}"><input type="hidden" name="date_to" value="{{ $filters['date_to']??'' }}">
    <label class="worklist-page-size">Events per page<span class="table-toolbar-select-wrap worklist-control"><select name="per_page" class="form-select" data-worklist-page-size aria-label="Events per page">@foreach($perPageOptions as $size)<option value="{{ $size }}" @selected((int)($filters['per_page']??25)===$size)>{{ $size }}</option>@endforeach</select><i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i></span></label>
    <a href="{{ route('admin.audit-logs.exportPdf', request()->except('page')) }}" class="audit-action audit-print-preview" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i><span>Print Preview</span></a>
    <div class="worklist-applied" data-worklist-applied>
        @php
            $chips = [];
            foreach ($fields as $key => $field) {
                if (filled($filters[$key] ?? null)) {
                    $chips[] = [$field['label'].': '.($field['options'][$filters[$key]] ?? $filters[$key]), [$key]];
                }
            }
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $chips[] = ['Event date: '.($filters['date_from'] ?? 'Start').' – '.($filters['date_to'] ?? 'Today'), ['date_from', 'date_to']];
            }
            if (!empty($filters['audit_log_id'])) {
                $chips[] = ['Event #'.$filters['audit_log_id'], ['audit_log_id']];
            }
        @endphp
        @foreach($chips as [$label, $keys])
            <span class="catalog-filter-chip worklist-chip">
                <span>{{ $label }}</span>
                <a class="worklist-chip-remove" href="{{ route('admin.audit-logs.index', request()->except([...$keys, 'page'])) }}" aria-label="Remove {{ $label }} filter"><i class="bi bi-x" aria-hidden="true"></i></a>
            </span>
        @endforeach
        <a href="{{ route('admin.audit-logs.index',request()->only('per_page', 'q')) }}" class="filter-clear-action worklist-clear" @if(!$activeCount) hidden @endif>Clear filters</a>
    </div>
    <div popover="manual" role="dialog" aria-label="Custom event date range" class="worklist-date-panel" data-worklist-date-panel>
        <strong>Custom Date Range</strong><label>From<input type="date" data-date-from></label><label>To<input type="date" data-date-to></label>
        <div><button type="button" data-date-cancel>Cancel</button><button type="button" data-date-use>Use date range</button></div>
    </div>
</form>
