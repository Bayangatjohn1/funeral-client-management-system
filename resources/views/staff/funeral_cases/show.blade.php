@extends('layouts.panel')

@section('hide_layout_topbar', '1')
@section('page_title', 'Case Full Information')
@section('page_desc', '')

@section('content')
@php
    $defaultReturnUrl = ($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH'
        ? route('funeral-cases.other-reports')
        : ($funeral_case->case_status === 'COMPLETED'
            ? route('funeral-cases.index', ['tab' => 'completed'])
            : route('funeral-cases.index', ['tab' => 'active']));
    $requestedReturnUrl = request()->query('return_to');
    $previousUrl = url()->previous();
    $currentUrl = request()->fullUrl();
    $returnUrl = is_string($requestedReturnUrl) && $requestedReturnUrl !== ''
        ? $requestedReturnUrl
        : ($previousUrl !== $currentUrl ? $previousUrl : $defaultReturnUrl);
    if (
        !is_string($returnUrl)
        || $returnUrl === ''
        || !\Illuminate\Support\Str::startsWith($returnUrl, [url('/'), '/'])
    ) {
        $returnUrl = $defaultReturnUrl;
    }
    $canRecordPayment = auth()->user()?->can('create', \App\Models\Payment::class)
        && (($funeral_case->entry_source ?? 'MAIN') !== 'OTHER_BRANCH')
        && (int) $funeral_case->branch_id === (int) (auth()->user()?->branch_id ?? 0)
        && (float) $funeral_case->balance_amount > 0;
    $paymentLockedMessage = (($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH')
        ? 'This other-branch case is locked for payment updates.'
        : null;
    $isOverlay = request()->boolean('overlay');
    $printSnapshot = app(\App\Support\CaseSnapshotDisplayService::class)->data($funeral_case);
    $printPackageName = $printSnapshot['package_name'] ?? $funeral_case->service_package ?? 'Saved Package';
    $printPackagePrice = (float) ($printSnapshot['package_price'] ?? $funeral_case->subtotal_amount ?? 0);
    $printCasket = ($printSnapshot['included_casket']['name'] ?? null) ?: $funeral_case->coffin_type;
    $printIntermentAt = $funeral_case->interment_at
        ?? $funeral_case->deceased?->interment_at
        ?? $funeral_case->serviceDetail?->internment_date
        ?? $funeral_case->deceased?->interment;
    $printDate = fn($dt) => $dt ? $dt->format('M d, Y') : 'Not set';
    $printTime = fn($time) => $time ? \Carbon\Carbon::parse($time)->format('h:i A') : 'Time not set';
    $printSchedule = fn($date, $time) => ($date ? $date->format('M d, Y') : 'Not set') . ' at ' . $printTime($time);
@endphp

@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="flash-error">{{ $errors->first() }}</div>
@endif
@if($paymentLockedMessage)
    <div class="flash-info">{{ $paymentLockedMessage }}</div>
@endif
@if($canRecordPayment)
    <section class="case-record-toolbar" aria-label="Case payment entry">
        <div class="case-record-actions">
            <a class="btn-outline" href="{{ route('payments.index', ['case_id' => $funeral_case->id, 'open_payment' => 1]) }}">
                Add Payment
            </a>
            <span class="text-sm">Resulting Payment Status</span>
        </div>
    </section>
@endif

<style>
    .case-detail-shell,
    .case-record-toolbar {
        width:min(100%, 112rem);
        margin:0 auto;
        padding-left:var(--panel-content-inline, 20px);
        padding-right:var(--panel-content-inline, 20px);
    }
    .case-record-toolbar {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:.75rem;
        margin-top:4px;
        margin-bottom:28px;
        padding-top:0;
    }
    .case-record-actions {
        display:flex;
        align-items:center;
        gap:.55rem;
        flex-wrap:wrap;
    }
    .case-detail-shell { padding-top:12px; padding-bottom:20px; }
    .case-record-toolbar .btn-outline {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:.45rem;
        min-height:2.65rem;
        padding:0 .95rem;
        border-radius:.75rem;
        background:#E1E7D9;
        border:1px solid var(--border);
        border-color:var(--border);
        color:var(--ink);
        font-size:.84rem;
        font-weight:700;
        box-shadow:none;
        text-decoration:none;
        transition:background-color .16s ease, border-color .16s ease, color .16s ease;
    }
    .case-record-toolbar .btn-outline i,
    .case-print-btn i {
        margin:0;
        font-size:.95rem;
        line-height:1;
    }
    .case-record-toolbar .btn-outline:hover {
        background:#C7D5BE;
        border-color:#8EA083;
        color:var(--ink);
    }
    .case-print-btn {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:.45rem;
        min-height:2.65rem;
        border-radius:.75rem;
        border:1px solid var(--accent);
        background:var(--accent);
        color:#fff;
        padding:0 .95rem;
        font-size:.84rem;
        font-weight:700;
        cursor:pointer;
        box-shadow:none;
        transition:background-color .16s ease, border-color .16s ease;
    }
    .case-print-btn:hover { background:#2F3A2E; border-color:#2F3A2E; }
    @media(max-width:640px) {
        .case-record-toolbar { align-items:stretch; flex-direction:column; }
        .case-record-actions, .case-record-toolbar .btn-outline, .case-print-btn { width:100%; justify-content:center; }
    }
</style>

{{-- Shared case detail content --}}
<div class="case-detail-shell">
    @include('partials.case_view_content')
</div>

<template id="casePrintTemplate">
    <div class="print-record">
        <div class="print-header">
            <div>
                <div class="print-brand">Sabangan Caguioa</div>
                <div class="print-subtitle">Funeral Home System</div>
            </div>
            <div class="print-code">
                <span>Case Record</span>
                <strong>{{ $funeral_case->case_code }}</strong>
            </div>
        </div>

        <div class="print-section">
            <div class="print-section-title">Case Information</div>
            <div class="print-grid print-grid-3">
                <div class="print-field">
                    <span>Branch</span>
                    <strong>{{ $funeral_case->branch?->branch_code ?? 'N/A' }}{{ $funeral_case->branch?->branch_name ? ' - ' . $funeral_case->branch->branch_name : '' }}</strong>
                </div>
                <div class="print-field">
                    <span>Date Created</span>
                    <strong>{{ $printDate($funeral_case->service_requested_at ?? $funeral_case->created_at) }}</strong>
                </div>
                <div class="print-field">
                    <span>Encoded By</span>
                    <strong>{{ $funeral_case->encodedBy?->name ?? 'N/A' }}</strong>
                </div>
                <div class="print-field">
                    <span>Case Status</span>
                    <strong>{{ $funeral_case->case_status ?? 'N/A' }}</strong>
                </div>
                <div class="print-field">
                    <span>Payment Status</span>
                    <strong>{{ $funeral_case->payment_status ?? 'UNPAID' }}</strong>
                </div>
                <div class="print-field">
                    <span>Interment</span>
                    <strong>{{ $printIntermentAt ? $printIntermentAt->format('M d, Y') : 'Not set' }}</strong>
                </div>
            </div>
        </div>

        <div class="print-section">
            <div class="print-section-title">Client Information</div>
            <div class="print-grid print-grid-2">
                <div class="print-field">
                    <span>Full Name</span>
                    <strong>{{ $funeral_case->client?->full_name ?? 'N/A' }}</strong>
                </div>
                <div class="print-field">
                    <span>Contact Number</span>
                    <strong>{{ $funeral_case->client?->contact_number ?? 'N/A' }}</strong>
                </div>
                <div class="print-field print-full">
                    <span>Address</span>
                    <strong>{{ $funeral_case->client?->address ?: 'N/A' }}</strong>
                </div>
            </div>
        </div>

        <div class="print-section">
            <div class="print-section-title">Deceased Information</div>
            <div class="print-grid print-grid-3">
                <div class="print-field print-full">
                    <span>Full Name</span>
                    <strong>{{ $funeral_case->deceased?->full_name ?? 'N/A' }}</strong>
                </div>
                <div class="print-field">
                    <span>Date of Birth</span>
                    <strong>{{ $printDate($funeral_case->deceased?->born) }}</strong>
                </div>
                <div class="print-field">
                    <span>Date of Death</span>
                    <strong>{{ $printDate($funeral_case->deceased?->died ?? $funeral_case->deceased?->date_of_death) }}</strong>
                </div>
                <div class="print-field">
                    <span>Age</span>
                    <strong>{{ $funeral_case->deceased?->age ?? 'N/A' }}</strong>
                </div>
                <div class="print-field">
                    <span>Cemetery</span>
                    <strong>{{ $funeral_case->deceased?->place_of_cemetery ?? 'N/A' }}</strong>
                </div>
                <div class="print-field print-full">
                    <span>Address</span>
                    <strong>{{ $funeral_case->deceased?->address ?: 'N/A' }}</strong>
                </div>
            </div>
        </div>

        <div class="print-section">
            <div class="print-section-title">Package &amp; Service Schedule</div>
            <div class="print-grid print-grid-2">
                <div class="print-field">
                    <span>Package</span>
                    <strong>{{ $printPackageName }}</strong>
                </div>
                <div class="print-field">
                    <span>Package Price</span>
                    <strong>PHP {{ number_format($printPackagePrice, 2) }}</strong>
                </div>
                <div class="print-field">
                    <span>Included Casket / Coffin</span>
                    <strong>{{ $printCasket ?: 'N/A' }}</strong>
                </div>
                <div class="print-field">
                    <span>Service Type</span>
                    <strong>{{ $funeral_case->service_type ?: 'N/A' }}</strong>
                </div>
                <div class="print-field">
                    <span>Wake Location</span>
                    <strong>{{ $funeral_case->wake_location ?: 'N/A' }}</strong>
                </div>
                <div class="print-field">
                    <span>Wake Start</span>
                    <strong>{{ $printSchedule($funeral_case->wake_start_date, $funeral_case->wake_start_time) }}</strong>
                </div>
                <div class="print-field">
                    <span>Funeral Service</span>
                    <strong>{{ $funeral_case->funeral_service_at ? $printSchedule($funeral_case->funeral_service_at, $funeral_case->funeral_service_time) : 'Not set' }}</strong>
                </div>
                <div class="print-field">
                    <span>Interment</span>
                    <strong>{{ $printIntermentAt ? $printSchedule($printIntermentAt, $funeral_case->interment_time ?? $printIntermentAt?->format('H:i:s')) : 'Not set' }}</strong>
                </div>
                <div class="print-field">
                    <span>Wake Duration</span>
                    <strong>{{ \App\Support\WakeDuration::labelFromDays($funeral_case->deceased?->wake_days) ?: 'Not set' }}</strong>
                </div>
            </div>
        </div>

        <div class="print-section">
            <div class="print-section-title">Payment Summary</div>
            <div class="print-grid print-grid-3">
                <div class="print-field">
                    <span>Service Amount</span>
                    <strong>PHP {{ number_format((float) $funeral_case->total_amount, 2) }}</strong>
                </div>
                <div class="print-field">
                    <span>Total Paid</span>
                    <strong>PHP {{ number_format((float) $funeral_case->total_paid, 2) }}</strong>
                </div>
                <div class="print-field">
                    <span>Remaining Balance</span>
                    <strong>PHP {{ number_format((float) $funeral_case->balance_amount, 2) }}</strong>
                </div>
            </div>
        </div>

        <div class="print-footer">
            <div>Printed on {{ now()->format('M d, Y h:i A') }}</div>
            <div>This record contains main case information only.</div>
        </div>
    </div>
</template>

{{-- Record actions --}}
<div class="no-print case-record-toolbar">
    @if(!$isOverlay)
        <a href="{{ $returnUrl }}" class="btn-outline">
            <i class="bi bi-arrow-left"></i><span>Back</span>
        </a>
    @else
        <div></div>
    @endif
    <div class="case-record-actions">
        <button id="printCaseBtn" type="button" class="case-print-btn">
            <i class="bi bi-printer"></i><span>Print Record</span>
        </button>
    </div>
</div>

<style>
    @media print {
        nav, aside, footer, header, .no-print { display: none !important; }
        html, body { height: auto !important; overflow: visible !important; background: white !important; }
        #caseViewContent { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        #caseViewContent,
        #caseViewContent * {
            color: #111827 !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }
        #caseViewContent .cv-shell { gap: 10px !important; }
        #caseViewContent .cv-card {
            background: #fff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0 !important;
            break-inside: avoid;
        }
        #caseViewContent .cv-card-head {
            background: #f3f4f6 !important;
            border-bottom: 1px solid #d1d5db !important;
            padding: 8px 10px !important;
        }
        #caseViewContent .cv-card-icon,
        #caseViewContent .cv-modify-btn,
        #caseViewContent .cv-tarp-actions,
        #caseViewContent .cv-tarp-form,
        #caseViewContent .cv-tarp-modal,
        #caseViewContent .cv-doc-actions {
            display: none !important;
        }
        #caseViewContent .cv-card-title,
        #caseViewContent .cv-field-label,
        #caseViewContent .cv-meta-label,
        #caseViewContent .cv-stat-lbl,
        #caseViewContent .cv-fin-label,
        #caseViewContent .cv-txn-lbl {
            color: #374151 !important;
            font-weight: 700 !important;
        }
        #caseViewContent .cv-fields,
        #caseViewContent .cv-stat-row,
        #caseViewContent .cv-fin-grid,
        #caseViewContent .cv-doc-wrap,
        #caseViewContent .cv-txn-list,
        #caseViewContent .cv-addon-list,
        #caseViewContent .cv-pkg-pair {
            padding: 10px !important;
            gap: 8px !important;
        }
        #caseViewContent .cv-field,
        #caseViewContent .cv-meta-item,
        #caseViewContent .cv-stat,
        #caseViewContent .cv-fin-item,
        #caseViewContent .cv-pkg-box,
        #caseViewContent .cv-addon-row,
        #caseViewContent .cv-txn,
        #caseViewContent .cv-doc-row {
            background: #fff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0 !important;
            padding: 8px 10px !important;
        }
        #caseViewContent .cv-field-tarpaulin {
            min-height: 0 !important;
            grid-column: auto !important;
            grid-row: auto !important;
        }
        #caseViewContent .cv-tarp-grid {
            display: block !important;
            padding: 10px !important;
        }
        #caseViewContent .cv-tarp-preview {
            min-height: 80px !important;
            max-height: 120px !important;
            background: #fff !important;
            border: 1px dashed #d1d5db !important;
        }
        #caseViewContent .cv-tarp-note {
            margin-top: 6px !important;
        }
    }
