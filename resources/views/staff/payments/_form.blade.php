{{-- NOTE: Keep input names/ids; JS summary and PaymentController depend on them. --}}
<style>
    .pf-shell { display: flex; flex-direction: column; gap: 1.25rem; }
    .pf-section {
        border: 1px solid #e2e8f0;
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
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }
    .pf-section-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: .8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background: #1b3358;
        box-shadow: 0 8px 18px rgba(27, 51, 88, .22);
        flex: 0 0 auto;
    }
    .pf-section-title { margin: 0; color: #0d1f38; font-size: .98rem; font-weight: 900; line-height: 1.2; }
    .pf-section-sub { margin-top: .18rem; color: #64748b; font-size: .78rem; font-weight: 600; line-height: 1.35; }
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
        border-color: #1b3358;
        box-shadow: 0 0 0 4px rgba(27,51,88,.09);
        outline: none;
        background: #fafcff;
    }
    .pf-input[readonly] { background: #f8fafc; color: #64748b; }
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
        color: #94a3b8;
        font-size: .78rem;
        line-height: 1;
    }
    .pf-control-wrap:focus-within .pf-control-icon { color: #1b3358; }
    .pf-help { margin-top: .45rem; color: #64748b; font-size: .72rem; font-weight: 600; line-height: 1.35; }
    .pf-help.soft { color: #94a3b8; }
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
        border-color: #1b3358;
        box-shadow: 0 0 0 4px rgba(27,51,88,.09);
        background: #fafcff;
    }
    .pf-amount-prefix {
        padding: 0 14px;
        font-size: .95rem;
        font-weight: 900;
        color: #4d6480;
        background: #f4f7fb;
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
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: .85rem;
        margin-top: .9rem;
    }
    .pf-snapshot-item { padding: .35rem .5rem; }
    .pf-snapshot-label { color: #64748b; font-size: .7rem; font-weight: 800; margin-bottom: .2rem; }
    .pf-snapshot-value { color: #0d1f38; font-size: .95rem; font-weight: 950; font-variant-numeric: tabular-nums; }
    .pf-snapshot-value.good { color: #047857; }
    .pf-snapshot-value.warn { color: #be123c; }

    .pf-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
    .pf-summary-card {
        border-radius: 14px;
        border: 1.5px solid #e4e8ef;
        background: #f8fafc;
        padding: .85rem .95rem;
        min-width: 0;
    }
    .pf-summary-card.accent-green { border-color: #a7f3d0; background: #f0fdf4; }
    .pf-summary-card.accent-navy { border-color: #1b3358; background: #1b3358; color: #fff; }
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
        background: #f8fafc;
        padding: 1rem;
    }
    .pf-note {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: .85rem 1rem;
        color: #475569;
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
    .pf-btn.secondary { border: 1px solid #dbe3ee; background: #fff; color: #475569; }
    .pf-btn.secondary:hover { background: #f8fafc; color: #0f172a; }
    .pf-btn.primary {
        border: 1px solid #1b3358;
        background: linear-gradient(135deg, #1b3358, #21476f);
        color: #fff;
        box-shadow: 0 10px 22px rgba(27,51,88,.25);
    }
    .pf-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 14px 28px rgba(27,51,88,.32); }
    .pf-btn:disabled { opacity: .45; pointer-events: none; transform: none; box-shadow: none; }

    html[data-theme='dark'] .pf-section { background: #102033; border-color: #243954; }
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
    html[data-theme='dark'] .pf-summary-card.accent-navy { background: #1b3358; border-color: #2a4f80; }
    html[data-theme='dark'] .pf-btn.secondary { background: #152035; border-color: #2a3f5f; color: #d8ecff; }

    @media (max-width: 900px) {
        .pf-grid.two,
        .pf-grid.three,
        .pf-summary-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
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

        <section class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-icon"><i class="bi bi-folder2-open"></i></div>
                <div>
                    <h3 class="pf-section-title">Case to Receive Payment</h3>
                    <div class="pf-section-sub">Choose the active funeral case first so the current balance and payment limit can be shown.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-field">
                    <label class="pf-label" for="funeral_case_id">Funeral Case <span class="pf-required">*</span></label>
                    @php
                        $preselectCase  = $preselectCase ?? null;
                        $prefillCaseId  = old('funeral_case_id', $preselectCase->id ?? null);
                        $includePreselect = $preselectCase && !$openCases->contains('id', $preselectCase->id);
                    @endphp
                    <div class="pf-control-wrap">
                        <select name="funeral_case_id" id="funeral_case_id" class="pf-input pf-select" required>
                            <option value="">Select case number or client name</option>
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
                    <p class="pf-help soft">Only open cases with remaining payable balance are available here.</p>
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
                <div class="pf-section-icon"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <h3 class="pf-section-title">Payment Details</h3>
                    <div class="pf-section-sub">Enter how much was received, when it was received, and the basic tracking reference.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-grid two">
                    <div class="pf-field">
                        <label class="pf-label" for="payment_method">Payment Method <span class="pf-required">*</span></label>
                        <div class="pf-control-wrap">
                            <select name="payment_method" id="payment_method" class="pf-input pf-select" required>
                                <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            </select>
                            <span class="pf-control-icon"><i class="bi bi-chevron-down"></i></span>
                        </div>
                    </div>

                    <div class="pf-field">
                        <label class="pf-label" for="payment_record_no_display">Payment Record No.</label>
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
                                placeholder="Enter payment amount"
                                required
                            >
                        </div>
                        <p id="pf_amount_hint" class="pf-help soft">Select a case to see the remaining balance.</p>
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
                    </div>
                </div>
            </div>
        </section>

        <section class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-icon"><i class="bi bi-receipt"></i></div>
                <div>
                    <h3 class="pf-section-title">Accounting Information</h3>
                    <div class="pf-section-sub">Use the official accounting or OR reference and the personnel who received the payment.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-grid two">
                    <div class="pf-field">
                        <label class="pf-label" for="accounting_reference_no">Accounting / OR Reference No. <span class="pf-required">*</span></label>
                        <input type="text" name="accounting_reference_no" id="accounting_reference_no" value="{{ old('accounting_reference_no') }}" class="pf-input" maxlength="100" placeholder="e.g., OR-000123 or accounting reference" required>
                        <p class="pf-help soft">Reference from accounting, official receipt, bank, or e-wallet record.</p>
                    </div>
                    <div class="pf-field">
                        <label class="pf-label" for="received_by">Received By <span class="pf-required">*</span></label>
                        <input type="text" name="received_by" id="received_by" value="{{ old('received_by') }}" class="pf-input" maxlength="120" placeholder="Name of accounting personnel" required>
                    </div>
                </div>
            </div>
        </section>

        <section id="pf_bank_fields" class="pf-section hidden">
            <div class="pf-section-head">
                <div class="pf-section-icon"><i class="bi bi-bank"></i></div>
                <div>
                    <h3 class="pf-section-title">Bank Transfer Details</h3>
                    <div class="pf-section-sub">These fields are required only when the payment method is Bank Transfer.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-bank-box">
                    <div class="pf-grid two">
                        <div class="pf-field">
                            <label class="pf-label" for="bank_or_channel">Bank / Payment Channel <span class="pf-required">*</span></label>
                            <div class="pf-control-wrap">
                                <select name="bank_or_channel" id="bank_or_channel" class="pf-input pf-select">
                                    <option value="">Select bank or payment channel</option>
                                    @foreach(['BDO', 'BPI', 'Metrobank', 'Landbank', 'Security Bank', 'UnionBank', 'RCBC', 'PNB', 'China Bank', 'EastWest Bank', 'AUB', 'GCash', 'Maya', 'Other'] as $channel)
                                        <option value="{{ $channel }}" {{ old('bank_or_channel') === $channel ? 'selected' : '' }}>{{ $channel }}</option>
                                    @endforeach
                                </select>
                                <span class="pf-control-icon"><i class="bi bi-chevron-down"></i></span>
                            </div>
                        </div>

                        <div class="pf-field">
                            <label class="pf-label" for="transaction_reference_no">Transaction Reference No. <span class="pf-required">*</span></label>
                            <input type="text" name="transaction_reference_no" id="transaction_reference_no" value="{{ old('transaction_reference_no') }}" class="pf-input" maxlength="100" placeholder="Reference number from bank or app">
                        </div>
                    </div>

                    <div id="pf_other_channel_wrap" class="hidden" style="margin-top:1rem;">
                        <label class="pf-label" for="other_bank_or_channel">Other Bank / Channel Name <span class="pf-required">*</span></label>
                        <input type="text" name="other_bank_or_channel" id="other_bank_or_channel" value="{{ old('other_bank_or_channel') }}" class="pf-input" maxlength="100" placeholder="Enter bank, app, or payment channel name">
                    </div>

                    <div class="pf-grid two" style="margin-top:1rem;">
                        <div class="pf-field">
                            <label class="pf-label" for="sender_name">Sender / Account Name</label>
                            <input type="text" name="sender_name" id="sender_name" value="{{ old('sender_name') }}" class="pf-input" maxlength="120" placeholder="Name shown on transfer record">
                        </div>
                        <div class="pf-field">
                            <label class="pf-label" for="transfer_datetime">Transfer Date &amp; Time</label>
                            <input type="datetime-local" name="transfer_datetime" id="transfer_datetime" value="{{ old('transfer_datetime') }}" class="pf-input">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-icon"><i class="bi bi-chat-left-text"></i></div>
                <div>
                    <h3 class="pf-section-title">Notes &amp; Preview</h3>
                    <div class="pf-section-sub">Add optional remarks and review the expected payment status after saving.</div>
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
            <div>Payments are recorded for monitoring only. Official receipt issuance remains under accounting.</div>
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
    const caseSelect    = document.getElementById('funeral_case_id');
    const amountInput   = document.getElementById('amount_paid');
    const paymentMethod = document.getElementById('payment_method');
    const bankFields    = document.getElementById('pf_bank_fields');
    const bankOrChannel = document.getElementById('bank_or_channel');
    const otherWrap     = document.getElementById('pf_other_channel_wrap');
    const otherChannel  = document.getElementById('other_bank_or_channel');
    const transactionRef= document.getElementById('transaction_reference_no');
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

        if (amountHint) {
            amountHint.textContent = balance > 0
                ? `Maximum receivable: ₱${fmt(balance)} remaining balance.`
                : 'This case is fully paid.';
        }
    }

    function updatePaymentMethodFields() {
        const isBank = paymentMethod?.value === 'bank_transfer';
        if (bankFields) bankFields.classList.toggle('hidden', !isBank);
        if (bankOrChannel) bankOrChannel.required = isBank;
        if (transactionRef) transactionRef.required = isBank;

        const isOther = isBank && bankOrChannel?.value === 'Other';
        if (otherWrap) otherWrap.classList.toggle('hidden', !isOther);
        if (otherChannel) otherChannel.required = isOther;
    }

    if (caseSelect) caseSelect.addEventListener('change', updateSummary);
    if (amountInput) amountInput.addEventListener('input', updateSummary);
    if (paymentMethod) paymentMethod.addEventListener('change', updatePaymentMethodFields);
    if (bankOrChannel) bankOrChannel.addEventListener('change', updatePaymentMethodFields);

    updateSummary();
    updatePaymentMethodFields();
})();
</script>
