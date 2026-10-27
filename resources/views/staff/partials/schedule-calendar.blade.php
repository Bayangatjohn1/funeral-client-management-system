@php
    $days = collect(range(0, 6))->map(fn ($offset) => $weekStart->copy()->addDays($offset));
    $slots = $calendarEvents->filter(fn ($event) => $event['has_time'])->map(fn ($event) => $event['date']->format('H:i'))->unique()->sort()->values();
    $calendarQuery = request()->except(['tab', 'date', 'due_window', 'alert_type', 'calendar_date', 'calendar_view']);
    $calendarQuery['schedule_section'] = $scheduleSection;
    $calendarUrl = fn ($date, $view = null) => route($remindersRoute, array_merge($calendarQuery, ['tab' => 'schedules', 'calendar_date' => $date->toDateString(), 'calendar_view' => $view ?? $calendarView]));
    $selectedEvents = $calendarEvents->filter(fn ($event) => $event['date']->isSameDay($calendarDate))->sortBy(fn ($event) => $event['has_time'] ? $event['sort_date']->timestamp : PHP_INT_MAX)->values();
    $selectedWakes = $calendarWakes->filter(fn ($wake) => $wake['start']->toDateString() <= $calendarDate->toDateString() && $wake['end']->toDateString() >= $calendarDate->toDateString());