</style>


<script>
    (() => {
        const btn = document.getElementById('printCaseBtn');
        const template = document.getElementById('casePrintTemplate');
        if (!btn || !template) return;
        btn.addEventListener('click', () => {
            const iframe = document.createElement('iframe');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
            document.body.appendChild(iframe);
            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<!doctype html><html><head>');
            doc.write(`<style>
                @page { size: auto; margin: 14mm; }
                * { box-sizing: border-box; }
                body {
                    margin: 0;
                    padding: 0;
                    background: #fff;
                    color: #111827;
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.45;
                }
                .print-record { width: 100%; }
                .print-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 20px;
                    padding-bottom: 14px;
                    border-bottom: 2px solid #111827;
                    margin-bottom: 14px;
                }
                .print-brand {
                    font-size: 20px;
                    font-weight: 700;
                    letter-spacing: .01em;
                }
                .print-subtitle {
                    font-size: 11px;
                    color: #4b5563;
                    text-transform: uppercase;
                    letter-spacing: .08em;
                    margin-top: 2px;
                }
                .print-code {
                    text-align: right;
                    display: grid;
                    gap: 2px;
                }
                .print-code span,
                .print-field span,
                .print-section-title {
                    color: #4b5563;
                    font-size: 10px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: .08em;
                }
                .print-code strong {
                    font-size: 18px;
                    font-family: Arial, sans-serif;
                }
                .print-section {
                    border: 1px solid #d1d5db;
                    margin-bottom: 10px;
                    break-inside: avoid;
                }
                .print-section-title {
                    padding: 8px 10px;
                    background: #f3f4f6;
                    border-bottom: 1px solid #d1d5db;
                }
                .print-grid {
                    display: grid;
                    gap: 0;
                }
                .print-grid-2 { grid-template-columns: 1fr 1fr; }
                .print-grid-3 { grid-template-columns: repeat(3, 1fr); }
                .print-field {
                    min-height: 52px;
                    padding: 9px 10px;
                    border-right: 1px solid #e5e7eb;
                    border-bottom: 1px solid #e5e7eb;
                }
                .print-field:nth-child(2n) { border-right: 0; }
                .print-grid-3 .print-field:nth-child(2n) { border-right: 1px solid #e5e7eb; }
                .print-grid-3 .print-field:nth-child(3n) { border-right: 0; }
                .print-field strong {
                    display: block;
                    margin-top: 3px;
                    color: #111827;
                    font-size: 12.5px;
                    font-weight: 700;
                }
                .print-full { grid-column: 1 / -1; border-right: 0 !important; }
                .print-footer {
                    display: flex;
                    justify-content: space-between;
                    gap: 16px;
                    margin-top: 14px;
                    padding-top: 8px;
                    border-top: 1px solid #d1d5db;
                    color: #4b5563;
                    font-size: 10px;
                }
            </style>`);
            doc.write('</head><body>');
            doc.write(template.innerHTML);
            doc.write('</body></html>');
            doc.close();
            iframe.onload = () => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => iframe.remove(), 500);
            };
        });
    })();
</script>
@endsection
