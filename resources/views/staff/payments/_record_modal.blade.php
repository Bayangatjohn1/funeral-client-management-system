@push('worklist-styles')
<style>
    html.payment-modal-open,
    html.payment-modal-open body,
    html.payment-modal-open .panel-shell-body { overflow: hidden !important; }

    #paymentFormModal {
        --pf-surface:#F4F8F5;
        --pf-soft:#E1ECE5;
        --pf-selected:#C9DFD2;
        --pf-border:#B6CBBE;
        --pf-ink:#102B20;
        --pf-muted:#496255;
        --pf-focus:#1B4332;
        --pf-primary:#1B4332;
        --pf-primary-hover:#123325;
        --pf-success:#137047;
        --pf-warning:#8A4B0B;
        --pf-cancel:#F8E5E4;
        --pf-cancel-hover:#EFD2D0;
        --pf-cancel-ink:#8A3537;
        --pf-error:#9F1C14;
        --pf-error-surface:#FFF2EF;
        position: fixed !important;
        inset: 0 !important;
        z-index: 2500 !important;
        width: 100vw;
        height: 100dvh;
        overflow: hidden;
        background: transparent;
        isolation: isolate;
        padding: 0 !important;
    }
    html[data-theme='dark'] #paymentFormModal {
        --pf-surface:#14261D;
        --pf-soft:#22392C;
        --pf-selected:#345542;
        --pf-border:#41634F;
        --pf-ink:#EAF2ED;
        --pf-muted:#BACCC0;
        --pf-focus:#80CDA6;
        --pf-primary:#205740;
        --pf-primary-hover:#184633;
        --pf-success:#8FD7B2;
        --pf-warning:#FCD38D;
        --pf-cancel:#42282D;
        --pf-cancel-hover:#563138;
        --pf-cancel-ink:#FECACA;
        --pf-error:#FECACA;
        --pf-error-surface:#42282D;
    }
    #paymentFormBackdrop {
        position: fixed !important;
        inset: 0 !important;
        width: 100vw;
        height: 100dvh;
        background: var(--modal-scrim) !important;
        opacity:0;
        transition:opacity 180ms ease;
    }
    #paymentFormModal .payment-modal-viewport {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 18px;
        overflow: hidden;
        background: transparent;
    }
    #paymentFormModal .payment-modal-sheet {
        display:flex;
        flex-direction:column;
        width: min(960px, 100%);
        height:min(720px, 100%);
        margin: 0 auto;
        overflow:hidden;
        border:0;
        border-radius:16px;
        background:var(--pf-surface) !important;
        color:var(--pf-ink);
        box-shadow:0 14px 40px rgb(8 29 19 / .18) !important;
        opacity:0;
        transform:translateY(8px);
        transition:opacity 180ms ease, transform 180ms ease;
    }
    #paymentFormModal.opacity-100 :is(#paymentFormBackdrop,.payment-modal-sheet) { opacity:1; transform:none; }
    #paymentFormModal .payment-modal-head {
        flex:none;
        padding:14px clamp(14px,3vw,24px);
        border-bottom:1px solid var(--pf-border);
        background:var(--pf-surface);
    }
    #paymentFormModal .payment-modal-body { flex:1; min-height:0; overflow:auto; }
    #paymentFormModal .payment-modal-body:has(.pf-wizard) { display:flex; overflow:hidden; }
    #paymentFormModal .payment-modal-icon { background:var(--pf-soft); color:var(--pf-ink); }
    #paymentFormModal .payment-modal-title { color:var(--pf-ink); font-size:18px; font-weight:700; }
    #paymentFormModal .payment-modal-copy { color:var(--pf-muted); font-size:13px; }
    #paymentFormModal .payment-modal-close {
        flex:0 0 44px; height:44px; cursor:pointer; border:0 !important;
        background:transparent !important; color:var(--pf-ink) !important; box-shadow:none !important;
    }
    #paymentFormModal .payment-modal-close:hover { background:var(--pf-soft) !important; }
    #paymentFormModal :is(button,a):focus-visible { outline:2px solid var(--pf-focus); outline-offset:-2px; }
    @media (max-width: 640px) {
        #paymentFormModal .payment-modal-viewport { padding:10px; }
    }
    @media (prefers-reduced-motion: reduce) {
        #paymentFormModal :is(#paymentFormBackdrop,.payment-modal-sheet) { transition:none; transform:none; }
    }
</style>
@endpush

