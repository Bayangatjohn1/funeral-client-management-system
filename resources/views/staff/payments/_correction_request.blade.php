@php
    $viewer = auth()->user();
    $sameBranch = $viewer && (int) $viewer->branch_id === (int) $payment->branch_id;
    $canRequest = $sameBranch && $viewer->isStaff()
        && (int) ($payment->recorded_by ?? $payment->encoded_by ?? $payment->received_by) === (int) $viewer->id
        && !in_array($payment->status, ['VOID', 'VOIDED'], true)
        && ($case ?? $payment->funeralCase)?->entry_source !== 'OTHER_BRANCH';
    $history = $viewer
        ? $payment->correctionRequests->filter(fn ($item) => $item->isVisibleTo($viewer))->sortByDesc('id')
        : collect();
    $pending = $history->firstWhere('status', 'PENDING');
@endphp
@if($canRequest)
    @if($pending)
        <div class="pm-correction-pending-state">
            <button type="button" class="pm-correction-disabled" disabled aria-describedby="pm-correction-pending-{{ $payment->id }}">Request Correction</button>
            <span id="pm-correction-pending-{{ $payment->id }}">A request is already awaiting review for this payment.</span>
            <a class="pw-status" data-status="PENDING" href="{{ route('payments.corrections.show', $pending) }}" data-payment-workflow>View Pending Request</a>
        </div>
    @elseif($payment->proof_path && $payment->proof_mime)
        <a href="{{ route('payments.corrections.create', $payment) }}" data-payment-workflow data-payment-correction-create class="pm-correction-action"><i class="bi bi-pencil-square" aria-hidden="true"></i> Request Correction</a>
    @elseif(!$payment->proof_path && !$payment->proof_upload_id)
        <details>
            <summary>Attach Original Payment Proof</summary>
            <p>Attach the receipt or transaction confirmation before requesting a correction.</p>
            <form method="POST" action="{{ route('payment-proof.attach', $payment) }}" data-existing-payment-proof>
                @csrf
                <x-payment-proof-upload context="attachment" :restore-old="false" />
                <button type="submit" class="pm-btn primary">Attach Proof</button>
            </form>
        </details>
    @else
        <p>Original payment proof is unavailable. Ask your Branch Admin to check the saved attachment.</p>
    @endif
@endif
@if($history->isNotEmpty() || ($sameBranch && ($viewer->isStaff() || $viewer->isBranchAdmin()) && $payment->proofUpload?->claimed_at))
    <section class="pm-correction-history" aria-label="Correction Request History">
        <h4 class="pm-detail-group-title">Correction Request History</h4>
        @if($payment->proofUpload?->claimed_at)
            <p class="pm-correction-proof">
                <i class="bi bi-paperclip" aria-hidden="true"></i>
                <span>Proof attached by <b>{{ $payment->proofUpload->user?->name ?? 'Unknown user' }}</b> on {{ $payment->proofUpload->claimed_at->format('M d, Y h:i A') }}.</span>
            </p>
        @endif
        @if($history->isNotEmpty())
            <ul class="pm-correction-history-list">
                @foreach($history as $item)
                    @include('staff.payments._correction_history_entry', ['entry' => $item, 'currentRequest' => null])
                @endforeach
            </ul>
        @endif
    </section>
@endif
