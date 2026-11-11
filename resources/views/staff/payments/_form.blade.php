{{-- NOTE: Keep input names/ids; JS summary and PaymentController depend on them. --}}
@include('partials.worklist_consistency_styles')
<style>
    .pf-shell { display: flex; flex-direction: column; gap: .95rem; }
    .pf-section-index {
        display:grid; place-items:center; flex:0 0 28px; height:28px;
        border-radius:50%; background:var(--pf-selected); color:var(--pf-ink);
        font-size:13px; font-weight:700;
    }
    .pf-section {
        border: 1px solid var(--pf-border);
        border-radius: 18px;
        background: var(--pf-surface);
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
    .pf-quick-amounts { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
    .pf-quick-amount { min-height:36px; padding:0 12px; border:1px solid var(--pf-border); border-radius:10px; background:var(--pf-soft); color:var(--pf-ink); font-size:12px; font-weight:750; cursor:pointer; }
    .pf-quick-amount:hover,.pf-quick-amount:focus-visible { background:var(--pf-selected); border-color:var(--pf-focus); outline:none; }
    .pf-payment-progress { margin-top:12px; }
    .pf-progress-meta { display:flex; justify-content:space-between; gap:12px; margin-bottom:6px; color:var(--pf-muted); font-size:11px; font-weight:700; }
    .pf-progress-track { height:8px; overflow:hidden; border-radius:999px; background:var(--pf-selected); }
    .pf-progress-bar { width:0; height:100%; border-radius:inherit; background:var(--pf-success); transition:width .2s ease; }
    .pf-method-types { display:flex; flex-wrap:wrap; gap:8px; }
    #pf_cashless_type_field > .pf-control-wrap { display:none; }
    .pf-method-type { flex:1 1 110px; min-height:54px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; border:1px solid var(--pf-border); border-radius:11px; background:var(--pf-soft); color:var(--pf-ink); font-size:12px; font-weight:650; cursor:pointer; }
    .pf-method-type i { font-size:18px; }
    .pf-method-type.is-active { background:var(--pf-primary); border-color:var(--pf-primary); color:#fff; }
    .pf-review-verification { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
    .pf-review-card { padding:14px; border:1px solid var(--pf-border); border-radius:13px; background:var(--pf-soft); }
    .pf-review-card h4 { display:flex; align-items:center; gap:7px; margin:0 0 10px; color:var(--pf-ink); font-size:12px; text-transform:uppercase; letter-spacing:.06em; }
    .pf-review-row { display:flex; justify-content:space-between; gap:16px; padding:5px 0; color:var(--pf-muted); font-size:12px; }
    .pf-review-row strong { color:var(--pf-ink); text-align:right; overflow-wrap:anywhere; }
    .pf-section-head {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        padding: .88rem 1rem;
        border-bottom: 1px solid var(--pf-border);
        background: var(--pf-selected);
    }
    .pf-section-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: .8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background: var(--pf-primary);
        box-shadow: none;
        flex: 0 0 auto;
    }
    .pf-section-title { margin: 0; color: var(--pf-ink); font-size: .98rem; font-weight: 650; line-height: 1.2; }
    .pf-section-sub { margin-top: .16rem; color: var(--pf-muted); font-size: .78rem; font-weight: 520; line-height: 1.35; }
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
        color: var(--pf-muted);
        text-transform: uppercase;
        letter-spacing: 0.085em;
        margin-bottom: 0.45rem;
    }
    .pf-required { color: #f43f5e; font-size: .78rem; line-height: 1; }
    .pf-input {
        width: 100%;
        min-height: 2.9rem;
        border-radius: 12px;
        border: 1.5px solid var(--pf-border);
        background: var(--pf-soft);
        padding: 0.78rem 1rem;
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--pf-ink);
        box-shadow: none;
        transition: border-color .18s, background .18s, color .18s;
        font-family: inherit;
        box-sizing: border-box;
    }
    .pf-input:focus {
        border-color: var(--pf-primary);
        box-shadow: none;
        outline: none;
        background: var(--pf-surface);
    }
    .pf-input:hover { background: var(--pf-selected); border-color: var(--pf-focus); }
    .pf-input[readonly] { background: var(--pf-soft); color: var(--pf-muted); }
    .pf-input::placeholder { color: var(--pf-muted); font-weight: 500; }
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
        color: var(--pf-muted);
        font-size: .78rem;
        line-height: 1;
    }
    .pf-control-wrap:focus-within .pf-control-icon { color: var(--pf-primary); }
    .pf-help { margin-top: .45rem; color: var(--pf-muted); font-size: .72rem; font-weight: 550; line-height: 1.35; }
    .pf-help.soft { color: var(--pf-muted); }
    .pf-input.is-invalid { border-color:#b42318 !important; background:#fff8f6 !important; box-shadow:0 0 0 3px rgba(180,35,24,.1) !important; }
    .pf-inline-error { display:block; margin-top:.4rem; color:#9f1c14; font-size:.72rem; font-weight:700; line-height:1.35; }
    .pf-payment-layout {
        display: grid;
        grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr);
        gap: 1rem;
        align-items: start;
    }
    .pf-method-layout { display:grid; gap:16px; }
    .pf-method-options {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }
    .pf-method-option {
        min-height: 4.6rem;
        border: 1.5px solid var(--pf-border);
        border-radius: 14px;
        background: var(--pf-soft);
        color: var(--pf-ink);
        padding: .85rem;
        text-align: left;
        cursor: pointer;
        box-shadow: none;
        transition: background .18s, border-color .18s, color .18s;
    }
    .pf-method-option:hover { background: var(--pf-selected); border-color: var(--pf-focus); }
    .pf-method-option.is-active {
        background: var(--pf-primary);
        border-color: var(--pf-primary);
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
        color: var(--pf-muted);
    }
    .pf-method-option.is-active .pf-method-option-copy { color: rgba(255,255,255,.75); }
    .pf-amount-wrap {
        display: flex;
        align-items: stretch;
        min-height: 2.95rem;
        border-radius: 12px;
        border: 1.5px solid var(--pf-border);
        background: var(--pf-soft);
        box-shadow: none;
        transition: border-color .18s, background .18s;
        overflow: hidden;
    }
    .pf-amount-wrap:focus-within {
        border-color: var(--pf-primary);
        box-shadow: none;
        background: var(--pf-surface);
    }
    .pf-amount-prefix {
        padding: 0 14px;
        font-size: .95rem;
        font-weight: 750;
        color: var(--pf-muted);
        background: var(--pf-selected);
        border-right: 1.5px solid var(--pf-border);
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
        color: var(--pf-ink);
        background: transparent;
        font-family: inherit;
        width: 100%;
    }
    .pf-amount-input::placeholder { color: var(--pf-muted); font-weight: 500; }

    .pf-snapshot {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
        border-radius: 14px;
        border: 1px solid var(--pf-border);
        background: var(--pf-soft);
        padding: .85rem;
        margin-top: .9rem;
    }
    .pf-snapshot-item { padding: .35rem .5rem; }
    .pf-snapshot-label { color: var(--pf-muted); font-size: .7rem; font-weight: 650; margin-bottom: .2rem; }
    .pf-snapshot-value { color: var(--pf-ink); font-size: .95rem; font-weight: 750; font-variant-numeric: tabular-nums; }
    .pf-snapshot-value.good { color: var(--pf-success); }
    .pf-snapshot-value.warn { color: var(--pf-warning); }

    .pf-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
    .pf-summary-card {
        border-radius: 14px;
        border: 1.5px solid var(--pf-border);
        background: var(--pf-soft);
        padding: .85rem .95rem;
        min-width: 0;
    }
    .pf-summary-card.accent-green { border-color: var(--pf-focus); background: var(--pf-soft); }
    .pf-summary-card.accent-navy { border-color: var(--pf-primary); background: var(--pf-primary); color: #fff; }
    .pf-summary-label {
        font-size: 0.6rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--pf-muted);
        margin-bottom: .28rem;
    }
    .pf-summary-card.accent-green .pf-summary-label { color: var(--pf-success); }
    .pf-summary-card.accent-navy .pf-summary-label { color: rgba(255,255,255,.62); }
    .pf-summary-value {
        font-size: .98rem;
        font-weight: 750;
        color: var(--pf-ink);
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }
    .pf-summary-card.accent-navy .pf-summary-value { color: #fff; }
    .pf-summary-card.accent-green .pf-summary-value { color: var(--pf-success); }
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
        border: 1px solid var(--pf-border);
        background: var(--pf-soft);
        padding: 1rem;
    }
    .pf-note {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        border-radius: 14px;
        border: 1px solid var(--pf-border);
        background: var(--pf-soft);
        padding: .85rem 1rem;
        color: var(--pf-muted);
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
        border-top: 1px solid var(--pf-border);
        background: var(--pf-surface);
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
    .pf-btn.secondary { border: 1px solid var(--pf-border); background: var(--pf-soft); color: var(--pf-muted); }
    .pf-btn.secondary:hover { background: var(--pf-selected); border-color: var(--pf-focus); color: var(--pf-ink); }
    .pf-btn.primary {
        border: 1px solid var(--pf-primary);
        background: var(--pf-primary);
        color: #fff;
        box-shadow: none;
    }
    .pf-btn.primary:hover { background: var(--pf-primary-hover); box-shadow: none; }
    .pf-btn:disabled { opacity: .45; pointer-events: none; transform: none; box-shadow: none; }

    @media (max-width: 900px) {
        .pf-grid.two,
        .pf-grid.three,
        .pf-payment-layout,
        .pf-review-layout,
        .pf-summary-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .pf-section-head { padding: .95rem; }
        .pf-section-body { padding: .95rem; }
        .pf-grid.two,
        .pf-grid.three,
        .pf-payment-layout,
        .pf-review-layout,
        .pf-summary-grid,
        .pf-snapshot { grid-template-columns: 1fr; }
        .pf-review-verification { grid-template-columns:1fr 1fr; }
        .pf-actions { flex-direction: column-reverse; align-items: stretch; }
        .pf-btn { width: 100%; }
    }
    #paymentFormModal .payment-modal-body > .pf-shell,
    #paymentForm,
    .pf-wizard {
        display:flex; flex:1; flex-direction:column; gap:0; min-width:0; min-height:0;
    }
    #paymentFormModal .payment-modal-body > .pf-shell > [role="alert"] {
        margin:12px 16px 0; flex:none; background:var(--pf-error-surface); color:var(--pf-error);
    }
    .pf-wizard-nav {
        display:flex; align-items:center; flex:none; gap:clamp(8px,2vw,18px);
        padding:12px clamp(14px,3vw,24px); border-bottom:1px solid var(--pf-border);
    }
    .pf-wizard-step {
        display:flex; align-items:center; gap:8px; min-width:0; padding:4px 0;
        color:var(--pf-ink); font-size:14px; font-weight:650; text-align:start;
        border:0; background:transparent; cursor:pointer;
    }
    .pf-wizard-step:disabled { color:var(--pf-muted); cursor:default; }
    .pf-wizard-marker {
        display:grid; place-items:center; width:30px; height:30px; flex:none;
        border-radius:50%; background:var(--pf-soft); color:var(--pf-ink);
    }
    .pf-wizard-step:is([aria-current="step"],.is-complete) .pf-wizard-marker { background:var(--pf-primary); color:#fff; }
    .pf-wizard-connector { flex:1; min-width:8px; height:2px; border-radius:2px; background:var(--pf-border); }
    .pf-wizard-step.is-complete + .pf-wizard-connector { background:var(--pf-success); }
    .pf-wizard-panels {
        flex:1; min-height:0; overflow:auto; overscroll-behavior:contain;
        scrollbar-width:thin; padding:18px clamp(14px,3vw,24px);
    }
    .pf-wizard-heading { margin:0 0 16px; }
    .pf-wizard-heading h3 { margin:0; font-size:18px; font-weight:700; color:var(--pf-ink); }
    .pf-wizard-heading p { margin:4px 0 0; font-size:14px; color:var(--pf-muted); }
    .pf-wizard-panel > .pf-section { border:0; border-radius:0; background:transparent; overflow:visible; }
    .pf-wizard-panel > .pf-section > .pf-section-head { display:none; }
    .pf-wizard-panel > .pf-section > .pf-section-body { padding:0; }
    .pf-wizard-panel > .pf-section:not(.hidden) + .pf-section:not(.hidden) { margin-top:16px; padding-top:16px; border-top:1px solid var(--pf-border); }
    .pf-wizard-panel .pf-snapshot,
    .pf-wizard-panel .pf-bank-box,
    .pf-wizard-panel .pf-review-card,
    .pf-wizard-panel .pf-summary-card { border:0; border-radius:10px; }
    .pf-wizard-panel .pf-review-verification .pf-review-card { background:transparent; padding:0; }
    .pf-wizard-panel .pf-review-row { font-size:13px; border-bottom:1px solid var(--pf-border); }
    .pf-wizard-panel .pf-summary-card.accent-navy { background:var(--pf-soft); color:var(--pf-ink); }
    .pf-wizard-panel .pf-summary-card.accent-navy .pf-summary-label { color:var(--pf-muted); }
    .pf-wizard-panel .pf-summary-card.accent-navy .pf-summary-value { color:var(--pf-warning); }
    .pf-wizard-panel .pf-note { margin-top:16px; border:0; background:var(--pf-soft); }
    .pf-wizard-panel :is(.pf-label,.pf-summary-label) { font-size:13px; text-transform:none; letter-spacing:normal; }
    .pf-wizard-panel .pf-help { font-size:13px; }
    .pf-wizard-panel .pf-label { flex-wrap:wrap; }
    .pf-optional { color:var(--pf-muted); font-size:12px; font-weight:400; }
    .pf-detail-title { margin:0 0 12px; color:var(--pf-ink); font-size:15px; font-weight:650; }
    .pf-detail-full { grid-column:1 / -1; }
    .pf-wizard-panel .pf-bank-box { padding:0; background:transparent; }
    #pf_cashless_tracking { margin-top:16px; }
    #pf_card_tracking_hint { margin:0; }
    #pf_card_tracking_error:empty { display:none; }
    #paymentFormModal .pf-payment-layout > .payment-proof-widget { grid-column:1 / -1; }
    .pf-wizard-panel #pf_amount_formatted { font-weight:700; color:var(--pf-ink); }
    html[data-theme] #paymentFormModal :is(.pf-input,.pf-amount-wrap) {
        border:1px solid transparent !important; border-radius:8px; background:var(--pf-soft) !important; color:var(--pf-ink) !important;
    }
    html[data-theme] #paymentFormModal :is(.pf-input,.pf-amount-wrap):focus-within { border-color:var(--pf-focus) !important; }
    html[data-theme] #paymentFormModal .pf-amount-input {
        background:transparent !important; border:0 !important; color:var(--pf-ink) !important; min-width:0;
    }
    html[data-theme] #paymentFormModal .pf-input.is-invalid { border-color:var(--pf-error) !important; background:var(--pf-error-surface) !important; box-shadow:none !important; }
    #paymentFormModal .pf-inline-error { color:var(--pf-error); }
    #paymentFormModal :is(.pf-method-option,.pf-method-type,.pf-quick-amount) {
        border:0; border-radius:8px; background:var(--pf-soft); color:var(--pf-ink);
    }
    #paymentFormModal :is(.pf-method-option,.pf-method-type,.pf-quick-amount):is(:hover,.is-active) { background:var(--pf-selected); }
    #paymentFormModal .pf-method-option.is-active .pf-method-option-copy { color:var(--pf-muted); }
    #paymentFormModal :is(.pf-method-option,.pf-method-type).is-active { box-shadow:inset 0 -3px var(--pf-success); }
    #paymentFormModal .pf-wizard .pf-actions {
        flex:none; flex-direction:row; align-items:center; gap:8px; position:static;
        margin:0; padding:10px clamp(14px,3vw,24px);
    }
    #paymentFormModal .pf-btn {
        width:auto; min-height:44px; border:0 !important; border-radius:8px;
        box-shadow:none !important; white-space:normal; text-align:center;
    }
    #paymentFormModal .pf-btn.primary { background:var(--pf-primary) !important; color:#fff !important; }
    #paymentFormModal .pf-btn.primary:hover { background:var(--pf-primary-hover) !important; }
    #paymentFormModal .pf-btn.secondary { background:var(--pf-soft) !important; color:var(--pf-ink) !important; }
    #paymentFormModal .pf-btn.secondary:hover { background:var(--pf-selected) !important; }
    #paymentFormModal #closePaymentFormBottom {
        margin-inline-end:auto; background:var(--pf-cancel) !important; color:var(--pf-cancel-ink) !important;
    }
    #paymentFormModal #closePaymentFormBottom:hover { background:var(--pf-cancel-hover) !important; }
    #paymentFormModal [hidden] { display:none !important; }
    #paymentFormModal [data-payment-case-trigger] {
        width:100%; min-height:46px; justify-content:space-between; padding:10px 14px;
        background:var(--pf-soft); color:var(--pf-ink); font-size:14px;
    }
    #paymentFormModal [data-payment-case-trigger]:is(:hover,[aria-expanded=true]) { background:var(--pf-selected); }
    #paymentFormModal [data-payment-case-trigger] span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    #paymentFormModal [data-payment-case-trigger][aria-invalid=true] { outline:1px solid var(--pf-error); }
    #paymentFormModal #funeral_case_id-options {
        --record-search-action:var(--reference-action-bg);
        --record-search-action-hover:var(--reference-action-hover);
        --record-search-action-active:var(--reference-action-hover);
        --record-search-action-text:var(--reference-action-text);
        width:min(560px,calc(100vw - 32px)); padding:14px;
        background:var(--pf-surface); color:var(--pf-ink);
    }
    #paymentFormModal #funeral_case_id-options:popover-open { display:flex; flex-direction:column; gap:12px; overflow:hidden; }
    #paymentFormModal #funeral_case_id-options .worklist-search-control { flex:none; max-width:none; }
    #paymentFormModal #funeral_case_id-options .worklist-search-control > input {
        min-height:0; outline:none !important; box-shadow:none !important;
    }
    #paymentFormModal #funeral_case_id-options .worklist-search-control:focus-within { border-color:var(--pf-focus); }
    #paymentFormModal #funeral_case_id-options .service-field-results {
        min-height:0; max-height:min(340px,45dvh); margin:0; gap:0;
        overflow:auto; overscroll-behavior:contain; scrollbar-gutter:stable;
        scrollbar-width:thin; scrollbar-color:var(--pf-border) transparent;
    }
    #paymentFormModal #funeral_case_id-options .service-field-results button {
        width:100%; min-height:58px; padding:11px 12px; border:0; border-bottom:1px solid var(--pf-border);
        background:transparent; color:inherit; font-size:13px; overflow-wrap:anywhere;
    }
    #paymentFormModal #funeral_case_id-options .service-field-results button:last-child { border-bottom:0; }
    #paymentFormModal #funeral_case_id-options .service-field-results button:is(:hover,:focus-visible,[aria-pressed=true]) { background:var(--pf-soft); }
    .pf-case-option-top { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:baseline; gap:2px 12px; }
    .pf-case-option-code { font-size:14px; font-weight:700; }
    .pf-case-option-balance { color:var(--pf-warning); font-weight:650; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .pf-case-option-names { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:4px 16px; margin-top:6px; }
    .pf-case-option-names > span { display:flex; align-items:baseline; gap:6px; min-width:0; font-weight:400; }
    .pf-case-option-names small, .pf-case-option-balance small { flex:none; color:var(--pf-muted); font-size:12px; font-weight:400; }
    .pf-case-option-balance small { margin-inline-end:6px; }
    #paymentFormModal #funeral_case_id-options mark { background:transparent; color:inherit; text-decoration:underline; text-underline-offset:3px; }
    #paymentFormModal #funeral_case_id-options .service-popover-footer { flex:none; margin:0; padding-top:10px; border-color:var(--pf-border); }
    #paymentFormModal #funeral_case_id-options .service-popover-done { min-height:40px; padding-inline:14px; }
    @media (max-width:640px) {
        .pf-wizard-nav { gap:8px; }
        .pf-wizard-step { gap:5px; font-size:12px; }
        .pf-wizard-marker { width:24px; height:24px; }
        .pf-wizard-panel .pf-summary-grid { grid-template-columns:1fr 1fr; }
        #paymentFormModal .pf-btn { padding-inline:10px; font-size:12px; }
        #paymentFormModal #funeral_case_id-options { padding:12px; }
        #paymentFormModal #funeral_case_id-options:popover-open { gap:10px; }
        #paymentFormModal #funeral_case_id-options .worklist-search-control > input { font-size:16px !important; }
        #paymentFormModal #funeral_case_id-options .worklist-search-control:has(.worklist-search-clear[hidden]) > input { padding-right:94px !important; }
        #paymentFormModal #funeral_case_id-options .service-field-results button { padding:10px 8px; }
        .pf-case-option-names { grid-template-columns:minmax(0,1fr); }
        .pf-case-option-names > span { display:grid; grid-template-columns:58px minmax(0,1fr); }
    }
</style>

<div class="pf-shell">

    {{-- Error banner --}}
    @if($errors->has('payment'))
        <div class="flex items-start gap-3 rounded-xl px-4 py-3 text-sm" role="alert">
            <i class="bi bi-exclamation-circle-fill text-rose-500 mt-0.5 flex-shrink-0"></i>
            <span class="font-medium">{{ $errors->first('payment') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('payments.store') }}" id="paymentForm" class="pf-shell">
        @csrf
        <input type="hidden" name="submission_id" value="{{ old('submission_id', (string) \Illuminate\Support\Str::uuid()) }}">

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
                    <label class="pf-label" id="pf_case_picker_label" for="funeral_case_id">Funeral Case <span class="pf-required">*</span></label>
                    @php
                        $preselectCase  = $preselectCase ?? null;
                        $prefillCaseId  = old('funeral_case_id', $preselectCase->id ?? null);
                        $includePreselect = $preselectCase && !$openCases->contains('id', $preselectCase->id);
                    @endphp
                    <div class="pf-control-wrap">
                        <select name="funeral_case_id" id="funeral_case_id" class="pf-input pf-select" data-search-placeholder="Case code or name" data-search-label="Search by case code, client or deceased name" aria-describedby="pf_case_help" required>
                            <option value="">Choose a case with remaining balance</option>
                            @foreach($openCases as $case)
                                <option
                                    value="{{ $case->id }}"
                                    data-total="{{ $case->total_amount }}"
                                    data-paid="{{ $case->total_paid }}"
                                    data-balance="{{ $case->balance_amount }}"
                                    data-branch-id="{{ $case->branch_id }}"
                                    data-case-code="{{ $case->case_code }}"
                                    data-search-terms="{{ $case->case_code }} {{ $case->client?->full_name }} {{ $case->deceased?->full_name }}"
                                    data-client="{{ $case->client?->full_name ?? 'Not provided' }}"
                                    data-deceased="{{ $case->deceased?->full_name ?? 'Not provided' }}"
                                    data-package="{{ $case->package_name_snapshot ?: $case->custom_package_name ?: $case->package?->name ?: $case->service_package ?: 'Not specified' }}"
                                    {{ $prefillCaseId == $case->id ? 'selected' : '' }}
                                >{{ $case->case_code }} · {{ $case->client?->full_name ?? '—' }} · {{ $case->deceased?->full_name ?? '—' }} · Balance: ₱{{ number_format((float)$case->balance_amount, 2) }}</option>
                            @endforeach
                            @if($includePreselect)
                                <option
                                    value="{{ $preselectCase->id }}"
                                    data-total="{{ $preselectCase->total_amount }}"
                                    data-paid="{{ $preselectCase->total_paid }}"
                                    data-balance="{{ $preselectCase->balance_amount }}"
                                    data-branch-id="{{ $preselectCase->branch_id }}"
                                    data-case-code="{{ $preselectCase->case_code }}"
                                    data-search-terms="{{ $preselectCase->case_code }} {{ $preselectCase->client?->full_name }} {{ $preselectCase->deceased?->full_name }}"
                                    data-client="{{ $preselectCase->client?->full_name ?? 'Not provided' }}"
                                    data-deceased="{{ $preselectCase->deceased?->full_name ?? 'Not provided' }}"
                                    data-package="{{ $preselectCase->package_name_snapshot ?: $preselectCase->custom_package_name ?: $preselectCase->package?->name ?: $preselectCase->service_package ?: 'Not specified' }}"
                                    selected
                                >{{ $preselectCase->case_code }} · {{ $preselectCase->client?->full_name ?? '—' }} · {{ $preselectCase->deceased?->full_name ?? '—' }} · Balance: ₱{{ number_format((float)$preselectCase->balance_amount, 2) }}</option>
                            @endif
                        </select>
                        <span class="pf-control-icon"><i class="bi bi-chevron-down"></i></span>
                    </div>
                    <p class="pf-help soft" id="pf_case_help">Choose an open case with a remaining balance.</p>
                    <p class="pf-inline-error" id="pf_case_picker_error" role="alert" @unless($errors->has('funeral_case_id')) hidden @endunless>{{ $errors->first('funeral_case_id', 'Choose a funeral case.') }}</p>
                    @if($fromDashboardSearch ?? false)
                        <p class="pf-help" id="pf_selected_deceased">Deceased: <span></span></p>
                    @endif
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
                                <span class="pf-method-option-copy">Bills or coins received in person.</span>
                            </button>
                            <button type="button" class="pf-method-option" data-payment-method-option="cashless">
                                <span class="pf-method-option-title">
                                    <i class="bi bi-credit-card-2-front"></i>
                                    Cashless
                                </span>
                                <span class="pf-method-option-copy">Bank transfer, e-wallet, card, or another payment channel.</span>
                            </button>
                        </div>
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
                        @error('cashless_type')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
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
                <h4 class="pf-detail-title">Cashless Transaction Details</h4>
                <div class="pf-bank-box">
                    <div data-cashless-panel="bank_transfer" class="cashless-panel hidden">
                        <div class="pf-grid two">
                            <div class="pf-field">
                                <label class="pf-label" for="bank_name">Bank Name <span class="pf-required">*</span></label>
                                <div class="pf-control-wrap">
                                    <select name="bank_name" id="bank_name" class="pf-input pf-select">
                                        <option value="">Select a bank, e.g. BDO</option>
                                        @foreach(['BDO', 'BPI', 'Metrobank', 'Landbank', 'Security Bank', 'UnionBank', 'RCBC', 'PNB', 'China Bank', 'Other Bank'] as $bank)
                                            <option value="{{ $bank }}" {{ old('bank_name', old('bank_or_channel') === 'Other' ? 'Other Bank' : old('bank_or_channel')) === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                                        @endforeach
                                    </select>
                                    <span class="pf-control-icon"><i class="bi bi-chevron-down"></i></span>
                                </div>
                                @error('bank_name')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="bank_account_name">Account Name <span class="pf-optional">(Optional)</span></label>
                                <input type="text" id="bank_account_name" data-account-name-field value="{{ old('cashless_type', 'bank_transfer') === 'bank_transfer' ? old('account_name', old('sender_name')) : '' }}" class="pf-input" maxlength="100" placeholder="e.g. Maria Santos" autocapitalize="words">
                                @error('account_name')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                            <div id="pf_other_bank_wrap" class="pf-field pf-detail-full hidden">
                                <label class="pf-label" for="other_bank_name">Other Bank Name <span class="pf-required">*</span></label>
                                <input type="text" name="other_bank_name" id="other_bank_name" value="{{ old('other_bank_name', old('other_bank_or_channel')) }}" class="pf-input" maxlength="80" placeholder="e.g. EastWest Bank">
                                @error('other_bank_name')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div data-cashless-panel="gcash" class="cashless-panel hidden">
                        <div class="pf-grid two">
                            <div class="pf-field">
                                <label class="pf-label" for="gcash_account_name">GCash Account Name <span class="pf-optional">(Optional)</span></label>
                                <input type="text" id="gcash_account_name" data-account-name-field value="{{ old('cashless_type') === 'gcash' ? old('account_name') : '' }}" class="pf-input" maxlength="100" placeholder="e.g. Maria Santos" autocapitalize="words">
                                @error('account_name')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="gcash_mobile_number">GCash Mobile Number <span class="pf-optional">(Optional)</span></label>
                                <input type="text" id="gcash_mobile_number" data-mobile-number-field value="{{ old('cashless_type') === 'gcash' ? old('mobile_number') : '' }}" class="pf-input" maxlength="13" inputmode="tel" placeholder="e.g. 09171234567">
                                @error('mobile_number')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div data-cashless-panel="maya" class="cashless-panel hidden">
                        <div class="pf-grid two">
                            <div class="pf-field">
                                <label class="pf-label" for="maya_account_name">Maya Account Name <span class="pf-optional">(Optional)</span></label>
                                <input type="text" id="maya_account_name" data-account-name-field value="{{ old('cashless_type') === 'maya' ? old('account_name') : '' }}" class="pf-input" maxlength="100" placeholder="e.g. Maria Santos" autocapitalize="words">
                                @error('account_name')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="maya_mobile_number">Maya Mobile Number <span class="pf-optional">(Optional)</span></label>
                                <input type="text" id="maya_mobile_number" data-mobile-number-field value="{{ old('cashless_type') === 'maya' ? old('mobile_number') : '' }}" class="pf-input" maxlength="13" inputmode="tel" placeholder="e.g. 09171234567">
                                @error('mobile_number')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div data-cashless-panel="card" class="cashless-panel hidden">
                        <div class="pf-grid two">
                            <div class="pf-field">
                                <label class="pf-label" for="card_type">Card Type <span class="pf-optional">(Optional)</span></label>
                                <div class="pf-control-wrap">
                                    <select name="card_type" id="card_type" class="pf-input pf-select">
                                        <option value="">Select a card type, e.g. Debit</option>
                                        <option value="debit" {{ old('card_type') === 'debit' ? 'selected' : '' }}>Debit</option>
                                        <option value="credit" {{ old('card_type') === 'credit' ? 'selected' : '' }}>Credit</option>
                                    </select>
                                    <span class="pf-control-icon"><i class="bi bi-chevron-down"></i></span>
                                </div>
                                @error('card_type')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="terminal_provider">Terminal / Provider <span class="pf-optional">(Optional)</span></label>
                                <input type="text" name="terminal_provider" id="terminal_provider" value="{{ old('terminal_provider') }}" class="pf-input" maxlength="80" placeholder="e.g. BDO POS">
                                @error('terminal_provider')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                            <p id="pf_card_tracking_hint" class="pf-help pf-detail-full">Enter at least one of the two fields below.</p>
                            <div class="pf-field">
                                <label class="pf-label" for="approval_code">Approval Code <span class="pf-optional">(Optional if reference provided)</span></label>
                                <input type="text" name="approval_code" id="approval_code" value="{{ old('approval_code') }}" class="pf-input" maxlength="40" placeholder="e.g. A12345" aria-describedby="pf_card_tracking_hint">
                            </div>
                            <div id="pf_card_reference_slot"></div>
                            <div id="pf_card_tracking_error" class="pf-detail-full">
                                @error('approval_code')<span id="approval_code_inline_error" class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div data-cashless-panel="other" class="cashless-panel hidden">
                        <div class="pf-grid two">
                            <div class="pf-field">
                                <label class="pf-label" for="payment_channel_other">Payment Channel <span class="pf-required">*</span></label>
                                <input type="text" id="payment_channel_other" data-payment-channel-field value="{{ old('payment_channel') }}" class="pf-input" maxlength="80" placeholder="e.g. PalawanPay">
                                @error('payment_channel')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="payment_notes">Notes <span class="pf-optional">(Optional)</span></label>
                                <input type="text" name="payment_notes" id="payment_notes" value="{{ old('payment_notes') }}" class="pf-input" maxlength="255" placeholder="e.g. Paid through a remittance center">
                                @error('payment_notes')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div id="pf_cashless_tracking">
                        <div id="pf_reference_field" class="pf-field">
                            <label class="pf-label" for="reference_number">Transaction Reference No. <span id="pf_reference_required" class="pf-required">*</span><span id="pf_reference_optional" class="pf-optional" hidden>(Optional if approval code provided)</span></label>
                            <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number', old('transaction_reference_no')) }}" class="pf-input" minlength="4" maxlength="60" placeholder="e.g. TXN-20260115-001">
                            @error('reference_number')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <input type="hidden" name="wallet_provider" id="wallet_provider" value="{{ old('wallet_provider') }}">
                    <input type="hidden" name="account_name" id="account_name" value="{{ old('account_name', old('sender_name')) }}">
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
                <h4 class="pf-detail-title">Payment Received</h4>
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
                                placeholder="e.g. 5000.00"
                                required
                            >
                        </div>
                        <p id="pf_amount_hint" class="pf-help soft">Choose a case first. This amount cannot exceed the remaining balance.</p>
                        <p id="pf_amount_formatted" class="pf-help">₱0.00</p>
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
                        <label class="pf-label" for="receipt_or_no">Receipt / OR No. <span id="pf_receipt_requirement"></span></label>
                        <input type="text" name="receipt_or_no" id="receipt_or_no" value="{{ old('receipt_or_no', old('accounting_reference_no')) }}" class="pf-input" minlength="3" maxlength="50" placeholder="e.g. OR-203456">
                        <p class="pf-help soft">Use the receipt or OR number issued by Accounting.</p>
                        @error('receipt_or_no')<span class="pf-inline-error" role="alert">{{ $message }}</span>@enderror
                    </div>

                    <x-payment-proof-upload context="payment" />
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
                        <div class="pf-review-row"><span>Payment proof</span><a data-proof-review target="_blank" rel="noopener" hidden>View Attached Proof</a></div>
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
            <button type="button" id="closePaymentFormBottom" class="pf-btn secondary">
                Cancel
            </button>
            <button type="button" id="pf_back_to_edit" class="pf-btn secondary">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                Back
            </button>
            <button type="submit" id="pf_submit_btn" class="pf-btn primary" {{ $openCases->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-check2-circle text-base"></i>
                Confirm Payment
            </button>
        </div>
    </form>
</div>

@include('partials.service-field-popover')
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
    const selectedDeceased = document.getElementById('pf_selected_deceased');
    const fromDashboardSearch = @json($fromDashboardSearch ?? false);
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

    let currentStep = 1;
    let wizardSteps = [];
    let wizardPanels;
    let continueButton;
    const backButton = document.getElementById('pf_back_to_edit');
    const submitBtn = document.getElementById('pf_submit_btn');
    const cannotSubmit = submitBtn?.disabled;
    const stepActions = ['Continue to Payment Method', 'Continue to Review'];

    function buildWizard() {
        if (!form) return;
        const sections = Array.from(form.children).filter((node) => node.classList?.contains('pf-section'));
        const caseSection = form.querySelector('.pf-section--case');
        const methodSection = sections.find((section) => section.querySelector('.pf-section-title')?.textContent.trim() === 'Payment Method');
        const reviewSection = sections.find((section) => section.querySelector('.pf-section-title')?.textContent.trim() === 'Review');
        const note = form.querySelector('.pf-note');
        const actions = form.querySelector('.pf-actions');
        if (!caseSection || !methodSection || !reviewSection || !cashlessFields || !receivedPaymentFields || !note || !actions || !submitBtn || !backButton) return;

        const amountField = amountInput?.closest('.pf-field');
        const caseBody = caseSection.querySelector('.pf-section-body');
        if (amountField && caseBody) {
            const amountPanel = document.createElement('div');
            amountPanel.className = 'pf-review-card';
            amountPanel.style.marginTop = '14px';
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
        root.className = 'pf-wizard';
        form.insertBefore(root, caseSection);

        const navigation = document.createElement('div');
        navigation.className = 'pf-wizard-nav';
        navigation.setAttribute('role', 'group');
        navigation.setAttribute('aria-label', 'Payment steps');

        wizardPanels = document.createElement('div');
        wizardPanels.className = 'pf-wizard-panels';
        root.append(navigation, wizardPanels, actions);

        continueButton = document.createElement('button');
        continueButton.type = 'button';
        continueButton.className = 'pf-btn primary';
        continueButton.addEventListener('click', () => continueStep(currentStep));
        actions.insertBefore(continueButton, submitBtn);

        const definitions = [
            { label:'Case & Amount', title:'Case & Payment Amount', copy:'Select the case and enter the amount received.', nodes:[caseSection] },
            { label:'Payment Details', title:'Payment Method & Details', copy:'Choose the method and enter the details of the payment received.', nodes:[methodSection, cashlessFields, receivedPaymentFields] },
            { label:'Review', title:'Review Payment', copy:'Check the transaction and updated balance before saving.', nodes:[reviewSection, note] },
        ];
        wizardSteps = definitions.map((definition, index) => {
            const stepNumber = index + 1;
            const panel = document.createElement('section');
            panel.className = 'pf-wizard-panel';
            panel.id = `pfWizardPanel${stepNumber}`;
            panel.setAttribute('aria-labelledby', `pfWizardHeading${stepNumber}`);
            panel.innerHTML = `<div class="pf-wizard-heading"><h3 id="pfWizardHeading${stepNumber}" tabindex="-1">${definition.title}</h3><p>${definition.copy}</p></div>`;
            panel.append(...definition.nodes);
            wizardPanels.appendChild(panel);

            if (index) {
                const connector = document.createElement('span');
                connector.className = 'pf-wizard-connector';
                connector.setAttribute('aria-hidden', 'true');
                navigation.appendChild(connector);
            }

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'pf-wizard-step';
            trigger.setAttribute('aria-controls', panel.id);
            trigger.setAttribute('aria-label', `Step ${stepNumber}: ${definition.label}`);
            trigger.innerHTML = `<span class="pf-wizard-marker" aria-hidden="true">${stepNumber}</span><span>${definition.label}</span>`;
            trigger.addEventListener('click', () => goToStep(stepNumber, true));
            navigation.appendChild(trigger);
            return { trigger, panel };
        });
        goToStep(1);
    }

    function goToStep(stepNumber, focusHeading = false) {
        if (!wizardSteps.length) return;
        const changed = currentStep !== stepNumber;
        currentStep = stepNumber;
        wizardSteps.forEach(({ trigger, panel }, index) => {
            const number = index + 1;
            const active = number === stepNumber;
            const complete = number < stepNumber;
            panel.hidden = !active;
            trigger.disabled = number > stepNumber;
            trigger.classList.toggle('is-complete', complete);
            if (active) trigger.setAttribute('aria-current', 'step');
            else trigger.removeAttribute('aria-current');
            trigger.firstElementChild.innerHTML = complete ? '<i class="bi bi-check-lg" aria-hidden="true"></i>' : String(number);
        });
        backButton.disabled = stepNumber === 1;
        const reviewing = stepNumber === wizardSteps.length;
        submitBtn.hidden = !reviewing;
        submitBtn.disabled = cannotSubmit || !reviewing;
        continueButton.hidden = reviewing;
        if (!reviewing) continueButton.innerHTML = `${stepActions[stepNumber - 1]} <i class="bi bi-chevron-right" aria-hidden="true"></i>`;
        if (changed) wizardPanels.scrollTop = 0;
        if (focusHeading) wizardSteps[stepNumber - 1].panel.querySelector('h3').focus({ preventScroll:true });
    }

    async function continueStep(stepNumber) {
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
            if (await checkPaymentIdentifiers()) return;
        }
        goToStep(stepNumber + 1, true);
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

        if (selectedDeceased) {
            selectedDeceased.hidden = !hasSel;
            selectedDeceased.querySelector('span').textContent = opt?.dataset.deceased ?? '';
        }

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
        if (paidAtInput) {
            paidAtInput.required = showReceivedPayment;
            paidAtInput.disabled = !showReceivedPayment;
        }
        if (receiptInput) {
            receiptInput.disabled = !showReceivedPayment;
            receiptInput.required = showReceivedPayment && !isCashless;
            const receiptRequirement = document.getElementById('pf_receipt_requirement');
            receiptRequirement.textContent = receiptInput.required ? '*' : '(Optional)';
            receiptRequirement.className = receiptInput.required ? 'pf-required' : 'pf-optional';
        }
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

        if (bankName) bankName.required = isCashless && type === 'bank_transfer';
        const isOtherBank = isCashless && type === 'bank_transfer' && bankName?.value === 'Other Bank';
        if (otherBankWrap) otherBankWrap.classList.toggle('hidden', !isOtherBank);
        if (otherBankName) otherBankName.required = isOtherBank;

        const needsReference = isCashless && ['bank_transfer', 'gcash', 'maya', 'other'].includes(type);
        if (referenceNo) referenceNo.required = needsReference;
        if (approvalCode) approvalCode.required = false;
        const isCard = isCashless && type === 'card';
        const referenceSlot = document.getElementById(isCard ? 'pf_card_reference_slot' : 'pf_cashless_tracking');
        const referenceField = document.getElementById('pf_reference_field');
        if (referenceSlot && referenceField && referenceField.parentElement !== referenceSlot) referenceSlot.append(referenceField);
        cashlessFields.querySelectorAll('.cashless-panel').forEach(panel => {
            const active = isCashless && panel.dataset.cashlessPanel === type;
            panel.classList.toggle('hidden', !active);
            panel.querySelectorAll('input, select').forEach(field => { field.disabled = !active; });
        });
        if (otherBankName) otherBankName.disabled = !isOtherBank;
        if (referenceNo) referenceNo.disabled = !isCashless || !type;
        document.getElementById('pf_cashless_tracking').classList.toggle('hidden', isCard);
        document.getElementById('pf_reference_required').hidden = !needsReference;
        document.getElementById('pf_reference_optional').hidden = !isCard;
        if (referenceNo) {
            if (isCard) referenceNo.setAttribute('aria-describedby', 'pf_card_tracking_hint');
            else referenceNo.removeAttribute('aria-describedby');
        }

        updateReviewDetails();

        const activeAccount = document.querySelector(`[data-cashless-panel="${type}"] [data-account-name-field]`);
        const activeMobile = document.querySelector(`[data-cashless-panel="${type}"] [data-mobile-number-field]`);
        const activeChannel = document.querySelector(`[data-cashless-panel="${type}"] [data-payment-channel-field]`);
        if (accountName) accountName.value = activeAccount?.value ?? '';
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
            const group = field === approvalCode ? document.getElementById('pf_card_tracking_error') : null;
            if (group) group.append(node);
            else (wrap || field).insertAdjacentElement('afterend', node);
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

    const identifierCheckUrl = @json(route('payments.identifier-check'));
    const identifierCheckState = new WeakMap();
    function identifierProvider() {
        const type = cashlessType?.value || '';
        if (type === 'bank_transfer') return bankName?.value === 'Other Bank' ? otherBankName?.value?.trim() : bankName?.value?.trim();
        if (type === 'gcash') return 'GCash';
        if (type === 'maya') return 'Maya';
        if (type === 'card') return document.getElementById('terminal_provider')?.value?.trim() || '';
        if (type === 'other') return document.getElementById('payment_channel_other')?.value?.trim() || '';
        return '';
    }
    function identifierCheckPayload(field, identifierType) {
        const selectedCase = caseSelect?.options[caseSelect.selectedIndex];
        return {
            branch_id: selectedCase?.dataset.branchId || '',
            payment_method: paymentMethod?.value || '',
            identifier_type: identifierType,
            identifier: field?.value?.trim() || '',
            cashless_type: cashlessType?.value || '',
            provider: identifierProvider(),
        };
    }
    async function checkIdentifier(field, identifierType) {
        if (!field || field.disabled || !field.value.trim()) {
            if (field?.dataset.identifierDuplicate === '1') clearFieldError(field);
            if (field) delete field.dataset.identifierDuplicate;
            return false;
        }
        const value = field.value.trim();
        const payload = identifierCheckPayload(field, identifierType);
        if (!payload.branch_id || (identifierType !== 'receipt_or_no' && (!payload.cashless_type || payload.payment_method !== 'cashless'))) return false;
        const state = identifierCheckState.get(field) || { sequence: 0, timer: null };
        const sequence = ++state.sequence;
        if (state.timer) clearTimeout(state.timer);
        identifierCheckState.set(field, state);
        try {
            const response = await fetch(identifierCheckUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]')?.value || '',
                },
                body: JSON.stringify(payload),
                credentials: 'same-origin',
            });
            if (!response.ok) return false;
            const result = await response.json();
            if (identifierCheckState.get(field)?.sequence !== sequence || field.value.trim() !== value) return false;
            if (result.duplicate) {
                field.dataset.identifierDuplicate = '1';
                showFieldError(field, result.message || 'This number is already used by another payment in this branch.', false);
                return true;
            }
            if (field.dataset.identifierDuplicate === '1') clearFieldError(field);
            delete field.dataset.identifierDuplicate;
            return false;
        } catch (error) {
            // Final server-side validation remains authoritative if live lookup is unavailable.
            return false;
        }
    }
    function scheduleIdentifierCheck(field, identifierType) {
        if (!field) return;
        const state = identifierCheckState.get(field) || { sequence: 0, timer: null };
        if (state.timer) clearTimeout(state.timer);
        state.sequence += 1;
        identifierCheckState.set(field, state);
        if (field.dataset.identifierDuplicate === '1') clearFieldError(field);
        delete field.dataset.identifierDuplicate;
        state.timer = setTimeout(() => checkIdentifier(field, identifierType), 450);
    }
    async function checkPaymentIdentifiers() {
        const checks = [];
        if (receiptInput && !receiptInput.disabled && receiptInput.value.trim()) checks.push([receiptInput, 'receipt_or_no']);
        if (paymentMethod?.value === 'cashless') {
            if (referenceNo && !referenceNo.disabled && referenceNo.value.trim()) checks.push([referenceNo, 'reference_number']);
            if (cashlessType?.value === 'card' && approvalCode && !approvalCode.disabled && approvalCode.value.trim()) checks.push([approvalCode, 'approval_code']);
        }
        const results = await Promise.all(checks.map(([field, type]) => checkIdentifier(field, type)));
        const duplicateIndex = results.findIndex(Boolean);
        if (duplicateIndex >= 0) checks[duplicateIndex][0]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return duplicateIndex >= 0;
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
        if (!field || field.disabled) return true;
        const value = (field?.value || '').trim();
        if (!value) {
            if (field.required) return fail(field, 'Enter the Receipt / OR number for this cash payment.');
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
            referenceNo,
            approvalCode,
            ...document.querySelectorAll('[data-account-name-field], [data-mobile-number-field], [data-payment-channel-field]'),
        ].forEach(clearFieldError);
    }

    function validateCashlessDetails() {
        updatePaymentMethodFields();
        clearCashlessValidity();

        if (!validateReceiptValue(receiptInput)) return false;
        if (paymentMethod?.value !== 'cashless') return window.validatePaymentProof?.(form) ?? false;

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
            if (!validateAccountNameValue(activeAccount)) return false;
            if (!validateReferenceValue(referenceNo, true)) return false;
        }
        if (type === 'gcash' || type === 'maya') {
            if (!validateAccountNameValue(activeAccount)) return false;
            if (!validateMobileValue(activeMobile)) return false;
            if (!validateReferenceValue(referenceNo, true)) return false;
        }
        if (type === 'card' && !approval && !ref) {
            return fail(approvalCode, 'Enter an approval code or a transaction reference number.');
        }
        if (type === 'card' && approval && !validateApprovalValue(approvalCode)) return false;
        if (type === 'card' && ref && !validateReferenceValue(referenceNo, false)) return false;
        if (type === 'other') {
            if (!validateChannelValue(activeChannel)) return false;
            if (!validateReferenceValue(referenceNo, true)) return false;
        }
        if (!validateReceiptValue(receiptInput)) return false;

        return window.validatePaymentProof?.(form) ?? false;
    }

    if (caseSelect) caseSelect.addEventListener('change', () => {
        if (fromDashboardSearch) {
            if (amountInput) amountInput.value = '';
        } else {
            fillAmountFromSelectedCase();
        }
        updateSummary();
        if (receiptInput?.value.trim()) scheduleIdentifierCheck(receiptInput, 'receipt_or_no');
        if (referenceNo?.value.trim()) scheduleIdentifierCheck(referenceNo, 'reference_number');
        if (approvalCode?.value.trim()) scheduleIdentifierCheck(approvalCode, 'approval_code');
    });
    if (amountInput) amountInput.addEventListener('input', updateSummary);
    if (paymentMethod) paymentMethod.addEventListener('change', () => {
        updatePaymentMethodFields();
        if (receiptInput?.value.trim()) scheduleIdentifierCheck(receiptInput, 'receipt_or_no');
    });
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
            if (referenceNo?.value.trim()) scheduleIdentifierCheck(referenceNo, 'reference_number');
            if (approvalCode?.value.trim()) scheduleIdentifierCheck(approvalCode, 'approval_code');
            if (paymentMethod?.value === 'cashless' && cashlessType.value && cashlessFields) {
                requestAnimationFrame(() => {
                    cashlessFields.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            }
        });
    }
    if (bankName) bankName.addEventListener('change', () => {
        updatePaymentMethodFields();
        if (referenceNo?.value.trim()) scheduleIdentifierCheck(referenceNo, 'reference_number');
    });
    otherBankName?.addEventListener('input', () => {
        clearFieldError(otherBankName);
        if (referenceNo?.value.trim()) scheduleIdentifierCheck(referenceNo, 'reference_number');
    });
    otherBankName?.addEventListener('blur', () => {
        if (bankName?.value === 'Other Bank') validateOtherBankValue(otherBankName);
    });
    if (referenceNo) {
        referenceNo.addEventListener('input', () => {
            clearFieldError(referenceNo);
            updatePaymentMethodFields();
            scheduleIdentifierCheck(referenceNo, 'reference_number');
        });
        referenceNo.addEventListener('blur', () => {
            validateReferenceValue(referenceNo, false);
            checkIdentifier(referenceNo, 'reference_number');
        });
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
        scheduleIdentifierCheck(receiptInput, 'receipt_or_no');
    });
    receiptInput?.addEventListener('blur', () => {
        if (validateReceiptValue(receiptInput)) checkIdentifier(receiptInput, 'receipt_or_no');
    });
    if (approvalCode) {
        approvalCode.addEventListener('input', () => scheduleIdentifierCheck(approvalCode, 'approval_code'));
        approvalCode.addEventListener('blur', () => {
            if (validateApprovalValue(approvalCode)) checkIdentifier(approvalCode, 'approval_code');
        });
    }
    document.getElementById('terminal_provider')?.addEventListener('input', () => {
        if (referenceNo?.value.trim()) scheduleIdentifierCheck(referenceNo, 'reference_number');
        if (approvalCode?.value.trim()) scheduleIdentifierCheck(approvalCode, 'approval_code');
    });
    document.getElementById('payment_channel_other')?.addEventListener('input', () => {
        if (referenceNo?.value.trim()) scheduleIdentifierCheck(referenceNo, 'reference_number');
    });
    backButton?.addEventListener('click', () => goToStep(currentStep - 1, true));
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
            updatePaymentMethodFields();
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
    let identifierSubmitBypass = false;
    form?.addEventListener('invalid', () => { identifierSubmitBypass = false; }, true);
    form?.addEventListener('submit', async (event) => {
        if (identifierSubmitBypass) {
            identifierSubmitBypass = false;
            return;
        }
        event.preventDefault();
        if (await checkPaymentIdentifiers()) return;
        identifierSubmitBypass = true;
        form.requestSubmit(submitBtn);
    }, true);
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
    buildWizard();
    const renderCaseChoice = (option, query) => {
        const text = (className, value) => {
            const part = document.createElement('span');
            part.className = className;
            const match = query ? value.toLowerCase().indexOf(query) : -1;
            if (match < 0) part.textContent = value;
            else {
                const mark = document.createElement('mark');
                mark.textContent = value.slice(match, match + query.length);
                part.append(value.slice(0, match), mark, value.slice(match + query.length));
            }
            return part;
        };
        const person = (label, value) => {
            const personName = document.createElement('span');
            const caption = document.createElement('small');
            caption.textContent = label;
            personName.append(caption, text('', value));
            return personName;
        };
        const top = text('pf-case-option-top', '');
        const balance = person('Balance', '₱' + fmt(toNum(option.dataset.balance)));
        balance.className = 'pf-case-option-balance';
        top.append(text('pf-case-option-code', option.dataset.caseCode), balance);
        const names = text('pf-case-option-names', '');
        names.append(person('Client', option.dataset.client), person('Deceased', option.dataset.deceased));
        const choice = document.createDocumentFragment();
        choice.append(top, names);
        return choice;
    };
    const casePicker = window.bindServiceFieldSelection?.(
        caseSelect,
        'Choose a case with remaining balance',
        document.querySelector('#paymentFormModal .payment-modal-sheet'),
        { renderChoice: renderCaseChoice, focusSearch: !matchMedia('(pointer: coarse)').matches, worklistSearch: true }
    );
    if (casePicker) {
        const { trigger, panel } = casePicker;
        const caseError = document.getElementById('pf_case_picker_error');
        trigger.id = 'pf_case_picker_trigger';
        trigger.firstElementChild.id = 'pf_case_picker_value';
        trigger.dataset.paymentCaseTrigger = '';
        document.getElementById('pf_case_picker_label').htmlFor = trigger.id;
        trigger.setAttribute('aria-labelledby', 'pf_case_picker_label pf_case_picker_value');
        trigger.setAttribute('aria-haspopup', 'dialog');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Choose a funeral case');
        trigger.setAttribute('aria-describedby', 'pf_case_help pf_case_picker_error');
        if (!caseError.hidden) trigger.setAttribute('aria-invalid', 'true');
        caseSelect.parentElement.querySelector('.pf-control-icon')?.setAttribute('hidden', '');
        caseSelect.addEventListener('invalid', event => {
            event.preventDefault();
            caseError.hidden = false;
            trigger.setAttribute('aria-invalid', 'true');
        });
        caseSelect.addEventListener('change', () => {
            caseError.hidden = true;
            trigger.removeAttribute('aria-invalid');
        });
    }
    const serverErrorFields = @json(array_keys($errors->toArray()));
    if (serverErrorFields.length && !serverErrorFields.some((field) => ['funeral_case_id', 'amount_paid', 'payment'].includes(field))) {
        goToStep(2);
    }
    updateSummary();
    updatePaymentMethodFields();
})();
</script>