@if($errors->any())
    <div class="flash-error">{{ $errors->first() }}</div>
@endif

<div id="paymentFormModal" class="fixed inset-0 z-40 hidden panel-overlay-content" aria-hidden="true">
    <div class="absolute inset-0" id="paymentFormBackdrop"></div>
    <div class="payment-modal-viewport">
        <div class="payment-modal-sheet" role="dialog" aria-modal="true" aria-labelledby="paymentFormTitle">
            <div class="payment-modal-head flex items-center justify-between px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="payment-modal-icon w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-cash-stack text-base" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h2 id="paymentFormTitle" class="payment-modal-title">Record Payment</h2>
                        <p class="payment-modal-copy mt-0.5">Enter payment details and review the updated balance before saving.</p>
                    </div>
                </div>
                <button type="button" id="closePaymentFormTop" class="payment-modal-close inline-flex items-center justify-center w-9 h-9 rounded-xl transition-colors focus:outline-none" aria-label="Close payment form">
                    <i class="bi bi-x-lg text-[.8rem]" aria-hidden="true"></i>
                </button>
            </div>
            <div class="payment-modal-body">
                @include('staff.payments._form')
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const openButton = document.getElementById('openPaymentForm');
    const modal = document.getElementById('paymentFormModal');
    const backdrop = document.getElementById('paymentFormBackdrop');
    const closeTop = document.getElementById('closePaymentFormTop');
    const closeBottom = document.getElementById('closePaymentFormBottom');
    const caseSelect = document.getElementById('funeral_case_id');
    const preselectCaseId = @json($preselectCase->id ?? null);
    const autoOpenPayment = @json($autoOpenPayment ?? false);

    if (!openButton || !modal) return;
    if (modal.parentElement !== document.body) document.body.appendChild(modal);

    let closing = false;
    const openModal = () => {
        if (!modal.classList.contains('hidden')) return;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('payment-modal-open');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => {
            if (closing || modal.classList.contains('hidden')) return;
            modal.classList.add('opacity-100');
            const caseField = modal.querySelector?.('[data-payment-case-trigger]') ?? caseSelect;
            const focusTarget = caseField?.getClientRects?.().length ? caseField : closeTop;
            focusTarget?.focus({ preventScroll: true });
        });
    };
    const closeModal = () => {
        if (closing || modal.classList.contains('hidden')) return;
        closing = true;
        modal.querySelector?.('#funeral_case_id-options:popover-open')?.hidePopover();
        modal.classList.remove('opacity-100');
        window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.documentElement.classList.remove('payment-modal-open');
            document.body.classList.remove('overflow-hidden');
            closing = false;
            document.getElementById('openPaymentForm')?.focus();
        }, window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ? 0 : 180);
    };

    // The toolbar is replaced after filtering; keep its current trigger usable.
    document.addEventListener('click', event => {
        if (event.target.closest?.('#openPaymentForm')) openModal();
    });
    backdrop?.addEventListener('click', closeModal);
    const viewport = modal.querySelector?.('.payment-modal-viewport');
    viewport?.addEventListener('click', event => {
        if (event.target === viewport) closeModal();
    });
    closeTop?.addEventListener('click', closeModal);
    closeBottom?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (event) => {
        if (event.defaultPrevented || modal.classList.contains('hidden') || document.querySelector?.('dialog[open]')) return;
        if (event.key === 'Escape' && !modal.querySelector?.(':popover-open')) {
            event.preventDefault();
            closeModal();
        }
        if (event.key !== 'Tab') return;
        const controls = [...modal.querySelectorAll('button, a[href], input, select, textarea, [tabindex]')]
            .filter(control => !control.disabled && control.tabIndex >= 0 && control.getClientRects().length);
        const first = controls[0];
        const last = controls.at(-1);
        if (event.shiftKey ? document.activeElement === first : document.activeElement === last) {
            event.preventDefault();
            (event.shiftKey ? last : first)?.focus();
        }
    });

    if (caseSelect && preselectCaseId) {
        caseSelect.value = String(preselectCaseId);
        caseSelect.dispatchEvent(new Event('change'));
    }
    if (autoOpenPayment) {
        openModal();

        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('record_payment');
        cleanUrl.searchParams.delete('case_id');
        cleanUrl.searchParams.delete('dashboard_search');
        window.history.replaceState({}, '', cleanUrl.toString());
    } else if (@json($errors->any())) {
        openModal();
    }
});
</script>
