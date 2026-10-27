<div class="upcoming-tools">
    <form method="GET" action="{{ route($remindersRoute) }}" class="schedule-search">
        @foreach(request()->except(['schedule_q', 'page']) as $key => $value)
            @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
        @endforeach
        <input type="hidden" name="tab" value="schedules"><input type="hidden" name="schedule_section" value="upcoming">
        <label for="schedule-search">Find a case</label><div><div class="schedule-search-field"><input type="text" autocomplete="off" id="schedule-search" name="schedule_q" value="{{ $scheduleSearch }}" maxlength="120" placeholder="Case code or deceased name"><button type="button" class="schedule-search-x" aria-label="Clear search" @if($scheduleSearch === '') hidden @endif><i class="bi bi-x-lg" aria-hidden="true"></i></button></div><button type="submit" class="calendar-control">Search</button></div>
        @if($scheduleSearch !== '')
            <p class="schedule-search-applied" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i> Showing matches for <strong>“{{ $scheduleSearch }}”</strong> · {{ $upcomingCases->count() }} {{ $upcomingCases->count() === 1 ? 'case' : 'cases' }}</p>
        @endif
    </form>

        <div class="calendar-controls calendar-mode">
            <div class="calendar-date-nav" role="group" aria-label="Schedule date navigation">
            @php $canPrevious = $calendarView === 'day' ? $calendarDate->toDateString() > now()->addDay()->toDateString() : $weekStart->greaterThan(now()->startOfWeek()); @endphp
            @if($canPrevious)
                <a class="calendar-control calendar-arrow" aria-label="Previous {{ $calendarView }}" href="{{ $calendarUrl($calendarDate->copy()->subDays($calendarView === 'day' ? 1 : 7)) }}"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
            @else
                <button class="calendar-control calendar-arrow" type="button" aria-label="Previous {{ $calendarView }}" disabled><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
            @endif
            <form method="GET" action="{{ route($remindersRoute) }}" class="calendar-date-jump">
                <input type="hidden" name="tab" value="schedules"><input type="hidden" name="calendar_view" value="{{ $calendarView }}">
                @foreach($calendarQuery as $key => $value)
                    @if(is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label class="sr-only" for="calendar-date">Choose schedule date</label><input id="calendar-date" type="date" name="calendar_date" min="{{ now()->addDay()->toDateString() }}" value="{{ $calendarDate->toDateString() }}" required onchange="this.form.requestSubmit()"><noscript><button type="submit">Go</button></noscript>
            </form>
            <a class="calendar-control calendar-arrow" aria-label="Next {{ $calendarView }}" href="{{ $calendarUrl($calendarDate->copy()->addDays($calendarView === 'day' ? 1 : 7)) }}"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </div>
            <div class="calendar-view-switch" aria-label="Calendar view"><a class="calendar-control {{ $calendarView === 'day' ? 'is-active' : '' }}" href="{{ $calendarUrl($calendarDate, 'day') }}" @if($calendarView === 'day') aria-current="page" @endif>Day</a><a class="calendar-control {{ $calendarView === 'week' ? 'is-active' : '' }}" href="{{ $calendarUrl($calendarDate, 'week') }}" @if($calendarView === 'week') aria-current="page" @endif>Week</a></div>
        </div>
</div>
<script>
(() => {
    const calendar = document.querySelector('.schedule-calendar');
    const media = matchMedia('(max-width:767px)');
    const update = () => {
        const view = calendar.dataset.upcomingView === 'auto' ? (media.matches ? 'cases' : 'calendar') : calendar.dataset.upcomingView;
        calendar.querySelectorAll('[data-upcoming-mode]').forEach(link => {link.classList.toggle('is-active',link.dataset.upcomingMode===view);if(link.dataset.upcomingMode===view)link.setAttribute('aria-current','page');else link.removeAttribute('aria-current');});
    };
    update(); media.addEventListener('change', update);
})();
</script>
