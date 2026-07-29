@extends('layouts.panel')

@section('page_title', 'Contract Preview')
@section('page_desc', 'Review the saved case data before issuing the Funeral Contract.')

@section('content')
@php
    $fmtMoney = fn ($amount) => '₱' . number_format((float) $amount, 2);
    $fmtDate = fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('M d, Y') : 'Not set';
    $fmtTime = fn ($time) => $time ? \Carbon\Carbon::parse($time)->format('h:i A') : '';
    $package = data_get($contractSnapshot, 'package', []);
    $client = data_get($contractSnapshot, 'client', []);
    $deceased = data_get($contractSnapshot, 'deceased', []);
    $schedule = data_get($contractSnapshot, 'schedule', []);
    $contractView = $contractView ?? app(\App\Services\CaseDocument\FuneralContractMapper::class)->map($contractSnapshot);
    $intermentTimeValue = old('interment_time', data_get($schedule, 'interment_time'));
    $intermentTimeValue = $intermentTimeValue ? \Carbon\Carbon::parse($intermentTimeValue)->format('H:i') : '';
@endphp

<style>
    .contract-preview-wrap { max-width:1120px; margin:0 auto; padding:24px 16px 40px; }
    .contract-preview-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px; }
    .contract-eyebrow { font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--brand, #8b1e1e); }
    .contract-title { margin:4px 0 0; font-size:24px; font-weight:800; color:var(--ink, #111827); }
    .contract-subtitle { margin:5px 0 0; color:var(--ink-muted, #64748b); font-size:14px; }
    .contract-grid { display:grid; grid-template-columns:minmax(0, 1.15fr) minmax(320px, .85fr); gap:18px; align-items:start; }
    .contract-card { border:1px solid var(--border, #e2e8f0); background:var(--surface, #fff); border-radius:14px; box-shadow:0 10px 30px rgba(15,23,42,.06); overflow:hidden; }
    .contract-card-head { padding:16px 18px; border-bottom:1px solid var(--border, #e2e8f0); display:flex; align-items:center; gap:10px; }
    .contract-card-head i { color:var(--brand, #8b1e1e); }
    .contract-card-title { font-size:15px; font-weight:800; color:var(--ink, #111827); }
    .contract-card-body { padding:18px; }
    .contract-section { margin-top:18px; }
    .contract-section:first-child { margin-top:0; }
    .contract-section-title { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-muted, #64748b); margin-bottom:9px; }
    .contract-fields { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px; }
    .contract-field { border:1px solid var(--border, #e2e8f0); border-radius:10px; padding:10px 12px; background:var(--subtle, #f8fafc); }
    .contract-label { font-size:11px; color:var(--ink-muted, #64748b); text-transform:uppercase; letter-spacing:.05em; font-weight:700; }
    .contract-value { margin-top:3px; font-size:14px; font-weight:700; color:var(--ink, #111827); word-break:break-word; }
    .contract-list { display:grid; gap:7px; margin:0; padding:0; list-style:none; }
    .contract-list li { display:flex; gap:8px; align-items:flex-start; padding:8px 10px; border:1px solid var(--border, #e2e8f0); border-radius:10px; color:var(--ink, #111827); background:var(--subtle, #f8fafc); font-size:13px; }
    .contract-list i { color:#16a34a; margin-top:1px; }
    .contract-total-row { display:flex; justify-content:space-between; gap:12px; padding:11px 0; border-bottom:1px solid var(--border, #e2e8f0); font-size:14px; }
    .contract-total-row:last-child { border-bottom:0; font-weight:800; font-size:16px; }
    .contract-form-grid { display:grid; gap:14px; }
    .contract-input-group label { display:block; font-size:12px; font-weight:800; color:var(--ink, #111827); margin-bottom:6px; }
    .contract-input-group input,
    .contract-input-group textarea { width:100%; border:1px solid var(--border, #e2e8f0); border-radius:10px; padding:10px 12px; background:var(--surface, #fff); color:var(--ink, #111827); }
    .contract-input-group textarea { min-height:88px; resize:vertical; }
    .contract-help { margin-top:4px; font-size:12px; color:var(--ink-muted, #64748b); }
    .contract-actions { display:flex; justify-content:flex-end; gap:10px; padding-top:6px; }
    .contract-additional-row { display:grid; grid-template-columns:1fr 130px; gap:10px; }
    .contract-pdf-preview { border:1px solid var(--border, #e2e8f0); border-radius:10px; overflow:hidden; background:#f8fafc; }
    .contract-pdf-frame { display:block; width:100%; height:720px; border:0; background:#fff; }
    .contract-paper { border:1px solid var(--ink, #111827); background:#fff; color:#111; padding:18px 20px; font-family:Georgia, "Times New Roman", serif; font-size:13px; line-height:1.28; }
    .contract-paper table { width:100%; border-collapse:collapse; }
    .contract-paper__head { width:56%; margin:0 auto 8px; }
    .contract-paper__logo-img { width:92px; height:48px; object-fit:contain; display:block; }
    .contract-paper__logo { width:92px; height:48px; background:#31583e; color:#fff; border:1px solid #23422f; padding:7px 8px; font:700 7px/1.12 Arial, sans-serif; text-transform:uppercase; display:flex; align-items:center; gap:7px; }
    .contract-paper__logo-bars { width:17px; height:27px; border:1px solid #eee; flex:0 0 auto; }
    .contract-paper__logo-bars span { display:block; height:9px; }
    .contract-paper__logo-bars span:nth-child(1) { background:#c7443e; }
    .contract-paper__logo-bars span:nth-child(2) { background:#f8f8f8; }
    .contract-paper__logo-bars span:nth-child(3) { background:#34844d; }
    .contract-paper__brand { padding-left:12px; vertical-align:middle; }
    .contract-paper__brand strong { display:block; font-size:14px; text-transform:uppercase; }
    .contract-paper__title { text-align:center; font-size:19px; font-weight:900; letter-spacing:.04em; margin:6px 0 4px; }
    .contract-paper__stack { width:280px; margin:0 auto 10px; }
    .contract-paper__stack td,
    .contract-paper__fields td { padding:3px 5px; vertical-align:bottom; }
    .contract-paper__package { margin:6px 0 8px; }
    .contract-paper__package td { padding:3px 5px; vertical-align:bottom; }
    .paper-label { font-size:10.5px; font-weight:900; text-transform:uppercase; white-space:nowrap; }
    .paper-line { border-bottom:1px solid #111; min-height:18px; padding:0 6px 2px; font-weight:400; word-break:break-word; }
    .paper-grid { border:1px solid #111; table-layout:fixed; margin-top:8px; }
    .paper-grid > tbody > tr > td { width:50%; vertical-align:top; }
    .paper-grid > tbody > tr > td + td { border-left:1px solid #111; }
    .paper-title { padding:5px 7px; border-bottom:1px solid #111; font-weight:900; text-decoration:underline; }
    .paper-row td { border-bottom:1px solid #b7b7b7; min-height:24px; padding:5px 7px; vertical-align:bottom; }
    .paper-row:last-child td { border-bottom:0; }
    .paper-service { width:145px; font-weight:900; }
    .paper-detail { border-bottom:1px dotted #777 !important; }
    .paper-additional td { border-bottom:1px solid #b7b7b7; min-height:22px; padding:5px 7px; vertical-align:top; }
    .paper-additional-group td { font-weight:900; background:#fafafa; border-top:1px solid #111; }
    .paper-additional-name { width:38%; font-weight:900; font-size:10.5px; }
    .paper-additional-detail { width:39%; }
    .paper-amount { width:96px; text-align:right; white-space:nowrap; font-weight:700; }
    .paper-note { border-top:1px solid #111; min-height:62px; padding:7px; font-size:11.5px; }
    .paper-totals { border-top:1px solid #111; }
    .paper-totals td { padding:5px 7px; }
    .paper-total-label { width:190px; font-weight:900; }
    .paper-total-value { text-align:right; border-bottom:1px solid #111; font-weight:900; white-space:nowrap; }
    .paper-remarks { margin-top:8px; border:1px solid #111; min-height:42px; }
    .paper-remarks td { padding:7px; vertical-align:top; }
    .paper-remarks-label { width:72px; border-right:1px solid #111; font-weight:900; }
    .paper-burial td { padding:10px 5px 4px; vertical-align:bottom; }
    .paper-lower { margin-top:12px; border-top:1px solid #111; table-layout:fixed; }
    .paper-lower td { vertical-align:top; }
    .paper-stip { width:55%; padding-top:18px; padding-right:22px; }
    .paper-stip-title { text-align:center; font-weight:900; text-transform:uppercase; margin-bottom:4px; }
    .paper-stip-body { font-size:11.5px; text-align:justify; }
    .paper-stip-body p { margin:0 0 4px; text-indent:18px; }
    .paper-manager { padding-top:36px; text-align:center; }
    .paper-manager .paper-sign-line { width:78%; margin:0 auto; }
    .paper-sign-name { min-height:15px; margin-bottom:0; text-align:center; font-weight:800; line-height:1.05; }
    .paper-sign-line { border-top:1px solid #111; min-height:14px; padding-top:2px; text-align:center; font-weight:800; }
    .paper-sign-label { text-align:center; font-size:11px; font-weight:800; margin-top:0; line-height:1.12; }
    .paper-client { width:46%; margin-top:18px; }
    html[data-theme="dark"] .contract-card { background:#111827; border-color:#334155; box-shadow:none; }
    html[data-theme="dark"] .contract-field,
    html[data-theme="dark"] .contract-list li { background:#0f172a; border-color:#334155; }
    html[data-theme="dark"] .contract-title,
    html[data-theme="dark"] .contract-card-title,
    html[data-theme="dark"] .contract-value,
    html[data-theme="dark"] .contract-list li,
    html[data-theme="dark"] .contract-input-group label { color:#f8fafc; }
    html[data-theme="dark"] .contract-input-group input,
    html[data-theme="dark"] .contract-input-group textarea { background:#0f172a; border-color:#334155; color:#f8fafc; }
    html[data-theme="dark"] .contract-paper { background:#fff; color:#111; }
    @media (max-width: 900px) {
        .contract-grid { grid-template-columns:1fr; }
        .contract-preview-head { flex-direction:column; }
    }
    @media (max-width: 640px) {
        .contract-fields { grid-template-columns:1fr; }
        .contract-additional-row { grid-template-columns:1fr; }
        .contract-actions { flex-direction:column-reverse; }
        .contract-actions .btn-secondary,
        .contract-actions .btn-outline { width:100%; justify-content:center; }
    }
</style>

<div class="contract-preview-wrap">
    <div class="contract-preview-head">
        <div>
            <div class="contract-eyebrow">Contract Preview</div>
            <h1 class="contract-title">Review Funeral Contract</h1>
            <p class="contract-subtitle">Review the saved case details that will appear on the Funeral Contract before issuing it.</p>
        </div>
        <a href="{{ route('funeral-cases.show', $funeral_case) }}" class="btn-outline">
            <i class="bi bi-arrow-left mr-1"></i>Back to Case
        </a>
    </div>

    @if($errors->any())
        <div class="flash-error mb-4">{{ $errors->first() }}</div>
    @endif

    <div class="contract-grid">
        <div class="contract-card">
            <div class="contract-card-head">
                <i class="bi bi-file-earmark-text"></i>
                <div class="contract-card-title">Snapshot From Case Record</div>
            </div>
            <div class="contract-card-body">
                <div class="contract-section">
                    <div class="contract-section-title">Print Layout Preview</div>
                    <div class="contract-pdf-preview">
                        <iframe
                            class="contract-pdf-frame"
                            src="{{ route('funeral-cases.documents.contract.preview-pdf', $funeral_case) }}"
                            title="Funeral Contract PDF Preview"
                        ></iframe>
                    </div>
                </div>

                <div class="contract-section">
                    <div class="contract-section-title">Client and Deceased</div>
                    <div class="contract-fields">
                        <div class="contract-field"><div class="contract-label">Contract Number</div><div class="contract-value">{{ data_get($contractView, 'contract_number') }}</div></div>
                        <div class="contract-field"><div class="contract-label">Case Number</div><div class="contract-value">{{ data_get($contractView, 'case_number') }}</div></div>
                        <div class="contract-field"><div class="contract-label">Contract Date</div><div class="contract-value">{{ $fmtDate(data_get($contractView, 'contract_date')) }}</div></div>
                        <div class="contract-field"><div class="contract-label">Client</div><div class="contract-value">{{ data_get($client, 'name') }}</div></div>
                        <div class="contract-field"><div class="contract-label">Contact</div><div class="contract-value">{{ data_get($client, 'contact_number') }}</div></div>
                        <div class="contract-field"><div class="contract-label">Address</div><div class="contract-value">{{ data_get($client, 'address') }}</div></div>
                        <div class="contract-field"><div class="contract-label">Deceased</div><div class="contract-value">{{ data_get($deceased, 'name') }}</div></div>
                    </div>
                </div>

                <div class="contract-section">
                    <div class="contract-section-title">Basic Funeral Service Inclusions</div>
                    <ul class="contract-list">
                        <li><i class="bi bi-check2-circle"></i><span>{{ data_get($package, 'name') }} - {{ $fmtMoney(data_get($package, 'base_price')) }}</span></li>
                        @foreach(data_get($contractView, 'basic_rows', []) as $row)
                            <li><i class="bi bi-check2-circle"></i><span>{{ data_get($row, 'label') }}: {{ data_get($row, 'value') }}</span></li>
                        @endforeach
                        @forelse(data_get($contractView, 'other_inclusions', []) as $item)
                            <li><i class="bi bi-check2-circle"></i><span>Other inclusion: {{ $item }}</span></li>
                        @empty
                            @if(count(data_get($contractView, 'basic_rows', [])) === 0)
                                <li><i class="bi bi-info-circle"></i><span>No saved inclusion details available.</span></li>
                            @endif
                        @endforelse
                    </ul>
                </div>

                <div class="contract-section">
                    <div class="contract-section-title">Casket Mapping</div>
                    <div class="contract-fields">
                        <div class="contract-field"><div class="contract-label">Included Casket</div><div class="contract-value">{{ data_get($contractView, 'included_casket.name') ?: 'Not included' }}</div></div>
                        <div class="contract-field"><div class="contract-label">Included Reference Value</div><div class="contract-value">{{ $fmtMoney(data_get($contractView, 'included_casket.reference_value')) }}</div></div>
                        <div class="contract-field"><div class="contract-label">Selected Casket</div><div class="contract-value">{{ data_get($contractView, 'selected_casket.name') ?: 'No replacement selected' }}</div></div>
                        <div class="contract-field"><div class="contract-label">Selected Reference Value</div><div class="contract-value">{{ $fmtMoney(data_get($contractView, 'selected_casket.reference_value')) }}</div></div>
                    </div>
                </div>

                <div class="contract-section">
                    <div class="contract-section-title">Freebies</div>
                    <ul class="contract-list">
                        @forelse(data_get($contractView, 'freebies', []) as $item)
                            <li><i class="bi bi-gift"></i><span>{{ $item }}</span></li>
                        @empty
                            <li><i class="bi bi-info-circle"></i><span>No saved freebies.</span></li>
                        @endforelse
                    </ul>
                </div>

                <div class="contract-section">
                    <div class="contract-section-title">Automatic Adjustments</div>
                    <ul class="contract-list">
                        @forelse(data_get($contractView, 'automatic_charges', []) as $item)
                            <li><i class="bi bi-plus-circle"></i><span>{{ data_get($item, 'label') }}: {{ data_get($item, 'display') }}</span></li>
                        @empty
                            <li><i class="bi bi-info-circle"></i><span>No saved automatic adjustments.</span></li>
                        @endforelse
                    </ul>
                </div>

                <div class="contract-section">
                    <div class="contract-section-title">Saved Global Add-ons</div>
                    <ul class="contract-list">
                        @forelse(data_get($contractView, 'add_ons', []) as $item)
                            <li><i class="bi bi-plus-circle"></i><span>{{ data_get($item, 'display') }}</span></li>
                        @empty
                            <li><i class="bi bi-info-circle"></i><span>No saved add-ons.</span></li>
                        @endforelse
                    </ul>
                </div>

                <div class="contract-section">
                    <div class="contract-section-title">Itemized Additional Services</div>
                    <ul class="contract-list">
                        @forelse(data_get($contractView, 'itemized_services', []) as $item)
                            <li><i class="bi bi-plus-circle"></i><span>{{ data_get($item, 'description') }} - {{ $fmtMoney(data_get($item, 'amount')) }}</span></li>
                        @empty
                            <li><i class="bi bi-info-circle"></i><span>No saved itemized services.</span></li>
                        @endforelse
                    </ul>
                </div>

                <div class="contract-section">
                    <div class="contract-section-title">Burial Schedule</div>
                    <div class="contract-fields">
                        <div class="contract-field"><div class="contract-label">Interment Date</div><div class="contract-value">{{ $fmtDate(data_get($schedule, 'interment_date')) }}</div></div>
                        <div class="contract-field"><div class="contract-label">Interment Time</div><div class="contract-value">{{ $fmtTime(data_get($schedule, 'interment_time')) ?: 'Optional' }}</div></div>
                        <div class="contract-field"><div class="contract-label">Cemetery</div><div class="contract-value">{{ data_get($schedule, 'cemetery') ?: 'Not set' }}</div></div>
                        <div class="contract-field"><div class="contract-label">Wake Duration</div><div class="contract-value">{{ data_get($schedule, 'wake_duration') ?: 'Not set' }}</div></div>
                    </div>
                </div>

                <div class="contract-section">
                    <div class="contract-section-title">Totals</div>
                    <div class="contract-total-row"><span>Base Package Price</span><strong>{{ $fmtMoney(data_get($contractView, 'totals.base_package_price')) }}</strong></div>
                    <div class="contract-total-row"><span>Total Additional Services</span><strong>{{ $fmtMoney(data_get($contractView, 'totals.total_additional_services')) }}</strong></div>
                    <div class="contract-total-row"><span>Subtotal</span><strong>{{ $fmtMoney(data_get($contractView, 'totals.subtotal')) }}</strong></div>
                    @if((float) data_get($contractView, 'totals.discount_amount', 0) > 0)
                        <div class="contract-total-row"><span>{{ data_get($contractView, 'totals.discount_label') }}</span><strong>-{{ $fmtMoney(data_get($contractView, 'totals.discount_amount')) }}</strong></div>
                    @endif
                    @if((float) data_get($contractView, 'totals.tax', 0) > 0)
                        <div class="contract-total-row"><span>Tax</span><strong>{{ $fmtMoney(data_get($contractView, 'totals.tax')) }}</strong></div>
                    @endif
                    <div class="contract-total-row"><span>Total</span><strong>{{ $fmtMoney(data_get($contractView, 'totals.contract_total')) }}</strong></div>
                    <div class="contract-total-row"><span>Deposit</span><strong>{{ $fmtMoney(data_get($contractView, 'totals.initial_deposit')) }}</strong></div>
                    <div class="contract-total-row"><span>Paid</span><strong>{{ $fmtMoney(data_get($contractView, 'totals.total_paid')) }}</strong></div>
                    <div class="contract-total-row"><span>Balance</span><strong>{{ $fmtMoney(data_get($contractView, 'totals.remaining_balance')) }}</strong></div>
                </div>
            </div>
        </div>

        <div class="contract-card">
            <div class="contract-card-head">
                <i class="bi bi-pencil-square"></i>
                <div class="contract-card-title">Optional Contract Details</div>
            </div>
            <div class="contract-card-body">
                @if($pricingMismatch ?? false)
                    <div class="flash-error mb-4">
                        The saved pricing breakdown does not match the case total. Please review the case billing record before finalizing the contract.
                    </div>
                @endif

                <form method="POST" action="{{ route('funeral-cases.documents.contract.store', $funeral_case) }}" class="contract-form-grid">
                    @csrf

                    <div class="contract-input-group">
                        <label for="interment_time">Interment Time</label>
                        <input id="interment_time" type="time" name="interment_time" value="{{ $intermentTimeValue }}">
                    </div>

                    <div class="contract-input-group">
                        <label for="remarks">Remarks</label>
                        <textarea id="remarks" name="remarks" placeholder="Optional remarks for the contract">{{ old('remarks', data_get($contractSnapshot, 'remarks')) }}</textarea>
                        <div class="contract-help">Charges shown above come only from saved case pricing records.</div>
                    </div>

                    <div class="contract-actions">
                        <a href="{{ route('funeral-cases.show', $funeral_case) }}" class="btn-outline">Cancel</a>
                        <button type="submit" class="btn-secondary" @disabled($pricingMismatch ?? false)>
                            <i class="bi bi-check2-circle mr-1"></i>Save and Generate PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
