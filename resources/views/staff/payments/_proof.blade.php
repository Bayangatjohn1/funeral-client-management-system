<div class="payment-original-proof">
    @if($proofPayment?->proof_path && $proofPayment->proof_mime)
        <a href="{{ route('payment-proof.payment', $proofPayment) }}" data-proof-viewer data-proof-mime="{{ $proofPayment->proof_mime }}" class="pw-text-action"><i class="bi bi-file-earmark-image" aria-hidden="true"></i> {{ $proofLabel ?? 'View Original Payment Proof' }}</a>
    @else
        <p>No proof on file.</p>
    @endif
</div>
