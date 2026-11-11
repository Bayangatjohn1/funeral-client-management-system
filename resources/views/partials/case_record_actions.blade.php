@php
    $canEditCase = ($case->entry_source ?? 'MAIN') !== 'OTHER_BRANCH' && auth()->user()?->can('update', $case);
    $canPrintCase = auth()->user()?->can('view', $case);
@endphp
<div class="case-record-actions">
    <a class="case-record-icon-action case-record-action-view primary-view-action record-action-control" data-case-summary-open aria-haspopup="dialog" aria-controls="caseSummaryDrawer" aria-expanded="false" href="{{ route(auth()->user()?->isOwner() ? 'owner.cases.show' : 'funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}" aria-label="View case {{ $case->case_code }}" data-action-tooltip="View case"><i class="bi bi-eye" aria-hidden="true"></i></a>
    @if($canEditCase || $canPrintCase)
        <div class="row-action-menu compact-action-menu" data-row-menu>
            <button type="button" class="row-action-trigger compact-action-trigger record-action-control" data-row-menu-trigger data-action-tooltip="More actions" aria-label="More actions for case {{ $case->case_code }}" aria-haspopup="menu" aria-expanded="false"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
            <div class="row-action-dropdown" role="menu">
                @if($canEditCase)
                    <a class="row-action-item compact-action-item case-record-action-edit" data-row-menu-item href="{{ route('funeral-cases.edit', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}"><i class="bi bi-pencil" aria-hidden="true"></i><span>Edit case</span></a>
                @endif
                @if($canPrintCase)
                    <a class="row-action-item compact-action-item case-record-action-print" data-row-menu-item href="{{ route('funeral-cases.print', $case) }}" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i><span>Print record</span></a>
                @endif
            </div>
        </div>
    @endif
</div>
