@php
    $historyTotalPaid = (float) $funeralCase->payments->sum('amount');
    $historyRemainingBalance = max(0, (float) $funeralCase->total_amount - $historyTotalPaid);
    $historyStatus = $historyRemainingBalance <= 0 ? 'PAID' : ($historyTotalPaid > 0 ? 'PARTIAL' : 'UNPAID');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment History - {{ $funeralCase->case_code }}</title>
    <style>
        @page{size:A4 portrait;margin:16mm}
        *{box-sizing:border-box}body{margin:24px;color:#172019;font-family:Arial,sans-serif;font-size:11px}.report{width:min(178mm,100%);margin:0 auto}.actions{margin-bottom:14px}.actions button{padding:9px 15px;border:1px solid #31553a;border-radius:6px;background:#31553a;color:#fff;font-weight:700}.report-heading{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:14px;padding-bottom:11px;border-bottom:3px solid #31553a}.brand{display:flex;align-items:center;gap:9px}.brand img{width:40px;height:40px;object-fit:contain}.brand strong{display:block;font-size:12px}.brand span,.generated span{display:block;margin-top:2px;color:#667168;font-size:8.5px}.title{text-align:center}.title h1{margin:0;font-size:17px;letter-spacing:.02em;text-transform:uppercase}.title span{display:block;margin-top:3px;color:#667168;font-size:8.5px}.generated{text-align:right;white-space:nowrap}.generated strong{display:block;font-size:10px}.case-meta{width:100%;border-collapse:collapse;margin:9px 0 12px;border-bottom:1px solid #aeb9ab}.case-meta th,.case-meta td{padding:5px;vertical-align:top}.case-meta th{color:#667168;font-size:8px;text-transform:uppercase;white-space:nowrap}.case-meta td{font-size:9.5px;font-weight:700}.account-line{display:grid;grid-template-columns:repeat(4,1fr);gap:0;margin-bottom:14px;border-top:2px solid #31553a;border-bottom:1px solid #aeb9ab}.account-item{padding:8px 7px;border-right:1px solid #d5ddd2}.account-item:last-child{border-right:0}.account-item span{display:block;color:#667168;font-size:8px;text-transform:uppercase}.account-item strong{display:block;margin-top:3px;font-size:11px;white-space:nowrap}.account-item.balance strong{color:#8c261f}.status{font-weight:800}.history-title{margin:0 0 6px;color:#31553a;font-size:10px;text-transform:uppercase;letter-spacing:.04em}.history-table{width:100%;table-layout:fixed;border-collapse:collapse;border-top:2px solid #31553a}.history-table th{padding:7px 5px;border-bottom:1px solid #31553a;color:#31553a;font-size:8px;text-transform:uppercase;text-align:left}.history-table td{padding:8px 5px;border-bottom:1px solid #cfd7cc;vertical-align:top;font-size:9px;line-height:1.25}.num{text-align:right!important;white-space:nowrap;font-variant-numeric:tabular-nums}.record{font-weight:800}.sub{display:block;margin-top:2px;color:#667168;font-size:8px}.summary{width:66%;margin:15px 0 0 auto;border-collapse:collapse;border-top:2px solid #31553a}.summary th,.summary td{padding:6px;border-bottom:1px solid #cfd7cc}.summary th{color:#566258;font-size:8.5px;text-transform:uppercase;text-align:left}.summary td{text-align:right;font-size:10.5px;font-weight:800;white-space:nowrap}.summary tr:last-child th,.summary tr:last-child td{border-bottom:3px double #31553a}.note{margin-top:9px;color:#667168;font-size:8px;text-align:right}@media print{body{margin:0}.report{width:100%}.actions{display:none}.history-table thead{display:table-header-group}.history-table tr,.report-heading,.case-meta,.account-line,.summary{break-inside:avoid}}
    </style>
</head>
<body>
<main class="report">
    <div class="actions"><button type="button" onclick="window.print()">Print Payment History</button></div>
    <header class="report-heading">
        <div class="brand"><img src="{{ asset('images/login-logo.png') }}" alt=""><div><strong>Sabangan Caguioa</strong><span>Funeral Home System</span></div></div>
        <div class="title"><h1>Payment History</h1><span>Case Account Statement</span></div>
        <div class="generated"><strong>{{ now()->format('M d, Y') }}</strong><span>Generated {{ now()->format('h:i A') }}</span></div>
    </header>

    <table class="case-meta"><tbody>
        <tr><th>Case Number</th><td>{{ $funeralCase->case_code }}</td><th>Branch</th><td>{{ $funeralCase->branch?->branch_code }} - {{ $funeralCase->branch?->branch_name }}</td></tr>
        <tr><th>Client</th><td>{{ $funeralCase->client?->full_name ?? 'Not provided' }}</td><th>Deceased</th><td>{{ $funeralCase->deceased?->full_name ?? 'Not provided' }}</td></tr>
    </tbody></table>

    <section class="account-line" aria-label="Current account summary">
        <div class="account-item"><span>Amount Availed</span><strong>PHP {{ number_format((float) $funeralCase->total_amount, 2) }}</strong></div>
        <div class="account-item"><span>Total Paid</span><strong>PHP {{ number_format($historyTotalPaid, 2) }}</strong></div>
        <div class="account-item balance"><span>Remaining Balance</span><strong>PHP {{ number_format($historyRemainingBalance, 2) }}</strong></div>
        <div class="account-item"><span>Payment Status</span><strong class="status">{{ \Illuminate\Support\Str::headline($historyStatus) }}</strong></div>
    </section>

    <h2 class="history-title">Valid Payment Transactions</h2>
    <table class="history-table">
        <colgroup><col style="width:18%"><col style="width:19%"><col style="width:15%"><col style="width:16%"><col style="width:16%"><col style="width:16%"></colgroup>
        <thead><tr><th>Payment Record</th><th>Date &amp; Time Received</th><th>Method</th><th class="num">Amount Paid</th><th class="num">Previous Balance</th><th class="num">Balance After</th></tr></thead>
        <tbody>
        @php $runningBalance = (float) $funeralCase->total_amount; @endphp
        @forelse($funeralCase->payments as $payment)
            @php
                $previousBalance = $runningBalance;
                $balanceAfter = max(0, $previousBalance - (float) $payment->amount);
                $runningBalance = $balanceAfter;
            @endphp
            <tr>
                <td><span class="record">{{ $payment->display_payment_record_no ?? 'Not provided' }}</span><span class="sub">Recorded by {{ $payment->recordedBy?->name ?? $payment->encodedBy?->name ?? 'Not provided' }}</span><span class="sub">Recorded on {{ $payment->created_at?->format('M d, Y h:i A') ?? 'Not provided' }}</span></td>
                <td>{{ ($payment->paid_at ?? $payment->paid_date)?->format('M d, Y') ?? 'Not provided' }}<span class="sub">{{ ($payment->paid_at ?? $payment->paid_date)?->format('h:i A') }}</span></td>
                <td>{{ \App\Support\Payments\PaymentDetails::label($payment) }}</td>
                <td class="num">PHP {{ number_format((float) $payment->amount, 2) }}</td>
                <td class="num">PHP {{ number_format($previousBalance, 2) }}</td>
                <td class="num">PHP {{ number_format($balanceAfter, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No valid payment transactions recorded.</td></tr>
        @endforelse
        </tbody>
    </table>

    <table class="summary"><tbody>
        <tr><th>Total Amount Availed</th><td>PHP {{ number_format((float) $funeralCase->total_amount, 2) }}</td></tr>
        <tr><th>Total Paid</th><td>PHP {{ number_format($historyTotalPaid, 2) }}</td></tr>
        <tr><th>Remaining Balance</th><td>PHP {{ number_format($historyRemainingBalance, 2) }}</td></tr>
    </tbody></table>
    <div class="note">Complete valid payment history. Voided transactions are excluded.</div>
</main>
</body>
</html>
