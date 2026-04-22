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

{{-- ── Official Document ── --}}
<div id="caseViewContent" class="print-container max-w-4xl mx-auto px-8 py-8 bg-white space-y-8 mt-6">

    {{-- Header --}}
    <div class="flex items-center gap-4 border-b-2 border-black pb-4">
        <img src="{{ asset('images/login-logo.png') }}" alt="Sabangan Caguioa Logo" class="h-14 w-auto">
        <div>
            <h1 class="text-2xl font-bold uppercase text-black tracking-wide">Sabangan Caguioa Funeral Home</h1>
            <p class="text-sm font-medium text-gray-500">Official Case Record</p>
        </div>
    </div>

    {{-- Case Code + Status --}}
    <div class="flex justify-between items-end border-b border-gray-300 pb-3">
        <div>
            <span class="text-[10px] uppercase font-bold text-gray-400 block mb-1">Case Code</span>
            <h2 class="text-3xl font-bold text-black uppercase tracking-tight">{{ $funeral_case->case_code }}</h2>
        </div>
        <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-gray-400 block mb-1">Branch &amp; Status</span>
            <div class="flex items-center gap-2 justify-end">
                <span class="text-sm font-semibold text-gray-700">{{ $funeral_case->branch?->branch_code ?? '—' }}</span>
                <span class="{{ in_array($funeral_case->case_status, ['DRAFT','ACTIVE']) ? 'status-pill-warning' : 'status-pill-success' }}">
                    {{ $funeral_case->case_status }}
                </span>
                <span class="{{ $funeral_case->payment_status === 'PAID' ? 'status-pill-success' : ($funeral_case->payment_status === 'PARTIAL' ? 'status-pill-warning' : 'status-pill-danger') }}">
                    {{ $funeral_case->payment_status }}
                </span>
            </div>
        </div>
    </div>

    {{-- Section 1: Client Information --}}
    <section>
        <h3 class="text-xs font-bold uppercase tracking-widest border-b border-black pb-1 mb-4">1. Client Information</h3>
        <div class="grid grid-cols-2 gap-x-12 gap-y-0.5">
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Full Name</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->client?->full_name ?? '—' }}</span>
            </div>
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Contact Number</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->client?->contact_number ?? '—' }}</span>
            </div>
            <div class="col-span-2 py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Address</span>
                <span class="text-sm font-bold italic text-right">{{ $funeral_case->client?->address ?? '—' }}</span>
            </div>
        </div>
    </section>

    {{-- Section 2: Deceased Information --}}
    <section>
        <h3 class="text-xs font-bold uppercase tracking-widest border-b border-black pb-1 mb-4">2. Deceased Information</h3>
        <div class="grid grid-cols-2 gap-x-12 gap-y-0.5">
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Full Name</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->deceased?->full_name ?? '—' }}</span>
            </div>
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Date of Birth</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->deceased?->born?->format('M d, Y') ?? '—' }}</span>
            </div>
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Date of Death</span>
                <span class="text-sm font-bold text-right">{{ ($funeral_case->deceased?->died ?? $funeral_case->deceased?->date_of_death)?->format('M d, Y') ?? '—' }}</span>
            </div>
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Age</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->deceased?->age ?? '—' }}</span>
            </div>
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Wake Days</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->deceased?->wake_days ?? '—' }}</span>
            </div>
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Cemetery</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->deceased?->place_of_cemetery ?? '—' }}</span>
            </div>
            @if($funeral_case->deceased?->address)
            <div class="col-span-2 py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Address</span>
                <span class="text-sm font-bold italic text-right">{{ $funeral_case->deceased->address }}</span>
            </div>
            @endif
        </div>
    </section>

    {{-- Section 3: Package --}}
    @php
        $pkg = $funeral_case->package;
        $pkgInclusions = $funeral_case->custom_package_inclusions ?: ($pkg?->inclusions ?? null);
        $pkgFreebies   = $funeral_case->custom_package_freebies   ?: ($pkg?->freebies   ?? null);
        $pkgPrice      = $funeral_case->custom_package_price      ?: ($pkg?->price       ?? null);
        $pkgCoffin     = $funeral_case->coffin_type               ?: ($pkg?->coffin_type ?? null);
    @endphp
    <section>
        <h3 class="text-xs font-bold uppercase tracking-widest border-b border-black pb-1 mb-4">3. Package</h3>
        <div class="grid grid-cols-2 gap-x-12 gap-y-0.5 mb-4">
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Package</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->service_package ?? $pkg?->name ?? '—' }}</span>
            </div>
            @if($funeral_case->custom_package_name)
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Custom Name</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->custom_package_name }}</span>
            </div>
            @endif
            @if($pkgCoffin)
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Coffin Type</span>
                <span class="text-sm font-bold text-right">{{ $pkgCoffin }}</span>
            </div>
            @endif
            @if($pkgPrice)
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Package Price</span>
                <span class="text-sm font-bold tabular-nums text-right">₱ {{ number_format((float) $pkgPrice, 2) }}</span>
            </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {{-- Inclusions --}}
            <div class="rounded-lg border border-slate-200 p-4">
                <div class="flex items-center gap-2 mb-3">
                    <i class="bi bi-check2-circle text-emerald-500 text-base"></i>
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-600">Inclusions</span>
                </div>
                @if($pkgInclusions)
                    <ul class="space-y-1.5">
                        @foreach(array_filter(array_map('trim', preg_split('/[\n,]+/', $pkgInclusions))) as $item)
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <i class="bi bi-dot text-emerald-400 text-lg leading-none mt-0.5 shrink-0"></i>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-slate-400 italic">Not yet configured for this package.</p>
                @endif
            </div>

            {{-- Freebies --}}
            <div class="rounded-lg border border-slate-200 p-4">
                <div class="flex items-center gap-2 mb-3">
                    <i class="bi bi-gift text-amber-500 text-base"></i>
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-600">Freebies</span>
                </div>
                @if($pkgFreebies)
                    <ul class="space-y-1.5">
                        @foreach(array_filter(array_map('trim', preg_split('/[\n,]+/', $pkgFreebies))) as $item)
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <i class="bi bi-dot text-amber-400 text-lg leading-none mt-0.5 shrink-0"></i>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-slate-400 italic">Not yet configured for this package.</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Section 4: Service Details --}}
    <section>
        <h3 class="text-xs font-bold uppercase tracking-widest border-b border-black pb-1 mb-4">4. Service Details</h3>
        <div class="grid grid-cols-2 gap-x-12 gap-y-0.5">
            @if($funeral_case->service_type)
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Service Type</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->service_type }}</span>
            </div>
            @endif
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Service Requested</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->service_requested_at?->format('M d, Y') ?? $funeral_case->created_at?->format('M d, Y') ?? '—' }}</span>
            </div>
            @if($funeral_case->wake_location)
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Wake Location</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->wake_location }}</span>
            </div>
            @endif
            @if($funeral_case->funeral_service_at)
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Funeral Service Date</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->funeral_service_at?->format('M d, Y') }}</span>
            </div>
            @endif
            @if($funeral_case->deceased?->interment_at ?? $funeral_case->deceased?->interment)
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Interment Date</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->deceased?->interment_at?->format('M d, Y H:i') ?? $funeral_case->deceased?->interment?->format('M d, Y') }}</span>
            </div>
            @endif
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Branch</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->branch?->branch_code ?? '—' }}</span>
            </div>
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Encoded By</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->encodedBy?->name ?? '—' }}</span>
            </div>
        </div>
        @if($funeral_case->additional_services)
        <div class="mt-3 rounded-lg border border-slate-200 px-4 py-3">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400 block mb-1">Additional Services</span>
                    <span class="text-sm font-semibold text-slate-800">{{ $funeral_case->additional_services }}</span>
                </div>
                @if($funeral_case->additional_service_amount)
                <div class="text-right shrink-0">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400 block mb-1">Amount</span>
                    <span class="text-sm font-bold tabular-nums">₱ {{ number_format((float) $funeral_case->additional_service_amount, 2) }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif
    </section>

    {{-- Section 5: Payment --}}
    <section>
        <h3 class="text-xs font-bold uppercase tracking-widest border-b border-black pb-1 mb-4">5. Payment</h3>

        {{-- Financial figures --}}
        <div class="grid grid-cols-2 gap-x-12 gap-y-0.5 mb-5">
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Subtotal</span>
                <span class="text-sm font-bold tabular-nums text-right">₱ {{ number_format((float) ($funeral_case->subtotal_amount ?? $funeral_case->total_amount), 2) }}</span>
            </div>
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Discount</span>
                <span class="text-sm font-bold tabular-nums text-right">₱ {{ number_format((float) ($funeral_case->discount_amount ?? 0), 2) }}{{ $funeral_case->discount_note ? ' — ' . $funeral_case->discount_note : '' }}</span>
            </div>
            @if($funeral_case->tax_amount)
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Tax ({{ $funeral_case->tax_rate }}%)</span>
                <span class="text-sm font-bold tabular-nums text-right">₱ {{ number_format((float) $funeral_case->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Total Amount</span>
                <span class="text-sm font-bold tabular-nums text-right text-black">₱ {{ number_format((float) $funeral_case->total_amount, 2) }}</span>
            </div>
            @if($funeral_case->paid_at)
            <div class="py-1.5 flex justify-between gap-4">
                <span class="text-sm text-gray-500 shrink-0">Last Payment Date</span>
                <span class="text-sm font-bold text-right">{{ $funeral_case->paid_at?->format('M d, Y h:i A') }}</span>
            </div>
            @endif
        </div>

        {{-- Summary tiles --}}
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="rounded-lg border border-gray-200 p-4 text-center">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Total Amount</div>
                <div class="text-xl font-bold text-black tabular-nums">₱ {{ number_format((float) $funeral_case->total_amount, 2) }}</div>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-center">
                <div class="text-[10px] font-bold uppercase tracking-widest text-emerald-500 mb-1">Total Paid</div>
                <div class="text-xl font-bold text-emerald-700 tabular-nums">₱ {{ number_format((float) $funeral_case->total_paid, 2) }}</div>
            </div>
            <div class="rounded-lg border {{ (float) $funeral_case->balance_amount > 0 ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50' }} p-4 text-center">
                <div class="text-[10px] font-bold uppercase tracking-widest {{ (float) $funeral_case->balance_amount > 0 ? 'text-red-500' : 'text-emerald-500' }} mb-1">Balance</div>
                <div class="text-xl font-bold tabular-nums {{ (float) $funeral_case->balance_amount > 0 ? 'text-red-700' : 'text-emerald-700' }}">₱ {{ number_format((float) $funeral_case->balance_amount, 2) }}</div>
            </div>
        </div>

        {{-- Payment transaction cards --}}
        @forelse($funeral_case->payments as $pmt)
            <div class="rounded-lg border border-slate-200 bg-white px-5 py-4 space-y-3 {{ !$loop->first ? 'mt-3' : '' }}">
                <div class="flex items-center justify-between gap-3">
                    <span class="font-mono text-sm font-bold text-slate-800">{{ $pmt->receipt_number ?? '—' }}</span>
                    <x-status-badge :status="$pmt->payment_status_after_payment ?? '—'" />
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-8 gap-y-2">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">Amount Paid</div>
                        <div class="text-base font-bold tabular-nums text-slate-900">₱ {{ number_format((float) $pmt->amount, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">Balance After</div>
                        <div class="text-base font-bold tabular-nums text-slate-700">₱ {{ number_format((float) ($pmt->balance_after_payment ?? 0), 2) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">Method</div>
                        <div class="text-sm font-semibold text-slate-700">{{ $pmt->method }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">Date / Time</div>
                        <div class="text-sm font-semibold text-slate-700">{{ $pmt->paid_at?->format('M d, Y H:i') ?? $pmt->paid_date?->format('M d, Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">Recorded By</div>
                        <div class="text-sm font-semibold text-slate-700">{{ $pmt->recordedBy?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 px-5 py-8 text-center text-slate-400 text-sm">
                No payment transactions recorded yet.
            </div>
        @endforelse
    </section>

    {{-- Signature line (print only) --}}
    <div class="mt-16 grid grid-cols-2 gap-20 print-only">
        <div class="text-center border-t border-black pt-2">
            <p class="text-xs font-bold uppercase">Staff Signature</p>
        </div>
        <div class="text-center border-t border-black pt-2">
            <p class="text-xs font-bold uppercase">Date Signed</p>
        </div>
    </div>

    {{-- Bottom Nav + Print button --}}
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 no-print">
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
        <button id="printCaseBtn" type="button" class="px-8 py-3 bg-black text-white font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors shadow-lg rounded">
            <i class="bi bi-printer mr-2"></i>Print Record
        </button>
    </div>

</div>

<style>
    .print-only { display: none; }
    @media print {
        nav, aside, footer, header, .no-print { display: none !important; }
        html, body { height: auto !important; overflow: visible !important; background: white !important; }
        #caseViewContent { max-width: 100% !important; margin: 0 !important; padding: 2rem !important; }
        .print-only { display: grid !important; }
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
            doc.write('</head><body>');
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
