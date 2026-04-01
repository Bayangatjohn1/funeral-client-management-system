@extends('layouts.panel')

@section('page_title', 'Case Full Information')

@section('content')
@php
    $defaultReturnUrl = ($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH'
        ? route('funeral-cases.other-reports')
        : ($funeral_case->case_status === 'COMPLETED'
            ? route('funeral-cases.completed')
            : route('funeral-cases.index'));
    $returnUrl = request()->query('return_to');
    if (!is_string($returnUrl) || $returnUrl === '' || !\Illuminate\Support\Str::startsWith($returnUrl, [url('/'), '/'])) {
        $returnUrl = $defaultReturnUrl;
    }
    $canRecordPayment = auth()->user()?->canEncodeAnyBranch()
        && (($funeral_case->entry_source ?? 'MAIN') !== 'OTHER_BRANCH')
        && strtoupper((string) ($funeral_case->branch?->branch_code ?? '')) === 'BR001'
        && (float) $funeral_case->balance_amount > 0;
@endphp

<div id="caseViewContent" class="space-y-6">
    @if(session('success'))
        <div class="flash-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="flash-error">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="detail-hero">
        <div class="detail-overline">Case Record</div>
        <h2 class="detail-title">Case {{ $funeral_case->case_code }}</h2>
        <p class="detail-copy">
            Full case, payment, and source details for this funeral service record.
        </p>

        <div class="detail-chip-row">
            <span class="detail-chip">Request Date: {{ $funeral_case->service_requested_at?->format('Y-m-d') ?? $funeral_case->created_at?->format('Y-m-d') }}</span>
            <span class="detail-chip">Branch: {{ $funeral_case->branch?->branch_code ?? '-' }}</span>
            <span class="{{ in_array($funeral_case->case_status, ['DRAFT', 'ACTIVE']) ? 'status-pill-warning' : 'status-pill-success' }}">
                {{ $funeral_case->case_status }}
            </span>
            <span class="{{
                $funeral_case->payment_status === 'PAID'
                    ? 'status-pill-success'
                    : ($funeral_case->payment_status === 'PARTIAL' ? 'status-pill-warning' : 'status-pill-danger')
            }}">
                {{ $funeral_case->payment_status }}
            </span>
        </div>
    </section>


    <section class="detail-section">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="detail-section-title">Payment Action</div>
                <div class="detail-section-copy">Record follow-up payment directly from this case record.</div>
            </div>
            @if($canRecordPayment)
                <button type="button" id="openCasePaymentForm" class="btn-secondary">
                    Add Payment
                </button>
            @endif
        </div>

        @if(($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH')
            <div class="flash-warning mt-4 mb-0">
                Other-branch reported cases are locked for payment updates.
            </div>
        @elseif((float) $funeral_case->balance_amount <= 0)
            <div class="flash-success mt-4 mb-0">
                This case is already fully paid.
            </div>
        @elseif(!$canRecordPayment)
            <div class="flash-info mt-4 mb-0">
                Payment updates are available only to authorized main-branch staff.
            </div>
        @endif

        @if($canRecordPayment)
            <div id="casePaymentPanel" class="{{ $errors->any() && old('return_to_case') ? '' : 'hidden' }} mt-4 rounded-2xl border border-surface-muted bg-slate-50 p-4 space-y-4">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="detail-section-title">Record Payment</div>
                        <div class="detail-section-copy">Review the live preview before saving.</div>
                    </div>
                    <button type="button" id="closeCasePaymentForm" class="btn-outline">
                        Close
                    </button>
                </div>

                <form method="POST" action="{{ route('payments.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="funeral_case_id" value="{{ $funeral_case->id }}">
                    <input type="hidden" name="return_to_case" value="1">

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <label class="form-label">Paid Date/Time</label>
                            <input
                                type="datetime-local"
                                name="paid_at"
                                value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}"
                                class="form-input"
                                required
                            >
                        </div>

                        <div>
                            <label class="form-label">Amount Paid</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                max="{{ number_format((float) $funeral_case->balance_amount, 2, '.', '') }}"
                                name="amount_paid"
                                id="case_amount_paid"
                                value="{{ old('amount_paid') }}"
                                class="form-input"
                                required
                            >
                        </div>

                        <div class="flex items-end">
                            <button class="btn-secondary w-full" type="submit">
                                Save Payment
                            </button>
                        </div>
                    </div>

                    <div class="detail-stat-grid">
                        <div class="detail-stat">
                            <div class="detail-stat-label">Current Total Amount</div>
                            <div class="detail-stat-value" id="case_total_amount_display">{{ number_format((float) $funeral_case->total_amount, 2) }}</div>
                        </div>
                        <div class="detail-stat">
                            <div class="detail-stat-label">Current Total Paid</div>
                            <div class="detail-stat-value" id="case_total_paid_display">{{ number_format((float) $funeral_case->total_paid, 2) }}</div>
                        </div>
                        <div class="detail-stat">
                            <div class="detail-stat-label">Current Balance</div>
                            <div class="detail-stat-value" id="case_current_balance_display">{{ number_format((float) $funeral_case->balance_amount, 2) }}</div>
                        </div>
                        <div class="detail-stat">
                            <div class="detail-stat-label">New Payment</div>
                            <div class="detail-stat-value" id="case_new_payment_display">0.00</div>
                        </div>
                        <div class="detail-stat">
                            <div class="detail-stat-label">New Total Paid</div>
                            <div class="detail-stat-value" id="case_new_total_paid_display">{{ number_format((float) $funeral_case->total_paid, 2) }}</div>
                        </div>
                        <div class="detail-stat">
                            <div class="detail-stat-label">New Remaining Balance</div>
                            <div class="detail-stat-value" id="case_new_balance_display">{{ number_format((float) $funeral_case->balance_amount, 2) }}</div>
                        </div>
                        <div class="detail-stat">
                            <div class="detail-stat-label">Resulting Payment Status</div>
                            <div class="detail-stat-value" id="case_new_status_display">{{ $funeral_case->payment_status }}</div>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </section>

    <section class="detail-section">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <div class="detail-section-title">Client Information</div>
                <dl class="detail-field-grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="detail-field">
                        <dt class="detail-field-label">Name</dt>
                        <dd class="detail-field-value">{{ $funeral_case->client?->full_name ?? '-' }}</dd>
                    </div>
                    <div class="detail-field">
                        <dt class="detail-field-label">Contact</dt>
                        <dd class="detail-field-value">{{ $funeral_case->client?->contact_number ?? '-' }}</dd>
                    </div>
                    <div class="detail-field sm:col-span-2">
                        <dt class="detail-field-label">Address</dt>
                        <dd class="detail-field-value">{{ $funeral_case->client?->address ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="space-y-2">
                <div class="detail-section-title">Deceased Information</div>
                <dl class="detail-field-grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="detail-field">
                        <dt class="detail-field-label">Name</dt>
                        <dd class="detail-field-value">{{ $funeral_case->deceased?->full_name ?? '-' }}</dd>
                    </div>
                    <div class="detail-field">
                        <dt class="detail-field-label">Born</dt>
                        <dd class="detail-field-value">{{ $funeral_case->deceased?->born?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div class="detail-field">
                        <dt class="detail-field-label">Died</dt>
                        <dd class="detail-field-value">{{ ($funeral_case->deceased?->died ?? $funeral_case->deceased?->date_of_death)?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div class="detail-field">
                        <dt class="detail-field-label">Interment</dt>
                        <dd class="detail-field-value">{{ $funeral_case->deceased?->interment_at?->format('Y-m-d H:i') ?? $funeral_case->deceased?->interment?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div class="detail-field">
                        <dt class="detail-field-label">Wake Days</dt>
                        <dd class="detail-field-value">{{ $funeral_case->deceased?->wake_days ?? '-' }}</dd>
                    </div>
                    <div class="detail-field">
                        <dt class="detail-field-label">Place of Cemetery</dt>
                        <dd class="detail-field-value">{{ $funeral_case->deceased?->place_of_cemetery ?? '-' }}</dd>
                    </div>
                    <div class="detail-field">
                        <dt class="detail-field-label">Coffin Measurement</dt>
                        <dd class="detail-field-value">{{ $funeral_case->deceased?->coffin_length_cm ? number_format((float) $funeral_case->deceased?->coffin_length_cm, 2) . ' cm' : '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <section class="detail-section">
        <div class="detail-section-title mb-3">Case Financial Summary</div>
        <dl class="detail-field-grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="detail-field">
                <dt class="detail-field-label">Package</dt>
                <dd class="detail-field-value">{{ $funeral_case->service_package ?? '-' }}</dd>
            </div>
            <div class="detail-field">
                <dt class="detail-field-label">Subtotal</dt>
                <dd class="detail-field-value">{{ number_format($funeral_case->subtotal_amount ?? $funeral_case->total_amount, 2) }}</dd>
            </div>
            <div class="detail-field">
                <dt class="detail-field-label">Discount</dt>
                <dd class="detail-field-value">{{ number_format($funeral_case->discount_amount ?? 0, 2) }}</dd>
            </div>
            <div class="detail-field">
                <dt class="detail-field-label">Discount Rule</dt>
                <dd class="detail-field-value">{{ $funeral_case->discount_note ?? 'None' }}</dd>
            </div>
            <div class="detail-field">
                <dt class="detail-field-label">Total</dt>
                <dd class="detail-field-value">{{ number_format($funeral_case->total_amount, 2) }}</dd>
            </div>
            <div class="detail-field">
                <dt class="detail-field-label">Total Paid</dt>
                <dd class="detail-field-value">{{ number_format((float) $funeral_case->total_paid, 2) }}</dd>
            </div>
            <div class="detail-field">
                <dt class="detail-field-label">Balance</dt>
                <dd class="detail-field-value">{{ number_format((float) $funeral_case->balance_amount, 2) }}</dd>
            </div>
            <div class="detail-field">
                <dt class="detail-field-label">Paid At</dt>
                <dd class="detail-field-value">{{ $funeral_case->paid_at?->format('Y-m-d H:i') ?? '-' }}</dd>
            </div>
        </dl>
    </section>

    <section class="detail-section">
        <div class="detail-section-title">Record Source</div>
        <dl class="detail-field-grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            <div class="detail-field">
                <dt class="detail-field-label">Reported Branch</dt>
                <dd class="detail-field-value">{{ $funeral_case->reportedBranch?->branch_code ?? $funeral_case->branch?->branch_code ?? '-' }}</dd>
            </div>
            <div class="detail-field">
                <dt class="detail-field-label">Reporter Name</dt>
                <dd class="detail-field-value">{{ $funeral_case->reporter_name ?? '-' }}</dd>
            </div>
            <div class="detail-field">
                <dt class="detail-field-label">Reporter Contact</dt>
                <dd class="detail-field-value">{{ $funeral_case->reporter_contact ?? '-' }}</dd>
            </div>
            <div class="detail-field">
                <dt class="detail-field-label">Reported At</dt>
                <dd class="detail-field-value">{{ $funeral_case->reported_at?->format('Y-m-d H:i') ?? '-' }}</dd>
            </div>
            <div class="detail-field md:col-span-3">
                <dt class="detail-field-label">Encoded By</dt>
                <dd class="detail-field-value">{{ $funeral_case->encodedBy?->name ?? '-' }}</dd>
            </div>
        </dl>
    </section>

    <section class="list-card">
        <div class="list-card-header">
            <div>
                <div class="list-card-title">Payment Transactions</div>
                <div class="list-card-copy">Receipt-level payment activity recorded for this case.</div>
            </div>
        </div>
        <div class="table-wrapper rounded-none border-0">
            <table class="table-base text-sm">
                <thead>
                    <tr>
                        <th class="text-left">Receipt No.</th>
                        <th class="text-left">Method</th>
                        <th class="text-left">Amount</th>
                        <th class="text-left">Balance After Payment</th>
                        <th class="text-left">Status After Payment</th>
                        <th class="text-left">Paid Date/Time</th>
                        <th class="text-left">Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($funeral_case->payments as $payment)
                        <tr>
                            <td>{{ $payment->receipt_number ?? '-' }}</td>
                            <td>{{ $payment->method }}</td>
                            <td>{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ number_format((float) ($payment->balance_after_payment ?? 0), 2) }}</td>
                            <td>
                                <span class="{{
                                    ($payment->payment_status_after_payment ?? '') === 'PAID'
                                        ? 'status-pill-success'
                                        : ((($payment->payment_status_after_payment ?? '') === 'PARTIAL') ? 'status-pill-warning' : 'status-pill-danger')
                                }}">
                                    {{ $payment->payment_status_after_payment ?? '-' }}
                                </span>
                            </td>
                            <td>{{ $payment->paid_at?->format('Y-m-d H:i') ?? $payment->paid_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ $payment->recordedBy?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-500">No payment transactions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="flex flex-wrap gap-2">
        <a href="{{ $returnUrl }}" class="btn-outline">Back</a>
        @if($canRecordPayment)
            <a href="{{ route('payments.history', ['q' => $funeral_case->case_code]) }}" class="btn-outline">Payment History</a>
        @endif
    </div>
</div>

@if($canRecordPayment)
    <script>
        (function () {
            const openButton = document.getElementById('openCasePaymentForm');
            const closeButton = document.getElementById('closeCasePaymentForm');
            const panel = document.getElementById('casePaymentPanel');
            const amountInput = document.getElementById('case_amount_paid');
            const totalAmount = {{ json_encode((float) $funeral_case->total_amount) }};
            const currentPaid = {{ json_encode((float) $funeral_case->total_paid) }};
            const currentBalance = {{ json_encode((float) $funeral_case->balance_amount) }};
            const paymentDisplay = document.getElementById('case_new_payment_display');
            const totalPaidDisplay = document.getElementById('case_new_total_paid_display');
            const balanceDisplay = document.getElementById('case_new_balance_display');
            const statusDisplay = document.getElementById('case_new_status_display');

            function format(value) {
                return value.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function sanitizeAmount() {
                const raw = parseFloat(amountInput.value || '0');
                if (!Number.isFinite(raw) || raw <= 0) {
                    return 0;
                }

                return Math.min(raw, currentBalance);
            }

            function updatePreview() {
                const payment = sanitizeAmount();
                const newTotalPaid = Math.min(currentPaid + payment, totalAmount);
                const newBalance = Math.max(totalAmount - newTotalPaid, 0);
                let status = 'UNPAID';

                if (newTotalPaid > 0 && newBalance > 0) {
                    status = 'PARTIAL';
                } else if (newBalance <= 0 && totalAmount > 0) {
                    status = 'PAID';
                }

                paymentDisplay.textContent = format(payment);
                totalPaidDisplay.textContent = format(newTotalPaid);
                balanceDisplay.textContent = format(newBalance);
                statusDisplay.textContent = status;
            }

            function openPanel() {
                panel.classList.remove('hidden');
                if (amountInput) {
                    amountInput.focus();
                }
            }

            function closePanel() {
                panel.classList.add('hidden');
            }

            if (openButton) {
                openButton.addEventListener('click', openPanel);
            }

            if (closeButton) {
                closeButton.addEventListener('click', closePanel);
            }

            if (amountInput) {
                amountInput.addEventListener('input', updatePreview);
                updatePreview();
            }
        })();
    </script>
@endif
@endsection

