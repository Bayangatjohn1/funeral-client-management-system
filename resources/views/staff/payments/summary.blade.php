@php
    $case = $payment->funeralCase;
    $packageName = $case?->package_name_snapshot
        ?: $case?->custom_package_name
        ?: $case?->package?->name
        ?: $case?->service_package
        ?: 'Not specified';
    $paidAt = $payment->paid_at ?? $payment->paid_date;
    $balanceAfter = (float) ($payment->balance_after_payment ?? $case?->balance_amount ?? 0);
    $paymentAmount = (float) $payment->amount;
    $previousBalance = $balanceAfter + $paymentAmount;
    $totalPackage = (float) ($case?->total_amount ?? 0);
    $totalPaidAfter = max($totalPackage - $balanceAfter, 0);
    $totalPaidBefore = max($totalPaidAfter - $paymentAmount, 0);
    $status = strtoupper((string) ($payment->payment_status_after_payment ?: ($balanceAfter <= 0 ? 'PAID' : 'PARTIAL')));
    $methodLabel = \App\Support\Payments\PaymentDetails::label($payment);
    $reference = $payment->approval_code ?: $payment->reference_number ?: $payment->transaction_reference_no;
    $recordedBy = $payment->recordedBy?->name ?: $payment->encodedBy?->name ?: 'Not provided';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Summary - {{ $payment->display_payment_record_no }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { color-scheme: light; }
        body { margin:0; background:#dbe4d4; color:#1d2a20; font-family:Inter,ui-sans-serif,system-ui,sans-serif; }
        .ps-page { width:min(148mm,calc(100% - 24px)); margin:20px auto; }
        .ps-actions { display:flex; justify-content:space-between; gap:12px; margin-bottom:16px; }
        .ps-btn { min-height:42px; display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:0 10px; border:1px solid #9dad97; border-radius:10px; background:#f5f7f1; color:#263226; font-size:11px; font-weight:750; text-decoration:none; cursor:pointer; }
        .ps-btn.primary { background:#31553a; border-color:#31553a; color:#fff; }
        .ps-sheet { background:#fff; border:1px solid #acbca5; border-radius:12px; overflow:hidden; box-shadow:0 12px 32px rgba(42,61,43,.1); }
        .ps-head { display:flex; justify-content:space-between; align-items:center; gap:14px; padding:12px 14px 10px; border-bottom:3px solid #31553a; }
        .ps-brand { display:flex; gap:9px; align-items:center; min-width:0; }
        .ps-brand img { width:34px; height:34px; object-fit:contain; border-radius:6px; }
        .ps-brand h1 { margin:0; font-size:17px; line-height:1.15; letter-spacing:-.015em; }
        .ps-brand p,.ps-doc-meta { margin:3px 0 0; color:#526055; font-size:9px; line-height:1.35; }
        .ps-doc-meta { flex:0 0 auto; text-align:right; }
        .ps-body { padding:9px 14px 6px; }
        .ps-note { display:flex; justify-content:space-between; gap:10px; align-items:center; margin-bottom:7px; color:#526055; font-size:8.5px; line-height:1.3; }
        .ps-copy { flex:0 0 auto; color:#526055; font-size:7.5px; font-weight:800; letter-spacing:.05em; text-transform:uppercase; }
        .ps-section { margin-top:7px; padding:7px 0 0; border:0; border-top:1px solid #aeb9ab; border-radius:0; background:transparent; break-inside:avoid; }
        .ps-section:first-of-type { margin-top:0; }
        .ps-section-head { display:flex; align-items:center; gap:7px; margin:0 0 6px; font-size:9px; font-weight:800; letter-spacing:.065em; text-transform:uppercase; color:#31553a; }
        .ps-step { display:inline; color:#31553a; font-size:9px; }
        .ps-step::after { content:"."; }
        .ps-payment { display:grid; grid-template-columns:1.05fr 1.95fr; align-items:center; padding:7px 0 0; overflow:visible; }
        .ps-payment-main { display:flex; flex-direction:column; justify-content:center; padding:0 12px 0 0; color:#1d2a20; }
        .ps-payment-main .ps-section-head { margin-bottom:3px; color:#31553a; }
        .ps-amount-label { color:#687269; font-size:8px; }
        .ps-amount { margin-top:1px; color:#19251b; font-size:15px; font-weight:850; letter-spacing:-.02em; white-space:nowrap; }
        .ps-payment-details { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1px 15px; padding:0 0 0 12px; border-left:1px solid #c3ccc0; align-content:center; }
        .ps-case-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 18px; }
        .ps-row { display:flex; justify-content:space-between; gap:8px; padding:1.5px 0; font-size:9px; line-height:1.3; }
        .ps-row span { flex:0 0 46%; color:#5c675e; }
        .ps-row strong { flex:1; color:#111811; text-align:right; overflow-wrap:anywhere; }
        .ps-account-flow { display:grid; grid-template-columns:1fr 18px 1fr 18px 1fr; align-items:center; margin-top:1px; }
        .ps-flow-item { padding:4px 7px; border-radius:0; background:transparent; border:0; border-bottom:2px solid #aeb9ab; }
        .ps-flow-item span { display:block; color:#687269; font-size:7.5px; line-height:1.2; }
        .ps-flow-item strong { display:block; margin-top:2px; color:#19251b; font-size:11px; white-space:nowrap; }
        .ps-flow-item.balance { background:transparent; border-color:#8c261f; }
        .ps-flow-item.balance strong { color:#8c261f; }
        .ps-flow-op { color:#7a867b; text-align:center; font-size:13px; font-weight:800; }
        .ps-account-meta { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-top:5px; padding-top:5px; border-top:1px dashed #b7c2b4; }
        .ps-balance { color:#8c261f !important; }
        .ps-status { display:inline-flex; padding:2px 6px; border-radius:999px; font-size:9px; font-weight:800; }
        .ps-status.paid { color:#176332; background:#dcefe0; }
        .ps-status.partial { color:#895313; background:#f8e9c9; }
        .ps-foot { padding:1px 14px 9px; color:#687269; font-size:7.8px; line-height:1.3; text-align:center; }
        @media screen and (max-width:560px){ .ps-page{width:calc(100% - 12px);margin:6px auto}.ps-actions{flex-direction:column}.ps-head{align-items:flex-start}.ps-payment{grid-template-columns:1fr}.ps-payment-details,.ps-case-grid{grid-template-columns:1fr}.ps-account-flow{grid-template-columns:1fr}.ps-flow-op{padding:2px;transform:rotate(90deg)}.ps-account-meta{grid-template-columns:1fr} }
        @page { size:148mm 105mm; margin:0; }
        @media print {
            html,body{width:148mm;height:105mm;min-height:0;margin:0;background:#fff;color:#000}
            body{overflow:hidden}
            .ps-page{width:148mm;height:105mm;margin:0}
            .ps-actions{display:none}
            /* Use the full receipt width. Compact print spacing below leaves room
               for wrapped names, addresses, and reference numbers. */
            *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .ps-sheet{width:148mm;height:105mm;border:0;border-radius:0;box-shadow:none;overflow:hidden;break-inside:avoid-page;page-break-inside:avoid}
            .ps-head{padding:5mm 6mm 2.5mm;break-inside:avoid-page;page-break-inside:avoid}
            .ps-brand img{width:28px;height:28px}
            .ps-brand h1{font-size:15px;line-height:1.1}
            .ps-brand p,.ps-doc-meta{font-size:8.5px;line-height:1.2}
            .ps-body{padding:2.5mm 6mm 1mm}
            .ps-note{justify-content:flex-end;margin-bottom:5px;font-size:8px}
            .ps-note>span:first-child{display:none}
            .ps-copy{padding:0;font-size:7.5px}
            .ps-section{margin-top:5px;padding:5px 0 0;border-radius:0}
            .ps-payment{padding:5px 0 0}
            .ps-payment-main{padding:0 10px 0 0}
            .ps-payment-details{padding:0 0 0 10px;gap:0 14px}
            .ps-step{font-size:9px}
            .ps-section-head{gap:5px;margin-bottom:4px;font-size:9px;line-height:1.15}
            .ps-amount-label{font-size:8.5px}
            .ps-amount{font-size:16px}
            .ps-row{padding:1px 0;font-size:9.5px;line-height:1.2}
            .ps-row span{color:#222}
            .ps-row strong{color:#000}
            .ps-status{padding:1px 5px;font-size:8px}
            .ps-account-flow{grid-template-columns:1fr 16px 1fr 16px 1fr}
            .ps-flow-item{padding:4px 6px}
            .ps-flow-item span{font-size:8px}
            .ps-flow-item strong{font-size:10.5px}
            .ps-flow-op{font-size:11px}
            .ps-account-meta{gap:10px;margin-top:4px;padding-top:4px}
            .ps-foot{padding:1mm 6mm 0;font-size:7.5px;line-height:1.2;break-inside:avoid-page;page-break-inside:avoid}
        }
    </style>
</head>
<body>
<main class="ps-page">
    <div class="ps-actions">
        <a class="ps-btn" href="{{ route('payments.history') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Payment Monitoring</a>
        <button class="ps-btn primary" type="button" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Print Payment Summary</button>
    </div>
    <article class="ps-sheet">
        <header class="ps-head">
            <div class="ps-brand">
                <img src="{{ asset('images/login-logo.png') }}" alt="">
                <div><h1>Payment Summary</h1><p>{{ $case?->branch?->branch_name ?? 'Funeral Service Branch' }}@if($case?->branch?->address) <span aria-hidden="true">&middot;</span> {{ $case->branch->address }}@endif</p></div>
            </div>
            <div class="ps-doc-meta"><strong>{{ $payment->display_payment_record_no ?? 'Payment record' }}</strong><br>Generated {{ now()->format('M d, Y h:i A') }}</div>
        </header>
        <div class="ps-body">
            <div class="ps-note">
                <span>Follow the numbered sections to review this payment and the updated account balance.</span>
                <span class="ps-copy">Reference copy · Not an Official Receipt</span>
            </div>

            <section class="ps-section ps-payment">
                <div class="ps-payment-main">
                    <h2 class="ps-section-head"><span class="ps-step">1</span> Payment received</h2>
                    <span class="ps-amount-label">Amount recorded</span>
                    <strong class="ps-amount">PHP {{ number_format($paymentAmount,2) }}</strong>
                </div>
                <div class="ps-payment-details">
                    <div class="ps-row"><span>Method</span><strong>{{ $methodLabel }}</strong></div>
                    <div class="ps-row"><span>Date &amp; time received</span><strong>{{ $paidAt?->format('M d, Y h:i A') ?? 'Not provided' }}</strong></div>
                    @if($reference)<div class="ps-row"><span>Transaction ref.</span><strong>{{ $reference }}</strong></div>@endif
                    <div class="ps-row"><span>OR reference</span><strong>{{ $payment->receipt_or_no ?: 'Not recorded' }}</strong></div>
                    <div class="ps-row"><span>Recorded by</span><strong>{{ $recordedBy }}</strong></div>
                    <div class="ps-row"><span>Recorded on</span><strong>{{ $payment->created_at?->format('M d, Y h:i A') ?? 'Not provided' }}</strong></div>
                </div>
            </section>

            <section class="ps-section">
                <h2 class="ps-section-head"><span class="ps-step">2</span> Payment applied to this case</h2>
                <div class="ps-case-grid">
                    <div class="ps-row"><span>Case code</span><strong>{{ $case?->case_code ?? 'Not provided' }}</strong></div>
                    <div class="ps-row"><span>Package</span><strong>{{ $packageName }}</strong></div>
                    <div class="ps-row"><span>Deceased</span><strong>{{ $case?->deceased?->full_name ?? 'Not provided' }}</strong></div>
                    <div class="ps-row"><span>Client</span><strong>{{ $case?->client?->full_name ?? 'Not provided' }}</strong></div>
                </div>
            </section>

            <section class="ps-section">
                <h2 class="ps-section-head"><span class="ps-step">3</span> Updated account balance</h2>
                <div class="ps-account-flow" aria-label="Previous balance minus payment received equals remaining balance">
                    <div class="ps-flow-item"><span>Previous balance</span><strong>PHP {{ number_format($previousBalance,2) }}</strong></div>
                    <div class="ps-flow-op" aria-hidden="true">−</div>
                    <div class="ps-flow-item"><span>Payment received</span><strong>PHP {{ number_format($paymentAmount,2) }}</strong></div>
                    <div class="ps-flow-op" aria-hidden="true">=</div>
                    <div class="ps-flow-item balance"><span>Remaining balance</span><strong>PHP {{ number_format($balanceAfter,2) }}</strong></div>
                </div>
                <div class="ps-account-meta">
                    <div class="ps-row"><span>Package total</span><strong>PHP {{ number_format($totalPackage,2) }}</strong></div>
                    <div class="ps-row"><span>Updated total paid</span><strong>PHP {{ number_format($totalPaidAfter,2) }}</strong></div>
                    <div class="ps-row"><span>Status</span><strong><span class="ps-status {{ $status === 'PAID' ? 'paid' : 'partial' }}">{{ $status === 'PAID' ? 'Fully Paid' : 'Partially Paid' }}</span></strong></div>
                </div>
            </section>
        </div>
        <footer class="ps-foot">This document is for reference only and is not an Official Receipt. For an Official Receipt, refer to the OR number above or the separately issued receipt.</footer>
    </article>
</main>
</body>
</html>
