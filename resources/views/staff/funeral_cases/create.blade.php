<div class="bg-white border-2 border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <div class="bg-slate-50 border-b px-5 py-4 flex justify-between items-center">
        <div>
            <h3 class="font-bold text-slate-800">Record Cash Collection</h3>
            <p class="text-xs text-slate-500">Manual entry for cash-only transactions</p>
        </div>
        <span class="text-[10px] font-black bg-green-100 text-green-700 px-2 py-1 rounded uppercase tracking-wider">Verified Cashier</span>
    </div>

    <div class="p-6">
        <form method="POST" action="{{ route('payments.store') }}" class="grid grid-cols-1 md:grid-cols-6 gap-6 items-end" id="paymentForm">
            @csrf
            
            <div class="md:col-span-2">
                <label class="block text-xs font-black uppercase text-slate-500 mb-2 tracking-tight">Active Funeral Case</label>
                <select name="funeral_case_id" id="funeral_case_id" class="w-full border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition-all" required>
                    <option value="">-- Select Case --</option>
                    @foreach($openCases as $case)
                        <option
                            value="{{ $case->id }}"
                            data-total="{{ $case->total_amount }}"
                            data-paid="{{ $case->total_paid }}"
                            data-balance="{{ $case->balance_amount }}"
                            data-branch="{{ $case->branch?->branch_code ?? 'MAIN' }}"
                            {{ old('funeral_case_id') == $case->id ? 'selected' : '' }}
                        >
                            [{{ $case->branch?->branch_code ?? 'MAIN' }}] {{ $case->case_code }} | {{ $case->client?->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-black uppercase text-slate-500 mb-2 tracking-tight">Date Received</label>
                <input type="datetime-local" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}" class="w-full border-slate-300 rounded-lg px-3 py-2.5 text-sm" required>
            </div>

            <div>
                <label class="block text-xs font-black uppercase text-slate-500 mb-2 tracking-tight">Cash Amount</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-slate-400 font-bold">₱</span>
                    <input type="number" step="0.01" min="0.01" name="amount_paid" id="amount_paid" class="w-full border-slate-300 rounded-lg pl-7 pr-3 py-2.5 text-sm font-black text-green-700 focus:ring-green-500 focus:border-green-500" placeholder="0.00" required>
                </div>
            </div>

            <div>
                <button class="w-full bg-slate-900 hover:bg-black text-white font-bold px-3 py-3 rounded-lg text-sm transition-all transform active:scale-95 shadow-lg disabled:bg-slate-300 disabled:scale-100" type="submit" id="saveBtn" {{ $openCases->isEmpty() ? 'disabled' : '' }}>
                    Save Payment
                </button>
            </div>
        </form>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4 pt-6 border-t border-slate-100">
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                <div class="text-[10px] text-slate-400 uppercase font-black tracking-widest mb-1">Origin Branch</div>
                <div id="branch_display" class="font-bold text-slate-700">-</div>
            </div>
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                <div class="text-[10px] text-slate-400 uppercase font-black tracking-widest mb-1">Contract Total</div>
                <div id="total_due_display" class="font-bold text-slate-800">-</div>
            </div>
            <div class="p-4 bg-green-50 rounded-xl border border-green-100">
                <div class="text-[10px] text-green-600 uppercase font-black tracking-widest mb-1">Previously Paid</div>
                <div id="total_paid_display" class="font-bold text-green-700">-</div>
            </div>
            <div class="p-4 bg-red-50 rounded-xl border border-red-100">
                <div class="text-[10px] text-red-600 uppercase font-black tracking-widest mb-1">Balance Outstanding</div>
                <div id="balance_display" class="font-black text-red-700 text-lg">-</div>
            </div>
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
        const branchDisplay = document.getElementById('branch_display');
        const saveBtn = document.getElementById('saveBtn');

        function formatCurrency(n) {
            return '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        caseSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            
            if (!selected.value) {
                totalDisplay.textContent = paidDisplay.textContent = balanceDisplay.textContent = branchDisplay.textContent = '-';
                amountInput.value = '';
                saveBtn.disabled = true;
                return;
            }

            const total = parseFloat(selected.dataset.total) || 0;
            const paid = parseFloat(selected.dataset.paid) || 0;
            const balance = parseFloat(selected.dataset.balance) || 0;
            const branch = selected.dataset.branch || 'MAIN';

            totalDisplay.textContent = formatCurrency(total);
            paidDisplay.textContent = formatCurrency(paid);
            balanceDisplay.textContent = formatCurrency(balance);
            branchDisplay.textContent = branch;
            
            // Set max attributes and default values
            amountInput.max = balance.toFixed(2);
            amountInput.value = balance > 0 ? balance.toFixed(2) : '';
            saveBtn.disabled = balance <= 0;
        });

        // Prevention logic for overpayment
        amountInput.addEventListener('input', function() {
            const max = parseFloat(this.max);
            const current = parseFloat(this.value);
            if (current > max) {
                this.classList.add('border-red-500', 'text-red-600');
                saveBtn.disabled = true;
            } else {
                this.classList.remove('border-red-500', 'text-red-600');
                saveBtn.disabled = false;
            }
        });
    })();
</script>

