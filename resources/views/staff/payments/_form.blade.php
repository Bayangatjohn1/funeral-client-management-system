<!-- NOTE: Keep input names/ids; JS summary and PaymentController depend on them. Feel free to restyle markup/classes. -->
<div class="bg-white border rounded p-4">
    <div class="font-semibold mb-3">Record Payment (Cash Only)</div>
    <form method="POST" action="{{ route('payments.store') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end" id="paymentForm">
        @csrf
        <div class="md:col-span-2">
            <label class="block text-sm font-medium">Open Case</label>
            <select name="funeral_case_id" id="funeral_case_id" class="w-full border rounded px-3 py-2 text-sm" required>
                <option value="">Select case</option>
        @php
            $preselectCase = $preselectCase ?? null;
            $prefillCaseId = old('funeral_case_id', $preselectCase->id ?? null);
            $includePreselect = $preselectCase && !$openCases->contains('id', $preselectCase->id);
        @endphp

        @foreach($openCases as $case)
            <option
                value="{{ $case->id }}"
                data-total="{{ $case->total_amount }}"
                data-paid="{{ $case->total_paid }}"
                data-balance="{{ $case->balance_amount }}"
                {{ $prefillCaseId == $case->id ? 'selected' : '' }}
            >
                {{ $case->case_code }} | {{ $case->client?->full_name ?? '-' }} | Bal: {{ number_format((float) $case->balance_amount, 2) }}
            </option>
        @endforeach

        @if($includePreselect)
            <option
                value="{{ $preselectCase->id }}"
                data-total="{{ $preselectCase->total_amount }}"
                data-paid="{{ $preselectCase->total_paid }}"
                data-balance="{{ $preselectCase->balance_amount }}"
                selected
            >
                {{ $preselectCase->case_code }} | {{ $preselectCase->client?->full_name ?? '-' }} | Bal: {{ number_format((float) $preselectCase->balance_amount, 2) }}
            </option>
        @endif
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium">Paid Date/Time</label>
            <input type="datetime-local" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}" class="w-full border rounded px-3 py-2 text-sm" required>
        </div>

        <div>
            <label class="block text-sm font-medium">Amount Paid</label>
            <input type="number" step="0.01" min="0.01" name="amount_paid" id="amount_paid" value="{{ old('amount_paid') }}" class="w-full border rounded px-3 py-2 text-sm" required>
        </div>

        <div>
            <button class="w-full bg-black text-white px-3 py-2 rounded text-sm" type="submit" {{ $openCases->isEmpty() ? 'disabled' : '' }}>
                Save Payment
            </button>
        </div>
    </form>

    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 text-xs text-gray-600">
        <div class="border rounded bg-slate-50 px-3 py-2">
            <div class="text-gray-500">Current Total Amount</div>
            <div class="font-medium text-slate-700" id="total_due_display">-</div>
        </div>
        <div class="border rounded bg-slate-50 px-3 py-2">
            <div class="text-gray-500">Current Total Paid</div>
            <div class="font-medium text-slate-700" id="total_paid_display">-</div>
        </div>
        <div class="border rounded bg-slate-50 px-3 py-2">
            <div class="text-gray-500">Current Balance</div>
            <div class="font-medium text-slate-700" id="balance_display">-</div>
        </div>
        <div class="border rounded bg-slate-50 px-3 py-2">
            <div class="text-gray-500">New Payment</div>
            <div class="font-medium text-slate-700" id="new_payment_display">0.00</div>
        </div>
        <div class="border rounded bg-slate-50 px-3 py-2">
            <div class="text-gray-500">New Total Paid</div>
            <div class="font-medium text-slate-700" id="new_total_paid_display">-</div>
        </div>
        <div class="border rounded bg-slate-50 px-3 py-2">
            <div class="text-gray-500">New Remaining Balance</div>
            <div class="font-medium text-slate-700" id="new_balance_display">-</div>
        </div>
        <div class="border rounded bg-slate-50 px-3 py-2">
            <div class="text-gray-500">Resulting Payment Status</div>
            <div class="font-medium text-slate-700" id="new_status_display">-</div>
        </div>
    </div>
</div>

<script>
    (function () {
        const caseSelect = document.getElementById('funeral_case_id');
        const amountInput = document.getElementById('amount_paid');
        const totalDisplay = document.getElementById('total_due_display');
        const paidDisplay = document.getElementById('total_paid_display');
        const balanceDisplay = document.getElementById('balance_display');
        const newPaymentDisplay = document.getElementById('new_payment_display');
        const newTotalPaidDisplay = document.getElementById('new_total_paid_display');
        const newBalanceDisplay = document.getElementById('new_balance_display');
        const newStatusDisplay = document.getElementById('new_status_display');

        function toNumber(value) {
            const n = parseFloat(value);
            return Number.isFinite(n) ? n : 0;
        }

        function format(n) {
            return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function updateSummary() {
            const selected = caseSelect.options[caseSelect.selectedIndex];
            const total = toNumber(selected ? selected.dataset.total : 0);
            const paid = toNumber(selected ? selected.dataset.paid : 0);
            const balance = toNumber(selected ? selected.dataset.balance : 0);
            const enteredPayment = Math.min(Math.max(toNumber(amountInput.value), 0), balance);
            const newTotalPaid = Math.min(paid + enteredPayment, total);
            const newBalance = Math.max(total - newTotalPaid, 0);
            let status = 'UNPAID';

            totalDisplay.textContent = total > 0 ? format(total) : '-';
            paidDisplay.textContent = selected && selected.value ? format(paid) : '-';
            balanceDisplay.textContent = selected && selected.value ? format(balance) : '-';
            newPaymentDisplay.textContent = selected && selected.value ? format(enteredPayment) : '0.00';
            newTotalPaidDisplay.textContent = selected && selected.value ? format(newTotalPaid) : '-';
            newBalanceDisplay.textContent = selected && selected.value ? format(newBalance) : '-';

            if (!selected || !selected.value) {
                amountInput.max = '';
                newStatusDisplay.textContent = '-';
                return;
            }

            if (newTotalPaid > 0 && newBalance > 0) {
                status = 'PARTIAL';
            } else if (newBalance <= 0 && total > 0) {
                status = 'PAID';
            }

            newStatusDisplay.textContent = status;
            amountInput.max = balance.toFixed(2);
            if (!amountInput.value || toNumber(amountInput.value) > balance) {
                const adjustedPayment = balance > 0 ? balance : 0;
                const adjustedTotalPaid = Math.min(paid + adjustedPayment, total);
                const adjustedBalance = Math.max(total - adjustedTotalPaid, 0);
                const adjustedStatus = adjustedTotalPaid > 0 && adjustedBalance > 0
                    ? 'PARTIAL'
                    : (adjustedBalance <= 0 && total > 0 ? 'PAID' : 'UNPAID');

                amountInput.value = balance > 0 ? balance.toFixed(2) : '';
                newPaymentDisplay.textContent = format(adjustedPayment);
                newTotalPaidDisplay.textContent = format(adjustedTotalPaid);
                newBalanceDisplay.textContent = format(adjustedBalance);
                newStatusDisplay.textContent = adjustedStatus;
            }
        }

        if (caseSelect) {
            caseSelect.addEventListener('change', updateSummary);
        }
        if (amountInput) {
            amountInput.addEventListener('input', updateSummary);
        }

        updateSummary();
    })();
</script>

