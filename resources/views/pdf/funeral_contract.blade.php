@php
    $case = $funeral_case;
    $client = $case->client;
    $deceased = $case->deceased;
    $pkg = $case->package;
    $fmtDate = fn($dt) => $dt ? \Carbon\Carbon::parse($dt)->format('M d, Y') : '';
    $fmtTime = fn($time) => $time ? \Carbon\Carbon::parse($time)->format('h:i A') : '';
    $fmtMoney = fn($amount) => '&#8369; ' . number_format((float) $amount, 2);
    $packageName = $case->package_name_snapshot ?: ($case->service_package ?? $pkg?->name ?? $case->custom_package_name ?? '');
    $packagePrice = $case->package_price_snapshot ?? $case->custom_package_price ?? $pkg?->price ?? $case->subtotal_amount ?? 0;
    $intermentAt = $case->interment_at ?? $case->serviceDetail?->internment_date ?? $deceased?->interment_at ?? $deceased?->interment;
    $inclusions = $case->package_inclusions_snapshot
        ? \App\Models\Package::parseLegacyItems($case->package_inclusions_snapshot)
        : ($case->custom_package_inclusions
            ? \App\Models\Package::parseLegacyItems($case->custom_package_inclusions)
            : ($pkg?->inclusionNames() ?? []));
    $freebies = $case->package_freebies_snapshot
        ? \App\Models\Package::parseLegacyItems($case->package_freebies_snapshot)
        : ($case->custom_package_freebies
            ? \App\Models\Package::parseLegacyItems($case->custom_package_freebies)
            : ($pkg?->freebieNames() ?? []));
    $includedServices = array_values(array_filter(array_merge([$packageName], $inclusions, $freebies)));
    $addOns = $case->caseAddOns ?? collect();
    $deposit = (float) ($case->total_paid ?? 0);
    $balance = (float) ($case->balance_amount ?? max((float) $case->total_amount - $deposit, 0));
    $logoPath = public_path('images/login-logo.png');
    $logoExtension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
    $canRenderLogo = file_exists($logoPath) && ($logoExtension !== 'png' || extension_loaded('gd'));
    $branchContact = data_get($case->branch, 'contact_number') ?: 'Contact Number';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract - {{ $contractNumber }}</title>
    <style>
        @page { margin: 22px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color:#111; font-size:10.5px; line-height:1.25; }
        .sheet { border:1.4px solid #111; padding:12px 14px 14px; min-height:96%; }
        .header { width:100%; border-collapse:collapse; margin-bottom:7px; }
        .logo-cell { width:72px; vertical-align:top; }
        .logo { width:58px; height:58px; object-fit:contain; }
        .home { text-align:center; vertical-align:top; padding-right:72px; }
        .home-name { font-size:16px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
        .home-line { font-size:9.5px; margin-top:2px; }
        .title { text-align:center; font-size:22px; font-weight:900; letter-spacing:.12em; margin:7px 0 9px; }
        .info { width:100%; border-collapse:collapse; margin-bottom:9px; }
        .info td { padding:3px 4px; vertical-align:bottom; }
        .label { font-size:8px; text-transform:uppercase; color:#333; letter-spacing:.04em; white-space:nowrap; }
        .line { border-bottom:1px solid #111; min-height:14px; font-weight:700; padding:0 4px 2px; }
        .body-table { width:100%; border-collapse:collapse; table-layout:fixed; }
        .body-table td { vertical-align:top; }
        .col { border:1px solid #111; min-height:310px; }
        .col + .col { border-left:0; }
        .col-title { background:#f0f0f0; border-bottom:1px solid #111; padding:6px 8px; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.05em; }
        .service-list { padding:7px 8px 8px; }
        .check-row { width:100%; border-collapse:collapse; margin-bottom:5px; }
        .check { width:14px; height:14px; border:1px solid #111; text-align:center; font-size:10px; line-height:12px; }
        .service-text { padding-left:6px; border-bottom:1px solid #ccc; height:16px; }
        .extra-table { width:100%; border-collapse:collapse; }
        .extra-table th { font-size:8px; color:#333; text-transform:uppercase; border-bottom:1px solid #111; padding:4px 5px; text-align:left; }
        .extra-table th.price { text-align:right; width:88px; }
        .extra-table td { border-bottom:1px solid #d0d0d0; padding:5px; height:18px; }
        .price { text-align:right; white-space:nowrap; }
        .summary-wrap { width:44%; margin:11px 0 8px auto; }
        .summary { width:100%; border-collapse:collapse; border:1px solid #111; }
        .summary th, .summary td { border-bottom:1px solid #111; padding:6px 8px; font-size:11px; }
        .summary th { text-align:left; text-transform:uppercase; }
        .summary td { text-align:right; font-weight:800; }
        .summary tr:last-child th, .summary tr:last-child td { border-bottom:0; }
        .remarks { margin-top:7px; border:1px solid #111; }
        .remarks-title { padding:5px 7px; border-bottom:1px solid #111; font-size:9px; font-weight:900; text-transform:uppercase; background:#f0f0f0; }
        .remarks-box { height:58px; padding:6px 8px; }
        .burial { margin-top:8px; border:1px solid #111; }
        .burial-title { padding:5px 7px; border-bottom:1px solid #111; font-size:9px; font-weight:900; text-transform:uppercase; background:#f0f0f0; }
        .burial-table { width:100%; border-collapse:collapse; }
        .burial-table td { padding:7px 8px; border-right:1px solid #111; }
        .burial-table td:last-child { border-right:0; }
        .signature { width:100%; border-collapse:collapse; margin-top:28px; }
        .signature td { width:33.33%; text-align:center; padding:0 18px; vertical-align:bottom; }
        .sig-line { border-top:1px solid #111; padding-top:5px; min-height:13px; font-weight:800; }
        .sig-label { font-size:8px; text-transform:uppercase; margin-top:2px; color:#333; }
        .foot { text-align:center; margin-top:10px; font-size:8px; color:#555; }
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
                    <div class="home-name">{{ $case->branch?->branch_name ?? 'Funeral Home' }}</div>
                    <div class="home-line">{{ $case->branch?->address ?? 'Funeral Home Address' }}</div>
                    <div class="home-line">{{ $branchContact }}</div>
                </td>
            </tr>
        </table>

        <div class="title">CONTRACT</div>

        <table class="info">
            <tr>
                <td width="16%"><span class="label">Contract / Case No.</span></td>
                <td width="34%"><div class="line">{{ $contractNumber }} / {{ $case->case_code }}</div></td>
                <td width="10%"><span class="label">Date</span></td>
                <td width="40%"><div class="line">{{ $generatedAt->format('M d, Y') }}</div></td>
            </tr>
            <tr>
                <td><span class="label">Client Name</span></td>
                <td colspan="3"><div class="line">{{ $client?->full_name }}</div></td>
            </tr>
            <tr>
                <td><span class="label">Address</span></td>
                <td colspan="3"><div class="line">{{ $client?->address }}</div></td>
            </tr>
            <tr>
                <td><span class="label">Telephone No.</span></td>
                <td><div class="line">{{ $client?->contact_number }}</div></td>
                <td><span class="label">Deceased</span></td>
                <td><div class="line">{{ $deceased?->full_name }}</div></td>
            </tr>
        </table>

        <table class="body-table">
            <tr>
                <td class="col" width="50%">
                    <div class="col-title">Basic Funeral Service Inclusions</div>
                    <div class="service-list">
                        @forelse(array_slice($includedServices, 0, 12) as $service)
                            <table class="check-row">
                                <tr>
                                    <td class="check">✓</td>
                                    <td class="service-text">{{ $service }}</td>
                                </tr>
                            </table>
                        @empty
                            <table class="check-row"><tr><td class="check"></td><td class="service-text">&nbsp;</td></tr></table>
                        @endforelse

                        @for($i = count(array_slice($includedServices, 0, 12)); $i < 12; $i++)
                            <table class="check-row">
                                <tr>
                                    <td class="check"></td>
                                    <td class="service-text">&nbsp;</td>
                                </tr>
                            </table>
                        @endfor
                    </div>
                </td>
                <td class="col" width="50%">
                    <div class="col-title">Additional Services</div>
                    <table class="extra-table">
                        <tr>
                            <th>Description</th>
                            <th class="price">Price</th>
                        </tr>
                        @foreach($addOns as $addOn)
                            <tr>
                                <td>{{ $addOn->add_on_name_snapshot }}{{ $addOn->quantity > 1 ? ' x ' . $addOn->quantity : '' }}</td>
                                <td class="price">{!! $fmtMoney($addOn->line_total) !!}</td>
                            </tr>
                        @endforeach
                        @if($case->additional_services || (float) ($case->additional_service_amount ?? 0) > 0)
                            <tr>
                                <td>{{ $case->additional_services ?: 'Manual extras' }}</td>
                                <td class="price">{!! $fmtMoney($case->additional_service_amount ?? 0) !!}</td>
                            </tr>
                        @endif
                        @for($i = $addOns->count() + (($case->additional_services || (float) ($case->additional_service_amount ?? 0) > 0) ? 1 : 0); $i < 11; $i++)
                            <tr>
                                <td>&nbsp;</td>
                                <td class="price">&nbsp;</td>
                            </tr>
                        @endfor
                    </table>
                </td>
            </tr>
        </table>

        <div class="summary-wrap">
            <table class="summary">
                <tr>
                    <th>Total</th>
                    <td>{!! $fmtMoney($case->total_amount) !!}</td>
                </tr>
                <tr>
                    <th>Deposit</th>
                    <td>{!! $fmtMoney($deposit) !!}</td>
                </tr>
                <tr>
                    <th>Balance</th>
                    <td>{!! $fmtMoney($balance) !!}</td>
                </tr>
            </table>
        </div>

        <div class="remarks">
            <div class="remarks-title">Remarks</div>
            <div class="remarks-box">{{ $case->discount_note }}</div>
        </div>

        <div class="burial">
            <div class="burial-title">Burial Information</div>
            <table class="burial-table">
                <tr>
                    <td width="32%"><span class="label">Date of Burial</span><div class="line">{{ $fmtDate($intermentAt) }}</div></td>
                    <td width="24%"><span class="label">Time</span><div class="line">{{ $fmtTime($case->interment_time) }}</div></td>
                    <td width="44%"><span class="label">Cemetery / Location</span><div class="line">{{ $deceased?->place_of_cemetery ?: $case->serviceDetail?->cemetery_place }}</div></td>
                </tr>
            </table>
        </div>

        <table class="signature">
            <tr>
                <td>
                    <div class="sig-line">&nbsp;</div>
                    <div class="sig-label">Manager's Signature</div>
                </td>
                <td>
                    <div class="sig-line">&nbsp;</div>
                    <div class="sig-label">Printed Name</div>
                </td>
                <td>
                    <div class="sig-line">Funeral Home</div>
                    <div class="sig-label">Designation</div>
                </td>
            </tr>
        </table>

        <div class="foot">Generated Document Reference: {{ $contractNumber }}</div>
    </div>
</body>
</html>
