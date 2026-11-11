@php($showActions = $showActions ?? true)
<div class="pm-record-head {{ $showActions ? '' : 'pm-record-head--no-actions' }}" aria-hidden="true">
    <span>Case</span>
    <span>Client / Deceased</span>
    <span>Last Payment</span>
    <span>Status</span>
    @if($showActions)<span>Actions</span>@endif
</div>
