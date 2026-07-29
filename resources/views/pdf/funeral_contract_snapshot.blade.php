@php
    $snapshot = $contractSnapshot ?? [];
    $fmtDate = function ($date) {
        return $date ? \Carbon\Carbon::parse($date)->format('M d, Y') : '';
    };
    $fmtTime = function ($time) {
        return $time ? \Carbon\Carbon::parse($time)->format('h:i A') : '';
    };
    $fmtMoney = fn ($amount) => '&#8369; ' . number_format((float) $amount, 2);
    $branch = data_get($snapshot, 'branch', []);
    $client = data_get($snapshot, 'client', []);
    $deceased = data_get($snapshot, 'deceased', []);
    $package = data_get($snapshot, 'package', []);
    $schedule = data_get($snapshot, 'schedule', []);
    $totals = data_get($snapshot, 'totals', []);
    $basicInclusions = array_values(data_get($snapshot, 'basic_inclusions', []));
    $freebies = array_values(data_get($snapshot, 'freebies', []));
    $additionalServices = array_values(data_get($snapshot, 'additional_services', []));
    $includedCasket = data_get($snapshot, 'included_casket');
    $details = data_get($snapshot, 'details', []);
    $logoPath = public_path('images/login-logo.png');
    $logoExtension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
    $canRenderLogo = file_exists($logoPath) && ($logoExtension !== 'png' || extension_loaded('gd'));
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract - {{ data_get($snapshot, 'contract_number', $contractNumber) }}</title>
    <style>
        @page { margin: 20px 26px; }
        body { font-family: DejaVu Sans, sans-serif; color:#111; font-size:10px; line-height:1.25; }
        .sheet { border:1.3px solid #111; padding:11px 13px 13px; min-height:96%; }
        .header { width:100%; border-collapse:collapse; margin-bottom:5px; }
        .logo-cell { width:70px; vertical-align:top; }
        .logo { width:56px; height:56px; object-fit:contain; }
        .home { text-align:center; vertical-align:top; padding-right:70px; }
        .home-name { font-size:15px; font-weight:800; text-transform:uppercase; letter-spacing:.03em; }
        .home-line { font-size:9.2px; margin-top:2px; }
        .title { text-align:center; font-size:21px; font-weight:900; letter-spacing:.14em; margin:6px 0 8px; }
        .meta { width:100%; border-collapse:collapse; margin-bottom:7px; }
        .meta td { padding:2px 4px; vertical-align:bottom; }
        .label { font-size:7.8px; text-transform:uppercase; color:#333; letter-spacing:.04em; white-space:nowrap; }
        .line { border-bottom:1px solid #111; min-height:13px; font-weight:700; padding:0 4px 2px; }
        .section-title { background:#f1f1f1; border:1px solid #111; border-bottom:0; padding:5px 7px; font-size:9px; font-weight:900; text-transform:uppercase; letter-spacing:.04em; }
        .section { border:1px solid #111; margin-top:7px; }
        .two-col { width:100%; border-collapse:collapse; table-layout:fixed; }
        .two-col td { vertical-align:top; width:50%; }
        .two-col td + td { border-left:1px solid #111; }
        .box-body { padding:7px 8px; }
        .row { width:100%; border-collapse:collapse; margin-bottom:4px; }
        .check { width:13px; height:13px; border:1px solid #111; text-align:center; font-size:9px; line-height:11px; }
        .row-text { padding-left:6px; border-bottom:1px solid #d2d2d2; height:15px; }
        .small-note { color:#444; font-size:8.2px; margin-top:4px; }
        .extras { width:100%; border-collapse:collapse; }
        .extras th { font-size:7.8px; color:#333; text-transform:uppercase; border-bottom:1px solid #111; padding:4px 5px; text-align:left; }
        .extras th.price { text-align:right; width:86px; }
        .extras td { border-bottom:1px solid #d2d2d2; padding:4px 5px; height:16px; vertical-align:top; }
        .price { text-align:right; white-space:nowrap; }
        .summary-wrap { width:43%; margin:8px 0 7px auto; }
        .summary { width:100%; border-collapse:collapse; border:1px solid #111; }
        .summary th, .summary td { border-bottom:1px solid #111; padding:5px 7px; font-size:10.5px; }
        .summary th { text-align:left; text-transform:uppercase; }
        .summary td { text-align:right; font-weight:800; }
        .summary tr:last-child th, .summary tr:last-child td { border-bottom:0; }
        .schedule { width:100%; border-collapse:collapse; }
        .schedule td { padding:6px 7px; border-right:1px solid #111; vertical-align:bottom; }
        .schedule td:last-child { border-right:0; }
        .stipulations { padding:7px 9px 4px; font-size:8.3px; min-height:56px; }
        .stipulations ol { margin:0; padding-left:14px; }
        .stipulations li { margin-bottom:3px; }
        .remarks { min-height:38px; padding:7px 8px; }
        .signature { width:100%; border-collapse:collapse; margin-top:25px; }
        .signature td { width:33.33%; text-align:center; padding:0 18px; vertical-align:bottom; }
        .sig-line { border-top:1px solid #111; padding-top:5px; min-height:13px; font-weight:800; }
        .sig-label { font-size:7.8px; text-transform:uppercase; margin-top:2px; color:#333; }
        .foot { text-align:center; margin-top:8px; font-size:7.8px; color:#555; }
    </style>
</head>
<body>
    <div class="sheet">
        <table class="header">
            <tr>
                <td class="logo-cell">
                    @if($canRenderLogo)
                        <img class="logo" src="{{ $logoPath }}" alt="Logo">
                    @endif
                </td>
                <td class="home">
                    <div class="home-name">{{ data_get($branch, 'name') ?: 'Sabangan-Caguioa Funeral Services' }}</div>
                    <div class="home-line">{{ data_get($branch, 'address') }}</div>
                    <div class="home-line">{{ data_get($branch, 'contact_number') }}</div>
                </td>
            </tr>
        </table>

        <div class="title">CONTRACT</div>

        <table class="meta">
            <tr>
                <td width="13%"><span class="label">No.</span></td>
                <td width="37%"><div class="line">{{ data_get($snapshot, 'case.case_code') ?: data_get($snapshot, 'contract_number', $contractNumber) }}</div></td>
                <td width="12%"><span class="label">Date</span></td>
                <td width="38%"><div class="line">{{ $fmtDate(data_get($snapshot, 'contract_date')) }}</div></td>
            </tr>
            <tr>
                <td><span class="label">Name</span></td>
                <td><div class="line">{{ data_get($client, 'name') }}</div></td>
                <td><span class="label">Tel. No.</span></td>
                <td><div class="line">{{ data_get($client, 'contact_number') }}</div></td>
            </tr>
            <tr>
                <td><span class="label">Address</span></td>
                <td colspan="3"><div class="line">{{ data_get($client, 'address') }}</div></td>
            </tr>
            <tr>
                <td><span class="label">Re: Funeral Service Rendered The Late</span></td>
                <td colspan="3"><div class="line">{{ data_get($deceased, 'name') }}</div></td>
            </tr>
        </table>

        <table class="two-col">
            <tr>
                <td>
                    <div class="section-title">Basic Funeral Service Inclusions</div>
                    <div class="box-body">
                        <table class="row"><tr><td class="check">&#10003;</td><td class="row-text">{{ data_get($package, 'name') }} - {!! $fmtMoney(data_get($package, 'base_price')) !!}</td></tr></table>
                        @if($includedCasket)
                            <table class="row"><tr><td class="check">&#10003;</td><td class="row-text">Included Casket: {{ data_get($includedCasket, 'name') }}{{ data_get($includedCasket, 'material') ? ' (' . data_get($includedCasket, 'material') . ')' : '' }}</td></tr></table>
                        @endif
                        @foreach(array_slice($basicInclusions, 0, 9) as $item)
                            <table class="row"><tr><td class="check">&#10003;</td><td class="row-text">{{ $item }}</td></tr></table>
                        @endforeach
                        @foreach(array_slice($freebies, 0, 4) as $item)
                            <table class="row"><tr><td class="check">&#10003;</td><td class="row-text">Free: {{ $item }}</td></tr></table>
                        @endforeach
                        <div class="small-note">Services and freebies listed here are included in the base package price and are not charged again.</div>
                    </div>
                </td>
                <td>
                    <div class="section-title">Additional Services</div>
                    <table class="extras">
                        <tr><th>Description</th><th class="price">Amount</th></tr>
                        @foreach(array_slice($additionalServices, 0, 11) as $service)
                            <tr><td>{{ data_get($service, 'description') }}</td><td class="price">{!! $fmtMoney(data_get($service, 'amount')) !!}</td></tr>
                        @endforeach
                        @if(data_get($details, 'excess_kilometers'))
                            <tr><td>Excess kilometers: {{ data_get($details, 'excess_kilometers') }}</td><td class="price"></td></tr>
                        @endif
                        @if(data_get($details, 'extended_service_days'))
                            <tr><td>Extended service days: {{ data_get($details, 'extended_service_days') }}</td><td class="price"></td></tr>
                        @endif
                        @if(data_get($details, 'casket_upgrade'))
                            <tr><td>Casket upgrade: {{ data_get($details, 'casket_upgrade') }}</td><td class="price"></td></tr>
                        @endif
                        @for($i = count($additionalServices); $i < 11; $i++)
                            <tr><td>&nbsp;</td><td class="price">&nbsp;</td></tr>
                        @endfor
                    </table>
                </td>
            </tr>
        </table>

        <div class="summary-wrap">
            <table class="summary">
                <tr><th>Total</th><td>{!! $fmtMoney(data_get($totals, 'total')) !!}</td></tr>
                <tr><th>Deposit</th><td>{!! $fmtMoney(data_get($totals, 'deposit')) !!}</td></tr>
                <tr><th>Balance</th><td>{!! $fmtMoney(data_get($totals, 'balance')) !!}</td></tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Burial Schedule</div>
            <table class="schedule">
                <tr>
                    <td width="26%"><span class="label">Date of Burial</span><div class="line">{{ $fmtDate(data_get($schedule, 'interment_date')) }}</div></td>
                    <td width="20%"><span class="label">Time</span><div class="line">{{ $fmtTime(data_get($schedule, 'interment_time')) }}</div></td>
                    <td width="34%"><span class="label">Cemetery / Interment Location</span><div class="line">{{ data_get($schedule, 'cemetery') }}</div></td>
                    <td width="20%"><span class="label">Wake Duration</span><div class="line">{{ data_get($schedule, 'wake_duration') }}</div></td>
                </tr>
            </table>
        </div>

        <div class="section"><div class="section-title">Remarks</div><div class="remarks">{{ data_get($snapshot, 'remarks') }}</div></div>

        <div class="section">
            <div class="section-title">Stipulations</div>
            <div class="stipulations">
                <ol>
                    <li>The undersigned agrees to the funeral service details, charges, payment terms, and schedule stated in this contract.</li>
                    <li>Package inclusions and freebies are covered by the base package price. Additional services are charged only when selected or rendered.</li>
                    <li>Any unpaid balance shall remain payable according to the funeral home's collection policy.</li>
                    <li>This issued contract uses a saved snapshot. Later catalog, package, or case changes do not silently alter it.</li>
                </ol>
            </div>
        </div>

        <table class="signature">
            <tr>
                <td><div class="sig-line">&nbsp;</div><div class="sig-label">Manager's Signature</div></td>
                <td><div class="sig-line">{{ data_get($client, 'name') }}</div><div class="sig-label">Client / Representative</div></td>
                <td><div class="sig-line">{{ data_get($branch, 'name') ?: 'Funeral Home' }}</div><div class="sig-label">Name of Funeral Home</div></td>
            </tr>
        </table>

        <div class="foot">Document Reference: {{ data_get($snapshot, 'contract_number', $contractNumber) }}</div>
    </div>
</body>
</html>
