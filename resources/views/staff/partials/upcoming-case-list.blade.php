<div class="upcoming-case-list case-mode" aria-label="Upcoming schedules by case">
    <p class="case-list-intro">All upcoming cases, ordered by their next event. Each card includes the complete recorded schedule, including earlier dates.</p>
    @forelse($upcomingCases as $entry)
        <article class="upcoming-case-card" data-schedule-case="{{ $entry['case']->id }}"><header><div><strong>{{ $entry['case']->case_code }}</strong><h3>{{ $entry['case']->deceased?->full_name ?? 'Not recorded' }}</h3><p>Client: {{ $entry['case']->client?->full_name ?? 'Not recorded' }}</p>@if($allowAllBranches ?? false)<small>{{ $entry['branch_label'] }}</small>@endif</div><span class="case-next-event">Next event<br>{{ $entry['next_at']->format('M d, Y') }}</span></header>
            @include('staff.partials.case-schedule-rows', ['caseEvents' => $entry['events'], 'showCaseLocations' => false])
            <button type="button" class="calendar-control" data-calendar-event="case-{{ $entry['case']->id }}">View Case Schedule <i class="bi bi-arrow-up-right" aria-hidden="true"></i></button>
        </article>
    @empty<div class="calendar-empty"><strong>{{ $scheduleSearch !== '' ? 'No matching upcoming cases' : 'No upcoming cases' }}</strong><p>{{ $scheduleSearch !== '' ? 'Check the case code or deceased name, or clear your search.' : 'Cases with events scheduled after today will appear here.' }}</p></div>@endforelse
</div>
