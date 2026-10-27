@php
    $snapshot = $contractSnapshot ?? [];
    $view = $contractView ?? app(\App\Services\CaseDocument\FuneralContractMapper::class)->map($snapshot);
    $branch = data_get($snapshot, 'branch', []);
    $client = data_get($snapshot, 'client', []);
    $deceased = data_get($snapshot, 'deceased', []);
    $schedule = data_get($snapshot, 'schedule', []);
    $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : '';
    $money = fn ($value) => 'PHP ' . number_format((float) $value, 2);
    $basicRows = collect(data_get($view, 'basic_rows', []))->keyBy('label');
    $includedValue = function (string $label) use ($basicRows): string {
        $value = trim((string) data_get($basicRows->get($label), 'value', ''));
        return in_array($value, ['Not configured', 'Not available in historical record', 'Not included'], true) ? '' : $value;
    };
    $casket = data_get($view, 'selected_casket.name') ?: data_get($view, 'included_casket.name') ?: $includedValue('Casket');
    $contractNumber = data_get($view, 'contract_number', $contractNumber ?? '');
    $headerAddress = trim((string) data_get($branch, 'address')) ?: 'Zone VI Poblacion, Bayambang, Pangasinan';
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
        @page { size: A4 portrait; margin: 36px 48px 30px; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: DejaVu Serif, Georgia, serif; font-size: 12px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        .heading { text-align: center; font-weight: 700; line-height: 1.22; }
        .heading-logo { display: block; width: 43px; height: 29px; margin: 0 auto 6px; }
        .business { font-size: 18px; letter-spacing: .04em; }
        .service { font-size: 13px; letter-spacing: .07em; }
        .tagline { font-size: 11px; letter-spacing: .08em; }
        .address { font-size: 11px; font-weight: 700; margin-top: 3px; }
        .proprietor { font-size: 11px; letter-spacing: .02em; margin-top: 2px; }
        .document-title { text-align: center; font-size: 23px; letter-spacing: .12em; font-weight: 700; margin: 14px 0 17px; }
        .meta { margin: 0 0 17px; }
        .meta td { vertical-align: bottom; }
        .serial { width: 58%; font-size: 12px; font-weight: 700; }
        .serial strong { font-size: 13px; letter-spacing: .04em; }
        .date-label { width: 8%; white-space: nowrap; font-weight: 700; }
        .date-value { border-bottom: 1px solid #111; padding: 0 5px 2px; font-size: 12px; }
        .field { margin: 9px 0; page-break-inside: avoid; table-layout: fixed; }
        .field td { vertical-align: bottom; padding: 0; }
        .field-label { white-space: nowrap; font-weight: 700; padding-right: 8px !important; }
        .field-value { border-bottom: 1px solid #111; padding: 0 6px 2px !important; min-height: 16px; font-weight: 500; }
        .declaration { margin: 13px 0 10px; line-height: 1.6; }
        .declaration strong { font-weight: 700; }
        .vitals { margin: 10px 0; }
        .vitals td { vertical-align: bottom; padding-right: 9px; }
        .vitals td:last-child { padding-right: 0; }
        .vital-label { font-weight: 700; white-space: nowrap; }
        .vital-value { border-bottom: 1px solid #111; padding: 0 4px 2px; min-height: 16px; }
        .promise { margin: 21px 0 18px; font-weight: 700; }
        .obligations { page-break-inside: avoid; }
        .obligations > tbody > tr > td { vertical-align: bottom; }
        .services { width: 65%; padding-right: 20px; }
        .service-row { margin-bottom: 15px; table-layout: fixed; }
        .service-row td { vertical-align: bottom; }
        .bullet { width: 17px; font-size: 16px; line-height: 1; text-align: center; }
        .service-name { width: 49%; white-space: nowrap; font-weight: 700; padding-right: 6px; }
        .service-value { border-bottom: 1px solid #111; padding: 0 3px 2px; min-height: 16px; }
        .totals { width: 35%; vertical-align: bottom !important; }
        .total-row { margin-top: 8px; }
        .total-row td { vertical-align: bottom; padding-top: 7px; }
        .total-label { white-space: nowrap; padding-right: 6px; font-weight: 700; }
        .total-value { border-bottom: 1px solid #111; text-align: right; padding: 0 3px 2px; white-space: nowrap; font-weight: 700; }
        .payment-note { font-size: 10px; text-align: right; margin-top: 4px; }
        .signatures { margin-top: 71px; page-break-inside: avoid; }
        .signatures td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 24px; }
        .signature-space { height: 27px; }
        .signature-line { border-top: 1px solid #111; padding-top: 5px; font-size: 11px; font-weight: 700; }
        .signature-sub { font-size: 10px; }
    </style>
</head>
<body>
    <div class="heading">
        @if($logoDataUri)
            <img class="heading-logo" src="{{ $logoDataUri }}" alt="Sabangan-Caguioa logo">
        @endif
        <div class="business">SABANGAN-CAGUIOA</div>
        <div class="service">FUNERAL SERVICES</div>
        <div class="tagline">DAY &amp; NIGHT SERVICE</div>
        <div class="address">{{ $headerAddress }}</div>
        <div class="proprietor">ROEL S. CAGUIOA - Prop.</div>
    </div>

    <div class="document-title">FUNERAL CONTRACT</div>
    <table class="meta"><tr>
        <td class="serial">No. <strong>{{ $contractNumber }}</strong></td>
        <td class="date-label">DATE:</td>
        <td class="date-value">{{ $date(data_get($snapshot, 'contract_date')) }}</td>
    </tr></table>

    <table class="field"><tr><td class="field-label" style="width:18%">CHARGE TO</td><td class="field-value">{{ data_get($client, 'name') }}</td></tr></table>
    <table class="field"><tr><td class="field-label" style="width:20%">Residing at</td><td class="field-value">{{ data_get($client, 'address') }}</td></tr></table>
    <p class="declaration">Hereby authorize <strong>SABANGAN-CAGUIOA FUNERAL SERVICES</strong> to render</p>
    <table class="field"><tr><td class="field-label" style="width:34%">Funeral service to the late</td><td class="field-value">{{ data_get($deceased, 'name') }}</td></tr></table>
    <table class="field"><tr><td class="field-label" style="width:5%">of</td><td class="field-value">{{ data_get($deceased, 'address') }}</td></tr></table>

    <table class="vitals"><tr>
        <td width="38%"><span class="vital-label">BORN</span> <span class="vital-value">{{ $date(data_get($deceased, 'date_of_birth')) }}</span></td>
        <td width="38%"><span class="vital-label">DIED</span> <span class="vital-value">{{ $date(data_get($deceased, 'date_of_death')) }}</span></td>
        <td width="24%"><span class="vital-label">AGE</span> <span class="vital-value">{{ data_get($deceased, 'age') }}</span></td>
    </tr></table>
    <table class="field"><tr><td class="field-label" style="width:27%">INTERMENT DATE</td><td class="field-value">{{ $date(data_get($schedule, 'interment_date')) }}</td></tr></table>
    <table class="field"><tr><td class="field-label" style="width:17%">CEMETERY</td><td class="field-value">{{ data_get($schedule, 'cemetery') }}</td></tr></table>

    <p class="promise">And I/We hereby promise to pay all the obligation as stipulated here under:</p>
    <table class="obligations"><tr>
        <td class="services">
            <table class="service-row"><tr><td class="bullet">&bull;</td><td class="service-name">CASKET/COFFIN</td><td class="service-value">{{ $casket }}</td></tr></table>
            <table class="service-row"><tr><td class="bullet">&bull;</td><td class="service-name">DAYS OF EMBALMING</td><td class="service-value">{{ $includedValue('Embalming') }}</td></tr></table>
            <table class="service-row"><tr><td class="bullet">&bull;</td><td class="service-name">HOME DECOR OR MORTUARY</td><td class="service-value">&nbsp;</td></tr></table>
            <table class="service-row"><tr><td class="bullet">&nbsp;</td><td class="service-name">ARRANGEMENT</td><td class="service-value">{{ $includedValue('Home Viewing Service') }}</td></tr></table>
            <table class="service-row"><tr><td class="bullet">&bull;</td><td class="service-name">FUNERAL HEARSE/COACH</td><td class="service-value">{{ $includedValue('Regular Hearse') }}</td></tr></table>
            <table class="service-row"><tr><td class="bullet">&bull;</td><td class="service-name">EXTRA SERVICE/OTHERS</td><td class="service-value">&nbsp;</td></tr></table>
        </td>
        <td class="totals">
            <table class="total-row"><tr><td class="total-label">TOTAL AMOUNT</td><td class="total-value">{{ $money(data_get($view, 'totals.contract_total')) }}</td></tr></table>
            <table class="total-row"><tr><td class="total-label">DOWN PAYMENT</td><td class="total-value">{{ $money(data_get($view, 'totals.initial_deposit')) }}</td></tr></table>
            <table class="total-row"><tr><td class="total-label">BALANCE</td><td class="total-value">{{ $money(data_get($view, 'totals.remaining_balance')) }}</td></tr></table>
            @if((float) data_get($view, 'totals.total_paid', 0) > (float) data_get($view, 'totals.initial_deposit', 0))
                <div class="payment-note">Paid to date: {{ $money(data_get($view, 'totals.total_paid')) }}</div>
            @endif
        </td>
    </tr></table>

    <table class="signatures"><tr>
        <td><div class="signature-space">&nbsp;</div><div class="signature-line">Lagda ng Nangako</div><div class="signature-sub">{{ data_get($client, 'name') }}</div></td>
        <td><div class="signature-space">&nbsp;</div><div class="signature-line">Manager</div><div class="signature-sub">SABANGAN-CAGUIOA Funeral Services</div></td>
    </tr></table>
</body>
</html>
