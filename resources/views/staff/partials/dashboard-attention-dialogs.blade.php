@foreach($attentionItems->unique('case_id') as $item)
    @php $case = $item['case']; @endphp
    <dialog id="attention-case-{{ $item['case_id'] }}" class="schedule-dialog case-schedule-dialog" aria-labelledby="attention-title-{{ $item['case_id'] }}">
        <header class="schedule-dialog-head"><div><small>{{ $branchLabel }}</small><h2 id="attention-title-{{ $item['case_id'] }}">Payment Follow-up Summary</h2><p>{{ $item['case_code'] }} · {{ $item['deceased_name'] }}</p></div><button type="button" class="schedule-close" data-calendar-close aria-label="Close summary"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
        <div class="schedule-dialog-body">
            <p class="schedule-advisory-note">{{ $item['message'] ?? $item['label'] }}</p>
            <div class="case-schedule-rows">
                <div class="case-schedule-row"><strong>Client</strong><span>{{ $case->client?->full_name ?? 'Not recorded' }}</span></div>
                <div class="case-schedule-row"><strong>Interment</strong><span>{{ $item['date']?->format('M d, Y') ?? 'Not recorded' }}@if($item['has_time'] ?? false) · {{ $item['date']->format('g:i A') }}@endif</span></div>
                <div class="case-schedule-row"><strong>Total paid</strong><span>PHP {{ number_format((float) $case->total_paid, 2) }}</span></div>
                <div class="case-schedule-row"><strong>Remaining balance</strong><span>PHP {{ number_format((float) $case->balance_amount, 2) }}</span></div>
            </div>
        </div>
        <footer><a href="{{ route('payments.history', ['q' => $item['case_code'], 'tab' => 'summary']) }}">View Payment Monitoring</a><button type="button" class="schedule-done" data-calendar-close>Done</button></footer>
    </dialog>
@endforeach
<script>
document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-attention-dialog]');
    if (!trigger) return;
    const dialog = document.getElementById(trigger.dataset.attentionDialog);
    if (!dialog) return;
    document.body.append(dialog); dialog.showModal();
    document.documentElement.classList.add('schedule-modal-open');
    dialog.addEventListener('close', () => document.documentElement.classList.remove('schedule-modal-open'), {once:true});
});
</script>
