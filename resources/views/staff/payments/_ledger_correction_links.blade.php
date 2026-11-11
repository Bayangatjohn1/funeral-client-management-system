@php
    $ledgerCorrection = $ledgerCorrection ?? $payment->ledgerCorrection();
    $casePayments = $casePayments ?? collect([$payment]);
    $replacement = $ledgerCorrection?->replacementPayment;
    $keptNumber = $payment->isVoided() && $ledgerCorrection?->correction_type === 'VOID_ONLY' && $ledgerCorrection->reason_category === 'Duplicate entry'
        ? $ledgerCorrection->evidence_reference : null;
    $keptPayment = $keptNumber ? $casePayments->first(fn ($entry) => $entry->id !== $payment->id && $entry->display_payment_record_no === $keptNumber) : null;
    $viewer = auth()->user();
@endphp
@foreach([['Payment kept', $keptNumber, $keptPayment], ['Replacement payment', $replacement?->display_payment_record_no, $replacement]] as [$label, $number, $related])
    @if($number)
        <div>{{ $label }} ·
            @if(($linkRelatedPayments ?? true) && $related && $viewer && !$viewer->isOwner() && $viewer->can('view', $related))
                <a href="{{ route('payments.summary', $related) }}" target="_blank" rel="noopener">{{ $number }}</a>
            @else
                <strong>{{ $number }}</strong>
            @endif
        </div>
    @endif
@endforeach
@if(($showCorrectionLink ?? false) && $ledgerCorrection && $viewer && $ledgerCorrection->isVisibleTo($viewer))
    <a href="{{ route('payments.corrections.show', $ledgerCorrection) }}" data-payment-workflow>{{ $ledgerCorrection->status === 'PENDING' ? 'View Pending Request' : 'View Correction Request' }}</a>
@endif
