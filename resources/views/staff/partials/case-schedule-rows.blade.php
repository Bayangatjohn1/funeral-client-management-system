<div class="case-schedule-rows">
    @foreach($caseEvents as $item)
        <div class="case-schedule-row" data-case-event="{{ $item['type'] }}">
            <div><strong>{{ $item['label'] }}</strong><span class="case-picked-label">Selected event</span></div>
            <div><span>{{ $item['date']?->format('M d, Y') ?? 'Date not recorded' }}</span><strong>{{ $item['date'] && $item['has_time'] ? $item['date']->format('g:i A') : 'Time not recorded' }}</strong>
            @if($showCaseLocations ?? false)<small>{{ $item['location'] ?: 'Location not recorded' }}</small>@endif</div>
        </div>
    @endforeach
</div>
