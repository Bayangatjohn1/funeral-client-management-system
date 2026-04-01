@extends('layouts.panel')

@section('page_title', 'Owner Case Details')

@section('content')
<div class="space-y-6">
    <section class="detail-hero">
        <div class="detail-overline">Read-only Case Details</div>
        <h2 class="detail-title">Case {{ $funeral_case->case_code }}</h2>
        <p class="detail-copy">Owner view of full case information, financial summary, and transaction activity.</p>

        <div class="detail-chip-row">
            <span class="detail-chip">Service Date: {{ $funeral_case->service_requested_at?->format('Y-m-d') ?? $funeral_case->created_at?->format('Y-m-d') }}</span>
            <span class="detail-chip">Branch: {{ $funeral_case->branch?->branch_code }} - {{ $funeral_case->branch?->branch_name }}</span>
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

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="detail-section">
            <div class="detail-section-title">Client Information</div>
            <div class="detail-field-grid">
                <div class="detail-field">
                    <div class="detail-field-label">Name</div>
                    <div class="detail-field-value">{{ $funeral_case->client?->full_name ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Contact</div>
                    <div class="detail-field-value">{{ $funeral_case->client?->contact_number ?? '-' }}</div>
                </div>
                <div class="detail-field sm:col-span-2">
                    <div class="detail-field-label">Address</div>
                    <div class="detail-field-value">{{ $funeral_case->client?->address ?? '-' }}</div>
                </div>
            </div>
        </section>

        <section class="detail-section">
            <div class="detail-section-title">Deceased Information</div>
            <div class="detail-field-grid">
                <div class="detail-field">
                    <div class="detail-field-label">Name</div>
                    <div class="detail-field-value">{{ $funeral_case->deceased?->full_name ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Born</div>
                    <div class="detail-field-value">{{ $funeral_case->deceased?->born?->format('Y-m-d') ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Died</div>
                    <div class="detail-field-value">{{ ($funeral_case->deceased?->died ?? $funeral_case->deceased?->date_of_death)?->format('Y-m-d') ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Interment</div>
                    <div class="detail-field-value">{{ $funeral_case->deceased?->interment_at?->format('Y-m-d H:i') ?? $funeral_case->deceased?->interment?->format('Y-m-d') ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Wake Days</div>
                    <div class="detail-field-value">{{ $funeral_case->deceased?->wake_days ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Place of Cemetery</div>
                    <div class="detail-field-value">{{ $funeral_case->deceased?->place_of_cemetery ?? '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Coffin Measurement</div>
                    <div class="detail-field-value">{{ $funeral_case->deceased?->coffin_length_cm ? number_format((float) $funeral_case->deceased?->coffin_length_cm, 2) . ' cm' : '-' }}</div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Coffin Size</div>
                    <div class="detail-field-value">{{ $funeral_case->deceased?->coffin_size ?? '-' }}</div>
                </div>
            </div>
        </section>
    </div>

    <section class="detail-section">
        <div class="detail-section-title">Financial Summary</div>
        <div class="detail-stat-grid">
            <div class="detail-stat">
                <div class="detail-stat-label">Package</div>
                <div class="detail-stat-value">{{ $funeral_case->service_package ?? '-' }}</div>
            </div>
            <div class="detail-stat">
                <div class="detail-stat-label">Subtotal</div>
                <div class="detail-stat-value">{{ number_format($funeral_case->subtotal_amount ?? $funeral_case->total_amount, 2) }}</div>
            </div>
            <div class="detail-stat">
                <div class="detail-stat-label">Discount</div>
                <div class="detail-stat-value">{{ number_format($funeral_case->discount_amount ?? 0, 2) }}</div>
            </div>
            <div class="detail-stat">
                <div class="detail-stat-label">Discount Rule</div>
                <div class="detail-stat-value">{{ $funeral_case->discount_note ?? 'None' }}</div>
            </div>
            <div class="detail-stat">
                <div class="detail-stat-label">Total</div>
                <div class="detail-stat-value">{{ number_format($funeral_case->total_amount, 2) }}</div>
            </div>
            <div class="detail-stat">
                <div class="detail-stat-label">Total Paid</div>
                <div class="detail-stat-value">{{ number_format((float) $funeral_case->total_paid, 2) }}</div>
            </div>
            <div class="detail-stat">
                <div class="detail-stat-label">Balance</div>
                <div class="detail-stat-value">{{ number_format((float) $funeral_case->balance_amount, 2) }}</div>
            </div>
        </div>
    </section>

    <section class="detail-section">
        <div class="detail-section-title">Record Source</div>
        <div class="detail-field-grid">
            <div class="detail-field">
                <div class="detail-field-label">Reported Branch</div>
                <div class="detail-field-value">{{ $funeral_case->reportedBranch?->branch_code ?? $funeral_case->branch?->branch_code ?? '-' }}</div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Reporter Name</div>
                <div class="detail-field-value">{{ $funeral_case->reporter_name ?? '-' }}</div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Reporter Contact</div>
                <div class="detail-field-value">{{ $funeral_case->reporter_contact ?? '-' }}</div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Reported At</div>
                <div class="detail-field-value">{{ $funeral_case->reported_at?->format('Y-m-d H:i') ?? '-' }}</div>
            </div>
            <div class="detail-field sm:col-span-2">
                <div class="detail-field-label">Encoded By</div>
                <div class="detail-field-value">{{ $funeral_case->encodedBy?->name ?? '-' }}</div>
            </div>
        </div>
    </section>

    <section class="list-card">
        <div class="list-card-header">
            <div>
                <div class="list-card-title">Payment Transactions</div>
                <div class="list-card-copy">Read-only transaction history recorded under this case.</div>
            </div>
        </div>
        <div class="table-wrapper rounded-none border-0">
            <table class="table-base text-sm">
                <thead>
                    <tr>
                        <th class="text-left">Method</th>
                        <th class="text-left">Amount</th>
                        <th class="text-left">Paid Date/Time</th>
                        <th class="text-left">Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($funeral_case->payments as $payment)
                        <tr>
                            <td>{{ $payment->method }}</td>
                            <td>{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->paid_at?->format('Y-m-d H:i') ?? $payment->paid_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ $payment->recordedBy?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-500">No payment transactions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="flex gap-2">
        <a href="{{ route('owner.history') }}" class="btn-outline">Back to Global Case History</a>
    </div>
</div>
@endsection

