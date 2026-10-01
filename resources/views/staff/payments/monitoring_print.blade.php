<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Monitoring Print Preview</title>
    <style>
        @page{size:A4 landscape;margin:9mm}
        *{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#172019;margin:24px;font-size:10px}.report{width:min(270mm,100%);margin:0 auto}.actions{margin-bottom:14px}.actions button{padding:9px 15px;border:1px solid #31553a;border-radius:6px;background:#31553a;color:#fff;font-weight:700}.report-heading{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:18px;padding:0 0 10px;border-bottom:3px solid #31553a}.brand{display:flex;align-items:center;gap:9px}.brand img{width:34px;height:34px;object-fit:contain}.brand strong{display:block;font-size:12px}.brand span,.report-generated span{display:block;margin-top:2px;color:#667168;font-size:8px}.report-title{text-align:center}.report-title h1{margin:0;font-size:18px;letter-spacing:.02em;text-transform:uppercase}.report-title span{display:block;margin-top:3px;color:#667168;font-size:8px}.report-generated{text-align:right;white-space:nowrap}.report-generated strong{display:block;font-size:10px}.report-meta{width:100%;border-collapse:collapse;margin:8px 0 11px;border-bottom:1px solid #aeb9ab}.report-meta th,.report-meta td{padding:4px 6px;vertical-align:top}.report-meta th{color:#667168;font-size:7.5px;text-transform:uppercase;white-space:nowrap}.report-meta td{font-size:9px;font-weight:700}.data-table{width:100%;table-layout:fixed;border-collapse:collapse;border-top:2px solid #31553a}.data-table th{padding:6px 5px;border-bottom:1px solid #31553a;color:#31553a;font-size:7.5px;text-transform:uppercase;letter-spacing:.025em;text-align:left}.data-table td{padding:6px 5px;border-bottom:1px solid #cfd7cc;vertical-align:top;line-height:1.2}.num{text-align:right!important;white-space:nowrap;font-variant-numeric:tabular-nums}.name-cell strong{display:block;font-size:9.5px}.name-cell span{display:block;margin-top:2px;color:#667168;font-size:8px}.last-payment,.status{white-space:nowrap}.status{font-weight:800;font-size:8px;letter-spacing:0;text-transform:uppercase}.report-summary{width:52%;margin:12px 0 0 auto;border-collapse:collapse;border-top:2px solid #31553a}.report-summary th,.report-summary td{padding:5px 6px;border-bottom:1px solid #cfd7cc}.report-summary th{color:#566258;font-size:8px;text-transform:uppercase;text-align:left}.report-summary td{text-align:right;font-size:10px;font-weight:800;white-space:nowrap}.report-summary tr:last-child th,.report-summary tr:last-child td{border-bottom:3px double #31553a}.report-note{margin-top:8px;color:#667168;font-size:7.5px;text-align:right}@media print{body{margin:0}.report{width:100%}.actions{display:none}.data-table thead{display:table-header-group}.data-table tr,.report-heading,.report-meta,.report-summary{break-inside:avoid}}
    </style>
</head>
<body>
@php
    $selectedBranch = ($branches ?? collect())->first(fn ($branch) => (string) $branch->id === (string) ($selectedBranchId ?? ''));
    $branchLabel = ($selectedBranchId ?? null) === 'all' ? 'All Branches' : ($selectedBranch ? trim($selectedBranch->branch_code.' - '.$selectedBranch->branch_name) : ($assignedBranch ? trim($assignedBranch->branch_code.' - '.$assignedBranch->branch_name) : 'Authorized Branch Scope'));
    $filterParts = array_values(array_filter([
        $paymentStatus ? 'Payment: '.\Illuminate\Support\Str::headline($paymentStatus === 'WITH_BALANCE' ? 'with balance' : $paymentStatus) : null,
        $caseStatus ? 'Case: '.\Illuminate\Support\Str::headline($caseStatus) : null,
        $paymentMethod ? 'Method: '.\Illuminate\Support\Str::headline($paymentMethod) : null,
        $q ? 'Search: '.$q : null,
    ]));
    $periodLabel = $paidFrom || $paidTo ? (($paidFrom ? \Illuminate\Support\Carbon::parse($paidFrom)->format('M d, Y') : 'Beginning').' to '.($paidTo ? \Illuminate\Support\Carbon::parse($paidTo)->format('M d, Y') : 'Present')) : 'All Dates';
@endphp
    <main class="report">
    <div class="actions"><button type="button" onclick="window.print()">Print Report</button></div>
    <header class="report-heading">
        <div class="brand"><img src="{{ asset('images/login-logo.png') }}" alt=""><div><strong>Sabangan Caguioa</strong><span>Funeral Home System</span></div></div>
        <div class="report-title"><h1>Payment Monitoring</h1><span>Management Financial Report</span></div>
        <div class="report-generated"><strong>{{ now()->format('M d, Y') }}</strong><span>Generated {{ now()->format('h:i A') }}</span></div>
    </header>
    <table class="report-meta"><tbody>
        <tr><th>Branch Scope</th><td>{{ $branchLabel }}</td><th>Payment Period</th><td>{{ $periodLabel }}</td><th>Cases Shown</th><td>{{ number_format($paymentCases->count()) }}</td></tr>
        <tr><th>Applied Filters</th><td colspan="3">{{ $filterParts ? implode(' / ', $filterParts) : 'None' }}</td><th>Balance Basis</th><td>All valid payments per case</td></tr>
    </tbody></table>
    <table class="data-table">
        <colgroup><col style="width:8%"><col style="width:24%"><col style="width:14%"><col style="width:13%"><col style="width:17%"><col style="width:14%"><col style="width:10%"></colgroup>
        <thead><tr><th>Case No.</th><th>Name</th><th class="num">Amount Availed</th><th class="num">Total Paid</th><th>Last Payment Date</th><th class="num">Balance</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($paymentCases as $case)
            <tr>
                <td>{{ $case->case_code ?? '-' }}</td>
                <td class="name-cell"><strong>{{ $case->client?->full_name ?? '-' }}</strong><span class="muted">Deceased: {{ $case->deceased?->full_name ?? '-' }}</span></td>
                <td class="num">PHP {{ number_format((float) $case->total_amount, 2) }}</td>
                <td class="num">PHP {{ number_format((float) $case->total_paid, 2) }}</td>
                <td class="last-payment">{{ $case->payments_max_paid_at ? \Illuminate\Support\Carbon::parse($case->payments_max_paid_at)->format('M d, Y h:i A') : 'No payment yet' }}</td>
                <td class="num">PHP {{ number_format((float) $case->balance_amount, 2) }}</td>
                <td class="status">{{ \Illuminate\Support\Str::headline($case->payment_status ?? 'UNPAID') }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No cases match the selected monitoring scope.</td></tr>
        @endforelse
        </tbody>
    </table>
    <table class="report-summary"><tbody>
        <tr><th>Total Amount Availed</th><td>PHP {{ number_format((float) $totalAmountAvailed, 2) }}</td></tr>
        <tr><th>Total Collected{{ ($paidFrom || $paidTo) ? ' in Selected Period' : '' }}</th><td>PHP {{ number_format((float) $totalCollected, 2) }}</td></tr>
        <tr><th>{{ ($selectedBranchId ?? null) === 'all' ? 'Total Collectibles' : 'Branch Collectibles' }}</th><td>PHP {{ number_format((float) $totalOutstanding, 2) }}</td></tr>
    </tbody></table>
    <div class="report-note">Current balances use all valid payments recorded for each case.</div>
    </main>
</body>
</html>
