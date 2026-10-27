@foreach($scheduleGroups as $group)
    <dialog id="schedule-day-{{ $group['key'] }}" class="schedule-dialog" aria-labelledby="schedule-title-{{ $group['key'] }}">
        <header class="schedule-dialog-head"><div><small>{{ $group['branch_label'] ?? $branchLabel ?? 'Branch schedules' }}</small><h2 id="schedule-title-{{ $group['key'] }}">Schedules · {{ \Carbon\Carbon::parse($group['date'])->format('M d, Y') }}</h2><p>{{ $group['case_count'] }} cases · {{ $group['events']->count() }} events</p></div><button type="button" class="schedule-close" aria-label="Close schedule timeline" data-close-schedule><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
        <div class="schedule-dialog-body"><p class="schedule-advisory-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Review staff, venue, and vehicle availability. Shared dates or times are for coordination.</p>@include('staff.partials.schedule-timeline', ['timelineEvents' => $group['events'], 'timelineFocus' => 0])</div>
        <footer><button type="button" class="schedule-done" data-close-schedule>Done</button></footer>
    </dialog>
@endforeach
<script>
document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-schedule-dialog]');
    if (trigger) {
        const dialog = document.getElementById(trigger.dataset.scheduleDialog);
        if (!dialog) return;
        dialog.querySelectorAll('[data-timeline-case]').forEach(row => row.classList.toggle('is-selected', row.dataset.timelineCase === trigger.dataset.focusCase));
        document.body.append(dialog);
        dialog.showModal();
        document.documentElement.classList.add('schedule-modal-open');
        dialog.addEventListener('close', () => document.documentElement.classList.remove('schedule-modal-open'), {once:true});
    }
    const close = event.target.closest('[data-close-schedule]');
    if (close) close.closest('dialog').close();
});
</script>
