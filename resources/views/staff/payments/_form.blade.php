{{-- NOTE: Keep input names/ids; JS summary and PaymentController depend on them. --}}
<style>
    .pf-shell { display: flex; flex-direction: column; gap: 1.25rem; }
    .pf-guide {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
        border: 1px solid #dbe7f5;
        border-radius: 18px;
        background: #f8fbff;
        padding: .85rem;
    }
    .pf-guide-step {
        display: flex;
        align-items: flex-start;
        gap: .7rem;
        border-radius: 14px;
        background: #ffffff;
        border: 1px solid #e6eef8;
        padding: .85rem;
        min-width: 0;
    }
    .pf-guide-num,
    .pf-section-index {
        width: 1.65rem;
        height: 1.65rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        background: #3E4A3D;
        color: #ffffff;
        font-size: .72rem;
        font-weight: 950;
        line-height: 1;
    }
    .pf-guide-title {
        color: #0d1f38;
        font-size: .82rem;
        font-weight: 950;
        line-height: 1.25;
    }
    .pf-guide-copy {
        margin-top: .18rem;
        color: #5F685F;
        font-size: .72rem;
        font-weight: 650;
        line-height: 1.35;
    }
    .pf-section {
        border: 1px solid #C9C5BB;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .05);
        overflow: hidden;
    }
    .pf-section-head {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid #edf2f7;
        background: linear-gradient(180deg, #ffffff 0%, #FAFAF7 100%);
    }
    .pf-section-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: .8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background: #3E4A3D;
        box-shadow: 0 8px 18px rgba(62, 74, 61, .18);
        flex: 0 0 auto;
    }
    .pf-section-title { margin: 0; color: #0d1f38; font-size: .98rem; font-weight: 900; line-height: 1.2; }
    .pf-section-sub { margin-top: .18rem; color: #5F685F; font-size: .78rem; font-weight: 600; line-height: 1.35; }
    .pf-section-body { padding: 1.1rem; }
    .pf-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
    .pf-grid.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pf-grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .pf-field { min-width: 0; }
    .pf-label {
        display: flex;
        align-items: center;
        gap: .3rem;
        font-size: 0.68rem;
        font-weight: 900;
        color: #4d6480;
        text-transform: uppercase;
        letter-spacing: 0.085em;
        margin-bottom: 0.45rem;
    }
    .pf-required { color: #f43f5e; font-size: .78rem; line-height: 1; }
    .pf-input {
        width: 100%;
        min-height: 2.9rem;
        border-radius: 12px;
        border: 1.5px solid #dde4ee;
        background: #ffffff;
        padding: 0.78rem 1rem;
        font-size: 0.9rem;
        font-weight: 500;
        color: #0d1f38;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        transition: border-color .18s, box-shadow .18s, background .18s;
        font-family: inherit;
        box-sizing: border-box;
    }
    .pf-input:focus {
        border-color: #3E4A3D;
        box-shadow: 0 0 0 3px rgba(62,74,61,.18);
        outline: none;
        background: #fff;
    }
    .pf-input[readonly] { background: #FAFAF7; color: #5F685F; }
    .pf-input::placeholder { color: #a8b6c7; font-weight: 500; }
    .pf-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        cursor: pointer;
        height: 2.9rem;
        line-height: normal;
        padding-right: 2.65rem;
        vertical-align: middle;
        background-image: none;
    }
    .pf-select::-ms-expand { display: none; }
    .pf-control-wrap { position: relative; }
    .pf-control-icon {
        pointer-events: none;
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #7A8076;
        font-size: .78rem;
        line-height: 1;
    }
    .pf-control-wrap:focus-within .pf-control-icon { color: #3E4A3D; }
    .pf-help { margin-top: .45rem; color: #5F685F; font-size: .72rem; font-weight: 600; line-height: 1.35; }
    .pf-help.soft { color: #7A8076; }
    .pf-amount-wrap {
        display: flex;
        align-items: stretch;
        min-height: 2.95rem;
        border-radius: 12px;
        border: 1.5px solid #dde4ee;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        transition: border-color .18s, box-shadow .18s, background .18s;
        overflow: hidden;
    }
    .pf-amount-wrap:focus-within {
        border-color: #3E4A3D;
        box-shadow: 0 0 0 3px rgba(62,74,61,.18);
        background: #fff;
    }
    .pf-amount-prefix {
        padding: 0 14px;
        font-size: .95rem;
        font-weight: 900;
        color: #4d6480;
        background: #F3F0E8;
        border-right: 1.5px solid #dde4ee;
        display: flex;
        align-items: center;
        flex-shrink: 0;
        user-select: none;
    }
    .pf-amount-input {
        flex: 1;
        border: 0;
        outline: none;
        padding: 0.75rem 1rem;
        font-size: 1.03rem;
        font-weight: 900;
        color: #0d1f38;
        background: transparent;
        font-family: inherit;
        width: 100%;
    }
    .pf-amount-input::placeholder { color: #a8b6c7; font-weight: 500; }

    .pf-snapshot {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
        border-radius: 14px;
        border: 1px solid #C9C5BB;
        background: #FAFAF7;
        padding: .85rem;
        margin-top: .9rem;
    }
    .pf-snapshot-item { padding: .35rem .5rem; }
    .pf-snapshot-label { color: #5F685F; font-size: .7rem; font-weight: 800; margin-bottom: .2rem; }
    .pf-snapshot-value { color: #0d1f38; font-size: .95rem; font-weight: 950; font-variant-numeric: tabular-nums; }
    .pf-snapshot-value.good { color: #047857; }
    .pf-snapshot-value.warn { color: #be123c; }

    .pf-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
    .pf-summary-card {
        border-radius: 14px;
        border: 1.5px solid #e4e8ef;
        background: #FAFAF7;
        padding: .85rem .95rem;
        min-width: 0;
    }
    .pf-summary-card.accent-green { border-color: #a7f3d0; background: #f0fdf4; }
    .pf-summary-card.accent-navy { border-color: #3E4A3D; background: #3E4A3D; color: #fff; }
    .pf-summary-label {
        font-size: 0.6rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #8da1bc;
        margin-bottom: .28rem;
    }
    .pf-summary-card.accent-green .pf-summary-label { color: #059669; }
    .pf-summary-card.accent-navy .pf-summary-label { color: rgba(255,255,255,.62); }
    .pf-summary-value {
        font-size: .98rem;
        font-weight: 950;
        color: #0d1f38;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }
    .pf-summary-card.accent-navy .pf-summary-value { color: #fff; }
    .pf-summary-card.accent-green .pf-summary-value { color: #065f46; }
    .pf-status-pill {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .pf-status-pill.paid { background: #d1fae5; color: #065f46; }
    .pf-status-pill.partial { background: #fef3c7; color: #92400e; }
    .pf-status-pill.unpaid { background: #fee2e2; color: #991b1b; }
    .pf-bank-box {
        border-radius: 16px;
        border: 1px solid #dbe5f0;
        background: #FAFAF7;
        padding: 1rem;
    }
    .pf-note {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        border-radius: 14px;
        border: 1px solid #C9C5BB;
        background: #FAFAF7;
        padding: .85rem 1rem;
        color: #5F685F;
        font-size: .78rem;
        font-weight: 650;
        line-height: 1.45;
    }
    .pf-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .75rem;
        padding-top: .15rem;
    }
    .pf-btn {
        min-height: 2.65rem;
        border-radius: 13px;
        padding: 0 .95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        font-size: .86rem;
        font-weight: 850;
        transition: transform .18s, box-shadow .18s, background .18s, color .18s;
    }
    .pf-btn.secondary { border: 1px solid #dbe3ee; background: #fff; color: #5F685F; }
    .pf-btn.secondary:hover { background: #F3F0E8; border-color: #3E4A3D; color: #3E4A3D; }
    .pf-btn.primary {
        border: 1px solid #2D372D;
        background: linear-gradient(135deg, #3E4A3D, #2D372D);
        color: #fff;
        box-shadow: 0 10px 22px rgba(62,74,61,.20);
    }
    .pf-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 14px 28px rgba(62,74,61,.28); }
    .pf-btn:disabled { opacity: .45; pointer-events: none; transform: none; box-shadow: none; }

    html[data-theme='dark'] .pf-section { background: #102033; border-color: #243954; }
    html[data-theme='dark'] .pf-guide { background: #102033; border-color: #243954; }
    html[data-theme='dark'] .pf-guide-step { background: #152035; border-color: #2a3f5f; }
    html[data-theme='dark'] .pf-guide-title { color: #e2ecf9; }
    html[data-theme='dark'] .pf-guide-copy { color: #8fabca; }
    html[data-theme='dark'] .pf-section-head { background: linear-gradient(180deg, #13263d 0%, #102033 100%); border-color: #243954; }
    html[data-theme='dark'] .pf-section-title { color: #e2ecf9; }
    html[data-theme='dark'] .pf-section-sub,
    html[data-theme='dark'] .pf-help { color: #8fabca; }
    html[data-theme='dark'] .pf-label { color: #7a9ec8; }
    html[data-theme='dark'] .pf-input,
    html[data-theme='dark'] .pf-amount-wrap { background: #1a2b41 !important; border-color: #2a3f5f !important; color: #d8ecff !important; }
    html[data-theme='dark'] .pf-input:focus,
    html[data-theme='dark'] .pf-amount-wrap:focus-within { border-color: #4a82c0 !important; box-shadow: 0 0 0 4px rgba(74,130,192,.15) !important; }
    html[data-theme='dark'] .pf-input[readonly] { background: #152035 !important; color: #8fabca !important; }
    html[data-theme='dark'] .pf-amount-prefix { background: #152035; border-color: #2a3f5f; color: #7a9ec8; }
    html[data-theme='dark'] .pf-amount-input { color: #d8ecff; }
    html[data-theme='dark'] .pf-snapshot,
    html[data-theme='dark'] .pf-bank-box,
    html[data-theme='dark'] .pf-note,
    html[data-theme='dark'] .pf-summary-card { background: #152035; border-color: #2a3f5f; }
    html[data-theme='dark'] .pf-snapshot-label,
    html[data-theme='dark'] .pf-summary-label { color: #7a9cc0; }
    html[data-theme='dark'] .pf-snapshot-value,
    html[data-theme='dark'] .pf-summary-value { color: #d8ecff; }
    html[data-theme='dark'] .pf-summary-card.accent-navy { background: #3E4A3D; border-color: #2a4f80; }
    html[data-theme='dark'] .pf-btn.secondary { background: #152035; border-color: #2a3f5f; color: #d8ecff; }

    @media (max-width: 900px) {
        .pf-guide,
        .pf-grid.two,
        .pf-grid.three,
        .pf-summary-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .pf-guide { grid-template-columns: 1fr; padding: .95rem; }
        .pf-section-head { padding: .95rem; }
        .pf-section-body { padding: .95rem; }
        .pf-grid.two,
        .pf-grid.three,
        .pf-summary-grid,
        .pf-snapshot { grid-template-columns: 1fr; }
        .pf-actions { flex-direction: column-reverse; align-items: stretch; }
        .pf-btn { width: 100%; }
    }
</style>

<div class="pf-shell">

    {{-- Error banner --}}
    @if($errors->has('payment'))
        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <i class="bi bi-exclamation-circle-fill text-rose-500 mt-0.5 flex-shrink-0"></i>
            <span class="font-medium">{{ $errors->first('payment') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('payments.store') }}" id="paymentForm" class="pf-shell">
        @csrf

        <div class="pf-guide" aria-label="Payment recording steps">
            <div class="pf-guide-step">
                <span class="pf-guide-num">1</span>
                <div>
                    <div class="pf-guide-title">Select the case</div>
                    <div class="pf-guide-copy">Pick the case first so the form can show the current balance and maximum receivable amount.</div>
                </div>
            </div>
            <div class="pf-guide-step">
                <span class="pf-guide-num">2</span>
                <div>
                    <div class="pf-guide-title">Enter payment details</div>
                    <div class="pf-guide-copy">Choose Cash or Cashless, then enter the amount received, date, and required transaction details.</div>
                </div>
            </div>
            <div class="pf-guide-step">
                <span class="pf-guide-num">3</span>
                <div>
                    <div class="pf-guide-title">Check the preview</div>
                    <div class="pf-guide-copy">Review the new paid amount, balance, and payment status before saving.</div>
                </div>
            </div>
        </div>

        <section class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-index">1</div>
                <div>
                    <h3 class="pf-section-title">Select Case</h3>
                    <div class="pf-section-sub">Start here. The selected case controls the balance, payment limit, and status preview.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-field">
                    <label class="pf-label" for="funeral_case_id">Start Here: Funeral Case <span class="pf-required">*</span></label>
                    @php
                        $preselectCase  = $preselectCase ?? null;
                        $prefillCaseId  = old('funeral_case_id', $preselectCase->id ?? null);
                        $includePreselect = $preselectCase && !$openCases->contains('id', $preselectCase->id);
                    @endphp
                    <div class="pf-control-wrap">
                        <select name="funeral_case_id" id="funeral_case_id" class="pf-input pf-select" required>
                            <option value="">Choose a case with remaining balance</option>
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
                        <span class="pf-control-icon"><i class="bi bi-chevron-down"></i></span>
                    </div>
                    <p class="pf-help soft">Only open cases with remaining payable balance are listed. You may also click an open case row behind this form to preselect it.</p>
                </div>

                <div id="pf_case_snapshot" class="pf-snapshot hidden">
                    <div class="pf-snapshot-item">
                        <div class="pf-snapshot-label">Total Case Amount</div>
                        <div class="pf-snapshot-value">₱ <span id="pf_snap_total">—</span></div>
                    </div>
                    <div class="pf-snapshot-item">
                        <div class="pf-snapshot-label">Total Paid</div>
                        <div class="pf-snapshot-value good">₱ <span id="pf_snap_paid">—</span></div>
                    </div>
                    <div class="pf-snapshot-item">
                        <div class="pf-snapshot-label">Remaining Balance</div>
                        <div class="pf-snapshot-value warn">₱ <span id="pf_snap_balance">—</span></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-index">2</div>
                <div>
                    <h3 class="pf-section-title">Enter Payment Received</h3>
                    <div class="pf-section-sub">Choose the payment method, then enter the exact amount, date received, and optional receipt number.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-grid two">
                    <div class="pf-field">
                        <label class="pf-label" for="payment_method">Payment Method <span class="pf-required">*</span></label>
                        <div class="pf-control-wrap">
                            <select name="payment_method" id="payment_method" class="pf-input pf-select" required>
                                <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="cashless" {{ in_array(old('payment_method'), ['cashless', 'bank_transfer'], true) ? 'selected' : '' }}>Cashless</option>
                            </select>
                            <span class="pf-control-icon"><i class="bi bi-chevron-down"></i></span>
                        </div>
                        <p class="pf-help soft">Choose Cash for physical payment. Choose Cashless for bank transfer, GCash, Maya, card, or other channels.</p>
                    </div>

                    <div class="pf-field">
                        <label class="pf-label" for="payment_record_no_display">Internal Payment Record No.</label>
                        <input type="text" id="payment_record_no_display" value="Generated after saving" class="pf-input" readonly>
                        <p class="pf-help soft">System-generated internal payment tracking number.</p>
                    </div>
                </div>

                <div class="pf-grid two" style="margin-top:1rem;">
                    <div class="pf-field">
                        <label class="pf-label" for="amount_paid">Amount Received <span class="pf-required">*</span></label>
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
                        <p id="pf_amount_hint" class="pf-help soft">Choose a case first. This amount cannot exceed the remaining balance.</p>
                        <p id="pf_amount_formatted" class="pf-help" style="font-weight:800;color:#333333;">₱0.00</p>
                    </div>

                    <div class="pf-field">
                        <label class="pf-label" for="paid_at_input">Payment Date &amp; Time <span class="pf-required">*</span></label>
                        <input
                            type="datetime-local"
                            name="paid_at"
                            id="paid_at_input"
                            value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}"
                            class="pf-input"
                            required
                        >
                        <p class="pf-help soft">Use the actual date and time the payment was received. Future dates are not allowed.</p>
                    </div>
                </div>

                <div class="pf-grid two" style="margin-top:1rem;">
                    <div class="pf-field">
                        <label class="pf-label" for="receipt_or_no">Receipt / OR No.</label>
                        <input type="text" name="receipt_or_no" id="receipt_or_no" value="{{ old('receipt_or_no', old('accounting_reference_no')) }}" class="pf-input" maxlength="100" placeholder="Optional receipt or OR number">
                        <p class="pf-help soft">Optional. Leave this blank if no receipt or OR has been issued yet.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="pf_cashless_fields" class="pf-section hidden">
            <div class="pf-section-head">
                <div class="pf-section-index">3</div>
                <div>
                    <h3 class="pf-section-title">Cashless Details</h3>
                    <div class="pf-section-sub">This section appears only for Cashless payments. Select the channel before entering the reference details.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-bank-box">
                    <div class="pf-grid two">
                        <div class="pf-field">
                            <label class="pf-label" for="cashless_type">Cashless Type <span class="pf-required">*</span></label>
                            <div class="pf-control-wrap">
                                <select name="cashless_type" id="cashless_type" class="pf-input pf-select">
                                    <option value="">Select cashless type</option>
                                    <option value="bank_transfer" {{ old('cashless_type', old('payment_method') === 'bank_transfer' ? 'bank_transfer' : null) === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="gcash" {{ old('cashless_type') === 'gcash' ? 'selected' : '' }}>GCash</option>
                                    <option value="maya" {{ old('cashless_type') === 'maya' ? 'selected' : '' }}>Maya</option>
                                    <option value="card" {{ old('cashless_type') === 'card' ? 'selected' : '' }}>Card</option>
                                    <option value="other" {{ old('cashless_type') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <span class="pf-control-icon"><i class="bi bi-chevron-down"></i></span>
                            </div>
                        </div>
                    </div>

                    <div data-cashless-panel="bank_transfer" class="cashless-panel hidden">
                        <div class="pf-grid two" style="margin-top:1rem;">
                            <div class="pf-field">
                                <label class="pf-label" for="bank_name">Bank Name <span class="pf-required">*</span></label>
                                <div class="pf-control-wrap">
                                    <select name="bank_name" id="bank_name" class="pf-input pf-select">
                                        <option value="">Select bank</option>
                                        @foreach(['BDO', 'BPI', 'Metrobank', 'Landbank', 'Security Bank', 'UnionBank', 'RCBC', 'PNB', 'China Bank', 'Other Bank'] as $bank)
                                            <option value="{{ $bank }}" {{ old('bank_name', old('bank_or_channel') === 'Other' ? 'Other Bank' : old('bank_or_channel')) === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                                        @endforeach
                                    </select>
                                    <span class="pf-control-icon"><i class="bi bi-chevron-down"></i></span>
                                </div>
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="account_name">Account Name</label>
                                <input type="text" name="account_name" id="account_name" value="{{ old('account_name', old('sender_name')) }}" class="pf-input" maxlength="100" placeholder="Name shown on transfer record">
                            </div>
                        </div>
                        <div id="pf_other_bank_wrap" class="hidden" style="margin-top:1rem;">
                            <label class="pf-label" for="other_bank_name">Other Bank Name <span class="pf-required">*</span></label>
                            <input type="text" name="other_bank_name" id="other_bank_name" value="{{ old('other_bank_name', old('other_bank_or_channel')) }}" class="pf-input" maxlength="100" placeholder="Enter bank name">
                        </div>
                    </div>

                    <div data-cashless-panel="gcash" class="cashless-panel hidden">
                        <input type="hidden" name="wallet_provider" id="wallet_provider" value="{{ old('wallet_provider') }}">
                        <div class="pf-grid two" style="margin-top:1rem;">
                            <div class="pf-field">
                                <label class="pf-label" for="gcash_account_name">GCash Account Name</label>
                                <input type="text" id="gcash_account_name" data-account-name-field value="{{ old('account_name') }}" class="pf-input" maxlength="100" placeholder="Optional">
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="gcash_mobile_number">GCash Mobile Number</label>
                                <input type="text" id="gcash_mobile_number" data-mobile-number-field value="{{ old('mobile_number') }}" class="pf-input" maxlength="30" placeholder="Optional">
                            </div>
                        </div>
                    </div>

                    <div data-cashless-panel="maya" class="cashless-panel hidden">
                        <div class="pf-grid two" style="margin-top:1rem;">
                            <div class="pf-field">
                                <label class="pf-label" for="maya_account_name">Maya Account Name</label>
                                <input type="text" id="maya_account_name" data-account-name-field value="{{ old('account_name') }}" class="pf-input" maxlength="100" placeholder="Optional">
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="maya_mobile_number">Maya Mobile Number</label>
                                <input type="text" id="maya_mobile_number" data-mobile-number-field value="{{ old('mobile_number') }}" class="pf-input" maxlength="30" placeholder="Optional">
                            </div>
                        </div>
                    </div>

                    <div data-cashless-panel="card" class="cashless-panel hidden">
                        <div class="pf-grid two" style="margin-top:1rem;">
                            <div class="pf-field">
                                <label class="pf-label" for="card_type">Card Type</label>
                                <div class="pf-control-wrap">
                                    <select name="card_type" id="card_type" class="pf-input pf-select">
                                        <option value="">Select card type</option>
                                        <option value="debit" {{ old('card_type') === 'debit' ? 'selected' : '' }}>Debit</option>
                                        <option value="credit" {{ old('card_type') === 'credit' ? 'selected' : '' }}>Credit</option>
                                    </select>
                                    <span class="pf-control-icon"><i class="bi bi-chevron-down"></i></span>
                                </div>
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="terminal_provider">Terminal / Provider</label>
                                <input type="text" name="terminal_provider" id="terminal_provider" value="{{ old('terminal_provider') }}" class="pf-input" maxlength="80" placeholder="Optional">
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="approval_code">Approval Code</label>
                                <input type="text" name="approval_code" id="approval_code" value="{{ old('approval_code') }}" class="pf-input" maxlength="40" placeholder="Required if no reference no.">
                            </div>
                            <div class="pf-field">
                                <label class="pf-label">Reference No.</label>
                                <p class="pf-help soft">Card payments need either approval code or reference number.</p>
                            </div>
                        </div>
                    </div>

                    <div data-cashless-panel="other" class="cashless-panel hidden">
                        <div class="pf-grid two" style="margin-top:1rem;">
                            <div class="pf-field">
                                <label class="pf-label" for="payment_channel_other">Payment Channel <span class="pf-required">*</span></label>
                                <input type="text" id="payment_channel_other" data-payment-channel-field value="{{ old('payment_channel') }}" class="pf-input" maxlength="100" placeholder="e.g., PalawanPay">
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="payment_notes">Notes</label>
                                <input type="text" name="payment_notes" id="payment_notes" value="{{ old('payment_notes') }}" class="pf-input" maxlength="255" placeholder="Optional">
                            </div>
                        </div>
                    </div>

                    <div id="pf_cashless_tracking" class="pf-grid two" style="margin-top:1rem;">
                        <div class="pf-field">
                            <label class="pf-label" for="reference_number">Reference No. <span class="pf-required">*</span></label>
                            <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number', old('transaction_reference_no')) }}" class="pf-input" maxlength="100" placeholder="Reference number from bank, wallet, or channel">
                        </div>
                    </div>

                    <input type="hidden" name="mobile_number" id="mobile_number" value="{{ old('mobile_number') }}">
                    <input type="hidden" name="payment_channel" id="payment_channel" value="{{ old('payment_channel') }}">
                    <input type="hidden" name="transaction_reference_no" id="transaction_reference_no" value="{{ old('transaction_reference_no') }}">
                </div>
            </div>
        </section>

        <section class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-index">4</div>
                <div>
                    <h3 class="pf-section-title">Check Result Before Saving</h3>
                    <div class="pf-section-sub">Add optional remarks, then verify the paid amount, remaining balance, and new payment status.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-field">
                    <label class="pf-label" for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="pf-input" style="min-height:90px; resize:vertical;" maxlength="1000" placeholder="Optional notes about this payment">{{ old('remarks') }}</textarea>
                </div>

                <div style="margin-top:1rem;">
                    <div class="pf-label" style="margin-bottom:.65rem;">After This Payment</div>
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
            </div>
        </section>

        {{-- Hidden legacy display elements (JS still writes to these; keep them) --}}
        <span id="total_due_display" class="hidden"></span>
        <span id="total_paid_display" class="hidden"></span>
        <span id="balance_display" class="hidden"></span>

        <div class="pf-note">
            <i class="bi bi-info-circle text-slate-500 text-base flex-shrink-0 mt-0.5"></i>
            <div>Before saving, make sure the selected case, amount received, payment method, date received, and transaction details are correct.</div>
        </div>

        <div class="pf-actions">
            <button type="button" id="closePaymentFormBottom" class="pf-btn secondary">
                Cancel
            </button>
            <button type="submit" id="pf_submit_btn" class="pf-btn primary" {{ $openCases->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-check2-circle text-base"></i>
                Save Payment
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const form          = document.getElementById('paymentForm');
    const caseSelect    = document.getElementById('funeral_case_id');
    const amountInput   = document.getElementById('amount_paid');
    const paymentMethod = document.getElementById('payment_method');
    const cashlessFields = document.getElementById('pf_cashless_fields');
    const cashlessType   = document.getElementById('cashless_type');
    const bankName       = document.getElementById('bank_name');
    const otherBankWrap  = document.getElementById('pf_other_bank_wrap');
    const otherBankName  = document.getElementById('other_bank_name');
    const referenceNo    = document.getElementById('reference_number');
    const approvalCode   = document.getElementById('approval_code');
    const walletProvider = document.getElementById('wallet_provider');
    const accountName    = document.getElementById('account_name');
    const mobileNumber   = document.getElementById('mobile_number');
    const paymentChannel = document.getElementById('payment_channel');
    const transactionRef = document.getElementById('transaction_reference_no');
    const snapshot      = document.getElementById('pf_case_snapshot');
    const snapTotal     = document.getElementById('pf_snap_total');
    const snapPaid      = document.getElementById('pf_snap_paid');
    const snapBalance   = document.getElementById('pf_snap_balance');
    const amountHint    = document.getElementById('pf_amount_hint');
    const amountFormatted = document.getElementById('pf_amount_formatted');

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
            if (amountHint) amountHint.textContent = 'Select a case to see the remaining balance.';
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
        if (amountFormatted) amountFormatted.textContent = `₱${fmt(toNum(amountInput?.value))}`;

        if (amountHint) {
            amountHint.textContent = balance > 0
                ? `Maximum receivable: ₱${fmt(balance)} remaining balance.`
                : 'This case is fully paid.';
        }
    }

    function updatePaymentMethodFields() {
        const isCashless = paymentMethod?.value === 'cashless';
        const type = cashlessType?.value || '';
        if (cashlessFields) cashlessFields.classList.toggle('hidden', !isCashless);
        if (cashlessType) cashlessType.required = isCashless;
        if (!isCashless) {
            if (cashlessType) cashlessType.value = '';
            if (bankName) bankName.value = '';
            if (otherBankName) otherBankName.value = '';
            if (referenceNo) referenceNo.value = '';
            if (approvalCode) approvalCode.value = '';
            if (walletProvider) walletProvider.value = '';
            if (accountName) accountName.value = '';
            if (mobileNumber) mobileNumber.value = '';
            if (paymentChannel) paymentChannel.value = '';
            if (transactionRef) transactionRef.value = '';
        }

        document.querySelectorAll('.cashless-panel').forEach(panel => {
            panel.classList.toggle('hidden', !isCashless || panel.dataset.cashlessPanel !== type);
        });

        if (bankName) bankName.required = isCashless && type === 'bank_transfer';
        const isOtherBank = isCashless && type === 'bank_transfer' && bankName?.value === 'Other Bank';
        if (otherBankWrap) otherBankWrap.classList.toggle('hidden', !isOtherBank);
        if (otherBankName) otherBankName.required = isOtherBank;

        const needsReference = isCashless && ['bank_transfer', 'gcash', 'maya', 'other'].includes(type);
        if (referenceNo) referenceNo.required = needsReference;
        if (approvalCode) approvalCode.required = false;

        const activeAccount = document.querySelector(`[data-cashless-panel="${type}"] [data-account-name-field]`);
        const activeMobile = document.querySelector(`[data-cashless-panel="${type}"] [data-mobile-number-field]`);
        const activeChannel = document.querySelector(`[data-cashless-panel="${type}"] [data-payment-channel-field]`);
        if (accountName && activeAccount) accountName.value = activeAccount.value;
        if (mobileNumber) mobileNumber.value = activeMobile ? activeMobile.value : '';
        if (paymentChannel) paymentChannel.value = activeChannel ? activeChannel.value : '';
        if (walletProvider) walletProvider.value = type === 'gcash' ? 'GCash' : (type === 'maya' ? 'Maya' : '');
        if (transactionRef && referenceNo) transactionRef.value = referenceNo.value;
    }

    function fail(field, message) {
        if (!field) return false;
        field.setCustomValidity(message);
        field.reportValidity();
        field.focus();
        return false;
    }

    const validReferencePattern = /^[A-Za-z0-9 _/-]+$/;
    const validAccountNamePattern = /^(?=.*[\p{L}])[\p{L}\p{M} .'-]+$/u;
    function validateReferenceValue(field, required = false) {
        const value = (field?.value || '').trim();
        if (required && !value) return fail(field, 'Reference number is required.');
        if (!value) return true;
        if (value.length < 4 || value.length > 60) return fail(field, 'Reference number must be 4 to 60 characters.');
        if (!validReferencePattern.test(value)) return fail(field, 'Reference number contains invalid characters.');
        field.setCustomValidity('');
        return true;
    }
    function validateAccountNameValue(field) {
        const value = (field?.value || '').trim();
        if (!value) return true;
        if (value.length < 2 || value.length > 100) return fail(field, 'Account name must be between 2 and 100 characters.');
        if (!validAccountNamePattern.test(value)) return fail(field, 'Account name should contain letters only and must not include numbers or special characters.');
        field.setCustomValidity('');
        return true;
    }

    function clearCashlessValidity() {
        [
            cashlessType,
            bankName,
            otherBankName,
            accountName,
            referenceNo,
            approvalCode,
            ...document.querySelectorAll('[data-account-name-field], [data-mobile-number-field], [data-payment-channel-field]'),
        ].forEach(field => field?.setCustomValidity?.(''));
    }

    function validateCashlessDetails() {
        updatePaymentMethodFields();
        clearCashlessValidity();

        if (paymentMethod?.value !== 'cashless') return true;

        const type = cashlessType?.value || '';
        const ref = (referenceNo?.value || '').trim();
        const approval = (approvalCode?.value || '').trim();
        const activeChannel = document.querySelector(`[data-cashless-panel="${type}"] [data-payment-channel-field]`);
        const activeAccount = document.querySelector(`[data-cashless-panel="${type}"] [data-account-name-field]`);

        if (!type) return fail(cashlessType, 'Please select a cashless type.');
        if (type === 'bank_transfer') {
            if (!(bankName?.value || '').trim()) return fail(bankName, 'Please select the bank name.');
            if (bankName.value === 'Other Bank' && !(otherBankName?.value || '').trim()) {
                return fail(otherBankName, 'Please enter the other bank name.');
            }
            if (!validateAccountNameValue(accountName)) return false;
            if (!validateReferenceValue(referenceNo, true)) return false;
        }
        if (type === 'gcash' || type === 'maya') {
            if (!validateAccountNameValue(activeAccount)) return false;
            if (!validateReferenceValue(referenceNo, true)) return false;
        }
        if (type === 'card' && !approval && !ref) {
            return fail(approvalCode, 'Please enter the approval code or reference number.');
        }
        if (type === 'card' && ref && !validateReferenceValue(referenceNo, false)) return false;
        if (type === 'other') {
            if (!(activeChannel?.value || '').trim()) return fail(activeChannel, 'Please enter the payment channel.');
            if (!validateReferenceValue(referenceNo, true)) return false;
        }

        return true;
    }

    if (caseSelect) caseSelect.addEventListener('change', updateSummary);
    if (amountInput) amountInput.addEventListener('input', updateSummary);
    if (paymentMethod) paymentMethod.addEventListener('change', updatePaymentMethodFields);
    if (cashlessType) cashlessType.addEventListener('change', updatePaymentMethodFields);
    if (bankName) bankName.addEventListener('change', updatePaymentMethodFields);
    if (accountName) {
        accountName.addEventListener('input', () => {
            accountName.setCustomValidity('');
            updatePaymentMethodFields();
        });
        accountName.addEventListener('blur', () => validateAccountNameValue(accountName));
    }
    if (referenceNo) {
        referenceNo.addEventListener('input', () => {
            referenceNo.setCustomValidity('');
            updatePaymentMethodFields();
        });
        referenceNo.addEventListener('blur', () => validateReferenceValue(referenceNo, false));
    }
    document.querySelectorAll('[data-account-name-field], [data-mobile-number-field], [data-payment-channel-field]').forEach(field => {
        field.addEventListener('input', () => {
            field.setCustomValidity('');
            updatePaymentMethodFields();
        });
    });
    document.querySelectorAll('[data-account-name-field]').forEach(field => {
        field.addEventListener('blur', () => validateAccountNameValue(field));
    });
    if (approvalCode) approvalCode.addEventListener('input', () => {
        approvalCode.setCustomValidity('');
        referenceNo?.setCustomValidity('');
    });
    form?.addEventListener('submit', (event) => {
        if (!validateCashlessDetails()) {
            event.preventDefault();
        }
    });

    updateSummary();
    updatePaymentMethodFields();
})();
</script>
