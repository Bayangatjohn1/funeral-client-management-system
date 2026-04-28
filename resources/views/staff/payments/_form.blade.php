{{-- NOTE: Keep input names/ids; JS summary and PaymentController depend on them. --}}
<style>
    .pf-label {
        display: block;
        font-size: 0.67rem;
        font-weight: 800;
        color: #4d6480;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        margin-bottom: 0.45rem;
    }
    .pf-input {
        width: 100%;
        border-radius: 10px;
        border: 1.5px solid #dde4ee;
        background: #ffffff;
        padding: 0.72rem 1rem;
        font-size: 0.88rem;
        font-weight: 400;
        color: #0d1f38;
        box-shadow: 0 1px 3px rgba(15,23,42,.05);
        transition: border-color .18s, box-shadow .18s;
        font-family: inherit;
        box-sizing: border-box;
    }
    .pf-input:focus {
        border-color: #1b3358;
        box-shadow: 0 0 0 4px rgba(27,51,88,.09);
        outline: none;
        background: #fafcff;
    }
    .pf-input::placeholder { color: #b0beca; }
    .pf-select { appearance: none; cursor: pointer; }
    .pf-amount-wrap {
        display: flex;
        align-items: center;
        border-radius: 10px;
        border: 1.5px solid #dde4ee;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(15,23,42,.05);
        transition: border-color .18s, box-shadow .18s;
        overflow: hidden;
    }
    .pf-amount-wrap:focus-within {
        border-color: #1b3358;
        box-shadow: 0 0 0 4px rgba(27,51,88,.09);
    }
    .pf-amount-prefix {
        padding: 0 12px;
        font-size: 1rem;
        font-weight: 700;
        color: #4d6480;
        background: #f4f7fb;
        border-right: 1.5px solid #dde4ee;
        height: 100%;
        display: flex;
        align-items: center;
        flex-shrink: 0;
        user-select: none;
    }
    .pf-amount-input {
        flex: 1;
        border: 0;
        outline: none;
        padding: 0.72rem 1rem;
        font-size: 1.05rem;
        font-weight: 700;
        color: #0d1f38;
        background: transparent;
        font-family: inherit;
        width: 100%;
    }
    .pf-amount-input::placeholder { color: #b0beca; font-weight: 400; }

    .pf-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    @media (min-width: 640px) {
        .pf-summary-grid { grid-template-columns: repeat(4, 1fr); }
    }
    .pf-summary-card {
        border-radius: 12px;
        border: 1.5px solid #e4e8ef;
        background: #f8fafc;
        padding: 12px 14px;
    }
    .pf-summary-card.accent-green {
        border-color: #a7f3d0;
        background: #f0fdf4;
    }
    .pf-summary-card.accent-navy {
        border-color: #1b3358;
        background: #1b3358;
        color: #fff;
    }
    .pf-summary-label {
        font-size: 0.6rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.11em;
        color: #9baec8;
        margin-bottom: 4px;
    }
    .pf-summary-card.accent-green .pf-summary-label { color: #059669; }
    .pf-summary-card.accent-navy .pf-summary-label { color: rgba(255,255,255,.55); }
    .pf-summary-value {
        font-size: 0.95rem;
        font-weight: 800;
        color: #0d1f38;
    }
    .pf-summary-card.accent-navy .pf-summary-value { color: #fff; }
    .pf-summary-card.accent-green .pf-summary-value { color: #065f46; }

    .pf-status-pill {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .pf-status-pill.paid    { background: #d1fae5; color: #065f46; }
    .pf-status-pill.partial { background: #fef3c7; color: #92400e; }
    .pf-status-pill.unpaid  { background: #fee2e2; color: #991b1b; }

    html[data-theme='dark'] .pf-label { color: #7a9ec8; }
    html[data-theme='dark'] .pf-input,
    html[data-theme='dark'] .pf-amount-wrap { background: #1a2b41 !important; border-color: #2a3f5f !important; color: #d8ecff !important; }
    html[data-theme='dark'] .pf-input:focus,
    html[data-theme='dark'] .pf-amount-wrap:focus-within { border-color: #4a82c0 !important; box-shadow: 0 0 0 4px rgba(74,130,192,.15) !important; }
    html[data-theme='dark'] .pf-amount-prefix { background: #152035; border-color: #2a3f5f; color: #7a9ec8; }
    html[data-theme='dark'] .pf-amount-input { color: #d8ecff; }
    html[data-theme='dark'] .pf-summary-card { background: #152035; border-color: #2a3f5f; }
    html[data-theme='dark'] .pf-summary-label { color: #7a9cc0; }
    html[data-theme='dark'] .pf-summary-value { color: #d8ecff; }
    html[data-theme='dark'] .pf-summary-card.accent-navy { background: #1b3358; border-color: #2a4f80; }
</style>

<div class="space-y-6">

    {{-- Error banner --}}
    @if($errors->has('payment'))
        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <i class="bi bi-exclamation-circle-fill text-rose-500 mt-0.5 flex-shrink-0"></i>
            <span class="font-medium">{{ $errors->first('payment') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('payments.store') }}" id="paymentForm" class="space-y-5">
        @csrf

        {{-- Case selector --}}
        <div>
            <label class="pf-label" for="funeral_case_id">Select Open Case <span class="text-rose-400 normal-case tracking-normal">*</span></label>
            @php
                $preselectCase  = $preselectCase ?? null;
                $prefillCaseId  = old('funeral_case_id', $preselectCase->id ?? null);
                $includePreselect = $preselectCase && !$openCases->contains('id', $preselectCase->id);
            @endphp
            <div class="relative">
                <select name="funeral_case_id" id="funeral_case_id" class="pf-input pf-select pr-9" required>
                    <option value="">— Choose a case —</option>
                    @foreach($openCases as $case)
                        <option
                            value="{{ $case->id }}"
                            data-total="{{ $case->total_amount }}"
                            data-paid="{{ $case->total_paid }}"
                            data-balance="{{ $case->balance_amount }}"
                            {{ $prefillCaseId == $case->id ? 'selected' : '' }}
                        >{{ $case->case_code }} · {{ $case->client?->full_name ?? '—' }} · Balance: ₱{{ number_format((float)$case->balance_amount, 2) }}</option>
                    @endforeach
                    @if($includePreselect)
                        <option
                            value="{{ $preselectCase->id }}"
                            data-total="{{ $preselectCase->total_amount }}"
                            data-paid="{{ $preselectCase->total_paid }}"
                            data-balance="{{ $preselectCase->balance_amount }}"
                            selected
                        >{{ $preselectCase->case_code }} · {{ $preselectCase->client?->full_name ?? '—' }} · Balance: ₱{{ number_format((float)$preselectCase->balance_amount, 2) }}</option>
                    @endif
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                    <i class="bi bi-chevron-down text-xs"></i>
                </span>
            </div>
        </div>

        {{-- Case snapshot (current balances) --}}
        <div id="pf_case_snapshot" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-3">Case Snapshot</div>
            <div class="grid grid-cols-3 gap-3 text-xs">
                <div>
                    <div class="text-slate-500 font-medium mb-0.5">Total Due</div>
                    <div class="font-black text-slate-800 text-sm">₱ <span id="pf_snap_total">—</span></div>
                </div>
                <div>
                    <div class="text-slate-500 font-medium mb-0.5">Already Paid</div>
                    <div class="font-black text-emerald-700 text-sm">₱ <span id="pf_snap_paid">—</span></div>
                </div>
                <div>
                    <div class="text-slate-500 font-medium mb-0.5">Balance</div>
                    <div class="font-black text-rose-700 text-sm">₱ <span id="pf_snap_balance">—</span></div>
                </div>
            </div>
        </div>

        {{-- Amount + Date --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="pf-label" for="amount_paid">Amount Received <span class="text-rose-400 normal-case tracking-normal">*</span></label>
                <div class="pf-amount-wrap">
                    <span class="pf-amount-prefix">₱</span>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="amount_paid"
                        id="amount_paid"
                        value="{{ old('amount_paid') }}"
                        class="pf-amount-input"
                        placeholder="0.00"
                        required
                    >
                </div>
                <p id="pf_amount_hint" class="mt-1.5 text-[11px] font-medium text-slate-400">Enter the amount received from the client.</p>
            </div>
            <div>
                <label class="pf-label" for="paid_at_input">Date &amp; Time Received <span class="text-rose-400 normal-case tracking-normal">*</span></label>
                <input
                    type="datetime-local"
                    name="paid_at"
                    id="paid_at_input"
                    value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}"
                    class="pf-input"
                    required
                >
            </div>
        </div>

        {{-- Live preview after payment --}}
        <div>
            <div class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-3">After This Payment</div>
            <div class="pf-summary-grid">
                <div class="pf-summary-card">
                    <div class="pf-summary-label">This Payment</div>
                    <div class="pf-summary-value">₱ <span id="new_payment_display">0.00</span></div>
                </div>
                <div class="pf-summary-card accent-green">
                    <div class="pf-summary-label">Total Paid</div>
                    <div class="pf-summary-value">₱ <span id="new_total_paid_display">—</span></div>
                </div>
                <div class="pf-summary-card accent-navy">
                    <div class="pf-summary-label">Remaining Balance</div>
                    <div class="pf-summary-value">₱ <span id="new_balance_display">—</span></div>
                </div>
                <div class="pf-summary-card">
                    <div class="pf-summary-label">New Status</div>
                    <div class="pf-summary-value" id="new_status_display">—</div>
                </div>
            </div>
        </div>

        {{-- Hidden legacy display elements (JS still writes to these; keep them) --}}
        <span id="total_due_display"   class="hidden"></span>
        <span id="total_paid_display"  class="hidden"></span>
        <span id="balance_display"     class="hidden"></span>

        {{-- Method badge (cash only) --}}
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <i class="bi bi-cash-coin text-slate-500 text-base flex-shrink-0"></i>
            <div class="text-xs font-semibold text-slate-600">Cash payment only. Bank transfer can be recorded separately.</div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3 pt-1">
            <button type="button" id="closePaymentFormBottom"
                class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                Cancel
            </button>
            <button
                type="submit"
                id="pf_submit_btn"
                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-[#1b3358] to-[#1e3a5f] text-white text-sm font-bold shadow-[0_4px_14px_rgba(27,51,88,.32)] hover:shadow-[0_6px_20px_rgba(27,51,88,.42)] hover:-translate-y-0.5 transition-all disabled:opacity-40 disabled:pointer-events-none disabled:transform-none"
                {{ $openCases->isEmpty() ? 'disabled' : '' }}
            >
                <i class="bi bi-check2-circle text-base"></i>
                Save Payment
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const caseSelect    = document.getElementById('funeral_case_id');
    const amountInput   = document.getElementById('amount_paid');
    const snapshot      = document.getElementById('pf_case_snapshot');
    const snapTotal     = document.getElementById('pf_snap_total');
    const snapPaid      = document.getElementById('pf_snap_paid');
    const snapBalance   = document.getElementById('pf_snap_balance');
    const amountHint    = document.getElementById('pf_amount_hint');

    const newPaymentDisplay  = document.getElementById('new_payment_display');
    const newTotalPaidDisplay= document.getElementById('new_total_paid_display');
    const newBalanceDisplay  = document.getElementById('new_balance_display');
    const newStatusDisplay   = document.getElementById('new_status_display');

    const totalDueDisplay  = document.getElementById('total_due_display');
    const totalPaidDisplay = document.getElementById('total_paid_display');
    const balanceDisplay   = document.getElementById('balance_display');

    function toNum(v) { const n = parseFloat(v); return Number.isFinite(n) ? n : 0; }
    function fmt(n)   { return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function statusPill(status) {
        const map = { PAID: 'paid', PARTIAL: 'partial', UNPAID: 'unpaid' };
        const cls = map[status] || 'unpaid';
        return `<span class="pf-status-pill ${cls}">${status}</span>`;
    }

    function updateSummary() {
        const opt     = caseSelect.options[caseSelect.selectedIndex];
        const hasSel  = opt && opt.value;
        const total   = toNum(opt?.dataset.total);
        const paid    = toNum(opt?.dataset.paid);
        const balance = toNum(opt?.dataset.balance);

        if (totalDueDisplay)  totalDueDisplay.textContent  = hasSel ? fmt(total)   : '-';
        if (totalPaidDisplay) totalPaidDisplay.textContent = hasSel ? fmt(paid)    : '-';
        if (balanceDisplay)   balanceDisplay.textContent   = hasSel ? fmt(balance) : '-';

        if (!hasSel) {
            if (snapshot) snapshot.classList.add('hidden');
            if (amountInput) amountInput.max = '';
            newPaymentDisplay.textContent  = '0.00';
            newTotalPaidDisplay.textContent = '—';
            newBalanceDisplay.textContent  = '—';
            newStatusDisplay.innerHTML     = '—';
            if (amountHint) amountHint.textContent = 'Select a case to see the balance.';
            return;
        }

        if (snapshot) {
            snapshot.classList.remove('hidden');
            if (snapTotal)   snapTotal.textContent   = fmt(total);
            if (snapPaid)    snapPaid.textContent    = fmt(paid);
            if (snapBalance) snapBalance.textContent = fmt(balance);
        }

        if (amountInput) {
            amountInput.max = balance.toFixed(2);
            if (!amountInput.value || toNum(amountInput.value) > balance) {
                amountInput.value = balance > 0 ? balance.toFixed(2) : '';
            }
        }

        const entered     = Math.min(Math.max(toNum(amountInput?.value), 0), balance);
        const newTotalPaid= Math.min(paid + entered, total);
        const newBalance  = Math.max(total - newTotalPaid, 0);
        const status      = newTotalPaid <= 0 ? 'UNPAID' : (newBalance > 0 ? 'PARTIAL' : 'PAID');

        newPaymentDisplay.textContent   = fmt(entered);
        newTotalPaidDisplay.textContent = fmt(newTotalPaid);
        newBalanceDisplay.textContent   = fmt(newBalance);
        newStatusDisplay.innerHTML      = statusPill(status);

        if (amountHint) {
            amountHint.textContent = balance > 0
                ? `Max receivable: ₱${fmt(balance)} (remaining balance)`
                : 'This case is fully paid.';
        }
    }

    if (caseSelect) caseSelect.addEventListener('change', updateSummary);
    if (amountInput) amountInput.addEventListener('input', updateSummary);

    updateSummary();
})();
</script>
