@php
    $snapshot = $contractSnapshot ?? [];
    $view = $contractView ?? app(\App\Services\CaseDocument\FuneralContractMapper::class)->map($snapshot);
    $fmtDate = function ($date) {
        return $date ? \Carbon\Carbon::parse($date)->format('M d, Y') : '';
    };
    $fmtTime = function ($time) {
        return $time ? \Carbon\Carbon::parse($time)->format('h:i A') : '';
    };
    $fmtMoney = fn ($amount) => '<span class="peso">&#8369;</span>' . number_format((float) $amount, 2);
    $branch = data_get($snapshot, 'branch', []);
    $client = data_get($snapshot, 'client', []);
    $deceased = data_get($snapshot, 'deceased', []);
    $schedule = data_get($snapshot, 'schedule', []);
    $contractNumber = data_get($view, 'contract_number', $contractNumber ?? '');
    $caseNumber = data_get($view, 'case_number', '');
    $logoPath = file_exists(public_path('images/login-logo.jpg'))
        ? public_path('images/login-logo.jpg')
        : public_path('images/login-logo.png');
    $logoExtension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION)) ?: 'png';
    $logoDataUri = file_exists($logoPath) && ($logoExtension !== 'png' || extension_loaded('gd'))
        ? 'data:image/' . $logoExtension . ';base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Funeral Contract - {{ $contractNumber }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 24px 42px;
        }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 9.4px; line-height: 1.24; }
        .sheet { padding: 0 2px; }
        table { border-collapse: collapse; width: 100%; }
        .masthead { width: 52%; margin: 0 auto 3px; }
        .masthead td { vertical-align: middle; }
        .logo-cell { width: 92px; }
        .logo { width: 82px; height: 46px; object-fit: contain; }
        .logo-mark { width: 82px; height: 42px; border: 1px solid #23422f; background: #31583e; color: #fff; padding: 6px 6px; font-family: DejaVu Sans, sans-serif; }
        .logo-bars { float: left; width: 15px; height: 24px; margin-right: 6px; border: 1px solid #eee; }
        .logo-red { height: 7px; background: #c7443e; }
        .logo-white { height: 7px; background: #f8f8f8; }
        .logo-green { height: 7px; background: #34844d; }
        .logo-text { font-size: 5.8px; line-height: 1.08; font-weight: 800; text-transform: uppercase; padding-top: 1px; }
        .brand { text-align: left; }
        .brand-name { font-size: 9.8px; font-weight: 800; text-transform: uppercase; }
        .brand-line { margin-top: 1px; font-size: 7.8px; }
        .peso { font-family: DejaVu Sans, sans-serif; font-weight: 400; }
        .title { text-align: center; font-size: 14px; font-weight: 900; letter-spacing: .04em; margin: 3px 0 2px; }
        .contract-stack { width: 220px; margin: 0 auto 5px; }
        .contract-stack td { padding: 1px 3px; vertical-align: bottom; }
        .field-table { margin-bottom: 5px; }
        .field-table td { padding: 2px 3px; vertical-align: bottom; }
        .package-table { margin: 3px 0 5px; }
        .package-table td { padding: 2px 3px; vertical-align: bottom; }
        .label { font-size: 8px; font-weight: 800; text-transform: uppercase; white-space: nowrap; }
        .line { border-bottom: 1px solid #111; min-height: 14px; padding: 0 5px 2px; font-weight: 400; }
        .service-grid { border: 1px solid #111; table-layout: fixed; }
        .service-grid,
        .remarks-box,
        .schedule-line,
        .signatures { page-break-inside: avoid; }
        .service-grid > tbody > tr > td { width: 50%; vertical-align: top; }
        .service-grid > tbody > tr > td + td { border-left: 1px solid #111; }
        .panel-title { padding: 3px 6px; border-bottom: 1px solid #111; font-weight: 900; text-decoration: underline; }
        .ruled td { border-bottom: 1px solid #aaa; height: 18px; padding: 3px 5px; vertical-align: top; }
        .ruled tr:last-child td { border-bottom: 0; }
        .pair-table td { border-bottom: 1px solid #aaa; height: 20px; padding: 3px 5px; vertical-align: bottom; }
        .pair-table tr:last-child td { border-bottom: 0; }
        .service-name { width: 130px; font-weight: 800; }
        .service-detail { border-bottom: 1px dotted #777 !important; }
        .additional-table td { border-bottom: 1px solid #aaa; min-height: 19px; padding: 3px 5px; vertical-align: top; }
        .additional-group td { font-weight: 800; border-top: 1px solid #111; background: #fafafa; }
        .extra-name { width: 38%; font-weight: 800; font-size: 8px; }
        .extra-detail { width: 39%; }
        .amount { width: 23%; text-align: right; white-space: nowrap; font-weight: 800; }
        .muted { color: #444; font-size: 7.8px; }
        .note-box { border-top: 1px solid #111; min-height: 74px; padding: 6px 7px; font-size: 7.8px; }
        .itemized-title { padding: 2px 4px; font-weight: 800; border-top: 1px solid #111; border-bottom: 1px solid #aaa; }
        .simple-totals { border-top: 1px solid #111; }
        .simple-totals td { padding: 3px 5px; height: 17px; }
        .simple-totals .total-label { width: 165px; font-weight: 800; }
        .simple-totals .total-line { border-bottom: 1px solid #111; text-align: right; font-weight: 800; }
        .remarks-box { margin-top: 5px; border: 1px solid #111; min-height: 36px; }
        .remarks-box td { vertical-align: top; }
        .remarks-label { width: 54px; padding: 4px 5px; font-weight: 800; }
        .remarks-value { border-left: 1px solid #111; padding: 4px 5px; }
        .schedule-line { margin-top: 7px; }
        .schedule-line td { padding: 4px 4px; vertical-align: bottom; }
        .lower-sign-grid { margin-top: 10px; border-top: 1px solid #111; table-layout: fixed; page-break-inside: avoid; }
        .lower-sign-grid td { vertical-align: top; }
        .stip-col { width: 55%; padding-top: 16px; padding-right: 18px; }
        .manager-col { width: 45%; padding-top: 29px; text-align: center; }
        .stipulations { margin-top: 0; }
        .stip-title { text-align: center; font-weight: 900; text-transform: uppercase; margin-bottom: 2px; }
        .stip-body { min-height: 60px; font-size: 7.8px; text-align: justify; }
        .stip-body p { margin: 0 0 3px; text-indent: 12px; }
        .signatures { margin-top: 18px; table-layout: fixed; }
        .signatures td { text-align: center; padding: 0 18px; vertical-align: bottom; }
        .client-signature { width: 46%; margin-top: 8px; text-align: center; page-break-inside: avoid; }
        .manager-signature { width: 78%; margin: 0 auto; }
        .sig-name { min-height: 12px; margin-bottom: 0; font-weight: 800; text-align: center; line-height: 1.05; }
        .sig-line { border-top: 1px solid #111; padding-top: 2px; min-height: 10px; font-weight: 800; }
        .sig-label { margin-top: 0; font-size: 7.8px; font-weight: 700; text-align: center; line-height: 1.1; }
    </style>
</head>
<body>
    <div class="sheet">
        <table class="masthead">
            <tr>
                <td class="logo-cell">
                    @if($logoDataUri)
                        <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
                    @else
                        <div class="logo-mark">
                            <div class="logo-bars"><div class="logo-red"></div><div class="logo-white"></div><div class="logo-green"></div></div>
                            <div class="logo-text">Sabangan-Caguioa<br>Funeral Services</div>
                        </div>
                    @endif
                </td>
                <td class="brand">
                    <div class="brand-name">Sabangan-Caguioa Funeral Services</div>
                    <div class="brand-line">{{ data_get($branch, 'address') ?: 'Zone VI Bayambang, Pangasinan' }}</div>
                    <div class="brand-line">{{ data_get($branch, 'contact_number') }}</div>
                </td>
            </tr>
        </table>

        <div class="title">CONTRACT</div>
        <table class="contract-stack">
            <tr>
                <td width="34%" class="label">Contract No:</td>
                <td><div class="line">{{ $contractNumber }}</div></td>
            </tr>
            <tr>
                <td class="label">Case No:</td>
                <td><div class="line">{{ $caseNumber }}</div></td>
            </tr>
            <tr>
                <td class="label">Date:</td>
                <td><div class="line">{{ $fmtDate(data_get($snapshot, 'contract_date')) }}</div></td>
            </tr>
        </table>

        <table class="field-table">
            <tr>
                <td width="9%" class="label">Name:</td>
                <td colspan="5"><div class="line">{{ data_get($client, 'name') }}</div></td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td colspan="3"><div class="line">{{ data_get($client, 'address') }}</div></td>
                <td width="12%" class="label">Tel No:</td>
                <td width="24%"><div class="line">{{ data_get($client, 'contact_number') }}</div></td>
            </tr>
            <tr>
                <td colspan="2" class="label">Re: Funeral Service Rendered The Late:</td>
                <td colspan="4"><div class="line">{{ data_get($deceased, 'name') }}</div></td>
            </tr>
        </table>

        <table class="package-table">
            <tr>
                <td width="22%" class="label">Selected Service Package:</td>
                <td width="42%"><div class="line">{{ data_get($view, 'selected_package.name') }}</div></td>
                <td width="17%" class="label">Base Package Price:</td>
                <td width="19%"><div class="line">{!! $fmtMoney(data_get($view, 'selected_package.base_price')) !!}</div></td>
            </tr>
        </table>

        <table class="service-grid">
            <tr>
                <td>
                    <div class="panel-title">Basic Funeral Service Inclusion:</div>
                    <table class="pair-table">
                        @foreach(data_get($view, 'basic_rows', []) as $row)
                            <tr>
                                <td class="service-name">{{ data_get($row, 'label') }}</td>
                                <td class="service-detail">{{ data_get($row, 'value') }}</td>
                            </tr>
                        @endforeach
                        @foreach(data_get($view, 'other_inclusions', []) as $item)
                            <tr><td class="service-name">&nbsp;</td><td class="service-detail">{{ $item }}</td></tr>
                        @endforeach
                        @for($i = count(data_get($view, 'basic_rows', [])) + count(data_get($view, 'other_inclusions', [])); $i < 10; $i++)
                            <tr><td class="service-name">&nbsp;</td><td class="service-detail">&nbsp;</td></tr>
                        @endfor
                    </table>
                    <div class="note-box">
                        <strong>Note:</strong> Supplemental services beyond the basic funeral service inclusion shall be charged under Additional Services.
                    </div>
                </td>
                <td>
                    <div class="panel-title">Additional Services:</div>
                    <table class="additional-table">
                        @foreach(data_get($view, 'physical_additional_rows', []) as $row)
                            <tr>
                                <td class="extra-name">{{ data_get($row, 'label') }}</td>
                                <td class="extra-detail">{{ data_get($row, 'details') }}</td>
                                <td class="amount">
                                    @if((float) data_get($row, 'amount', 0) > 0)
                                        {!! $fmtMoney(data_get($row, 'amount')) !!}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if(count(data_get($view, 'add_ons', [])) > 0)
                            <tr class="additional-group"><td colspan="3">Add-ons</td></tr>
                        @endif
                        @foreach(data_get($view, 'add_ons', []) as $service)
                            <tr>
                                <td class="extra-name">{{ data_get($service, 'label') }}</td>
                                <td class="extra-detail">{{ data_get($service, 'details') }}</td>
                                <td class="amount">{!! $fmtMoney(data_get($service, 'amount')) !!}</td>
                            </tr>
                        @endforeach
                        @if(count(data_get($view, 'itemized_services', [])) > 0)
                            <tr class="additional-group"><td colspan="3">Other Itemized Services</td></tr>
                        @endif
                        @foreach(data_get($view, 'itemized_services', []) as $service)
                            <tr>
                                <td class="extra-name">{{ data_get($service, 'label') }}</td>
                                <td class="extra-detail">{{ data_get($service, 'details') }}</td>
                                <td class="amount">{!! $fmtMoney(data_get($service, 'amount')) !!}</td>
                            </tr>
                        @endforeach
                    </table>
                    <table class="simple-totals">
                        <tr><td class="total-label">Package:</td><td class="total-line">{!! $fmtMoney(data_get($view, 'totals.base_package_price')) !!}</td></tr>
                        <tr><td class="total-label">Additional Services:</td><td class="total-line">{!! $fmtMoney(data_get($view, 'totals.total_additional_services')) !!}</td></tr>
                        @if((float) data_get($view, 'totals.discount_amount', 0) > 0)
                            <tr><td class="total-label">{{ data_get($view, 'totals.discount_label') }}:</td><td class="total-line">{!! $fmtMoney(data_get($view, 'totals.discount_amount')) !!}</td></tr>
                        @endif
                        @if((float) data_get($view, 'totals.tax', 0) > 0)
                            <tr><td class="total-label">Tax:</td><td class="total-line">{!! $fmtMoney(data_get($view, 'totals.tax')) !!}</td></tr>
                        @endif
                        <tr><td class="total-label">Total:</td><td class="total-line">{!! $fmtMoney(data_get($view, 'totals.contract_total')) !!}</td></tr>
                        <tr><td class="total-label">Deposit:</td><td class="total-line">{!! $fmtMoney(data_get($view, 'totals.initial_deposit')) !!}</td></tr>
                        <tr><td class="total-label">Paid:</td><td class="total-line">{!! $fmtMoney(data_get($view, 'totals.total_paid')) !!}</td></tr>
                        <tr><td class="total-label">Balance:</td><td class="total-line">{!! $fmtMoney(data_get($view, 'totals.remaining_balance')) !!}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="remarks-box">
            <tr>
                <td class="remarks-label">Remarks:</td>
                <td class="remarks-value">{{ data_get($snapshot, 'remarks') }}</td>
            </tr>
        </table>

        <table class="schedule-line">
            <tr>
                <td width="14%" class="label">Date of Burial:</td>
                <td width="20%"><div class="line">{{ $fmtDate(data_get($schedule, 'interment_date')) }}</div></td>
                <td width="7%" class="label">Time:</td>
                <td width="16%"><div class="line">{{ $fmtTime(data_get($schedule, 'interment_time')) }}</div></td>
                <td width="13%" class="label">Location:</td>
                <td width="30%"><div class="line">{{ data_get($schedule, 'cemetery') ?: 'Cemetery / Interment Location' }}</div></td>
            </tr>
        </table>

        <table class="lower-sign-grid">
            <tr>
                <td class="stip-col">
                    <div class="stipulations">
                        <div class="stip-title">Stipulations:</div>
                        <div class="stip-body">
                            @foreach(data_get($view, 'stipulations', []) as $stipulation)
                                <p>{{ $stipulation }}</p>
                            @endforeach
                        </div>
                    </div>
                </td>
                <td class="manager-col">
                    <div class="manager-signature">
                        <div class="sig-line">&nbsp;</div>
                        <div class="sig-label">Manager</div>
                        <div class="sig-label">Name of Funeral Home</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="client-signature">
            <div class="sig-name">{{ data_get($client, 'name') }}</div>
            <div class="sig-line">&nbsp;</div>
            <div class="sig-label">Client / Representative</div>
        </div>
    </div>
</body>
</html>
