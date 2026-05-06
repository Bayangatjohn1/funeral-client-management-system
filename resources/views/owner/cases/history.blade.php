@extends('layouts.panel')

@section('page_title', 'Global Case History')
@section('page_desc', 'Review completed case records across all branches.')

@section('content')
<div class="owner-page-shell">
@php
    $ownerHistoryChips = collect();
    if (filled($branchId ?? null)) {
        $ownerBranch = ($branches ?? collect())->firstWhere('id', (int) $branchId);
        $ownerHistoryChips->push([
            'icon' => 'bi-building',
            'label' => 'Branch: ' . ($ownerBranch ? trim(($ownerBranch->branch_code ?? '') . ' - ' . ($ownerBranch->branch_name ?? '')) : 'Selected Branch'),
        ]);
    }
    if (filled(request('q'))) {
        $ownerHistoryChips->push(['icon' => 'bi-search', 'label' => 'Search: ' . request('q')]);
    }
    if (filled(request('case_status'))) {
        $ownerHistoryChips->push(['icon' => 'bi-clipboard-check', 'label' => 'Case: ' . \Illuminate\Support\Str::headline(strtolower(request('case_status')))]);
    }
    if (filled(request('payment_status'))) {
        $ownerHistoryChips->push(['icon' => 'bi-wallet2', 'label' => 'Payment: ' . \Illuminate\Support\Str::headline(strtolower(request('payment_status')))]);
    }
    if (filled(request('service_type'))) {
        $ownerHistoryChips->push(['icon' => 'bi-tag', 'label' => 'Service: ' . request('service_type')]);
    }
    if (filled(request('package_id'))) {
        $selectedPackage = ($packages ?? collect())->firstWhere('id', (int) request('package_id'));
        $ownerHistoryChips->push(['icon' => 'bi-box', 'label' => 'Package: ' . ($selectedPackage?->name ?? 'Selected Package')]);
    }
    if (filled($datePreset ?? null)) {
        $ownerHistoryChips->push(['icon' => 'bi-calendar3', 'label' => 'Date: ' . \Illuminate\Support\Str::headline(strtolower((string) $datePreset))]);
    }
    if (filled($intermentFrom ?? null) || filled($intermentTo ?? null)) {
        $ownerHistoryChips->push(['icon' => 'bi-calendar-event', 'label' => 'Interment: ' . (($intermentFrom ?? null) ?: 'Start') . ' - ' . (($intermentTo ?? null) ?: 'Today')]);
    }
@endphp
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
    ])
</div>

<div class="case-records-master-chip-row owner-history-chip-row">
    <div class="case-compact-inline-chips case-records-quick-chips" aria-label="Applied branch and filters">
        @forelse($ownerHistoryChips as $chip)
            <span class="case-compact-chip">
                <i class="bi {{ $chip['icon'] }}"></i>{{ $chip['label'] }}
            </span>
        @empty
            <span class="case-compact-chip">
                <i class="bi bi-funnel"></i>All records
            </span>
        @endforelse
    </div>
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
<div id="caseViewOverlay" style="display:none; position:fixed; inset:0; z-index:400; background:rgba(0,0,0,0.55); backdrop-filter:blur(3px); -webkit-backdrop-filter:blur(3px); align-items:center; justify-content:center;">
    <div id="caseViewSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200"
         style="background:var(--card);border-color:var(--border);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface-panel);flex-shrink:0;">
            <span style="font-size:13px;font-weight:700;color:var(--ink);">Case Details</span>
            <button id="caseViewClose" type="button" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--ink-muted);cursor:pointer;" aria-label="Close">
                <i class="bi bi-x-lg" style="font-size:.75rem;"></i>
            </button>
        </div>
        <div id="caseViewContent" class="overflow-y-auto" style="max-height:calc(92vh - 54px);padding:16px;">
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 0;gap:10px;">
                <div style="width:28px;height:28px;border-radius:50%;border:2px solid var(--border);border-top-color:var(--brand);animation:spin 1s linear infinite;"></div>
                <span style="font-size:13px;color:var(--ink-muted);">Loading…</span>
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
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 0;gap:10px;">
            <div style="width:28px;height:28px;border-radius:50%;border:2px solid var(--border);border-top-color:var(--brand);animation:spin 1s linear infinite;"></div>
            <span style="font-size:13px;color:var(--ink-muted);">Loading…</span>
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
                content.innerHTML = `<div style="padding:20px;font-size:13px;color:#9E4B3F;">Unable to load case details.</div>`;
            }
        } catch {
            content.innerHTML = `<div style="padding:20px;font-size:13px;color:#9E4B3F;">Network error. Please try again.</div>`;
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
