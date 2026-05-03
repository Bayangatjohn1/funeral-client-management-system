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
                        <div class="text-xs text-slate-500 mt-0.5">Log the received amount, payment method, and transaction details.</div>
                    </div>
                    <button type="button" id="closeCasePaymentForm" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition-colors focus:outline-none shadow-sm">
                        <i class="bi bi-x-lg" style="font-size:.75rem"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('payments.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="funeral_case_id" value="{{ $funeral_case->id }}">
                    <input type="hidden" name="return_to_case" value="1">
                    <input type="hidden" name="return_to" value="{{ $returnUrl }}">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" id="case_payment_method" class="form-input" required>
                                <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="cashless" {{ in_array(old('payment_method'), ['cashless', 'bank_transfer'], true) ? 'selected' : '' }}>Cashless</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Payment Record No.</label>
                            <input type="text" value="Generated on save" class="form-input bg-slate-50" readonly>
                        </div>
                        <div>
                            <label class="form-label">Payment Date &amp; Time</label>
                            <input type="datetime-local" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Payment Amount</label>
                            <input type="number" step="0.01" min="0.01"
                                max="{{ number_format((float) $funeral_case->balance_amount, 2, '.', '') }}"
                                name="amount_paid" id="case_amount_paid"
                                value="{{ old('amount_paid') }}" class="form-input" required>
                            <p id="case_amount_formatted" class="text-xs font-bold text-slate-600 mt-1">₱0.00</p>
                        </div>
                        <div>
                            <label class="form-label">Receipt / OR No.</label>
                            <input type="text" name="receipt_or_no" value="{{ old('receipt_or_no', old('accounting_reference_no')) }}" class="form-input" maxlength="100" placeholder="Optional">
                        </div>
                    </div>
                    <div id="case_cashless_fields" class="hidden grid grid-cols-1 gap-3 sm:grid-cols-2 rounded-xl border border-slate-200 bg-white p-4">
                        <div>
                            <label class="form-label">Cashless Type</label>
                            <select name="cashless_type" id="case_cashless_type" class="form-input">
                                <option value="">Choose cashless type</option>
                                <option value="bank_transfer" {{ old('cashless_type', old('payment_method') === 'bank_transfer' ? 'bank_transfer' : null) === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="gcash" {{ old('cashless_type') === 'gcash' ? 'selected' : '' }}>GCash</option>
                                <option value="maya" {{ old('cashless_type') === 'maya' ? 'selected' : '' }}>Maya</option>
                                <option value="card" {{ old('cashless_type') === 'card' ? 'selected' : '' }}>Card</option>
                                <option value="other" {{ old('cashless_type') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div data-case-cashless-panel="bank_transfer" class="case-cashless-panel hidden">
                            <label class="form-label">Bank Name</label>
                            <select name="bank_name" id="case_bank_name" class="form-input">
                                <option value="">Choose bank</option>
                                @foreach(['BDO', 'BPI', 'Metrobank', 'Landbank', 'Security Bank', 'UnionBank', 'RCBC', 'PNB', 'China Bank', 'Other Bank'] as $bank)
                                    <option value="{{ $bank }}" {{ old('bank_name', old('bank_or_channel') === 'Other' ? 'Other Bank' : old('bank_or_channel')) === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="case_other_bank_wrap" class="hidden sm:col-span-2">
                            <label class="form-label">Other Bank Name</label>
                            <input type="text" name="other_bank_name" id="case_other_bank_name" value="{{ old('other_bank_name', old('other_bank_or_channel')) }}" class="form-input" maxlength="100">
                        </div>
                        <div data-case-cashless-panel="gcash" class="case-cashless-panel hidden">
                            <label class="form-label">GCash Account Name</label>
                            <input type="text" data-case-account-name-field value="{{ old('account_name') }}" class="form-input" maxlength="120">
                        </div>
                        <div data-case-cashless-panel="gcash" class="case-cashless-panel hidden">
                            <label class="form-label">GCash Mobile Number</label>
                            <input type="text" data-case-mobile-number-field value="{{ old('mobile_number') }}" class="form-input" maxlength="30">
                        </div>
                        <div data-case-cashless-panel="maya" class="case-cashless-panel hidden">
                            <label class="form-label">Maya Account Name</label>
                            <input type="text" data-case-account-name-field value="{{ old('account_name') }}" class="form-input" maxlength="120">
                        </div>
                        <div data-case-cashless-panel="maya" class="case-cashless-panel hidden">
                            <label class="form-label">Maya Mobile Number</label>
                            <input type="text" data-case-mobile-number-field value="{{ old('mobile_number') }}" class="form-input" maxlength="30">
                        </div>
                        <div data-case-cashless-panel="card" class="case-cashless-panel hidden">
                            <label class="form-label">Card Type</label>
                            <select name="card_type" id="case_card_type" class="form-input">
                                <option value="">Select card type</option>
                                <option value="debit" {{ old('card_type') === 'debit' ? 'selected' : '' }}>Debit</option>
                                <option value="credit" {{ old('card_type') === 'credit' ? 'selected' : '' }}>Credit</option>
                            </select>
                        </div>
                        <div data-case-cashless-panel="card" class="case-cashless-panel hidden">
                            <label class="form-label">Terminal / Provider</label>
                            <input type="text" name="terminal_provider" id="case_terminal_provider" value="{{ old('terminal_provider') }}" class="form-input" maxlength="80">
                        </div>
                        <div data-case-cashless-panel="card" class="case-cashless-panel hidden">
                            <label class="form-label">Approval Code</label>
                            <input type="text" name="approval_code" id="case_approval_code" value="{{ old('approval_code') }}" class="form-input" maxlength="40">
                        </div>
                        <div data-case-cashless-panel="other" class="case-cashless-panel hidden">
                            <label class="form-label">Payment Channel</label>
                            <input type="text" data-case-payment-channel-field value="{{ old('payment_channel') }}" class="form-input" maxlength="100">
                        </div>
                        <div data-case-cashless-panel="other" class="case-cashless-panel hidden">
                            <label class="form-label">Notes</label>
                            <input type="text" name="payment_notes" value="{{ old('payment_notes') }}" class="form-input" maxlength="255">
                        </div>
                        <div>
                            <label class="form-label">Reference No.</label>
                            <input type="text" name="reference_number" id="case_reference_number" value="{{ old('reference_number', old('transaction_reference_no')) }}" class="form-input" maxlength="100">
                        </div>
                        <input type="hidden" name="wallet_provider" id="case_wallet_provider" value="{{ old('wallet_provider') }}">
                        <input type="hidden" name="account_name" id="case_account_name" value="{{ old('account_name', old('sender_name')) }}">
                        <input type="hidden" name="mobile_number" id="case_mobile_number" value="{{ old('mobile_number') }}">
                        <input type="hidden" name="payment_channel" id="case_payment_channel" value="{{ old('payment_channel') }}">
                        <input type="hidden" name="transaction_reference_no" id="case_transaction_reference_no" value="{{ old('transaction_reference_no') }}">
                    </div>
                    <div>
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-input min-h-[80px]" maxlength="1000" placeholder="Optional">{{ old('remarks') }}</textarea>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach([
                            ['label' => 'Total Amount',   'id' => 'case_total_amount_display',    'value' => number_format((float) $funeral_case->total_amount, 2),  'static' => true],
                            ['label' => 'Total Paid',     'id' => 'case_total_paid_display',      'value' => number_format((float) $funeral_case->total_paid, 2),    'static' => true],
                            ['label' => 'Balance',        'id' => 'case_current_balance_display', 'value' => number_format((float) $funeral_case->balance_amount, 2),'static' => true],
                            ['label' => 'New Payment',    'id' => 'case_new_payment_display',     'value' => '0.00',                                                 'static' => false],
                            ['label' => 'New Total Paid', 'id' => 'case_new_total_paid_display',  'value' => number_format((float) $funeral_case->total_paid, 2),    'static' => false],
                            ['label' => 'New Balance',    'id' => 'case_new_balance_display',     'value' => number_format((float) $funeral_case->balance_amount, 2),'static' => false],
                            ['label' => 'Resulting Payment Status',     'id' => 'case_new_status_display',      'value' => $funeral_case->payment_status,                          'static' => false],
                        ] as $stat)
                            <div class="rounded-lg border {{ $stat['static'] ? 'border-slate-200 bg-white' : 'border-slate-300 bg-white' }} px-3 py-3">
                                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">{{ $stat['label'] }}</div>
                                <div class="text-sm font-bold text-slate-800 tabular-nums" id="{{ $stat['id'] }}">{{ $stat['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-end">
                        <button class="btn-secondary" type="submit">Save Payment</button>
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
                <a href="{{ route('payments.history', ['q' => $funeral_case->case_code]) }}" class="btn-outline">Payment Monitoring</a>
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
        const paymentMethod = document.getElementById('case_payment_method');
        const form = paymentMethod?.closest('form');
        const cashlessFields = document.getElementById('case_cashless_fields');
        const cashlessType = document.getElementById('case_cashless_type');
        const bankName = document.getElementById('case_bank_name');
        const referenceNo = document.getElementById('case_reference_number');
        const transactionRef = document.getElementById('case_transaction_reference_no');
        const otherBankWrap = document.getElementById('case_other_bank_wrap');
        const otherBankName = document.getElementById('case_other_bank_name');
        const walletProvider = document.getElementById('case_wallet_provider');
        const accountName = document.getElementById('case_account_name');
        const mobileNumber = document.getElementById('case_mobile_number');
        const paymentChannel = document.getElementById('case_payment_channel');
        const totalAmount    = {{ json_encode((float) $funeral_case->total_amount) }};
        const currentPaid    = {{ json_encode((float) $funeral_case->total_paid) }};
        const currentBalance = {{ json_encode((float) $funeral_case->balance_amount) }};
        const paymentDisplay   = document.getElementById('case_new_payment_display');
        const totalPaidDisplay = document.getElementById('case_new_total_paid_display');
        const balanceDisplay   = document.getElementById('case_new_balance_display');
        const statusDisplay    = document.getElementById('case_new_status_display');
        const amountFormatted  = document.getElementById('case_amount_formatted');

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
            if (amountFormatted) amountFormatted.textContent = `₱${fmt(raw > 0 && Number.isFinite(raw) ? raw : 0)}`;
        };
        const updateMethodFields = () => {
            const isCashless = paymentMethod?.value === 'cashless';
            const type = cashlessType?.value || '';
            cashlessFields?.classList.toggle('hidden', !isCashless);
            if (cashlessType) cashlessType.required = isCashless;
            if (!isCashless) {
                if (cashlessType) cashlessType.value = '';
                if (bankName) bankName.value = '';
                if (otherBankName) otherBankName.value = '';
                if (referenceNo) referenceNo.value = '';
                if (walletProvider) walletProvider.value = '';
                if (accountName) accountName.value = '';
                if (mobileNumber) mobileNumber.value = '';
                if (paymentChannel) paymentChannel.value = '';
                if (transactionRef) transactionRef.value = '';
                const approval = document.getElementById('case_approval_code');
                if (approval) approval.value = '';
            }
            document.querySelectorAll('.case-cashless-panel').forEach(panel => {
                panel.classList.toggle('hidden', !isCashless || panel.dataset.caseCashlessPanel !== type);
            });
            if (bankName) bankName.required = isCashless && type === 'bank_transfer';
            const isOtherBank = isCashless && type === 'bank_transfer' && bankName?.value === 'Other Bank';
            otherBankWrap?.classList.toggle('hidden', !isOtherBank);
            if (otherBankName) otherBankName.required = isOtherBank;
            if (referenceNo) referenceNo.required = isCashless && ['bank_transfer', 'gcash', 'maya', 'other'].includes(type);
            if (transactionRef && referenceNo) transactionRef.value = referenceNo.value;

            const activeAccount = document.querySelector(`[data-case-cashless-panel="${type}"] [data-case-account-name-field]`);
            const activeMobile = document.querySelector(`[data-case-cashless-panel="${type}"] [data-case-mobile-number-field]`);
            const activeChannel = document.querySelector(`[data-case-cashless-panel="${type}"] [data-case-payment-channel-field]`);
            if (accountName) accountName.value = activeAccount ? activeAccount.value : '';
            if (mobileNumber) mobileNumber.value = activeMobile ? activeMobile.value : '';
            if (paymentChannel) paymentChannel.value = activeChannel ? activeChannel.value : '';
            if (walletProvider) walletProvider.value = type === 'gcash' ? 'GCash' : (type === 'maya' ? 'Maya' : '');
        };
        const fail = (field, message) => {
            if (!field) return false;
            field.setCustomValidity(message);
            field.reportValidity();
            field.focus();
            return false;
        };
        const validReferencePattern = /^[A-Za-z0-9 _/-]+$/;
        const validAccountNamePattern = /^(?=.*[\p{L}])[\p{L}\p{M} .'-]+$/u;
        const validateReferenceValue = (field, required = false) => {
            const value = (field?.value || '').trim();
            if (required && !value) return fail(field, 'Reference number is required.');
            if (!value) return true;
            if (value.length < 4 || value.length > 60) return fail(field, 'Reference number must be 4 to 60 characters.');
            if (!validReferencePattern.test(value)) return fail(field, 'Reference number contains invalid characters.');
            field.setCustomValidity('');
            return true;
        };
        const validateAccountNameValue = (field) => {
            const value = (field?.value || '').trim();
            if (!value) return true;
            if (value.length < 2 || value.length > 100) return fail(field, 'Account name must be between 2 and 100 characters.');
            if (!validAccountNamePattern.test(value)) return fail(field, 'Account name should contain letters only and must not include numbers or special characters.');
            field.setCustomValidity('');
            return true;
        };
        const clearCashlessValidity = () => {
            [
                cashlessType,
                bankName,
                otherBankName,
                referenceNo,
                document.getElementById('case_approval_code'),
                ...document.querySelectorAll('[data-case-account-name-field], [data-case-mobile-number-field], [data-case-payment-channel-field]'),
            ].forEach(field => field?.setCustomValidity?.(''));
        };
        const validateCashlessDetails = () => {
            updateMethodFields();
            clearCashlessValidity();
            if (paymentMethod?.value !== 'cashless') return true;

            const type = cashlessType?.value || '';
            const ref = (referenceNo?.value || '').trim();
            const approval = (document.getElementById('case_approval_code')?.value || '').trim();
            const activeChannel = document.querySelector(`[data-case-cashless-panel="${type}"] [data-case-payment-channel-field]`);
            const activeAccount = document.querySelector(`[data-case-cashless-panel="${type}"] [data-case-account-name-field]`);

            if (!type) return fail(cashlessType, 'Please select a cashless type.');
            if (type === 'bank_transfer') {
                if (!(bankName?.value || '').trim()) return fail(bankName, 'Please select the bank name.');
                if (bankName.value === 'Other Bank' && !(otherBankName?.value || '').trim()) {
                    return fail(otherBankName, 'Please enter the other bank name.');
                }
                if (!validateReferenceValue(referenceNo, true)) return false;
            }
            if (type === 'gcash' || type === 'maya') {
                if (!validateAccountNameValue(activeAccount)) return false;
                if (!validateReferenceValue(referenceNo, true)) return false;
            }
            if (type === 'card' && !approval && !ref) {
                return fail(document.getElementById('case_approval_code'), 'Please enter the approval code or reference number.');
            }
            if (type === 'card' && ref && !validateReferenceValue(referenceNo, false)) return false;
            if (type === 'other') {
                if (!(activeChannel?.value || '').trim()) return fail(activeChannel, 'Please enter the payment channel.');
                if (!validateReferenceValue(referenceNo, true)) return false;
            }

            return true;
        };
        if (openButton)  openButton.addEventListener('click',  () => { panel.classList.remove('hidden'); amountInput?.focus(); });
        if (closeButton) closeButton.addEventListener('click', () => panel.classList.add('hidden'));
        if (amountInput) { amountInput.addEventListener('input', updatePreview); updatePreview(); }
        if (paymentMethod) paymentMethod.addEventListener('change', updateMethodFields);
        if (cashlessType) cashlessType.addEventListener('change', updateMethodFields);
        if (bankName) bankName.addEventListener('change', updateMethodFields);
        if (referenceNo) {
            referenceNo.addEventListener('input', () => {
                referenceNo.setCustomValidity('');
                updateMethodFields();
            });
            referenceNo.addEventListener('blur', () => validateReferenceValue(referenceNo, false));
        }
        document.querySelectorAll('[data-case-account-name-field], [data-case-mobile-number-field], [data-case-payment-channel-field]').forEach(field => {
            field.addEventListener('input', updateMethodFields);
        });
        document.querySelectorAll('[data-case-account-name-field]').forEach(field => {
            field.addEventListener('blur', () => validateAccountNameValue(field));
        });
        document.getElementById('case_approval_code')?.addEventListener('input', clearCashlessValidity);
        form?.addEventListener('submit', (event) => {
            if (!validateCashlessDetails()) {
                event.preventDefault();
            }
        });
        updateMethodFields();
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
