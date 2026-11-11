@php
    $directorySearchName = $directoryKind === 'branches' ? $searchRecord->branch_code : $searchRecord->name;
    $directorySearchDetail = $directoryKind === 'branches' ? $searchRecord->branch_name : $searchRecord->email;
    $directorySearchFields = $directoryKind === 'branches'
        ? [$searchRecord->branch_code,$searchRecord->branch_name,$searchRecord->address]
        : array_merge([$searchRecord->name,$searchRecord->email],auth()->user()?->isSystemAdmin()?[$searchRecord->contact_number,$searchRecord->branch?->branch_name]:[]);
@endphp
data-worklist-search-id="{{ $searchRecord->id }}" data-worklist-name="{{ $directorySearchName }}" data-worklist-detail="{{ $directorySearchDetail }}" data-worklist-fields="{{ json_encode(array_values(array_filter($directorySearchFields,fn($value)=>filled($value))),JSON_UNESCAPED_UNICODE) }}"
