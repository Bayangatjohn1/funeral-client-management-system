<style>
    html.payment-modal-open,
    html.payment-modal-open body,
    html.payment-modal-open .panel-shell-body { overflow: hidden !important; }

    #paymentFormModal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 2500 !important;
        width: 100vw;
        height: 100dvh;
        overflow: hidden;
        background: #C6D4BE;
        isolation: isolate;
        padding: 0 !important;
    }
    #paymentFormBackdrop {
        position: fixed !important;
        inset: 0 !important;
        width: 100vw;
        height: 100dvh;
        background: #C6D4BE !important;
    }
    #paymentFormModal .payment-modal-viewport {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 18px;
        overflow: hidden;
        background: #C6D4BE;
    }
    #paymentFormModal .payment-modal-sheet {
        width: min(1120px, 100%);
        max-height: calc(100dvh - 80px);
        margin: 0 auto;
        overflow-y: auto;
        border: 1px solid #AEBBA8;
        border-radius: 20px;
        background: #D3DEC9;
        box-shadow: none !important;
    }
    #paymentFormModal .payment-modal-head {
        border-bottom: 1px solid #AEBBA8;
        background: #C7D5BE;
    }
    #paymentFormModal .payment-modal-icon { background: #3E4A3D; color: #ffffff; }
    #paymentFormModal .payment-modal-title { color: #232821; }
    #paymentFormModal .payment-modal-copy { color: #3F4C3E; }
    #paymentFormModal .payment-modal-close { cursor: pointer; box-shadow: none !important; }
    #paymentFormModal .payment-modal-close:hover { background: #C7D5BE !important; color: var(--ink) !important; }
    @media (max-width: 640px) {
        #paymentFormModal .payment-modal-viewport { padding: 16px 10px; }
        #paymentFormModal .payment-modal-sheet { max-height: calc(100dvh - 32px); }
    }
</style>

@if($errors->any())
    <div class="flash-error">{{ $errors->first() }}</div>
@endif

<div id="paymentFormModal" class="fixed inset-0 z-40 hidden panel-overlay-content" aria-hidden="true">
    <div class="absolute inset-0" id="paymentFormBackdrop"></div>
    <div class="payment-modal-viewport">
        <div class="payment-modal-sheet rounded-2xl overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="paymentFormTitle">
            <div class="payment-modal-head flex items-center justify-between px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="payment-modal-icon w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-cash-stack text-base" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div id="paymentFormTitle" class="payment-modal-title text-sm font-bold">Record Payment</div>
                        <div class="payment-modal-copy text-xs mt-0.5">Record a received payment, review the updated package balance, and confirm before saving.</div>
                    </div>
                </div>
                <button type="button" id="closePaymentFormTop" class="payment-modal-close inline-flex items-center justify-center w-9 h-9 rounded-xl transition-colors focus:outline-none" aria-label="Close payment form">
                    <i class="bi bi-x-lg text-[.8rem]" aria-hidden="true"></i>
                </button>
            </div>
            <div class="p-6">
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

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('opacity-100'));
        document.documentElement.classList.add('payment-modal-open');
        document.body.classList.add('overflow-hidden');
        window.setTimeout(() => caseSelect?.focus(), 50);
    };
    const closeModal = () => {
        modal.classList.remove('opacity-100');
        modal.setAttribute('aria-hidden', 'true');
        window.setTimeout(() => {
            modal.classList.add('hidden');
            document.documentElement.classList.remove('payment-modal-open');
            document.body.classList.remove('overflow-hidden');
            openButton.focus();
        }, 190);
    };

    openButton.addEventListener('click', openModal);
    backdrop?.addEventListener('click', closeModal);
    closeTop?.addEventListener('click', closeModal);
    closeBottom?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
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
        window.history.replaceState({}, '', cleanUrl.toString());
    } else if (@json($errors->any())) {
        openModal();
    }
});
</script>
