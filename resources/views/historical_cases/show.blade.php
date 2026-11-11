@extends('layouts.panel')
@section('page_title', 'Historical Submission')
@section('page_desc', 'Historical case review')
@section('page_back')
    <a href="{{ route('historical-cases.index') }}" class="hc-detail-back" aria-label="Back to historical cases"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Back to cases</span></a>
@endsection
@push('worklist-styles')
<style>
    .historical-case-detail-page { display:grid; gap:16px; width:min(1120px,100%); margin:0 auto; padding:20px 16px; color:var(--rs-text,var(--ink)); }
    .panel-page-header .hc-detail-back { display:inline-flex; min-height:40px; align-items:center; justify-content:center; gap:8px; padding:0 14px; border:0; border-radius:10px; background:var(--rs-control,var(--surface-muted)); color:var(--rs-text,var(--ink)); font-size:13px; font-weight:600; text-decoration:none; }
    .panel-page-header .hc-detail-back:hover { background:var(--rs-hover,var(--surface-muted)); }
    .panel-page-header .hc-detail-back:focus-visible { outline:2px solid var(--filter-ui-focus,#24513e); outline-offset:2px; }
    .hc-detail-alert { padding:12px 14px; border-radius:10px; font-size:14px; }
    .hc-detail-alert--success { background:#DCEFE3; color:#166534; }
    .hc-detail-alert--error { background:#FDE8E7; color:#B42318; }
    html[data-theme='dark'] .hc-detail-alert--success { background:#183829; color:#86EFAC; }
    html[data-theme='dark'] .hc-detail-alert--error { background:#442326; color:#FDA29B; }
    .hc-detail-surface { display:grid; gap:20px; padding:24px; border:0; border-radius:12px; background:var(--rs-surface,var(--card)); box-shadow:none; }
    .hc-detail-identity { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:16px; padding-bottom:20px; border-bottom:1px solid var(--rs-divider,var(--border)); }
    .hc-detail-code { color:var(--rs-muted,var(--ink-muted)); font-size:12px; font-weight:650; letter-spacing:.055em; }
    .hc-detail-name { margin-top:4px; color:var(--rs-text,var(--ink)); font-size:24px; font-weight:700; line-height:1.25; }
    .hc-detail-client { margin-top:5px; color:var(--rs-muted,var(--ink-muted)); font-size:14px; }
    .hc-detail-status { color:#175CD3; font-size:14px; font-weight:600; }
    .hc-detail-status[data-status='APPROVED'] { color:#166534; }
    .hc-detail-status[data-status='NEEDS_CHANGES'] { color:#92400E; }
    .hc-detail-status[data-status='REJECTED'] { color:#B42318; }
    html[data-theme='dark'] .hc-detail-status { color:#84B8FF; }
    html[data-theme='dark'] .hc-detail-status[data-status='APPROVED'] { color:#4ADE80; }
    html[data-theme='dark'] .hc-detail-status[data-status='NEEDS_CHANGES'] { color:#FBBF24; }
    html[data-theme='dark'] .hc-detail-status[data-status='REJECTED'] { color:#FDA29B; }
    .hc-detail-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:0 24px; }
    .hc-detail-field { min-width:0; padding:12px 0; border-bottom:1px solid var(--rs-divider,var(--border)); }
    .hc-detail-field-label { color:var(--rs-muted,var(--ink-muted)); font-size:12px; font-weight:600; }
    .hc-detail-field-value { margin-top:4px; color:var(--rs-text,var(--ink)); font-size:14px; font-weight:600; overflow-wrap:anywhere; }
    .hc-detail-section { padding-top:20px; border-top:1px solid var(--rs-divider,var(--border)); }
    .hc-detail-section:first-child { padding-top:0; border-top:0; }
    .hc-detail-section-title { color:var(--rs-text,var(--ink)); font-size:15px; font-weight:700; }
    .hc-detail-section-copy { margin-top:5px; color:var(--rs-muted,var(--ink-muted)); font-size:13px; line-height:1.55; }
    .hc-detail-notice { padding:14px 16px; border-radius:10px; background:color-mix(in srgb,#FBBF24 14%,var(--rs-surface,var(--card))); color:var(--rs-text,var(--ink)); }
    .hc-detail-links { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
    .hc-detail-link { display:inline-flex; min-height:38px; align-items:center; gap:7px; padding:0 12px; border-radius:8px; background:var(--rs-control,var(--surface-muted)); color:var(--rs-text,var(--ink)); font-size:13px; font-weight:600; text-decoration:none; }
    .hc-detail-link:hover { background:var(--rs-hover,var(--surface-muted)); color:#175CD3; }
    .hc-payment-list { display:grid; margin-top:12px; }
    .hc-payment-row { display:grid; grid-template-columns:.8fr 1.1fr .8fr 1.1fr; gap:16px; padding:14px 0; border-top:1px solid var(--rs-divider,var(--border)); }
    .hc-payment-row:first-child { border-top:0; }
    .hc-payment-field small { display:block; color:var(--rs-muted,var(--ink-muted)); font-size:11px; font-weight:600; }
    .hc-payment-field strong { display:block; margin-top:4px; color:var(--rs-text,var(--ink)); font-size:13px; overflow-wrap:anywhere; }
    .hc-payment-meta { grid-column:1/-1; }
    .hc-review-history { display:grid; gap:8px; margin-top:10px; }
    .hc-review-entry { padding:10px 0; border-top:1px solid var(--rs-divider,var(--border)); color:var(--rs-text,var(--ink)); font-size:13px; }
    .hc-review-entry:first-child { border-top:0; }
    .hc-review-form { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:end; gap:16px; margin-top:12px; }
    .hc-review-form textarea { min-height:104px; width:100%; padding:12px 14px; border:0; border-radius:10px; background:var(--rs-control,var(--surface-muted)); color:var(--rs-text,var(--ink)); resize:vertical; }
    .hc-review-form textarea:focus-visible { outline:2px solid var(--filter-ui-focus,#24513e); outline-offset:2px; }
    .hc-review-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
    .hc-review-action { display:inline-flex; min-height:42px; align-items:center; justify-content:center; padding:0 14px; border:0; border-radius:9px; font-size:13px; font-weight:600; }
    .hc-review-action--warning { background:#FEF0C7; color:#92400E; }
    .hc-review-action--danger { background:#FEE4E2; color:#B42318; }
    .hc-review-action--success { background:#DCEFE3; color:#166534; }
    .hc-review-action--warning:hover { background:#FDE5A5; }
    .hc-review-action--danger:hover { background:#FECACA; }
    .hc-review-action--success:hover { background:#C7E6D2; }
    html[data-theme='dark'] .hc-review-action--warning { background:#493719; color:#FBBF24; }
    html[data-theme='dark'] .hc-review-action--danger { background:#442326; color:#FDA29B; }
    html[data-theme='dark'] .hc-review-action--success { background:#183829; color:#86EFAC; }
    @media(max-width:800px) {
        .hc-detail-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .hc-payment-row { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .hc-review-form { grid-template-columns:1fr; }
        .hc-review-actions { justify-content:flex-start; }
    }
    @media(max-width:520px) {
        .historical-case-detail-page { padding:16px; }
        .hc-detail-surface { padding:18px 16px; }
        .hc-detail-grid,.hc-payment-row { grid-template-columns:1fr; }
        .panel-page-header .hc-detail-back { width:40px; padding:0; }
        .panel-page-header .hc-detail-back span { display:none; }
    }
</style>
@endpush
@section('content')
@php($data = $submission->submission_data)
@php($v = $data['validated'] ?? [])
<main class="historical-case-detail-page">
    @if(session('success'))<div class="hc-detail-alert hc-detail-alert--success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="hc-detail-alert hc-detail-alert--error" role="alert">{{ $errors->first() }}</div>@endif
    <article class="hc-detail-surface">
        <header class="hc-detail-identity"><div><p class="hc-detail-code">{{ $submission->submission_code }}</p><h1 class="hc-detail-name">{{ trim(($v['deceased_first_name'] ?? '').' '.($v['deceased_last_name'] ?? '')) }}</h1><p class="hc-detail-client">Client: {{ trim(($v['client_first_name'] ?? '').' '.($v['client_last_name'] ?? '')) }}</p></div><span class="hc-detail-status" data-status="{{ $submission->status }}">{{ \Illuminate\Support\Str::headline($submission->status) }}</span></header>
        <section class="hc-detail-grid" aria-label="Submission details">
            @foreach(['Branch' => $submission->branch?->branch_code.' · '.$submission->branch?->branch_name, 'Encoded by' => $submission->encoder?->name, 'Date encoded' => $submission->created_at?->format('M d, Y h:i A'), 'Client' => trim(($v['client_first_name'] ?? '').' '.($v['client_middle_name'] ?? '').' '.($v['client_last_name'] ?? '').' '.($v['client_suffix'] ?? '')), 'Deceased' => trim(($v['deceased_first_name'] ?? '').' '.($v['deceased_middle_name'] ?? '').' '.($v['deceased_last_name'] ?? '').' '.($v['deceased_suffix'] ?? '')), 'Date of death' => $v['died'] ?? '—', 'Wake start' => ($v['wake_start_date'] ?? '—').' '.($v['wake_start_time'] ?? ''), 'Wake end' => ($v['wake_end_date'] ?? '—').' '.($v['wake_end_time'] ?? ''), 'Funeral ceremony' => ($v['funeral_service_at'] ?? '—').' '.($v['funeral_service_time'] ?? ''), 'Interment' => ($v['interment_at'] ?? '—').' '.($v['interment_time'] ?? ''), 'Package' => $v['custom_package_name'] ?? ($data['service_package_name'] ?? '—'), 'Service amount' => '₱'.number_format((float)($data['pricing']['total'] ?? 0), 2), 'Recorded payments' => count($data['historical_payments'] ?? [])] as $label => $value)
                <div class="hc-detail-field"><div class="hc-detail-field-label">{{ $label }}</div><div class="hc-detail-field-value">{{ $value }}</div></div>
            @endforeach
        </section>
        @if(!empty($v['historical_entry_reason']))<section class="hc-detail-notice"><h2 class="hc-detail-section-title">Reason for historical entry</h2><p class="hc-detail-section-copy">{{ $v['historical_entry_reason'] }}</p></section>@endif
        @if(!empty($data['photo_path']) || !empty($data['senior_proof_path']))
            <section class="hc-detail-section"><h2 class="hc-detail-section-title">Supporting documents</h2><div class="hc-detail-links">
                @foreach(['Deceased photo' => 'photo_path', 'Senior ID / certificate proof' => 'senior_proof_path'] as $label => $pathKey)
                    @if(!empty($data[$pathKey]))<a class="hc-detail-link" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($data[$pathKey]) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark" aria-hidden="true"></i>View {{ $label }}</a>@endif
                @endforeach
            </div></section>
        @endif
        <section class="hc-detail-section">
            <h2 class="hc-detail-section-title">Historical payment ledger</h2>
            <p class="hc-detail-section-copy">Actual recorded transactions supplied for this case. A missing proof explanation is shown for reviewer assessment.</p>
            <div class="hc-payment-list">
            @forelse($data['historical_payments'] ?? [] as $paymentIndex => $payment)
                @php($details = $payment['details'] ?? [])
                <div class="hc-payment-row">
                    <div class="hc-payment-field"><small>Payment {{ $paymentIndex + 1 }}</small><strong>₱{{ number_format((float)($payment['amount'] ?? 0), 2) }}</strong></div>
                    <div class="hc-payment-field"><small>Actual date &amp; time</small><strong>{{ !empty($payment['paid_at']) ? \Carbon\Carbon::parse($payment['paid_at'])->format('M d, Y h:i A') : 'Not recorded' }}</strong></div>
                    <div class="hc-payment-field"><small>Method</small><strong>{{ \App\Support\Payments\PaymentDetails::label((object) ['payment_method' => $details['payment_method'] ?? null, 'payment_mode' => $details['legacy_payment_mode'] ?? null, 'cashless_type' => $details['cashless_type'] ?? null]) }}</strong></div>
                    <div class="hc-payment-field"><small>Receipt / Reference</small><strong>{{ $payment['receipt_or_no'] ?: ($details['reference_number'] ?? 'Not recorded') }}</strong></div>
                    @if(!empty($payment['proof']['path']))
                    <div class="hc-payment-meta"><a class="hc-detail-link" href="{{ route('historical-cases.payment-proof', [$submission, $paymentIndex]) }}" target="_blank" rel="noopener"><i class="bi bi-receipt" aria-hidden="true"></i>View payment proof{{ !empty($payment['proof']['name']) ? ' · '.$payment['proof']['name'] : '' }}</a></div>
                    @elseif(!empty($payment['proof_missing_reason']))
                        <div class="hc-payment-meta hc-detail-notice"><strong>Proof unavailable:</strong> {{ $payment['proof_missing_reason'] }}</div>
                    @else
                        <div class="hc-payment-meta hc-detail-alert hc-detail-alert--error">No proof is attached and no reason was provided.</div>
                    @endif
                </div>
            @empty
                <p class="hc-detail-section-copy">No historical payments were submitted.</p>
            @endforelse
            </div>
        </section>
        @if($submission->review_note)<div class="hc-detail-notice"><strong>Latest review note</strong><p class="hc-detail-section-copy">{{ $submission->review_note }}</p></div>@endif
        @if(!empty($data['possible_matches']))<section class="hc-detail-notice"><h2 class="hc-detail-section-title">Possible matching cases · review before decision</h2><p class="hc-detail-section-copy">These are review suggestions based on matching deceased name and date of death. They do not block or overwrite this submission.</p><ul class="mt-2 space-y-1 text-sm">@foreach($data['possible_matches'] as $match)<li><strong>{{ $match['case_code'] }}</strong> · {{ $match['client_name'] }} · {{ $match['deceased_name'] }} · Service date {{ $match['service_date'] ?: 'not recorded' }}</li>@endforeach</ul></section>@endif
        @if($submission->reviews->isNotEmpty())<section class="hc-detail-section"><h2 class="hc-detail-section-title">Review history</h2><div class="hc-review-history">@foreach($submission->reviews as $review)<div class="hc-review-entry"><strong>{{ str_replace('_',' ', $review->action) }}</strong> · {{ $review->reviewer?->name }} · {{ $review->created_at?->format('M d, Y h:i A') }}@if($review->note)<p class="hc-detail-section-copy">{{ $review->note }}</p>@endif</div>@endforeach</div></section>@endif
        @if((auth()->user()->isBranchAdmin() || auth()->user()->isSystemAdmin()) && $submission->status === \App\Models\HistoricalCaseSubmission::PENDING && (int)$submission->encoded_by !== (int)auth()->id())
            <section class="hc-detail-section"><h2 class="hc-detail-section-title">Review decision</h2><p class="hc-detail-section-copy">Confirm the submission or return it to the encoder with a clear review note.</p><form method="POST" action="{{ route('historical-cases.decide', $submission) }}" class="hc-review-form">@csrf<textarea name="note" aria-label="Review note" placeholder="Required for changes requested or rejection"></textarea><div class="hc-review-actions"><button name="decision" value="needs_changes" class="hc-review-action hc-review-action--warning">Request changes</button><button name="decision" value="reject" class="hc-review-action hc-review-action--danger">Reject</button><button name="decision" value="approve" class="hc-review-action hc-review-action--success">Approve &amp; create case</button></div></form></section>
        @endif
        @if(auth()->user()->isStaff() && (int)$submission->encoded_by === (int)auth()->id() && in_array($submission->status, [\App\Models\HistoricalCaseSubmission::NEEDS_CHANGES, \App\Models\HistoricalCaseSubmission::REJECTED], true))
            <a href="{{ route('historical-cases.resume', $submission) }}" class="hc-review-action hc-review-action--success">Correct and resubmit</a>
        @endif
    </article>
</main>
@endsection
