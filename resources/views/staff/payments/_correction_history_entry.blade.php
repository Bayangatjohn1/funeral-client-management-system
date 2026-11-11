@php $viewing = $currentRequest?->is($entry); @endphp
<li class="pm-correction-entry" @if($viewing) aria-current="true" tabindex="-1" @endif>
    <div class="pm-correction-entry-head">
        <h3>{{ $entry->correction_type === 'VOID_ONLY' ? 'Void without replacement' : 'Correct payment' }}</h3>
        <span class="pm-correction-status" data-status="{{ $entry->status }}">{{ \Illuminate\Support\Str::headline($entry->status) }}</span>
    </div>
    <dl class="pm-correction-meta">
        <div><dt>Requested by</dt><dd>{{ $entry->requester?->name ?? 'Unknown user' }} · {{ $entry->created_at?->format('M d, Y h:i A') }}</dd></div>
        @if($entry->reviewed_at)
            <div><dt>Reviewed by</dt><dd>{{ $entry->reviewer?->name ?? 'Unknown user' }} · {{ $entry->reviewed_at->format('M d, Y h:i A') }}</dd></div>
        @endif
        @if($entry->review_reason)
            <div><dt>Review reason</dt><dd>{{ $entry->review_reason }}</dd></div>
        @endif
    </dl>
    <div class="pm-correction-entry-footer">
        @if($viewing)
            <span class="pw-audit-current">Viewing now</span>
        @endif
        <a class="pw-text-action" href="{{ route('payments.corrections.show', $entry) }}"
            @if($currentRequest) data-workflow-history-link data-correction-request-number="{{ $entry->id }}" aria-label="View request #{{ $entry->id }}" @endif
            data-payment-workflow>View Request <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        @if($currentRequest)<small>Request #{{ $entry->id }}</small>@endif
    </div>
</li>
