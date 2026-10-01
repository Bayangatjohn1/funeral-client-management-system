@if(auth()->user()?->role === 'staff' && !in_array($payment->status, ['VOID', 'VOIDED'], true))
    @php($hasPendingCorrection = $payment->correctionRequests()->where('status', 'PENDING')->exists())
    @if($hasPendingCorrection)
        <span class="pm-status" style="background:#fef3c7;color:#92400e">Correction Pending</span>
    @else
        <details class="w-full rounded-xl border border-amber-200 bg-amber-50 p-3">
            <summary class="cursor-pointer text-sm font-semibold text-amber-900">Request Payment Correction</summary>
            <form method="POST" action="{{ route('payments.corrections.store', $payment) }}" class="mt-3 grid gap-3 md:grid-cols-2">
                @csrf
                <label class="text-sm"><span class="block font-medium">Request type</span><select name="correction_type" required class="mt-1 w-full rounded-lg border-gray-300"><option value="CORRECT">Correct Payment</option><option value="VOID_ONLY">Void Without Replacement</option></select></label>
                <label class="text-sm"><span class="block font-medium">Correct amount (if applicable)</span><input type="number" step="0.01" min="0.01" name="amount" value="{{ $payment->amount }}" class="mt-1 w-full rounded-lg border-gray-300"></label>
                <label class="text-sm"><span class="block font-medium">Correct payment date</span><input type="date" name="paid_date" value="{{ ($payment->paid_at ?? $payment->paid_date)?->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border-gray-300"></label>
                <label class="text-sm"><span class="block font-medium">Reference number</span><input name="reference_number" value="{{ $payment->reference_number }}" class="mt-1 w-full rounded-lg border-gray-300"></label>
                <label class="text-sm md:col-span-2"><span class="block font-medium">Reason</span><textarea name="reason" required minlength="10" maxlength="1000" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Explain why this transaction needs correction"></textarea></label>
                <div class="md:col-span-2"><button class="rounded-lg bg-amber-700 px-4 py-2 text-white">Submit for Branch Admin review</button></div>
            </form>
        </details>
    @endif
@endif