@endphp
<section class="schedule-calendar" data-upcoming-view="{{ $scheduleSection === 'upcoming' ? $upcomingView : 'calendar' }}" aria-label="Branch schedule calendar">
    @php
        $sections = ['ongoing' => ['Ongoing Wakes', 'Cases with a wake period currently in progress.'], 'today' => ["Today's Events", 'Wake starts and ends, funeral ceremonies, and interments scheduled today.'], 'upcoming' => ['Upcoming Events', 'Wake starts and ends, funeral ceremonies, and interments scheduled after today.']];
    @endphp
    <nav class="schedule-section-tabs" aria-label="Schedule sections">
        @foreach($sections as $key => $section)
            <a class="schedule-section-tab {{ $scheduleSection === $key ? 'is-active' : '' }}" href="{{ route($remindersRoute, array_merge(request()->except(['tab', 'schedule_section', 'calendar_date', 'calendar_view', 'date', 'due_window', 'alert_type', 'focus_case']), ['tab' => 'schedules', 'schedule_section' => $key])) }}" @if($scheduleSection === $key) aria-current="page" @endif>{{ $section[0] }} <span>{{ $sectionCounts[$key] }}</span></a>
        @endforeach
    </nav>
    <header class="calendar-toolbar">
        <div><h2>{{ $sections[$scheduleSection][0] }}</h2><p>{{ $sections[$scheduleSection][1] }}</p><small class="schedule-section-range calendar-mode">{{ $scheduleSection === 'ongoing' ? $calendarWakes->count().' cases' : ($calendarView === 'day' ? $calendarDate->format('l, M d, Y').' · '.$selectedEvents->count().' events' : $weekStart->format('M d').' – '.$weekStart->copy()->addDays(6)->format('M d, Y').' · '.$calendarEvents->count().' events this week') }}</small><small class="schedule-section-range case-mode">{{ $upcomingCases->count() }} upcoming cases{{ $scheduleSearch !== '' ? ' matching your search' : '' }}</small></div>
        @if($scheduleSection === 'upcoming')
    <div class="upcoming-view-switch" aria-label="Upcoming schedule view">
        <a class="calendar-control" data-upcoming-mode="calendar" href="{{ route($remindersRoute, array_merge(request()->query(), ['tab' => 'schedules', 'schedule_section' => 'upcoming', 'upcoming_view' => 'calendar'])) }}">Calendar</a>
        <a class="calendar-control" data-upcoming-mode="cases" href="{{ route($remindersRoute, array_merge(request()->query(), ['tab' => 'schedules', 'schedule_section' => 'upcoming', 'upcoming_view' => 'cases'])) }}">By Case</a>
    </div>
        @endif
    </header>
    @if($scheduleSection === 'upcoming')
        @include('staff.partials.upcoming-schedule-tools')

    @endif
    @if($scheduleSection === 'ongoing')
        <div class="ongoing-wake-list">
            @forelse($calendarWakes as $wake)
                @php $wakeDays = max(1, \App\Support\WakeDuration::days($wake['start']->toDateString(), $wake['end']->toDateString()) ?? 1); $wakeDay = min($wakeDays, (int) $wake['start']->copy()->startOfDay()->diffInDays(now()->startOfDay()) + 1); @endphp
                <article class="ongoing-wake-card"><header><div><strong>{{ $wake['case_code'] }} · {{ $wake['deceased_name'] }}</strong><p>{{ $wake['case']->wake_location ?: 'Location not recorded' }}</p></div><span class="ongoing-wake-state">In progress</span></header><div class="ongoing-wake-progress"><span>Day {{ $wakeDay }} of {{ $wakeDays }}</span><progress value="{{ $wakeDay }}" max="{{ $wakeDays }}" aria-label="Wake day progress"></progress></div><dl><div><dt>Wake begins</dt><dd>{{ $wake['start']->format('M d, Y · g:i A') }}</dd></div><div><dt>Wake ends</dt><dd>{{ $wake['end']->format('M d, Y · g:i A') }}</dd></div></dl>@if($allowAllBranches ?? false)<small>{{ $wake['branch_label'] }}</small>@endif<button type="button" class="calendar-control" data-calendar-event="wake-{{ $wake['case_id'] }}">View wake details <i class="bi bi-arrow-up-right" aria-hidden="true"></i></button></article>
            @empty<div class="calendar-empty"><strong>No ongoing wakes</strong><p>No cases have a wake period currently in progress.</p></div>@endforelse
        </div>
    @else
    @if($scheduleSection === 'upcoming')
        @include('staff.partials.upcoming-case-list')
    @endif
    <div class="calendar-mode">
    <div class="calendar-legend"><span><i class="calendar-dot wake"></i> Wake begins</span><span><i class="calendar-dot wake-end"></i> Wake ends</span><span><i class="calendar-dot ceremony"></i> Funeral ceremony</span><span><i class="calendar-dot interment"></i> Interment</span><small>Events show recorded start times; shared times are for coordination.</small></div>
    @if($scheduleSection === 'upcoming')
    <div class="calendar-mobile-days" aria-label="Choose schedule day">@foreach($days as $day)<a @if($day->toDateString() > now()->toDateString()) href="{{ $calendarUrl($day, 'day') }}" @else aria-disabled="true" tabindex="-1" @endif class="{{ $day->isSameDay($calendarDate) ? 'is-active' : '' }}" @if($day->isSameDay($calendarDate)) aria-current="date" @endif><span>{{ $day->format('D') }}</span><strong>{{ $day->format('d') }}</strong></a>@endforeach</div>
    @endif
    @if($calendarView === 'week')
        <div class="calendar-week" role="region" aria-label="Weekly schedules" tabindex="0">
            <div class="calendar-grid calendar-days"><div class="calendar-axis">Time</div>@foreach($days as $day)<a @if($day->toDateString() > now()->toDateString()) href="{{ $calendarUrl($day, 'day') }}" @else aria-disabled="true" tabindex="-1" @endif class="calendar-day {{ $day->isToday() ? 'is-today' : '' }}"><span>{{ $day->format('D') }}</span><strong>{{ $day->format('d') }}</strong>@if($day->isToday())<small>Today</small>@endif</a>@endforeach</div>
            @if($calendarWakes->isNotEmpty())
            <div class="calendar-wake-area"><div class="calendar-wake-label">Wake periods <small>Start → end</small></div><div class="calendar-wake-bars">
                @forelse($calendarWakes as $wake)
                    @php
                        $startOffset = max(0, (int) $weekStart->diffInDays($wake['start']->copy()->startOfDay(), false));
                        $endOffset = min(6, (int) $weekStart->diffInDays($wake['end']->copy()->startOfDay(), false));
                    @endphp
                    <div class="calendar-wake-track"><button type="button" class="calendar-wake-bar {{ $wake['active'] ? 'is-current' : '' }}" style="grid-column:{{ $startOffset + 1 }}/{{ $endOffset + 2 }}" data-calendar-event="wake-{{ $wake['case_id'] }}"><strong>{{ $wake['case_code'] }}</strong> · {{ $wake['deceased_name'] }} @if($wake['active'])<span>Currently in wake</span>@endif</button></div>
                @empty<p class="calendar-no-wakes">No wake periods during this week.</p>@endforelse
            </div></div>
            @endif
            @foreach($slots as $slot)
                <div class="calendar-grid calendar-time-row"><div class="calendar-axis">{{ \Carbon\Carbon::createFromFormat('H:i', $slot)->format('g:i A') }}</div>
                    @foreach($days as $day)
                        @php $cellEvents = $calendarEvents->filter(fn ($event) => $event['has_time'] && $event['date']->isSameDay($day) && $event['date']->format('H:i') === $slot); @endphp
                        <div class="calendar-cell {{ $day->isToday() ? 'is-today' : '' }}">
                            @if($cellEvents->count() > 1)<small class="calendar-shared">Same time · {{ $cellEvents->count() }} events</small>@endif
                            <div class="calendar-cell-events">@foreach($cellEvents as $event)@include('staff.partials.schedule-calendar-event')@endforeach</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
            @if($calendarEvents->contains(fn ($event) => !$event['has_time']))<div class="calendar-grid calendar-time-row"><div class="calendar-axis">Time not recorded</div>@foreach($days as $day)<div class="calendar-cell"><div class="calendar-cell-events">@foreach($calendarEvents->filter(fn ($event) => !$event['has_time'] && $event['date']->isSameDay($day)) as $event)@include('staff.partials.schedule-calendar-event')@endforeach</div></div>@endforeach</div>@endif
            @if($calendarEvents->isEmpty())<div class="calendar-empty"><i class="bi bi-calendar-check" aria-hidden="true"></i><strong>No scheduled events this week</strong><p>Use the arrows to review another week.</p></div>@endif
        </div>
    @endif
    <div class="calendar-agenda {{ $calendarView === 'week' ? 'is-mobile-only' : '' }}">
        @if($scheduleSection === 'upcoming')<h3>{{ $calendarDate->format('l, M d, Y') }}</h3>@endif
        @forelse($selectedEvents->groupBy(fn ($event) => $event['has_time'] ? $event['date']->format('H:i') : 'unknown') as $slot => $events)
            <div class="calendar-agenda-slot"><div class="calendar-agenda-time"><strong>{{ $slot === 'unknown' ? 'Time not recorded' : $events->first()['date']->format('g:i A') }}</strong>@if($slot !== 'unknown' && $events->count() > 1)<span class="calendar-shared">Same time · {{ $events->count() }} events</span>@endif</div><div class="calendar-agenda-events">@foreach($events as $event)@include('staff.partials.schedule-calendar-event')@endforeach</div></div>
        @empty<div class="calendar-empty"><i class="bi bi-calendar-check" aria-hidden="true"></i><strong>No scheduled events on this date</strong><p>Choose another day to review schedules.</p></div>@endforelse
    </div>
    </div>
    @endif
</section>
@include('staff.partials.case-schedule-dialogs')

@include('staff.partials.schedule-calendar-styles')
