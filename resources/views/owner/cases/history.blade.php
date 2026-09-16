@extends('layouts.panel')

@section('page_title', 'Master Case Records')
@section('page_desc', 'Review completed case records across all branches.')
@section('hide_layout_topbar', '1')

@section('content')
<div class="owner-page-shell">
@if($errors->any())
    <div class="flash-error">
        {{ $errors->first() }}
    </div>
@endif

<div class="filter-panel owner-history-filter-panel">
    @include('partials.case_filter_toolbar', [
        'action' => route('owner.history'),
        'resetUrl' => route('owner.history'),
        'branchMode' => 'all',
        'branchId' => $branchId,
        'branches' => $branches,
        'datePreset' => $datePreset ?? '',
        'dateFrom' => $dateFrom ?? null,
        'dateTo' => $dateTo ?? null,
        'intermentFrom' => $intermentFrom ?? null,
        'intermentTo' => $intermentTo ?? null,
        'serviceTypes' => $serviceTypes ?? collect(),
        'packages' => $packages ?? collect(),
        'showVerificationStatus' => false,
        'showPackage' => true,
        'showEncodedBy' => false,
        'showInlineChips' => false,
        'useDateDisplayLabel' => true,
    ])
</div>

<div class="list-card">
    <div class="table-wrapper rounded-none border-0">
        <table class="table-base table-system-table owner-history-table text-sm">
            <thead>
                <tr>
                    <th class="text-left">Case</th>
                    <th class="text-left">Branch</th>
                    <th class="text-left">Family / Client</th>
                    <th class="text-left">Service</th>
                    <th class="text-left">Interment</th>
                    <th class="text-left">Payment</th>
                    <th class="table-status-col text-left">Case Status</th>
                    <th class="table-status-col table-payment-status-col text-left">Payment Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                @php
                    $intermentDate = $case->deceased?->interment_at ?? $case->interment_at ?? $case->deceased?->interment;
                @endphp
                <tr
                    data-clickable-row
                    data-row-href="{{ route('owner.cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}"
                    tabindex="0"
                    role="link"
                    aria-label="Open full case details for {{ $case->case_code }}"
                >
                    <td>
                        <div class="table-primary whitespace-nowrap">{{ $case->case_code }}</div>
                        <div class="table-secondary">Encoded {{ $case->created_at?->format('M d, Y') }}</div>
                    </td>
                    <td>
                        <div class="table-primary whitespace-nowrap">{{ $case->branch?->branch_code ?? '-' }}</div>
                        <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->branch?->branch_name ?? '-', 24) }}</div>
                    </td>
                    <td>
                        <div class="table-primary">{{ \Illuminate\Support\Str::limit($case->deceased?->full_name ?? '-', 30) }}</div>
                        <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->client?->full_name ?? '-', 28) }}</div>
                    </td>
                    <td>
                        <div class="table-primary">{{ $case->service_type ?? '-' }}</div>
                        <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->package?->name ?? $case->service_package ?? '-', 30) }}</div>
                    </td>
                    <td>
                        <div class="table-primary whitespace-nowrap">{{ $intermentDate ? $intermentDate->format('M d, Y') : '-' }}</div>
                        <div class="table-secondary">{{ $intermentDate && $intermentDate->format('H:i') !== '00:00' ? $intermentDate->format('h:i A') : 'Service date' }}</div>
                    </td>
                    <td>
                        <div class="table-primary whitespace-nowrap">{{ number_format((float) $case->total_amount, 2) }}</div>
                        <div class="table-secondary whitespace-nowrap">Paid {{ number_format((float) $case->total_paid, 2) }} &middot; Bal {{ number_format((float) $case->balance_amount, 2) }}</div>
                    </td>
                    <td class="table-status-cell">
                        <x-status-badge :status="$case->case_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->case_status))" />
                    </td>
                    <td class="table-status-cell table-payment-status-cell">
                        <x-status-badge :status="$case->payment_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->payment_status))" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-6 text-center text-slate-500">No records found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    @if($cases->hasPages()){{ $cases->links() }}@endif
</div>

{{-- Case view modal --}}
<div id="caseViewOverlay" class="case-view-overlay">
    <div id="caseViewSheet" class="case-view-sheet relative w-[92vw] max-w-4xl max-h-[92vh] overflow-hidden transform transition-all duration-200 scale-95 opacity-0">
        <div class="case-view-head">
            <span class="case-view-title">Case Details</span>
            <button id="caseViewClose" type="button" class="case-view-close" aria-label="Close">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div id="caseViewContent" class="case-view-content overflow-y-auto">
            <div class="case-view-loading">
                <div class="case-view-spinner"></div>
                <span>Loading...</span>
            </div>
        </div>
    </div>
</div>

</div>

<script>
(function () {
    const overlay  = document.getElementById('caseViewOverlay');
    const sheet    = document.getElementById('caseViewSheet');
    const content  = document.getElementById('caseViewContent');
    const closeBtn = document.getElementById('caseViewClose');

    const loadingHtml = `
        <div class="case-view-loading">
            <div class="case-view-spinner"></div>
            <span>Loading...</span>
        </div>`;

    const show = () => {
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => {
            sheet.classList.remove('scale-95', 'opacity-0');
            sheet.classList.add('scale-100', 'opacity-100');
        });
    };

    const hide = () => {
        sheet.classList.add('scale-95', 'opacity-0');
        sheet.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
            content.innerHTML = loadingHtml;
        }, 180);
    };

    const load = async (url) => {
        content.innerHTML = loadingHtml;
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const payload = doc.querySelector('#caseViewContent');
            if (payload) {
                content.innerHTML = payload.innerHTML;
                doc.querySelectorAll('script').forEach(oldScript => {
                    const script = document.createElement('script');
                    script.textContent = oldScript.textContent;
                    content.appendChild(script);
                });
            } else {
                content.innerHTML = `<div class="case-view-error">Unable to load case details.</div>`;
            }
        } catch {
            content.innerHTML = `<div class="case-view-error">Network error. Please try again.</div>`;
        }
    };

    document.querySelectorAll('.open-case-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            show();
            load(btn.dataset.url);
        });
    });

    closeBtn.addEventListener('click', hide);
    overlay.addEventListener('click', e => { if (e.target === overlay) hide(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.style.display !== 'none') hide(); });
})();
</script>
@endsection
