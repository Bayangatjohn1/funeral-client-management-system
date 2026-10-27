@foreach($modalCases as $entry)
    <dialog id="calendar-case-{{ $entry['case']->id }}" class="schedule-dialog calendar-details case-schedule-dialog" aria-labelledby="case-schedule-title-{{ $entry['case']->id }}">
        <header class="schedule-dialog-head"><div><small>{{ $entry['branch_label'] }}</small><h2 id="case-schedule-title-{{ $entry['case']->id }}">Case Schedule</h2><p>{{ $entry['case']->case_code }} · {{ $entry['case']->deceased?->full_name ?? 'Not recorded' }}</p></div><button type="button" class="schedule-close" data-calendar-close aria-label="Close case schedule"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
        <div class="schedule-dialog-body"><p class="case-schedule-context">The complete recorded schedule for this case, including dates outside the selected week.</p><dl><dt>Client</dt><dd>{{ $entry['case']->client?->full_name ?? 'Not recorded' }}</dd><dt>Case status</dt><dd>{{ $entry['case']->case_status === 'COMPLETED' ? 'Completed' : 'Ongoing' }}</dd></dl>
            @include('staff.partials.case-schedule-rows', ['caseEvents' => $entry['events'], 'showCaseLocations' => true])
        </div>
        <footer><a class="calendar-control" href="{{ ($dashboardScheduleModal ?? false) ? route('funeral-cases.index', ['q' => $entry['case']->case_code]) : route('funeral-cases.show', ['funeral_case' => $entry['case']->id, 'return_to' => request()->fullUrl()]) }}">{{ ($dashboardScheduleModal ?? false) ? 'View Case Record' : 'View Case' }}</a><button type="button" class="schedule-done" data-calendar-close>Done</button></footer>
    </dialog>
@endforeach
