{{-- NOTE: Keep input names/ids; JS summary and PaymentController depend on them. --}}
<style>
    .pf-shell { display: flex; flex-direction: column; gap: .95rem; }
    .pf-guide {
        display: none;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
        border: 1px solid #AEBBA8;
        border-radius: 16px;
        background: #C7D5BE;
        padding: .85rem;
    }
    .pf-guide-step {
        display: flex;
        align-items: flex-start;
        gap: .7rem;
        border-radius: 14px;
        background: #DCE6D6;
        border: 1px solid #AEBBA8;
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
        font-weight: 750;
        line-height: 1;
    }
    .pf-guide-title {
        color: #232821;
        font-size: .82rem;
        font-weight: 700;
        line-height: 1.25;
    }
    .pf-guide-copy {
        margin-top: .18rem;
        color: #3F4C3E;
        font-size: .72rem;
        font-weight: 550;
        line-height: 1.35;
    }
    .pf-section {
        border: 1px solid #AEBBA8;
        border-radius: 18px;
        background: #D3DEC9;
        box-shadow: none;
        overflow: hidden;
    }
    .pf-section--case {
        position: relative;
        z-index: 20;
        overflow: visible;
    }
    .pf-section--case .pf-section-head {
        border-radius: 17px 17px 0 0;
    }
    .pf-section--case .pf-control-wrap {
        z-index: 21;
    }
    .pf-section--case .filter-select-menu {
        z-index: 1001;
        max-height: min(20rem, 45vh);
    }
    .pf-accordion { display:flex; flex-direction:column; gap:.8rem; }
    .pf-accordion-step { border:1px solid #AEBBA8; border-radius:18px; background:#D3DEC9; overflow:visible; }
    .pf-accordion-trigger { width:100%; min-height:64px; display:flex; align-items:center; gap:12px; padding:12px 16px; border:0; border-radius:17px; background:#C7D5BE; color:#232821; text-align:left; cursor:pointer; }
    .pf-accordion-step.is-active .pf-accordion-trigger { border-radius:17px 17px 0 0; border-bottom:1px solid #AEBBA8; }
    .pf-accordion-number { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; border-radius:50%; background:#3E4A3D; color:#fff; font-size:12px; font-weight:800; }
    .pf-accordion-step.is-complete .pf-accordion-number { background:#27683d; }
    .pf-accordion-heading { flex:1; min-width:0; }
    .pf-accordion-title { display:flex; align-items:center; gap:8px; font-size:15px; font-weight:800; }
    .pf-accordion-copy { margin-top:3px; color:#465446; font-size:12px; line-height:1.35; }
    .pf-accordion-summary { display:none; margin-top:4px; color:#31553a; font-size:12px; font-weight:750; overflow-wrap:anywhere; }
    .pf-accordion-step.is-complete:not(.is-active) .pf-accordion-summary { display:block; }
    .pf-accordion-step.is-complete:not(.is-active) .pf-accordion-copy { display:none; }
    .pf-accordion-chevron { transition:transform .18s ease; }
    .pf-accordion-step.is-active .pf-accordion-chevron { transform:rotate(180deg); }
    .pf-accordion-content { display:none; padding:14px; }
    .pf-accordion-step.is-active .pf-accordion-content { display:flex; flex-direction:column; gap:14px; }
    .pf-accordion-content > .pf-section { border-radius:14px; }
    .pf-accordion-content > .pf-section > .pf-section-head { display:none; }
    .pf-step-action { display:flex; justify-content:flex-end; padding-top:2px; }
    .pf-quick-amounts { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
    .pf-quick-amount { min-height:36px; padding:0 12px; border:1px solid #9dad97; border-radius:10px; background:#eef3e9; color:#314031; font-size:12px; font-weight:750; cursor:pointer; }
    .pf-quick-amount:hover,.pf-quick-amount:focus-visible { background:#d9e5d2; border-color:#647a61; outline:none; }
    .pf-payment-progress { margin-top:12px; }
    .pf-progress-meta { display:flex; justify-content:space-between; gap:12px; margin-bottom:6px; color:#4b594b; font-size:11px; font-weight:700; }
    .pf-progress-track { height:8px; overflow:hidden; border-radius:999px; background:#bac8b4; }
    .pf-progress-bar { width:0; height:100%; border-radius:inherit; background:#39704a; transition:width .2s ease; }
    .pf-method-types { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:8px; margin-top:10px; }
    #pf_cashless_type_field > .pf-control-wrap { display:none; }
    .pf-method-type { min-height:58px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:5px; border:1px solid #AEBBA8; border-radius:11px; background:#E1E7D9; color:#334133; font-size:11px; font-weight:750; cursor:pointer; }
    .pf-method-type i { font-size:18px; }
    .pf-method-type.is-active { background:#3E4A3D; border-color:#2D372D; color:#fff; }
    .pf-review-verification { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
    .pf-review-card { padding:14px; border:1px solid #AEBBA8; border-radius:13px; background:#E1E7D9; }
    .pf-review-card h4 { display:flex; align-items:center; gap:7px; margin:0 0 10px; color:#314031; font-size:12px; text-transform:uppercase; letter-spacing:.06em; }
    .pf-review-row { display:flex; justify-content:space-between; gap:16px; padding:5px 0; color:#566356; font-size:12px; }
    .pf-review-row strong { color:#232821; text-align:right; overflow-wrap:anywhere; }
    .pf-section-head {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        padding: .88rem 1rem;
        border-bottom: 1px solid #AEBBA8;
        background: #C7D5BE;
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
        box-shadow: none;
        flex: 0 0 auto;
    }
    .pf-section-title { margin: 0; color: #232821; font-size: .98rem; font-weight: 650; line-height: 1.2; }
    .pf-section-sub { margin-top: .16rem; color: #3F4C3E; font-size: .78rem; font-weight: 520; line-height: 1.35; }
    .pf-section-body { padding: 1rem; }
    .pf-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
    .pf-grid.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pf-grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .pf-field { min-width: 0; }
    .pf-label {
        display: flex;
        align-items: center;
        gap: .3rem;
        font-size: 0.68rem;
        font-weight: 600;
        color: #3F4C3E;
        text-transform: uppercase;
        letter-spacing: 0.085em;
        margin-bottom: 0.45rem;
    }
    .pf-required { color: #f43f5e; font-size: .78rem; line-height: 1; }
    .pf-input {
        width: 100%;
        min-height: 2.9rem;
        border-radius: 12px;
        border: 1.5px solid #AEBBA8;
        background: #E1E7D9;
        padding: 0.78rem 1rem;
        font-size: 0.9rem;
        font-weight: 500;
        color: #232821;
        box-shadow: none;
        transition: border-color .18s, background .18s, color .18s;
        font-family: inherit;
        box-sizing: border-box;
    }
    .pf-input:focus {
        border-color: #3E4A3D;
        box-shadow: none;
        outline: none;
        background: #FBFCF7;
    }
    .pf-input:hover { background: #C7D5BE; border-color: #8EA083; }
    .pf-input[readonly] { background: #DCE6D6; color: #3F4C3E; }
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
    .pf-help { margin-top: .45rem; color: #3F4C3E; font-size: .72rem; font-weight: 550; line-height: 1.35; }
    .pf-help.soft { color: #4D594B; }
    .pf-input.is-invalid { border-color:#b42318 !important; background:#fff8f6 !important; box-shadow:0 0 0 3px rgba(180,35,24,.1) !important; }
    .pf-inline-error { display:block; margin-top:.4rem; color:#9f1c14; font-size:.72rem; font-weight:700; line-height:1.35; }
    .pf-payment-layout {
        display: grid;
        grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr);
        gap: 1rem;
        align-items: start;
    }
    .pf-method-layout {
        display: grid;
        grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr);
        gap: 1rem;
        align-items: start;
    }
    .pf-method-options {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }
    .pf-method-option {
        min-height: 4.6rem;
        border: 1.5px solid #AEBBA8;
        border-radius: 14px;
        background: #E1E7D9;
        color: #232821;
        padding: .85rem;
        text-align: left;
        cursor: pointer;
        box-shadow: none;
        transition: background .18s, border-color .18s, color .18s;
    }
    .pf-method-option:hover { background: #C7D5BE; border-color: #8EA083; }
    .pf-method-option.is-active {
        background: #3E4A3D;
        border-color: #2D372D;
        color: #ffffff;
    }
    .pf-method-option-title {
        display: flex;
        align-items: center;
        gap: .45rem;
        font-size: .92rem;
        font-weight: 650;
        line-height: 1.2;
    }
    .pf-method-option-copy {
        margin-top: .3rem;
        font-size: .72rem;
        font-weight: 520;
        line-height: 1.35;
        color: #3F4C3E;
    }
    .pf-method-option.is-active .pf-method-option-copy { color: rgba(255,255,255,.75); }
    .pf-payment-layout .pf-record-number { grid-column: 1 / -1; }
    .pf-record-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        border: 1px solid #AEBBA8;
        border-radius: 13px;
        background: #DCE6D6;
        padding: .75rem .9rem;
    }
    .pf-record-card span:first-child {
        color: #3F4C3E;
        font-size: .76rem;
        font-weight: 560;
    }
    .pf-record-card span:last-child {
        color: #232821;
        font-size: .82rem;
        font-weight: 650;
        text-align: right;
    }
    .pf-amount-wrap {
        display: flex;
        align-items: stretch;
        min-height: 2.95rem;
        border-radius: 12px;
        border: 1.5px solid #AEBBA8;
        background: #E1E7D9;
        box-shadow: none;
        transition: border-color .18s, background .18s;
        overflow: hidden;
    }
    .pf-amount-wrap:focus-within {
        border-color: #3E4A3D;
        box-shadow: none;
        background: #FBFCF7;
    }
    .pf-amount-prefix {
        padding: 0 14px;
        font-size: .95rem;
        font-weight: 750;
        color: #3F4C3E;
        background: #C7D5BE;
        border-right: 1.5px solid #AEBBA8;
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
        font-weight: 750;
        color: #232821;
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
        border: 1px solid #AEBBA8;
        background: #DCE6D6;
        padding: .85rem;
        margin-top: .9rem;
    }
    .pf-snapshot-item { padding: .35rem .5rem; }
    .pf-snapshot-label { color: #3F4C3E; font-size: .7rem; font-weight: 650; margin-bottom: .2rem; }
    .pf-snapshot-value { color: #232821; font-size: .95rem; font-weight: 750; font-variant-numeric: tabular-nums; }
    .pf-snapshot-value.good { color: #047857; }
    .pf-snapshot-value.warn { color: #be123c; }

    .pf-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
    .pf-summary-card {
        border-radius: 14px;
        border: 1.5px solid #AEBBA8;
        background: #DCE6D6;
        padding: .85rem .95rem;
        min-width: 0;
    }
    .pf-summary-card.accent-green { border-color: #8EA083; background: #DCE6D6; }
    .pf-summary-card.accent-navy { border-color: #3E4A3D; background: #3E4A3D; color: #fff; }
    .pf-summary-label {
        font-size: 0.6rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #3F4C3E;
        margin-bottom: .28rem;
    }
    .pf-summary-card.accent-green .pf-summary-label { color: #059669; }
    .pf-summary-card.accent-navy .pf-summary-label { color: rgba(255,255,255,.62); }
    .pf-summary-value {
        font-size: .98rem;
        font-weight: 750;
        color: #232821;
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
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .pf-status-pill.paid { background: #d1fae5; color: #065f46; }
    .pf-status-pill.partial { background: #fef3c7; color: #92400e; }
    .pf-status-pill.unpaid { background: #fee2e2; color: #991b1b; }
    .pf-bank-box {
        border-radius: 16px;
        border: 1px solid #AEBBA8;
        background: #DCE6D6;
        padding: 1rem;
    }
    .pf-note {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        border-radius: 14px;
        border: 1px solid #AEBBA8;
        background: #DCE6D6;
        padding: .85rem 1rem;
        color: #3F4C3E;
        font-size: .78rem;
        font-weight: 550;
        line-height: 1.45;
    }
    .pf-review-layout {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .pf-review-layout .pf-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pf-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .75rem;
        position: sticky;
        bottom: 0;
        z-index: 5;
        margin-top: .15rem;
        padding: .85rem 0 0;
        border-top: 1px solid #AEBBA8;
        background: #D3DEC9;
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
        font-weight: 700;
        cursor: pointer;
        box-shadow: none;
        transition: background .18s, color .18s, border-color .18s;
    }
    .pf-btn.secondary { border: 1px solid #AEBBA8; background: #E1E7D9; color: #3F4C3E; }
    .pf-btn.secondary:hover { background: #C7D5BE; border-color: #8EA083; color: #232821; }
    .pf-btn.primary {
        border: 1px solid #2D372D;
        background: #3E4A3D;
        color: #fff;
        box-shadow: none;
    }
    .pf-btn.primary:hover { background: #2F3A2E; box-shadow: none; }
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
    html[data-theme='dark'] .pf-amount-wrap:focus-within { border-color: #4a82c0 !important; box-shadow: none !important; }
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
        .pf-method-layout,
        .pf-payment-layout,
        .pf-review-layout,
        .pf-summary-grid { grid-template-columns: 1fr 1fr; }
        .pf-method-types { grid-template-columns:repeat(3,minmax(0,1fr)); }
    }
    @media (max-width: 640px) {
        .pf-guide { grid-template-columns: 1fr; padding: .95rem; }
        .pf-section-head { padding: .95rem; }
        .pf-section-body { padding: .95rem; }
        .pf-method-options,
        .pf-grid.two,
        .pf-grid.three,
        .pf-method-layout,
        .pf-payment-layout,
        .pf-review-layout,
        .pf-summary-grid,
        .pf-snapshot { grid-template-columns: 1fr; }
        .pf-review-verification,.pf-method-types { grid-template-columns:1fr 1fr; }
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

        <section class="pf-section pf-section--case">
            <div class="pf-section-head">
                <div class="pf-section-index">1</div>
                <div>
                    <h3 class="pf-section-title">Case</h3>
                    <div class="pf-section-sub">Choose the case first so the form can show its balance and payment limit.</div>
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
                            <option value="">Choose a case with remaining balance</option>
                            @foreach($openCases as $case)
                                <option
                                    value="{{ $case->id }}"
                                    data-total="{{ $case->total_amount }}"
                                    data-paid="{{ $case->total_paid }}"
                                    data-balance="{{ $case->balance_amount }}"
                                    data-case-code="{{ $case->case_code }}"
                                    data-client="{{ $case->client?->full_name ?? 'Not provided' }}"
                                    data-deceased="{{ $case->deceased?->full_name ?? 'Not provided' }}"
                                    data-package="{{ $case->package_name_snapshot ?: $case->custom_package_name ?: $case->package?->name ?: $case->service_package ?: 'Not specified' }}"
                                    {{ $prefillCaseId == $case->id ? 'selected' : '' }}
                                >{{ $case->case_code }} · {{ $case->client?->full_name ?? '—' }} · Balance: ₱{{ number_format((float)$case->balance_amount, 2) }}</option>
                            @endforeach
                            @if($includePreselect)
                                <option
                                    value="{{ $preselectCase->id }}"
                                    data-total="{{ $preselectCase->total_amount }}"
                                    data-paid="{{ $preselectCase->total_paid }}"
                                    data-balance="{{ $preselectCase->balance_amount }}"
                                    data-case-code="{{ $preselectCase->case_code }}"
                                    data-client="{{ $preselectCase->client?->full_name ?? 'Not provided' }}"
                                    data-deceased="{{ $preselectCase->deceased?->full_name ?? 'Not provided' }}"
                                    data-package="{{ $preselectCase->package_name_snapshot ?: $preselectCase->custom_package_name ?: $preselectCase->package?->name ?: $preselectCase->service_package ?: 'Not specified' }}"
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
                    <div class="pf-payment-progress" style="grid-column:1/-1;">
                        <div class="pf-progress-meta"><span>Payment progress</span><span id="pf_progress_label">0% paid</span></div>
                        <div class="pf-progress-track" role="progressbar" aria-label="Case payment progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div id="pf_progress_bar" class="pf-progress-bar"></div></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-index">2</div>
                <div>
                    <h3 class="pf-section-title">Payment Method</h3>
                    <div class="pf-section-sub">Choose Cash or Cashless first. The next fields will adjust based on this selection.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-method-layout">
                    <div class="pf-field">
                        <label class="pf-label" for="payment_method">Payment Method <span class="pf-required">*</span></label>
                        <select name="payment_method" id="payment_method" class="hidden" required>
                            <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="cashless" {{ in_array(old('payment_method'), ['cashless', 'bank_transfer'], true) ? 'selected' : '' }}>Cashless</option>
                        </select>
                        <div class="pf-method-options" role="group" aria-label="Payment method">
                            <button type="button" class="pf-method-option" data-payment-method-option="cash">
                                <span class="pf-method-option-title">
                                    <i class="bi bi-cash"></i>
                                    Cash
                                </span>
                                <span class="pf-method-option-copy">Use this for physical cash payments.</span>
                            </button>
                            <button type="button" class="pf-method-option" data-payment-method-option="cashless">
                                <span class="pf-method-option-title">
                                    <i class="bi bi-credit-card-2-front"></i>
                                    Cashless
                                </span>
                                <span class="pf-method-option-copy">Use this for bank, wallet, card, or other digital payments.</span>
                            </button>
                        </div>
                        <p class="pf-help soft">Choose one method first so the form shows only the needed fields.</p>
                    </div>

                    <div id="pf_cashless_type_field" class="pf-field hidden">
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
                        <div class="pf-method-types" data-cashless-type-options aria-label="Cashless type options"></div>
                        <p class="pf-help soft">Select the channel so only the needed transaction fields appear.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="pf_cashless_fields" class="pf-section hidden">
            <div class="pf-section-head">
                <div class="pf-section-index">3</div>
                <div>
                    <h3 class="pf-section-title">Transaction Details</h3>
                    <div class="pf-section-sub">Complete only the fields needed for the selected cashless type.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-bank-box">
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
                                <input type="text" name="account_name" id="account_name" value="{{ old('account_name', old('sender_name')) }}" class="pf-input" maxlength="100" placeholder="Name shown on transfer record" autocapitalize="words">
                                @error('account_name')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div id="pf_other_bank_wrap" class="hidden" style="margin-top:1rem;">
                            <label class="pf-label" for="other_bank_name">Other Bank Name <span class="pf-required">*</span></label>
                            <input type="text" name="other_bank_name" id="other_bank_name" value="{{ old('other_bank_name', old('other_bank_or_channel')) }}" class="pf-input" maxlength="80" placeholder="Enter bank name">
                            @error('other_bank_name')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div data-cashless-panel="gcash" class="cashless-panel hidden">
                        <input type="hidden" name="wallet_provider" id="wallet_provider" value="{{ old('wallet_provider') }}">
                        <div class="pf-grid two" style="margin-top:1rem;">
                            <div class="pf-field">
                                <label class="pf-label" for="gcash_account_name">GCash Account Name</label>
                                <input type="text" id="gcash_account_name" data-account-name-field value="{{ old('account_name') }}" class="pf-input" maxlength="100" placeholder="Optional" autocapitalize="words">
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="gcash_mobile_number">GCash Mobile Number</label>
                                <input type="text" id="gcash_mobile_number" data-mobile-number-field value="{{ old('mobile_number') }}" class="pf-input" maxlength="13" inputmode="tel" placeholder="e.g., 09171234567">
                                @error('mobile_number')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div data-cashless-panel="maya" class="cashless-panel hidden">
                        <div class="pf-grid two" style="margin-top:1rem;">
                            <div class="pf-field">
                                <label class="pf-label" for="maya_account_name">Maya Account Name</label>
                                <input type="text" id="maya_account_name" data-account-name-field value="{{ old('account_name') }}" class="pf-input" maxlength="100" placeholder="Optional" autocapitalize="words">
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="maya_mobile_number">Maya Mobile Number</label>
                                <input type="text" id="maya_mobile_number" data-mobile-number-field value="{{ old('mobile_number') }}" class="pf-input" maxlength="13" inputmode="tel" placeholder="e.g., 09171234567">
                                @error('mobile_number')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
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
                                @error('approval_code')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
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
                                <input type="text" id="payment_channel_other" data-payment-channel-field value="{{ old('payment_channel') }}" class="pf-input" maxlength="80" placeholder="e.g., PalawanPay">
                                @error('payment_channel')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
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
                            <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number', old('transaction_reference_no')) }}" class="pf-input" minlength="4" maxlength="60" placeholder="Reference number from bank, wallet, or channel">
                            @error('reference_number')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <input type="hidden" name="mobile_number" id="mobile_number" value="{{ old('mobile_number') }}">
                    <input type="hidden" name="payment_channel" id="payment_channel" value="{{ old('payment_channel') }}">
                    <input type="hidden" name="transaction_reference_no" id="transaction_reference_no" value="{{ old('transaction_reference_no') }}">
                </div>
            </div>
        </section>

        <section id="pf_received_payment_fields" class="pf-section hidden">
            <div class="pf-section-head">
                <div class="pf-section-index">4</div>
                <div>
                    <h3 class="pf-section-title">Payment Received</h3>
                    <div class="pf-section-sub">Enter the amount, received date, and optional receipt details.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-payment-layout">
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
                        <p id="pf_amount_formatted" class="pf-help" style="font-weight:700;color:#232821;">₱0.00</p>
                    </div>

                    <div class="pf-field">
                        <label class="pf-label" for="paid_at_input">Date &amp; Time Received <span class="pf-required">*</span></label>
                        <input
                            type="datetime-local"
                            name="paid_at"
                            id="paid_at_input"
                            value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}"
                            max="{{ now()->format('Y-m-d\\TH:i') }}"
                            class="pf-input"
                            required
                        >
                        <p class="pf-help soft">Use the actual date and time the payment was received.</p>
                    </div>

                    <div class="pf-field">
                        <label class="pf-label" for="receipt_or_no">Receipt / OR No. <span class="pf-help soft">(optional)</span></label>
                        <input type="text" name="receipt_or_no" id="receipt_or_no" value="{{ old('receipt_or_no', old('accounting_reference_no')) }}" class="pf-input" minlength="3" maxlength="50" placeholder="Receipt or OR number from Accounting">
                        <p class="pf-help soft">Enter the receipt or OR number issued by Accounting. Leave blank if not yet issued.</p>
                        @error('receipt_or_no')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                    </div>

                    <div class="pf-field pf-record-number">
                        <input type="hidden" id="payment_record_no_display" value="Generated after saving" readonly>
                        <div class="pf-record-card" aria-label="Internal payment record number">
                            <span>Internal payment record number</span>
                            <span>Generated after saving</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-index">5</div>
                <div>
                    <h3 class="pf-section-title">Review</h3>
                    <div class="pf-section-sub">Verify the payment outcome before saving.</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="pf-review-layout">
                <div class="pf-review-verification">
                    <div class="pf-review-card">
                        <h4><i class="bi bi-folder2-open" aria-hidden="true"></i> Case Details</h4>
                        <div class="pf-review-row"><span>Case Code</span><strong id="pf_review_case">—</strong></div>
                        <div class="pf-review-row"><span>Deceased</span><strong id="pf_review_deceased">—</strong></div>
                        <div class="pf-review-row"><span>Client</span><strong id="pf_review_client">—</strong></div>
                        <div class="pf-review-row"><span>Package Availed</span><strong id="pf_review_package">—</strong></div>
                    </div>
                    <div class="pf-review-card">
                        <h4><i class="bi bi-credit-card-2-front" aria-hidden="true"></i> Payment Details</h4>
                        <div class="pf-review-row"><span>Payment Method</span><strong id="pf_review_method">—</strong></div>
                        <div class="pf-review-row"><span>Reference</span><strong id="pf_review_reference">Not applicable</strong></div>
                        <div class="pf-review-row"><span>Receipt / OR No.</span><strong id="pf_review_or">Not provided</strong></div>
                        <div class="pf-review-row"><span>Date &amp; Time Received</span><strong id="pf_review_date">—</strong></div>
                        <div class="pf-review-row"><span>Recorded By</span><strong>{{ auth()->user()?->name ?? 'Current staff user' }}</strong></div>
                    </div>
                </div>
                <div>
                    <div class="pf-label" style="margin-bottom:.65rem;">After This Payment</div>
                    <div class="pf-summary-grid">
                        <div class="pf-summary-card">
                            <div class="pf-summary-label">Previous Remaining Balance</div>
                            <div class="pf-summary-value">₱ <span id="previous_balance_display">—</span></div>
                        </div>
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

                <div class="pf-field">
                    <label class="pf-label" for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="pf-input" style="min-height:90px; resize:vertical;" maxlength="1000" placeholder="Optional notes about this payment">{{ old('remarks') }}</textarea>
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
            <button type="button" id="pf_back_to_edit" class="pf-btn secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to Edit
            </button>
            <button type="button" id="closePaymentFormBottom" class="pf-btn secondary">
                Cancel
            </button>
            <button type="submit" id="pf_submit_btn" class="pf-btn primary" {{ $openCases->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-check2-circle text-base"></i>
                Confirm Payment
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const form          = document.getElementById('paymentForm');
    const caseSelect    = document.getElementById('funeral_case_id');
    const amountInput   = document.getElementById('amount_paid');
    const paidAtInput   = document.getElementById('paid_at_input');
    const receiptInput  = document.getElementById('receipt_or_no');
    const paymentMethod = document.getElementById('payment_method');
    const paymentMethodOptions = document.querySelectorAll('[data-payment-method-option]');
    const cashlessFields = document.getElementById('pf_cashless_fields');
    const cashlessTypeField = document.getElementById('pf_cashless_type_field');
    const cashlessType   = document.getElementById('cashless_type');
    const receivedPaymentFields = document.getElementById('pf_received_payment_fields');
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
    const previousBalanceDisplay = document.getElementById('previous_balance_display');
    const newTotalPaidDisplay= document.getElementById('new_total_paid_display');
    const newBalanceDisplay  = document.getElementById('new_balance_display');
    const newStatusDisplay   = document.getElementById('new_status_display');

    const totalDueDisplay  = document.getElementById('total_due_display');
    const totalPaidDisplay = document.getElementById('total_paid_display');
    const balanceDisplay   = document.getElementById('balance_display');

    function toNum(v) { const n = parseFloat(v); return Number.isFinite(n) ? n : 0; }
    function fmt(n)   { return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    const accordionState = { current: 1, completed: new Set() };
    let accordionSteps = [];

    function buildAccordion() {
        if (!form || form.querySelector('.pf-accordion')) return;
        const sections = Array.from(form.children).filter((node) => node.classList?.contains('pf-section'));
        const caseSection = form.querySelector('.pf-section--case');
        const methodSection = sections.find((section) => section.querySelector('.pf-section-title')?.textContent.trim() === 'Payment Method');
        const reviewSection = sections.find((section) => section.querySelector('.pf-section-title')?.textContent.trim() === 'Review');
        const note = form.querySelector('.pf-note');
        const actions = form.querySelector('.pf-actions');
        if (!caseSection || !methodSection || !reviewSection || !cashlessFields || !receivedPaymentFields || !note || !actions) return;

        const amountField = amountInput?.closest('.pf-field');
        const caseBody = caseSection.querySelector('.pf-section-body');
        if (amountField && caseBody) {
            const amountPanel = document.createElement('div');
            amountPanel.className = 'pf-review-card';
            amountPanel.style.marginTop = '14px';
            amountField.parentElement?.removeChild(amountField);
            amountPanel.appendChild(amountField);
            const quick = document.createElement('div');
            quick.className = 'pf-quick-amounts';
            quick.setAttribute('aria-label', 'Quick payment amount');
            [['Full Balance', 1], ['50%', .5], ['25%', .25]].forEach(([label, ratio]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'pf-quick-amount';
                button.textContent = label;
                button.addEventListener('click', () => {
                    const balance = selectedCaseBalance();
                    if (!balance) return caseSelect?.reportValidity();
                    amountInput.value = (balance * ratio).toFixed(2);
                    updateSummary();
                });
                quick.appendChild(button);
            });
            amountPanel.appendChild(quick);
            caseBody.appendChild(amountPanel);
        }

        const root = document.createElement('div');
        root.className = 'pf-accordion';
        form.insertBefore(root, caseSection);
        const definitions = [
            { title:'Case & Payment Amount', copy:'Select the case and confirm the amount received.', icon:'bi-folder2-open', nodes:[caseSection] },
            { title:'Payment Method & Details', copy:'Choose how the payment was received and enter its transaction details.', icon:'bi-credit-card-2-front', nodes:[methodSection, cashlessFields, receivedPaymentFields] },
            { title:'Review Payment', copy:'Verify the case, payment details, and updated balance before saving.', icon:'bi-clipboard2-check', nodes:[reviewSection, note, actions] },
        ];
        accordionSteps = definitions.map((definition, index) => {
            const stepNumber = index + 1;
            const step = document.createElement('section');
            step.className = 'pf-accordion-step';
            step.dataset.step = String(stepNumber);
            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'pf-accordion-trigger';
            trigger.setAttribute('aria-expanded', stepNumber === 1 ? 'true' : 'false');
            trigger.innerHTML = `<span class="pf-accordion-number">${stepNumber}</span><span class="pf-accordion-heading"><span class="pf-accordion-title"><i class="bi ${definition.icon}" aria-hidden="true"></i>${definition.title}</span><span class="pf-accordion-copy">${definition.copy}</span><span class="pf-accordion-summary" data-step-summary></span></span><i class="bi bi-chevron-down pf-accordion-chevron" aria-hidden="true"></i>`;
            const content = document.createElement('div');
            content.className = 'pf-accordion-content';
            content.id = `pfAccordionPanel${stepNumber}`;
            trigger.setAttribute('aria-controls', content.id);
            definition.nodes.forEach((node) => content.appendChild(node));
            if (stepNumber < 3) {
                const action = document.createElement('div');
                action.className = 'pf-step-action';
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'pf-btn primary';
                button.innerHTML = `${stepNumber === 1 ? 'Continue to Payment Method' : 'Continue to Review'} <i class="bi bi-arrow-right" aria-hidden="true"></i>`;
                button.addEventListener('click', () => continueStep(stepNumber));
                action.appendChild(button);
                content.appendChild(action);
            }
            trigger.addEventListener('click', () => {
                if (stepNumber <= accordionState.current || accordionState.completed.has(stepNumber)) goToStep(stepNumber);
            });
            step.append(trigger, content);
            root.appendChild(step);
            return step;
        });
        goToStep(1);
    }

    function goToStep(stepNumber) {
        accordionState.current = stepNumber;
        accordionSteps.forEach((step, index) => {
            const active = index + 1 === stepNumber;
            step.classList.toggle('is-active', active);
            step.classList.toggle('is-complete', accordionState.completed.has(index + 1));
            step.querySelector('.pf-accordion-trigger')?.setAttribute('aria-expanded', active ? 'true' : 'false');
            const number = step.querySelector('.pf-accordion-number');
            if (number) number.innerHTML = accordionState.completed.has(index + 1) ? '<i class="bi bi-check-lg" aria-hidden="true"></i>' : String(index + 1);
        });
        updateAccordionSummaries();
        requestAnimationFrame(() => accordionSteps[stepNumber - 1]?.querySelector('.pf-accordion-trigger')?.scrollIntoView({ behavior:'smooth', block:'nearest' }));
    }

    function continueStep(stepNumber) {
        if (stepNumber === 1) {
            if (!caseSelect?.value) return caseSelect?.reportValidity();
            if (!amountInput?.value || toNum(amountInput.value) <= 0 || toNum(amountInput.value) > selectedCaseBalance()) return amountInput?.reportValidity();
        }
        if (stepNumber === 2) {
            if (!validateCashlessDetails()) return;
            if (!validatePaymentDateValue(paidAtInput)) return;
            const fields = [paidAtInput, receiptInput, ...Array.from(receivedPaymentFields.querySelectorAll('[required]'))];
            const invalid = fields.find((field) => field && !field.disabled && !field.checkValidity());
            if (invalid) return invalid.reportValidity();
        }
        accordionState.completed.add(stepNumber);
        goToStep(stepNumber + 1);
    }

    function updateAccordionSummaries() {
        if (!accordionSteps.length) return;
        const option = caseSelect?.options[caseSelect.selectedIndex];
        const amount = toNum(amountInput?.value);
        const caseSummary = option?.value ? `✓ ${option.dataset.caseCode} · ${option.dataset.client} · Paying PHP ${fmt(amount)} of PHP ${fmt(toNum(option.dataset.balance))} · Click to edit` : 'Select a case and payment amount';
        const method = paymentMethod?.value === 'cashless' ? (cashlessType?.selectedOptions[0]?.textContent || 'Cashless') : 'Cash';
        const ref = referenceNo?.value || approvalCode?.value;
        const methodSummary = `✓ ${method}${ref ? ` · Ref: ${ref}` : ''}${receiptInput?.value ? ` · OR: ${receiptInput.value}` : ''} · Click to edit`;
        const summaries = [caseSummary, methodSummary, 'Ready for final confirmation'];
        accordionSteps.forEach((step, index) => { const el = step.querySelector('[data-step-summary]'); if (el) el.textContent = summaries[index]; });
    }

    function buildCashlessTypeButtons() {
        const host = document.querySelector('[data-cashless-type-options]');
        if (!host || !cashlessType || host.children.length) return;
        const icons = { bank_transfer:'bi-bank', gcash:'bi-phone', maya:'bi-wallet2', card:'bi-credit-card', other:'bi-grid' };
        Array.from(cashlessType.options).filter((option) => option.value).forEach((option) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pf-method-type';
            button.dataset.value = option.value;
            button.setAttribute('aria-pressed', option.selected ? 'true' : 'false');
            button.innerHTML = `<i class="bi ${icons[option.value] || 'bi-credit-card'}" aria-hidden="true"></i><span>${option.textContent}</span>`;
            button.addEventListener('click', () => { cashlessType.value = option.value; cashlessType.dispatchEvent(new Event('change', { bubbles:true })); });
            host.appendChild(button);
        });
    }

    function updateReviewDetails() {
        const option = caseSelect?.options[caseSelect.selectedIndex];
        const put = (id, value) => { const element = document.getElementById(id); if (element) element.textContent = value || '—'; };
        put('pf_review_case', option?.dataset.caseCode);
        put('pf_review_deceased', option?.dataset.deceased);
        put('pf_review_client', option?.dataset.client);
        put('pf_review_package', option?.dataset.package);
        const methodText = paymentMethod?.value === 'cashless' ? (cashlessType?.selectedOptions[0]?.textContent || 'Cashless') : 'Cash';
        put('pf_review_method', methodText);
        put('pf_review_reference', referenceNo?.value || approvalCode?.value || 'Not applicable');
        put('pf_review_or', receiptInput?.value || 'Not provided');
        put('pf_review_date', paidAtInput?.value ? new Date(paidAtInput.value).toLocaleString([], { dateStyle:'medium', timeStyle:'short' }) : null);
        document.querySelectorAll('.pf-method-type').forEach((button) => {
            const active = button.dataset.value === cashlessType?.value;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        updateAccordionSummaries();
    }

    function statusPill(status) {
        const map = { PAID: 'paid', PARTIAL: 'partial', UNPAID: 'unpaid' };
        const cls = map[status] || 'unpaid';
        return `<span class="pf-status-pill ${cls}">${status}</span>`;
    }

    function selectedCaseBalance() {
        const opt = caseSelect.options[caseSelect.selectedIndex];
        return opt && opt.value ? toNum(opt.dataset.balance) : 0;
    }

    function fillAmountFromSelectedCase() {
        if (!amountInput) return;
        const balance = selectedCaseBalance();
        amountInput.value = balance > 0 ? balance.toFixed(2) : '';
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
            if (previousBalanceDisplay) previousBalanceDisplay.textContent = '—';
            newTotalPaidDisplay.textContent = '—';
            newBalanceDisplay.textContent  = '—';
            newStatusDisplay.innerHTML     = '—';
            if (amountHint) amountHint.textContent = 'Select a case to see the remaining balance.';
            const progressBar = document.getElementById('pf_progress_bar');
            const progressLabel = document.getElementById('pf_progress_label');
            if (progressBar) progressBar.style.width = '0%';
            if (progressLabel) progressLabel.textContent = '0% paid';
            updateReviewDetails();
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
        const progress = total > 0 ? Math.min((paid / total) * 100, 100) : 0;
        const progressBar = document.getElementById('pf_progress_bar');
        const progressLabel = document.getElementById('pf_progress_label');
        const progressTrack = progressBar?.parentElement;
        if (progressBar) progressBar.style.width = `${progress}%`;
        if (progressLabel) progressLabel.textContent = `${Math.round(progress)}% paid before this payment`;
        if (progressTrack) progressTrack.setAttribute('aria-valuenow', String(Math.round(progress)));

        newPaymentDisplay.textContent   = fmt(entered);
        if (previousBalanceDisplay) previousBalanceDisplay.textContent = fmt(balance);
        newTotalPaidDisplay.textContent = fmt(newTotalPaid);
        newBalanceDisplay.textContent   = fmt(newBalance);
        newStatusDisplay.innerHTML      = statusPill(status);
        if (amountFormatted) amountFormatted.textContent = `₱${fmt(toNum(amountInput?.value))}`;

        if (amountHint) {
            amountHint.textContent = balance > 0
                ? `Maximum receivable: ₱${fmt(balance)} remaining balance.`
                : 'This case is fully paid.';
        }
        updateReviewDetails();
    }

    function updatePaymentMethodFields() {
        const isCashless = paymentMethod?.value === 'cashless';
        const type = cashlessType?.value || '';
        const showReceivedPayment = isCashless ? Boolean(type) : Boolean(paymentMethod?.value);
        paymentMethodOptions.forEach((option) => {
            const isActive = option.dataset.paymentMethodOption === paymentMethod?.value;
            option.classList.toggle('is-active', isActive);
            option.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        if (cashlessTypeField) cashlessTypeField.classList.toggle('hidden', !isCashless);
        if (cashlessFields) cashlessFields.classList.toggle('hidden', !isCashless || !type);
        if (receivedPaymentFields) receivedPaymentFields.classList.toggle('hidden', !showReceivedPayment);
        if (amountInput) {
            amountInput.required = showReceivedPayment;
            amountInput.disabled = !showReceivedPayment;
        }
        if (paidAtInput) {
            paidAtInput.required = showReceivedPayment;
            paidAtInput.disabled = !showReceivedPayment;
        }
        if (receiptInput) receiptInput.disabled = !showReceivedPayment;
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
        updateReviewDetails();

        const activeAccount = document.querySelector(`[data-cashless-panel="${type}"] [data-account-name-field]`);
        const activeMobile = document.querySelector(`[data-cashless-panel="${type}"] [data-mobile-number-field]`);
        const activeChannel = document.querySelector(`[data-cashless-panel="${type}"] [data-payment-channel-field]`);
        if (accountName && activeAccount) accountName.value = activeAccount.value;
        if (mobileNumber) mobileNumber.value = activeMobile ? activeMobile.value : '';
        if (paymentChannel) paymentChannel.value = activeChannel ? activeChannel.value : '';
        if (walletProvider) walletProvider.value = type === 'gcash' ? 'GCash' : (type === 'maya' ? 'Maya' : '');
        if (transactionRef && referenceNo) transactionRef.value = referenceNo.value;

        const visibleSections = [...document.querySelectorAll('#paymentForm .pf-section:not(.hidden)')];
        visibleSections.forEach((section, index) => {
            const marker = section.querySelector('.pf-section-index');
            if (marker) marker.textContent = String(index + 1);
        });
    }

    function inlineErrorNode(field) {
        if (!field?.id) return null;
        const id = `${field.id}_inline_error`;
        let node = document.getElementById(id);
        if (!node) {
            node = document.createElement('span');
            node.id = id;
            node.className = 'pf-inline-error';
            node.setAttribute('role', 'alert');
            const wrap = field.closest('.pf-control-wrap');
            (wrap || field).insertAdjacentElement('afterend', node);
        }
        return node;
    }

    function clearFieldError(field) {
        if (!field) return;
        field.setCustomValidity('');
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');
        const node = field.id ? document.getElementById(`${field.id}_inline_error`) : null;
        if (node) node.remove();
    }

    function showFieldError(field, message, focus = true) {
        if (!field) return false;
        field.setCustomValidity(message);
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        const node = inlineErrorNode(field);
        if (node) {
            node.textContent = message;
            field.setAttribute('aria-describedby', node.id);
        }
        if (focus) field.focus();
        return false;
    }

    function fail(field, message) {
        return showFieldError(field, message, true);
    }

    const validReferencePattern = /^[A-Za-z0-9 _/-]+$/;
    const validAccountNamePattern = /^(?=.*[\p{L}])[\p{L}\p{M} .'-]+$/u;
    const validMobilePattern = /^(09\d{9}|\+?639\d{9})$/;
    const validReceiptPattern = /^[A-Za-z0-9 /-]+$/;
    const validBankNamePattern = /^[A-Za-z0-9 .&()-]+$/;
    const validApprovalPattern = /^[A-Za-z0-9_/-]+$/;
    const validChannelPattern = /^[A-Za-z0-9 ._&()-]+$/;
    function validateReferenceValue(field, required = false) {
        const value = (field?.value || '').trim();
        if (required && !value) return fail(field, 'Reference number is required.');
        if (!value) return true;
        if (value.length < 4 || value.length > 60) return fail(field, 'Reference number must be 4 to 60 characters.');
        if (!validReferencePattern.test(value)) return fail(field, 'Reference number contains invalid characters.');
        clearFieldError(field);
        return true;
    }
    function validateAccountNameValue(field) {
        const value = (field?.value || '').trim();
        if (!value) return true;
        if (value.length < 2 || value.length > 100) return fail(field, 'Account name must be between 2 and 100 characters.');
        if (!validAccountNamePattern.test(value)) return fail(field, 'Account name should contain letters only and must not include numbers or special characters.');
        clearFieldError(field);
        return true;
    }

    function validateMobileValue(field) {
        const value = (field?.value || '').trim();
        if (!value) {
            clearFieldError(field);
            return true;
        }
        if (!validMobilePattern.test(value)) return fail(field, 'Enter a valid Philippine mobile number, e.g. 09171234567.');
        clearFieldError(field);
        return true;
    }

    function validateReceiptValue(field) {
        const value = (field?.value || '').trim();
        if (!value) {
            clearFieldError(field);
            return true;
        }
        if (value.length < 3 || value.length > 50) return fail(field, 'Receipt / OR number must be 3 to 50 characters.');
        if (!validReceiptPattern.test(value)) return fail(field, 'Use letters, numbers, spaces, hyphens, or slashes only.');
        clearFieldError(field);
        return true;
    }

    function localDateTimeValue(date = new Date()) {
        const pad = (value) => String(value).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    function validatePaymentDateValue(field) {
        if (!field) return true;
        field.max = localDateTimeValue();
        if (!field.value) return fail(field, 'Payment date and time is required.');
        const selected = new Date(field.value);
        if (Number.isNaN(selected.getTime())) return fail(field, 'Enter a valid payment date and time.');
        if (selected.getTime() > Date.now()) return fail(field, 'Payment date and time cannot be in the future.');
        clearFieldError(field);
        return true;
    }

    function validateOtherBankValue(field) {
        const value = (field?.value || '').trim();
        if (!value) return fail(field, 'Please enter the other bank name.');
        if (value.length < 2 || value.length > 80 || !validBankNamePattern.test(value)) {
            return fail(field, 'Bank name must be between 2 and 80 valid characters.');
        }
        clearFieldError(field);
        return true;
    }

    function validateApprovalValue(field) {
        const value = (field?.value || '').trim();
        if (!value) return true;
        if (value.length < 4 || value.length > 40 || !validApprovalPattern.test(value)) {
            return fail(field, 'Approval code must be 4 to 40 characters using letters, numbers, _, /, or -.');
        }
        clearFieldError(field);
        return true;
    }

    function validateChannelValue(field) {
        const value = (field?.value || '').trim();
        if (!value) return fail(field, 'Please enter the payment channel.');
        if (value.length < 2 || value.length > 80 || !validChannelPattern.test(value)) {
            return fail(field, 'Payment channel must be between 2 and 80 valid characters.');
        }
        clearFieldError(field);
        return true;
    }

    function capitalizeName(field) {
        if (!field?.value) return;
        field.value = field.value.toLocaleLowerCase().replace(/(^|[\s.'-])([\p{L}])/gu, (match, prefix, letter) => `${prefix}${letter.toLocaleUpperCase()}`);
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
        ].forEach(clearFieldError);
    }

    function validateCashlessDetails() {
        updatePaymentMethodFields();
        clearCashlessValidity();

        if (!validateReceiptValue(receiptInput)) return false;
        if (paymentMethod?.value !== 'cashless') return true;

        const type = cashlessType?.value || '';
        const ref = (referenceNo?.value || '').trim();
        const approval = (approvalCode?.value || '').trim();
        const activeChannel = document.querySelector(`[data-cashless-panel="${type}"] [data-payment-channel-field]`);
        const activeAccount = document.querySelector(`[data-cashless-panel="${type}"] [data-account-name-field]`);
        const activeMobile = document.querySelector(`[data-cashless-panel="${type}"] [data-mobile-number-field]`);

        if (!type) return fail(cashlessType, 'Please select a cashless type.');
        if (type === 'bank_transfer') {
            if (!(bankName?.value || '').trim()) return fail(bankName, 'Please select the bank name.');
            if (bankName.value === 'Other Bank' && !validateOtherBankValue(otherBankName)) return false;
            if (!validateAccountNameValue(accountName)) return false;
            if (!validateReferenceValue(referenceNo, true)) return false;
        }
        if (type === 'gcash' || type === 'maya') {
            if (!validateAccountNameValue(activeAccount)) return false;
            if (!validateMobileValue(activeMobile)) return false;
            if (!validateReferenceValue(referenceNo, true)) return false;
        }
        if (type === 'card' && !approval && !ref) {
            return fail(approvalCode, 'Please enter the approval code or reference number.');
        }
        if (type === 'card' && approval && !validateApprovalValue(approvalCode)) return false;
        if (type === 'card' && ref && !validateReferenceValue(referenceNo, false)) return false;
        if (type === 'other') {
            if (!validateChannelValue(activeChannel)) return false;
            if (!validateReferenceValue(referenceNo, true)) return false;
        }
        if (!validateReceiptValue(receiptInput)) return false;

        return true;
    }

    if (caseSelect) caseSelect.addEventListener('change', () => {
        fillAmountFromSelectedCase();
        updateSummary();
    });
    if (amountInput) amountInput.addEventListener('input', updateSummary);
    if (paymentMethod) paymentMethod.addEventListener('change', updatePaymentMethodFields);
    paymentMethodOptions.forEach((option) => {
        option.addEventListener('click', () => {
            if (!paymentMethod) return;
            paymentMethod.value = option.dataset.paymentMethodOption || 'cash';
            paymentMethod.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
    if (cashlessType) {
        cashlessType.addEventListener('change', () => {
            updatePaymentMethodFields();
            if (paymentMethod?.value === 'cashless' && cashlessType.value && cashlessFields) {
                requestAnimationFrame(() => {
                    cashlessFields.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            }
        });
    }
    if (bankName) bankName.addEventListener('change', updatePaymentMethodFields);
    otherBankName?.addEventListener('input', () => clearFieldError(otherBankName));
    otherBankName?.addEventListener('blur', () => {
        if (bankName?.value === 'Other Bank') validateOtherBankValue(otherBankName);
    });
    if (accountName) {
        accountName.addEventListener('input', () => {
            clearFieldError(accountName);
            updatePaymentMethodFields();
        });
        accountName.addEventListener('blur', () => {
            capitalizeName(accountName);
            validateAccountNameValue(accountName);
        });
    }
    if (referenceNo) {
        referenceNo.addEventListener('input', () => {
            clearFieldError(referenceNo);
            updatePaymentMethodFields();
        });
        referenceNo.addEventListener('blur', () => validateReferenceValue(referenceNo, false));
    }
    paidAtInput?.addEventListener('input', () => {
        clearFieldError(paidAtInput);
        paidAtInput.max = localDateTimeValue();
        updateReviewDetails();
    });
    paidAtInput?.addEventListener('change', () => validatePaymentDateValue(paidAtInput));
    paidAtInput?.addEventListener('blur', () => validatePaymentDateValue(paidAtInput));
    receiptInput?.addEventListener('input', () => {
        clearFieldError(receiptInput);
        updateReviewDetails();
    });
    receiptInput?.addEventListener('blur', () => validateReceiptValue(receiptInput));
    document.getElementById('pf_back_to_edit')?.addEventListener('click', () => goToStep(2));
    document.querySelectorAll('[data-account-name-field], [data-mobile-number-field], [data-payment-channel-field]').forEach(field => {
        field.addEventListener('input', () => {
            clearFieldError(field);
            updatePaymentMethodFields();
        });
    });
    document.querySelectorAll('[data-account-name-field]').forEach(field => {
        field.addEventListener('blur', () => {
            capitalizeName(field);
            validateAccountNameValue(field);
        });
    });
    document.querySelectorAll('[data-mobile-number-field]').forEach(field => {
        field.addEventListener('blur', () => validateMobileValue(field));
    });
    if (approvalCode) approvalCode.addEventListener('input', () => {
        clearFieldError(approvalCode);
        clearFieldError(referenceNo);
    });
    approvalCode?.addEventListener('blur', () => validateApprovalValue(approvalCode));
    document.querySelectorAll('[data-payment-channel-field]').forEach(field => {
        field.addEventListener('blur', () => validateChannelValue(field));
    });
    form?.addEventListener('submit', (event) => {
        if (!validateCashlessDetails() || !validatePaymentDateValue(paidAtInput)) {
            event.preventDefault();
            goToStep(2);
        }
    });

    form?.addEventListener('invalid', (event) => {
        const target = event.target;
        if (target === caseSelect || target === amountInput) goToStep(1);
        else if (target !== form) goToStep(2);
    }, true);

    buildCashlessTypeButtons();
    buildAccordion();
    const serverErrorFields = @json(array_keys($errors->toArray()));
    if (serverErrorFields.length && !serverErrorFields.some((field) => ['funeral_case_id', 'amount_paid', 'payment'].includes(field))) {
        goToStep(2);
    }
    updateSummary();
    updatePaymentMethodFields();
})();
</script>
