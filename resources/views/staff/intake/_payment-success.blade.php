@php
    $intakePayment = session('intake_payment');
    $paymentStatusLabel = session('summary.payment_status') === 'PAID' ? 'Fully paid' : 'Partially paid';
@endphp
<dialog id="intakePaymentSuccess" aria-labelledby="intakePaymentSuccessTitle" aria-describedby="intakePaymentSuccessDescription">
    <header class="ips-header">
        <span class="ips-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
        <div><h2 id="intakePaymentSuccessTitle">Case saved successfully</h2><p id="intakePaymentSuccessDescription">Your case and initial payment have been recorded.</p></div>
        <button type="button" class="ips-close" aria-label="Close payment summary"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </header>
    <div class="ips-content">
        <div class="ips-heading"><h3>Payment summary</h3><span class="ips-status">{{ $paymentStatusLabel }}</span></div>
        <p class="ips-record">{{ $intakePayment['record_no'] }} &middot; {{ $intakePayment['case_code'] }}</p>
        <div class="ips-amounts">
            <div><span>Payment received</span><strong>PHP {{ number_format($intakePayment['amount'], 2) }}</strong></div>
            <div><span>Remaining balance</span><strong>PHP {{ number_format($intakePayment['balance'], 2) }}</strong></div>
        </div>
        <dl class="ips-details">
            <div><dt>Client</dt><dd>{{ $intakePayment['client'] }}</dd></div>
            <div><dt>Deceased</dt><dd>{{ $intakePayment['deceased'] }}</dd></div>
            <div><dt>Package</dt><dd>{{ session('summary.package') }}</dd></div>
            <div><dt>Package total</dt><dd>PHP {{ number_format(session('summary.total'), 2) }}</dd></div>
            <div><dt>Payment method</dt><dd>{{ $intakePayment['method'] }}</dd></div>
            <div><dt>Received at</dt><dd>{{ $intakePayment['received_at'] }}</dd></div>
        </dl>
        <p class="ips-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Payment summary is a reference copy, not an Official Receipt.</p>
    </div>
    <footer class="ips-footer">
        <a id="intakePrintPaymentCopy" href="{{ route('payments.summary', $intakePayment['id']) }}" target="_blank" rel="noopener" class="ips-print"><i class="bi bi-printer" aria-hidden="true"></i> Print Payment Summary <span class="sr-only">(opens in a new tab)</span></a>
        <button type="button" id="intakePaymentDone" autofocus>Done</button>
    </footer>
