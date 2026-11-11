<section class="pw-section pw-payment-history" aria-label="Case payment history">
    @forelse($historyCases as $historyCase)
        <div class="pw-history-heading">
            <strong>{{ $historyCase->case_code }} · {{ $historyCase->deceased?->full_name }}</strong>
            <small>{{ (int) $historyCase->id === (int) $payment?->funeral_case_id ? 'Original case' : 'Proposed destination case' }} · {{ $historyCase->payments->count() }} transactions</small>
        </div>
        <div class="pw-history-scroll" tabindex="0" role="region" aria-label="Payment transactions for {{ $historyCase->case_code }}">
            <table>
                <thead><tr><th scope="col">Payment record</th><th scope="col">Method / reference</th><th scope="col">Amount / status</th><th scope="col">Proof</th></tr></thead>
                <tbody>
                @forelse($historyCase->payments as $transaction)
                    @php
                        $underReview = (int) $transaction->id === (int) $item->payment_id;
                        $keepEntry = $duplicateVoid && !$underReview && trim((string) $item->evidence_reference) === $transaction->display_payment_record_no;
                        $voided = in_array($transaction->status, ['VOID', 'VOIDED'], true);
                        $posted = in_array($transaction->status, [null, 'VALID', 'POSTED'], true) && !$transaction->trashed();
                        $reference = $transaction->approval_code ?: ($transaction->reference_number ?: $transaction->transaction_reference_no);
                    @endphp
                    <tr @if($underReview) class="pw-history-current" aria-current="true" @endif>
                        <td>
                            <strong>{{ $transaction->display_payment_record_no ?: 'Record number unavailable' }}</strong>
                            <small>{{ ($transaction->paid_at ?? $transaction->paid_date)?->format('M d, Y g:i A') ?: 'Payment date not recorded' }}</small>
                            @if($transaction->replaces_payment_id)<small>Corrected payment</small>@endif
                        </td>
                        <td>
                            <span>{{ \App\Support\Payments\PaymentDetails::label($transaction) }}</span>
                            @if($transaction->receipt_or_no || $transaction->payment_method === 'cash')<small>Receipt / OR · {{ $transaction->receipt_or_no ?: 'Not recorded' }}</small>@endif
                            @if($reference)<small>{{ $transaction->approval_code ? 'Approval code' : 'Transaction ref.' }} · {{ $reference }}</small>@endif
                            <small>Recorded by {{ $transaction->recordedBy?->name ?: 'Not recorded' }}</small>
                        </td>
                        <td>
                            <strong>₱{{ number_format((float) $transaction->amount, 2) }}</strong>
                            <span class="pw-history-status" data-posted="{{ $posted ? 'true' : 'false' }}">{{ $transaction->trashed() ? 'Deleted' : ($voided ? 'Voided' : ($posted ? 'Posted' : ucfirst(strtolower((string) $transaction->status)))) }}</span>
                            @if($underReview)<small class="pw-history-marker">Requested transaction</small>@endif
                            @if($keepEntry)<small class="pw-history-marker">Entry to keep</small>@endif
                        </td>
                        <td>
                            @if($transaction->proof_path && !$transaction->trashed())
                                <a href="{{ route('payment-proof.payment', $transaction) }}" class="pw-text-action" data-proof-viewer data-proof-mime="{{ $transaction->proof_mime }}" aria-label="View proof for {{ $transaction->display_payment_record_no }}"><i class="bi bi-file-earmark-image" aria-hidden="true"></i> View Proof</a>
                            @else
                                <small>{{ $transaction->trashed() ? 'Proof unavailable for deleted record' : 'No proof on file' }}</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No payment transactions recorded for this case.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @empty
        <p>Payment history is unavailable for this case.</p>
    @endforelse
</section>
