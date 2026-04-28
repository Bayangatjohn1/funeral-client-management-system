@extends('layouts.panel')

@section('page_title', 'Case Full Information')
@section('page_desc', 'View full case details, services, and payment progress.')

@section('content')
@php
    $defaultReturnUrl = ($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH'
        ? route('funeral-cases.other-reports')
        : ($funeral_case->case_status === 'COMPLETED'
            ? route('funeral-cases.index', ['tab' => 'completed'])
            : route('funeral-cases.index', ['tab' => 'active']));
    $returnUrl = request()->query('return_to');
    if (!is_string($returnUrl) || $returnUrl === '' || !\Illuminate\Support\Str::startsWith($returnUrl, [url('/'), '/'])) {
        $returnUrl = $defaultReturnUrl;
    }
    $canRecordPayment = auth()->user()?->canEncodeAnyBranch()
        && (($funeral_case->entry_source ?? 'MAIN') !== 'OTHER_BRANCH')
        && strtoupper((string) ($funeral_case->branch?->branch_code ?? '')) === 'BR001'
        && (float) $funeral_case->balance_amount > 0;
    $isOverlay = request()->boolean('overlay');
@endphp

@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="flash-error">{{ $errors->first() }}</div>
@endif

{{-- Payment Action panel (screen only, not printed) --}}
@if(($funeral_case->entry_source ?? 'MAIN') !== 'OTHER_BRANCH')
<div class="no-print max-w-4xl mx-auto px-4 pt-6">
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div>
                <div class="text-sm font-semibold text-slate-800">Payment Action</div>
                <div class="text-xs text-slate-500 mt-0.5">Record a follow-up payment for this case.</div>
            </div>
            @if($canRecordPayment)
                <button type="button" id="openCasePaymentForm" class="btn-secondary text-sm">
                    <i class="bi bi-plus-lg mr-1"></i>Add Payment
                </button>
            @endif
        </div>
        <div class="px-5 py-4">
            @if((float) $funeral_case->balance_amount <= 0)
                <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <i class="bi bi-check-circle-fill text-emerald-500"></i>
                    This case is already fully paid.
                </div>
            @elseif(!$canRecordPayment)
                <div class="flash-info mb-0">Payment updates are available only to authorized main-branch staff.</div>
            @endif
        </div>

        @if($canRecordPayment)
            <div id="casePaymentPanel" class="{{ $errors->any() && old('return_to_case') ? '' : 'hidden' }} border-t border-slate-100 px-5 py-5 bg-slate-50 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Record Payment</div>
                        <div class="text-xs text-slate-500 mt-0.5">Review the live preview before saving.</div>
                    </div>
                    <button type="button" id="closeCasePaymentForm" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition-colors focus:outline-none shadow-sm">
                        <i class="bi bi-x-lg" style="font-size:.75rem"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('payments.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="funeral_case_id" value="{{ $funeral_case->id }}">
                    <input type="hidden" name="return_to_case" value="1">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="form-label">Paid Date/Time</label>
                            <input type="datetime-local" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Amount Paid</label>
                            <input type="number" step="0.01" min="0.01"
                                max="{{ number_format((float) $funeral_case->balance_amount, 2, '.', '') }}"
                                name="amount_paid" id="case_amount_paid"
                                value="{{ old('amount_paid') }}" class="form-input" required>
                        </div>
                        <div class="flex items-end">
                            <button class="btn-secondary w-full" type="submit">Save Payment</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach([
                            ['label' => 'Total Amount',   'id' => 'case_total_amount_display',    'value' => number_format((float) $funeral_case->total_amount, 2),  'static' => true],
                            ['label' => 'Total Paid',     'id' => 'case_total_paid_display',      'value' => number_format((float) $funeral_case->total_paid, 2),    'static' => true],
                            ['label' => 'Balance',        'id' => 'case_current_balance_display', 'value' => number_format((float) $funeral_case->balance_amount, 2),'static' => true],
                            ['label' => 'New Payment',    'id' => 'case_new_payment_display',     'value' => '0.00',                                                 'static' => false],
                            ['label' => 'New Total Paid', 'id' => 'case_new_total_paid_display',  'value' => number_format((float) $funeral_case->total_paid, 2),    'static' => false],
                            ['label' => 'New Balance',    'id' => 'case_new_balance_display',     'value' => number_format((float) $funeral_case->balance_amount, 2),'static' => false],
                            ['label' => 'New Status',     'id' => 'case_new_status_display',      'value' => $funeral_case->payment_status,                          'static' => false],
                        ] as $stat)
                            <div class="rounded-lg border {{ $stat['static'] ? 'border-slate-200 bg-white' : 'border-slate-300 bg-white' }} px-3 py-3">
                                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">{{ $stat['label'] }}</div>
                                <div class="text-sm font-bold text-slate-800 tabular-nums" id="{{ $stat['id'] }}">{{ $stat['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@elseif(($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH')
<div class="no-print max-w-4xl mx-auto px-4 pt-6">
    <div class="flash-warning">Other-branch reported cases are locked for payment updates.</div>
</div>
@endif

{{-- Shared case detail content --}}
<div class="max-w-4xl mx-auto px-4 py-6">
    @include('partials.case_view_content')
</div>

{{-- Bottom Nav + Print button (screen only) --}}
<div class="no-print max-w-4xl mx-auto px-4 pb-8 flex flex-wrap items-center justify-between gap-3">
    @if(!$isOverlay)
        <div class="flex flex-wrap gap-2">
            <a href="{{ $returnUrl }}" class="btn-outline">
                <i class="bi bi-arrow-left mr-1"></i>Back
            </a>
            @if($canRecordPayment)
                <a href="{{ route('payments.history', ['q' => $funeral_case->case_code]) }}" class="btn-outline">Payment History</a>
            @endif
        </div>
    @else
        <div></div>
    @endif
    <button id="printCaseBtn" type="button" class="px-6 py-2.5 bg-black text-white text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors shadow rounded-lg">
        <i class="bi bi-printer mr-2"></i>Print Record
    </button>
</div>

<style>
    @media print {
        nav, aside, footer, header, .no-print { display: none !important; }
        html, body { height: auto !important; overflow: visible !important; background: white !important; }
        #caseViewContent { max-width: 100% !important; margin: 0 !important; padding: 2rem !important; }
    }
</style>

@if($canRecordPayment)
<script>
    (function () {
        const openButton  = document.getElementById('openCasePaymentForm');
        const closeButton = document.getElementById('closeCasePaymentForm');
        const panel       = document.getElementById('casePaymentPanel');
        const amountInput = document.getElementById('case_amount_paid');
        const totalAmount    = {{ json_encode((float) $funeral_case->total_amount) }};
        const currentPaid    = {{ json_encode((float) $funeral_case->total_paid) }};
        const currentBalance = {{ json_encode((float) $funeral_case->balance_amount) }};
        const paymentDisplay   = document.getElementById('case_new_payment_display');
        const totalPaidDisplay = document.getElementById('case_new_total_paid_display');
        const balanceDisplay   = document.getElementById('case_new_balance_display');
        const statusDisplay    = document.getElementById('case_new_status_display');

        const fmt = v => v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const sanitize = () => {
            const raw = parseFloat(amountInput.value || '0');
            return (!Number.isFinite(raw) || raw <= 0) ? 0 : Math.min(raw, currentBalance);
        };
        const updatePreview = () => {
            const payment    = sanitize();
            const newPaid    = Math.min(currentPaid + payment, totalAmount);
            const newBalance = Math.max(totalAmount - newPaid, 0);
            const status     = newPaid > 0 && newBalance > 0 ? 'PARTIAL' : (newBalance <= 0 && totalAmount > 0 ? 'PAID' : 'UNPAID');
            paymentDisplay.textContent   = fmt(payment);
            totalPaidDisplay.textContent = fmt(newPaid);
            balanceDisplay.textContent   = fmt(newBalance);
            statusDisplay.textContent    = status;
        };
        if (openButton)  openButton.addEventListener('click',  () => { panel.classList.remove('hidden'); amountInput?.focus(); });
        if (closeButton) closeButton.addEventListener('click', () => panel.classList.add('hidden'));
        if (amountInput) { amountInput.addEventListener('input', updatePreview); updatePreview(); }
    })();
</script>
@endif

<script>
    (() => {
        const btn = document.getElementById('printCaseBtn');
        const source = document.getElementById('caseViewContent');
        if (!btn || !source) return;
        btn.addEventListener('click', () => {
            const iframe = document.createElement('iframe');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
            document.body.appendChild(iframe);
            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<!doctype html><html><head>');
            document.querySelectorAll('link[rel="stylesheet"]').forEach(l => {
                if (l.href) doc.write(`<link rel="stylesheet" href="${l.href}">`);
            });
            document.querySelectorAll('style').forEach(s => {
                doc.write('<style>' + s.innerHTML + '</style>');
            });
            doc.write('</head><body style="padding:2rem;">');
            doc.write(source.outerHTML);
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