</dialog>
<style>
#intakePaymentSuccess{width:min(560px,calc(100% - 32px));max-height:calc(100dvh - 40px);margin:auto;padding:0;overflow:auto;border:1px solid var(--staff-border,#dce3da);border-radius:18px;background:var(--staff-card,#fff);color:var(--staff-ink,#203329);box-shadow:0 24px 80px rgb(0 0 0 / .25)}
#intakePaymentSuccess::backdrop{background:var(--modal-scrim)}
/* Override the panel's global heading/paragraph colors with this dialog's theme tokens. */
#intakePaymentSuccess :is(h2,h3,dd,strong,.ips-status,.ips-close,#intakePaymentDone){color:var(--staff-ink,#203329) !important}
#intakePaymentSuccess :is(p,dt,.ips-amounts span){color:var(--staff-muted,#5a6b60) !important}
body:has(#intakePaymentSuccess[open]){overflow:hidden}
#intakePaymentSuccess .ips-header{display:flex;align-items:flex-start;gap:12px;padding:24px;border-bottom:1px solid var(--staff-border,#dce3da)}
#intakePaymentSuccess h2{margin:0;font-size:21px;font-weight:750;line-height:1.3}
#intakePaymentSuccess .ips-header p{margin:6px 0 0;font-size:13px;color:var(--staff-muted,#5a6b60)}
#intakePaymentSuccess .ips-icon{display:grid;place-items:center;flex:none;width:38px;height:38px;border-radius:50%;background:var(--staff-soft,#edf5ec);color:var(--staff-action,#31553a);font-size:22px}
#intakePaymentSuccess .ips-close{flex:none;margin-left:auto;width:34px;height:34px;border-radius:8px}
#intakePaymentSuccess .ips-content{padding:22px 24px}
#intakePaymentSuccess .ips-heading{display:flex;gap:12px;align-items:center;justify-content:space-between}
#intakePaymentSuccess h3{margin:0;font-size:16px;font-weight:750}
#intakePaymentSuccess .ips-status{padding:5px 10px;border-radius:20px;font-size:12px;font-weight:700;background:var(--staff-soft,#edf5ec)}
#intakePaymentSuccess .ips-record{margin:7px 0 18px;font-size:12px;color:var(--staff-muted,#5a6b60)}
#intakePaymentSuccess .ips-amounts{display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:16px;border:1px solid var(--staff-border,#dce3da);border-radius:10px;background:var(--staff-soft,#f4f7f1)}
#intakePaymentSuccess .ips-amounts span{display:block;font-size:12px;color:var(--staff-muted,#5a6b60)}
#intakePaymentSuccess .ips-amounts strong{display:block;margin-top:5px;font-size:21px;font-weight:750;overflow-wrap:anywhere}
#intakePaymentSuccess .ips-details{margin:18px 0}
#intakePaymentSuccess .ips-details>div{display:grid;grid-template-columns:130px 1fr;gap:16px;padding:7px 0;font-size:13px}
#intakePaymentSuccess dt{color:var(--staff-muted,#5a6b60)}
#intakePaymentSuccess dd{margin:0;font-weight:650;text-align:right;overflow-wrap:anywhere}
#intakePaymentSuccess .ips-note{display:flex;gap:8px;margin:0;font-size:12px;color:var(--staff-muted,#5a6b60)}
#intakePaymentSuccess .ips-footer{display:flex;gap:12px;padding:18px 24px;border-top:1px solid var(--staff-border,#dce3da)}
#intakePaymentSuccess .ips-footer :is(a,button){display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:10px 18px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;border:1px solid var(--staff-border,#dce3da)}
#intakePaymentSuccess .ips-print{flex:1;background:#31553a;color:#fff}
#intakePaymentSuccess :is(a,button):focus-visible{outline:2px solid var(--staff-action,#31553a);outline-offset:3px}
@media(max-width:480px){#intakePaymentSuccess .ips-header,#intakePaymentSuccess .ips-content,#intakePaymentSuccess .ips-footer{padding:18px}#intakePaymentSuccess .ips-amounts{grid-template-columns:1fr}#intakePaymentSuccess .ips-details>div{grid-template-columns:100px 1fr;gap:10px}#intakePaymentSuccess .ips-footer{flex-wrap:wrap}}
</style>
<script>
(() => {
    const modal = document.getElementById('intakePaymentSuccess');
    const previousFocus = document.activeElement;
    const confirmation = document.getElementById('intakeSavedConfirmation');
    const confirmationKey = 'intake-confirmation-{{ $intakePayment['id'] }}';
    modal.querySelector('.ips-close').addEventListener('click', () => modal.close());
    document.getElementById('intakePaymentDone').addEventListener('click', () => modal.close());
    modal.addEventListener('close', () => {
        modal.remove();
        if (confirmation) {
            sessionStorage.setItem(confirmationKey, '1');
            confirmation.showModal();
            return;
        }
        if (previousFocus && previousFocus !== document.body) previousFocus.focus();
        else document.querySelector('#intakeSavedDone, .records-page a, .records-page button')?.focus();
    });
    if (confirmation && sessionStorage.getItem(confirmationKey) === '1') {
        modal.remove();
        confirmation.showModal();
    } else modal.showModal();
})();
</script>
