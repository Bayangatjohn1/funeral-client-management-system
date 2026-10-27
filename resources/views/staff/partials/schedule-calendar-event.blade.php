@php
    $kind = str_contains($event['type'], 'wake') ? (str_contains($event['type'], 'wake_end') ? 'wake wake-end' : 'wake wake-start') : (str_contains($event['type'], 'interment') ? 'interment' : 'ceremony');
@endphp
<button type="button" class="calendar-event {{ $kind }} {{ (int)($focusedCaseId ?? 0) === (int)$event['case_id'] ? 'is-focused' : '' }}" data-calendar-event="event-{{ $event['case_id'] }}-{{ $event['type'] }}">
    <span class="calendar-event-type">{{ $event['label'] }}</span>
    <strong>{{ $event['case_code'] }}</strong>
    <span class="calendar-event-name">{{ $event['deceased_name'] }}</span>
    @if($allowAllBranches ?? false)<small>{{ $event['branch_label'] }}</small>@endif
</button>
