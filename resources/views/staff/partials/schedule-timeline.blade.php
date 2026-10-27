<div class="schedule-timeline" aria-label="Schedule timeline">
    @foreach(collect($timelineEvents)->sortBy(fn ($event) => !empty($event['has_time']) ? $event['sort_date']->timestamp : PHP_INT_MAX)->groupBy(fn ($event) => !empty($event['has_time']) ? $event['date']->format('H:i') : 'unknown') as $slot => $events)
        <section class="schedule-slot">
            <div class="schedule-slot-time">
                <strong>{{ $slot === 'unknown' ? 'Time not recorded' : $events->first()['date']->format('g:i A') }}</strong>
                @if($slot !== 'unknown' && $events->count() > 1)<span class="schedule-same-time">Same time · {{ $events->count() }} events</span>@endif
            </div>
            <div class="schedule-slot-events">
                @foreach($events as $event)
                    <article class="schedule-event {{ (int)($timelineFocus ?? 0) === (int)$event['case_id'] ? 'is-selected' : '' }}" data-timeline-case="{{ $event['case_id'] }}">
                        <div class="schedule-event-top"><span class="schedule-event-label"><i class="bi {{ str_contains($event['type'], 'interment') ? 'bi-geo-alt' : 'bi-calendar-event' }}" aria-hidden="true"></i> {{ $event['label'] }}</span><span class="schedule-event-status">{{ $event['case']->case_status === 'COMPLETED' ? 'Completed' : 'Ongoing' }}</span></div>
                        <a class="schedule-event-case" href="{{ route('funeral-cases.show', ['funeral_case' => $event['case_id'], 'return_to' => request()->fullUrl()]) }}">{{ $event['case_code'] }} <span>· {{ $event['deceased_name'] }}</span><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                        <p><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $event['location'] ?: 'Location not recorded' }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
